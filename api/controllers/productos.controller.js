const db = require("../db/db");
const bwipjs = require("bwip-js");
const PDFDocument = require("pdfkit");
const fs = require("fs");
const path = require("path");

/* ===============================
   HELPERS
=============================== */
function val(v) {
  if (!v || v === "") return "N/A";
  return String(v).trim();
}

function num(v) {
  const n = Number(v);
  return Number.isFinite(n) ? n : 0;
}

function boolDb(v) {
  if (typeof v === "boolean") return v ? 1 : 0;
  if (typeof v === "number") return v === 1 ? 1 : 0;
  if (typeof v === "string") {
    return ["1", "true", "si", "sí", "yes"].includes(v.trim().toLowerCase()) ? 1 : 0;
  }
  return 0;
}

function generarCodigoBarras(id_local) {
  return `${id_local}${Date.now()}`;
}

function generarSKU(nombre, id_local) {
  return (
    nombre.toUpperCase().replace(/[^A-Z0-9]/g, "").slice(0, 5) +
    "-" + id_local +
    "-" + Date.now().toString().slice(-4)
  );
}

function eliminarArchivoProducto(nombreArchivo) {
  if (!nombreArchivo) return;

  const ruta = path.join(__dirname, "../uploads/productos", nombreArchivo);

  if (!fs.existsSync(ruta)) return;

  try {
    fs.unlinkSync(ruta);
  } catch (error) {
    console.error("⚠️ No se pudo eliminar imagen de producto:", error.message);
  }
}

/* ===============================
   CREAR PRODUCTO PRO
=============================== */
exports.crearProducto = async (req, res) => {
  try {
    const id_local = req.user.id_local;

    let {
      id_subcategoria,
      nombre_producto,
      marca,
      color,
      capacidad,
      estado,
      precio_compra,
      precio_unitario,
      precio_mayorista,
      precio_docena,
      codigo_barras,
      sku,
      stock_minimo,
      graba_iva
    } = req.body;

    const imagen = req.file ? req.file.filename : null;

    if (!nombre_producto || !id_subcategoria || !precio_unitario) {
      return res.status(400).json({ ok: false, mensaje: "Campos obligatorios faltantes" });
    }

    codigo_barras = codigo_barras || generarCodigoBarras(id_local);
    sku = sku || generarSKU(nombre_producto, id_local);

    await db.query(`
      INSERT INTO productos (
        id_subcategoria,
        id_local,
        marca,
        color,
        capacidad,
        estado,
        nombre_producto,
        imagen,
        precio_compra,
        precio_unitario,
        precio_mayorista,
        precio_docena,
        codigo_barras,
        sku,
        stock_minimo,
        graba_iva,
        activo
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    `, [
      id_subcategoria,
      id_local,
      val(marca),
      val(color),
      val(capacidad),
      val(estado),
      nombre_producto,
      imagen,
      num(precio_compra),
      num(precio_unitario),
      num(precio_mayorista),
      num(precio_docena),
      codigo_barras,
      sku,
      num(stock_minimo),
      boolDb(graba_iva)
    ]);

    res.json({ ok: true, mensaje: "Producto creado" });

  } catch (err) {
    console.error(err);
    res.status(500).json({ ok: false });
  }
};

