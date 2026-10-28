<?php
if (!isset($_SESSION['id_user'])) { echo "<meta http-equiv='refresh' content='0; url=index.php'>"; exit; }
require_once 'config/database.php';
require_once 'helpers/db_compat.php';
$rol_gc   = $_SESSION['permisos_acceso'] ?? '';
// Acepta rol legacy O el perfil nuevo asignado (per_id -> perfil.per_nombre)
$es_admin = (strtolower($rol_gc) === 'super admin');
if (!$es_admin) {
    $qPerfilGC = $mysqli->prepare(
        "SELECT p.per_nombre FROM usuario u JOIN perfil p ON u.per_id = p.per_id WHERE u.id_user = ?"
    );
    $qPerfilGC->bind_param('i', $_SESSION['id_user']);
    $qPerfilGC->execute();
    $rowPerfilGC = $qPerfilGC->get_result()->fetch_assoc();
    if ($rowPerfilGC) {
        $es_admin = strtolower($rowPerfilGC['per_nombre']) === 'super admin';
    }
}
// "Cliente" de Gift Cards: empresa dueña de sus propios códigos, identificada
// por NOMBRE DE PERFIL (no solo por cli_id: ver $es_empresa_staff abajo).
$rolesClienteExterno = ['cliente_giftcard', 'empresa_cliente', 'cliente giftcard', 'empresa cliente'];
$nombrePerfilGCView  = $es_admin ? 'super admin' : strtolower($rowPerfilGC['per_nombre'] ?? '');
$es_cliente = !$es_admin && (in_array(strtolower($rol_gc), $rolesClienteExterno) || in_array($nombrePerfilGCView, $rolesClienteExterno));

// PER-01: personal interno (Financiero, Supervisor, etc.) con el módulo Gift
// Card habilitado y una empresa vinculada (cli_id) ve, en solo lectura, las
// solicitudes y lotes de esa empresa.
$es_empresa_staff = !$es_admin && !$es_cliente && !empty($_SESSION['cli_id']);

// H-003/H-013: staff interno (no cliente) con el módulo Gift Card habilitado
// en su perfil (ej. Supervisor) ve/gestiona igual que Super Admin — antes
// esta vista solo mostraba la administración a 'Super Admin'. Independiente
// de $es_empresa_staff (PER-01, solo lectura y solo de SU empresa).
$es_staff_gc = $es_admin;
if (!$es_staff_gc && !$es_cliente) {
    $qModGC = $mysqli->prepare(
        "SELECT 1 FROM perfil_modulo pm JOIN usuario u ON u.per_id = pm.per_id
         WHERE u.id_user = ? AND pm.pm_modulo = 'giftcard' LIMIT 1"
    );
    $qModGC->bind_param('i', $_SESSION['id_user']);
    $qModGC->execute();
    $es_staff_gc = (bool)$qModGC->get_result()->fetch_assoc();
}
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
                    <?php if ($es_cliente): ?>
                    <button class="btn btn-success" data-toggle="modal" data-target="#modal_solicitar" style="color:#fff;">
                        <i class="icon dripicons-mail"></i> Solicitar Códigos
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <section class="page-content container-fluid">

        <?php if ($es_staff_gc): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="icon dripicons-bell text-warning"></i> Aprobaciones Pendientes</span>
                        <button class="btn btn-sm btn-outline-secondary" id="btn_actualizar_sol">
                            <i class="icon dripicons-clockwise"></i> Actualizar
                        </button>
                    </h5>
                    <div class="card-body">
                        <div id="loader_solicitudes"><div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span></div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header d-flex align-items-center justify-content-between">
                        <span>Lotes de Gift Cards</span>
                        <button class="btn btn-sm btn-info" id="btn_crear_lote_directo" style="color:#fff;">
                            <i class="icon dripicons-plus"></i> Crear Nuevos Lotes
                        </button>
                    </h5>
                    <div class="card-body">
                        <div id="loader_lotes"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($es_cliente): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="icon dripicons-list"></i> Mis Solicitudes</span>
                        <button class="btn btn-sm btn-outline-secondary" id="btn_actualizar_mis_sol">
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

        <?php if ($es_empresa_staff): ?>
        <div class="alert alert-secondary py-2 px-3 mb-3" style="font-size:.85rem;">
            <i class="icon dripicons-information"></i> Vista de solo lectura: puedes consultar los lotes y
            solicitudes de Gift Cards de tu empresa. Aprobar, rechazar o crear lotes es exclusivo de administración.
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="icon dripicons-list"></i> Solicitudes de mi Empresa</span>
                        <button class="btn btn-sm btn-outline-secondary" id="btn_actualizar_sol_empresa">
                            <i class="icon dripicons-clockwise"></i> Actualizar
                        </button>
                    </h5>
                    <div class="card-body">
                        <div id="loader_solicitudes_empresa"><div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span></div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header"><span>Lotes de mi Empresa</span></h5>
                    <div class="card-body">
                        <div id="loader_lotes"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$es_admin && !$es_cliente && !$es_empresa_staff): ?>
        <div class="text-center text-muted py-5">
            <i class="icon dripicons-warning" style="font-size:2.5rem; display:block; margin-bottom:10px; opacity:.4;"></i>
            Tu perfil no tiene una empresa asignada para Gift Cards. Contacta a un administrador para que te
            vincule a la empresa correspondiente.
        </div>
        <?php endif; ?>

    </section>
