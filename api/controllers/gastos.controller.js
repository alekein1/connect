const db = require("../db/db");

const METODOS_PAGO_VALIDOS = ["EFECTIVO", "TRANSFERENCIA", "TARJETA", "OTRO"];

function round2(value) {
  return Math.round((Number(value || 0) + Number.EPSILON) * 100) / 100;
}

function fechaActualEcuador() {
  const parts = new Intl.DateTimeFormat("en", {
    timeZone: "America/Guayaquil",
    year: "numeric",
    month: "2-digit",
    day: "2-digit"
  }).formatToParts(new Date());

  const year = parts.find((part) => part.type === "year")?.value;
  const month = parts.find((part) => part.type === "month")?.value;
  const day = parts.find((part) => part.type === "day")?.value;

  return `${year}-${month}-${day}`;
}

function validarFechaIso(value) {
  return /^\d{4}-\d{2}-\d{2}$/.test(String(value || "").trim());
}

function normalizarTexto(value, maxLength, requerido = false, nombreCampo = "Campo", mensajeRequerido = null) {
  const text = value === null || value === undefined ? "" : String(value).trim();

  if (!text) {
    if (requerido) {
      const error = new Error(mensajeRequerido || `${nombreCampo} es obligatorio`);
      error.statusCode = 400;
      throw error;
    }
    return null;
  }

  return text.slice(0, maxLength);
}

function normalizarMetodoPago(value) {
  const metodo = String(value || "EFECTIVO").trim().toUpperCase();

  if (!METODOS_PAGO_VALIDOS.includes(metodo)) {
    const error = new Error("Método de pago inválido");
    error.statusCode = 400;
    throw error;
  }

  return metodo;
}

function normalizarMonto(value) {
  const monto = Number(value);

  if (!Number.isFinite(monto) || monto <= 0) {
    const error = new Error("El monto debe ser mayor a 0");
    error.statusCode = 400;
    throw error;
  }

  return round2(monto);
}

function normalizarIdEntero(value, nombre = "ID") {
  const id = Number(value);

  if (!Number.isInteger(id) || id <= 0) {
    const error = new Error(`${nombre} inválido`);
    error.statusCode = 400;
    throw error;
  }

  return id;
}

function normalizarFechaGasto(value) {
  if (value === undefined || value === null || String(value).trim() === "") {
    return null;
  }

  const fecha = String(value).trim();
  const valida = /^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/.test(fecha);

  if (!valida) {
    const error = new Error("La fecha del gasto debe tener formato YYYY-MM-DD o YYYY-MM-DD HH:mm:ss");
    error.statusCode = 400;
    throw error;
  }

  return fecha.length === 10 ? `${fecha} 00:00:00` : fecha;
}

