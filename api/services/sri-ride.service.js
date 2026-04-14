const fs = require("fs");
const path = require("path");
const PDFDocument = require("pdfkit");
const db = require("../db/db");
const comprobanteConfig = require("../config/comprobante");
const { createError } = require("./sri-certificado.service");
const { ensureSriTables, getSriConfig } = require("./sri-config.service");
const { ensureDetalleVentaImeiColumn } = require("./ventas-schema.service");

const UPLOADS_ROOT = path.resolve(__dirname, "../uploads");
const SRI_RIDE_DIR = path.join(UPLOADS_ROOT, "sri-ride", "facturas");

function ensureRideDir() {
  fs.mkdirSync(SRI_RIDE_DIR, { recursive: true });
}

function safeText(value, fallback = "N/A") {
  if (value === null || value === undefined) return fallback;
  const text = String(value).trim();
  return text ? text : fallback;
}

function formatMoney(value) {
  return Number(value || 0).toFixed(2);
}

function formatDateEc(value) {
  if (!value) return "N/A";

  if (typeof value === "string") {
    const mysqlMatch = value.trim().match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T]\d{2}:\d{2}:\d{2})?$/);
    if (mysqlMatch) {
      const [, year, month, day] = mysqlMatch;
      return `${day}/${month}/${year}`;
    }
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return safeText(value);

  return new Intl.DateTimeFormat("es-EC", {
    timeZone: "America/Guayaquil",
    day: "2-digit",
    month: "2-digit",
    year: "numeric"
  }).format(date);
}

function formatDateTimeEc(value) {
  if (!value) return "N/A";

  if (typeof value === "string") {
    const mysqlMatch = value.trim().match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})$/);
    if (mysqlMatch) {
      const [, year, month, day, hour, minute, second] = mysqlMatch;
      return `${day}/${month}/${year}, ${hour}:${minute}:${second}`;
    }
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return safeText(value);

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

function getLegalNotes() {
  return [
    "El cliente declara que los valores entregados y la presente compra no corresponden a ninguna actividad ilegal o ilicita, ni seran destinados a acciones tipificadas por la ley.",
    "La garantia cubre cuando el cliente presente el producto sin golpes, rayones o defectos fisicos.",
    "La garantia cubre exclusivamente linea y software.",
    "Una vez que el producto este facturado no hay devoluciones."
  ];
}

