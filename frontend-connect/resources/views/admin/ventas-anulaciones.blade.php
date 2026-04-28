@extends('admin.layout')

@section('title', 'Anular ventas')

@section('content')

<style>
.page-wrap{
    display:flex;
    flex-direction:column;
    gap:22px;
}

.hero-card,
.panel-card,
.detail-card{
    background:linear-gradient(180deg,#081225 0%,#07101f 100%);
    border:1px solid rgba(255,255,255,.06);
    border-radius:22px;
    box-shadow:0 18px 45px rgba(0,0,0,.35);
}

.hero-card{
    padding:24px;
    display:flex;
    justify-content:space-between;
    gap:20px;
    flex-wrap:wrap;
    align-items:flex-end;
}

.hero-eyebrow{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:6px 12px;
    border-radius:999px;
    background:rgba(248,113,113,.12);
    border:1px solid rgba(248,113,113,.16);
    color:#fecaca;
    font-size:12px;
    font-weight:700;
    letter-spacing:.03em;
    text-transform:uppercase;
}

.hero-title{
    margin:14px 0 8px;
    color:#fff;
    font-size:28px;
    font-weight:800;
}

.hero-copy{
    margin:0;
    max-width:720px;
    color:#94a3b8;
    line-height:1.6;
    font-size:14px;
}

.toolbar{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:flex-end;
}

.field-inline{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.field-inline span{
    font-size:12px;
    font-weight:700;
    color:#94a3b8;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.input-pro,
.select-pro,
.textarea-pro{
    background:#0f172a;
    border:1px solid rgba(255,255,255,.1);
    color:#fff;
    border-radius:12px;
    padding:12px 14px;
    font-size:14px;
    outline:none;
}

.input-pro,
.select-pro{
    min-width:170px;
}

.textarea-pro{
    width:100%;
    min-height:108px;
    resize:vertical;
    line-height:1.5;
}

.input-pro:focus,
.select-pro:focus,
.textarea-pro:focus{
    border-color:#60a5fa;
    box-shadow:0 0 0 3px rgba(96,165,250,.18);
}

.btn-primary-pro,
.btn-secondary-pro,
.btn-danger-pro{
    border:none;
    font-weight:800;
    padding:12px 18px;
    border-radius:12px;
    cursor:pointer;
    transition:.2s ease;
}

.btn-primary-pro{
    background:linear-gradient(135deg,#f4c842 0%,#d8a910 100%);
    color:#111827;
    box-shadow:0 10px 20px rgba(216,169,16,.20);
}

.btn-secondary-pro{
    background:#111c31;
    color:#e5e7eb;
    border:1px solid rgba(255,255,255,.08);
}

.btn-danger-pro{
    background:linear-gradient(135deg,#ef4444 0%,#b91c1c 100%);
    color:#fff;
    box-shadow:0 10px 20px rgba(185,28,28,.2);
}

.btn-primary-pro:hover,
.btn-secondary-pro:hover,
.btn-danger-pro:hover{
    transform:translateY(-1px);
    opacity:.97;
}

.btn-primary-pro:disabled,
.btn-secondary-pro:disabled,
.btn-danger-pro:disabled{
    opacity:.6;
    cursor:not-allowed;
    transform:none;
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:16px;
}

.mini-stat{
    background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));
    border:1px solid rgba(255,255,255,.05);
    border-radius:18px;
    padding:18px;
}

.mini-stat span{
    display:block;
    color:#94a3b8;
    font-size:13px;
    margin-bottom:10px;
}

.mini-stat strong{
    display:block;
    color:#fff;
    font-size:28px;
    font-weight:800;
    margin-bottom:8px;
}

.mini-stat small{
    display:block;
    color:#64748b;
    font-size:12px;
}

.panel-card,
.detail-card{
    padding:24px;
}

.panel-header,
.detail-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
    margin-bottom:18px;
    flex-wrap:wrap;
}

.panel-title,
.detail-title{
    margin:0;
    font-size:22px;
    font-weight:800;
    color:#fff;
}

.panel-subtitle,
.detail-subtitle{
    margin:8px 0 0;
    color:#94a3b8;
    font-size:14px;
}

.meta-chips{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.meta-chip{
    padding:9px 12px;
    border-radius:999px;
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.05);
    color:#cbd5e1;
    font-size:12px;
    font-weight:700;
}

.alert-pro{
    display:none;
    padding:14px 16px;
    border-radius:14px;
    font-size:14px;
    font-weight:600;
}

.alert-error{
    background:rgba(127,29,29,.28);
    border:1px solid rgba(248,113,113,.22);
    color:#fecaca;
}

.alert-success{
    background:rgba(20,83,45,.24);
    border:1px solid rgba(74,222,128,.18);
    color:#bbf7d0;
}

.table-wrap{
    width:100%;
    overflow-x:auto;
    border-radius:18px;
    border:1px solid rgba(255,255,255,.05);
    background:rgba(255,255,255,.015);
}

.table-pro{
    width:100%;
    min-width:1120px;
    border-collapse:collapse;
}

.table-pro thead th{
    text-align:left;
    padding:16px 18px;
    font-size:12px;
    font-weight:800;
    color:#94a3b8;
    text-transform:uppercase;
    letter-spacing:.04em;
    background:rgba(255,255,255,.02);
    border-bottom:1px solid rgba(255,255,255,.06);
}

.table-pro tbody td{
    padding:16px 18px;
    color:#e5e7eb;
    border-bottom:1px solid rgba(255,255,255,.05);
    vertical-align:middle;
    font-size:14px;
}

.table-pro tbody tr:hover{
    background:rgba(255,255,255,.025);
}

.table-empty{
    text-align:center;
    color:#94a3b8 !important;
    padding:36px 18px !important;
}

.stack{
    display:flex;
    flex-direction:column;
    gap:5px;
}

.stack strong{
    color:#fff;
    font-size:14px;
}

.stack small{
    color:#64748b;
    font-size:12px;
}

.badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:7px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
}

.badge-paid{
    background:rgba(34,197,94,.12);
    border:1px solid rgba(34,197,94,.18);
    color:#4ade80;
}

.badge-cancelled{
    background:rgba(248,113,113,.12);
    border:1px solid rgba(248,113,113,.18);
    color:#fca5a5;
}

.badge-neutral{
    background:rgba(148,163,184,.12);
    border:1px solid rgba(148,163,184,.18);
    color:#cbd5e1;
}

.actions-inline{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.btn-link-pro{
    border:none;
    background:#111c31;
    color:#cbd5e1;
    padding:10px 14px;
    border-radius:10px;
    cursor:pointer;
    font-weight:700;
}

.btn-link-pro:hover{
    background:#15213b;
}

.btn-link-pro.active{
    background:#1d4ed8;
    color:#fff;
}

.pagination-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-top:16px;
}

.pagination-text{
    color:#94a3b8;
    font-size:13px;
}

.detail-empty{
    padding:22px;
    border-radius:18px;
    background:rgba(255,255,255,.02);
    border:1px dashed rgba(255,255,255,.1);
    color:#94a3b8;
    line-height:1.7;
}

.detail-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:18px;
}

