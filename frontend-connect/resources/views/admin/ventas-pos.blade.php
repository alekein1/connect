@extends('caja.layout')

@section('title', 'POS Web')

@section('content')

<style>
.pos-page{
    display:flex;
    flex-direction:column;
    gap:20px;
}

.notice-bar,
.pos-grid,
.panel-pos,
.summary-card{
    background:linear-gradient(180deg,#081225 0%,#07101f 100%);
    border:1px solid rgba(255,255,255,.06);
    border-radius:22px;
    box-shadow:0 18px 45px rgba(0,0,0,.35);
}

.notice-bar{
    padding:18px 20px;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
    flex-wrap:wrap;
}

.notice-title{
    margin:0 0 6px;
    color:#fff;
    font-size:22px;
    font-weight:800;
}

.notice-copy{
    margin:0;
    color:#94a3b8;
    font-size:14px;
    line-height:1.6;
    max-width:760px;
}

.notice-chip{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    background:rgba(59,130,246,.12);
    border:1px solid rgba(96,165,250,.16);
    color:#bfdbfe;
    font-size:12px;
    font-weight:700;
    letter-spacing:.03em;
    text-transform:uppercase;
}

.pos-grid{
    display:grid;
    grid-template-columns:minmax(0, 1.65fr) minmax(340px, .95fr);
    gap:0;
    overflow:hidden;
}

.panel-pos{
    border-radius:0;
    box-shadow:none;
    border:none;
    background:transparent;
    padding:22px;
}

.panel-pos.left{
    border-right:1px solid rgba(255,255,255,.06);
}

.search-stack{
    position:relative;
    display:flex;
    flex-direction:column;
    gap:8px;
    margin-bottom:18px;
}

.search-row{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.search-row input{
    flex:1;
    min-width:220px;
}

.field,
.summary-card{
    background:rgba(255,255,255,.02);
    border:1px solid rgba(255,255,255,.06);
    border-radius:18px;
    padding:16px;
}

.field-title{
    margin:0 0 4px;
    color:#fff;
    font-size:16px;
    font-weight:700;
}

.field-copy{
    margin:0;
    color:#94a3b8;
    font-size:13px;
    line-height:1.5;
}

.input-pro,
.select-pro,
.textarea-pro{
    width:100%;
    background:#0f172a;
    border:1px solid rgba(255,255,255,.08);
    color:#fff;
    border-radius:14px;
    padding:12px 14px;
    font-size:14px;
    outline:none;
}

.textarea-pro{
    resize:vertical;
    min-height:88px;
}

.input-pro:focus,
.select-pro:focus,
.textarea-pro:focus{
    border-color:#60a5fa;
    box-shadow:0 0 0 3px rgba(96,165,250,.18);
}

.btn-pro,
.btn-outline-pro,
.btn-danger-pro,
.btn-ghost-pro{
    border:none;
    border-radius:14px;
    padding:12px 16px;
    font-weight:800;
    cursor:pointer;
    transition:.2s ease;
}

.btn-pro{
    background:linear-gradient(135deg,#f4c842 0%,#d8a910 100%);
    color:#111827;
    box-shadow:0 10px 20px rgba(216,169,16,.18);
}

.btn-outline-pro{
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.08);
    color:#fff;
}

.btn-danger-pro{
    background:linear-gradient(135deg,#ef4444 0%,#b91c1c 100%);
    color:#fff;
    box-shadow:0 10px 22px rgba(239,68,68,.15);
}

.btn-ghost-pro{
    background:transparent;
    border:1px dashed rgba(148,163,184,.25);
    color:#cbd5e1;
}

.btn-pro:hover,
.btn-outline-pro:hover,
.btn-danger-pro:hover,
.btn-ghost-pro:hover{
    transform:translateY(-1px);
    opacity:.97;
}

.btn-pro:disabled,
.btn-outline-pro:disabled,
.btn-danger-pro:disabled,
.btn-ghost-pro:disabled{
    opacity:.65;
    cursor:not-allowed;
    transform:none;
}

.dropdown-results{
    position:absolute;
    top:100%;
    left:0;
    right:0;
    margin-top:6px;
    background:#020617;
    border:1px solid rgba(255,255,255,.07);
    border-radius:16px;
    z-index:25;
    max-height:320px;
    overflow:auto;
    display:none;
}

.dropdown-item{
    width:100%;
    background:transparent;
    border:none;
    text-align:left;
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 14px;
    cursor:pointer;
    border-bottom:1px solid rgba(255,255,255,.05);
}

.dropdown-item:last-child{
    border-bottom:none;
}

.dropdown-item:hover{
    background:rgba(255,255,255,.03);
}

.dropdown-thumb{
    width:46px;
    height:46px;
    border-radius:12px;
    object-fit:cover;
    flex-shrink:0;
    background:#111827;
}

.dropdown-meta{
    display:flex;
    flex-direction:column;
    gap:4px;
    min-width:0;
}

.dropdown-meta strong{
    color:#fff;
    font-size:14px;
}

.dropdown-meta span{
    color:#94a3b8;
    font-size:12px;
}

.table-wrap-pro{
    border:1px solid rgba(255,255,255,.06);
    border-radius:18px;
    overflow:hidden;
    background:rgba(255,255,255,.02);
}

.table-pro{
    width:100%;
    min-width:900px;
    border-collapse:collapse;
}

.table-pro th{
    text-align:left;
    padding:14px 16px;
    font-size:12px;
    font-weight:800;
    color:#94a3b8;
    text-transform:uppercase;
    letter-spacing:.04em;
    background:rgba(255,255,255,.02);
    border-bottom:1px solid rgba(255,255,255,.06);
}

.table-pro td{
    padding:14px 16px;
    border-bottom:1px solid rgba(255,255,255,.05);
    color:#e5e7eb;
    vertical-align:top;
    font-size:14px;
}

.table-pro tr:last-child td{
    border-bottom:none;
}

.product-main{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
}

.product-info{
    display:flex;
    align-items:center;
    gap:12px;
    min-width:0;
}

.product-info img{
    width:48px;
    height:48px;
    border-radius:12px;
    object-fit:cover;
    background:#111827;
}

.product-text{
    min-width:0;
}

.product-text strong{
    display:block;
    color:#fff;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.product-text small{
    color:#64748b;
}

.btn-delete{
    width:34px;
    height:34px;
    border:none;
    border-radius:10px;
    background:rgba(239,68,68,.14);
    color:#fca5a5;
    cursor:pointer;
    font-weight:800;
    flex-shrink:0;
}

.empty-state{
    padding:30px 20px;
    text-align:center;
    color:#94a3b8;
}

.right-stack{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.switch-row{
    display:flex;
    gap:10px;
}

.switch-row button{
    flex:1;
}

.switch-btn{
    background:#111827;
    border:1px solid rgba(255,255,255,.08);
    color:#cbd5e1;
    border-radius:14px;
    padding:11px 12px;
    font-weight:800;
    cursor:pointer;
}

.switch-btn.active{
    background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);
    border-color:transparent;
    color:#fff;
}

.hidden{
    display:none !important;
}

.box-status{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:12px;
    margin-top:14px;
}

.mini-kpi{
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.05);
    border-radius:16px;
    padding:14px;
}

.mini-kpi span{
    display:block;
    color:#94a3b8;
    font-size:12px;
    margin-bottom:8px;
}

.mini-kpi strong{
    display:block;
    color:#fff;
    font-size:22px;
    font-weight:800;
}

.status-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.status-pill.open{
    background:rgba(34,197,94,.14);
    border:1px solid rgba(74,222,128,.2);
    color:#86efac;
}

.status-pill.closed{
    background:rgba(148,163,184,.12);
    border:1px solid rgba(148,163,184,.18);
    color:#cbd5e1;
}

.grid-2{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:12px;
}

.summary-row{
    display:flex;
    justify-content:space-between;
    gap:12px;
    color:#cbd5e1;
    font-size:14px;
    margin-top:8px;
}

.summary-row strong{
    color:#fff;
}

.summary-row.total strong:last-child{
    color:#facc15;
    font-size:24px;
}

.actions-stack{
    display:flex;
    flex-direction:column;
    gap:10px;
    margin-top:16px;
}

.actions-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:10px;
}

.actions-grid .full{
    grid-column:1 / -1;
}

.feedback-box{
    min-height:52px;
    padding:14px 16px;
    border-radius:16px;
    border:1px solid rgba(255,255,255,.06);
    background:rgba(255,255,255,.02);
    color:#cbd5e1;
    font-size:14px;
    line-height:1.5;
}

.feedback-box.error{
    color:#fecaca;
    background:rgba(127,29,29,.28);
    border-color:rgba(248,113,113,.22);
}

.feedback-box.success{
    color:#bbf7d0;
    background:rgba(20,83,45,.3);
    border-color:rgba(74,222,128,.22);
}

.reprint-meta{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.reprint-meta strong{
    color:#fff;
}

.muted{
    color:#94a3b8;
}

@media (max-width: 1180px){
    .pos-grid{
        grid-template-columns:1fr;
    }

    .panel-pos.left{
        border-right:none;
        border-bottom:1px solid rgba(255,255,255,.06);
    }
}

@media (max-width: 767.98px){
    .panel-pos{
        padding:16px;
    }

    .box-status,
    .grid-2,
    .actions-grid{
        grid-template-columns:1fr;
    }

    .search-row{
        flex-direction:column;
    }
}
</style>

<div class="pos-page">
    <section class="notice-bar">
        <div>
            <div class="notice-chip">Ventas web conectadas al API</div>
            <h2 class="notice-title">Punto de venta web</h2>
            <p class="notice-copy">
                Este flujo usa el mismo backend de ventas y los mismos endpoints del SRI. Si la venta sale sin SRI,
                se abre un ticket imprimible de nota de venta. Si sale con SRI y la factura queda autorizada,
                se abre el RIDE en una pestaña nueva para reimpresión.
            </p>
        </div>
        <div class="summary-card" style="max-width:360px;">
            <p class="field-title">Último comprobante</p>
            <div id="lastSaleMeta" class="reprint-meta muted">
                <span>Aún no hay una venta emitida en esta sesión.</span>
            </div>
            <div class="actions-stack" style="margin-top:14px;">
                <button id="btnReprintLast" type="button" class="btn-outline-pro" disabled>Reimprimir último comprobante</button>
            </div>
        </div>
    </section>

    <section class="pos-grid">
        <div class="panel-pos left">
            <div class="search-stack">
                <div class="search-row">
                    <input id="inputBuscar" class="input-pro" placeholder="Escanea o busca por nombre, código, SKU o IMEI">
                    <button id="btnBuscarManual" type="button" class="btn-outline-pro">Buscar</button>
                </div>
                <div id="dropdownResultados" class="dropdown-results"></div>
            </div>

            <div class="table-wrap-pro">
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th>IMEI</th>
                        </tr>
                    </thead>
                    <tbody id="detalleVenta">
                        <tr>
                            <td colspan="5" class="empty-state">Agrega productos para empezar la venta.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-pos">
            <div class="right-stack">
                <div class="field">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                        <div>
                            <p class="field-title">Caja activa</p>
                            <p class="field-copy">Abre o cierra tu caja desde aquí antes de registrar ventas.</p>
                        </div>
                        <span id="cajaEstadoChip" class="status-pill closed">Sin caja abierta</span>
                    </div>

                    <div class="box-status">
                        <div class="mini-kpi">
                            <span>Apertura</span>
                            <strong id="kpiAperturaCaja">$0.00</strong>
                        </div>
                        <div class="mini-kpi">
                            <span>Ventas acumuladas</span>
                            <strong id="kpiVentasCaja">$0.00</strong>
                        </div>
                    </div>

                    <div class="grid-2" style="margin-top:14px;">
                        <div>
                            <label class="muted">Monto apertura</label>
                            <input id="montoAperturaCaja" type="number" min="0" step="0.01" class="input-pro" placeholder="Ej: 100.00">
                        </div>
                        <div>
                            <label class="muted">Monto cierre</label>
                            <input id="montoCierreCaja" type="number" min="0" step="0.01" class="input-pro" placeholder="Ej: 245.50">
                        </div>
                    </div>

                    <div class="actions-grid" style="margin-top:14px;">
                        <button id="btnAbrirCaja" type="button" class="btn-pro">Abrir caja</button>
                        <button id="btnCerrarCaja" type="button" class="btn-danger-pro">Cerrar caja</button>
                    </div>
                </div>

                <div class="field">
                    <p class="field-title">Tipo de comprobante</p>
                    <div class="switch-row">
                        <button id="btnConsumidor" type="button" class="switch-btn active">Consumidor final</button>
                        <button id="btnFactura" type="button" class="switch-btn">Factura</button>
                    </div>
                </div>

                <div id="clienteBox" class="field hidden">
                    <p class="field-title">Datos del cliente</p>
                    <div class="grid-2">
                        <div>
                            <label class="muted">Cédula / RUC</label>
                            <input id="clienteCedula" class="input-pro" placeholder="Documento">
                        </div>
                        <div>
                            <label class="muted">Nombre</label>
                            <input id="clienteNombre" class="input-pro" placeholder="Nombre completo">
                        </div>
                        <div>
                            <label class="muted">Correo</label>
                            <input id="clienteCorreo" type="email" class="input-pro" placeholder="correo@cliente.com">
                        </div>
                        <div>
                            <label class="muted">Teléfono</label>
                            <input id="clienteTelefono" class="input-pro" placeholder="Teléfono">
                        </div>
                    </div>
                    <div style="margin-top:12px;">
                        <label class="muted">Dirección</label>
                        <textarea id="clienteDireccion" class="textarea-pro" placeholder="Dirección fiscal"></textarea>
                    </div>
                </div>

                <div class="field">
                    <p class="field-title">Tipo de venta</p>
                    <div class="grid-2">
                        <div>
                            <label class="muted">Modalidad</label>
                            <select id="tipoVenta" class="select-pro">
                                <option value="CONTADO">Contado</option>
                                <option value="FINANCIADO">Financiado</option>
                            </select>
                        </div>
                        <div>
                            <label class="muted">Aumento por producto</label>
                            <input id="recargoFinanciamiento" type="number" min="0" step="0.01" class="input-pro" placeholder="Ej: 5.00">
                        </div>
                    </div>

                    <div id="financiamientoBox" class="grid-2 hidden" style="margin-top:12px;">
                        <div>
                            <label class="muted">Entrada</label>
                            <input id="entradaFinanciamiento" type="number" min="0" step="0.01" class="input-pro" placeholder="Monto entrada">
                        </div>
                        <div>
                            <label class="muted">Cuotas</label>
                            <input id="cuotasFinanciamiento" type="number" min="1" step="1" class="input-pro" placeholder="Número de cuotas">
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="muted">Proveedor</label>
                            <select id="proveedorFinanciamiento" class="select-pro">
                                <option value="PAYJOY">PAYJOY</option>
                                <option value="HAPPY">HAPPY</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <p class="field-title">Pago y descuento</p>
                    <div class="grid-2">
                        <div>
                            <label class="muted">Forma de pago</label>
                            <select id="formaPago" class="select-pro">
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TARJETA">Tarjeta</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                            </select>
                        </div>
                        <div>
                            <label class="muted">Monto pago</label>
                            <input id="montoPago" class="input-pro" placeholder="Se calcula automáticamente" readonly>
                        </div>
                        <div>
                            <label class="muted">Descuento</label>
                            <input id="descuentoInput" type="number" min="0" step="0.01" class="input-pro" placeholder="0.00">
                        </div>
                        <div>
                            <label class="muted">Motivo descuento</label>
                            <input id="motivoDescuentoInput" class="input-pro" placeholder="Motivo opcional">
                        </div>
                    </div>
                </div>

                <div class="summary-card">
                    <p class="field-title">Resumen de venta</p>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong id="resumenSubtotal">$0.00</strong>
                    </div>
                    <div class="summary-row">
                        <span>Descuento</span>
                        <strong id="resumenDescuento">$0.00</strong>
                    </div>
                    <div class="summary-row">
                        <span>Entrada crédito</span>
                        <strong id="resumenEntrada">$0.00</strong>
                    </div>
                    <div class="summary-row">
                        <span>Saldo crédito</span>
                        <strong id="resumenSaldo">$0.00</strong>
                    </div>
                    <div class="summary-row total" style="margin-top:14px;">
                        <strong>Total</strong>
                        <strong id="resumenTotal">$0.00</strong>
                    </div>

                    <div class="actions-stack">
                        <button id="btnVentaNormal" type="button" class="btn-pro">🧾 Nota de venta</button>
                        <div class="actions-grid">
                            <button id="btnCredito" type="button" class="btn-outline-pro">💳 Crédito</button>
                            <button id="btnSri" type="button" class="btn-outline-pro">📄 Emitir con SRI</button>
                            <button id="btnLimpiarVenta" type="button" class="btn-ghost-pro full">Limpiar formulario</button>
                        </div>
                    </div>
                </div>

                <div id="feedbackBox" class="feedback-box">
                    Usa el buscador para agregar productos y emite la venta desde esta misma pantalla.
                </div>
            </div>
        </div>
    </section>
</div>

<script>
const API = "{{ env('API_URL') }}";
const API_BASE = String(API || "").replace(/\/api\/?$/, "");
const IMG = `${API_BASE}/api/uploads/productos/`;
const TOKEN = localStorage.getItem("token");
const LAST_SALE_KEY = "pos_web_last_sale";

if (!TOKEN) {
    window.location.href = "/login";
}

let carrito = [];
let resultadosBusqueda = [];
let cajaActual = null;

const inputBuscar = document.getElementById("inputBuscar");
const btnBuscarManual = document.getElementById("btnBuscarManual");
const dropdownResultados = document.getElementById("dropdownResultados");
const detalleVenta = document.getElementById("detalleVenta");
const btnConsumidor = document.getElementById("btnConsumidor");
const btnFactura = document.getElementById("btnFactura");
const clienteBox = document.getElementById("clienteBox");
const clienteCedula = document.getElementById("clienteCedula");
const clienteNombre = document.getElementById("clienteNombre");
const clienteCorreo = document.getElementById("clienteCorreo");
const clienteDireccion = document.getElementById("clienteDireccion");
const clienteTelefono = document.getElementById("clienteTelefono");
const tipoVentaSelect = document.getElementById("tipoVenta");
const financiamientoBox = document.getElementById("financiamientoBox");
const recargoFinanciamientoInput = document.getElementById("recargoFinanciamiento");
const entradaFinanciamientoInput = document.getElementById("entradaFinanciamiento");
const cuotasFinanciamientoInput = document.getElementById("cuotasFinanciamiento");
const proveedorFinanciamientoSelect = document.getElementById("proveedorFinanciamiento");
const pagoSelect = document.getElementById("formaPago");
const montoInput = document.getElementById("montoPago");
const descuentoInput = document.getElementById("descuentoInput");
const motivoDescuentoInput = document.getElementById("motivoDescuentoInput");
const btnVentaNormal = document.getElementById("btnVentaNormal");
const btnCredito = document.getElementById("btnCredito");
const btnSri = document.getElementById("btnSri");
const btnLimpiarVenta = document.getElementById("btnLimpiarVenta");
const feedbackBox = document.getElementById("feedbackBox");
const btnReprintLast = document.getElementById("btnReprintLast");
const lastSaleMeta = document.getElementById("lastSaleMeta");
const cajaEstadoChip = document.getElementById("cajaEstadoChip");
const kpiAperturaCaja = document.getElementById("kpiAperturaCaja");
const kpiVentasCaja = document.getElementById("kpiVentasCaja");
const montoAperturaCaja = document.getElementById("montoAperturaCaja");
const montoCierreCaja = document.getElementById("montoCierreCaja");
const btnAbrirCaja = document.getElementById("btnAbrirCaja");
const btnCerrarCaja = document.getElementById("btnCerrarCaja");
const resumenSubtotal = document.getElementById("resumenSubtotal");
const resumenDescuento = document.getElementById("resumenDescuento");
const resumenEntrada = document.getElementById("resumenEntrada");
const resumenSaldo = document.getElementById("resumenSaldo");
const resumenTotal = document.getElementById("resumenTotal");

function buildApiUrl(value = "") {
    if (!value) return "";
    if (/^https?:\/\//i.test(value)) return value;
    return `${API_BASE}${value.startsWith("/") ? value : `/${value}`}`;
}

async function apiFetch(url, options = {}) {
    const headers = {
        Accept: "application/json",
        Authorization: `Bearer ${TOKEN}`,
        ...(options.headers || {})
    };

    const response = await fetch(url, {
        ...options,
        headers
    });

    const data = await response.json().catch(() => ({
        ok: false,
        mensaje: "La respuesta del servidor no fue válida"
    }));

    if (!response.ok || data.ok === false) {
        throw new Error(data?.mensaje || "No se pudo completar la solicitud");
    }

    return data;
}

function escapeHtml(value = "") {
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function money(value) {
    return `$${Number(value || 0).toFixed(2)}`;
}

function numberOrZero(value) {
    const parsed = Number(value || 0);
    return Number.isFinite(parsed) ? parsed : 0;
}

function showFeedback(message, type = "default") {
    feedbackBox.className = "feedback-box";
    if (type === "error") {
        feedbackBox.classList.add("error");
    }
    if (type === "success") {
        feedbackBox.classList.add("success");
    }
    feedbackBox.textContent = message;
}

function setTipoComprobante(esFactura) {
    btnFactura.classList.toggle("active", esFactura);
    btnConsumidor.classList.toggle("active", !esFactura);
    clienteBox.classList.toggle("hidden", !esFactura);
}

function esFacturaActiva() {
    return btnFactura.classList.contains("active");
}

function setTipoVenta(tipoVenta) {
    financiamientoBox.classList.toggle("hidden", tipoVenta !== "FINANCIADO");
    render();
}

function getButtonSet() {
    return [
        btnVentaNormal,
        btnCredito,
        btnSri,
        btnLimpiarVenta,
        btnAbrirCaja,
        btnCerrarCaja
    ];
}

function setButtonState(button, text, disabled) {
    if (!button) return;
    if (text) {
        button.textContent = text;
    }
    button.disabled = Boolean(disabled);
}

function getPopupWindow(title) {
    const popup = window.open("", "_blank", "noopener,noreferrer,width=460,height=760");

    if (!popup) {
        return null;
    }

    popup.document.write(`
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>${escapeHtml(title)}</title>
            <style>
                body{
                    margin:0;
                    min-height:100vh;
                    display:grid;
                    place-items:center;
                    background:#0f172a;
                    color:#fff;
                    font-family:Arial,sans-serif;
                }
                .box{
                    padding:28px;
                    border-radius:18px;
                    background:#111827;
                    border:1px solid rgba(255,255,255,.08);
                    text-align:center;
                    width:min(92vw, 420px);
                }
            </style>
        </head>
        <body>
            <div class="box">
                <strong>${escapeHtml(title)}</strong>
                <p>Preparando comprobante...</p>
            </div>
        </body>
        </html>
    `);
    popup.document.close();

    return popup;
}

function buildTicketHtml(payload) {
    const itemsHtml = (payload.items || [])
        .map((item) => `
            <tr>
                <td class="desc">
                    ${escapeHtml(item.codigo || "")}<br>
                    ${escapeHtml(item.nombre || "")}
                    ${item.imei ? `<br>IMEI: ${escapeHtml(item.imei)}` : ""}
                </td>
                <td class="qty">${escapeHtml(String(item.cantidad || 0))}</td>
                <td class="money">${Number(item.precio || 0).toFixed(2)}</td>
                <td class="money">${Number(item.total || 0).toFixed(2)}</td>
            </tr>
        `)
        .join("");

    return `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>${escapeHtml(payload.titulo || "Nota de venta")}</title>
            <style>
                body{
                    margin:0;
                    font-family:"Courier New",monospace;
                    background:#fff;
                    color:#000;
                }
                .ticket{
                    width:80mm;
                    padding:10px 12px 16px;
                    margin:0 auto;
                    font-size:12px;
                    line-height:1.22;
                }
                .center{text-align:center;}
                .divider{
                    border-top:1px dashed #000;
                    margin:8px 0;
                }
                .row{
                    display:flex;
                    justify-content:space-between;
                    gap:8px;
                }
                .row span:last-child{
                    min-width:80px;
                    text-align:right;
                }
                table{
                    width:100%;
                    border-collapse:collapse;
                    margin-top:6px;
                }
                th,td{
                    padding:2px 0;
                    vertical-align:top;
                }
                th{
                    text-align:left;
                    border-bottom:1px dashed #000;
                }
                .qty{
                    width:40px;
                    text-align:center;
                }
                .money{
                    width:62px;
                    text-align:right;
                }
                .desc{
                    word-break:break-word;
                }
                .strong{
                    font-weight:700;
                }
                @media print{
                    .print-actions{display:none;}
                }
            </style>
        </head>
        <body>
            <div class="ticket">
                <div class="center strong">${escapeHtml(payload.localNombre || "CONNECT")}</div>
                ${payload.localDireccion ? `<div class="center">${escapeHtml(payload.localDireccion)}</div>` : ""}
                ${payload.localTelefono ? `<div class="center">Tel: ${escapeHtml(payload.localTelefono)}</div>` : ""}
                <div class="divider"></div>
                <div class="center strong">${escapeHtml(payload.titulo || "NOTA DE VENTA")}</div>
                ${payload.numeroComprobante ? `<div>No: ${escapeHtml(payload.numeroComprobante)}</div>` : ""}
                <div>Fecha: ${escapeHtml(payload.fecha || "")}</div>
                <div>Tipo venta: ${escapeHtml(payload.tipoVenta || "")}</div>
                <div>Forma pago: ${escapeHtml(payload.formaPago || "EFECTIVO")}</div>
                <div>Cliente: ${escapeHtml(payload.clienteNombre || "CONSUMIDOR FINAL")}</div>
                ${payload.clienteCedula ? `<div>Documento: ${escapeHtml(payload.clienteCedula)}</div>` : ""}
                ${payload.clienteDireccion ? `<div>Direccion: ${escapeHtml(payload.clienteDireccion)}</div>` : ""}
                ${payload.clienteTelefono ? `<div>Telefono: ${escapeHtml(payload.clienteTelefono)}</div>` : ""}
                ${payload.clienteCorreo ? `<div>Email: ${escapeHtml(payload.clienteCorreo)}</div>` : ""}
                <div class="divider"></div>
                <table>
                    <thead>
                        <tr>
                            <th>Descripcion</th>
                            <th class="qty">Cant</th>
                            <th class="money">P.U.</th>
                            <th class="money">Total</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
                <div class="divider"></div>
                <div class="row"><span>Subtotal</span><span>${Number(payload.subtotal || 0).toFixed(2)}</span></div>
                <div class="row"><span>IVA</span><span>${Number(payload.iva || 0).toFixed(2)}</span></div>
                ${payload.entradaCredito ? `<div class="row"><span>Entrada</span><span>${Number(payload.entradaCredito || 0).toFixed(2)}</span></div>` : ""}
                ${payload.saldoCredito ? `<div class="row"><span>Saldo</span><span>${Number(payload.saldoCredito || 0).toFixed(2)}</span></div>` : ""}
                <div class="row strong"><span>Total</span><span>${Number(payload.total || 0).toFixed(2)}</span></div>
                <div class="divider"></div>
                <div>${escapeHtml(payload.mensajeFinal || "Gracias por su compra")}</div>
                <div class="print-actions" style="margin-top:14px;text-align:center;">
                    <button onclick="window.print()" style="padding:10px 14px;border:none;border-radius:10px;background:#111827;color:#fff;cursor:pointer;">Imprimir</button>
                </div>
            </div>
            <script>
                window.addEventListener("load", () => {
                    setTimeout(() => window.print(), 300);
                });
            <\/script>
        </body>
        </html>
    `;
}

function renderTicketPopup(popup, payload) {
    if (!popup || popup.closed) {
        const fallback = getPopupWindow(payload.titulo || "Ticket");
        if (!fallback) {
            throw new Error("Tu navegador bloqueó la ventana de impresión");
        }
        popup = fallback;
    }

    popup.document.open();
    popup.document.write(buildTicketHtml(payload));
    popup.document.close();
    popup.focus();
    return popup;
}

function obtenerPrecioVigente(item) {
    if (item.precioEditado) {
        return Number(item.precio || 0);
    }

    return Number((Number(item.precioBase || 0) + obtenerRecargoFinanciamiento()).toFixed(2));
}

function obtenerSubtotalCarrito() {
    return carrito.reduce((acc, item) => acc + (obtenerPrecioVigente(item) * Number(item.cantidad || 0)), 0);
}

function obtenerDescuentoActual(subtotal) {
    const descuento = Number(descuentoInput.value || 0);
    if (!Number.isFinite(descuento) || descuento <= 0) {
        return 0;
    }
    return Math.min(descuento, subtotal);
}

function obtenerRecargoFinanciamiento() {
    const recargo = Number(recargoFinanciamientoInput.value || 0);
    return Number.isFinite(recargo) && recargo > 0 ? recargo : 0;
}

function limpiarBusqueda() {
    inputBuscar.value = "";
    resultadosBusqueda = [];
    dropdownResultados.innerHTML = "";
    dropdownResultados.style.display = "none";
}

function cambiarCantidad(index, value) {
    const cantidad = Number(value);
    carrito[index].cantidad = Number.isFinite(cantidad) && cantidad > 0 ? cantidad : 1;
    render();
}

function cambiarPrecio(index, value) {
    const precio = Number(value);
    if (!Number.isFinite(precio) || precio < 0) {
        return;
    }
    carrito[index].precio = precio;
    carrito[index].precioEditado = true;
    render();
}

function setIMEI(index, value) {
    carrito[index].imei = value || null;
}

function eliminarProducto(index) {
    carrito.splice(index, 1);
    render();
}

function agregarProducto(producto) {
    const existente = carrito.find((item) => Number(item.id_producto) === Number(producto.id_producto));

    if (existente && !producto.imeis) {
        existente.cantidad += 1;
    } else {
        carrito.push({
            id_producto: producto.id_producto,
            nombre: producto.nombre_producto,
            precioBase: Number(producto.precio_unitario || 0),
            precio: Number(producto.precio_unitario || 0),
            precioEditado: false,
            cantidad: 1,
            imei: producto.imei_encontrado || null,
            imeis: producto.imeis || "",
            imagen: producto.imagen || ""
        });
    }

    render();
}

function renderDropdown() {
    dropdownResultados.innerHTML = "";

    if (!resultadosBusqueda.length) {
        dropdownResultados.style.display = "none";
        return;
    }

    resultadosBusqueda.forEach((prod) => {
        const item = document.createElement("button");
        item.type = "button";
        item.className = "dropdown-item";
        item.innerHTML = `
            <img class="dropdown-thumb" src="${prod.imagen ? IMG + prod.imagen : "https://via.placeholder.com/46"}" alt="">
            <div class="dropdown-meta">
                <strong>${escapeHtml(prod.nombre_producto || "Producto")}</strong>
                <span>$${Number(prod.precio_unitario || 0).toFixed(2)} · Stock ${Number(prod.stock_actual || 0)}${prod.imei_encontrado ? ` · IMEI ${escapeHtml(prod.imei_encontrado)}` : ""}</span>
            </div>
        `;
        item.onclick = () => {
            agregarProducto(prod);
            limpiarBusqueda();
        };
        dropdownResultados.appendChild(item);
    });

    dropdownResultados.style.display = "block";
}

function render() {
    if (!carrito.length) {
        detalleVenta.innerHTML = `
            <tr>
                <td colspan="5" class="empty-state">Agrega productos para empezar la venta.</td>
            </tr>
        `;
    } else {
        detalleVenta.innerHTML = carrito.map((item, index) => {
            const precioVigente = obtenerPrecioVigente(item);
            item.precio = precioVigente;
            const subtotal = Number((precioVigente * Number(item.cantidad || 0)).toFixed(2));
            const imeis = String(item.imeis || "")
                .split(",")
                .map((value) => value.trim())
                .filter(Boolean);

            return `
                <tr>
                    <td>
                        <div class="product-main">
                            <div class="product-info">
                                <img src="${item.imagen ? IMG + item.imagen : "https://via.placeholder.com/48"}" alt="">
                                <div class="product-text">
                                    <strong>${escapeHtml(item.nombre)}</strong>
                                    <small>ID ${escapeHtml(item.id_producto)}</small>
                                </div>
                            </div>
                            <button type="button" class="btn-delete" onclick="eliminarProducto(${index})">✕</button>
                        </div>
                    </td>
                    <td>
                        <input class="input-pro" type="number" min="1" step="1" value="${Number(item.cantidad || 1)}" onchange="cambiarCantidad(${index}, this.value)">
                    </td>
                    <td>
                        ${
                            tipoVentaSelect.value === "FINANCIADO"
                                ? `<input class="input-pro" type="number" min="0" step="0.01" value="${precioVigente.toFixed(2)}" onchange="cambiarPrecio(${index}, this.value)">`
                                : `<strong>${money(precioVigente)}</strong>`
                        }
                    </td>
                    <td><strong>${money(subtotal)}</strong></td>
                    <td>
                        <select class="select-pro" onchange="setIMEI(${index}, this.value)">
                            <option value="">Sin IMEI</option>
                            ${imeis.map((imei) => `<option value="${escapeHtml(imei)}" ${item.imei === imei ? "selected" : ""}>${escapeHtml(imei)}</option>`).join("")}
                        </select>
                    </td>
                </tr>
            `;
        }).join("");
    }

    const subtotal = Number(obtenerSubtotalCarrito().toFixed(2));
    const descuento = Number(obtenerDescuentoActual(subtotal).toFixed(2));
    const total = Number((subtotal - descuento).toFixed(2));
    const entrada = tipoVentaSelect.value === "FINANCIADO"
        ? Number(Math.max(Number(entradaFinanciamientoInput.value || 0), 0).toFixed(2))
        : total;
    const saldo = tipoVentaSelect.value === "FINANCIADO"
        ? Number(Math.max(total - entrada, 0).toFixed(2))
        : 0;
    const montoPago = tipoVentaSelect.value === "FINANCIADO"
        ? Math.min(entrada, total)
        : total;

    montoInput.value = montoPago > 0 ? montoPago.toFixed(2) : "";
    resumenSubtotal.textContent = money(subtotal);
    resumenDescuento.textContent = money(descuento);
    resumenEntrada.textContent = money(tipoVentaSelect.value === "FINANCIADO" ? entrada : 0);
    resumenSaldo.textContent = money(saldo);
    resumenTotal.textContent = money(total);
}

async function buscarProductosPOS(query) {
    const q = String(query || "").trim();

    if (q.length < 2) {
        return [];
    }

    const data = await apiFetch(`${API}/ventas/buscar?q=${encodeURIComponent(q)}`);
    return Array.isArray(data.data) ? data.data : [];
}

async function ejecutarBusquedaManual() {
    const q = inputBuscar.value.trim();

    if (q.length < 2) {
        showFeedback("Escribe al menos dos caracteres para buscar.", "error");
        return;
    }

    try {
        resultadosBusqueda = await buscarProductosPOS(q);
        renderDropdown();
        if (!resultadosBusqueda.length) {
            showFeedback("No se encontraron productos con ese criterio.", "error");
        }
    } catch (error) {
        showFeedback(error.message || "No se pudo buscar productos.", "error");
    }
}

async function verificarCaja() {
    try {
        const data = await apiFetch(`${API}/caja/verificar`);
        cajaActual = data.abierta ? data.caja : null;
        renderCaja();
    } catch (error) {
        cajaActual = null;
        renderCaja();
        showFeedback(error.message || "No se pudo verificar la caja.", "error");
    }
}

function renderCaja() {
    const abierta = Boolean(cajaActual);
    cajaEstadoChip.className = `status-pill ${abierta ? "open" : "closed"}`;
    cajaEstadoChip.textContent = abierta ? "Caja abierta" : "Sin caja abierta";
    kpiAperturaCaja.textContent = money(cajaActual?.monto_apertura || 0);
    kpiVentasCaja.textContent = money(cajaActual?.total_ventas || 0);

    btnAbrirCaja.disabled = abierta;
    btnCerrarCaja.disabled = !abierta;

    btnVentaNormal.disabled = !abierta;
    btnCredito.disabled = !abierta;
    btnSri.disabled = !abierta;
}

async function abrirCaja() {
    const monto = Number(montoAperturaCaja.value || 0);

    try {
        setButtonState(btnAbrirCaja, "Abriendo...", true);
        await apiFetch(`${API}/caja/abrir`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ monto_apertura: monto })
        });
        montoAperturaCaja.value = "";
        await verificarCaja();
        showFeedback("Caja abierta correctamente.", "success");
    } catch (error) {
        showFeedback(error.message || "No se pudo abrir la caja.", "error");
    } finally {
        setButtonState(btnAbrirCaja, "Abrir caja", Boolean(cajaActual));
    }
}

async function cerrarCaja() {
    const monto = Number(montoCierreCaja.value || 0);

    try {
        setButtonState(btnCerrarCaja, "Cerrando...", true);
        const data = await apiFetch(`${API}/caja/cerrar`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ monto_cierre: monto })
        });
        montoCierreCaja.value = "";
        cajaActual = null;
        renderCaja();
        showFeedback(`Caja cerrada. Diferencia: ${money(data?.resumen?.diferencia || 0)}.`, "success");
    } catch (error) {
        showFeedback(error.message || "No se pudo cerrar la caja.", "error");
    } finally {
        setButtonState(btnCerrarCaja, "Cerrar caja", false);
        renderCaja();
    }
}

