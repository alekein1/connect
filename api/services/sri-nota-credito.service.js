const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { createPrivateKey, createPublicKey } = require("crypto");
const { Crypto } = require("@peculiar/webcrypto");
const xadesjs = require("xadesjs");
const xpath = require("xpath");
const { DOMParser, XMLSerializer, DOMImplementation } = require("@xmldom/xmldom");
const PDFDocument = require("pdfkit");
const db = require("../db/db");
const comprobanteConfig = require("../config/comprobante");
const { createError, resolveCertificatePath } = require("./sri-certificado.service");
const {
  ensureSriTables,
  getSriConfig,
  getSriConfigInternal,
  listActiveLocales
} = require("./sri-config.service");
const {
  ensureDetalleVentaImeiColumn,
  ensureVentaAnulacionSchema
} = require("./ventas-schema.service");

xadesjs.Application.setEngine("NodeJS", new Crypto());
xadesjs.setNodeDependencies({
  XMLSerializer,
  DOMParser,
  DOMImplementation,
  xpath
});

const UPLOADS_ROOT = path.resolve(__dirname, "../uploads");
const SRI_NC_XML_DIR = path.join(UPLOADS_ROOT, "sri-xml", "notas-credito");
const SRI_NC_SIGNED_DIR = path.join(UPLOADS_ROOT, "sri-xml", "firmados", "notas-credito");
const SRI_NC_AUTH_DIR = path.join(UPLOADS_ROOT, "sri-xml", "autorizados", "notas-credito");
const SRI_NC_RIDE_DIR = path.join(UPLOADS_ROOT, "sri-ride", "notas-credito");

const SRI_ENDPOINTS = {
  PRUEBAS: {
    recepcion: "https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline",
    autorizacion: "https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline"
  },
  PRODUCCION: {
    recepcion: "https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline",
    autorizacion: "https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline"
  }
};

let ensureSriNotasCreditoTablePromise = null;

function ensureNcDirs() {
  fs.mkdirSync(SRI_NC_XML_DIR, { recursive: true });
  fs.mkdirSync(SRI_NC_SIGNED_DIR, { recursive: true });
  fs.mkdirSync(SRI_NC_AUTH_DIR, { recursive: true });
  fs.mkdirSync(SRI_NC_RIDE_DIR, { recursive: true });
}

async function ensureSriNotasCreditoTable() {
  if (!ensureSriNotasCreditoTablePromise) {
    ensureSriNotasCreditoTablePromise = (async () => {
      await ensureSriTables();

      await db.query(`
        CREATE TABLE IF NOT EXISTS sri_notas_credito (
          id_nota_credito INT(11) NOT NULL AUTO_INCREMENT,
          id_local INT(11) NOT NULL,
          id_local_config INT(11) DEFAULT NULL,
          id_venta INT(11) NOT NULL,
          id_documento_sri_factura INT(11) DEFAULT NULL,
          id_usuario_emisor INT(11) DEFAULT NULL,
          motivo VARCHAR(300) NOT NULL,
          estado ENUM('BORRADOR','XML_GENERADO','FIRMADO','ENVIADO','RECIBIDO','AUTORIZADO','RECHAZADO','ERROR') NOT NULL DEFAULT 'BORRADOR',
          ambiente ENUM('PRUEBAS','PRODUCCION') NOT NULL DEFAULT 'PRUEBAS',
          estab VARCHAR(3) NOT NULL,
          pto_emi VARCHAR(3) NOT NULL,
          secuencial VARCHAR(9) NOT NULL,
          numero_comprobante VARCHAR(17) NOT NULL,
          fecha_emision DATETIME NOT NULL,
          clave_acceso VARCHAR(49) DEFAULT NULL,
          valor_modificacion DECIMAL(14,2) NOT NULL DEFAULT 0.00,
          total_sin_impuestos DECIMAL(14,2) NOT NULL DEFAULT 0.00,
          total_impuesto DECIMAL(14,2) NOT NULL DEFAULT 0.00,
          xml_generado_path VARCHAR(255) DEFAULT NULL,
          xml_firmado_path VARCHAR(255) DEFAULT NULL,
          xml_autorizado_path VARCHAR(255) DEFAULT NULL,
          ride_path VARCHAR(255) DEFAULT NULL,
          numero_autorizacion VARCHAR(100) DEFAULT NULL,
          fecha_autorizacion DATETIME DEFAULT NULL,
          respuesta_sri_json LONGTEXT DEFAULT NULL,
          error_codigo VARCHAR(60) DEFAULT NULL,
          error_detalle TEXT DEFAULT NULL,
          aplico_anulacion_venta TINYINT(1) NOT NULL DEFAULT 0,
          fecha_aplicacion_venta DATETIME DEFAULT NULL,
          detalle_aplicacion_venta TEXT DEFAULT NULL,
          creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
          actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id_nota_credito),
          UNIQUE KEY uk_nc_venta (id_venta),
          UNIQUE KEY uk_nc_clave (clave_acceso),
          UNIQUE KEY uk_nc_numero (id_local, estab, pto_emi, secuencial),
          KEY idx_nc_local_config (id_local_config, estab, pto_emi),
          KEY idx_nc_local_estado (id_local, estado),
          KEY idx_nc_documento_factura (id_documento_sri_factura)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
      `);

      try {
        await db.query(`
          ALTER TABLE sri_notas_credito
          ADD COLUMN id_local_config INT(11) DEFAULT NULL AFTER id_local
        `);
      } catch (error) {
        if (error.code !== "ER_DUP_FIELDNAME") {
          throw error;
        }
      }

      try {
        await db.query(`
          ALTER TABLE sri_notas_credito
          ADD INDEX idx_nc_local_config (id_local_config, estab, pto_emi)
        `);
      } catch (error) {
        if (error.code !== "ER_DUP_KEYNAME") {
          throw error;
        }
      }

      await db.query(`
        UPDATE sri_notas_credito nc
        INNER JOIN locales l
          ON l.id_local = nc.id_local
        SET nc.id_local_config = COALESCE(l.id_local_sri_maestro, l.id_local)
        WHERE nc.id_local_config IS NULL
      `);
    })().catch((error) => {
      ensureSriNotasCreditoTablePromise = null;
      throw error;
    });
  }

  return ensureSriNotasCreditoTablePromise;
}

function round2(value) {
  return Math.round((Number(value || 0) + Number.EPSILON) * 100) / 100;
}

function formatMoney(value) {
  return round2(value).toFixed(2);
}

function padLeft(value, length) {
  return String(value || "").padStart(length, "0");
}

function digitsOnly(value) {
  return String(value || "").replace(/\D/g, "");
}

function safeText(value, fallback = "") {
  if (value === null || value === undefined) return fallback;
  const text = String(value).trim();
  return text || fallback;
}

function xmlEscape(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&apos;");
}

function wrapCdata(value) {
  return `<![CDATA[${String(value ?? "").replace(/]]>/g, "]]]]><![CDATA[>")}]]>`;
}

function getAmbienteCodigo(value) {
  return String(value).toUpperCase() === "PRODUCCION" ? "2" : "1";
}

function formatDateEc(value) {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    throw createError("La fecha no es válida para el documento SRI");
  }

  return new Intl.DateTimeFormat("es-EC", {
    timeZone: "America/Guayaquil",
    day: "2-digit",
    month: "2-digit",
    year: "numeric"
  }).format(date);
}

function formatDateForAccessKey(value) {
  return formatDateEc(value).replace(/\//g, "");
}

function toMysqlDateTime(value) {
  if (!value) return null;

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return null;

  const parts = new Intl.DateTimeFormat("en-CA", {
    timeZone: "America/Guayaquil",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false,
    hourCycle: "h23"
  }).formatToParts(date).reduce((acc, part) => {
    if (part.type !== "literal") {
      acc[part.type] = part.value;
    }
    return acc;
  }, {});

  return `${parts.year}-${parts.month}-${parts.day} ${parts.hour}:${parts.minute}:${parts.second}`;
}

function getCurrentDateTimeEcSql() {
  return toMysqlDateTime(new Date());
}

function modulo11(base48) {
  let factor = 2;
  let total = 0;

  for (let index = base48.length - 1; index >= 0; index -= 1) {
    total += Number(base48[index]) * factor;
    factor = factor === 7 ? 2 : factor + 1;
  }

  const residue = total % 11;
  const digit = 11 - residue;

  if (digit === 11) return 0;
  if (digit === 10) return 1;
  return digit;
}

function safeJsonParse(value, fallback = {}) {
  if (!value) return fallback;

  try {
    return JSON.parse(value);
  } catch (error) {
    return fallback;
  }
}

function toUploadUrl(filePath) {
  if (!filePath) return null;

  const absolutePath = path.resolve(filePath);

  if (!absolutePath.startsWith(UPLOADS_ROOT)) {
    return null;
  }

  const relativePath = path.relative(UPLOADS_ROOT, absolutePath).split(path.sep).join("/");
  return `/api/uploads/${relativePath}`;
}

function readText(node, expression) {
  const value = xpath.select1(`string(${expression})`, node);
  return String(value || "").trim();
}

function parseMensajes(parentNode) {
  const messageNodes = xpath.select(".//*[local-name()='mensajes']/*[local-name()='mensaje']", parentNode);

  return messageNodes.map((node) => ({
    identificador: readText(node, "./*[local-name()='identificador']"),
    mensaje: readText(node, "./*[local-name()='mensaje']"),
    informacionAdicional: readText(node, "./*[local-name()='informacionAdicional']"),
    tipo: readText(node, "./*[local-name()='tipo']")
  }));
}

function summarizeMensajes(mensajes = []) {
  return mensajes
    .map((item) => [item.identificador, item.mensaje, item.informacionAdicional].filter(Boolean).join(" - "))
    .filter(Boolean)
    .join(" | ");
}

function isProcessingMensaje(mensaje = {}) {
  const combined = [
    mensaje.identificador,
    mensaje.mensaje,
    mensaje.informacionAdicional
  ].filter(Boolean).join(" ").toUpperCase();

  return mensaje.identificador === "70" || combined.includes("EN PROCESAMIENTO");
}

function isProcessingResponse(mensajes = []) {
  return Array.isArray(mensajes) && mensajes.length > 0 && mensajes.every(isProcessingMensaje);
}

function getSoapFault(doc) {
  const faultNode = xpath.select1("//*[local-name()='Fault']", doc);
  if (!faultNode) return null;

  return {
    code: readText(faultNode, "./*[local-name()='faultcode']"),
    message: readText(faultNode, "./*[local-name()='faultstring']")
  };
}

function parseRecepcionSoapResponse(xmlText) {
  const doc = new DOMParser().parseFromString(String(xmlText || ""), "text/xml");
  const fault = getSoapFault(doc);

  if (fault) {
    return {
      ok: false,
      fault
    };
  }

  const responseNode = xpath.select1("//*[local-name()='RespuestaRecepcionComprobante']", doc);
  if (!responseNode) {
    return {
      ok: false,
      fault: {
        code: "SOAP_RESPONSE_INVALIDA",
        message: "El SRI no devolvio RespuestaRecepcionComprobante"
      }
    };
  }

  const comprobanteNodes = xpath.select("./*[local-name()='comprobantes']/*[local-name()='comprobante']", responseNode);

  return {
    ok: true,
    estado: readText(responseNode, "./*[local-name()='estado']"),
    comprobantes: comprobanteNodes.map((node) => ({
      claveAcceso: readText(node, "./*[local-name()='claveAcceso']"),
      mensajes: parseMensajes(node)
    }))
  };
}

function parseAutorizacionSoapResponse(xmlText) {
  const doc = new DOMParser().parseFromString(String(xmlText || ""), "text/xml");
  const fault = getSoapFault(doc);

  if (fault) {
    return {
      ok: false,
      fault
    };
  }

  const responseNode = xpath.select1("//*[local-name()='RespuestaAutorizacionComprobante']", doc);
  if (!responseNode) {
    return {
      ok: false,
      fault: {
        code: "SOAP_RESPONSE_INVALIDA",
        message: "El SRI no devolvio RespuestaAutorizacionComprobante"
      }
    };
  }

  const authorizationNodes = xpath.select("./*[local-name()='autorizaciones']/*[local-name()='autorizacion']", responseNode);

  return {
    ok: true,
    claveAccesoConsultada: readText(responseNode, "./*[local-name()='claveAccesoConsultada']"),
    numeroComprobantes: Number(readText(responseNode, "./*[local-name()='numeroComprobantes']") || 0),
    autorizaciones: authorizationNodes.map((node) => ({
      estado: readText(node, "./*[local-name()='estado']"),
      numeroAutorizacion: readText(node, "./*[local-name()='numeroAutorizacion']"),
      fechaAutorizacion: readText(node, "./*[local-name()='fechaAutorizacion']"),
      ambiente: readText(node, "./*[local-name()='ambiente']"),
      comprobante: readText(node, "./*[local-name()='comprobante']"),
      mensajes: parseMensajes(node)
    }))
  };
}

function getSriSoapEndpoints(ambiente) {
  return ambiente === "PRODUCCION"
    ? SRI_ENDPOINTS.PRODUCCION
    : SRI_ENDPOINTS.PRUEBAS;
}

function buildRecepcionEnvelope(xmlFirmado) {
  const xmlBase64 = Buffer.from(String(xmlFirmado || ""), "utf8").toString("base64");

  return `<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ec="http://ec.gob.sri.ws.recepcion">
  <soapenv:Header/>
  <soapenv:Body>
    <ec:validarComprobante>
      <xml>${xmlBase64}</xml>
    </ec:validarComprobante>
  </soapenv:Body>
</soapenv:Envelope>`;
}

function buildAutorizacionEnvelope(claveAcceso) {
  return `<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ec="http://ec.gob.sri.ws.autorizacion">
  <soapenv:Header/>
  <soapenv:Body>
    <ec:autorizacionComprobante>
      <claveAccesoComprobante>${xmlEscape(claveAcceso)}</claveAccesoComprobante>
    </ec:autorizacionComprobante>
  </soapenv:Body>
</soapenv:Envelope>`;
}

async function soapRequest(url, envelope) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 25000);

  try {
    const response = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "text/xml; charset=utf-8",
        Accept: "text/xml",
        SOAPAction: "\"\""
      },
      body: envelope,
      signal: controller.signal
    });

    const text = await response.text();

    return {
      status: response.status,
      ok: response.ok,
      text
    };
  } catch (error) {
    if (error?.name === "AbortError") {
      throw createError("El SRI no respondio dentro del tiempo esperado", 504);
    }

    throw createError(`No se pudo conectar con el SRI: ${error.message}`, 502);
  } finally {
    clearTimeout(timeout);
  }
}

