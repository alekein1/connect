@extends('superadmin.layout')

@section('title', 'Notas de credito SRI')

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
        line-height:1.55;
        max-width:880px;
    }

    .stats-grid{
        display:grid;
        grid-template-columns:repeat(6, minmax(180px, 1fr));
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
        grid-template-columns:minmax(300px, 1.8fr) repeat(2, minmax(180px, .95fr)) repeat(2, minmax(160px, .8fr));
        gap:14px;
        align-items:end;
    }

    .filter-grid .field:first-child{
        grid-column:span 2;
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
    .field select,
    .modal-textarea{
        width:100%;
        border:1px solid rgba(255,255,255,.10);
        background:#0f172a;
        color:#fff;
        border-radius:12px;
        padding:12px 14px;
        outline:none;
        font-size:14px;
        box-sizing:border-box;
    }

    .field input:focus,
    .field select:focus,
    .modal-textarea:focus{
        border-color:rgba(59,130,246,.55);
        box-shadow:0 0 0 3px rgba(59,130,246,.10);
    }

    .filter-actions{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        margin-top:16px;
    }

    .btn-primary-pro,
    .btn-secondary-pro,
    .action-btn,
    .modal-btn{
        border:none;
        padding:12px 18px;
        border-radius:12px;
        cursor:pointer;
        font-weight:800;
        transition:.2s ease;
    }

    .btn-primary-pro,
    .modal-btn.primary{
        background:linear-gradient(135deg, #38bdf8 0%, #2563eb 100%);
        color:#fff;
        box-shadow:0 10px 20px rgba(37,99,235,.20);
    }

    .btn-secondary-pro,
    .modal-btn.secondary{
        background:rgba(255,255,255,.06);
        color:#fff;
        border:1px solid rgba(255,255,255,.08);
    }

    .btn-primary-pro:hover,
    .btn-secondary-pro:hover,
    .action-btn:hover,
    .modal-btn:hover{
        transform:translateY(-1px);
    }

    .status-line{
        margin-top:14px;
        color:#94a3b8;
        font-size:13px;
    }

    .feedback{
        display:none;
        border-radius:16px;
        padding:14px 16px;
        font-size:14px;
        line-height:1.5;
        margin-top:16px;
        border:1px solid transparent;
    }

    .feedback.show{
        display:block;
    }

    .feedback.info{
        background:rgba(59,130,246,.10);
        color:#bfdbfe;
        border-color:rgba(59,130,246,.24);
    }

    .feedback.success{
        background:rgba(34,197,94,.10);
        color:#86efac;
        border-color:rgba(34,197,94,.24);
    }

    .feedback.error{
        background:rgba(239,68,68,.10);
        color:#fca5a5;
        border-color:rgba(239,68,68,.24);
    }

    .table-wrap{
        position:relative;
        width:100%;
        max-width:100%;
        display:block;
        width:100%;
        overflow-x:auto;
        overflow-y:hidden;
        border-radius:16px;
        border:1px solid rgba(255,255,255,.05);
        background:rgba(255,255,255,.015);
        box-shadow:inset 0 1px 0 rgba(255,255,255,.04);
        padding-bottom:10px;
        scrollbar-gutter:stable both-edges;
        -webkit-overflow-scrolling:touch;
        scrollbar-width:thin;
        scrollbar-color:rgba(59,130,246,.72) rgba(15,23,42,.78);
    }

    .table-wrap.can-scroll{
        cursor:grab;
    }

    .table-wrap.can-scroll:active{
        cursor:grabbing;
    }

    .table-wrap::-webkit-scrollbar{
        height:12px;
    }

    .table-wrap::-webkit-scrollbar-track{
        background:rgba(15,23,42,.78);
        border-radius:999px;
    }

    .table-wrap::-webkit-scrollbar-thumb{
        background:rgba(59,130,246,.72);
        border-radius:999px;
        border:2px solid rgba(15,23,42,.78);
    }

    .table-creditos{
        width:max-content;
        min-width:1280px;
        border-collapse:collapse;
        table-layout:auto;
    }

    .table-creditos thead th{
        text-align:left;
        font-size:12px;
        font-weight:700;
        color:#94a3b8;
        padding:16px 18px;
        background:rgba(255,255,255,.02);
        border-bottom:1px solid rgba(255,255,255,.06);
        text-transform:uppercase;
        letter-spacing:.04em;
        white-space:nowrap;
    }

    .table-creditos tbody td{
        padding:16px 18px;
        color:#e5e7eb;
        border-bottom:1px solid rgba(255,255,255,.05);
        vertical-align:top;
    }

    .table-creditos th:nth-child(1),
    .table-creditos td:nth-child(1){
        width:340px;
    }

    .table-creditos th:nth-child(2),
    .table-creditos td:nth-child(2){
        width:220px;
    }

    .table-creditos th:nth-child(3),
    .table-creditos td:nth-child(3){
        width:320px;
    }

    .table-creditos th:nth-child(4),
    .table-creditos td:nth-child(4){
        width:260px;
    }

    .table-creditos th:nth-child(5),
    .table-creditos td:nth-child(5){
        width:170px;
    }

    .table-creditos tbody tr:hover{
        background:rgba(255,255,255,.025);
    }

    .table-empty{
        text-align:center;
        color:#94a3b8;
        padding:34px 20px !important;
    }

    .table-hint{
        margin:0;
        color:#94a3b8;
        font-size:12px;
        line-height:1.45;
    }

    .table-scroll-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        margin:0 0 14px;
        flex-wrap:wrap;
    }

    .table-scroll-actions{
        display:flex;
        align-items:center;
        gap:8px;
        flex-wrap:wrap;
    }

    .scroll-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:1px solid rgba(255,255,255,.08);
        background:rgba(15,23,42,.82);
        color:#e2e8f0;
        border-radius:999px;
        padding:9px 14px;
        font-size:12px;
        font-weight:800;
        cursor:pointer;
        transition:.2s ease;
    }

    .scroll-btn:hover:not(:disabled){
        transform:translateY(-1px);
        border-color:rgba(96,165,250,.32);
        color:#fff;
    }

    .scroll-btn:disabled{
        opacity:.42;
        cursor:not-allowed;
    }

    .strong{
        color:#fff;
        font-weight:800;
        line-height:1.3;
    }

    .muted{
        color:#94a3b8;
        font-size:12px;
        margin-top:4px;
        line-height:1.45;
    }

    .mono{
        font-family:"Courier New", monospace;
        color:#e2e8f0;
        font-size:12px;
        line-height:1.55;
        overflow-wrap:anywhere;
        word-break:break-word;
    }

    .cell-stack{
        display:grid;
        gap:6px;
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

    .badge-danger{
        background:rgba(239,68,68,.16);
        color:#fca5a5;
        border:1px solid rgba(239,68,68,.22);
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
        flex-direction:column;
        gap:8px;
        min-width:128px;
    }

    .action-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        text-decoration:none;
        padding:9px 12px;
        font-size:13px;
        font-weight:800;
        background:rgba(255,255,255,.08);
        color:#fff;
        border:1px solid rgba(255,255,255,.08);
        width:100%;
        box-sizing:border-box;
        min-height:40px;
        text-align:center;
        font-size:12px;
        line-height:1.2;
    }

    .action-btn.primary{
        background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color:#111827;
        border:none;
    }

    .action-btn.brand{
        background:rgba(59,130,246,.14);
        color:#bfdbfe;
        border:1px solid rgba(59,130,246,.22);
    }

    .action-btn.success{
        background:rgba(34,197,94,.12);
        color:#86efac;
        border:1px solid rgba(34,197,94,.22);
    }

    .action-btn[disabled],
    .modal-btn[disabled]{
        opacity:.55;
        cursor:not-allowed;
        transform:none;
    }

    .error-box{
        margin-top:10px;
        padding:10px 12px;
        border-radius:12px;
        background:rgba(239,68,68,.10);
        color:#fecaca;
        border:1px solid rgba(239,68,68,.18);
        font-size:12px;
        line-height:1.45;
    }

    .modal-backdrop{
        position:fixed;
        inset:0;
        background:rgba(2,6,23,.72);
        backdrop-filter:blur(4px);
        display:none;
        align-items:center;
        justify-content:center;
        z-index:1000;
        padding:24px;
    }

    .modal-backdrop.show{
        display:flex;
    }

    .modal-card{
        width:min(680px, 100%);
        background:linear-gradient(180deg, #0b162c 0%, #091120 100%);
        border:1px solid rgba(255,255,255,.08);
        border-radius:24px;
        padding:24px;
        box-shadow:0 24px 60px rgba(0,0,0,.45);
    }

    .modal-header{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:16px;
        margin-bottom:18px;
    }

    .modal-title{
        margin:0;
        font-size:22px;
        font-weight:800;
        color:#fff;
    }

    .modal-subtitle{
        margin:6px 0 0;
        color:#94a3b8;
        font-size:14px;
        line-height:1.55;
    }

    .modal-close{
        width:40px;
        height:40px;
        border-radius:999px;
        border:1px solid rgba(255,255,255,.08);
        background:rgba(255,255,255,.04);
        color:#fff;
        cursor:pointer;
        font-size:18px;
    }

    .modal-factura{
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:14px;
        margin-bottom:16px;
    }

    .modal-factura .mini-stat{
        padding:16px;
    }

    .modal-textarea{
        min-height:120px;
        resize:vertical;
    }

    .modal-actions{
        display:flex;
        justify-content:flex-end;
        gap:10px;
        margin-top:18px;
        flex-wrap:wrap;
    }

    @media (max-width: 1400px){
        .stats-grid{
            grid-template-columns:repeat(3, minmax(0, 1fr));
        }

        .filter-grid{
            grid-template-columns:repeat(3, minmax(0, 1fr));
        }

        .filter-grid .field:first-child{
            grid-column:span 3;
        }

        .table-creditos{
            min-width:1180px;
        }

        .table-creditos th:nth-child(1),
        .table-creditos td:nth-child(1){
            width:300px;
        }

        .table-creditos th:nth-child(2),
        .table-creditos td:nth-child(2){
            width:200px;
        }

        .table-creditos th:nth-child(3),
        .table-creditos td:nth-child(3){
            width:280px;
        }

        .table-creditos th:nth-child(4),
        .table-creditos td:nth-child(4){
            width:230px;
        }

        .table-creditos th:nth-child(5),
        .table-creditos td:nth-child(5){
            width:160px;
        }
    }

    @media (max-width: 767.98px){
        .stats-grid,
        .filter-grid,
        .modal-factura{
            grid-template-columns:1fr;
        }

        .filter-grid .field:first-child{
            grid-column:auto;
        }

        .table-scroll-head{
            flex-direction:column;
            align-items:flex-start;
        }

        .table-scroll-actions{
            width:100%;
        }

        .scroll-btn{
            flex:1;
            justify-content:center;
        }

        .table-creditos{
            min-width:1040px;
        }

        .table-creditos thead th,
        .table-creditos tbody td{
            padding:14px 14px;
        }

    }
</style>

<div class="page-wrap">
    <section class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Modulo extra de notas de credito SRI</h3>
                <p class="panel-subtitle">
                    Este panel trabaja separado de la logica de facturacion actual. Aqui el super admin puede emitir
                    notas de credito sobre facturas ya autorizadas por el SRI, consultar su estado y aplicar la
                    anulacion definitiva de la venta solo despues de la autorizacion tributaria.
                </p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="mini-stat">
                <span>Facturas autorizadas</span>
                <strong id="statTotalFacturas">0</strong>
                <small>Base disponible para anulacion</small>
            </div>
            <div class="mini-stat">
                <span>Sin nota de credito</span>
                <strong id="statSinNota">0</strong>
                <small>Listas para emitir</small>
            </div>
            <div class="mini-stat">
                <span>Pendientes SRI</span>
                <strong id="statPendientes">0</strong>
                <small>Enviadas o por consultar</small>
            </div>
            <div class="mini-stat">
                <span>Autorizadas</span>
                <strong id="statAutorizadas">0</strong>
                <small>Notas de credito aprobadas</small>
            </div>
            <div class="mini-stat">
                <span>Rechazadas</span>
                <strong id="statRechazadas">0</strong>
                <small>Con devolucion de error SRI</small>
            </div>
            <div class="mini-stat">
                <span>Anulaciones aplicadas</span>
                <strong id="statAplicadas">0</strong>
                <small>Venta e inventario actualizados</small>
            </div>
        </div>
    </section>

    <section class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Filtros de control</h3>
                <p class="panel-subtitle">
                    Busca por factura, cliente, identificacion, local o autorizacion. Tambien puedes separar las
                    facturas que todavia no tienen nota de credito o quedarte solo con las ya autorizadas.
                </p>
            </div>
        </div>

        <div class="filter-grid">
            <div class="field">
                <label for="buscarInput">Buscar</label>
                <input id="buscarInput" type="text" placeholder="Factura, cliente, autorizacion, local...">
            </div>

            <div class="field">
                <label for="localSelect">Local</label>
                <select id="localSelect">
                    <option value="">Todos</option>
                </select>
            </div>

            <div class="field">
                <label for="estadoSelect">Estado nota credito</label>
                <select id="estadoSelect">
                    <option value="">Todos</option>
                    <option value="SIN_NC">Sin nota de credito</option>
                    <option value="PENDIENTE">Pendiente SRI</option>
                    <option value="AUTORIZADO">Autorizado</option>
                    <option value="RECHAZADO">Rechazado</option>
                    <option value="ERROR">Error</option>
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

        <div id="statusLine" class="status-line">Cargando facturas autorizadas...</div>
        <div id="feedbackBox" class="feedback"></div>
    </section>

    <section class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Facturas controladas</h3>
                <p class="panel-subtitle">
                    Desde aqui puedes emitir la nota de credito, volver a consultar el estado en el SRI o abrir el XML
                    autorizado y el RIDE cuando ya existan.
                </p>
            </div>
        </div>

        <div class="table-scroll-head">
            <p class="table-hint">Desliza la tabla hacia la derecha para ver todas las acciones y documentos disponibles.</p>
            <div class="table-scroll-actions">
                <button type="button" class="scroll-btn" id="scrollTableLeft">← Izquierda</button>
                <button type="button" class="scroll-btn" id="scrollTableRight">Derecha →</button>
            </div>
        </div>

        <div class="table-wrap" id="creditosTableWrap">
            <table class="table-creditos">
                <thead>
                    <tr>
                        <th>Factura y local</th>
                        <th>Cliente</th>
                        <th>Nota de credito</th>
                        <th>Estado y aplicacion</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="creditosTableBody">
                    <tr>
                        <td colspan="5" class="table-empty">Cargando modulo...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="motivoModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal-card">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Emitir nota de credito</h3>
                <p class="modal-subtitle">
                    Confirma el motivo de anulacion. La venta solo se marcara como anulada cuando el SRI autorice la
                    nota de credito.
                </p>
            </div>

            <button id="btnCerrarModal" type="button" class="modal-close">×</button>
        </div>

        <div class="modal-factura">
            <div class="mini-stat">
                <span>Factura</span>
                <strong id="modalFacturaNumero">-</strong>
                <small id="modalFacturaMeta">Venta #0</small>
            </div>
            <div class="mini-stat">
                <span>Total</span>
                <strong id="modalFacturaTotal">$0.00</strong>
                <small id="modalFacturaCliente">Cliente</small>
            </div>
        </div>

        <div class="field">
            <label for="motivoTextarea">Motivo de la nota de credito</label>
            <textarea
                id="motivoTextarea"
                class="modal-textarea"
                maxlength="300"
                placeholder="Ejemplo: Anulacion total de factura por devolucion o regularizacion administrativa."
            ></textarea>
        </div>

        <div class="modal-actions">
            <button id="btnCancelarModal" type="button" class="modal-btn secondary">Cancelar</button>
            <button id="btnConfirmarEmitir" type="button" class="modal-btn primary">Emitir nota de credito</button>
        </div>
    </div>
</div>

<script>
const API = "{{ env('API_URL') }}";
const token = localStorage.getItem("token");

const buscarInput = document.getElementById("buscarInput");
const localSelect = document.getElementById("localSelect");
const estadoSelect = document.getElementById("estadoSelect");
const fechaDesdeInput = document.getElementById("fechaDesdeInput");
const fechaHastaInput = document.getElementById("fechaHastaInput");
const btnFiltrar = document.getElementById("btnFiltrar");
const btnLimpiar = document.getElementById("btnLimpiar");
const statusLine = document.getElementById("statusLine");
const feedbackBox = document.getElementById("feedbackBox");
const creditosTableBody = document.getElementById("creditosTableBody");
const creditosTableWrap = document.getElementById("creditosTableWrap");
const scrollTableLeft = document.getElementById("scrollTableLeft");
const scrollTableRight = document.getElementById("scrollTableRight");

const statTotalFacturas = document.getElementById("statTotalFacturas");
const statSinNota = document.getElementById("statSinNota");
const statPendientes = document.getElementById("statPendientes");
const statAutorizadas = document.getElementById("statAutorizadas");
const statRechazadas = document.getElementById("statRechazadas");
const statAplicadas = document.getElementById("statAplicadas");

const motivoModal = document.getElementById("motivoModal");
const btnCerrarModal = document.getElementById("btnCerrarModal");
const btnCancelarModal = document.getElementById("btnCancelarModal");
const btnConfirmarEmitir = document.getElementById("btnConfirmarEmitir");
const motivoTextarea = document.getElementById("motivoTextarea");
const modalFacturaNumero = document.getElementById("modalFacturaNumero");
const modalFacturaMeta = document.getElementById("modalFacturaMeta");
const modalFacturaTotal = document.getElementById("modalFacturaTotal");
const modalFacturaCliente = document.getElementById("modalFacturaCliente");

let currentVenta = null;
let currentItems = [];
let isBusy = false;

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

function buildApiUrl(relativePath) {
    return new URL(relativePath, API).href;
}

function getEstadoBadgeClass(value) {
    const normalized = String(value || "").toUpperCase();

    if (normalized === "AUTORIZADO") {
        return "badge badge-success";
    }

    if (normalized === "RECHAZADO" || normalized === "ERROR") {
        return "badge badge-danger";
    }

    if (normalized === "PENDIENTE" || normalized === "ENVIADO" || normalized === "RECIBIDO" || normalized === "FIRMADO" || normalized === "XML_GENERADO" || normalized === "BORRADOR") {
        return "badge badge-warning";
    }

    if (normalized === "SIN_NC") {
        return "badge badge-brand";
    }

    return "badge badge-muted";
}

function getAplicacionBadge(item) {
    if (item.aplico_anulacion_venta) {
        return {
            className: "badge badge-success",
            label: "Aplicada"
        };
    }

    if (String(item.estado_nota_credito || "").toUpperCase() === "AUTORIZADO") {
        return {
            className: "badge badge-brand",
            label: "Lista para aplicar"
        };
    }

    return {
        className: "badge badge-muted",
        label: "Pendiente"
    };
}

function setBusyState(value) {
    isBusy = Boolean(value);
    btnFiltrar.disabled = isBusy;
    btnLimpiar.disabled = isBusy;
    btnConfirmarEmitir.disabled = isBusy;
}

function showFeedback(message, type = "info") {
    feedbackBox.className = `feedback show ${type}`;
    feedbackBox.textContent = message;
}

function clearFeedback() {
    feedbackBox.className = "feedback";
    feedbackBox.textContent = "";
}

function renderResumen(resumen = {}) {
    statTotalFacturas.textContent = resumen.total_facturas || 0;
    statSinNota.textContent = resumen.sin_nota_credito || 0;
    statPendientes.textContent = resumen.pendientes || 0;
    statAutorizadas.textContent = resumen.autorizadas || 0;
    statRechazadas.textContent = resumen.rechazadas || 0;
    statAplicadas.textContent = resumen.aplicadas || 0;
}

function renderLocales(locales = []) {
    const currentValue = localSelect.value;

    localSelect.innerHTML = '<option value="">Todos</option>';

    locales.forEach((local) => {
        const option = document.createElement("option");
        option.value = local.id_local;
        option.textContent = local.nombre_local;
        localSelect.appendChild(option);
    });

    if ([...localSelect.options].some((option) => option.value === currentValue)) {
        localSelect.value = currentValue;
    }
}

function renderTabla(items = []) {
    currentItems = items;

    if (!items.length) {
        creditosTableBody.innerHTML = `
            <tr>
                <td colspan="5" class="table-empty">No se encontraron facturas con los filtros seleccionados.</td>
            </tr>
        `;
        requestAnimationFrame(updateTableScrollState);
        return;
    }

    creditosTableBody.innerHTML = items.map((item) => {
        const estadoBadge = item.id_nota_credito
            ? {
                className: getEstadoBadgeClass(item.estado_nota_credito),
                label: safeText(item.estado_nota_credito, "N/A")
            }
            : {
                className: getEstadoBadgeClass("SIN_NC"),
                label: "SIN_NC"
            };

        const aplicacionBadge = getAplicacionBadge(item);

        const acciones = [];

        if (item.puede_emitir) {
            acciones.push(`
                <button class="action-btn primary" data-action="emitir" data-id="${item.id_venta}">
                    Emitir NC
                </button>
            `);
        }

        if (item.id_nota_credito && (item.puede_consultar || item.puede_reaplicar)) {
            const estadoNc = String(item.estado_nota_credito || "").toUpperCase();
            acciones.push(`
                <button class="action-btn brand" data-action="consultar" data-id="${item.id_nota_credito}">
                    ${item.puede_reaplicar ? "Aplicar anulacion" : (estadoNc === "RECHAZADO" || estadoNc === "ERROR" ? "Reprocesar NC" : "Consultar SRI")}
                </button>
            `);
        }

        if (item.id_nota_credito && String(item.estado_nota_credito || "").toUpperCase() === "AUTORIZADO") {
            acciones.push(`
                <button class="action-btn" data-action="ride" data-id="${item.id_nota_credito}">
                    Actualizar RIDE
                </button>
            `);
        }

        if (item.ride_url) {
            acciones.push(`
                <a class="action-btn success" href="${buildApiUrl(item.ride_url)}" target="_blank" rel="noopener">
                    RIDE
                </a>
            `);
        }

        if (item.xml_autorizado_url) {
            acciones.push(`
                <a class="action-btn" href="${buildApiUrl(item.xml_autorizado_url)}" target="_blank" rel="noopener">
                    XML
                </a>
            `);
        }

        return `
            <tr>
                <td>
                    <div class="cell-stack">
                        <div class="strong">${escapeHtml(safeText(item.nombre_local, "Sin local"))}</div>
                        <div class="muted">Local #${item.id_local}</div>
                        <div class="strong">${escapeHtml(safeText(item.numero_comprobante_factura, "Sin numero"))}</div>
                        <div class="muted">Venta #${item.id_venta} · $${escapeHtml(formatMoney(item.total_factura))}</div>
                        <div class="muted">Fecha venta: ${escapeHtml(formatDateTime(item.fecha_venta))}</div>
                        <div class="mono">${escapeHtml(safeText(item.factura_numero_autorizacion, "Sin autorizacion"))}</div>
                    </div>
                </td>
                <td>
                    <div class="cell-stack">
                        <div class="strong">${escapeHtml(safeText(item.cliente_nombres, "CONSUMIDOR FINAL"))}</div>
                        <div class="muted">${escapeHtml(safeText(item.cliente_cedula, "Sin identificacion"))}</div>
                    </div>
                </td>
                <td>
                    <div class="cell-stack">
                        <div class="strong">${escapeHtml(safeText(item.numero_comprobante_nota_credito || "Sin emitir", "Sin emitir"))}</div>
                        <div class="muted">Motivo: ${escapeHtml(safeText(item.motivo_nota_credito, "Pendiente de registrar"))}</div>
                        <div class="muted">Fecha NC: ${escapeHtml(formatDateTime(item.fecha_emision_nota_credito))}</div>
                        <div class="muted">Valor: $${escapeHtml(formatMoney(item.valor_modificacion))}</div>
                    </div>
                </td>
                <td>
                    <div class="cell-stack">
                        <span class="${estadoBadge.className}">${escapeHtml(estadoBadge.label)}</span>
                        <span class="${aplicacionBadge.className}">${escapeHtml(aplicacionBadge.label)}</span>
                        <div class="muted">Factura SRI: ${escapeHtml(safeText(item.estado_sri, "N/A"))}</div>
                        <div class="muted">Autorizacion NC: ${escapeHtml(formatDateTime(item.fecha_autorizacion_nota_credito))}</div>
                        <div class="muted">Estado venta: ${escapeHtml(safeText(item.venta_estado, "N/A"))}</div>
                        ${item.error_detalle ? `<div class="error-box">${escapeHtml(item.error_detalle)}</div>` : ""}
                    </div>
                </td>
                <td>
                    <div class="actions">
                        ${acciones.length ? acciones.join("") : '<span class="badge badge-muted">Sin acciones</span>'}
                    </div>
                </td>
            </tr>
        `;
    }).join("");

    requestAnimationFrame(updateTableScrollState);
}

function updateTableScrollState() {
    if (!creditosTableWrap || !scrollTableLeft || !scrollTableRight) {
        return;
    }

    const maxScroll = Math.max(0, creditosTableWrap.scrollWidth - creditosTableWrap.clientWidth);
    const currentScroll = creditosTableWrap.scrollLeft;
    const hasOverflow = maxScroll > 12;

    creditosTableWrap.classList.toggle("can-scroll", hasOverflow);
    scrollTableLeft.disabled = !hasOverflow || currentScroll <= 8;
    scrollTableRight.disabled = !hasOverflow || currentScroll >= (maxScroll - 8);
}

function moveTableScroll(direction) {
    if (!creditosTableWrap) {
        return;
    }

    const distance = Math.max(320, creditosTableWrap.clientWidth * 0.42) * direction;
    creditosTableWrap.scrollBy({
        left: distance,
        behavior: "smooth"
    });
}

function buildQueryString() {
    const params = new URLSearchParams();

    if (buscarInput.value.trim()) {
        params.set("buscar", buscarInput.value.trim());
    }

    if (localSelect.value) {
        params.set("id_local", localSelect.value);
    }

    if (estadoSelect.value) {
        params.set("estado_nota_credito", estadoSelect.value);
    }

    if (fechaDesdeInput.value) {
        params.set("fecha_desde", fechaDesdeInput.value);
    }

    if (fechaHastaInput.value) {
        params.set("fecha_hasta", fechaHastaInput.value);
    }

    return params.toString();
}

function openEmitirModal(item) {
    currentVenta = item;
    modalFacturaNumero.textContent = safeText(item.numero_comprobante_factura, "Sin numero");
    modalFacturaMeta.textContent = `Venta #${item.id_venta}`;
    modalFacturaTotal.textContent = `$${formatMoney(item.total_factura)}`;
    modalFacturaCliente.textContent = safeText(item.cliente_nombres, "CONSUMIDOR FINAL");
    motivoTextarea.value = `Anulacion total de factura ${safeText(item.numero_comprobante_factura, "")} por regularizacion administrativa.`;
    motivoModal.classList.add("show");
    motivoModal.setAttribute("aria-hidden", "false");
    motivoTextarea.focus();
}

function closeEmitirModal() {
    currentVenta = null;
    motivoTextarea.value = "";
    motivoModal.classList.remove("show");
    motivoModal.setAttribute("aria-hidden", "true");
}

async function cargarFacturas() {
    try {
        clearFeedback();
        statusLine.textContent = "Consultando facturas autorizadas...";

        const query = buildQueryString();
        const endpoint = query
            ? `${API}/notas-credito/superadmin/facturas?${query}`
            : `${API}/notas-credito/superadmin/facturas`;

        const res = await fetch(endpoint, {
            headers: {
                Authorization: "Bearer " + token
            }
        });

        const payload = await res.json();

        if (!res.ok || !payload.ok) {
            throw new Error(payload?.mensaje || "No se pudo cargar el modulo de notas de credito");
        }

        const data = payload.data || {};
        renderResumen(data.resumen || {});
        renderLocales(data.locales || []);
        renderTabla(data.items || []);
        statusLine.textContent = `${(data.items || []).length} facturas cargadas`;
    } catch (error) {
        console.error(error);
        statusLine.textContent = error.message || "Error al cargar facturas";
        renderResumen({});
        renderTabla([]);
        showFeedback(error.message || "No se pudo cargar la informacion", "error");
    }
}

async function emitirNotaCredito(idVenta, motivo) {
    try {
        setBusyState(true);
        clearFeedback();
        statusLine.textContent = `Emitiendo nota de credito para la venta #${idVenta}...`;

        const res = await fetch(`${API}/notas-credito/superadmin/ventas/${idVenta}/emitir`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: "Bearer " + token
            },
            body: JSON.stringify({ motivo })
        });

        const payload = await res.json();

        if (!res.ok || !payload.ok) {
            throw new Error(payload?.mensaje || "No se pudo emitir la nota de credito");
        }

        const estadoFinal = safeText(payload?.data?.estado || payload?.data?.estado_nota_credito, "PROCESADA");
        const detalleRechazo = safeText(payload?.data?.error_detalle, "");
        statusLine.textContent = `Venta #${idVenta} actualizada con nota de credito`;
        closeEmitirModal();
        await cargarFacturas();
        const feedbackType = ["RECHAZADO", "ERROR"].includes(estadoFinal.toUpperCase()) ? "error" : "success";
        showFeedback(
            detalleRechazo
                ? `Nota de credito procesada correctamente. Estado actual: ${estadoFinal}. Detalle SRI: ${detalleRechazo}`
                : `Nota de credito procesada correctamente. Estado actual: ${estadoFinal}.`,
            feedbackType
        );
    } catch (error) {
        console.error(error);
        statusLine.textContent = error.message || "Error al emitir la nota de credito";
        showFeedback(error.message || "No se pudo emitir la nota de credito", "error");
    } finally {
        setBusyState(false);
    }
}

