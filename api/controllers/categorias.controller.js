const db = require("../db/db");

/* =====================================================
   ➕ CREAR CATEGORÍA
   id_local se toma del TOKEN
===================================================== */
exports.crearCategoria = async (req, res) => {
  try {
    const { nombre_categoria } = req.body;
    const { id_local } = req.user; // 👈 DESDE EL TOKEN

    if (!nombre_categoria) {
      return res.status(400).json({
        ok: false,
        mensaje: "El nombre de la categoría es obligatorio"
      });
    }

    // Verificar duplicado en el MISMO local
    const [existe] = await db.query(
      `SELECT id_categoria
       FROM categorias
       WHERE id_local = ? AND nombre_categoria = ?
       LIMIT 1`,
      [id_local, nombre_categoria]
    );

    if (existe.length > 0) {
      return res.status(409).json({
        ok: false,
        mensaje: "La categoría ya existe en este local"
      });
    }

    // Insertar categoría
    const [result] = await db.query(
      `INSERT INTO categorias (
        id_local,
        nombre_categoria,
        activo
      ) VALUES (?, ?, 1)`,
      [id_local, nombre_categoria]
    );

    res.status(201).json({
      ok: true,
      mensaje: "Categoría creada correctamente",
      data: {
        id_categoria: result.insertId,
        nombre_categoria
      }
    });

  } catch (error) {
    console.error("❌ Error crearCategoria:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error interno al crear la categoría"
    });
  }
};

/* =====================================================
   📋 LISTAR CATEGORÍAS DEL LOCAL
===================================================== */
exports.listarCategorias = async (req, res) => {
  try {
    const { id_local } = req.user;

    const [rows] = await db.query(
      `SELECT
        id_categoria,
        nombre_categoria,
        activo,
        fecha_creacion
       FROM categorias
       WHERE id_local = ?
       ORDER BY nombre_categoria ASC`,
      [id_local]
    );

    res.json({
      ok: true,
      data: rows
    });

  } catch (error) {
    console.error("❌ Error listarCategorias:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al listar categorías"
    });
  }
};

/* =====================================================
   🔍 OBTENER CATEGORÍA POR ID (DEL MISMO LOCAL)
===================================================== */
exports.obtenerCategoria = async (req, res) => {
  try {
    const { id } = req.params;
    const { id_local } = req.user;

    const [[categoria]] = await db.query(
      `SELECT *
       FROM categorias
       WHERE id_categoria = ? AND id_local = ?`,
      [id, id_local]
    );

    if (!categoria) {
      return res.status(404).json({
        ok: false,
        mensaje: "Categoría no encontrada"
      });
    }

    res.json({
      ok: true,
      data: categoria
    });

  } catch (error) {
    console.error("❌ Error obtenerCategoria:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al obtener categoría"
    });
  }
};

/* =====================================================
   ✏️ ACTUALIZAR CATEGORÍA
===================================================== */
exports.actualizarCategoria = async (req, res) => {
  try {
    const { id } = req.params;
    const { nombre_categoria, activo } = req.body;
    const { id_local } = req.user;

    if (!nombre_categoria) {
      return res.status(400).json({
        ok: false,
        mensaje: "El nombre de la categoría es obligatorio"
      });
    }

    const [result] = await db.query(
      `UPDATE categorias SET
        nombre_categoria = ?,
        activo = ?
       WHERE id_categoria = ? AND id_local = ?`,
      [
        nombre_categoria,
        activo !== undefined ? (activo ? 1 : 0) : 1,
        id,
        id_local
      ]
    );

    if (result.affectedRows === 0) {
      return res.status(404).json({
        ok: false,
        mensaje: "Categoría no encontrada"
      });
    }

    res.json({
      ok: true,
      mensaje: "Categoría actualizada correctamente"
    });

  } catch (error) {
    console.error("❌ Error actualizarCategoria:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al actualizar categoría"
    });
  }
};

/* =====================================================
   ❌ DESACTIVAR CATEGORÍA (NO BORRAR)
===================================================== */
exports.desactivarCategoria = async (req, res) => {
  try {
    const { id } = req.params;
    const { id_local } = req.user;

    const [result] = await db.query(
      `UPDATE categorias
       SET activo = 0
       WHERE id_categoria = ? AND id_local = ?`,
      [id, id_local]
    );

    if (result.affectedRows === 0) {
      return res.status(404).json({
        ok: false,
        mensaje: "Categoría no encontrada"
      });
    }

    res.json({
      ok: true,
      mensaje: "Categoría desactivada correctamente"
    });

  } catch (error) {
    console.error("❌ Error desactivarCategoria:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al desactivar categoría"
    });
  }
};