function obtenerIdVentaDesdeRespuesta(data) {
    return (
        data?.data?.id_venta ||
        data?.id_venta ||
        data?.venta?.id_venta ||
        data?.data?.venta?.id_venta ||
        null
    );
}

function obtenerNumeroComprobanteDesdeRespuesta(data) {
    return (
        data?.data?.numero_comprobante ||
        data?.numero_comprobante ||
        data?.venta?.numero_comprobante ||
        data?.data?.venta?.numero_comprobante ||
        ""
    );
}

function obtenerFechaVentaDesdeRespuesta(data) {
    return (
        data?.data?.fecha_venta ||
        data?.fecha_venta ||
        data?.venta?.fecha_venta ||
        data?.data?.venta?.fecha_venta ||
        new Date().toLocaleString("es-EC")
    );
}

function obtenerNumeroDesdeRespuesta(data, keys = []) {
    for (const key of keys) {
        const value =
            data?.[key] ??
            data?.data?.[key] ??
            data?.venta?.[key] ??
            data?.data?.venta?.[key];

        if (value !== undefined && value !== null && value !== "") {
            const parsed = Number(value);
            if (!Number.isNaN(parsed)) {
                return parsed;
            }
        }
    }

    return null;
}

async function obtenerVentaDetalle(idVenta) {
    if (!idVenta) {
        return null;
    }

    try {
        const data = await apiFetch(`${API}/ventas/${idVenta}`);
        return data?.venta || null;
    } catch {
        return null;
    }
}

