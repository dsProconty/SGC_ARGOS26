<?php
if (!isset($_SESSION['id_user'])) { echo "<meta http-equiv='refresh' content='0; url=index.php'>"; exit; }
$rol_gc = $_SESSION['permisos_acceso'] ?? '';
$es_admin   = ($rol_gc === 'Super Admin');
$es_cliente = in_array($rol_gc, ['cliente_giftcard', 'empresa_cliente']);
?>
<div class="content" data-layout="tabbed">
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">GIFT CARDS</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Gift Cards</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex" style="gap:8px;">
                    <?php if ($es_admin): ?>
                    <button class="btn btn-info" onclick="$('#modal_nuevo_lote').modal('show')" style="color:#fff;">
                        <i class="icon dripicons-plus"></i> Nuevo Lote
                    </button>
                    <?php endif; ?>
                    <?php if ($es_cliente): ?>
                    <button class="btn btn-success" onclick="$('#modal_solicitar').modal('show')" style="color:#fff;">
                        <i class="icon dripicons-mail"></i> Solicitar Códigos
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <section class="page-content container-fluid">

        <?php if ($es_admin): ?>
        <!-- ══ PANEL ADMIN: Aprobaciones Pendientes ══ -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="icon dripicons-bell text-warning"></i> Aprobaciones Pendientes</span>
                        <button class="btn btn-sm btn-outline-secondary" onclick="cargarSolicitudes()">
                            <i class="icon dripicons-clockwise"></i> Actualizar
                        </button>
                    </h5>
                    <div class="card-body">
                        <div id="loader_solicitudes"><div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ PANEL ADMIN: Lotes creados ══ -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header">Lotes de Gift Cards</h5>
                    <div class="card-body">
                        <div id="loader_lotes"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($es_cliente): ?>
        <!-- ══ PANEL CLIENTE: Mis Solicitudes ══ -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="icon dripicons-list"></i> Mis Solicitudes</span>
                        <button class="btn btn-sm btn-outline-secondary" onclick="cargarMisSolicitudes()">
                            <i class="icon dripicons-clockwise"></i> Actualizar
                        </button>
                    </h5>
                    <div class="card-body">
                        <div id="loader_mis_solicitudes"><div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span></div></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </section>
</div>

<!-- ══ MODAL: Ver Códigos ══ -->
<div class="modal fade" id="modal_codigos" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-list"></i> <span id="titulo_codigos">Códigos del Lote</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="loader_codigos"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btn_exportar_excel" style="color:#fff;">
                    <i class="icon dripicons-download"></i> Exportar Excel
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php if ($es_admin): ?>
<!-- ══ MODAL: Nuevo Lote (Admin) ══ -->
<div class="modal fade" id="modal_nuevo_lote" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-plus"></i> Nuevo Lote de Gift Cards</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_lote"></div>
                <div class="form-group">
                    <label>Cantidad de códigos</label>
                    <input type="number" class="form-control" id="lgc_cantidad" min="1" max="1000" placeholder="Ej: 50">
                </div>
                <div class="form-group">
                    <label>Cupo por código ($)</label>
                    <input type="number" class="form-control" id="lgc_cupo_codigo" step="0.01" min="0.01" placeholder="Ej: 25.00">
                </div>
                <div class="form-group">
                    <label>Período de facturación</label>
                    <input type="date" class="form-control" id="lgc_periodo_facturacion">
                </div>
                <div class="form-group">
                    <label>Fecha de caducidad de los códigos</label>
                    <input type="date" class="form-control" id="lgc_fecha_caducidad">
                    <small class="text-muted">Después de esta fecha los códigos no podrán usarse.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btn_crear_lote" style="color:#fff;">
                    <i class="icon dripicons-checkmark"></i> Crear Lote
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: Preview + Aprobar/Rechazar ══ -->
<div class="modal fade" id="modal_preview_sol" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" id="preview_header">
                <h5 class="modal-title" id="preview_titulo">Revisar Solicitud</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_preview"></div>
                <input type="hidden" id="preview_sol_id">
                <input type="hidden" id="preview_accion">
                <div id="preview_datos" class="mb-3"></div>
                <div class="form-group">
                    <label id="preview_notas_label">Notas / Observaciones</label>
                    <textarea class="form-control" id="preview_notas" rows="3" placeholder="Opcional..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="btn_confirmar_accion" style="color:#fff; min-width:130px;">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: Historial de Auditoría ══ -->
<div class="modal fade" id="modal_historial" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-clock"></i> Historial de Auditoría</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="historial_body">
                <div class="text-center"><span class="spinner-border spinner-border-sm"></span></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($es_cliente): ?>
<!-- ══ MODAL: Solicitar Códigos (Cliente) ══ -->
<div class="modal fade" id="modal_solicitar" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-mail"></i> Solicitar Códigos Gift Card</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_solicitar"></div>
                <div class="alert alert-info py-2 px-3" style="font-size:.85rem;">
                    <i class="icon dripicons-information"></i>
                    Tu solicitud quedará <strong>pendiente de aprobación</strong>. Recibirás una notificación cuando sea procesada.
                </div>
                <div class="form-group">
                    <label>Cantidad de códigos</label>
                    <input type="number" class="form-control" id="sol_cantidad" min="1" max="1000" placeholder="Ej: 50">
                </div>
                <div class="form-group">
                    <label>Cupo por código ($)</label>
                    <input type="number" class="form-control" id="sol_cupo_codigo" step="0.01" min="0.01" placeholder="Ej: 25.00">
                </div>
                <div class="form-group">
                    <label>Período de facturación</label>
                    <input type="date" class="form-control" id="sol_periodo_facturacion">
                </div>
                <div class="form-group">
                    <label>Fecha de caducidad deseada</label>
                    <input type="date" class="form-control" id="sol_fecha_caducidad">
                    <small class="text-muted">Después de esta fecha los códigos no podrán usarse.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn_enviar_solicitud" style="color:#fff;">
                    <i class="icon dripicons-mail"></i> Enviar Solicitud
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="assets/vendor/sheetjs/xlsx.full.min.js"></script>
<script src="js/giftcard.js"></script>
