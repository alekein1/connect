const bcrypt = require("bcryptjs");
const db = require("../db/db");

/* =====================================================
   📋 LISTAR USUARIOS CAJA (SOLO SU LOCAL)
===================================================== */
exports.listarUsuariosCaja = async (req, res) => {
  try {

    const id_local = req.user.id_local;

    const [rows] = await db.query(`
      SELECT 
        id_usuario,
        usuario,
        correo,
        activo,
        fecha_creacion
      FROM usuarios
      WHERE rol = 'CAJA'
      AND id_local = ?
      ORDER BY id_usuario DESC
    `, [id_local]);

    res.json({ ok: true, data: rows });

  } catch (error) {
    console.error("❌ listarUsuariosCaja:", error);
    res.status(500).json({ ok: false });
  }
};

/* =====================================================
   ➕ CREAR USUARIO CAJA
===================================================== */
exports.crearUsuarioCaja = async (req, res) => {
  try {

    const id_local = req.user.id_local; // 🔐 DEL TOKEN
    const { usuario, correo, password } = req.body;

    if (!usuario || !password) {
      return res.status(400).json({
        ok: false,
        mensaje: "Usuario y password requeridos"
      });
    }

    // verificar duplicado
    const [existe] = await db.query(
  `SELECT id_usuario FROM usuarios 
   WHERE (usuario = ? OR correo = ?) AND id_local = ?
   LIMIT 1`,
  [usuario, correo, id_local]
);

    if (existe.length > 0) {
      return res.status(409).json({
        ok: false,
        mensaje: "El usuario ya existe"
      });
    }

    const hash = await bcrypt.hash(password, 10);

    const [result] = await db.query(`
      INSERT INTO usuarios (
        id_local,
        usuario,
        correo,
        password,
        rol,
        activo
      ) VALUES (?, ?, ?, ?, 'CAJA', 1)
    `, [id_local, usuario, correo || null, hash]);

    res.status(201).json({
      ok: true,
      mensaje: "Usuario caja creado",
      id_usuario: result.insertId
    });

  } catch (error) {
    console.error("❌ crearUsuarioCaja:", error);
    res.status(500).json({ ok: false });
  }
};

/* =====================================================
   ✏️ ACTUALIZAR USUARIO CAJA
===================================================== */
exports.actualizarUsuarioCaja = async (req, res) => {
  try {

    const { id } = req.params;
    const id_local = req.user.id_local;

    const { usuario, correo, activo } = req.body;

    // 🔐 validar que pertenece al local
    const [[user]] = await db.query(`
      SELECT id_usuario FROM usuarios
      WHERE id_usuario = ? AND id_local = ? AND rol = 'CAJA'
    `, [id, id_local]);

    if (!user) {
      return res.status(403).json({
        ok: false,
        mensaje: "No autorizado"
      });
    }

    await db.query(`
      UPDATE usuarios
      SET usuario = ?, correo = ?, activo = ?
      WHERE id_usuario = ?
    `, [
      usuario,
      correo || null,
      activo ?? 1,
      id
    ]);

    res.json({
      ok: true,
      mensaje: "Usuario actualizado"
    });

  } catch (error) {
    console.error("❌ actualizarUsuarioCaja:", error);
    res.status(500).json({ ok: false });
  }
};

/* =====================================================
   🔐 CAMBIAR PASSWORD
===================================================== */
exports.cambiarPasswordCaja = async (req, res) => {
  try {

    const { id } = req.params;
    const id_local = req.user.id_local;
    const { password } = req.body;

    if (!password || password.length < 4) {
  return res.status(400).json({
    ok: false,
    mensaje: "Password inválido"
  });
}

    const [[user]] = await db.query(`
      SELECT id_usuario FROM usuarios
      WHERE id_usuario = ? AND id_local = ? AND rol = 'CAJA'
    `, [id, id_local]);

    if (!user) {
      return res.status(403).json({ ok: false });
    }

    const hash = await bcrypt.hash(password, 10);

    await db.query(`
      UPDATE usuarios SET password = ?
      WHERE id_usuario = ?
    `, [hash, id]);

    res.json({
      ok: true,
      mensaje: "Password actualizado"
    });

  } catch (error) {
    console.error("❌ cambiarPasswordCaja:", error);
    res.status(500).json({ ok: false });
  }
};

/* =====================================================
   🚫 DESACTIVAR (NO ELIMINAR)
===================================================== */
exports.desactivarUsuarioCaja = async (req, res) => {
  try {

    const { id } = req.params;
    const id_local = req.user.id_local;

    const [[user]] = await db.query(`
      SELECT id_usuario FROM usuarios
      WHERE id_usuario = ? AND id_local = ? AND rol = 'CAJA'
    `, [id, id_local]);

    if (!user) {
      return res.status(403).json({ ok: false });
    }

    await db.query(`
      UPDATE usuarios SET activo = 0
      WHERE id_usuario = ?
    `, [id]);

    res.json({
      ok: true,
      mensaje: "Usuario desactivado"
    });

  } catch (error) {
    console.error("❌ desactivarUsuarioCaja:", error);
    res.status(500).json({ ok: false });
  }
};