function obtenerValoresTributarios(dataVenta, totalFallback) {
    const subtotal = obtenerNumeroDesdeRespuesta(dataVenta, ["subtotal", "subtotal_0", "base_imponible"]) ?? totalFallback;
    const iva = obtenerNumeroDesdeRespuesta(dataVenta, ["iva", "impuesto", "impuestos", "valor_iva", "total_iva"]) ?? 0;
    const total = obtenerNumeroDesdeRespuesta(dataVenta, ["total", "total_venta", "importe_total"]) ?? Number((subtotal + iva).toFixed(2));

    return {
        subtotal: Number(subtotal.toFixed(2)),
        iva: Number(iva.toFixed(2)),
        total: Number(total.toFixed(2))
    };
}

function guardarUltimaVenta(payload) {
    localStorage.setItem(LAST_SALE_KEY, JSON.stringify(payload));
    renderUltimaVenta();
}

function obtenerUltimaVenta() {
    try {
        return JSON.parse(localStorage.getItem(LAST_SALE_KEY) || "null");
    } catch {
        return null;
    }
}

function renderUltimaVenta() {
    const lastSale = obtenerUltimaVenta();

    if (!lastSale?.idVenta) {
        btnReprintLast.disabled = true;
        lastSaleMeta.innerHTML = "<span>Aún no hay una venta emitida en esta sesión.</span>";
        return;
    }

    btnReprintLast.disabled = false;
    const modo = lastSale.mode === "SRI" ? "Factura SRI" : lastSale.mode === "CREDITO" ? "Crédito" : "Nota de venta";
    lastSaleMeta.innerHTML = `
        <strong>${escapeHtml(modo)}</strong>
        <span>Venta #${escapeHtml(lastSale.idVenta)} · ${escapeHtml(lastSale.numeroComprobante || "Sin número")}</span>
        <span>${escapeHtml(lastSale.fechaVenta || "")}</span>
    `;
}