function construirFiltrosGastos(idLocal, query, alias = "g") {
  const where = [`${alias}.id_local = ?`];
  const params = [idLocal];

  const desde = query.desde ? String(query.desde).trim() : null;
  const hasta = query.hasta ? String(query.hasta).trim() : null;
  const categoria = query.categoria ? String(query.categoria).trim() : null;
  const metodoPago = query.metodo_pago ? String(query.metodo_pago).trim().toUpperCase() : null;
  const idCajaRaw = query.id_caja;
  const idCaja = idCajaRaw ? Number(idCajaRaw) : null;

  if (desde) {
    if (!validarFechaIso(desde)) {
      const error = new Error("La fecha 'desde' debe tener formato YYYY-MM-DD");
      error.statusCode = 400;
      throw error;
    }

    where.push(`DATE(${alias}.fecha_gasto) >= ?`);
    params.push(desde);
  }

  if (hasta) {
    if (!validarFechaIso(hasta)) {
      const error = new Error("La fecha 'hasta' debe tener formato YYYY-MM-DD");
      error.statusCode = 400;
      throw error;
    }

    where.push(`DATE(${alias}.fecha_gasto) <= ?`);
    params.push(hasta);
  }

  if (desde && hasta && desde > hasta) {
    const error = new Error("La fecha 'desde' no puede ser mayor que 'hasta'");
    error.statusCode = 400;
    throw error;
  }

  if (categoria) {
    where.push(`${alias}.categoria = ?`);
    params.push(categoria);
  }

  if (metodoPago) {
    if (!METODOS_PAGO_VALIDOS.includes(metodoPago)) {
      const error = new Error("Filtro 'metodo_pago' inválido");
      error.statusCode = 400;
      throw error;
    }

    where.push(`${alias}.metodo_pago = ?`);
    params.push(metodoPago);
  }

  if (idCajaRaw !== undefined && idCajaRaw !== null && String(idCajaRaw).trim() !== "") {
    if (!idCaja) {
      const error = new Error("Filtro 'id_caja' inválido");
      error.statusCode = 400;
      throw error;
    }

    where.push(`${alias}.id_caja = ?`);
    params.push(idCaja);
  }

  return {
    sql: where.join(" AND "),
    params,
    filtros: {
      desde,
      hasta,
      categoria,
      metodo_pago: metodoPago,
      id_caja: idCaja || null
    }
  };
}

async function resolverCajaLocal({
  connection,
  idLocal,
  idUsuario,
  idCajaBody,
  usarCajaAbiertaPorDefecto = false
}) {
  if (idCajaBody !== undefined && idCajaBody !== null && String(idCajaBody).trim() !== "") {
    const idCaja = Number(idCajaBody);

    if (!idCaja) {
      const error = new Error("Caja inválida");
      error.statusCode = 400;
      throw error;
    }

    const [[caja]] = await connection.query(`
      SELECT id_caja
      FROM caja_apertura
      WHERE id_caja = ?
        AND id_local = ?
      LIMIT 1
    `, [idCaja, idLocal]);

    if (!caja) {
      const error = new Error("La caja no pertenece a este local");
      error.statusCode = 404;
      throw error;
    }

    return idCaja;
  }

  if (!usarCajaAbiertaPorDefecto) {
    return null;
  }

  const [[cajaAbierta]] = await connection.query(`
    SELECT id_caja
    FROM caja_apertura
    WHERE id_usuario = ?
      AND id_local = ?
      AND estado = 'ABIERTA'
    ORDER BY id_caja DESC
    LIMIT 1
  `, [idUsuario, idLocal]);

  return cajaAbierta ? cajaAbierta.id_caja : null;
}

/* =====================================================
   ➕ CREAR GASTO
   Siempre se registra en el local del token
===================================================== */
exports.crearGasto = async (req, res) => {
  const connection = await db.getConnection();

  try {
    const idLocal = req.user.id_local;
    const idUsuario = req.user.id_usuario;

    const categoria = normalizarTexto(req.body.categoria, 50, true, "La categoría", "La categoría es obligatoria");
    const descripcion = normalizarTexto(req.body.descripcion, 255, false);
    const monto = normalizarMonto(req.body.monto);
    const metodoPago = normalizarMetodoPago(req.body.metodo_pago);
    const referencia = normalizarTexto(req.body.referencia, 100, false);
    const observacion = normalizarTexto(req.body.observacion, 255, false);
    const fechaGasto = normalizarFechaGasto(req.body.fecha_gasto);
    const idCaja = await resolverCajaLocal({
      connection,
      idLocal,
      idUsuario,
      idCajaBody: req.body.id_caja,
      usarCajaAbiertaPorDefecto: true
    });

    const [result] = await connection.query(`
      INSERT INTO gastos (
        id_local,
        id_usuario,
        fecha_gasto,
        categoria,
        descripcion,
        monto,
        metodo_pago,
        referencia,
        observacion,
        id_caja
      ) VALUES (?, ?, ${fechaGasto ? "?" : "NOW()"}, ?, ?, ?, ?, ?, ?, ?)
    `, fechaGasto
      ? [
          idLocal,
          idUsuario,
          fechaGasto,
          categoria,
          descripcion,
          monto,
          metodoPago,
          referencia,
          observacion,
          idCaja
        ]
      : [
          idLocal,
          idUsuario,
          categoria,
          descripcion,
          monto,
          metodoPago,
          referencia,
          observacion,
          idCaja
        ]);

    return res.status(201).json({
      ok: true,
      mensaje: "Gasto registrado correctamente",
      data: {
        id_gasto: result.insertId,
        id_local: idLocal,
        id_usuario: idUsuario,
        id_caja: idCaja,
        categoria,
        descripcion,
        monto,
        metodo_pago: metodoPago,
        referencia,
        observacion,
        fecha_gasto: fechaGasto || null
      }
    });

  } catch (error) {
    console.error("❌ crearGasto:", error);
    return res.status(error.statusCode || 500).json({
      ok: false,
      mensaje: error.message || "Error al registrar gasto"
    });
  } finally {
    connection.release();
  }
};

