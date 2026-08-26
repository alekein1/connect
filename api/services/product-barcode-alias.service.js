async function ensureProductBarcodeAliasesTable(connection) {
  await connection.query(`
    CREATE TABLE IF NOT EXISTS producto_codigos_barras (
      id_codigo_barras BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      id_producto INT NOT NULL,
      codigo_barras VARCHAR(100) NOT NULL,
      creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id_codigo_barras),
      UNIQUE KEY uq_producto_codigo_barras (id_producto, codigo_barras),
      KEY idx_codigo_barras (codigo_barras),
      CONSTRAINT fk_producto_codigo_barras_producto
        FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  `);
}

async function ensureLocalBarcodeUniqueness(connection) {
  const [indexes] = await connection.query(`SHOW INDEX FROM productos`);
  const indexesByName = new Map();

  for (const index of indexes) {
    if (!indexesByName.has(index.Key_name)) {
      indexesByName.set(index.Key_name, []);
    }

    indexesByName.get(index.Key_name).push(index);
  }

  const hasLocalBarcodeIndex = Array.from(indexesByName.values()).some((indexColumns) => {
    const orderedColumns = indexColumns
      .slice()
      .sort((a, b) => Number(a.Seq_in_index) - Number(b.Seq_in_index))
      .map((column) => column.Column_name);

    return (
      Number(indexColumns[0].Non_unique) === 0 &&
      orderedColumns.length === 2 &&
      orderedColumns[0] === "id_local" &&
      orderedColumns[1] === "codigo_barras"
    );
  });

  if (hasLocalBarcodeIndex) {
    return;
  }

  const globalBarcodeIndex = Array.from(indexesByName.entries()).find(([, indexColumns]) => {
    const orderedColumns = indexColumns
      .slice()
      .sort((a, b) => Number(a.Seq_in_index) - Number(b.Seq_in_index))
      .map((column) => column.Column_name);

    return (
      Number(indexColumns[0].Non_unique) === 0 &&
      orderedColumns.length === 1 &&
      orderedColumns[0] === "codigo_barras"
    );
  });

  if (globalBarcodeIndex) {
    const indexName = globalBarcodeIndex[0].replace(/\`/g, "\`\`");
    await connection.query(
      `ALTER TABLE productos
       DROP INDEX \`${indexName}\`,
       ADD UNIQUE KEY uq_productos_local_codigo_barras (id_local, codigo_barras)`
    );
    return;
  }

  await connection.query(
    `ALTER TABLE productos
     ADD UNIQUE KEY uq_productos_local_codigo_barras (id_local, codigo_barras)`
  );
}

async function copiarCodigosBarrasProducto(connection, idProductoOrigen, idProductoDestino, codigoPrincipalOrigen) {
  await ensureProductBarcodeAliasesTable(connection);

  await connection.query(
    `
      INSERT IGNORE INTO producto_codigos_barras (id_producto, codigo_barras)
      SELECT ?, codigo_barras
      FROM producto_codigos_barras
      WHERE id_producto = ?
    `,
    [idProductoDestino, idProductoOrigen]
  );

  const codigo = String(codigoPrincipalOrigen || "").trim();
  if (codigo) {
    await connection.query(
      `
        INSERT IGNORE INTO producto_codigos_barras (id_producto, codigo_barras)
        VALUES (?, ?)
      `,
      [idProductoDestino, codigo]
    );
  }
}