/* ===============================
   LISTAR (YA CON NUEVOS CAMPOS)
=============================== */
exports.listarProductos = async (req, res) => {
  try {
    const id_local = req.user.id_local;

    // 🔥 parámetros
    const page  = Math.max(1, Number(req.query.page || 1));
    const limit = Math.max(1, Number(req.query.limit || 20));
    const offset = (page - 1) * limit;

    // 🔹 TOTAL REGISTROS
    const [[count]] = await db.query(`
      SELECT COUNT(*) as total
      FROM productos
      WHERE id_local = ?
    `, [id_local]);

    const total = count.total;
    const total_pages = Math.ceil(total / limit);

    // 🔹 DATA PAGINADA
    const [rows] = await db.query(`
      SELECT 
        p.*,
        s.nombre_subcategoria,
        c.nombre_categoria
      FROM productos p
      JOIN subcategorias s ON s.id_subcategoria = p.id_subcategoria
      JOIN categorias c ON c.id_categoria = s.id_categoria
      WHERE p.id_local = ?
      ORDER BY p.id_producto DESC
      LIMIT ? OFFSET ?
    `, [id_local, limit, offset]);

    res.json({
      ok: true,
      data: rows,

      // 🔥 metadata útil para frontend
      pagination: {
        page,
        limit,
        total,
        total_pages
      }
    });

  } catch (err) {
    console.error("❌ listarProductos:", err);
    res.status(500).json({ ok: false });
  }
};
/* ===============================
   ACTUALIZAR PRO
=============================== */
exports.actualizarProducto = async (req, res) => {
  try {
    const id_local = req.user.id_local;
    const { id } = req.params;
    const imagen = req.file ? req.file.filename : null;

    const {
      id_subcategoria,
      nombre_producto,
      marca,
      color,
      capacidad,
      estado,
      precio_compra,
      precio_unitario,
      precio_mayorista,
      precio_docena,
      stock_minimo,
      activo,
      graba_iva
    } = req.body;

    const [[productoActual]] = await db.query(`
      SELECT
        id_producto,
        id_subcategoria,
        nombre_producto,
        marca,
        color,
        capacidad,
        estado,
        imagen,
        precio_compra,
        precio_unitario,
        precio_mayorista,
        precio_docena,
        stock_minimo,
        graba_iva,
        activo
      FROM productos
      WHERE id_producto = ? AND id_local = ?
      LIMIT 1
    `, [id, id_local]);

    if (!productoActual) {
      return res.status(404).json({
        ok: false,
        mensaje: "Producto no encontrado"
      });
    }

    const [result] = await db.query(`
      UPDATE productos SET
        id_subcategoria = ?,
        nombre_producto = ?,
        marca = ?,
        color = ?,
        capacidad = ?,
        estado = ?,
        imagen = ?,
        precio_compra = ?,
        precio_unitario = ?,
        precio_mayorista = ?,
        precio_docena = ?,
        stock_minimo = ?,
        graba_iva = ?,
        activo = ?
      WHERE id_producto = ? AND id_local = ?
    `, [
      id_subcategoria !== undefined ? id_subcategoria : productoActual.id_subcategoria,
      nombre_producto !== undefined ? nombre_producto : productoActual.nombre_producto,
      marca !== undefined ? val(marca) : productoActual.marca,
      color !== undefined ? val(color) : productoActual.color,
      capacidad !== undefined ? val(capacidad) : productoActual.capacidad,
      estado !== undefined ? val(estado) : productoActual.estado,
      imagen || productoActual.imagen,
      precio_compra !== undefined ? num(precio_compra) : num(productoActual.precio_compra),
      precio_unitario !== undefined ? num(precio_unitario) : num(productoActual.precio_unitario),
      precio_mayorista !== undefined ? num(precio_mayorista) : num(productoActual.precio_mayorista),
      precio_docena !== undefined ? num(precio_docena) : num(productoActual.precio_docena),
      stock_minimo !== undefined ? num(stock_minimo) : num(productoActual.stock_minimo),
      graba_iva !== undefined ? boolDb(graba_iva) : boolDb(productoActual.graba_iva),
      activo !== undefined ? boolDb(activo) : boolDb(productoActual.activo),
      id,
      id_local
    ]);

    if (result.affectedRows === 0) {
      return res.status(404).json({
        ok: false,
        mensaje: "Producto no encontrado"
      });
    }

    res.json({ ok: true, mensaje: "Producto actualizado correctamente" });

  } catch (err) {
    console.error("❌ actualizarProducto:", err);
    res.status(500).json({ ok: false, mensaje: "Error al actualizar producto" });
  }
};

/* ===============================
   ELIMINAR PRODUCTO COMPLETO
=============================== */
exports.eliminarProducto = async (req, res) => {
  const connection = await db.getConnection();
  let enTransaccion = false;

  try {
    const id_local = req.user.id_local;
    const { id } = req.params;

    const [[producto]] = await connection.query(`
      SELECT
        id_producto,
        id_local,
        nombre_producto,
        imagen
      FROM productos
      WHERE id_producto = ? AND id_local = ?
      LIMIT 1
    `, [id, id_local]);

    if (!producto) {
      return res.status(404).json({
        ok: false,
        mensaje: "Producto no encontrado"
      });
    }

    const [[ventasRelacionadas]] = await connection.query(`
      SELECT COUNT(*) AS total
      FROM detalle_venta
      WHERE id_producto = ?
    `, [id]);

    if (Number(ventasRelacionadas?.total || 0) > 0) {
      return res.status(409).json({
        ok: false,
        mensaje: "No se puede eliminar el producto porque ya tiene ventas registradas"
      });
    }

    await connection.beginTransaction();
    enTransaccion = true;

    await connection.query(`
      DELETE FROM inventario_imei
      WHERE id_producto = ?
        AND id_local = ?
    `, [id, id_local]);

    await connection.query(`
      DELETE FROM inventario_stock
      WHERE id_producto = ?
        AND id_local = ?
    `, [id, id_local]);

    const [deleted] = await connection.query(`
      DELETE FROM productos
      WHERE id_producto = ?
        AND id_local = ?
    `, [id, id_local]);

    if (!deleted.affectedRows) {
      await connection.rollback();
      enTransaccion = false;

      return res.status(404).json({
        ok: false,
        mensaje: "Producto no encontrado"
      });
    }

    await connection.commit();
    enTransaccion = false;

    eliminarArchivoProducto(producto.imagen);

    res.json({
      ok: true,
      mensaje: "Producto eliminado correctamente"
    });

  } catch (err) {
    if (enTransaccion) {
      await connection.rollback();
    }

    console.error("❌ eliminarProducto:", err);
    res.status(500).json({
      ok: false,
      mensaje: "Error al eliminar producto"
    });
  } finally {
    connection.release();
  }
};

