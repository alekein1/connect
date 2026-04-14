const fs = require("fs");
const path = require("path");
const PDFDocument = require("pdfkit");
const db = require("../db/db");
const UPLOADS_ROOT = path.resolve(__dirname, "../uploads");

function fechaActualEcuador() {
  return new Intl.DateTimeFormat("en-CA", {
    timeZone: "America/Guayaquil",
    year: "numeric",
    month: "2-digit",
    day: "2-digit"
  }).format(new Date());
}

function inicioMesEcuador() {
  const hoy = fechaActualEcuador();
  return `${hoy.slice(0, 8)}01`;
}

function isValidDate(value) {
  return /^\d{4}-\d{2}-\d{2}$/.test(String(value || "").trim());
}

function toNumber(value) {
  const parsed = Number(value || 0);
  return Number.isFinite(parsed) ? parsed : 0;
}

function round2(value) {
  return Number(toNumber(value).toFixed(2));
}

function safeText(value, fallback = "N/A") {
  if (value === null || value === undefined) return fallback;
  const text = String(value).trim();
  return text || fallback;
}

function formatMoney(value) {
  return round2(value).toFixed(2);
}

function formatDateTimeEc(value = new Date()) {
  const date = value instanceof Date ? value : new Date(value);

  return new Intl.DateTimeFormat("es-EC", {
    timeZone: "America/Guayaquil",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false
  }).format(date);
}

function resolveLogoPath() {
  const candidates = [
    path.resolve(__dirname, "../../frontend-connect/public/images/connect.png"),
    path.resolve(__dirname, "../uploads/logo.png")
  ];

  return candidates.find((candidate) => fs.existsSync(candidate)) || null;
}

function toUploadUrl(filePath) {
  if (!filePath) return null;

  const absolutePath = path.resolve(filePath);

  if (!absolutePath.startsWith(UPLOADS_ROOT)) {
    return null;
  }

  const relativePath = path.relative(UPLOADS_ROOT, absolutePath).split(path.sep).join("/");
  return `/api/uploads/${relativePath}`;
}

function normalizeBooleanFlag(value) {
  if (value === undefined || value === null) return false;
  return ["1", "true", "si", "sí", "yes"].includes(String(value).trim().toLowerCase());
}

function getEstadoStock(item) {
  const stockActual = toNumber(item.stock_actual);
  const stockMinimo = toNumber(item.stock_minimo);

  if (stockActual <= 0) {
    return {
      label: "Sin stock",
      color: "#b91c1c",
      fill: "#fee2e2"
    };
  }

  if (stockMinimo > 0 && stockActual <= stockMinimo) {
    return {
      label: "Stock bajo",
      color: "#b45309",
      fill: "#fef3c7"
    };
  }

  return {
    label: "Normal",
    color: "#166534",
    fill: "#dcfce7"
  };
}

