const fs = require("fs");
const path = require("path");
const xpath = require("xpath");
const { DOMParser } = require("@xmldom/xmldom");
const db = require("../db/db");
const { createError } = require("./sri-certificado.service");
const { ensureSriTables, getSriConfigInternal } = require("./sri-config.service");

const UPLOADS_ROOT = path.resolve(__dirname, "../uploads");
const SRI_XML_AUTHORIZED_DIR = path.join(UPLOADS_ROOT, "sri-xml", "autorizados");

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

function ensureAuthorizedDir() {
  fs.mkdirSync(SRI_XML_AUTHORIZED_DIR, { recursive: true });
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
  const relativePath = path.relative(UPLOADS_ROOT, filePath).split(path.sep).join("/");
  return `/api/uploads/${relativePath}`;
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

async function getSriDocumentoVenta(idVenta) {
  const [[row]] = await db.query(`
    SELECT
      sd.*,
      v.id_local,
      v.numero_comprobante,
      v.clave_acceso,
      v.estado_sri
    FROM sri_documentos sd
    INNER JOIN ventas v
      ON v.id_venta = sd.id_venta
    WHERE sd.id_venta = ?
      AND sd.tipo_comprobante = 'FACTURA'
    LIMIT 1
  `, [idVenta]);

  if (!row) {
    throw createError("No existe un documento SRI para esta venta. Primero genera el XML");
  }

  return row;
}

function ensureFacturaAccess(documento, user) {
  if (!user) return;
  if (!Array.isArray(user.roles) || user.roles.includes("ADMIN")) return;

  if (Number(user.id_local || 0) !== Number(documento.id_local || 0)) {
    throw createError("No puedes procesar una venta de otro local", 403);
  }
}

function mergeSriStage(documento, stageName, data) {
  const current = safeJsonParse(documento.respuesta_sri_json, {});
  return JSON.stringify({
    ...current,
    [stageName]: data
  });
}

async function updateSriDocumentoAfterRecepcion(documento, payload) {
  await db.query(`
    UPDATE sri_documentos SET
      estado = ?,
      respuesta_sri_json = ?,
      error_codigo = ?,
      error_detalle = ?
    WHERE id_documento_sri = ?
  `, [
    payload.estado_documento,
    payload.respuesta_sri_json,
    payload.error_codigo,
    payload.error_detalle,
    documento.id_documento_sri
  ]);

  if (payload.estado_venta) {
    await db.query(`
      UPDATE ventas SET
        estado_sri = ?
      WHERE id_venta = ?
    `, [payload.estado_venta, documento.id_venta]);
  }
}

async function updateSriDocumentoAfterAutorizacion(documento, payload) {
  await db.query(`
    UPDATE sri_documentos SET
      estado = ?,
      xml_autorizado_path = ?,
      numero_autorizacion = ?,
      fecha_autorizacion = ?,
      respuesta_sri_json = ?,
      error_codigo = ?,
      error_detalle = ?
    WHERE id_documento_sri = ?
  `, [
    payload.estado_documento,
    payload.xml_autorizado_path,
    payload.numero_autorizacion,
    payload.fecha_autorizacion,
    payload.respuesta_sri_json,
    payload.error_codigo,
    payload.error_detalle,
    documento.id_documento_sri
  ]);

  if (payload.estado_venta) {
    await db.query(`
      UPDATE ventas SET
        estado_sri = ?,
        numero_autorizacion = COALESCE(?, numero_autorizacion)
      WHERE id_venta = ?
    `, [payload.estado_venta, payload.numero_autorizacion, documento.id_venta]);
  }
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

async function enviarFacturaFirmadaSriDesdeVenta({ id_venta, user = null }) {
  await ensureSriTables();

  const idVenta = Number(id_venta || 0);
  if (!idVenta) {
    throw createError("Debes indicar un id_venta válido");
  }

  const documento = await getSriDocumentoVenta(idVenta);
  ensureFacturaAccess(documento, user);

  if (!documento.xml_firmado_path || !fs.existsSync(documento.xml_firmado_path)) {
    throw createError("No existe un XML firmado para esta venta. Primero firma el comprobante");
  }

  const config = await getSriConfigInternal(documento.id_local);
  if (!config) {
    throw createError(`El local ${documento.id_local} no tiene configuración SRI guardada`);
  }

  const endpoints = getSriSoapEndpoints(config.ambiente);
  const xmlFirmado = fs.readFileSync(documento.xml_firmado_path, "utf8");
  const soapEnvelope = buildRecepcionEnvelope(xmlFirmado);
  const soapResponse = await soapRequest(endpoints.recepcion, soapEnvelope);
  const parsed = parseRecepcionSoapResponse(soapResponse.text);

  if (!parsed.ok) {
    throw createError(`El SRI devolvio un error tecnico en recepción: ${parsed.fault.message}`, 502);
  }

  const mergedJson = mergeSriStage(documento, "recepcion", {
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
    await updateSriDocumentoAfterRecepcion(documento, {
      estado_documento: "RECIBIDO",
      respuesta_sri_json: mergedJson,
      error_codigo: null,
      error_detalle: null,
      estado_venta: "PENDIENTE"
    });

    return {
      id_documento_sri: documento.id_documento_sri,
      id_venta: documento.id_venta,
      id_local: documento.id_local,
      clave_acceso: documento.clave_acceso,
      ambiente: config.ambiente,
      estado: "RECIBIDO",
      recepcion_sri: parsed,
      xml_firmado_path: documento.xml_firmado_path,
      xml_firmado_url: toUploadUrl(documento.xml_firmado_path)
    };
  }

  if (parsed.estado === "DEVUELTA") {
    if (isProcessingResponse(allMessages)) {
      await updateSriDocumentoAfterRecepcion(documento, {
        estado_documento: "ENVIADO",
        respuesta_sri_json: mergedJson,
        error_codigo: null,
        error_detalle: null,
        estado_venta: "PENDIENTE"
      });

      return {
        id_documento_sri: documento.id_documento_sri,
        id_venta: documento.id_venta,
        id_local: documento.id_local,
        clave_acceso: documento.clave_acceso,
        ambiente: config.ambiente,
        estado: "ENVIADO",
        recepcion_sri: parsed,
        mensajes: allMessages
      };
    }

    await updateSriDocumentoAfterRecepcion(documento, {
      estado_documento: "RECHAZADO",
      respuesta_sri_json: mergedJson,
      error_codigo: errorCodigo,
      error_detalle: errorDetalle,
      estado_venta: "RECHAZADA"
    });

    return {
      id_documento_sri: documento.id_documento_sri,
      id_venta: documento.id_venta,
      id_local: documento.id_local,
      clave_acceso: documento.clave_acceso,
      ambiente: config.ambiente,
      estado: "RECHAZADO",
      recepcion_sri: parsed,
      error_codigo: errorCodigo,
      error_detalle: errorDetalle,
      mensajes: allMessages
    };
  }

  throw createError(`El SRI devolvio un estado de recepción no esperado: ${parsed.estado || "SIN_ESTADO"}`, 502);
}

async function consultarAutorizacionFacturaSriDesdeVenta({ id_venta, user = null }) {
  await ensureSriTables();
  ensureAuthorizedDir();

  const idVenta = Number(id_venta || 0);
  if (!idVenta) {
    throw createError("Debes indicar un id_venta válido");
  }

  const documento = await getSriDocumentoVenta(idVenta);
  ensureFacturaAccess(documento, user);

  if (!documento.clave_acceso) {
    throw createError("La venta todavía no tiene clave de acceso");
  }

  const config = await getSriConfigInternal(documento.id_local);
  if (!config) {
    throw createError(`El local ${documento.id_local} no tiene configuración SRI guardada`);
  }

  const endpoints = getSriSoapEndpoints(config.ambiente);
  const soapEnvelope = buildAutorizacionEnvelope(documento.clave_acceso);
  const soapResponse = await soapRequest(endpoints.autorizacion, soapEnvelope);
  const parsed = parseAutorizacionSoapResponse(soapResponse.text);

  if (!parsed.ok) {
    throw createError(`El SRI devolvio un error tecnico en autorización: ${parsed.fault.message}`, 502);
  }

  const mergedJson = mergeSriStage(documento, "autorizacion", {
    consultado_en: new Date().toISOString(),
    endpoint: endpoints.autorizacion,
    http_status: soapResponse.status,
    claveAccesoConsultada: parsed.claveAccesoConsultada,
    numeroComprobantes: parsed.numeroComprobantes,
    autorizaciones: parsed.autorizaciones
  });

  const autorizacion = parsed.autorizaciones?.[0] || null;

  if (!autorizacion) {
    const pendingState = documento.estado === "RECHAZADO" && documento.error_codigo === "70"
      ? "ENVIADO"
      : documento.estado;

    await db.query(`
      UPDATE sri_documentos SET
        estado = ?,
        respuesta_sri_json = ?,
        error_codigo = NULL,
        error_detalle = NULL
      WHERE id_documento_sri = ?
    `, [pendingState, mergedJson, documento.id_documento_sri]);

    if (pendingState === "ENVIADO") {
      await db.query(`
        UPDATE ventas SET
          estado_sri = 'PENDIENTE'
        WHERE id_venta = ?
      `, [documento.id_venta]);
    }

    return {
      id_documento_sri: documento.id_documento_sri,
      id_venta: documento.id_venta,
      id_local: documento.id_local,
      clave_acceso: documento.clave_acceso,
      ambiente: config.ambiente,
      estado: pendingState,
      autorizado: false,
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
      SRI_XML_AUTHORIZED_DIR,
      `factura_autorizada_${idVenta}_${documento.clave_acceso}.xml`
    );

    fs.writeFileSync(authorizedFilePath, xmlAutorizado, "utf8");

    await updateSriDocumentoAfterAutorizacion(documento, {
      estado_documento: "AUTORIZADO",
      xml_autorizado_path: authorizedFilePath,
      numero_autorizacion: autorizacion.numeroAutorizacion || null,
      fecha_autorizacion: toMysqlDateTime(autorizacion.fechaAutorizacion),
      respuesta_sri_json: mergedJson,
      error_codigo: null,
      error_detalle: null,
      estado_venta: "AUTORIZADA"
    });

    return {
      id_documento_sri: documento.id_documento_sri,
      id_venta: documento.id_venta,
      id_local: documento.id_local,
      clave_acceso: documento.clave_acceso,
      ambiente: config.ambiente,
      estado: "AUTORIZADO",
      numero_autorizacion: autorizacion.numeroAutorizacion || null,
      fecha_autorizacion: autorizacion.fechaAutorizacion || null,
      xml_autorizado_path: authorizedFilePath,
      xml_autorizado_url: toUploadUrl(authorizedFilePath),
      autorizacion_sri: parsed,
      xml_autorizado: xmlAutorizado
    };
  }

  if (autorizacion.estado === "NO AUTORIZADO") {
    await updateSriDocumentoAfterAutorizacion(documento, {
      estado_documento: "RECHAZADO",
      xml_autorizado_path: null,
      numero_autorizacion: autorizacion.numeroAutorizacion || null,
      fecha_autorizacion: toMysqlDateTime(autorizacion.fechaAutorizacion),
      respuesta_sri_json: mergedJson,
      error_codigo: errorCodigo,
      error_detalle: errorDetalle,
      estado_venta: "RECHAZADA"
    });

    return {
      id_documento_sri: documento.id_documento_sri,
      id_venta: documento.id_venta,
      id_local: documento.id_local,
      clave_acceso: documento.clave_acceso,
      ambiente: config.ambiente,
      estado: "RECHAZADO",
      numero_autorizacion: autorizacion.numeroAutorizacion || null,
      fecha_autorizacion: autorizacion.fechaAutorizacion || null,
      autorizacion_sri: parsed,
      error_codigo: errorCodigo,
      error_detalle: errorDetalle,
      mensajes: autorizacion.mensajes || []
    };
  }

  await db.query(`
    UPDATE sri_documentos SET
      respuesta_sri_json = ?,
      error_codigo = NULL,
      error_detalle = NULL
    WHERE id_documento_sri = ?
  `, [mergedJson, documento.id_documento_sri]);

  return {
    id_documento_sri: documento.id_documento_sri,
    id_venta: documento.id_venta,
    id_local: documento.id_local,
    clave_acceso: documento.clave_acceso,
    ambiente: config.ambiente,
    estado: documento.estado,
    autorizado: false,
    pendiente_autorizacion: true,
    autorizacion_sri: parsed
  };
}

module.exports = {
  enviarFacturaFirmadaSriDesdeVenta,
  consultarAutorizacionFacturaSriDesdeVenta
};