/* =====================================================
   📋 LISTAR GASTOS DEL LOCAL
===================================================== */
exports.listarGastos = async (req, res) => {
  try {
    const idLocal = req.user.id_local;
    const filtros = construirFiltrosGastos(idLocal, req.query, "g");

    const [rows] = await db.query(`
      SELECT
        g.id_gasto,
        g.id_local,
        g.id_usuario,
        g.id_caja,
        g.fecha_gasto,
        g.categoria,
        g.descripcion,
        g.monto,
        g.metodo_pago,
        g.referencia,
        g.observacion,
        g.creado_en,
        u.usuario,
        c.estado AS estado_caja
      FROM gastos g
      INNER JOIN usuarios u
        ON u.id_usuario = g.id_usuario
      LEFT JOIN caja_apertura c
        ON c.id_caja = g.id_caja
       AND c.id_local = g.id_local
      WHERE ${filtros.sql}
      ORDER BY g.fecha_gasto DESC, g.id_gasto DESC
    `, filtros.params);

    return res.json({
      ok: true,
      filtros: filtros.filtros,
      data: rows
    });

  } catch (error) {
    console.error("❌ listarGastos:", error);
    return res.status(error.statusCode || 500).json({
      ok: false,
      mensaje: error.message || "Error al listar gastos"
    });
  }
};

/* =====================================================
   🔍 OBTENER GASTO POR ID
   Solo del local del token
===================================================== */
exports.obtenerGasto = async (req, res) => {
  try {
    const idLocal = req.user.id_local;
    const idGasto = normalizarIdEntero(req.params.id, "ID de gasto");

    const [[gasto]] = await db.query(`
      SELECT
        g.*,
        u.usuario,
        c.estado AS estado_caja
      FROM gastos g
      INNER JOIN usuarios u
        ON u.id_usuario = g.id_usuario
      LEFT JOIN caja_apertura c
        ON c.id_caja = g.id_caja
       AND c.id_local = g.id_local
      WHERE g.id_gasto = ?
        AND g.id_local = ?
      LIMIT 1
    `, [idGasto, idLocal]);

    if (!gasto) {
      return res.status(404).json({
        ok: false,
        mensaje: "Gasto no encontrado"
      });
    }

    return res.json({
      ok: true,
      data: gasto
    });

  } catch (error) {
    console.error("❌ obtenerGasto:", error);
    return res.status(500).json({
      ok: false,
      mensaje: "Error al obtener gasto"
    });
  }
};

