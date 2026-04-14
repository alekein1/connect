const db = require("../db/db");

function fechaActualEcuador() {
  return new Intl.DateTimeFormat("en-CA", {
    timeZone: "America/Guayaquil",
    year: "numeric",
    month: "2-digit",
    day: "2-digit"
  }).format(new Date());
}

/* ============================================
   1️⃣ VERIFICAR CAJA ABIERTA
============================================ */
exports.verificarCaja = async (req, res) => {
  try {
    const id_usuario = req.user.id_usuario;
    const id_local   = req.user.id_local;

    const [rows] = await db.query(`
      SELECT *
      FROM caja_apertura
      WHERE id_usuario = ?
      AND id_local = ?
      ORDER BY id_caja DESC
      LIMIT 1
    `, [id_usuario, id_local]);

    if (!rows.length || rows[0].estado !== "ABIERTA") {
      return res.json({ ok: true, abierta: false });
    }

    const caja = rows[0];

    // 🔥 ventas SOLO de ese local
    const [ventas] = await db.query(`
      SELECT SUM(total) AS total_ventas
      FROM ventas
      WHERE id_caja = ?
      AND id_local = ?
      AND estado = 'PAGADA'
    `, [caja.id_caja, id_local]);

    res.json({
      ok: true,
      abierta: true,
      caja: {
        ...caja,
        total_ventas: ventas[0].total_ventas || 0
      }
    });

  } catch (error) {
    console.error("❌ verificarCaja:", error);
    res.status(500).json({ ok: false });
  }
};


/* ============================================
   2️⃣ ABRIR CAJA
============================================ */
exports.abrirCaja = async (req, res) => {
  const connection = await db.getConnection();

  try {
    const id_usuario = req.user.id_usuario;
    const id_local   = req.user.id_local;

    const monto_apertura = Number(req.body.monto_apertura);

    if (isNaN(monto_apertura) || monto_apertura < 0) {
      return res.status(400).json({
        ok: false,
        mensaje: "Monto inválido"
      });
    }

    // 🔒 verificar si YA tiene caja abierta en ese local
    const [existe] = await connection.query(`
      SELECT id_caja
      FROM caja_apertura
      WHERE id_usuario = ?
      AND id_local = ?
      AND estado = 'ABIERTA'
      LIMIT 1
    `, [id_usuario, id_local]);

    if (existe.length) {
      return res.status(400).json({
        ok: false,
        mensaje: "Ya tienes una caja abierta"
      });
    }

    const [result] = await connection.query(`
      INSERT INTO caja_apertura
      (id_usuario, id_local, monto_apertura, estado)
      VALUES (?, ?, ?, 'ABIERTA')
    `, [id_usuario, id_local, monto_apertura]);

    res.json({
      ok: true,
      mensaje: "Caja abierta correctamente",
      id_caja: result.insertId
    });

  } catch (error) {
    console.error("❌ abrirCaja:", error);
    res.status(500).json({ ok: false });

  } finally {
    connection.release();
  }
};


/* ============================================
   3️⃣ CERRAR CAJA
============================================ */
exports.cerrarCaja = async (req, res) => {
  const connection = await db.getConnection();

  try {
    const id_usuario = req.user.id_usuario;
    const id_local   = req.user.id_local;
    const monto_cierre = Number(req.body.monto_cierre);

    if (isNaN(monto_cierre) || monto_cierre < 0) {
      return res.status(400).json({
        ok: false,
        mensaje: "Monto de cierre inválido"
      });
    }

    // 🔥 buscar caja ABIERTA del usuario en ese local
    const [rows] = await connection.query(`
      SELECT *
      FROM caja_apertura
      WHERE id_usuario = ?
      AND id_local = ?
      AND estado = 'ABIERTA'
      LIMIT 1
    `, [id_usuario, id_local]);

    if (!rows.length) {
      return res.status(400).json({
        ok: false,
        mensaje: "No tienes caja abierta"
      });
    }

    const caja = rows[0];

    // 🔥 total ventas REAL
    const [ventas] = await connection.query(`
      SELECT SUM(total) as total_ventas
      FROM ventas
      WHERE id_caja = ?
      AND id_local = ?
      AND estado = 'PAGADA'
    `, [caja.id_caja, id_local]);

    const total_ventas = ventas[0].total_ventas || 0;

    // 🔥 cálculo final esperado
    const esperado = Number(caja.monto_apertura) + total_ventas;

    await connection.query(`
      UPDATE caja_apertura
      SET estado = 'CERRADA',
          fecha_cierre = NOW(),
          monto_cierre = ?
      WHERE id_caja = ?
    `, [monto_cierre, caja.id_caja]);

    res.json({
      ok: true,
      mensaje: "Caja cerrada correctamente",
      resumen: {
        apertura: caja.monto_apertura,
        ventas: total_ventas,
        esperado,
        cierre: monto_cierre,
        diferencia: monto_cierre - esperado
      }
    });

  } catch (error) {
    console.error("❌ cerrarCaja:", error);
    res.status(500).json({ ok: false });

  } finally {
    connection.release();
  }
};