.detail-block{
    background:rgba(255,255,255,.02);
    border:1px solid rgba(255,255,255,.05);
    border-radius:18px;
    padding:18px;
}

.detail-block h4{
    margin:0 0 14px;
    font-size:16px;
    color:#fff;
}

.detail-list{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:12px 16px;
}

.detail-item{
    display:flex;
    flex-direction:column;
    gap:5px;
}

.detail-item span{
    font-size:12px;
    color:#94a3b8;
    text-transform:uppercase;
    letter-spacing:.03em;
}

.detail-item strong{
    color:#fff;
    font-size:14px;
    word-break:break-word;
}

.detail-table{
    width:100%;
    border-collapse:collapse;
}

.detail-table th,
.detail-table td{
    padding:12px 10px;
    border-bottom:1px solid rgba(255,255,255,.06);
    text-align:left;
    font-size:13px;
}

.detail-table th{
    color:#94a3b8;
    text-transform:uppercase;
    font-size:11px;
    letter-spacing:.04em;
}

.detail-table td{
    color:#e5e7eb;
}

.detail-actions{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.detail-note{
    color:#94a3b8;
    font-size:13px;
    line-height:1.6;
}

@media (max-width: 1180px){
    .stats-grid{
        grid-template-columns:repeat(2,1fr);
    }

    .detail-grid{
        grid-template-columns:1fr;
    }
}

@media (max-width: 768px){
    .hero-card,
    .panel-card,
    .detail-card{
        padding:18px;
    }

    .hero-title{
        font-size:24px;
    }

    .stats-grid{
        grid-template-columns:1fr;
    }

    .toolbar,
    .field-inline,
    .input-pro,
    .select-pro,
    .btn-primary-pro,
    .btn-secondary-pro,
    .btn-danger-pro{
        width:100%;
    }

    .detail-list{
        grid-template-columns:1fr;
    }
}
</style>

<div class="page-wrap">

    <div class="hero-card">
        <div>
            <span class="hero-eyebrow">Devoluciones y anulaciones</span>
            <h3 class="hero-title">Anular ventas sin borrar historial</h3>
            <p class="hero-copy">
                Busca la venta, revisa su detalle y anúlala de forma segura. El sistema marca la venta como anulada,
                devuelve el stock al inventario, reactiva el IMEI si aplica y mantiene la trazabilidad para contabilidad.
            </p>
        </div>

        <div class="toolbar">
            <label class="field-inline">
                <span>Buscar</span>
                <input id="f_buscar" class="input-pro" type="text" placeholder="Factura, cliente, IMEI o producto">
            </label>

            <label class="field-inline">
                <span>Estado</span>
                <select id="f_estado" class="select-pro">
                    <option value="PAGADA">Pagadas</option>
                    <option value="ANULADA">Anuladas</option>
                    <option value="TODAS">Todas</option>
                </select>
            </label>

            <label class="field-inline">
                <span>Desde</span>
                <input id="f_desde" class="input-pro" type="date">
            </label>

            <label class="field-inline">
                <span>Hasta</span>
                <input id="f_hasta" class="input-pro" type="date">
            </label>

            <button id="btnAplicar" type="button" class="btn-primary-pro">Buscar ventas</button>
            <button id="btnLimpiar" type="button" class="btn-secondary-pro">Limpiar</button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="mini-stat">
            <span>Registros filtrados</span>
            <strong id="kpiRegistros">0</strong>
            <small>Total de ventas encontradas con el filtro actual</small>
        </div>

        <div class="mini-stat">
            <span>Ventas pagadas</span>
            <strong id="kpiPagadas">0</strong>
            <small>Ventas todavía activas dentro del resultado</small>
        </div>

        <div class="mini-stat">
            <span>Ventas anuladas</span>
            <strong id="kpiAnuladas">0</strong>
            <small>Ventas ya revertidas y conservadas en historial</small>
        </div>

        <div class="mini-stat">
            <span>Total pagado</span>
            <strong id="kpiMontoPagado">$0.00</strong>
            <small>Monto activo en el filtro actual</small>
        </div>
    </div>

    <div id="alerta" class="alert-pro alert-error"></div>

    <div class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Ventas del local</h3>
                <p id="panelMeta" class="panel-subtitle">Consulta las ventas pagadas del local para seleccionar una anulacion.</p>
            </div>

            <div class="meta-chips">
                <span id="chipEstado" class="meta-chip">Estado: PAGADA</span>
                <span id="chipPagina" class="meta-chip">Pagina: 1</span>
                <span id="chipMontoAnulado" class="meta-chip">Monto anulado: $0.00</span>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-pro">
                <thead>
                    <tr>
                        <th>Venta</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>SRI</th>
                        <th>Usuario</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaVentas">
                    <tr>
                        <td colspan="8" class="table-empty">Consultando ventas...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar">
            <div id="paginationText" class="pagination-text">Sin resultados todavía.</div>

            <div class="actions-inline">
                <button id="btnPrev" type="button" class="btn-secondary-pro">Anterior</button>
                <button id="btnNext" type="button" class="btn-secondary-pro">Siguiente</button>
            </div>
        </div>
    </div>

    <div class="detail-card">
        <div class="detail-header">
            <div>
                <h3 class="detail-title">Detalle de venta</h3>
                <p id="detailMeta" class="detail-subtitle">Selecciona una venta para revisar el detalle y ejecutar la anulacion.</p>
            </div>

            <div class="meta-chips">
                <span id="chipVenta" class="meta-chip">Venta: --</span>
                <span id="chipCliente" class="meta-chip">Cliente: --</span>
                <span id="chipDetalleEstado" class="meta-chip">Estado: --</span>
            </div>
        </div>

        <div id="detalleVenta">
            <div class="detail-empty">
                Cuando selecciones una venta, aquí verás el comprobante, los productos, pagos y el formulario para anularla con motivo.
            </div>
        </div>
    </div>

</div>

<script>
const API = "{{ env('API_URL') }}";
const token = localStorage.getItem("token");

if (!token) {
    location.href = "/login";
}

const fBuscar = document.getElementById("f_buscar");
const fEstado = document.getElementById("f_estado");
const fDesde = document.getElementById("f_desde");
const fHasta = document.getElementById("f_hasta");
const btnAplicar = document.getElementById("btnAplicar");
const btnLimpiar = document.getElementById("btnLimpiar");
const btnPrev = document.getElementById("btnPrev");
const btnNext = document.getElementById("btnNext");

const alerta = document.getElementById("alerta");
const tablaVentas = document.getElementById("tablaVentas");
const panelMeta = document.getElementById("panelMeta");
const paginationText = document.getElementById("paginationText");
const detalleVenta = document.getElementById("detalleVenta");
const detailMeta = document.getElementById("detailMeta");

const kpiRegistros = document.getElementById("kpiRegistros");
const kpiPagadas = document.getElementById("kpiPagadas");
const kpiAnuladas = document.getElementById("kpiAnuladas");
const kpiMontoPagado = document.getElementById("kpiMontoPagado");

const chipEstado = document.getElementById("chipEstado");
const chipPagina = document.getElementById("chipPagina");
const chipMontoAnulado = document.getElementById("chipMontoAnulado");
const chipVenta = document.getElementById("chipVenta");
const chipCliente = document.getElementById("chipCliente");
const chipDetalleEstado = document.getElementById("chipDetalleEstado");

const state = {
    page: 1,
    limit: 15,
    selectedVentaId: null,
    loadingDetail: false
};

btnAplicar.addEventListener("click", aplicarFiltros);
btnLimpiar.addEventListener("click", limpiarFiltros);
btnPrev.addEventListener("click", () => cambiarPagina(-1));
btnNext.addEventListener("click", () => cambiarPagina(1));

fBuscar.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        aplicarFiltros();
    }
});