async function getStockReportData({
  id_local = 0,
  id_categoria = 0,
  id_subcategoria = 0,
  id_producto = 0,
  buscar = "",
  solo_stock_bajo = false,
  solo_sin_existencia = false
}) {
  const params = [];
  let where = ` WHERE l.activo = 1 `;

  if (id_local) {
    where += ` AND l.id_local = ? `;
    params.push(id_local);
  }

  if (id_categoria) {
    where += ` AND c.id_categoria = ? `;
    params.push(id_categoria);
  }

  if (id_subcategoria) {
    where += ` AND s.id_subcategoria = ? `;
    params.push(id_subcategoria);
  }

  if (id_producto) {
    where += ` AND p.id_producto = ? `;
    params.push(id_producto);
  }

  if (buscar) {
    where += ` AND (
      p.nombre_producto LIKE ?
      OR p.codigo_barras LIKE ?
      OR p.sku LIKE ?
    ) `;
    params.push(`%${buscar}%`, `%${buscar}%`, `%${buscar}%`);
  }

  if (solo_stock_bajo) {
    where += ` AND p.activo = 1 AND COALESCE(p.stock_minimo, 0) > 0 AND COALESCE(i.stock_actual, 0) <= COALESCE(p.stock_minimo, 0) `;
  }

  if (solo_sin_existencia) {
    where += ` AND COALESCE(i.stock_actual, 0) <= 0 `;
  }

  const [items] = await db.query(`
    SELECT
      l.id_local,
      l.nombre_local,
      l.direccion AS local_direccion,
      l.telefono AS local_telefono,
      c.nombre_categoria,
      s.nombre_subcategoria,
      p.id_producto,
      p.nombre_producto,
      p.marca,
      p.codigo_barras,
      p.sku,
      p.precio_compra,
      p.precio_unitario,
      p.stock_minimo,
      p.activo AS producto_activo,
      COALESCE(i.stock_actual, 0) AS stock_actual,
      ROUND(COALESCE(i.stock_actual, 0) * COALESCE(p.precio_compra, 0), 2) AS valor_invertido
    FROM productos p
    INNER JOIN locales l
      ON l.id_local = p.id_local
    LEFT JOIN inventario_stock i
      ON i.id_producto = p.id_producto
     AND i.id_local = p.id_local
    LEFT JOIN subcategorias s
      ON s.id_subcategoria = p.id_subcategoria
    LEFT JOIN categorias c
      ON c.id_categoria = s.id_categoria
    ${where}
    ORDER BY l.nombre_local ASC, c.nombre_categoria ASC, s.nombre_subcategoria ASC, p.nombre_producto ASC
  `, params);

  const normalizedItems = items.map((item) => ({
    ...item,
    id_local: Number(item.id_local),
    stock_actual: toNumber(item.stock_actual),
    stock_minimo: toNumber(item.stock_minimo),
    precio_compra: round2(item.precio_compra),
    precio_unitario: round2(item.precio_unitario),
    valor_invertido: round2(item.valor_invertido),
    producto_activo: Number(item.producto_activo || 0) === 1,
    estado_stock: getEstadoStock(item)
  }));

  const localesMap = new Map();

  normalizedItems.forEach((item) => {
    if (!localesMap.has(item.id_local)) {
      localesMap.set(item.id_local, {
        id_local: item.id_local,
        nombre_local: item.nombre_local,
        local_direccion: item.local_direccion,
        local_telefono: item.local_telefono,
        productos: 0,
        stock_unidades: 0,
        stock_bajo: 0,
        sin_stock: 0,
        valor_invertido: 0
      });
    }

    const local = localesMap.get(item.id_local);
    local.productos += 1;
    local.stock_unidades += item.stock_actual;
    local.valor_invertido += item.valor_invertido;

    if (item.estado_stock.label === "Stock bajo") {
      local.stock_bajo += 1;
    }

    if (item.estado_stock.label === "Sin stock") {
      local.sin_stock += 1;
    }
  });

  const locales = [...localesMap.values()].map((local) => ({
    ...local,
    valor_invertido: round2(local.valor_invertido)
  }));

  const resumen = normalizedItems.reduce((acc, item) => {
    acc.productos_total += 1;
    acc.stock_unidades_total += item.stock_actual;
    acc.valor_invertido_total += item.valor_invertido;

    if (item.estado_stock.label === "Stock bajo") {
      acc.stock_bajo_total += 1;
    }

    if (item.estado_stock.label === "Sin stock") {
      acc.sin_stock_total += 1;
    }

    return acc;
  }, {
    locales_total: locales.length,
    productos_total: 0,
    stock_unidades_total: 0,
    stock_bajo_total: 0,
    sin_stock_total: 0,
    valor_invertido_total: 0
  });

  resumen.valor_invertido_total = round2(resumen.valor_invertido_total);

  return {
    filtros: {
      id_local: id_local || null,
      id_categoria: id_categoria || null,
      id_subcategoria: id_subcategoria || null,
      id_producto: id_producto || null,
      buscar: buscar || null,
      solo_stock_bajo,
      solo_sin_existencia
    },
    resumen,
    locales,
    items: normalizedItems
  };
}

