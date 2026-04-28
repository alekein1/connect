const db = require("../db/db");
const fs = require("fs");
const path = require("path");
const PDFDocument = require("pdfkit");
const comprobanteConfig = require("../config/comprobante");
const {
  getSriConfig,
  ensureSriTables
} = require("../services/sri-config.service");
const {
  ensureDetalleVentaImeiColumn,
  ensureVentaAnulacionSchema
} = require("../services/ventas-schema.service");

/* ============================================
   📦 GENERAR SECUENCIAL FACTURA
============================================ */
async function generarSecuencial(connection, establecimiento = "001", punto_emision = "001") {
  const [[row]] = await connection.query(`
    SELECT secuencial
    FROM ventas
    WHERE establecimiento = ?
      AND punto_emision = ?
    ORDER BY CAST(secuencial AS UNSIGNED) DESC
    LIMIT 1
    FOR UPDATE
  `, [establecimiento, punto_emision]);

  const nuevo = row?.secuencial ? Number(row.secuencial) + 1 : 1;

  return String(nuevo).padStart(9, "0");
}

/* ============================================
   👤 CREAR O BUSCAR CLIENTE
============================================ */
async function obtenerOCrearCliente(connection, cliente) {
  if (!cliente || !cliente.cedula) return null;

  const cedula = String(cliente.cedula).trim();

  const [[existe]] = await connection.query(`
    SELECT id_cliente
    FROM clientes
    WHERE cedula = ?
    LIMIT 1
  `, [cedula]);

  if (existe) return existe.id_cliente;

  const [nuevo] = await connection.query(`
    INSERT INTO clientes (
      nombres,
      cedula,
      telefono,
      correo,
      direccion,
      activo
    ) VALUES (?, ?, ?, ?, ?, 1)
  `, [
    cliente.nombres || "CONSUMIDOR",
    cedula,
    cliente.telefono || null,
    cliente.correo || null,
    cliente.direccion || null
  ]);

  return nuevo.insertId;
}

/* ============================================
   💳 SUMAR PAGOS
============================================ */
function sumarPagos(pagos = []) {
  return pagos.reduce((acc, p) => acc + Number(p.monto || 0), 0);
}

function round2(value) {
  return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
}

function createHttpError(message, statusCode = 400) {
  const error = new Error(message);
  error.statusCode = statusCode;
  return error;
}

function getFirstFiniteNumber(...values) {
  for (const value of values) {
    const number = Number(value);
    if (Number.isFinite(number)) {
      return number;
    }
  }

  return null;
}

function resolvePrecioUnitarioVenta({
  item,
  precioBase,
  usaTarjeta = false,
  aumentoPorProductoGlobal = 0
}) {
  const base = round2(precioBase);
  const cantidad = Math.max(1, Number(item?.cantidad || 1));
  const totalLineaSolicitado = getFirstFiniteNumber(item?.total_linea, item?.precio_total);
  const precioSolicitado = getFirstFiniteNumber(
    item?.precio_unitario,
    item?.precio,
    item?.precio_final,
    totalLineaSolicitado !== null ? totalLineaSolicitado / cantidad : null
  );
  const aumentoSolicitado = getFirstFiniteNumber(
    item?.aumento_por_producto,
    item?.aumento,
    item?.recargo,
    item?.recargo_tarjeta,
    aumentoPorProductoGlobal
  );

  if (precioSolicitado !== null && precioSolicitado > 0) {
    const precioNormalizado = round2(precioSolicitado);

    if (usaTarjeta || precioNormalizado >= base) {
      return precioNormalizado;
    }
  }

  if (aumentoSolicitado !== null && aumentoSolicitado > 0) {
    return round2(base + aumentoSolicitado);
  }

  return base;
}

function normalizarBooleano(value) {
  if (typeof value === "boolean") return value;
  if (typeof value === "number") return value === 1;
  if (typeof value === "string") {
    return ["1", "true", "si", "sí", "yes"].includes(value.trim().toLowerCase());
  }
  return false;
}

function safeText(value, fallback = "N/A") {
  if (value === null || value === undefined) return fallback;
  const text = String(value).trim();
  return text ? text : fallback;
}

function digitsOnly(value) {
  return String(value || "").replace(/\D/g, "");
}

function padLeft(value, length) {
  return String(value || "").padStart(length, "0");
}

function formatMoney(value) {
  return Number(value || 0).toFixed(2);
}

function formatDateEc(value) {
  return new Intl.DateTimeFormat("es-EC", {
    timeZone: "America/Guayaquil",
    day: "2-digit",
    month: "2-digit",
    year: "numeric"
  }).format(new Date(value));
}

function getCurrentDateTimeEcSql() {
  const parts = new Intl.DateTimeFormat("en-CA", {
    timeZone: "America/Guayaquil",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false,
    hourCycle: "h23"
  }).formatToParts(new Date()).reduce((acc, part) => {
    if (part.type !== "literal") {
      acc[part.type] = part.value;
    }
    return acc;
  }, {});

  return `${parts.year}-${parts.month}-${parts.day} ${parts.hour}:${parts.minute}:${parts.second}`;
}

function formatTimeEc(value) {
  return new Intl.DateTimeFormat("es-EC", {
    timeZone: "America/Guayaquil",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false
  }).format(new Date(value));
}

