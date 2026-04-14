@extends('admin.layout')

@section('title','Dashboard')

@section('content')

<div class="kpis">

    <div class="kpi">
        <span>Ventas hoy</span>
        <strong id="kpiVentasHoy">$0.00</strong>
    </div>

    <div class="kpi">
        <span>Caja actual</span>
        <strong id="kpiCajaActual">$0.00</strong>
    </div>

    <div class="kpi">
        <span>Productos</span>
        <strong id="kpiProductos">0</strong>
    </div>

    <div class="kpi">
        <span>Stock bajo</span>
        <strong id="kpiStockBajo">0</strong>
    </div>

</div>

<div id="dashboardAdminStatus" style="margin-top:16px;color:#94a3b8;font-size:14px;">
    Cargando indicadores...
</div>

<script>
const API = "{{ rtrim(env('API_URL', 'http://localhost:4004/api'), '/') }}";

function money(value){
    return new Intl.NumberFormat("es-EC", {
        style: "currency",
        currency: "USD"
    }).format(Number(value || 0));
}

function number(value){
    return new Intl.NumberFormat("es-EC").format(Number(value || 0));
}

async function cargarDashboardAdmin(){
    const token = localStorage.getItem("token");
    const status = document.getElementById("dashboardAdminStatus");

    if(!token){
        status.textContent = "No se encontró el token del administrador.";
        return;
    }

    try{
        const res = await fetch(`${API}/admins/dashboard`, {
            headers: {
                Authorization: "Bearer " + token
            }
        });

        const raw = await res.text();
        let json = {};

        try{
            json = raw ? JSON.parse(raw) : {};
        }catch(e){
            json = { mensaje: raw || "Respuesta inválida del servidor" };
        }

        if(!res.ok){
            status.textContent = json.mensaje || "No se pudo cargar el dashboard.";
            return;
        }

        const data = json.data || {};

        document.getElementById("kpiVentasHoy").innerText = money(data.ventas_hoy);
        document.getElementById("kpiCajaActual").innerText = money(data.caja_actual);
        document.getElementById("kpiProductos").innerText = number(data.productos);
        document.getElementById("kpiStockBajo").innerText = number(data.stock_bajo);

        status.textContent = data.caja_abierta
            ? `Actualizado al ${data.fecha}. Caja abierta detectada para este local.`
            : `Actualizado al ${data.fecha}. No hay caja abierta en este momento.`;
    }catch(error){
        console.error("Error cargar dashboard admin:", error);
        status.textContent = "No se pudo conectar con el backend.";
    }
}

document.addEventListener("DOMContentLoaded", cargarDashboardAdmin);
</script>

@endsection
