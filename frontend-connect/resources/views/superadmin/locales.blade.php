@extends('superadmin.layout')

@section('title', 'Locales')

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

    .field input,
    .field select{
        width: 100%;
        border: 1px solid rgba(255,255,255,0.10);
        background: #0f172a;
        color: #fff;
        border-radius: 12px;
        padding: 13px 14px;
        outline: none;
        font-size: 14px;
    }

    .field input:focus,
    .field select:focus{
        border-color: rgba(244,200,66,.55);
        box-shadow: 0 0 0 3px rgba(244,200,66,.10);
    }

    .field small{
        color: #64748b;
        font-size: 12px;
        line-height: 1.45;
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

    @media (max-width: 900px){
        .stats-grid{
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-wrap">

    <div class="stats-grid">
        <div class="mini-stat">
            <span>Total de locales</span>
            <strong id="kpiTotal">0</strong>
        </div>
        <div class="mini-stat">
            <span>Locales activos</span>
            <strong id="kpiActivos">0</strong>
        </div>
        <div class="mini-stat">
            <span>Locales inactivos</span>
            <strong id="kpiInactivos">0</strong>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Gestión de Locales</h3>
                <p class="panel-subtitle">Crea, edita y administra los locales del sistema desde este módulo.</p>
            </div>

            <button class="btn-primary-pro" type="button" onclick="abrirModal()">
                + Nuevo Local
            </button>
        </div>

        <div class="table-wrap">
            <table class="table-locales">
                <thead>
                    <tr>
                        <th style="width:70px;">#</th>
                        <th>Nombre</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th style="width:230px;">Facturación SRI</th>
                        <th style="width:120px;">Estado</th>
                        <th style="width:180px;">Fecha</th>
                        <th style="width:190px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaLocales">
                    <tr>
                        <td colspan="8" class="table-empty">Cargando locales...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div class="modal-pro" id="modalLocal">
    <div class="modal-card">
        <div class="modal-head">
            <h4 id="tituloModal">Nuevo Local</h4>
            <button type="button" class="btn-close-pro" onclick="cerrarModal()">×</button>
        </div>

        <div class="modal-body-pro">
            <input type="hidden" id="id_local">

            <div class="form-grid">
                <div class="field">
                    <label for="nombre_local">Nombre del local</label>
                    <input type="text" id="nombre_local" placeholder="Ej: Sucursal Norte">
                </div>

                <div class="field">
                    <label for="direccion">Dirección</label>
                    <input type="text" id="direccion" placeholder="Ej: Av. Unidad Nacional y Colón">
                </div>

                <div class="field">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" placeholder="Ej: 0999999999">
                </div>

                <div class="field">
                    <label for="id_local_sri_maestro">Facturación SRI</label>
                    <select id="id_local_sri_maestro"></select>
                    <small>
                        Si eliges un local maestro, este local seguirá usando su propio POS, inventario y usuarios,
                        pero facturará con la misma configuración SRI y el mismo secuencial fiscal del local maestro.
                    </small>
                </div>
            </div>

            <div id="mensajeModal" class="alert-inline"></div>
        </div>

        <div class="modal-foot">
            <button type="button" class="btn-secondary-pro" onclick="cerrarModal()">Cancelar</button>
            <button type="button" class="btn-primary-pro" onclick="guardar()">Guardar</button>
        </div>
    </div>
</div>

<script>
const API = "{{ env('API_URL') }}";
const token = localStorage.getItem("token");
let localesCache = [];

document.addEventListener("DOMContentLoaded", () => {
    if (!token) {
        window.location.href = "/login";
        return;
    }

    cargarLocales();
});

function mostrarMensajeModal(texto, tipo = "error") {
    const box = document.getElementById("mensajeModal");
    box.style.display = "block";
    box.className = "alert-inline " + (tipo === "success" ? "alert-success" : "alert-error");
    box.textContent = texto;
}

function limpiarMensajeModal() {
    const box = document.getElementById("mensajeModal");
    box.style.display = "none";
    box.textContent = "";
    box.className = "alert-inline";
}

function abrirModal() {
    document.getElementById("tituloModal").innerText = "Nuevo Local";
    document.getElementById("id_local").value = "";
    document.getElementById("nombre_local").value = "";
    document.getElementById("direccion").value = "";
    document.getElementById("telefono").value = "";
    cargarOpcionesSriMaestro();
    document.getElementById("id_local_sri_maestro").value = "";
    limpiarMensajeModal();

    document.getElementById("modalLocal").classList.add("show");
}

function cerrarModal() {
    document.getElementById("modalLocal").classList.remove("show");
}

function editar(local) {
    document.getElementById("tituloModal").innerText = "Editar Local";
    document.getElementById("id_local").value = local.id_local;
    document.getElementById("nombre_local").value = local.nombre_local || "";
    document.getElementById("direccion").value = local.direccion || "";
    document.getElementById("telefono").value = local.telefono || "";
    cargarOpcionesSriMaestro(local.id_local);
    document.getElementById("id_local_sri_maestro").value = local.id_local_sri_maestro || "";
    limpiarMensajeModal();

    document.getElementById("modalLocal").classList.add("show");
}

function formatearFecha(valor) {
    if (!valor) return "-";

    const fecha = new Date(valor);
    if (isNaN(fecha.getTime())) return valor;

    return fecha.toLocaleString("es-EC", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit"
    });
}

function pintarKPIs(locales = []) {
    const total = locales.length;
    const activos = locales.filter(l => Number(l.activo) === 1).length;
    const inactivos = total - activos;

    document.getElementById("kpiTotal").textContent = total;
    document.getElementById("kpiActivos").textContent = activos;
    document.getElementById("kpiInactivos").textContent = inactivos;
}

function cargarOpcionesSriMaestro(idLocalActual = null) {
    const select = document.getElementById("id_local_sri_maestro");

    select.innerHTML = `<option value="">Configuración propia</option>`;

    localesCache
        .filter(local => Number(local.id_local) !== Number(idLocalActual || 0) && Number(local.activo) === 1)
        .forEach((local) => {
            const option = document.createElement("option");
            option.value = local.id_local;
            option.textContent = `${local.nombre_local} (#${local.id_local})`;
            select.appendChild(option);
        });
}

function renderSriMode(local) {
    if (local.id_local_sri_maestro) {
        return `
            <div class="local-name">Compartida</div>
            <div style="color:#94a3b8;font-size:12px;margin-top:4px;">
                Usa la configuración del local ${local.nombre_local_sri_maestro || "#" + local.id_local_sri_maestro}
            </div>
        `;
    }

    return `
        <div class="local-name">Propia</div>
        <div style="color:#94a3b8;font-size:12px;margin-top:4px;">
            Mantiene su propia configuración SRI
        </div>
    `;
}

async function cargarLocales() {
    const tbody = document.getElementById("tablaLocales");
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="table-empty">Cargando locales...</td>
        </tr>
    `;

    try {
        const res = await fetch(`${API}/locales`, {
            method: "GET",
            headers: {
                "Authorization": "Bearer " + token
            }
        });

        const json = await res.json();

        if (!json.ok) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="table-empty">No se pudo cargar la información.</td>
                </tr>
            `;
            return;
        }

        const locales = json.data || [];
        localesCache = locales;
        pintarKPIs(locales);

        if (locales.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="table-empty">No hay locales registrados todavía.</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = "";

        locales.forEach((l, i) => {
            const localString = encodeURIComponent(JSON.stringify(l));

            tbody.innerHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td class="local-name">${l.nombre_local || "-"}</td>
                    <td>${l.direccion || "-"}</td>
                    <td>${l.telefono || "-"}</td>
                    <td>${renderSriMode(l)}</td>
                    <td>
                        <span class="badge-state ${Number(l.activo) === 1 ? "badge-active" : "badge-inactive"}">
                            ${Number(l.activo) === 1 ? "Activo" : "Inactivo"}
                        </span>
                    </td>
                    <td>${formatearFecha(l.fecha_creacion || l.creado_en)}</td>
                    <td>
                        <div class="actions">
                            <button class="btn-action" onclick="editarDesdeString('${localString}')">Editar</button>
                            <button class="btn-action btn-danger" onclick="eliminar(${l.id_local})">Desactivar</button>
                        </div>
                    </td>
                </tr>
            `;
        });

    } catch (error) {
        console.error(error);
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="table-empty">Error de conexión con la API.</td>
            </tr>
        `;
    }
}

function editarDesdeString(localString) {
    const local = JSON.parse(decodeURIComponent(localString));
    editar(local);
}

async function guardar() {
    const id = document.getElementById("id_local").value.trim();
    const nombre_local = document.getElementById("nombre_local").value.trim();
    const direccion = document.getElementById("direccion").value.trim();
    const telefono = document.getElementById("telefono").value.trim();
    const id_local_sri_maestro = document.getElementById("id_local_sri_maestro").value;

    if (!nombre_local) {
        mostrarMensajeModal("El nombre del local es obligatorio.");
        return;
    }

    const payload = {
        nombre_local,
        direccion,
        telefono,
        id_local_sri_maestro: id_local_sri_maestro || null
    };

    const url = id ? `${API}/locales/${id}` : `${API}/locales`;
    const method = id ? "PUT" : "POST";

    try {
        const res = await fetch(url, {
            method,
            headers: {
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token
            },
            body: JSON.stringify(payload)
        });

        const json = await res.json();

        if (!json.ok) {
            mostrarMensajeModal(json.mensaje || "No se pudo guardar el local.");
            return;
        }

        cerrarModal();
        cargarLocales();

    } catch (error) {
        console.error(error);
        mostrarMensajeModal("Error de conexión al guardar.");
    }
}

async function eliminar(id) {
    const confirmado = confirm("¿Deseas desactivar este local?");
    if (!confirmado) return;

    try {
        const res = await fetch(`${API}/locales/${id}`, {
            method: "DELETE",
            headers: {
                "Authorization": "Bearer " + token
            }
        });

        const json = await res.json();

        if (!json.ok) {
            alert(json.mensaje || "No se pudo desactivar el local.");
            return;
        }

        cargarLocales();

    } catch (error) {
        console.error(error);
        alert("Error de conexión al desactivar.");
    }
}

document.getElementById("modalLocal").addEventListener("click", function(e){
    if (e.target.id === "modalLocal") {
        cerrarModal();
    }
});
</script>

@endsection
