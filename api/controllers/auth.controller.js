const bcrypt = require("bcryptjs");
const jwt = require("jsonwebtoken");
const db = require("../db/db");
const jwtConfig = require("../config/jwt");

/* =====================================================
   👑 CREAR SUPER ADMIN (SOLO POSTMAN)
===================================================== */
exports.crearSuperAdmin = async (req, res) => {
  try {
    const { usuario, correo, password } = req.body;

    if (!usuario || !password) {
      return res.status(400).json({
        ok: false,
        mensaje: "Usuario y contraseña son obligatorios"
      });
    }

    // 🔎 Verificar si ya existe
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

    // 🔐 Hash
    const hash = await bcrypt.hash(password, 10);

    // ➕ Insertar SUPER ADMIN (sin local)
    const [result] = await db.query(
      `INSERT INTO usuarios (
        id_local,
        usuario,
        correo,
        password,
        rol,
        activo
      ) VALUES (NULL, ?, ?, ?, 'SUPER_ADMIN', 1)`,
      [usuario, correo || null, hash]
    );

    res.status(201).json({
      ok: true,
      mensaje: "SUPER ADMIN creado correctamente",
      data: {
        id_usuario: result.insertId,
        usuario,
        rol: "SUPER_ADMIN"
      }
    });

  } catch (error) {
    console.error("❌ Error crearSuperAdmin:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error interno"
    });
  }
};

/* =====================================================
   🔐 LOGIN (MULTIROL READY)
===================================================== */
exports.login = async (req, res) => {
  try {
    const { usuario, password } = req.body;

    if (!usuario || !password) {
      return res.status(400).json({
        ok: false,
        mensaje: "Usuario y contraseña son obligatorios"
      });
    }

    // 🔎 Buscar usuario
    const [[user]] = await db.query(
      `SELECT 
        id_usuario,
        id_local,
        usuario,
        correo,
        password,
        rol,
        activo
       FROM usuarios
       WHERE usuario = ?
       LIMIT 1`,
      [usuario]
    );

    if (!user) {
      return res.status(404).json({
        ok: false,
        mensaje: "Usuario no encontrado"
      });
    }

    if (!user.activo) {
      return res.status(401).json({
        ok: false,
        mensaje: "Usuario inactivo"
      });
    }

    // 🔐 Validar password
    const valido = await bcrypt.compare(password, user.password);
    if (!valido) {
      return res.status(401).json({
        ok: false,
        mensaje: "Contraseña incorrecta"
      });
    }

    // 🧠 ROLES (escala a futuro)
    const roles = [user.rol];

    // 🔑 Payload limpio
    const payload = {
      id_usuario: user.id_usuario,
      id_local: user.id_local,
      usuario: user.usuario,
      correo: user.correo,
      roles
    };

    // 🔐 Token
    const token = jwt.sign(payload, jwtConfig.secret, {
      expiresIn: jwtConfig.expiresIn
    });

    res.json({
      ok: true,
      mensaje: "Login exitoso",
      token,
      usuario: payload
    });

  } catch (error) {
    console.error("❌ Error login:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al iniciar sesión"
    });
  }
};
