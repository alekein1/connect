<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect Core | Sistema profesional</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg: #07090d;
            --bg-soft: #0d1118;
            --panel: rgba(255,255,255,0.06);
            --panel-2: rgba(255,255,255,0.04);
            --border: rgba(255,255,255,0.10);
            --text: #f5f7fb;
            --muted: #a9b1bc;
            --gold: #d4af37;
            --gold-2: #f0c94d;
            --blue: #7d7cff;
            --blue-2: #4f46e5;
            --shadow: 0 20px 60px rgba(0,0,0,.45);
            --radius: 24px;
            --container: 1240px;
        }

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        html{
            scroll-behavior:smooth;
        }

        body{
            font-family:'Inter',sans-serif;
            background:
                radial-gradient(circle at 15% 20%, rgba(79,70,229,.20), transparent 28%),
                radial-gradient(circle at 85% 25%, rgba(212,175,55,.18), transparent 24%),
                radial-gradient(circle at 70% 80%, rgba(125,124,255,.10), transparent 22%),
                linear-gradient(180deg, #06070b 0%, #090c12 45%, #07090d 100%);
            color:var(--text);
            min-height:100vh;
            overflow-x:hidden;
        }

        .container{
            width:min(92%, var(--container));
            margin:0 auto;
        }

        .nav{
            position:sticky;
            top:0;
            z-index:50;
            backdrop-filter: blur(14px);
            background: rgba(7,9,13,.55);
            border-bottom:1px solid rgba(255,255,255,.06);
        }

        .nav-wrap{
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:18px 0;
        }

        .brand{
            display:flex;
            align-items:center;
            gap:14px;
            text-decoration:none;
            color:var(--text);
        }

        .brand-badge{
            width:42px;
            height:42px;
            border-radius:14px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:900;
            font-size:18px;
            color:#0b0b0b;
            background:linear-gradient(135deg,var(--gold),var(--gold-2));
            box-shadow:0 10px 24px rgba(212,175,55,.28);
        }

        .brand-text{
            display:flex;
            flex-direction:column;
            line-height:1.05;
        }

        .brand-text strong{
            font-size:16px;
            letter-spacing:.4px;
        }

        .brand-text span{
            color:var(--muted);
            font-size:12px;
            font-weight:600;
        }

        .nav-actions{
            display:flex;
            align-items:center;
            gap:14px;
        }

        .nav-link{
            color:var(--muted);
            text-decoration:none;
            font-weight:600;
            font-size:14px;
            transition:.25s ease;
        }

        .nav-link:hover{
            color:#fff;
        }

        .btn{
            border:none;
            outline:none;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            padding:14px 24px;
            border-radius:999px;
            font-weight:800;
            font-size:15px;
            transition:.25s ease;
            cursor:pointer;
        }

        .btn-primary{
            color:#0a0a0a;
            background:linear-gradient(135deg,var(--gold),var(--gold-2));
            box-shadow:0 14px 30px rgba(212,175,55,.22);
        }

        .btn-primary:hover{
            transform:translateY(-2px);
            box-shadow:0 18px 36px rgba(212,175,55,.28);
        }

        .btn-secondary{
            background:rgba(255,255,255,.06);
            color:#fff;
            border:1px solid rgba(255,255,255,.10);
        }

        .btn-secondary:hover{
            background:rgba(255,255,255,.10);
            transform:translateY(-2px);
        }

        .hero{
            padding:72px 0 36px;
        }

        .hero-grid{
            display:grid;
            grid-template-columns: 1.08fr .92fr;
            gap:42px;
            align-items:center;
        }

        .eyebrow{
            display:inline-flex;
            align-items:center;
            gap:10px;
            padding:10px 14px;
            border-radius:999px;
            background:rgba(255,255,255,.05);
            border:1px solid rgba(255,255,255,.08);
            color:#dfe5ed;
            font-size:13px;
            font-weight:700;
            margin-bottom:22px;
        }

        .eyebrow-dot{
            width:9px;
            height:9px;
            border-radius:50%;
            background:linear-gradient(135deg,var(--gold),var(--gold-2));
            box-shadow:0 0 12px rgba(212,175,55,.85);
        }

        .hero h1{
            font-size:clamp(2.8rem, 6vw, 5.2rem);
            line-height:.98;
            letter-spacing:-2.5px;
            font-weight:900;
            max-width:760px;
        }

        .hero h1 .accent{
            background:linear-gradient(135deg,#fff 0%, #d9dfff 28%, #a5a4ff 60%, var(--gold-2) 100%);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
            background-clip:text;
        }

        .hero p{
            margin-top:24px;
            max-width:700px;
            color:var(--muted);
            font-size:1.16rem;
            line-height:1.75;
        }

        .hero-actions{
            display:flex;
            gap:14px;
            flex-wrap:wrap;
            margin-top:30px;
        }

        .hero-metrics{
            display:grid;
            grid-template-columns: repeat(3, 1fr);
            gap:16px;
            margin-top:34px;
        }

        .metric{
            background:rgba(255,255,255,.04);
            border:1px solid rgba(255,255,255,.08);
            border-radius:20px;
            padding:18px 20px;
            box-shadow: var(--shadow);
        }

        .metric strong{
            display:block;
            font-size:1.25rem;
            margin-bottom:6px;
        }

        .metric span{
            color:var(--muted);
            font-size:14px;
            line-height:1.5;
        }

        .hero-visual{
            position:relative;
        }

        .visual-card{
            position:relative;
            background:
                linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.03));
            border:1px solid rgba(255,255,255,.12);
            border-radius:32px;
            padding:28px;
            min-height:620px;
            box-shadow: var(--shadow);
            overflow:hidden;
            backdrop-filter: blur(16px);
        }

        .visual-glow{
            position:absolute;
            inset:auto;
            width:340px;
            height:340px;
            right:-80px;
            top:-80px;
            background:radial-gradient(circle, rgba(212,175,55,.30), transparent 62%);
            pointer-events:none;
        }

        .visual-glow-2{
            position:absolute;
            width:320px;
            height:320px;
            left:-100px;
            bottom:-120px;
            background:radial-gradient(circle, rgba(79,70,229,.24), transparent 62%);
            pointer-events:none;
        }

        .chip-row{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-bottom:22px;
        }

        .chip{
            padding:10px 14px;
            border-radius:999px;
            background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.10);
            color:#dfe5ed;
            font-size:13px;
            font-weight:700;
        }

        .logo-box{
            position:relative;
            background:
                radial-gradient(circle at center, rgba(255,255,255,.92), rgba(255,255,255,.78));
            border-radius:28px;
            min-height:300px;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:26px;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.8),
                0 18px 40px rgba(0,0,0,.25);
            border:1px solid rgba(255,255,255,.55);
        }

        .logo-box img{
            width:min(100%, 460px);
            max-height:220px;
            object-fit:contain;
            filter: drop-shadow(0 12px 24px rgba(0,0,0,.18));
        }

        .floating-card{
            margin-top:20px;
            background:rgba(8,12,18,.88);
            border:1px solid rgba(255,255,255,.10);
            border-radius:24px;
            padding:22px;
        }

        .floating-title{
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-bottom:16px;
        }

        .floating-title h3{
            font-size:18px;
        }

        .badge{
            padding:8px 12px;
            border-radius:999px;
            font-size:12px;
            font-weight:800;
            color:#0a0a0a;
            background:linear-gradient(135deg,var(--gold),var(--gold-2));
        }

        .mini-grid{
            display:grid;
            grid-template-columns:repeat(2,1fr);
            gap:14px;
        }

        .mini-item{
            padding:16px;
            border-radius:18px;
            background:rgba(255,255,255,.04);
            border:1px solid rgba(255,255,255,.07);
        }

        .mini-item strong{
            display:block;
            font-size:14px;
            margin-bottom:8px;
        }

        .mini-item p{
            color:var(--muted);
            font-size:13px;
            line-height:1.6;
        }

        section{
            padding:34px 0 0;
        }

        .section-head{
            text-align:center;
            max-width:780px;
            margin:0 auto 26px;
        }

        .section-head .kicker{
            display:inline-block;
            color:var(--gold-2);
            font-weight:800;
            font-size:13px;
            letter-spacing:1.2px;
            text-transform:uppercase;
            margin-bottom:14px;
        }

        .section-head h2{
            font-size:clamp(2rem,4vw,3rem);
            line-height:1.08;
            font-weight:900;
            letter-spacing:-1.2px;
        }

        .section-head p{
            margin-top:16px;
            color:var(--muted);
            line-height:1.75;
            font-size:1.05rem;
        }

        .feature-grid{
            display:grid;
            grid-template-columns: repeat(3, 1fr);
            gap:18px;
            margin-top:26px;
        }

        .feature-card{
            position:relative;
            padding:26px;
            border-radius:26px;
            background:
                linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.035));
            border:1px solid rgba(255,255,255,.10);
            overflow:hidden;
            min-height:240px;
            box-shadow: var(--shadow);
        }

        .feature-card::before{
            content:"";
            position:absolute;
            width:160px;
            height:160px;
            right:-50px;
            top:-50px;
            background:radial-gradient(circle, rgba(212,175,55,.12), transparent 65%);
        }

        .icon{
            width:52px;
            height:52px;
            border-radius:16px;
            display:flex;
            align-items:center;
            justify-content:center;
            margin-bottom:20px;
            font-size:22px;
            background:rgba(255,255,255,.07);
            border:1px solid rgba(255,255,255,.10);
        }

        .feature-card h3{
            font-size:1.2rem;
            margin-bottom:12px;
        }

        .feature-card p{
            color:var(--muted);
            line-height:1.75;
            font-size:15px;
        }

        .split{
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap:22px;
            margin-top:28px;
        }

        .panel{
            background:
                linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
            border:1px solid rgba(255,255,255,.09);
            border-radius:28px;
            padding:28px;
            box-shadow: var(--shadow);
        }

        .panel h3{
            font-size:1.45rem;
            margin-bottom:12px;
        }

        .panel p{
            color:var(--muted);
            line-height:1.75;
            margin-bottom:22px;
        }

        .list{
            display:grid;
            gap:12px;
        }

        .list-item{
            display:flex;
            gap:12px;
            align-items:flex-start;
            padding:14px 16px;
            border-radius:18px;
            background:rgba(255,255,255,.04);
            border:1px solid rgba(255,255,255,.07);
        }

        .check{
            width:26px;
            height:26px;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            flex-shrink:0;
            color:#0b0b0b;
            font-size:14px;
            font-weight:900;
            background:linear-gradient(135deg,var(--gold),var(--gold-2));
        }

        .list-item strong{
            display:block;
            margin-bottom:5px;
            font-size:15px;
        }

        .list-item span{
            color:var(--muted);
            font-size:14px;
            line-height:1.6;
        }

        .cta{
            margin:38px 0 56px;
            padding:34px;
            border-radius:32px;
            background:
                radial-gradient(circle at top right, rgba(212,175,55,.18), transparent 28%),
                radial-gradient(circle at bottom left, rgba(79,70,229,.18), transparent 28%),
                linear-gradient(180deg, rgba(255,255,255,.07), rgba(255,255,255,.03));
            border:1px solid rgba(255,255,255,.10);
            box-shadow: var(--shadow);
            text-align:center;
        }

        .cta h3{
            font-size:clamp(1.8rem,3vw,2.8rem);
            font-weight:900;
            letter-spacing:-1px;
            margin-bottom:12px;
        }

        .cta p{
            color:var(--muted);
            max-width:760px;
            margin:0 auto 24px;
            line-height:1.75;
            font-size:1.05rem;
        }

        .footer{
            padding:0 0 34px;
            text-align:center;
            color:#7e8794;
            font-size:14px;
        }

        @media (max-width: 1100px){
            .hero-grid,
            .split,
            .feature-grid{
                grid-template-columns:1fr;
            }

            .hero{
                padding-top:50px;
            }

            .visual-card{
                min-height:auto;
            }
        }

        @media (max-width: 780px){
            .nav-wrap{
                gap:18px;
            }

            .nav-actions .nav-link{
                display:none;
            }

            .hero-metrics,
            .mini-grid{
                grid-template-columns:1fr;
            }

            .hero h1{
                letter-spacing:-1.5px;
            }

            .logo-box{
                min-height:220px;
            }
        }

        @media (max-width: 520px){
            .btn{
                width:100%;
            }

            .hero-actions{
                flex-direction:column;
            }

            .brand-text span{
                display:none;
            }

            .cta{
                padding:26px 18px;
            }
        }
    </style>