/* =====================================================
   ✏️ ACTUALIZAR GASTO
   Solo si pertenece al local del token
===================================================== */
exports.actualizarGasto = async (req, res) => {
  const connection = await db.getConnection();

  try {
    const idLocal = req.user.id_local;
    const idUsuario = req.user.id_usuario;
    const idGasto = normalizarIdEntero(req.params.id, "ID de gasto");

    const [[actual]] = await connection.query(`
      SELECT *
      FROM gastos
      WHERE id_gasto = ?
        AND id_local = ?
      LIMIT 1
    `, [idGasto, idLocal]);

    if (!actual) {
      return res.status(404).json({
        ok: false,
        mensaje: "Gasto no encontrado"
      });
    }

    const categoria = req.body.categoria !== undefined
      ? normalizarTexto(req.body.categoria, 50, true, "La categoría", "La categoría es obligatoria")
      : actual.categoria;
    const descripcion = req.body.descripcion !== undefined
      ? normalizarTexto(req.body.descripcion, 255, false)
      : actual.descripcion;
    const monto = req.body.monto !== undefined
      ? normalizarMonto(req.body.monto)
      : round2(actual.monto);
    const metodoPago = req.body.metodo_pago !== undefined
      ? normalizarMetodoPago(req.body.metodo_pago)
      : actual.metodo_pago;
    const referencia = req.body.referencia !== undefined
      ? normalizarTexto(req.body.referencia, 100, false)
      : actual.referencia;
    const observacion = req.body.observacion !== undefined
      ? normalizarTexto(req.body.observacion, 255, false)
      : actual.observacion;
    const fechaGasto = req.body.fecha_gasto !== undefined
      ? (
          req.body.fecha_gasto === null || String(req.body.fecha_gasto).trim() === ""
            ? actual.fecha_gasto
            : normalizarFechaGasto(req.body.fecha_gasto)
        )
      : actual.fecha_gasto;
    const idCaja = req.body.id_caja !== undefined
      ? await resolverCajaLocal({
          connection,
          idLocal,
          idUsuario,
          idCajaBody: req.body.id_caja
        })
      : actual.id_caja;

    await connection.query(`
      UPDATE gastos SET
        fecha_gasto = ?,
        categoria = ?,
        descripcion = ?,
        monto = ?,
        metodo_pago = ?,
        referencia = ?,
        observacion = ?,
        id_caja = ?
      WHERE id_gasto = ?
        AND id_local = ?
    `, [
      fechaGasto,
      categoria,
      descripcion,
      monto,
      metodoPago,
      referencia,
      observacion,
      idCaja,
      idGasto,
      idLocal
    ]);

    return res.json({
      ok: true,
      mensaje: "Gasto actualizado correctamente"
    });

  } catch (error) {
    console.error("❌ actualizarGasto:", error);
    return res.status(error.statusCode || 500).json({
      ok: false,
      mensaje: error.message || "Error al actualizar gasto"
    });
  } finally {
    connection.release();
  }
};

/* =====================================================
   ❌ ELIMINAR GASTO
   Hard delete controlado por local
===================================================== */
exports.eliminarGasto = async (req, res) => {
  try {
    const idLocal = req.user.id_local;
    const idGasto = normalizarIdEntero(req.params.id, "ID de gasto");

    const [[gasto]] = await db.query(`
      SELECT
        id_gasto,
        categoria,
        descripcion,
        monto,
        fecha_gasto,
        id_caja
      FROM gastos
      WHERE id_gasto = ?
        AND id_local = ?
      LIMIT 1
    `, [idGasto, idLocal]);

    if (!gasto) {
      return res.status(404).json({
        ok: false,
        mensaje: "Gasto no encontrado"
      });
    }

    await db.query(`
      DELETE FROM gastos
      WHERE id_gasto = ?
        AND id_local = ?
      LIMIT 1
    `, [idGasto, idLocal]);

    return res.json({
      ok: true,
      mensaje: "Gasto eliminado correctamente",
      data: {
        id_gasto: gasto.id_gasto,
        categoria: gasto.categoria,
        descripcion: gasto.descripcion,
        monto: round2(gasto.monto),
        fecha_gasto: gasto.fecha_gasto,
        id_caja: gasto.id_caja
      }
    });

  } catch (error) {
    console.error("❌ eliminarGasto:", error);
    return res.status(error.statusCode || 500).json({
      ok: false,
      mensaje: error.message || "Error al eliminar gasto"
    });
  }
};