function resetFormularioVenta() {
    carrito = [];
    limpiarBusqueda();
    inputBuscar.focus();

    clienteCedula.value = "";
    clienteNombre.value = "";
    clienteCorreo.value = "";
    clienteDireccion.value = "";
    clienteTelefono.value = "";

    tipoVentaSelect.value = "CONTADO";
    setTipoVenta("CONTADO");
    recargoFinanciamientoInput.value = "";
    entradaFinanciamientoInput.value = "";
    cuotasFinanciamientoInput.value = "";
    proveedorFinanciamientoSelect.value = "PAYJOY";
    pagoSelect.value = "EFECTIVO";
    montoInput.value = "";
    descuentoInput.value = "";
    motivoDescuentoInput.value = "";
    setTipoComprobante(false);
    render();
}

async function construirTicketPayload({
    data,
    idVenta,
    cliente,
    tipoVenta,
    pagos,
    modoVenta,
    entradaCredito,
    saldoCredito
}) {
    const totalCarrito = carrito.reduce((acc, item) => acc + (obtenerPrecioVigente(item) * Number(item.cantidad || 0)), 0);
    const ventaDetalle = await obtenerVentaDetalle(idVenta);
    const fuente = ventaDetalle || data;
    const valores = obtenerValoresTributarios(fuente, Number(totalCarrito.toFixed(2)));
    const detalleVenta = Array.isArray(fuente?.detalle) ? fuente.detalle : [];
    const items = detalleVenta.length
        ? detalleVenta.map((item) => ({
            codigo: item.id_producto,
            nombre: item.nombre_producto || item.nombre || "",
            cantidad: item.cantidad,
            precio: Number(item.precio_unitario ?? item.precio ?? 0),
            total: Number(item.subtotal ?? item.total ?? 0),
            imei: item.imei || ""
        }))
        : carrito.map((item) => ({
            codigo: item.id_producto,
            nombre: item.nombre,
            cantidad: item.cantidad,
            precio: obtenerPrecioVigente(item),
            total: obtenerPrecioVigente(item) * Number(item.cantidad || 0),
            imei: item.imei || ""
        }));

    return {
        titulo: modoVenta === "CREDITO" ? "RECIBO DE CREDITO" : "TICKET DE NOTA DE VENTA",
        numeroComprobante: obtenerNumeroComprobanteDesdeRespuesta(fuente),
        fecha: obtenerFechaVentaDesdeRespuesta(fuente),
        tipoVenta,
        formaPago: pagos?.[0]?.forma_pago || "EFECTIVO",
        clienteNombre: cliente?.nombres || "CONSUMIDOR FINAL",
        clienteCedula: cliente?.cedula || "",
        clienteDireccion: cliente?.direccion || "",
        clienteTelefono: cliente?.telefono || "",
        clienteCorreo: cliente?.correo || "",
        localNombre: fuente?.nombre_local || "CONNECT",
        localDireccion: fuente?.local_direccion || "",
        localTelefono: fuente?.local_telefono || "",
        subtotal: valores.subtotal,
        iva: valores.iva,
        total: valores.total,
        entradaCredito,
        saldoCredito,
        items,
        mensajeFinal: "Gracias por su compra"
    };
}