function runOpenSsl(args, options = {}) {
  try {
    return execFileSync("openssl", args, {
      encoding: "utf8",
      stdio: ["pipe", "pipe", "pipe"],
      ...options
    });
  } catch (error) {
    const stderr = error.stderr ? String(error.stderr).trim() : "";
    const stdout = error.stdout ? String(error.stdout).trim() : "";
    const detail = stderr || stdout || error.message;

    if (/invalid password|mac verify error|password/i.test(detail)) {
      throw createError("La clave del certificado es incorrecta para firmar el XML");
    }

    throw createError(`OpenSSL no pudo preparar el material de firma: ${detail}`, 500);
  }
}

function extractPemBlock(text, beginLabel, endLabel) {
  const pattern = new RegExp(`-----BEGIN ${beginLabel}-----[\\s\\S]+?-----END ${endLabel}-----`);
  const match = String(text || "").match(pattern);
  return match ? match[0] : null;
}

function pemToBase64(pem) {
  return String(pem || "")
    .replace(/-----BEGIN [^-]+-----/g, "")
    .replace(/-----END [^-]+-----/g, "")
    .replace(/\s+/g, "");
}

function makeId(prefix) {
  return `${prefix}${Date.now()}${Math.floor(Math.random() * 100000)}`;
}

async function extractPkcs12Material(certPath, password) {
  const normalizedPath = resolveCertificatePath(certPath);

  if (!password) {
    throw createError("No existe una clave de certificado guardada para este local");
  }

  const env = {
    ...process.env,
    SRI_CERT_PASS: String(password)
  };

  const certBundle = runOpenSsl([
    "pkcs12",
    "-in", normalizedPath,
    "-clcerts",
    "-nokeys",
    "-passin", "env:SRI_CERT_PASS"
  ], { env });

  const privateKeyBundle = runOpenSsl([
    "pkcs12",
    "-in", normalizedPath,
    "-nocerts",
    "-nodes",
    "-passin", "env:SRI_CERT_PASS"
  ], { env });

  const certPem = extractPemBlock(certBundle, "CERTIFICATE", "CERTIFICATE");
  const privateKeyPem = extractPemBlock(privateKeyBundle, "PRIVATE KEY", "PRIVATE KEY");

  if (!certPem || !privateKeyPem) {
    throw createError("No se pudo extraer el certificado o la llave privada para firmar");
  }

  const certBase64 = pemToBase64(certPem);
  const privateKeyDer = createPrivateKey(privateKeyPem).export({
    format: "der",
    type: "pkcs8"
  });
  const publicKeyDer = createPublicKey(certPem).export({
    format: "der",
    type: "spki"
  });

  return {
    certBase64,
    privateKeyDer,
    publicKeyDer
  };
}

function normalizeSignedXml(xmlString) {
  return String(xmlString)
    .replace(/\r\n/g, "\n")
    .replace(/\sxmlns=""/g, "");
}

function resolveLogoPath() {
  const configured = comprobanteConfig.logoPath;
  const candidates = [];
  const apiAssetsDir = path.resolve(__dirname, "../assets");
  const publicImagesDir = path.resolve(__dirname, "../../frontend-connect/public/images");

  if (configured) {
    candidates.push(
      path.isAbsolute(configured)
        ? configured
        : path.resolve(__dirname, "..", configured),
      path.isAbsolute(configured)
        ? configured
        : path.resolve(process.cwd(), configured),
      path.isAbsolute(configured)
        ? configured
        : path.resolve(apiAssetsDir, configured),
      path.isAbsolute(configured)
        ? configured
        : path.resolve(apiAssetsDir, path.basename(configured)),
      path.isAbsolute(configured)
        ? configured
        : path.resolve(publicImagesDir, configured),
      path.isAbsolute(configured)
        ? configured
        : path.resolve(publicImagesDir, path.basename(configured))
    );
  }

  candidates.push(
    path.resolve(__dirname, "../assets/connect.png"),
    path.resolve(__dirname, "../assets/connect.jpg"),
    path.resolve(__dirname, "../assets/connect.jpeg"),
    path.resolve(process.cwd(), "api/assets/connect.png"),
    path.resolve(process.cwd(), "api/assets/connect.jpg"),
    path.resolve(process.cwd(), "api/assets/connect.jpeg"),
    path.resolve(__dirname, "../../frontend-connect/public/images/connect.png"),
    path.resolve(__dirname, "../../frontend-connect/public/images/connect.jpg"),
    path.resolve(__dirname, "../../frontend-connect/public/images/connect.jpeg"),
    path.resolve(process.cwd(), "frontend-connect/public/images/connect.png"),
    path.resolve(process.cwd(), "frontend-connect/public/images/connect.jpg"),
    path.resolve(process.cwd(), "frontend-connect/public/images/connect.jpeg"),
    path.resolve(process.cwd(), "public/images/connect.png"),
    path.resolve(process.cwd(), "public/images/connect.jpg"),
    path.resolve(process.cwd(), "public/images/connect.jpeg")
  );

  if (fs.existsSync(apiAssetsDir)) {
    const discoveredAssetLogo = fs.readdirSync(apiAssetsDir).find((fileName) => (
      /^connect\.(png|jpg|jpeg|webp|svg)$/i.test(fileName)
        || /^logo\.(png|jpg|jpeg|webp|svg)$/i.test(fileName)
    ));

    if (discoveredAssetLogo) {
      candidates.push(path.join(apiAssetsDir, discoveredAssetLogo));
    }
  }

  if (fs.existsSync(publicImagesDir)) {
    const discoveredLogo = fs.readdirSync(publicImagesDir).find((fileName) => (
      /^connect\.(png|jpg|jpeg|webp|svg)$/i.test(fileName)
        || /^logo\.(png|jpg|jpeg|webp|svg)$/i.test(fileName)
    ));

    if (discoveredLogo) {
      candidates.push(path.join(publicImagesDir, discoveredLogo));
    }
  }

  return candidates.find((filePath) => fs.existsSync(filePath)) || null;
}

function inferComprador(venta) {
  const rawIdentificacion = digitsOnly(venta.cliente_cedula);
  const razonSocial = safeText(venta.cliente_nombres, "CONSUMIDOR FINAL");

  if (!rawIdentificacion || /^9+$/.test(rawIdentificacion)) {
    return {
      tipoIdentificacionComprador: "07",
      identificacionComprador: "9999999999999",
      razonSocialComprador: "CONSUMIDOR FINAL",
      direccionComprador: safeText(venta.cliente_direccion, null),
      correo: safeText(venta.cliente_correo, null),
      telefono: safeText(venta.cliente_telefono, null)
    };
  }

  if (rawIdentificacion.length === 13) {
    return {
      tipoIdentificacionComprador: "04",
      identificacionComprador: rawIdentificacion,
      razonSocialComprador: razonSocial,
      direccionComprador: safeText(venta.cliente_direccion, null),
      correo: safeText(venta.cliente_correo, null),
      telefono: safeText(venta.cliente_telefono, null)
    };
  }

  if (rawIdentificacion.length === 10) {
    return {
      tipoIdentificacionComprador: "05",
      identificacionComprador: rawIdentificacion,
      razonSocialComprador: razonSocial,
      direccionComprador: safeText(venta.cliente_direccion, null),
      correo: safeText(venta.cliente_correo, null),
      telefono: safeText(venta.cliente_telefono, null)
    };
  }

  return {
    tipoIdentificacionComprador: "06",
    identificacionComprador: safeText(venta.cliente_cedula, rawIdentificacion),
    razonSocialComprador: razonSocial,
    direccionComprador: safeText(venta.cliente_direccion, null),
    correo: safeText(venta.cliente_correo, null),
    telefono: safeText(venta.cliente_telefono, null)
  };
}

function getIvaMeta(grabaIva) {
  if (Number(grabaIva || 0) === 1) {
    return {
      codigo: "2",
      codigoPorcentaje: "4",
      tarifa: 15
    };
  }

  return {
    codigo: "2",
    codigoPorcentaje: "0",
    tarifa: 0
  };
}

function parseSriNumber(value) {
  const normalized = String(value ?? "")
    .replace(/,/g, "")
    .trim();

  if (!normalized) {
    return 0;
  }

  const parsed = Number(normalized);
  return Number.isFinite(parsed) ? round2(parsed) : 0;
}

function normalizeComprobanteXml(xmlContent) {
  const rawXml = String(xmlContent || "").trim();

  if (!rawXml) {
    return "";
  }

  const doc = new DOMParser().parseFromString(rawXml, "text/xml");
  const comprobanteNode = xpath.select1("//*[local-name()='autorizacion']/*[local-name()='comprobante']", doc);

  if (!comprobanteNode) {
    return rawXml;
  }

  const embeddedXml = String(comprobanteNode.textContent || "").trim();
  return embeddedXml || rawXml;
}

function parseInvoiceDetailOverridesFromXml(xmlContent, detalles = []) {
  const normalizedXml = normalizeComprobanteXml(xmlContent);

  if (!normalizedXml) {
    return null;
  }

  const doc = new DOMParser().parseFromString(normalizedXml, "text/xml");
  const detalleNodes = xpath.select("//*[local-name()='factura']/*[local-name()='detalles']/*[local-name()='detalle']", doc);

  if (!detalleNodes.length) {
    return null;
  }

  if (Array.isArray(detalles) && detalles.length > 0 && detalleNodes.length !== detalles.length) {
    return null;
  }

  return detalleNodes.map((node, index) => {
    const detalleActual = detalles[index] || {};
    const impuestoNode = xpath.select1("./*[local-name()='impuestos']/*[local-name()='impuesto'][1]", node);
    const precioTotalSinImpuesto = parseSriNumber(
      readText(node, "./*[local-name()='precioTotalSinImpuesto']")
    );
    const baseImponible = impuestoNode
      ? parseSriNumber(readText(impuestoNode, "./*[local-name()='baseImponible']"))
      : precioTotalSinImpuesto;

    return {
      sri_codigo_principal: safeText(
        readText(node, "./*[local-name()='codigoPrincipal']"),
        safeText(detalleActual.sku, String(detalleActual.id_producto || ""))
      ),
      sri_codigo_auxiliar: safeText(
        readText(node, "./*[local-name()='codigoAuxiliar']"),
        safeText(detalleActual.codigo_barras, "")
      ),
      sri_descripcion: safeText(
        readText(node, "./*[local-name()='descripcion']"),
        safeText(detalleActual.nombre_producto, `PRODUCTO ${detalleActual.id_producto || index + 1}`)
      ),
      sri_cantidad: parseSriNumber(readText(node, "./*[local-name()='cantidad']")) || Number(detalleActual.cantidad || 0),
      sri_precio_unitario: parseSriNumber(readText(node, "./*[local-name()='precioUnitario']")),
      sri_descuento: parseSriNumber(readText(node, "./*[local-name()='descuento']")),
      sri_total_sin_impuesto: precioTotalSinImpuesto,
      sri_codigo_impuesto: safeText(
        impuestoNode ? readText(impuestoNode, "./*[local-name()='codigo']") : "",
        "2"
      ),
      sri_codigo_porcentaje: safeText(
        impuestoNode ? readText(impuestoNode, "./*[local-name()='codigoPorcentaje']") : "",
        "0"
      ),
      sri_tarifa: impuestoNode
        ? parseSriNumber(readText(impuestoNode, "./*[local-name()='tarifa']"))
        : 0,
      sri_base_imponible: baseImponible || precioTotalSinImpuesto,
      sri_valor_impuesto: impuestoNode
        ? parseSriNumber(readText(impuestoNode, "./*[local-name()='valor']"))
        : 0
    };
  });
}

function buildFacturaXmlFallbackCandidates(venta) {
  const idVenta = Number(venta?.id_venta || 0);
  const claveAcceso = safeText(
    venta?.factura_clave_acceso,
    safeText(venta?.factura_numero_autorizacion, "")
  );

  if (!idVenta || !claveAcceso) {
    return [];
  }

  return [
    path.join(UPLOADS_ROOT, "sri-xml", "facturas", `factura_${idVenta}_${claveAcceso}.xml`),
    path.join(UPLOADS_ROOT, "sri-xml", "firmados", "facturas", `factura_firmada_${idVenta}_${claveAcceso}.xml`),
    path.join(UPLOADS_ROOT, "sri-xml", "autorizados", `factura_autorizada_${idVenta}_${claveAcceso}.xml`),
    path.join(UPLOADS_ROOT, "sri-xml", "autorizados", "facturas", `factura_autorizada_${idVenta}_${claveAcceso}.xml`)
  ];
}

function findFacturaXmlPathByClaveAcceso(claveAcceso) {
  const normalizedClave = safeText(claveAcceso, "");

  if (!normalizedClave) {
    return null;
  }

  const searchDirs = [
    path.join(UPLOADS_ROOT, "sri-xml", "facturas"),
    path.join(UPLOADS_ROOT, "sri-xml", "firmados", "facturas"),
    path.join(UPLOADS_ROOT, "sri-xml", "autorizados"),
    path.join(UPLOADS_ROOT, "sri-xml", "autorizados", "facturas")
  ];

  for (const dirPath of searchDirs) {
    if (!fs.existsSync(dirPath)) {
      continue;
    }

    const fileName = fs.readdirSync(dirPath).find((entry) => (
      entry.endsWith(".xml") && entry.includes(normalizedClave)
    ));

    if (fileName) {
      return path.join(dirPath, fileName);
    }
  }

  return null;
}

function enrichDetallesWithFacturaXml(venta, detalles = []) {
  const discoveredByClave = findFacturaXmlPathByClaveAcceso(
    safeText(venta?.factura_clave_acceso, safeText(venta?.factura_numero_autorizacion, ""))
  );
  const fileCandidates = [
    venta?.factura_xml_generado_path,
    venta?.factura_xml_firmado_path,
    venta?.factura_xml_autorizado_path,
    ...buildFacturaXmlFallbackCandidates(venta),
    discoveredByClave
  ].filter(Boolean);

  for (const candidate of fileCandidates) {
    const resolvedPath = path.resolve(candidate);

    if (!fs.existsSync(resolvedPath)) {
      continue;
    }

    try {
      const xmlContent = fs.readFileSync(resolvedPath, "utf8");
      const overrides = parseInvoiceDetailOverridesFromXml(xmlContent, detalles);

      if (Array.isArray(overrides) && overrides.length === detalles.length) {
        return detalles.map((item, index) => ({
          ...item,
          ...overrides[index]
        }));
      }
    } catch (error) {
      continue;
    }
  }

  return detalles;
}

