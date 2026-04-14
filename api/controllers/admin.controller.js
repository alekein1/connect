const bcrypt = require("bcryptjs");
const db = require("../db/db");

function fechaActualEcuador() {
  return new Intl.DateTimeFormat("en-CA", {
    timeZone: "America/Guayaquil",
    year: "numeric",
    month: "2-digit",
    day: "2-digit"
  }).format(new Date());
}

/* =====================================================
   📊 DASHBOARD ADMIN (POR LOCAL)
===================================================== */
exports.dashboardAdmin = async (req, res) => {
  try {
    const id_local = Number(req.user?.id_local || 0);

    if (!id_local) {
      return res.status(400).json({
        ok: false,
        mensaje: "No se pudo resolver el local del administrador"
      });
    }

    const fecha = fechaActualEcuador();

    const [
      [[ventasHoy]],
      [[cajaActual]],
      [[productos]],
      [[stockBajo]]
    ] = await Promise.all([
      db.query(`
        SELECT
          COUNT(*) AS total_registros,
          COALESCE(SUM(total), 0) AS total_ventas
        FROM ventas
        WHERE id_local = ?
          AND estado = 'PAGADA'
          AND DATE(fecha_venta) = ?
      `, [id_local, fecha]),
      db.query(`
        SELECT
          c.id_caja,
          c.estado,
          c.monto_apertura,
          c.fecha_apertura,
          COALESCE(SUM(v.total), 0) AS total_ventas
        FROM caja_apertura c
        LEFT JOIN ventas v
          ON v.id_caja = c.id_caja
         AND v.id_local = c.id_local
         AND v.estado = 'PAGADA'
        WHERE c.id_local = ?
          AND c.estado = 'ABIERTA'
        GROUP BY c.id_caja, c.estado, c.monto_apertura, c.fecha_apertura
        ORDER BY c.id_caja DESC
        LIMIT 1
      `, [id_local]),
      db.query(`
        SELECT
          COUNT(*) AS total_productos,
          SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS productos_activos,
          SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) AS productos_inactivos
        FROM productos
        WHERE id_local = ?
      `, [id_local]),
      db.query(`
        SELECT COUNT(*) AS total_stock_bajo
        FROM productos p
        LEFT JOIN inventario_stock i
          ON i.id_producto = p.id_producto
         AND i.id_local = p.id_local
        WHERE p.id_local = ?
          AND p.activo = 1
          AND COALESCE(i.stock_actual, 0) <= COALESCE(p.stock_minimo, 0)
      `, [id_local])
    ]);

    const cajaAbierta = Boolean(cajaActual?.id_caja);
    const totalCajaActual = cajaAbierta
      ? Number(Number(cajaActual.monto_apertura || 0) + Number(cajaActual.total_ventas || 0))
      : 0;

    res.json({
      ok: true,
      data: {
        fecha,
        id_local,
        ventas_hoy: Number(ventasHoy?.total_ventas || 0),
        ventas_hoy_registros: Number(ventasHoy?.total_registros || 0),
        caja_actual: Number(totalCajaActual.toFixed(2)),
        caja_abierta: cajaAbierta,
        productos: Number(productos?.total_productos || 0),
        productos_activos: Number(productos?.productos_activos || 0),
        productos_inactivos: Number(productos?.productos_inactivos || 0),
        stock_bajo: Number(stockBajo?.total_stock_bajo || 0)
      }
    });

  } catch (error) {
    console.error("❌ dashboardAdmin:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al obtener el dashboard del administrador"
    });
  }
};

/* =====================================================
   📋 LISTAR ADMINS
===================================================== */
exports.listarAdmins = async (req, res) => {
  try {

    const [rows] = await db.query(`
      SELECT 
        u.id_usuario,
        u.usuario,
        u.correo,
        u.id_local,
        l.nombre_local,
        u.activo,
        u.fecha_creacion
      FROM usuarios u
      LEFT JOIN locales l ON l.id_local = u.id_local
      WHERE u.rol = 'ADMIN'
      ORDER BY u.id_usuario DESC
    `);

    res.json({
      ok: true,
      data: rows
    });

  } catch (error) {
    console.error("❌ listarAdmins:", error);
    res.status(500).json({ ok: false });
  }
};

/* =====================================================
   ➕ CREAR ADMIN
===================================================== */
exports.crearAdmin = async (req, res) => {
  try {

    const { usuario, correo, password, id_local } = req.body;

    if (!usuario || !password || !id_local) {
      return res.status(400).json({
        ok: false,
        mensaje: "usuario, password e id_local son obligatorios"
      });
    }

    // verificar duplicado
    const [existe] = await db.query(
      "SELECT id_usuario FROM usuarios WHERE usuario = ? LIMIT 1",
      [usuario]
    );

    if (existe.length > 0) {
      return res.status(409).json({
        ok: false,
        mensaje: "El usuario ya existe"
      });
    }

    // hash
    const hash = await bcrypt.hash(password, 10);

    const [result] = await db.query(`
      INSERT INTO usuarios (
        id_local,
        usuario,
        correo,
        password,
        rol,
        activo
      ) VALUES (?, ?, ?, ?, 'ADMIN', 1)
    `, [id_local, usuario, correo || null, hash]);

    res.status(201).json({
      ok: true,
      mensaje: "Admin creado correctamente",
      id_usuario: result.insertId
    });

  } catch (error) {
    console.error("❌ crearAdmin:", error);
    res.status(500).json({ ok: false });
  }
};

/* =====================================================
   ✏️ ACTUALIZAR ADMIN
===================================================== */
exports.actualizarAdmin = async (req, res) => {
  try {

    const { id } = req.params;
    const { usuario, correo, id_local, activo } = req.body;

    await db.query(`
      UPDATE usuarios
      SET usuario = ?, correo = ?, id_local = ?, activo = ?
      WHERE id_usuario = ? AND rol = 'ADMIN'
    `, [
      usuario,
      correo || null,
      id_local,
      activo ?? 1,
      id
    ]);

    res.json({
      ok: true,
      mensaje: "Admin actualizado"
    });

  } catch (error) {
    console.error("❌ actualizarAdmin:", error);
    res.status(500).json({ ok: false });
  }
};

/* =====================================================
   🔐 CAMBIAR PASSWORD ADMIN
===================================================== */
exports.cambiarPassword = async (req, res) => {
  try {

    const { id } = req.params;
    const { password } = req.body;

    if (!password) {
      return res.status(400).json({
        ok: false,
        mensaje: "Password requerido"
      });
    }

    const hash = await bcrypt.hash(password, 10);

    await db.query(`
      UPDATE usuarios
      SET password = ?
      WHERE id_usuario = ? AND rol = 'ADMIN'
    `, [hash, id]);

    res.json({
      ok: true,
      mensaje: "Password actualizado"
    });

  } catch (error) {
    console.error("❌ cambiarPassword:", error);
    res.status(500).json({ ok: false });
  }
};

/* =====================================================
   🗑️ DESACTIVAR ADMIN
===================================================== */
exports.eliminarAdmin = async (req, res) => {
  try {

    const { id } = req.params;

    await db.query(`
      UPDATE usuarios
      SET activo = 0
      WHERE id_usuario = ? AND rol = 'ADMIN'
    `, [id]);

    res.json({
      ok: true,
      mensaje: "Admin desactivado"
    });

  } catch (error) {
    console.error("❌ eliminarAdmin:", error);
    res.status(500).json({ ok: false });
  }
};