function renderStockReportPdf(doc, reportData) {
  const logoPath = resolveLogoPath();
  const generatedAt = formatDateTimeEc(new Date());
  const { resumen, locales, items, filtros } = reportData;

  const colors = {
    brand: "#123c8d",
    brandSoft: "#eef4ff",
    text: "#0f172a",
    muted: "#64748b",
    border: "#cbd5e1",
    panel: "#f8fafc",
    white: "#ffffff"
  };

  const drawCard = ({ x, y, width, height, label, value, hint }) => {
    doc
      .roundedRect(x, y, width, height, 10)
      .fillAndStroke(colors.white, colors.border);

    doc.font("Helvetica-Bold").fontSize(9).fillColor(colors.muted).text(label, x + 12, y + 12, {
      width: width - 24
    });

    doc.font("Helvetica-Bold").fontSize(18).fillColor(colors.text).text(String(value), x + 12, y + 28, {
      width: width - 24
    });

    doc.font("Helvetica").fontSize(8.5).fillColor(colors.muted).text(hint, x + 12, y + 52, {
      width: width - 24
    });
  };

  const pageWidth = doc.page.width - doc.page.margins.left - doc.page.margins.right;
  const startX = doc.page.margins.left;
  let y = doc.page.margins.top;

  doc
    .roundedRect(startX, y, pageWidth, 82, 14)
    .fillAndStroke(colors.panel, colors.border);

  if (logoPath) {
    doc.image(logoPath, startX + 16, y + 15, { fit: [90, 40], align: "left" });
  }

  doc.font("Helvetica-Bold").fontSize(22).fillColor(colors.brand).text("REPORTE PROFESIONAL DE STOCK", startX + 120, y + 16);
  doc.font("Helvetica").fontSize(10).fillColor(colors.muted).text("Consolidado ejecutivo de inventario por local", startX + 120, y + 43);
  doc.text(`Generado: ${generatedAt}`, startX + 120, y + 58);

  y += 100;

  const filterText = [
    filtros.id_local ? `Local filtrado: ${filtros.id_local}` : "Locales: todos",
    filtros.solo_stock_bajo ? "Solo stock bajo: SI" : "Solo stock bajo: NO",
    filtros.solo_sin_existencia ? "Solo sin existencia: SI" : "Solo sin existencia: NO"
  ].join("  |  ");

  doc.font("Helvetica-Bold").fontSize(10).fillColor(colors.text).text("Resumen ejecutivo", startX, y);
  doc.font("Helvetica").fontSize(9).fillColor(colors.muted).text(filterText, startX, y + 14, { width: pageWidth });

  y += 38;

  const cardGap = 12;
  const cardWidth = (pageWidth - (cardGap * 2)) / 3;
  const cardHeight = 76;

  drawCard({
    x: startX,
    y,
    width: cardWidth,
    height: cardHeight,
    label: "Locales incluidos",
    value: resumen.locales_total,
    hint: `${resumen.productos_total} productos en el reporte`
  });

  drawCard({
    x: startX + cardWidth + cardGap,
    y,
    width: cardWidth,
    height: cardHeight,
    label: "Stock total",
    value: resumen.stock_unidades_total,
    hint: `${resumen.stock_bajo_total} productos en stock bajo`
  });

  drawCard({
    x: startX + ((cardWidth + cardGap) * 2),
    y,
    width: cardWidth,
    height: cardHeight,
    label: "Valor inventario",
    value: `$${formatMoney(resumen.valor_invertido_total)}`,
    hint: `${resumen.sin_stock_total} productos sin stock`
  });

  y += cardHeight + 22;

  doc.font("Helvetica-Bold").fontSize(10).fillColor(colors.text).text("Resumen por local", startX, y);
  y += 18;

  if (!locales.length) {
    doc.font("Helvetica").fontSize(10).fillColor(colors.muted).text("No hay datos de stock para los filtros consultados.", startX, y);
    y += 24;
  } else {
    locales.forEach((local) => {
      if (y > doc.page.height - 160) {
        doc.addPage();
        y = doc.page.margins.top;
      }

      doc.roundedRect(startX, y, pageWidth, 44, 10).fillAndStroke(colors.white, colors.border);
      doc.font("Helvetica-Bold").fontSize(10).fillColor(colors.text).text(local.nombre_local, startX + 12, y + 10);
      doc.font("Helvetica").fontSize(8.5).fillColor(colors.muted).text(
        `Productos: ${local.productos}  |  Unidades: ${local.stock_unidades}  |  Stock bajo: ${local.stock_bajo}  |  Sin stock: ${local.sin_stock}  |  Valor: $${formatMoney(local.valor_invertido)}`,
        startX + 12,
        y + 24,
        { width: pageWidth - 24 }
      );
      y += 54;
    });
  }

  if (y > doc.page.height - 220) {
    doc.addPage();
    y = doc.page.margins.top;
  }

  y += 8;
  doc.font("Helvetica-Bold").fontSize(10).fillColor(colors.text).text("Detalle de stock", startX, y);
  y += 18;

  const columns = [
    { key: "local", label: "LOCAL", width: 90, align: "left" },
    { key: "producto", label: "PRODUCTO", width: 180, align: "left" },
    { key: "categoria", label: "CATEGORIA", width: 92, align: "left" },
    { key: "stock", label: "STOCK", width: 48, align: "center" },
    { key: "minimo", label: "MIN", width: 42, align: "center" },
    { key: "estado", label: "ESTADO", width: 74, align: "center" },
    { key: "punit", label: "P. UNIT", width: 62, align: "right" },
    { key: "valor", label: "VALOR", width: 70, align: "right" }
  ];

  const drawHeader = () => {
    let x = startX;

    columns.forEach((col) => {
      doc.rect(x, y, col.width, 20).fillAndStroke(colors.brand, colors.brand);
      doc.font("Helvetica-Bold").fontSize(7.6).fillColor(colors.white).text(col.label, x + 4, y + 6, {
        width: col.width - 8,
        align: col.align
      });
      x += col.width;
    });

    y += 20;
  };

  drawHeader();

  items.forEach((item) => {
    if (y > doc.page.height - 45) {
      doc.addPage({ size: "A4", layout: "landscape", margin: 26 });
      y = doc.page.margins.top;
      drawHeader();
    }

    const row = {
      local: safeText(item.nombre_local),
      producto: safeText(item.nombre_producto),
      categoria: safeText(item.nombre_categoria),
      stock: item.stock_actual,
      minimo: item.stock_minimo,
      estado: item.estado_stock.label,
      punit: `$${formatMoney(item.precio_unitario)}`,
      valor: `$${formatMoney(item.valor_invertido)}`
    };

    let x = startX;

    columns.forEach((col) => {
      doc.rect(x, y, col.width, 22).fillAndStroke(colors.white, colors.border);

      if (col.key === "estado") {
        doc.roundedRect(x + 6, y + 4, col.width - 12, 14, 7).fill(item.estado_stock.fill);
        doc.font("Helvetica-Bold").fontSize(7.3).fillColor(item.estado_stock.color).text(row[col.key], x + 8, y + 8, {
          width: col.width - 16,
          align: "center"
        });
      } else {
        doc.font(col.key === "producto" ? "Helvetica-Bold" : "Helvetica").fontSize(7.6).fillColor(colors.text).text(String(row[col.key]), x + 4, y + 7, {
          width: col.width - 8,
          align: col.align,
          ellipsis: true
        });
      }

      x += col.width;
    });

    y += 22;
  });

  y += 14;
  doc.font("Helvetica").fontSize(8).fillColor(colors.muted).text(
    "Reporte generado automáticamente por CONNECT. Revisa especialmente los productos marcados como Stock bajo o Sin stock para priorizar reposición.",
    startX,
    y,
    { width: pageWidth }
  );
}

