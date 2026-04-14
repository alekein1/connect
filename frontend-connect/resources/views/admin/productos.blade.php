@extends('admin.layout')

@section('title', 'Productos')

@section('content')

<style>
   .page-wrap{
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .panel-card{
        background: linear-gradient(180deg, #081225 0%, #07101f 100%);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 18px 45px rgba(0,0,0,.35);
    }

    .panel-header{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }

    .panel-title{
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        color: #fff;
    }

    .panel-subtitle{
        margin: 6px 0 0;
        color: #94a3b8;
        font-size: 14px;
    }

    .header-actions{
        display:flex;
        align-items:center;
        gap:12px;
        flex-wrap:wrap;
    }

    .search-products{
        width:260px;
        max-width:100%;
        border: 1px solid rgba(255,255,255,0.10);
        background: #0f172a;
        color: #fff;
        border-radius: 12px;
        padding: 12px 14px;
        outline: none;
        font-size: 14px;
    }

    .search-products::placeholder{
        color:#94a3b8;
    }

    .search-products:focus{
        border-color: rgba(244,200,66,.55);
        box-shadow: 0 0 0 3px rgba(244,200,66,.10);
    }

    .btn-primary-pro{
        border: none;
        background: linear-gradient(135deg, #f4c842 0%, #d8a910 100%);
        color: #111827;
        font-weight: 800;
        padding: 12px 18px;
        border-radius: 12px;
        cursor: pointer;
        transition: .2s ease;
        box-shadow: 0 10px 20px rgba(216,169,16,.20);
    }

    .btn-primary-pro:hover{
        transform: translateY(-1px);
        opacity: .96;
    }

    .table-wrap{
        width: 100%;
        overflow-x: auto;
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.05);
        background: rgba(255,255,255,0.015);
    }

    .pagination-bar{
        display:flex;
        align-items:center;
        justify-content:center;
        gap:12px;
        margin-top:16px;
        flex-wrap:wrap;
    }

    .pagination-info{
        color:#94a3b8;
        font-size:13px;
        font-weight:600;
    }

    .table-locales{
        width: 100%;
        border-collapse: collapse;
        min-width: 880px;
    }

    .table-locales thead th{
        text-align: left;
        font-size: 13px;
        font-weight: 700;
        color: #94a3b8;
        padding: 16px 18px;
        background: rgba(255,255,255,0.02);
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }

    .table-locales tbody td{
        padding: 16px 18px;
        color: #e5e7eb;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        vertical-align: middle;
    }

    .table-locales tbody tr:hover{
        background: rgba(255,255,255,0.025);
    }

    .table-empty{
        text-align: center;
        color: #94a3b8;
        padding: 34px 20px !important;
    }

    .local-name{
        font-weight: 700;
        color: #fff;
    }

    .badge-state{
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .badge-active{
        background: rgba(34,197,94,.15);
        color: #4ade80;
        border: 1px solid rgba(34,197,94,.25);
    }

    .badge-inactive{
        background: rgba(148,163,184,.12);
        color: #cbd5e1;
        border: 1px solid rgba(148,163,184,.18);
    }

    .actions{
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-action{
        border: 1px solid rgba(255,255,255,0.08);
        background: #0f172a;
        color: #fff;
        padding: 8px 12px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: .2s ease;
    }

    .btn-action:hover{
        background: #172036;
    }

    .btn-danger{
        background: rgba(127,29,29,.18);
        color: #fca5a5;
        border: 1px solid rgba(239,68,68,.18);
    }

    .btn-danger:hover{
        background: rgba(127,29,29,.28);
    }

    .stats-grid{
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    .mini-stat{
        background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.015));
        border: 1px solid rgba(255,255,255,.05);
        border-radius: 16px;
        padding: 18px;
    }

    .mini-stat span{
        display: block;
        color: #94a3b8;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .mini-stat strong{
        font-size: 28px;
        color: #fff;
        font-weight: 800;
    }

    .modal-pro{
        position: fixed;
        inset: 0;
        background: rgba(2,6,23,.72);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
        backdrop-filter: blur(6px);
    }

    .modal-pro.show{
        display: flex;
    }

    .modal-card{
    width: 100%;
    max-width: 420px; /* 👈 MÁS COMPACTO */
    max-height: 90vh; /* 👈 NO SE SALE DE LA PANTALLA */
    
    display: flex;
    flex-direction: column;

    background: linear-gradient(180deg, #0b1220 0%, #09111d 100%);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 20px;
    box-shadow: 0 25px 70px rgba(0,0,0,.45);
    overflow: hidden;
}

    .modal-head{
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 22px 24px 14px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .modal-head h4{
        margin: 0;
        color: #fff;
        font-size: 20px;
        font-weight: 800;
    }

    .btn-close-pro{
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: none;
        background: rgba(255,255,255,.06);
        color: #fff;
        font-size: 20px;
        cursor: pointer;
    }

    .modal-body-pro{
    padding: 18px 20px;
    overflow-y: auto; /* 👈 SCROLL SOLO ADENTRO */
}

    .form-grid{
    display: flex;
    flex-direction: column;
    gap: 14px;
}
    .field{
    display: flex;
    flex-direction: column;
    gap: 4px; /* 👈 MENOS ESPACIO */
}

    .field label{
    display: inline-block;
    width: fit-content;

    font-size: 11px;
    color: #94a3b8;
    font-weight: 500;
    letter-spacing: .4px;
    text-transform: uppercase;
}
.field{
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.field input,
.select-pro{
    width: 100%;
    max-width: 100%; /* 👈 CLAVE */
    box-sizing: border-box;

    height: 40px;
    padding: 10px 12px;

    border: 1px solid rgba(255,255,255,0.10);
    background: #0f172a;
    color: #fff;
    border-radius: 12px;

    font-size: 13px;
}
.modal-body-pro{
    padding: 16px 18px;
}

    .field input{
        width: 100%;
        border: 1px solid rgba(255,255,255,0.10);
        background: #0f172a;
        color: #fff;
        border-radius: 12px;
        padding: 13px 14px;
        outline: none;
        font-size: 14px;
    }

    .field input:focus{
        border-color: rgba(244,200,66,.55);
        box-shadow: 0 0 0 3px rgba(244,200,66,.10);
    }

    .modal-foot{
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 0 24px 24px;
    }

    .btn-secondary-pro{
        border: 1px solid rgba(255,255,255,0.10);
        background: rgba(255,255,255,0.04);
        color: #fff;
        font-weight: 700;
        padding: 12px 16px;
        border-radius: 12px;
        cursor: pointer;
    }

    .alert-inline{
        margin-top: 14px;
        padding: 12px 14px;
        border-radius: 12px;
        font-size: 14px;
        display: none;
    }

    .alert-error{
        background: rgba(127,29,29,.18);
        color: #fecaca;
        border: 1px solid rgba(239,68,68,.18);
    }

    .alert-success{
        background: rgba(20,83,45,.20);
        color: #bbf7d0;
        border: 1px solid rgba(34,197,94,.18);
    }
    /* ===============================
   SELECT PRO
=============================== */

.select-pro-wrapper{
    position: relative;
    width: 100%;
}

.select-pro{
    width: 100%;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;

    border: 1px solid rgba(255,255,255,0.10);
    background: #0f172a;
    color: #fff;

    border-radius: 12px;
    padding: 13px 40px 13px 14px;

    font-size: 14px;
    outline: none;
    cursor: pointer;

    transition: all .2s ease;
}

/* HOVER */
.select-pro:hover{
    border-color: rgba(255,255,255,0.2);
}

/* FOCUS */
.select-pro:focus{
    border-color: rgba(244,200,66,.55);
    box-shadow: 0 0 0 3px rgba(244,200,66,.10);
}

/* ICONO */
.select-icon{
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #94a3b8;
    font-size: 12px;
}

/* OPCIONES (limitado por navegador) */
.select-pro option{
    background: #0f172a;
    color: #fff;
}

    @media (max-width: 900px){
        .stats-grid{
            grid-template-columns: 1fr;
        }
    }

.img-product{
    width:50px;
    height:50px;
    border-radius:12px;
    object-fit:cover;
    background:#0f172a;
    border:1px solid rgba(255,255,255,0.08);
}

.file-input{
    border:1px dashed rgba(255,255,255,.2);
    padding:14px;
    border-radius:12px;
    text-align:center;
    cursor:pointer;
}

.file-input:hover{
    border-color:#f4c842;
}

.check-field{
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 14px;
    background: rgba(255,255,255,0.02);
}

.check-field input[type="checkbox"]{
    width: 18px;
    height: 18px;
    margin-top: 2px;
    accent-color: #f4c842;
    cursor: pointer;
}

.check-copy{
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.check-copy strong{
    color: #fff;
    font-size: 14px;
}

.check-copy span{
    color: #94a3b8;
    font-size: 12px;
    line-height: 1.45;
}

.preview{
    margin-top:10px;
    width:100%;
    max-height:120px;
    object-fit:contain;
}

.image-note{
    margin-top:8px;
    color:#94a3b8;
    font-size:12px;
    line-height:1.45;
}

@media (max-width: 500px){

    .modal-card{
        max-width: 95%;
        max-height: 92vh;
    }

    .modal-foot{
        flex-direction: column;
        gap: 10px;
    }

    .modal-foot button{
        width: 100%;
    }
}
</style>

<div class="page-wrap">

    <!-- KPIs -->
    <div class="stats-grid">
        <div class="mini-stat">
            <span>Total productos</span>
            <strong id="kpiTotal">0</strong>
        </div>
        <div class="mini-stat">
            <span>Activos</span>
            <strong id="kpiActivos">0</strong>
        </div>
        <div class="mini-stat">
            <span>Inactivos</span>
            <strong id="kpiInactivos">0</strong>
        </div>
    </div>

    <!-- TABLA -->
    <div class="panel-card">

        <div class="panel-header">
            <div>
                <h3 class="panel-title">Gestión de Productos</h3>
                <p class="panel-subtitle">Inventario de tu local</p>
            </div>
            <div class="header-actions">
                <input
                    type="text"
                    id="buscarProducto"
                    class="search-products"
                    placeholder="Buscar producto..."
                >
                <button class="btn-primary-pro" onclick="abrirModal()">
                    + Nuevo Producto
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-locales">
                <thead>
                    <tr>
                        <th>Img</th>
                        <th>Producto</th>
                        <th>Marca</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody id="tablaProductos">
                    <tr>
                        <td colspan="7" class="table-empty">Cargando...</td>
                    </tr>
                </tbody>
            </table>

            <div class="pagination-bar">
                <button id="btnAnterior" class="btn-action" onclick="cambiarPagina(-1)">Anterior</button>
                <span id="infoPaginacion" class="pagination-info">Cargando pagina...</span>
                <button id="btnSiguiente" class="btn-action" onclick="cambiarPagina(1)">Siguiente</button>
            </div>
        </div>

    </div>

</div>

<!-- MODAL -->
<div class="modal-pro" id="modalProducto">
    <div class="modal-card">

        <div class="modal-head">
            <h4 id="tituloModal">Nuevo Producto</h4>
            <button class="btn-close-pro" onclick="cerrarModal()">×</button>
        </div>

        <div class="modal-body-pro">

            <input type="hidden" id="id_producto">
            <input type="hidden" id="imagen_actual">

            <div class="form-grid">

                <div class="field">
                    <label>Nombre</label>
                    <input type="text" id="nombre_producto">
                </div>

                <div class="field">
                    <label>Subcategoría</label>
                    <select id="id_subcategoria" class="select-pro"></select>
                </div>

                <div class="field">
                    <label>Marca</label>
                    <input type="text" id="marca">
                </div>

                <div class="field">
                    <label>Color</label>
                    <input type="text" id="color">
                </div>

                <div class="field">
                    <label>Capacidad</label>
                    <input type="text" id="capacidad">
                </div>

                <div class="field">
                    <label>Estado</label>
                    <input type="text" id="estado">
                </div>

                <div class="field">
                    <label>Precio compra</label>
                    <input type="number" id="precio_compra" step="0.01">
                </div>

                <div class="field">
                    <label>Precio</label>
                    <input type="number" id="precio_unitario" step="0.01">
                </div>

                <div class="field">
                    <label>Stock mínimo</label>
                    <input type="number" id="stock_minimo">
                </div>

                <div class="field">
                    <label>Impuestos</label>
                    <label class="check-field" for="graba_iva">
                        <input type="checkbox" id="graba_iva">
                        <div class="check-copy">
                            <strong>Este producto grava IVA</strong>
                            <span>Si está marcado, el precio ingresado se tomará con IVA 15% incluido al momento de vender.</span>
                        </div>
                    </label>
                </div>

                <!-- IMAGEN -->
                <div class="field">
                    <label>Imagen</label>

                    <div class="file-input" onclick="document.getElementById('imagen').click()">
                        📷 Seleccionar imagen
                    </div>

                    <input type="file" id="imagen" accept="image/*" hidden>
                    <div id="textoImagen" class="image-note">Puedes subir una imagen nueva si quieres reemplazar la actual.</div>

                    <img id="preview" class="preview" style="display:none;">
                </div>

            </div>

        </div>

        <div class="modal-foot">
            <button class="btn-secondary-pro" onclick="cerrarModal()">Cancelar</button>
            <button class="btn-primary-pro" onclick="guardar()">Guardar</button>
        </div>

    </div>
</div>

<script>
const API = "{{ env('API_URL') }}";
const token = localStorage.getItem("token");
let buscandoProductos = false;
let paginaActual = 1;
let totalPaginas = 1;
const limiteProductos = 20;

document.addEventListener("DOMContentLoaded", ()=>{
    cargarProductos();
    cargarSubcategorias();

    document.getElementById("buscarProducto").addEventListener("input", (e)=>{
        const q = e.target.value.trim();

        if(q.length >= 2){
            buscandoProductos = true;
            buscarProductos(q);
            return;
        }

        buscandoProductos = false;
        paginaActual = 1;
        cargarProductos();
    });
});

function renderTabla(data){
    const tbody = document.getElementById("tablaProductos");
    tbody.innerHTML = "";

    if(!data.length){
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="table-empty">Sin resultados</td>
            </tr>
        `;
        return;
    }

    data.forEach(p=>{
        tbody.innerHTML += `
        <tr>
            <td>
                ${p.imagen 
  ? `<img src="${API}/uploads/productos/${p.imagen}" class="img-product">` 
  : ''
}
            </td>
            <td class="local-name">${p.nombre_producto}</td>
            <td>${p.marca ?? "N/A"}</td>
            <td>$${p.precio_unitario}</td>
            <td>${p.stock_minimo ?? 0}</td>
            <td>
                <span class="badge-state ${p.activo==1?'badge-active':'badge-inactive'}">
                    ${p.activo==1?'Activo':'Inactivo'}
                </span>
            </td>
            <td>
                <button class="btn-action" onclick='editar(${JSON.stringify(p)})'>Editar</button>
                <button class="btn-action" onclick="etiqueta(${p.id_producto})">Etiqueta</button>
                <button class="btn-action btn-danger" onclick='eliminarProducto(${p.id_producto}, ${JSON.stringify(p.nombre_producto ?? "")})'>Eliminar</button>
            </td>
        </tr>`;
    });
}

function refrescarProductos(){
    const q = document.getElementById("buscarProducto").value.trim();

    if(q.length >= 2){
        buscarProductos(q);
        return;
    }

    cargarProductos();
}

function actualizarPaginacion(){
    const info = document.getElementById("infoPaginacion");
    const btnAnterior = document.getElementById("btnAnterior");
    const btnSiguiente = document.getElementById("btnSiguiente");

    if(buscandoProductos){
        info.innerText = "Resultados de busqueda";
        btnAnterior.disabled = true;
        btnSiguiente.disabled = true;
        btnAnterior.style.opacity = ".5";
        btnSiguiente.style.opacity = ".5";
        return;
    }

    info.innerText = `Pagina ${paginaActual} de ${totalPaginas}`;
    btnAnterior.disabled = paginaActual <= 1;
    btnSiguiente.disabled = paginaActual >= totalPaginas;
    btnAnterior.style.opacity = paginaActual <= 1 ? ".5" : "1";
    btnSiguiente.style.opacity = paginaActual >= totalPaginas ? ".5" : "1";
}

function cambiarPagina(delta){
    if(buscandoProductos){
        return;
    }

    const nuevaPagina = paginaActual + delta;

    if(nuevaPagina < 1 || nuevaPagina > totalPaginas){
        return;
    }

    paginaActual = nuevaPagina;
    cargarProductos();
}

/* ===============================
   LISTAR
=============================== */
async function cargarProductos(){

    const res = await fetch(`${API}/productos?page=${paginaActual}&limit=${limiteProductos}`, {
        headers:{ Authorization:"Bearer "+token }
    });

    const json = await res.json();
    const data = json.data || [];
    const pagination = json.pagination || {};

    totalPaginas = Number(pagination.total_pages || 1);

    document.getElementById("kpiTotal").innerText = Number(pagination.total || data.length);
    document.getElementById("kpiActivos").innerText = data.filter(p=>p.activo==1).length;
    document.getElementById("kpiInactivos").innerText = data.filter(p=>p.activo==0).length;
    renderTabla(data);
    actualizarPaginacion();
}

/* ===============================
   SUBCATEGORIAS
=============================== */
async function cargarSubcategorias(){

    const res = await fetch(`${API}/subcategorias`, {
        headers:{ Authorization:"Bearer "+token }
    });

    const json = await res.json();
    const select = document.getElementById("id_subcategoria");

    select.innerHTML = "";

    json.data.forEach(s=>{
        select.innerHTML += `<option value="${s.id_subcategoria}">
            ${s.nombre_categoria} - ${s.nombre_subcategoria}
        </option>`;
    });
}

async function buscarProductos(q){
    try{
        const res = await fetch(`${API}/productos/buscar?q=${encodeURIComponent(q)}`, {
            headers:{ Authorization:"Bearer "+token }
        });

        const json = await res.json();
        const data = json.data || [];

        document.getElementById("kpiTotal").innerText = data.length;
        document.getElementById("kpiActivos").innerText = data.filter(p=>p.activo==1).length;
        document.getElementById("kpiInactivos").innerText = data.filter(p=>p.activo==0).length;
        renderTabla(data);
        actualizarPaginacion();
    }catch(error){
        console.error("Error buscar producto:", error);
    }
}


/* ===============================
   MODAL
=============================== */
function abrirModal(){

    // limpiar si es nuevo
    document.getElementById("tituloModal").innerText = "Nuevo Producto";
    document.getElementById("id_producto").value = "";

    document.getElementById("nombre_producto").value = "";
    document.getElementById("marca").value = "";
    document.getElementById("color").value = "";
    document.getElementById("capacidad").value = "";
    document.getElementById("estado").value = "";
    document.getElementById("precio_compra").value = "";
    document.getElementById("precio_unitario").value = "";
    document.getElementById("stock_minimo").value = "";
    document.getElementById("graba_iva").checked = false;
    document.getElementById("imagen_actual").value = "";
    document.getElementById("textoImagen").innerText = "Puedes subir una imagen nueva si quieres reemplazar la actual.";

    document.getElementById("preview").style.display = "none";
    document.getElementById("preview").src = "";
    document.getElementById("imagen").value = "";

    document.getElementById("modalProducto").classList.add("show");
}

function cerrarModal(){
    document.getElementById("modalProducto").classList.remove("show");
}



/* ===============================
   GUARDAR
=============================== */
async function guardar(){
    const botonGuardar = document.querySelector(".modal-foot .btn-primary-pro");
    const id = document.getElementById("id_producto").value;
    const inputImagen = document.getElementById("imagen");

    const payload = {
        nombre_producto: document.getElementById("nombre_producto").value,
        id_subcategoria: document.getElementById("id_subcategoria").value,
        marca: document.getElementById("marca").value,
        color: document.getElementById("color").value,
        capacidad: document.getElementById("capacidad").value,
        estado: document.getElementById("estado").value,
        precio_compra: document.getElementById("precio_compra").value,
        precio_unitario: document.getElementById("precio_unitario").value,
        stock_minimo: document.getElementById("stock_minimo").value,
        graba_iva: document.getElementById("graba_iva").checked ? 1 : 0
    };

    botonGuardar.disabled = true;
    botonGuardar.style.opacity = ".7";
    botonGuardar.style.cursor = "not-allowed";

    try{
        let res;

        if(id && !inputImagen.files[0]){
            res = await fetch(`${API}/productos/${id}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: "Bearer " + token
                },
                body: JSON.stringify(payload)
            });
        }else{
            const formData = new FormData();

            formData.append("nombre_producto", payload.nombre_producto);
            formData.append("id_subcategoria", payload.id_subcategoria);
            formData.append("marca", payload.marca);
            formData.append("color", payload.color);
            formData.append("capacidad", payload.capacidad);
            formData.append("estado", payload.estado);
            formData.append("precio_compra", payload.precio_compra);
            formData.append("precio_unitario", payload.precio_unitario);
            formData.append("stock_minimo", payload.stock_minimo);
            formData.append("graba_iva", payload.graba_iva);

            if(inputImagen.files[0]){
                formData.append("imagen", inputImagen.files[0]);
            }

            const url = id ? `${API}/productos/${id}` : `${API}/productos`;
            const method = id ? "PUT" : "POST";

            res = await fetch(url,{
                method,
                headers:{ Authorization:"Bearer "+token },
                body: formData
            });
        }

        const raw = await res.text();
        let json = {};

        try{
            json = raw ? JSON.parse(raw) : {};
        }catch(e){
            json = { mensaje: raw || "Respuesta inválida del servidor" };
        }

        if(!res.ok){
            alert(json.mensaje || "No se pudo guardar el producto");
            return;
        }

        cerrarModal();
        refrescarProductos();
    }catch(error){
        console.error("Error guardar producto:", error);
        alert("No se pudo guardar el producto");
    }finally{
        botonGuardar.disabled = false;
        botonGuardar.style.opacity = "";
        botonGuardar.style.cursor = "";
    }
}

function editar(p){

    document.getElementById("tituloModal").innerText = "Editar Producto";

    document.getElementById("id_producto").value = p.id_producto;

    document.getElementById("nombre_producto").value = p.nombre_producto ?? "";
    document.getElementById("id_subcategoria").value = p.id_subcategoria ?? "";
    document.getElementById("marca").value = p.marca ?? "";
    document.getElementById("color").value = p.color ?? "";
    document.getElementById("capacidad").value = p.capacidad ?? "";
    document.getElementById("estado").value = p.estado ?? "";
    document.getElementById("precio_compra").value = p.precio_compra ?? "";
    document.getElementById("precio_unitario").value = p.precio_unitario ?? "";
    document.getElementById("stock_minimo").value = p.stock_minimo ?? "";
    document.getElementById("graba_iva").checked = Number(p.graba_iva || 0) === 1;
    document.getElementById("imagen_actual").value = p.imagen || "";
    document.getElementById("imagen").value = "";

    const preview = document.getElementById("preview");
    const textoImagen = document.getElementById("textoImagen");

    if (p.imagen) {
        preview.src = `${API}/uploads/productos/${p.imagen}`;
        preview.style.display = "block";
        textoImagen.innerText = "Imagen actual del producto. Si seleccionas otra, se reemplazará.";
    } else {
        preview.src = "";
        preview.style.display = "none";
        textoImagen.innerText = "Este producto no tiene imagen. Puedes subir una nueva.";
    }

    document.getElementById("modalProducto").classList.add("show"); // ✅
}

async function eliminarProducto(id, nombreProducto){
    const confirmado = confirm(`¿Deseas eliminar completamente el producto "${nombreProducto}"?`);

    if(!confirmado){
        return;
    }

    try{
        const res = await fetch(`${API}/productos/${id}`, {
            method: "DELETE",
            headers:{ Authorization:"Bearer "+token }
        });

        const raw = await res.text();
        let json = {};

        try{
            json = raw ? JSON.parse(raw) : {};
        }catch(e){
            json = { mensaje: raw || "Respuesta inválida del servidor" };
        }

        if(!res.ok){
            alert(json.mensaje || "No se pudo eliminar el producto");
            return;
        }

        refrescarProductos();
    }catch(error){
        console.error("Error eliminar producto:", error);
        alert("No se pudo eliminar el producto");
    }
}

/* ===============================
   ETIQUETA
=============================== */
async function etiqueta(id){

    const cantidad = prompt("¿Cuántas etiquetas imprimir?", "1");

    if(!cantidad || isNaN(cantidad) || Number(cantidad) <= 0){
        alert("Cantidad inválida");
        return;
    }

    const res = await fetch(`${API}/productos/${id}/etiquetas`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Authorization: "Bearer " + token
        },
        body: JSON.stringify({
            cantidad: Number(cantidad)
        })
    });

    if(!res.ok){
        alert("Error generando etiqueta");
        return;
    }

    const blob = await res.blob();
    const url = URL.createObjectURL(blob);

    window.open(url, "_blank");
}
/* ===============================
   PREVIEW
=============================== */
document.getElementById("imagen").addEventListener("change", e=>{
    const file = e.target.files[0];
    if(!file) return;

    const url = URL.createObjectURL(file);
    document.getElementById("preview").src = url;
    document.getElementById("preview").style.display = "block";
});
</script>

@endsection