function buildLineasCredito(detalles, venta) {
  if (!Array.isArray(detalles) || detalles.length === 0) {
    throw createError("La venta no tiene detalle y no se puede construir la nota de crédito");
  }

  const tieneDetalleSriOriginal = detalles.every((item) => (
    item &&
    item.sri_codigo_principal &&
    item.sri_descripcion &&
    Number(item.sri_cantidad || 0) > 0
  ));

  if (tieneDetalleSriOriginal) {
    const lineas = detalles.map((item) => {
      const cantidad = Number(item.sri_cantidad || item.cantidad || 0);
      const precioUnitarioSinImpuesto = round2(item.sri_precio_unitario);
      const descuentoLinea = round2(item.sri_descuento);
      const totalSinImpuesto = round2(item.sri_total_sin_impuesto);
      const baseImponibleImpuesto = round2(item.sri_base_imponible || totalSinImpuesto);
      const impuestoLinea = round2(item.sri_valor_impuesto);
      const tarifa = round2(item.sri_tarifa);
      const ivaMeta = {
        codigo: safeText(item.sri_codigo_impuesto, "2"),
        codigoPorcentaje: safeText(item.sri_codigo_porcentaje, tarifa > 0 ? "4" : "0"),
        tarifa
      };

      return {
        id_detalle: item.id_detalle,
        id_producto: item.id_producto,
        cantidad,
        precioUnitarioSinImpuesto,
        descuentoLinea,
        totalSinImpuesto,
        baseImponibleImpuesto,
        impuestoLinea,
        descripcion: safeText(item.sri_descripcion, safeText(item.nombre_producto, `PRODUCTO ${item.id_producto}`)),
        codigoPrincipal: safeText(item.sri_codigo_principal, safeText(item.sku, String(item.id_producto))),
        codigoAuxiliar: safeText(item.sri_codigo_auxiliar, safeText(item.codigo_barras, safeText(item.sku, null))),
        grabaIva: tarifa > 0 || impuestoLinea > 0,
        ivaMeta
      };
    });

    const totalSinImpuestos = round2(
      lineas.reduce((acc, item) => acc + item.totalSinImpuesto, 0)
    );
    const totalDescuento = round2(
      lineas.reduce((acc, item) => acc + item.descuentoLinea, 0)
    );
    const totalImpuestoXml = round2(
      lineas.reduce((acc, item) => acc + item.impuestoLinea, 0)
    );
    const importeTotal = round2(totalSinImpuestos + totalImpuestoXml);
    const ventaSubtotal = round2(venta.subtotal);
    const ventaImpuesto = round2(venta.impuesto);
    const ventaTotal = round2(venta.total);
    const mismatches = [];

    if (Math.abs(totalSinImpuestos - ventaSubtotal) > 0.02) {
      mismatches.push(`subtotal XML ${formatMoney(totalSinImpuestos)} vs venta ${formatMoney(ventaSubtotal)}`);
    }

    if (Math.abs(totalImpuestoXml - ventaImpuesto) > 0.02) {
      mismatches.push(`impuesto XML ${formatMoney(totalImpuestoXml)} vs venta ${formatMoney(ventaImpuesto)}`);
    }

    if (Math.abs(importeTotal - ventaTotal) > 0.02) {
      mismatches.push(`total XML ${formatMoney(importeTotal)} vs venta ${formatMoney(ventaTotal)}`);
    }

    if (mismatches.length > 0) {
      throw createError(`La factura original no cuadra con la venta para la nota de crédito: ${mismatches.join(" | ")}`);
    }

    const totalConImpuestosMap = new Map();

    lineas.forEach((item) => {
      const key = [
        item.ivaMeta.codigo,
        item.ivaMeta.codigoPorcentaje,
        formatMoney(item.ivaMeta.tarifa)
      ].join("|");

      const current = totalConImpuestosMap.get(key) || {
        codigo: item.ivaMeta.codigo,
        codigoPorcentaje: item.ivaMeta.codigoPorcentaje,
        tarifa: item.ivaMeta.tarifa,
        baseImponible: 0,
        valor: 0
      };

      current.baseImponible = round2(current.baseImponible + (item.baseImponibleImpuesto ?? item.totalSinImpuesto));
      current.valor = round2(current.valor + item.impuestoLinea);
      totalConImpuestosMap.set(key, current);
    });

    return {
      lineas,
      resumen: {
        totalSinImpuestos,
        totalDescuento,
        totalConImpuestos: Array.from(totalConImpuestosMap.values()),
        totalImpuesto: totalImpuestoXml,
        importeTotal
      }
    };
  }

  const detalleBase = detalles.map((item) => {
    const cantidad = Number(item.cantidad || 0);
    const baseOriginal = round2(item.subtotal);
    const ivaMeta = getIvaMeta(item.graba_iva);
    const grossOriginal = ivaMeta.tarifa > 0
      ? round2(baseOriginal * (1 + ivaMeta.tarifa / 100))
      : baseOriginal;

    return {
      id_detalle: item.id_detalle,
      id_producto: item.id_producto,
      cantidad,
      baseOriginal,
      grossOriginal,
      precioUnitarioSinImpuesto: cantidad > 0
        ? round2(baseOriginal / cantidad)
        : 0,
      descripcion: safeText(item.nombre_producto, `PRODUCTO ${item.id_producto}`),
      codigoPrincipal: safeText(item.sku, String(item.id_producto)),
      codigoAuxiliar: safeText(item.codigo_barras, safeText(item.sku, null)),
      grabaIva: ivaMeta.tarifa > 0,
      ivaMeta
    };
  });

  const ventaSubtotal = round2(venta.subtotal);
  const ventaImpuesto = round2(venta.impuesto);
  const ventaTotal = round2(venta.total);
  const totalBaseOriginalGravado = round2(
    detalleBase
      .filter((item) => item.grabaIva)
      .reduce((acc, item) => acc + item.baseOriginal, 0)
  );
  const totalBaseOriginalNoGravado = round2(
    detalleBase
      .filter((item) => !item.grabaIva)
      .reduce((acc, item) => acc + item.baseOriginal, 0)
  );
  const totalBaseOriginal = round2(totalBaseOriginalGravado + totalBaseOriginalNoGravado);

  if (Math.abs(round2(ventaSubtotal + ventaImpuesto) - ventaTotal) > 0.02) {
    throw createError(
      `Los totales de la venta no cuadran para la nota de crédito (${formatMoney(ventaSubtotal)} + ${formatMoney(ventaImpuesto)} vs ${formatMoney(ventaTotal)})`
    );
  }

  if (totalBaseOriginal <= 0) {
    throw createError("La venta no tiene base imponible válida para la nota de crédito");
  }

  if (totalBaseOriginalGravado <= 0 && ventaImpuesto > 0.02) {
    throw createError("La venta registra impuesto, pero su detalle no tiene productos gravados");
  }

  let baseGravadaConDescuento = 0;
  let baseNoGravadaConDescuento = 0;

  if (totalBaseOriginalGravado > 0 && totalBaseOriginalNoGravado > 0) {
    baseNoGravadaConDescuento = round2(ventaSubtotal * (totalBaseOriginalNoGravado / totalBaseOriginal));
    baseGravadaConDescuento = round2(ventaSubtotal - baseNoGravadaConDescuento);
  } else if (totalBaseOriginalGravado > 0) {
    baseGravadaConDescuento = ventaSubtotal;
  } else {
    baseNoGravadaConDescuento = ventaSubtotal;
  }

  const subtotalCalculado = round2(baseGravadaConDescuento + baseNoGravadaConDescuento);
  const impuestoTotal = totalBaseOriginalGravado > 0 ? ventaImpuesto : 0;
  const totalCalculado = round2(subtotalCalculado + impuestoTotal);

  const mismatches = [];

  if (Math.abs(subtotalCalculado - ventaSubtotal) > 0.02) {
    mismatches.push(`subtotal esperado ${formatMoney(subtotalCalculado)} vs venta ${formatMoney(ventaSubtotal)}`);
  }

  if (Math.abs(impuestoTotal - ventaImpuesto) > 0.02) {
    mismatches.push(`impuesto esperado ${formatMoney(impuestoTotal)} vs venta ${formatMoney(ventaImpuesto)}`);
  }

  if (Math.abs(totalCalculado - ventaTotal) > 0.02) {
    mismatches.push(`total esperado ${formatMoney(totalCalculado)} vs venta ${formatMoney(ventaTotal)}`);
  }

  if (mismatches.length > 0) {
    throw createError(`La venta no cuadra con su detalle para la nota de crédito: ${mismatches.join(" | ")}`);
  }

  const grupos = [
    {
      items: detalleBase.filter((item) => item.grabaIva),
      targetBase: baseGravadaConDescuento,
      targetImpuesto: impuestoTotal
    },
    {
      items: detalleBase.filter((item) => !item.grabaIva),
      targetBase: baseNoGravadaConDescuento,
      targetImpuesto: 0
    }
  ];

  const lineasMap = new Map();

  for (const grupo of grupos) {
    if (!grupo.items.length) continue;

    const totalBaseOriginalGrupo = round2(
      grupo.items.reduce((acc, item) => acc + item.baseOriginal, 0)
    );

    let acumuladoBase = 0;
    let acumuladoImpuesto = 0;

    grupo.items.forEach((item, index) => {
      const isLast = index === grupo.items.length - 1;

      let baseNeta;
      if (isLast) {
        baseNeta = round2(grupo.targetBase - acumuladoBase);
      } else if (totalBaseOriginalGrupo > 0) {
        baseNeta = round2(grupo.targetBase * (item.baseOriginal / totalBaseOriginalGrupo));
      } else {
        baseNeta = 0;
      }

      let impuestoLinea = 0;
      if (item.grabaIva) {
        if (isLast) {
          impuestoLinea = round2(grupo.targetImpuesto - acumuladoImpuesto);
        } else if (grupo.targetBase > 0) {
          impuestoLinea = round2(grupo.targetImpuesto * (baseNeta / grupo.targetBase));
        }
      }

      acumuladoBase = round2(acumuladoBase + baseNeta);
      acumuladoImpuesto = round2(acumuladoImpuesto + impuestoLinea);

      const descuentoLinea = round2(item.baseOriginal - baseNeta);
      const totalSinImpuesto = round2(baseNeta);

      lineasMap.set(item.id_detalle, {
        ...item,
        descuentoLinea,
        totalSinImpuesto,
        impuestoLinea
      });
    });
  }

  const lineas = detalleBase.map((item) => lineasMap.get(item.id_detalle));

  const totalSinImpuestos = round2(
    lineas.reduce((acc, item) => acc + item.totalSinImpuesto, 0)
  );
  const totalDescuento = round2(
    lineas.reduce((acc, item) => acc + item.descuentoLinea, 0)
  );
  const totalImpuestoXml = round2(
    lineas.reduce((acc, item) => acc + item.impuestoLinea, 0)
  );
  const importeTotal = round2(totalSinImpuestos + totalImpuestoXml);

  if (Math.abs(importeTotal - ventaTotal) > 0.02) {
    throw createError(
      `La nota de crédito calculada no coincide con el total de la venta (${formatMoney(importeTotal)} vs ${formatMoney(ventaTotal)})`
    );
  }

  const totalConImpuestos = [];
  const baseNoGravada = round2(
    lineas
      .filter((item) => !item.grabaIva)
      .reduce((acc, item) => acc + item.totalSinImpuesto, 0)
  );
  const baseGravada = round2(
    lineas
      .filter((item) => item.grabaIva)
      .reduce((acc, item) => acc + item.totalSinImpuesto, 0)
  );

  if (baseNoGravada > 0) {
    totalConImpuestos.push({
      codigo: "2",
      codigoPorcentaje: "0",
      tarifa: 0,
      baseImponible: baseNoGravada,
      valor: 0
    });
  }

  if (baseGravada > 0) {
    totalConImpuestos.push({
      codigo: "2",
      codigoPorcentaje: "4",
      tarifa: 15,
      baseImponible: baseGravada,
      valor: totalImpuestoXml
    });
  }

  return {
    lineas,
    resumen: {
      totalSinImpuestos,
      totalDescuento,
      totalConImpuestos,
      totalImpuesto: totalImpuestoXml,
      importeTotal
    }
  };
}

function buildCodigoNumericoNotaCredito({ venta, secuencial }) {
  const ventaId = padLeft(Number(venta.id_venta || 0) % 100000, 5);
  const localId = padLeft(Number(venta.id_local || 0) % 100, 2);
  const secTail = padLeft(String(secuencial || "").slice(-1), 1);
  return `${ventaId}${localId}${secTail}`;
}

function buildClaveAccesoNotaCredito({ venta, config, fechaEmision, secuencial, claveActual = null }) {
  const fecha = formatDateForAccessKey(fechaEmision);
  const codDoc = "04";
  const ruc = digitsOnly(config.ruc);
  const ambiente = getAmbienteCodigo(config.ambiente);
  const establecimiento = padLeft(config.establecimiento || "001", 3);
  const puntoEmision = padLeft(config.punto_emision || "001", 3);
  const codigoNumerico = buildCodigoNumericoNotaCredito({ venta, secuencial });
  const tipoEmision = "1";
  const base48 = `${fecha}${codDoc}${ruc}${ambiente}${establecimiento}${puntoEmision}${secuencial}${codigoNumerico}${tipoEmision}`;
  const generatedClaveAcceso = `${base48}${modulo11(base48)}`;

  if (claveActual && /^\d{49}$/.test(claveActual)) {
    return claveActual === generatedClaveAcceso
      ? claveActual
      : generatedClaveAcceso;
  }

  return generatedClaveAcceso;
}