[fEstado, fDesde, fHasta].forEach((input) => {
    input.addEventListener("change", aplicarFiltros);
});

inicializar();

async function inicializar(){
    fEstado.value = "PAGADA";
    await cargarVentas();
}

function aplicarFiltros(){
    state.page = 1;
    cargarVentas();
}

function limpiarFiltros(){
    fBuscar.value = "";
    fEstado.value = "PAGADA";
    fDesde.value = "";
    fHasta.value = "";
    state.page = 1;
    ocultarAlerta();
    detalleVenta.innerHTML = `
        <div class="detail-empty">
            Cuando selecciones una venta, aquí verás el comprobante, los productos, pagos y el formulario para anularla con motivo.
        </div>
    `;
    detailMeta.textContent = "Selecciona una venta para revisar el detalle y ejecutar la anulacion.";
    chipVenta.textContent = "Venta: --";
    chipCliente.textContent = "Cliente: --";
    chipDetalleEstado.textContent = "Estado: --";
    state.selectedVentaId = null;
    cargarVentas();
}

function cambiarPagina(delta){
    const siguiente = state.page + delta;

    if (siguiente < 1) {
        return;
    }

    state.page = siguiente;
    cargarVentas();
}

async function api(url, options = {}){
    const config = {
        ...options,
        headers: {
            "Authorization": "Bearer " + token,
            "Accept": "application/json",
            ...(options.headers || {})
        }
    };

    const res = await fetch(url, config);
    const data = await res.json().catch(() => ({
        ok: false,
        mensaje: "La respuesta del servidor no fue valida"
    }));

    if (!res.ok || !data.ok) {
        throw new Error(data.mensaje || "No se pudo completar la operación");
    }

    return data;
}

