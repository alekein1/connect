const fs = require("fs");
const path = require("path");
const db = require("../db/db");
const { createError } = require("./sri-certificado.service");
const { ensureSriTables, getSriConfig } = require("./sri-config.service");

const UPLOADS_ROOT = path.resolve(__dirname, "../uploads");
const SRI_XML_DIR = path.join(UPLOADS_ROOT, "sri-xml", "facturas");

function round2(value) {
  return Math.round((Number(value || 0) + Number.EPSILON) * 100) / 100;
}

function formatMoney(value) {
  return round2(value).toFixed(2);
}

function padLeft(value, length) {
  return String(value || "").padStart(length, "0");
}

function digitsOnly(value) {
  return String(value || "").replace(/\D/g, "");
}

function safeText(value, fallback = "") {
  if (value === null || value === undefined) return fallback;
  const text = String(value).trim();
  return text || fallback;
}

function xmlEscape(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&apos;");
}

function formatDateEc(value) {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    throw createError("La venta no tiene una fecha válida para generar la factura SRI");
  }

  return new Intl.DateTimeFormat("es-EC", {
    timeZone: "America/Guayaquil",
    day: "2-digit",
    month: "2-digit",
    year: "numeric"
  }).format(date);
}

function formatDateForAccessKey(value) {
  return formatDateEc(value).replace(/\//g, "");
}

function getAmbienteCodigo(value) {
  return String(value).toUpperCase() === "PRODUCCION" ? "2" : "1";
}

function modulo11(base48) {
  let factor = 2;
  let total = 0;

  for (let index = base48.length - 1; index >= 0; index -= 1) {
    total += Number(base48[index]) * factor;
    factor = factor === 7 ? 2 : factor + 1;
  }

  const residue = total % 11;
  const digit = 11 - residue;

  if (digit === 11) return 0;
  if (digit === 10) return 1;
  return digit;
}

function buildCodigoNumerico(venta) {
  const ventaId = padLeft(Number(venta.id_venta || 0) % 100000, 5);
  const localId = padLeft(Number(venta.id_local || 0) % 100, 2);
  const secTail = padLeft(String(venta.secuencial || "").slice(-1), 1);
  return `${ventaId}${localId}${secTail}`;
}

function ensureXmlDir() {
  fs.mkdirSync(SRI_XML_DIR, { recursive: true });
}

function toPublicUploadPath(filePath) {
  const absolutePath = path.resolve(filePath);

  if (!absolutePath.startsWith(UPLOADS_ROOT)) {
    return null;
  }

  const relativePath = path.relative(UPLOADS_ROOT, absolutePath).split(path.sep).join("/");
  return `/api/uploads/${relativePath}`;
}

async function getVentaBase(idVenta) {
  const [[venta]] = await db.query(`
    SELECT
      v.*,
      c.nombres AS cliente_nombres,
      c.cedula AS cliente_cedula,
      c.correo AS cliente_correo,
      c.direccion AS cliente_direccion,
      c.telefono AS cliente_telefono,
      l.nombre_local,
      l.direccion AS local_direccion,
      l.telefono AS local_telefono
    FROM ventas v
    INNER JOIN locales l
      ON l.id_local = v.id_local
    LEFT JOIN clientes c
      ON c.id_cliente = v.id_cliente
    WHERE v.id_venta = ?
    LIMIT 1
  `, [idVenta]);

  if (!venta) {
    throw createError("La venta indicada no existe");
  }

  return venta;
}

async function getVentaDetalles(idVenta) {
  const [rows] = await db.query(`
    SELECT
      dv.id_detalle,
      dv.id_producto,
      dv.cantidad,
      dv.precio_unitario,
      dv.subtotal,
      p.nombre_producto,
      p.graba_iva,
      p.sku,
      p.codigo_barras,
      p.marca,
      p.capacidad
    FROM detalle_venta dv
    INNER JOIN productos p
      ON p.id_producto = dv.id_producto
    WHERE dv.id_venta = ?
    ORDER BY dv.id_detalle ASC
  `, [idVenta]);

  return rows;
}

async function getVentaPagos(idVenta) {
  const [rows] = await db.query(`
    SELECT
      id_pago,
      monto,
      forma_pago,
      fecha_pago
    FROM ventas_pagos
    WHERE id_venta = ?
    ORDER BY id_pago ASC
  `, [idVenta]);

  return rows;
}

async function getExistingSriDocument(idVenta) {
  const [[row]] = await db.query(`
    SELECT *
    FROM sri_documentos
    WHERE id_venta = ?
      AND tipo_comprobante = 'FACTURA'
    LIMIT 1
  `, [idVenta]);

  return row || null;
}

function inferComprador(venta) {
  const rawIdentificacion = digitsOnly(venta.cliente_cedula);
  const razonSocial = safeText(venta.cliente_nombres, "CONSUMIDOR FINAL");

  if (!rawIdentificacion || /^9+$/.test(rawIdentificacion)) {
    return {
      tipoIdentificacionComprador: "07",
      identificacionComprador: "9999999999999",
      razonSocialComprador: "CONSUMIDOR FINAL",
      direccionComprador: safeText(venta.cliente_direccion, null),
      correo: safeText(venta.cliente_correo, null),
      telefono: safeText(venta.cliente_telefono, null)
    };
  }

  if (rawIdentificacion.length === 13) {
    return {
      tipoIdentificacionComprador: "04",
      identificacionComprador: rawIdentificacion,
      razonSocialComprador: razonSocial,
      direccionComprador: safeText(venta.cliente_direccion, null),
      correo: safeText(venta.cliente_correo, null),
      telefono: safeText(venta.cliente_telefono, null)
    };
  }

  if (rawIdentificacion.length === 10) {
    return {
      tipoIdentificacionComprador: "05",
      identificacionComprador: rawIdentificacion,
      razonSocialComprador: razonSocial,
      direccionComprador: safeText(venta.cliente_direccion, null),
      correo: safeText(venta.cliente_correo, null),
      telefono: safeText(venta.cliente_telefono, null)
    };
  }

  return {
    tipoIdentificacionComprador: "06",
    identificacionComprador: safeText(venta.cliente_cedula, rawIdentificacion),
    razonSocialComprador: razonSocial,
    direccionComprador: safeText(venta.cliente_direccion, null),
    correo: safeText(venta.cliente_correo, null),
    telefono: safeText(venta.cliente_telefono, null)
  };
}

function mapFormaPagoSri(formaPago) {
  switch (String(formaPago || "").toUpperCase()) {
    case "EFECTIVO":
      return "01";
    case "TARJETA":
      return "19";
    case "TRANSFERENCIA":
      return "20";
    default:
      return "20";
  }
}

function getIvaMeta(grabaIva) {
  if (Number(grabaIva || 0) === 1) {
    return {
      codigo: "2",
      codigoPorcentaje: "4",
      tarifa: 15
    };
  }

  return {
    codigo: "2",
    codigoPorcentaje: "0",
    tarifa: 0
  };
}

function buildLineasFactura(detalles, venta) {
  if (!Array.isArray(detalles) || detalles.length === 0) {
    throw createError("La venta no tiene detalle y no se puede construir el XML");
  }

  const detalleBase = detalles.map((item) => {
    const cantidad = Number(item.cantidad || 0);
    const baseOriginal = round2(item.subtotal);
    const ivaMeta = getIvaMeta(item.graba_iva);
    const grossOriginal = ivaMeta.tarifa > 0
      ? round2(baseOriginal * (1 + ivaMeta.tarifa / 100))
      : baseOriginal;

    return {
      id_detalle: item.id_detalle,
      id_producto: item.id_producto,
      cantidad,
      baseOriginal,
      grossOriginal,
      precioUnitarioSinImpuesto: cantidad > 0
        ? round2(baseOriginal / cantidad)
        : 0,
      descripcion: safeText(item.nombre_producto, `PRODUCTO ${item.id_producto}`),
      codigoPrincipal: safeText(item.sku, String(item.id_producto)),
      codigoAuxiliar: safeText(item.codigo_barras, safeText(item.sku, null)),
      grabaIva: ivaMeta.tarifa > 0,
      ivaMeta
    };
  });

  const totalGravadoOriginal = round2(
    detalleBase
      .filter((item) => item.grabaIva)
      .reduce((acc, item) => acc + item.grossOriginal, 0)
  );
  const totalNoGravadoOriginal = round2(
    detalleBase
      .filter((item) => !item.grabaIva)
      .reduce((acc, item) => acc + item.grossOriginal, 0)
  );
  const totalBrutoOriginal = round2(totalGravadoOriginal + totalNoGravadoOriginal);
  const descuentoAplicado = round2(venta.descuento);
  const descuentoGravado = totalBrutoOriginal > 0
    ? round2(descuentoAplicado * (totalGravadoOriginal / totalBrutoOriginal))
    : 0;
  const descuentoNoGravado = round2(descuentoAplicado - descuentoGravado);
  const totalGravadoConDescuento = round2(Math.max(0, totalGravadoOriginal - descuentoGravado));
  const totalNoGravadoConDescuento = round2(Math.max(0, totalNoGravadoOriginal - descuentoNoGravado));
  const baseGravadaConDescuento = totalGravadoConDescuento > 0
    ? round2(totalGravadoConDescuento / 1.15)
    : 0;
  const impuestoTotal = totalGravadoConDescuento > 0
    ? round2(totalGravadoConDescuento - baseGravadaConDescuento)
    : 0;
  const subtotalCalculado = round2(baseGravadaConDescuento + totalNoGravadoConDescuento);
  const totalCalculado = round2(subtotalCalculado + impuestoTotal);

  const ventaSubtotal = round2(venta.subtotal);
  const ventaImpuesto = round2(venta.impuesto);
  const ventaTotal = round2(venta.total);

  const mismatches = [];

  if (Math.abs(subtotalCalculado - ventaSubtotal) > 0.02) {
    mismatches.push(`subtotal esperado ${formatMoney(subtotalCalculado)} vs venta ${formatMoney(ventaSubtotal)}`);
  }

  if (Math.abs(impuestoTotal - ventaImpuesto) > 0.02) {
    mismatches.push(`impuesto esperado ${formatMoney(impuestoTotal)} vs venta ${formatMoney(ventaImpuesto)}`);
  }

  if (Math.abs(totalCalculado - ventaTotal) > 0.02) {
    mismatches.push(`total esperado ${formatMoney(totalCalculado)} vs venta ${formatMoney(ventaTotal)}`);
  }

  if (mismatches.length > 0) {
    throw createError(`La venta no cuadra con su detalle para XML SRI: ${mismatches.join(" | ")}`);
  }

  const grupos = [
    {
      key: "gravado",
      items: detalleBase.filter((item) => item.grabaIva),
      targetBase: baseGravadaConDescuento,
      targetImpuesto: impuestoTotal
    },
    {
      key: "no_gravado",
      items: detalleBase.filter((item) => !item.grabaIva),
      targetBase: totalNoGravadoConDescuento,
      targetImpuesto: 0
    }
  ];

  const lineasMap = new Map();

  for (const grupo of grupos) {
    if (!grupo.items.length) continue;

    const totalBaseOriginalGrupo = round2(
      grupo.items.reduce((acc, item) => acc + item.baseOriginal, 0)
    );

    let acumuladoBase = 0;
    let acumuladoImpuesto = 0;

    grupo.items.forEach((item, index) => {
      const isLast = index === grupo.items.length - 1;

      let baseNeta;
      if (isLast) {
        baseNeta = round2(grupo.targetBase - acumuladoBase);
      } else if (totalBaseOriginalGrupo > 0) {
        baseNeta = round2(grupo.targetBase * (item.baseOriginal / totalBaseOriginalGrupo));
      } else {
        baseNeta = 0;
      }

      let impuestoLinea = 0;
      if (item.grabaIva) {
        if (isLast) {
          impuestoLinea = round2(grupo.targetImpuesto - acumuladoImpuesto);
        } else {
          impuestoLinea = round2(baseNeta * 0.15);
        }
      }

      acumuladoBase = round2(acumuladoBase + baseNeta);
      acumuladoImpuesto = round2(acumuladoImpuesto + impuestoLinea);

      const descuentoLinea = round2(item.baseOriginal - baseNeta);
      const totalSinImpuesto = round2(baseNeta);

      lineasMap.set(item.id_detalle, {
        ...item,
        descuentoLinea,
        totalSinImpuesto,
        impuestoLinea,
        grossNeto: round2(totalSinImpuesto + impuestoLinea)
      });
    });
  }

  const lineas = detalleBase.map((item) => lineasMap.get(item.id_detalle));

  const totalSinImpuestos = round2(
    lineas.reduce((acc, item) => acc + item.totalSinImpuesto, 0)
  );
  const totalDescuento = round2(
    lineas.reduce((acc, item) => acc + item.descuentoLinea, 0)
  );
  const totalImpuestoXml = round2(
    lineas.reduce((acc, item) => acc + item.impuestoLinea, 0)
  );
  const importeTotal = round2(totalSinImpuestos + totalImpuestoXml);

  if (Math.abs(importeTotal - ventaTotal) > 0.02) {
    throw createError(
      `El XML calculado no coincide con el total de la venta (${formatMoney(importeTotal)} vs ${formatMoney(ventaTotal)})`
    );
  }

  const totalConImpuestos = [];
  const baseNoGravada = round2(
    lineas
      .filter((item) => !item.grabaIva)
      .reduce((acc, item) => acc + item.totalSinImpuesto, 0)
  );
  const baseGravada = round2(
    lineas
      .filter((item) => item.grabaIva)
      .reduce((acc, item) => acc + item.totalSinImpuesto, 0)
  );

  if (baseNoGravada > 0) {
    totalConImpuestos.push({
      codigo: "2",
      codigoPorcentaje: "0",
      tarifa: 0,
      baseImponible: baseNoGravada,
      valor: 0
    });
  }

  if (baseGravada > 0) {
    totalConImpuestos.push({
      codigo: "2",
      codigoPorcentaje: "4",
      tarifa: 15,
      baseImponible: baseGravada,
      valor: totalImpuestoXml
    });
  }

  return {
    lineas,
    resumen: {
      totalSinImpuestos,
      totalDescuento,
      totalConImpuestos,
      totalImpuesto: totalImpuestoXml,
      importeTotal
    }
  };
}

function buildPagosSri(venta, pagos) {
  const pagosValidos = (pagos || [])
    .map((item) => ({
      formaPago: mapFormaPagoSri(item.forma_pago),
      total: round2(item.monto)
    }))
    .filter((item) => item.total > 0);

  const totalVenta = round2(venta.total);
  const totalPagosRegistrados = round2(
    pagosValidos.reduce((acc, item) => acc + item.total, 0)
  );

  if (String(venta.tipo_venta || "CONTADO").toUpperCase() === "FINANCIADO") {
    const pagosSri = [...pagosValidos];
    const saldoPendiente = round2(totalVenta - totalPagosRegistrados);

    if (saldoPendiente > 0) {
      pagosSri.push({
        formaPago: "20",
        total: saldoPendiente,
        plazo: Number(venta.cuotas || 1) || 1,
        unidadTiempo: "MESES"
      });
    }

    if (!pagosSri.length) {
      pagosSri.push({
        formaPago: "20",
        total: totalVenta,
        plazo: Number(venta.cuotas || 1) || 1,
        unidadTiempo: "MESES"
      });
    }

    return pagosSri;
  }

  if (!pagosValidos.length) {
    return [{
      formaPago: mapFormaPagoSri(venta.forma_pago),
      total: totalVenta
    }];
  }

  if (Math.abs(totalPagosRegistrados - totalVenta) > 0.02) {
    return [{
      formaPago: mapFormaPagoSri(venta.forma_pago),
      total: totalVenta
    }];
  }

  return pagosValidos;
}

function buildClaveAcceso({ venta, config, existingClaveAcceso }) {
  const fecha = formatDateForAccessKey(venta.fecha_venta);
  const codDoc = "01";
  const ruc = digitsOnly(config.ruc);
  const ambiente = getAmbienteCodigo(config.ambiente);
  const establecimiento = padLeft(venta.establecimiento || config.establecimiento || "001", 3);
  const puntoEmision = padLeft(venta.punto_emision || config.punto_emision || "001", 3);
  const secuencial = padLeft(venta.secuencial || venta.id_venta, 9);
  const codigoNumerico = buildCodigoNumerico(venta);
  const tipoEmision = "1";
  const base48 = `${fecha}${codDoc}${ruc}${ambiente}${establecimiento}${puntoEmision}${secuencial}${codigoNumerico}${tipoEmision}`;
  const generatedClaveAcceso = `${base48}${modulo11(base48)}`;

  if (existingClaveAcceso && /^\d{49}$/.test(existingClaveAcceso)) {
    return existingClaveAcceso === generatedClaveAcceso
      ? existingClaveAcceso
      : generatedClaveAcceso;
  }

  return generatedClaveAcceso;
}

function buildFacturaXml({ venta, config, comprador, facturaData, pagosSri, claveAcceso }) {
  const { lineas, resumen } = facturaData;
  const ambienteCodigo = getAmbienteCodigo(config.ambiente);
  const establecimiento = padLeft(venta.establecimiento || config.establecimiento || "001", 3);
  const puntoEmision = padLeft(venta.punto_emision || config.punto_emision || "001", 3);
  const secuencial = padLeft(venta.secuencial || venta.id_venta, 9);
  const fechaEmision = formatDateEc(venta.fecha_venta);
  const contribuyenteEspecial = (() => {
    const value = safeText(config.contribuyente_especial, "");
    if (!value) return null;
    if (["NO", "N/A", "NINGUNO"].includes(value.toUpperCase())) return null;
    return value;
  })();

  const infoTributaria = [
    `    <ambiente>${ambienteCodigo}</ambiente>`,
    `    <tipoEmision>1</tipoEmision>`,
    `    <razonSocial>${xmlEscape(config.razon_social)}</razonSocial>`,
    config.nombre_comercial
      ? `    <nombreComercial>${xmlEscape(config.nombre_comercial)}</nombreComercial>`
      : null,
    `    <ruc>${xmlEscape(digitsOnly(config.ruc))}</ruc>`,
    `    <claveAcceso>${claveAcceso}</claveAcceso>`,
    `    <codDoc>01</codDoc>`,
    `    <estab>${establecimiento}</estab>`,
    `    <ptoEmi>${puntoEmision}</ptoEmi>`,
    `    <secuencial>${secuencial}</secuencial>`,
    `    <dirMatriz>${xmlEscape(config.dir_matriz)}</dirMatriz>`
  ].filter(Boolean).join("\n");

  const infoFactura = [
    `    <fechaEmision>${fechaEmision}</fechaEmision>`,
    `    <dirEstablecimiento>${xmlEscape(config.dir_establecimiento)}</dirEstablecimiento>`,
    contribuyenteEspecial
      ? `    <contribuyenteEspecial>${xmlEscape(contribuyenteEspecial)}</contribuyenteEspecial>`
      : null,
    `    <obligadoContabilidad>${xmlEscape(config.obligado_contabilidad || "NO")}</obligadoContabilidad>`,
    `    <tipoIdentificacionComprador>${comprador.tipoIdentificacionComprador}</tipoIdentificacionComprador>`,
    `    <razonSocialComprador>${xmlEscape(comprador.razonSocialComprador)}</razonSocialComprador>`,
    `    <identificacionComprador>${xmlEscape(comprador.identificacionComprador)}</identificacionComprador>`,
    comprador.direccionComprador
      ? `    <direccionComprador>${xmlEscape(comprador.direccionComprador)}</direccionComprador>`
      : null,
    `    <totalSinImpuestos>${formatMoney(resumen.totalSinImpuestos)}</totalSinImpuestos>`,
    `    <totalDescuento>${formatMoney(resumen.totalDescuento)}</totalDescuento>`,
    "    <totalConImpuestos>",
    ...resumen.totalConImpuestos.map((item) => [
      "      <totalImpuesto>",
      `        <codigo>${item.codigo}</codigo>`,
      `        <codigoPorcentaje>${item.codigoPorcentaje}</codigoPorcentaje>`,
      `        <baseImponible>${formatMoney(item.baseImponible)}</baseImponible>`,
      `        <valor>${formatMoney(item.valor)}</valor>`,
      "      </totalImpuesto>"
    ].join("\n")),
    "    </totalConImpuestos>",
    "    <propina>0.00</propina>",
    `    <importeTotal>${formatMoney(resumen.importeTotal)}</importeTotal>`,
    "    <moneda>DOLAR</moneda>",
    "    <pagos>",
    ...pagosSri.map((item) => [
      "      <pago>",
      `        <formaPago>${item.formaPago}</formaPago>`,
      `        <total>${formatMoney(item.total)}</total>`,
      item.plazo ? `        <plazo>${item.plazo}</plazo>` : null,
      item.unidadTiempo ? `        <unidadTiempo>${xmlEscape(item.unidadTiempo)}</unidadTiempo>` : null,
      "      </pago>"
    ].filter(Boolean).join("\n")),
    "    </pagos>"
  ].filter(Boolean).join("\n");

  const detalles = [
    "  <detalles>",
    ...lineas.map((item) => {
      const detalleLines = [
        "    <detalle>",
        `      <codigoPrincipal>${xmlEscape(item.codigoPrincipal)}</codigoPrincipal>`,
        item.codigoAuxiliar && item.codigoAuxiliar !== item.codigoPrincipal
          ? `      <codigoAuxiliar>${xmlEscape(item.codigoAuxiliar)}</codigoAuxiliar>`
          : null,
        `      <descripcion>${xmlEscape(item.descripcion)}</descripcion>`,
        `      <cantidad>${formatMoney(item.cantidad)}</cantidad>`,
        `      <precioUnitario>${formatMoney(item.precioUnitarioSinImpuesto)}</precioUnitario>`,
        `      <descuento>${formatMoney(item.descuentoLinea)}</descuento>`,
        `      <precioTotalSinImpuesto>${formatMoney(item.totalSinImpuesto)}</precioTotalSinImpuesto>`,
        "      <impuestos>",
        "        <impuesto>",
        `          <codigo>${item.ivaMeta.codigo}</codigo>`,
        `          <codigoPorcentaje>${item.ivaMeta.codigoPorcentaje}</codigoPorcentaje>`,
        `          <tarifa>${formatMoney(item.ivaMeta.tarifa)}</tarifa>`,
        `          <baseImponible>${formatMoney(item.totalSinImpuesto)}</baseImponible>`,
        `          <valor>${formatMoney(item.impuestoLinea)}</valor>`,
        "        </impuesto>",
        "      </impuestos>",
        "    </detalle>"
      ].filter(Boolean);

      return detalleLines.join("\n");
    }),
    "  </detalles>"
  ].join("\n");

  const camposAdicionales = [
    comprador.correo
      ? `    <campoAdicional nombre="Email">${xmlEscape(comprador.correo)}</campoAdicional>`
      : null,
    comprador.telefono
      ? `    <campoAdicional nombre="Telefono">${xmlEscape(comprador.telefono)}</campoAdicional>`
      : null,
    comprador.direccionComprador
      ? `    <campoAdicional nombre="Direccion">${xmlEscape(comprador.direccionComprador)}</campoAdicional>`
      : null,
    venta.nombre_local
      ? `    <campoAdicional nombre="Local">${xmlEscape(venta.nombre_local)}</campoAdicional>`
      : null
  ].filter(Boolean);

  const infoAdicional = camposAdicionales.length
    ? [
      "  <infoAdicional>",
      ...camposAdicionales,
      "  </infoAdicional>"
    ].join("\n")
    : null;

  return [
    "<?xml version=\"1.0\" encoding=\"UTF-8\"?>",
    "<factura id=\"comprobante\" version=\"1.1.0\">",
    "  <infoTributaria>",
    infoTributaria,
    "  </infoTributaria>",
    "  <infoFactura>",
    infoFactura,
    "  </infoFactura>",
    detalles,
    infoAdicional,
    "</factura>"
  ].filter(Boolean).join("\n");
}

async function saveSriDocumento({
  venta,
  config,
  claveAcceso,
  xmlFilePath,
  previewData
}) {
  await db.query(`
    INSERT INTO sri_documentos (
      id_local,
      id_venta,
      tipo_comprobante,
      clave_acceso,
      estado,
      ambiente,
      xml_generado_path,
      respuesta_sri_json
    ) VALUES (?, ?, 'FACTURA', ?, 'XML_GENERADO', ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      clave_acceso = VALUES(clave_acceso),
      estado = 'XML_GENERADO',
      ambiente = VALUES(ambiente),
      xml_generado_path = VALUES(xml_generado_path),
      respuesta_sri_json = VALUES(respuesta_sri_json),
      error_codigo = NULL,
      error_detalle = NULL
  `, [
    venta.id_local,
    venta.id_venta,
    claveAcceso,
    config.ambiente,
    xmlFilePath,
    JSON.stringify(previewData)
  ]);

  await db.query(`
    UPDATE ventas SET
      clave_acceso = ?,
      ambiente = ?,
      establecimiento = ?,
      punto_emision = ?,
      secuencial = ?,
      estado_sri = 'PENDIENTE'
    WHERE id_venta = ?
  `, [
    claveAcceso,
    config.ambiente,
    padLeft(venta.establecimiento || config.establecimiento || "001", 3),
    padLeft(venta.punto_emision || config.punto_emision || "001", 3),
    padLeft(venta.secuencial || venta.id_venta, 9),
    venta.id_venta
  ]);

  const [[documento]] = await db.query(`
    SELECT *
    FROM sri_documentos
    WHERE id_venta = ?
      AND tipo_comprobante = 'FACTURA'
    LIMIT 1
  `, [venta.id_venta]);

  return documento;
}

async function generarFacturaXmlDesdeVenta({ id_venta, user = null }) {
  await ensureSriTables();
  ensureXmlDir();

  const idVenta = Number(id_venta || 0);
  if (!idVenta) {
    throw createError("Debes indicar un id_venta válido");
  }

  const venta = await getVentaBase(idVenta);

  if (user && Array.isArray(user.roles) && !user.roles.includes("ADMIN")) {
    if (Number(user.id_local || 0) !== Number(venta.id_local || 0)) {
      throw createError("No puedes generar XML para una venta de otro local", 403);
    }
  }

  const config = await getSriConfig(venta.id_local);
  if (!config) {
    throw createError(`El local ${venta.id_local} no tiene configuración SRI guardada`);
  }

  const detalles = await getVentaDetalles(idVenta);
  const pagos = await getVentaPagos(idVenta);
  const existingDocumento = await getExistingSriDocument(idVenta);
  const comprador = inferComprador(venta);
  const facturaData = buildLineasFactura(detalles, venta);
  const pagosSri = buildPagosSri(venta, pagos);
  const claveAcceso = buildClaveAcceso({
    venta,
    config,
    existingClaveAcceso: existingDocumento?.clave_acceso || venta.clave_acceso
  });
  const xml = buildFacturaXml({
    venta,
    config,
    comprador,
    facturaData,
    pagosSri,
    claveAcceso
  });

  const fileName = `factura_${idVenta}_${claveAcceso}.xml`;
  const xmlFilePath = path.join(SRI_XML_DIR, fileName);
  fs.writeFileSync(xmlFilePath, xml, "utf8");

  const previewData = {
    generado_en: new Date().toISOString(),
    venta: {
      id_venta: venta.id_venta,
      id_local: venta.id_local,
      numero_comprobante: venta.numero_comprobante,
      fecha_venta: venta.fecha_venta,
      subtotal: round2(venta.subtotal),
      descuento: round2(venta.descuento),
      impuesto: round2(venta.impuesto),
      total: round2(venta.total)
    },
    config: {
      id_local: config.id_local,
      ruc: config.ruc,
      razon_social: config.razon_social,
      ambiente: config.ambiente,
      establecimiento: config.establecimiento,
      punto_emision: config.punto_emision
    },
    comprador,
    pagos: pagosSri,
    resumen_factura: {
      totalSinImpuestos: facturaData.resumen.totalSinImpuestos,
      totalDescuento: facturaData.resumen.totalDescuento,
      totalImpuesto: facturaData.resumen.totalImpuesto,
      importeTotal: facturaData.resumen.importeTotal
    }
  };

  const documento = await saveSriDocumento({
    venta,
    config,
    claveAcceso,
    xmlFilePath,
    previewData
  });

  return {
    id_documento_sri: documento?.id_documento_sri || null,
    id_venta: venta.id_venta,
    id_local: venta.id_local,
    clave_acceso: claveAcceso,
    ambiente: config.ambiente,
    estado: "XML_GENERADO",
    numero_comprobante: venta.numero_comprobante,
    xml_generado_path: xmlFilePath,
    xml_generado_url: toPublicUploadPath(xmlFilePath),
    resumen: previewData.resumen_factura,
    comprador,
    pagos: pagosSri,
    xml
  };
}

module.exports = {
  generarFacturaXmlDesdeVenta
};
