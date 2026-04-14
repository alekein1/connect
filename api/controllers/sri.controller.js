const fs = require("fs");
const {
  createError,
  probarCertificadoPkcs12,
  resolveCertificatePath
} = require("../services/sri-certificado.service");
const {
  ensureSriTables,
  getSriConfig,
  listActiveLocales,
  saveSriConfig
} = require("../services/sri-config.service");
const {
  generarFacturaXmlDesdeVenta
} = require("../services/sri-factura.service");
const {
  firmarFacturaXmlDesdeVenta
} = require("../services/sri-firma.service");
const {
  enviarFacturaFirmadaSriDesdeVenta,
  consultarAutorizacionFacturaSriDesdeVenta
} = require("../services/sri-ws.service");
const {
  generarRideFacturaSriDesdeVenta,
  enviarFacturaSriPorCorreoDesdeVenta
} = require("../services/sri-ride.service");

function cleanupFile(filePath) {
  if (!filePath) return;
  if (!fs.existsSync(filePath)) return;

  try {
    fs.unlinkSync(filePath);
  } catch (error) {
    console.error("⚠️ No se pudo eliminar archivo temporal SRI:", error.message);
  }
}

exports.infoSri = async (req, res) => {
  try {
    await ensureSriTables();
    const locales = await listActiveLocales();

    res.json({
      ok: true,
      data: {
        modulo: "SRI",
        estado: "BASE_INICIAL",
        descripcion: "Modulo separado para iniciar facturacion electronica sin tocar ventas.controller.js",
        locales_prueba: locales,
        tablas: [
          "sri_configuraciones",
          "sri_documentos"
        ],
        endpoints: [
          {
            metodo: "POST",
            ruta: "/api/sri/certificado/probar",
            descripcion: "Valida un archivo .p12/.pfx y devuelve informacion del certificado"
          },
          {
            metodo: "GET",
            ruta: "/api/sri/pruebas/configuracion?id_local=1",
            descripcion: "Carga la configuracion fiscal SRI de un local"
          },
          {
            metodo: "POST",
            ruta: "/api/sri/pruebas/configuracion",
            descripcion: "Guarda la configuracion fiscal SRI de un local"
          },
          {
            metodo: "POST",
            ruta: "/api/sri/pruebas/facturas/:id_venta/xml",
            descripcion: "Genera la clave de acceso y el XML base de una factura desde una venta ya registrada"
          },
          {
            metodo: "POST",
            ruta: "/api/sri/pruebas/facturas/:id_venta/firmar",
            descripcion: "Firma el XML generado con el certificado del local y guarda el xml_firmado"
          },
          {
            metodo: "POST",
            ruta: "/api/sri/pruebas/facturas/:id_venta/enviar",
            descripcion: "Envia el XML firmado al servicio de recepción del SRI"
          },
          {
            metodo: "POST",
            ruta: "/api/sri/pruebas/facturas/:id_venta/autorizar",
            descripcion: "Consulta la autorización del comprobante en el SRI por clave de acceso"
          },
          {
            metodo: "POST",
            ruta: "/api/sri/pruebas/facturas/:id_venta/ride",
            descripcion: "Genera el PDF RIDE desde un comprobante ya autorizado"
          },
          {
            metodo: "POST",
            ruta: "/api/sri/pruebas/facturas/:id_venta/email",
            descripcion: "Envía por correo el RIDE PDF junto con el XML autorizado"
          }
        ]
      }
    });
  } catch (error) {
    console.error("❌ infoSri:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al obtener informacion del modulo SRI"
    });
  }
};

exports.listarLocalesPruebas = async (req, res) => {
  try {
    await ensureSriTables();
    const data = await listActiveLocales();

    res.json({
      ok: true,
      data
    });
  } catch (error) {
    console.error("❌ listarLocalesPruebas:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al listar locales de prueba"
    });
  }
};

exports.probarCertificado = async (req, res) => {
  let tempFilePath = null;

  try {
    const claveCertificado = req.body?.clave_certificado;
    const rutaCertificado = req.body?.ruta_certificado;

    let certPath;

    if (req.file?.path) {
      certPath = req.file.path;
      tempFilePath = req.file.path;
    } else if (rutaCertificado) {
      certPath = resolveCertificatePath(rutaCertificado);
    } else {
      throw createError("Debes adjuntar un certificado o enviar ruta_certificado");
    }

    const data = probarCertificadoPkcs12({
      certPath,
      password: claveCertificado
    });

    res.json({
      ok: true,
      mensaje: "Certificado validado correctamente",
      data
    });
  } catch (error) {
    console.error("❌ probarCertificado:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al validar el certificado"
    });
  } finally {
    cleanupFile(tempFilePath);
  }
};

