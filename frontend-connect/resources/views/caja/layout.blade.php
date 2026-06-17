<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title') · Caja</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">

<style>
:root{
    --sidebar-width:260px;
    --shell-bg:#050b18;
    --sidebar-bg:#020617;
    --surface:#0f172a;
    --surface-border:rgba(255,255,255,0.07);
    --text-main:#ffffff;
    --text-soft:#cbd5e1;
    --text-muted:#94a3b8;
}

*,
*::before,
*::after{
    box-sizing:border-box;
}

html,
body{
    margin:0;
    max-width:100%;
    overflow-x:hidden;
}

body{
    font-family:'Inter',sans-serif;
    background:
        radial-gradient(circle at top, rgba(37,99,235,.22), transparent 35%),
        linear-gradient(180deg, #050b18 0%, #020617 100%);
    color:var(--text-main);
}

body.sidebar-open{
    overflow:hidden;
}

img{
    display:block;
    max-width:100%;
    height:auto;
}

button,
input,
select,
textarea{
    font:inherit;
}

a{
    color:inherit;
}

.app-shell{
    min-height:100vh;
}

.sidebar-overlay{
    position:fixed;
    inset:0;
    background:rgba(2,6,23,.72);
    opacity:0;
    pointer-events:none;
    transition:opacity .25s ease;
    z-index:35;
    backdrop-filter:blur(2px);
}

body.sidebar-open .sidebar-overlay{
    opacity:1;
    pointer-events:auto;
}

.sidebar{
    position:fixed;
    inset:0 auto 0 0;
    width:min(86vw, 300px);
    padding:18px 16px 24px;
    background:rgba(2,6,23,.98);
    border-right:1px solid rgba(255,255,255,.06);
    transform:translateX(-100%);
    transition:transform .25s ease;
    z-index:40;
    display:flex;
    flex-direction:column;
    overflow-y:auto;
    box-shadow:0 24px 60px rgba(0,0,0,.45);
}

body.sidebar-open .sidebar{
    transform:translateX(0);
}

.sidebar-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:22px;
}

.logo{
    background:#fff;
    border-radius:14px;
    padding:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:64px;
    flex:1;
}

.logo img{
    width:136px;
}

.sidebar-close{
    width:42px;
    height:42px;
    border:none;
    border-radius:12px;
    background:rgba(255,255,255,.06);
    color:#fff;
    font-size:24px;
    cursor:pointer;
    flex-shrink:0;
}

.menu{
    flex:1;
}

.menu a{
    display:flex;
    align-items:center;
    gap:10px;
    padding:12px 14px;
    border-radius:12px;
    color:var(--text-soft);
    text-decoration:none;
    margin-bottom:8px;
    transition:background .2s ease, color .2s ease, transform .2s ease;
}

.menu a:hover{
    background:#0f172a;
    color:#fff;
    transform:translateX(2px);
}

.menu .active{
    background:#0f172a;
    color:#fff;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.04);
}

.sidebar-footer{
    margin-top:16px;
    padding-top:16px;
    border-top:1px solid rgba(255,255,255,.08);
}

.btn-logout{
    background:#111827;
    border:1px solid rgba(255,255,255,.08);
    padding:11px 16px;
    border-radius:12px;
    color:#fff;
    cursor:pointer;
    font-weight:700;
    width:100%;
}

.content{
    width:100%;
    min-height:100vh;
    min-width:0;
    padding:clamp(16px, 3vw, 30px);
}

.content > *{
    min-width:0;
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    margin-bottom:24px;
}

.topbar-main{
    display:flex;
    align-items:center;
    gap:14px;
    min-width:0;
}

.menu-toggle{
    width:46px;
    height:46px;
    border:none;
    border-radius:14px;
    background:rgba(255,255,255,.06);
    color:#fff;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
}

.menu-toggle svg,
.sidebar-close svg{
    width:22px;
    height:22px;
}

.topbar-label{
    display:block;
    color:var(--text-muted);
    font-size:11px;
    font-weight:700;
    letter-spacing:.08em;
    text-transform:uppercase;
    margin-bottom:4px;
}

.topbar h1{
    margin:0;
    font-size:clamp(24px, 3vw, 32px);
    line-height:1.1;
}

.topbar-actions{
    display:flex;
    align-items:center;
    gap:12px;
    justify-content:flex-end;
    flex-wrap:wrap;
}

.page-wrap,
.caja-page,
.pos-page,
.hero-card,
.panel-card,
.kpi-card,
.panel-pos,
.summary-card,
.table-wrap,
.table-wrap-pro,
.stats-grid,
.panel-grid,
.field-grid,
.pos-grid,
.grid-2,
.payment-grid,
.payment-layout,
.preview-grid-2,
.preview-body,
.actions-grid,
.actions-stack,
.search-row,
.btn-row,
.preview-foot-actions{
    min-width:0;
}

.table-wrap,
.table-wrap-pro{
    max-width:100%;
    overflow-x:auto;
    overflow-y:hidden;
    -webkit-overflow-scrolling:touch;
    scrollbar-gutter:stable both-edges;
    scrollbar-width:thin;
    scrollbar-color:rgba(59,130,246,.58) rgba(15,23,42,.72);
}

.table-wrap::-webkit-scrollbar,
.table-wrap-pro::-webkit-scrollbar{
    height:11px;
}

.table-wrap::-webkit-scrollbar-track,
.table-wrap-pro::-webkit-scrollbar-track{
    background:rgba(15,23,42,.72);
}

