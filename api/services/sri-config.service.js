const crypto = require("crypto");
const fs = require("fs");
const path = require("path");
const db = require("../db/db");
const {
  createError,
  probarCertificadoPkcs12,
  resolveCertificatePath
} = require("./sri-certificado.service");

const UPLOADS_SRI_DIR = path.resolve(__dirname, "../uploads/sri-certificados");

function getCipherKey() {
  const secret =
    process.env.SRI_CONFIG_SECRET ||
    process.env.JWT_SECRET ||
    "CONNECT_2026_SRI_CONFIG_SECRET";

  return crypto.createHash("sha256").update(String(secret)).digest();
}

function encryptValue(value) {
  if (value === null || value === undefined || value === "") return null;

  const iv = crypto.randomBytes(16);
  const cipher = crypto.createCipheriv("aes-256-cbc", getCipherKey(), iv);
  let encrypted = cipher.update(String(value), "utf8", "hex");
  encrypted += cipher.final("hex");

  return `${iv.toString("hex")}:${encrypted}`;
}

function decryptValue(value) {
  if (!value) return null;

  if (!String(value).includes(":")) {
    return String(value);
  }

  try {
    const [ivHex, encryptedHex] = String(value).split(":");
    const decipher = crypto.createDecipheriv(
      "aes-256-cbc",
      getCipherKey(),
      Buffer.from(ivHex, "hex")
    );

    let decrypted = decipher.update(encryptedHex, "hex", "utf8");
    decrypted += decipher.final("utf8");

    return decrypted;
  } catch (error) {
    console.error("⚠️ No se pudo descifrar clave SRI:", error.message);
    return null;
  }
}

function safeTrim(value, fallback = null) {
  if (value === undefined || value === null) return fallback;
  const text = String(value).trim();
  return text || fallback;
}

function normalizeAmbiente(value) {
  const normalized = safeTrim(value, "PRUEBAS").toUpperCase();
  return normalized === "PRODUCCION" ? "PRODUCCION" : "PRUEBAS";
}

function normalizeObligado(value) {
  const normalized = safeTrim(value, "NO").toUpperCase();
  return ["SI", "SÍ"].includes(normalized) ? "SI" : "NO";
}

function padCode(value, length = 3) {
  return String(value || "").replace(/\D/g, "").padStart(length, "0").slice(-length);
}

function toMysqlDateTime(value) {
  if (!value) return null;

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return null;

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  const hour = String(date.getHours()).padStart(2, "0");
  const minute = String(date.getMinutes()).padStart(2, "0");
  const second = String(date.getSeconds()).padStart(2, "0");

  return `${year}-${month}-${day} ${hour}:${minute}:${second}`;
}

function maybeDeleteStoredCert(filePath) {
  if (!filePath) return;
  if (!fs.existsSync(filePath)) return;

  const normalized = path.resolve(filePath);

  if (!normalized.startsWith(UPLOADS_SRI_DIR)) {
    return;
  }

  try {
    fs.unlinkSync(normalized);
  } catch (error) {
    console.error("⚠️ No se pudo eliminar certificado anterior:", error.message);
  }
}

function mapLocalRow(row) {
  if (!row) return null;

  return {
    id_local: Number(row.id_local),
    nombre_local: row.nombre_local,
    direccion: row.direccion,
    telefono: row.telefono,
    activo: row.activo === undefined ? true : Number(row.activo || 0) === 1,
    id_local_sri_maestro: row.id_local_sri_maestro ? Number(row.id_local_sri_maestro) : null,
    nombre_local_sri_maestro: row.nombre_local_sri_maestro || null,
    creado_en: row.creado_en || row.fecha_creacion || null,
    actualizado_en: row.actualizado_en || null
  };
}

function mapConfigRow(row) {
  if (!row) return null;

  return {
    id_config_sri: row.id_config_sri,
    id_local: row.id_local,
    ruc: row.ruc,
    razon_social: row.razon_social,
    nombre_comercial: row.nombre_comercial,
    dir_matriz: row.dir_matriz,
    dir_establecimiento: row.dir_establecimiento,
    establecimiento: row.establecimiento,
    punto_emision: row.punto_emision,
    obligado_contabilidad: row.obligado_contabilidad,
    contribuyente_especial: row.contribuyente_especial,
    ambiente: row.ambiente,
    telefono: row.telefono,
    correo_notificacion: row.correo_notificacion,
    certificado_path: row.certificado_path,
    certificado_archivo: row.certificado_path ? path.basename(row.certificado_path) : null,
    certificado_subject: row.certificado_subject,
    certificado_issuer: row.certificado_issuer,
    certificado_fingerprint_sha256: row.certificado_fingerprint_sha256,
    certificado_valido_hasta: row.certificado_valido_hasta,
    tiene_clave_certificado: Boolean(decryptValue(row.clave_certificado_encrypted)),
    activo: Number(row.activo || 0) === 1,
    creado_en: row.creado_en,
    actualizado_en: row.actualizado_en
  };
}

