const db = require("../db/db");
const { generarFacturaXmlDesdeVenta } = require("./sri-factura.service");
const { firmarFacturaXmlDesdeVenta } = require("./sri-firma.service");
const {
  enviarFacturaFirmadaSriDesdeVenta,
  consultarAutorizacionFacturaSriDesdeVenta
} = require("./sri-ws.service");
const {
  generarRideFacturaSriDesdeVenta,
  enviarFacturaSriPorCorreoDesdeVenta
} = require("./sri-ride.service");
const { ensureSriTables } = require("./sri-config.service");
const { createError } = require("./sri-certificado.service");

const PASOS = ["xml", "firmar", "enviar", "autorizar", "ride", "email"];
const REINTENTOS_MS = [15000, 30000, 60000, 120000, 300000, 600000];
const MAX_INTENTOS = REINTENTOS_MS.length;

let workerStarted = false;
let processing = false;

function safeJsonParse(value, fallback = {}) {
  if (!value) return fallback;

  try {
    return typeof value === "string" ? JSON.parse(value) : value;
  } catch {
    return fallback;
  }
}

function normalizePaso(paso) {
  const normalized = String(paso || "").trim().toLowerCase();
  return PASOS.includes(normalized) ? normalized : "xml";
}

function siguientePaso(paso, payload = {}) {
  switch (paso) {
    case "xml":
      return "firmar";
    case "firmar":
      return "enviar";
    case "enviar":
      return "autorizar";
    case "autorizar":
      return payload.correo_destino ? "email" : "ride";
    case "ride":
      return payload.correo_destino ? "email" : null;
    case "email":
    default:
      return null;
  }
}

function esErrorSriTransitorio(error) {
  const raw = String(error?.message || error || "");
  const text = raw.toUpperCase();

  return [
    "HTTP 302",
    "302",
    "BAD GATEWAY",
    "502",
    "504",
    "TIMEOUT",
    "ETIMEDOUT",
    "ECONNRESET",
    "ECONNREFUSED",
    "ENOTFOUND",
    "EAI_AGAIN",
    "FETCH FAILED",
    "NO SE PUDO CONECTAR",
    "NO RESPONDIO",
    "SOCKET HANG UP",
    "TEMPORALMENTE"
  ].some((pattern) => text.includes(pattern));
}

function delayParaIntento(intentos) {
  const index = Math.min(Math.max(Number(intentos || 0), 0), REINTENTOS_MS.length - 1);
  return REINTENTOS_MS[index];
}

function mysqlDateFromNow(ms) {
  const date = new Date(Date.now() + ms);
  const pad = (value) => String(value).padStart(2, "0");

  return [
    date.getFullYear(),
    "-",
    pad(date.getMonth() + 1),
    "-",
    pad(date.getDate()),
    " ",
    pad(date.getHours()),
    ":",
    pad(date.getMinutes()),
    ":",
    pad(date.getSeconds())
  ].join("");
}

async function ensureSriRetryTables() {
  await ensureSriTables();

  await db.query(`
    CREATE TABLE IF NOT EXISTS sri_reintentos (
      id_reintento_sri INT(11) NOT NULL AUTO_INCREMENT,
      id_venta INT(11) NOT NULL,
      paso_actual ENUM('xml','firmar','enviar','autorizar','ride','email') NOT NULL DEFAULT 'xml',
      estado ENUM('PENDIENTE','PROCESANDO','COMPLETADO','FALLIDO') NOT NULL DEFAULT 'PENDIENTE',
      intentos INT(11) NOT NULL DEFAULT 0,
      max_intentos INT(11) NOT NULL DEFAULT ${MAX_INTENTOS},
      siguiente_intento_en DATETIME NOT NULL,
      ultimo_error TEXT DEFAULT NULL,
      payload_json LONGTEXT DEFAULT NULL,
      creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id_reintento_sri),
      UNIQUE KEY uk_sri_reintento_venta (id_venta),
      KEY idx_sri_reintento_estado_fecha (estado, siguiente_intento_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
  `);

  await db.query(`
    UPDATE sri_reintentos
    SET estado = 'PENDIENTE',
        siguiente_intento_en = NOW()
    WHERE estado = 'PROCESANDO'
      AND actualizado_en < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
  `);
}

async function encolarReintentoSri({
  id_venta,
  paso = "xml",
  motivo = null,
  payload = {},
  delayMs = 15000
}) {
  await ensureSriRetryTables();

  const idVenta = Number(id_venta || 0);
  if (!idVenta) {
    throw createError("Debes indicar un id_venta valido para reintentar SRI");
  }

  const pasoActual = normalizePaso(paso);
  const siguienteIntento = mysqlDateFromNow(delayMs);

  await db.query(`
    INSERT INTO sri_reintentos (
      id_venta,
      paso_actual,
      estado,
      intentos,
      max_intentos,
      siguiente_intento_en,
      ultimo_error,
      payload_json
    ) VALUES (?, ?, 'PENDIENTE', 0, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      paso_actual = VALUES(paso_actual),
      estado = 'PENDIENTE',
      siguiente_intento_en = VALUES(siguiente_intento_en),
      ultimo_error = VALUES(ultimo_error),
      payload_json = VALUES(payload_json)
  `, [
    idVenta,
    pasoActual,
    MAX_INTENTOS,
    siguienteIntento,
    motivo ? String(motivo).slice(0, 1000) : null,
    JSON.stringify(payload || {})
  ]);

  return {
    id_venta: idVenta,
    paso_actual: pasoActual,
    estado: "PENDIENTE",
    siguiente_intento_en: siguienteIntento
  };
}

