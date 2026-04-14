module.exports = {
  smtp: {
    host: process.env.SMTP_HOST || "mail.connectriobamba.com",
    port: Number(process.env.SMTP_PORT || 465),
    secure: String(process.env.SMTP_SECURE || "true").toLowerCase() === "true",
    user: process.env.SMTP_USER || "noreply@connectriobamba.com",
    pass: process.env.SMTP_PASS || "Connect2026",
    fromName: process.env.SMTP_FROM_NAME || "Connect",
    fromEmail: process.env.SMTP_FROM_EMAIL || "noreply@connectriobamba.com"
  },
  emisor: {
    razonSocial: process.env.COMPROBANTE_RAZON_SOCIAL || "YUQUILEMA CEPEDA JOSE JULIO",
    nombreComercial: process.env.COMPROBANTE_NOMBRE_COMERCIAL || "CONNECT IMPORTADORA TECNOLÓGICA",
    ruc: process.env.COMPROBANTE_RUC || "0604614248001",
    matriz: process.env.COMPROBANTE_MATRIZ || "Riobamba - Ecuador",
    obligadoContabilidad: process.env.COMPROBANTE_OBLIGADO_CONTABILIDAD || "NO",
    ambiente: process.env.COMPROBANTE_AMBIENTE || "PRODICCIÓN"
  },
  logoPath:
    process.env.COMPROBANTE_LOGO_PATH ||
    "../assets/connect.png"
};
