<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pruebas SRI</title>
    <style>
        :root{
            --bg:#07111f;
            --bg-soft:#0d1a2e;
            --card:#0f1d33;
            --line:rgba(255,255,255,.08);
            --text:#eef2ff;
            --muted:#93a4bf;
            --accent:#f4c842;
            --accent-deep:#d8a910;
            --danger:#ef4444;
            --success:#22c55e;
        }

        *{ box-sizing:border-box; }

        body{
            margin:0;
            min-height:100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(244,200,66,.15), transparent 28%),
                radial-gradient(circle at bottom right, rgba(34,197,94,.10), transparent 22%),
                linear-gradient(180deg, #050b16 0%, var(--bg) 100%);
            color:var(--text);
        }

        .shell{
            width:min(1100px, calc(100% - 32px));
            margin:32px auto;
            display:grid;
            gap:20px;
        }

        .hero,
        .card{
            background:linear-gradient(180deg, rgba(15,29,51,.96), rgba(10,20,36,.98));
            border:1px solid var(--line);
            border-radius:24px;
            box-shadow:0 18px 48px rgba(0,0,0,.35);
        }

        .hero{
            padding:28px;
        }

        .eyebrow{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:8px 12px;
            border-radius:999px;
            background:rgba(244,200,66,.12);
            color:#ffe7a1;
            font-size:12px;
            font-weight:700;
            letter-spacing:.06em;
            text-transform:uppercase;
        }

        h1{
            margin:16px 0 10px;
            font-size:38px;
            line-height:1.05;
        }

        .hero p{
            margin:0;
            color:var(--muted);
            max-width:760px;
            line-height:1.6;
        }

        .grid{
            display:grid;
            grid-template-columns: 1.05fr .95fr;
            gap:20px;
        }

        .card{
            padding:24px;
        }

        .card h2{
            margin:0 0 8px;
            font-size:22px;
        }

        .card p{
            margin:0 0 18px;
            color:var(--muted);
            line-height:1.55;
        }

        .form-grid{
            display:grid;
            gap:14px;
        }

        .field{
            display:grid;
            gap:8px;
        }

        .field label{
            font-size:12px;
            font-weight:700;
            color:#bfd0ea;
            text-transform:uppercase;
            letter-spacing:.05em;
        }

        .field input,
        .field textarea,
        .field select{
            width:100%;
            border:1px solid rgba(255,255,255,.10);
            background:var(--bg-soft);
            color:var(--text);
            border-radius:14px;
            padding:14px 15px;
            outline:none;
            font-size:14px;
        }

        .field input:focus,
        .field textarea:focus,
        .field select:focus{
            border-color:rgba(244,200,66,.5);
            box-shadow:0 0 0 3px rgba(244,200,66,.10);
        }

        .hint{
            color:var(--muted);
            font-size:12px;
            line-height:1.5;
        }

        .actions{
            display:flex;
            gap:12px;
            flex-wrap:wrap;
            margin-top:8px;
        }

        .btn{
            border:none;
            border-radius:14px;
            padding:13px 18px;
            font-size:14px;
            font-weight:800;
            cursor:pointer;
            transition:.2s ease;
        }

        .btn-primary{
            background:linear-gradient(135deg, var(--accent), var(--accent-deep));
            color:#101828;
        }

        .btn-secondary{
            background:rgba(255,255,255,.06);
            color:var(--text);
            border:1px solid rgba(255,255,255,.08);
        }

        .btn:hover{
            transform:translateY(-1px);
        }

        .status{
            display:none;
            margin-top:16px;
            padding:14px 16px;
            border-radius:14px;
            font-size:14px;
            line-height:1.5;
        }

        .status.show{
            display:block;
        }

        .status.error{
            background:rgba(239,68,68,.12);
            border:1px solid rgba(239,68,68,.22);
            color:#fecaca;
        }

        .status.success{
            background:rgba(34,197,94,.10);
            border:1px solid rgba(34,197,94,.22);
            color:#bbf7d0;
        }

        .viewer{
            background:#06101d;
            border:1px solid rgba(255,255,255,.08);
            border-radius:18px;
            padding:18px;
            min-height:520px;
            display:flex;
            flex-direction:column;
            gap:14px;
        }

        .viewer-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            flex-wrap:wrap;
        }

        .viewer-head h3{
            margin:0;
            font-size:18px;
        }

        .api-chip{
            padding:8px 12px;
            border-radius:999px;
            background:rgba(255,255,255,.06);
            color:#cdd8ea;
            font-size:12px;
            max-width:100%;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }

        pre{
            margin:0;
            white-space:pre-wrap;
            word-break:break-word;
            color:#d9e5f7;
            line-height:1.55;
            font-size:13px;
            font-family: "SFMono-Regular", Menlo, Monaco, Consolas, monospace;
        }

        .mini-list{
            display:grid;
            gap:10px;
            margin-top:14px;
        }

        .mini-item{
            padding:12px 14px;
            border-radius:14px;
            background:rgba(255,255,255,.04);
            border:1px solid rgba(255,255,255,.06);
            color:var(--muted);
            font-size:13px;
            line-height:1.5;
        }

        @media (max-width: 920px){
            .grid{
                grid-template-columns:1fr;
            }

            h1{
                font-size:30px;
            }

            .viewer{
                min-height:420px;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="hero">
            <div class="eyebrow">Modo Pruebas SRI</div>
            <h1>Panel abierto para probar tu firma electronica</h1>
            <p>
                Esta pantalla te deja validar el certificado <code>.p12/.pfx</code> sin login.
                Sirve para confirmar que la clave funciona, que el archivo abre bien y que podemos
                leer datos del emisor antes de construir XML, firma y envio real al SRI.
            </p>
        </section>

        <section class="grid">
            <div class="card">
                <h2>Probar Certificado</h2>
                <p>Sube tu firma electronica o, si ya la tienes en el servidor, indica la ruta y la clave.</p>

                <form id="formSri" class="form-grid">
                    <div class="field">
                        <label>Archivo Certificado</label>
                        <input type="file" id="certificado" name="certificado" accept=".p12,.pfx">
                        <div class="hint">Puedes subir un archivo <code>.p12</code> o <code>.pfx</code>.</div>
                    </div>

                    <div class="field">
                        <label>Ruta Certificado en Servidor</label>
                        <input type="text" id="ruta_certificado" name="ruta_certificado" placeholder="/ruta/al/certificado.p12">
                        <div class="hint">Opcional. Usalo si el archivo ya esta guardado en el servidor y no quieres subirlo otra vez.</div>
                    </div>

                    <div class="field">
                        <label>Clave del Certificado</label>
                        <input type="password" id="clave_certificado" name="clave_certificado" placeholder="Ingresa la clave de tu firma">
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn-primary" id="btnProbar">Probar firma</button>
                        <button type="button" class="btn btn-secondary" id="btnInfo">Consultar modulo</button>
                    </div>
                </form>

                <div id="status" class="status"></div>

                <div class="mini-list">
                    <div class="mini-item">
                        Endpoint abierto de prueba: <code>/api/sri/pruebas/certificado/probar</code>
                    </div>
                    <div class="mini-item">
                        Esto es solo para pruebas. Cuando avances a produccion conviene cerrar estas rutas publicas.
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="viewer">
                    <div class="viewer-head">
                        <h3>Respuesta del backend</h3>
                        <div class="api-chip" id="apiChip"></div>
                    </div>
                    <pre id="resultado">Esperando prueba...</pre>
                </div>
            </div>
        </section>

        <section class="grid">
            <div class="card">
                <h2>Configuracion SRI por Local</h2>
                <p>Guarda la informacion fiscal del emisor sin tocar tu flujo actual de ventas.</p>

                <form id="formConfigSri" class="form-grid">
                    <div class="field">
                        <label>Local</label>
                        <select id="config_id_local"></select>
                        <div class="hint">La lista se carga automaticamente desde los locales activos registrados en tu base.</div>
                    </div>

                    <div class="field">
                        <label>RUC</label>
                        <input type="text" id="config_ruc" maxlength="13" placeholder="0604614248001">
                    </div>

                    <div class="field">
                        <label>Razon Social</label>
                        <input type="text" id="config_razon_social" placeholder="Razon social del emisor">
                    </div>

                    <div class="field">
                        <label>Nombre Comercial</label>
                        <input type="text" id="config_nombre_comercial" placeholder="Nombre comercial">
                    </div>

                    <div class="field">
                        <label>Direccion Matriz</label>
                        <input type="text" id="config_dir_matriz" placeholder="Direccion matriz">
                    </div>

                    <div class="field">
                        <label>Direccion Establecimiento</label>
                        <input type="text" id="config_dir_establecimiento" placeholder="Direccion del establecimiento emisor">
                    </div>

                    <div class="field">
                        <label>Establecimiento</label>
                        <input type="text" id="config_establecimiento" value="001" maxlength="3">
                    </div>

                    <div class="field">
                        <label>Punto de Emision</label>
                        <input type="text" id="config_punto_emision" value="001" maxlength="3">
                    </div>

                    <div class="field">
                        <label>Obligado a Llevar Contabilidad</label>
                        <select id="config_obligado_contabilidad">
                            <option value="NO">NO</option>
                            <option value="SI">SI</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Contribuyente Especial</label>
                        <input type="text" id="config_contribuyente_especial" placeholder="Opcional">
                    </div>

                    <div class="field">
                        <label>Ambiente</label>
                        <select id="config_ambiente">
                            <option value="PRUEBAS">PRUEBAS</option>
                            <option value="PRODUCCION">PRODUCCION</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Correo Notificacion</label>
                        <input type="email" id="config_correo_notificacion" placeholder="facturacion@tuempresa.com">
                    </div>

                    <div class="field">
                        <label>Telefono</label>
                        <input type="text" id="config_telefono" placeholder="0999999999">
                    </div>

                    <div class="field">
                        <label>Archivo Certificado</label>
                        <input type="file" id="config_certificado" accept=".p12,.pfx">
                    </div>

                    <div class="field">
                        <label>Ruta Certificado en Servidor</label>
                        <input type="text" id="config_ruta_certificado" placeholder="/ruta/al/certificado.p12">
                    </div>

                    <div class="field">
                        <label>Clave Certificado</label>
                        <input type="password" id="config_clave_certificado" placeholder="Solo si quieres guardar o actualizar la clave">
                    </div>

                    <div class="actions">
                        <button type="button" class="btn btn-secondary" id="btnCargarConfig">Cargar configuracion</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarConfig">Guardar configuracion</button>
                    </div>
                </form>

                <div id="statusConfig" class="status"></div>

                <div class="mini-list">
                    <div class="mini-item">
                        La configuracion se guarda en tablas nuevas del modulo: <code>sri_configuraciones</code> y <code>sri_documentos</code>.
                    </div>
                    <div class="mini-item">
                        Aqui no tocamos ventas todavia. Solo dejamos la base lista para el siguiente paso: clave de acceso y XML.
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="viewer">
                    <div class="viewer-head">
                        <h3>Configuracion guardada</h3>
                        <div class="api-chip" id="apiChipConfig"></div>
                    </div>
                    <pre id="resultadoConfig">Esperando configuracion...</pre>
                </div>
            </div>
        </section>

        <section class="grid">
            <div class="card">
                <h2>Proceso de Factura SRI</h2>
                <p>Usa una venta ya registrada para generar XML, firmarlo, enviarlo al SRI y consultar su autorizacion en pruebas.</p>

                <form id="formXmlSri" class="form-grid">
                    <div class="field">
                        <label>ID Venta</label>
                        <input type="number" id="xml_id_venta" placeholder="Ej: 35">
                        <div class="hint">Debe ser una venta ya guardada. El local sale automaticamente desde la propia venta.</div>
                    </div>

                    <div class="field">
                        <label>Correo destino</label>
                        <input type="email" id="xml_correo_destino" placeholder="Opcional. Si lo dejas vacio, usa el correo del cliente">
                        <div class="hint">Solo se usa para el paso de envio por correo.</div>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn-primary" id="btnGenerarXml">Generar XML</button>
                        <button type="button" class="btn btn-secondary" id="btnFirmarXml">Firmar XML</button>
                        <button type="button" class="btn btn-secondary" id="btnEnviarSri">Enviar a SRI</button>
                        <button type="button" class="btn btn-secondary" id="btnAutorizarSri">Consultar autorizacion</button>
                        <button type="button" class="btn btn-secondary" id="btnGenerarRide">Generar RIDE</button>
                        <button type="button" class="btn btn-secondary" id="btnEnviarCorreo">Enviar correo</button>
                    </div>
                </form>

                <div id="statusXml" class="status"></div>

                <div class="mini-list">
                    <div class="mini-item">
                        Endpoints abiertos de prueba: <code>/api/sri/pruebas/facturas/:id_venta/xml</code>, <code>/firmar</code>, <code>/enviar</code>, <code>/autorizar</code>, <code>/ride</code> y <code>/email</code>.
                    </div>
                    <div class="mini-item">
                        Si el SRI devuelve rechazo o todavia no autoriza, veras el detalle real en el JSON de respuesta sin tocar el POS.
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="viewer">
                    <div class="viewer-head">
                        <h3>Respuesta de Factura SRI</h3>
                        <div class="api-chip" id="apiChipXml"></div>
                    </div>
                    <pre id="resultadoXml">Esperando acciones de factura SRI...</pre>
                </div>
            </div>
        </section>
    </div>

    <script>
        const API = "{{ rtrim(env('API_URL', 'http://localhost:4004/api'), '/') }}";
        const ENDPOINT_INFO = `${API}/sri/pruebas`;
        const ENDPOINT_CERT = `${API}/sri/pruebas/certificado/probar`;
        const ENDPOINT_LOCALES = `${API}/sri/pruebas/locales`;
        const ENDPOINT_CONFIG = `${API}/sri/pruebas/configuracion`;
        const ENDPOINT_XML_BASE = `${API}/sri/pruebas/facturas`;

        const formSri = document.getElementById("formSri");
        const resultado = document.getElementById("resultado");
        const status = document.getElementById("status");
        const btnProbar = document.getElementById("btnProbar");
        const btnInfo = document.getElementById("btnInfo");
        const apiChip = document.getElementById("apiChip");
        const formConfigSri = document.getElementById("formConfigSri");
        const statusConfig = document.getElementById("statusConfig");
        const resultadoConfig = document.getElementById("resultadoConfig");
        const btnGuardarConfig = document.getElementById("btnGuardarConfig");
        const btnCargarConfig = document.getElementById("btnCargarConfig");
        const apiChipConfig = document.getElementById("apiChipConfig");
        const formXmlSri = document.getElementById("formXmlSri");
        const resultadoXml = document.getElementById("resultadoXml");
        const statusXml = document.getElementById("statusXml");
        const btnGenerarXml = document.getElementById("btnGenerarXml");
        const btnFirmarXml = document.getElementById("btnFirmarXml");
        const btnEnviarSri = document.getElementById("btnEnviarSri");
        const btnAutorizarSri = document.getElementById("btnAutorizarSri");
        const btnGenerarRide = document.getElementById("btnGenerarRide");
        const btnEnviarCorreo = document.getElementById("btnEnviarCorreo");
        const apiChipXml = document.getElementById("apiChipXml");

        apiChip.textContent = API;
        apiChipConfig.textContent = ENDPOINT_CONFIG;
        apiChipXml.textContent = `${ENDPOINT_XML_BASE}/:id_venta/{xml|firmar|enviar|autorizar|ride|email}`;

        function setStatus(type, message){
            status.className = `status show ${type}`;
            status.textContent = message;
        }

        function resetStatus(){
            status.className = "status";
            status.textContent = "";
        }

        function setConfigStatus(type, message){
            statusConfig.className = `status show ${type}`;
            statusConfig.textContent = message;
        }

        function resetConfigStatus(){
            statusConfig.className = "status";
            statusConfig.textContent = "";
        }

        function prettyPrint(data){
            resultado.textContent = JSON.stringify(data, null, 2);
        }

        function prettyPrintConfig(data){
            resultadoConfig.textContent = JSON.stringify(data, null, 2);
        }

        function setXmlStatus(type, message){
            statusXml.className = `status show ${type}`;
            statusXml.textContent = message;
        }

        function resetXmlStatus(){
            statusXml.className = "status";
            statusXml.textContent = "";
        }

        function prettyPrintXml(data){
            resultadoXml.textContent = JSON.stringify(data, null, 2);
        }

        async function cargarLocalesPrueba(){
            const select = document.getElementById("config_id_local");
            select.innerHTML = `<option value="">Cargando locales...</option>`;

            try{
                const res = await fetch(ENDPOINT_LOCALES);
                const json = await res.json();
                const locales = json.data || [];

                if(!res.ok || !locales.length){
                    select.innerHTML = `<option value="">Sin locales disponibles</option>`;
                    setConfigStatus("error", json.mensaje || "No se encontraron locales activos");
                    return;
                }

                select.innerHTML = "";

                locales.forEach((local) => {
                    const option = document.createElement("option");
                    option.value = local.id_local;
                    option.textContent = `${local.id_local} - ${local.nombre_local}`;
                    select.appendChild(option);
                });

                await cargarConfiguracionSri();
            }catch(error){
                console.error(error);
                select.innerHTML = `<option value="">Error al cargar locales</option>`;
                setConfigStatus("error", "No se pudo cargar la lista de locales");
            }
        }

        async function consultarModulo(){
            resetStatus();
            resultado.textContent = "Consultando modulo...";

            try{
                const res = await fetch(ENDPOINT_INFO);
                const json = await res.json();

                prettyPrint(json);

                if(!res.ok){
                    setStatus("error", json.mensaje || "No se pudo consultar el modulo");
                    return;
                }

                setStatus("success", "Modulo SRI de pruebas disponible");
            }catch(error){
                console.error(error);
                resultado.textContent = String(error);
                setStatus("error", "No se pudo conectar con el backend");
            }
        }

        btnInfo.addEventListener("click", consultarModulo);

        function leerIdLocalConfig(){
            return document.getElementById("config_id_local").value.trim();
        }

        async function cargarConfiguracionSri(){
            resetConfigStatus();
            resultadoConfig.textContent = "Consultando configuracion...";

            const idLocal = leerIdLocalConfig();

            if(!idLocal){
                setConfigStatus("error", "Debes indicar el id_local");
                return;
            }

            try{
                const res = await fetch(`${ENDPOINT_CONFIG}?id_local=${encodeURIComponent(idLocal)}`);
                const raw = await res.text();
                let json = {};

                try{
                    json = raw ? JSON.parse(raw) : {};
                }catch(e){
                    json = { raw };
                }

                prettyPrintConfig(json);

                if(!res.ok){
                    setConfigStatus("error", json.mensaje || "No se pudo cargar la configuracion");
                    return;
                }

                const data = json.data || {};

                document.getElementById("config_ruc").value = data.ruc || "";
                document.getElementById("config_razon_social").value = data.razon_social || "";
                document.getElementById("config_nombre_comercial").value = data.nombre_comercial || "";
                document.getElementById("config_dir_matriz").value = data.dir_matriz || "";
                document.getElementById("config_dir_establecimiento").value = data.dir_establecimiento || "";
                document.getElementById("config_establecimiento").value = data.establecimiento || "001";
                document.getElementById("config_punto_emision").value = data.punto_emision || "001";
                document.getElementById("config_obligado_contabilidad").value = data.obligado_contabilidad || "NO";
                document.getElementById("config_contribuyente_especial").value = data.contribuyente_especial || "";
                document.getElementById("config_ambiente").value = data.ambiente || "PRUEBAS";
                document.getElementById("config_correo_notificacion").value = data.correo_notificacion || "";
                document.getElementById("config_telefono").value = data.telefono || "";
                document.getElementById("config_ruta_certificado").value = data.certificado_path || "";
                document.getElementById("config_clave_certificado").value = "";
                document.getElementById("config_certificado").value = "";

                setConfigStatus("success", data ? "Configuracion cargada correctamente" : "Aun no existe configuracion para ese local");
            }catch(error){
                console.error(error);
                resultadoConfig.textContent = String(error);
                setConfigStatus("error", "No se pudo consultar la configuracion SRI");
            }
        }

        btnCargarConfig.addEventListener("click", cargarConfiguracionSri);
        document.getElementById("config_id_local").addEventListener("change", cargarConfiguracionSri);

        formSri.addEventListener("submit", async (event)=>{
            event.preventDefault();
            resetStatus();

            const clave = document.getElementById("clave_certificado").value.trim();
            const ruta = document.getElementById("ruta_certificado").value.trim();
            const archivo = document.getElementById("certificado").files[0];

            if(!clave){
                setStatus("error", "Debes ingresar la clave del certificado");
                return;
            }

            if(!archivo && !ruta){
                setStatus("error", "Debes subir un archivo o escribir una ruta del certificado");
                return;
            }

            btnProbar.disabled = true;
            btnProbar.textContent = "Probando...";
            resultado.textContent = "Validando certificado...";

            try{
                const formData = new FormData();
                formData.append("clave_certificado", clave);

                if(ruta){
                    formData.append("ruta_certificado", ruta);
                }

                if(archivo){
                    formData.append("certificado", archivo);
                }

                const res = await fetch(ENDPOINT_CERT, {
                    method: "POST",
                    body: formData
                });

                const raw = await res.text();
                let json = {};

                try{
                    json = raw ? JSON.parse(raw) : {};
                }catch(e){
                    json = { raw };
                }

                prettyPrint(json);

                if(!res.ok){
                    setStatus("error", json.mensaje || "La prueba del certificado fallo");
                    return;
                }

                setStatus("success", "Certificado validado correctamente");
            }catch(error){
                console.error(error);
                resultado.textContent = String(error);
                setStatus("error", "No se pudo completar la prueba del certificado");
            }finally{
                btnProbar.disabled = false;
                btnProbar.textContent = "Probar firma";
            }
        });

        formConfigSri.addEventListener("submit", async (event)=>{
            event.preventDefault();
            resetConfigStatus();

            const idLocal = leerIdLocalConfig();

            if(!idLocal){
                setConfigStatus("error", "Debes indicar el id_local");
                return;
            }

            btnGuardarConfig.disabled = true;
            btnGuardarConfig.textContent = "Guardando...";
            resultadoConfig.textContent = "Guardando configuracion SRI...";

            try{
                const formData = new FormData();
                formData.append("id_local", idLocal);
                formData.append("ruc", document.getElementById("config_ruc").value.trim());
                formData.append("razon_social", document.getElementById("config_razon_social").value.trim());
                formData.append("nombre_comercial", document.getElementById("config_nombre_comercial").value.trim());
                formData.append("dir_matriz", document.getElementById("config_dir_matriz").value.trim());
                formData.append("dir_establecimiento", document.getElementById("config_dir_establecimiento").value.trim());
                formData.append("establecimiento", document.getElementById("config_establecimiento").value.trim());
                formData.append("punto_emision", document.getElementById("config_punto_emision").value.trim());
                formData.append("obligado_contabilidad", document.getElementById("config_obligado_contabilidad").value);
                formData.append("contribuyente_especial", document.getElementById("config_contribuyente_especial").value.trim());
                formData.append("ambiente", document.getElementById("config_ambiente").value);
                formData.append("correo_notificacion", document.getElementById("config_correo_notificacion").value.trim());
                formData.append("telefono", document.getElementById("config_telefono").value.trim());

                const rutaConfig = document.getElementById("config_ruta_certificado").value.trim();
                const claveConfig = document.getElementById("config_clave_certificado").value.trim();
                const archivoConfig = document.getElementById("config_certificado").files[0];

                if(rutaConfig){
                    formData.append("ruta_certificado", rutaConfig);
                }

                if(claveConfig){
                    formData.append("clave_certificado", claveConfig);
                }

                if(archivoConfig){
                    formData.append("certificado", archivoConfig);
                }

                const res = await fetch(ENDPOINT_CONFIG, {
                    method: "POST",
                    body: formData
                });

                const raw = await res.text();
                let json = {};

                try{
                    json = raw ? JSON.parse(raw) : {};
                }catch(e){
                    json = { raw };
                }

                prettyPrintConfig(json);

                if(!res.ok){
                    setConfigStatus("error", json.mensaje || "No se pudo guardar la configuracion SRI");
                    return;
                }

                setConfigStatus("success", "Configuracion SRI guardada correctamente");

                if(json.data?.certificado_path){
                    document.getElementById("config_ruta_certificado").value = json.data.certificado_path;
                }

                document.getElementById("config_clave_certificado").value = "";
                document.getElementById("config_certificado").value = "";
            }catch(error){
                console.error(error);
                resultadoConfig.textContent = String(error);
                setConfigStatus("error", "No se pudo guardar la configuracion SRI");
            }finally{
                btnGuardarConfig.disabled = false;
                btnGuardarConfig.textContent = "Guardar configuracion";
            }
        });

        function leerIdVentaXml(){
            const idVenta = document.getElementById("xml_id_venta").value.trim();

            if(!idVenta){
                setXmlStatus("error", "Debes indicar el id de la venta");
                return null;
            }

            return idVenta;
        }

        async function ejecutarAccionFacturaSri({ accion, boton, cargando, exitoDefault, body = null }){
            resetXmlStatus();

            const idVenta = leerIdVentaXml();

            if(!idVenta){
                return;
            }

            const originalText = boton.textContent;
            boton.disabled = true;
            boton.textContent = cargando;
            resultadoXml.textContent = `Procesando ${accion}...`;

            try{
                const res = await fetch(`${ENDPOINT_XML_BASE}/${encodeURIComponent(idVenta)}/${accion}`, {
                    method: "POST",
                    headers: body ? { "Content-Type": "application/json" } : undefined,
                    body: body ? JSON.stringify(body) : undefined
                });

                const raw = await res.text();
                let json = {};

                try{
                    json = raw ? JSON.parse(raw) : {};
                }catch(e){
                    json = { raw };
                }

                prettyPrintXml(json);

                if(!res.ok){
                    setXmlStatus("error", json.mensaje || `No se pudo procesar ${accion}`);
                    return;
                }

                if(json.data?.estado === "RECHAZADO"){
                    setXmlStatus("error", json.data?.error_detalle || json.mensaje || "El SRI rechazo el comprobante");
                    return;
                }

                if(json.data?.pendiente_autorizacion){
                    setXmlStatus("success", "La consulta se hizo correctamente, pero el SRI aun no devuelve una autorizacion");
                    return;
                }

                setXmlStatus("success", json.mensaje || exitoDefault);
            }catch(error){
                console.error(error);
                resultadoXml.textContent = String(error);
                setXmlStatus("error", `No se pudo procesar ${accion} en el SRI`);
            }finally{
                boton.disabled = false;
                boton.textContent = originalText;
            }
        }

        formXmlSri.addEventListener("submit", async (event)=>{
            event.preventDefault();
            await ejecutarAccionFacturaSri({
                accion: "xml",
                boton: btnGenerarXml,
                cargando: "Generando...",
                exitoDefault: "XML de factura generado correctamente"
            });
        });

        btnFirmarXml.addEventListener("click", async ()=>{
            await ejecutarAccionFacturaSri({
                accion: "firmar",
                boton: btnFirmarXml,
                cargando: "Firmando...",
                exitoDefault: "XML firmado correctamente"
            });
        });

        btnEnviarSri.addEventListener("click", async ()=>{
            await ejecutarAccionFacturaSri({
                accion: "enviar",
                boton: btnEnviarSri,
                cargando: "Enviando...",
                exitoDefault: "Recepcion SRI procesada correctamente"
            });
        });

        btnAutorizarSri.addEventListener("click", async ()=>{
            await ejecutarAccionFacturaSri({
                accion: "autorizar",
                boton: btnAutorizarSri,
                cargando: "Consultando...",
                exitoDefault: "Consulta de autorizacion procesada correctamente"
            });
        });

        btnGenerarRide.addEventListener("click", async ()=>{
            await ejecutarAccionFacturaSri({
                accion: "ride",
                boton: btnGenerarRide,
                cargando: "Generando RIDE...",
                exitoDefault: "RIDE generado correctamente"
            });
        });

        btnEnviarCorreo.addEventListener("click", async ()=>{
            const correoDestino = document.getElementById("xml_correo_destino").value.trim();

            await ejecutarAccionFacturaSri({
                accion: "email",
                boton: btnEnviarCorreo,
                cargando: "Enviando correo...",
                exitoDefault: "Factura electrónica enviada correctamente por correo",
                body: {
                    correo_destino: correoDestino || undefined
                }
            });
        });

        consultarModulo();
        cargarLocalesPrueba();
    </script>
</body>
</html>