async function consultarNotaCredito(idNotaCredito, { aplicar = true } = {}) {
    try {
        setBusyState(true);
        clearFeedback();
        statusLine.textContent = `Consultando nota de credito #${idNotaCredito} en el SRI...`;
        const query = aplicar ? "" : "?aplicar=0";

        const res = await fetch(`${API}/notas-credito/superadmin/${idNotaCredito}/consultar${query}`, {
            method: "POST",
            headers: {
                Authorization: "Bearer " + token
            }
        });

        const payload = await res.json();

        if (!res.ok || !payload.ok) {
            throw new Error(payload?.mensaje || "No se pudo consultar la nota de credito");
        }

        const estadoFinal = safeText(payload?.data?.estado || payload?.data?.estado_nota_credito, "ACTUALIZADA");
        const detalleRechazo = safeText(payload?.data?.error_detalle, "");
        const aplicacion = payload?.data?.aplicacion?.ya_aplicada
            ? " La anulacion de la venta ya quedo aplicada."
            : payload?.data?.aplicacion?.aplicada
                ? " La anulacion de la venta se aplico correctamente."
                : "";

        statusLine.textContent = `Nota de credito #${idNotaCredito} consultada correctamente`;
        await cargarFacturas();
        const feedbackType = ["RECHAZADO", "ERROR"].includes(estadoFinal.toUpperCase()) ? "error" : "success";
        showFeedback(
            detalleRechazo
                ? `Consulta completada. Estado actual: ${estadoFinal}.${aplicacion} Detalle SRI: ${detalleRechazo}`
                : `Consulta completada. Estado actual: ${estadoFinal}.${aplicacion}`,
            feedbackType
        );
    } catch (error) {
        console.error(error);
        statusLine.textContent = error.message || "Error al consultar la nota de credito";
        showFeedback(error.message || "No se pudo consultar la nota de credito", "error");
    } finally {
        setBusyState(false);
    }
}