exports.reporteGeneralSuperadmin = async (req, res) => {
  try {
    const fecha_desde = String(req.query.fecha_desde || inicioMesEcuador()).trim();
    const fecha_hasta = String(req.query.fecha_hasta || fechaActualEcuador()).trim();
    const id_local = Number(req.query.id_local || 0);

    if (!isValidDate(fecha_desde) || !isValidDate(fecha_hasta)) {
      return res.status(400).json({
        ok: false,
        mensaje: "Las fechas deben tener formato YYYY-MM-DD"
      });
    }

    if (fecha_desde > fecha_hasta) {
      return res.status(400).json({
        ok: false,
        mensaje: "fecha_desde no puede ser mayor que fecha_hasta"
      });
    }

    const whereLocales = id_local ? `WHERE l.id_local = ?` : ``;
    const paramsLocales = [fecha_desde, fecha_hasta, fecha_desde, fecha_hasta];

    if (id_local) {
      paramsLocales.push(id_local);
    }

    const [rows] = await db.query(`
      SELECT
        l.id_local,
        l.nombre_local,
        l.direccion,
        l.telefono,
        l.activo,

        COALESCE(admins.total_admins, 0) AS total_admins,
        COALESCE(admins.admins_activos, 0) AS admins_activos,

        COALESCE(ventas.tickets_total, 0) AS tickets_total,
        COALESCE(ventas.ventas_total, 0) AS ventas_total,
        ventas.ultima_venta,

        COALESCE(productos.total_productos, 0) AS total_productos,
        COALESCE(productos.productos_activos, 0) AS productos_activos,

        COALESCE(inventario.stock_unidades_total, 0) AS stock_unidades_total,
        COALESCE(inventario.stock_bajo_total, 0) AS stock_bajo_total,
        COALESCE(inventario.stock_sin_existencia, 0) AS stock_sin_existencia,

        COALESCE(cajas_periodo.total_cajas_periodo, 0) AS total_cajas_periodo,
        cajas_periodo.ultima_apertura,

        COALESCE(cajas_actuales.cajas_abiertas_actuales, 0) AS cajas_abiertas_actuales,
        COALESCE(cajas_actuales.caja_actual_total, 0) AS caja_actual_total
      FROM locales l
      LEFT JOIN (
        SELECT
          id_local,
          COUNT(*) AS total_admins,
          SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS admins_activos
        FROM usuarios
        WHERE rol = 'ADMIN'
        GROUP BY id_local
      ) admins
        ON admins.id_local = l.id_local
      LEFT JOIN (
        SELECT
          id_local,
          COUNT(*) AS tickets_total,
          COALESCE(SUM(total), 0) AS ventas_total,
          MAX(fecha_venta) AS ultima_venta
        FROM ventas
        WHERE estado = 'PAGADA'
          AND DATE(fecha_venta) BETWEEN ? AND ?
        GROUP BY id_local
      ) ventas
        ON ventas.id_local = l.id_local
      LEFT JOIN (
        SELECT
          id_local,
          COUNT(*) AS total_productos,
          SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS productos_activos
        FROM productos
        GROUP BY id_local
      ) productos
        ON productos.id_local = l.id_local
      LEFT JOIN (
        SELECT
          p.id_local,
          COALESCE(SUM(COALESCE(i.stock_actual, 0)), 0) AS stock_unidades_total,
          SUM(
            CASE
              WHEN p.activo = 1
               AND COALESCE(p.stock_minimo, 0) > 0
               AND COALESCE(i.stock_actual, 0) <= COALESCE(p.stock_minimo, 0)
              THEN 1 ELSE 0
            END
          ) AS stock_bajo_total,
          SUM(
            CASE
              WHEN p.activo = 1
               AND COALESCE(i.stock_actual, 0) <= 0
              THEN 1 ELSE 0
            END
          ) AS stock_sin_existencia
        FROM productos p
        LEFT JOIN inventario_stock i
          ON i.id_producto = p.id_producto
         AND i.id_local = p.id_local
        GROUP BY p.id_local
      ) inventario
        ON inventario.id_local = l.id_local
      LEFT JOIN (
        SELECT
          id_local,
          COUNT(*) AS total_cajas_periodo,
          MAX(fecha_apertura) AS ultima_apertura
        FROM caja_apertura
        WHERE DATE(fecha_apertura) BETWEEN ? AND ?
        GROUP BY id_local
      ) cajas_periodo
        ON cajas_periodo.id_local = l.id_local
      LEFT JOIN (
        SELECT
          c.id_local,
          COUNT(*) AS cajas_abiertas_actuales,
          COALESCE(SUM(c.monto_apertura + COALESCE(v.total_ventas, 0)), 0) AS caja_actual_total
        FROM caja_apertura c
        LEFT JOIN (
          SELECT
            id_caja,
            id_local,
            COALESCE(SUM(total), 0) AS total_ventas
          FROM ventas
          WHERE estado = 'PAGADA'
          GROUP BY id_caja, id_local
        ) v
          ON v.id_caja = c.id_caja
         AND v.id_local = c.id_local
        WHERE c.estado = 'ABIERTA'
        GROUP BY c.id_local
      ) cajas_actuales
        ON cajas_actuales.id_local = l.id_local
      ${whereLocales}
      ORDER BY COALESCE(ventas.ventas_total, 0) DESC, l.nombre_local ASC
    `, paramsLocales);

    const paramsSerie = [fecha_desde, fecha_hasta];
    const localFilterSerie = id_local ? ` AND id_local = ? ` : ``;

    if (id_local) {
      paramsSerie.push(id_local);
    }

    const [serieVentas] = await db.query(`
      SELECT
        DATE(fecha_venta) AS fecha,
        id_local,
        COUNT(*) AS tickets_total,
        COALESCE(SUM(total), 0) AS ventas_total
      FROM ventas
      WHERE estado = 'PAGADA'
        AND DATE(fecha_venta) BETWEEN ? AND ?
        ${localFilterSerie}
      GROUP BY DATE(fecha_venta), id_local
      ORDER BY fecha ASC, id_local ASC
    `, paramsSerie);

    const locales = rows.map((row) => {
      const ventas_total = round2(row.ventas_total);
      const tickets_total = toNumber(row.tickets_total);

      return {
        id_local: row.id_local,
        nombre_local: row.nombre_local,
        direccion: row.direccion,
        telefono: row.telefono,
        activo: Number(row.activo || 0) === 1,
        total_admins: toNumber(row.total_admins),
        admins_activos: toNumber(row.admins_activos),
        tickets_total,
        ventas_total,
        ticket_promedio: tickets_total > 0 ? round2(ventas_total / tickets_total) : 0,
        ultima_venta: row.ultima_venta,
        total_productos: toNumber(row.total_productos),
        productos_activos: toNumber(row.productos_activos),
        stock_unidades_total: toNumber(row.stock_unidades_total),
        stock_bajo_total: toNumber(row.stock_bajo_total),
        stock_sin_existencia: toNumber(row.stock_sin_existencia),
        total_cajas_periodo: toNumber(row.total_cajas_periodo),
        cajas_abiertas_actuales: toNumber(row.cajas_abiertas_actuales),
        caja_actual_total: round2(row.caja_actual_total),
        ultima_apertura: row.ultima_apertura
      };
    });

    const resumen_global = locales.reduce((acc, local) => {
      acc.locales_total += 1;
      acc.locales_activos += local.activo ? 1 : 0;
      acc.admins_activos += local.admins_activos;
      acc.ventas_total += local.ventas_total;
      acc.tickets_total += local.tickets_total;
      acc.productos_total += local.total_productos;
      acc.stock_unidades_total += local.stock_unidades_total;
      acc.stock_bajo_total += local.stock_bajo_total;
      acc.stock_sin_existencia += local.stock_sin_existencia;
      acc.cajas_periodo += local.total_cajas_periodo;
      acc.cajas_abiertas_actuales += local.cajas_abiertas_actuales;
      acc.caja_actual_total += local.caja_actual_total;
      return acc;
    }, {
      locales_total: 0,
      locales_activos: 0,
      admins_activos: 0,
      ventas_total: 0,
      tickets_total: 0,
      productos_total: 0,
      stock_unidades_total: 0,
      stock_bajo_total: 0,
      stock_sin_existencia: 0,
      cajas_periodo: 0,
      cajas_abiertas_actuales: 0,
      caja_actual_total: 0
    });

    resumen_global.ventas_total = round2(resumen_global.ventas_total);
    resumen_global.ticket_promedio = resumen_global.tickets_total > 0
      ? round2(resumen_global.ventas_total / resumen_global.tickets_total)
      : 0;
    resumen_global.caja_actual_total = round2(resumen_global.caja_actual_total);

    const ranking_ventas = [...locales]
      .sort((a, b) => b.ventas_total - a.ventas_total)
      .map((local, index) => ({
        posicion: index + 1,
        id_local: local.id_local,
        nombre_local: local.nombre_local,
        valor: local.ventas_total
      }));

    const ranking_stock_bajo = [...locales]
      .sort((a, b) => b.stock_bajo_total - a.stock_bajo_total)
      .filter((local) => local.stock_bajo_total > 0)
      .map((local, index) => ({
        posicion: index + 1,
        id_local: local.id_local,
        nombre_local: local.nombre_local,
        valor: local.stock_bajo_total
      }));

    const alertas = [
      ...locales
        .filter((local) => local.admins_activos === 0)
        .map((local) => ({
          tipo: "SIN_ADMIN",
          nivel: "alto",
          id_local: local.id_local,
          nombre_local: local.nombre_local,
          mensaje: "No tiene administradores activos asignados"
        })),
      ...locales
        .filter((local) => local.ventas_total === 0)
        .map((local) => ({
          tipo: "SIN_VENTAS",
          nivel: "medio",
          id_local: local.id_local,
          nombre_local: local.nombre_local,
          mensaje: "No registra ventas pagadas en el rango consultado"
        })),
      ...locales
        .filter((local) => local.stock_bajo_total > 0)
        .sort((a, b) => b.stock_bajo_total - a.stock_bajo_total)
        .slice(0, 6)
        .map((local) => ({
          tipo: "STOCK_BAJO",
          nivel: local.stock_bajo_total >= 10 ? "alto" : "medio",
          id_local: local.id_local,
          nombre_local: local.nombre_local,
          mensaje: `${local.stock_bajo_total} productos con stock bajo`
        }))
    ];

    res.json({
      ok: true,
      data: {
        filtros: {
          fecha_desde,
          fecha_hasta,
          id_local: id_local || null
        },
        resumen_global,
        locales,
        estadisticas: {
          ranking_ventas,
          ranking_stock_bajo,
          serie_ventas: serieVentas.map((item) => ({
            fecha: item.fecha,
            id_local: Number(item.id_local),
            tickets_total: toNumber(item.tickets_total),
            ventas_total: round2(item.ventas_total)
          })),
          locales_sin_ventas: locales
            .filter((local) => local.ventas_total === 0)
            .map((local) => ({
              id_local: local.id_local,
              nombre_local: local.nombre_local
            })),
          locales_sin_admin: locales
            .filter((local) => local.admins_activos === 0)
            .map((local) => ({
              id_local: local.id_local,
              nombre_local: local.nombre_local
            })),
          alertas
        }
      }
    });
  } catch (error) {
    console.error("❌ reporteGeneralSuperadmin:", error);
    res.status(500).json({
      ok: false,
      mensaje: error.message || "Error al obtener reportes del super admin"
    });
  }
};