function buildNotaCreditoXml({
  venta,
  facturaSri,
  notaCredito,
  config,
  comprador,
  creditoData,
  claveAcceso
}) {
  const { lineas, resumen } = creditoData;
  const ambienteCodigo = getAmbienteCodigo(config.ambiente);
  const establecimiento = padLeft(notaCredito.estab || config.establecimiento || "001", 3);
  const puntoEmision = padLeft(notaCredito.pto_emi || config.punto_emision || "001", 3);
  const secuencial = padLeft(notaCredito.secuencial || notaCredito.id_nota_credito, 9);
  const fechaEmision = formatDateEc(notaCredito.fecha_emision);
  const contribuyenteEspecial = (() => {
    const value = safeText(config.contribuyente_especial, "");
    if (!value) return null;
    if (["NO", "N/A", "NINGUNO"].includes(value.toUpperCase())) return null;
    return value;
  })();

  const infoTributaria = [
    `    <ambiente>${ambienteCodigo}</ambiente>`,
    "    <tipoEmision>1</tipoEmision>",
    `    <razonSocial>${xmlEscape(config.razon_social)}</razonSocial>`,
    config.nombre_comercial
      ? `    <nombreComercial>${xmlEscape(config.nombre_comercial)}</nombreComercial>`
      : null,
    `    <ruc>${xmlEscape(digitsOnly(config.ruc))}</ruc>`,
    `    <claveAcceso>${claveAcceso}</claveAcceso>`,
    "    <codDoc>04</codDoc>",
    `    <estab>${establecimiento}</estab>`,
    `    <ptoEmi>${puntoEmision}</ptoEmi>`,
    `    <secuencial>${secuencial}</secuencial>`,
    `    <dirMatriz>${xmlEscape(config.dir_matriz)}</dirMatriz>`
  ].filter(Boolean).join("\n");

  const infoNotaCredito = [
    `    <fechaEmision>${fechaEmision}</fechaEmision>`,
    `    <dirEstablecimiento>${xmlEscape(config.dir_establecimiento)}</dirEstablecimiento>`,
    `    <tipoIdentificacionComprador>${comprador.tipoIdentificacionComprador}</tipoIdentificacionComprador>`,
    `    <razonSocialComprador>${xmlEscape(comprador.razonSocialComprador)}</razonSocialComprador>`,
    `    <identificacionComprador>${xmlEscape(comprador.identificacionComprador)}</identificacionComprador>`,
    contribuyenteEspecial
      ? `    <contribuyenteEspecial>${xmlEscape(contribuyenteEspecial)}</contribuyenteEspecial>`
      : null,
    `    <obligadoContabilidad>${xmlEscape(config.obligado_contabilidad || "NO")}</obligadoContabilidad>`,
    "    <codDocModificado>01</codDocModificado>",
    `    <numDocModificado>${xmlEscape(safeText(venta.numero_comprobante, facturaSri.numero_comprobante || ""))}</numDocModificado>`,
    `    <fechaEmisionDocSustento>${formatDateEc(venta.fecha_venta)}</fechaEmisionDocSustento>`,
    `    <totalSinImpuestos>${formatMoney(resumen.totalSinImpuestos)}</totalSinImpuestos>`,
    `    <valorModificacion>${formatMoney(resumen.importeTotal)}</valorModificacion>`,
    "    <moneda>DOLAR</moneda>",
    "    <totalConImpuestos>",
    ...resumen.totalConImpuestos.map((item) => [
      "      <totalImpuesto>",
      `        <codigo>${item.codigo}</codigo>`,
      `        <codigoPorcentaje>${item.codigoPorcentaje}</codigoPorcentaje>`,
      `        <baseImponible>${formatMoney(item.baseImponible)}</baseImponible>`,
      `        <valor>${formatMoney(item.valor)}</valor>`,
      "      </totalImpuesto>"
    ].join("\n")),
    "    </totalConImpuestos>",
    `    <motivo>${xmlEscape(notaCredito.motivo)}</motivo>`
  ].filter(Boolean).join("\n");

  const detalles = [
    "  <detalles>",
    ...lineas.map((item) => [
      "    <detalle>",
      `      <codigoInterno>${xmlEscape(item.codigoPrincipal)}</codigoInterno>`,
      item.codigoAuxiliar && item.codigoAuxiliar !== item.codigoPrincipal
        ? `      <codigoAdicional>${xmlEscape(item.codigoAuxiliar)}</codigoAdicional>`
        : null,
      `      <descripcion>${xmlEscape(item.descripcion)}</descripcion>`,
      `      <cantidad>${formatMoney(item.cantidad)}</cantidad>`,
      `      <precioUnitario>${formatMoney(item.precioUnitarioSinImpuesto)}</precioUnitario>`,
      `      <descuento>${formatMoney(item.descuentoLinea)}</descuento>`,
      `      <precioTotalSinImpuesto>${formatMoney(item.totalSinImpuesto)}</precioTotalSinImpuesto>`,
      "      <impuestos>",
      "        <impuesto>",
      `          <codigo>${item.ivaMeta.codigo}</codigo>`,
      `          <codigoPorcentaje>${item.ivaMeta.codigoPorcentaje}</codigoPorcentaje>`,
      `          <tarifa>${formatMoney(item.ivaMeta.tarifa)}</tarifa>`,
      `          <baseImponible>${formatMoney(item.baseImponibleImpuesto ?? item.totalSinImpuesto)}</baseImponible>`,
      `          <valor>${formatMoney(item.impuestoLinea)}</valor>`,
      "        </impuesto>",
      "      </impuestos>",
      "    </detalle>"
    ].filter(Boolean).join("\n")),
    "  </detalles>"
  ].join("\n");

  const camposAdicionales = [
    comprador.correo
      ? `    <campoAdicional nombre="Email">${xmlEscape(comprador.correo)}</campoAdicional>`
      : null,
    comprador.telefono
      ? `    <campoAdicional nombre="Telefono">${xmlEscape(comprador.telefono)}</campoAdicional>`
      : null,
    comprador.direccionComprador
      ? `    <campoAdicional nombre="Direccion">${xmlEscape(comprador.direccionComprador)}</campoAdicional>`
      : null,
    venta.nombre_local
      ? `    <campoAdicional nombre="Local">${xmlEscape(venta.nombre_local)}</campoAdicional>`
      : null,
    facturaSri.numero_autorizacion
      ? `    <campoAdicional nombre="AutorizacionFactura">${xmlEscape(facturaSri.numero_autorizacion)}</campoAdicional>`
      : null
  ].filter(Boolean);

  const infoAdicional = camposAdicionales.length
    ? [
      "  <infoAdicional>",
      ...camposAdicionales,
      "  </infoAdicional>"
    ].join("\n")
    : null;

  return [
    "<?xml version=\"1.0\" encoding=\"UTF-8\"?>",
    "<notaCredito id=\"comprobante\" version=\"1.0.0\">",
    "  <infoTributaria>",
    infoTributaria,
    "  </infoTributaria>",
    "  <infoNotaCredito>",
    infoNotaCredito,
    "  </infoNotaCredito>",
    detalles,
    infoAdicional,
    "</notaCredito>"
  ].filter(Boolean).join("\n");
}

function buildAuthorizedXml(autorizacion) {
  const mensajesXml = (autorizacion.mensajes || []).length
    ? [
      "  <mensajes>",
      ...(autorizacion.mensajes || []).map((mensaje) => [
        "    <mensaje>",
        `      <identificador>${xmlEscape(mensaje.identificador || "")}</identificador>`,
        `      <mensaje>${xmlEscape(mensaje.mensaje || "")}</mensaje>`,
        mensaje.informacionAdicional
          ? `      <informacionAdicional>${xmlEscape(mensaje.informacionAdicional)}</informacionAdicional>`
          : null,
        mensaje.tipo
          ? `      <tipo>${xmlEscape(mensaje.tipo)}</tipo>`
          : null,
        "    </mensaje>"
      ].filter(Boolean).join("\n")),
      "  </mensajes>"
    ].join("\n")
    : null;

  return [
    "<?xml version=\"1.0\" encoding=\"UTF-8\"?>",
    "<autorizacion>",
    `  <estado>${xmlEscape(autorizacion.estado || "")}</estado>`,
    `  <numeroAutorizacion>${xmlEscape(autorizacion.numeroAutorizacion || "")}</numeroAutorizacion>`,
    `  <fechaAutorizacion>${xmlEscape(autorizacion.fechaAutorizacion || "")}</fechaAutorizacion>`,
    `  <ambiente>${xmlEscape(autorizacion.ambiente || "")}</ambiente>`,
    `  <comprobante>${wrapCdata(autorizacion.comprobante || "")}</comprobante>`,
    mensajesXml,
    "</autorizacion>"
  ].filter(Boolean).join("\n");
}

async function getVentaBaseConFacturaAutorizada(idVenta) {
  const [[venta]] = await db.query(`
    SELECT
      v.*,
      c.nombres AS cliente_nombres,
      c.cedula AS cliente_cedula,
      c.correo AS cliente_correo,
      c.direccion AS cliente_direccion,
      c.telefono AS cliente_telefono,
      l.nombre_local,
      l.direccion AS local_direccion,
      l.telefono AS local_telefono,
      sd.id_documento_sri AS id_documento_sri_factura,
      sd.clave_acceso AS factura_clave_acceso,
      sd.numero_autorizacion AS factura_numero_autorizacion,
      sd.fecha_autorizacion AS factura_fecha_autorizacion,
      sd.ambiente AS factura_ambiente,
      sd.estado AS factura_estado_documento,
      sd.xml_generado_path AS factura_xml_generado_path,
      sd.xml_firmado_path AS factura_xml_firmado_path,
      sd.xml_autorizado_path AS factura_xml_autorizado_path
    FROM ventas v
    INNER JOIN sri_documentos sd
      ON sd.id_venta = v.id_venta
     AND sd.tipo_comprobante = 'FACTURA'
    INNER JOIN locales l
      ON l.id_local = v.id_local
    LEFT JOIN clientes c
      ON c.id_cliente = v.id_cliente
    WHERE v.id_venta = ?
    ORDER BY sd.id_documento_sri DESC
    LIMIT 1
  `, [idVenta]);

  if (!venta) {
    throw createError("No se encontró una factura SRI para esta venta", 404);
  }

  if (venta.factura_estado_documento !== "AUTORIZADO") {
    throw createError("La factura todavía no está autorizada por el SRI", 409);
  }

  return venta;
}

async function getVentaDetallesSri(idVenta) {
  const [rows] = await db.query(`
    SELECT
      dv.id_detalle,
      dv.id_producto,
      dv.cantidad,
      dv.subtotal,
      p.nombre_producto,
      p.graba_iva,
      p.sku,
      p.codigo_barras
    FROM detalle_venta dv
    INNER JOIN productos p
      ON p.id_producto = dv.id_producto
    WHERE dv.id_venta = ?
    ORDER BY dv.id_detalle ASC
  `, [idVenta]);

  return rows;
}

async function getVentaDetalleAplicacion(executor, idVenta) {
  const [rows] = await executor.query(`
    SELECT
      id_detalle,
      id_producto,
      cantidad,
      imei
    FROM detalle_venta
    WHERE id_venta = ?
    ORDER BY id_detalle ASC
  `, [idVenta]);

  return rows;
}

async function getExistingNotaCreditoByVenta(idVenta) {
  const [[row]] = await db.query(`
    SELECT *
    FROM sri_notas_credito
    WHERE id_venta = ?
    LIMIT 1
  `, [idVenta]);

  return row || null;
}

async function getNotaCreditoById(idNotaCredito) {
  const [[row]] = await db.query(`
    SELECT
      nc.*,
      v.id_local AS venta_id_local,
      v.estado AS venta_estado,
      v.numero_comprobante AS numero_comprobante_factura,
      v.fecha_venta,
      v.total AS venta_total,
      sd.numero_autorizacion AS factura_numero_autorizacion,
      sd.fecha_autorizacion AS factura_fecha_autorizacion,
      l.nombre_local,
      c.nombres AS cliente_nombres,
      c.cedula AS cliente_cedula
    FROM sri_notas_credito nc
    INNER JOIN ventas v
      ON v.id_venta = nc.id_venta
    LEFT JOIN sri_documentos sd
      ON sd.id_venta = v.id_venta
     AND sd.tipo_comprobante = 'FACTURA'
    INNER JOIN locales l
      ON l.id_local = nc.id_local
    LEFT JOIN clientes c
      ON c.id_cliente = v.id_cliente
    WHERE nc.id_nota_credito = ?
    LIMIT 1
  `, [idNotaCredito]);

  return row || null;
}

function mapNotaCreditoItem(row) {
  if (!row) return null;

  return {
    id_nota_credito: Number(row.id_nota_credito),
    id_venta: Number(row.id_venta),
    id_local: Number(row.id_local),
    nombre_local: safeText(row.nombre_local, ""),
    cliente_nombres: safeText(row.cliente_nombres, "CONSUMIDOR FINAL"),
    cliente_cedula: safeText(row.cliente_cedula, ""),
    venta_estado: safeText(row.venta_estado, ""),
    numero_comprobante_factura: safeText(row.numero_comprobante_factura, ""),
    numero_comprobante_nota_credito: safeText(row.numero_comprobante, ""),
    factura_numero_autorizacion: safeText(row.factura_numero_autorizacion, ""),
    factura_fecha_autorizacion: row.factura_fecha_autorizacion || null,
    fecha_venta: row.fecha_venta || null,
    fecha_emision_nota_credito: row.fecha_emision || null,
    fecha_autorizacion: row.fecha_autorizacion || null,
    ambiente: safeText(row.ambiente, ""),
    estado: safeText(row.estado, ""),
    motivo: safeText(row.motivo, ""),
    clave_acceso: safeText(row.clave_acceso, ""),
    numero_autorizacion: safeText(row.numero_autorizacion, ""),
    total_factura: round2(row.total_factura || row.venta_total || 0),
    valor_modificacion: round2(row.valor_modificacion || 0),
    total_sin_impuestos: round2(row.total_sin_impuestos || 0),
    total_impuesto: round2(row.total_impuesto || 0),
    error_codigo: safeText(row.error_codigo, ""),
    error_detalle: safeText(row.error_detalle, ""),
    aplico_anulacion_venta: Number(row.aplico_anulacion_venta || 0) === 1,
    fecha_aplicacion_venta: row.fecha_aplicacion_venta || null,
    ride_url: toUploadUrl(row.ride_path),
    xml_autorizado_url: toUploadUrl(row.xml_autorizado_path)
  };
}

async function generarSecuencialNotaCredito(connection, { idLocalConfig, estab, ptoEmi, excludeIdNotaCredito = 0 }) {
  const whereExclude = excludeIdNotaCredito
    ? " AND id_nota_credito <> ? "
    : "";
  const params = [
    Number(idLocalConfig || 0),
    estab,
    ptoEmi
  ];

  if (excludeIdNotaCredito) {
    params.push(Number(excludeIdNotaCredito));
  }

  const [[row]] = await connection.query(`
    SELECT secuencial
    FROM sri_notas_credito
    WHERE id_local_config = ?
      AND estab = ?
      AND pto_emi = ?
      ${whereExclude}
    ORDER BY CAST(secuencial AS UNSIGNED) DESC
    LIMIT 1
    FOR UPDATE
  `, params);

  const nuevo = row?.secuencial ? Number(row.secuencial) + 1 : 1;
  return padLeft(nuevo, 9);
}

function mergeSriStage(notaCredito, stageName, data) {
  const current = safeJsonParse(notaCredito.respuesta_sri_json, {});
  return JSON.stringify({
    ...current,
    [stageName]: data
  });
}

