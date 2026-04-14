const db = require("../db/db");

function normalizarImei(valor = "") {
  return String(valor || "")
    .replace(/\D+/g, "")
    .trim();
}

async function buscarImeiExistente(connection, imei) {
  const [[duplicado]] = await connection.query(
    `
    SELECT id_imei, imei1, imei2
    FROM inventario_imei
    WHERE imei1 = ? OR imei2 = ?
    LIMIT 1
    `,
    [imei, imei]
  );

  return duplicado || null;
}

function normalizarTexto(valor = "") {
  return String(valor || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .trim()
    .toLowerCase();
}

function textoNullable(valor = "") {
  const limpio = String(valor ?? "").trim();

  if (!limpio || limpio.toUpperCase() === "N/A") {
    return null;
  }

  return limpio;
}

function numeroSeguro(valor) {
  const numero = Number(valor);
  return Number.isFinite(numero) ? numero : 0;
}

function boolDb(valor) {
  if (typeof valor === "boolean") return valor ? 1 : 0;
  if (typeof valor === "number") return valor === 1 ? 1 : 0;
  if (typeof valor === "string") {
    return ["1", "true", "si", "sí", "yes"].includes(valor.trim().toLowerCase()) ? 1 : 0;
  }
  return 0;
}

function generarCodigoBarrasBase(idLocal) {
  return `${idLocal}${Date.now()}${Math.floor(Math.random() * 1000).toString().padStart(3, "0")}`;
}

function generarSkuBase(nombre, idLocal) {
  return (
    String(nombre || "PRODUCTO")
      .toUpperCase()
      .replace(/[^A-Z0-9]/g, "")
      .slice(0, 5) +
    "-" + idLocal +
    "-" + Date.now().toString().slice(-4)
  );
}

function esProductoConImei(producto = {}) {
  const categoria = normalizarTexto(producto.nombre_categoria);
  const subcategoria = normalizarTexto(producto.nombre_subcategoria);

  return (
    categoria.includes("celular") ||
    subcategoria.includes("celular") ||
    subcategoria.includes("telefono")
  );
}

function crearReferenciaTraspaso({
  idLocalOrigen,
  idLocalDestino,
  idProductoOrigen,
  idProductoDestino,
  cantidad
}) {
  return `TRASPASO:${idLocalOrigen}>${idLocalDestino}:${idProductoOrigen}>${idProductoDestino}:${cantidad}:${Date.now()}`
    .slice(0, 100);
}

async function generarCodigoBarrasUnico(connection, idLocal) {
  for (let intento = 0; intento < 10; intento++) {
    const codigo = generarCodigoBarrasBase(idLocal);
    const [[existe]] = await connection.query(
      `SELECT id_producto FROM productos WHERE codigo_barras = ? LIMIT 1`,
      [codigo]
    );

    if (!existe) {
      return codigo;
    }
  }

  throw new Error("No se pudo generar un código de barras único para el local destino");
}

async function asegurarCategoriaDestino(connection, idLocalDestino, nombreCategoria) {
  const nombre = textoNullable(nombreCategoria) || "General";
  const [[categoria]] = await connection.query(
    `
    SELECT id_categoria, activo
    FROM categorias
    WHERE id_local = ?
      AND nombre_categoria = ?
    LIMIT 1
    `,
    [idLocalDestino, nombre]
  );

  if (categoria) {
    if (Number(categoria.activo) !== 1) {
      await connection.query(
        `UPDATE categorias SET activo = 1 WHERE id_categoria = ?`,
        [categoria.id_categoria]
      );
    }

    return categoria.id_categoria;
  }

  const [result] = await connection.query(
    `
    INSERT INTO categorias (id_local, nombre_categoria, activo)
    VALUES (?, ?, 1)
    `,
    [idLocalDestino, nombre]
  );

  return result.insertId;
}

async function asegurarSubcategoriaDestino(connection, idCategoriaDestino, nombreSubcategoria) {
  const nombre = textoNullable(nombreSubcategoria) || "General";
  const [[subcategoria]] = await connection.query(
    `
    SELECT id_subcategoria, activo
    FROM subcategorias
    WHERE id_categoria = ?
      AND nombre_subcategoria = ?
    LIMIT 1
    `,
    [idCategoriaDestino, nombre]
  );

  if (subcategoria) {
    if (Number(subcategoria.activo) !== 1) {
      await connection.query(
        `UPDATE subcategorias SET activo = 1 WHERE id_subcategoria = ?`,
        [subcategoria.id_subcategoria]
      );
    }

    return subcategoria.id_subcategoria;
  }

  const [result] = await connection.query(
    `
    INSERT INTO subcategorias (id_categoria, nombre_subcategoria, activo)
    VALUES (?, ?, 1)
    `,
    [idCategoriaDestino, nombre]
  );

  return result.insertId;
}

async function asegurarProductoDestino(connection, productoOrigen, idLocalDestino) {
  const idCategoriaDestino = await asegurarCategoriaDestino(
    connection,
    idLocalDestino,
    productoOrigen.nombre_categoria
  );

  const idSubcategoriaDestino = await asegurarSubcategoriaDestino(
    connection,
    idCategoriaDestino,
    productoOrigen.nombre_subcategoria
  );

  const [[productoDestino]] = await connection.query(
    `
    SELECT id_producto, activo
    FROM productos
    WHERE id_local = ?
      AND id_subcategoria = ?
      AND nombre_producto = ?
      AND marca <=> ?
      AND color <=> ?
      AND capacidad <=> ?
      AND estado <=> ?
    LIMIT 1
    `,
    [
      idLocalDestino,
      idSubcategoriaDestino,
      productoOrigen.nombre_producto,
      textoNullable(productoOrigen.marca),
      textoNullable(productoOrigen.color),
      textoNullable(productoOrigen.capacidad),
      textoNullable(productoOrigen.estado)
    ]
  );

  if (productoDestino) {
    if (Number(productoDestino.activo) !== 1) {
      await connection.query(
        `UPDATE productos SET activo = 1 WHERE id_producto = ?`,
        [productoDestino.id_producto]
      );
    }

    return {
      id_producto: productoDestino.id_producto,
      creado: false
    };
  }

  const codigoBarras = await generarCodigoBarrasUnico(connection, idLocalDestino);
  const sku = generarSkuBase(productoOrigen.nombre_producto, idLocalDestino);

  const [result] = await connection.query(
    `
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
    `,
    [
      idSubcategoriaDestino,
      idLocalDestino,
      textoNullable(productoOrigen.marca),
      textoNullable(productoOrigen.color),
      textoNullable(productoOrigen.capacidad),
      textoNullable(productoOrigen.estado) || "Nuevo",
      productoOrigen.nombre_producto,
      textoNullable(productoOrigen.imagen),
      numeroSeguro(productoOrigen.precio_compra),
      numeroSeguro(productoOrigen.precio_unitario),
      numeroSeguro(productoOrigen.precio_mayorista),
      numeroSeguro(productoOrigen.precio_docena),
      codigoBarras,
      sku,
      numeroSeguro(productoOrigen.stock_minimo),
      boolDb(productoOrigen.graba_iva)
    ]
  );

  return {
    id_producto: result.insertId,
    creado: true
  };
}

async function asegurarStockProducto(connection, idProducto, idLocal) {
  let [[stock]] = await connection.query(
    `
    SELECT id_stock, stock_actual
    FROM inventario_stock
    WHERE id_producto = ? AND id_local = ?
    LIMIT 1
    FOR UPDATE
    `,
    [idProducto, idLocal]
  );

  if (stock) {
    return {
      id_stock: stock.id_stock,
      stock_actual: Number(stock.stock_actual || 0)
    };
  }

  const [result] = await connection.query(
    `
    INSERT INTO inventario_stock (id_producto, id_local, stock_actual)
    VALUES (?, ?, 0)
    `,
    [idProducto, idLocal]
  );

  return {
    id_stock: result.insertId,
    stock_actual: 0
  };
}

/* =====================================================
   📊 LISTAR INVENTARIO (CORREGIDO POR LOCAL)
===================================================== */
exports.listarInventario = async (req, res) => {
  try {
    const id_local = req.user.id_local;

    const {
      id_categoria,
      id_subcategoria,
      id_producto,
      stock_bajo,
      buscar
    } = req.query;

    let filtros = ` WHERE i.id_local = ? `;
    const params = [id_local];

    if (id_categoria) {
      filtros += ` AND c.id_categoria = ? `;
      params.push(id_categoria);
    }

    if (id_subcategoria) {
      filtros += ` AND s.id_subcategoria = ? `;
      params.push(id_subcategoria);
    }

    if (id_producto) {
      filtros += ` AND p.id_producto = ? `;
      params.push(id_producto);
    }

    if (stock_bajo === "1") {
      filtros += ` AND i.stock_actual <= p.stock_minimo `;
    }

    if (buscar) {
      filtros += ` AND (
        p.nombre_producto LIKE ?
        OR p.codigo_barras LIKE ?
        OR p.sku LIKE ?
      )`;
      params.push(`%${buscar}%`, `%${buscar}%`, `%${buscar}%`);
    }

    await db.query(
  `
  INSERT INTO inventario_stock (id_producto, id_local, stock_actual)
  SELECT p.id_producto, ?, 0
  FROM productos p
  WHERE p.id_local = ?   -- 🔥 ESTA ES LA CLAVE
  AND NOT EXISTS (
    SELECT 1
    FROM inventario_stock i
    WHERE i.id_producto = p.id_producto
      AND i.id_local = ?
  )
  `,
  [id_local, id_local, id_local]
);

    // 🔹 CONSULTA
    const [rows] = await db.query(
      `
      SELECT
        i.id_stock,
        i.stock_actual,
        i.fecha_actualizacion,

        p.id_producto,
        p.nombre_producto,
        p.codigo_barras,
        p.sku,
        p.precio_unitario,
        p.stock_minimo,
        p.imagen,

        s.id_subcategoria,
        s.nombre_subcategoria,

        c.id_categoria,
        c.nombre_categoria

      FROM inventario_stock i
      INNER JOIN productos p ON p.id_producto = i.id_producto
      INNER JOIN subcategorias s ON s.id_subcategoria = p.id_subcategoria
      INNER JOIN categorias c ON c.id_categoria = s.id_categoria
      ${filtros}
      ORDER BY p.nombre_producto ASC
      `,
      params
    );

    res.json({ ok: true, data: rows });

  } catch (error) {
    console.error("❌ listarInventario:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al listar inventario"
    });
  }
};

