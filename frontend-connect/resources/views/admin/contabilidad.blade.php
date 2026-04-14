@extends('admin.layout')

@section('title', 'Contabilidad')

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
    background:rgba(14,165,233,.12);
    border:1px solid rgba(56,189,248,.18);
    color:#7dd3fc;
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
    max-width:700px;
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
    font-size:30px;
    font-weight:800;
    margin-bottom:8px;
}

.mini-stat small{
    display:block;
    color:#64748b;
    font-size:12px;
}

.money-positive{
    color:#4ade80 !important;
}

.money-warning{
    color:#facc15 !important;
}

.money-danger{
    color:#fca5a5 !important;
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

.today-grid,
.bottom-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
}

.split-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:16px;
}

.metric-box{
    padding:16px;
    border-radius:18px;
    background:rgba(255,255,255,.025);
    border:1px solid rgba(255,255,255,.05);
}

.metric-box span{
    display:block;
    color:#94a3b8;
    font-size:12px;
    margin-bottom:10px;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.metric-box strong{
    display:block;
    color:#fff;
    font-size:28px;
    font-weight:800;
    margin-bottom:6px;
}

.metric-box small{
    color:#64748b;
    font-size:12px;
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
    min-width:780px;
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
    gap:4px;
}

.stack strong{
    color:#fff;
    font-size:14px;
}

.stack small{
    color:#64748b;
    font-size:12px;
}

@media (max-width: 1200px){
    .stats-grid{
        grid-template-columns:repeat(2,1fr);
    }

    .today-grid,
    .bottom-grid,
    .split-grid{
        grid-template-columns:1fr;
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
            <span class="hero-eyebrow">Control financiero</span>
            <h3 class="hero-title">Resumen contable del local</h3>
            <p class="hero-copy">
                Consulta ventas, cartera, gastos, utilidad neta y distribucion del dinero del local actual
                sin mezclar informacion de otros locales.
            </p>
        </div>

        <div class="toolbar">
            <label class="field-inline">
                <span>Desde</span>
                <input type="date" id="f_desde" class="input-pro">
            </label>

            <label class="field-inline">
                <span>Hasta</span>
                <input type="date" id="f_hasta" class="input-pro">
            </label>

            <button id="btnRecargar" type="button" class="btn-primary-pro">Actualizar resumen</button>
        </div>
    </div>

    <div id="alerta" class="alert-pro"></div>

    <div class="stats-grid">
        <div class="mini-stat">
            <span>Ventas facturadas</span>
            <strong id="kpiVentas">$0.00</strong>
            <small>Total vendido en el periodo</small>
        </div>

        <div class="mini-stat">
            <span>Cobrado real</span>
            <strong id="kpiCobrado" class="money-positive">$0.00</strong>
            <small>Dinero realmente ingresado</small>
        </div>

        <div class="mini-stat">
            <span>Por cobrar</span>
            <strong id="kpiPorCobrar" class="money-warning">$0.00</strong>
            <small>Saldo pendiente en financiados</small>
        </div>

        <div class="mini-stat">
            <span>Ganancia bruta</span>
            <strong id="kpiGanancia">$0.00</strong>
            <small>Ganancia antes de gastos</small>
        </div>

        <div class="mini-stat">
            <span>Gastos</span>
            <strong id="kpiGastos" class="money-danger">$0.00</strong>
            <small>Salidas registradas del periodo</small>
        </div>

        <div class="mini-stat">
            <span>Utilidad neta</span>
            <strong id="kpiUtilidadNeta">$0.00</strong>
            <small>Ganancia menos gastos</small>
        </div>

        <div class="mini-stat">
            <span>Flujo de caja neto</span>
            <strong id="kpiFlujoNeto">$0.00</strong>
            <small>Cobrado menos gastos</small>
        </div>

        <div class="mini-stat">
            <span>Inversion actual</span>
            <strong id="kpiInventario">$0.00</strong>
            <small>Valor actual del inventario</small>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Estado general</h3>
                <p id="metaResumen" class="panel-subtitle">Consultando informacion contable...</p>
            </div>

            <div class="meta-chips">
                <span class="meta-chip" id="chipPeriodo">Periodo: --</span>
                <span class="meta-chip" id="chipVentas">Ventas: 0</span>
                <span class="meta-chip" id="chipGastos">Gastos: 0</span>
            </div>
        </div>

        <div class="today-grid">
            <div class="metric-box">
                <span>Hoy</span>
                <strong id="todayVentas">$0.00</strong>
                <small id="todayOperaciones">0 operaciones</small>
            </div>

            <div class="metric-box">
                <span>Cobrado hoy</span>
                <strong id="todayCobrado" class="money-positive">$0.00</strong>
                <small id="todayGastos">$0.00 en gastos del dia</small>
            </div>

            <div class="metric-box">
                <span>Utilidad neta hoy</span>
                <strong id="todayUtilidad">$0.00</strong>
                <small id="todayPorCobrar">$0.00 por cobrar hoy</small>
            </div>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Ultimos 7 dias</h3>
                <p class="panel-subtitle">Comportamiento de ventas, cartera y gastos del local.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-pro">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Operaciones</th>
                        <th>Ventas</th>
                        <th>Cobrado estimado</th>
                        <th>Cartera generada</th>
                        <th>Gastos</th>
                    </tr>
                </thead>
                <tbody id="tablaPeriodo">
                    <tr>
                        <td colspan="6" class="table-empty">Consultando tendencia semanal...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bottom-grid">
        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Proveedores financiados</h3>
                    <p class="panel-subtitle">PayJoy, Happy y otros proveedores del periodo.</p>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Operaciones</th>
                            <th>Colocado</th>
                            <th>Entrada</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody id="tablaProveedores">
                        <tr>
                            <td colspan="5" class="table-empty">Cargando proveedores...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Formas de cobro</h3>
                    <p class="panel-subtitle">Distribucion de ingresos ya cobrados.</p>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Metodo</th>
                            <th>Movimientos</th>
                            <th>Total cobrado</th>
                        </tr>
                    </thead>
                    <tbody id="tablaCobros">
                        <tr>
                            <td colspan="3" class="table-empty">Cargando formas de cobro...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Gastos por categoria</h3>
                    <p class="panel-subtitle">Impacto de salidas por categoria del periodo.</p>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Registros</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="tablaCategoriasGasto">
                        <tr>
                            <td colspan="3" class="table-empty">Cargando categorias...</td>
                        </tr>
                    </tbody>
                </table>
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

const fDesde = document.getElementById("f_desde");
const fHasta = document.getElementById("f_hasta");
const btnRecargar = document.getElementById("btnRecargar");
const alerta = document.getElementById("alerta");

btnRecargar.addEventListener("click", cargarContabilidad);

inicializar();

async function inicializar(){
    const hoy = fechaActualGuayaquil();
    fHasta.value = hoy;
    fDesde.value = primerDiaMes(hoy);
    await cargarContabilidad();
}

async function apiGet(url){
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
        throw new Error(data.mensaje || "No se pudo cargar la informacion");
    }

    return data;
}

async function cargarContabilidad(){
    ocultarAlerta();
    pintarCarga();
    btnRecargar.disabled = true;
    btnRecargar.textContent = "Actualizando...";

    try {
        const query = construirQueryPeriodo();
        const [resumen, dashboard] = await Promise.all([
            apiGet(`${API}/contabilidad/resumen${query}`),
            apiGet(`${API}/contabilidad/dashboard`)
        ]);

        renderResumen(resumen);
        renderDashboard(dashboard);
    } catch (error) {
        limpiarResumen();
        mostrarAlerta(error.message || "No fue posible obtener la contabilidad.");
    } finally {
        btnRecargar.disabled = false;
        btnRecargar.textContent = "Actualizar resumen";
    }
}

function construirQueryPeriodo(){
    const params = new URLSearchParams();

    if (fDesde.value) params.set("desde", fDesde.value);
    if (fHasta.value) params.set("hasta", fHasta.value);

    const query = params.toString();
    return query ? `?${query}` : "";
}

function renderResumen(payload){
    const data = payload.data || {};
    const proveedores = Array.isArray(payload.proveedores_financiamiento) ? payload.proveedores_financiamiento : [];
    const formasCobro = Array.isArray(payload.formas_cobro) ? payload.formas_cobro : [];
    const gastosCategorias = Array.isArray(payload.gastos_por_categoria) ? payload.gastos_por_categoria : [];
    const filtro = payload.filtro || {};

    setText("kpiVentas", money(data.ventas_total));
    setText("kpiCobrado", money(data.cobrado_total));
    setText("kpiPorCobrar", money(data.por_cobrar_total));
    setText("kpiGanancia", money(data.ganancia_total));
    setText("kpiGastos", money(data.gastos_total));
    setText("kpiUtilidadNeta", money(data.utilidad_neta));
    setText("kpiFlujoNeto", money(data.flujo_caja_neto));
    setText("kpiInventario", money(data.inversion_actual));

    setText("metaResumen", `Ticket promedio ${money(data.ticket_promedio)} | ${number(data.unidades_vendidas)} unidades vendidas | ${number(data.porcentaje_cobrado)}% cobrado.`);
    setText("chipPeriodo", `Periodo: ${formatoPeriodo(filtro.desde, filtro.hasta)}`);
    setText("chipVentas", `Ventas: ${number(data.total_ventas || 0)}`);
    setText("chipGastos", `Gastos: ${number(data.total_registros_gastos || 0)}`);

    renderTabla(
        "tablaProveedores",
        proveedores,
        5,
        (row) => `
            <tr>
                <td><div class="stack"><strong>${escapeHtml(row.proveedor_financiamiento || "SIN PROVEEDOR")}</strong><small>Financiado</small></div></td>
                <td>${number(row.operaciones)}</td>
                <td>${money(row.total_colocado)}</td>
                <td>${money(row.entrada_total)}</td>
                <td>${money(row.saldo_pendiente)}</td>
            </tr>
        `,
        "Sin proveedores financiados en el periodo."
    );

    renderTabla(
        "tablaCobros",
        formasCobro,
        3,
        (row) => `
            <tr>
                <td><strong>${escapeHtml(row.forma_pago || "N/D")}</strong></td>
                <td>${number(row.movimientos)}</td>
                <td>${money(row.total_cobrado)}</td>
            </tr>
        `,
        "No hay cobros registrados en el periodo."
    );

    renderTabla(
        "tablaCategoriasGasto",
        gastosCategorias,
        3,
        (row) => `
            <tr>
                <td><div class="stack"><strong>${escapeHtml(row.categoria || "Sin categoria")}</strong><small>Salida del local</small></div></td>
                <td>${number(row.total_registros)}</td>
                <td>${money(row.total_monto)}</td>
            </tr>
        `,
        "No hay gastos para el periodo seleccionado."
    );
}

function renderDashboard(payload){
    const data = payload.data || {};
    const ventasPeriodo = Array.isArray(data.ventas_ultimos_7_dias) ? data.ventas_ultimos_7_dias : [];
    const gastosPeriodo = Array.isArray(data.gastos_ultimos_7_dias) ? data.gastos_ultimos_7_dias : [];

    setText("todayVentas", money(data.ventas_hoy));
    setText("todayOperaciones", `${number(data.operaciones_hoy)} operaciones hoy`);
    setText("todayCobrado", money(data.cobrado_hoy));
    setText("todayGastos", `${money(data.gastos_hoy)} en gastos del dia`);
    setText("todayUtilidad", money(data.utilidad_neta_hoy));
    setText("todayPorCobrar", `${money(data.por_cobrar_hoy)} por cobrar hoy`);

    const mapaGastos = new Map(gastosPeriodo.map((row) => [row.fecha, row]));
    const fechas = new Set();

    ventasPeriodo.forEach((row) => fechas.add(row.fecha));
    gastosPeriodo.forEach((row) => fechas.add(row.fecha));

    const combinados = Array.from(fechas)
        .sort()
        .map((fecha) => {
            const venta = ventasPeriodo.find((row) => row.fecha === fecha) || {};
            const gasto = mapaGastos.get(fecha) || {};

            return {
                fecha,
                operaciones: venta.operaciones || 0,
                ventas_total: venta.ventas_total || 0,
                cobrado_estimado: venta.cobrado_estimado || 0,
                cartera_generada: venta.cartera_generada || 0,
                gastos_total: gasto.total_monto || 0
            };
        });

    renderTabla(
        "tablaPeriodo",
        combinados,
        6,
        (row) => `
            <tr>
                <td>${fechaBonita(row.fecha)}</td>
                <td>${number(row.operaciones)}</td>
                <td>${money(row.ventas_total)}</td>
                <td>${money(row.cobrado_estimado)}</td>
                <td>${money(row.cartera_generada)}</td>
                <td>${money(row.gastos_total)}</td>
            </tr>
        `,
        "No hay movimientos en los ultimos 7 dias."
    );
}

function pintarCarga(){
    const placeholders = {
        tablaPeriodo: { colspan: 6, mensaje: "Consultando tendencia semanal..." },
        tablaProveedores: { colspan: 5, mensaje: "Cargando proveedores..." },
        tablaCobros: { colspan: 3, mensaje: "Cargando formas de cobro..." },
        tablaCategoriasGasto: { colspan: 3, mensaje: "Cargando categorias..." }
    };

    Object.entries(placeholders).forEach(([id, config]) => {
        document.getElementById(id).innerHTML = `
            <tr>
                <td colspan="${config.colspan}" class="table-empty">${config.mensaje}</td>
            </tr>
        `;
    });

    setText("metaResumen", "Consultando informacion contable...");
}

function limpiarResumen(){
    [
        "kpiVentas",
        "kpiCobrado",
        "kpiPorCobrar",
        "kpiGanancia",
        "kpiGastos",
        "kpiUtilidadNeta",
        "kpiFlujoNeto",
        "kpiInventario",
        "todayVentas",
        "todayCobrado",
        "todayUtilidad"
    ].forEach((id) => setText(id, "$0.00"));

    setText("todayOperaciones", "0 operaciones hoy");
    setText("todayGastos", "$0.00 en gastos del dia");
    setText("todayPorCobrar", "$0.00 por cobrar hoy");
    setText("metaResumen", "No fue posible cargar la informacion contable.");
    setText("chipPeriodo", `Periodo: ${formatoPeriodo(fDesde.value, fHasta.value)}`);
    setText("chipVentas", "Ventas: 0");
    setText("chipGastos", "Gastos: 0");
}

function renderTabla(id, rows, colspan, renderer, emptyMessage){
    const tbody = document.getElementById(id);

    if (!rows.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${colspan}" class="table-empty">${emptyMessage}</td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = rows.map(renderer).join("");
}

function mostrarAlerta(mensaje){
    alerta.style.display = "block";
    alerta.textContent = mensaje;
}

function ocultarAlerta(){
    alerta.style.display = "none";
    alerta.textContent = "";
}

function setText(id, value){
    document.getElementById(id).textContent = value;
}

function money(value){
    return `$${Number(value || 0).toFixed(2)}`;
}

function number(value){
    return new Intl.NumberFormat("es-EC").format(Number(value || 0));
}

function fechaBonita(value){
    if (!value) return "--";
    const parts = String(value).split("-");
    if (parts.length !== 3) return value;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

function formatoPeriodo(desde, hasta){
    if (!desde && !hasta) return "Historico";
    if (desde && hasta) return `${fechaBonita(desde)} a ${fechaBonita(hasta)}`;
    if (desde) return `Desde ${fechaBonita(desde)}`;
    return `Hasta ${fechaBonita(hasta)}`;
}

function fechaActualGuayaquil(){
    const parts = new Intl.DateTimeFormat("en", {
        timeZone: "America/Guayaquil",
        year: "numeric",
        month: "2-digit",
        day: "2-digit"
    }).formatToParts(new Date());

    const year = parts.find((part) => part.type === "year")?.value;
    const month = parts.find((part) => part.type === "month")?.value;
    const day = parts.find((part) => part.type === "day")?.value;

    return `${year}-${month}-${day}`;
}

function primerDiaMes(fechaIso){
    const parts = String(fechaIso).split("-");
    return `${parts[0]}-${parts[1]}-01`;
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
