const db = require("../db/db");

function round2(value) {
  return Math.round((Number(value || 0) + Number.EPSILON) * 100) / 100;
}

function fechaActualEcuador() {
  const parts = new Intl.DateTimeFormat("en", {
    timeZone: "America/Guayaquil",
    year: "numeric",
    month: "2-digit",
    day: "2-digit"
  }).formatToParts(new Date());

  const year = parts.find((part) => part.type === "year")?.value;
  const month = parts.find((part) => part.type === "month")?.value;
  const day = parts.find((part) => part.type === "day")?.value;

  return `${year}-${month}-${day}`;
}

function restarDias(fechaIso, dias) {
  const [year, month, day] = String(fechaIso).split("-").map(Number);
  const date = new Date(Date.UTC(year, month - 1, day));
  date.setUTCDate(date.getUTCDate() - Number(dias || 0));
  return date.toISOString().slice(0, 10);
}

function validarFechaIso(value) {
  return /^\d{4}-\d{2}-\d{2}$/.test(String(value || "").trim());
}

function construirFiltroVentas(idLocal, desde, hasta, alias = "v") {
  const where = [
    `${alias}.id_local = ?`,
    `${alias}.estado = 'PAGADA'`
  ];
  const params = [idLocal];

  if (desde) {
    where.push(`DATE(${alias}.fecha_venta) >= ?`);
    params.push(desde);
  }

  if (hasta) {
    where.push(`DATE(${alias}.fecha_venta) <= ?`);
    params.push(hasta);
  }

  return {
    sql: where.join(" AND "),
    params
  };
}

function construirFiltroGastos(idLocal, desde, hasta, alias = "g") {
  const where = [`${alias}.id_local = ?`];
  const params = [idLocal];

  if (desde) {
    where.push(`DATE(${alias}.fecha_gasto) >= ?`);
    params.push(desde);
  }

  if (hasta) {
    where.push(`DATE(${alias}.fecha_gasto) <= ?`);
    params.push(hasta);
  }

  return {
    sql: where.join(" AND "),
    params
  };
}

function construirPeriodo(req) {
  const desde = req.query.desde ? String(req.query.desde).trim() : null;
  const hasta = req.query.hasta ? String(req.query.hasta).trim() : null;

  if (desde && !validarFechaIso(desde)) {
    const error = new Error("La fecha 'desde' debe tener formato YYYY-MM-DD");
    error.statusCode = 400;
    throw error;
  }

  if (hasta && !validarFechaIso(hasta)) {
    const error = new Error("La fecha 'hasta' debe tener formato YYYY-MM-DD");
    error.statusCode = 400;
    throw error;
  }

  if (desde && hasta && desde > hasta) {
    const error = new Error("La fecha 'desde' no puede ser mayor que 'hasta'");
    error.statusCode = 400;
    throw error;
  }

  return { desde, hasta };
}

