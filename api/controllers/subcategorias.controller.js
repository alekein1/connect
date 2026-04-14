const db = require("../db/db");

/* ===============================
   LISTAR
=============================== */
exports.listarSubcategorias = async (req, res) => {
  try {
    const [rows] = await db.query(`
      SELECT 
        s.id_subcategoria,
        s.id_categoria,
        c.nombre_categoria,
        s.nombre_subcategoria,
        s.activo
      FROM subcategorias s
      INNER JOIN categorias c ON c.id_categoria = s.id_categoria
      ORDER BY s.id_subcategoria DESC
    `);

    res.json({ ok: true, data: rows });
  } catch (error) {
    res.status(500).json({ ok: false, mensaje: "Error al listar" });
  }
};

/* ===============================
   CREAR
=============================== */
exports.crearSubcategoria = async (req, res) => {
  try {
    const { id_categoria, nombre_subcategoria } = req.body;

    await db.query(
      `INSERT INTO subcategorias (id_categoria, nombre_subcategoria, activo)
       VALUES (?, ?, 1)`,
      [id_categoria, nombre_subcategoria]
    );

    res.json({ ok: true, mensaje: "Subcategoría creada" });
  } catch (error) {
    res.status(500).json({ ok: false });
  }
};

/* ===============================
   ACTUALIZAR
=============================== */
exports.actualizarSubcategoria = async (req, res) => {
  try {
    const { nombre_subcategoria } = req.body;
    const { id } = req.params;

    await db.query(
      `UPDATE subcategorias
       SET nombre_subcategoria = ?
       WHERE id_subcategoria = ?`,
      [nombre_subcategoria, id]
    );

    res.json({ ok: true });
  } catch (error) {
    res.status(500).json({ ok: false });
  }
};

/* ===============================
   DESACTIVAR
=============================== */
exports.desactivarSubcategoria = async (req, res) => {
  try {
    const { id } = req.params;

    await db.query(
      `UPDATE subcategorias SET activo = 0 WHERE id_subcategoria = ?`,
      [id]
    );

    res.json({ ok: true });
  } catch (error) {
    res.status(500).json({ ok: false });
  }
};