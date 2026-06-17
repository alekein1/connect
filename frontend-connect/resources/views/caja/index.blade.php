@extends('caja.layout')

@section('title', 'Caja')

@section('content')

<style>
.caja-page{
    display:flex;
    flex-direction:column;
    gap:20px;
}

.hero-card,
.panel-card,
.kpi-card{
    background:linear-gradient(180deg,#081225 0%,#07101f 100%);
    border:1px solid rgba(255,255,255,.06);
    border-radius:22px;
    box-shadow:0 18px 45px rgba(0,0,0,.35);
}

.hero-card,
.panel-card{
    padding:22px;
}

.hero-card{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:18px;
    flex-wrap:wrap;
}

.hero-chip{
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
    text-transform:uppercase;
    letter-spacing:.03em;
}

.hero-title{
    margin:12px 0 8px;
    color:#fff;
    font-size:28px;
    font-weight:800;
}

.hero-copy{
    margin:0;
    max-width:760px;
    color:#94a3b8;
    line-height:1.6;
    font-size:14px;
}

.status-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:9px 13px;
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

.stats-grid{
    display:grid;
    grid-template-columns:repeat(4, minmax(0, 1fr));
    gap:14px;
}

.kpi-card{
    padding:18px;
}

.kpi-card span{
    display:block;
    color:#94a3b8;
    font-size:13px;
    margin-bottom:10px;
}

.kpi-card strong{
    display:block;
    color:#fff;
    font-size:28px;
    font-weight:800;
}

.panel-grid{
    display:grid;
    grid-template-columns:minmax(0, 1.1fr) minmax(320px, .9fr);
    gap:18px;
}

.field-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:12px;
}