/* =====================================================
   📊 RESUMEN CONTABLE
   - Separa ventas, dinero realmente cobrado y cartera
   - En financiado (PayJoy / Happy / etc.) el dinero real es la entrada
===================================================== */
exports.resumenContable = async (req, res) => {
  try {
    const idLocal = req.user.id_local;
    const { desde, hasta } = construirPeriodo(req);
    const filtroVentas = construirFiltroVentas(idLocal, desde, hasta, "v");
    const filtroGastos = construirFiltroGastos(idLocal, desde, hasta, "g");

    const [[inventario]] = await db.query(`
      SELECT COALESCE(SUM(i.stock_actual * p.precio_compra), 0) AS inversion_actual
      FROM inventario_stock i
      INNER JOIN productos p
        ON p.id_producto = i.id_producto
      WHERE i.id_local = ?
    `, [idLocal]);

    const [[ventas]] = await db.query(`
      SELECT
        COUNT(*) AS total_ventas,
        COALESCE(SUM(v.subtotal), 0) AS subtotal_facturado,
        COALESCE(SUM(v.impuesto), 0) AS impuesto_total,
        COALESCE(SUM(v.total), 0) AS ventas_total,
        COALESCE(SUM(CASE WHEN v.tipo_venta = 'CONTADO' THEN v.total ELSE 0 END), 0) AS ventas_contado_total,
        COALESCE(SUM(CASE WHEN v.tipo_venta = 'FINANCIADO' THEN v.total ELSE 0 END), 0) AS ventas_financiadas_total,
        COALESCE(SUM(CASE WHEN v.tipo_venta = 'FINANCIADO' THEN v.entrada ELSE 0 END), 0) AS entradas_financiadas_total,
        COALESCE(SUM(CASE WHEN v.tipo_venta = 'FINANCIADO' THEN v.saldo ELSE 0 END), 0) AS saldo_financiado_total
      FROM ventas v
      WHERE ${filtroVentas.sql}
    `, filtroVentas.params);

    const [[detalle]] = await db.query(`
      SELECT
        COALESCE(SUM(d.cantidad), 0) AS unidades_vendidas,
        COALESCE(SUM(d.subtotal), 0) AS ventas_detalle_total,
        COALESCE(SUM(d.costo_total), 0) AS costo_vendido_total,
        COALESCE(SUM(d.ganancia), 0) AS ganancia_total
      FROM detalle_venta d
      INNER JOIN ventas v
        ON v.id_venta = d.id_venta
      WHERE ${filtroVentas.sql}
    `, filtroVentas.params);

    const [[cobros]] = await db.query(`
      SELECT COALESCE(SUM(vp.monto), 0) AS cobrado_total
      FROM ventas_pagos vp
      INNER JOIN ventas v
        ON v.id_venta = vp.id_venta
      WHERE ${filtroVentas.sql}
    `, filtroVentas.params);

    const [proveedores] = await db.query(`
      SELECT
        COALESCE(NULLIF(TRIM(v.proveedor_financiamiento), ''), 'SIN PROVEEDOR') AS proveedor_financiamiento,
        COUNT(*) AS operaciones,
        COALESCE(SUM(v.total), 0) AS total_colocado,
        COALESCE(SUM(v.entrada), 0) AS entrada_total,
        COALESCE(SUM(v.saldo), 0) AS saldo_pendiente
      FROM ventas v
      WHERE ${filtroVentas.sql}
        AND v.tipo_venta = 'FINANCIADO'
      GROUP BY COALESCE(NULLIF(TRIM(v.proveedor_financiamiento), ''), 'SIN PROVEEDOR')
      ORDER BY saldo_pendiente DESC, total_colocado DESC
    `, filtroVentas.params);

    const [formasCobro] = await db.query(`
      SELECT
        vp.forma_pago,
        COUNT(*) AS movimientos,
        COALESCE(SUM(vp.monto), 0) AS total_cobrado
      FROM ventas_pagos vp
      INNER JOIN ventas v
        ON v.id_venta = vp.id_venta
      WHERE ${filtroVentas.sql}
      GROUP BY vp.forma_pago
      ORDER BY total_cobrado DESC, movimientos DESC
    `, filtroVentas.params);

    const [[gastos]] = await db.query(`
      SELECT
        COUNT(*) AS total_registros_gastos,
        COALESCE(SUM(g.monto), 0) AS gastos_total
      FROM gastos g
      WHERE ${filtroGastos.sql}
    `, filtroGastos.params);

    const [gastosPorCategoria] = await db.query(`
      SELECT
        g.categoria,
        COUNT(*) AS total_registros,
        COALESCE(SUM(g.monto), 0) AS total_monto
      FROM gastos g
      WHERE ${filtroGastos.sql}
      GROUP BY g.categoria
      ORDER BY total_monto DESC, total_registros DESC
    `, filtroGastos.params);

    const [gastosPorMetodo] = await db.query(`
      SELECT
        g.metodo_pago,
        COUNT(*) AS total_registros,
        COALESCE(SUM(g.monto), 0) AS total_monto
      FROM gastos g
      WHERE ${filtroGastos.sql}
      GROUP BY g.metodo_pago
      ORDER BY total_monto DESC, total_registros DESC
    `, filtroGastos.params);

    const ventasTotal = Number(ventas.ventas_total || 0);
    const cobradoTotal = Number(cobros.cobrado_total || 0);
    const saldoFinanciadoTotal = Number(ventas.saldo_financiado_total || 0);
    const totalVentas = Number(ventas.total_ventas || 0);
    const gastosTotal = Number(gastos.gastos_total || 0);
    const gananciaTotal = Number(detalle.ganancia_total || 0);

    return res.json({
      ok: true,
      filtro: {
        desde,
        hasta
      },
      data: {
        inversion_actual: round2(inventario.inversion_actual),
        total_ventas: totalVentas,
        subtotal_facturado: round2(ventas.subtotal_facturado),
        impuesto_total: round2(ventas.impuesto_total),
        ventas_total: round2(ventasTotal),
        cobrado_total: round2(cobradoTotal),
        por_cobrar_total: round2(saldoFinanciadoTotal),
        total_registros_gastos: Number(gastos.total_registros_gastos || 0),
        gastos_total: round2(gastosTotal),
        ventas_contado_total: round2(ventas.ventas_contado_total),
        ventas_financiadas_total: round2(ventas.ventas_financiadas_total),
        entradas_financiadas_total: round2(ventas.entradas_financiadas_total),
        saldo_financiado_total: round2(saldoFinanciadoTotal),
        ventas_detalle_total: round2(detalle.ventas_detalle_total),
        costo_vendido_total: round2(detalle.costo_vendido_total),
        ganancia_total: round2(gananciaTotal),
        utilidad_neta: round2(gananciaTotal - gastosTotal),
        flujo_caja_neto: round2(cobradoTotal - gastosTotal),
        unidades_vendidas: Number(detalle.unidades_vendidas || 0),
        ticket_promedio: totalVentas > 0 ? round2(ventasTotal / totalVentas) : 0,
        porcentaje_cobrado: ventasTotal > 0 ? round2((cobradoTotal / ventasTotal) * 100) : 0
      },
      proveedores_financiamiento: proveedores.map((row) => ({
        proveedor_financiamiento: row.proveedor_financiamiento,
        operaciones: Number(row.operaciones || 0),
        total_colocado: round2(row.total_colocado),
        entrada_total: round2(row.entrada_total),
        saldo_pendiente: round2(row.saldo_pendiente)
      })),
      formas_cobro: formasCobro.map((row) => ({
        forma_pago: row.forma_pago,
        movimientos: Number(row.movimientos || 0),
        total_cobrado: round2(row.total_cobrado)
      })),
      gastos_por_categoria: gastosPorCategoria.map((row) => ({
        categoria: row.categoria,
        total_registros: Number(row.total_registros || 0),
        total_monto: round2(row.total_monto)
      })),
      gastos_por_metodo_pago: gastosPorMetodo.map((row) => ({
        metodo_pago: row.metodo_pago,
        total_registros: Number(row.total_registros || 0),
        total_monto: round2(row.total_monto)
      }))
    });

  } catch (error) {
    console.error("❌ resumenContable:", error);
    return res.status(error.statusCode || 500).json({
      ok: false,
      mensaje: error.message || "Error al obtener resumen contable"
    });
  }
};

