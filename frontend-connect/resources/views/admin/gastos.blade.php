@extends('admin.layout')

@section('title', 'Gastos')

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
    background:rgba(248,113,113,.12);
    border:1px solid rgba(248,113,113,.18);
    color:#fda4af;
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

.input-pro{
    min-width:170px;
}

.select-pro{
    min-width:180px;
}

.textarea-pro{
    min-height:96px;
    resize:vertical;
}

.input-pro:focus,
.select-pro:focus,
.textarea-pro:focus{
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

.btn-secondary-pro{
    border:1px solid rgba(255,255,255,.10);
    background:rgba(255,255,255,.04);
    color:#fff;
    font-weight:700;
    padding:12px 16px;
    border-radius:12px;
    cursor:pointer;
}

.btn-action{
    border:1px solid rgba(255,255,255,0.08);
    background:#0f172a;
    color:#fff;
    padding:8px 12px;
    border-radius:10px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    transition:.2s ease;
}

.btn-action:hover{
    background:#172036;
}

.btn-action:disabled,
.btn-secondary-pro:disabled{
    opacity:.65;
    cursor:not-allowed;
}

.btn-danger{
    background:rgba(127,29,29,.18);
    color:#fca5a5;
    border:1px solid rgba(239,68,68,.18);
}

.btn-danger:hover{
    background:rgba(127,29,29,.28);
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
    grid-template-columns:repeat(5,1fr);
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
    min-width:1160px;
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

.actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.money-danger{
    color:#fca5a5;
    font-weight:800;
}

.modal-pro{
    position:fixed;
    inset:0;
    background:rgba(2,6,23,.72);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
    padding:20px;
    backdrop-filter:blur(6px);
}

.modal-pro.show{
    display:flex;
}

.modal-card{
    width:100%;
    max-width:760px;
    background:linear-gradient(180deg,#0b1220 0%,#09111d 100%);
    border:1px solid rgba(255,255,255,.08);
    border-radius:22px;
    box-shadow:0 25px 70px rgba(0,0,0,.45);
    overflow:hidden;
}

.modal-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:22px 24px 14px;
    border-bottom:1px solid rgba(255,255,255,.05);
}

.modal-head h4{
    margin:0;
    color:#fff;
    font-size:20px;
    font-weight:800;
}

.btn-close-pro{
    width:40px;
    height:40px;
    border-radius:12px;
    border:none;
    background:rgba(255,255,255,.06);
    color:#fff;
    font-size:20px;
    cursor:pointer;
}

.modal-body-pro{
    padding:22px 24px;
}

.form-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:16px;
}

.field{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.field-full{
    grid-column:1 / -1;
}

.field label{
    color:#cbd5e1;
    font-size:14px;
    font-weight:700;
}

.modal-foot{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    padding:0 24px 24px;
}

.hint-text{
    margin-top:14px;
    color:#64748b;
    font-size:12px;
}

@media (max-width: 1280px){
    .stats-grid{
        grid-template-columns:repeat(3,1fr);
    }
}

@media (max-width: 920px){
    .stats-grid{
        grid-template-columns:repeat(2,1fr);
    }

    .form-grid{
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
    .select-pro,
    .btn-primary-pro{
        width:100%;
    }
}
</style>

<div class="page-wrap">

    <div class="hero-card">
        <div>
            <span class="hero-eyebrow">Control de egresos</span>
            <h3 class="hero-title">Gestion de gastos del local</h3>
            <p class="hero-copy">
                Registra, filtra y administra los gastos que afectan la utilidad del local actual.
                Todo lo que ves aqui se consume directamente desde el backend del local autenticado.
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

            <label class="field-inline">
                <span>Categoria</span>
                <input type="text" id="f_categoria" class="input-pro" list="listaCategoriasFiltro" placeholder="Ej. Servicios">
            </label>

            <label class="field-inline">
                <span>Metodo</span>
                <select id="f_metodo" class="select-pro">
                    <option value="">Todos</option>
                    <option value="EFECTIVO">EFECTIVO</option>
                    <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                    <option value="TARJETA">TARJETA</option>
                    <option value="OTRO">OTRO</option>
                </select>
            </label>

            <button id="btnRecargar" type="button" class="btn-primary-pro">Actualizar gastos</button>
            <button type="button" class="btn-secondary-pro" onclick="abrirModal()">Nuevo gasto</button>
        </div>
    </div>

    <datalist id="listaCategoriasFiltro"></datalist>
    <datalist id="listaCategoriasModal"></datalist>

    <div id="alerta" class="alert-pro"></div>

    <div class="stats-grid">
        <div class="mini-stat">
            <span>Total gastos</span>
            <strong id="kpiTotalGastos">$0.00</strong>
            <small>Egresos del periodo</small>
        </div>

        <div class="mini-stat">
            <span>Registros</span>
            <strong id="kpiRegistros">0</strong>
            <small>Movimientos encontrados</small>
        </div>

        <div class="mini-stat">
            <span>Efectivo</span>
            <strong id="kpiEfectivo">$0.00</strong>
            <small>Pagado en caja</small>
        </div>

        <div class="mini-stat">
            <span>Transferencia</span>
            <strong id="kpiTransferencia">$0.00</strong>
            <small>Pagado por banco</small>
        </div>

        <div class="mini-stat">
            <span>Tarjeta y otros</span>
            <strong id="kpiTarjetaOtros">$0.00</strong>
            <small>Suma de tarjeta y otros</small>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Detalle de gastos</h3>
                <p id="metaGastos" class="panel-subtitle">Consultando gastos del local...</p>
            </div>

            <div class="meta-chips">
                <span class="meta-chip" id="chipPeriodo">Periodo: --</span>
                <span class="meta-chip" id="chipCategoria">Categoria: Todas</span>
                <span class="meta-chip" id="chipMetodo">Metodo: Todos</span>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-pro">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Categoria</th>
                        <th>Descripcion</th>
                        <th>Monto</th>
                        <th>Metodo</th>
                        <th>Referencia</th>
                        <th>Usuario</th>
                        <th>Caja</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaGastos">
                    <tr>
                        <td colspan="9" class="table-empty">Consultando gastos...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div class="modal-pro" id="modalGasto">
    <div class="modal-card">
        <div class="modal-head">
            <h4 id="tituloModal">Nuevo gasto</h4>
            <button class="btn-close-pro" onclick="cerrarModal()">×</button>
        </div>

        <div class="modal-body-pro">
            <input type="hidden" id="id_gasto">

            <div class="form-grid">
                <div class="field">
                    <label>Categoria</label>
                    <input type="text" id="g_categoria" class="input-pro" list="listaCategoriasModal" placeholder="Ej. Arriendo">
                </div>

                <div class="field">
                    <label>Monto</label>
                    <input type="number" id="g_monto" class="input-pro" min="0" step="0.01" placeholder="0.00">
                </div>

                <div class="field">
                    <label>Metodo de pago</label>
                    <select id="g_metodo_pago" class="select-pro">
                        <option value="EFECTIVO">EFECTIVO</option>
                        <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                        <option value="TARJETA">TARJETA</option>
                        <option value="OTRO">OTRO</option>
                    </select>
                </div>

                <div class="field">
                    <label>Fecha del gasto</label>
                    <input type="datetime-local" id="g_fecha_gasto" class="input-pro">
                </div>

                <div class="field">
                    <label>Referencia</label>
                    <input type="text" id="g_referencia" class="input-pro" placeholder="Factura, comprobante o nota">
                </div>

                <div class="field">
                    <label>ID caja</label>
                    <input type="number" id="g_id_caja" class="input-pro" min="1" placeholder="Opcional">
                </div>

                <div class="field field-full">
                    <label>Descripcion</label>
                    <textarea id="g_descripcion" class="textarea-pro" placeholder="Describe el gasto"></textarea>
                </div>

                <div class="field field-full">
                    <label>Observacion</label>
                    <textarea id="g_observacion" class="textarea-pro" placeholder="Notas internas"></textarea>
                </div>
            </div>

            <div class="hint-text">
                Si dejas fecha vacia, el backend usara la fecha actual. Si dejas caja vacia al crear, intentara usar la caja abierta del usuario.
            </div>
        </div>

        <div class="modal-foot">
            <button
                id="btnEliminarModal"
                type="button"
                class="btn-action btn-danger"
                style="display:none"
                onclick="eliminarGastoDesdeModal()"
            >
                Eliminar gasto
            </button>
            <button class="btn-secondary-pro" onclick="cerrarModal()">Cancelar</button>
            <button id="btnGuardar" class="btn-primary-pro" onclick="guardarGasto()">Guardar gasto</button>
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
const fCategoria = document.getElementById("f_categoria");
const fMetodo = document.getElementById("f_metodo");
const btnRecargar = document.getElementById("btnRecargar");
const alerta = document.getElementById("alerta");
const modal = document.getElementById("modalGasto");
const btnGuardar = document.getElementById("btnGuardar");
const btnEliminarModal = document.getElementById("btnEliminarModal");
let gastosActuales = [];
const eliminandoGastos = new Set();

btnRecargar.addEventListener("click", cargarGastos);
fMetodo.addEventListener("change", cargarGastos);

inicializar();

async function inicializar(){
    const hoy = fechaActualGuayaquil();
    fHasta.value = hoy;
    fDesde.value = primerDiaMes(hoy);
    await cargarGastos();
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

async function apiSend(url, method, body){
    const options = {
        method,
        headers: {
            "Authorization": "Bearer " + token,
            "Accept": "application/json"
        }
    };

    if (body !== undefined) {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(body);
    }

    const res = await fetch(url, options);

    const data = await res.json().catch(() => ({
        ok: false,
        mensaje: "La respuesta del servidor no fue valida"
    }));

    if (!res.ok || !data.ok) {
        throw new Error(data.mensaje || "No se pudo procesar la solicitud");
    }

    return data;
}

async function cargarGastos(){
    ocultarAlerta();
    pintarCarga();
    btnRecargar.disabled = true;
    btnRecargar.textContent = "Actualizando...";

    try {
        const query = construirQuery();
        const [listado, resumen] = await Promise.all([
            apiGet(`${API}/gastos${query}`),
            apiGet(`${API}/gastos/resumen${query}`)
        ]);

        renderResumen(resumen);
        renderTablaGastos(Array.isArray(listado.data) ? listado.data : []);
        construirCategorias(Array.isArray(listado.data) ? listado.data : [], resumen.por_categoria || []);
    } catch (error) {
        limpiarVista();
        mostrarAlerta(error.message || "No fue posible cargar los gastos.");
    } finally {
        btnRecargar.disabled = false;
        btnRecargar.textContent = "Actualizar gastos";
    }
}

function construirQuery(){
    const params = new URLSearchParams();

    if (fDesde.value) params.set("desde", fDesde.value);
    if (fHasta.value) params.set("hasta", fHasta.value);
    if (fCategoria.value.trim()) params.set("categoria", fCategoria.value.trim());
    if (fMetodo.value) params.set("metodo_pago", fMetodo.value);

    const query = params.toString();
    return query ? `?${query}` : "";
}

function renderResumen(payload){
    const data = payload.data || {};
    const filtros = payload.filtros || {};

    setText("kpiTotalGastos", money(data.total_gastos));
    setText("kpiRegistros", number(data.total_registros));
    setText("kpiEfectivo", money(data.total_efectivo));
    setText("kpiTransferencia", money(data.total_transferencia));
    setText("kpiTarjetaOtros", money(Number(data.total_tarjeta || 0) + Number(data.total_otro || 0)));

    setText("metaGastos", `${number(data.total_registros)} gastos encontrados para el periodo consultado.`);
    setText("chipPeriodo", `Periodo: ${formatoPeriodo(filtros.desde, filtros.hasta)}`);
    setText("chipCategoria", `Categoria: ${filtros.categoria || "Todas"}`);
    setText("chipMetodo", `Metodo: ${filtros.metodo_pago || "Todos"}`);
}

function renderTablaGastos(rows){
    const tbody = document.getElementById("tablaGastos");
    gastosActuales = Array.isArray(rows) ? rows : [];

    if (!gastosActuales.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="table-empty">No hay gastos para los filtros seleccionados.</td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = gastosActuales.map((row) => {
        const idGasto = Number(row.id_gasto);
        const eliminando = eliminandoGastos.has(idGasto);
        const etiqueta = obtenerEtiquetaGasto(row);

        return `
        <tr ${eliminando ? 'data-eliminando="true"' : ""}>
            <td>
                <div class="stack">
                    <strong>${fechaHoraBonita(row.fecha_gasto)}</strong>
                    <small>Registro #${escapeHtml(row.id_gasto)}</small>
                </div>
            </td>
            <td>
                <div class="stack">
                    <strong>${escapeHtml(row.categoria || "Sin categoria")}</strong>
                    <small>${escapeHtml(row.observacion || "Sin observacion")}</small>
                </div>
            </td>
            <td>
                <div class="stack">
                    <strong>${escapeHtml(row.descripcion || "Sin descripcion")}</strong>
                    <small>${escapeHtml(row.referencia || "Sin referencia")}</small>
                </div>
            </td>
            <td class="money-danger">${money(row.monto)}</td>
            <td>${escapeHtml(row.metodo_pago || "N/D")}</td>
            <td>${escapeHtml(row.referencia || "--")}</td>
            <td>
                <div class="stack">
                    <strong>${escapeHtml(row.usuario || "N/D")}</strong>
                    <small>Local actual</small>
                </div>
            </td>
            <td>${row.id_caja ? `Caja #${escapeHtml(row.id_caja)}` : "Sin caja"}</td>
            <td>
                <div class="actions">
                    <button class="btn-action" onclick="editarGasto(${idGasto})" ${eliminando ? "disabled" : ""}>Editar</button>
                    <button
                        class="btn-action btn-danger"
                        onclick='eliminarGasto(${idGasto}, ${JSON.stringify(etiqueta)})'
                        ${eliminando ? "disabled" : ""}
                    >
                        ${eliminando ? "Eliminando..." : "Eliminar"}
                    </button>
                </div>
            </td>
        </tr>
    `;
    }).join("");
}

function construirCategorias(rows, categoriasResumen){
    const valores = new Set();

    rows.forEach((row) => {
        if (row.categoria) valores.add(String(row.categoria).trim());
    });

    categoriasResumen.forEach((row) => {
        if (row.categoria) valores.add(String(row.categoria).trim());
    });

    const html = Array.from(valores)
        .sort((a, b) => a.localeCompare(b))
        .map((categoria) => `<option value="${escapeHtml(categoria)}"></option>`)
        .join("");

    document.getElementById("listaCategoriasFiltro").innerHTML = html;
    document.getElementById("listaCategoriasModal").innerHTML = html;
}

function pintarCarga(){
    gastosActuales = [];
    document.getElementById("tablaGastos").innerHTML = `
        <tr>
            <td colspan="9" class="table-empty">Consultando gastos...</td>
        </tr>
    `;
    setText("metaGastos", "Consultando gastos del local...");
}

function limpiarVista(){
    gastosActuales = [];
    setText("kpiTotalGastos", "$0.00");
    setText("kpiRegistros", "0");
    setText("kpiEfectivo", "$0.00");
    setText("kpiTransferencia", "$0.00");
    setText("kpiTarjetaOtros", "$0.00");
    setText("metaGastos", "No fue posible cargar los gastos.");
    setText("chipPeriodo", `Periodo: ${formatoPeriodo(fDesde.value, fHasta.value)}`);
    setText("chipCategoria", `Categoria: ${fCategoria.value.trim() || "Todas"}`);
    setText("chipMetodo", `Metodo: ${fMetodo.value || "Todos"}`);
}

function resetFormularioGasto(){
    document.getElementById("tituloModal").textContent = "Nuevo gasto";
    document.getElementById("id_gasto").value = "";
    document.getElementById("g_categoria").value = "";
    document.getElementById("g_descripcion").value = "";
    document.getElementById("g_monto").value = "";
    document.getElementById("g_metodo_pago").value = "EFECTIVO";
    document.getElementById("g_fecha_gasto").value = "";
    document.getElementById("g_referencia").value = "";
    document.getElementById("g_observacion").value = "";
    document.getElementById("g_id_caja").value = "";
    btnGuardar.disabled = false;
    btnGuardar.textContent = "Guardar gasto";
    actualizarAccionesModal();
}

function abrirModal(){
    resetFormularioGasto();
    modal.classList.add("show");
}

function cerrarModal(){
    modal.classList.remove("show");
}

async function editarGasto(id){
    try {
        ocultarAlerta();
        const detalle = await apiGet(`${API}/gastos/${id}`);
        const gasto = detalle.data || {};

        document.getElementById("tituloModal").textContent = "Editar gasto";
        document.getElementById("id_gasto").value = gasto.id_gasto || "";
        document.getElementById("g_categoria").value = gasto.categoria || "";
        document.getElementById("g_descripcion").value = gasto.descripcion || "";
        document.getElementById("g_monto").value = gasto.monto || "";
        document.getElementById("g_metodo_pago").value = gasto.metodo_pago || "EFECTIVO";
        document.getElementById("g_fecha_gasto").value = mysqlToDatetimeLocal(gasto.fecha_gasto);
        document.getElementById("g_referencia").value = gasto.referencia || "";
        document.getElementById("g_observacion").value = gasto.observacion || "";
        document.getElementById("g_id_caja").value = gasto.id_caja || "";
        btnGuardar.disabled = false;
        btnGuardar.textContent = "Guardar cambios";
        actualizarAccionesModal(gasto.id_gasto);

        modal.classList.add("show");
    } catch (error) {
        mostrarAlerta(error.message || "No fue posible cargar el gasto.");
    }
}

async function guardarGasto(){
    const id = document.getElementById("id_gasto").value;
    const payload = {
        categoria: document.getElementById("g_categoria").value.trim(),
        descripcion: limpiarTexto(document.getElementById("g_descripcion").value),
        monto: document.getElementById("g_monto").value,
        metodo_pago: document.getElementById("g_metodo_pago").value,
        fecha_gasto: datetimeLocalToMysql(document.getElementById("g_fecha_gasto").value),
        referencia: limpiarTexto(document.getElementById("g_referencia").value),
        observacion: limpiarTexto(document.getElementById("g_observacion").value),
        id_caja: limpiarTexto(document.getElementById("g_id_caja").value)
    };

    btnGuardar.disabled = true;
    btnGuardar.textContent = id ? "Guardando..." : "Creando...";

    try {
        if (id) {
            await apiSend(`${API}/gastos/${id}`, "PUT", payload);
        } else {
            await apiSend(`${API}/gastos`, "POST", payload);
        }

        cerrarModal();
        await cargarGastos();
    } catch (error) {
        mostrarAlerta(error.message || "No fue posible guardar el gasto.");
    } finally {
        btnGuardar.disabled = false;
        btnGuardar.textContent = id ? "Guardar cambios" : "Guardar gasto";
        actualizarAccionesModal(id);
    }
}

async function eliminarGastoDesdeModal(){
    const id = Number(document.getElementById("id_gasto").value || 0);

    if (!id) {
        return;
    }

    const etiqueta = obtenerEtiquetaGasto({
        id_gasto: id,
        categoria: document.getElementById("g_categoria").value,
        descripcion: document.getElementById("g_descripcion").value
    });

    await eliminarGasto(id, etiqueta);
}

async function eliminarGasto(id, etiqueta = ""){
    const idGasto = Number(id);

    if (!idGasto || eliminandoGastos.has(idGasto)) {
        return;
    }

    const gastoActual = gastosActuales.find((row) => Number(row.id_gasto) === idGasto);
    const nombreGasto = limpiarTexto(etiqueta) || obtenerEtiquetaGasto(gastoActual || { id_gasto: idGasto });

    if (!confirm(`¿Eliminar el gasto ${nombreGasto}?`)) {
        return;
    }

    ocultarAlerta();
    eliminandoGastos.add(idGasto);
    renderTablaGastos(gastosActuales);
    actualizarAccionesModal(idGasto);

    if (String(document.getElementById("id_gasto").value) === String(idGasto)) {
        btnGuardar.disabled = true;
        btnGuardar.textContent = "Eliminando...";
    }

    try {
        await apiSend(`${API}/gastos/${idGasto}`, "DELETE");

        if (String(document.getElementById("id_gasto").value) === String(idGasto)) {
            cerrarModal();
            resetFormularioGasto();
        }
        await cargarGastos();
    } catch (error) {
        mostrarAlerta(error.message || "No fue posible eliminar el gasto.");
    } finally {
        eliminandoGastos.delete(idGasto);
        renderTablaGastos(gastosActuales);
        actualizarAccionesModal();

        if (modal.classList.contains("show")) {
            const idActual = document.getElementById("id_gasto").value;
            btnGuardar.disabled = false;
            btnGuardar.textContent = idActual ? "Guardar cambios" : "Guardar gasto";
        }
    }
}

function actualizarAccionesModal(id = document.getElementById("id_gasto").value){
    const idGasto = Number(id || 0);
    const mostrarEliminar = idGasto > 0;
    const eliminando = mostrarEliminar && eliminandoGastos.has(idGasto);

    btnEliminarModal.style.display = mostrarEliminar ? "inline-flex" : "none";
    btnEliminarModal.disabled = !mostrarEliminar || eliminando;
    btnEliminarModal.textContent = eliminando ? "Eliminando..." : "Eliminar gasto";
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

function formatoPeriodo(desde, hasta){
    if (!desde && !hasta) return "Historico";
    if (desde && hasta) return `${fechaBonita(desde)} a ${fechaBonita(hasta)}`;
    if (desde) return `Desde ${fechaBonita(desde)}`;
    return `Hasta ${fechaBonita(hasta)}`;
}

function fechaBonita(value){
    if (!value) return "--";
    const parts = String(value).split("-");
    if (parts.length !== 3) return value;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

function fechaHoraBonita(value){
    if (!value) return "--";

    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);

    if (match) {
        return `${match[3]}/${match[2]}/${match[1]} ${match[4]}:${match[5]}`;
    }

    return String(value).replace("T", " ");
}

function mysqlToDatetimeLocal(value){
    if (!value) return "";

    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);

    if (match) {
        return `${match[1]}-${match[2]}-${match[3]}T${match[4]}:${match[5]}`;
    }

    return "";
}

function datetimeLocalToMysql(value){
    if (!value) return "";
    return `${value.replace("T", " ")}:00`;
}

function limpiarTexto(value){
    const text = String(value || "").trim();
    return text || null;
}

function obtenerEtiquetaGasto(row){
    const categoria = limpiarTexto(row?.categoria);
    const descripcion = limpiarTexto(row?.descripcion);

    if (categoria && descripcion) {
        return `${categoria} - ${descripcion}`;
    }

    return categoria || descripcion || `Registro #${row?.id_gasto || ""}`;
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
