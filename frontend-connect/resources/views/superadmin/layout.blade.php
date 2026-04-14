<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin · Connect</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">

<style>
body{
    margin:0;
    font-family:'Inter',sans-serif;
    background:#0b0f19;
    color:#fff;
}

/* LAYOUT */
.layout{
    display:flex;
}

/* SIDEBAR */
.sidebar{
    width:240px;
    background:#06080f;
    min-height:100vh;
    padding:20px;
    border-right:1px solid rgba(255,255,255,0.05);
}

.logo{
    text-align:center;
    margin-bottom:30px;
}

.logo img{
    width:140px;
    background:#fff;
    padding:8px;
    border-radius:10px;
}

.menu a{
    display:block;
    color:#aaa;
    padding:12px;
    border-radius:10px;
    margin-bottom:8px;
    text-decoration:none;
    transition:0.2s;
}

.menu a:hover,
.menu a.active{
    background:#111827;
    color:#fff;
}

/* CONTENT */
.content{
    flex:1;
    padding:25px;
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
    border-radius:8px;
    color:#fff;
    cursor:pointer;
}

/* CARDS */
.cards{
    display:grid;
    grid-template-columns: repeat(4,1fr);
    gap:20px;
}

.card{
    background:#111827;
    padding:20px;
    border-radius:14px;
}

.card h4{
    color:#aaa;
    font-size:14px;
}

.card p{
    font-size:28px;
    font-weight:bold;
}

/* GRID */
.grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:20px;
    margin-top:20px;
}

.box{
    background:#111827;
    padding:20px;
    border-radius:14px;
}
</style>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <div class="sidebar">

        <div class="logo">
            <img src="{{ asset('images/connect.png') }}">
        </div>

        <div class="menu">
            <a href="/superadmin" class="{{ request()->is('superadmin') ? 'active' : '' }}">📊 Dashboard</a>
            <a href="/superadmin/locales" class="{{ request()->is('superadmin/locales') ? 'active' : '' }}">🏬 Locales</a>
            <a href="/superadmin/admins" class="{{ request()->is('superadmin/admins') ? 'active' : '' }}">👨‍💼 Administradores</a>
            <a href="/superadmin/reportes" class="{{ request()->is('superadmin/reportes') ? 'active' : '' }}">📈 Reportes</a>
            <a href="/superadmin/rides" class="{{ request()->is('superadmin/rides') ? 'active' : '' }}">🧾 RIDE SRI</a>
        </div>

    </div>

    <!-- CONTENIDO -->
    <div class="content">

        <!-- TOP -->
        <div class="topbar">
            <h2>@yield('title')</h2>

            <button class="btn-logout" onclick="logout()">
                Cerrar sesión
            </button>
        </div>

        @yield('content')

    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", ()=>{
    const token = localStorage.getItem("token");
    const user = JSON.parse(localStorage.getItem("usuario") || "{}");

    if(!token || !user.roles || !user.roles.includes("SUPER_ADMIN")){
        window.location.href = "/login";
    }
});

function logout(){
    localStorage.removeItem("token");
    localStorage.removeItem("usuario");
    window.location.href = "/login";
}
</script>

</body>
</html>