</div>

<!-- Modal Ver Códigos -->
<div class="modal fade" id="modal_codigos" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-list"></i> <span id="titulo_codigos">Códigos del Lote</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body"><div id="loader_codigos"></div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btn_exportar_excel" style="color:#fff;">
                    <i class="icon dripicons-download"></i> Exportar Excel
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php if ($es_staff_gc): ?>
<!-- Modal Preview Aprobar/Rechazar -->
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
                <div id="preview_sin_cliente" class="alert alert-warning py-2 px-3" style="display:none; font-size:.85rem;">
                    <i class="icon dripicons-warning"></i>
                    El solicitante no tiene un <strong>cliente</strong> vinculado. Sin vincularlo, la cobranza de este
                    lote <strong>no se registrará</strong> en Gestiones.
                    <div class="input-group input-group-sm mt-2">
                        <input type="text" class="form-control" id="preview_nuevo_cliente" placeholder="Nombre de la empresa / cliente">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-warning" id="btn_crear_cliente_gc">
                                <i class="icon dripicons-plus"></i> Crear y Vincular
                            </button>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label id="preview_notas_label">Notas / Observaciones</label>
                    <textarea class="form-control" id="preview_notas" rows="3" placeholder="Opcional..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="btn_confirmar_accion" style="color:#fff; min-width:130px;">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Historial -->
<div class="modal fade" id="modal_historial" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-clock"></i> Historial de Auditoría</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="historial_body"></div>
        </div>
    </div>
</div>

<!-- Modal Bloquear / Reactivar Gift Cards -->
<div class="modal fade" id="modal_bloqueo" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-lock" id="bloqueo_icono"></i> <span id="bloqueo_titulo">Bloquear Gift Cards</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_bloqueo"></div>
                <p id="bloqueo_resumen" class="mb-2"></p>
                <div class="form-group mb-0">
                    <label for="bloqueo_motivo" id="bloqueo_motivo_label">Motivo <span class="text-danger">*</span></label>
                    <textarea id="bloqueo_motivo" class="form-control" rows="3" maxlength="255" placeholder="Ej: emitidas por error, solicitud del cliente..."></textarea>
                </div>
                <small class="text-muted d-block mt-2" id="bloqueo_nota"></small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" id="btn_confirmar_bloqueo" style="color:#fff; min-width:130px;">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Lote Directo (GC-B) -->