.table-wrap::-webkit-scrollbar-thumb,
.table-wrap-pro::-webkit-scrollbar-thumb{
    background:rgba(59,130,246,.58);
    border-radius:999px;
    border:2px solid rgba(15,23,42,.72);
}

.table-wrap table,
.table-wrap-pro table{
    width:max-content;
    min-width:100%;
}

.content .panel-subtitle,
.content .detail-subtitle,
.content .hero-copy,
.content .table-empty,
.content .muted,
.content .mono{
    overflow-wrap:anywhere;
    word-break:break-word;
}

@media (min-width: 992px){
    .sidebar-overlay,
    .sidebar-close,
    .menu-toggle{
        display:none;
    }

    .sidebar{
        width:var(--sidebar-width);
        transform:none;
        box-shadow:none;
        padding:20px;
    }

    .content{
        width:calc(100% - var(--sidebar-width));
        margin-left:var(--sidebar-width);
        padding:clamp(18px, 3vw, 32px);
    }
}

@media (max-width: 1180px){
    .content .pos-grid,
    .content .panel-grid,
    .content .payment-layout,
    .content .preview-body{
        grid-template-columns:1fr !important;
    }

    .content .stats-grid,
    .content .field-grid,
    .content .grid-2,
    .content .payment-grid,
    .content .preview-grid-2{
        grid-template-columns:repeat(2, minmax(0, 1fr)) !important;
    }
}

@media (max-width: 991.98px){
    .topbar{
        flex-direction:column;
        align-items:stretch;
    }

    .topbar-actions{
        width:100%;
    }
}

@media (max-width: 767.98px){
    .content .stats-grid,
    .content .field-grid,
    .content .grid-2,
    .content .payment-grid,
    .content .preview-grid-2,
    .content .actions-grid{
        grid-template-columns:1fr !important;
    }

    .content .search-row,
    .content .preview-head,
    .content .preview-foot,
    .content .preview-foot-actions{
        display:flex;
        flex-direction:column;
        align-items:stretch;
        gap:12px;
    }

    .content .search-row > *,
    .content .preview-head > *,
    .content .preview-foot > *,
    .content .preview-foot-actions > *{
        width:100%;
    }

    .table-wrap table,
    .table-wrap-pro table{
        min-width:720px;
    }

    .content{
        padding:16px 12px 24px;
    }

    .sidebar{
        width:min(88vw, 300px);
        padding:16px 14px 20px;
    }

    .logo{
        min-height:58px;
    }

    .logo img{
        width:120px;
    }

    .topbar h1{
        font-size:24px;
    }
}
</style>
</head>

<body>

<div class="app-shell">
    <div class="sidebar-overlay" onclick="closeSidebar()"></div>

    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo">
                <img src="{{ asset('images/connect.png') }}" alt="Connect">
            </div>

            <button type="button" class="sidebar-close" onclick="closeSidebar()" aria-label="Cerrar menu">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <nav class="menu">
            <a href="/caja" class="{{ request()->is('caja') && !request()->is('caja/pos') ? 'active' : '' }}">💰 Caja</a>
            <a href="/caja/pos" class="{{ request()->is('caja/pos') ? 'active' : '' }}">🛒 Punto de venta</a>
        </nav>

        <div class="sidebar-footer">
            <button type="button" class="btn-logout" onclick="logout()">Cerrar sesion</button>
        </div>
    </aside>

    <main class="content">
        <div class="topbar">
            <div class="topbar-main">
                <button type="button" class="menu-toggle" onclick="toggleSidebar()" aria-label="Abrir menu">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7H20M4 12H20M4 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>

                <div>
                    <span class="topbar-label">Rol Caja</span>
                    <h1>@yield('title')</h1>
                </div>
            </div>

            <div class="topbar-actions">
                <button type="button" class="btn-logout" onclick="logout()">Cerrar sesion</button>
            </div>
        </div>

        @yield('content')
    </main>
</div>

<script>
function setSidebarState(isOpen){
    document.body.classList.toggle("sidebar-open", Boolean(isOpen));
}

function toggleSidebar(){
    setSidebarState(!document.body.classList.contains("sidebar-open"));
}

function closeSidebar(){
    setSidebarState(false);
}

document.addEventListener("DOMContentLoaded", ()=>{
    const token = localStorage.getItem("token");
    const user = JSON.parse(localStorage.getItem("usuario") || "{}");
    const roles = Array.isArray(user.roles) ? user.roles : [];
    const isCaja = roles.includes("CAJA");

    if(!token || roles.length === 0){
        window.location.href = "/login";
        return;
    }

    if(!isCaja){
        if(roles.includes("ADMIN")){
            window.location.href = "/admin";
            return;
        }

        if(roles.includes("SUPER_ADMIN")){
            window.location.href = "/superadmin";
            return;
        }

        window.location.href = "/login";
        return;
    }

    document.querySelectorAll(".menu a").forEach((link)=>{
        link.addEventListener("click", ()=>{
            if(window.innerWidth <= 991){
                closeSidebar();
            }
        });
    });
});

window.addEventListener("resize", ()=>{
    if(window.innerWidth > 991){
        closeSidebar();
    }
});

window.addEventListener("keydown", (event)=>{
    if(event.key === "Escape"){
        closeSidebar();
    }
});

function logout(){
    localStorage.clear();
    window.location.href = "/login";
}
</script>

</body>
</html>
