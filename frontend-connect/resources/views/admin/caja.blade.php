@extends('admin.layout')

@section('title', 'Caja')

@section('content')

<style>
.page-wrap{
    display:flex;
    flex-direction:column;
    gap:22px;
}

.hero-card,
.panel-card{
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
    background:rgba(59,130,246,.12);
    border:1px solid rgba(96,165,250,.15);
    color:#93c5fd;
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
    max-width:680px;
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

.input-pro{
    min-width:180px;
    background:#0f172a;
    border:1px solid rgba(255,255,255,.1);
    color:#fff;
    border-radius:12px;
    padding:12px 14px;
    font-size:14px;
    outline:none;
}

.input-pro:focus{
    border-color:#60a5fa;
    box-shadow:0 0 0 3px rgba(96,165,250,.18);
}

.btn-primary-pro{
    border:none;
    background:linear-gradient(135deg,#f4c842 0%,#d8a910 100%);
    color:#111827;
    font-weight:800;
    padding:12px 18px;
    border-radius:12px;
    cursor:pointer;
    transition:.2s ease;
    box-shadow:0 10px 20px rgba(216,169,16,.20);
}

.btn-primary-pro:hover{
    transform:translateY(-1px);
    opacity:.96;
}

.btn-primary-pro:disabled{
    opacity:.65;
    cursor:not-allowed;
    transform:none;
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
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
    font-size:30px;
    font-weight:800;
    margin-bottom:8px;
}

.mini-stat small{
    display:block;
    color:#64748b;
    font-size:12px;
}

.alert-pro{
    display:none;
    padding:14px 16px;
    border-radius:14px;
    background:rgba(127,29,29,.28);
    border:1px solid rgba(248,113,113,.22);
    color:#fecaca;
    font-size:14px;
    font-weight:600;
}

.panel-card{
    padding:24px;
}

.panel-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
    margin-bottom:18px;
    flex-wrap:wrap;
}

.panel-title{
    margin:0;
    font-size:22px;
    font-weight:800;
    color:#fff;
}

.panel-subtitle{
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

.table-wrap{
    width:100%;
    overflow-x:auto;
    border-radius:18px;
    border:1px solid rgba(255,255,255,.05);
    background:rgba(255,255,255,.015);
}

.table-pro{
    width:100%;
    min-width:1080px;
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

.badge-open{
    background:rgba(251,191,36,.12);
    border:1px solid rgba(251,191,36,.18);
    color:#facc15;
}

.badge-closed{
    background:rgba(34,197,94,.12);
    border:1px solid rgba(34,197,94,.18);
    color:#4ade80;
}

.badge-neutral{
    background:rgba(148,163,184,.12);
    border:1px solid rgba(148,163,184,.18);
    color:#cbd5e1;
}

.money-positive{
    color:#4ade80;
    font-weight:800;
}

.money-negative{
    color:#fca5a5;
    font-weight:800;
}

.money-neutral{
    color:#e5e7eb;
    font-weight:800;
}

@media (max-width: 1100px){
    .stats-grid{
        grid-template-columns:repeat(2,1fr);
    }
}

@media (max-width: 768px){
    .hero-card,
    .panel-card{
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
    .btn-primary-pro{
        width:100%;
    }
}
</style>

<div class="page-wrap">

    <div class="hero-card">
        <div>
            <span class="hero-eyebrow">Caja del local</span>
            <h3 class="hero-title">Resumen diario de caja</h3>
            <p class="hero-copy">
                Consulta las ventas del dia, el dinero con el que abrio cada caja y el monto con el que cerro,
                siempre filtrado por el local del administrador autenticado.
            </p>
        </div>

        <div class="toolbar">
            <label class="field-inline">
                <span>Fecha</span>
                <input type="date" id="f_fecha" class="input-pro">
            </label>

            <button id="btnRecargar" type="button" class="btn-primary-pro">Actualizar resumen</button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="mini-stat">
            <span>Ventas del dia</span>
            <strong id="kpiVentasDia">$0.00</strong>
            <small>Total pagado del local en la fecha consultada</small>
        </div>

        <div class="mini-stat">
            <span>Registros de ventas</span>
            <strong id="kpiRegistros">0</strong>
            <small>Comprobantes pagados en el dia</small>
        </div>

        <div class="mini-stat">
            <span>Cajas registradas</span>
            <strong id="kpiTotalCajas">0</strong>
            <small>Aperturas detectadas para la fecha</small>
        </div>

        <div class="mini-stat">
            <span>Cajas abiertas</span>
            <strong id="kpiAbiertas">0</strong>
            <small>Cajas que aun siguen operando</small>
        </div>

        <div class="mini-stat">
            <span>Total apertura</span>
            <strong id="kpiApertura">$0.00</strong>
            <small>Suma del efectivo inicial del dia</small>
        </div>

        <div class="mini-stat">
            <span>Total cierre</span>
            <strong id="kpiCierre">$0.00</strong>
            <small>Suma del efectivo declarado al cerrar</small>
        </div>
    </div>

    <div id="alerta" class="alert-pro"></div>

    <div class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Detalle de cajas</h3>
                <p id="panelMeta" class="panel-subtitle">Cargando informacion del resumen...</p>
            </div>

            <div class="meta-chips">
                <span class="meta-chip" id="chipLocal">Local: --</span>
                <span class="meta-chip" id="chipFecha">Fecha: --</span>
                <span class="meta-chip" id="chipCerradas">Cerradas: 0</span>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-pro">
                <thead>
                    <tr>
                        <th>Caja</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Apertura</th>
                        <th>Ventas</th>
                        <th>Esperado</th>
                        <th>Cierre</th>
                        <th>Diferencia</th>
                        <th>Fechas</th>
                    </tr>
                </thead>
                <tbody id="tablaCajas">
                    <tr>
                        <td colspan="9" class="table-empty">Consultando datos de caja...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
const API = "{{ env('API_URL') }}";
const token = localStorage.getItem("token");

if (!token) {
    location.href = "/login";
}

const fFecha = document.getElementById("f_fecha");
const btnRecargar = document.getElementById("btnRecargar");
const alerta = document.getElementById("alerta");
const panelMeta = document.getElementById("panelMeta");
const tablaCajas = document.getElementById("tablaCajas");
const chipLocal = document.getElementById("chipLocal");
const chipFecha = document.getElementById("chipFecha");
const chipCerradas = document.getElementById("chipCerradas");

const kpiVentasDia = document.getElementById("kpiVentasDia");
const kpiRegistros = document.getElementById("kpiRegistros");
const kpiTotalCajas = document.getElementById("kpiTotalCajas");
const kpiAbiertas = document.getElementById("kpiAbiertas");
const kpiApertura = document.getElementById("kpiApertura");
const kpiCierre = document.getElementById("kpiCierre");

btnRecargar.addEventListener("click", cargarResumen);
fFecha.addEventListener("change", cargarResumen);

inicializar();

async function inicializar(){
    fFecha.value = fechaActualGuayaquil();
    await cargarResumen();
}

async function api(url){
    const res = await fetch(url, {
        headers: {
            "Authorization": "Bearer " + token,
            "Accept": "application/json"
        }
    });

    const data = await res.json().catch(() => ({
        ok: false,
        mensaje: "La respuesta del servidor no fue valida"
    }));

    if (!res.ok || !data.ok) {
        throw new Error(data.mensaje || "No se pudo consultar el resumen de caja");
    }

    return data;
}

async function cargarResumen(){
    const fecha = fFecha.value || fechaActualGuayaquil();

    ocultarAlerta();
    pintarCargando();
    btnRecargar.disabled = true;
    btnRecargar.textContent = "Consultando...";

    try {
        const data = await api(`${API}/caja/resumen?fecha=${encodeURIComponent(fecha)}`);
        renderResumen(data);
    } catch (error) {
        limpiarResumen();
        tablaCajas.innerHTML = `
            <tr>
                <td colspan="9" class="table-empty">No fue posible cargar el resumen para esta fecha.</td>
            </tr>
        `;
        mostrarAlerta(error.message || "Ocurrio un error inesperado al consultar la caja.");
    } finally {
        btnRecargar.disabled = false;
        btnRecargar.textContent = "Actualizar resumen";
    }
}

function renderResumen(data){
    const resumen = data.resumen || {};
    const cajas = Array.isArray(data.cajas) ? data.cajas : [];
    const totalCerradas = Number(resumen.cajas_cerradas || 0);

    kpiVentasDia.textContent = formatearDinero(resumen.ventas_del_dia);
    kpiRegistros.textContent = formatearNumero(resumen.total_registros_ventas);
    kpiTotalCajas.textContent = formatearNumero(resumen.total_cajas);
    kpiAbiertas.textContent = formatearNumero(resumen.cajas_abiertas);
    kpiApertura.textContent = formatearDinero(resumen.total_apertura);
    kpiCierre.textContent = formatearDinero(resumen.total_cierre);

    chipLocal.textContent = `Local: ${data.local?.id_local ?? "--"}`;
    chipFecha.textContent = `Fecha: ${formatearFechaSimple(data.fecha)}`;
    chipCerradas.textContent = `Cerradas: ${formatearNumero(totalCerradas)}`;

    panelMeta.textContent = cajas.length
        ? `Se encontraron ${formatearNumero(cajas.length)} cajas para el ${formatearFechaSimple(data.fecha)}.`
        : `No hay aperturas de caja registradas para el ${formatearFechaSimple(data.fecha)}.`;

    if (!cajas.length) {
        tablaCajas.innerHTML = `
            <tr>
                <td colspan="9" class="table-empty">No se registran cajas para la fecha seleccionada.</td>
            </tr>
        `;
        return;
    }

    tablaCajas.innerHTML = cajas.map((caja) => {
        const diferencia = caja.diferencia === null || caja.diferencia === undefined
            ? null
            : Number(caja.diferencia);

        return `
            <tr>
                <td>
                    <div class="stack">
                        <strong>#${escapeHtml(caja.id_caja)}</strong>
                        <small>ID usuario: ${escapeHtml(caja.id_usuario)}</small>
                    </div>
                </td>
                <td>
                    <div class="stack">
                        <strong>${escapeHtml(caja.usuario || "Sin usuario")}</strong>
                        <small>Caja del local actual</small>
                    </div>
                </td>
                <td>${badgeEstado(caja.estado)}</td>
                <td>${formatearDinero(caja.monto_apertura)}</td>
                <td>${formatearDinero(caja.total_ventas)}</td>
                <td>${formatearDinero(caja.esperado)}</td>
                <td>${caja.monto_cierre === null ? "Pendiente" : formatearDinero(caja.monto_cierre)}</td>
                <td class="${claseDiferencia(diferencia)}">${diferencia === null ? "Pendiente" : formatearDinero(diferencia)}</td>
                <td>
                    <div class="stack">
                        <strong>Abre: ${formatearFechaHora(caja.fecha_apertura)}</strong>
                        <small>Cierra: ${caja.fecha_cierre ? formatearFechaHora(caja.fecha_cierre) : "Sin cierre"}</small>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

function pintarCargando(){
    panelMeta.textContent = "Consultando informacion del resumen...";
    tablaCajas.innerHTML = `
        <tr>
            <td colspan="9" class="table-empty">Consultando datos de caja...</td>
        </tr>
    `;
}

function limpiarResumen(){
    kpiVentasDia.textContent = "$0.00";
    kpiRegistros.textContent = "0";
    kpiTotalCajas.textContent = "0";
    kpiAbiertas.textContent = "0";
    kpiApertura.textContent = "$0.00";
    kpiCierre.textContent = "$0.00";
    chipLocal.textContent = "Local: --";
    chipFecha.textContent = `Fecha: ${formatearFechaSimple(fFecha.value || fechaActualGuayaquil())}`;
    chipCerradas.textContent = "Cerradas: 0";
    panelMeta.textContent = "No fue posible obtener informacion del resumen.";
}

function mostrarAlerta(mensaje){
    alerta.style.display = "block";
    alerta.textContent = mensaje;
}

function ocultarAlerta(){
    alerta.style.display = "none";
    alerta.textContent = "";
}

function badgeEstado(estado){
    if (estado === "ABIERTA") {
        return `<span class="badge badge-open">ABIERTA</span>`;
    }

    if (estado === "CERRADA") {
        return `<span class="badge badge-closed">CERRADA</span>`;
    }

    return `<span class="badge badge-neutral">${escapeHtml(estado || "N/D")}</span>`;
}

function claseDiferencia(valor){
    if (valor === null || Number.isNaN(valor)) {
        return "money-neutral";
    }

    if (valor > 0) {
        return "money-positive";
    }

    if (valor < 0) {
        return "money-negative";
    }

    return "money-neutral";
}

function formatearDinero(valor){
    return `$${Number(valor || 0).toFixed(2)}`;
}

function formatearNumero(valor){
    return new Intl.NumberFormat("es-EC").format(Number(valor || 0));
}

function formatearFechaSimple(valor){
    if (!valor) {
        return "--";
    }

    const partes = String(valor).split("-");

    if (partes.length !== 3) {
        return valor;
    }

    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function formatearFechaHora(valor){
    if (!valor) {
        return "--";
    }

    const fecha = new Date(valor);

    if (Number.isNaN(fecha.getTime())) {
        return String(valor).replace("T", " ");
    }

    return new Intl.DateTimeFormat("es-EC", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit"
    }).format(fecha);
}

function fechaActualGuayaquil(){
    const partes = new Intl.DateTimeFormat("en", {
        timeZone: "America/Guayaquil",
        year: "numeric",
        month: "2-digit",
        day: "2-digit"
    }).formatToParts(new Date());

    const year = partes.find((parte) => parte.type === "year")?.value;
    const month = partes.find((parte) => parte.type === "month")?.value;
    const day = partes.find((parte) => parte.type === "day")?.value;

    return `${year}-${month}-${day}`;
}

function escapeHtml(valor){
    return String(valor ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}
</script>

@endsection