async function insertNotaCreditoBase(connection, {
  venta,
  config,
  motivo,
  resumen,
  idUsuarioEmisor
}) {
  const idLocalConfig = Number(config.id_local_config || config.id_local || venta.id_local);
  const estab = padLeft(config.establecimiento || "001", 3);
  const ptoEmi = padLeft(config.punto_emision || "001", 3);
  const secuencial = await generarSecuencialNotaCredito(connection, {
    idLocalConfig,
    estab,
    ptoEmi
  });
  const numeroComprobante = `${estab}-${ptoEmi}-${secuencial}`;
  const fechaEmision = getCurrentDateTimeEcSql();

  const [result] = await connection.query(`
    INSERT INTO sri_notas_credito (
      id_local,
      id_local_config,
      id_venta,
      id_documento_sri_factura,
      id_usuario_emisor,
      motivo,
      estado,
      ambiente,
      estab,
      pto_emi,
      secuencial,
      numero_comprobante,
      fecha_emision,
      valor_modificacion,
      total_sin_impuestos,
      total_impuesto
    ) VALUES (?, ?, ?, ?, ?, ?, 'BORRADOR', ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `, [
    venta.id_local,
    idLocalConfig,
    venta.id_venta,
    venta.id_documento_sri_factura,
    idUsuarioEmisor || null,
    motivo,
    config.ambiente,
    estab,
    ptoEmi,
    secuencial,
    numeroComprobante,
    fechaEmision,
    resumen.importeTotal,
    resumen.totalSinImpuestos,
    resumen.totalImpuesto
  ]);

  return {
    id_nota_credito: result.insertId,
    id_local_config: idLocalConfig,
    estab,
    pto_emi: ptoEmi,
    secuencial,
    numero_comprobante: numeroComprobante,
    fecha_emision: fechaEmision,
    motivo
  };
}

async function updateNotaCreditoGenerated({
  idNotaCredito,
  claveAcceso,
  xmlFilePath,
  previewData,
  secuencial = null,
  estab = null,
  ptoEmi = null,
  numeroComprobante = null,
  idLocalConfig = null,
  ambiente = null,
  resumen = null
}) {
  await db.query(`
    UPDATE sri_notas_credito SET
      id_local_config = COALESCE(?, id_local_config),
      ambiente = COALESCE(?, ambiente),
      estab = COALESCE(?, estab),
      pto_emi = COALESCE(?, pto_emi),
      secuencial = COALESCE(?, secuencial),
      numero_comprobante = COALESCE(?, numero_comprobante),
      valor_modificacion = COALESCE(?, valor_modificacion),
      total_sin_impuestos = COALESCE(?, total_sin_impuestos),
      total_impuesto = COALESCE(?, total_impuesto),
      clave_acceso = ?,
      estado = 'XML_GENERADO',
      xml_generado_path = ?,
      xml_firmado_path = NULL,
      xml_autorizado_path = NULL,
      ride_path = NULL,
      numero_autorizacion = NULL,
      fecha_autorizacion = NULL,
      respuesta_sri_json = ?,
      error_codigo = NULL,
      error_detalle = NULL
    WHERE id_nota_credito = ?
  `, [
    idLocalConfig,
    ambiente,
    estab,
    ptoEmi,
    secuencial,
    numeroComprobante,
    resumen ? resumen.importeTotal : null,
    resumen ? resumen.totalSinImpuestos : null,
    resumen ? resumen.totalImpuesto : null,
    claveAcceso,
    xmlFilePath,
    JSON.stringify(previewData),
    idNotaCredito
  ]);
}

async function regenerarNotaCreditoExistente(idNotaCredito) {
  ensureNcDirs();

  const notaCredito = await getNotaCreditoById(idNotaCredito);

  if (!notaCredito) {
    throw createError("La nota de crédito no existe", 404);
  }

  if (String(notaCredito.estado || "").toUpperCase() === "AUTORIZADO") {
    throw createError("La nota de crédito ya está autorizada y no se puede regenerar", 409);
  }

  const venta = await getVentaBaseConFacturaAutorizada(notaCredito.id_venta);
  const config = await getSriConfig(venta.id_local);

  if (!config) {
    throw createError(`El local ${venta.id_local} no tiene configuración SRI guardada`);
  }

  const detallesBase = await getVentaDetallesSri(venta.id_venta);
  const detalles = enrichDetallesWithFacturaXml(venta, detallesBase);
  const comprador = inferComprador(venta);
  const creditoData = buildLineasCredito(detalles, venta);
  const idLocalConfig = Number(config.id_local_config || config.id_local || venta.id_local);
  const estab = padLeft(config.establecimiento || notaCredito.estab || "001", 3);
  const ptoEmi = padLeft(config.punto_emision || notaCredito.pto_emi || "001", 3);
  const connection = await db.getConnection();
  let secuencial = notaCredito.secuencial;
  let numeroComprobante = notaCredito.numero_comprobante;

  try {
    await connection.beginTransaction();
    secuencial = await generarSecuencialNotaCredito(connection, {
      idLocalConfig,
      estab,
      ptoEmi,
      excludeIdNotaCredito: idNotaCredito
    });
    numeroComprobante = `${estab}-${ptoEmi}-${secuencial}`;
    await connection.commit();
  } catch (error) {
    try {
      await connection.rollback();
    } catch (rollbackError) {
      // ignore
    }
    throw error;
  } finally {
    connection.release();
  }

  const claveAcceso = buildClaveAccesoNotaCredito({
    venta,
    config,
    fechaEmision: notaCredito.fecha_emision,
    secuencial
  });

  const xml = buildNotaCreditoXml({
    venta,
    facturaSri: {
      numero_comprobante: venta.numero_comprobante,
      numero_autorizacion: venta.factura_numero_autorizacion
    },
    notaCredito: {
      ...notaCredito,
      estab,
      pto_emi: ptoEmi,
      secuencial,
      numero_comprobante: numeroComprobante,
      motivo: notaCredito.motivo
    },
    config,
    comprador,
    creditoData,
    claveAcceso
  });

  const xmlFilePath = path.join(
    SRI_NC_XML_DIR,
    `nota_credito_${notaCredito.id_nota_credito}_${claveAcceso}.xml`
  );
  fs.writeFileSync(xmlFilePath, xml, "utf8");

  const previewData = {
    regenerado_en: new Date().toISOString(),
    venta: {
      id_venta: venta.id_venta,
      id_local: venta.id_local,
      numero_comprobante: venta.numero_comprobante,
      fecha_venta: venta.fecha_venta,
      total: round2(venta.total)
    },
    nota_credito: {
      id_nota_credito: notaCredito.id_nota_credito,
      numero_comprobante: notaCredito.numero_comprobante,
      fecha_emision: notaCredito.fecha_emision,
      motivo: notaCredito.motivo
    },
    config: {
      id_local: config.id_local,
      ruc: config.ruc,
      razon_social: config.razon_social,
      ambiente: config.ambiente,
      establecimiento: config.establecimiento,
      punto_emision: config.punto_emision
    },
    comprador,
    resumen_nota_credito: {
      totalSinImpuestos: creditoData.resumen.totalSinImpuestos,
      totalDescuento: creditoData.resumen.totalDescuento,
      totalImpuesto: creditoData.resumen.totalImpuesto,
      valorModificacion: creditoData.resumen.importeTotal
    }
  };

  await updateNotaCreditoGenerated({
    idNotaCredito: notaCredito.id_nota_credito,
    claveAcceso,
    xmlFilePath,
    previewData,
    secuencial,
    estab,
    ptoEmi,
    numeroComprobante,
    idLocalConfig,
    ambiente: config.ambiente,
    resumen: creditoData.resumen
  });

  return {
    ...notaCredito,
    estado: "XML_GENERADO",
    id_local_config: idLocalConfig,
    estab,
    pto_emi: ptoEmi,
    secuencial,
    numero_comprobante: numeroComprobante,
    clave_acceso: claveAcceso,
    xml_generado_path: xmlFilePath,
    valor_modificacion: creditoData.resumen.importeTotal,
    total_sin_impuestos: creditoData.resumen.totalSinImpuestos,
    total_impuesto: creditoData.resumen.totalImpuesto
  };
}

async function getNotaCreditoForFirma(idNotaCredito) {
  const [[row]] = await db.query(`
    SELECT *
    FROM sri_notas_credito
    WHERE id_nota_credito = ?
    LIMIT 1
  `, [idNotaCredito]);

  if (!row) {
    throw createError("La nota de crédito no existe", 404);
  }

  if (!row.xml_generado_path || !fs.existsSync(row.xml_generado_path)) {
    throw createError("No se encontró el XML generado de la nota de crédito");
  }

  return row;
}

async function firmarNotaCreditoXml(idNotaCredito) {
  ensureNcDirs();
  const notaCredito = await getNotaCreditoForFirma(idNotaCredito);
  const config = await getSriConfigInternal(notaCredito.id_local);

  if (!config) {
    throw createError(`El local ${notaCredito.id_local} no tiene configuración SRI guardada`);
  }

  if (!config.certificado_path) {
    throw createError("El local no tiene un certificado guardado para firmar");
  }

  const xmlGenerado = fs.readFileSync(notaCredito.xml_generado_path, "utf8");
  const { certBase64, privateKeyDer, publicKeyDer } = await extractPkcs12Material(
    config.certificado_path,
    config.clave_certificado
  );

  const privateKey = await xadesjs.Application.crypto.subtle.importKey(
    "pkcs8",
    privateKeyDer,
    {
      name: "RSASSA-PKCS1-v1_5",
      hash: "SHA-1"
    },
    false,
    ["sign"]
  );

  const publicKey = await xadesjs.Application.crypto.subtle.importKey(
    "spki",
    publicKeyDer,
    {
      name: "RSASSA-PKCS1-v1_5",
      hash: "SHA-1"
    },
    true,
    ["verify"]
  );

  const xmlDoc = xadesjs.Parse(xmlGenerado);
  const referenceId = makeId("Reference-ID-");
  const signedXml = new xadesjs.SignedXml();

  await signedXml.Sign(
    {
      name: "RSASSA-PKCS1-v1_5",
      hash: "SHA-1"
    },
    privateKey,
    xmlDoc,
    {
      keyValue: publicKey,
      x509: [certBase64],
      signingCertificate: {
        certificate: certBase64,
        digestAlgorithm: "SHA-1"
      },
      signingTime: {
        value: new Date()
      },
      references: [{
        id: referenceId,
        uri: "#comprobante",
        hash: "SHA-1",
        transforms: ["enveloped"]
      }]
    }
  );

  signedXml.XmlSignature.SignedInfo.CanonicalizationMethod.Algorithm =
    "http://www.w3.org/TR/2001/REC-xml-c14n-20010315";

  const rawSignedXml = signedXml.toString();
  const signedXmlString = normalizeSignedXml(
    rawSignedXml.startsWith("<?xml")
      ? rawSignedXml
      : `<?xml version="1.0" encoding="UTF-8"?>\n${rawSignedXml}`
  );

  const signedFilePath = path.join(
    SRI_NC_SIGNED_DIR,
    `nota_credito_firmada_${notaCredito.id_nota_credito}_${notaCredito.clave_acceso}.xml`
  );
  fs.writeFileSync(signedFilePath, signedXmlString, "utf8");

  await db.query(`
    UPDATE sri_notas_credito SET
      estado = 'FIRMADO',
      xml_firmado_path = ?,
      error_codigo = NULL,
      error_detalle = NULL
    WHERE id_nota_credito = ?
  `, [signedFilePath, notaCredito.id_nota_credito]);

  return {
    ...notaCredito,
    estado: "FIRMADO",
    xml_firmado_path: signedFilePath
  };
}

async function enviarNotaCreditoFirmada(idNotaCredito) {
  const notaCredito = await getNotaCreditoById(idNotaCredito);

  if (!notaCredito) {
    throw createError("La nota de crédito no existe", 404);
  }

  if (!notaCredito.xml_firmado_path || !fs.existsSync(notaCredito.xml_firmado_path)) {
    throw createError("No existe un XML firmado para la nota de crédito");
  }

  const config = await getSriConfigInternal(notaCredito.id_local);
  if (!config) {
    throw createError(`El local ${notaCredito.id_local} no tiene configuración SRI guardada`);
  }

  const endpoints = getSriSoapEndpoints(config.ambiente);
  const xmlFirmado = fs.readFileSync(notaCredito.xml_firmado_path, "utf8");
  const soapEnvelope = buildRecepcionEnvelope(xmlFirmado);
  const soapResponse = await soapRequest(endpoints.recepcion, soapEnvelope);
  const parsed = parseRecepcionSoapResponse(soapResponse.text);

  if (!parsed.ok) {
    throw createError(`El SRI devolvio un error técnico en recepción: ${parsed.fault.message}`, 502);
  }

  const mergedJson = mergeSriStage(notaCredito, "recepcion", {
    consultado_en: new Date().toISOString(),
    endpoint: endpoints.recepcion,
    http_status: soapResponse.status,
    estado: parsed.estado,
    comprobantes: parsed.comprobantes
  });

  const firstMessage = parsed.comprobantes?.[0]?.mensajes?.[0] || null;
  const allMessages = (parsed.comprobantes || []).flatMap((item) => item.mensajes || []);
  const errorCodigo = firstMessage?.identificador || null;
  const errorDetalle = summarizeMensajes(allMessages) || null;

  if (parsed.estado === "RECIBIDA") {
    await db.query(`
      UPDATE sri_notas_credito SET
        estado = 'RECIBIDO',
        respuesta_sri_json = ?,
        error_codigo = NULL,
        error_detalle = NULL
      WHERE id_nota_credito = ?
    `, [mergedJson, idNotaCredito]);

    return {
      estado: "RECIBIDO",
      recepcion_sri: parsed
    };
  }

  if (parsed.estado === "DEVUELTA") {
    if (isProcessingResponse(allMessages)) {
      await db.query(`
        UPDATE sri_notas_credito SET
          estado = 'ENVIADO',
          respuesta_sri_json = ?,
          error_codigo = NULL,
          error_detalle = NULL
        WHERE id_nota_credito = ?
      `, [mergedJson, idNotaCredito]);

      return {
        estado: "ENVIADO",
        recepcion_sri: parsed,
        mensajes: allMessages
      };
    }

    await db.query(`
      UPDATE sri_notas_credito SET
        estado = 'RECHAZADO',
        respuesta_sri_json = ?,
        error_codigo = ?,
        error_detalle = ?
      WHERE id_nota_credito = ?
    `, [mergedJson, errorCodigo, errorDetalle, idNotaCredito]);

    return {
      estado: "RECHAZADO",
      recepcion_sri: parsed,
      error_codigo: errorCodigo,
      error_detalle: errorDetalle,
      mensajes: allMessages
    };
  }

  throw createError(`El SRI devolvio un estado de recepción no esperado: ${parsed.estado || "SIN_ESTADO"}`, 502);
}

