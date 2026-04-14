<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title') · Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">

<style>
body{
    margin:0;
    font-family:'Inter',sans-serif;
    background:#050b18;
    color:#fff;
}

/* SIDEBAR */
.sidebar{
    width:240px;
    height:100vh;
    background:#020617;
    position:fixed;
    padding:20px;
    display:flex;
    flex-direction:column;
}

.logo{
    background:#fff;
    border-radius:12px;
    padding:10px;
    text-align:center;
    margin-bottom:25px;
}

.logo img{ width:140px; }

/* MENU */
.menu{
    flex:1;
}

.menu a{
    display:block;
    padding:12px 14px;
    border-radius:10px;
    color:#cbd5e1;
    text-decoration:none;
    margin-bottom:6px;
    transition:.2s;
}

.menu a:hover{
    background:#0f172a;
}

.menu .active{
    background:#0f172a;
    color:#fff;
}

/* SUBMENU */
.submenu{
    margin-left:10px;
    display:none;
}

.submenu a{
    font-size:14px;
    padding-left:20px;
}

/* CONTENT */
.content{
    margin-left:240px;
    padding:40px 50px; /* 🔥 MÁS ESPACIO */
}

/* TOPBAR */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.btn-logout{
    background:#111;
    border:none;
    padding:10px 15px;
    border-radius:10px;
    color:#fff;
    cursor:pointer;
}

/* KPI */
.kpis{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:15px;
}

.kpi{
    background:#0f172a;
    padding:20px;
    border-radius:14px;
}

.kpi span{
    color:#94a3b8;
    font-size:14px;
}

.kpi strong{
    font-size:28px;
    display:block;
    margin-top:8px;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="logo">
        <img src="{{ asset('images/connect.png') }}">
    </div>

    <div class="menu">

        <a href="/admin" class="{{ request()->is('admin') ? 'active' : '' }}">📊 Dashboard</a>

        <a href="/admin/usarios" class="{{ request()->is('admin/usarios') ? 'active' : '' }}">👤 Usuarios Caja</a>
        <a href="/admin/categorias" class="{{ request()->is('admin/categorias') ? 'active' : '' }}">📂 Categorías</a>
        <a href="/admin/subcategorias" class="{{ request()->is('admin/subcategorias') ? 'active' : '' }}">🧩 Subcategorías</a>
        <a href="/admin/productos" class="{{ request()->is('admin/productos') ? 'active' : '' }}">💎 Productos</a>
        <a href="/admin/inventario" class="{{ request()->is('admin/inventario') ? 'active' : '' }}">📦 Inventario</a>
        <a href="/admin/caja" class="{{ request()->is('admin/caja') ? 'active' : '' }}">🧾 Caja</a>

       <a href="#" onclick="toggleMenu('contableMenu'); return false;"
   class="{{ request()->is('admin/contabilidad*') || request()->is('admin/gastos*') ? 'active' : '' }}">
   📊 Contabilidad ▾
</a>

<div id="contableMenu"
     class="submenu {{ request()->is('admin/contabilidad*') || request()->is('admin/gastos*') ? 'open' : '' }}">

    <a href="/admin/contabilidad"
       class="{{ request()->is('admin/contabilidad') ? 'active' : '' }}">
       Resumen
    </a>

    <a href="/admin/gastos"
       class="{{ request()->is('admin/gastos') ? 'active' : '' }}">
       Gastos
    </a>

</div>

    </div>

</div>

<!-- CONTENT -->
<div class="content">

    <div class="topbar">
        <h2>@yield('title')</h2>
        <button class="btn-logout" onclick="logout()">Cerrar sesión</button>
    </div>

    @yield('content')

</div>

<script>
/* =====================================
   🔒 VALIDACIÓN ADMIN
===================================== */
document.addEventListener("DOMContentLoaded", ()=>{

    const token = localStorage.getItem("token");
    const user = JSON.parse(localStorage.getItem("usuario") || "{}");

    if(!token || !user.roles || !user.roles.includes("ADMIN")){
        window.location.href="/login";
    }

});

/* =====================================
   SUBMENÚ
===================================== */
function toggleMenu(id){
    const el = document.getElementById(id);
    el.style.display = el.style.display === "block" ? "none" : "block";
}

/* =====================================
   LOGOUT
===================================== */
function logout(){
    localStorage.clear();
    window.location.href="/login";
}
</script>

</body>
</html>