async function ensureLocalesSriMasterColumn() {
  let columnAdded = false;

  try {
    await db.query(`
      ALTER TABLE locales
      ADD COLUMN id_local_sri_maestro INT(11) NULL DEFAULT NULL AFTER telefono
    `);
    columnAdded = true;
  } catch (error) {
    if (error.code !== "ER_DUP_FIELDNAME") {
      throw error;
    }
  }

  try {
    await db.query(`
      ALTER TABLE locales
      ADD INDEX idx_local_sri_maestro (id_local_sri_maestro)
    `);
  } catch (error) {
    if (error.code !== "ER_DUP_KEYNAME") {
      throw error;
    }
  }

  if (columnAdded) {
    const [defaultRows] = await db.query(`
      SELECT id_local, id_local_sri_maestro
      FROM locales
      WHERE id_local IN (3, 4, 5)
    `);

    const hasLocal3 = defaultRows.some((row) => Number(row.id_local) === 3);

    if (hasLocal3) {
      await db.query(`
        UPDATE locales
        SET id_local_sri_maestro = 3
        WHERE id_local IN (4, 5)
          AND id_local_sri_maestro IS NULL
      `);
    }
  }
}

async function ensureSriTables() {
  await ensureLocalesSriMasterColumn();

  await db.query(`
    CREATE TABLE IF NOT EXISTS sri_configuraciones (
      id_config_sri INT(11) NOT NULL AUTO_INCREMENT,
      id_local INT(11) NOT NULL,
      ruc VARCHAR(13) NOT NULL,
      razon_social VARCHAR(255) NOT NULL,
      nombre_comercial VARCHAR(255) DEFAULT NULL,
      dir_matriz VARCHAR(255) NOT NULL,
      dir_establecimiento VARCHAR(255) NOT NULL,
      establecimiento VARCHAR(3) NOT NULL,
      punto_emision VARCHAR(3) NOT NULL,
      obligado_contabilidad ENUM('SI','NO') NOT NULL DEFAULT 'NO',
      contribuyente_especial VARCHAR(30) DEFAULT NULL,
      ambiente ENUM('PRUEBAS','PRODUCCION') NOT NULL DEFAULT 'PRUEBAS',
      telefono VARCHAR(20) DEFAULT NULL,
      correo_notificacion VARCHAR(150) DEFAULT NULL,
      certificado_path VARCHAR(255) DEFAULT NULL,
      clave_certificado_encrypted TEXT DEFAULT NULL,
      certificado_subject VARCHAR(700) DEFAULT NULL,
      certificado_issuer VARCHAR(700) DEFAULT NULL,
      certificado_fingerprint_sha256 VARCHAR(255) DEFAULT NULL,
      certificado_valido_hasta DATETIME DEFAULT NULL,
      activo TINYINT(1) NOT NULL DEFAULT 1,
      creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id_config_sri),
      UNIQUE KEY uk_sri_config_local (id_local),
      KEY fk_sri_config_local (id_local),
      CONSTRAINT fk_sri_config_local
        FOREIGN KEY (id_local) REFERENCES locales(id_local)
        ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
  `);

  await db.query(`
    CREATE TABLE IF NOT EXISTS sri_documentos (
      id_documento_sri INT(11) NOT NULL AUTO_INCREMENT,
      id_local INT(11) NOT NULL,
      id_venta INT(11) NOT NULL,
      tipo_comprobante ENUM('FACTURA','NOTA_CREDITO','NOTA_DEBITO','GUIA_REMISION','RETENCION') NOT NULL DEFAULT 'FACTURA',
      clave_acceso VARCHAR(49) DEFAULT NULL,
      estado ENUM('BORRADOR','XML_GENERADO','FIRMADO','ENVIADO','RECIBIDO','AUTORIZADO','RECHAZADO','ERROR') NOT NULL DEFAULT 'BORRADOR',
      ambiente ENUM('PRUEBAS','PRODUCCION') NOT NULL DEFAULT 'PRUEBAS',
      xml_generado_path VARCHAR(255) DEFAULT NULL,
      xml_firmado_path VARCHAR(255) DEFAULT NULL,
      xml_autorizado_path VARCHAR(255) DEFAULT NULL,
      ride_path VARCHAR(255) DEFAULT NULL,
      numero_autorizacion VARCHAR(100) DEFAULT NULL,
      fecha_autorizacion DATETIME DEFAULT NULL,
      respuesta_sri_json LONGTEXT DEFAULT NULL,
      error_codigo VARCHAR(60) DEFAULT NULL,
      error_detalle TEXT DEFAULT NULL,
      creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id_documento_sri),
      UNIQUE KEY uk_sri_documento_venta_tipo (id_venta, tipo_comprobante),
      UNIQUE KEY uk_sri_documento_clave (clave_acceso),
      KEY idx_sri_documento_local (id_local),
      KEY idx_sri_documento_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
  `);
}