async function asegurarStockProductoVenta(connection, idProducto, idLocal) {
  let [[stock]] = await connection.query(`
    SELECT id_stock, stock_actual
    FROM inventario_stock
    WHERE id_producto = ?
      AND id_local = ?
    LIMIT 1
    FOR UPDATE
  `, [idProducto, idLocal]);

  if (stock) {
    return {
      id_stock: stock.id_stock,
      stock_actual: Number(stock.stock_actual || 0)
    };
  }

  const [result] = await connection.query(`
    INSERT INTO inventario_stock (id_producto, id_local, stock_actual)
    VALUES (?, ?, 0)
  `, [idProducto, idLocal]);

  return {
    id_stock: result.insertId,
    stock_actual: 0
  };
}

async function aplicarAnulacionVentaPorNotaCredito({ idNotaCredito, idUsuario }) {
  await ensureVentaAnulacionSchema();
  await ensureDetalleVentaImeiColumn();

  const connection = await db.getConnection();
  let transactionStarted = false;

  try {
    const [[notaCredito]] = await connection.query(`
      SELECT
        nc.*,
        v.id_local AS venta_id_local,
        v.estado AS venta_estado
      FROM sri_notas_credito nc
      INNER JOIN ventas v
        ON v.id_venta = nc.id_venta
      WHERE nc.id_nota_credito = ?
      LIMIT 1
      FOR UPDATE
    `, [idNotaCredito]);

    if (!notaCredito) {
      throw createError("La nota de crédito no existe", 404);
    }

    if (Number(notaCredito.aplico_anulacion_venta || 0) === 1) {
      return {
        aplicada: true,
        ya_aplicada: true
      };
    }

    if (notaCredito.estado !== "AUTORIZADO") {
      throw createError("La nota de crédito aún no está autorizada por el SRI", 409);
    }

    if (notaCredito.venta_estado === "ANULADA") {
      await connection.query(`
        UPDATE sri_notas_credito SET
          aplico_anulacion_venta = 1,
          fecha_aplicacion_venta = ?,
          detalle_aplicacion_venta = ?
        WHERE id_nota_credito = ?
      `, [
        getCurrentDateTimeEcSql(),
        "La venta ya se encontraba anulada antes de aplicar la nota de crédito.",
        idNotaCredito
      ]);

      return {
        aplicada: true,
        ya_aplicada: true
      };
    }

    const detalle = await getVentaDetalleAplicacion(connection, notaCredito.id_venta);

    if (!detalle.length) {
      throw createError("La venta no tiene detalle para restaurar inventario", 409);
    }

    await connection.beginTransaction();
    transactionStarted = true;

    const referencia = `NC-ANULACION-${notaCredito.id_nota_credito}`;

    for (const item of detalle) {
      const cantidad = Number(item.cantidad || 0);
      const stock = await asegurarStockProductoVenta(
        connection,
        item.id_producto,
        notaCredito.id_local
      );
      const stockNuevo = stock.stock_actual + cantidad;

      await connection.query(`
        UPDATE inventario_stock
        SET stock_actual = ?,
            fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_stock = ?
      `, [stockNuevo, stock.id_stock]);

      if (item.imei) {
        const imei = String(item.imei).trim();
        const [[imeiRow]] = await connection.query(`
          SELECT id_imei, estado
          FROM inventario_imei
          WHERE id_producto = ?
            AND id_local = ?
            AND (imei1 = ? OR imei2 = ?)
          LIMIT 1
          FOR UPDATE
        `, [item.id_producto, notaCredito.id_local, imei, imei]);

        if (!imeiRow) {
          throw createError(`No se encontró el IMEI ${imei} para restaurarlo al inventario`, 409);
        }

        if (String(imeiRow.estado || "").toLowerCase() !== "vendido") {
          throw createError(`El IMEI ${imei} no está en estado vendido y no se puede revertir automáticamente`, 409);
        }

        await connection.query(`
          UPDATE inventario_imei
          SET estado = 'disponible'
          WHERE id_imei = ?
        `, [imeiRow.id_imei]);
      }

      await connection.query(`
        INSERT INTO movimientos_stock (
          id_producto,
          id_local,
          id_usuario,
          tipo,
          motivo,
          cantidad,
          stock_anterior,
          stock_nuevo,
          referencia
        ) VALUES (?, ?, ?, 'ENTRADA', 'AJUSTE', ?, ?, ?, ?)
      `, [
        item.id_producto,
        notaCredito.id_local,
        idUsuario || notaCredito.id_usuario_emisor || null,
        cantidad,
        stock.stock_actual,
        stockNuevo,
        referencia
      ]);
    }

    const fechaAplicacion = getCurrentDateTimeEcSql();
    const motivoAnulacion = `Nota de crédito ${notaCredito.numero_comprobante} autorizada. ${safeText(notaCredito.motivo, "")}`.trim();

    await connection.query(`
      UPDATE ventas
      SET estado = 'ANULADA',
          estado_sri = 'NOTA_CREDITO_AUTORIZADA',
          motivo_anulacion = ?,
          fecha_anulacion = ?,
          id_usuario_anulacion = ?
      WHERE id_venta = ?
        AND id_local = ?
    `, [
      motivoAnulacion.slice(0, 255),
      fechaAplicacion,
      idUsuario || notaCredito.id_usuario_emisor || null,
      notaCredito.id_venta,
      notaCredito.id_local
    ]);

    await connection.query(`
      UPDATE sri_notas_credito
      SET aplico_anulacion_venta = 1,
          fecha_aplicacion_venta = ?,
          detalle_aplicacion_venta = ?
      WHERE id_nota_credito = ?
    `, [
      fechaAplicacion,
      "Venta anulada y stock restaurado correctamente.",
      idNotaCredito
    ]);

    await connection.commit();
    transactionStarted = false;

    return {
      aplicada: true,
      ya_aplicada: false
    };
  } catch (error) {
    if (transactionStarted) {
      await connection.rollback();
    }

    throw error;
  } finally {
    connection.release();
  }
}

function generateRideNotaCreditoPdfBuffer(data) {
  const logoPath = resolveLogoPath();

  return new Promise((resolve, reject) => {
    const doc = new PDFDocument({
      size: "A4",
      margin: 34
    });

    const chunks = [];
    const colors = {
      brand: "#0f3d91",
      brandSoft: "#eff6ff",
      text: "#0f172a",
      muted: "#64748b",
      border: "#cbd5e1",
      panel: "#f8fafc",
      white: "#ffffff",
      success: "#15803d"
    };

    doc.on("data", (chunk) => chunks.push(chunk));
    doc.on("end", () => resolve(Buffer.concat(chunks)));
    doc.on("error", reject);

    const pageWidth = doc.page.width - doc.page.margins.left - doc.page.margins.right;
    const leftX = doc.page.margins.left;
    let y = doc.page.margins.top;

    const drawPanel = (x, currentY, width, height, fillColor = colors.white, radius = 16) => {
      doc.save();
      doc.roundedRect(x, currentY, width, height, radius).fill(fillColor);
      doc.roundedRect(x, currentY, width, height, radius).lineWidth(1).stroke(colors.border);
      doc.restore();
    };

    const drawLabelValue = (x, currentY, label, value, width, valueFontSize = 10) => {
      doc.font("Helvetica-Bold").fontSize(8).fillColor(colors.muted).text(label, x, currentY, { width });
      doc.font("Helvetica").fontSize(valueFontSize).fillColor(colors.text).text(safeText(value, "N/A"), x, currentY + 11, { width });
    };

    const headerPadding = 18;
    const headerGap = 22;
    const headerBoxWidth = 222;
    const headerBoxX = leftX + pageWidth - headerBoxWidth - headerPadding;
    const headerTextX = leftX + 155;
    const headerTextWidth = Math.max(96, headerBoxX - headerTextX - headerGap);
    const emisorTitleFontSize = headerTextWidth <= 132 ? 12 : 15;

    doc.font("Helvetica-Bold").fontSize(emisorTitleFontSize).fillColor(colors.brand);
    const emisorTitulo = safeText(data.emisor_razon_social || comprobanteConfig.emisor.razonSocial);
    const emisorTitleHeight = doc.heightOfString(emisorTitulo, {
      width: headerTextWidth,
      lineGap: 1
    });
    const rucText = `RUC: ${safeText(data.emisor_ruc)}`;
    const dirMatrizText = `Dir. Matriz: ${safeText(data.emisor_dir_matriz)}`;
    const dirEstablecimientoText = `Dir. Establecimiento: ${safeText(data.local_direccion)}`;
    const rucHeight = doc.font("Helvetica").fontSize(9.5).heightOfString(rucText, { width: headerTextWidth });
    const dirMatrizHeight = doc.heightOfString(dirMatrizText, { width: headerTextWidth });
    const dirEstablecimientoHeight = doc.heightOfString(dirEstablecimientoText, { width: headerTextWidth });
    const headerInfoTop = y + 18 + emisorTitleHeight + 6;
    const headerInfoBottom = headerInfoTop
      + rucHeight
      + 3
      + dirMatrizHeight
      + 3
      + dirEstablecimientoHeight;
    const headerBoxBottom = y + 18 + 94;
    const logoBottom = y + 18 + 56;
    const headerHeight = Math.max(
      136,
      headerInfoBottom - y + 16,
      headerBoxBottom - y + 18,
      logoBottom - y + 18
    );

    drawPanel(leftX, y, pageWidth, headerHeight, colors.panel, 18);

    if (logoPath) {
      doc.image(logoPath, leftX + 18, y + 18, { fit: [120, 56], align: "left" });
    }

    doc
      .text(emisorTitulo, headerTextX, y + 14, {
        width: headerTextWidth,
        lineGap: 1
      });

    let headerInfoY = headerInfoTop;

    doc
      .font("Helvetica")
      .fontSize(9.5)
      .fillColor(colors.text)
      .text(rucText, headerTextX, headerInfoY, { width: headerTextWidth });
    headerInfoY += rucHeight + 3;
    doc.text(dirMatrizText, headerTextX, headerInfoY, {
      width: headerTextWidth
    });
    headerInfoY += dirMatrizHeight + 3;
    doc.text(dirEstablecimientoText, headerTextX, headerInfoY, {
      width: headerTextWidth
    });

    drawPanel(headerBoxX, y + 18, headerBoxWidth, 94, colors.white, 16);

    doc.font("Helvetica-Bold").fontSize(13).fillColor(colors.text).text("NOTA DE CREDITO", headerBoxX + 16, y + 32, {
      width: headerBoxWidth - 32,
      align: "center"
    });
    doc.font("Helvetica-Bold").fontSize(10.5).fillColor(colors.brand).text(safeText(data.numero_comprobante), headerBoxX + 16, y + 60, {
      width: headerBoxWidth - 32,
      align: "center"
    });
    doc.font("Helvetica").fontSize(7.2).fillColor(colors.text).text(`Autorizacion: ${safeText(data.numero_autorizacion)}`, headerBoxX + 16, y + 82, {
      width: headerBoxWidth - 32,
      align: "center"
    });

    y += headerHeight + 18;

    const infoPanelHeight = 116;
    drawPanel(leftX, y, pageWidth, infoPanelHeight, colors.white, 16);

    doc.font("Helvetica-Bold").fontSize(11).fillColor(colors.brand).text("Datos del comprobante", leftX + 18, y + 16);
    const infoInnerWidth = pageWidth - 36;
    const infoGap = 18;
    const infoCol1W = Math.round(infoInnerWidth * 0.42);
    const infoCol2W = Math.round(infoInnerWidth * 0.28);
    const infoCol3W = infoInnerWidth - infoCol1W - infoCol2W - (infoGap * 2);
    const infoCol1X = leftX + 18;
    const infoCol2X = infoCol1X + infoCol1W + infoGap;
    const infoCol3X = infoCol2X + infoCol2W + infoGap;

    drawLabelValue(infoCol1X, y + 38, "Cliente", data.cliente_nombres, infoCol1W);
    drawLabelValue(infoCol1X, y + 68, "Identificacion", data.cliente_cedula, infoCol1W);
    drawLabelValue(infoCol2X, y + 38, "Factura modificada", data.numero_comprobante_factura, infoCol2W);
    drawLabelValue(infoCol2X, y + 68, "Fecha factura", formatDateEc(data.fecha_venta), infoCol2W);
    drawLabelValue(infoCol3X, y + 38, "Fecha autorizacion", formatDateEc(data.fecha_autorizacion), infoCol3W);
    drawLabelValue(infoCol3X, y + 68, "Ambiente", data.ambiente, infoCol3W);

    y += infoPanelHeight + 22;

    doc.font("Helvetica-Bold").fontSize(11).fillColor(colors.brand).text("Detalle", leftX, y);
    y += 16;

    const tableInset = 14;
    const tableGap = 10;
    const tableInnerX = leftX + tableInset;
    const tableRight = leftX + pageWidth - tableInset;
    const tableUsableWidth = tableRight - tableInnerX;
    const cantidadWidth = 55;
    const precioWidth = 65;
    const descuentoWidth = 65;
    const subtotalWidth = 82;
    const descripcionWidth = tableUsableWidth - cantidadWidth - precioWidth - descuentoWidth - subtotalWidth - (tableGap * 4);
    const columns = {
      descripcion: { x: tableInnerX, width: descripcionWidth },
      cantidad: { x: tableInnerX + descripcionWidth + tableGap, width: cantidadWidth },
      precio: { x: tableInnerX + descripcionWidth + tableGap + cantidadWidth + tableGap, width: precioWidth },
      descuento: { x: tableInnerX + descripcionWidth + tableGap + cantidadWidth + tableGap + precioWidth + tableGap, width: descuentoWidth },
      subtotal: { x: tableInnerX + descripcionWidth + tableGap + cantidadWidth + tableGap + precioWidth + tableGap + descuentoWidth + tableGap, width: subtotalWidth }
    };

    doc.save();
    doc.roundedRect(leftX, y, pageWidth, 30, 12).fill(colors.brand);
    doc.restore();

    doc.font("Helvetica-Bold").fontSize(9).fillColor(colors.white);
    doc.text("Descripcion", columns.descripcion.x, y + 10, { width: columns.descripcion.width });
    doc.text("Cantidad", columns.cantidad.x, y + 10, { width: columns.cantidad.width, align: "right" });
    doc.text("P. Unit.", columns.precio.x, y + 10, { width: columns.precio.width, align: "right" });
    doc.text("Desc.", columns.descuento.x, y + 10, { width: columns.descuento.width, align: "right" });
    doc.text("Subtotal", columns.subtotal.x, y + 10, { width: columns.subtotal.width, align: "right" });

    y += 36;

    doc.font("Helvetica").fontSize(9.2).fillColor(colors.text);
    data.detalles.forEach((item) => {
      const descHeight = doc.heightOfString(safeText(item.descripcion), {
        width: columns.descripcion.width
      });
      const rowHeight = Math.max(32, descHeight + 16);

      doc.save();
      doc.roundedRect(leftX, y, pageWidth, rowHeight, 10).fill(colors.panel);
      doc.restore();

      doc.text(safeText(item.descripcion), columns.descripcion.x, y + 8, {
        width: columns.descripcion.width
      });
      doc.text(formatMoney(item.cantidad), columns.cantidad.x, y + 8, {
        width: columns.cantidad.width,
        align: "right"
      });
      doc.text(formatMoney(item.precio_unitario), columns.precio.x, y + 8, {
        width: columns.precio.width,
        align: "right"
      });
      doc.text(formatMoney(item.descuento), columns.descuento.x, y + 8, {
        width: columns.descuento.width,
        align: "right"
      });
      doc.text(formatMoney(item.subtotal), columns.subtotal.x, y + 8, {
        width: columns.subtotal.width,
        align: "right"
      });

      y += rowHeight + 8;
    });

    const bottomGap = 18;
    const totalBoxWidth = 170;
    const motiveBoxWidth = pageWidth - totalBoxWidth - bottomGap;
    const motiveTextWidth = motiveBoxWidth - 32;
    const motiveTextHeight = doc.heightOfString(safeText(data.motivo, "Sin motivo"), {
      width: motiveTextWidth,
      lineGap: 2
    });
    const motiveBoxHeight = Math.max(88, motiveTextHeight + 48);
    const totalBoxHeight = Math.max(112, motiveBoxHeight);
    const totalBoxX = leftX + motiveBoxWidth + bottomGap;

    drawPanel(leftX, y, motiveBoxWidth, motiveBoxHeight, colors.white, 14);
    doc.font("Helvetica-Bold").fontSize(11).fillColor(colors.brand).text("Motivo de la modificación", leftX + 16, y + 14);
    doc.font("Helvetica").fontSize(10).fillColor(colors.text).text(safeText(data.motivo, "Sin motivo"), leftX + 16, y + 34, {
      width: motiveTextWidth,
      lineGap: 2
    });

    drawPanel(totalBoxX, y, totalBoxWidth, totalBoxHeight, colors.white, 14);

    const drawTotalRow = (label, value, offsetY, highlight = false) => {
      doc.font(highlight ? "Helvetica-Bold" : "Helvetica").fontSize(highlight ? 11 : 10).fillColor(highlight ? colors.brand : colors.text);
      doc.text(label, totalBoxX + 16, y + offsetY, { width: 68 });
      doc.text(`$ ${formatMoney(value)}`, totalBoxX + 82, y + offsetY, {
        width: totalBoxWidth - 98,
        align: "right"
      });
    };

    drawTotalRow("Subtotal", data.total_sin_impuestos, 18);
    drawTotalRow("IVA", data.total_impuesto, 40);
    drawTotalRow("Total NC", data.valor_modificacion, 66, true);

    y += totalBoxHeight + 22;

    doc.font("Helvetica").fontSize(8.5).fillColor(colors.muted).text(
      "Este RIDE corresponde a una nota de crédito electrónica autorizada por el SRI. Conserve este PDF junto con el XML autorizado para respaldo tributario.",
      leftX,
      y,
      { width: pageWidth, align: "center" }
    );

    doc.end();
  });
}