exports.reporteRidesSuperadmin = async (req, res) => {
  try {
    const id_local = Number(req.query.id_local || 0);
    const ambiente = String(req.query.ambiente || "").trim().toUpperCase();
    const estado = String(req.query.estado || "").trim().toUpperCase();
    const buscar = String(req.query.buscar || "").trim();
    const fecha_desde = isValidDate(req.query.fecha_desde) ? req.query.fecha_desde : null;
    const fecha_hasta = isValidDate(req.query.fecha_hasta) ? req.query.fecha_hasta : null;

    const params = [];
    let where = `
      WHERE sd.tipo_comprobante = 'FACTURA'
        AND sd.ride_path IS NOT NULL
    `;

    if (id_local) {
      where += ` AND sd.id_local = ? `;
      params.push(id_local);
    }

    if (ambiente) {
      where += ` AND sd.ambiente = ? `;
      params.push(ambiente);
    }

    if (estado) {
      where += ` AND sd.estado = ? `;
      params.push(estado);
    }

    if (fecha_desde) {
      where += ` AND DATE(COALESCE(sd.fecha_autorizacion, v.fecha_venta)) >= ? `;
      params.push(fecha_desde);
    }

    if (fecha_hasta) {
      where += ` AND DATE(COALESCE(sd.fecha_autorizacion, v.fecha_venta)) <= ? `;
      params.push(fecha_hasta);
    }

    if (buscar) {
      const like = `%${buscar}%`;
      where += `
        AND (
          CAST(sd.id_documento_sri AS CHAR) = ?
          OR CAST(v.id_venta AS CHAR) = ?
          OR v.numero_comprobante LIKE ?
          OR sd.numero_autorizacion LIKE ?
          OR sd.clave_acceso LIKE ?
          OR COALESCE(c.nombres, '') LIKE ?
          OR COALESCE(c.cedula, '') LIKE ?
          OR COALESCE(l.nombre_local, '') LIKE ?
        )
      `;
      params.push(buscar, buscar, like, like, like, like, like, like);
    }

    const [rows] = await db.query(`
      SELECT
        sd.id_documento_sri,
        sd.id_venta,
        sd.id_local,
        sd.estado,
        sd.ambiente,
        sd.clave_acceso,
        sd.numero_autorizacion,
        sd.ride_path,
        sd.xml_autorizado_path,
        DATE_FORMAT(sd.fecha_autorizacion, '%Y-%m-%d %H:%i:%s') AS fecha_autorizacion,
        DATE_FORMAT(v.fecha_venta, '%Y-%m-%d %H:%i:%s') AS fecha_venta,
        v.numero_comprobante,
        v.estado_sri,
        ROUND(COALESCE(v.total, 0), 2) AS total,
        l.nombre_local,
        l.direccion AS local_direccion,
        c.nombres AS cliente_nombres,
        c.cedula AS cliente_cedula,
        c.correo AS cliente_correo
      FROM sri_documentos sd
      INNER JOIN ventas v
        ON v.id_venta = sd.id_venta
      INNER JOIN locales l
        ON l.id_local = sd.id_local
      LEFT JOIN clientes c
        ON c.id_cliente = v.id_cliente
      ${where}
      ORDER BY COALESCE(sd.fecha_autorizacion, v.fecha_venta) DESC, sd.id_documento_sri DESC
      LIMIT 500
    `, params);

    const [localesRows] = await db.query(`
      SELECT
        l.id_local,
        l.nombre_local,
        COUNT(*) AS rides_generados
      FROM sri_documentos sd
      INNER JOIN locales l
        ON l.id_local = sd.id_local
      WHERE sd.tipo_comprobante = 'FACTURA'
        AND sd.ride_path IS NOT NULL
      GROUP BY l.id_local, l.nombre_local
      ORDER BY l.nombre_local ASC
    `);

    const items = rows.map((row) => {
      const rideDisponible = Boolean(row.ride_path && fs.existsSync(row.ride_path));
      const xmlDisponible = Boolean(row.xml_autorizado_path && fs.existsSync(row.xml_autorizado_path));

      return {
        id_documento_sri: Number(row.id_documento_sri),
        id_venta: Number(row.id_venta),
        id_local: Number(row.id_local),
        estado: safeText(row.estado, "N/A"),
        ambiente: safeText(row.ambiente, "N/A"),
        clave_acceso: safeText(row.clave_acceso, ""),
        numero_autorizacion: safeText(row.numero_autorizacion, ""),
        numero_comprobante: safeText(row.numero_comprobante, ""),
        estado_sri: safeText(row.estado_sri, ""),
        total: round2(row.total),
        fecha_venta: row.fecha_venta || null,
        fecha_autorizacion: row.fecha_autorizacion || null,
        nombre_local: safeText(row.nombre_local, "Sin local"),
        local_direccion: safeText(row.local_direccion, ""),
        cliente_nombres: safeText(row.cliente_nombres, "CONSUMIDOR FINAL"),
        cliente_cedula: safeText(row.cliente_cedula, ""),
        cliente_correo: safeText(row.cliente_correo, ""),
        ride_disponible: rideDisponible,
        xml_disponible: xmlDisponible,
        ride_url: rideDisponible ? toUploadUrl(row.ride_path) : null,
        xml_autorizado_url: xmlDisponible ? toUploadUrl(row.xml_autorizado_path) : null
      };
    });

    const resumen = items.reduce((acc, item) => {
      acc.total_rides += 1;

      if (item.ambiente === "PRODUCCION") {
        acc.produccion += 1;
      }

      if (item.ambiente === "PRUEBAS") {
        acc.pruebas += 1;
      }

      if (item.estado === "AUTORIZADO") {
        acc.autorizados += 1;
      }

      if (item.xml_disponible) {
        acc.xml_disponibles += 1;
      }

      return acc;
    }, {
      total_rides: 0,
      produccion: 0,
      pruebas: 0,
      autorizados: 0,
      xml_disponibles: 0
    });

    resumen.locales_total = new Set(items.map((item) => item.id_local)).size;

    res.json({
      ok: true,
      data: {
        filtros: {
          id_local: id_local || null,
          ambiente: ambiente || null,
          estado: estado || null,
          buscar: buscar || null,
          fecha_desde,
          fecha_hasta
        },
        resumen,
        locales: localesRows.map((row) => ({
          id_local: Number(row.id_local),
          nombre_local: safeText(row.nombre_local, "Sin local"),
          rides_generados: Number(row.rides_generados || 0)
        })),
        items
      }
    });
  } catch (error) {
    console.error("❌ reporteRidesSuperadmin:", error);
    res.status(500).json({
      ok: false,
      mensaje: error.message || "Error al consultar los RIDE generados"
    });
  }
};