.field{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.field label{
    color:#94a3b8;
    font-size:13px;
    font-weight:700;
}

.input-pro{
    width:100%;
    background:#0f172a;
    border:1px solid rgba(255,255,255,.08);
    color:#fff;
    border-radius:14px;
    padding:12px 14px;
    font-size:14px;
    outline:none;
}

.input-pro:focus{
    border-color:#60a5fa;
    box-shadow:0 0 0 3px rgba(96,165,250,.18);
}

.btn-row{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:12px;
    margin-top:16px;
}

.btn-primary,
.btn-danger,
.btn-secondary{
    border:none;
    border-radius:14px;
    padding:13px 16px;
    font-weight:800;
    cursor:pointer;
    transition:.2s ease;
}

.btn-primary{
    background:linear-gradient(135deg,#f4c842 0%,#d8a910 100%);
    color:#111827;
    box-shadow:0 10px 20px rgba(216,169,16,.18);
}

.btn-danger{
    background:linear-gradient(135deg,#ef4444 0%,#b91c1c 100%);
    color:#fff;
    box-shadow:0 10px 22px rgba(239,68,68,.15);
}

.btn-secondary{
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.08);
    color:#fff;
}

.btn-primary:hover,
.btn-danger:hover,
.btn-secondary:hover{
    transform:translateY(-1px);
    opacity:.97;
}

.btn-primary:disabled,
.btn-danger:disabled,
.btn-secondary:disabled{
    opacity:.65;
    cursor:not-allowed;
    transform:none;
}

.panel-title{
    margin:0 0 6px;
    color:#fff;
    font-size:20px;
    font-weight:800;
}

.panel-copy{
    margin:0;
    color:#94a3b8;
    font-size:14px;
    line-height:1.6;
}

.summary-list{
    display:flex;
    flex-direction:column;
    gap:12px;
}

.summary-item{
    display:flex;
    justify-content:space-between;
    gap:16px;
    padding:14px 16px;
    border-radius:16px;
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.05);
    color:#cbd5e1;
}

.summary-item strong{
    color:#fff;
}

.summary-item.total strong:last-child{
    color:#facc15;
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

@media (max-width: 1100px){
    .stats-grid{
        grid-template-columns:repeat(2, minmax(0, 1fr));
    }

    .panel-grid{
        grid-template-columns:1fr;
    }
}

@media (max-width: 767.98px){
    .hero-card,
    .panel-card{
        padding:18px;
    }

    .stats-grid,
    .field-grid,
    .btn-row{
        grid-template-columns:1fr;
    }
}
</style>

<div class="caja-page">
    <section class="hero-card">
        <div>
            <span class="hero-chip">Gestión separada de caja</span>
            <h2 class="hero-title">Abrir y cerrar caja</h2>
            <p class="hero-copy">
                Aquí manejas la apertura y el cierre de caja en una pantalla independiente del POS.
                Cuando la caja esté abierta, puedes ir al punto de venta y registrar ventas normalmente.
            </p>
        </div>
        <div>
            <span id="estadoCajaChip" class="status-pill closed">Sin caja abierta</span>
        </div>
    </section>

    <section class="stats-grid">
        <article class="kpi-card">
            <span>Apertura</span>
            <strong id="kpiApertura">$0.00</strong>
        </article>
        <article class="kpi-card">
            <span>Ventas acumuladas</span>
            <strong id="kpiVentas">$0.00</strong>
        </article>
        <article class="kpi-card">
            <span>Total esperado</span>
            <strong id="kpiEsperado">$0.00</strong>
        </article>
        <article class="kpi-card">
            <span>Diferencia estimada</span>
            <strong id="kpiDiferencia">$0.00</strong>
        </article>
    </section>

    <section class="panel-grid">
        <article class="panel-card">
            <h3 class="panel-title">Operación de caja</h3>
            <p class="panel-copy">
                Usa apertura cuando inicies el turno. Usa cierre cuando termines y quieras comparar lo esperado frente al dinero real.
            </p>

            <div class="field-grid" style="margin-top:16px;">
                <div class="field">
                    <label for="montoApertura">Monto de apertura</label>
                    <input id="montoApertura" type="number" min="0" step="0.01" class="input-pro" placeholder="Ej: 100.00">
                </div>
                <div class="field">
                    <label for="montoCierre">Monto de cierre</label>
                    <input id="montoCierre" type="number" min="0" step="0.01" class="input-pro" placeholder="Ej: 245.50">
                </div>
            </div>

            <div class="btn-row">
                <button id="btnAbrirCaja" type="button" class="btn-primary">Abrir caja</button>
                <button id="btnCerrarCaja" type="button" class="btn-danger">Cerrar caja</button>
            </div>

            <div class="btn-row" style="margin-top:12px;">
                <button id="btnIrPos" type="button" class="btn-secondary" style="grid-column:1 / -1;">Ir al punto de venta</button>
            </div>
        </article>

        <article class="panel-card">
            <h3 class="panel-title">Resumen actual</h3>
            <p class="panel-copy">Esto se actualiza con la caja abierta del usuario autenticado en su local.</p>

            <div class="summary-list" style="margin-top:16px;">
                <div class="summary-item">
                    <span>Estado</span>
                    <strong id="resumenEstado">Sin caja abierta</strong>
                </div>
                <div class="summary-item">
                    <span>Monto apertura</span>
                    <strong id="resumenApertura">$0.00</strong>
                </div>
                <div class="summary-item">
                    <span>Ventas pagadas</span>
                    <strong id="resumenVentas">$0.00</strong>
                </div>
                <div class="summary-item total">
                    <span>Total esperado</span>
                    <strong id="resumenEsperado">$0.00</strong>
                </div>
            </div>
        </article>
    </section>

    <div id="feedbackBox" class="feedback-box">
        Abre tu caja aquí y luego continúa con las ventas desde el punto de venta.
    </div>
</div>

<script>
const API = "{{ env('API_URL') }}";
const TOKEN = localStorage.getItem("token");

if (!TOKEN) {
    window.location.href = "/login";
}

let cajaActual = null;

const estadoCajaChip = document.getElementById("estadoCajaChip");
const kpiApertura = document.getElementById("kpiApertura");
const kpiVentas = document.getElementById("kpiVentas");
const kpiEsperado = document.getElementById("kpiEsperado");
const kpiDiferencia = document.getElementById("kpiDiferencia");
const resumenEstado = document.getElementById("resumenEstado");
const resumenApertura = document.getElementById("resumenApertura");
const resumenVentas = document.getElementById("resumenVentas");
const resumenEsperado = document.getElementById("resumenEsperado");
const montoApertura = document.getElementById("montoApertura");
const montoCierre = document.getElementById("montoCierre");
const btnAbrirCaja = document.getElementById("btnAbrirCaja");
const btnCerrarCaja = document.getElementById("btnCerrarCaja");
const btnIrPos = document.getElementById("btnIrPos");
const feedbackBox = document.getElementById("feedbackBox");

function money(value) {
    return `$${Number(value || 0).toFixed(2)}`;
}

function showFeedback(message, type = "default") {
    feedbackBox.className = "feedback-box";
    if (type === "error") feedbackBox.classList.add("error");
    if (type === "success") feedbackBox.classList.add("success");
    feedbackBox.textContent = message;
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

function setButtonState(button, text, disabled) {
    if (!button) return;
    if (text) button.textContent = text;
    button.disabled = Boolean(disabled);
}

function renderCaja(resumenCierre = null) {
    const apertura = Number(cajaActual?.monto_apertura || 0);
    const ventas = Number(cajaActual?.total_ventas || 0);
    const esperado = apertura + ventas;
    const diferencia = resumenCierre
        ? Number(resumenCierre.diferencia || 0)
        : 0;
    const abierta = Boolean(cajaActual);

    estadoCajaChip.className = `status-pill ${abierta ? "open" : "closed"}`;
    estadoCajaChip.textContent = abierta ? "Caja abierta" : "Sin caja abierta";

    kpiApertura.textContent = money(apertura);
    kpiVentas.textContent = money(ventas);
    kpiEsperado.textContent = money(esperado);
    kpiDiferencia.textContent = money(diferencia);

    resumenEstado.textContent = abierta ? "Caja abierta" : "Sin caja abierta";
    resumenApertura.textContent = money(apertura);
    resumenVentas.textContent = money(ventas);
    resumenEsperado.textContent = money(esperado);

    btnAbrirCaja.disabled = abierta;
    btnCerrarCaja.disabled = !abierta;
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

async function abrirCaja() {
    const monto = Number(montoApertura.value || 0);

    try {
        setButtonState(btnAbrirCaja, "Abriendo...", true);
        await apiFetch(`${API}/caja/abrir`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ monto_apertura: monto })
        });
        montoApertura.value = "";
        await verificarCaja();
        showFeedback("Caja abierta correctamente. Ya puedes ir al punto de venta.", "success");
    } catch (error) {
        showFeedback(error.message || "No se pudo abrir la caja.", "error");
    } finally {
        setButtonState(btnAbrirCaja, "Abrir caja", Boolean(cajaActual));
    }
}

async function cerrarCaja() {
    const monto = Number(montoCierre.value || 0);

    try {
        setButtonState(btnCerrarCaja, "Cerrando...", true);
        const data = await apiFetch(`${API}/caja/cerrar`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ monto_cierre: monto })
        });
        montoCierre.value = "";
        cajaActual = null;
        renderCaja(data?.resumen || null);
        showFeedback(`Caja cerrada correctamente. Diferencia final: ${money(data?.resumen?.diferencia || 0)}.`, "success");
    } catch (error) {
        showFeedback(error.message || "No se pudo cerrar la caja.", "error");
    } finally {
        setButtonState(btnCerrarCaja, "Cerrar caja", false);
        renderCaja();
    }
}

btnAbrirCaja.addEventListener("click", abrirCaja);
btnCerrarCaja.addEventListener("click", cerrarCaja);
btnIrPos.addEventListener("click", () => {
    window.location.href = "/caja/pos";
});

(async function initCaja() {
    await verificarCaja();
})();
</script>

@endsection