<link rel="stylesheet" href="assets/vendor/select2/select2.min.css">
<style>
/* Select2 del modal "Crear Lote": mismo alto y borde que los .form-control */
#modal_crear_lote .input-group > .select2-container { flex: 1 1 auto; min-width: 0; }
#modal_crear_lote .select2-container .select2-selection--single { height: calc(1.5em + .75rem + 2px); border: 1px solid #ced4da; border-radius: .25rem 0 0 .25rem; }
#modal_crear_lote .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: calc(1.5em + .75rem); padding-left: .75rem; }
#modal_crear_lote .select2-container--default .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .75rem); }
#modal_crear_lote .select2-container--default.select2-container--disabled .select2-selection--single { background: #e9ecef; }
.select2-container--open { z-index: 2000; }
.select2-dropdown { z-index: 2000; }
.select2-search--dropdown .select2-search__field { padding: .35rem .5rem; }
</style>
<div class="modal fade" id="modal_crear_lote" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-plus"></i> Crear Nuevo Lote de Gift Cards</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_crear_lote"></div>
                <div class="form-group">
                    <label>Cliente / Empresa</label>
                    <div class="input-group">
                        <select class="form-control" id="lote_cli_id">
                            <option value="">— Seleccionar —</option>
                        </select>
                        <div class="input-group-append">
                            <button class="btn btn-success" type="button" id="btn_toggle_nuevo_cliente" title="Crear cliente nuevo">
                                <i class="icon dripicons-plus" style="color:#fff"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mt-2" id="wrap_lote_nuevo_cliente" style="display:none;">
                        <input type="text" class="form-control" id="lote_nuevo_cliente" placeholder="Nombre del cliente nuevo">
                    </div>
                </div>
                <div class="form-group">
                    <label>Cantidad de códigos</label>
                    <input type="number" class="form-control" id="lote_cantidad" min="1" max="1000" placeholder="Ej: 50">
                </div>
                <div class="form-group">
                    <label>Cupo por código ($)</label>
                    <input type="number" class="form-control" id="lote_cupo_codigo" step="0.01" min="0.01" placeholder="Ej: 25.00">
                </div>
                <div class="form-group">
                    <label>Período de facturación</label>
                    <input type="date" class="form-control" id="lote_periodo_facturacion">
                </div>
                <div class="form-group">
                    <label>Fecha de caducidad</label>
                    <input type="date" class="form-control" id="lote_fecha_caducidad">
                    <small class="text-muted">Después de esta fecha los códigos no podrán usarse.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btn_guardar_lote_directo" style="color:#fff;">
                    <i class="icon dripicons-checkmark"></i> Crear Lote
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($es_cliente): ?>
<!-- Modal Solicitar Códigos -->
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

<script src="assets/vendor/exceljs/exceljs.min.js"></script>
<script src="js/export_theme.js"></script>
<script>
var lgc_id_actual = null;