/* =====================================================
   ➕ AJUSTAR STOCK
===================================================== */
exports.ajustarStock = async (req, res) => {
  try {
    const id_local = req.user.id_local;
    const { id_producto } = req.params;
    const { cantidad } = req.body;

    if (!cantidad || isNaN(cantidad)) {
      return res.status(400).json({ ok: false, mensaje: "Cantidad inválida" });
    }

    // 🔥 VALIDAR PRODUCTO DEL LOCAL
    const [[producto]] = await db.query(
      `
      SELECT id_producto 
      FROM productos 
      WHERE id_producto = ? AND id_local = ?
      LIMIT 1
      `,
      [id_producto, id_local]
    );

    if (!producto) {
      return res.status(403).json({
        ok: false,
        mensaje: "Producto no pertenece a este local"
      });
    }

    // 🔹 BUSCAR STOCK
    const [[stock]] = await db.query(
      `
      SELECT id_stock, stock_actual
      FROM inventario_stock
      WHERE id_producto = ? AND id_local = ?
      LIMIT 1
      `,
      [id_producto, id_local]
    );

    let stockRow = stock;

    // 🔥 CREAR SI NO EXISTE
    if (!stockRow) {
      const [result] = await db.query(
        `
        INSERT INTO inventario_stock (id_producto, id_local, stock_actual)
        VALUES (?, ?, 0)
        `,
        [id_producto, id_local]
      );

      stockRow = {
        id_stock: result.insertId,
        stock_actual: 0
      };
    }

    const nuevoStock = stockRow.stock_actual + Number(cantidad);

    if (nuevoStock < 0) {
      return res.status(400).json({
        ok: false,
        mensaje: "Stock insuficiente"
      });
    }

    await db.query(
      `
      UPDATE inventario_stock
      SET stock_actual = ?, fecha_actualizacion = CURRENT_TIMESTAMP
      WHERE id_stock = ?
      `,
      [nuevoStock, stockRow.id_stock]
    );

    res.json({
      ok: true,
      mensaje: "Stock actualizado",
      stock_actual: nuevoStock
    });

  } catch (error) {
    console.error("❌ ajustarStock:", error);
    res.status(500).json({ ok: false });
  }
};