async function buildRideNotaCreditoData(idNotaCredito) {
  const [[row]] = await db.query(`
    SELECT
      nc.*,
      v.numero_comprobante AS numero_comprobante_factura,
      v.fecha_venta,
      v.subtotal AS venta_subtotal,
      v.impuesto AS venta_impuesto,
      v.descuento AS venta_descuento,
      v.total AS venta_total,
      c.nombres AS cliente_nombres,
      c.cedula AS cliente_cedula,
      c.correo AS cliente_correo,
      c.telefono AS cliente_telefono,
      c.direccion AS cliente_direccion,
      l.nombre_local,
      l.direccion AS local_direccion,
      sd.xml_generado_path AS factura_xml_generado_path,
      sd.xml_firmado_path AS factura_xml_firmado_path,
      sd.xml_autorizado_path AS factura_xml_autorizado_path
    FROM sri_notas_credito nc
    INNER JOIN ventas v
      ON v.id_venta = nc.id_venta
    LEFT JOIN sri_documentos sd
      ON sd.id_venta = v.id_venta
     AND sd.tipo_comprobante = 'FACTURA'
    INNER JOIN locales l
      ON l.id_local = nc.id_local
    LEFT JOIN clientes c
      ON c.id_cliente = v.id_cliente
    WHERE nc.id_nota_credito = ?
    LIMIT 1
  `, [idNotaCredito]);

  if (!row) {
    throw createError("La nota de crédito no existe", 404);
  }

  if (row.estado !== "AUTORIZADO") {
    throw createError("La nota de crédito todavía no está autorizada", 409);
  }

  const config = await getSriConfig(row.id_local);

  if (!config) {
    throw createError("No se encontró la configuración SRI efectiva del local");
  }

  const detallesBase = await getVentaDetallesSri(row.id_venta);
  const detalles = enrichDetallesWithFacturaXml(row, detallesBase);
  const creditoData = buildLineasCredito(detalles, {
    subtotal: row.venta_subtotal,
    impuesto: row.venta_impuesto,
    descuento: row.venta_descuento,
    total: row.venta_total
  });

  return {
    id_nota_credito: row.id_nota_credito,
    numero_comprobante: row.numero_comprobante,
    numero_comprobante_factura: row.numero_comprobante_factura,
    numero_autorizacion: row.numero_autorizacion,
    fecha_autorizacion: row.fecha_autorizacion,
    fecha_venta: row.fecha_venta,
    ambiente: row.ambiente,
    motivo: row.motivo,
    cliente_nombres: safeText(row.cliente_nombres, "CONSUMIDOR FINAL"),
    cliente_cedula: safeText(row.cliente_cedula, ""),
    nombre_local: safeText(row.nombre_local, ""),
    local_direccion: safeText(config.dir_establecimiento || row.local_direccion, row.local_direccion),
    emisor_razon_social: config.razon_social,
    emisor_ruc: config.ruc,
    emisor_dir_matriz: config.dir_matriz,
    total_sin_impuestos: row.total_sin_impuestos,
    total_impuesto: row.total_impuesto,
    valor_modificacion: row.valor_modificacion,
    detalles: creditoData.lineas.map((item) => ({
      descripcion: item.descripcion,
      cantidad: item.cantidad,
      precio_unitario: item.precioUnitarioSinImpuesto,
      descuento: item.descuentoLinea,
      subtotal: item.totalSinImpuesto
    }))
  };
}

async function generarRideNotaCredito(idNotaCredito) {
  ensureNcDirs();
  const rideData = await buildRideNotaCreditoData(idNotaCredito);
  const buffer = await generateRideNotaCreditoPdfBuffer(rideData);
  const rideFilePath = path.join(
    SRI_NC_RIDE_DIR,
    `ride_nota_credito_${idNotaCredito}_${safeText(rideData.numero_comprobante).replace(/[^a-zA-Z0-9-_]/g, "_")}.pdf`
  );

  fs.writeFileSync(rideFilePath, buffer);

  await db.query(`
    UPDATE sri_notas_credito
    SET ride_path = ?
    WHERE id_nota_credito = ?
  `, [rideFilePath, idNotaCredito]);

  return {
    ride_path: rideFilePath,
    ride_url: toUploadUrl(rideFilePath)
  };
}

async function consultarAutorizacionNotaCredito(idNotaCredito, { intentarAplicar = true } = {}) {
  ensureNcDirs();
  let notaCredito = await getNotaCreditoById(idNotaCredito);

  if (!notaCredito) {
    throw createError("La nota de crédito no existe", 404);
  }

  if (!notaCredito.clave_acceso) {
    throw createError("La nota de crédito todavía no tiene clave de acceso", 409);
  }

  if (notaCredito.estado === "AUTORIZADO") {
    let ride = null;

    try {
      ride = await generarRideNotaCredito(idNotaCredito);
    } catch (error) {
      ride = {
        error_ride: error.message
      };
    }

    let aplicacion = null;
    if (intentarAplicar && Number(notaCredito.aplico_anulacion_venta || 0) !== 1) {
      aplicacion = await aplicarAnulacionVentaPorNotaCredito({
        idNotaCredito,
        idUsuario: notaCredito.id_usuario_emisor
      });
    }

    const updated = await getNotaCreditoById(idNotaCredito);

    return {
      ...mapNotaCreditoItem(updated),
      ride,
      aplicacion
    };
  }

  let estadoActual = String(notaCredito.estado || "").toUpperCase();

  if (["RECHAZADO", "ERROR"].includes(estadoActual) && !notaCredito.fecha_autorizacion) {
    await regenerarNotaCreditoExistente(idNotaCredito);
    notaCredito = await getNotaCreditoById(idNotaCredito);
    estadoActual = String(notaCredito?.estado || "").toUpperCase();
  }

  const tieneXmlGenerado = Boolean(
    notaCredito.xml_generado_path &&
    fs.existsSync(notaCredito.xml_generado_path)
  );
  const tieneXmlFirmado = Boolean(
    notaCredito.xml_firmado_path &&
    fs.existsSync(notaCredito.xml_firmado_path)
  );

  if (tieneXmlGenerado && (!tieneXmlFirmado || ["BORRADOR", "XML_GENERADO", "ERROR"].includes(estadoActual))) {
    await firmarNotaCreditoXml(idNotaCredito);
    notaCredito = await getNotaCreditoById(idNotaCredito);
  }

  if (String(notaCredito?.estado || "").toUpperCase() === "FIRMADO") {
    await enviarNotaCreditoFirmada(idNotaCredito);
    notaCredito = await getNotaCreditoById(idNotaCredito);
  }

  if (["RECHAZADO", "ERROR"].includes(String(notaCredito?.estado || "").toUpperCase()) && !notaCredito?.fecha_autorizacion) {
    return {
      ...mapNotaCreditoItem(notaCredito),
      recepcion_rechazada: true
    };
  }

  if (String(notaCredito?.estado || "").toUpperCase() === "AUTORIZADO") {
    return consultarAutorizacionNotaCredito(idNotaCredito, { intentarAplicar });
  }

  const config = await getSriConfigInternal(notaCredito.id_local);

  if (!config) {
    throw createError(`El local ${notaCredito.id_local} no tiene configuración SRI guardada`);
  }

  const endpoints = getSriSoapEndpoints(config.ambiente);
  const soapEnvelope = buildAutorizacionEnvelope(notaCredito.clave_acceso);
  const soapResponse = await soapRequest(endpoints.autorizacion, soapEnvelope);
  const parsed = parseAutorizacionSoapResponse(soapResponse.text);

  if (!parsed.ok) {
    throw createError(`El SRI devolvio un error técnico en autorización: ${parsed.fault.message}`, 502);
  }

  const mergedJson = mergeSriStage(notaCredito, "autorizacion", {
    consultado_en: new Date().toISOString(),
    endpoint: endpoints.autorizacion,
    http_status: soapResponse.status,
    claveAccesoConsultada: parsed.claveAccesoConsultada,
    numeroComprobantes: parsed.numeroComprobantes,
    autorizaciones: parsed.autorizaciones
  });

  const autorizacion = parsed.autorizaciones?.[0] || null;

  if (!autorizacion) {
    const pendingState = notaCredito.estado === "RECHAZADO" && notaCredito.error_codigo === "70"
      ? "ENVIADO"
      : notaCredito.estado;

    await db.query(`
      UPDATE sri_notas_credito SET
        estado = ?,
        respuesta_sri_json = ?,
        error_codigo = CASE
          WHEN estado IN ('RECHAZADO', 'ERROR') THEN error_codigo
          ELSE NULL
        END,
        error_detalle = CASE
          WHEN estado IN ('RECHAZADO', 'ERROR') THEN error_detalle
          ELSE NULL
        END
      WHERE id_nota_credito = ?
    `, [pendingState, mergedJson, idNotaCredito]);

    const updated = await getNotaCreditoById(idNotaCredito);
    return {
      ...mapNotaCreditoItem(updated),
      pendiente_autorizacion: true,
      autorizacion_sri: parsed
    };
  }

  const firstMessage = autorizacion.mensajes?.[0] || null;
  const errorCodigo = firstMessage?.identificador || null;
  const errorDetalle = summarizeMensajes(autorizacion.mensajes || []) || null;

  if (autorizacion.estado === "AUTORIZADO") {
    const xmlAutorizado = buildAuthorizedXml(autorizacion);
    const authorizedFilePath = path.join(
      SRI_NC_AUTH_DIR,
      `nota_credito_autorizada_${idNotaCredito}_${notaCredito.clave_acceso}.xml`
    );

    fs.writeFileSync(authorizedFilePath, xmlAutorizado, "utf8");

    await db.query(`
      UPDATE sri_notas_credito SET
        estado = 'AUTORIZADO',
        xml_autorizado_path = ?,
        numero_autorizacion = ?,
        fecha_autorizacion = ?,
        respuesta_sri_json = ?,
        error_codigo = NULL,
        error_detalle = NULL
      WHERE id_nota_credito = ?
    `, [
      authorizedFilePath,
      autorizacion.numeroAutorizacion || null,
      toMysqlDateTime(autorizacion.fechaAutorizacion),
      mergedJson,
      idNotaCredito
    ]);

    let ride = null;
    try {
      ride = await generarRideNotaCredito(idNotaCredito);
    } catch (error) {
      ride = {
        error_ride: error.message
      };
    }

    let aplicacion = null;
    if (intentarAplicar) {
      aplicacion = await aplicarAnulacionVentaPorNotaCredito({
        idNotaCredito,
        idUsuario: notaCredito.id_usuario_emisor
      });
    }

    const updated = await getNotaCreditoById(idNotaCredito);

    return {
      ...mapNotaCreditoItem(updated),
      autorizacion_sri: parsed,
      ride,
      aplicacion
    };
  }

  if (autorizacion.estado === "NO AUTORIZADO") {
    await db.query(`
      UPDATE sri_notas_credito SET
        estado = 'RECHAZADO',
        numero_autorizacion = ?,
        fecha_autorizacion = ?,
        respuesta_sri_json = ?,
        error_codigo = ?,
        error_detalle = ?
      WHERE id_nota_credito = ?
    `, [
      autorizacion.numeroAutorizacion || null,
      toMysqlDateTime(autorizacion.fechaAutorizacion),
      mergedJson,
      errorCodigo,
      errorDetalle,
      idNotaCredito
    ]);

    const updated = await getNotaCreditoById(idNotaCredito);
    return {
      ...mapNotaCreditoItem(updated),
      autorizacion_sri: parsed,
      mensajes: autorizacion.mensajes || []
    };
  }

  await db.query(`
    UPDATE sri_notas_credito SET
      respuesta_sri_json = ?,
      error_codigo = NULL,
      error_detalle = NULL
    WHERE id_nota_credito = ?
  `, [mergedJson, idNotaCredito]);

  const updated = await getNotaCreditoById(idNotaCredito);
  return {
    ...mapNotaCreditoItem(updated),
    pendiente_autorizacion: true,
    autorizacion_sri: parsed
  };
}