async function findLocalById(idLocal) {
  await ensureLocalesSriMasterColumn();

  const [[local]] = await db.query(`
    SELECT
      l.id_local,
      l.nombre_local,
      l.direccion,
      l.telefono,
      l.activo,
      l.id_local_sri_maestro,
      ml.nombre_local AS nombre_local_sri_maestro
    FROM locales l
    LEFT JOIN locales ml
      ON ml.id_local = l.id_local_sri_maestro
    WHERE l.id_local = ?
    LIMIT 1
  `, [idLocal]);

  return mapLocalRow(local);
}

async function listActiveLocales() {
  await ensureLocalesSriMasterColumn();

  const [rows] = await db.query(`
    SELECT
      l.id_local,
      l.nombre_local,
      l.direccion,
      l.telefono,
      l.activo,
      l.id_local_sri_maestro,
      ml.nombre_local AS nombre_local_sri_maestro
    FROM locales l
    LEFT JOIN locales ml
      ON ml.id_local = l.id_local_sri_maestro
    WHERE l.activo = 1
    ORDER BY l.id_local ASC
  `);

  return rows.map(mapLocalRow);
}

async function getRawSriConfig(idLocal) {
  const [[row]] = await db.query(`
    SELECT *
    FROM sri_configuraciones
    WHERE id_local = ?
    LIMIT 1
  `, [idLocal]);

  return row || null;
}

function normalizeSriMasterLocalId(value) {
  const number = Number(value || 0);
  return number > 0 ? number : null;
}

async function validateSriMasterLink(idLocal, idLocalSriMaestro) {
  await ensureSriTables();

  const normalizedMasterId = normalizeSriMasterLocalId(idLocalSriMaestro);

  if (!normalizedMasterId) {
    return null;
  }

  if (Number(idLocal || 0) === normalizedMasterId) {
    throw createError("Un local no puede usarse a si mismo como maestro SRI");
  }

  const masterLocal = await findLocalById(normalizedMasterId);

  if (!masterLocal) {
    throw createError("El local maestro SRI indicado no existe");
  }

  if (!masterLocal.activo) {
    throw createError("El local maestro SRI indicado está inactivo");
  }

  if (masterLocal.id_local_sri_maestro) {
    throw createError("No puedes elegir como maestro un local que ya hereda configuración SRI de otro local");
  }

  return normalizedMasterId;
}

async function resolveSriContext(idLocal) {
  await ensureSriTables();

  const localOperativo = await findLocalById(idLocal);

  if (!localOperativo) {
    throw createError("El local indicado no existe");
  }

  const usaConfiguracionCompartida = Boolean(localOperativo.id_local_sri_maestro);
  const idLocalSriMaestroEfectivo = usaConfiguracionCompartida
    ? Number(localOperativo.id_local_sri_maestro)
    : Number(localOperativo.id_local);

  const localSriMaestro = usaConfiguracionCompartida
    ? await findLocalById(idLocalSriMaestroEfectivo)
    : localOperativo;

  if (!localSriMaestro) {
    throw createError("El local maestro SRI configurado ya no existe");
  }

  if (!localSriMaestro.activo) {
    throw createError("El local maestro SRI configurado está inactivo");
  }

  if (localSriMaestro.id_local_sri_maestro) {
    throw createError("La configuración SRI del local maestro es inválida porque apunta a otro local");
  }

  const rawConfig = await getRawSriConfig(idLocalSriMaestroEfectivo);
  const config = mapConfigRow(rawConfig);

  return {
    id_local_operativo: Number(localOperativo.id_local),
    id_local_sri_maestro_efectivo: idLocalSriMaestroEfectivo,
    usa_configuracion_compartida: usaConfiguracionCompartida,
    local_operativo: localOperativo,
    local_sri_maestro: localSriMaestro,
    config,
    config_internal: rawConfig
      ? {
          ...config,
          clave_certificado: decryptValue(rawConfig.clave_certificado_encrypted)
        }
      : null
  };
}