/* =====================================================
   📱 INGRESO POR IMEI (PRO)
===================================================== */
exports.ingresarPorIMEI = async (req, res) => {
  const connection = await db.getConnection();
  let transactionIniciada = false;

  try {
    const id_local = req.user.id_local;
    const { id_producto } = req.params;
    const { imeis } = req.body;

    if (!imeis || !Array.isArray(imeis) || imeis.length === 0) {
      return res.status(400).json({
        ok: false,
        mensaje: "Debe enviar IMEIs"
      });
    }

    // 🔥 VALIDAR PRODUCTO DEL LOCAL
    const [[producto]] = await connection.query(
      `
      SELECT id_producto
      FROM productos
      WHERE id_producto = ? AND id_local = ?
      LIMIT 1
      `,
      [id_producto, id_local]
    );

    if (!producto) {
      return res.status(403).json({
        ok: false,
        mensaje: "Producto no pertenece a este local"
      });
    }

    const equiposNormalizados = [];
    const imeisEnSolicitud = new Set();

    for (const item of imeis) {
      const imei1 = normalizarImei(item?.imei1);
      const imei2 = normalizarImei(item?.imei2);

      if (!imei1) continue;

      if (imei2 && imei1 === imei2) {
        return res.status(400).json({
          ok: false,
          mensaje: `IMEI 2 no puede ser igual a IMEI 1: ${imei1}`
        });
      }

      for (const imei of [imei1, imei2].filter(Boolean)) {
        if (imeisEnSolicitud.has(imei)) {
          return res.status(400).json({
            ok: false,
            mensaje: `IMEI repetido en la lista: ${imei}`
          });
        }

        imeisEnSolicitud.add(imei);
      }

      equiposNormalizados.push({
        imei1,
        imei2: imei2 || null
      });
    }

    if (equiposNormalizados.length === 0) {
      return res.status(400).json({
        ok: false,
        mensaje: "No se ingresaron IMEIs validos"
      });
    }

    await connection.beginTransaction();
    transactionIniciada = true;

    let cantidadIngresada = 0;

    for (const item of equiposNormalizados) {
      const { imei1, imei2 } = item;

      const existe1 = await buscarImeiExistente(connection, imei1);

      if (existe1) {
        await connection.rollback();
        return res.status(400).json({
          ok: false,
          mensaje: `IMEI duplicado: ${imei1}`
        });
      }

      // 🔥 VALIDAR IMEI2 SI EXISTE
      if (imei2) {
        const existe2 = await buscarImeiExistente(connection, imei2);

        if (existe2) {
          await connection.rollback();
          return res.status(400).json({
            ok: false,
            mensaje: `IMEI2 duplicado: ${imei2}`
          });
        }
      }

      // ✅ INSERTAR
      await connection.query(
        `
        INSERT INTO inventario_imei (
          id_producto,
          id_local,
          imei1,
          imei2,
          estado
        ) VALUES (?, ?, ?, ?, 'disponible')
        `,
        [id_producto, id_local, imei1, imei2 || null]
      );

      cantidadIngresada++;
    }

    // 🔥 STOCK
    const [[stock]] = await connection.query(
      `
      SELECT id_stock
      FROM inventario_stock
      WHERE id_producto = ? AND id_local = ?
      LIMIT 1
      `,
      [id_producto, id_local]
    );

    if (stock) {
      await connection.query(
        `
        UPDATE inventario_stock
        SET stock_actual = stock_actual + ?,
            fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_stock = ?
        `,
        [cantidadIngresada, stock.id_stock]
      );
    } else {
      await connection.query(
        `
        INSERT INTO inventario_stock (id_producto, id_local, stock_actual)
        VALUES (?, ?, ?)
        `,
        [id_producto, id_local, cantidadIngresada]
      );
    }

    await connection.commit();

    res.json({
      ok: true,
      mensaje: `Ingresados ${cantidadIngresada} equipos`
    });

  } catch (error) {
    if (transactionIniciada) {
      await connection.rollback();
    }

    console.error("❌ IMEI:", error);

    res.status(500).json({
      ok: false,
      mensaje: "Error al ingresar IMEIs"
    });

  } finally {
    connection.release();
  }
};