function limpiarFiltros() {
    buscarInput.value = "";
    localSelect.value = "";
    estadoSelect.value = "";
    fechaDesdeInput.value = "";
    fechaHastaInput.value = "";
    clearFeedback();
    cargarFacturas();
}

btnFiltrar.addEventListener("click", cargarFacturas);
btnLimpiar.addEventListener("click", limpiarFiltros);
btnCerrarModal.addEventListener("click", closeEmitirModal);
btnCancelarModal.addEventListener("click", closeEmitirModal);
scrollTableLeft.addEventListener("click", () => moveTableScroll(-1));
scrollTableRight.addEventListener("click", () => moveTableScroll(1));
creditosTableWrap.addEventListener("scroll", updateTableScrollState);
window.addEventListener("resize", () => requestAnimationFrame(updateTableScrollState));

btnConfirmarEmitir.addEventListener("click", () => {
    if (!currentVenta) {
        return;
    }

    const motivo = motivoTextarea.value.trim();

    if (!motivo) {
        showFeedback("Debes escribir el motivo de la nota de credito antes de continuar.", "error");
        motivoTextarea.focus();
        return;
    }

    emitirNotaCredito(currentVenta.id_venta, motivo);
});

motivoModal.addEventListener("click", (event) => {
    if (event.target === motivoModal) {
        closeEmitirModal();
    }
});

buscarInput.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        cargarFacturas();
    }
});

creditosTableBody.addEventListener("click", (event) => {
    const button = event.target.closest("[data-action]");

    if (!button || isBusy) {
        return;
    }

    const action = button.dataset.action;
    const id = Number(button.dataset.id || 0);

    if (!id) {
        return;
    }

    if (action === "emitir") {
        const item = currentItems.find((row) => Number(row.id_venta) === id);

        if (item) {
            clearFeedback();
            openEmitirModal(item);
        }
        return;
    }

    if (action === "consultar") {
        consultarNotaCredito(id);
        return;
    }

    if (action === "ride") {
        consultarNotaCredito(id, { aplicar: false });
    }
});

document.addEventListener("DOMContentLoaded", () => {
    updateTableScrollState();
    cargarFacturas();
});
</script>

@endsection