/* ============================================
   4️⃣ LISTAR CAJAS (POR LOCAL)
============================================ */
exports.listarCajas = async (req, res) => {
  try {
    const id_local = req.user.id_local;

    const [rows] = await db.query(`
      SELECT c.*, u.usuario
      FROM caja_apertura c
      INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
      WHERE c.id_local = ?
      ORDER BY c.id_caja DESC
    `, [id_local]);

    res.json({
      ok: true,
      data: rows
    });

  } catch (error) {
    console.error("❌ listarCajas:", error);
    res.status(500).json({ ok: false });
  }
};


/* ============================================
   5️⃣ DETALLE CAJA (CON SEGURIDAD)
============================================ */
exports.detalleCaja = async (req, res) => {
  try {
    const id_local = req.user.id_local;
    const { id } = req.params;

    const [caja] = await db.query(`
      SELECT *
      FROM caja_apertura
      WHERE id_caja = ?
      AND id_local = ?
      LIMIT 1
    `, [id, id_local]);

    if (!caja.length) {
      return res.status(404).json({
        ok: false,
        mensaje: "Caja no encontrada"
      });
    }

    const [ventas] = await db.query(`
      SELECT *
      FROM ventas
      WHERE id_caja = ?
      AND id_local = ?
    `, [id, id_local]);

    res.json({
      ok: true,
      caja: caja[0],
      ventas
    });

  } catch (error) {
    console.error("❌ detalleCaja:", error);
    res.status(500).json({ ok: false });
  }
};

/* ============================================
   6️⃣ RESUMEN DE CAJA DEL LOCAL (ADMIN)
============================================ */
exports.resumenCajasLocal = async (req, res) => {
  try {
    const id_local = req.user.id_local;
    const fecha = String(req.query.fecha || fechaActualEcuador()).trim();

    if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
      return res.status(400).json({
        ok: false,
        mensaje: "La fecha debe tener formato YYYY-MM-DD"
      });
    }

    const [[ventasDia]] = await db.query(`
      SELECT
        COUNT(*) AS total_registros,
        COALESCE(SUM(total), 0) AS total_ventas
      FROM ventas
      WHERE id_local = ?
        AND estado = 'PAGADA'
        AND DATE(fecha_venta) = ?
    `, [id_local, fecha]);

    const [cajas] = await db.query(`
      SELECT
        c.id_caja,
        c.id_usuario,
        u.usuario,
        c.estado,
        c.monto_apertura,
        c.monto_cierre,
        c.fecha_apertura,
        c.fecha_cierre,
        COALESCE(v.total_ventas, 0) AS total_ventas,
        ROUND(c.monto_apertura + COALESCE(v.total_ventas, 0), 2) AS esperado,
        CASE
          WHEN c.monto_cierre IS NOT NULL
            THEN ROUND(c.monto_cierre - (c.monto_apertura + COALESCE(v.total_ventas, 0)), 2)
          ELSE NULL
        END AS diferencia
      FROM caja_apertura c
      INNER JOIN usuarios u
        ON u.id_usuario = c.id_usuario
      LEFT JOIN (
        SELECT
          id_caja,
          SUM(total) AS total_ventas
        FROM ventas
        WHERE id_local = ?
          AND estado = 'PAGADA'
          AND DATE(fecha_venta) = ?
        GROUP BY id_caja
      ) v ON v.id_caja = c.id_caja
      WHERE c.id_local = ?
        AND DATE(c.fecha_apertura) = ?
      ORDER BY c.id_caja DESC
    `, [id_local, fecha, id_local, fecha]);

    const resumen = cajas.reduce((acc, caja) => {
      acc.total_apertura += Number(caja.monto_apertura || 0);
      acc.total_cierre += Number(caja.monto_cierre || 0);
      acc.cajas_abiertas += caja.estado === "ABIERTA" ? 1 : 0;
      acc.cajas_cerradas += caja.estado === "CERRADA" ? 1 : 0;
      return acc;
    }, {
      total_apertura: 0,
      total_cierre: 0,
      cajas_abiertas: 0,
      cajas_cerradas: 0
    });

    res.json({
      ok: true,
      fecha,
      local: {
        id_local
      },
      resumen: {
        ventas_del_dia: Number(ventasDia.total_ventas || 0),
        total_registros_ventas: Number(ventasDia.total_registros || 0),
        total_cajas: cajas.length,
        cajas_abiertas: resumen.cajas_abiertas,
        cajas_cerradas: resumen.cajas_cerradas,
        total_apertura: Number(resumen.total_apertura.toFixed(2)),
        total_cierre: Number(resumen.total_cierre.toFixed(2))
      },
      cajas
    });

  } catch (error) {
    console.error("❌ resumenCajasLocal:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al obtener el resumen de caja"
    });
  }
};