function resolveIdLocal(req, source = "auth") {
  if (source === "auth") {
    return Number(req.user?.id_local || 0);
  }

  return Number(req.query?.id_local || req.body?.id_local || 0);
}

exports.obtenerConfiguracionSri = async (req, res) => {
  try {
    const idLocal = resolveIdLocal(req, "auth");

    if (!idLocal) {
      throw createError("No se pudo resolver el id_local del usuario autenticado");
    }

    const data = await getSriConfig(idLocal);

    res.json({
      ok: true,
      data
    });
  } catch (error) {
    console.error("❌ obtenerConfiguracionSri:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al obtener configuracion SRI"
    });
  }
};

exports.obtenerConfiguracionSriPruebas = async (req, res) => {
  try {
    const idLocal = resolveIdLocal(req, "public");

    if (!idLocal) {
      throw createError("Debes enviar id_local");
    }

    const data = await getSriConfig(idLocal);

    res.json({
      ok: true,
      data
    });
  } catch (error) {
    console.error("❌ obtenerConfiguracionSriPruebas:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al obtener configuracion SRI de pruebas"
    });
  }
};

async function guardarConfigHandler(req, res, source = "auth") {
  let tempFilePath = null;

  try {
    const idLocal = resolveIdLocal(req, source);

    if (!idLocal) {
      throw createError("Debes indicar id_local");
    }

    const certPath = req.file?.path
      ? req.file.path
      : (req.body?.ruta_certificado || null);

    if (req.file?.path) {
      tempFilePath = req.file.path;
    }

    const data = await saveSriConfig({
      id_local: idLocal,
      ruc: req.body?.ruc,
      razon_social: req.body?.razon_social,
      nombre_comercial: req.body?.nombre_comercial,
      dir_matriz: req.body?.dir_matriz,
      dir_establecimiento: req.body?.dir_establecimiento,
      establecimiento: req.body?.establecimiento,
      punto_emision: req.body?.punto_emision,
      obligado_contabilidad: req.body?.obligado_contabilidad,
      contribuyente_especial: req.body?.contribuyente_especial,
      ambiente: req.body?.ambiente,
      telefono: req.body?.telefono,
      correo_notificacion: req.body?.correo_notificacion,
      certificado_path: certPath,
      clave_certificado: req.body?.clave_certificado
    });

    tempFilePath = null;

    res.json({
      ok: true,
      mensaje: "Configuracion SRI guardada correctamente",
      data
    });
  } catch (error) {
    console.error("❌ guardarConfiguracionSri:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al guardar configuracion SRI"
    });
  } finally {
    cleanupFile(tempFilePath);
  }
}

exports.guardarConfiguracionSri = async (req, res) => {
  return guardarConfigHandler(req, res, "auth");
};

exports.guardarConfiguracionSriPruebas = async (req, res) => {
  return guardarConfigHandler(req, res, "public");
};

async function generarXmlFacturaHandler(req, res, source = "auth") {
  try {
    const idVenta = Number(req.params?.id_venta || req.body?.id_venta || 0);

    if (!idVenta) {
      throw createError("Debes indicar un id_venta válido");
    }

    const data = await generarFacturaXmlDesdeVenta({
      id_venta: idVenta,
      user: source === "auth" ? req.user : null
    });

    res.json({
      ok: true,
      mensaje: "XML de factura generado correctamente",
      data
    });
  } catch (error) {
    console.error("❌ generarXmlFactura:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al generar el XML de la factura"
    });
  }
}

exports.generarXmlFactura = async (req, res) => {
  return generarXmlFacturaHandler(req, res, "auth");
};

exports.generarXmlFacturaPruebas = async (req, res) => {
  return generarXmlFacturaHandler(req, res, "public");
};

async function firmarXmlFacturaHandler(req, res, source = "auth") {
  try {
    const idVenta = Number(req.params?.id_venta || req.body?.id_venta || 0);

    if (!idVenta) {
      throw createError("Debes indicar un id_venta válido");
    }

    const data = await firmarFacturaXmlDesdeVenta({
      id_venta: idVenta,
      user: source === "auth" ? req.user : null
    });

    res.json({
      ok: true,
      mensaje: "XML firmado correctamente",
      data
    });
  } catch (error) {
    console.error("❌ firmarXmlFactura:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al firmar el XML de la factura"
    });
  }
}