/* =====================================================
   📊 RESUMEN DE GASTOS
   Siempre solo del local autenticado
===================================================== */
exports.resumenGastos = async (req, res) => {
  try {
    const idLocal = req.user.id_local;
    const filtros = construirFiltrosGastos(idLocal, req.query, "g");

    const [[resumen]] = await db.query(`
      SELECT
        COUNT(*) AS total_registros,
        COALESCE(SUM(g.monto), 0) AS total_gastos,
        COALESCE(SUM(CASE WHEN g.metodo_pago = 'EFECTIVO' THEN g.monto ELSE 0 END), 0) AS total_efectivo,
        COALESCE(SUM(CASE WHEN g.metodo_pago = 'TRANSFERENCIA' THEN g.monto ELSE 0 END), 0) AS total_transferencia,
        COALESCE(SUM(CASE WHEN g.metodo_pago = 'TARJETA' THEN g.monto ELSE 0 END), 0) AS total_tarjeta,
        COALESCE(SUM(CASE WHEN g.metodo_pago = 'OTRO' THEN g.monto ELSE 0 END), 0) AS total_otro
      FROM gastos g
      WHERE ${filtros.sql}
    `, filtros.params);

    const [porCategoria] = await db.query(`
      SELECT
        g.categoria,
        COUNT(*) AS total_registros,
        COALESCE(SUM(g.monto), 0) AS total_monto
      FROM gastos g
      WHERE ${filtros.sql}
      GROUP BY g.categoria
      ORDER BY total_monto DESC, total_registros DESC
    `, filtros.params);

    const [porMetodo] = await db.query(`
      SELECT
        g.metodo_pago,
        COUNT(*) AS total_registros,
        COALESCE(SUM(g.monto), 0) AS total_monto
      FROM gastos g
      WHERE ${filtros.sql}
      GROUP BY g.metodo_pago
      ORDER BY total_monto DESC, total_registros DESC
    `, filtros.params);

    const [porDia] = await db.query(`
      SELECT
        DATE(g.fecha_gasto) AS fecha,
        COUNT(*) AS total_registros,
        COALESCE(SUM(g.monto), 0) AS total_monto
      FROM gastos g
      WHERE ${filtros.sql}
      GROUP BY DATE(g.fecha_gasto)
      ORDER BY fecha ASC
    `, filtros.params);

    return res.json({
      ok: true,
      filtros: filtros.filtros,
      data: {
        fecha_consulta: fechaActualEcuador(),
        total_registros: Number(resumen.total_registros || 0),
        total_gastos: round2(resumen.total_gastos),
        total_efectivo: round2(resumen.total_efectivo),
        total_transferencia: round2(resumen.total_transferencia),
        total_tarjeta: round2(resumen.total_tarjeta),
        total_otro: round2(resumen.total_otro)
      },
      por_categoria: porCategoria.map((row) => ({
        categoria: row.categoria,
        total_registros: Number(row.total_registros || 0),
        total_monto: round2(row.total_monto)
      })),
      por_metodo_pago: porMetodo.map((row) => ({
        metodo_pago: row.metodo_pago,
        total_registros: Number(row.total_registros || 0),
        total_monto: round2(row.total_monto)
      })),
      por_dia: porDia.map((row) => ({
        fecha: row.fecha,
        total_registros: Number(row.total_registros || 0),
        total_monto: round2(row.total_monto)
      }))
    });

  } catch (error) {
    console.error("❌ resumenGastos:", error);
    return res.status(error.statusCode || 500).json({
      ok: false,
      mensaje: error.message || "Error al obtener resumen de gastos"
    });
  }
};
