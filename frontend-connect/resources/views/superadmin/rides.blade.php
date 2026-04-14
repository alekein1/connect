@extends('superadmin.layout')

@section('title', 'RIDE SRI')

@section('content')

<style>
    .page-wrap{
        display:flex;
        flex-direction:column;
        gap:24px;
    }

    .panel-card{
        background:linear-gradient(180deg, #081225 0%, #07101f 100%);
        border:1px solid rgba(255,255,255,0.06);
        border-radius:22px;
        padding:24px;
        box-shadow:0 18px 45px rgba(0,0,0,.35);
    }

    .panel-header{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:16px;
        margin-bottom:20px;
        flex-wrap:wrap;
    }

    .panel-title{
        margin:0;
        font-size:24px;
        font-weight:800;
        color:#fff;
    }

    .panel-subtitle{
        margin:6px 0 0;
        color:#94a3b8;
        font-size:14px;
        line-height:1.5;
        max-width:760px;
    }

    .stats-grid{
        display:grid;
        grid-template-columns:repeat(5, minmax(0, 1fr));
        gap:16px;
    }

    .mini-stat{
        background:linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.015));
        border:1px solid rgba(255,255,255,.05);
        border-radius:18px;
        padding:18px;
    }

    .mini-stat span{
        display:block;
        color:#94a3b8;
        font-size:13px;
        margin-bottom:8px;
    }

    .mini-stat strong{
        display:block;
        font-size:28px;
        color:#fff;
        font-weight:800;
        margin-bottom:6px;
    }

    .mini-stat small{
        color:#64748b;
        font-size:12px;
    }

    .filter-grid{
        display:grid;
        grid-template-columns:repeat(6, minmax(0, 1fr));
        gap:14px;
        align-items:end;
    }

    .field{
        display:flex;
        flex-direction:column;
        gap:8px;
    }

    .field label{
        color:#cbd5e1;
        font-size:12px;
        font-weight:700;
        letter-spacing:.04em;
        text-transform:uppercase;
    }

    .field input,
    .field select{
        width:100%;
        border:1px solid rgba(255,255,255,.10);
        background:#0f172a;
        color:#fff;
        border-radius:12px;
        padding:12px 14px;
        outline:none;
        font-size:14px;
    }

    .field input:focus,
    .field select:focus{
        border-color:rgba(244,200,66,.55);
        box-shadow:0 0 0 3px rgba(244,200,66,.10);
    }

    .filter-actions{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        margin-top:16px;
    }

    .btn-primary-pro,
    .btn-secondary-pro{
        border:none;
        padding:12px 18px;
        border-radius:12px;
        cursor:pointer;
        font-weight:800;
        transition:.2s ease;
    }

    .btn-primary-pro{
        background:linear-gradient(135deg, #f4c842 0%, #d8a910 100%);
        color:#111827;
        box-shadow:0 10px 20px rgba(216,169,16,.20);
    }

    .btn-secondary-pro{
        background:rgba(255,255,255,.06);
        color:#fff;
        border:1px solid rgba(255,255,255,.08);
    }

    .btn-primary-pro:hover,
    .btn-secondary-pro:hover{
        transform:translateY(-1px);
    }

    .status-line{
        margin-top:14px;
        color:#94a3b8;
        font-size:13px;
    }

    .table-wrap{
        width:100%;
        overflow-x:auto;
        border-radius:16px;
        border:1px solid rgba(255,255,255,.05);
        background:rgba(255,255,255,.015);
    }

    .table-rides{
        width:100%;
        min-width:1320px;
        border-collapse:collapse;
    }

    .table-rides thead th{
        text-align:left;
        font-size:12px;
        font-weight:700;
        color:#94a3b8;
        padding:16px 18px;
        background:rgba(255,255,255,.02);
        border-bottom:1px solid rgba(255,255,255,.06);
        text-transform:uppercase;
        letter-spacing:.04em;
    }

    .table-rides tbody td{
        padding:16px 18px;
        color:#e5e7eb;
        border-bottom:1px solid rgba(255,255,255,.05);
        vertical-align:top;
    }

    .table-rides tbody tr:hover{
        background:rgba(255,255,255,.025);
    }

    .table-empty{
        text-align:center;
        color:#94a3b8;
        padding:34px 20px !important;
    }

    .strong{
        color:#fff;
        font-weight:800;
    }

    .muted{
        color:#64748b;
        font-size:12px;
        margin-top:4px;
        line-height:1.45;
    }

    .mono{
        font-family:"Courier New", monospace;
        color:#e2e8f0;
        font-size:12px;
        word-break:break-all;
    }

    .badge{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        padding:7px 12px;
        border-radius:999px;
        font-size:12px;
        font-weight:700;
        white-space:nowrap;
    }

    .badge-success{
        background:rgba(34,197,94,.15);
        color:#4ade80;
        border:1px solid rgba(34,197,94,.25);
    }

    .badge-warning{
        background:rgba(245,158,11,.16);
        color:#fbbf24;
        border:1px solid rgba(245,158,11,.20);
    }

    .badge-muted{
        background:rgba(148,163,184,.12);
        color:#cbd5e1;
        border:1px solid rgba(148,163,184,.18);
    }

    .badge-brand{
        background:rgba(59,130,246,.14);
        color:#93c5fd;
        border:1px solid rgba(59,130,246,.22);
    }

    .actions{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
    }

    .btn-link{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        text-decoration:none;
        border-radius:10px;
        padding:9px 12px;
        font-size:13px;
        font-weight:800;
        background:linear-gradient(135deg, #f4c842 0%, #d8a910 100%);
        color:#111827;
    }

    .btn-link.alt{
        background:rgba(255,255,255,.08);
        color:#fff;
        border:1px solid rgba(255,255,255,.08);
    }

    .btn-link.ghost{
        background:rgba(59,130,246,.14);
        color:#bfdbfe;
        border:1px solid rgba(59,130,246,.22);
    }

    @media (max-width: 1280px){
        .stats-grid{
            grid-template-columns:repeat(2, minmax(0, 1fr));
        }

        .filter-grid{
            grid-template-columns:repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px){
        .stats-grid,
        .filter-grid{
            grid-template-columns:1fr;
        }
    }
</style>

<div class="page-wrap">
    <section class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Biblioteca de RIDE</h3>
                <p class="panel-subtitle">
                    Consulta todos los PDF RIDE que ya fueron generados desde el flujo SRI. Puedes filtrarlos por local,
                    ambiente, estado o buscar por comprobante, cliente, clave de acceso o autorización.
                </p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="mini-stat">
                <span>RIDE generados</span>
                <strong id="statTotal">0</strong>
                <small>Total de PDF encontrados</small>
            </div>
            <div class="mini-stat">
                <span>Locales con RIDE</span>
                <strong id="statLocales">0</strong>
                <small>Locales distintos con documentos</small>
            </div>
            <div class="mini-stat">
                <span>Producción</span>
                <strong id="statProduccion">0</strong>
                <small>Documentos en ambiente real</small>
            </div>
            <div class="mini-stat">
                <span>Pruebas</span>
                <strong id="statPruebas">0</strong>
                <small>Documentos del ambiente de pruebas</small>
            </div>
            <div class="mini-stat">
                <span>XML disponibles</span>
                <strong id="statXml">0</strong>
                <small>Con XML autorizado encontrado</small>
            </div>
        </div>
    </section>

    <section class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Filtros</h3>
                <p class="panel-subtitle">Ajusta los criterios y vuelve a cargar la lista.</p>
            </div>
        </div>

        <div class="filter-grid">
            <div class="field">
                <label for="buscarInput">Buscar</label>
                <input id="buscarInput" type="text" placeholder="Comprobante, cliente, clave, autorización...">
            </div>

            <div class="field">
                <label for="localSelect">Local</label>
                <select id="localSelect">
                    <option value="">Todos</option>
                </select>
            </div>

            <div class="field">
                <label for="ambienteSelect">Ambiente</label>
                <select id="ambienteSelect">
                    <option value="">Todos</option>
                    <option value="PRODUCCION">PRODUCCIÓN</option>
                    <option value="PRUEBAS">PRUEBAS</option>
                </select>
            </div>

            <div class="field">
                <label for="estadoSelect">Estado</label>
                <select id="estadoSelect">
                    <option value="">Todos</option>
                    <option value="AUTORIZADO">AUTORIZADO</option>
                    <option value="RECHAZADO">RECHAZADO</option>
                    <option value="ENVIADO">ENVIADO</option>
                    <option value="XML_GENERADO">XML_GENERADO</option>
                </select>
            </div>

            <div class="field">
                <label for="fechaDesdeInput">Fecha desde</label>
                <input id="fechaDesdeInput" type="date">
            </div>

            <div class="field">
                <label for="fechaHastaInput">Fecha hasta</label>
                <input id="fechaHastaInput" type="date">
            </div>
        </div>

        <div class="filter-actions">
            <button id="btnFiltrar" class="btn-primary-pro">Aplicar filtros</button>
            <button id="btnLimpiar" class="btn-secondary-pro">Limpiar</button>
        </div>

        <div id="statusLine" class="status-line">Cargando RIDE...</div>
    </section>

    <section class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Documentos generados</h3>
                <p class="panel-subtitle">Abre el PDF, descárgalo o consulta el XML autorizado si está disponible.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-rides">
                <thead>
                    <tr>
                        <th>Local</th>
                        <th>Comprobante</th>
                        <th>Cliente</th>
                        <th>Autorización</th>
                        <th>Ambiente</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="ridesTableBody">
                    <tr>
                        <td colspan="8" class="table-empty">Cargando documentos...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
const API = "{{ env('API_URL') }}";
const token = localStorage.getItem("token");

const buscarInput = document.getElementById("buscarInput");
const localSelect = document.getElementById("localSelect");
const ambienteSelect = document.getElementById("ambienteSelect");
const estadoSelect = document.getElementById("estadoSelect");
const fechaDesdeInput = document.getElementById("fechaDesdeInput");
const fechaHastaInput = document.getElementById("fechaHastaInput");
const btnFiltrar = document.getElementById("btnFiltrar");
const btnLimpiar = document.getElementById("btnLimpiar");
const statusLine = document.getElementById("statusLine");
const ridesTableBody = document.getElementById("ridesTableBody");

const statTotal = document.getElementById("statTotal");
const statLocales = document.getElementById("statLocales");
const statProduccion = document.getElementById("statProduccion");
const statPruebas = document.getElementById("statPruebas");
const statXml = document.getElementById("statXml");

function safeText(value, fallback = "N/A") {
    if (value === null || value === undefined) {
        return fallback;
    }

    const text = String(value).trim();
    return text || fallback;
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatMoney(value) {
    const number = Number(value || 0);
    return number.toFixed(2);
}

function formatDateTime(value) {
    if (!value) {
        return "Sin fecha";
    }

    if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value)) {
        const [date, time] = value.split(" ");
        const [year, month, day] = date.split("-");
        return `${day}/${month}/${year} ${time}`;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString("es-EC", {
        timeZone: "America/Guayaquil",
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: false
    });
}

function getBadgeClass(value, type = "estado") {
    const normalized = String(value || "").toUpperCase();

    if (type === "ambiente") {
        return normalized === "PRODUCCION" ? "badge badge-success" : "badge badge-brand";
    }

    if (normalized === "AUTORIZADO") {
        return "badge badge-success";
    }

    if (normalized === "RECHAZADO") {
        return "badge badge-warning";
    }

    return "badge badge-muted";
}

function buildApiUrl(relativePath) {
    return new URL(relativePath, API).href;
}

function renderResumen(resumen = {}) {
    statTotal.textContent = resumen.total_rides || 0;
    statLocales.textContent = resumen.locales_total || 0;
    statProduccion.textContent = resumen.produccion || 0;
    statPruebas.textContent = resumen.pruebas || 0;
    statXml.textContent = resumen.xml_disponibles || 0;
}

function renderLocales(locales = []) {
    const currentValue = localSelect.value;

    localSelect.innerHTML = '<option value="">Todos</option>';

    locales.forEach((local) => {
        const option = document.createElement("option");
        option.value = local.id_local;
        option.textContent = `${local.nombre_local} (${local.rides_generados})`;
        localSelect.appendChild(option);
    });

    if ([...localSelect.options].some((option) => option.value === currentValue)) {
        localSelect.value = currentValue;
    }
}

function renderTabla(items = []) {
    if (!items.length) {
        ridesTableBody.innerHTML = `
            <tr>
                <td colspan="8" class="table-empty">No se encontraron RIDE con los filtros seleccionados.</td>
            </tr>
        `;
        return;
    }

    ridesTableBody.innerHTML = items.map((item) => {
        const rideLink = item.ride_url
            ? `
                <a class="btn-link" href="${buildApiUrl(item.ride_url)}" target="_blank" rel="noopener">
                    Abrir PDF
                </a>
                <a class="btn-link alt" href="${buildApiUrl(item.ride_url)}" download>
                    Descargar
                </a>
            `
            : '<span class="badge badge-muted">Sin PDF</span>';

        const xmlLink = item.xml_autorizado_url
            ? `
                <a class="btn-link ghost" href="${buildApiUrl(item.xml_autorizado_url)}" target="_blank" rel="noopener">
                    Ver XML
                </a>
            `
            : "";

        return `
            <tr>
                <td>
                    <div class="strong">${escapeHtml(safeText(item.nombre_local))}</div>
                    <div class="muted">Local #${item.id_local}</div>
                </td>
                <td>
                    <div class="strong">${escapeHtml(safeText(item.numero_comprobante, "Sin número"))}</div>
                    <div class="muted">Venta #${item.id_venta}</div>
                    <div class="mono">${escapeHtml(safeText(item.clave_acceso, "Sin clave"))}</div>
                </td>
                <td>
                    <div class="strong">${escapeHtml(safeText(item.cliente_nombres, "CONSUMIDOR FINAL"))}</div>
                    <div class="muted">${escapeHtml(safeText(item.cliente_cedula, "Sin identificación"))}</div>
                    <div class="muted">${escapeHtml(safeText(item.cliente_correo, "Sin correo"))}</div>
                </td>
                <td>
                    <div class="mono">${escapeHtml(safeText(item.numero_autorizacion, "Sin autorización"))}</div>
                    <div class="muted">Autorizado: ${escapeHtml(formatDateTime(item.fecha_autorizacion))}</div>
                    <div class="muted">Venta: ${escapeHtml(formatDateTime(item.fecha_venta))}</div>
                </td>
                <td>
                    <span class="${getBadgeClass(item.ambiente, "ambiente")}">${escapeHtml(safeText(item.ambiente))}</span>
                </td>
                <td>
                    <span class="${getBadgeClass(item.estado)}">${escapeHtml(safeText(item.estado))}</span>
                    <div class="muted">Venta SRI: ${escapeHtml(safeText(item.estado_sri, "N/A"))}</div>
                </td>
                <td>
                    <div class="strong">$${escapeHtml(formatMoney(item.total))}</div>
                </td>
                <td>
                    <div class="actions">
                        ${rideLink}
                        ${xmlLink}
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

function buildQueryString() {
    const params = new URLSearchParams();

    if (buscarInput.value.trim()) {
        params.set("buscar", buscarInput.value.trim());
    }

    if (localSelect.value) {
        params.set("id_local", localSelect.value);
    }

    if (ambienteSelect.value) {
        params.set("ambiente", ambienteSelect.value);
    }

    if (estadoSelect.value) {
        params.set("estado", estadoSelect.value);
    }

    if (fechaDesdeInput.value) {
        params.set("fecha_desde", fechaDesdeInput.value);
    }

    if (fechaHastaInput.value) {
        params.set("fecha_hasta", fechaHastaInput.value);
    }

    return params.toString();
}

async function cargarRides() {
    try {
        statusLine.textContent = "Consultando RIDE generados...";

        const query = buildQueryString();
        const endpoint = query
            ? `${API}/reportes/superadmin/rides?${query}`
            : `${API}/reportes/superadmin/rides`;

        const res = await fetch(endpoint, {
            headers: {
                Authorization: "Bearer " + token
            }
        });

        const payload = await res.json();

        if (!res.ok || !payload.ok) {
            throw new Error(payload?.mensaje || "No se pudo cargar la biblioteca de RIDE");
        }

        const data = payload.data || {};
        renderResumen(data.resumen || {});
        renderLocales(data.locales || []);
        renderTabla(data.items || []);

        statusLine.textContent = `${(data.items || []).length} RIDE cargados`;
    } catch (error) {
        console.error(error);
        statusLine.textContent = error.message || "Error al cargar la biblioteca de RIDE";
        renderTabla([]);
        renderResumen({});
    }
}

function limpiarFiltros() {
    buscarInput.value = "";
    localSelect.value = "";
    ambienteSelect.value = "";
    estadoSelect.value = "";
    fechaDesdeInput.value = "";
    fechaHastaInput.value = "";
    cargarRides();
}

btnFiltrar.addEventListener("click", cargarRides);
btnLimpiar.addEventListener("click", limpiarFiltros);

buscarInput.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        cargarRides();
    }
});

document.addEventListener("DOMContentLoaded", cargarRides);
</script>

@endsection