</head>
<body>

    <nav class="nav">
        <div class="container nav-wrap">
            <a href="/" class="brand">
                <div class="brand-badge">C</div>
                <div class="brand-text">
                    <strong>CONNECT CORE</strong>
                    <span>Importadora Tecnológica</span>
                </div>
            </a>

            <div class="nav-actions">
                <a href="#funciones" class="nav-link">Funciones</a>
                <a href="#beneficios" class="nav-link">Beneficios</a>
                <a href="/login" class="btn btn-primary">Ingresar al sistema</a>
            </div>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="container hero-grid">

                <div>
                    <div class="eyebrow">
                        <span class="eyebrow-dot"></span>
                        Software comercial para negocios tecnológicos
                    </div>

                    <h1>
                        Gestiona tu tienda con una imagen
                        <span class="accent">realmente profesional</span>
                    </h1>

                    <p>
                        Connect Core es una plataforma de gestión pensada para tiendas de celulares,
                        accesorios y tecnología. Controla inventario, ventas, múltiples locales,
                        usuarios y operación diaria desde una sola interfaz moderna, rápida y confiable.
                    </p>

                    <div class="hero-actions">
                        <a href="/login" class="btn btn-primary">🚀 Ingresar ahora</a>
                        <a href="#funciones" class="btn btn-secondary">Ver funciones</a>
                    </div>

                    <div class="hero-metrics">
                        <div class="metric">
                            <strong>Multi-local</strong>
                            <span>Administra varios puntos de venta sin mezclar información.</span>
                        </div>
                        <div class="metric">
                            <strong>Inventario exacto</strong>
                            <span>Control de stock, movimientos y disponibilidad en tiempo real.</span>
                        </div>
                        <div class="metric">
                            <strong>Operación rápida</strong>
                            <span>Diseñado para trabajar ágil en mostrador, caja y administración.</span>
                        </div>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="visual-card">
                        <div class="visual-glow"></div>
                        <div class="visual-glow-2"></div>

                        <div class="chip-row">
                            <div class="chip">Ventas</div>
                            <div class="chip">Inventario</div>
                            <div class="chip">Locales</div>
                            <div class="chip">Usuarios</div>
                            <div class="chip">Reportes</div>
                        </div>

                        <div class="logo-box">
                            <img src="{{ asset('images/connect.png') }}" alt="Connect Importadora Tecnológica">
                        </div>

                        <div class="floating-card">
                            <div class="floating-title">
                                <h3>Control centralizado</h3>
                                <span class="badge">PRO</span>
                            </div>

                            <div class="mini-grid">
                                <div class="mini-item">
                                    <strong>Super administración</strong>
                                    <p>Crea locales, usuarios y controla toda la operación desde un mismo panel.</p>
                                </div>
                                <div class="mini-item">
                                    <strong>Seguridad por roles</strong>
                                    <p>Accesos diferenciados para super admin, administrador y caja.</p>
                                </div>
                                <div class="mini-item">
                                    <strong>Escalable</strong>
                                    <p>Estructura preparada para crecer con más productos, módulos y sucursales.</p>
                                </div>
                                <div class="mini-item">
                                    <strong>Preparado para producción</strong>
                                    <p>Construido con backend API y frontend profesional para operación real.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <section id="funciones">
            <div class="container">
                <div class="section-head">
                    <div class="kicker">Funciones principales</div>
                    <h2>Todo lo necesario para administrar una tienda tecnológica moderna</h2>
                    <p>
                        Una presentación profesional también debe transmitir capacidad operativa.
                        Por eso esta landing no solo vende diseño: comunica claramente lo que el sistema resuelve.
                    </p>
                </div>

                <div class="feature-grid">
                    <article class="feature-card">
                        <div class="icon">🏬</div>
                        <h3>Gestión multi-local</h3>
                        <p>
                            Supervisa diferentes locales desde una sola plataforma y mantén la información
                            organizada por sucursal, sin perder control global del negocio.
                        </p>
                    </article>

                    <article class="feature-card">
                        <div class="icon">📦</div>
                        <h3>Inventario inteligente</h3>
                        <p>
                            Controla entradas, salidas, stock disponible y movimientos con precisión.
                            Ideal para productos tecnológicos y operación comercial diaria.
                        </p>
                    </article>

                    <article class="feature-card">
                        <div class="icon">💳</div>
                        <h3>Ventas ágiles</h3>
                        <p>
                            Interfaz rápida para atención en mostrador, caja y procesos de venta,
                            pensada para negocios que necesitan velocidad y orden.
                        </p>
                    </article>

                    <article class="feature-card">
                        <div class="icon">👥</div>
                        <h3>Usuarios y roles</h3>
                        <p>
                            Define permisos para super administrador, administrador y caja,
                            asegurando una operación más segura y bien distribuida.
                        </p>
                    </article>

                    <article class="feature-card">
                        <div class="icon">📊</div>
                        <h3>Reportes claros</h3>
                        <p>
                            Visualiza información útil para la toma de decisiones:
                            ventas, productos, rendimiento y control general del negocio.
                        </p>
                    </article>

                    <article class="feature-card">
                        <div class="icon">⚡</div>
                        <h3>Listo para crecer</h3>
                        <p>
                            Estructura escalable para seguir agregando módulos, automatizaciones
                            y funciones avanzadas sin perder estabilidad.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section id="beneficios">
            <div class="container">
                <div class="split">
                    <div class="panel">
                        <h3>Una imagen más seria para vender mejor tu software</h3>
                        <p>
                            Esta versión ya no se siente como una página improvisada. La composición,
                            el contraste, las tarjetas, la jerarquía y el uso del logo están pensados
                            para transmitir una marca tecnológica más sólida.
                        </p>

                        <div class="list">
                            <div class="list-item">
                                <div class="check">✓</div>
                                <div>
                                    <strong>Logo visible correctamente</strong>
                                    <span>El logo va dentro de una tarjeta iluminada para evitar que se pierda sobre fondos oscuros.</span>
                                </div>
                            </div>
                            <div class="list-item">
                                <div class="check">✓</div>
                                <div>
                                    <strong>Jerarquía visual profesional</strong>
                                    <span>Títulos grandes, subtítulos claros, acciones visibles y secciones bien distribuidas.</span>
                                </div>
                            </div>
                            <div class="list-item">
                                <div class="check">✓</div>
                                <div>
                                    <strong>Diseño más vendible</strong>
                                    <span>Se siente más cercano a una landing de software comercial que a una página básica.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Diseñado para operación comercial real</h3>
                        <p>
                            Connect Core no es una simple vitrina. Es la puerta de entrada a un sistema
                            preparado para tiendas con inventario, ventas rápidas, administración de usuarios
                            y manejo de locales.
                        </p>

                        <div class="list">
                            <div class="list-item">
                                <div class="check">✓</div>
                                <div>
                                    <strong>Backoffice organizado</strong>
                                    <span>Administración limpia y lista para módulos como productos, compras, clientes y reportes.</span>
                                </div>
                            </div>
                            <div class="list-item">
                                <div class="check">✓</div>
                                <div>
                                    <strong>Ideal para hosting compartido</strong>
                                    <span>Frontend Laravel con una presentación seria y consumo de API desacoplada.</span>
                                </div>
                            </div>
                            <div class="list-item">
                                <div class="check">✓</div>
                                <div>
                                    <strong>Base para seguir subiendo nivel</strong>
                                    <span>Sobre esto ya puedes montar login premium, dashboard moderno y experiencia de usuario más fuerte.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cta">
                    <h3>Potencia la gestión de tu negocio con una plataforma más moderna</h3>
                    <p>
                        Controla inventario, ventas, locales y usuarios desde una sola solución.
                        Una imagen más fuerte genera más confianza, y un sistema mejor estructurado
                        genera una operación más sólida.
                    </p>
                    <a href="/login" class="btn btn-primary">Ingresar al sistema</a>
                </div>
            </div>
        </section>
    </main>

    <div class="container footer">
        © {{ date('Y') }} Connect Core · Sistema profesional para tiendas tecnológicas
    </div>

</body>
</html>