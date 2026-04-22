<?php
if (!isset($_SESSION['id_user'])) { echo "<meta http-equiv='refresh' content='0; url=index.php'>"; exit; }
$rol_gc     = $_SESSION['permisos_acceso'] ?? '';
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

        <?php if ($es_admin): ?>
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
                    <h5 class="card-header">Lotes de Gift Cards</h5>
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

<?php if ($es_admin): ?>
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

<script src="assets/vendor/sheetjs/xlsx.full.min.js"></script>
<script>
var lgc_id_actual = null;

$(document).ready(function () {

    // Inicialización
    <?php if ($es_admin): ?>
    cargarSolicitudes();
    cargarLotes();
    $('#btn_actualizar_sol').on('click', function () { cargarSolicitudes(); });
    <?php endif; ?>
    <?php if ($es_cliente): ?>
    cargarMisSolicitudes();
    $('#btn_actualizar_mis_sol').on('click', function () { cargarMisSolicitudes(); });
    <?php endif; ?>

    // Exportar Excel
    $('#btn_exportar_excel').on('click', function () {
        var tabla = document.getElementById('table_codigos');
        if (!tabla) return;
        var clone = tabla.cloneNode(true);
        $(clone).find('.badge, code').each(function () { $(this).replaceWith($(this).text()); });
        var wb    = XLSX.utils.book_new();
        var ws    = XLSX.utils.table_to_sheet(clone);
        var titulo = $('#titulo_codigos').text().replace('Códigos del Lote — Período: ', '').replace(/\//g, '-');
        XLSX.utils.book_append_sheet(wb, ws, 'Gift Cards');
        XLSX.writeFile(wb, 'giftcards_' + titulo + '.xlsx');
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
                } else { $('#alerta_solicitar').html('<div class="alert alert-danger mb-0">' + r.mensaje + '</div>'); }
            },
            error:    function () { $('#alerta_solicitar').html('<div class="alert alert-danger mb-0">Error de conexión.</div>'); },
            complete: function () { $('#btn_enviar_solicitud').prop('disabled', false).html('<i class="icon dripicons-mail"></i> Enviar Solicitud'); }
        });
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
                else { $('#alerta_preview').html('<div class="alert alert-danger mb-0">' + r.mensaje + '</div>'); }
            },
            error:    function () { $('#alerta_preview').html('<div class="alert alert-danger mb-0">Error de conexión.</div>'); },
            complete: function () { $('#btn_confirmar_accion').prop('disabled', false); }
        });
    });

    // Limpiar modales
    $('#modal_solicitar').on('hidden.bs.modal',   function () { $('#alerta_solicitar').html(''); });
    $('#modal_preview_sol').on('hidden.bs.modal', function () { $('#alerta_preview').html(''); $('#preview_notas').val(''); });
});

// ── Funciones globales ──────────────────────────────────────

function cargarLotes() {
    $('#loader_lotes').html('<div class="text-center p-3"><span class="spinner-border"></span></div>');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=list_lotes', type: 'GET',
        success: function (r) {
            $('#loader_lotes').html(r);
            if ($.fn.dataTable.isDataTable('#table_lotes')) $('#table_lotes').DataTable().destroy();
            $('#table_lotes').dataTable({ order: [[0, 'desc']] });
        }
    });
}

function cargarSolicitudes() {
    $('#loader_solicitudes').html('<div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span></div>');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=list_solicitudes', type: 'GET',
        success: function (r) {
            $('#loader_solicitudes').html(r);
            if ($.fn.dataTable.isDataTable('#table_solicitudes')) $('#table_solicitudes').DataTable().destroy();
            $('#table_solicitudes').dataTable({ order: [[6, 'desc']], pageLength: 10 });
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

function ver_codigos(lgc_id, periodo) {
    lgc_id_actual = lgc_id;
    $('#titulo_codigos').text('Códigos del Lote — Período: ' + periodo);
    $('#loader_codigos').html('<div class="text-center p-4"><span class="spinner-border"></span></div>');
    $('#modal_codigos').modal('show');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=list_codigos&lgc_id=' + lgc_id, type: 'GET',
        success: function (r) {
            $('#loader_codigos').html(r);
            if ($.fn.dataTable.isDataTable('#table_codigos')) $('#table_codigos').DataTable().destroy();
            $('#table_codigos').dataTable({ pageLength: 25, scrollX: true });
        }
    });
}

function previsualizarSolicitud(sol_id, accion) {
    $('#preview_sol_id').val(sol_id);
    $('#preview_accion').val(accion);
    $('#preview_datos').html('<div class="text-center"><span class="spinner-border spinner-border-sm"></span></div>');
    $('#alerta_preview').html('');
    $('#preview_notas').val('');
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
                '<tr><th class="bg-light">Cantidad</th><td><strong>' + d.sol_cantidad + '</strong> códigos</td></tr>' +
                '<tr><th class="bg-light">Cupo por código</th><td>$' + parseFloat(d.sol_cupo_codigo).toFixed(2) + '</td></tr>' +
                '<tr><th class="bg-light">Cupo total</th><td><strong>$' + t + '</strong></td></tr>' +
                '<tr><th class="bg-light">Período</th><td>' + fFecha(d.sol_periodo_facturacion) + '</td></tr>' +
                '<tr><th class="bg-light">Caducidad</th><td>' + fFecha(d.sol_fecha_caducidad) + '</td></tr>' +
                '<tr><th class="bg-light">Fecha solicitud</th><td>' + fFechaHora(d.sol_fecha_solicitud) + '</td></tr>' +
                '</table>');
        }
    });
}

function verHistorial(sol_id) {
    $('#historial_body').html('<div class="text-center"><span class="spinner-border spinner-border-sm"></span></div>');
    $('#modal_historial').modal('show');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=ver_historial&sol_id=' + sol_id, type: 'GET', dataType: 'json',
        success: function (r) {
            if (!r.success || !r.data.length) { $('#historial_body').html('<p class="text-muted text-center">Sin registros.</p>'); return; }
            var html = '<div class="list-group">';
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

function esc(s) { return $('<div>').text(s).html(); }
function fFecha(d) { if (!d) return '-'; var p = d.split('-'); return p[2]+'/'+p[1]+'/'+p[0]; }
function fFechaHora(d) { if (!d) return '-'; var p = d.split(' '); return fFecha(p[0]) + (p[1] ? ' '+p[1].substring(0,5) : ''); }
</script>