function toUploadUrl(filePath) {
  if (!filePath) return null;
  const relativePath = path.relative(UPLOADS_ROOT, filePath).split(path.sep).join("/");
  return `/api/uploads/${relativePath}`;
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

function buildMailTransport() {
  let nodemailer;

  try {
    nodemailer = require("nodemailer");
  } catch (error) {
    throw createError("Debes instalar manualmente la dependencia: npm install nodemailer");
  }

  const { smtp } = comprobanteConfig;
  const missing = [];

  if (!smtp.host) missing.push("SMTP_HOST");
  if (!smtp.port) missing.push("SMTP_PORT");
  if (!smtp.user) missing.push("SMTP_USER");
  if (!smtp.pass) missing.push("SMTP_PASS");
  if (!smtp.fromEmail) missing.push("SMTP_FROM_EMAIL");

  if (missing.length > 0) {
    throw createError(`Configura manualmente estas variables: ${missing.join(", ")}`);
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

async function getFacturaSriForRide(idVenta) {
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
      sd.id_documento_sri,
      sd.estado AS estado_documento_sri,
      sd.clave_acceso AS sri_clave_acceso,
      sd.ambiente AS sri_ambiente,
      sd.xml_autorizado_path,
      sd.ride_path,
      sd.numero_autorizacion AS sri_numero_autorizacion,
      sd.fecha_autorizacion AS sri_fecha_autorizacion,
      DATE_FORMAT(sd.fecha_autorizacion, '%Y-%m-%d %H:%i:%s') AS sri_fecha_autorizacion_texto
    FROM ventas v
    LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
    LEFT JOIN locales l ON l.id_local = v.id_local
    INNER JOIN sri_documentos sd
      ON sd.id_venta = v.id_venta
     AND sd.tipo_comprobante = 'FACTURA'
    WHERE v.id_venta = ?
    LIMIT 1
  `, [idVenta]);

  if (!venta) {
    throw createError("No se encontro informacion SRI para esta venta", 404);
  }

  const sriConfig = await getSriConfig(venta.id_local);

  if (!sriConfig) {
    throw createError("No se encontró la configuración SRI efectiva para esta venta");
  }

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
    emisor_ruc: sriConfig.ruc,
    emisor_razon_social: sriConfig.razon_social,
    emisor_nombre_comercial: sriConfig.nombre_comercial,
    emisor_dir_matriz: sriConfig.dir_matriz,
    detalle,
    pagos
  };
}

function generateRidePdfBuffer(venta) {
  const logoPath = resolveLogoPath();
  const legalNotes = getLegalNotes();

  return new Promise((resolve, reject) => {
    const doc = new PDFDocument({
      size: "A4",
      margin: 34
    });

    const chunks = [];
    const colors = {
      brand: "#0f3d91",
      brandSoft: "#eff6ff",
      text: "#0f172a",
      muted: "#64748b",
      border: "#cbd5e1",
      panel: "#f8fafc",
      white: "#ffffff",
      success: "#15803d"
    };

    doc.on("data", chunk => chunks.push(chunk));
    doc.on("end", () => resolve(Buffer.concat(chunks)));
    doc.on("error", reject);

    const pageWidth = doc.page.width - doc.page.margins.left - doc.page.margins.right;
    const leftX = doc.page.margins.left;
    let y = doc.page.margins.top;

    const drawLabelValue = (x, currentY, label, value, width, valueFontSize = 10) => {
      doc
        .font("Helvetica-Bold")
        .fontSize(8)
        .fillColor(colors.muted)
        .text(label, x, currentY, { width });

      doc
        .font("Helvetica")
        .fontSize(valueFontSize)
        .fillColor(colors.text)
        .text(safeText(value), x, currentY + 11, { width });
    };

    const drawFieldBlock = (x, startY, label, value, width, options = {}) => {
      const {
        labelFontSize = 8,
        valueFontSize = 9.2,
        labelValueGap = 11,
        gapAfter = 6,
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

    const leftHeaderWidth = 300;
    const rightHeaderWidth = pageWidth - leftHeaderWidth - 16;
    const headerHeight = 170;

    doc
      .roundedRect(leftX, y, leftHeaderWidth, headerHeight, 12)
      .fillAndStroke(colors.white, colors.border);

    if (logoPath) {
      doc.image(logoPath, leftX + 16, y + 16, {
        fit: [95, 40],
        align: "left"
      });
    } else {
      doc
        .font("Helvetica-Bold")
        .fontSize(22)
        .fillColor(colors.brand)
        .text("CONNECT", leftX + 16, y + 24);
    }

    doc
      .font("Helvetica-Bold")
      .fontSize(13.5)
      .fillColor(colors.text)
      .text(safeText(venta.emisor_razon_social || comprobanteConfig.emisor.razonSocial), leftX + 16, y + 64, {
        width: leftHeaderWidth - 32
      });

    doc
      .font("Helvetica")
      .fontSize(9)
      .fillColor(colors.muted)
      .text(`RUC: ${safeText(venta.emisor_ruc || comprobanteConfig.emisor.ruc)}`, leftX + 16, y + 84)
      .text(`Local: ${safeText(venta.nombre_local)}`, leftX + 16, y + 97, {
        width: leftHeaderWidth - 32
      })
      .text(`Matriz: ${safeText(venta.emisor_dir_matriz || venta.local_direccion)}`, leftX + 16, y + 110, {
        width: leftHeaderWidth - 32
      });

    doc
      .roundedRect(leftX + leftHeaderWidth + 16, y, rightHeaderWidth, headerHeight, 12)
      .fillAndStroke(colors.white, colors.border);

    doc
      .font("Helvetica-Bold")
      .fontSize(14.5)
      .fillColor(colors.brand)
      .text("RIDE FACTURA ELECTRONICA", leftX + leftHeaderWidth + 28, y + 16, {
        width: rightHeaderWidth - 56,
        align: "center",
        lineGap: -1
      });

    doc
      .font("Helvetica-Bold")
      .fontSize(11)
      .fillColor(colors.success)
      .text("AUTORIZADA", leftX + leftHeaderWidth + 28, y + 54, {
        width: rightHeaderWidth - 56,
        align: "center"
      });

    let headerInfoY = y + 78;
    headerInfoY = drawFieldBlock(
      leftX + leftHeaderWidth + 24,
      headerInfoY,
      "No. comprobante",
      venta.numero_comprobante,
      rightHeaderWidth - 48,
      { valueFontSize: 9.8, gapAfter: 6 }
    );
    headerInfoY = drawFieldBlock(
      leftX + leftHeaderWidth + 24,
      headerInfoY,
      "No. autorizacion",
      venta.sri_numero_autorizacion,
      rightHeaderWidth - 48,
      { valueFontSize: 8.1, gapAfter: 6 }
    );
    drawFieldBlock(
      leftX + leftHeaderWidth + 24,
      headerInfoY,
      "Fecha autorizacion",
      formatDateTimeEc(venta.sri_fecha_autorizacion_texto || venta.sri_fecha_autorizacion),
      rightHeaderWidth - 48,
      { valueFontSize: 8.8, gapAfter: 0 }
    );

    y += headerHeight + 16;

    const topInfoWidth = (pageWidth - 16) / 2;
    const topInfoHeight = 136;

    doc
      .roundedRect(leftX, y, topInfoWidth, topInfoHeight, 12)
      .fillAndStroke(colors.panel, colors.border);

    doc
      .font("Helvetica-Bold")
      .fontSize(10)
      .fillColor(colors.text)
      .text("Datos del comprador", leftX + 14, y + 12);

    drawLabelValue(leftX + 14, y + 34, "Cliente", venta.cliente_nombres, topInfoWidth - 28);
    drawLabelValue(leftX + 14, y + 58, "Cedula / RUC", venta.cliente_cedula, topInfoWidth - 28);
    drawLabelValue(leftX + 14, y + 82, "Correo", venta.cliente_correo, topInfoWidth - 28, 8.6);
    drawLabelValue(leftX + 14, y + 106, "Direccion", venta.cliente_direccion, topInfoWidth - 28, 8.6);

    doc
      .roundedRect(leftX + topInfoWidth + 16, y, topInfoWidth, topInfoHeight, 12)
      .fillAndStroke(colors.panel, colors.border);

    doc
      .font("Helvetica-Bold")
      .fontSize(10)
      .fillColor(colors.text)
      .text("Datos tributarios", leftX + topInfoWidth + 30, y + 12);

    drawLabelValue(leftX + topInfoWidth + 30, y + 34, "Clave de acceso", venta.sri_clave_acceso, topInfoWidth - 44, 7.2);
    drawLabelValue(leftX + topInfoWidth + 30, y + 58, "Ambiente", venta.sri_ambiente, topInfoWidth - 44);
    drawLabelValue(leftX + topInfoWidth + 30, y + 82, "Fecha emision", formatDateEc(venta.fecha_venta), topInfoWidth - 44);
    drawLabelValue(leftX + topInfoWidth + 30, y + 106, "Telefono", venta.cliente_telefono || venta.local_telefono, topInfoWidth - 44);

    y += topInfoHeight + 18;

    const columns = [
      { key: "codigo", label: "COD.", width: 72 },
      { key: "descripcion", label: "DESCRIPCION", width: 196 },
      { key: "cantidad", label: "CANT.", width: 44 },
      { key: "precio", label: "P. UNIT", width: 64 },
      { key: "subtotal", label: "SUBTOTAL", width: 66 },
      { key: "total", label: "TOTAL", width: 68 }
    ];

    const drawTableHeader = (headerY) => {
      let currentX = leftX;

      columns.forEach((column) => {
        doc
          .roundedRect(currentX, headerY, column.width, 24, 5)
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

    venta.detalle.forEach((item) => {
      const descripcion = item.imei
        ? `${safeText(item.nombre_producto)}\nIMEI: ${safeText(item.imei, "")}`
        : safeText(item.nombre_producto);
      const descripcionColumn = columns.find((column) => column.key === "descripcion");
      const descripcionHeight = doc.heightOfString(descripcion, {
        width: descripcionColumn.width - 8,
        lineGap: 1
      });
      const rowHeight = Math.max(24, Math.ceil(descripcionHeight) + 10);

      if (y + rowHeight > 690) {
        doc.addPage();
        y = doc.page.margins.top;
        drawTableHeader(y);
        y += 28;
      }

      const cantidad = Number(item.cantidad || 0);
      const precioUnitario = Number(item.precio_unitario || 0);
      const subtotal = Number(item.subtotal || 0);
      const total = cantidad * precioUnitario;
      const row = {
        codigo: safeText(item.codigo_barras || item.sku || item.id_producto, "-"),
        descripcion,
        cantidad: cantidad.toFixed(2),
        precio: formatMoney(precioUnitario),
        subtotal: formatMoney(subtotal),
        total: formatMoney(total)
      };

      let currentX = leftX;

      columns.forEach((column) => {
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

    const leftBottomWidth = 292;
    const rightBottomWidth = pageWidth - leftBottomWidth - 16;
    const infoBoxHeight = 112;

    doc
      .roundedRect(leftX, y, leftBottomWidth, infoBoxHeight, 12)
      .fillAndStroke(colors.panel, colors.border);

    doc
      .font("Helvetica-Bold")
      .fontSize(10)
      .fillColor(colors.text)
      .text("Informacion adicional", leftX + 16, y + 14);

    const pagosLabel = venta.pagos.length
      ? venta.pagos.map((pago) => `${pago.forma_pago}: $${formatMoney(pago.monto)}`).join(" | ")
      : safeText(venta.forma_pago);

    drawLabelValue(leftX + 16, y + 38, "Pagos", pagosLabel, leftBottomWidth - 32, 8.5);
    drawLabelValue(leftX + 16, y + 64, "Correo envio", venta.cliente_correo || comprobanteConfig.smtp.fromEmail, leftBottomWidth - 32, 8.5);
    drawLabelValue(leftX + 16, y + 90, "Local", venta.nombre_local, leftBottomWidth - 32, 8.8);

    doc
      .roundedRect(leftX + leftBottomWidth + 16, y, rightBottomWidth, infoBoxHeight, 12)
      .fillAndStroke(colors.white, colors.border);

    doc
      .font("Helvetica-Bold")
      .fontSize(11)
      .fillColor(colors.text)
      .text("Resumen", leftX + leftBottomWidth + 30, y + 14);

    const totals = [
      ["Subtotal", formatMoney(venta.subtotal)],
      ["Descuento", formatMoney(venta.descuento)],
      ["IVA", formatMoney(venta.impuesto)],
      ["Total", formatMoney(venta.total)]
    ];

    let totalsY = y + 38;
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

    const rideNoteText = "Este RIDE corresponde a una factura electronica autorizada por el SRI. Conserva este PDF junto con el XML autorizado para respaldo tributario.";
    const legalNotesText = legalNotes
      .map((note, index) => `${index + 1}. ${note}`)
      .join("\n");
    const noteTextY = y + 28;
    const noteTextWidth = pageWidth - 32;
    const noteTextHeight = doc.heightOfString(`${rideNoteText}\n${legalNotesText}`, {
      width: noteTextWidth,
      lineGap: 2
    });
    const noteBoxHeight = Math.max(86, noteTextHeight + 42);

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
      .fontSize(8.8)
      .fillColor(colors.text)
      .text(
        `${rideNoteText}\n${legalNotesText}`,
        leftX + 16,
        noteTextY,
        {
          width: noteTextWidth,
          lineGap: 2
        }
      );

    doc.end();
  });
}

async function generarRideFacturaSriDesdeVenta({ id_venta, user = null }) {
  await ensureSriTables();
  ensureRideDir();

  const idVenta = Number(id_venta || 0);
  if (!idVenta) {
    throw createError("Debes indicar un id_venta válido");
  }

  const venta = await getFacturaSriForRide(idVenta);

  if (user && Array.isArray(user.roles) && !user.roles.includes("ADMIN")) {
    if (Number(user.id_local || 0) !== Number(venta.id_local || 0)) {
      throw createError("No puedes generar el RIDE de una venta de otro local", 403);
    }
  }

  if (venta.estado_documento_sri !== "AUTORIZADO") {
    throw createError("El documento SRI todavía no está autorizado");
  }

  if (!venta.xml_autorizado_path || !fs.existsSync(venta.xml_autorizado_path)) {
    throw createError("No existe el XML autorizado para esta venta");
  }

  const pdfBuffer = await generateRidePdfBuffer(venta);
  const rideFilePath = path.join(
    SRI_RIDE_DIR,
    `ride_factura_${idVenta}_${venta.sri_clave_acceso}.pdf`
  );

  fs.writeFileSync(rideFilePath, pdfBuffer);

  await db.query(`
    UPDATE sri_documentos SET
      ride_path = ?
    WHERE id_documento_sri = ?
  `, [rideFilePath, venta.id_documento_sri]);

  return {
    id_documento_sri: venta.id_documento_sri,
    id_venta: venta.id_venta,
    id_local: venta.id_local,
    estado: "AUTORIZADO",
    numero_comprobante: venta.numero_comprobante,
    clave_acceso: venta.sri_clave_acceso,
    numero_autorizacion: venta.sri_numero_autorizacion,
    ride_path: rideFilePath,
    ride_url: toUploadUrl(rideFilePath)
  };
}

async function enviarFacturaSriPorCorreoDesdeVenta({
  id_venta,
  user = null,
  correo_destino = null,
  asunto = null,
  mensaje = null
}) {
  await ensureSriTables();
  ensureRideDir();

  const rideInfo = await generarRideFacturaSriDesdeVenta({
    id_venta,
    user
  });

  const venta = await getFacturaSriForRide(Number(id_venta));

  if (user && Array.isArray(user.roles) && !user.roles.includes("ADMIN")) {
    if (Number(user.id_local || 0) !== Number(venta.id_local || 0)) {
      throw createError("No puedes enviar el RIDE de una venta de otro local", 403);
    }
  }

  const correoDestino = safeText(correo_destino || venta.cliente_correo, "");

  if (!correoDestino) {
    throw createError("La venta no tiene correo de destino. Envia correo_destino en el body.");
  }

  const transporter = buildMailTransport();
  const pdfBuffer = fs.readFileSync(rideInfo.ride_path);
  const xmlBuffer = fs.readFileSync(venta.xml_autorizado_path);
  const subject = asunto || `Factura electronica ${safeText(venta.numero_comprobante, venta.id_venta)}`;
  const text = mensaje || `Adjuntamos el RIDE y el XML autorizado de la factura ${safeText(venta.numero_comprobante, venta.id_venta)}.`;
  const legalNotes = getLegalNotes();
  const textWithLegalNotes = `${text}\n\nNotas y condiciones:\n${legalNotes
    .map((note, index) => `${index + 1}. ${note}`)
    .join("\n")}`;
  const html = `
    <div style="font-family: Arial, sans-serif; background:#f8fafc; padding:24px;">
      <div style="max-width:680px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">
        <div style="background:#0f3d91; color:#ffffff; padding:20px 24px;">
          <div style="font-size:20px; font-weight:700;">CONNECT</div>
          <div style="font-size:13px; opacity:.9;">Factura electronica autorizada por el SRI</div>
        </div>
        <div style="padding:24px; color:#0f172a;">
          <p style="margin:0 0 12px;">Hola,</p>
          <p style="margin:0 0 12px;">${text}</p>
          <p style="margin:0 0 8px;">No. comprobante: <strong>${safeText(venta.numero_comprobante)}</strong></p>
          <p style="margin:0 0 8px;">No. autorizacion: <strong>${safeText(venta.sri_numero_autorizacion)}</strong></p>
          <p style="margin:0 0 12px;">Se adjuntan el archivo PDF RIDE y el XML autorizado.</p>
          <div style="margin:16px 0 12px; padding:16px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px;">
            <div style="font-size:13px; font-weight:700; margin:0 0 8px; color:#0f3d91;">Notas y condiciones</div>
            <ol style="margin:0; padding-left:18px; color:#0f172a;">
              ${legalNotes.map((note) => `<li style="margin-bottom:8px;">${note}</li>`).join("")}
            </ol>
          </div>
          <p style="margin:0;">Gracias por su compra.</p>
        </div>
      </div>
    </div>
  `;

  const mailInfo = await transporter.sendMail({
    from: `"${comprobanteConfig.smtp.fromName}" <${comprobanteConfig.smtp.fromEmail}>`,
    to: correoDestino,
    subject,
    text: textWithLegalNotes,
    html,
    attachments: [
      {
        filename: `ride-${safeText(venta.numero_comprobante, venta.id_venta).replace(/[^a-zA-Z0-9-_]/g, "_")}.pdf`,
        content: pdfBuffer,
        contentType: "application/pdf"
      },
      {
        filename: `factura-${safeText(venta.numero_comprobante, venta.id_venta).replace(/[^a-zA-Z0-9-_]/g, "_")}.xml`,
        content: xmlBuffer,
        contentType: "application/xml"
      }
    ]
  });

  await db.query(`
    UPDATE sri_documentos SET
      respuesta_sri_json = JSON_SET(
        COALESCE(respuesta_sri_json, '{}'),
        '$.email',
        JSON_OBJECT(
          'enviado_en', ?,
          'correo_destino', ?,
          'message_id', ?
        )
      )
    WHERE id_documento_sri = ?
  `, [
    new Date().toISOString(),
    correoDestino,
    mailInfo.messageId || null,
    venta.id_documento_sri
  ]);

  return {
    id_documento_sri: venta.id_documento_sri,
    id_venta: venta.id_venta,
    id_local: venta.id_local,
    estado: "AUTORIZADO",
    numero_comprobante: venta.numero_comprobante,
    numero_autorizacion: venta.sri_numero_autorizacion,
    correo_destino: correoDestino,
    ride_path: rideInfo.ride_path,
    ride_url: rideInfo.ride_url,
    xml_autorizado_path: venta.xml_autorizado_path,
    xml_autorizado_url: toUploadUrl(venta.xml_autorizado_path),
    message_id: mailInfo.messageId || null
  };
}

module.exports = {
  generarRideFacturaSriDesdeVenta,
  enviarFacturaSriPorCorreoDesdeVenta
};
