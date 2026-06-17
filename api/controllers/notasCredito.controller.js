const {
  listarFacturasParaNotasCredito,
  emitirNotaCreditoDesdeVenta,
  consultarAutorizacionNotaCredito
} = require("../services/sri-nota-credito.service");

function createHttpError(message, status = 400) {
  const error = new Error(message);
  error.status = status;
  return error;
}

function isValidDate(value) {
  return /^\d{4}-\d{2}-\d{2}$/.test(String(value || "").trim());
}

function parsePositiveId(value, label) {
  const parsed = Number(value || 0);

  if (!Number.isInteger(parsed) || parsed <= 0) {
    throw createHttpError(`Debes indicar un ${label} válido`);
  }

  return parsed;
}

function normalizeEstadoNotaCredito(value = "") {
  const normalized = String(value || "").trim().toUpperCase();

  if (!normalized) {
    return "";
  }

  const allowed = new Set([
    "SIN_NC",
    "PENDIENTE",
    "BORRADOR",
    "XML_GENERADO",
    "FIRMADO",
    "ENVIADO",
    "RECIBIDO",
    "AUTORIZADO",
    "RECHAZADO",
    "ERROR"
  ]);

  if (!allowed.has(normalized)) {
    throw createHttpError("El estado de la nota de crédito no es válido");
  }

  return normalized;
}

exports.listarFacturasSuperadmin = async (req, res) => {
  try {
    const id_local = Number(req.query.id_local || 0);
    const buscar = String(req.query.buscar || "").trim();
    const estado_nota_credito = normalizeEstadoNotaCredito(req.query.estado_nota_credito);
    const fecha_desde = isValidDate(req.query.fecha_desde) ? req.query.fecha_desde : "";
    const fecha_hasta = isValidDate(req.query.fecha_hasta) ? req.query.fecha_hasta : "";

    if (req.query.fecha_desde && !fecha_desde) {
      throw createHttpError("La fecha desde debe tener formato YYYY-MM-DD");
    }

    if (req.query.fecha_hasta && !fecha_hasta) {
      throw createHttpError("La fecha hasta debe tener formato YYYY-MM-DD");
    }

    if (fecha_desde && fecha_hasta && fecha_desde > fecha_hasta) {
      throw createHttpError("La fecha desde no puede ser mayor que la fecha hasta");
    }

    const data = await listarFacturasParaNotasCredito({
      buscar,
      id_local,
      estado_nota_credito,
      fecha_desde,
      fecha_hasta
    });

    res.json({
      ok: true,
      data: {
        filtros: {
          buscar: buscar || null,
          id_local: id_local || null,
          estado_nota_credito: estado_nota_credito || null,
          fecha_desde: fecha_desde || null,
          fecha_hasta: fecha_hasta || null
        },
        ...data
      }
    });
  } catch (error) {
    console.error("❌ listarFacturasSuperadmin:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al listar facturas para notas de crédito"
    });
  }
};

exports.emitirNotaCreditoSuperadmin = async (req, res) => {
  try {
    const id_venta = parsePositiveId(req.params.id_venta, "id_venta");
    const motivo = String(req.body?.motivo || "").trim();

    if (!motivo) {
      throw createHttpError("Debes indicar el motivo de la nota de crédito");
    }

    const data = await emitirNotaCreditoDesdeVenta({
      id_venta,
      motivo,
      user: req.user
    });

    res.json({
      ok: true,
      mensaje: "Nota de crédito procesada correctamente",
      data
    });
  } catch (error) {
    console.error("❌ emitirNotaCreditoSuperadmin:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al emitir la nota de crédito"
    });
  }
};

exports.consultarNotaCreditoSuperadmin = async (req, res) => {
  try {
    const id_nota_credito = parsePositiveId(req.params.id_nota_credito, "id_nota_credito");
    const aplicarRaw = String(req.query.aplicar ?? "1").trim().toLowerCase();
    const intentarAplicar = !["0", "false", "no"].includes(aplicarRaw);
    const data = await consultarAutorizacionNotaCredito(id_nota_credito, {
      intentarAplicar
    });

    res.json({
      ok: true,
      mensaje: "Consulta de nota de crédito completada",
      data
    });
  } catch (error) {
    console.error("❌ consultarNotaCreditoSuperadmin:", error);
    res.status(error.status || 500).json({
      ok: false,
      mensaje: error.message || "Error al consultar la nota de crédito"
    });
  }
};