$(document).ready(function () {

    // Inicialización
    <?php if ($es_staff_gc): ?>
    cargarSolicitudes();
    cargarLotes();
    $('#btn_actualizar_sol').on('click', function () { cargarSolicitudes(); });
    <?php endif; ?>
    <?php if ($es_cliente): ?>
    cargarMisSolicitudes();
    $('#btn_actualizar_mis_sol').on('click', function () { cargarMisSolicitudes(); });
    <?php endif; ?>
    <?php if ($es_empresa_staff): ?>
    cargarSolicitudesEmpresa();
    cargarLotes();
    $('#btn_actualizar_sol_empresa').on('click', function () { cargarSolicitudesEmpresa(); });
    <?php endif; ?>

    // Exportar Excel
    $('#btn_exportar_excel').on('click', function () {
        var tabla = document.getElementById('table_codigos');
        if (!tabla) return;
        var clone = tabla.cloneNode(true);
        $(clone).find('.col_chk').remove(); // casillas de bloqueo: no van al Excel
        $(clone).find('.badge, code').each(function () { $(this).replaceWith($(this).text()); });
        var titulo = $('#titulo_codigos').text().replace('Códigos del Lote — Período: ', '').replace(/\//g, '-');
        var wb = new ExcelJS.Workbook();
        ArgosExport.tableToSheet(wb, clone, 'Gift Cards');
        ArgosExport.download(wb, 'giftcards_' + titulo + '.xlsx');
    });

    // Enviar Solicitud (Cliente)
    $('#btn_enviar_solicitud').on('click', function () {
        var cantidad  = parseInt($('#sol_cantidad').val()) || 0;
        var cupo      = parseFloat($('#sol_cupo_codigo').val()) || 0;
        var periodo   = $('#sol_periodo_facturacion').val();
        var caducidad = $('#sol_fecha_caducidad').val();
        if (cantidad <= 0 || cupo <= 0 || !periodo || !caducidad) {
            $('#alerta_solicitar').html('<div class="alert alert-warning mb-0">Complete todos los campos.</div>');
            return;
        }
        $('#alerta_solicitar').html('');
        $('#btn_enviar_solicitud').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Enviando...');
        $.ajax({
            url: 'ajax/giftcard/giftcard.php', type: 'POST', dataType: 'json',
            data: { action: 'solicitar_lote', cantidad: cantidad, cupo_codigo: cupo, periodo_facturacion: periodo, fecha_caducidad: caducidad },
            success: function (r) {
                if (r.success) {
                    $('#modal_solicitar').modal('hide');
                    $('#sol_cantidad,#sol_cupo_codigo,#sol_periodo_facturacion,#sol_fecha_caducidad').val('');
                    cargarMisSolicitudes();
                    mostrarToast('success', r.mensaje);
                } else { $('#alerta_solicitar').html('<div class="alert alert-danger mb-0">' + esc(r.mensaje) + '</div>'); }
            },
            error:    function () { $('#alerta_solicitar').html('<div class="alert alert-danger mb-0">Error de conexión.</div>'); },
            complete: function () { $('#btn_enviar_solicitud').prop('disabled', false).html('<i class="icon dripicons-mail"></i> Enviar Solicitud'); }
        });
    });

    // GC-B: Crear Nuevo Lote directo (Super Admin)
    $('#btn_crear_lote_directo').on('click', abrirModalCrearLote);

    $('#btn_toggle_nuevo_cliente').on('click', function () {
        var abierto = $('#wrap_lote_nuevo_cliente').is(':visible');
        if (abierto) {
            $('#wrap_lote_nuevo_cliente').slideUp();
            $('#lote_nuevo_cliente').val('');
            $('#lote_cli_id').prop('disabled', false).trigger('change');
        } else {
            $('#wrap_lote_nuevo_cliente').slideDown();
            $('#lote_cli_id').val('').prop('disabled', true).trigger('change');
        }
    });

    $('#btn_guardar_lote_directo').on('click', function () {
        var cliId        = $('#lote_cli_id').val();
        var nuevoCliente = $('#lote_nuevo_cliente').val().trim();
        var cantidad     = parseInt($('#lote_cantidad').val()) || 0;
        var cupo         = parseFloat($('#lote_cupo_codigo').val()) || 0;
        var periodo      = $('#lote_periodo_facturacion').val();
        var caducidad    = $('#lote_fecha_caducidad').val();

        if ((!cliId && !nuevoCliente) || cantidad <= 0 || cupo <= 0 || !periodo || !caducidad) {
            $('#alerta_crear_lote').html('<div class="alert alert-warning mb-0">Complete todos los campos, incluyendo el cliente.</div>');
            return;
        }
        $('#alerta_crear_lote').html('');
        $('#btn_guardar_lote_directo').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Creando...');
        $.ajax({
            url: 'ajax/giftcard/giftcard.php', type: 'POST', dataType: 'json',
            data: {
                action: 'crear_lote', cli_id: cliId, nuevo_cliente: nuevoCliente,
                cantidad: cantidad, cupo_codigo: cupo, periodo_facturacion: periodo, fecha_caducidad: caducidad
            },
            success: function (r) {
                if (r.success) {
                    $('#modal_crear_lote').modal('hide');
                    cargarSolicitudes();
                    mostrarToast('success', r.mensaje);
                } else { $('#alerta_crear_lote').html('<div class="alert alert-danger mb-0">' + esc(r.mensaje) + '</div>'); }
            },
            error:    function () { $('#alerta_crear_lote').html('<div class="alert alert-danger mb-0">Error de conexión.</div>'); },
            complete: function () { $('#btn_guardar_lote_directo').prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Crear Lote'); }
        });
    });

    $('#modal_crear_lote').on('hidden.bs.modal', function () {
        $('#lote_cli_id').val('').prop('disabled', false).trigger('change');
        $('#lote_nuevo_cliente').val('');
        $('#wrap_lote_nuevo_cliente').hide();
        $('#lote_cantidad,#lote_cupo_codigo,#lote_periodo_facturacion,#lote_fecha_caducidad').val('');
        $('#alerta_crear_lote').html('');
    });

    // Confirmar Aprobar/Rechazar
    $('#btn_confirmar_accion').on('click', function () {
        var sol_id = $('#preview_sol_id').val();
        var accion = $('#preview_accion').val();
        var notas  = $('#preview_notas').val();
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Procesando...');
        $('#alerta_preview').html('');
        $.ajax({
            url: 'ajax/giftcard/giftcard.php', type: 'POST', dataType: 'json',
            data: { action: (accion === 'APPROVE') ? 'aprobar_solicitud' : 'rechazar_solicitud', sol_id: sol_id, notas: notas },
            success: function (r) {
                if (r.success) { $('#modal_preview_sol').modal('hide'); cargarSolicitudes(); cargarLotes(); mostrarToast('success', r.mensaje); }
                else { $('#alerta_preview').html('<div class="alert alert-danger mb-0">' + esc(r.mensaje) + '</div>'); }
            },
            error:    function () { $('#alerta_preview').html('<div class="alert alert-danger mb-0">Error de conexión.</div>'); },
            complete: function () { $('#btn_confirmar_accion').prop('disabled', false); }
        });
    });

    // GC-A: crear y vincular cliente al solicitante desde el modal de revisión
    $('#btn_crear_cliente_gc').on('click', crearClienteSolicitante);

    // Limpiar modales
    $('#modal_solicitar').on('hidden.bs.modal',   function () { $('#alerta_solicitar').html(''); });
    $('#modal_preview_sol').on('hidden.bs.modal', function () { $('#alerta_preview').html(''); $('#preview_notas').val(''); $('#preview_sin_cliente').hide(); });
});

// ── Funciones globales ──────────────────────────────────────

// Lotes paginados por el servidor: la tabla se arma una sola vez y después solo se recarga la página
// visible (sin perder la página ni la búsqueda en que estaba el usuario).
function cargarLotes() {
    if ($.fn.dataTable.isDataTable('#table_lotes')) {
        $('#table_lotes').DataTable().ajax.reload(null, false);
        return;
    }
    $('#loader_lotes').html(
        '<div class="form-inline mb-2"><label class="mr-2" for="filtro_lotes_estado">Mostrar:</label>' +
        '<select id="filtro_lotes_estado" class="form-control form-control-sm">' +
        '<option value="">Todos los lotes</option>' +
        '<option value="activos">Solo con códigos activos</option>' +
        '<option value="bloqueados">Solo con códigos bloqueados</option></select></div>' +
        '<table id="table_lotes" class="table table-striped table-bordered" style="width:100%"><thead><tr>' +
        '<th>No.</th><th>Fecha Creación</th><th>Cliente</th><th>Período Facturación</th>' +
        '<th>Cantidad</th><th>Cupo x Código</th><th>Disponibles</th>' +
        '<th>Consumidos</th><th>Bloqueados</th><th>Creado por</th><th>Acciones</th>' +
        '</tr></thead><tbody></tbody></table>');
    var tabla = $('#table_lotes').DataTable({
        serverSide: true,
        processing: true,
        searchDelay: 400,
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [{ targets: 10, orderable: false }],
        ajax: {
            url: 'ajax/giftcard/giftcard.php?action=list_lotes',
            type: 'GET',
            data: function (d) { d.estado = $('#filtro_lotes_estado').val(); }
        }
    });
    $('#filtro_lotes_estado').on('change', function () { tabla.ajax.reload(); });
}

function cargarSolicitudes() {
    $('#loader_solicitudes').html('<div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span></div>');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=list_solicitudes', type: 'GET',
        success: function (r) {
            $('#loader_solicitudes').html(r);
            if ($.fn.dataTable.isDataTable('#table_solicitudes')) $('#table_solicitudes').DataTable().destroy();
            $('#table_solicitudes').dataTable({ order: [[7, 'asc']], pageLength: 10 });
        }
    });
}

