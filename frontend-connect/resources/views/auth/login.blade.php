<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login · Connect Core</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">

<style>
body{
    background: radial-gradient(circle at top,#1a1f2e,#07090d);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:'Inter',sans-serif;
}

.login-card{
    width:100%;
    max-width:420px;
    background:rgba(255,255,255,0.05);
    backdrop-filter:blur(15px);
    border:1px solid rgba(255,255,255,0.1);
    padding:35px;
    border-radius:20px;
    color:#fff;
    box-shadow:0 20px 60px rgba(0,0,0,0.6);
}

.logo-box{
    background:#fff;
    padding:10px;
    border-radius:12px;
    text-align:center;
    margin-bottom:20px;
}

.logo-box img{
    width:150px;
}

.title{
    text-align:center;
    font-weight:800;
    margin-bottom:25px;
}

.form-control{
    background:#111;
    border:1px solid #333;
    color:#fff;
}

.form-control:focus{
    border-color:#d4af37;
    box-shadow:none;
}

.btn-main{
    background:linear-gradient(135deg,#d4af37,#f0c94d);
    color:#000;
    font-weight:700;
    border:none;
    padding:12px;
    border-radius:10px;
}

.footer{
    text-align:center;
    margin-top:20px;
    font-size:12px;
    color:#aaa;
}

.footer span{
    color:#d4af37;
}
</style>
</head>

<body>

<div class="login-card">

    <div class="logo-box">
        <img src="{{ asset('images/connect.png') }}">
    </div>

    <h4 class="title">Iniciar sesión</h4>

    <form id="loginForm">

        <input type="text" id="usuario" class="form-control mb-3" placeholder="Usuario" required>
        <input type="password" id="password" class="form-control mb-3" placeholder="Contraseña" required>

        <button class="btn btn-main w-100" id="btnLogin">
            Ingresar
        </button>

    </form>

    <div id="mensaje" class="mt-3"></div>

    <div class="footer">
        © {{ date('Y') }} <span>Master Repair</span> · Soluciones informáticas y ciberseguridad
    </div>

</div>

<script>
const API = "{{ env('API_URL') }}";

const form = document.getElementById("loginForm");
const btn = document.getElementById("btnLogin");
const msg = document.getElementById("mensaje");

form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const usuario = document.getElementById("usuario").value;
    const password = document.getElementById("password").value;

    btn.disabled = true;
    btn.innerText = "Validando...";

    try {
        const res = await fetch(`${API}/auth/login`, {
            method:"POST",
            headers:{ "Content-Type":"application/json" },
            body: JSON.stringify({ usuario, password })
        });

        const json = await res.json();

        if(!json.ok){
            throw new Error(json.mensaje);
        }

        // guardar sesión
        localStorage.setItem("token", json.token);
        localStorage.setItem("usuario", JSON.stringify(json.usuario));

        msg.innerHTML = `<div class="alert alert-success">Acceso correcto</div>`;

        const roles = json.usuario.roles || [];

        // 🔥 MULTIROL REAL
        let destino = "/login";

        if(roles.includes("SUPER_ADMIN")){
            destino = "/superadmin";
        }
        else if(roles.includes("ADMIN")){
            destino = "/admin";
        }
        else if(roles.includes("CAJA")){
            destino = "/caja";
        }

        setTimeout(()=> window.location.href = destino, 700);

    } catch(err){
        msg.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
    }

    btn.disabled = false;
    btn.innerText = "Ingresar";
});


/* 🔒 AUTO REDIRECT */
document.addEventListener("DOMContentLoaded", ()=>{
    const token = localStorage.getItem("token");
    const user = JSON.parse(localStorage.getItem("usuario") || "{}");

    if(token && user.roles){
        if(user.roles.includes("SUPER_ADMIN")) location.href="/superadmin";
        if(user.roles.includes("ADMIN")) location.href="/admin";
        if(user.roles.includes("CAJA")) location.href="/caja";
    }
});
</script>

</body>
</html>
