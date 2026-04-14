@extends('admin.layout')

@section('title', 'Inventario')

@section('content')

<style>
/* =========================
   WRAPPER
========================= */
.page-wrap{
    display:flex;
    flex-direction:column;
    gap:20px;
}

/* =========================
   CARD
========================= */
.panel-card{
    background: linear-gradient(180deg,#081225,#07101f);
    border:1px solid rgba(255,255,255,0.06);
    border-radius:20px;
    padding:20px;
    box-shadow:0 18px 40px rgba(0,0,0,.4);
}

/* =========================
   HEADER
========================= */
.panel-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
    gap:12px;
    flex-wrap:wrap;
}

.panel-title{
    color:#fff;
    font-size:20px;
    font-weight:800;
}

.header-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

/* =========================
   FILTROS
========================= */
.filtros{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.select-pro,
.input-pro{
    background:#0f172a;
    border:1px solid rgba(255,255,255,0.1);
    color:#fff;
    border-radius:10px;
    padding:10px;
    font-size:13px;
    outline:none;
    min-width:160px;
}

.select-pro:focus,
.input-pro:focus{
    border-color:#f4c842;
    box-shadow:0 0 0 2px rgba(244,200,66,.2);
}

/* =========================
   TABLA
========================= */
.table-wrap{
    margin-top:15px;
    border-radius:14px;
    overflow:hidden;
    border:1px solid rgba(255,255,255,0.05);
}

.table-pro{
    width:100%;
    border-collapse:collapse;
}

.table-pro thead{
    background:rgba(255,255,255,0.03);
}

.table-pro th{
    padding:14px;
    font-size:12px;
    color:#94a3b8;
    text-align:left;
}

.table-pro td{
    padding:14px;
    border-top:1px solid rgba(255,255,255,0.05);
    color:#e5e7eb;
    font-size:13px;
}

.table-pro tr:hover{
    background:rgba(255,255,255,0.03);
}

/* =========================
   STOCK BAJO
========================= */
.row-warning{
    background:rgba(251,191,36,0.08);
}

/* =========================
   BADGE
========================= */
.badge{
    padding:5px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
}

.badge-ok{
    background:rgba(34,197,94,.15);
    color:#4ade80;
}

.badge-low{
    background:rgba(251,191,36,.15);
    color:#facc15;
}

/* =========================
   BOTÓN
========================= */
.btn-ajustar{
    background: linear-gradient(135deg,#f4c842,#d8a910);
    border:none;
    padding:6px 14px;
    border-radius:10px;
    font-size:12px;
    font-weight:800;
    color:#111827;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    gap:5px;
    transition: all .2s ease;
    box-shadow: 0 6px 15px rgba(216,169,16,.25);
}

.btn-pdf{
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    color:#fff;
    box-shadow:0 6px 15px rgba(37,99,235,.25);
}

.btn-transfer{
    background:linear-gradient(135deg,#38bdf8,#1d4ed8);
    color:#fff;
    box-shadow:0 6px 15px rgba(29,78,216,.28);
}

.action-buttons{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

/* hover */
.btn-ajustar:hover{
    transform: translateY(-1px);
    box-shadow: 0 10px 20px rgba(216,169,16,.35);
}

/* click */
.btn-ajustar:active{
    transform: scale(0.96);
    box-shadow: 0 4px 10px rgba(0,0,0,.4);
}

.modal-backdrop{
    position:fixed;
    inset:0;
    background:rgba(2,6,23,.78);
    backdrop-filter:blur(4px);
    display:none;
    align-items:center;
    justify-content:center;
    padding:20px;
    z-index:9999;
}

.modal-backdrop.show{
    display:flex;
}

.modal-card{
    width:min(610px, 100%);
    background:linear-gradient(180deg,#081225,#07101f);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:20px;
    padding:18px;
    box-shadow:0 30px 70px rgba(0,0,0,.45);
}

.modal-title{
    color:#fff;
    font-size:18px;
    font-weight:800;
    margin:0 0 8px;
}

.modal-copy{
    color:#cbd5e1;
    font-size:12px;
    line-height:1.55;
    margin:0 0 12px;
}

.modal-note{
    display:flex;
    gap:10px;
    align-items:flex-start;
    background:rgba(251,191,36,.08);
    border:1px solid rgba(251,191,36,.22);
    border-radius:14px;
    padding:12px 14px;
    margin-bottom:14px;
    color:#fde68a;
    font-size:12px;
    line-height:1.55;
}

.modal-note strong{
    color:#facc15;
}

.imei-rows{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.imei-row-card{
    background:rgba(15,23,42,.92);
    border:1px solid rgba(255,255,255,.07);
    border-radius:16px;
    padding:12px 14px;
    overflow:hidden;
}

.imei-row-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:10px;
}

.imei-row-badge{
    display:inline-flex;
    align-items:center;
    padding:6px 10px;
    border-radius:999px;
    background:rgba(59,130,246,.14);
    color:#bfdbfe;
    font-size:12px;
    font-weight:700;
}

.imei-row-remove{
    border:none;
    background:transparent;
    color:#94a3b8;
    font-size:12px;
    font-weight:700;
    cursor:pointer;
    padding:0;
}

.imei-row-remove:hover{
    color:#fff;
}

.imei-row-remove[disabled]{
    opacity:.35;
    cursor:not-allowed;
}

.imei-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    align-items:start;
    gap:12px;
}

.imei-field{
    display:flex;
    flex-direction:column;
    gap:5px;
    width:100%;
    min-width:0;
    box-sizing:border-box;
}

.imei-field-primary{
    max-width:none;
    justify-self:stretch;
}

.imei-field-secondary{
    max-width:none;
    justify-self:stretch;
    margin-left:0;
}

.imei-label{
    color:#e2e8f0;
    font-size:11px;
    font-weight:700;
}

.imei-input{
    width:100%;
    background:#0b1324;
    border:1px solid rgba(255,255,255,.1);
    color:#fff;
    border-radius:12px;
    box-sizing:border-box;
    padding:8px 10px;
    font-size:12px;
    min-height:34px;
    outline:none;
}

.imei-input:focus{
    border-color:#f4c842;
    box-shadow:0 0 0 2px rgba(244,200,66,.2);
}

.imei-help{
    margin-top:8px;
    color:#94a3b8;
    font-size:11px;
    line-height:1.5;
}

.imei-footer{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    margin-top:12px;
    flex-wrap:wrap;
}

.imei-counter{
    color:#f8fafc;
    font-size:12px;
    font-weight:800;
}

.imei-counter strong{
    color:#facc15;
}

.transfer-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:12px;
}

.transfer-field{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.transfer-label{
    color:#e2e8f0;
    font-size:12px;
    font-weight:700;
}

.transfer-input,
.transfer-select{
    width:100%;
    min-height:38px;
    background:#0b1324;
    border:1px solid rgba(255,255,255,.1);
    color:#fff;
    border-radius:12px;
    box-sizing:border-box;
    padding:9px 11px;
    font-size:12px;
    outline:none;
}

.transfer-input:focus,
.transfer-select:focus{
    border-color:#38bdf8;
    box-shadow:0 0 0 2px rgba(56,189,248,.18);
}

.transfer-section{
    margin-top:14px;
}

.transfer-summary{
    color:#cbd5e1;
    font-size:12px;
    line-height:1.5;
}

.transfer-summary strong{
    color:#f8fafc;
}

.transfer-helper{
    margin-top:8px;
    color:#94a3b8;
    font-size:11px;
    line-height:1.5;
}

.transfer-imei-list{
    max-height:250px;
    overflow:auto;
    display:flex;
    flex-direction:column;
    gap:10px;
    margin-top:10px;
    padding-right:4px;
}

.transfer-imei-item{
    display:flex;
    gap:10px;
    align-items:flex-start;
    background:rgba(15,23,42,.92);
    border:1px solid rgba(255,255,255,.07);
    border-radius:14px;
    padding:10px 12px;
}

.transfer-imei-item input{
    margin-top:2px;
}

.transfer-imei-copy{
    display:flex;
    flex-direction:column;
    gap:4px;
    min-width:0;
}

.transfer-imei-title{
    color:#fff;
    font-size:12px;
    font-weight:700;
    word-break:break-word;
}

.transfer-imei-subtitle{
    color:#94a3b8;
    font-size:11px;
    word-break:break-word;
}

.transfer-empty{
    border:1px dashed rgba(255,255,255,.14);
    border-radius:14px;
    padding:14px;
    color:#94a3b8;
    font-size:12px;
    text-align:center;
}

.transfer-hidden{
    display:none;
}

@media (max-width: 760px){
    .imei-grid{
        grid-template-columns:1fr;
        justify-content:stretch;
    }

    .imei-field-primary,
    .imei-field-secondary{
        max-width:none;
        justify-self:stretch;
        margin-left:0;
    }

    .imei-footer{
        align-items:stretch;
    }

    .transfer-grid{
        grid-template-columns:1fr;
    }
}

.modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:16px;
    flex-wrap:wrap;
}

.btn-modal-secondary{
    background:#1e293b;
    color:#fff;
    box-shadow:none;
}
</style>


<div class="page-wrap">

    <!-- INVENTARIO -->
    <div class="panel-card">

        <!-- HEADER -->
        <div class="panel-header">
            <h3 class="panel-title">📦 Inventario</h3>
            <div class="header-actions">
                <button class="btn-ajustar btn-pdf" id="btnDescargarPdfStock">
                    Descargar PDF
                </button>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="filtros">
            <select id="f_categoria" class="select-pro">
                <option value="">Todas las categorías</option>
            </select>

            <select id="f_subcategoria" class="select-pro">
                <option value="">Todas las subcategorías</option>
            </select>

            <select id="f_producto" class="select-pro">
                <option value="">Todos los productos</option>
            </select>

            <input id="f_buscar" class="input-pro" placeholder="Buscar...">

            <label style="display:flex;align-items:center;color:#fff;font-size:12px;">
                <input type="checkbox" id="f_stock_bajo" style="margin-right:5px;">
                Stock bajo
            </label>
        </div>

        <!-- TABLA -->
        <div class="table-wrap">
            <table class="table-pro">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody id="tablaInventario">
                    <tr>
                        <td colspan="7" style="text-align:center;color:#94a3b8;">
                            Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

</div>

<div class="modal-backdrop" id="imeiModal">
    <div class="modal-card">
        <h4 class="modal-title" id="imeiModalTitle">Ingresar IMEIs</h4>
        <p class="modal-copy">
            Registra un celular por fila para que se diferencie claramente entre <b>IMEI 1</b> e <b>IMEI 2</b>.
        </p>
        <div class="modal-note">
            <div>
                <strong>Cada fila representa 1 celular.</strong>
                IMEI 1 es obligatorio. IMEI 2 es opcional y pertenece al mismo equipo, asi que no suma otro celular.
            </div>
        </div>

        <div class="imei-rows" id="imeiRows"></div>

        <div class="imei-footer">
            <button type="button" class="btn-ajustar btn-modal-secondary" id="btnAgregarFilaImei">
                + Agregar otro celular
            </button>
            <div class="imei-counter" id="imeiReadyCount">
                <strong>0</strong> celulares listos para ingresar
            </div>
        </div>

        <div class="modal-actions">
            <button type="button" class="btn-ajustar btn-modal-secondary" id="btnCerrarImeiModal">
                Cancelar
            </button>
            <button type="button" class="btn-ajustar" id="btnGuardarImeiModal">
                Guardar IMEIs
            </button>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="traspasoModal">
    <div class="modal-card">
        <h4 class="modal-title" id="traspasoModalTitle">Traspasar producto</h4>
        <p class="modal-copy">
            Mueve stock de este local a otro local activo. Si el producto no existe en destino, el sistema lo crea automaticamente.
        </p>

        <div class="modal-note">
            <div>
                <strong>El traspaso descuenta del local actual y suma en el local destino.</strong>
                Para celulares, se transfieren los IMEIs reales seleccionados.
            </div>
        </div>

        <div class="transfer-grid">
            <label class="transfer-field">
                <span class="transfer-label">Local destino</span>
                <select id="traspasoLocalDestino" class="transfer-select">
                    <option value="">Seleccionar destino</option>
                </select>
            </label>

            <label class="transfer-field" id="traspasoCantidadWrap">
                <span class="transfer-label">Cantidad</span>
                <input
                    id="traspasoCantidad"
                    type="number"
                    min="1"
                    step="1"
                    class="transfer-input"
                    placeholder="Cantidad"
                >
            </label>
        </div>

        <div class="transfer-section">
            <div class="transfer-summary" id="traspasoResumen">
                Stock disponible en este local: <strong>0</strong>
            </div>
            <div class="transfer-helper" id="traspasoHelper">
                Selecciona el local destino y confirma la cantidad a mover.
            </div>
        </div>

        <div class="transfer-section transfer-hidden" id="traspasoImeiWrap">
            <div class="transfer-summary" id="traspasoImeiResumen">
                <strong>0</strong> equipos seleccionados para traspaso
            </div>
            <div class="transfer-imei-list" id="traspasoImeiList"></div>
        </div>

        <div class="modal-actions">
            <button type="button" class="btn-ajustar btn-modal-secondary" id="btnCerrarTraspasoModal">
                Cancelar
            </button>
            <button type="button" class="btn-ajustar btn-transfer" id="btnGuardarTraspasoModal">
                Confirmar traspaso
            </button>
        </div>
    </div>
</div>


<script>
const API   = "{{ env('API_URL') }}";
const token = localStorage.getItem("token");

if (!token) location.href = "/login";

/* ===========================
   ELEMENTOS
=========================== */
const f_categoria    = document.getElementById("f_categoria");
const f_subcategoria = document.getElementById("f_subcategoria");
const f_producto     = document.getElementById("f_producto");
const f_buscar       = document.getElementById("f_buscar");
const f_stock_bajo   = document.getElementById("f_stock_bajo");
const tabla          = document.getElementById("tablaInventario");
const btnDescargarPdfStock = document.getElementById("btnDescargarPdfStock");
const imeiModal = document.getElementById("imeiModal");
const imeiModalTitle = document.getElementById("imeiModalTitle");
const imeiRows = document.getElementById("imeiRows");
const btnCerrarImeiModal = document.getElementById("btnCerrarImeiModal");
const btnGuardarImeiModal = document.getElementById("btnGuardarImeiModal");
const btnAgregarFilaImei = document.getElementById("btnAgregarFilaImei");
const imeiReadyCount = document.getElementById("imeiReadyCount");
const traspasoModal = document.getElementById("traspasoModal");
const traspasoModalTitle = document.getElementById("traspasoModalTitle");
const traspasoLocalDestino = document.getElementById("traspasoLocalDestino");
const traspasoCantidadWrap = document.getElementById("traspasoCantidadWrap");
const traspasoCantidad = document.getElementById("traspasoCantidad");
const traspasoResumen = document.getElementById("traspasoResumen");
const traspasoHelper = document.getElementById("traspasoHelper");
const traspasoImeiWrap = document.getElementById("traspasoImeiWrap");
const traspasoImeiResumen = document.getElementById("traspasoImeiResumen");
const traspasoImeiList = document.getElementById("traspasoImeiList");
const btnCerrarTraspasoModal = document.getElementById("btnCerrarTraspasoModal");
const btnGuardarTraspasoModal = document.getElementById("btnGuardarTraspasoModal");

let inventarioActual = [];
let productoImeiActivo = null;
let productoTraspasoActivo = null;
let localesDestinoCache = [];
let imeisTraspasoDisponibles = [];

/* ===========================
   INIT
=========================== */
init();

async function init(){
    await cargarCategorias();
    try{
        await cargarLocalesDestino();
    }catch(error){
        console.error("Error cargar locales destino:", error);
    }
    await cargarInventario();
}

/* ===========================
   FETCH BASE
=========================== */
async function api(url){
    const res = await fetch(url,{
        headers:{ Authorization: "Bearer "+token }
    });

    if(!res.ok){
        alert("Error de conexión");
        throw new Error("API error");
    }

    return res.json();
}

/* ===========================
   CATEGORIAS
=========================== */
async function cargarCategorias(){
    const json = await api(`${API}/categorias`);

    f_categoria.innerHTML = `<option value="">Todas</option>`;

    json.data.forEach(c=>{
        f_categoria.innerHTML += `<option value="${c.id_categoria}">${c.nombre_categoria}</option>`;
    });
}

async function cargarLocalesDestino(){
    const json = await api(`${API}/inventario/locales-destino`);
    localesDestinoCache = Array.isArray(json.data) ? json.data : [];
    renderLocalesDestino();
}

function renderLocalesDestino(selectedValue = ""){
    traspasoLocalDestino.innerHTML = `<option value="">Seleccionar destino</option>`;

    localesDestinoCache.forEach((local) => {
        const selected = String(local.id_local) === String(selectedValue) ? "selected" : "";
        traspasoLocalDestino.innerHTML += `
            <option value="${local.id_local}" ${selected}>
                ${local.nombre_local}
            </option>
        `;
    });
}

/* ===========================
   SUBCATEGORIAS
=========================== */
f_categoria.onchange = async ()=>{
    const json = await api(`${API}/subcategorias`);

    f_subcategoria.innerHTML = `<option value="">Todas</option>`;

    json.data
        .filter(s => s.id_categoria == f_categoria.value)
        .forEach(s=>{
            f_subcategoria.innerHTML += `<option value="${s.id_subcategoria}">${s.nombre_subcategoria}</option>`;
        });

    f_producto.innerHTML = `<option value="">Todos</option>`;
    cargarInventario();
};

/* ===========================
   PRODUCTOS
=========================== */
f_subcategoria.onchange = async ()=>{
    const json = await api(`${API}/productos`);

    f_producto.innerHTML = `<option value="">Todos</option>`;

    json.data
        .filter(p => p.id_subcategoria == f_subcategoria.value)
        .forEach(p=>{
            f_producto.innerHTML += `<option value="${p.id_producto}">${p.nombre_producto}</option>`;
        });

    cargarInventario();
};

/* ===========================
   FILTROS
=========================== */
[f_producto, f_buscar, f_stock_bajo].forEach(el=>{
    el.onchange = cargarInventario;
});

/* ===========================
   INVENTARIO
=========================== */
async function cargarInventario(){

    tabla.innerHTML = `<tr><td colspan="7" class="text-center">Cargando...</td></tr>`;

    const params = new URLSearchParams();

    if(f_categoria.value)    params.append("id_categoria", f_categoria.value);
    if(f_subcategoria.value) params.append("id_subcategoria", f_subcategoria.value);
    if(f_producto.value)     params.append("id_producto", f_producto.value);
    if(f_buscar.value)       params.append("buscar", f_buscar.value);
    if(f_stock_bajo.checked) params.append("stock_bajo", 1);

    const json = await api(`${API}/inventario?${params}`);
    inventarioActual = Array.isArray(json.data) ? json.data : [];

    tabla.innerHTML = "";

    if(!inventarioActual.length){
        tabla.innerHTML = `<tr><td colspan="7" class="text-center">Sin resultados</td></tr>`;
        return;
    }

    inventarioActual.forEach((i,idx)=>{

        const bajo = i.stock_actual <= i.stock_minimo;
        const usaImei = esProductoConImei(i);

        tabla.innerHTML += `
        <tr class="${bajo ? 'table-warning' : ''}">
            <td>${idx+1}</td>
            <td>${i.nombre_producto}</td>
            <td>${i.nombre_categoria} / ${i.nombre_subcategoria}</td>
            <td><b>${i.stock_actual}</b></td>
            <td>$${i.precio_unitario}</td>
            <td>${bajo ? '⚠ Bajo' : '✔ OK'}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-dark btn-ajustar" onclick="ajustar(${i.id_producto})">
                        ${usaImei ? 'Ajustar / IMEI' : 'Ajustar'}
                    </button>
                    <button class="btn btn-sm btn-dark btn-ajustar btn-transfer" onclick="abrirTraspaso(${i.id_producto})">
                        Traspasar
                    </button>
                </div>
            </td>
        </tr>`;
    });
}

function normalizarTexto(valor = ""){
    return valor
        .toString()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .trim()
        .toLowerCase();
}

function esProductoConImei(producto){
    const categoria = normalizarTexto(producto?.nombre_categoria);
    const subcategoria = normalizarTexto(producto?.nombre_subcategoria);
    return categoria.includes("celular") || subcategoria.includes("celular") || subcategoria.includes("telefono");
}

function cerrarModalImei(limpiar = true){
    imeiModal.classList.remove("show");

    if(limpiar){
        reiniciarFormularioImei();
        productoImeiActivo = null;
    }
}

function abrirModalImei(producto){
    productoImeiActivo = producto;
    imeiModalTitle.textContent = `Ingresar IMEIs: ${producto.nombre_producto}`;
    reiniciarFormularioImei();
    imeiModal.classList.add("show");
    const primerInput = imeiRows.querySelector('[data-field="imei1"]');

    if(primerInput){
        primerInput.focus();
    }
}

function normalizarImeiValor(valor = ""){
    return valor
        .toString()
        .replace(/\D+/g, "")
        .trim();
}

function obtenerFilasImei(){
    return Array.from(imeiRows.querySelectorAll("[data-imei-row]"));
}

function actualizarEstadoFilasImei(){
    const filas = obtenerFilasImei();

    filas.forEach((fila, index) => {
        const titulo = fila.querySelector("[data-imei-row-title]");
        const btnQuitar = fila.querySelector("[data-remove-row]");

        titulo.textContent = `Celular ${index + 1}`;
        btnQuitar.disabled = filas.length === 1;
    });

    const listos = filas.filter((fila) => {
        const imei1 = fila.querySelector('[data-field="imei1"]');
        return normalizarImeiValor(imei1?.value).length > 0;
    }).length;

    imeiReadyCount.innerHTML = `<strong>${listos}</strong> celular${listos === 1 ? "" : "es"} listo${listos === 1 ? "" : "s"} para ingresar`;
}

function crearFilaImei(valores = {}){
    const fila = document.createElement("div");
    fila.className = "imei-row-card";
    fila.dataset.imeiRow = "true";
    fila.innerHTML = `
        <div class="imei-row-head">
            <span class="imei-row-badge" data-imei-row-title>Celular</span>
            <button type="button" class="imei-row-remove" data-remove-row>Quitar</button>
        </div>

        <div class="imei-grid">
            <label class="imei-field imei-field-primary">
                <span class="imei-label">IMEI 1</span>
                <input
                    type="text"
                    class="imei-input"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="Obligatorio"
                    data-field="imei1"
                >
            </label>

            <label class="imei-field imei-field-secondary">
                <span class="imei-label">IMEI 2 Opcional</span>
                <input
                    type="text"
                    class="imei-input"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="Opcional"
                    data-field="imei2"
                >
            </label>
        </div>

        <div class="imei-help">
            Si el equipo tiene un solo IMEI, deja IMEI 2 vacio. Esta fila sigue contando como 1 celular.
        </div>
    `;

    fila.querySelector('[data-field="imei1"]').value = normalizarImeiValor(valores.imei1 || "");
    fila.querySelector('[data-field="imei2"]').value = normalizarImeiValor(valores.imei2 || "");

    return fila;
}

function agregarFilaImei(valores = {}, enfocar = true){
    const fila = crearFilaImei(valores);
    imeiRows.appendChild(fila);
    actualizarEstadoFilasImei();

    if(enfocar){
        const input = fila.querySelector('[data-field="imei1"]');
        input?.focus();
    }

    return fila;
}

function reiniciarFormularioImei(){
    imeiRows.innerHTML = "";
    agregarFilaImei({}, false);
}

function obtenerImeisDesdeFormulario(){
    return obtenerFilasImei()
        .map((fila) => {
            const imei1 = normalizarImeiValor(fila.querySelector('[data-field="imei1"]')?.value || "");
            const imei2 = normalizarImeiValor(fila.querySelector('[data-field="imei2"]')?.value || "");

            return {
                imei1,
                imei2: imei2 || null
            };
        })
        .filter(item => item.imei1);
}

async function guardarImeisDesdeModal(){
    if(!productoImeiActivo){
        return;
    }

    const imeis = obtenerImeisDesdeFormulario();

    if(!imeis.length){
        alert("Debe ingresar al menos un IMEI 1 valido");
        return;
    }

    const originalText = btnGuardarImeiModal.innerText;
    btnGuardarImeiModal.disabled = true;
    btnGuardarImeiModal.innerText = "Guardando...";

    try{
        const res = await fetch(`${API}/inventario/${productoImeiActivo.id_producto}/imei`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: "Bearer " + token
            },
            body: JSON.stringify({ imeis })
        });

        const json = await res.json();

        if(!json.ok){
            alert(json.mensaje || "No se pudieron registrar los IMEIs");
            return;
        }

        alert(json.mensaje || "IMEIs ingresados correctamente");
        cerrarModalImei();
        cargarInventario();
    }catch(error){
        console.error("Error guardar IMEIs:", error);
        alert("No se pudieron registrar los IMEIs");
    }finally{
        btnGuardarImeiModal.disabled = false;
        btnGuardarImeiModal.innerText = originalText;
    }
}

function resetearTraspasoModal(){
    productoTraspasoActivo = null;
    imeisTraspasoDisponibles = [];
    renderLocalesDestino();
    traspasoCantidad.value = "1";
    traspasoResumen.innerHTML = `Stock disponible en este local: <strong>0</strong>`;
    traspasoHelper.textContent = "Selecciona el local destino y confirma la cantidad a mover.";
    traspasoImeiResumen.innerHTML = `<strong>0</strong> equipos seleccionados para traspaso`;
    traspasoImeiList.innerHTML = "";
    traspasoCantidadWrap.classList.remove("transfer-hidden");
    traspasoImeiWrap.classList.add("transfer-hidden");
}

function cerrarModalTraspaso(limpiar = true){
    traspasoModal.classList.remove("show");

    if(limpiar){
        resetearTraspasoModal();
    }
}

function obtenerImeisSeleccionadosParaTraspaso(){
    return Array.from(traspasoImeiList.querySelectorAll("[data-traspaso-imei]:checked"))
        .map(input => input.value)
        .filter(Boolean);
}

function actualizarResumenTraspaso(){
    if(!productoTraspasoActivo){
        return;
    }

    const stock = Number(productoTraspasoActivo.stock_actual || 0);
    traspasoResumen.innerHTML = `Stock disponible en este local: <strong>${stock}</strong>`;

    if(esProductoConImei(productoTraspasoActivo)){
        const seleccionados = obtenerImeisSeleccionadosParaTraspaso().length;
        traspasoImeiResumen.innerHTML = `<strong>${seleccionados}</strong> equipos seleccionados para traspaso`;
        traspasoHelper.textContent = "Selecciona los IMEIs exactos que quieres mover al otro local.";
        return;
    }

    traspasoHelper.textContent = `Puedes mover hasta ${stock} unidad(es) a otro local activo.`;
}

function renderImeisTraspaso(){
    if(!imeisTraspasoDisponibles.length){
        traspasoImeiList.innerHTML = `
            <div class="transfer-empty">
                No hay IMEIs disponibles para este producto en el local actual.
            </div>
        `;
        actualizarResumenTraspaso();
        return;
    }

    traspasoImeiList.innerHTML = imeisTraspasoDisponibles.map((item) => `
        <label class="transfer-imei-item">
            <input type="checkbox" value="${item.imei1}" data-traspaso-imei>
            <span class="transfer-imei-copy">
                <span class="transfer-imei-title">IMEI 1: ${item.imei1}</span>
                <span class="transfer-imei-subtitle">${item.imei2 ? `IMEI 2: ${item.imei2}` : "Sin IMEI 2"}</span>
            </span>
        </label>
    `).join("");

    actualizarResumenTraspaso();
}

async function cargarImeisParaTraspaso(idProducto){
    traspasoImeiList.innerHTML = `
        <div class="transfer-empty">
            Cargando IMEIs disponibles...
        </div>
    `;

    const json = await api(`${API}/inventario/${idProducto}/imeis-disponibles`);
    imeisTraspasoDisponibles = Array.isArray(json.data) ? json.data : [];
    renderImeisTraspaso();
}

async function abrirModalTraspasoPorProducto(producto){
    productoTraspasoActivo = producto;
    traspasoModalTitle.textContent = `Traspasar: ${producto.nombre_producto}`;
    renderLocalesDestino();
    traspasoCantidad.value = "1";
    traspasoModal.classList.add("show");

    const usaImei = esProductoConImei(producto);
    traspasoCantidadWrap.classList.toggle("transfer-hidden", usaImei);
    traspasoImeiWrap.classList.toggle("transfer-hidden", !usaImei);
    actualizarResumenTraspaso();

    if(usaImei){
        await cargarImeisParaTraspaso(producto.id_producto);
    }else{
        traspasoCantidad.focus();
    }
}

async function abrirTraspaso(id){
    const producto = inventarioActual.find(item => Number(item.id_producto) === Number(id));

    if(!producto){
        alert("No se encontró el producto en la lista actual");
        return;
    }

    if(!localesDestinoCache.length){
        try{
            await cargarLocalesDestino();
        }catch(error){
            console.error("Error cargar destinos:", error);
        }
    }

    if(!localesDestinoCache.length){
        alert("No hay otros locales activos disponibles para traspasos");
        return;
    }

    await abrirModalTraspasoPorProducto(producto);
}

async function guardarTraspasoDesdeModal(){
    if(!productoTraspasoActivo){
        return;
    }

    const idLocalDestino = Number(traspasoLocalDestino.value || 0);

    if(!idLocalDestino){
        alert("Debes seleccionar un local destino");
        return;
    }

    const payload = {
        id_local_destino: idLocalDestino
    };

    if(esProductoConImei(productoTraspasoActivo)){
        const imeis = obtenerImeisSeleccionadosParaTraspaso();

        if(!imeis.length){
            alert("Debes seleccionar al menos un IMEI para el traspaso");
            return;
        }

        payload.imeis = imeis;
    }else{
        const cantidad = Number(traspasoCantidad.value || 0);

        if(!Number.isInteger(cantidad) || cantidad <= 0){
            alert("Debes ingresar una cantidad válida");
            return;
        }

        if(cantidad > Number(productoTraspasoActivo.stock_actual || 0)){
            alert("La cantidad supera el stock disponible");
            return;
        }

        payload.cantidad = cantidad;
    }

    const originalText = btnGuardarTraspasoModal.innerText;
    btnGuardarTraspasoModal.disabled = true;
    btnGuardarTraspasoModal.innerText = "Traspasando...";

    try{
        const res = await fetch(`${API}/inventario/${productoTraspasoActivo.id_producto}/traspasar`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: "Bearer " + token
            },
            body: JSON.stringify(payload)
        });

        const json = await res.json();

        if(!json.ok){
            alert(json.mensaje || "No se pudo completar el traspaso");
            return;
        }

        alert(json.mensaje || "Traspaso realizado correctamente");
        cerrarModalTraspaso();
        await cargarInventario();
    }catch(error){
        console.error("Error traspasar producto:", error);
        alert("No se pudo completar el traspaso");
    }finally{
        btnGuardarTraspasoModal.disabled = false;
        btnGuardarTraspasoModal.innerText = originalText;
    }
}