function cargarMisSolicitudes() {
    $('#loader_mis_solicitudes').html('<div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span></div>');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=mis_solicitudes', type: 'GET',
        success: function (r) { $('#loader_mis_solicitudes').html(r); }
    });
}

function cargarSolicitudesEmpresa() {
    $('#loader_solicitudes_empresa').html('<div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span></div>');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=list_solicitudes_empresa', type: 'GET',
        success: function (r) { $('#loader_solicitudes_empresa').html(r); }
    });
}

// Selector de cliente con buscador (Select2, ya cargado en main.php)
function iniciarBuscadorCliente() {
    var $s = $('#lote_cli_id');
    if (typeof $.fn.select2 !== 'function') return; // sin Select2 queda el desplegable normal
    if ($s.hasClass('select2-hidden-accessible')) $s.select2('destroy');
    $s.select2({
        dropdownParent: $('#modal_crear_lote'),
        placeholder: '— Seleccionar —',
        width: '1%',
        language: {
            noResults: function () { return 'Sin resultados. Usa "+" para crear un cliente nuevo.'; },
            searching: function () { return 'Buscando…'; }
        }
    });
}

function abrirModalCrearLote() {
    $('#lote_cli_id').html('<option value="">Cargando...</option>');
    $.getJSON('ajax/clientes/clientes.php?action=list', function (res) {
        var opts = '<option value="">— Seleccionar —</option>';
        if (res.success) {
            res.data.forEach(function (c) {
                var nombre = (c.cli_descripcion || '').trim();
                if (!nombre) return; // clientes sin nombre no se pueden elegir
                opts += '<option value="' + c.cli_id + '">' + esc(nombre) + '</option>';
            });
        }
        $('#lote_cli_id').html(opts);
        iniciarBuscadorCliente();
    });
    $('#modal_crear_lote').modal('show');
}