exports.listarLocalesDestino = async (req, res) => {
  try {
    const idLocalActual = Number(req.user.id_local || 0);

    const [rows] = await db.query(
      `
      SELECT id_local, nombre_local
      FROM locales
      WHERE activo = 1
        AND id_local <> ?
      ORDER BY nombre_local ASC
      `,
      [idLocalActual]
    );

    res.json({
      ok: true,
      data: rows
    });
  } catch (error) {
    console.error("❌ listarLocalesDestino:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al listar locales destino"
    });
  }
};

exports.listarImeisDisponibles = async (req, res) => {
  try {
    const idLocal = Number(req.user.id_local || 0);
    const idProducto = Number(req.params.id_producto || 0);

    if (!idProducto) {
      return res.status(400).json({
        ok: false,
        mensaje: "Producto inválido"
      });
    }

    const [[producto]] = await db.query(
      `
      SELECT id_producto
      FROM productos
      WHERE id_producto = ? AND id_local = ?
      LIMIT 1
      `,
      [idProducto, idLocal]
    );

    if (!producto) {
      return res.status(404).json({
        ok: false,
        mensaje: "Producto no encontrado en este local"
      });
    }

    const [rows] = await db.query(
      `
      SELECT
        id_imei,
        imei1,
        imei2,
        estado,
        fecha_ingreso
      FROM inventario_imei
      WHERE id_producto = ?
        AND id_local = ?
        AND estado = 'disponible'
      ORDER BY fecha_ingreso ASC, id_imei ASC
      `,
      [idProducto, idLocal]
    );

    res.json({
      ok: true,
      data: rows
    });
  } catch (error) {
    console.error("❌ listarImeisDisponibles:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al listar IMEIs disponibles"
    });
  }
};