async function enviarComprobantePorCorreo(idVenta) {
    return apiFetch(`${API}/ventas/${idVenta}/comprobante/email`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({})
    });
}

async function postSri(idVenta, action, body, buttonText) {
    setButtonState(btnSri, buttonText, true);
    return apiFetch(`${API}/sri/facturas/${idVenta}/${action}`, {
        method: "POST",
        headers: body ? { "Content-Type": "application/json" } : undefined,
        body: body ? JSON.stringify(body) : undefined
    });
}

function postSriSilencioso(idVenta, action, body = null) {
    return apiFetch(`${API}/sri/facturas/${idVenta}/${action}`, {
        method: "POST",
        headers: body ? { "Content-Type": "application/json" } : undefined,
        body: body ? JSON.stringify(body) : undefined
    });
}

function mensajeSriParaCaja(error, fallback = "La factura quedó pendiente de autorización. Consulta el estado del SRI más tarde.") {
    const raw = String(error?.message || error || "");
    const normalized = raw.toUpperCase();

    if (
        normalized.includes("HTTP 302") ||
        normalized.includes("FETCH FAILED") ||
        normalized.includes("NO SE PUDO CONECTAR") ||
        normalized.includes("NO RESPONDIO") ||
        normalized.includes("TIMEOUT") ||
        normalized.includes("ECONNRESET") ||
        normalized.includes("ETIMEDOUT") ||
        normalized.includes("BAD GATEWAY") ||
        normalized.includes("502") ||
        normalized.includes("504") ||
        normalized.includes("RESPUESTA DEL SERVIDOR NO FUE VÁLIDA") ||
        normalized.includes("RESPUESTA DEL SERVIDOR NO FUE VALIDA")
    ) {
        return "El SRI no está respondiendo correctamente en este momento. La venta quedó guardada y el sistema seguirá intentando en segundo plano.";
    }

    if (normalized.includes("CLAVE ACCESO REGISTRADA")) {
        return "La factura ya fue recibida por el SRI. Consulta la autorización más tarde.";
    }

    return raw || fallback;
}