exports.firmarXmlFactura = async (req, res) => {
  return firmarXmlFacturaHandler(req, res, "auth");
};

exports.firmarXmlFacturaPruebas = async (req, res) => {
  return firmarXmlFacturaHandler(req, res, "public");
};

async function enviarFacturaSriHandler(req, res, source = "auth") {
  try {
    const idVenta = Number(req.params?.id_venta || req.body?.id_venta || 0);

    if (!idVenta) {
      throw createError("Debes indicar un id_venta válido");
    }

    const data = await enviarFacturaFirmadaSriDesdeVenta({
      id_venta: idVenta,
      user: source === "auth" ? req.user : null
    });

    res.json({
      ok: true,
      mensaje: data.estado === "RECHAZADO"
        ? "El SRI devolvio el comprobante en recepción"
        : "Recepcion SRI procesada correctamente",
      data
    });
  } catch (error) {
    console.error("❌ enviarFacturaSri:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al enviar la factura al SRI"
    });
  }
}

exports.enviarFacturaSri = async (req, res) => {
  return enviarFacturaSriHandler(req, res, "auth");
};

exports.enviarFacturaSriPruebas = async (req, res) => {
  return enviarFacturaSriHandler(req, res, "public");
};

async function autorizarFacturaSriHandler(req, res, source = "auth") {
  try {
    const idVenta = Number(req.params?.id_venta || req.body?.id_venta || 0);

    if (!idVenta) {
      throw createError("Debes indicar un id_venta válido");
    }

    const data = await consultarAutorizacionFacturaSriDesdeVenta({
      id_venta: idVenta,
      user: source === "auth" ? req.user : null
    });

    res.json({
      ok: true,
      mensaje: data.estado === "AUTORIZADO"
        ? "Factura autorizada correctamente por el SRI"
        : (
          data.estado === "RECHAZADO"
            ? "El SRI respondio que el comprobante no esta autorizado"
            : "Consulta de autorización SRI procesada correctamente"
        ),
      data
    });
  } catch (error) {
    console.error("❌ autorizarFacturaSri:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al consultar la autorización en el SRI"
    });
  }
}

exports.autorizarFacturaSri = async (req, res) => {
  return autorizarFacturaSriHandler(req, res, "auth");
};

exports.autorizarFacturaSriPruebas = async (req, res) => {
  return autorizarFacturaSriHandler(req, res, "public");
};

async function generarRideFacturaSriHandler(req, res, source = "auth") {
  try {
    const idVenta = Number(req.params?.id_venta || req.body?.id_venta || 0);

    if (!idVenta) {
      throw createError("Debes indicar un id_venta válido");
    }

    const data = await generarRideFacturaSriDesdeVenta({
      id_venta: idVenta,
      user: source === "auth" ? req.user : null
    });

    res.json({
      ok: true,
      mensaje: "RIDE generado correctamente",
      data
    });
  } catch (error) {
    console.error("❌ generarRideFacturaSri:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al generar el RIDE"
    });
  }
}

exports.generarRideFacturaSri = async (req, res) => {
  return generarRideFacturaSriHandler(req, res, "auth");
};

exports.generarRideFacturaSriPruebas = async (req, res) => {
  return generarRideFacturaSriHandler(req, res, "public");
};

async function enviarFacturaSriCorreoHandler(req, res, source = "auth") {
  try {
    const idVenta = Number(req.params?.id_venta || req.body?.id_venta || 0);

    if (!idVenta) {
      throw createError("Debes indicar un id_venta válido");
    }

    const data = await enviarFacturaSriPorCorreoDesdeVenta({
      id_venta: idVenta,
      user: source === "auth" ? req.user : null,
      correo_destino: req.body?.correo_destino,
      asunto: req.body?.asunto,
      mensaje: req.body?.mensaje
    });

    res.json({
      ok: true,
      mensaje: "Factura electrónica enviada correctamente por correo",
      data
    });
  } catch (error) {
    console.error("❌ enviarFacturaSriCorreo:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al enviar la factura electrónica por correo"
    });
  }
}

exports.enviarFacturaSriCorreo = async (req, res) => {
  return enviarFacturaSriCorreoHandler(req, res, "auth");
};

exports.enviarFacturaSriCorreoPruebas = async (req, res) => {
  return enviarFacturaSriCorreoHandler(req, res, "public");
};