async function reanudarNotaCreditoPendiente(idNotaCredito) {
  let notaCredito = await getNotaCreditoById(idNotaCredito);

  if (!notaCredito) {
    throw createError("La nota de crédito pendiente no existe", 404);
  }

  if (notaCredito.estado === "AUTORIZADO") {
    return consultarAutorizacionNotaCredito(idNotaCredito);
  }

  if (["RECHAZADO", "ERROR"].includes(String(notaCredito.estado || "").toUpperCase()) && !notaCredito.fecha_autorizacion) {
    await regenerarNotaCreditoExistente(idNotaCredito);
    notaCredito = await getNotaCreditoById(idNotaCredito);
  }

  const tieneXmlGenerado = Boolean(
    notaCredito.clave_acceso &&
    notaCredito.xml_generado_path &&
    fs.existsSync(notaCredito.xml_generado_path)
  );

  if (!tieneXmlGenerado) {
    throw createError(
      "La nota de crédito pendiente no tiene XML generado disponible. Revisa la configuración SRI y vuelve a intentarlo.",
      409
    );
  }

  const tieneXmlFirmado = Boolean(
    notaCredito.xml_firmado_path &&
    fs.existsSync(notaCredito.xml_firmado_path)
  );

  if (!tieneXmlFirmado || ["BORRADOR", "XML_GENERADO", "ERROR"].includes(String(notaCredito.estado || "").toUpperCase())) {
    await firmarNotaCreditoXml(idNotaCredito);
  }

  const despuesDeFirmar = await getNotaCreditoById(idNotaCredito);
  const estadoDespuesDeFirmar = String(despuesDeFirmar?.estado || "").toUpperCase();

  if (estadoDespuesDeFirmar === "FIRMADO") {
    await enviarNotaCreditoFirmada(idNotaCredito);
  }

  return consultarAutorizacionNotaCredito(idNotaCredito);
}

async function emitirNotaCreditoDesdeVenta({ id_venta, motivo, user }) {
  await ensureSriNotasCreditoTable();
  ensureNcDirs();

  const idVenta = Number(id_venta || 0);
  const motivoNormalizado = String(motivo || "").trim().slice(0, 300);

  if (!idVenta) {
    throw createError("Debes indicar un id_venta válido");
  }

  if (!motivoNormalizado) {
    throw createError("Debes indicar el motivo de la nota de crédito");
  }

  const venta = await getVentaBaseConFacturaAutorizada(idVenta);

  if (venta.estado === "ANULADA") {
    throw createError("La venta ya está anulada y no puede generar otra nota de crédito", 409);
  }

  if (venta.estado !== "PAGADA") {
    throw createError("Solo se pueden emitir notas de crédito sobre ventas PAGADAS", 409);
  }

  const existente = await getExistingNotaCreditoByVenta(idVenta);

  if (existente) {
    const estadoExistente = String(existente.estado || "").toUpperCase();

    if (estadoExistente === "AUTORIZADO" || Number(existente.aplico_anulacion_venta || 0) === 1) {
      throw createError("Esta venta ya tiene una nota de crédito registrada en el sistema", 409);
    }

    return {
      ...(await reanudarNotaCreditoPendiente(Number(existente.id_nota_credito))),
      reanudada_desde_existente: true
    };
  }

  const config = await getSriConfig(venta.id_local);

  if (!config) {
    throw createError(`El local ${venta.id_local} no tiene configuración SRI guardada`);
  }

  const detallesBase = await getVentaDetallesSri(idVenta);
  const detalles = enrichDetallesWithFacturaXml(venta, detallesBase);
  const comprador = inferComprador(venta);
  const creditoData = buildLineasCredito(detalles, venta);

  const connection = await db.getConnection();

  try {
    await connection.beginTransaction();

    const notaBase = await insertNotaCreditoBase(connection, {
      venta,
      config,
      motivo: motivoNormalizado,
      resumen: creditoData.resumen,
      idUsuarioEmisor: Number(user?.id_usuario || 0) || null
    });

    await connection.commit();

    const claveAcceso = buildClaveAccesoNotaCredito({
      venta,
      config,
      fechaEmision: notaBase.fecha_emision,
      secuencial: notaBase.secuencial
    });

    const xml = buildNotaCreditoXml({
      venta,
      facturaSri: {
        numero_comprobante: venta.numero_comprobante,
        numero_autorizacion: venta.factura_numero_autorizacion
      },
      notaCredito: {
        ...notaBase,
        motivo: motivoNormalizado
      },
      config,
      comprador,
      creditoData,
      claveAcceso
    });

    const xmlFilePath = path.join(
      SRI_NC_XML_DIR,
      `nota_credito_${notaBase.id_nota_credito}_${claveAcceso}.xml`
    );
    fs.writeFileSync(xmlFilePath, xml, "utf8");

    const previewData = {
      generado_en: new Date().toISOString(),
      venta: {
        id_venta: venta.id_venta,
        id_local: venta.id_local,
        numero_comprobante: venta.numero_comprobante,
        fecha_venta: venta.fecha_venta,
        total: round2(venta.total)
      },
      nota_credito: {
        id_nota_credito: notaBase.id_nota_credito,
        numero_comprobante: notaBase.numero_comprobante,
        fecha_emision: notaBase.fecha_emision,
        motivo: motivoNormalizado
      },
      config: {
        id_local: config.id_local,
        ruc: config.ruc,
        razon_social: config.razon_social,
        ambiente: config.ambiente,
        establecimiento: config.establecimiento,
        punto_emision: config.punto_emision
      },
      comprador,
      resumen_nota_credito: {
        totalSinImpuestos: creditoData.resumen.totalSinImpuestos,
        totalDescuento: creditoData.resumen.totalDescuento,
        totalImpuesto: creditoData.resumen.totalImpuesto,
        valorModificacion: creditoData.resumen.importeTotal
      }
    };

    await updateNotaCreditoGenerated({
      idNotaCredito: notaBase.id_nota_credito,
      claveAcceso,
      xmlFilePath,
      previewData,
      secuencial: notaBase.secuencial,
      estab: notaBase.estab,
      ptoEmi: notaBase.pto_emi,
      numeroComprobante: notaBase.numero_comprobante,
      idLocalConfig: notaBase.id_local_config,
      ambiente: config.ambiente,
      resumen: creditoData.resumen
    });

    await firmarNotaCreditoXml(notaBase.id_nota_credito);
    await enviarNotaCreditoFirmada(notaBase.id_nota_credito);
    const finalData = await consultarAutorizacionNotaCredito(notaBase.id_nota_credito);

    return {
      ...finalData,
      xml_generado_url: toUploadUrl(xmlFilePath)
    };
  } catch (error) {
    try {
      await connection.rollback();
    } catch (rollbackError) {
      // ignore
    }
    throw error;
  } finally {
    connection.release();
  }
}

async function listarFacturasParaNotasCredito({
  buscar = "",
  id_local = 0,
  estado_nota_credito = "",
  fecha_desde = "",
  fecha_hasta = ""
} = {}) {
  await ensureSriNotasCreditoTable();

  const params = [];
  let where = `
    WHERE sd.tipo_comprobante = 'FACTURA'
      AND sd.estado = 'AUTORIZADO'
  `;

  if (id_local) {
    where += ` AND v.id_local = ? `;
    params.push(Number(id_local));
  }

  if (fecha_desde) {
    where += ` AND DATE(v.fecha_venta) >= ? `;
    params.push(fecha_desde);
  }

  if (fecha_hasta) {
    where += ` AND DATE(v.fecha_venta) <= ? `;
    params.push(fecha_hasta);
  }

  if (estado_nota_credito === "SIN_NC") {
    where += ` AND nc.id_nota_credito IS NULL `;
  } else if (estado_nota_credito === "PENDIENTE") {
    where += ` AND nc.id_nota_credito IS NOT NULL AND nc.estado IN ('BORRADOR','XML_GENERADO','FIRMADO','ENVIADO','RECIBIDO') `;
  } else if (estado_nota_credito) {
    where += ` AND nc.estado = ? `;
    params.push(estado_nota_credito);
  }

  if (buscar) {
    const like = `%${buscar}%`;
    where += `
      AND (
        CAST(v.id_venta AS CHAR) = ?
        OR v.numero_comprobante LIKE ?
        OR COALESCE(c.nombres, '') LIKE ?
        OR COALESCE(c.cedula, '') LIKE ?
        OR COALESCE(l.nombre_local, '') LIKE ?
        OR COALESCE(nc.numero_comprobante, '') LIKE ?
        OR COALESCE(nc.numero_autorizacion, '') LIKE ?
        OR COALESCE(sd.numero_autorizacion, '') LIKE ?
      )
    `;
    params.push(buscar, like, like, like, like, like, like, like);
  }

  const [rows] = await db.query(`
    SELECT
      v.id_venta,
      v.id_local,
      v.numero_comprobante,
      v.fecha_venta,
      v.total AS total_factura,
      v.estado AS venta_estado,
      v.estado_sri,
      l.nombre_local,
      c.nombres AS cliente_nombres,
      c.cedula AS cliente_cedula,
      sd.id_documento_sri AS id_documento_sri_factura,
      sd.clave_acceso AS factura_clave_acceso,
      sd.numero_autorizacion AS factura_numero_autorizacion,
      DATE_FORMAT(sd.fecha_autorizacion, '%Y-%m-%d %H:%i:%s') AS factura_fecha_autorizacion,
      sd.ambiente AS factura_ambiente,
      nc.id_nota_credito,
      nc.numero_comprobante AS nc_numero_comprobante,
      nc.estado AS nc_estado,
      nc.numero_autorizacion AS nc_numero_autorizacion,
      DATE_FORMAT(nc.fecha_autorizacion, '%Y-%m-%d %H:%i:%s') AS nc_fecha_autorizacion,
      DATE_FORMAT(nc.fecha_emision, '%Y-%m-%d %H:%i:%s') AS nc_fecha_emision,
      nc.motivo,
      nc.valor_modificacion,
      nc.aplico_anulacion_venta,
      nc.ride_path,
      nc.xml_autorizado_path,
      nc.error_codigo,
      nc.error_detalle
    FROM ventas v
    INNER JOIN sri_documentos sd
      ON sd.id_venta = v.id_venta
    INNER JOIN locales l
      ON l.id_local = v.id_local
    LEFT JOIN clientes c
      ON c.id_cliente = v.id_cliente
    LEFT JOIN sri_notas_credito nc
      ON nc.id_venta = v.id_venta
    ${where}
    ORDER BY COALESCE(nc.actualizado_en, sd.fecha_autorizacion, v.fecha_venta) DESC, v.id_venta DESC
    LIMIT 500
  `, params);

  const locales = await listActiveLocales();

  const items = rows.map((row) => ({
    id_venta: Number(row.id_venta),
    id_local: Number(row.id_local),
    nombre_local: safeText(row.nombre_local, "Sin local"),
    numero_comprobante_factura: safeText(row.numero_comprobante, ""),
    fecha_venta: row.fecha_venta || null,
    total_factura: round2(row.total_factura || 0),
    venta_estado: safeText(row.venta_estado, ""),
    estado_sri: safeText(row.estado_sri, ""),
    cliente_nombres: safeText(row.cliente_nombres, "CONSUMIDOR FINAL"),
    cliente_cedula: safeText(row.cliente_cedula, ""),
    factura_numero_autorizacion: safeText(row.factura_numero_autorizacion, ""),
    factura_fecha_autorizacion: row.factura_fecha_autorizacion || null,
    factura_ambiente: safeText(row.factura_ambiente, ""),
    id_nota_credito: row.id_nota_credito ? Number(row.id_nota_credito) : null,
    numero_comprobante_nota_credito: safeText(row.nc_numero_comprobante, ""),
    estado_nota_credito: safeText(row.nc_estado, ""),
    numero_autorizacion_nota_credito: safeText(row.nc_numero_autorizacion, ""),
    fecha_autorizacion_nota_credito: row.nc_fecha_autorizacion || null,
    fecha_emision_nota_credito: row.nc_fecha_emision || null,
    motivo_nota_credito: safeText(row.motivo, ""),
    valor_modificacion: round2(row.valor_modificacion || 0),
    aplico_anulacion_venta: Number(row.aplico_anulacion_venta || 0) === 1,
    ride_url: toUploadUrl(row.ride_path),
    xml_autorizado_url: toUploadUrl(row.xml_autorizado_path),
    error_codigo: safeText(row.error_codigo, ""),
    error_detalle: safeText(row.error_detalle, ""),
    puede_emitir: !row.id_nota_credito && row.venta_estado === "PAGADA",
    puede_consultar: Boolean(row.id_nota_credito) && !["AUTORIZADO"].includes(String(row.nc_estado || "").toUpperCase()),
    puede_reaplicar: Boolean(row.id_nota_credito) && String(row.nc_estado || "").toUpperCase() === "AUTORIZADO" && Number(row.aplico_anulacion_venta || 0) !== 1
  }));

  const resumen = items.reduce((acc, item) => {
    acc.total_facturas += 1;
    if (!item.id_nota_credito) acc.sin_nota_credito += 1;
    if (item.estado_nota_credito === "AUTORIZADO") acc.autorizadas += 1;
    if (item.estado_nota_credito === "RECHAZADO") acc.rechazadas += 1;
    if (["BORRADOR", "XML_GENERADO", "FIRMADO", "ENVIADO", "RECIBIDO"].includes(item.estado_nota_credito)) {
      acc.pendientes += 1;
    }
    if (item.aplico_anulacion_venta) acc.aplicadas += 1;
    return acc;
  }, {
    total_facturas: 0,
    sin_nota_credito: 0,
    autorizadas: 0,
    rechazadas: 0,
    pendientes: 0,
    aplicadas: 0
  });

  return {
    resumen,
    locales: locales.map((local) => ({
      id_local: Number(local.id_local),
      nombre_local: safeText(local.nombre_local, "Sin local")
    })),
    items
  };
}

module.exports = {
  ensureSriNotasCreditoTable,
  listarFacturasParaNotasCredito,
  emitirNotaCreditoDesdeVenta,
  consultarAutorizacionNotaCredito
};
