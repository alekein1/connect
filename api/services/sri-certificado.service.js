const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");

function createError(message, status = 400) {
  const error = new Error(message);
  error.status = status;
  return error;
}

function runOpenSsl(args, options = {}) {
  try {
    return execFileSync("openssl", args, {
      encoding: "utf8",
      stdio: ["pipe", "pipe", "pipe"],
      ...options
    });
  } catch (error) {
    if (error.code === "ENOENT") {
      throw createError(
        "OpenSSL no esta disponible en el servidor. Instalala manualmente antes de probar la firma electronica.",
        500
      );
    }

    const stderr = error.stderr ? String(error.stderr).trim() : "";
    const stdout = error.stdout ? String(error.stdout).trim() : "";
    const detail = stderr || stdout || error.message;

    if (/invalid password|mac verify error|password/i.test(detail)) {
      throw createError("La clave del certificado es incorrecta o el archivo no corresponde a un PKCS12 valido");
    }

    if (/No such file|No existe el archivo|cannot open/i.test(detail)) {
      throw createError("No se pudo abrir el archivo del certificado");
    }

    throw createError(`OpenSSL no pudo procesar el certificado: ${detail}`, 500);
  }
}

function extractPemBlock(text, beginLabel, endLabel) {
  const pattern = new RegExp(`-----BEGIN ${beginLabel}-----[\\s\\S]+?-----END ${endLabel}-----`);
  const match = String(text || "").match(pattern);
  return match ? match[0] : null;
}

function parseLineValue(text, prefix) {
  const line = String(text || "")
    .split("\n")
    .map(item => item.trim())
    .find(item => item.startsWith(prefix));

  return line ? line.slice(prefix.length).trim() : null;
}

function normalizeDate(value) {
  if (!value) return null;
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? null : date;
}

function diffDays(fromDate, toDate) {
  if (!fromDate || !toDate) return null;
  const ms = toDate.getTime() - fromDate.getTime();
  return Math.ceil(ms / (1000 * 60 * 60 * 24));
}

function inferRuc(certText, subjectLine) {
  const sources = [subjectLine, certText].filter(Boolean);

  for (const source of sources) {
    const matches = source.match(/\b\d{13}\b/g);
    if (matches && matches.length > 0) {
      return matches[0];
    }
  }

  return null;
}

function resolveCertificatePath(rawPath) {
  if (!rawPath) {
    throw createError("Debes enviar un archivo certificado o una ruta_certificado");
  }

  const normalized = path.isAbsolute(rawPath)
    ? rawPath
    : path.resolve(__dirname, "..", rawPath);

  if (!fs.existsSync(normalized)) {
    throw createError("El archivo del certificado no existe en la ruta indicada");
  }

  const ext = path.extname(normalized).toLowerCase();
  if (![".p12", ".pfx"].includes(ext)) {
    throw createError("El certificado debe estar en formato .p12 o .pfx");
  }

  return normalized;
}

function probarCertificadoPkcs12({ certPath, password }) {
  if (!password) {
    throw createError("Debes enviar la clave_certificado");
  }

  const normalizedPath = resolveCertificatePath(certPath);
  const env = {
    ...process.env,
    SRI_CERT_PASS: String(password)
  };

  runOpenSsl(["version"]);

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
  const privateKeyPem =
    extractPemBlock(privateKeyBundle, "PRIVATE KEY", "PRIVATE KEY") ||
    extractPemBlock(privateKeyBundle, "ENCRYPTED PRIVATE KEY", "ENCRYPTED PRIVATE KEY");

  if (!certPem) {
    throw createError("No se pudo extraer el certificado publico del archivo PKCS12");
  }

  if (!privateKeyPem) {
    throw createError("No se pudo extraer la clave privada del archivo PKCS12");
  }

  const certInfo = runOpenSsl([
    "x509",
    "-noout",
    "-subject",
    "-issuer",
    "-dates",
    "-serial",
    "-fingerprint",
    "-sha256"
  ], {
    input: certPem
  });

  const certText = runOpenSsl([
    "x509",
    "-noout",
    "-text"
  ], {
    input: certPem
  });

  const subject = parseLineValue(certInfo, "subject=");
  const issuer = parseLineValue(certInfo, "issuer=");
  const validFromRaw = parseLineValue(certInfo, "notBefore=");
  const validToRaw = parseLineValue(certInfo, "notAfter=");
  const serial = parseLineValue(certInfo, "serial=");
  const fingerprint = parseLineValue(certInfo, "sha256 Fingerprint=");

  const validFrom = normalizeDate(validFromRaw);
  const validTo = normalizeDate(validToRaw);
  const now = new Date();
  const expiresInDays = diffDays(now, validTo);
  const expired = validTo ? validTo.getTime() < now.getTime() : false;
  const ruc = inferRuc(certText, subject);

  const warnings = [];

  if (!ruc) {
    warnings.push("No se pudo inferir un RUC de 13 digitos desde el certificado");
  }

  if (expired) {
    warnings.push("El certificado ya esta vencido");
  } else if (expiresInDays !== null && expiresInDays <= 30) {
    warnings.push(`El certificado vence pronto: ${expiresInDays} dia(s)`);
  }

  return {
    certificado: {
      archivo: path.basename(normalizedPath),
      ruta: normalizedPath,
      tamano_bytes: fs.statSync(normalizedPath).size,
      extension: path.extname(normalizedPath).toLowerCase()
    },
    emisor: {
      subject,
      issuer,
      ruc_inferido: ruc
    },
    vigencia: {
      valido_desde: validFrom ? validFrom.toISOString() : null,
      valido_hasta: validTo ? validTo.toISOString() : null,
      vencido: expired,
      dias_para_vencer: expiresInDays
    },
    seguridad: {
      serial,
      fingerprint_sha256: fingerprint,
      tiene_clave_privada: true
    },
    warnings
  };
}

module.exports = {
  createError,
  probarCertificadoPkcs12,
  resolveCertificatePath
};