function construirParamsInventario(){
    const params = new URLSearchParams();

    if(f_categoria.value)    params.append("id_categoria", f_categoria.value);
    if(f_subcategoria.value) params.append("id_subcategoria", f_subcategoria.value);
    if(f_producto.value)     params.append("id_producto", f_producto.value);
    if(f_buscar.value)       params.append("buscar", f_buscar.value);
    if(f_stock_bajo.checked) params.append("stock_bajo", 1);

    return params;
}

async function descargarPdfStock(){
    const originalText = btnDescargarPdfStock.innerText;
    btnDescargarPdfStock.disabled = true;
    btnDescargarPdfStock.innerText = "Generando...";

    try{
        const params = construirParamsInventario();
        const res = await fetch(`${API}/reportes/admin/stock/pdf?${params.toString()}`, {
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

            alert(json.mensaje || "No se pudo generar el PDF del stock");
            return;
        }

        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = "reporte-stock.pdf";
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    }catch(error){
        console.error("Error descargar PDF stock:", error);
        alert("No se pudo descargar el PDF del stock");
    }finally{
        btnDescargarPdfStock.disabled = false;
        btnDescargarPdfStock.innerText = originalText;
    }
}

/* ===========================
   AJUSTAR STOCK
=========================== */
async function ajustarCantidadManual(id){

    const cantidad = prompt("Cantidad (+ entrada / - salida)");

    if(!cantidad || isNaN(cantidad)){
        alert("Cantidad inválida");
        return;
    }

    const res = await fetch(`${API}/inventario/${id}/ajustar`,{
        method:"PUT",
        headers:{
            "Content-Type":"application/json",
            Authorization:"Bearer "+token
        },
        body: JSON.stringify({
            cantidad: Number(cantidad)
        })
    });

    const json = await res.json();

    if(!json.ok){
        alert(json.mensaje || "Error");
        return;
    }

    cargarInventario();
}

async function ajustar(id){
    const producto = inventarioActual.find(item => Number(item.id_producto) === Number(id));

    if(!producto){
        alert("No se encontró el producto en la lista actual");
        return;
    }

    if(esProductoConImei(producto)){
        const usarImeis = confirm(
            `${producto.nombre_producto} pertenece a celulares.\n\nAceptar = ingresar equipos por IMEI\nCancelar = ajuste manual por cantidad`
        );

        if(usarImeis){
            abrirModalImei(producto);
            return;
        }
    }

    await ajustarCantidadManual(id);
}

btnCerrarImeiModal.addEventListener("click", () => cerrarModalImei());
btnGuardarImeiModal.addEventListener("click", guardarImeisDesdeModal);
btnAgregarFilaImei.addEventListener("click", () => agregarFilaImei());
btnCerrarTraspasoModal.addEventListener("click", () => cerrarModalTraspaso());
btnGuardarTraspasoModal.addEventListener("click", guardarTraspasoDesdeModal);

imeiRows.addEventListener("input", (event) => {
    const input = event.target.closest("[data-field]");

    if(!input){
        return;
    }

    input.value = normalizarImeiValor(input.value);
    actualizarEstadoFilasImei();
});

imeiRows.addEventListener("click", (event) => {
    const btnQuitar = event.target.closest("[data-remove-row]");

    if(!btnQuitar){
        return;
    }

    const filas = obtenerFilasImei();
    const fila = btnQuitar.closest("[data-imei-row]");

    if(filas.length === 1){
        fila.querySelector('[data-field="imei1"]').value = "";
        fila.querySelector('[data-field="imei2"]').value = "";
        fila.querySelector('[data-field="imei1"]').focus();
        actualizarEstadoFilasImei();
        return;
    }

    fila.remove();
    actualizarEstadoFilasImei();
});

traspasoCantidad.addEventListener("input", () => {
    if(Number(traspasoCantidad.value || 0) < 1){
        traspasoCantidad.value = "1";
    }
});

traspasoImeiList.addEventListener("change", (event) => {
    if(event.target.matches("[data-traspaso-imei]")){
        actualizarResumenTraspaso();
    }
});

imeiModal.addEventListener("click", (event) => {
    if(event.target === imeiModal){
        cerrarModalImei();
    }
});

traspasoModal.addEventListener("click", (event) => {
    if(event.target === traspasoModal){
        cerrarModalTraspaso();
    }
});

btnDescargarPdfStock.addEventListener("click", descargarPdfStock);
reiniciarFormularioImei();
resetearTraspasoModal();
</script>

@endsection