async function migrarCodigosBarrasTraspasados(connection) {
  await ensureLocalBarcodeUniqueness(connection);
  await ensureProductBarcodeAliasesTable(connection);

  const [traspasos] = await connection.query(
    `
      SELECT referencia, MIN(fecha_movimiento) AS fecha_traspaso
      FROM movimientos_stock
      WHERE referencia LIKE 'TRASPASO:%'
      GROUP BY referencia
      ORDER BY fecha_traspaso ASC
    `
  );

  let actualizados = 0;
  let aliasAgregados = 0;
  let conflictos = 0;
  const transferencias = [];

  for (const traspaso of traspasos) {
    const coincidencia = String(traspaso.referencia || "").match(
      /^TRASPASO:(\d+)>(\d+):(\d+)>(\d+):/
    );

    if (!coincidencia) {
      continue;
    }

    const [, idLocalOrigen, idLocalDestino, idProductoOrigen, idProductoDestino] =
      coincidencia.map(Number);
    transferencias.push({
      idLocalOrigen,
      idLocalDestino,
      idProductoOrigen,
      idProductoDestino
    });
  }

  if (!transferencias.length) {
    return {
      total_traspasos: traspasos.length,
      codigos_actualizados: actualizados,
      codigos_alternativos: aliasAgregados,
      conflictos
    };
  }

  const [productos] = await connection.query(
    `
      SELECT id_producto, id_local, codigo_barras
      FROM productos
    `
  );
  const productosPorId = new Map(
    productos.map((producto) => [
      Number(producto.id_producto),
      {
        idLocal: Number(producto.id_local),
        codigo: String(producto.codigo_barras || "").trim()
      }
    ])
  );
  const codigosPorLocal = new Map();

  for (const producto of productos) {
    const idLocal = Number(producto.id_local);
    const codigo = String(producto.codigo_barras || "").trim();
    if (!codigosPorLocal.has(idLocal)) {
      codigosPorLocal.set(idLocal, new Map());
    }
    if (codigo) {
      codigosPorLocal.get(idLocal).set(codigo, Number(producto.id_producto));
    }
  }

  const aliases = new Map();
  const actualizaciones = new Map();

  for (const transferencia of transferencias) {
    const origen = productosPorId.get(transferencia.idProductoOrigen);
    const destino = productosPorId.get(transferencia.idProductoDestino);

    if (
      !origen ||
      !destino ||
      origen.idLocal !== transferencia.idLocalOrigen ||
      destino.idLocal !== transferencia.idLocalDestino ||
      !origen.codigo
    ) {
      continue;
    }

    aliases.set(
      `${transferencia.idProductoDestino}:${origen.codigo}`,
      [transferencia.idProductoDestino, origen.codigo]
    );

    if (destino.codigo === origen.codigo) {
      continue;
    }

    const codigosDestino = codigosPorLocal.get(destino.idLocal);
    const propietarioActual = codigosDestino.get(origen.codigo);
    if (propietarioActual && propietarioActual !== transferencia.idProductoDestino) {
      conflictos += 1;
      continue;
    }

    if (destino.codigo) {
      codigosDestino.delete(destino.codigo);
    }
    codigosDestino.set(origen.codigo, transferencia.idProductoDestino);
    destino.codigo = origen.codigo;
    actualizaciones.set(transferencia.idProductoDestino, origen.codigo);
  }

  const filasAlias = [...aliases.values()];
  for (let inicio = 0; inicio < filasAlias.length; inicio += 250) {
    const bloque = filasAlias.slice(inicio, inicio + 250);
    const placeholders = bloque.map(() => "(?, ?)").join(", ");
    await connection.query(
      `INSERT IGNORE INTO producto_codigos_barras (id_producto, codigo_barras)
       VALUES ${placeholders}`,
      bloque.flat()
    );
  }
  aliasAgregados = filasAlias.length;

  const cambios = [...actualizaciones.entries()];
  for (let inicio = 0; inicio < cambios.length; inicio += 150) {
    const bloque = cambios.slice(inicio, inicio + 150);
    const whenThen = bloque.map(() => "WHEN ? THEN ?").join(" ");
    const ids = bloque.map(([idProducto]) => idProducto);
    await connection.query(
      `
        UPDATE productos
        SET codigo_barras = CASE id_producto ${whenThen} END
        WHERE id_producto IN (?)
      `,
      [...bloque.flat(), ids]
    );
  }
  actualizados = cambios.length;

  return {
    total_traspasos: traspasos.length,
    codigos_actualizados: actualizados,
    codigos_alternativos: aliasAgregados,
    conflictos
  };
}

module.exports = {
  ensureProductBarcodeAliasesTable,
  ensureLocalBarcodeUniqueness,
  copiarCodigosBarrasProducto,
  migrarCodigosBarrasTraspasados
};
