@extends('superadmin.layout')

@section('title', 'Dashboard')

@section('content')

<!-- KPIs -->
<div class="cards">

    <div class="card">
        <h4>Total Locales</h4>
        <p id="kpi_locales">0</p>
    </div>

    <div class="card">
        <h4>Administradores</h4>
        <p id="kpi_admins">0</p>
    </div>

    <div class="card">
        <h4>Ventas Globales</h4>
        <p id="kpi_ventas">$0.00</p>
    </div>

    <div class="card">
        <h4>Locales Activos</h4>
        <p id="kpi_activos">0</p>
    </div>

</div>

<!-- CONTENIDO -->
<div class="grid">

    <div class="box">
        <h4>📊 Resumen general</h4>
        <p style="color:#aaa;">
            Aquí podrás visualizar el estado general del sistema:
            locales registrados, usuarios administradores y actividad.
        </p>
    </div>

    <div class="box">
        <h4>🚀 Acciones rápidas</h4>
        <ul style="color:#aaa;">
            <li>Crear nuevo local</li>
            <li>Crear administrador</li>
            <li>Ver reportes por local</li>
        </ul>
    </div>

</div>

<script>
const API = "{{ env('API_URL') }}";
const token = localStorage.getItem("token");

/* 🔒 protección */
if(!token){
    window.location.href = "/login";
}

/* 🔥 cargar KPIs */
async function cargarKPIs(){

    try{
        const res = await fetch(`${API}/locales`, {
            headers:{
                Authorization: "Bearer " + token
            }
        });

        const data = await res.json();

        if(data.ok){
            const locales = data.data;

            document.getElementById("kpi_locales").innerText = locales.length;

            const activos = locales.filter(l => l.activo == 1).length;
            document.getElementById("kpi_activos").innerText = activos;
        }

        // fake por ahora
        document.getElementById("kpi_admins").innerText = 3;
        document.getElementById("kpi_ventas").innerText = "$0.00";

    }catch(e){
        console.error(e);
    }

}

cargarKPIs();
</script>

@endsection