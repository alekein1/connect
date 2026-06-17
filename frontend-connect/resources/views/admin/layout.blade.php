<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title') · Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">

<style>
:root{
    --sidebar-width:260px;
    --shell-bg:#050b18;
    --sidebar-bg:#020617;
    --surface:#0f172a;
    --surface-soft:#111c31;
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
        radial-gradient(circle at top, rgba(30,41,59,.45), transparent 38%),
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

.sidebar-footer{
    margin-top:16px;
    padding-top:16px;
    border-top:1px solid rgba(255,255,255,.08);
}

.sidebar-footer .btn-logout{
    width:100%;
    display:inline-flex;
    align-items:center;
    justify-content:center;
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

.submenu{
    margin:6px 0 12px 12px;
    padding-left:10px;
    border-left:1px solid rgba(148,163,184,.18);
    display:none;
}

.submenu.open{
    display:block;
}

.submenu a{
    font-size:14px;
    padding:10px 12px;
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

.topbar-copy{
    min-width:0;
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
    flex-wrap:wrap;
    justify-content:flex-end;
}

.btn-logout{
    background:#111827;
    border:1px solid rgba(255,255,255,.08);
    padding:11px 16px;
    border-radius:12px;
    color:#fff;
    cursor:pointer;
    font-weight:700;
}

.kpis,
.cards{
    display:grid;
    grid-template-columns:repeat(4, minmax(0, 1fr));
    gap:16px;
}

.grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:20px;
    margin-top:20px;
}

.kpi,
.card,
.box{
    background:#0f172a;
    border:1px solid rgba(255,255,255,.05);
    padding:20px;
    border-radius:18px;
    min-width:0;
}

.kpi span,
.card h4{
    color:var(--text-muted);
    font-size:14px;
    margin:0;
}

.kpi strong,
.card p{
    font-size:clamp(24px, 3vw, 30px);
    display:block;
    margin-top:8px;
    margin-bottom:0;
}

.page-wrap,
.panel-card,
.hero-card,
.detail-card,
.table-wrap,
.table-wrap-pro,
.table-responsive,
.modal-card,
.stats-grid,
.filter-grid,
.actions,
.actions-inline,
.header-actions,
.toolbar,
.two-col,
.form-grid,
.detail-grid,
.mini-grid,
.chart-row,
.panel-grid,
.field-grid,
.grid-2,
.payment-grid,
.payment-layout,
.preview-grid-2,
.preview-body,
.search-row,
.btn-row,
.preview-foot-actions,
.table-scroll-actions{
    min-width:0;
}

.table-wrap,
.table-wrap-pro,
.table-responsive{
    max-width:100%;
    overflow-x:auto;
    overflow-y:hidden;
    -webkit-overflow-scrolling:touch;
    scrollbar-gutter:stable both-edges;
    scrollbar-width:thin;
    scrollbar-color:rgba(59,130,246,.58) rgba(15,23,42,.72);
}

.table-wrap::-webkit-scrollbar,
.table-wrap-pro::-webkit-scrollbar,
.table-responsive::-webkit-scrollbar{
    height:11px;
}

.table-wrap::-webkit-scrollbar-track,
.table-wrap-pro::-webkit-scrollbar-track,
.table-responsive::-webkit-scrollbar-track{
    background:rgba(15,23,42,.72);
}

.table-wrap::-webkit-scrollbar-thumb,
.table-wrap-pro::-webkit-scrollbar-thumb,
.table-responsive::-webkit-scrollbar-thumb{
    background:rgba(59,130,246,.58);
    border-radius:999px;
    border:2px solid rgba(15,23,42,.72);
}

.table-wrap table,
.table-wrap-pro table,
.table-responsive table{
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
    body{
        overflow-y:auto;
    }

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

@media (max-width: 1199.98px){
    .kpis,
    .cards{
        grid-template-columns:repeat(2, minmax(0, 1fr));
    }

    .grid,
    .content .stats-grid,
    .content .filter-grid,
    .content .detail-grid,
    .content .two-col,
    .content .form-grid,
    .content .mini-grid,
    .content .chart-row,
    .content .panel-grid,
    .content .field-grid,
    .content .payment-grid,
    .content .payment-layout,
    .content .grid-2,
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

    .btn-logout{
        width:100%;
    }
}

@media (max-width: 767.98px){
    .kpis,
    .cards,
    .grid,
    .content .stats-grid,
    .content .filter-grid,
    .content .detail-grid,
    .content .two-col,
    .content .form-grid,
    .content .mini-grid,
    .content .chart-row,
    .content .panel-grid,
    .content .field-grid,
    .content .payment-grid,
    .content .payment-layout,
    .content .grid-2,
    .content .preview-grid-2{
        grid-template-columns:1fr !important;
    }

    .content .panel-card,
    .content .hero-card,
    .content .detail-card,
    .content .box,
    .content .kpi,
    .content .card{
        padding:18px !important;
        border-radius:18px;
    }

    .content .panel-header,
    .content .detail-header,
    .content .toolbar,
    .content .header-actions,
    .content .filter-actions,
    .content .actions,
    .content .actions-inline,
    .content .modal-actions,
    .content .detail-actions,
    .content .pagination-bar,
    .content .meta-chips,
    .content .btn-row,
    .content .preview-foot-actions,
    .content .table-scroll-actions{
        width:100%;
        display:flex;
        flex-direction:column;
        align-items:stretch;
    }

    .content .toolbar > *,
    .content .header-actions > *,
    .content .filter-actions > *,
    .content .actions > *,
    .content .actions-inline > *,
    .content .modal-actions > *,
    .content .detail-actions > *,
    .content .pagination-bar > *,
    .content .meta-chips > *,
    .content .btn-row > *,
    .content .preview-foot-actions > *,
    .content .table-scroll-actions > *{
        width:100%;
    }

    .content .search-products,
    .content .input-pro,
    .content .select-pro,
    .content .field input,
    .content .field select,
    .content .field textarea{
        width:100%;
        min-width:0;
    }

    .table-wrap table,
    .table-wrap-pro table,
    .table-responsive table{
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

    <aside class="sidebar" id="adminSidebar">
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
            <a href="/admin" class="{{ request()->is('admin') ? 'active' : '' }}">📊 Dashboard</a>
            <a href="/admin/usarios" class="{{ request()->is('admin/usarios') ? 'active' : '' }}">👤 Usuarios Caja</a>
            <a href="/admin/categorias" class="{{ request()->is('admin/categorias') ? 'active' : '' }}">📂 Categorias</a>
            <a href="/admin/subcategorias" class="{{ request()->is('admin/subcategorias') ? 'active' : '' }}">🧩 Subcategorias</a>
            <a href="/admin/productos" class="{{ request()->is('admin/productos') ? 'active' : '' }}">💎 Productos</a>
            <a href="/admin/inventario" class="{{ request()->is('admin/inventario') ? 'active' : '' }}">📦 Inventario</a>
            <a href="/admin/caja" class="{{ request()->is('admin/caja') ? 'active' : '' }}">🧾 Resumen Caja</a>
            <a href="/admin/ventas/anulaciones" class="{{ request()->is('admin/ventas/anulaciones') ? 'active' : '' }}">↩️ Anular ventas</a>

            <a
                href="#"
                data-keep-open="true"
                onclick="toggleMenu('contableMenu'); return false;"
                class="{{ request()->is('admin/contabilidad*') || request()->is('admin/gastos*') ? 'active' : '' }}"
            >
                📊 Contabilidad ▾
            </a>

            <div id="contableMenu" class="submenu {{ request()->is('admin/contabilidad*') || request()->is('admin/gastos*') ? 'open' : '' }}">
                <a href="/admin/contabilidad" class="{{ request()->is('admin/contabilidad') ? 'active' : '' }}">Resumen</a>
                <a href="/admin/gastos" class="{{ request()->is('admin/gastos') ? 'active' : '' }}">Gastos</a>
            </div>
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

                <div class="topbar-copy">
                    <span class="topbar-label">Panel Administrativo</span>
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

function toggleMenu(id){
    const el = document.getElementById(id);
    if(!el) return;
    el.classList.toggle("open");
}

document.addEventListener("DOMContentLoaded", ()=>{
    const token = localStorage.getItem("token");
    const user = JSON.parse(localStorage.getItem("usuario") || "{}");
    const roles = Array.isArray(user.roles) ? user.roles : [];
    const isAdmin = roles.includes("ADMIN");
    const isCaja = roles.includes("CAJA");

    if(!token || roles.length === 0){
        window.location.href = "/login";
        return;
    }

    if(!isAdmin){
        window.location.href = isCaja ? "/caja" : "/login";
        return;
    }

    document.querySelectorAll(".menu a").forEach((link)=>{
        link.addEventListener("click", ()=>{
            if(link.dataset.keepOpen === "true"){
                return;
            }
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
