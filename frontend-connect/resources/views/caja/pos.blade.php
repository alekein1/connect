@extends('caja.layout')

@section('title', 'POS Web')

@section('content')

<style>
.pos-page{
    display:flex;
    flex-direction:column;
    gap:20px;
}

.notice-bar,
.pos-grid,
.panel-pos,
.summary-card{
    background:linear-gradient(180deg,#081225 0%,#07101f 100%);
    border:1px solid rgba(255,255,255,.06);
    border-radius:22px;
    box-shadow:0 18px 45px rgba(0,0,0,.35);
}

.notice-bar{
    padding:18px 20px;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
    flex-wrap:wrap;
}

.notice-title{
    margin:0 0 6px;
    color:#fff;
    font-size:22px;
    font-weight:800;
}

.notice-copy{
    margin:0;
    color:#94a3b8;
    font-size:14px;
    line-height:1.6;
    max-width:760px;
}

.notice-chip{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    background:rgba(59,130,246,.12);
    border:1px solid rgba(96,165,250,.16);
    color:#bfdbfe;
    font-size:12px;
    font-weight:700;
    letter-spacing:.03em;
    text-transform:uppercase;
}

.pos-grid{
    display:grid;
    grid-template-columns:minmax(0, 1.65fr) minmax(340px, .95fr);
    gap:0;
    overflow:hidden;
}

.panel-pos{
    border-radius:0;
    box-shadow:none;
    border:none;
    background:transparent;
    padding:22px;
}

.panel-pos.left{
    border-right:1px solid rgba(255,255,255,.06);
}

.search-stack{
    position:relative;
    display:flex;
    flex-direction:column;
    gap:8px;
    margin-bottom:18px;
}

.search-row{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.search-row input{
    flex:1;
    min-width:220px;
}

.field,
.summary-card{
    background:rgba(255,255,255,.02);
    border:1px solid rgba(255,255,255,.06);
    border-radius:18px;
    padding:16px;
}

.field-title{
    margin:0 0 4px;
    color:#fff;
    font-size:16px;
    font-weight:700;
}

.field-copy{
    margin:0;
    color:#94a3b8;
    font-size:13px;
    line-height:1.5;
}

.input-pro,
.select-pro,
.textarea-pro{
    width:100%;
    background:#0f172a;
    border:1px solid rgba(255,255,255,.08);
    color:#fff;
    border-radius:14px;
    padding:12px 14px;
    font-size:14px;
    outline:none;
}

.textarea-pro{
    resize:vertical;
    min-height:88px;
}

.input-pro:focus,
.select-pro:focus,
.textarea-pro:focus{
    border-color:#60a5fa;
    box-shadow:0 0 0 3px rgba(96,165,250,.18);
}

.btn-pro,
.btn-outline-pro,
.btn-danger-pro,
.btn-ghost-pro{
    border:none;
    border-radius:14px;
    padding:12px 16px;
    font-weight:800;
    cursor:pointer;
    transition:.2s ease;
}

.btn-pro{
    background:linear-gradient(135deg,#f4c842 0%,#d8a910 100%);
    color:#111827;
    box-shadow:0 10px 20px rgba(216,169,16,.18);
}

.btn-outline-pro{
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.08);
    color:#fff;
}

.btn-danger-pro{
    background:linear-gradient(135deg,#ef4444 0%,#b91c1c 100%);
    color:#fff;
    box-shadow:0 10px 22px rgba(239,68,68,.15);
}

.btn-ghost-pro{
    background:transparent;
    border:1px dashed rgba(148,163,184,.25);
    color:#cbd5e1;
}

.btn-pro:hover,
.btn-outline-pro:hover,
.btn-danger-pro:hover,
.btn-ghost-pro:hover{
    transform:translateY(-1px);
    opacity:.97;
}

.btn-pro:disabled,
.btn-outline-pro:disabled,
.btn-danger-pro:disabled,
.btn-ghost-pro:disabled{
    opacity:.65;
    cursor:not-allowed;
    transform:none;
}

.dropdown-results{
    position:absolute;
    top:100%;
    left:0;
    right:0;
    margin-top:6px;
    background:#020617;
    border:1px solid rgba(255,255,255,.07);
    border-radius:16px;
    z-index:25;
    max-height:320px;
    overflow:auto;
    display:none;
}

.dropdown-item{
    width:100%;
    background:transparent;
    border:none;
    text-align:left;
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 14px;
    cursor:pointer;
    border-bottom:1px solid rgba(255,255,255,.05);
}

.dropdown-item:last-child{
    border-bottom:none;
}

.dropdown-item:hover{
    background:rgba(255,255,255,.03);
}

.dropdown-thumb{
    width:46px;
    height:46px;
    border-radius:12px;
    object-fit:cover;
    flex-shrink:0;
    background:#111827;
}

.dropdown-meta{
    display:flex;
    flex-direction:column;
    gap:4px;
    min-width:0;
}

.dropdown-meta strong{
    color:#fff;
    font-size:14px;
}

.dropdown-meta span{
    color:#94a3b8;
    font-size:12px;
}

.table-wrap-pro{
    border:1px solid rgba(255,255,255,.06);
    border-radius:18px;
    overflow-x:auto;
    overflow-y:hidden;
    background:rgba(255,255,255,.02);
    padding-bottom:8px;
    scrollbar-gutter:stable both-edges;
    scrollbar-width:thin;
    scrollbar-color:rgba(59,130,246,.62) rgba(15,23,42,.72);
}

.table-wrap-pro::-webkit-scrollbar{
    height:10px;
}

.table-wrap-pro::-webkit-scrollbar-track{
    background:rgba(15,23,42,.72);
    border-radius:999px;
}

.table-wrap-pro::-webkit-scrollbar-thumb{
    background:rgba(59,130,246,.62);
    border-radius:999px;
    border:2px solid rgba(15,23,42,.72);
}

.table-scroll-hint{
    display:none;
    margin:0 0 10px;
    color:#94a3b8;
    font-size:12px;
    line-height:1.45;
}

.table-pro{
    width:100%;
    min-width:920px;
    border-collapse:collapse;
    table-layout:fixed;
}

.table-pro th{
    text-align:left;
    padding:14px 16px;
    font-size:12px;
    font-weight:800;
    color:#94a3b8;
    text-transform:uppercase;
    letter-spacing:.04em;
    background:rgba(255,255,255,.02);
    border-bottom:1px solid rgba(255,255,255,.06);
}

.table-pro td{
    padding:14px 16px;
    border-bottom:1px solid rgba(255,255,255,.05);
    color:#e5e7eb;
    vertical-align:top;
    font-size:14px;
}

.table-pro tr:last-child td{
    border-bottom:none;
}

.table-pro th:nth-child(1),
.table-pro td:nth-child(1){
    width:40%;
}

.table-pro th:nth-child(2),
.table-pro td:nth-child(2){
    width:14%;
}

.table-pro th:nth-child(3),
.table-pro td:nth-child(3){
    width:14%;
}

.table-pro th:nth-child(4),
.table-pro td:nth-child(4){
    width:14%;
}

.table-pro th:nth-child(5),
.table-pro td:nth-child(5){
    width:18%;
}

.product-main{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
}

.product-info{
    display:flex;
    align-items:flex-start;
    gap:12px;
    min-width:0;
}

.product-info img{
    width:48px;
    height:48px;
    border-radius:12px;
    object-fit:cover;
    background:#111827;
}

.product-text{
    min-width:0;
}

.product-text strong{
    display:block;
    color:#fff;
    white-space:normal;
    overflow:visible;
    text-overflow:clip;
    line-height:1.35;
    margin-bottom:4px;
}

.product-text small{
    color:#64748b;
    display:block;
    line-height:1.45;
}

.product-imei-status{
    display:block;
    margin-top:6px;
    color:#93c5fd;
    font-size:12px;
    line-height:1.45;
    overflow-wrap:anywhere;
    word-break:break-word;
}

.imei-cell{
    display:flex;
    flex-direction:column;
    gap:8px;
    min-width:0;
}

.imei-cell small{
    color:#94a3b8;
    font-size:12px;
    line-height:1.45;
    overflow-wrap:anywhere;
    word-break:break-word;
}

.imei-cell-label{
    display:block;
    color:#94a3b8;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.imei-select{
    min-width:0;
    width:100%;
}

.btn-delete{
    width:34px;
    height:34px;
    border:none;
    border-radius:10px;
    background:rgba(239,68,68,.14);
    color:#fca5a5;
    cursor:pointer;
    font-weight:800;
    flex-shrink:0;
}

.empty-state{
    padding:30px 20px;
    text-align:center;
    color:#94a3b8;
}

.right-stack{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.switch-row{
    display:flex;
    gap:10px;
}

.switch-row button{
    flex:1;
}

.switch-btn{
    background:#111827;
    border:1px solid rgba(255,255,255,.08);
    color:#cbd5e1;
    border-radius:14px;
    padding:11px 12px;
    font-weight:800;
    cursor:pointer;
}

.switch-btn.active{
    background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);
    border-color:transparent;
    color:#fff;
}

.hidden{
    display:none !important;
}

.box-status{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:12px;
    margin-top:14px;
}

.mini-kpi{
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.05);
    border-radius:16px;
    padding:14px;
}

.mini-kpi span{
    display:block;
    color:#94a3b8;
    font-size:12px;
    margin-bottom:8px;
}

.mini-kpi strong{
    display:block;
    color:#fff;
    font-size:22px;
    font-weight:800;
}

.status-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.status-pill.open{
    background:rgba(34,197,94,.14);
    border:1px solid rgba(74,222,128,.2);
    color:#86efac;
}

.status-pill.closed{
    background:rgba(148,163,184,.12);
    border:1px solid rgba(148,163,184,.18);
    color:#cbd5e1;
}

.grid-2{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:12px;
}

.summary-row{
    display:flex;
    justify-content:space-between;
    gap:12px;
    color:#cbd5e1;
    font-size:14px;
    margin-top:8px;
}

.summary-row strong{
    color:#fff;
}

.summary-row.total strong:last-child{
    color:#facc15;
    font-size:24px;
}

.actions-stack{
    display:flex;
    flex-direction:column;
    gap:10px;
    margin-top:16px;
}

.actions-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:10px;
}

.actions-grid .full{
    grid-column:1 / -1;
}

.feedback-box{
    min-height:52px;
    padding:14px 16px;
    border-radius:16px;
    border:1px solid rgba(255,255,255,.06);
    background:rgba(255,255,255,.02);
    color:#cbd5e1;
    font-size:14px;
    line-height:1.5;
}

.feedback-box.error{
    color:#fecaca;
    background:rgba(127,29,29,.28);
    border-color:rgba(248,113,113,.22);
}

.feedback-box.success{
    color:#bbf7d0;
    background:rgba(20,83,45,.3);
    border-color:rgba(74,222,128,.22);
}

.reprint-meta{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.reprint-meta strong{
    color:#fff;
}

.muted{
    color:#94a3b8;
}

.payment-grid{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:10px;
}

.payment-layout{
    display:grid;
    grid-template-columns:minmax(0, 1.25fr) minmax(180px, .75fr);
    gap:12px;
    align-items:start;
}

.preview-modal{
    position:fixed;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:22px;
    background:rgba(2,6,23,.78);
    backdrop-filter:blur(4px);
    z-index:90;
}

.preview-dialog{
    width:min(1160px, 96vw);
    max-height:92vh;
    overflow:hidden;
    display:flex;
    flex-direction:column;
    background:linear-gradient(180deg,#081225 0%,#07101f 100%);
    border:1px solid rgba(255,255,255,.08);
    border-radius:26px;
    box-shadow:0 28px 70px rgba(0,0,0,.42);
}

.preview-head,
.preview-foot{
    padding:18px 22px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    border-bottom:1px solid rgba(255,255,255,.06);
}

.preview-foot{
    border-bottom:none;
    border-top:1px solid rgba(255,255,255,.06);
}

.preview-head-copy{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.preview-head-copy h3{
    margin:0;
    color:#fff;
    font-size:24px;
    font-weight:800;
}

.preview-head-copy p{
    margin:0;
    color:#94a3b8;
    font-size:13px;
    line-height:1.55;
}

.preview-close{
    width:44px;
    height:44px;
    border:none;
    border-radius:14px;
    background:rgba(255,255,255,.05);
    color:#fff;
    font-size:20px;
    cursor:pointer;
    flex-shrink:0;
}

.preview-body{
    padding:20px 22px;
    overflow:auto;
    display:grid;
    grid-template-columns:minmax(0, 1.15fr) minmax(300px, .85fr);
    gap:18px;
}

.preview-stack{
    display:flex;
    flex-direction:column;
    gap:16px;
    min-width:0;
}

.preview-card{
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.06);
    border-radius:20px;
    padding:18px;
}

.preview-card-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:14px;
}

.preview-grid-2{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:12px;
}

.preview-tag{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    background:rgba(59,130,246,.12);
    border:1px solid rgba(96,165,250,.16);
    color:#dbeafe;
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.03em;
}

.preview-helper{
    color:#94a3b8;
    font-size:13px;
    line-height:1.5;
}

.preview-items-list{
    display:flex;
    flex-direction:column;
    gap:12px;
}

.preview-item-row{
    display:grid;
    grid-template-columns:minmax(0, 1.5fr) 90px 110px 120px 150px 38px;
    gap:10px;
    align-items:center;
    padding:12px;
    border-radius:16px;
    background:rgba(15,23,42,.6);
    border:1px solid rgba(255,255,255,.05);
}

.preview-item-main{
    min-width:0;
}

.preview-item-main strong{
    display:block;
    color:#fff;
    font-size:14px;
    margin-bottom:4px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.preview-item-main small{
    display:block;
    color:#94a3b8;
    font-size:12px;
    line-height:1.45;
}

.preview-mini-label{
    display:block;
    color:#94a3b8;
    font-size:11px;
    margin-bottom:6px;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.preview-line-total{
    color:#fff;
    font-size:15px;
    font-weight:800;
}

.preview-row-note{
    color:#94a3b8;
    font-size:12px;
}

.preview-summary-box{
    display:flex;
    flex-direction:column;
    gap:10px;
}

.preview-summary-row{
    display:flex;
    justify-content:space-between;
    gap:12px;
    color:#cbd5e1;
    font-size:14px;
}

.preview-summary-row strong{
    color:#fff;
}

.preview-summary-row.total strong:last-child{
    color:#facc15;
    font-size:24px;
}

.preview-foot-actions{
    display:flex;
    justify-content:flex-end;
    gap:12px;
    width:100%;
}

@media (max-width: 1180px){
    .pos-grid{
        grid-template-columns:1fr;
    }

    .panel-pos.left{
        border-right:none;
        border-bottom:1px solid rgba(255,255,255,.06);
    }
}

@media (max-width: 767.98px){
    .panel-pos{
        padding:16px;
    }

    .table-pro{
        min-width:860px;
    }

    .table-scroll-hint{
        display:block;
    }

    .product-info img{
        width:42px;
        height:42px;
    }

    .empty-state{
        padding:24px 16px;
        text-align:center;
    }

    .box-status,
    .grid-2,
    .preview-grid-2,
    .actions-grid,
    .payment-grid,
    .payment-layout{
        grid-template-columns:1fr;
    }

    .preview-body{
        grid-template-columns:1fr;
        padding:16px;
    }

    .preview-head,
    .preview-foot{
        padding:16px;
    }

    .preview-item-row{
        grid-template-columns:1fr;
    }

    .preview-foot-actions{
        flex-direction:column-reverse;
    }

    .search-row{
        flex-direction:column;
    }
}
</style>

<div class="pos-page">
    <section class="notice-bar">
        <div>
            <div class="notice-chip">Ventas web conectadas al API</div>
            <h2 class="notice-title">Punto de venta web</h2>
            <p class="notice-copy">
                Este flujo usa el mismo backend de ventas y los mismos endpoints del SRI. Si la venta sale sin SRI,
                se abre un ticket imprimible de nota de venta. Si sale con SRI y la factura queda autorizada,
                se abre el RIDE en una pestaña nueva para reimpresión.
            </p>
        </div>
        <div class="summary-card" style="max-width:360px;">
            <p class="field-title">Último comprobante</p>
            <div id="lastSaleMeta" class="reprint-meta muted">
                <span>Aún no hay una venta emitida en esta sesión.</span>
            </div>
            <div class="actions-stack" style="margin-top:14px;">
                <button id="btnReprintLast" type="button" class="btn-outline-pro" disabled>Reimprimir último comprobante</button>
            </div>
        </div>
    </section>

    <section class="pos-grid">
        <div class="panel-pos left">
            <div class="search-stack">
                <div class="search-row">
                    <input id="inputBuscar" class="input-pro" placeholder="Escanea o busca por nombre, código, SKU o IMEI">
                    <button id="btnBuscarManual" type="button" class="btn-outline-pro">Buscar</button>
                </div>
                <div id="dropdownResultados" class="dropdown-results"></div>
            </div>

            <div class="table-wrap-pro">
                <p class="table-scroll-hint">Desliza la tabla a la izquierda o derecha para ver cantidad, precio, subtotal e IMEI.</p>
                <table class="table-pro">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th>IMEI</th>
                        </tr>
                    </thead>
                    <tbody id="detalleVenta">
                        <tr>
                            <td colspan="5" class="empty-state">Agrega productos para empezar la venta.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-pos">
            <div class="right-stack">
                <div class="field">
                    <p class="field-title">Tipo de comprobante</p>
                    <div class="switch-row">
                        <button id="btnConsumidor" type="button" class="switch-btn active">Consumidor final</button>
                        <button id="btnFactura" type="button" class="switch-btn">Factura</button>
                    </div>
                </div>

                <div id="clienteBox" class="field hidden">
                    <p class="field-title">Datos del cliente</p>
                    <div class="grid-2">
                        <div>
                            <label class="muted">Cédula / RUC</label>
                            <input id="clienteCedula" class="input-pro" placeholder="Documento">
                        </div>
                        <div>
                            <label class="muted">Nombre</label>
                            <input id="clienteNombre" class="input-pro" placeholder="Nombre completo">
                        </div>
                        <div>
                            <label class="muted">Correo</label>
                            <input id="clienteCorreo" type="email" class="input-pro" placeholder="correo@cliente.com">
                        </div>
                        <div>
                            <label class="muted">Teléfono</label>
                            <input id="clienteTelefono" class="input-pro" placeholder="Teléfono">
                        </div>
                    </div>
                    <div style="margin-top:12px;">
                        <label class="muted">Dirección</label>
                        <textarea id="clienteDireccion" class="textarea-pro" placeholder="Dirección fiscal"></textarea>
                    </div>
                </div>

                <div class="field">
                    <p class="field-title">Tipo de venta</p>
                    <div class="grid-2">
                        <div>
                            <label class="muted">Modalidad</label>
                            <select id="tipoVenta" class="select-pro">
                                <option value="CONTADO">Contado</option>
                                <option value="FINANCIADO">Financiado</option>
                            </select>
                        </div>
                        <div>
                            <label class="muted">Aumento por producto</label>
                            <input id="recargoFinanciamiento" type="number" min="0" step="0.01" class="input-pro" placeholder="Ej: 5.00">
                        </div>
                    </div>

                    <div id="financiamientoBox" class="grid-2 hidden" style="margin-top:12px;">
                        <div>
                            <label class="muted">Entrada</label>
                            <input id="entradaFinanciamiento" type="number" min="0" step="0.01" class="input-pro" placeholder="Monto entrada">
                        </div>
                        <div>
                            <label class="muted">Cuotas</label>
                            <input id="cuotasFinanciamiento" type="number" min="1" step="1" class="input-pro" placeholder="Número de cuotas">
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="muted">Proveedor</label>
                            <select id="proveedorFinanciamiento" class="select-pro">
                                <option value="PAYJOY">PAYJOY</option>
                                <option value="HAPPY">HAPPY</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <p class="field-title">Pago y descuento</p>
                    <div class="payment-layout">
                        <div>
                            <label class="muted">Tipo de pago</label>
                            <select id="formaPago" class="select-pro" style="margin-top:10px;">
                                <option value="EFECTIVO">Contado</option>
                                <option value="TARJETA">Tarjeta</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                            </select>
                        </div>
                        <div>
                            <label class="muted">Monto pago</label>
                            <input id="montoPago" class="input-pro" placeholder="Se calcula automáticamente" readonly>
                        </div>
                    </div>
                    <div class="grid-2" style="margin-top:12px;">
                        <div>
                            <label class="muted">Descuento</label>
                            <input id="descuentoInput" type="number" min="0" step="0.01" class="input-pro" placeholder="0.00">
                        </div>
                        <div>
                            <label class="muted">Motivo descuento</label>
                            <input id="motivoDescuentoInput" class="input-pro" placeholder="Motivo opcional">
                        </div>
                    </div>
                </div>

                <div class="summary-card">
                    <p class="field-title">Resumen de venta</p>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong id="resumenSubtotal">$0.00</strong>
                    </div>
                    <div class="summary-row">
                        <span>Descuento</span>
                        <strong id="resumenDescuento">$0.00</strong>
                    </div>
                    <div class="summary-row">
                        <span>Entrada crédito</span>
                        <strong id="resumenEntrada">$0.00</strong>
                    </div>
                    <div class="summary-row">
                        <span>Saldo crédito</span>
                        <strong id="resumenSaldo">$0.00</strong>
                    </div>
                    <div class="summary-row total" style="margin-top:14px;">
                        <strong>Total</strong>
                        <strong id="resumenTotal">$0.00</strong>
                    </div>

                    <div class="actions-stack">
                        <button id="btnVentaNormal" type="button" class="btn-pro">🧾 Nota de venta</button>
                        <div class="actions-grid">
                            <button id="btnCredito" type="button" class="btn-outline-pro">💳 Crédito</button>
                            <button id="btnSri" type="button" class="btn-outline-pro">📄 Emitir con SRI</button>
                            <button id="btnLimpiarVenta" type="button" class="btn-ghost-pro full">Limpiar formulario</button>
                        </div>
                    </div>
                </div>

                <div id="feedbackBox" class="feedback-box">
                    Usa el buscador para agregar productos y emite la venta desde esta misma pantalla.
                </div>
            </div>
        </div>
    </section>
</div>

<div id="previewVentaModal" class="preview-modal hidden" aria-hidden="true">
    <div class="preview-dialog" role="dialog" aria-modal="true" aria-labelledby="previewVentaTitle">
        <div class="preview-head">
            <div class="preview-head-copy">
                <h3 id="previewVentaTitle">Previsualización de venta</h3>
                <p>Revisa y ajusta los datos antes de aprobar la venta o cancelarla.</p>
            </div>
            <button id="btnClosePreviewVenta" type="button" class="preview-close" aria-label="Cerrar previsualización">✕</button>
        </div>

        <div class="preview-body">
            <div class="preview-stack">
                <div class="preview-card">
                    <div class="preview-card-head">
                        <div>
                            <span id="previewModoTag" class="preview-tag">Nota de venta</span>
                        </div>
                        <span id="previewItemsCount" class="muted">0 productos</span>
                    </div>

                    <div class="switch-row">
                        <button id="previewConsumidorBtn" type="button" class="switch-btn active">Consumidor final</button>
                        <button id="previewFacturaBtn" type="button" class="switch-btn">Factura</button>
                    </div>
                </div>

                <div id="previewClienteCard" class="preview-card hidden">
                    <p class="field-title">Datos del cliente</p>
                    <div class="preview-grid-2">
                        <div>
                            <label class="muted">Cédula / RUC</label>
                            <input id="previewClienteCedula" class="input-pro" placeholder="Documento">
                        </div>
                        <div>
                            <label class="muted">Nombre</label>
                            <input id="previewClienteNombre" class="input-pro" placeholder="Nombre completo">
                        </div>
                        <div>
                            <label class="muted">Correo</label>
                            <input id="previewClienteCorreo" type="email" class="input-pro" placeholder="correo@cliente.com">
                        </div>
                        <div>
                            <label class="muted">Teléfono</label>
                            <input id="previewClienteTelefono" class="input-pro" placeholder="Teléfono">
                        </div>
                    </div>
                    <div style="margin-top:12px;">
                        <label class="muted">Dirección</label>
                        <textarea id="previewClienteDireccion" class="textarea-pro" placeholder="Dirección fiscal"></textarea>
                    </div>
                </div>

                <div class="preview-card">
                    <p class="field-title">Configuración de venta</p>
                    <div class="preview-grid-2">
                        <div>
                            <label class="muted">Modalidad</label>
                            <select id="previewTipoVenta" class="select-pro">
                                <option value="CONTADO">Contado</option>
                                <option value="FINANCIADO">Financiado</option>
                            </select>
                        </div>
                        <div>
                            <label class="muted">Aumento por producto</label>
                            <input id="previewRecargoFinanciamiento" type="number" min="0" step="0.01" class="input-pro" placeholder="Ej: 5.00">
                        </div>
                    </div>

                    <div id="previewFinanciamientoBox" class="preview-grid-2 hidden" style="margin-top:12px;">
                        <div>
                            <label class="muted">Entrada</label>
                            <input id="previewEntradaFinanciamiento" type="number" min="0" step="0.01" class="input-pro" placeholder="Monto entrada">
                        </div>
                        <div>
                            <label class="muted">Cuotas</label>
                            <input id="previewCuotasFinanciamiento" type="number" min="1" step="1" class="input-pro" placeholder="Número de cuotas">
                        </div>
                        <div style="grid-column:1 / -1;">
                            <label class="muted">Proveedor</label>
                            <select id="previewProveedorFinanciamiento" class="select-pro">
                                <option value="PAYJOY">PAYJOY</option>
                                <option value="HAPPY">HAPPY</option>
                            </select>
                        </div>
                    </div>

                    <div class="payment-layout" style="margin-top:12px;">
                        <div>
                            <label class="muted">Tipo de pago</label>
                            <select id="previewFormaPago" class="select-pro" style="margin-top:10px;">
                                <option value="EFECTIVO">Contado</option>
                                <option value="TARJETA">Tarjeta</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                            </select>
                        </div>
                        <div>
                            <label class="muted">Monto pago</label>
                            <input id="previewMontoPago" class="input-pro" readonly>
                        </div>
                    </div>

                    <div class="preview-grid-2" style="margin-top:12px;">
                        <div>
                            <label class="muted">Descuento</label>
                            <input id="previewDescuento" type="number" min="0" step="0.01" class="input-pro" placeholder="0.00">
                        </div>
                        <div>
                            <label class="muted">Motivo descuento</label>
                            <input id="previewMotivoDescuento" class="input-pro" placeholder="Motivo opcional">
                        </div>
                    </div>
                </div>

                <div class="preview-card">
                    <div class="preview-card-head">
                        <p class="field-title" style="margin:0;">Productos</p>
                        <span class="preview-helper">Puedes ajustar cantidad, IMEI y quitar filas antes de aprobar.</span>
                    </div>
                    <div id="previewItemsList" class="preview-items-list"></div>
                </div>
            </div>

            <div class="preview-stack">
                <div class="preview-card">
                    <p class="field-title">Resumen final</p>
                    <div class="preview-summary-box">
                        <div class="preview-summary-row">
                            <span>Subtotal</span>
                            <strong id="previewResumenSubtotal">$0.00</strong>
                        </div>
                        <div class="preview-summary-row">
                            <span>Descuento</span>
                            <strong id="previewResumenDescuento">$0.00</strong>
                        </div>
                        <div class="preview-summary-row">
                            <span>Entrada crédito</span>
                            <strong id="previewResumenEntrada">$0.00</strong>
                        </div>
                        <div class="preview-summary-row">
                            <span>Saldo crédito</span>
                            <strong id="previewResumenSaldo">$0.00</strong>
                        </div>
                        <div class="preview-summary-row total" style="margin-top:10px;">
                            <strong>Total</strong>
                            <strong id="previewResumenTotal">$0.00</strong>
                        </div>
                    </div>
                </div>

                <div class="preview-card">
                    <p class="field-title">Acción al aprobar</p>
                    <p id="previewActionCopy" class="preview-helper" style="margin:0;">
                        Se guardará la venta y luego se emitirá el comprobante correspondiente.
                    </p>
                </div>
            </div>
        </div>

        <div class="preview-foot">
            <div class="preview-foot-actions">
                <button id="btnCancelPreviewVenta" type="button" class="btn-ghost-pro">Cancelar</button>
                <button id="btnConfirmPreviewVenta" type="button" class="btn-pro">Confirmar y vender</button>
            </div>
        </div>
    </div>
</div>

<script>
const API = "{{ env('API_URL') }}";
const API_BASE = String(API || "").replace(/\/api\/?$/, "");
const IMG = `${API_BASE}/api/uploads/productos/`;
const TOKEN = localStorage.getItem("token");
const LAST_SALE_KEY = "pos_web_last_sale";

if (!TOKEN) {
    window.location.href = "/login";
}

let carrito = [];
let resultadosBusqueda = [];
let cajaActual = null;
let ventaPreviewState = null;

const inputBuscar = document.getElementById("inputBuscar");
const btnBuscarManual = document.getElementById("btnBuscarManual");
const dropdownResultados = document.getElementById("dropdownResultados");
const detalleVenta = document.getElementById("detalleVenta");
const btnConsumidor = document.getElementById("btnConsumidor");
const btnFactura = document.getElementById("btnFactura");
const clienteBox = document.getElementById("clienteBox");
const clienteCedula = document.getElementById("clienteCedula");
const clienteNombre = document.getElementById("clienteNombre");
const clienteCorreo = document.getElementById("clienteCorreo");
const clienteDireccion = document.getElementById("clienteDireccion");
const clienteTelefono = document.getElementById("clienteTelefono");
const tipoVentaSelect = document.getElementById("tipoVenta");
const financiamientoBox = document.getElementById("financiamientoBox");
const recargoFinanciamientoInput = document.getElementById("recargoFinanciamiento");
const entradaFinanciamientoInput = document.getElementById("entradaFinanciamiento");
const cuotasFinanciamientoInput = document.getElementById("cuotasFinanciamiento");
const proveedorFinanciamientoSelect = document.getElementById("proveedorFinanciamiento");
const pagoSelect = document.getElementById("formaPago");
const montoInput = document.getElementById("montoPago");
const descuentoInput = document.getElementById("descuentoInput");
const motivoDescuentoInput = document.getElementById("motivoDescuentoInput");
const btnVentaNormal = document.getElementById("btnVentaNormal");
const btnCredito = document.getElementById("btnCredito");
const btnSri = document.getElementById("btnSri");
const btnLimpiarVenta = document.getElementById("btnLimpiarVenta");
const feedbackBox = document.getElementById("feedbackBox");
const btnReprintLast = document.getElementById("btnReprintLast");
const lastSaleMeta = document.getElementById("lastSaleMeta");
const resumenSubtotal = document.getElementById("resumenSubtotal");
const resumenDescuento = document.getElementById("resumenDescuento");
const resumenEntrada = document.getElementById("resumenEntrada");
const resumenSaldo = document.getElementById("resumenSaldo");
const resumenTotal = document.getElementById("resumenTotal");
const previewVentaModal = document.getElementById("previewVentaModal");
const btnClosePreviewVenta = document.getElementById("btnClosePreviewVenta");
const btnCancelPreviewVenta = document.getElementById("btnCancelPreviewVenta");
const btnConfirmPreviewVenta = document.getElementById("btnConfirmPreviewVenta");
const previewVentaTitle = document.getElementById("previewVentaTitle");
const previewModoTag = document.getElementById("previewModoTag");
const previewItemsCount = document.getElementById("previewItemsCount");
const previewConsumidorBtn = document.getElementById("previewConsumidorBtn");
const previewFacturaBtn = document.getElementById("previewFacturaBtn");
const previewClienteCard = document.getElementById("previewClienteCard");
const previewClienteCedula = document.getElementById("previewClienteCedula");
const previewClienteNombre = document.getElementById("previewClienteNombre");
const previewClienteCorreo = document.getElementById("previewClienteCorreo");
const previewClienteTelefono = document.getElementById("previewClienteTelefono");
const previewClienteDireccion = document.getElementById("previewClienteDireccion");
const previewTipoVenta = document.getElementById("previewTipoVenta");
const previewRecargoFinanciamiento = document.getElementById("previewRecargoFinanciamiento");
const previewFinanciamientoBox = document.getElementById("previewFinanciamientoBox");
const previewEntradaFinanciamiento = document.getElementById("previewEntradaFinanciamiento");
const previewCuotasFinanciamiento = document.getElementById("previewCuotasFinanciamiento");
const previewProveedorFinanciamiento = document.getElementById("previewProveedorFinanciamiento");
const previewFormaPago = document.getElementById("previewFormaPago");
const previewMontoPago = document.getElementById("previewMontoPago");
const previewDescuento = document.getElementById("previewDescuento");
const previewMotivoDescuento = document.getElementById("previewMotivoDescuento");
const previewItemsList = document.getElementById("previewItemsList");
const previewResumenSubtotal = document.getElementById("previewResumenSubtotal");
const previewResumenDescuento = document.getElementById("previewResumenDescuento");
const previewResumenEntrada = document.getElementById("previewResumenEntrada");
const previewResumenSaldo = document.getElementById("previewResumenSaldo");
const previewResumenTotal = document.getElementById("previewResumenTotal");
const previewActionCopy = document.getElementById("previewActionCopy");

function buildApiUrl(value = "") {
    if (!value) return "";
    if (/^https?:\/\//i.test(value)) return value;
    return `${API_BASE}${value.startsWith("/") ? value : `/${value}`}`;
}

async function apiFetch(url, options = {}) {
    const headers = {
        Accept: "application/json",
        Authorization: `Bearer ${TOKEN}`,
        ...(options.headers || {})
    };

    const response = await fetch(url, {
        ...options,
        headers
    });

    const data = await response.json().catch(() => ({
        ok: false,
        mensaje: "La respuesta del servidor no fue válida"
    }));

    if (!response.ok || data.ok === false) {
        throw new Error(data?.mensaje || "No se pudo completar la solicitud");
    }

    return data;
}

function escapeHtml(value = "") {
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function money(value) {
    return `$${Number(value || 0).toFixed(2)}`;
}

function getPaymentLabel(value = "EFECTIVO") {
    const labels = {
        EFECTIVO: "Contado",
        TARJETA: "Tarjeta",
        TRANSFERENCIA: "Transferencia"
    };

    return labels[String(value || "EFECTIVO").toUpperCase()] || "Contado";
}

function getPreviewModeConfig(mode = "NORMAL") {
    const normalized = String(mode || "NORMAL").toUpperCase();

    if (normalized === "SRI") {
        return {
            tag: "Factura SRI",
            title: "Previsualización de factura SRI",
            action: "Se guardará la venta y luego continuará el flujo del SRI para autorización, correo y RIDE.",
            confirmLabel: "Confirmar y emitir con SRI"
        };
    }

    if (normalized === "CREDITO") {
        return {
            tag: "Crédito",
            title: "Previsualización de crédito",
            action: "Se guardará el crédito interno y luego podrás imprimir o reimprimir el comprobante.",
            confirmLabel: "Confirmar y guardar crédito"
        };
    }

    return {
        tag: "Nota de venta",
        title: "Previsualización de nota de venta",
        action: "Se guardará la nota de venta interna y luego podrás imprimir o guardar el comprobante.",
        confirmLabel: "Confirmar y guardar venta"
    };
}

function showFeedback(message, type = "default") {
    feedbackBox.className = "feedback-box";
    if (type === "error") {
        feedbackBox.classList.add("error");
    }
    if (type === "success") {
        feedbackBox.classList.add("success");
    }
    feedbackBox.textContent = message;
}

function setTipoComprobante(esFactura) {
    btnFactura.classList.toggle("active", esFactura);
    btnConsumidor.classList.toggle("active", !esFactura);
    clienteBox.classList.toggle("hidden", !esFactura);
}

function esFacturaActiva() {
    return btnFactura.classList.contains("active");
}

function setTipoVenta(tipoVenta) {
    financiamientoBox.classList.toggle("hidden", tipoVenta !== "FINANCIADO");
    render();
}

function setFormaPago(value = "EFECTIVO") {
    const normalized = String(value || "EFECTIVO").toUpperCase();
    pagoSelect.value = normalized;
    render();
}

function setButtonState(button, text, disabled) {
    if (!button) return;
    if (text) {
        button.textContent = text;
    }
    button.disabled = Boolean(disabled);
}

function getPopupWindow(title) {
    const popup = window.open("about:blank", "_blank", "width=460,height=760");

    if (!popup) {
        return null;
    }

    popup.document.write(`
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>${escapeHtml(title)}</title>
            <style>
                body{
                    margin:0;
                    min-height:100vh;
                    display:grid;
                    place-items:center;
                    background:#0f172a;
                    color:#fff;
                    font-family:Arial,sans-serif;
                }
                .box{
                    padding:28px;
                    border-radius:18px;
                    background:#111827;
                    border:1px solid rgba(255,255,255,.08);
                    text-align:center;
                    width:min(92vw, 420px);
                }
            </style>
        </head>
        <body>
            <div class="box">
                <strong>${escapeHtml(title)}</strong>
                <p>Preparando comprobante...</p>
            </div>
        </body>
        </html>
    `);
    popup.document.close();

    return popup;
}

function revokePopupDocumentUrl(popup) {
    const currentUrl = popup?.__printDocumentUrl;
    if (currentUrl) {
        URL.revokeObjectURL(currentUrl);
        popup.__printDocumentUrl = null;
    }
}

function buildTicketHtml(payload) {
    const itemsHtml = (payload.items || [])
        .map((item) => `
            <tr>
                <td class="desc">
                    ${escapeHtml(item.codigo || "")}<br>
                    ${escapeHtml(item.nombre || "")}
                    ${item.imei ? `<br>IMEI: ${escapeHtml(item.imei)}` : ""}
                </td>
                <td class="qty">${escapeHtml(String(item.cantidad || 0))}</td>
                <td class="money">${Number(item.precio || 0).toFixed(2)}</td>
                <td class="money">${Number(item.total || 0).toFixed(2)}</td>
            </tr>
        `)
        .join("");

    return `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>${escapeHtml(payload.titulo || "Nota de venta")}</title>
            <style>
                body{
                    margin:0;
                    font-family:"Courier New",monospace;
                    background:#fff;
                    color:#000;
                }
                .ticket{
                    width:80mm;
                    padding:10px 12px 16px;
                    margin:0 auto;
                    font-size:12px;
                    line-height:1.22;
                }
                .center{text-align:center;}
                .divider{
                    border-top:1px dashed #000;
                    margin:8px 0;
                }
                .row{
                    display:flex;
                    justify-content:space-between;
                    gap:8px;
                }
                .row span:last-child{
                    min-width:80px;
                    text-align:right;
                }
                table{
                    width:100%;
                    border-collapse:collapse;
                    margin-top:6px;
                }
                th,td{
                    padding:2px 0;
                    vertical-align:top;
                }
                th{
                    text-align:left;
                    border-bottom:1px dashed #000;
                }
                .qty{
                    width:40px;
                    text-align:center;
                }
                .money{
                    width:62px;
                    text-align:right;
                }
                .desc{
                    word-break:break-word;
                }
                .strong{
                    font-weight:700;
                }
                @media print{
                    .print-actions{display:none;}
                }
            </style>
        </head>
        <body>
            <div class="ticket">
                <div class="center strong">${escapeHtml(payload.localNombre || "CONNECT")}</div>
                ${payload.localDireccion ? `<div class="center">${escapeHtml(payload.localDireccion)}</div>` : ""}
                ${payload.localTelefono ? `<div class="center">Tel: ${escapeHtml(payload.localTelefono)}</div>` : ""}
                <div class="divider"></div>
                <div class="center strong">${escapeHtml(payload.titulo || "NOTA DE VENTA")}</div>
                ${payload.numeroComprobante ? `<div>No: ${escapeHtml(payload.numeroComprobante)}</div>` : ""}
                <div>Fecha: ${escapeHtml(payload.fecha || "")}</div>
                <div>Tipo venta: ${escapeHtml(payload.tipoVenta || "")}</div>
                <div>Forma pago: ${escapeHtml(payload.formaPagoLabel || getPaymentLabel(payload.formaPago || "EFECTIVO"))}</div>
                <div>Cliente: ${escapeHtml(payload.clienteNombre || "CONSUMIDOR FINAL")}</div>
                ${payload.clienteCedula ? `<div>Documento: ${escapeHtml(payload.clienteCedula)}</div>` : ""}
                ${payload.clienteDireccion ? `<div>Direccion: ${escapeHtml(payload.clienteDireccion)}</div>` : ""}
                ${payload.clienteTelefono ? `<div>Telefono: ${escapeHtml(payload.clienteTelefono)}</div>` : ""}
                ${payload.clienteCorreo ? `<div>Email: ${escapeHtml(payload.clienteCorreo)}</div>` : ""}
                <div class="divider"></div>
                <table>
                    <thead>
                        <tr>
                            <th>Descripcion</th>
                            <th class="qty">Cant</th>
                            <th class="money">P.U.</th>
                            <th class="money">Total</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
                <div class="divider"></div>
                <div class="row"><span>Subtotal</span><span>${Number(payload.subtotal || 0).toFixed(2)}</span></div>
                <div class="row"><span>IVA</span><span>${Number(payload.iva || 0).toFixed(2)}</span></div>
                ${payload.entradaCredito ? `<div class="row"><span>Entrada</span><span>${Number(payload.entradaCredito || 0).toFixed(2)}</span></div>` : ""}
                ${payload.saldoCredito ? `<div class="row"><span>Saldo</span><span>${Number(payload.saldoCredito || 0).toFixed(2)}</span></div>` : ""}
                <div class="row strong"><span>Total</span><span>${Number(payload.total || 0).toFixed(2)}</span></div>
                <div class="divider"></div>
                <div>${escapeHtml(payload.mensajeFinal || "Gracias por su compra")}</div>
                <div class="print-actions" style="margin-top:14px;text-align:center;">
                    <button onclick="window.print()" style="padding:10px 14px;border:none;border-radius:10px;background:#111827;color:#fff;cursor:pointer;">Imprimir</button>
                </div>
            </div>
            <script>
                window.addEventListener("load", () => {
                    setTimeout(() => window.print(), 300);
                });
            <\/script>
        </body>
        </html>
    `;
}

function buildTicketDocumentUrl(payload) {
    const html = buildTicketHtml(payload);
    const blob = new Blob([html], { type: "text/html;charset=utf-8" });
    return URL.createObjectURL(blob);
}

function renderTicketPopup(popup, payload) {
    const documentUrl = buildTicketDocumentUrl(payload);

    if (!popup || popup.closed) {
        const fallback = window.open(documentUrl, "_blank");
        if (!fallback) {
            URL.revokeObjectURL(documentUrl);
            throw new Error("Tu navegador bloqueó el documento imprimible");
        }
        fallback.__printDocumentUrl = documentUrl;
        fallback.focus();
        return fallback;
    }

    revokePopupDocumentUrl(popup);
    popup.__printDocumentUrl = documentUrl;
    popup.location.replace(documentUrl);
    popup.focus();
    return popup;
}

function obtenerPrecioVigente(item) {
    if (item.precioEditado) {
        return Number(item.precio || 0);
    }

    return Number((Number(item.precioBase || 0) + obtenerRecargoFinanciamiento()).toFixed(2));
}

function obtenerSubtotalCarrito() {
    return carrito.reduce((acc, item) => acc + (obtenerPrecioVigente(item) * Number(item.cantidad || 0)), 0);
}

function obtenerDescuentoActual(subtotal) {
    const descuento = Number(descuentoInput.value || 0);
    if (!Number.isFinite(descuento) || descuento <= 0) {
        return 0;
    }
    return Math.min(descuento, subtotal);
}

function obtenerRecargoFinanciamiento() {
    const recargo = Number(recargoFinanciamientoInput.value || 0);
    return Number.isFinite(recargo) && recargo > 0 ? recargo : 0;
}

function limpiarBusqueda() {
    inputBuscar.value = "";
    resultadosBusqueda = [];
    dropdownResultados.innerHTML = "";
    dropdownResultados.style.display = "none";
}

function cambiarCantidad(index, value) {
    const cantidad = Number(value);
    carrito[index].cantidad = Number.isFinite(cantidad) && cantidad > 0 ? cantidad : 1;
    render();
}

function cambiarPrecio(index, value) {
    const precio = Number(value);
    if (!Number.isFinite(precio) || precio < 0) {
        return;
    }
    carrito[index].precio = precio;
    carrito[index].precioEditado = true;
    render();
}

function setIMEI(index, value) {
    carrito[index].imei = value || null;
}

function eliminarProducto(index) {
    carrito.splice(index, 1);
    render();
}

function agregarProducto(producto) {
    const existente = carrito.find((item) => Number(item.id_producto) === Number(producto.id_producto));

    if (existente && !producto.imeis) {
        existente.cantidad += 1;
    } else {
        carrito.push({
            id_producto: producto.id_producto,
            nombre: producto.nombre_producto,
            precioBase: Number(producto.precio_unitario || 0),
            precio: Number(producto.precio_unitario || 0),
            precioEditado: false,
            cantidad: 1,
            imei: producto.imei_encontrado || null,
            imeis: producto.imeis || "",
            imagen: producto.imagen || ""
        });
    }

    render();
}

function renderDropdown() {
    dropdownResultados.innerHTML = "";

    if (!resultadosBusqueda.length) {
        dropdownResultados.style.display = "none";
        return;
    }

    resultadosBusqueda.forEach((prod) => {
        const item = document.createElement("button");
        item.type = "button";
        item.className = "dropdown-item";
        item.innerHTML = `
            <img class="dropdown-thumb" src="${prod.imagen ? IMG + prod.imagen : "https://via.placeholder.com/46"}" alt="">
            <div class="dropdown-meta">
                <strong>${escapeHtml(prod.nombre_producto || "Producto")}</strong>
                <span>$${Number(prod.precio_unitario || 0).toFixed(2)} · Stock ${Number(prod.stock_actual || 0)}${prod.imei_encontrado ? ` · IMEI ${escapeHtml(prod.imei_encontrado)}` : ""}</span>
            </div>
        `;
        item.onclick = () => {
            agregarProducto(prod);
            limpiarBusqueda();
        };
        dropdownResultados.appendChild(item);
    });

    dropdownResultados.style.display = "block";
}

function render() {
    if (!carrito.length) {
        detalleVenta.innerHTML = `
            <tr>
                <td colspan="5" class="empty-state">Agrega productos para empezar la venta.</td>
            </tr>
        `;
    } else {
        detalleVenta.innerHTML = carrito.map((item, index) => {
            const precioVigente = obtenerPrecioVigente(item);
            item.precio = precioVigente;
            const subtotal = Number((precioVigente * Number(item.cantidad || 0)).toFixed(2));
            const imeis = String(item.imeis || "")
                .split(",")
                .map((value) => value.trim())
                .filter(Boolean);

            return `
                <tr>
                    <td>
                        <div class="product-main">
                            <div class="product-info">
                                <img src="${item.imagen ? IMG + item.imagen : "https://via.placeholder.com/48"}" alt="">
                                <div class="product-text">
                                    <strong>${escapeHtml(item.nombre)}</strong>
                                    <small>ID ${escapeHtml(item.id_producto)}</small>
                                    <span class="product-imei-status">
                                        ${
                                            item.imei
                                                ? `IMEI activo: ${escapeHtml(item.imei)}`
                                                : imeis.length
                                                    ? `${imeis.length} IMEI disponible${imeis.length === 1 ? "" : "s"}`
                                                    : "Sin IMEI registrado"
                                        }
                                    </span>
                                </div>
                            </div>
                            <button type="button" class="btn-delete" onclick="eliminarProducto(${index})">✕</button>
                        </div>
                    </td>
                    <td>
                        <input class="input-pro" type="number" min="1" step="1" value="${Number(item.cantidad || 1)}" onchange="cambiarCantidad(${index}, this.value)">
                    </td>
                    <td>
                        ${
                            tipoVentaSelect.value === "FINANCIADO"
                                ? `<input class="input-pro" type="number" min="0" step="0.01" value="${precioVigente.toFixed(2)}" onchange="cambiarPrecio(${index}, this.value)">`
                                : `<strong>${money(precioVigente)}</strong>`
                        }
                    </td>
                    <td><strong>${money(subtotal)}</strong></td>
                    <td>
                        <div class="imei-cell">
                            <span class="imei-cell-label">Selecciona</span>
                            <select class="select-pro imei-select" onchange="setIMEI(${index}, this.value)">
                                <option value="">Sin IMEI</option>
                                ${imeis.map((imei) => `<option value="${escapeHtml(imei)}" ${item.imei === imei ? "selected" : ""}>${escapeHtml(imei)}</option>`).join("")}
                            </select>
                            <small>
                                ${
                                    item.imei
                                        ? `Activo: ${escapeHtml(item.imei)}`
                                        : imeis.length
                                            ? `${imeis.length} disponible${imeis.length === 1 ? "" : "s"}`
                                            : "No registrado"
                                }
                            </small>
                        </div>
                    </td>
                </tr>
            `;
        }).join("");
    }

    const subtotal = Number(obtenerSubtotalCarrito().toFixed(2));
    const descuento = Number(obtenerDescuentoActual(subtotal).toFixed(2));
    const total = Number((subtotal - descuento).toFixed(2));
    const entrada = tipoVentaSelect.value === "FINANCIADO"
        ? Number(Math.max(Number(entradaFinanciamientoInput.value || 0), 0).toFixed(2))
        : total;
    const saldo = tipoVentaSelect.value === "FINANCIADO"
        ? Number(Math.max(total - entrada, 0).toFixed(2))
        : 0;
    const montoPago = tipoVentaSelect.value === "FINANCIADO"
        ? Math.min(entrada, total)
        : total;

    montoInput.value = montoPago > 0 ? montoPago.toFixed(2) : "";
    resumenSubtotal.textContent = money(subtotal);
    resumenDescuento.textContent = money(descuento);
    resumenEntrada.textContent = money(tipoVentaSelect.value === "FINANCIADO" ? entrada : 0);
    resumenSaldo.textContent = money(saldo);
    resumenTotal.textContent = money(total);
}

function buildVentaPreviewState(modoVenta = "NORMAL") {
    const normalizedMode = String(modoVenta || "NORMAL").toUpperCase();
    const forceFactura = normalizedMode === "SRI";
    const forceConsumidor = normalizedMode === "CREDITO";

    return {
        modoVenta: normalizedMode,
        forceFactura,
        forceConsumidor,
        esFactura: forceFactura ? true : forceConsumidor ? false : esFacturaActiva(),
        cliente: {
            cedula: clienteCedula.value.trim(),
            nombres: clienteNombre.value.trim(),
            correo: clienteCorreo.value.trim(),
            direccion: clienteDireccion.value.trim(),
            telefono: clienteTelefono.value.trim()
        },
        tipoVenta: tipoVentaSelect.value || "CONTADO",
        recargo: recargoFinanciamientoInput.value || "",
        entrada: entradaFinanciamientoInput.value || "",
        cuotas: cuotasFinanciamientoInput.value || "",
        proveedor: proveedorFinanciamientoSelect.value || "PAYJOY",
        formaPago: pagoSelect.value || "EFECTIVO",
        descuento: descuentoInput.value || "",
        motivoDescuento: motivoDescuentoInput.value || "",
        items: carrito.map((item) => ({
            ...item
        }))
    };
}

function getPreviewRecargo(state) {
    const recargo = Number(state?.recargo || 0);
    return Number.isFinite(recargo) && recargo > 0 ? recargo : 0;
}

function getPreviewPrice(item, state) {
    if (item.precioEditado) {
        return Number(item.precio || 0);
    }

    return Number((Number(item.precioBase || 0) + getPreviewRecargo(state)).toFixed(2));
}

function getPreviewTotals(state) {
    const subtotal = Number(state.items.reduce((acc, item) => {
        return acc + (getPreviewPrice(item, state) * Number(item.cantidad || 0));
    }, 0).toFixed(2));

    const descuento = Number(Math.min(
        Math.max(Number(state.descuento || 0), 0),
        subtotal
    ).toFixed(2));

    const total = Number((subtotal - descuento).toFixed(2));
    const entrada = state.tipoVenta === "FINANCIADO"
        ? Number(Math.max(Number(state.entrada || 0), 0).toFixed(2))
        : total;
    const saldo = state.tipoVenta === "FINANCIADO"
        ? Number(Math.max(total - entrada, 0).toFixed(2))
        : 0;
    const montoPago = state.tipoVenta === "FINANCIADO"
        ? Math.min(entrada, total)
        : total;

    return {
        subtotal,
        descuento,
        total,
        entrada,
        saldo,
        montoPago
    };
}

function renderVentaPreviewItems() {
    if (!ventaPreviewState) {
        previewItemsList.innerHTML = "";
        return;
    }

    if (!ventaPreviewState.items.length) {
        previewItemsList.innerHTML = `<div class="empty-state" style="padding:20px;">No hay productos en la previsualización.</div>`;
        return;
    }

    previewItemsList.innerHTML = ventaPreviewState.items.map((item, index) => {
        const precio = getPreviewPrice(item, ventaPreviewState);
        const subtotal = Number((precio * Number(item.cantidad || 0)).toFixed(2));
        const imeis = String(item.imeis || "")
            .split(",")
            .map((value) => value.trim())
            .filter(Boolean);

        return `
            <div class="preview-item-row">
                <div class="preview-item-main">
                    <strong>${escapeHtml(item.nombre || "Producto")}</strong>
                    <small>ID ${escapeHtml(item.id_producto)}${item.imei ? ` · IMEI activo ${escapeHtml(item.imei)}` : ""}</small>
                </div>
                <div>
                    <span class="preview-mini-label">Cantidad</span>
                    <input type="number" min="1" step="1" class="input-pro preview-item-qty" data-preview-index="${index}" value="${Number(item.cantidad || 1)}">
                </div>
                <div>
                    <span class="preview-mini-label">Precio</span>
                    ${
                        ventaPreviewState.tipoVenta === "FINANCIADO"
                            ? `<input type="number" min="0" step="0.01" class="input-pro preview-item-price" data-preview-index="${index}" value="${precio.toFixed(2)}">`
                            : `<div class="preview-line-total">${money(precio)}</div>`
                    }
                </div>
                <div>
                    <span class="preview-mini-label">Subtotal</span>
                    <div class="preview-line-total">${money(subtotal)}</div>
                </div>
                <div>
                    <span class="preview-mini-label">IMEI</span>
                    ${
                        imeis.length
                            ? `
                                <select class="select-pro preview-item-imei" data-preview-index="${index}">
                                    <option value="">Sin IMEI</option>
                                    ${imeis.map((imei) => `<option value="${escapeHtml(imei)}" ${item.imei === imei ? "selected" : ""}>${escapeHtml(imei)}</option>`).join("")}
                                </select>
                            `
                            : `<div class="preview-row-note">Sin IMEI</div>`
                    }
                </div>
                <button type="button" class="btn-delete preview-remove" data-preview-remove="${index}" aria-label="Quitar producto">✕</button>
            </div>
        `;
    }).join("");
}

function renderVentaPreview() {
    if (!ventaPreviewState) {
        return;
    }

    const config = getPreviewModeConfig(ventaPreviewState.modoVenta);
    const totals = getPreviewTotals(ventaPreviewState);

    previewVentaTitle.textContent = config.title;
    previewModoTag.textContent = config.tag;
    previewActionCopy.textContent = config.action;
    btnConfirmPreviewVenta.textContent = config.confirmLabel;
    previewItemsCount.textContent = `${ventaPreviewState.items.length} producto${ventaPreviewState.items.length === 1 ? "" : "s"}`;

    previewConsumidorBtn.classList.toggle("active", !ventaPreviewState.esFactura);
    previewFacturaBtn.classList.toggle("active", ventaPreviewState.esFactura);
    previewConsumidorBtn.disabled = Boolean(ventaPreviewState.forceFactura);
    previewFacturaBtn.disabled = Boolean(ventaPreviewState.forceConsumidor);

    previewClienteCard.classList.toggle("hidden", !ventaPreviewState.esFactura);
    previewTipoVenta.value = ventaPreviewState.tipoVenta;
    previewRecargoFinanciamiento.value = ventaPreviewState.recargo;
    previewEntradaFinanciamiento.value = ventaPreviewState.entrada;
    previewCuotasFinanciamiento.value = ventaPreviewState.cuotas;
    previewProveedorFinanciamiento.value = ventaPreviewState.proveedor;
    previewFormaPago.value = ventaPreviewState.formaPago || "EFECTIVO";
    previewDescuento.value = ventaPreviewState.descuento;
    previewMotivoDescuento.value = ventaPreviewState.motivoDescuento;

    previewClienteCedula.value = ventaPreviewState.cliente.cedula;
    previewClienteNombre.value = ventaPreviewState.cliente.nombres;
    previewClienteCorreo.value = ventaPreviewState.cliente.correo;
    previewClienteTelefono.value = ventaPreviewState.cliente.telefono;
    previewClienteDireccion.value = ventaPreviewState.cliente.direccion;

    previewFinanciamientoBox.classList.toggle("hidden", ventaPreviewState.tipoVenta !== "FINANCIADO");
    previewMontoPago.value = totals.montoPago > 0 ? totals.montoPago.toFixed(2) : "";
    previewResumenSubtotal.textContent = money(totals.subtotal);
    previewResumenDescuento.textContent = money(totals.descuento);
    previewResumenEntrada.textContent = money(ventaPreviewState.tipoVenta === "FINANCIADO" ? totals.entrada : 0);
    previewResumenSaldo.textContent = money(totals.saldo);
    previewResumenTotal.textContent = money(totals.total);
    btnConfirmPreviewVenta.disabled = !ventaPreviewState.items.length;
    renderVentaPreviewItems();
}

function openVentaPreview(modoVenta = "NORMAL") {
    if (!cajaActual) {
        showFeedback("Debes abrir una caja desde la pantalla de Caja antes de vender.", "error");
        return;
    }

    if (!carrito.length) {
        showFeedback("No hay productos en el carrito.", "error");
        return;
    }

    ventaPreviewState = buildVentaPreviewState(modoVenta);
    renderVentaPreview();
    previewVentaModal.classList.remove("hidden");
    previewVentaModal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeVentaPreview() {
    ventaPreviewState = null;
    previewVentaModal.classList.add("hidden");
    previewVentaModal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
}

function applyVentaPreviewState() {
    if (!ventaPreviewState) {
        return;
    }

    carrito = ventaPreviewState.items.map((item) => ({
        ...item
    }));

    setTipoComprobante(Boolean(ventaPreviewState.esFactura));
    clienteCedula.value = ventaPreviewState.cliente.cedula || "";
    clienteNombre.value = ventaPreviewState.cliente.nombres || "";
    clienteCorreo.value = ventaPreviewState.cliente.correo || "";
    clienteTelefono.value = ventaPreviewState.cliente.telefono || "";
    clienteDireccion.value = ventaPreviewState.cliente.direccion || "";

    tipoVentaSelect.value = ventaPreviewState.tipoVenta;
    recargoFinanciamientoInput.value = ventaPreviewState.recargo || "";
    entradaFinanciamientoInput.value = ventaPreviewState.entrada || "";
    cuotasFinanciamientoInput.value = ventaPreviewState.cuotas || "";
    proveedorFinanciamientoSelect.value = ventaPreviewState.proveedor || "PAYJOY";
    descuentoInput.value = ventaPreviewState.descuento || "";
    motivoDescuentoInput.value = ventaPreviewState.motivoDescuento || "";

    setTipoVenta(ventaPreviewState.tipoVenta);
    setFormaPago(ventaPreviewState.formaPago || "EFECTIVO");
    render();
}

async function buscarProductosPOS(query) {
    const q = String(query || "").trim();

    if (q.length < 2) {
        return [];
    }

    const data = await apiFetch(`${API}/ventas/buscar?q=${encodeURIComponent(q)}`);
    return Array.isArray(data.data) ? data.data : [];
}

async function ejecutarBusquedaManual() {
    const q = inputBuscar.value.trim();

    if (q.length < 2) {
        showFeedback("Escribe al menos dos caracteres para buscar.", "error");
        return;
    }

    try {
        resultadosBusqueda = await buscarProductosPOS(q);
        renderDropdown();
        if (!resultadosBusqueda.length) {
            showFeedback("No se encontraron productos con ese criterio.", "error");
        }
    } catch (error) {
        showFeedback(error.message || "No se pudo buscar productos.", "error");
    }
}

async function verificarCaja() {
    try {
        const data = await apiFetch(`${API}/caja/verificar`);
        cajaActual = data.abierta ? data.caja : null;
        renderCaja();
    } catch (error) {
        cajaActual = null;
        renderCaja();
        showFeedback(error.message || "No se pudo verificar la caja.", "error");
    }
}

function renderCaja() {
    const abierta = Boolean(cajaActual);
    btnVentaNormal.disabled = !abierta;
    btnCredito.disabled = !abierta;
    btnSri.disabled = !abierta;
}

function obtenerIdVentaDesdeRespuesta(data) {
    return (
        data?.data?.id_venta ||
        data?.id_venta ||
        data?.venta?.id_venta ||
        data?.data?.venta?.id_venta ||
        null
    );
}

function obtenerNumeroComprobanteDesdeRespuesta(data) {
    return (
        data?.data?.numero_comprobante ||
        data?.numero_comprobante ||
        data?.venta?.numero_comprobante ||
        data?.data?.venta?.numero_comprobante ||
        ""
    );
}

function obtenerFechaVentaDesdeRespuesta(data) {
    return (
        data?.data?.fecha_venta ||
        data?.fecha_venta ||
        data?.venta?.fecha_venta ||
        data?.data?.venta?.fecha_venta ||
        new Date().toLocaleString("es-EC")
    );
}

function obtenerNumeroDesdeRespuesta(data, keys = []) {
    for (const key of keys) {
        const value =
            data?.[key] ??
            data?.data?.[key] ??
            data?.venta?.[key] ??
            data?.data?.venta?.[key];

        if (value !== undefined && value !== null && value !== "") {
            const parsed = Number(value);
            if (!Number.isNaN(parsed)) {
                return parsed;
            }
        }
    }

    return null;
}

async function obtenerVentaDetalle(idVenta) {
    if (!idVenta) {
        return null;
    }

    try {
        const data = await apiFetch(`${API}/ventas/${idVenta}`);
        return data?.venta || null;
    } catch {
        return null;
    }
}

function obtenerValoresTributarios(dataVenta, totalFallback) {
    const subtotal = obtenerNumeroDesdeRespuesta(dataVenta, ["subtotal", "subtotal_0", "base_imponible"]) ?? totalFallback;
    const iva = obtenerNumeroDesdeRespuesta(dataVenta, ["iva", "impuesto", "impuestos", "valor_iva", "total_iva"]) ?? 0;
    const total = obtenerNumeroDesdeRespuesta(dataVenta, ["total", "total_venta", "importe_total"]) ?? Number((subtotal + iva).toFixed(2));

    return {
        subtotal: Number(subtotal.toFixed(2)),
        iva: Number(iva.toFixed(2)),
        total: Number(total.toFixed(2))
    };
}

function guardarUltimaVenta(payload) {
    localStorage.setItem(LAST_SALE_KEY, JSON.stringify(payload));
    renderUltimaVenta();
}

function obtenerUltimaVenta() {
    try {
        return JSON.parse(localStorage.getItem(LAST_SALE_KEY) || "null");
    } catch {
        return null;
    }
}

function renderUltimaVenta() {
    const lastSale = obtenerUltimaVenta();

    if (!lastSale?.idVenta) {
        btnReprintLast.disabled = true;
        lastSaleMeta.innerHTML = "<span>Aún no hay una venta emitida en esta sesión.</span>";
        return;
    }

    btnReprintLast.disabled = false;
    const modo = lastSale.mode === "SRI" ? "Factura SRI" : lastSale.mode === "CREDITO" ? "Crédito" : "Nota de venta";
    lastSaleMeta.innerHTML = `
        <strong>${escapeHtml(modo)}</strong>
        <span>Venta #${escapeHtml(lastSale.idVenta)} · ${escapeHtml(lastSale.numeroComprobante || "Sin número")}</span>
        ${lastSale.formaPagoLabel ? `<span>Pago: ${escapeHtml(lastSale.formaPagoLabel)}</span>` : ""}
        <span>${escapeHtml(lastSale.fechaVenta || "")}</span>
    `;
}

function resetFormularioVenta() {
    carrito = [];
    limpiarBusqueda();
    inputBuscar.focus();

    clienteCedula.value = "";
    clienteNombre.value = "";
    clienteCorreo.value = "";
    clienteDireccion.value = "";
    clienteTelefono.value = "";

    tipoVentaSelect.value = "CONTADO";
    setTipoVenta("CONTADO");
    recargoFinanciamientoInput.value = "";
    entradaFinanciamientoInput.value = "";
    cuotasFinanciamientoInput.value = "";
    proveedorFinanciamientoSelect.value = "PAYJOY";
    setFormaPago("EFECTIVO");
    montoInput.value = "";
    descuentoInput.value = "";
    motivoDescuentoInput.value = "";
    setTipoComprobante(false);
    render();
}

async function construirTicketPayload({
    data,
    idVenta,
    cliente,
    tipoVenta,
    pagos,
    modoVenta,
    entradaCredito,
    saldoCredito
}) {
    const totalCarrito = carrito.reduce((acc, item) => acc + (obtenerPrecioVigente(item) * Number(item.cantidad || 0)), 0);
    const ventaDetalle = await obtenerVentaDetalle(idVenta);
    const fuente = ventaDetalle || data;
    const valores = obtenerValoresTributarios(fuente, Number(totalCarrito.toFixed(2)));
    const detalleVenta = Array.isArray(fuente?.detalle) ? fuente.detalle : [];
    const items = detalleVenta.length
        ? detalleVenta.map((item) => ({
            codigo: item.id_producto,
            nombre: item.nombre_producto || item.nombre || "",
            cantidad: item.cantidad,
            precio: Number(item.precio_unitario ?? item.precio ?? 0),
            total: Number(item.subtotal ?? item.total ?? 0),
            imei: item.imei || ""
        }))
        : carrito.map((item) => ({
            codigo: item.id_producto,
            nombre: item.nombre,
            cantidad: item.cantidad,
            precio: obtenerPrecioVigente(item),
            total: obtenerPrecioVigente(item) * Number(item.cantidad || 0),
            imei: item.imei || ""
        }));

    return {
        titulo: modoVenta === "CREDITO" ? "RECIBO DE CREDITO" : "TICKET DE NOTA DE VENTA",
        numeroComprobante: obtenerNumeroComprobanteDesdeRespuesta(fuente),
        fecha: obtenerFechaVentaDesdeRespuesta(fuente),
        tipoVenta,
        formaPago: pagos?.[0]?.forma_pago || "EFECTIVO",
        formaPagoLabel: getPaymentLabel(pagos?.[0]?.forma_pago || "EFECTIVO"),
        clienteNombre: cliente?.nombres || "CONSUMIDOR FINAL",
        clienteCedula: cliente?.cedula || "",
        clienteDireccion: cliente?.direccion || "",
        clienteTelefono: cliente?.telefono || "",
        clienteCorreo: cliente?.correo || "",
        localNombre: fuente?.nombre_local || "CONNECT",
        localDireccion: fuente?.local_direccion || "",
        localTelefono: fuente?.local_telefono || "",
        subtotal: valores.subtotal,
        iva: valores.iva,
        total: valores.total,
        entradaCredito,
        saldoCredito,
        items,
        mensajeFinal: "Gracias por su compra"
    };
}

async function enviarComprobantePorCorreo(idVenta) {
    return apiFetch(`${API}/ventas/${idVenta}/comprobante/email`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({})
    });
}

async function postSri(idVenta, action, body, buttonText) {
    setButtonState(btnSri, buttonText, true);
    return apiFetch(`${API}/sri/facturas/${idVenta}/${action}`, {
        method: "POST",
        headers: body ? { "Content-Type": "application/json" } : undefined,
        body: body ? JSON.stringify(body) : undefined
    });
}

function postSriSilencioso(idVenta, action, body = null) {
    return apiFetch(`${API}/sri/facturas/${idVenta}/${action}`, {
        method: "POST",
        headers: body ? { "Content-Type": "application/json" } : undefined,
        body: body ? JSON.stringify(body) : undefined
    });
}

function mensajeSriParaCaja(error, fallback = "La factura quedó pendiente de autorización. Consulta el estado del SRI más tarde.") {
    const raw = String(error?.message || error || "");
    const normalized = raw.toUpperCase();

    if (
        normalized.includes("HTTP 302") ||
        normalized.includes("FETCH FAILED") ||
        normalized.includes("NO SE PUDO CONECTAR") ||
        normalized.includes("NO RESPONDIO") ||
        normalized.includes("TIMEOUT") ||
        normalized.includes("ECONNRESET") ||
        normalized.includes("ETIMEDOUT")
    ) {
        return "El SRI no está respondiendo correctamente en este momento. La venta quedó guardada; consulta la autorización más tarde.";
    }

    if (normalized.includes("CLAVE ACCESO REGISTRADA")) {
        return "La factura ya fue recibida por el SRI. Consulta la autorización más tarde.";
    }

    return raw || fallback;
}

const sriAutorizacionesProgramadas = new Set();
const sriReintentosAutorizacionMs = [30000, 120000, 300000];

function esperar(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function obtenerRideSriSilencioso(idVenta) {
    try {
        const ride = await postSriSilencioso(idVenta, "ride");
        return ride?.data?.ride_url || "";
    } catch {
        return "";
    }
}

function actualizarUltimaVentaSriAutorizada(idVenta, rideUrl = "") {
    const lastSale = obtenerUltimaVenta();

    if (Number(lastSale?.idVenta || 0) !== Number(idVenta || 0)) {
        return;
    }

    guardarUltimaVenta({
        ...lastSale,
        mode: "SRI",
        rideUrl: rideUrl || lastSale.rideUrl || ""
    });
}

function programarConsultaAutorizacionSri(idVenta) {
    const id = Number(idVenta || 0);

    if (!id || sriAutorizacionesProgramadas.has(id)) {
        return;
    }

    sriAutorizacionesProgramadas.add(id);

    (async () => {
        for (const delay of sriReintentosAutorizacionMs) {
            await esperar(delay);

            try {
                const autorizacion = await postSriSilencioso(id, "autorizar");
                const estado = autorizacion?.data?.estado;

                if (estado === "AUTORIZADO") {
                    const rideUrl = await obtenerRideSriSilencioso(id);
                    actualizarUltimaVentaSriAutorizada(id, rideUrl);
                    showFeedback("Factura autorizada automáticamente por el SRI. Ya puedes reimprimir el RIDE.", "success");
                    return;
                }

                if (estado === "RECHAZADO") {
                    showFeedback("El SRI no autorizó la factura. Revisa el detalle en el módulo SRI.", "error");
                    return;
                }
            } catch {
                // El SRI puede estar intermitente; el siguiente intento vuelve a consultar.
            }
        }
    })().finally(() => {
        sriAutorizacionesProgramadas.delete(id);
    });
}

async function procesarSriVenta(idVenta, correoDestino, popupWindow) {
    await postSri(idVenta, "xml", null, "Generando XML...");
    await postSri(idVenta, "firmar", null, "Firmando...");

    const envio = await postSri(idVenta, "enviar", null, "Enviando al SRI...");
    const envioEstado = envio?.data?.estado;
    const envioCodigo = String(envio?.data?.error_codigo || "");
    const envioDetalle = envio?.data?.error_detalle || envio?.mensaje || "";

    if (envioEstado === "RECHAZADO" && envioCodigo !== "43") {
        throw new Error(envioDetalle || "El SRI rechazó la recepción");
    }

    let autorizacion = null;

    try {
        autorizacion = await postSri(idVenta, "autorizar", null, "Consultando autorización...");
    } catch (error) {
        return {
            estado: "PENDIENTE",
            mensaje: mensajeSriParaCaja(error),
            reintentarAutorizacion: true
        };
    }

    if (autorizacion?.data?.pendiente_autorizacion) {
        if (popupWindow && !popupWindow.closed) {
            popupWindow.close();
        }
        return {
            estado: "PENDIENTE",
            mensaje: "Factura enviada al SRI, aún en proceso",
            reintentarAutorizacion: true
        };
    }

    if (autorizacion?.data?.estado !== "AUTORIZADO") {
        throw new Error(autorizacion?.mensaje || "La factura no fue autorizada");
    }

    let email = null;
    let ride = null;
    let avisoCorreo = null;

    if (correoDestino) {
        try {
            email = await postSri(idVenta, "email", {
                correo_destino: correoDestino
            }, "Enviando correo...");
        } catch (error) {
            avisoCorreo = error.message || "No se pudo enviar el correo";
        }
    }

    if (!email?.data?.ride_url) {
        ride = await postSri(idVenta, "ride", null, "Generando RIDE...");
    }

    const rideUrl = email?.data?.ride_url || ride?.data?.ride_url || "";

    if (rideUrl && popupWindow && !popupWindow.closed) {
        popupWindow.location.href = buildApiUrl(rideUrl);
    } else if (rideUrl) {
        window.open(buildApiUrl(rideUrl), "_blank", "noopener,noreferrer");
    } else if (popupWindow && !popupWindow.closed) {
        popupWindow.close();
    }

    return {
        estado: "AUTORIZADO",
        rideUrl,
        avisoCorreo
    };
}

async function reimprimirUltimoComprobante() {
    const lastSale = obtenerUltimaVenta();

    if (!lastSale?.idVenta) {
        showFeedback("No hay un comprobante reciente para reimprimir.", "error");
        return;
    }

    const popup = getPopupWindow("Reimprimiendo comprobante...");

    try {
        if (lastSale.mode === "SRI") {
            const rideUrl = lastSale.rideUrl
                ? buildApiUrl(lastSale.rideUrl)
                : buildApiUrl((await postSri(lastSale.idVenta, "ride", null, "Generando RIDE..."))?.data?.ride_url || "");

            if (!rideUrl) {
                throw new Error("No se pudo obtener el RIDE para reimpresión");
            }

            if (popup && !popup.closed) {
                popup.location.href = rideUrl;
            } else {
                window.open(rideUrl, "_blank", "noopener,noreferrer");
            }
        } else if (lastSale.ticketPayload) {
            renderTicketPopup(popup, lastSale.ticketPayload);
        } else {
            throw new Error("No hay ticket guardado para esta venta");
        }

        showFeedback("Reimpresión lista.", "success");
    } catch (error) {
        if (popup && !popup.closed) {
            popup.close();
        }
        showFeedback(error.message || "No se pudo reimprimir el comprobante.", "error");
    } finally {
        setButtonState(btnSri, "📄 Emitir con SRI", false);
    }
}

async function crearVenta(modoVenta = "NORMAL") {
    let ventaGuardada = false;
    let popupWindow = null;
    let avisoCorreoInterno = null;
    let avisoImpresion = null;

    if (!cajaActual) {
        showFeedback("Debes abrir una caja desde la pantalla de Caja antes de vender.", "error");
        return;
    }

    if (!carrito.length) {
        showFeedback("No hay productos en el carrito.", "error");
        return;
    }

    if (modoVenta !== "SRI") {
        popupWindow = getPopupWindow("Preparando ticket...");
    } else {
        popupWindow = getPopupWindow("Preparando factura SRI...");
    }

    try {
        if (modoVenta === "CREDITO" && esFacturaActiva()) {
            throw new Error("Si el comprobante es factura, debes finalizar con Emitir con SRI");
        }

        if (modoVenta === "SRI") {
            if (!esFacturaActiva()) {
                throw new Error("Emitir con SRI solo está disponible para factura");
            }
            if (!clienteCedula.value.trim()) {
                throw new Error("Debes ingresar cédula o RUC para facturar");
            }
            if (!clienteNombre.value.trim()) {
                throw new Error("Debes ingresar el nombre del cliente");
            }
            if (!clienteDireccion.value.trim()) {
                throw new Error("Debes ingresar la dirección fiscal");
            }
        }

        const tipoVenta = tipoVentaSelect.value;
        const formaPago = pagoSelect.value || "EFECTIVO";
        const formaPagoLabel = getPaymentLabel(formaPago);
        const aumentoPorProducto = obtenerRecargoFinanciamiento();
        const subtotal = obtenerSubtotalCarrito();
        const descuento = obtenerDescuentoActual(subtotal);
        const total = Number((subtotal - descuento).toFixed(2));
        const entradaFinanciamiento = Number(entradaFinanciamientoInput.value || 0);
        const cuotasFinanciamiento = Number(cuotasFinanciamientoInput.value || 0);
        const proveedorFinanciamiento = proveedorFinanciamientoSelect.value || "";
        const montoPago = tipoVenta === "FINANCIADO" ? entradaFinanciamiento : total;
        const saldoCredito = Number(Math.max(total - entradaFinanciamiento, 0).toFixed(2));
        const pagos = [{
            forma_pago: formaPago,
            monto: Number(montoPago.toFixed(2))
        }];

        const clienteActivo = esFacturaActiva();
        const cliente = clienteActivo
            ? {
                cedula: clienteCedula.value.trim(),
                nombres: clienteNombre.value.trim(),
                correo: clienteCorreo.value.trim(),
                direccion: clienteDireccion.value.trim(),
                telefono: clienteTelefono.value.trim()
            }
            : {
                cedula: "9999999999",
                nombres: "CONSUMIDOR FINAL"
            };

        const body = {
            productos: carrito.map((item) => ({
                id_producto: item.id_producto,
                cantidad: item.cantidad,
                imei: item.imei,
                precio_unitario: obtenerPrecioVigente(item),
                precio_final: obtenerPrecioVigente(item),
                aumento_por_producto: aumentoPorProducto
            })),
            pagos,
            tipo_venta: tipoVenta,
            cliente,
            descuento,
            motivo_descuento: motivoDescuentoInput.value.trim() || null,
            aumento_por_producto: aumentoPorProducto
        };

        if (tipoVenta === "FINANCIADO") {
            body.entrada = entradaFinanciamiento;
            body.cuotas = cuotasFinanciamiento;
            body.proveedor = proveedorFinanciamiento;
            body.proveedor_financiamiento = proveedorFinanciamiento;
        }

        setButtonState(btnVentaNormal, modoVenta === "NORMAL" ? "Guardando..." : "🧾 Nota de venta", modoVenta === "NORMAL");
        setButtonState(btnCredito, modoVenta === "CREDITO" ? "Guardando..." : "💳 Crédito", modoVenta === "CREDITO");
        setButtonState(btnSri, modoVenta === "SRI" ? "Guardando venta..." : "📄 Emitir con SRI", modoVenta === "SRI");

        const data = await apiFetch(`${API}/ventas`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(body)
        });

        const idVenta = obtenerIdVentaDesdeRespuesta(data);
        ventaGuardada = Boolean(idVenta);

        if (!idVenta) {
            throw new Error("La venta se guardó sin devolver un id válido");
        }

        if (modoVenta !== "SRI") {
            const ticketPayload = await construirTicketPayload({
                data,
                idVenta,
                cliente,
                tipoVenta,
                pagos,
                modoVenta,
                entradaCredito: tipoVenta === "FINANCIADO" ? entradaFinanciamiento : 0,
                saldoCredito: tipoVenta === "FINANCIADO" ? saldoCredito : 0
            });

            try {
                renderTicketPopup(popupWindow, ticketPayload);
            } catch (error) {
                avisoImpresion = error.message || "No se pudo abrir la ventana de impresión";
            }

            guardarUltimaVenta({
                idVenta,
                mode: modoVenta,
                numeroComprobante: ticketPayload.numeroComprobante,
                fechaVenta: ticketPayload.fecha,
                formaPagoLabel,
                ticketPayload
            });
        }

        if (modoVenta === "NORMAL" && idVenta && clienteActivo && cliente?.correo) {
            try {
                await enviarComprobantePorCorreo(idVenta);
            } catch (error) {
                avisoCorreoInterno = error.message || "No se pudo enviar el comprobante por correo";
            }
        }

        if (modoVenta === "SRI") {
            const sri = await procesarSriVenta(idVenta, cliente?.correo || "", popupWindow);

            guardarUltimaVenta({
                idVenta,
                mode: "SRI",
                numeroComprobante: obtenerNumeroComprobanteDesdeRespuesta(data),
                fechaVenta: obtenerFechaVentaDesdeRespuesta(data),
                formaPagoLabel,
                rideUrl: sri.rideUrl || ""
            });

            if (sri.estado === "PENDIENTE") {
                if (sri.reintentarAutorizacion) {
                    programarConsultaAutorizacionSri(idVenta);
                }
                showFeedback(sri.mensaje || "Venta guardada. La factura fue enviada al SRI y sigue en proceso.", "success");
            } else if (sri.avisoCorreo) {
                showFeedback(`Factura autorizada. El correo no se pudo enviar: ${sri.avisoCorreo}`, "success");
            } else {
                showFeedback("Factura autorizada correctamente y abierta en nueva pestaña.", "success");
            }
        } else if (modoVenta === "CREDITO") {
            showFeedback(
                avisoImpresion
                    ? `Crédito guardado. ${avisoImpresion}. Puedes usar el botón de reimpresión.`
                    : "Crédito guardado y ticket listo para imprimir.",
                "success"
            );
        } else if (avisoCorreoInterno) {
            showFeedback(
                `Venta guardada${avisoImpresion ? `. ${avisoImpresion}. Usa el botón de reimpresión.` : " y ticket listo para imprimir."} El correo no se pudo enviar: ${avisoCorreoInterno}`,
                "success"
            );
        } else if (avisoImpresion) {
            showFeedback(`Venta guardada. ${avisoImpresion}. Puedes usar el botón de reimpresión.`, "success");
        } else {
            showFeedback("Venta guardada y ticket listo para imprimir.", "success");
        }

        await verificarCaja();
        resetFormularioVenta();
    } catch (error) {
        if (popupWindow && !popupWindow.closed) {
            popupWindow.close();
        }

        if (modoVenta === "SRI") {
            const mensajeSri = mensajeSriParaCaja(error, "No se pudo completar el proceso SRI.");
            showFeedback(
                ventaGuardada
                    ? `Venta guardada. ${mensajeSri}`
                    : mensajeSri,
                "error"
            );
        } else {
            showFeedback(error.message || "No se pudo crear la venta.", "error");
        }
    } finally {
        setButtonState(btnVentaNormal, "🧾 Nota de venta", false);
        setButtonState(btnCredito, "💳 Crédito", false);
        setButtonState(btnSri, "📄 Emitir con SRI", false);
        renderCaja();
    }
}

btnConsumidor.addEventListener("click", () => setTipoComprobante(false));
btnFactura.addEventListener("click", () => setTipoComprobante(true));
tipoVentaSelect.addEventListener("change", (event) => setTipoVenta(event.target.value));
recargoFinanciamientoInput.addEventListener("input", () => render());
entradaFinanciamientoInput.addEventListener("input", () => render());
descuentoInput.addEventListener("input", () => render());
btnBuscarManual.addEventListener("click", ejecutarBusquedaManual);
pagoSelect.addEventListener("change", (event) => setFormaPago(event.target.value || "EFECTIVO"));
btnVentaNormal.addEventListener("click", () => openVentaPreview("NORMAL"));
btnCredito.addEventListener("click", () => openVentaPreview("CREDITO"));
btnSri.addEventListener("click", () => openVentaPreview("SRI"));
btnLimpiarVenta.addEventListener("click", resetFormularioVenta);
btnReprintLast.addEventListener("click", reimprimirUltimoComprobante);
btnClosePreviewVenta.addEventListener("click", closeVentaPreview);
btnCancelPreviewVenta.addEventListener("click", closeVentaPreview);
btnConfirmPreviewVenta.addEventListener("click", async () => {
    if (!ventaPreviewState) {
        return;
    }

    const modoVenta = ventaPreviewState.modoVenta;
    applyVentaPreviewState();
    closeVentaPreview();
    await crearVenta(modoVenta);
});

previewConsumidorBtn.addEventListener("click", () => {
    if (!ventaPreviewState || ventaPreviewState.forceFactura) {
        return;
    }
    ventaPreviewState.esFactura = false;
    renderVentaPreview();
});

previewFacturaBtn.addEventListener("click", () => {
    if (!ventaPreviewState || ventaPreviewState.forceConsumidor) {
        return;
    }
    ventaPreviewState.esFactura = true;
    renderVentaPreview();
});

previewTipoVenta.addEventListener("change", (event) => {
    if (!ventaPreviewState) {
        return;
    }
    ventaPreviewState.tipoVenta = event.target.value || "CONTADO";
    renderVentaPreview();
});

previewRecargoFinanciamiento.addEventListener("change", (event) => {
    if (!ventaPreviewState) {
        return;
    }
    ventaPreviewState.recargo = event.target.value || "";
    renderVentaPreview();
});

previewEntradaFinanciamiento.addEventListener("change", (event) => {
    if (!ventaPreviewState) {
        return;
    }
    ventaPreviewState.entrada = event.target.value || "";
    renderVentaPreview();
});

previewCuotasFinanciamiento.addEventListener("change", (event) => {
    if (!ventaPreviewState) {
        return;
    }
    ventaPreviewState.cuotas = event.target.value || "";
});

previewProveedorFinanciamiento.addEventListener("change", (event) => {
    if (!ventaPreviewState) {
        return;
    }
    ventaPreviewState.proveedor = event.target.value || "PAYJOY";
});

previewFormaPago.addEventListener("change", (event) => {
    if (!ventaPreviewState) {
        return;
    }
    ventaPreviewState.formaPago = event.target.value || "EFECTIVO";
});

previewDescuento.addEventListener("change", (event) => {
    if (!ventaPreviewState) {
        return;
    }
    ventaPreviewState.descuento = event.target.value || "";
    renderVentaPreview();
});

previewMotivoDescuento.addEventListener("input", (event) => {
    if (!ventaPreviewState) {
        return;
    }
    ventaPreviewState.motivoDescuento = event.target.value || "";
});

previewClienteCedula.addEventListener("input", (event) => {
    if (ventaPreviewState) ventaPreviewState.cliente.cedula = event.target.value || "";
});
previewClienteNombre.addEventListener("input", (event) => {
    if (ventaPreviewState) ventaPreviewState.cliente.nombres = event.target.value || "";
});
previewClienteCorreo.addEventListener("input", (event) => {
    if (ventaPreviewState) ventaPreviewState.cliente.correo = event.target.value || "";
});
previewClienteTelefono.addEventListener("input", (event) => {
    if (ventaPreviewState) ventaPreviewState.cliente.telefono = event.target.value || "";
});
previewClienteDireccion.addEventListener("input", (event) => {
    if (ventaPreviewState) ventaPreviewState.cliente.direccion = event.target.value || "";
});

previewItemsList.addEventListener("change", (event) => {
    if (!ventaPreviewState) {
        return;
    }

    const index = Number(event.target.dataset.previewIndex);
    if (!Number.isInteger(index) || !ventaPreviewState.items[index]) {
        return;
    }

    const item = ventaPreviewState.items[index];

    if (event.target.classList.contains("preview-item-qty")) {
        const cantidad = Number(event.target.value || 1);
        item.cantidad = Number.isFinite(cantidad) && cantidad > 0 ? cantidad : 1;
        renderVentaPreview();
        return;
    }

    if (event.target.classList.contains("preview-item-price")) {
        const precio = Number(event.target.value || 0);
        item.precio = Number.isFinite(precio) && precio >= 0 ? precio : 0;
        item.precioEditado = true;
        renderVentaPreview();
        return;
    }

    if (event.target.classList.contains("preview-item-imei")) {
        item.imei = event.target.value || null;
    }
});

previewItemsList.addEventListener("click", (event) => {
    if (!ventaPreviewState) {
        return;
    }

    const button = event.target.closest("[data-preview-remove]");
    if (!button) {
        return;
    }

    const index = Number(button.dataset.previewRemove);
    if (!Number.isInteger(index)) {
        return;
    }

    ventaPreviewState.items.splice(index, 1);
    renderVentaPreview();
});

inputBuscar.addEventListener("input", async () => {
    const query = inputBuscar.value.trim();

    if (query.length < 2) {
        dropdownResultados.style.display = "none";
        resultadosBusqueda = [];
        return;
    }

    try {
        resultadosBusqueda = await buscarProductosPOS(query);
        renderDropdown();
    } catch (error) {
        showFeedback(error.message || "No se pudo buscar productos.", "error");
    }
});

inputBuscar.addEventListener("keydown", async (event) => {
    if (event.key !== "Enter") {
        return;
    }

    event.preventDefault();
    const query = inputBuscar.value.trim();

    if (!query) {
        return;
    }

    try {
        if (!resultadosBusqueda.length) {
            resultadosBusqueda = await buscarProductosPOS(query);
        }

        if (resultadosBusqueda.length) {
            agregarProducto(resultadosBusqueda[0]);
            limpiarBusqueda();
        }
    } catch (error) {
        showFeedback(error.message || "No se pudo agregar el producto.", "error");
    }
});

document.addEventListener("click", (event) => {
    if (!inputBuscar.contains(event.target) && !dropdownResultados.contains(event.target) && !btnBuscarManual.contains(event.target)) {
        dropdownResultados.style.display = "none";
    }

    if (event.target === previewVentaModal) {
        closeVentaPreview();
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && ventaPreviewState) {
        closeVentaPreview();
    }
});

window.cambiarCantidad = cambiarCantidad;
window.cambiarPrecio = cambiarPrecio;
window.setIMEI = setIMEI;
window.eliminarProducto = eliminarProducto;

(async function initPosWeb() {
    setTipoComprobante(false);
    setTipoVenta("CONTADO");
    setFormaPago("EFECTIVO");
    renderUltimaVenta();
    await verificarCaja();
    if (!cajaActual) {
        showFeedback("Abre una caja desde la pestaña nueva antes de registrar ventas.", "default");
    }
})();
</script>

@endsection