var periodo_actual = '';

function ver_codigos(lgc_id, periodo) {
    lgc_id_actual = lgc_id;
    periodo_actual = periodo;
    $('#titulo_codigos').text('Códigos del Lote — Período: ' + periodo);
    $('#loader_codigos').html('<div class="text-center p-4"><span class="spinner-border"></span></div>');
    $('#modal_codigos').modal('show');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=list_codigos&lgc_id=' + lgc_id, type: 'GET',
        success: function (r) {
            $('#loader_codigos').html(r);
            if ($.fn.dataTable.isDataTable('#table_codigos')) $('#table_codigos').DataTable().destroy();
            var conChk = $('#table_codigos th.col_chk').length > 0;
            $('#table_codigos').dataTable({
                pageLength: 25, scrollX: true,
                order: [[conChk ? 1 : 0, 'asc']],
                columnDefs: conChk ? [{ orderable: false, targets: 0 }] : []
            });
            $('#chk_todos_codigos').on('change', function () {
                $('#table_codigos').DataTable().$('input.chk_cgc').prop('checked', this.checked);
            });
        }
    });
}

// ── Bloquear / reactivar Gift Cards aprobadas ───────────────
var bloqueoActual = null;

// ids = null → todos los códigos del lote que estén en el estado de origen (activos / bloqueados)
function abrirBloqueo(lgc_id, accion, ids, cantidad) {
    var bloquear = (accion === 'bloquear');
    bloqueoActual = { lgc_id: lgc_id, accion: accion, ids: ids };
    $('#alerta_bloqueo').html('');
    $('#bloqueo_motivo').val('');
    $('#bloqueo_titulo').text(bloquear ? 'Bloquear Gift Cards' : 'Reactivar Gift Cards');
    $('#bloqueo_icono').attr('class', 'icon ' + (bloquear ? 'dripicons-lock' : 'dripicons-lock-open'));
    var que = ids ? (cantidad + (cantidad === 1 ? ' código seleccionado' : ' códigos seleccionados'))
                  : ('los ' + cantidad + (bloquear ? ' códigos activos' : ' códigos bloqueados') + ' del lote #' + lgc_id);
    $('#bloqueo_resumen').html(bloquear ? 'Se van a <strong>bloquear</strong> ' + que + '. El POS los rechazará hasta que se reactiven.'
                                        : 'Se van a <strong>reactivar</strong> ' + que + '.');
    $('#bloqueo_motivo_label').html(bloquear ? 'Motivo <span class="text-danger">*</span>' : 'Motivo (opcional)');
    $('#bloqueo_nota').text(bloquear ? 'El saldo de cada código se conserva. Bloquear no cambia lo que la empresa debe en Cobranza.'
                                     : 'Cada código vuelve a activo, o a vencido / consumido si ya corresponde.');
    $('#btn_confirmar_bloqueo').removeClass('btn-success btn-danger').addClass(bloquear ? 'btn-danger' : 'btn-success')
        .html(bloquear ? '<i class="icon dripicons-lock"></i> Bloquear' : '<i class="icon dripicons-lock-open"></i> Reactivar');
    $('#modal_bloqueo').modal('show');
}

function abrirBloqueoSeleccion(accion) {
    var origen = (accion === 'bloquear') ? 'activo' : 'bloqueado';
    var ids = [];
    $('#table_codigos').DataTable().$('input.chk_cgc:checked').each(function () {
        if ($(this).data('estado') === origen) ids.push(parseInt(this.value, 10));
    });
    if (!ids.length) {
        mostrarToast('error', accion === 'bloquear' ? 'Marque al menos un código activo para bloquear.' : 'Marque al menos un código bloqueado para reactivar.');
        return;
    }
    abrirBloqueo(lgc_id_actual, accion, ids, ids.length);
}