async function ejecutarPasoSri(idVenta, paso, payload) {
  switch (paso) {
    case "xml":
      await generarFacturaXmlDesdeVenta({ id_venta: idVenta });
      return { siguiente: "firmar" };
    case "firmar":
      await firmarFacturaXmlDesdeVenta({ id_venta: idVenta });
      return { siguiente: "enviar" };
    case "enviar": {
      const envio = await enviarFacturaFirmadaSriDesdeVenta({ id_venta: idVenta });
      if (envio?.estado === "RECHAZADO" && String(envio?.error_codigo || "") !== "43") {
        const error = new Error(envio?.error_detalle || "El SRI rechazo la recepcion");
        error.retryable = false;
        throw error;
      }
      return { siguiente: "autorizar" };
    }
    case "autorizar": {
      const autorizacion = await consultarAutorizacionFacturaSriDesdeVenta({ id_venta: idVenta });
      if (autorizacion?.data?.pendiente_autorizacion || autorizacion?.pendiente_autorizacion) {
        const error = new Error("El SRI aun no devuelve autorizacion");
        error.retryable = true;
        throw error;
      }
      if (autorizacion?.estado !== "AUTORIZADO") {
        const error = new Error(autorizacion?.error_detalle || "La factura no fue autorizada");
        error.retryable = false;
        throw error;
      }
      return { siguiente: siguientePaso("autorizar", payload) };
    }
    case "ride":
      await generarRideFacturaSriDesdeVenta({ id_venta: idVenta });
      return { siguiente: siguientePaso("ride", payload) };
    case "email":
      if (payload.correo_destino) {
        await enviarFacturaSriPorCorreoDesdeVenta({
          id_venta: idVenta,
          correo_destino: payload.correo_destino
        });
      }
      return { siguiente: null };
    default:
      return { siguiente: null };
  }
}

async function marcarCompletado(idReintento) {
  await db.query(`
    UPDATE sri_reintentos
    SET estado = 'COMPLETADO',
        ultimo_error = NULL
    WHERE id_reintento_sri = ?
  `, [idReintento]);
}

async function programarSiguienteIntento(job, error) {
  const intentos = Number(job.intentos || 0) + 1;
  const agotado = intentos >= Number(job.max_intentos || MAX_INTENTOS);
  const retryable = error?.retryable === true || esErrorSriTransitorio(error);
  const estado = !retryable || agotado ? "FALLIDO" : "PENDIENTE";
  const siguienteIntento = mysqlDateFromNow(delayParaIntento(intentos));

  await db.query(`
    UPDATE sri_reintentos
    SET estado = ?,
        intentos = ?,
        siguiente_intento_en = ?,
        ultimo_error = ?
    WHERE id_reintento_sri = ?
  `, [
    estado,
    intentos,
    siguienteIntento,
    String(error?.message || error || "Error SRI").slice(0, 1000),
    job.id_reintento_sri
  ]);
}

async function procesarJob(job) {
  const payload = safeJsonParse(job.payload_json, {});

  await db.query(`
    UPDATE sri_reintentos
    SET estado = 'PROCESANDO'
    WHERE id_reintento_sri = ?
  `, [job.id_reintento_sri]);

  try {
    let paso = normalizePaso(job.paso_actual);

    while (paso) {
      const result = await ejecutarPasoSri(Number(job.id_venta), paso, payload);
      paso = result.siguiente;

      if (paso) {
        await db.query(`
          UPDATE sri_reintentos
          SET paso_actual = ?,
              ultimo_error = NULL
          WHERE id_reintento_sri = ?
        `, [paso, job.id_reintento_sri]);
      }
    }

    await marcarCompletado(job.id_reintento_sri);
  } catch (error) {
    await programarSiguienteIntento(job, error);
  }
}

async function procesarReintentosSri({ limit = 5 } = {}) {
  if (processing) return;
  processing = true;

  try {
    await ensureSriRetryTables();

    const [jobs] = await db.query(`
      SELECT *
      FROM sri_reintentos
      WHERE estado = 'PENDIENTE'
        AND siguiente_intento_en <= NOW()
      ORDER BY siguiente_intento_en ASC, id_reintento_sri ASC
      LIMIT ?
    `, [limit]);

    for (const job of jobs) {
      await procesarJob(job);
    }
  } catch (error) {
    console.error("❌ procesarReintentosSri:", error);
  } finally {
    processing = false;
  }
}

function iniciarWorkerReintentosSri() {
  if (String(process.env.SRI_RETRY_WORKER || "true").toLowerCase() === "false") {
    console.log("ℹ️ Worker de reintentos SRI desactivado por SRI_RETRY_WORKER=false");
    return;
  }

  if (workerStarted) return;
  workerStarted = true;

  ensureSriRetryTables().catch((error) => {
    console.error("❌ ensureSriRetryTables:", error);
  });

  setInterval(() => {
    procesarReintentosSri().catch((error) => {
      console.error("❌ worker SRI:", error);
    });
  }, 15000);
}

module.exports = {
  encolarReintentoSri,
  esErrorSriTransitorio,
  iniciarWorkerReintentosSri,
  procesarReintentosSri
};
