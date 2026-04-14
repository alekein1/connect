@extends('superadmin.layout')

@section('title', 'Reportes')

@section('content')

<style>
    .page-wrap{
        display:flex;
        flex-direction:column;
        gap:24px;
    }

    .panel-card{
        background: linear-gradient(180deg, #081225 0%, #07101f 100%);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 22px;
        padding: 24px;
        box-shadow: 0 18px 45px rgba(0,0,0,.35);
    }

    .panel-header{
        display:flex;
        align-items:center;
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
    }

    .filter-grid{
        display:grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
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

    .stats-grid{
        display:grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap:16px;
    }

    .mini-stat{
        background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.015));
        border: 1px solid rgba(255,255,255,.05);
        border-radius: 18px;
        padding: 18px;
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
        margin-bottom:8px;
    }

    .mini-stat small{
        color:#64748b;
        font-size:12px;
    }

    .status-line{
        margin-top:4px;
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

    .table-report{
        width:100%;
        min-width:1180px;
        border-collapse:collapse;
    }

    .table-report thead th{
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

    .table-report tbody td{
        padding:16px 18px;
        color:#e5e7eb;
        border-bottom:1px solid rgba(255,255,255,.05);
        vertical-align:middle;
    }

    .table-report tbody tr:hover{
        background:rgba(255,255,255,.025);
    }

    .local-name{
        font-weight:800;
        color:#fff;
    }

    .local-note{
        margin-top:4px;
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
        font-weight:700;
        white-space:nowrap;
    }

    .badge-success{
        background:rgba(34,197,94,.15);
        color:#4ade80;
        border:1px solid rgba(34,197,94,.25);
    }

    .badge-muted{
        background:rgba(148,163,184,.12);
        color:#cbd5e1;
        border:1px solid rgba(148,163,184,.18);
    }

    .badge-warning{
        background:rgba(245,158,11,.16);
        color:#fbbf24;
        border:1px solid rgba(245,158,11,.20);
    }

    .two-col{
        display:grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap:20px;
    }

    .chart-list,
    .alert-list{
        display:flex;
        flex-direction:column;
        gap:14px;
    }

    .chart-row{
        display:grid;
        grid-template-columns: 160px 1fr auto;
        gap:14px;
        align-items:center;
    }

    .chart-label{
        color:#e5e7eb;
        font-size:14px;
        font-weight:700;
    }

    .chart-track{
        height:12px;
        background:rgba(255,255,255,.06);
        border-radius:999px;
        overflow:hidden;
        position:relative;
    }

    .chart-fill{
        height:100%;
        border-radius:999px;
        background:linear-gradient(90deg, #f4c842 0%, #2dd4bf 100%);
    }

    .chart-fill-danger{
        background:linear-gradient(90deg, #fb7185 0%, #f97316 100%);
    }

    .chart-value{
        color:#cbd5e1;
        font-size:13px;
        font-weight:700;
        white-space:nowrap;
    }

    .alert-item{
        padding:14px 16px;
        border-radius:16px;
        border:1px solid rgba(255,255,255,.06);
        background:rgba(255,255,255,.02);
    }

    .alert-item strong{
        display:block;
        font-size:14px;
        color:#fff;
        margin-bottom:6px;
    }

    .alert-item span{
        color:#94a3b8;
        font-size:13px;
        line-height:1.5;
    }

    .alert-high{
        border-color:rgba(239,68,68,.22);
        background:rgba(127,29,29,.14);
    }

    .alert-medium{
        border-color:rgba(245,158,11,.18);
        background:rgba(120,53,15,.12);
    }

    .empty-state{
        color:#94a3b8;
        font-size:14px;
        padding:18px 0;
    }

    @media (max-width: 1100px){
        .filter-grid,
        .stats-grid,
        .two-col{
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 760px){
        .filter-grid,
        .stats-grid,
        .two-col{
            grid-template-columns: 1fr;
        }

        .chart-row{
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-wrap">

    <div class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Centro de Reportes Multi-local</h3>
                <p class="panel-subtitle">
                    Revisa ventas, cajas e inventario de todos los locales desde un solo punto,
                    con filtro por rango y lectura comparativa entre sucursales.
                </p>
            </div>
        </div>

        <div class="filter-grid">
            <div class="field">
                <label>Local</label>
                <select id="filtroLocal">
                    <option value="">Todos los locales</option>
                </select>
            </div>

            <div class="field">
                <label>Fecha desde</label>
                <input type="date" id="filtroDesde">
            </div>

            <div class="field">
                <label>Fecha hasta</label>
                <input type="date" id="filtroHasta">
            </div>

            <div class="field">
                <label>Acciones</label>
                <div class="filter-actions">
                    <button class="btn-primary-pro" id="btnAplicar">Actualizar</button>
                    <button class="btn-secondary-pro" id="btnHoy">Este mes</button>
                    <button class="btn-secondary-pro" id="btnDescargarStockPdf">PDF Stock</button>
                </div>
            </div>
        </div>

        <div class="status-line" id="estadoReporte">Cargando reporte...</div>
    </div>

    <div class="stats-grid">
        <div class="mini-stat">
            <span>Ventas del periodo</span>
            <strong id="kpiVentas">$0.00</strong>
            <small id="kpiVentasMeta">0 tickets procesados</small>
        </div>
        <div class="mini-stat">
            <span>Ticket promedio</span>
            <strong id="kpiTicketPromedio">$0.00</strong>
            <small id="kpiLocalesActivos">0 locales activos</small>
        </div>
        <div class="mini-stat">
            <span>Stock total</span>
            <strong id="kpiStockTotal">0</strong>
            <small id="kpiProductos">0 productos monitoreados</small>
        </div>
        <div class="mini-stat">
            <span>Stock bajo</span>
            <strong id="kpiStockBajo">0</strong>
            <small id="kpiSinExistencia">0 sin existencia</small>
        </div>
        <div class="mini-stat">
            <span>Cajas abiertas</span>
            <strong id="kpiCajasAbiertas">0</strong>
            <small id="kpiCajasPeriodo">0 cajas en el periodo</small>
        </div>
        <div class="mini-stat">
            <span>Caja actual</span>
            <strong id="kpiCajaActual">$0.00</strong>
            <small id="kpiAdmins">0 admins activos</small>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Reporte por Local</h3>
                <p class="panel-subtitle">Vista ejecutiva para comparar desempeño, inventario y caja entre locales.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-report">
                <thead>
                    <tr>
                        <th>Local</th>
                        <th>Admins</th>
                        <th>Ventas</th>
                        <th>Tickets</th>
                        <th>Promedio</th>
                        <th>Productos</th>
                        <th>Stock</th>
                        <th>Stock bajo</th>
                        <th>Cajas abiertas</th>
                        <th>Caja actual</th>
                    </tr>
                </thead>
                <tbody id="tablaLocalesReportes">
                    <tr>
                        <td colspan="10" class="empty-state">Cargando reporte...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="two-col">
        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Ranking de Ventas</h3>
                    <p class="panel-subtitle">Participación por local dentro del rango consultado.</p>
                </div>
            </div>
            <div class="chart-list" id="rankingVentas"></div>
        </div>

        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Riesgo de Inventario</h3>
                    <p class="panel-subtitle">Locales con más productos en stock bajo.</p>
                </div>
            </div>
            <div class="chart-list" id="rankingStock"></div>
        </div>
    </div>

    <div class="two-col">
        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Evolución Diaria</h3>
                    <p class="panel-subtitle">Ventas acumuladas por día dentro del periodo elegido.</p>
                </div>
            </div>
            <div class="chart-list" id="serieVentas"></div>
        </div>

        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Alertas Operativas</h3>
                    <p class="panel-subtitle">Señales rápidas para detectar locales que requieren atención.</p>
                </div>
            </div>
            <div class="alert-list" id="listaAlertas"></div>
        </div>
    </div>

</div>

<script>
const API = "{{ rtrim(env('API_URL', 'http://localhost:4004/api'), '/') }}";
const token = localStorage.getItem("token");

const filtroLocal = document.getElementById("filtroLocal");
const filtroDesde = document.getElementById("filtroDesde");
const filtroHasta = document.getElementById("filtroHasta");
const estadoReporte = document.getElementById("estadoReporte");
const tablaLocalesReportes = document.getElementById("tablaLocalesReportes");
const btnDescargarStockPdf = document.getElementById("btnDescargarStockPdf");

function money(value){
    return new Intl.NumberFormat("es-EC", {
        style: "currency",
        currency: "USD"
    }).format(Number(value || 0));
}

function number(value){
    return new Intl.NumberFormat("es-EC").format(Number(value || 0));
}

function formatDate(value){
    if(!value){
        return "Sin movimientos";
    }

    const date = new Date(value);
    if(Number.isNaN(date.getTime())){
        return value;
    }

    return new Intl.DateTimeFormat("es-EC", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit"
    }).format(date);
}

function formatDayLabel(value){
    if(!value){
        return "-";
    }

    const date = new Date(value);
    if(Number.isNaN(date.getTime())){
        return value;
    }

    return new Intl.DateTimeFormat("es-EC", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit"
    }).format(date);
}

function getTodayLocal(){
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, "0");
    const day = String(now.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

function getMonthStart(){
    const today = getTodayLocal();
    return `${today.slice(0, 8)}01`;
}

function setDefaultDates(){
    filtroDesde.value = getMonthStart();
    filtroHasta.value = getTodayLocal();
}

async function cargarLocalesFiltro(){
    try{
        const res = await fetch(`${API}/locales`, {
            headers: {
                Authorization: "Bearer " + token
            }
        });

        const json = await res.json();

        if(!res.ok || !json.ok){
            return;
        }

        const locales = json.data || [];
        filtroLocal.innerHTML = `<option value="">Todos los locales</option>`;

        locales.forEach((local) => {
            const option = document.createElement("option");
            option.value = local.id_local;
            option.textContent = `${local.id_local} - ${local.nombre_local}`;
            filtroLocal.appendChild(option);
        });
    }catch(error){
        console.error("Error cargar locales:", error);
    }
}

function renderKPIs(resumen){
    document.getElementById("kpiVentas").innerText = money(resumen.ventas_total);
    document.getElementById("kpiVentasMeta").innerText = `${number(resumen.tickets_total)} tickets procesados`;
    document.getElementById("kpiTicketPromedio").innerText = money(resumen.ticket_promedio);
    document.getElementById("kpiLocalesActivos").innerText = `${number(resumen.locales_activos)} locales activos`;
    document.getElementById("kpiStockTotal").innerText = number(resumen.stock_unidades_total);
    document.getElementById("kpiProductos").innerText = `${number(resumen.productos_total)} productos monitoreados`;
    document.getElementById("kpiStockBajo").innerText = number(resumen.stock_bajo_total);
    document.getElementById("kpiSinExistencia").innerText = `${number(resumen.stock_sin_existencia)} sin existencia`;
    document.getElementById("kpiCajasAbiertas").innerText = number(resumen.cajas_abiertas_actuales);
    document.getElementById("kpiCajasPeriodo").innerText = `${number(resumen.cajas_periodo)} cajas en el periodo`;
    document.getElementById("kpiCajaActual").innerText = money(resumen.caja_actual_total);
    document.getElementById("kpiAdmins").innerText = `${number(resumen.admins_activos)} admins activos`;
}

function renderTabla(locales){
    if(!locales.length){
        tablaLocalesReportes.innerHTML = `
            <tr>
                <td colspan="10" class="empty-state">No hay información para el rango consultado.</td>
            </tr>
        `;
        return;
    }

    tablaLocalesReportes.innerHTML = "";

    locales.forEach((local) => {
        const badgeLocal = local.activo
            ? `<span class="badge badge-success">Activo</span>`
            : `<span class="badge badge-muted">Inactivo</span>`;

        const badgeStock = local.stock_bajo_total > 0
            ? `<span class="badge badge-warning">${number(local.stock_bajo_total)} bajo</span>`
            : `<span class="badge badge-success">Normal</span>`;

        const badgeCaja = local.cajas_abiertas_actuales > 0
            ? `<span class="badge badge-success">${number(local.cajas_abiertas_actuales)} abiertas</span>`
            : `<span class="badge badge-muted">Sin caja abierta</span>`;

        tablaLocalesReportes.innerHTML += `
            <tr>
                <td>
                    <div class="local-name">${local.nombre_local}</div>
                    <div class="local-note">Ultima venta: ${formatDate(local.ultima_venta)}</div>
                    <div class="local-note">${badgeLocal}</div>
                </td>
                <td>${number(local.admins_activos)} / ${number(local.total_admins)}</td>
                <td>${money(local.ventas_total)}</td>
                <td>${number(local.tickets_total)}</td>
                <td>${money(local.ticket_promedio)}</td>
                <td>${number(local.total_productos)}</td>
                <td>${number(local.stock_unidades_total)}</td>
                <td>${badgeStock}</td>
                <td>${badgeCaja}</td>
                <td>${money(local.caja_actual_total)}</td>
            </tr>
        `;
    });
}

function renderRanking(containerId, items, formatter, danger = false){
    const container = document.getElementById(containerId);

    if(!items.length){
        container.innerHTML = `<div class="empty-state">Sin datos para mostrar.</div>`;
        return;
    }

    const max = Math.max(...items.map(item => Number(item.valor || 0)), 0);

    container.innerHTML = items.slice(0, 8).map((item) => {
        const value = Number(item.valor || 0);
        const width = max > 0 ? Math.max((value / max) * 100, 4) : 0;

        return `
            <div class="chart-row">
                <div class="chart-label">${item.nombre_local}</div>
                <div class="chart-track">
                    <div class="chart-fill ${danger ? 'chart-fill-danger' : ''}" style="width:${width}%"></div>
                </div>
                <div class="chart-value">${formatter(value)}</div>
            </div>
        `;
    }).join("");
}

function renderSerie(serie){
    const container = document.getElementById("serieVentas");

    if(!serie.length){
        container.innerHTML = `<div class="empty-state">No hay ventas registradas en este periodo.</div>`;
        return;
    }

    const grouped = serie.reduce((acc, item) => {
        const key = item.fecha;
        acc[key] = (acc[key] || 0) + Number(item.ventas_total || 0);
        return acc;
    }, {});

    const data = Object.entries(grouped).map(([fecha, total]) => ({ fecha, total }));
    const max = Math.max(...data.map(item => item.total), 0);

    container.innerHTML = data.map((item) => {
        const width = max > 0 ? Math.max((item.total / max) * 100, 4) : 0;

        return `
            <div class="chart-row">
                <div class="chart-label">${formatDayLabel(item.fecha)}</div>
                <div class="chart-track">
                    <div class="chart-fill" style="width:${width}%"></div>
                </div>
                <div class="chart-value">${money(item.total)}</div>
            </div>
        `;
    }).join("");
}

function renderAlertas(alertas){
    const container = document.getElementById("listaAlertas");

    if(!alertas.length){
        container.innerHTML = `<div class="empty-state">Sin alertas relevantes en este periodo.</div>`;
        return;
    }

    container.innerHTML = alertas.slice(0, 10).map((alerta) => `
        <div class="alert-item ${alerta.nivel === 'alto' ? 'alert-high' : 'alert-medium'}">
            <strong>${alerta.nombre_local}</strong>
            <span>${alerta.mensaje}</span>
        </div>
    `).join("");
}

async function cargarReportes(){
    estadoReporte.textContent = "Actualizando reporte...";

    try{
        const params = new URLSearchParams();

        if(filtroDesde.value){
            params.set("fecha_desde", filtroDesde.value);
        }

        if(filtroHasta.value){
            params.set("fecha_hasta", filtroHasta.value);
        }

        if(filtroLocal.value){
            params.set("id_local", filtroLocal.value);
        }

        const res = await fetch(`${API}/reportes/superadmin/resumen?${params.toString()}`, {
            headers: {
                Authorization: "Bearer " + token
            }
        });

        const raw = await res.text();
        let json = {};

        try{
            json = raw ? JSON.parse(raw) : {};
        }catch(error){
            json = { mensaje: raw || "Respuesta inválida del servidor" };
        }

        if(!res.ok || !json.ok){
            estadoReporte.textContent = json.mensaje || "No se pudo cargar el reporte.";
            renderTabla([]);
            renderRanking("rankingVentas", [], money);
            renderRanking("rankingStock", [], number, true);
            renderSerie([]);
            renderAlertas([]);
            return;
        }

        const data = json.data || {};
        renderKPIs(data.resumen_global || {});
        renderTabla(data.locales || []);
        renderRanking("rankingVentas", data.estadisticas?.ranking_ventas || [], money);
        renderRanking("rankingStock", data.estadisticas?.ranking_stock_bajo || [], number, true);
        renderSerie(data.estadisticas?.serie_ventas || []);
        renderAlertas(data.estadisticas?.alertas || []);

        estadoReporte.textContent = `Reporte actualizado del ${data.filtros?.fecha_desde || '-'} al ${data.filtros?.fecha_hasta || '-'}${data.filtros?.id_local ? ` para el local ${data.filtros.id_local}` : ''}.`;
    }catch(error){
        console.error("Error cargar reportes:", error);
        estadoReporte.textContent = "No se pudo conectar con el backend de reportes.";
    }
}

async function descargarPdfStock(){
    const originalText = btnDescargarStockPdf.textContent;
    btnDescargarStockPdf.disabled = true;
    btnDescargarStockPdf.textContent = "Generando PDF...";
    estadoReporte.textContent = "Generando reporte PDF de stock...";

    try{
        const params = new URLSearchParams();

        if(filtroLocal.value){
            params.set("id_local", filtroLocal.value);
        }

        const res = await fetch(`${API}/reportes/superadmin/stock/pdf?${params.toString()}`, {
            headers: {
                Authorization: "Bearer " + token
            }
        });

        if(!res.ok){
            const raw = await res.text();
            let json = {};

            try{
                json = raw ? JSON.parse(raw) : {};
            }catch(error){
                json = { mensaje: raw || "No se pudo generar el PDF" };
            }

            estadoReporte.textContent = json.mensaje || "No se pudo generar el PDF de stock.";
            return;
        }

        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        const localSuffix = filtroLocal.value ? `-local-${filtroLocal.value}` : "-global";

        a.href = url;
        a.download = `reporte-stock${localSuffix}.pdf`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);

        estadoReporte.textContent = "Reporte PDF de stock generado correctamente.";
    }catch(error){
        console.error("Error descargar PDF stock:", error);
        estadoReporte.textContent = "No se pudo descargar el PDF de stock.";
    }finally{
        btnDescargarStockPdf.disabled = false;
        btnDescargarStockPdf.textContent = originalText;
    }
}

document.getElementById("btnAplicar").addEventListener("click", cargarReportes);
document.getElementById("btnHoy").addEventListener("click", () => {
    filtroLocal.value = "";
    setDefaultDates();
    cargarReportes();
});
btnDescargarStockPdf.addEventListener("click", descargarPdfStock);

document.addEventListener("DOMContentLoaded", async () => {
    if(!token){
        window.location.href = "/login";
        return;
    }

    setDefaultDates();
    await cargarLocalesFiltro();
    await cargarReportes();
});
</script>

@endsection