function esErrorSriTransitorio(error) {
    const raw = String(error?.message || error || "");
    const normalized = raw.toUpperCase();

    return [
        "HTTP 302",
        "FETCH FAILED",
        "NO SE PUDO CONECTAR",
        "NO RESPONDIO",
        "TIMEOUT",
        "ECONNRESET",
        "ETIMEDOUT",
        "BAD GATEWAY",
        "502",
        "504",
        "RESPUESTA DEL SERVIDOR NO FUE VÁLIDA",
        "RESPUESTA DEL SERVIDOR NO FUE VALIDA",
        "CLAVE ACCESO REGISTRADA"
    ].some((pattern) => normalized.includes(pattern));
}

async function programarReintentoSriServidor(idVenta, paso, correoDestino = "", motivo = "") {
    try {
        await postSriSilencioso(idVenta, "reintentar", {
            paso,
            correo_destino: correoDestino || undefined,
            motivo: motivo || undefined
        });
        return true;
    } catch {
        return false;
    }
}

async function devolverSriPendiente(idVenta, paso, correoDestino, error, popupWindow) {
    if (popupWindow && !popupWindow.closed) {
        popupWindow.close();
    }

    const programado = await programarReintentoSriServidor(
        idVenta,
        paso,
        correoDestino,
        error?.message || error || ""
    );

    if (programado) {
        programarConsultaAutorizacionSri(idVenta);
    }

    return {
        estado: "PENDIENTE",
        mensaje: programado
            ? mensajeSriParaCaja(error)
            : "Venta guardada. No se pudo programar el reintento automatico; revisa la factura en el modulo SRI.",
        reintentarAutorizacion: programado
    };
}

const sriAutorizacionesProgramadas = new Set();
const sriReintentosAutorizacionMs = [30000, 120000, 300000];