$(document).on('click', '#btn_confirmar_bloqueo', function () {
    if (!bloqueoActual) return;
    var bloquear = (bloqueoActual.accion === 'bloquear');
    var motivo = $.trim($('#bloqueo_motivo').val());
    if (bloquear && motivo.length < 3) {
        $('#alerta_bloqueo').html('<div class="alert alert-danger py-2">Indique el motivo del bloqueo (mínimo 3 caracteres).</div>');
        return;
    }
    var $btn = $(this).prop('disabled', true);
    var datos = { action: 'bloquear_codigos', lgc_id: bloqueoActual.lgc_id, accion: bloqueoActual.accion, motivo: motivo };
    if (bloqueoActual.ids) datos.cgc_ids = bloqueoActual.ids;
    $.ajax({
        url: 'ajax/giftcard/giftcard.php', type: 'POST', data: datos, dataType: 'json',
        success: function (r) {
            if (!r.success) {
                $('#alerta_bloqueo').html('<div class="alert alert-danger py-2">' + esc(r.mensaje || 'No se pudo completar') + '</div>');
                return;
            }
            $('#modal_bloqueo').modal('hide');
            mostrarToast('success', r.mensaje);
            cargarLotes();
            if ($('#modal_codigos').hasClass('show') && lgc_id_actual === bloqueoActual.lgc_id) ver_codigos(lgc_id_actual, periodo_actual);
        },
        error: function () { $('#alerta_bloqueo').html('<div class="alert alert-danger py-2">Error de conexión.</div>'); },
        complete: function () { $btn.prop('disabled', false); }
    });
});

function previsualizarSolicitud(sol_id, accion) {
    $('#preview_sol_id').val(sol_id);
    $('#preview_accion').val(accion);
    $('#preview_datos').html('<div class="text-center"><span class="spinner-border spinner-border-sm"></span></div>');
    $('#alerta_preview').html('');
    $('#preview_notas').val('');
    $('#preview_sin_cliente').hide();
    $('#preview_nuevo_cliente').val('');
    var ok = (accion === 'APPROVE');
    $('#preview_titulo').text((ok ? 'Aprobar' : 'Rechazar') + ' Solicitud #' + sol_id);
    $('#preview_notas_label').text(ok ? 'Observaciones (opcional)' : 'Motivo del rechazo (recomendado)');
    $('#btn_confirmar_accion').removeClass('btn-success btn-danger').addClass(ok ? 'btn-success' : 'btn-danger')
        .html(ok ? '<i class="icon dripicons-checkmark"></i> Aprobar' : '<i class="icon dripicons-cross"></i> Rechazar');
    $('#modal_preview_sol').modal('show');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=get_solicitud&sol_id=' + sol_id, type: 'GET', dataType: 'json',
        success: function (r) {
            if (!r.success) { $('#preview_datos').html('<div class="alert alert-danger">No se pudo cargar.</div>'); return; }
            var d = r.data, t = (parseFloat(d.sol_cantidad) * parseFloat(d.sol_cupo_codigo)).toFixed(2);
            $('#preview_datos').html('<table class="table table-sm table-bordered mb-0">' +
                '<tr><th class="bg-light" style="width:45%">Solicitante</th><td>' + esc(d.name_user) + '</td></tr>' +
                '<tr><th class="bg-light">Cliente</th><td>' + (d.cli_descripcion ? '<strong>' + esc(d.cli_descripcion) + '</strong>' : '<span class="text-muted">Sin vincular</span>') + '</td></tr>' +
                '<tr><th class="bg-light">Cantidad</th><td><strong>' + d.sol_cantidad + '</strong> códigos</td></tr>' +
                '<tr><th class="bg-light">Cupo por código</th><td>$' + parseFloat(d.sol_cupo_codigo).toFixed(2) + '</td></tr>' +
                '<tr><th class="bg-light">Cupo total</th><td><strong>$' + t + '</strong></td></tr>' +
                '<tr><th class="bg-light">Período</th><td>' + fFecha(d.sol_periodo_facturacion) + '</td></tr>' +
                '<tr><th class="bg-light">Caducidad</th><td>' + fFecha(d.sol_fecha_caducidad) + '</td></tr>' +
                '<tr><th class="bg-light">Fecha solicitud</th><td>' + fFechaHora(d.sol_fecha_solicitud) + '</td></tr>' +
                '</table>');
            if (accion === 'APPROVE' && !d.cli_id) {
                // Se sugiere el nombre del solicitante como nombre de empresa, pero
                // seleccionado: si el admin empieza a escribir sin borrar antes,
                // reemplaza el sugerido en vez de concatenarse con lo escrito.
                $('#preview_nuevo_cliente').val(d.name_user || '').select();
                $('#preview_sin_cliente').show();
            }
        }
    });
}