exports.traspasarProducto = async (req, res) => {
  const connection = await db.getConnection();
  let transactionIniciada = false;

  try {
    const idUsuario = Number(req.user.id_usuario || 0);
    const idLocalOrigen = Number(req.user.id_local || 0);
    const idProductoOrigen = Number(req.params.id_producto || 0);
    const idLocalDestino = Number(req.body?.id_local_destino || 0);
    const cantidadSolicitada = Number(req.body?.cantidad || 0);
    const imeisSolicitados = Array.isArray(req.body?.imeis)
      ? req.body.imeis.map(normalizarImei).filter(Boolean)
      : [];

    if (!idProductoOrigen || !idLocalDestino) {
      return res.status(400).json({
        ok: false,
        mensaje: "Debes indicar producto y local destino"
      });
    }

    if (idLocalDestino === idLocalOrigen) {
      return res.status(400).json({
        ok: false,
        mensaje: "El local destino debe ser diferente al local actual"
      });
    }

    const [[localDestino]] = await connection.query(
      `
      SELECT id_local, nombre_local
      FROM locales
      WHERE id_local = ?
        AND activo = 1
      LIMIT 1
      `,
      [idLocalDestino]
    );

    if (!localDestino) {
      return res.status(404).json({
        ok: false,
        mensaje: "El local destino no existe o está inactivo"
      });
    }

    const [[productoOrigen]] = await connection.query(
      `
      SELECT
        p.id_producto,
        p.id_local,
        p.id_subcategoria,
        p.nombre_producto,
        p.marca,
        p.color,
        p.capacidad,
        p.estado,
        p.imagen,
        p.precio_compra,
        p.precio_unitario,
        p.precio_mayorista,
        p.precio_docena,
        p.stock_minimo,
        p.graba_iva,
        COALESCE(i.stock_actual, 0) AS stock_actual,
        s.nombre_subcategoria,
        c.nombre_categoria,
        (
          SELECT COUNT(*)
          FROM inventario_imei ii
          WHERE ii.id_producto = p.id_producto
            AND ii.id_local = p.id_local
            AND ii.estado = 'disponible'
        ) AS total_imeis_disponibles
      FROM productos p
      INNER JOIN subcategorias s
        ON s.id_subcategoria = p.id_subcategoria
      INNER JOIN categorias c
        ON c.id_categoria = s.id_categoria
      LEFT JOIN inventario_stock i
        ON i.id_producto = p.id_producto
       AND i.id_local = p.id_local
      WHERE p.id_producto = ?
        AND p.id_local = ?
      LIMIT 1
      `,
      [idProductoOrigen, idLocalOrigen]
    );

    if (!productoOrigen) {
      return res.status(404).json({
        ok: false,
        mensaje: "Producto no encontrado en tu local"
      });
    }

    const stockOrigenActual = Number(productoOrigen.stock_actual || 0);

    if (stockOrigenActual <= 0) {
      return res.status(400).json({
        ok: false,
        mensaje: "No hay stock disponible para traspasar"
      });
    }

    const productoUsaImei =
      esProductoConImei(productoOrigen) ||
      Number(productoOrigen.total_imeis_disponibles || 0) > 0;

    let cantidadTransferida = 0;
    let productoDestinoInfo = null;
    let stockOrigen = null;
    let stockDestino = null;

    await connection.beginTransaction();
    transactionIniciada = true;

    productoDestinoInfo = await asegurarProductoDestino(
      connection,
      productoOrigen,
      idLocalDestino
    );

    stockOrigen = await asegurarStockProducto(
      connection,
      idProductoOrigen,
      idLocalOrigen
    );

    stockDestino = await asegurarStockProducto(
      connection,
      productoDestinoInfo.id_producto,
      idLocalDestino
    );

    if (productoUsaImei) {
      if (!imeisSolicitados.length) {
        await connection.rollback();
        transactionIniciada = false;

        return res.status(400).json({
          ok: false,
          mensaje: "Debes seleccionar al menos un IMEI para traspasar"
        });
      }

      const imeisUnicos = [...new Set(imeisSolicitados)];

      if (imeisUnicos.length !== imeisSolicitados.length) {
        await connection.rollback();
        transactionIniciada = false;

        return res.status(400).json({
          ok: false,
          mensaje: "No puedes repetir IMEIs en el mismo traspaso"
        });
      }

      if (stockOrigen.stock_actual < imeisUnicos.length) {
        await connection.rollback();
        transactionIniciada = false;

        return res.status(400).json({
          ok: false,
          mensaje: "El stock del local origen no alcanza para esos IMEIs"
        });
      }

      const placeholdersImeis = imeisUnicos.map(() => "?").join(",");
      const [imeisDisponibles] = await connection.query(
        `
        SELECT id_imei, imei1, imei2
        FROM inventario_imei
        WHERE id_producto = ?
          AND id_local = ?
          AND estado = 'disponible'
          AND imei1 IN (${placeholdersImeis})
        ORDER BY fecha_ingreso ASC, id_imei ASC
        `,
        [idProductoOrigen, idLocalOrigen, ...imeisUnicos]
      );

      if (imeisDisponibles.length !== imeisUnicos.length) {
        const encontrados = new Set(imeisDisponibles.map(item => item.imei1));
        const faltantes = imeisUnicos.filter(imei => !encontrados.has(imei));

        await connection.rollback();
        transactionIniciada = false;

        return res.status(400).json({
          ok: false,
          mensaje: `IMEIs no disponibles para traspaso: ${faltantes.join(", ")}`
        });
      }

      const idsImeis = imeisDisponibles.map(item => item.id_imei);
      const placeholdersIds = idsImeis.map(() => "?").join(",");

      await connection.query(
        `
        UPDATE inventario_imei
        SET id_producto = ?, id_local = ?
        WHERE id_imei IN (${placeholdersIds})
        `,
        [productoDestinoInfo.id_producto, idLocalDestino, ...idsImeis]
      );

      cantidadTransferida = imeisDisponibles.length;
    } else {
      if (!Number.isInteger(cantidadSolicitada) || cantidadSolicitada <= 0) {
        await connection.rollback();
        transactionIniciada = false;

        return res.status(400).json({
          ok: false,
          mensaje: "Debes ingresar una cantidad válida para el traspaso"
        });
      }

      if (stockOrigen.stock_actual < cantidadSolicitada) {
        await connection.rollback();
        transactionIniciada = false;

        return res.status(400).json({
          ok: false,
          mensaje: "Stock insuficiente en el local origen"
        });
      }

      cantidadTransferida = cantidadSolicitada;
    }

    const stockOrigenNuevo = stockOrigen.stock_actual - cantidadTransferida;
    const stockDestinoNuevo = stockDestino.stock_actual + cantidadTransferida;

    await connection.query(
      `
      UPDATE inventario_stock
      SET stock_actual = ?, fecha_actualizacion = CURRENT_TIMESTAMP
      WHERE id_stock = ?
      `,
      [stockOrigenNuevo, stockOrigen.id_stock]
    );

    await connection.query(
      `
      UPDATE inventario_stock
      SET stock_actual = ?, fecha_actualizacion = CURRENT_TIMESTAMP
      WHERE id_stock = ?
      `,
      [stockDestinoNuevo, stockDestino.id_stock]
    );

    const referencia = crearReferenciaTraspaso({
      idLocalOrigen,
      idLocalDestino,
      idProductoOrigen,
      idProductoDestino: productoDestinoInfo.id_producto,
      cantidad: cantidadTransferida
    });

    await connection.query(
      `
      INSERT INTO movimientos_stock (
        id_producto,
        id_local,
        id_usuario,
        tipo,
        motivo,
        cantidad,
        stock_anterior,
        stock_nuevo,
        referencia
      ) VALUES (?, ?, ?, 'SALIDA', 'AJUSTE', ?, ?, ?, ?)
      `,
      [
        idProductoOrigen,
        idLocalOrigen,
        idUsuario,
        cantidadTransferida,
        stockOrigen.stock_actual,
        stockOrigenNuevo,
        referencia
      ]
    );

    await connection.query(
      `
      INSERT INTO movimientos_stock (
        id_producto,
        id_local,
        id_usuario,
        tipo,
        motivo,
        cantidad,
        stock_anterior,
        stock_nuevo,
        referencia
      ) VALUES (?, ?, ?, 'ENTRADA', 'AJUSTE', ?, ?, ?, ?)
      `,
      [
        productoDestinoInfo.id_producto,
        idLocalDestino,
        idUsuario,
        cantidadTransferida,
        stockDestino.stock_actual,
        stockDestinoNuevo,
        referencia
      ]
    );

    await connection.commit();
    transactionIniciada = false;

    res.json({
      ok: true,
      mensaje: `Traspasados ${cantidadTransferida} unidad(es) a ${localDestino.nombre_local}`,
      data: {
        cantidad_traspasada: cantidadTransferida,
        id_local_destino: idLocalDestino,
        nombre_local_destino: localDestino.nombre_local,
        id_producto_destino: productoDestinoInfo.id_producto,
        producto_destino_creado: productoDestinoInfo.creado,
        usa_imei: productoUsaImei
      }
    });
  } catch (error) {
    if (transactionIniciada) {
      await connection.rollback();
    }

    console.error("❌ traspasarProducto:", error);
    res.status(500).json({
      ok: false,
      mensaje: error.message || "Error al traspasar producto"
    });
  } finally {
    connection.release();
  }
};