function esperar(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function obtenerRideSriSilencioso(idVenta) {
    try {
        const ride = await postSriSilencioso(idVenta, "ride");
        return ride?.data?.ride_url || "";
    } catch {
        return "";
    }
}

function actualizarUltimaVentaSriAutorizada(idVenta, rideUrl = "") {
    const lastSale = obtenerUltimaVenta();

    if (Number(lastSale?.idVenta || 0) !== Number(idVenta || 0)) {
        return;
    }

    guardarUltimaVenta({
        ...lastSale,
        mode: "SRI",
        rideUrl: rideUrl || lastSale.rideUrl || ""
    });
}

function programarConsultaAutorizacionSri(idVenta) {
    const id = Number(idVenta || 0);

    if (!id || sriAutorizacionesProgramadas.has(id)) {
        return;
    }

    sriAutorizacionesProgramadas.add(id);

    (async () => {
        for (const delay of sriReintentosAutorizacionMs) {
            await esperar(delay);

            try {
                const autorizacion = await postSriSilencioso(id, "autorizar");
                const estado = autorizacion?.data?.estado;

                if (estado === "AUTORIZADO") {
                    const rideUrl = await obtenerRideSriSilencioso(id);
                    actualizarUltimaVentaSriAutorizada(id, rideUrl);
                    showFeedback("Factura autorizada automáticamente por el SRI. Ya puedes reimprimir el RIDE.", "success");
                    return;
                }

                if (estado === "RECHAZADO") {
                    showFeedback("El SRI no autorizó la factura. Revisa el detalle en el módulo SRI.", "error");
                    return;
                }
            } catch {
                // El SRI puede estar intermitente; el siguiente intento vuelve a consultar.
            }
        }
    })().finally(() => {
        sriAutorizacionesProgramadas.delete(id);
    });
}

async function procesarSriVenta(idVenta, correoDestino, popupWindow) {
    try {
        await postSri(idVenta, "xml", null, "Generando XML...");
    } catch (error) {
        if (esErrorSriTransitorio(error)) {
            return devolverSriPendiente(idVenta, "xml", correoDestino, error, popupWindow);
        }
        throw error;
    }

    try {
        await postSri(idVenta, "firmar", null, "Firmando...");
    } catch (error) {
        if (esErrorSriTransitorio(error)) {
            return devolverSriPendiente(idVenta, "firmar", correoDestino, error, popupWindow);
        }
        throw error;
    }

    let envio = null;

    try {
        envio = await postSri(idVenta, "enviar", null, "Enviando al SRI...");
    } catch (error) {
        if (esErrorSriTransitorio(error)) {
            return devolverSriPendiente(idVenta, "enviar", correoDestino, error, popupWindow);
        }
        throw error;
    }

    const envioEstado = envio?.data?.estado;
    const envioCodigo = String(envio?.data?.error_codigo || "");
    const envioDetalle = envio?.data?.error_detalle || envio?.mensaje || "";

    if (envioEstado === "RECHAZADO" && envioCodigo !== "43") {
        throw new Error(envioDetalle || "El SRI rechazó la recepción");
    }

    let autorizacion = null;

    try {
        autorizacion = await postSri(idVenta, "autorizar", null, "Consultando autorización...");
    } catch (error) {
        if (esErrorSriTransitorio(error)) {
            return devolverSriPendiente(idVenta, "autorizar", correoDestino, error, popupWindow);
        }
        throw error;
    }

    if (autorizacion?.data?.pendiente_autorizacion) {
        if (popupWindow && !popupWindow.closed) {
            popupWindow.close();
        }
        await programarReintentoSriServidor(idVenta, "autorizar", correoDestino, "El SRI aun no devuelve autorizacion");
        return {
            estado: "PENDIENTE",
            mensaje: "Factura enviada al SRI, aún en proceso",
            reintentarAutorizacion: true
        };
    }

    if (autorizacion?.data?.estado !== "AUTORIZADO") {
        throw new Error(autorizacion?.mensaje || "La factura no fue autorizada");
    }

    let email = null;
    let ride = null;
    let avisoCorreo = null;

    if (correoDestino) {
        try {
            email = await postSri(idVenta, "email", {
                correo_destino: correoDestino
            }, "Enviando correo...");
        } catch (error) {
            avisoCorreo = error.message || "No se pudo enviar el correo";
        }
    }

    if (!email?.data?.ride_url) {
        try {
            ride = await postSri(idVenta, "ride", null, "Generando RIDE...");
        } catch (error) {
            if (esErrorSriTransitorio(error)) {
                await programarReintentoSriServidor(idVenta, "ride", correoDestino, error.message || "");
                return {
                    estado: "PENDIENTE",
                    mensaje: "Factura autorizada. El RIDE se generará automáticamente en segundo plano.",
                    reintentarAutorizacion: true
                };
            }
            throw error;
        }
    }

    const rideUrl = email?.data?.ride_url || ride?.data?.ride_url || "";

    if (rideUrl && popupWindow && !popupWindow.closed) {
        popupWindow.location.href = buildApiUrl(rideUrl);
    } else if (rideUrl) {
        window.open(buildApiUrl(rideUrl), "_blank", "noopener,noreferrer");
    } else if (popupWindow && !popupWindow.closed) {
        popupWindow.close();
    }

    return {
        estado: "AUTORIZADO",
        rideUrl,
        avisoCorreo
    };
}

async function reimprimirUltimoComprobante() {
    const lastSale = obtenerUltimaVenta();

    if (!lastSale?.idVenta) {
        showFeedback("No hay un comprobante reciente para reimprimir.", "error");
        return;
    }

    const popup = getPopupWindow("Reimprimiendo comprobante...");

    try {
        if (lastSale.mode === "SRI") {
            const rideUrl = lastSale.rideUrl
                ? buildApiUrl(lastSale.rideUrl)
                : buildApiUrl((await postSri(lastSale.idVenta, "ride", null, "Generando RIDE..."))?.data?.ride_url || "");

            if (!rideUrl) {
                throw new Error("No se pudo obtener el RIDE para reimpresión");
            }

            if (popup && !popup.closed) {
                popup.location.href = rideUrl;
            } else {
                window.open(rideUrl, "_blank", "noopener,noreferrer");
            }
        } else if (lastSale.ticketPayload) {
            renderTicketPopup(popup, lastSale.ticketPayload);
        } else {
            throw new Error("No hay ticket guardado para esta venta");
        }

        showFeedback("Reimpresión lista.", "success");
    } catch (error) {
        if (popup && !popup.closed) {
            popup.close();
        }
        showFeedback(error.message || "No se pudo reimprimir el comprobante.", "error");
    } finally {
        setButtonState(btnSri, "📄 Emitir con SRI", false);
    }
}

async function crearVenta(modoVenta = "NORMAL") {
    let ventaGuardada = false;
    let popupWindow = null;
    let avisoCorreoInterno = null;
    let avisoImpresion = null;

    if (!cajaActual) {
        showFeedback("Debes abrir una caja antes de vender.", "error");
        return;
    }

    if (!carrito.length) {
        showFeedback("No hay productos en el carrito.", "error");
        return;
    }

    if (modoVenta !== "SRI") {
        popupWindow = getPopupWindow("Preparando ticket...");
    } else {
        popupWindow = getPopupWindow("Preparando factura SRI...");
    }

    try {
        if (modoVenta === "CREDITO" && esFacturaActiva()) {
            throw new Error("Si el comprobante es factura, debes finalizar con Emitir con SRI");
        }

        if (modoVenta === "SRI") {
            if (!esFacturaActiva()) {
                throw new Error("Emitir con SRI solo está disponible para factura");
            }
            if (!clienteCedula.value.trim()) {
                throw new Error("Debes ingresar cédula o RUC para facturar");
            }
            if (!clienteNombre.value.trim()) {
                throw new Error("Debes ingresar el nombre del cliente");
            }
            if (!clienteDireccion.value.trim()) {
                throw new Error("Debes ingresar la dirección fiscal");
            }
        }

        const tipoVenta = tipoVentaSelect.value;
        const formaPago = pagoSelect.value || "EFECTIVO";
        const aumentoPorProducto = obtenerRecargoFinanciamiento();
        const subtotal = obtenerSubtotalCarrito();
        const descuento = obtenerDescuentoActual(subtotal);
        const total = Number((subtotal - descuento).toFixed(2));
        const entradaFinanciamiento = Number(entradaFinanciamientoInput.value || 0);
        const cuotasFinanciamiento = Number(cuotasFinanciamientoInput.value || 0);
        const proveedorFinanciamiento = proveedorFinanciamientoSelect.value || "";
        const montoPago = tipoVenta === "FINANCIADO" ? entradaFinanciamiento : total;
        const saldoCredito = Number(Math.max(total - entradaFinanciamiento, 0).toFixed(2));
        const pagos = [{
            forma_pago: formaPago,
            monto: Number(montoPago.toFixed(2))
        }];

        const clienteActivo = esFacturaActiva();
        const cliente = clienteActivo
            ? {
                cedula: clienteCedula.value.trim(),
                nombres: clienteNombre.value.trim(),
                correo: clienteCorreo.value.trim(),
                direccion: clienteDireccion.value.trim(),
                telefono: clienteTelefono.value.trim()
            }
            : {
                cedula: "9999999999",
                nombres: "CONSUMIDOR FINAL"
            };

        const body = {
            productos: carrito.map((item) => ({
                id_producto: item.id_producto,
                cantidad: item.cantidad,
                imei: item.imei,
                precio_unitario: obtenerPrecioVigente(item),
                precio_final: obtenerPrecioVigente(item),
                aumento_por_producto: aumentoPorProducto
            })),
            pagos,
            tipo_venta: tipoVenta,
            cliente,
            descuento,
            motivo_descuento: motivoDescuentoInput.value.trim() || null,
            aumento_por_producto: aumentoPorProducto
        };

        if (tipoVenta === "FINANCIADO") {
            body.entrada = entradaFinanciamiento;
            body.cuotas = cuotasFinanciamiento;
            body.proveedor = proveedorFinanciamiento;
            body.proveedor_financiamiento = proveedorFinanciamiento;
        }

        setButtonState(btnVentaNormal, modoVenta === "NORMAL" ? "Guardando..." : "🧾 Nota de venta", modoVenta === "NORMAL");
        setButtonState(btnCredito, modoVenta === "CREDITO" ? "Guardando..." : "💳 Crédito", modoVenta === "CREDITO");
        setButtonState(btnSri, modoVenta === "SRI" ? "Guardando venta..." : "📄 Emitir con SRI", modoVenta === "SRI");

        const data = await apiFetch(`${API}/ventas`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(body)
        });

        const idVenta = obtenerIdVentaDesdeRespuesta(data);
        ventaGuardada = Boolean(idVenta);

        if (!idVenta) {
            throw new Error("La venta se guardó sin devolver un id válido");
        }

        if (modoVenta !== "SRI") {
            const ticketPayload = await construirTicketPayload({
                data,
                idVenta,
                cliente,
                tipoVenta,
                pagos,
                modoVenta,
                entradaCredito: tipoVenta === "FINANCIADO" ? entradaFinanciamiento : 0,
                saldoCredito: tipoVenta === "FINANCIADO" ? saldoCredito : 0
            });

            try {
                renderTicketPopup(popupWindow, ticketPayload);
            } catch (error) {
                avisoImpresion = error.message || "No se pudo abrir la ventana de impresión";
            }

            guardarUltimaVenta({
                idVenta,
                mode: modoVenta,
                numeroComprobante: ticketPayload.numeroComprobante,
                fechaVenta: ticketPayload.fecha,
                ticketPayload
            });
        }

        if (modoVenta === "NORMAL" && idVenta && clienteActivo && cliente?.correo) {
            try {
                await enviarComprobantePorCorreo(idVenta);
            } catch (error) {
                avisoCorreoInterno = error.message || "No se pudo enviar el comprobante por correo";
            }
        }

        if (modoVenta === "SRI") {
            const sri = await procesarSriVenta(idVenta, cliente?.correo || "", popupWindow);

            guardarUltimaVenta({
                idVenta,
                mode: "SRI",
                numeroComprobante: obtenerNumeroComprobanteDesdeRespuesta(data),
                fechaVenta: obtenerFechaVentaDesdeRespuesta(data),
                rideUrl: sri.rideUrl || ""
            });

            if (sri.estado === "PENDIENTE") {
                if (sri.reintentarAutorizacion) {
                    programarConsultaAutorizacionSri(idVenta);
                }
                showFeedback(sri.mensaje || "Venta guardada. La factura fue enviada al SRI y sigue en proceso.", "success");
            } else if (sri.avisoCorreo) {
                showFeedback(`Factura autorizada. El correo no se pudo enviar: ${sri.avisoCorreo}`, "success");
            } else {
                showFeedback("Factura autorizada correctamente y abierta en nueva pestaña.", "success");
            }
        } else if (modoVenta === "CREDITO") {
            showFeedback(
                avisoImpresion
                    ? `Crédito guardado. ${avisoImpresion}. Puedes usar el botón de reimpresión.`
                    : "Crédito guardado y ticket listo para imprimir.",
                "success"
            );
        } else if (avisoCorreoInterno) {
            showFeedback(
                `Venta guardada${avisoImpresion ? `. ${avisoImpresion}. Usa el botón de reimpresión.` : " y ticket listo para imprimir."} El correo no se pudo enviar: ${avisoCorreoInterno}`,
                "success"
            );
        } else if (avisoImpresion) {
            showFeedback(`Venta guardada. ${avisoImpresion}. Puedes usar el botón de reimpresión.`, "success");
        } else {
            showFeedback("Venta guardada y ticket listo para imprimir.", "success");
        }

        await verificarCaja();
        resetFormularioVenta();
    } catch (error) {
        if (popupWindow && !popupWindow.closed) {
            popupWindow.close();
        }

        if (modoVenta === "SRI") {
            const mensajeSri = mensajeSriParaCaja(error, "No se pudo completar el proceso SRI.");
            showFeedback(
                ventaGuardada
                    ? `Venta guardada. ${mensajeSri}`
                    : mensajeSri,
                "error"
            );
        } else {
            showFeedback(error.message || "No se pudo crear la venta.", "error");
        }
    } finally {
        setButtonState(btnVentaNormal, "🧾 Nota de venta", false);
        setButtonState(btnCredito, "💳 Crédito", false);
        setButtonState(btnSri, "📄 Emitir con SRI", false);
        renderCaja();
    }
}

