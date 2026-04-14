const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { createPrivateKey, createPublicKey } = require("crypto");
const { Crypto } = require("@peculiar/webcrypto");
const xadesjs = require("xadesjs");
const xpath = require("xpath");
const xmldom = require("@xmldom/xmldom");
const db = require("../db/db");
const { createError, resolveCertificatePath } = require("./sri-certificado.service");
const { ensureSriTables, getSriConfigInternal } = require("./sri-config.service");

xadesjs.Application.setEngine("NodeJS", new Crypto());
xadesjs.setNodeDependencies({
  XMLSerializer: xmldom.XMLSerializer,
  DOMParser: xmldom.DOMParser,
  DOMImplementation: xmldom.DOMImplementation,
  xpath
});

const UPLOADS_ROOT = path.resolve(__dirname, "../uploads");
const SRI_XML_SIGNED_DIR = path.join(UPLOADS_ROOT, "sri-xml", "firmados");

function ensureSignedDir() {
  fs.mkdirSync(SRI_XML_SIGNED_DIR, { recursive: true });
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
    certPem,
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

async function getDocumentoPendiente(idVenta) {
  const [[row]] = await db.query(`
    SELECT
      sd.*,
      v.id_local,
      v.numero_comprobante,
      v.clave_acceso,
      v.fecha_venta
    FROM sri_documentos sd
    INNER JOIN ventas v
      ON v.id_venta = sd.id_venta
    WHERE sd.id_venta = ?
      AND sd.tipo_comprobante = 'FACTURA'
    LIMIT 1
  `, [idVenta]);

  if (!row) {
    throw createError("Primero debes generar el XML base de la factura");
  }

  if (!row.xml_generado_path || !fs.existsSync(row.xml_generado_path)) {
    throw createError("No se encontró el archivo XML generado para esta venta");
  }

  return row;
}

async function firmarFacturaXmlDesdeVenta({ id_venta, user = null }) {
  await ensureSriTables();
  ensureSignedDir();

  const idVenta = Number(id_venta || 0);
  if (!idVenta) {
    throw createError("Debes indicar un id_venta válido");
  }

  const documento = await getDocumentoPendiente(idVenta);

  if (user && Array.isArray(user.roles) && !user.roles.includes("ADMIN")) {
    if (Number(user.id_local || 0) !== Number(documento.id_local || 0)) {
      throw createError("No puedes firmar una venta de otro local", 403);
    }
  }

  const config = await getSriConfigInternal(documento.id_local);
  if (!config) {
    throw createError(`El local ${documento.id_local} no tiene configuración SRI guardada`);
  }

  if (!config.certificado_path) {
    throw createError("El local no tiene un certificado guardado para firmar");
  }

  const xmlGenerado = fs.readFileSync(documento.xml_generado_path, "utf8");
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
    SRI_XML_SIGNED_DIR,
    `factura_firmada_${idVenta}_${documento.clave_acceso}.xml`
  );
  fs.writeFileSync(signedFilePath, signedXmlString, "utf8");

  await db.query(`
    UPDATE sri_documentos SET
      estado = 'FIRMADO',
      xml_firmado_path = ?,
      error_codigo = NULL,
      error_detalle = NULL
    WHERE id_venta = ?
      AND tipo_comprobante = 'FACTURA'
  `, [signedFilePath, idVenta]);

  const [[updatedDocumento]] = await db.query(`
    SELECT *
    FROM sri_documentos
    WHERE id_venta = ?
      AND tipo_comprobante = 'FACTURA'
    LIMIT 1
  `, [idVenta]);

  return {
    id_documento_sri: updatedDocumento?.id_documento_sri || documento.id_documento_sri,
    id_venta: idVenta,
    id_local: documento.id_local,
    clave_acceso: documento.clave_acceso,
    estado: "FIRMADO",
    xml_generado_path: documento.xml_generado_path,
    xml_firmado_path: signedFilePath,
    xml_firmado_url: (() => {
      const relativePath = path.relative(UPLOADS_ROOT, signedFilePath).split(path.sep).join("/");
      return `/api/uploads/${relativePath}`;
    })(),
    xml_firmado: signedXmlString
  };
}

module.exports = {
  firmarFacturaXmlDesdeVenta
};