exports.reporteStockPdfSuperadmin = async (req, res) => {
  try {
    const id_local = Number(req.query.id_local || 0);
    const id_categoria = Number(req.query.id_categoria || 0);
    const id_subcategoria = Number(req.query.id_subcategoria || 0);
    const id_producto = Number(req.query.id_producto || 0);
    const buscar = String(req.query.buscar || "").trim();
    const solo_stock_bajo = normalizeBooleanFlag(req.query.stock_bajo);
    const solo_sin_existencia = normalizeBooleanFlag(req.query.sin_existencia);

    const reportData = await getStockReportData({
      id_local,
      id_categoria,
      id_subcategoria,
      id_producto,
      buscar,
      solo_stock_bajo,
      solo_sin_existencia
    });

    const filenameParts = ["reporte-stock"];

    if (id_local) {
      filenameParts.push(`local-${id_local}`);
    } else {
      filenameParts.push("global");
    }

    if (solo_stock_bajo) {
      filenameParts.push("stock-bajo");
    }

    if (solo_sin_existencia) {
      filenameParts.push("sin-existencia");
    }

    filenameParts.push(fechaActualEcuador());

    const filename = `${filenameParts.join("-")}.pdf`;

    res.setHeader("Content-Type", "application/pdf");
    res.setHeader("Content-Disposition", `attachment; filename="${filename}"`);

    const doc = new PDFDocument({
      size: "A4",
      layout: "landscape",
      margin: 26
    });

    doc.pipe(res);
    renderStockReportPdf(doc, reportData);
    doc.end();
  } catch (error) {
    console.error("❌ reporteStockPdfSuperadmin:", error);

    if (!res.headersSent) {
      res.status(500).json({
        ok: false,
        mensaje: error.message || "Error al generar el PDF de stock"
      });
    }
  }
};