async function cargarVentas(){
    ocultarAlerta();
    bloquearPaginacion(true);
    btnAplicar.disabled = true;
    btnAplicar.textContent = "Consultando...";
    tablaVentas.innerHTML = `
        <tr>
            <td colspan="8" class="table-empty">Consultando ventas...</td>
        </tr>
    `;

    try {
        const params = new URLSearchParams({
            page: state.page,
            limit: state.limit,
            estado: fEstado.value || "PAGADA"
        });

        if (fBuscar.value.trim()) {
            params.set("buscar", fBuscar.value.trim());
        }

        if (fDesde.value) {
            params.set("desde", fDesde.value);
        }

        if (fHasta.value) {
            params.set("hasta", fHasta.value);
        }

        const response = await api(`${API}/ventas?${params.toString()}`);
        renderVentas(response);
    } catch (error) {
        limpiarResumen();
        tablaVentas.innerHTML = `
            <tr>
                <td colspan="8" class="table-empty">No fue posible consultar las ventas.</td>
            </tr>
        `;
        mostrarAlerta(error.message || "Ocurrio un error inesperado al consultar ventas.");
    } finally {
        btnAplicar.disabled = false;
        btnAplicar.textContent = "Buscar ventas";
    }
}

function renderVentas(response){
    const resumen = response.resumen || {};
    const ventas = Array.isArray(response.data) ? response.data : [];
    const paginacion = response.paginacion || {};

    kpiRegistros.textContent = number(resumen.total_registros || 0);
    kpiPagadas.textContent = number(resumen.total_pagadas || 0);
    kpiAnuladas.textContent = number(resumen.total_anuladas || 0);
    kpiMontoPagado.textContent = money(resumen.monto_pagado || 0);

    chipEstado.textContent = `Estado: ${escapeHtml(fEstado.value || "PAGADA")}`;
    chipPagina.textContent = `Pagina: ${number(paginacion.page || 1)} / ${number(paginacion.total_paginas || 1)}`;
    chipMontoAnulado.textContent = `Monto anulado: ${money(resumen.monto_anulado || 0)}`;

    panelMeta.textContent = ventas.length
        ? `Se encontraron ${number(resumen.total_registros || 0)} ventas con el filtro actual.`
        : "No hay ventas para el filtro seleccionado.";

    paginationText.textContent = `Mostrando ${number(ventas.length)} registros de ${number(paginacion.total_registros || 0)}.`;

    bloquearPaginacion(false, Boolean(paginacion.has_prev), Boolean(paginacion.has_next));

    if (!ventas.length) {
        tablaVentas.innerHTML = `
            <tr>
                <td colspan="8" class="table-empty">No se encontraron ventas con esos filtros.</td>
            </tr>
        `;
        return;
    }

    tablaVentas.innerHTML = ventas.map((venta) => {
        const isActive = Number(state.selectedVentaId || 0) === Number(venta.id_venta || 0);

        return `
            <tr>
                <td>
                    <div class="stack">
                        <strong>#${escapeHtml(venta.id_venta)}</strong>
                        <small>${escapeHtml(venta.numero_comprobante || "Sin comprobante")}</small>
                    </div>
                </td>
                <td>
                    <div class="stack">
                        <strong>${escapeHtml(venta.cliente_nombres || "Consumidor final")}</strong>
                        <small>${escapeHtml(venta.cliente_cedula || "Sin identificacion")}</small>
                    </div>
                </td>
                <td>
                    <div class="stack">
                        <strong>${formatDateTime(venta.fecha_venta)}</strong>
                        <small>${escapeHtml(venta.tipo_venta || "N/D")}</small>
                    </div>
                </td>
                <td>
                    <div class="stack">
                        <strong>${money(venta.total)}</strong>
                        <small>${number(venta.total_items || 0)} items</small>
                    </div>
                </td>
                <td>${badgeEstadoVenta(venta.estado)}</td>
                <td>${badgeEstadoSri(venta.estado_documento_sri)}</td>
                <td>${escapeHtml(venta.usuario || "Sin usuario")}</td>
                <td>
                    <div class="actions-inline">
                        <button
                            type="button"
                            class="btn-link-pro ${isActive ? "active" : ""}"
                            onclick="seleccionarVenta(${Number(venta.id_venta)})"
                        >
                            Ver detalle
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

function limpiarResumen(){
    kpiRegistros.textContent = "0";
    kpiPagadas.textContent = "0";
    kpiAnuladas.textContent = "0";
    kpiMontoPagado.textContent = "$0.00";
    chipEstado.textContent = "Estado: --";
    chipPagina.textContent = "Pagina: --";
    chipMontoAnulado.textContent = "Monto anulado: $0.00";
    panelMeta.textContent = "No fue posible obtener el listado de ventas.";
    paginationText.textContent = "Sin resultados todavía.";
    bloquearPaginacion(true);
}

function bloquearPaginacion(disabled, hasPrev = false, hasNext = false){
    btnPrev.disabled = disabled || !hasPrev;
    btnNext.disabled = disabled || !hasNext;
}

function mostrarAlerta(mensaje, tipo = "error"){
    alerta.className = `alert-pro ${tipo === "success" ? "alert-success" : "alert-error"}`;
    alerta.style.display = "block";
    alerta.textContent = mensaje;
}

function ocultarAlerta(){
    alerta.style.display = "none";
    alerta.textContent = "";
}

async function seleccionarVenta(idVenta){
    state.selectedVentaId = Number(idVenta || 0);
    renderSelectedRow();
    await cargarDetalleVenta(state.selectedVentaId);
}

function renderSelectedRow(){
    const botones = document.querySelectorAll(".btn-link-pro");

    botones.forEach((boton) => {
        boton.classList.remove("active");
    });

    const activeButton = [...botones].find((button) => button.getAttribute("onclick") === `seleccionarVenta(${state.selectedVentaId})`);

    if (activeButton) {
        activeButton.classList.add("active");
    }
}

async function cargarDetalleVenta(idVenta){
    if (!idVenta) {
        return;
    }

    state.loadingDetail = true;
    detailMeta.textContent = "Consultando detalle de la venta seleccionada...";
    detalleVenta.innerHTML = `
        <div class="detail-empty">
            Consultando detalle, productos y pagos de la venta seleccionada...
        </div>
    `;

    try {
        const response = await api(`${API}/ventas/${idVenta}`);
        renderDetalleVenta(response.venta);
    } catch (error) {
        detalleVenta.innerHTML = `
            <div class="detail-empty">
                No fue posible cargar el detalle de esta venta.
            </div>
        `;
        detailMeta.textContent = "No se pudo cargar el detalle seleccionado.";
        mostrarAlerta(error.message || "No se pudo cargar el detalle de la venta.");
    } finally {
        state.loadingDetail = false;
    }
}

function renderDetalleVenta(venta){
    const detalle = Array.isArray(venta.detalle) ? venta.detalle : [];
    const pagos = Array.isArray(venta.pagos) ? venta.pagos : [];
    const puedeAnular = Boolean(venta.puede_anular);

    chipVenta.textContent = `Venta: #${escapeHtml(venta.id_venta)}`;
    chipCliente.textContent = `Cliente: ${escapeHtml(venta.cliente_nombres || "Consumidor final")}`;
    chipDetalleEstado.textContent = `Estado: ${escapeHtml(venta.estado || "N/D")}`;

    detailMeta.textContent = puedeAnular
        ? "La venta se puede anular desde este panel."
        : "Esta venta ya fue anulada o requiere otro flujo por su estado actual.";

    detalleVenta.innerHTML = `
        <div class="detail-grid">
            <div class="detail-block">
                <h4>Datos generales</h4>
                <div class="detail-list">
                    <div class="detail-item">
                        <span>Comprobante</span>
                        <strong>${escapeHtml(venta.numero_comprobante || "Sin comprobante")}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Fecha venta</span>
                        <strong>${formatDateTime(venta.fecha_venta)}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Cliente</span>
                        <strong>${escapeHtml(venta.cliente_nombres || "Consumidor final")}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Cedula/RUC</span>
                        <strong>${escapeHtml(venta.cliente_cedula || "Sin identificacion")}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Tipo venta</span>
                        <strong>${escapeHtml(venta.tipo_venta || "N/D")}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Estado SRI</span>
                        <strong>${escapeHtml(venta.estado_documento_sri || "SIN_DOCUMENTO")}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Total</span>
                        <strong>${money(venta.total)}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Registrada por</span>
                        <strong>${escapeHtml(venta.usuario_venta || "Sin usuario")}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Estado actual</span>
                        <strong>${escapeHtml(venta.estado || "N/D")}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Anulacion</span>
                        <strong>${venta.fecha_anulacion ? formatDateTime(venta.fecha_anulacion) : "No aplicada"}</strong>
                    </div>
                </div>
            </div>

            <div class="detail-block detail-actions">
                <h4>Accion de anulacion</h4>
                ${renderPanelAnulacion(venta, puedeAnular)}
            </div>

            <div class="detail-block">
                <h4>Productos de la venta</h4>
                ${renderTablaDetalle(detalle)}
            </div>

            <div class="detail-block">
                <h4>Pagos registrados</h4>
                ${renderTablaPagos(pagos)}
            </div>
        </div>
    `;

    const btnAnular = document.getElementById("btnAnularVenta");

    if (btnAnular) {
        btnAnular.addEventListener("click", () => anularVentaSeleccionada(venta.id_venta));
    }
}

function renderPanelAnulacion(venta, puedeAnular){
    if (puedeAnular) {
        return `
            <p class="detail-note">
                Esta accion no elimina registros de la base de datos. La venta cambia a estado ANULADA,
                se repone el stock y se libera el IMEI vendido.
            </p>
            <textarea id="motivoAnulacion" class="textarea-pro" placeholder="Ejemplo: Cliente devolvio el celular por cambio de equipo"></textarea>
            <button id="btnAnularVenta" type="button" class="btn-danger-pro">Anular venta y devolver stock</button>
        `;
    }

    if (venta.estado_documento_sri === "AUTORIZADO") {
        return `
            <p class="detail-note">
                Esta venta tiene un documento SRI autorizado. El sistema la bloquea para evitar una anulacion directa;
                en ese caso debes manejarla con nota de credito.
            </p>
        `;
    }

    return `
        <p class="detail-note">
            Esta venta no se puede anular desde este panel porque su estado actual es <strong>${escapeHtml(venta.estado || "N/D")}</strong>.
        </p>
        ${venta.motivo_anulacion ? `<p class="detail-note"><strong>Motivo registrado:</strong> ${escapeHtml(venta.motivo_anulacion)}</p>` : ""}
        ${venta.usuario_anulacion ? `<p class="detail-note"><strong>Usuario que anuló:</strong> ${escapeHtml(venta.usuario_anulacion)}</p>` : ""}
    `;
}

function renderTablaDetalle(detalle){
    if (!detalle.length) {
        return `<p class="detail-note">No hay productos asociados a esta venta.</p>`;
    }

    return `
        <div class="table-wrap">
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>IMEI</th>
                        <th>Cantidad</th>
                        <th>P. unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${detalle.map((item) => `
                        <tr>
                            <td>${escapeHtml(item.nombre_producto || "Producto")}</td>
                            <td>${escapeHtml(item.imei || "N/A")}</td>
                            <td>${number(item.cantidad || 0)}</td>
                            <td>${money(item.precio_unitario || 0)}</td>
                            <td>${money(Number(item.precio_unitario || 0) * Number(item.cantidad || 0))}</td>
                        </tr>
                    `).join("")}
                </tbody>
            </table>
        </div>
    `;
}

function renderTablaPagos(pagos){
    if (!pagos.length) {
        return `<p class="detail-note">No hay pagos registrados en esta venta.</p>`;
    }

    return `
        <div class="table-wrap">
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>Forma de pago</th>
                        <th>Monto</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    ${pagos.map((pago) => `
                        <tr>
                            <td>${escapeHtml(pago.forma_pago || "N/D")}</td>
                            <td>${money(pago.monto || 0)}</td>
                            <td>${formatDateTime(pago.fecha_pago)}</td>
                        </tr>
                    `).join("")}
                </tbody>
            </table>
        </div>
    `;
}

async function anularVentaSeleccionada(idVenta){
    const motivoEl = document.getElementById("motivoAnulacion");
    const btnAnular = document.getElementById("btnAnularVenta");
    const motivo = String(motivoEl?.value || "").trim();

    if (!motivo) {
        mostrarAlerta("Debes escribir el motivo de la anulacion antes de continuar.");
        motivoEl?.focus();
        return;
    }

    const confirmacion = window.confirm("Se anulará la venta, se devolverá el stock y el IMEI volverá a disponible. ¿Deseas continuar?");

    if (!confirmacion) {
        return;
    }

    if (btnAnular) {
        btnAnular.disabled = true;
        btnAnular.textContent = "Anulando...";
    }

    ocultarAlerta();

    try {
        const response = await api(`${API}/ventas/${idVenta}/anular`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ motivo })
        });

        renderDetalleVenta(response.venta);
        mostrarAlerta(response.mensaje || "Venta anulada correctamente.", "success");
        await cargarVentas();
    } catch (error) {
        mostrarAlerta(error.message || "No se pudo anular la venta.");
    } finally {
        if (btnAnular) {
            btnAnular.disabled = false;
            btnAnular.textContent = "Anular venta y devolver stock";
        }
    }
}