async function getSriConfig(idLocal) {
  const context = await resolveSriContext(idLocal);

  if (!context.config) {
    return null;
  }

  return {
    ...context.config,
    id_local_operativo: context.id_local_operativo,
    id_local_config: context.id_local_sri_maestro_efectivo,
    usa_configuracion_compartida: context.usa_configuracion_compartida,
    id_local_sri_maestro_efectivo: context.id_local_sri_maestro_efectivo,
    local_operativo: context.local_operativo,
    local_sri_maestro: context.local_sri_maestro,
    editable: !context.usa_configuracion_compartida
  };
}

async function getSriConfigInternal(idLocal) {
  const context = await resolveSriContext(idLocal);

  if (!context.config_internal) return null;

  return {
    ...context.config_internal,
    id_local_operativo: context.id_local_operativo,
    id_local_config: context.id_local_sri_maestro_efectivo,
    usa_configuracion_compartida: context.usa_configuracion_compartida,
    id_local_sri_maestro_efectivo: context.id_local_sri_maestro_efectivo,
    local_operativo: context.local_operativo,
    local_sri_maestro: context.local_sri_maestro,
    editable: !context.usa_configuracion_compartida
  };
}

async function saveSriConfig(payload) {
  await ensureSriTables();

  const idLocal = Number(payload.id_local || 0);
  if (!idLocal) {
    throw createError("Debes indicar id_local");
  }

  const local = await findLocalById(idLocal);
  if (!local) {
    const locales = await listActiveLocales();
    const disponibles = locales.map(item => item.id_local).join(", ");

    throw createError(
      disponibles
        ? `El local indicado no existe. Locales disponibles: ${disponibles}`
        : "El local indicado no existe y no hay locales activos registrados"
    );
  }

  if (local.id_local_sri_maestro) {
    throw createError(
      `El local ${idLocal} usa la configuración SRI del local ${local.id_local_sri_maestro}. Debes editar el local maestro.`
    );
  }

  const existing = await getRawSriConfig(idLocal);
  const decryptedExistingPassword = existing
    ? decryptValue(existing.clave_certificado_encrypted)
    : null;

  const incomingCertPath = payload.certificado_path
    ? resolveCertificatePath(payload.certificado_path)
    : null;

  const finalCertPath = incomingCertPath || existing?.certificado_path || null;
  const incomingPassword = safeTrim(payload.clave_certificado, null);
  const finalPassword = incomingPassword || decryptedExistingPassword || null;

  if (incomingCertPath && !finalPassword) {
    throw createError("Debes enviar la clave_certificado para guardar un nuevo certificado");
  }

  if (incomingPassword && !finalCertPath) {
    throw createError("No puedes guardar la clave del certificado sin un archivo asociado");
  }

  let certMeta = null;
  if ((incomingCertPath || incomingPassword) && finalCertPath && finalPassword) {
    certMeta = probarCertificadoPkcs12({
      certPath: finalCertPath,
      password: finalPassword
    });
  }

  const merged = {
    id_local: idLocal,
    ruc: safeTrim(payload.ruc, existing?.ruc || null),
    razon_social: safeTrim(payload.razon_social, existing?.razon_social || null),
    nombre_comercial: safeTrim(payload.nombre_comercial, existing?.nombre_comercial || null),
    dir_matriz: safeTrim(payload.dir_matriz, existing?.dir_matriz || local.direccion || null),
    dir_establecimiento: safeTrim(payload.dir_establecimiento, existing?.dir_establecimiento || local.direccion || null),
    establecimiento: padCode(payload.establecimiento || existing?.establecimiento || "001", 3),
    punto_emision: padCode(payload.punto_emision || existing?.punto_emision || "001", 3),
    obligado_contabilidad: normalizeObligado(payload.obligado_contabilidad || existing?.obligado_contabilidad || "NO"),
    contribuyente_especial: safeTrim(payload.contribuyente_especial, existing?.contribuyente_especial || null),
    ambiente: normalizeAmbiente(payload.ambiente || existing?.ambiente || "PRUEBAS"),
    telefono: safeTrim(payload.telefono, existing?.telefono || local.telefono || null),
    correo_notificacion: safeTrim(payload.correo_notificacion, existing?.correo_notificacion || null),
    certificado_path: finalCertPath,
    clave_certificado_encrypted: finalPassword
      ? encryptValue(finalPassword)
      : (existing?.clave_certificado_encrypted || null),
    certificado_subject: certMeta?.emisor?.subject || existing?.certificado_subject || null,
    certificado_issuer: certMeta?.emisor?.issuer || existing?.certificado_issuer || null,
    certificado_fingerprint_sha256: certMeta?.seguridad?.fingerprint_sha256 || existing?.certificado_fingerprint_sha256 || null,
    certificado_valido_hasta: toMysqlDateTime(certMeta?.vigencia?.valido_hasta) || existing?.certificado_valido_hasta || null
  };

  const missing = [];

  if (!merged.ruc) missing.push("ruc");
  if (!merged.razon_social) missing.push("razon_social");
  if (!merged.dir_matriz) missing.push("dir_matriz");
  if (!merged.dir_establecimiento) missing.push("dir_establecimiento");

  if (missing.length > 0) {
    throw createError(`Faltan campos obligatorios de configuracion SRI: ${missing.join(", ")}`);
  }

  if (!/^\d{13}$/.test(merged.ruc)) {
    throw createError("El RUC debe tener exactamente 13 digitos");
  }

  if (existing) {
    await db.query(`
      UPDATE sri_configuraciones SET
        ruc = ?,
        razon_social = ?,
        nombre_comercial = ?,
        dir_matriz = ?,
        dir_establecimiento = ?,
        establecimiento = ?,
        punto_emision = ?,
        obligado_contabilidad = ?,
        contribuyente_especial = ?,
        ambiente = ?,
        telefono = ?,
        correo_notificacion = ?,
        certificado_path = ?,
        clave_certificado_encrypted = ?,
        certificado_subject = ?,
        certificado_issuer = ?,
        certificado_fingerprint_sha256 = ?,
        certificado_valido_hasta = ?,
        activo = 1
      WHERE id_local = ?
    `, [
      merged.ruc,
      merged.razon_social,
      merged.nombre_comercial,
      merged.dir_matriz,
      merged.dir_establecimiento,
      merged.establecimiento,
      merged.punto_emision,
      merged.obligado_contabilidad,
      merged.contribuyente_especial,
      merged.ambiente,
      merged.telefono,
      merged.correo_notificacion,
      merged.certificado_path,
      merged.clave_certificado_encrypted,
      merged.certificado_subject,
      merged.certificado_issuer,
      merged.certificado_fingerprint_sha256,
      merged.certificado_valido_hasta,
      idLocal
    ]);
  } else {
    await db.query(`
      INSERT INTO sri_configuraciones (
        id_local,
        ruc,
        razon_social,
        nombre_comercial,
        dir_matriz,
        dir_establecimiento,
        establecimiento,
        punto_emision,
        obligado_contabilidad,
        contribuyente_especial,
        ambiente,
        telefono,
        correo_notificacion,
        certificado_path,
        clave_certificado_encrypted,
        certificado_subject,
        certificado_issuer,
        certificado_fingerprint_sha256,
        certificado_valido_hasta,
        activo
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    `, [
      merged.id_local,
      merged.ruc,
      merged.razon_social,
      merged.nombre_comercial,
      merged.dir_matriz,
      merged.dir_establecimiento,
      merged.establecimiento,
      merged.punto_emision,
      merged.obligado_contabilidad,
      merged.contribuyente_especial,
      merged.ambiente,
      merged.telefono,
      merged.correo_notificacion,
      merged.certificado_path,
      merged.clave_certificado_encrypted,
      merged.certificado_subject,
      merged.certificado_issuer,
      merged.certificado_fingerprint_sha256,
      merged.certificado_valido_hasta
    ]);
  }

  if (incomingCertPath && existing?.certificado_path && existing.certificado_path !== incomingCertPath) {
    maybeDeleteStoredCert(existing.certificado_path);
  }

  return getSriConfig(idLocal);
}

module.exports = {
  ensureSriTables,
  getSriConfig,
  getSriConfigInternal,
  listActiveLocales,
  saveSriConfig,
  findLocalById,
  validateSriMasterLink,
  resolveSriContext
};