exports.reporteStockPdfAdmin = async (req, res) => {
  try {
    const id_local = Number(req.user?.id_local || 0);

    if (!id_local) {
      return res.status(400).json({
        ok: false,
        mensaje: "No se pudo resolver el local del administrador"
      });
    }

    const id_categoria = Number(req.query.id_categoria || 0);
    const id_subcategoria = Number(req.query.id_subcategoria || 0);
    const id_producto = Number(req.query.id_producto || 0);
    const buscar = String(req.query.buscar || "").trim();
    const solo_stock_bajo = normalizeBooleanFlag(req.query.stock_bajo);
    const solo_sin_existencia = normalizeBooleanFlag(req.query.sin_existencia);

    const reportData = await getStockReportData({
      id_local,
      id_categoria,
      id_subcategoria,
      id_producto,
      buscar,
      solo_stock_bajo,
      solo_sin_existencia
    });

    const filenameParts = ["reporte-stock-admin", `local-${id_local}`, fechaActualEcuador()];

    if (solo_stock_bajo) {
      filenameParts.splice(2, 0, "stock-bajo");
    }

    if (solo_sin_existencia) {
      filenameParts.splice(2, 0, "sin-existencia");
    }

    const filename = `${filenameParts.join("-")}.pdf`;

    res.setHeader("Content-Type", "application/pdf");
    res.setHeader("Content-Disposition", `attachment; filename="${filename}"`);

    const doc = new PDFDocument({
      size: "A4",
      layout: "landscape",
      margin: 26
    });

    doc.pipe(res);
    renderStockReportPdf(doc, reportData);
    doc.end();
  } catch (error) {
    console.error("❌ reporteStockPdfAdmin:", error);

    if (!res.headersSent) {
      res.status(500).json({
        ok: false,
        mensaje: error.message || "Error al generar el PDF de stock del local"
      });
    }
  }
};