/* ===============================
   ETIQUETA PRO (COMO TU FOTO)
=============================== */
exports.generarEtiquetas = async (req, res) => {
  try {
    const id_local = req.user.id_local;
    const { id } = req.params;
    const { cantidad = 1 } = req.body;

    const [[p]] = await db.query(`
      SELECT nombre_producto, precio_unitario, codigo_barras
      FROM productos
      WHERE id_producto = ? AND id_local = ?
    `, [id, id_local]);

    if (!p) return res.status(404).json({ ok: false });

    // ✅ TAMAÑO FIJO POR ETIQUETA
    const doc = new PDFDocument({
      size: [85, 60], // 🔥 YA NO MULTIPLICADO
      margin: 2
    });

    res.setHeader("Content-Type", "application/pdf");
    doc.pipe(res);

   const barcode = await bwipjs.toBuffer({
  bcid: "code128",
  text: p.codigo_barras,
  scale: 3,         // no tan alto
  height: 10,
  includetext: false,
  paddingwidth: 6,
});

    for (let i = 0; i < cantidad; i++) {

      if (i > 0) doc.addPage(); // 🔥 CLAVE

      // fondo
      doc.rect(0, 0, 85, 60).fill("#FFFFFF");
      doc.fillColor("#000");

      // 🏪 negocio
      doc.font("Helvetica-Bold")
        .fontSize(6)
        .text("Connect Tecnología", 2, 2, {
          width: 80,
          align: "center"
        });

      // 🔳 barcode
     doc.image(barcode, 2, 10, {
  fit: [80, 18],   // 🔥 tamaño controlado SIN deformar
  align: "center"
});

      // 🔢 código
      doc.font("Helvetica")
        .fontSize(7)
        .text(p.codigo_barras, 2, 29, {
          width: 80,
          align: "center"
        });

      let nombre = p.nombre_producto;

if (nombre.length > 40) {
  nombre = nombre.substring(0, 37) + "...";
}

doc.fontSize(6)
  .text(nombre, 2, 37, {
    width: 80,
    align: "center"
  });

      // 💲 precio
      doc.font("Helvetica-Bold")
        .fontSize(10)
        .text(`$${Number(p.precio_unitario).toFixed(2)}`, 2, 44, {
          width: 80,
          align: "center"
        });
    }

    doc.end();

  } catch (err) {
    console.error(err);
    res.status(500).json({ ok: false });
  }
};

/* ===============================
   🔎 BUSCAR PRODUCTOS (INTELIGENTE)
=============================== */
exports.buscarProductos = async (req, res) => {
  try {
    const id_local = req.user.id_local;
    let { q } = req.query;

    if (!q || q.trim().length < 2) {
      return res.json({ ok: true, data: [] });
    }

    q = q.trim();

    const palabras = q.split(" ");

    let where = ` WHERE p.id_local = ? `;
    let params = [id_local];

    // 🔥 CAMBIO: OR GLOBAL (no AND)
    let condiciones = [];

    palabras.forEach(palabra => {
      const like = `%${palabra}%`;

      condiciones.push(`
        p.nombre_producto LIKE ?
        OR p.marca LIKE ?
        OR p.codigo_barras LIKE ?
        OR p.sku LIKE ?
      `);

      params.push(like, like, like, like);
    });

    if (condiciones.length) {
      where += ` AND ( ${condiciones.join(" OR ")} )`;
    }

    const [rows] = await db.query(`
  SELECT 
    p.*,
    s.nombre_subcategoria,
    c.nombre_categoria
  FROM productos p
  INNER JOIN subcategorias s ON s.id_subcategoria = p.id_subcategoria
  INNER JOIN categorias c ON c.id_categoria = s.id_categoria
  ${where}
  ORDER BY p.nombre_producto ASC
  LIMIT 50
`, params);
    res.json({ ok: true, data: rows });

  } catch (error) {
    console.error("❌ buscarProductos:", error);
    res.status(500).json({ ok: false });
  }
};