/* =====================================================
   📈 DASHBOARD CONTABLE
   - Hoy
   - Ultimos 7 dias
   - Cartera por proveedor (PayJoy / Happy / etc.)
===================================================== */
exports.dashboardContable = async (req, res) => {
  try {
    const idLocal = req.user.id_local;
    const hoy = fechaActualEcuador();
    const desde7 = restarDias(hoy, 6);

    const [[inventario]] = await db.query(`
      SELECT COALESCE(SUM(i.stock_actual * p.precio_compra), 0) AS inversion_actual
      FROM inventario_stock i
      INNER JOIN productos p
        ON p.id_producto = i.id_producto
      WHERE i.id_local = ?
    `, [idLocal]);

    const [[ventasHoy]] = await db.query(`
      SELECT
        COUNT(*) AS operaciones_hoy,
        COALESCE(SUM(v.total), 0) AS ventas_hoy,
        COALESCE(SUM(CASE WHEN v.tipo_venta = 'FINANCIADO' THEN v.saldo ELSE 0 END), 0) AS por_cobrar_hoy
      FROM ventas v
      WHERE v.id_local = ?
        AND v.estado = 'PAGADA'
        AND DATE(v.fecha_venta) = ?
    `, [idLocal, hoy]);

    const [[cobrosHoy]] = await db.query(`
      SELECT COALESCE(SUM(vp.monto), 0) AS cobrado_hoy
      FROM ventas_pagos vp
      INNER JOIN ventas v
        ON v.id_venta = vp.id_venta
      WHERE v.id_local = ?
        AND v.estado = 'PAGADA'
        AND DATE(vp.fecha_pago) = ?
    `, [idLocal, hoy]);

    const [[utilidadHoy]] = await db.query(`
      SELECT COALESCE(SUM(d.ganancia), 0) AS utilidad_hoy
      FROM detalle_venta d
      INNER JOIN ventas v
        ON v.id_venta = d.id_venta
      WHERE v.id_local = ?
        AND v.estado = 'PAGADA'
        AND DATE(v.fecha_venta) = ?
    `, [idLocal, hoy]);

    const [[gastosHoy]] = await db.query(`
      SELECT
        COUNT(*) AS registros_gastos_hoy,
        COALESCE(SUM(g.monto), 0) AS gastos_hoy
      FROM gastos g
      WHERE g.id_local = ?
        AND DATE(g.fecha_gasto) = ?
    `, [idLocal, hoy]);

    const [[carteraTotal]] = await db.query(`
      SELECT
        COALESCE(SUM(CASE WHEN v.tipo_venta = 'FINANCIADO' THEN v.saldo ELSE 0 END), 0) AS por_cobrar_total,
        COALESCE(SUM(v.total), 0) AS ventas_historicas,
        COALESCE(SUM(CASE WHEN v.tipo_venta = 'FINANCIADO' THEN v.total ELSE 0 END), 0) AS ventas_financiadas_historicas
      FROM ventas v
      WHERE v.id_local = ?
        AND v.estado = 'PAGADA'
    `, [idLocal]);

    const [ventasPeriodo] = await db.query(`
      SELECT
        DATE(v.fecha_venta) AS fecha,
        COUNT(*) AS operaciones,
        COALESCE(SUM(v.total), 0) AS ventas_total,
        COALESCE(SUM(CASE WHEN v.tipo_venta = 'CONTADO' THEN v.total ELSE v.entrada END), 0) AS cobrado_estimado,
        COALESCE(SUM(CASE WHEN v.tipo_venta = 'FINANCIADO' THEN v.saldo ELSE 0 END), 0) AS cartera_generada
      FROM ventas v
      WHERE v.id_local = ?
        AND v.estado = 'PAGADA'
        AND DATE(v.fecha_venta) BETWEEN ? AND ?
      GROUP BY DATE(v.fecha_venta)
      ORDER BY fecha ASC
    `, [idLocal, desde7, hoy]);

    const [proveedores] = await db.query(`
      SELECT
        COALESCE(NULLIF(TRIM(v.proveedor_financiamiento), ''), 'SIN PROVEEDOR') AS proveedor_financiamiento,
        COUNT(*) AS operaciones,
        COALESCE(SUM(v.total), 0) AS total_colocado,
        COALESCE(SUM(v.entrada), 0) AS entrada_total,
        COALESCE(SUM(v.saldo), 0) AS saldo_pendiente
      FROM ventas v
      WHERE v.id_local = ?
        AND v.estado = 'PAGADA'
        AND v.tipo_venta = 'FINANCIADO'
      GROUP BY COALESCE(NULLIF(TRIM(v.proveedor_financiamiento), ''), 'SIN PROVEEDOR')
      ORDER BY saldo_pendiente DESC, total_colocado DESC
    `, [idLocal]);

    const [gastosPeriodo] = await db.query(`
      SELECT
        DATE(g.fecha_gasto) AS fecha,
        COUNT(*) AS total_registros,
        COALESCE(SUM(g.monto), 0) AS total_monto
      FROM gastos g
      WHERE g.id_local = ?
        AND DATE(g.fecha_gasto) BETWEEN ? AND ?
      GROUP BY DATE(g.fecha_gasto)
      ORDER BY fecha ASC
    `, [idLocal, desde7, hoy]);

    return res.json({
      ok: true,
      data: {
        fecha_hoy: hoy,
        desde_7_dias: desde7,
        inversion_actual: round2(inventario.inversion_actual),
        operaciones_hoy: Number(ventasHoy.operaciones_hoy || 0),
        ventas_hoy: round2(ventasHoy.ventas_hoy),
        cobrado_hoy: round2(cobrosHoy.cobrado_hoy),
        por_cobrar_hoy: round2(ventasHoy.por_cobrar_hoy),
        registros_gastos_hoy: Number(gastosHoy.registros_gastos_hoy || 0),
        gastos_hoy: round2(gastosHoy.gastos_hoy),
        utilidad_hoy: round2(utilidadHoy.utilidad_hoy),
        utilidad_neta_hoy: round2(Number(utilidadHoy.utilidad_hoy || 0) - Number(gastosHoy.gastos_hoy || 0)),
        por_cobrar_total: round2(carteraTotal.por_cobrar_total),
        ventas_historicas: round2(carteraTotal.ventas_historicas),
        ventas_financiadas_historicas: round2(carteraTotal.ventas_financiadas_historicas),
        ventas_ultimos_7_dias: ventasPeriodo.map((row) => ({
          fecha: row.fecha,
          operaciones: Number(row.operaciones || 0),
          ventas_total: round2(row.ventas_total),
          cobrado_estimado: round2(row.cobrado_estimado),
          cartera_generada: round2(row.cartera_generada)
        })),
        gastos_ultimos_7_dias: gastosPeriodo.map((row) => ({
          fecha: row.fecha,
          total_registros: Number(row.total_registros || 0),
          total_monto: round2(row.total_monto)
        })),
        proveedores_financiamiento: proveedores.map((row) => ({
          proveedor_financiamiento: row.proveedor_financiamiento,
          operaciones: Number(row.operaciones || 0),
          total_colocado: round2(row.total_colocado),
          entrada_total: round2(row.entrada_total),
          saldo_pendiente: round2(row.saldo_pendiente)
        }))
      }
    });

  } catch (error) {
    console.error("❌ dashboardContable:", error);
    return res.status(500).json({
      ok: false,
      mensaje: error.message || "Error al obtener dashboard contable"
    });
  }
};