btnConsumidor.addEventListener("click", () => setTipoComprobante(false));
btnFactura.addEventListener("click", () => setTipoComprobante(true));
tipoVentaSelect.addEventListener("change", (event) => setTipoVenta(event.target.value));
recargoFinanciamientoInput.addEventListener("input", () => render());
entradaFinanciamientoInput.addEventListener("input", () => render());
descuentoInput.addEventListener("input", () => render());
pagoSelect.addEventListener("change", () => render());
btnBuscarManual.addEventListener("click", ejecutarBusquedaManual);
btnAbrirCaja.addEventListener("click", abrirCaja);
btnCerrarCaja.addEventListener("click", cerrarCaja);
btnVentaNormal.addEventListener("click", () => crearVenta("NORMAL"));
btnCredito.addEventListener("click", () => crearVenta("CREDITO"));
btnSri.addEventListener("click", () => crearVenta("SRI"));
btnLimpiarVenta.addEventListener("click", resetFormularioVenta);
btnReprintLast.addEventListener("click", reimprimirUltimoComprobante);

inputBuscar.addEventListener("input", async () => {
    const query = inputBuscar.value.trim();

    if (query.length < 2) {
        dropdownResultados.style.display = "none";
        resultadosBusqueda = [];
        return;
    }

    try {
        resultadosBusqueda = await buscarProductosPOS(query);
        renderDropdown();
    } catch (error) {
        showFeedback(error.message || "No se pudo buscar productos.", "error");
    }
});

inputBuscar.addEventListener("keydown", async (event) => {
    if (event.key !== "Enter") {
        return;
    }

    event.preventDefault();
    const query = inputBuscar.value.trim();

    if (!query) {
        return;
    }

    try {
        if (!resultadosBusqueda.length) {
            resultadosBusqueda = await buscarProductosPOS(query);
        }

        if (resultadosBusqueda.length) {
            agregarProducto(resultadosBusqueda[0]);
            limpiarBusqueda();
        }
    } catch (error) {
        showFeedback(error.message || "No se pudo agregar el producto.", "error");
    }
});

document.addEventListener("click", (event) => {
    if (!inputBuscar.contains(event.target) && !dropdownResultados.contains(event.target) && !btnBuscarManual.contains(event.target)) {
        dropdownResultados.style.display = "none";
    }
});

window.cambiarCantidad = cambiarCantidad;
window.cambiarPrecio = cambiarPrecio;
window.setIMEI = setIMEI;
window.eliminarProducto = eliminarProducto;

(async function initPosWeb() {
    setTipoComprobante(false);
    setTipoVenta("CONTADO");
    render();
    renderUltimaVenta();
    await verificarCaja();
    if (!cajaActual) {
        showFeedback("Abre una caja antes de registrar ventas.", "default");
    }
})();
</script>

@endsection