function crearClienteSolicitante() {
    var sol_id = $('#preview_sol_id').val();
    var desc   = $('#preview_nuevo_cliente').val().trim();
    if (!desc) { $('#preview_nuevo_cliente').focus(); return; }
    $('#btn_crear_cliente_gc').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php', type: 'POST', dataType: 'json',
        data: { action: 'crear_cliente_solicitante', sol_id: sol_id, cli_descripcion: desc },
        success: function (r) {
            if (r.success) {
                $('#preview_sin_cliente').hide();
                $('#preview_datos tr:nth-child(2) td').html('<strong>' + esc(r.cli_descripcion) + '</strong>');
                mostrarToast('success', 'Cliente creado y vinculado.');
            } else {
                mostrarToast('error', r.mensaje || 'No se pudo crear el cliente.');
            }
        },
        error:    function () { mostrarToast('error', 'Error de conexión.'); },
        complete: function () { $('#btn_crear_cliente_gc').prop('disabled', false).html('<i class="icon dripicons-plus"></i> Crear y Vincular'); }
    });
}

function verHistorial(sol_id) {
    cargarYRenderizarHistorial('ajax/giftcard/giftcard.php?action=ver_historial&sol_id=' + sol_id);
}

function verHistorialLote(lgc_id) {
    cargarYRenderizarHistorial('ajax/giftcard/giftcard.php?action=ver_historial_lote&lgc_id=' + lgc_id);
}

function cargarYRenderizarHistorial(url) {
    $('#historial_body').html('<div class="text-center"><span class="spinner-border spinner-border-sm"></span></div>');
    $('#modal_historial').modal('show');
    $.ajax({
        url: url, type: 'GET', dataType: 'json',
        success: function (r) {
            var bloqueos = (r && r.bloqueos) || [];
            if (!r.success || (!r.data.length && !bloqueos.length)) {
                $('#historial_body').html('<p class="text-muted text-center">Sin historial disponible para este lote.</p>');
                return;
            }
            var html = '<div class="list-group">';
            bloqueos.forEach(function (b) {
                var bl = b.gbh_accion === 'bloqueado';
                var n = parseInt(b.gbh_cantidad, 10) || 0;
                html += '<div class="list-group-item"><div class="d-flex justify-content-between align-items-center">' +
                    '<span><strong>' + esc(b.name_user || '—') + '</strong> &nbsp;<span class="badge badge-' + (bl ? 'dark' : 'success') + '">' + (bl ? 'Bloqueó' : 'Reactivó') + ' ' +
                    n + (n === 1 ? ' código' : ' códigos') + (b.gbh_alcance === 'lote' ? ' (lote)' : '') + '</span></span>' +
                    '<small class="text-muted">' + fFechaHora(b.gbh_fecha) + '</small></div>' +
                    (b.gbh_motivo ? '<p class="mb-0 mt-1 text-muted small">' + esc(b.gbh_motivo) + '</p>' : '') +
                    (b.gbh_codigos ? '<p class="mb-0 mt-1 small"><code>' + esc(b.gbh_codigos) + '</code></p>' : '') + '</div>';
            });
            r.data.forEach(function (h) {
                var ok = h.aph_accion === 'APPROVE';
                html += '<div class="list-group-item"><div class="d-flex justify-content-between align-items-center">' +
                    '<span><strong>' + esc(h.name_user) + '</strong> &nbsp;<span class="badge badge-' + (ok ? 'success' : 'danger') + '">' + (ok ? 'Aprobado' : 'Rechazado') + '</span></span>' +
                    '<small class="text-muted">' + fFechaHora(h.aph_timestamp) + '</small></div>' +
                    (h.aph_notas ? '<p class="mb-0 mt-1 text-muted small">' + esc(h.aph_notas) + '</p>' : '') + '</div>';
            });
            $('#historial_body').html(html + '</div>');
        }
    });
}

function mostrarToast(tipo, msg) {
    var t = $('<div>').css({ position:'fixed', bottom:'20px', right:'20px', zIndex:9999,
        background: tipo === 'success' ? '#28a745' : '#dc3545', color:'#fff',
        padding:'12px 20px', borderRadius:'8px', boxShadow:'0 4px 12px rgba(0,0,0,.2)', fontSize:'.9rem', maxWidth:'320px' }).text(msg);
    $('body').append(t);
    setTimeout(function () { t.fadeOut(400, function () { $(this).remove(); }); }, 4000);
}

function fFecha(d) { if (!d) return '-'; var p = d.split('-'); return p[2]+'/'+p[1]+'/'+p[0]; }
function fFechaHora(d) { if (!d) return '-'; var p = d.split(' '); return fFecha(p[0]) + (p[1] ? ' '+p[1].substring(0,5) : ''); }
</script>
