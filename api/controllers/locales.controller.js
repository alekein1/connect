const db = require("../db/db");
const {
  ensureSriTables,
  validateSriMasterLink
} = require("../services/sri-config.service");

/* =====================================================
   📋 LISTAR LOCALES
===================================================== */
exports.listarLocales = async (req, res) => {
  try {
    await ensureSriTables();

    const [rows] = await db.query(`
      SELECT
        l.*,
        ml.nombre_local AS nombre_local_sri_maestro
      FROM locales l
      LEFT JOIN locales ml
        ON ml.id_local = l.id_local_sri_maestro
      ORDER BY l.id_local DESC
    `);

    res.json({
      ok: true,
      data: rows
    });

  } catch (error) {
    console.error("❌ listarLocales:", error);
    res.status(error.status || 500).json({ ok: false, mensaje: error.message || "Error interno" });
  }
};

/* =====================================================
   🔍 OBTENER LOCAL
===================================================== */
exports.obtenerLocal = async (req, res) => {
  try {
    await ensureSriTables();

    const { id } = req.params;

    const [[local]] = await db.query(
      `
        SELECT
          l.*,
          ml.nombre_local AS nombre_local_sri_maestro
        FROM locales l
        LEFT JOIN locales ml
          ON ml.id_local = l.id_local_sri_maestro
        WHERE l.id_local = ?
      `,
      [id]
    );

    if (!local) {
      return res.status(404).json({
        ok: false,
        mensaje: "Local no encontrado"
      });
    }

    res.json({
      ok: true,
      data: local
    });

  } catch (error) {
    console.error("❌ obtenerLocal:", error);
    res.status(error.status || 500).json({ ok: false, mensaje: error.message || "Error al obtener el local" });
  }
};

/* =====================================================
   ➕ CREAR LOCAL
===================================================== */
exports.crearLocal = async (req, res) => {
  try {
    await ensureSriTables();

    const { nombre_local, direccion, telefono } = req.body;
    const id_local_sri_maestro = await validateSriMasterLink(
      null,
      req.body?.id_local_sri_maestro
    );

    if (!nombre_local) {
      return res.status(400).json({
        ok: false,
        mensaje: "Nombre del local requerido"
      });
    }

    const [result] = await db.query(`
      INSERT INTO locales (nombre_local, direccion, telefono, id_local_sri_maestro, activo)
      VALUES (?, ?, ?, ?, 1)
    `, [nombre_local, direccion || null, telefono || null, id_local_sri_maestro]);

    res.status(201).json({
      ok: true,
      mensaje: "Local creado",
      id_local: result.insertId
    });

  } catch (error) {
    console.error("❌ crearLocal:", error);
    res.status(error.status || 500).json({ ok: false, mensaje: error.message || "Error al crear el local" });
  }
};

/* =====================================================
   ✏️ ACTUALIZAR LOCAL
===================================================== */
exports.actualizarLocal = async (req, res) => {
  try {
    await ensureSriTables();

    const { id } = req.params;
    const { nombre_local, direccion, telefono, activo } = req.body;
    const id_local_sri_maestro = await validateSriMasterLink(
      Number(id),
      req.body?.id_local_sri_maestro
    );

    await db.query(`
      UPDATE locales
      SET nombre_local = ?, direccion = ?, telefono = ?, id_local_sri_maestro = ?, activo = ?
      WHERE id_local = ?
    `, [
      nombre_local,
      direccion || null,
      telefono || null,
      id_local_sri_maestro,
      activo ?? 1,
      id
    ]);

    res.json({
      ok: true,
      mensaje: "Local actualizado"
    });

  } catch (error) {
    console.error("❌ actualizarLocal:", error);
    res.status(error.status || 500).json({ ok: false, mensaje: error.message || "Error al actualizar el local" });
  }
};

/* =====================================================
   🗑️ ELIMINAR LOCAL (SOFT DELETE)
===================================================== */
exports.eliminarLocal = async (req, res) => {
  try {
    await ensureSriTables();

    const { id } = req.params;
    const [[dependencias]] = await db.query(`
      SELECT COUNT(*) AS total
      FROM locales
      WHERE id_local_sri_maestro = ?
        AND activo = 1
    `, [id]);

    if (Number(dependencias?.total || 0) > 0) {
      return res.status(400).json({
        ok: false,
        mensaje: "No puedes desactivar este local porque otros locales usan su configuración SRI"
      });
    }

    await db.query(`
      UPDATE locales SET activo = 0
      WHERE id_local = ?
    `, [id]);

    res.json({
      ok: true,
      mensaje: "Local desactivado"
    });

  } catch (error) {
    console.error("❌ eliminarLocal:", error);
    res.status(error.status || 500).json({ ok: false, mensaje: error.message || "Error al desactivar el local" });
  }
};