function badgeEstadoVenta(estado){
    if (estado === "PAGADA") {
        return `<span class="badge badge-paid">PAGADA</span>`;
    }

    if (estado === "ANULADA") {
        return `<span class="badge badge-cancelled">ANULADA</span>`;
    }

    return `<span class="badge badge-neutral">${escapeHtml(estado || "N/D")}</span>`;
}

function badgeEstadoSri(estado){
    if (estado === "AUTORIZADO") {
        return `<span class="badge badge-cancelled">AUTORIZADO</span>`;
    }

    if (estado === "SIN_DOCUMENTO") {
        return `<span class="badge badge-neutral">SIN DOCUMENTO</span>`;
    }

    return `<span class="badge badge-paid">${escapeHtml(estado || "N/D")}</span>`;
}

function money(value){
    return `$${Number(value || 0).toFixed(2)}`;
}

function number(value){
    return new Intl.NumberFormat("es-EC").format(Number(value || 0));
}

function formatDateTime(value){
    if (!value) {
        return "--";
    }

    const fecha = new Date(value);

    if (Number.isNaN(fecha.getTime())) {
        return String(value).replace("T", " ");
    }

    return new Intl.DateTimeFormat("es-EC", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit"
    }).format(fecha);
}

function escapeHtml(value){
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}
</script>

@endsection