function formatDateTimeEc(value) {
  if (!value) return "";

  // 🔥 FORZAR QUE SEA UTC
  const date = new Date(value + "Z");

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

function formatDateKey(value) {
  const [day, month, year] = formatDateEc(value).split("/");
  return `${day}${month}${year}`;
}

function validarFechaIso(value) {
  return /^\d{4}-\d{2}-\d{2}$/.test(String(value || "").trim());
}

function resolverFiltroEstadoVentas(value = "PAGADA") {
  const normalized = String(value || "PAGADA").trim().toUpperCase();

  if (["PAGADA", "ANULADA", "TODAS"].includes(normalized)) {
    return normalized;
  }

  throw createHttpError("El filtro de estado debe ser PAGADA, ANULADA o TODAS");
}

function construirFiltrosVentasAdmin({
  idLocal,
  buscar = "",
  estado = "PAGADA",
  desde = null,
  hasta = null
}) {
  const where = ["v.id_local = ?"];
  const params = [idLocal];

  if (estado !== "TODAS") {
    where.push("v.estado = ?");
    params.push(estado);
  }

  if (desde) {
    if (!validarFechaIso(desde)) {
      throw createHttpError("La fecha 'desde' debe tener formato YYYY-MM-DD");
    }

    where.push("DATE(v.fecha_venta) >= ?");
    params.push(desde);
  }

  if (hasta) {
    if (!validarFechaIso(hasta)) {
      throw createHttpError("La fecha 'hasta' debe tener formato YYYY-MM-DD");
    }

    where.push("DATE(v.fecha_venta) <= ?");
    params.push(hasta);
  }

  if (desde && hasta && desde > hasta) {
    throw createHttpError("La fecha 'desde' no puede ser mayor que 'hasta'");
  }

  const termino = String(buscar || "").trim();

  if (termino) {
    const likeTerm = `%${termino}%`;

    where.push(`
      (
        CAST(v.id_venta AS CHAR) = ?
        OR v.numero_comprobante LIKE ?
        OR COALESCE(c.nombres, '') LIKE ?
        OR COALESCE(c.cedula, '') LIKE ?
        OR COALESCE(u.usuario, '') LIKE ?
        OR EXISTS (
          SELECT 1
          FROM detalle_venta d2
          INNER JOIN productos p2
            ON p2.id_producto = d2.id_producto
          WHERE d2.id_venta = v.id_venta
            AND (
              COALESCE(d2.imei, '') LIKE ?
              OR COALESCE(p2.nombre_producto, '') LIKE ?
            )
        )
      )
    `);
    params.push(
      termino,
      likeTerm,
      likeTerm,
      likeTerm,
      likeTerm,
      likeTerm,
      likeTerm
    );
  }

  return {
    sql: where.join(" AND "),
    params
  };
}

function getLegalNotes() {
  return [
    "El cliente declara que los valores entregados y la presente compra no corresponden a ninguna actividad ilegal o ilicita, ni seran destinados a acciones tipificadas por la ley.",
    "La garantia cubre cuando el cliente presente el producto sin golpes, rayones o defectos fisicos.",
    "La garantia cubre exclusivamente linea y software.",
    "Una vez que el producto este facturado no hay devoluciones."
  ];
}

function resolveLogoPath() {
  const configured = comprobanteConfig.logoPath;
  const candidates = [];

  if (configured) {
    candidates.push(
      path.isAbsolute(configured)
        ? configured
        : path.resolve(__dirname, "..", configured)
    );
  }

  candidates.push(
    path.resolve(__dirname, "../../frontend-connect/public/images/connect.png")
  );

  return candidates.find(filePath => fs.existsSync(filePath)) || null;
}

function buildSriPreviewData(venta) {
  const ambiente = safeText(venta.ambiente || comprobanteConfig.emisor.ambiente, "PRUEBAS");
  const ambienteCodigo = ambiente.toUpperCase() === "PRODUCCION" ? "2" : "1";
  const ruc = padLeft(digitsOnly(comprobanteConfig.emisor.ruc), 13);
  const establecimiento = padLeft(venta.establecimiento || "001", 3);
  const puntoEmision = padLeft(venta.punto_emision || "001", 3);
  const secuencial = padLeft(venta.secuencial || venta.id_venta, 9);
  const codigoNumerico = padLeft(`${venta.id_venta}${digitsOnly(venta.total)}`, 8).slice(-8);

  return {
    ambiente,
    emision: "NORMAL",
    claveAcceso:
      venta.clave_acceso ||
      `${formatDateKey(venta.fecha_venta)}01${ruc}${ambienteCodigo}${establecimiento}${puntoEmision}${secuencial}${codigoNumerico}1`,
    numeroAutorizacion:
      venta.numero_autorizacion ||
      `${formatDateKey(venta.fecha_venta)}${padLeft(venta.id_venta, 10)}${padLeft(digitsOnly(venta.total), 8)}`,
    fechaAutorizacion: formatDateTimeEc(venta.fecha_venta)
  };
}

function buildMailTransport() {
  let nodemailer;

  try {
    nodemailer = require("nodemailer");
  } catch (error) {
    throw new Error("Debes instalar manualmente la dependencia: npm install nodemailer");
  }

  const { smtp } = comprobanteConfig;
  const missing = [];

  if (!smtp.host) missing.push("SMTP_HOST");
  if (!smtp.port) missing.push("SMTP_PORT");
  if (!smtp.user) missing.push("SMTP_USER");
  if (!smtp.pass) missing.push("SMTP_PASS");
  if (!smtp.fromEmail) missing.push("SMTP_FROM_EMAIL");

  if (missing.length > 0) {
    throw new Error(`Configura manualmente estas variables: ${missing.join(", ")}`);
  }

  return nodemailer.createTransport({
    host: smtp.host,
    port: Number(smtp.port),
    secure: Boolean(smtp.secure),
    auth: {
      user: smtp.user,
      pass: smtp.pass
    }
  });
}

async function fetchVentaForComprobante(idVenta, idLocal) {
  await ensureDetalleVentaImeiColumn();

  const [[venta]] = await db.query(`
    SELECT
      v.*,
      c.nombres AS cliente_nombres,
      c.cedula AS cliente_cedula,
      c.correo AS cliente_correo,
      c.telefono AS cliente_telefono,
      c.direccion AS cliente_direccion,
      l.nombre_local,
      l.direccion AS local_direccion,
      l.telefono AS local_telefono,
      u.usuario,
      u.correo AS usuario_correo
    FROM ventas v
    LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
    LEFT JOIN locales l ON l.id_local = v.id_local
    LEFT JOIN usuarios u ON u.id_usuario = v.id_usuario
    WHERE v.id_venta = ?
      AND v.id_local = ?
    LIMIT 1
  `, [idVenta, idLocal]);

  if (!venta) return null;

  const [detalle] = await db.query(`
    SELECT
      d.*,
      p.nombre_producto,
      p.codigo_barras,
      p.sku
    FROM detalle_venta d
    INNER JOIN productos p ON p.id_producto = d.id_producto
    WHERE d.id_venta = ?
    ORDER BY d.id_detalle ASC
  `, [idVenta]);

  const [pagos] = await db.query(`
    SELECT
      id_pago,
      monto,
      forma_pago,
      fecha_pago
    FROM ventas_pagos
    WHERE id_venta = ?
    ORDER BY id_pago ASC
  `, [idVenta]);

  return {
    ...venta,
    detalle,
    pagos
  };
}

async function asegurarStockProductoVenta(connection, idProducto, idLocal) {
  let [[stock]] = await connection.query(
    `
    SELECT id_stock, stock_actual
    FROM inventario_stock
    WHERE id_producto = ?
      AND id_local = ?
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

async function fetchVentaAdminDetalle(executor, idVenta, idLocal) {
  await ensureSriTables();
  await ensureVentaAnulacionSchema();
  await ensureDetalleVentaImeiColumn();

  const [[venta]] = await executor.query(
    `
    SELECT
      v.*,
      c.nombres AS cliente_nombres,
      c.cedula AS cliente_cedula,
      c.correo AS cliente_correo,
      c.telefono AS cliente_telefono,
      c.direccion AS cliente_direccion,
      l.nombre_local,
      u.usuario AS usuario_venta,
      ua.usuario AS usuario_anulacion,
      COALESCE((
        SELECT sd.estado
        FROM sri_documentos sd
        WHERE sd.id_venta = v.id_venta
          AND sd.tipo_comprobante = 'FACTURA'
        ORDER BY sd.id_documento_sri DESC
        LIMIT 1
      ), 'SIN_DOCUMENTO') AS estado_documento_sri
    FROM ventas v
    LEFT JOIN clientes c
      ON c.id_cliente = v.id_cliente
    LEFT JOIN locales l
      ON l.id_local = v.id_local
    LEFT JOIN usuarios u
      ON u.id_usuario = v.id_usuario
    LEFT JOIN usuarios ua
      ON ua.id_usuario = v.id_usuario_anulacion
    WHERE v.id_venta = ?
      AND v.id_local = ?
    LIMIT 1
    `,
    [idVenta, idLocal]
  );

  if (!venta) {
    return null;
  }

  const [detalle] = await executor.query(
    `
    SELECT
      d.id_detalle,
      d.id_producto,
      d.cantidad,
      d.imei,
      d.precio_unitario,
      d.costo_unitario,
      d.subtotal,
      d.costo_total,
      d.ganancia,
      p.nombre_producto,
      p.codigo_barras,
      p.sku
    FROM detalle_venta d
    INNER JOIN productos p
      ON p.id_producto = d.id_producto
    WHERE d.id_venta = ?
    ORDER BY d.id_detalle ASC
    `,
    [idVenta]
  );

  const [pagos] = await executor.query(
    `
    SELECT
      id_pago,
      monto,
      forma_pago,
      fecha_pago
    FROM ventas_pagos
    WHERE id_venta = ?
    ORDER BY id_pago ASC
    `,
    [idVenta]
  );

  return {
    ...venta,
    detalle,
    pagos,
    puede_anular:
      venta.estado === "PAGADA" &&
      venta.estado_documento_sri !== "AUTORIZADO"
  };
}

function generateComprobantePdfBuffer(venta) {
  const sri = buildSriPreviewData(venta);
  const logoPath = resolveLogoPath();
  const legalNotes = getLegalNotes();

  return new Promise((resolve, reject) => {
    const doc = new PDFDocument({
      size: "A4",
      margin: 36
    });

    const chunks = [];
    const colors = {
      brand: "#1d4ed8",
      brandSoft: "#eff6ff",
      text: "#0f172a",
      muted: "#64748b",
      border: "#cbd5e1",
      panel: "#f8fafc",
      white: "#ffffff"
    };

    doc.on("data", chunk => chunks.push(chunk));
    doc.on("end", () => resolve(Buffer.concat(chunks)));
    doc.on("error", reject);

    const pageWidth = doc.page.width - doc.page.margins.left - doc.page.margins.right;
    const leftX = doc.page.margins.left;
    let y = doc.page.margins.top;

    const drawLabelValue = (x, currentY, label, value, width) => {
      doc
        .font("Helvetica-Bold")
        .fontSize(8)
        .fillColor(colors.muted)
        .text(label, x, currentY, { width });

      doc
        .font("Helvetica")
        .fontSize(10)
        .fillColor(colors.text)
        .text(safeText(value), x, currentY + 11, { width });
    };

    const drawFieldBlock = (x, startY, label, value, width, options = {}) => {
      const {
        labelFontSize = 7.2,
        valueFontSize = 8.6,
        labelValueGap = 8,
        gapAfter = 4,
        lineGap = 0
      } = options;

      doc
        .font("Helvetica-Bold")
        .fontSize(labelFontSize)
        .fillColor(colors.muted)
        .text(label, x, startY, { width });

      const valueY = startY + labelValueGap;
      doc
        .font("Helvetica")
        .fontSize(valueFontSize)
        .fillColor(colors.text)
        .text(safeText(value), x, valueY, {
          width,
          lineGap
        });

      const valueHeight = doc.heightOfString(safeText(value), {
        width,
        lineGap
      });

      return valueY + valueHeight + gapAfter;
    };

    const leftHeaderWidth = 295;
    const rightHeaderWidth = pageWidth - leftHeaderWidth - 14;

    doc
      .roundedRect(leftX, y, leftHeaderWidth, 124, 12)
      .fillAndStroke(colors.white, colors.border);

    if (logoPath) {
      doc.image(logoPath, leftX + 16, y + 16, {
        fit: [92, 38],
        align: "left"
      });
    } else {
      doc
        .font("Helvetica-Bold")
        .fontSize(22)
        .fillColor(colors.brand)
        .text("CONNECT", leftX + 16, y + 22);
    }

    doc
      .font("Helvetica-Bold")
      .fontSize(14)
      .fillColor(colors.text)
      .text(safeText(comprobanteConfig.emisor.razonSocial), leftX + 16, y + 62, {
        width: leftHeaderWidth - 32
      });

    doc
      .font("Helvetica")
      .fontSize(9)
      .fillColor(colors.muted)
      .text(`RUC: ${safeText(comprobanteConfig.emisor.ruc)}`, leftX + 16, y + 82)
      .text(`Matriz: ${safeText(comprobanteConfig.emisor.matriz)}`, leftX + 16, y + 95, {
        width: leftHeaderWidth - 32
      })
      .text(`Local: ${safeText(venta.nombre_local)}`, leftX + 16, y + 108, {
        width: leftHeaderWidth - 32
      });

    doc
      .roundedRect(leftX + leftHeaderWidth + 14, y, rightHeaderWidth, 124, 12)
      .fillAndStroke(colors.white, colors.border);

    doc
      .font("Helvetica-Bold")
      .fontSize(16)
      .fillColor(colors.brand)
      .text("FACTURA INTERNA", leftX + leftHeaderWidth + 28, y + 16, {
        width: rightHeaderWidth - 56,
        align: "center"
      });

    drawLabelValue(leftX + leftHeaderWidth + 24, y + 46, "No. comprobante", venta.numero_comprobante, rightHeaderWidth - 48);
    drawLabelValue(leftX + leftHeaderWidth + 24, y + 75, "Autorizacion referencial", sri.numeroAutorizacion, rightHeaderWidth - 48);

    y += 142;

    const topInfoWidth = (pageWidth - 14) / 2;
    const topInfoHeight = 148;

    doc
      .roundedRect(leftX, y, topInfoWidth, topInfoHeight, 12)
      .fillAndStroke(colors.panel, colors.border);

    doc
      .font("Helvetica-Bold")
      .fontSize(10)
      .fillColor(colors.text)
      .text("Datos del cliente", leftX + 14, y + 12);

    let clientInfoY = y + 28;
    clientInfoY = drawFieldBlock(leftX + 14, clientInfoY, "Cliente", venta.cliente_nombres, topInfoWidth - 28, {
      gapAfter: 3
    });
    clientInfoY = drawFieldBlock(leftX + 14, clientInfoY, "Cedula/RUC", venta.cliente_cedula, topInfoWidth - 28, {
      gapAfter: 3
    });
    clientInfoY = drawFieldBlock(leftX + 14, clientInfoY, "Telefono", venta.cliente_telefono, topInfoWidth - 28, {
      gapAfter: 3
    });
    clientInfoY = drawFieldBlock(leftX + 14, clientInfoY, "Correo", venta.cliente_correo, topInfoWidth - 28, {
      valueFontSize: 8.1,
      gapAfter: 3
    });
    drawFieldBlock(leftX + 14, clientInfoY, "Direccion", venta.cliente_direccion, topInfoWidth - 28, {
      valueFontSize: 8.1,
      gapAfter: 0
    });

    doc
      .roundedRect(leftX + topInfoWidth + 14, y, topInfoWidth, topInfoHeight, 12)
      .fillAndStroke(colors.panel, colors.border);

    doc
      .font("Helvetica-Bold")
      .fontSize(10)
      .fillColor(colors.text)
      .text("Datos del documento", leftX + topInfoWidth + 24, y + 12);

    let documentInfoY = y + 28;
    documentInfoY = drawFieldBlock(
      leftX + topInfoWidth + 24,
      documentInfoY,
      "Fecha de emision",
      formatDateTimeEc(venta.fecha_venta),
      topInfoWidth - 34,
      { gapAfter: 3 }
    );
    documentInfoY = drawFieldBlock(
      leftX + topInfoWidth + 24,
      documentInfoY,
      "Fecha autorizacion",
      sri.fechaAutorizacion,
      topInfoWidth - 34,
      { gapAfter: 3 }
    );
    documentInfoY = drawFieldBlock(
      leftX + topInfoWidth + 24,
      documentInfoY,
      "Ambiente / Emision",
      `${sri.ambiente} / ${sri.emision}`,
      topInfoWidth - 34,
      { gapAfter: 3 }
    );
    documentInfoY = drawFieldBlock(
      leftX + topInfoWidth + 24,
      documentInfoY,
      "Autorizacion referencial",
      sri.numeroAutorizacion,
      topInfoWidth - 34,
      { valueFontSize: 7.8, gapAfter: 2 }
    );
    drawFieldBlock(
      leftX + topInfoWidth + 24,
      documentInfoY,
      "Clave acceso ref.",
      sri.claveAcceso,
      topInfoWidth - 34,
      { valueFontSize: 7.2, gapAfter: 0 }
    );

    y += topInfoHeight + 18;

    const columns = [
      { key: "codigo", label: "COD.", width: 62 },
      { key: "descripcion", label: "DESCRIPCION", width: 175 },
      { key: "cantidad", label: "CANT.", width: 40 },
      { key: "precio", label: "P. UNIT", width: 60 },
      { key: "subtotal", label: "SUBTOTAL", width: 60 },
      { key: "iva", label: "IVA", width: 50 },
      { key: "total", label: "TOTAL", width: 68 }
    ];

    const drawTableHeader = headerY => {
      let currentX = leftX;

      columns.forEach(column => {
        doc
          .roundedRect(currentX, headerY, column.width, 24, 6)
          .fillAndStroke(colors.brand, colors.brand);

        doc
          .font("Helvetica-Bold")
          .fontSize(8)
          .fillColor(colors.white)
          .text(column.label, currentX + 4, headerY + 8, {
            width: column.width - 8,
            align: column.key === "descripcion" ? "left" : "center"
          });

        currentX += column.width;
      });
    };

    drawTableHeader(y);
    y += 28;

    venta.detalle.forEach(item => {
      const descripcion = item.imei
        ? `${safeText(item.nombre_producto)}\nIMEI: ${safeText(item.imei, "")}`
        : safeText(item.nombre_producto);
      const descripcionColumn = columns.find(column => column.key === "descripcion");
      const descripcionHeight = doc.heightOfString(descripcion, {
        width: descripcionColumn.width - 8,
        lineGap: 1
      });
      const rowHeight = Math.max(24, Math.ceil(descripcionHeight) + 10);

      if (y + rowHeight > 680) {
        doc.addPage();
        y = doc.page.margins.top;
        drawTableHeader(y);
        y += 28;
      }

      const cantidad = Number(item.cantidad || 0);
      const precioUnitario = Number(item.precio_unitario || 0);
      const subtotalLinea = Number(item.subtotal || 0);
      const totalLinea = round2(cantidad * precioUnitario);
      const ivaLinea = round2(totalLinea - subtotalLinea);
      const row = {
        codigo: safeText(item.codigo_barras || item.sku || item.id_producto, "-"),
        descripcion,
        cantidad: cantidad.toFixed(2),
        precio: formatMoney(precioUnitario),
        subtotal: formatMoney(subtotalLinea),
        iva: formatMoney(ivaLinea),
        total: formatMoney(totalLinea)
      };

      let currentX = leftX;
      columns.forEach(column => {
        doc
          .rect(currentX, y, column.width, rowHeight)
          .fillAndStroke(colors.white, colors.border);

        doc
          .font(column.key === "descripcion" ? "Helvetica-Bold" : "Helvetica")
          .fontSize(8.5)
          .fillColor(colors.text)
          .text(String(row[column.key]), currentX + 4, y + 7, {
            width: column.width - 8,
            align: column.key === "descripcion" ? "left" : "center",
            ellipsis: column.key === "descripcion" ? false : true,
            lineGap: column.key === "descripcion" ? 1 : 0
          });

        currentX += column.width;
      });

      y += rowHeight;
    });

    y += 16;

    const leftBottomWidth = 300;
    const rightBottomWidth = pageWidth - leftBottomWidth - 14;
    const infoBoxHeight = 116;

    doc
      .roundedRect(leftX, y, leftBottomWidth, infoBoxHeight, 12)
      .fillAndStroke(colors.panel, colors.border);

    doc
      .font("Helvetica-Bold")
      .fontSize(11)
      .fillColor(colors.text)
      .text("Informacion adicional", leftX + 16, y + 14);

    const pagosLabel = venta.pagos.length
      ? venta.pagos.map(pago => `${pago.forma_pago}: $${formatMoney(pago.monto)}`).join(" | ")
      : safeText(venta.forma_pago);

    drawLabelValue(leftX + 16, y + 36, "Direccion", venta.cliente_direccion || venta.local_direccion, leftBottomWidth - 32);
    drawLabelValue(leftX + 16, y + 62, "Atendido por", venta.usuario, (leftBottomWidth - 44) / 2);
    drawLabelValue(leftX + 16 + (leftBottomWidth - 44) / 2 + 12, y + 62, "Telefono local", venta.local_telefono, (leftBottomWidth - 44) / 2);
    drawLabelValue(leftX + 16, y + 88, "Pagos", pagosLabel, leftBottomWidth - 32);

    doc
      .roundedRect(leftX + leftBottomWidth + 14, y, rightBottomWidth, infoBoxHeight, 12)
      .fillAndStroke(colors.white, colors.border);

    doc
      .font("Helvetica-Bold")
      .fontSize(11)
      .fillColor(colors.text)
      .text("Resumen", leftX + leftBottomWidth + 30, y + 14);

    const totals = [
      ["Subtotal", formatMoney(venta.subtotal)],
      ["Descuento", formatMoney(venta.descuento)],
      ["IVA 15%", formatMoney(venta.impuesto)],
      ["Total", formatMoney(venta.total)]
    ];

    let totalsY = y + 40;
    totals.forEach(([label, value], index) => {
      doc
        .font(index === totals.length - 1 ? "Helvetica-Bold" : "Helvetica")
        .fontSize(index === totals.length - 1 ? 11 : 10)
        .fillColor(colors.text)
        .text(label, leftX + leftBottomWidth + 30, totalsY, { width: 110 });

      doc
        .font(index === totals.length - 1 ? "Helvetica-Bold" : "Helvetica")
        .fontSize(index === totals.length - 1 ? 11 : 10)
        .fillColor(index === totals.length - 1 ? colors.brand : colors.text)
        .text(`$ ${value}`, leftX + leftBottomWidth + 30, totalsY, {
          width: rightBottomWidth - 60,
          align: "right"
        });

      totalsY += 18;
    });

    y += infoBoxHeight + 18;

    const legalText = [
      "Comprobante interno generado por CONNECT. Los datos de autorizacion y clave de acceso mostrados son referenciales mientras se integra el flujo oficial del SRI.",
      ...legalNotes.map((note, index) => `${index + 1}. ${note}`)
    ].join("\n");
    const legalTextHeight = doc.heightOfString(legalText, {
      width: pageWidth - 32,
      lineGap: 2
    });
    const noteBoxHeight = Math.max(94, legalTextHeight + 42);

    doc
      .roundedRect(leftX, y, pageWidth, noteBoxHeight, 12)
      .fillAndStroke(colors.brandSoft, colors.border);

    doc
      .font("Helvetica-Bold")
      .fontSize(10)
      .fillColor(colors.brand)
      .text("Notas y condiciones", leftX + 16, y + 14);

    doc
      .font("Helvetica")
      .fontSize(9)
      .fillColor(colors.text)
      .text(legalText, leftX + 16, y + 28, {
        width: pageWidth - 32,
        lineGap: 2
      });

    doc.end();
  });
}

async function sendComprobanteEmail({ venta, correoDestino, pdfBuffer, asunto, mensaje }) {
  const transporter = buildMailTransport();
  const attachmentName = `comprobante-${safeText(venta.numero_comprobante, venta.id_venta).replace(/[^a-zA-Z0-9-_]/g, "_")}.pdf`;
  const subject = asunto || `Comprobante ${safeText(venta.numero_comprobante, venta.id_venta)}`;
  const legalNotes = getLegalNotes();
  const text = mensaje || `Adjuntamos el comprobante de la venta ${safeText(venta.numero_comprobante, venta.id_venta)}.`;
  const textWithLegalNotes = [
    text,
    "",
    "Notas y condiciones:",
    ...legalNotes.map((note, index) => `${index + 1}. ${note}`)
  ].join("\n");
  const html = `
    <div style="font-family: Arial, sans-serif; background:#f8fafc; padding:24px;">
      <div style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">
        <div style="background:#1d4ed8; color:#ffffff; padding:20px 24px;">
          <div style="font-size:20px; font-weight:700;">CONNECT</div>
          <div style="font-size:13px; opacity:.9;">Envio de comprobante interno</div>
        </div>
        <div style="padding:24px; color:#0f172a;">
          <p style="margin:0 0 12px;">Hola,</p>
          <p style="margin:0 0 12px;">${text}</p>
          <p style="margin:0 0 12px;">No. comprobante: <strong>${safeText(venta.numero_comprobante)}</strong></p>
          <div style="margin:0 0 12px; padding:14px 16px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px;">
            <div style="font-size:13px; font-weight:700; margin:0 0 8px; color:#1d4ed8;">Notas y condiciones</div>
            <ol style="margin:0; padding-left:18px; color:#0f172a; font-size:13px; line-height:1.55;">
              ${legalNotes.map((note) => `<li>${note}</li>`).join("")}
            </ol>
          </div>
          <p style="margin:0;">Gracias por tu compra.</p>
        </div>
      </div>
    </div>
  `;

  return transporter.sendMail({
    from: `"${comprobanteConfig.smtp.fromName}" <${comprobanteConfig.smtp.fromEmail}>`,
    to: correoDestino,
    subject,
    text: textWithLegalNotes,
    html,
    attachments: [
      {
        filename: attachmentName,
        content: pdfBuffer,
        contentType: "application/pdf"
      }
    ]
  });
}

/* ============================================
   🧾 CREAR VENTA PRO
============================================ */
exports.crearVenta = async (req, res) => {
  const connection = await db.getConnection();

  try {
    await ensureDetalleVentaImeiColumn();
    await connection.beginTransaction();

    const {
      productos = [],
      pagos = [],
      tipo_venta = "CONTADO", // CONTADO | FINANCIADO
      entrada = 0,
      cuotas = 0,
      proveedor_financiamiento = null,
      cliente = null,
      descuento = 0,
      motivo_descuento = null,
      aumento_por_producto = 0,
      recargo_por_producto = 0
    } = req.body;

    const id_usuario = req.user.id_usuario;
    const id_local   = req.user.id_local;
    const sriConfig = await getSriConfig(id_local);
    const establecimiento = sriConfig?.establecimiento || "001";
    const punto_emision   = sriConfig?.punto_emision || "001";
    const ambienteSri     = sriConfig?.ambiente || "PRUEBAS";

    if (!Array.isArray(productos) || productos.length === 0) {
      throw new Error("Debe enviar al menos un producto");
    }

    if (!["CONTADO", "FINANCIADO"].includes(tipo_venta)) {
      throw new Error("Tipo de venta inválido");
    }

    if (!Array.isArray(pagos)) {
      throw new Error("Pagos inválidos");
    }

    const usaTarjeta = pagos.some(
      pago => String(pago?.forma_pago || "").trim().toUpperCase() === "TARJETA"
    );
    const aumentoPorProductoGlobal = Math.max(
      0,
      Number(aumento_por_producto || recargo_por_producto || 0)
    );

    /* ===============================
       🔐 VALIDAR CAJA ABIERTA
    =============================== */
    const [[caja]] = await connection.query(`
      SELECT id_caja
      FROM caja_apertura
      WHERE id_usuario = ?
        AND id_local = ?
        AND estado = 'ABIERTA'
      LIMIT 1
    `, [id_usuario, id_local]);

    if (!caja) {
      throw new Error("No hay caja abierta");
    }

    const id_caja = caja.id_caja;

    /* ===============================
       👤 CLIENTE
    =============================== */
    const id_cliente = await obtenerOCrearCliente(connection, cliente);

    /* ===============================
       📦 VALIDAR PRODUCTOS Y ARMAR DETALLE
    =============================== */
    const detalleProcesado = [];
    let subtotal = 0;
    let totalBruto = 0;
    let totalGravado = 0;
    let totalNoGravado = 0;

    for (const item of productos) {
      const id_producto = Number(item.id_producto);
      const cantidad = Number(item.cantidad || 1);
      const imei = item.imei ? String(item.imei).trim() : null;

      if (!id_producto || cantidad <= 0) {
        throw new Error("Producto o cantidad inválida");
      }

      const [[prod]] = await connection.query(`
        SELECT 
          p.id_producto,
          p.nombre_producto,
          p.precio_unitario,
          p.precio_compra,
          p.graba_iva,
          i.stock_actual
        FROM productos p
        INNER JOIN inventario_stock i
          ON i.id_producto = p.id_producto
         AND i.id_local = ?
        WHERE p.id_producto = ?
          AND p.id_local = ?
        LIMIT 1
      `, [id_local, id_producto, id_local]);

      if (!prod) {
        throw new Error(`Producto no existe en este local: ${id_producto}`);
      }

      if (prod.stock_actual < cantidad) {
        throw new Error(`Stock insuficiente para ${prod.nombre_producto}`);
      }

      if (imei) {
        const [[imeiRow]] = await connection.query(`
          SELECT id_imei, estado
          FROM inventario_imei
          WHERE id_producto = ?
            AND id_local = ?
            AND imei1 = ?
          LIMIT 1
        `, [id_producto, id_local, imei]);

        if (!imeiRow) {
          throw new Error(`IMEI no encontrado para ${prod.nombre_producto}`);
        }

        if (imeiRow.estado !== "disponible") {
          throw new Error(`IMEI no disponible: ${imei}`);
        }

        if (cantidad !== 1) {
          throw new Error(`Cuando se vende por IMEI, la cantidad debe ser 1`);
        }
      }

      const precio_unitario = resolvePrecioUnitarioVenta({
        item,
        precioBase: Number(prod.precio_unitario || 0),
        usaTarjeta,
        aumentoPorProductoGlobal
      });
      const costo_unitario  = Number(prod.precio_compra || 0);
      const graba_iva = normalizarBooleano(
        item.graba_iva !== undefined ? item.graba_iva : prod.graba_iva
      );
      const total_linea = round2(precio_unitario * cantidad);
      const subtotal_linea = graba_iva
        ? round2(total_linea / 1.15)
        : total_linea;
      const impuesto_linea = graba_iva
        ? round2(total_linea - subtotal_linea)
        : 0;
      const costo_total     = costo_unitario * cantidad;
      const ganancia        = total_linea - costo_total;

      subtotal += subtotal_linea;
      totalBruto += total_linea;
      if (graba_iva) {
        totalGravado += total_linea;
      } else {
        totalNoGravado += total_linea;
      }

      detalleProcesado.push({
        id_producto,
        nombre_producto: prod.nombre_producto,
        cantidad,
        imei,
        graba_iva,
        precio_unitario,
        subtotal_linea,
        impuesto_linea,
        total_linea,
        costo_unitario,
        costo_total,
        ganancia,
        stock_anterior: Number(prod.stock_actual),
        stock_nuevo: Number(prod.stock_actual) - cantidad
      });
    }

    /* ===============================
       💰 TOTALES
    =============================== */
    subtotal = round2(subtotal);
    totalBruto = round2(totalBruto);
    totalGravado = round2(totalGravado);
    totalNoGravado = round2(totalNoGravado);

    const descuentoNum = Math.max(0, Number(descuento || 0));
    const descuentoAplicado = round2(Math.min(descuentoNum, totalBruto));
    const descuentoGravado = totalBruto > 0
      ? round2(descuentoAplicado * (totalGravado / totalBruto))
      : 0;
    const descuentoNoGravado = round2(descuentoAplicado - descuentoGravado);

    // Separar la base e IVA desde el total gravado evita perder 1 centavo.
    const totalGravadoConDescuento = round2(Math.max(0, totalGravado - descuentoGravado));
    const totalNoGravadoConDescuento = round2(Math.max(0, totalNoGravado - descuentoNoGravado));
    const baseGravadaConDescuento = totalGravadoConDescuento > 0
      ? round2(totalGravadoConDescuento / 1.15)
      : 0;
    const impuesto = totalGravadoConDescuento > 0
      ? round2(totalGravadoConDescuento - baseGravadaConDescuento)
      : 0;
    subtotal = round2(baseGravadaConDescuento + totalNoGravadoConDescuento);
    const total = round2(totalBruto - descuentoAplicado);

    const totalPagos = sumarPagos(pagos);
    const entradaNum = Math.max(0, Number(entrada || 0));

    if (tipo_venta === "CONTADO") {
      if (Math.abs(totalPagos - total) > 0.01) {
        throw new Error("Los pagos no cuadran con el total");
      }
    }

    let saldo = 0;
    let cuotasNum = 0;
    let proveedor = null;

    if (tipo_venta === "FINANCIADO") {
      cuotasNum = Number(cuotas || 0);
      proveedor = proveedor_financiamiento || null;
      saldo = total - entradaNum;

      if (saldo < 0) {
        throw new Error("La entrada no puede ser mayor al total");
      }

      if (!proveedor) {
        throw new Error("Debe indicar proveedor de financiamiento");
      }

      if (totalPagos > 0 && Math.abs(totalPagos - entradaNum) > 0.01) {
        throw new Error("Los pagos deben coincidir con la entrada");
      }
    }

    /* ===============================
       🧾 FACTURA INTERNA
    =============================== */
    const secuencial = await generarSecuencial(connection, establecimiento, punto_emision);
    const numero_comprobante = `${establecimiento}-${punto_emision}-${secuencial}`;

    /* ===============================
       🧾 INSERTAR VENTA
    =============================== */
    const fechaVenta = getCurrentDateTimeEcSql();

    const [ventaInsert] = await connection.query(`
      INSERT INTO ventas (
        id_local,
        id_usuario,
        id_caja,
        id_cliente,
        tipo_comprobante,
        numero_comprobante,
        fecha_venta,
        subtotal,
        descuento,
        motivo_descuento,
        impuesto,
        total,
        forma_pago,
        estado,
        tipo_venta,
        entrada,
        saldo,
        cuotas,
        proveedor_financiamiento,
        estado_sri,
        ambiente,
        establecimiento,
        punto_emision,
        secuencial
      ) VALUES (?, ?, ?, ?, 'FACTURA', ?, ?, ?, ?, ?, ?, ?, ?, 'PAGADA', ?, ?, ?, ?, ?, 'NO_ENVIADA', ?, ?, ?, ?)
    `, [
      id_local,
      id_usuario,
      id_caja,
      id_cliente,
      numero_comprobante,
      fechaVenta,
      subtotal,
      descuentoAplicado,
      motivo_descuento || null,
      impuesto,
      total,
      pagos.length ? pagos[0].forma_pago : "EFECTIVO", // compatibilidad con tu tabla actual
      tipo_venta,
      tipo_venta === "FINANCIADO" ? entradaNum : total,
      saldo,
      cuotasNum,
      proveedor,
      ambienteSri,
      establecimiento,
      punto_emision,
      secuencial
    ]);

    const id_venta = ventaInsert.insertId;

    /* ===============================
       📦 DETALLE + STOCK + IMEI + MOVIMIENTOS
    =============================== */
    for (const item of detalleProcesado) {
      await connection.query(`
        INSERT INTO detalle_venta (
          id_venta,
          id_producto,
          cantidad,
          imei,
          precio_unitario,
          costo_unitario,
          subtotal,
          costo_total,
          ganancia
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
      `, [
        id_venta,
        item.id_producto,
        item.cantidad,
        item.imei,
        item.precio_unitario,
        item.costo_unitario,
        item.subtotal_linea,
        item.costo_total,
        item.ganancia
      ]);

      await connection.query(`
        UPDATE inventario_stock
        SET stock_actual = ?,
            fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_producto = ?
          AND id_local = ?
      `, [
        item.stock_nuevo,
        item.id_producto,
        id_local
      ]);

      if (item.imei) {
        await connection.query(`
          UPDATE inventario_imei
          SET estado = 'vendido'
          WHERE id_producto = ?
            AND id_local = ?
            AND imei1 = ?
            AND estado = 'disponible'
        `, [
          item.id_producto,
          id_local,
          item.imei
        ]);
      }

      await connection.query(`
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
        ) VALUES (?, ?, ?, 'SALIDA', 'VENTA', ?, ?, ?, ?)
      `, [
        item.id_producto,
        id_local,
        id_usuario,
        item.cantidad,
        item.stock_anterior,
        item.stock_nuevo,
        `VENTA-${id_venta}`
      ]);
    }

    /* ===============================
       💳 REGISTRAR PAGOS
    =============================== */
    for (const pago of pagos) {
      const monto = Number(pago.monto || 0);
      const forma_pago = pago.forma_pago;

      if (monto <= 0) continue;

      await connection.query(`
        INSERT INTO ventas_pagos (
          id_venta,
          monto,
          forma_pago
        ) VALUES (?, ?, ?)
      `, [id_venta, monto, forma_pago]);
    }

    await connection.commit();

    res.json({
      ok: true,
      mensaje: "Venta registrada correctamente",
      venta: {
        id_venta,
        numero_comprobante,
        fecha_venta: fechaVenta,
        subtotal,
        descuento: descuentoAplicado,
        impuesto,
        total,
        tipo_venta,
        entrada: tipo_venta === "FINANCIADO" ? entradaNum : total,
        saldo,
        cuotas: cuotasNum,
        proveedor_financiamiento: proveedor,
        estado_sri: "NO_ENVIADA"
      }
    });

  } catch (error) {
    await connection.rollback();
    console.error("❌ crearVenta:", error);

    res.status(500).json({
      ok: false,
      mensaje: error.message || "Error al crear la venta"
    });
  } finally {
    connection.release();
  }
};

exports.listarVentasAdmin = async (req, res) => {
  try {
    await ensureSriTables();
    await ensureVentaAnulacionSchema();
    await ensureDetalleVentaImeiColumn();

    const idLocal = Number(req.user.id_local || 0);
    const buscar = String(req.query?.buscar || "").trim();
    const estado = resolverFiltroEstadoVentas(req.query?.estado || "PAGADA");
    const desde = req.query?.desde ? String(req.query.desde).trim() : null;
    const hasta = req.query?.hasta ? String(req.query.hasta).trim() : null;
    const pageQuery = Number(req.query?.page);
    const limitQuery = Number(req.query?.limit);
    const page = Number.isFinite(pageQuery) && pageQuery > 0
      ? Math.floor(pageQuery)
      : 1;
    const limit = Number.isFinite(limitQuery) && limitQuery > 0
      ? Math.min(50, Math.floor(limitQuery))
      : 20;
    const offset = (page - 1) * limit;

    const filtros = construirFiltrosVentasAdmin({
      idLocal,
      buscar,
      estado,
      desde,
      hasta
    });

    const [summaryRows] = await db.query(
      `
      SELECT
        COUNT(*) AS total_registros,
        COALESCE(SUM(CASE WHEN v.estado = 'PAGADA' THEN 1 ELSE 0 END), 0) AS total_pagadas,
        COALESCE(SUM(CASE WHEN v.estado = 'ANULADA' THEN 1 ELSE 0 END), 0) AS total_anuladas,
        COALESCE(SUM(CASE WHEN v.estado = 'PAGADA' THEN v.total ELSE 0 END), 0) AS monto_pagado,
        COALESCE(SUM(CASE WHEN v.estado = 'ANULADA' THEN v.total ELSE 0 END), 0) AS monto_anulado
      FROM ventas v
      LEFT JOIN clientes c
        ON c.id_cliente = v.id_cliente
      LEFT JOIN usuarios u
        ON u.id_usuario = v.id_usuario
      WHERE ${filtros.sql}
      `,
      filtros.params
    );

    const [rows] = await db.query(
      `
      SELECT
        v.id_venta,
        v.numero_comprobante,
        v.fecha_venta,
        v.total,
        v.estado,
        v.tipo_venta,
        v.forma_pago,
        v.entrada,
        v.saldo,
        v.descuento,
        v.motivo_anulacion,
        v.fecha_anulacion,
        c.nombres AS cliente_nombres,
        c.cedula AS cliente_cedula,
        u.usuario,
        (
          SELECT COUNT(*)
          FROM detalle_venta d
          WHERE d.id_venta = v.id_venta
        ) AS total_items,
        COALESCE((
          SELECT sd.estado
          FROM sri_documentos sd
          WHERE sd.id_venta = v.id_venta
            AND sd.tipo_comprobante = 'FACTURA'
          ORDER BY sd.id_documento_sri DESC
          LIMIT 1
        ), 'SIN_DOCUMENTO') AS estado_documento_sri
      FROM ventas v
      LEFT JOIN clientes c
        ON c.id_cliente = v.id_cliente
      LEFT JOIN usuarios u
        ON u.id_usuario = v.id_usuario
      WHERE ${filtros.sql}
      ORDER BY v.id_venta DESC
      LIMIT ?
      OFFSET ?
      `,
      [...filtros.params, limit, offset]
    );

    const resumen = summaryRows?.[0] || {};
    const totalRegistros = Number(resumen.total_registros || 0);
    const totalPaginas = totalRegistros > 0 ? Math.ceil(totalRegistros / limit) : 1;

    res.json({
      ok: true,
      filtros: {
        buscar,
        estado,
        desde,
        hasta,
        page,
        limit
      },
      resumen: {
        total_registros: totalRegistros,
        total_pagadas: Number(resumen.total_pagadas || 0),
        total_anuladas: Number(resumen.total_anuladas || 0),
        monto_pagado: round2(resumen.monto_pagado || 0),
        monto_anulado: round2(resumen.monto_anulado || 0)
      },
      paginacion: {
        page,
        limit,
        total_registros: totalRegistros,
        total_paginas: totalPaginas,
        has_prev: page > 1,
        has_next: page < totalPaginas
      },
      data: rows.map((row) => ({
        ...row,
        total: round2(row.total || 0),
        entrada: round2(row.entrada || 0),
        saldo: round2(row.saldo || 0),
        descuento: round2(row.descuento || 0),
        total_items: Number(row.total_items || 0),
        puede_anular:
          row.estado === "PAGADA" &&
          row.estado_documento_sri !== "AUTORIZADO"
      }))
    });
  } catch (error) {
    console.error("❌ listarVentasAdmin:", error);
    res.status(error.statusCode || 500).json({
      ok: false,
      mensaje: error.message || "Error al listar ventas"
    });
  }
};

exports.obtenerDetalleVentaAdmin = async (req, res) => {
  try {
    const idVenta = Number(req.params.id || 0);
    const idLocal = Number(req.user.id_local || 0);

    if (!idVenta) {
      return res.status(400).json({
        ok: false,
        mensaje: "Id de venta invalido"
      });
    }

    const venta = await fetchVentaAdminDetalle(db, idVenta, idLocal);

    if (!venta) {
      return res.status(404).json({
        ok: false,
        mensaje: "Venta no encontrada"
      });
    }

    res.json({
      ok: true,
      venta
    });
  } catch (error) {
    console.error("❌ obtenerDetalleVentaAdmin:", error);
    res.status(error.statusCode || 500).json({
      ok: false,
      mensaje: error.message || "Error al consultar el detalle de la venta"
    });
  }
};

exports.anularVentaAdmin = async (req, res) => {
  const connection = await db.getConnection();
  let transactionStarted = false;

  try {
    await ensureSriTables();
    await ensureVentaAnulacionSchema();
    await ensureDetalleVentaImeiColumn();

    const idVenta = Number(req.params.id || 0);
    const idLocal = Number(req.user.id_local || 0);
    const idUsuario = Number(req.user.id_usuario || 0);
    const motivo = String(req.body?.motivo || "").trim().slice(0, 255);

    if (!idVenta) {
      return res.status(400).json({
        ok: false,
        mensaje: "Id de venta invalido"
      });
    }

    if (!motivo) {
      return res.status(400).json({
        ok: false,
        mensaje: "Debes indicar el motivo de la anulacion"
      });
    }

    await connection.beginTransaction();
    transactionStarted = true;

    const [[venta]] = await connection.query(
      `
      SELECT
        v.id_venta,
        v.id_local,
        v.numero_comprobante,
        v.estado,
        v.total,
        COALESCE((
          SELECT sd.estado
          FROM sri_documentos sd
          WHERE sd.id_venta = v.id_venta
            AND sd.tipo_comprobante = 'FACTURA'
          ORDER BY sd.id_documento_sri DESC
          LIMIT 1
        ), 'SIN_DOCUMENTO') AS estado_documento_sri
      FROM ventas v
      WHERE v.id_venta = ?
        AND v.id_local = ?
      LIMIT 1
      FOR UPDATE
      `,
      [idVenta, idLocal]
    );

    if (!venta) {
      throw createHttpError("Venta no encontrada", 404);
    }

    if (venta.estado === "ANULADA") {
      throw createHttpError("La venta ya fue anulada anteriormente", 409);
    }

    if (venta.estado !== "PAGADA") {
      throw createHttpError("Solo se pueden anular ventas en estado PAGADA", 409);
    }

    if (venta.estado_documento_sri === "AUTORIZADO") {
      throw createHttpError(
        "Esta venta ya tiene un documento SRI autorizado. Debes gestionarla con una nota de credito.",
        409
      );
    }

    const [detalle] = await connection.query(
      `
      SELECT
        id_detalle,
        id_producto,
        cantidad,
        imei
      FROM detalle_venta
      WHERE id_venta = ?
      ORDER BY id_detalle ASC
      `,
      [idVenta]
    );

    if (!detalle.length) {
      throw createHttpError("La venta no tiene detalle para restaurar inventario", 409);
    }

    const referencia = `ANULACION-VENTA-${idVenta}`;

    for (const item of detalle) {
      const cantidad = Number(item.cantidad || 0);
      const stock = await asegurarStockProductoVenta(
        connection,
        item.id_producto,
        idLocal
      );
      const stockNuevo = stock.stock_actual + cantidad;

      await connection.query(
        `
        UPDATE inventario_stock
        SET stock_actual = ?,
            fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_stock = ?
        `,
        [stockNuevo, stock.id_stock]
      );

      if (item.imei) {
        const imei = String(item.imei).trim();
        const [[imeiRow]] = await connection.query(
          `
          SELECT id_imei, estado
          FROM inventario_imei
          WHERE id_producto = ?
            AND id_local = ?
            AND (imei1 = ? OR imei2 = ?)
          LIMIT 1
          FOR UPDATE
          `,
          [item.id_producto, idLocal, imei, imei]
        );

        if (!imeiRow) {
          throw createHttpError(
            `No se encontro el IMEI ${imei} para restaurarlo al inventario`,
            409
          );
        }

        if (String(imeiRow.estado || "").toLowerCase() !== "vendido") {
          throw createHttpError(
            `El IMEI ${imei} ya no esta en estado vendido y no se puede revertir automaticamente`,
            409
          );
        }

        await connection.query(
          `
          UPDATE inventario_imei
          SET estado = 'disponible'
          WHERE id_imei = ?
          `,
          [imeiRow.id_imei]
        );
      }

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
          item.id_producto,
          idLocal,
          idUsuario,
          cantidad,
          stock.stock_actual,
          stockNuevo,
          referencia
        ]
      );
    }

    const fechaAnulacion = getCurrentDateTimeEcSql();

    await connection.query(
      `
      UPDATE ventas
      SET estado = 'ANULADA',
          motivo_anulacion = ?,
          fecha_anulacion = ?,
          id_usuario_anulacion = ?
      WHERE id_venta = ?
        AND id_local = ?
      `,
      [motivo, fechaAnulacion, idUsuario, idVenta, idLocal]
    );

    await connection.commit();
    transactionStarted = false;

    const ventaActualizada = await fetchVentaAdminDetalle(connection, idVenta, idLocal);

    res.json({
      ok: true,
      mensaje: "Venta anulada correctamente y el inventario fue restaurado",
      venta: ventaActualizada
    });
  } catch (error) {
    if (transactionStarted) {
      await connection.rollback();
    }

    console.error("❌ anularVentaAdmin:", error);
    res.status(error.statusCode || 500).json({
      ok: false,
      mensaje: error.message || "Error al anular la venta"
    });
  } finally {
    connection.release();
  }
};

exports.buscarProductoPOS = async (req, res) => {
  try {
    const id_local = req.user.id_local;
    const { q } = req.query;

    if (!q) {
      return res.status(400).json({
        ok: false,
        mensaje: "Debe enviar parámetro de búsqueda"
      });
    }

    const busqueda = `%${q}%`;

    const [rows] = await db.query(`
  SELECT 
    p.id_producto,
    p.nombre_producto,
    p.codigo_barras,
    p.sku,
    p.imagen,
    p.precio_unitario,
    p.graba_iva,
    i.stock_actual,

    (
      SELECT GROUP_CONCAT(imei1 SEPARATOR ',')
      FROM inventario_imei 
      WHERE id_producto = p.id_producto 
      AND id_local = ?
      AND estado = 'disponible'
    ) as imeis

  FROM productos p
  INNER JOIN inventario_stock i 
    ON i.id_producto = p.id_producto
   AND i.id_local = ?

  WHERE 
    p.id_local = ?
    AND p.activo = 1
    AND (
      p.codigo_barras = ?
      OR p.sku = ?
      OR p.nombre_producto LIKE ?
    )

  LIMIT 10
`, [
  id_local,   // imeis subquery
  id_local,   // inventario_stock
  id_local,   // productos
  q,
  q,
  busqueda
]);

    res.json({
      ok: true,
      data: rows
    });

  } catch (error) {
    console.error("❌ buscarProductoPOS:", error);
    res.status(500).json({
      ok: false,
      mensaje: "Error al buscar producto"
    });
  }
};

exports.enviarComprobantePdfPorCorreo = async (req, res) => {
  try {
    const idVenta = Number(req.params.id);
    const idLocal = req.user.id_local;

    if (!idVenta) {
      return res.status(400).json({
        ok: false,
        mensaje: "Id de venta invalido"
      });
    }

    const venta = await fetchVentaForComprobante(idVenta, idLocal);

    if (!venta) {
      return res.status(404).json({
        ok: false,
        mensaje: "Venta no encontrada"
      });
    }

    const correoDestino = safeText(
      req.body?.correo_destino || venta.cliente_correo,
      ""
    );

    if (!correoDestino) {
      return res.status(400).json({
        ok: false,
        mensaje: "La venta no tiene correo de destino. Envia correo_destino en el body."
      });
    }

    const pdfBuffer = await generateComprobantePdfBuffer(venta);
    const mailInfo = await sendComprobanteEmail({
      venta,
      correoDestino,
      pdfBuffer,
      asunto: req.body?.asunto,
      mensaje: req.body?.mensaje
    });

    res.json({
      ok: true,
      mensaje: "Comprobante enviado correctamente",
      data: {
        id_venta: venta.id_venta,
        numero_comprobante: venta.numero_comprobante,
        correo_destino: correoDestino,
        message_id: mailInfo.messageId || null
      }
    });
  } catch (error) {
    console.error("❌ enviarComprobantePdfPorCorreo:", error);

    res.status(500).json({
      ok: false,
      mensaje: error.message || "Error al enviar el comprobante"
    });
  }
};
