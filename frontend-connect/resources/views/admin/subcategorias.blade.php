@extends('admin.layout')

@section('title', 'Subcategorías')

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
        max-width: 560px;
        background: linear-gradient(180deg, #0b1220 0%, #09111d 100%);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 22px;
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
        padding: 22px 24px;
    }

    .form-grid{
        display: grid;
        gap: 16px;
    }

    .field{
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .field label{
        color: #cbd5e1;
        font-size: 14px;
        font-weight: 700;
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
</style>
<div class="page-wrap">

    <!-- KPIs -->
    <div class="stats-grid">
        <div class="mini-stat">
            <span>Total</span>
            <strong id="kpiTotal">0</strong>
        </div>
        <div class="mini-stat">
            <span>Activas</span>
            <strong id="kpiActivas">0</strong>
        </div>
        <div class="mini-stat">
            <span>Inactivas</span>
            <strong id="kpiInactivas">0</strong>
        </div>
    </div>

    <!-- TABLA -->
    <div class="panel-card">

        <div class="panel-header">
            <div>
                <h3 class="panel-title">Gestión de Subcategorías</h3>
                <p class="panel-subtitle">Organiza tus productos</p>
            </div>

            <button class="btn-primary-pro" onclick="abrirModal()">
                + Nueva Subcategoría
            </button>
        </div>

        <div class="table-wrap">
            <table class="table-locales">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subcategoría</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody id="tablaSubcategorias">
                    <tr>
                        <td colspan="5" class="table-empty">Cargando...</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

</div>

<!-- MODAL -->
<div class="modal-pro" id="modalSubcategoria">
    <div class="modal-card">

        <div class="modal-head">
            <h4 id="tituloModal">Nueva Subcategoría</h4>
            <button class="btn-close-pro" onclick="cerrarModal()">×</button>
        </div>

        <div class="modal-body-pro">

            <input type="hidden" id="id_subcategoria">

            <div class="form-grid">

                <!-- SELECT CATEGORÍAS -->
                <div class="field">
                    <label>Categoría</label>
                    <div class="select-pro-wrapper">
    <select id="id_categoria" class="select-pro"></select>
    <span class="select-icon">▼</span>
</div>
                </div>

                <div class="field">
                    <label>Nombre</label>
                    <input type="text" id="nombre_subcategoria">
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

document.addEventListener("DOMContentLoaded", () => {
    if(!token) return location.href="/login";

    cargarCategorias();
    cargarSubcategorias();
});

/* ======================================
   🔥 CARGAR CATEGORÍAS (SELECT)
====================================== */
async function cargarCategorias(){

    const res = await fetch(`${API}/categorias`, {
        headers:{ Authorization: "Bearer " + token }
    });

    const json = await res.json();

    const select = document.getElementById("id_categoria");

    select.innerHTML = `<option value="">Seleccione</option>`;

    json.data.forEach(c=>{
        select.innerHTML += `
            <option value="${c.id_categoria}">
                ${c.nombre_categoria}
            </option>
        `;
    });
}

/* ======================================
   LISTAR
====================================== */
async function cargarSubcategorias(){

    const tbody = document.getElementById("tablaSubcategorias");

    const res = await fetch(`${API}/subcategorias`, {
        headers:{ Authorization: "Bearer " + token }
    });

    const json = await res.json();
    const data = json.data || [];

    document.getElementById("kpiTotal").innerText = data.length;
    document.getElementById("kpiActivas").innerText = data.filter(s=>s.activo==1).length;
    document.getElementById("kpiInactivas").innerText = data.filter(s=>s.activo==0).length;

    tbody.innerHTML = "";

    if(data.length === 0){
        tbody.innerHTML = `<tr><td colspan="5" class="table-empty">Sin registros</td></tr>`;
        return;
    }

    data.forEach((s,i)=>{

        tbody.innerHTML += `
            <tr>
                <td>${i+1}</td>
                <td class="local-name">${s.nombre_subcategoria}</td>
                <td>${s.nombre_categoria}</td>
                <td>
                    <span class="badge-state ${s.activo==1 ? 'badge-active':'badge-inactive'}">
                        ${s.activo==1 ? 'Activo':'Inactivo'}
                    </span>
                </td>
                <td>
                    <div class="actions">
                        <button class="btn-action" onclick='editar(${JSON.stringify(s)})'>Editar</button>
                        <button class="btn-action btn-danger" onclick="eliminar(${s.id_subcategoria})">Desactivar</button>
                    </div>
                </td>
            </tr>
        `;
    });

}

/* ======================================
   MODAL
====================================== */
function abrirModal(){
    document.getElementById("id_subcategoria").value="";
    document.getElementById("nombre_subcategoria").value="";
    document.getElementById("id_categoria").value="";

    document.getElementById("modalSubcategoria").classList.add("show");
}

function cerrarModal(){
    document.getElementById("modalSubcategoria").classList.remove("show");
}

function editar(s){

    document.getElementById("id_subcategoria").value = s.id_subcategoria;
    document.getElementById("nombre_subcategoria").value = s.nombre_subcategoria;
    document.getElementById("id_categoria").value = s.id_categoria;

    document.getElementById("modalSubcategoria").classList.add("show");
}

/* ======================================
   GUARDAR
====================================== */
async function guardar(){

    const id = document.getElementById("id_subcategoria").value;

    const data = {
        id_categoria: document.getElementById("id_categoria").value,
        nombre_subcategoria: document.getElementById("nombre_subcategoria").value
    };

    let url = `${API}/subcategorias`;
    let method = "POST";

    if(id){
        url = `${API}/subcategorias/${id}`;
        method = "PUT";
    }

    const res = await fetch(url,{
        method,
        headers:{
            "Content-Type":"application/json",
            Authorization:"Bearer "+token
        },
        body: JSON.stringify(data)
    });

    const json = await res.json();

    if(!json.ok){
        alert("Error");
        return;
    }

    cerrarModal();
    cargarSubcategorias();
}

/* ======================================
   DESACTIVAR
====================================== */
async function eliminar(id){

    if(!confirm("¿Desactivar?")) return;

    await fetch(`${API}/subcategorias/${id}`,{
        method:"DELETE",
        headers:{ Authorization:"Bearer "+token }
    });

    cargarSubcategorias();
}
</script>

@endsection