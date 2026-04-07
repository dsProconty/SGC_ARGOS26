var lgc_id_actual = null;

$(document).ready(function () {

    // ── Inicialización según rol ─────────────────────────────
    if ($('#loader_lotes').length)           cargarLotes();
    if ($('#loader_solicitudes').length)     cargarSolicitudes();
    if ($('#loader_mis_solicitudes').length) cargarMisSolicitudes();

    // ── Exportar Excel ───────────────────────────────────────
    $('#btn_exportar_excel').on('click', function () {
        var tabla = document.getElementById('table_codigos');
        if (!tabla) return;
        var clone = tabla.cloneNode(true);
        $(clone).find('.badge, code').each(function () {
            $(this).replaceWith($(this).text());
        });
        var wb    = XLSX.utils.book_new();
        var ws    = XLSX.utils.table_to_sheet(clone);
        var titulo = $('#titulo_codigos').text().replace('Códigos del Lote — Período: ', '').replace(/\//g, '-');
        XLSX.utils.book_append_sheet(wb, ws, 'Gift Cards');
        XLSX.writeFile(wb, 'giftcards_' + titulo + '.xlsx');
    });

    // ── Crear Lote (Admin) ───────────────────────────────────
    $('#btn_crear_lote').on('click', function () {
        var cantidad  = parseInt($('#lgc_cantidad').val()) || 0;
        var cupo      = parseFloat($('#lgc_cupo_codigo').val()) || 0;
        var periodo   = $('#lgc_periodo_facturacion').val();
        var caducidad = $('#lgc_fecha_caducidad').val();

        if (cantidad <= 0 || cupo <= 0 || !periodo || !caducidad) {
            $('#alerta_lote').html('<div class="alert alert-warning mb-0">Complete todos los campos.</div>');
            return;
        }
        $('#alerta_lote').html('');
        $('#btn_crear_lote').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Creando...');

        $.ajax({
            url: 'ajax/giftcard/giftcard.php',
            type: 'POST',
            data: { action: 'crear_lote', cantidad: cantidad, cupo_codigo: cupo, periodo_facturacion: periodo, fecha_caducidad: caducidad },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    $('#modal_nuevo_lote').modal('hide');
                    $('#lgc_cantidad, #lgc_cupo_codigo, #lgc_periodo_facturacion, #lgc_fecha_caducidad').val('');
                    cargarLotes();
                } else {
                    $('#alerta_lote').html('<div class="alert alert-danger mb-0">' + resp.mensaje + '</div>');
                }
            },
            error: function () {
                $('#alerta_lote').html('<div class="alert alert-danger mb-0">Error de conexión.</div>');
            },
            complete: function () {
                $('#btn_crear_lote').prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Crear Lote');
            }
        });
    });

    // ── Enviar Solicitud (Cliente) ───────────────────────────
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
            url: 'ajax/giftcard/giftcard.php',
            type: 'POST',
            data: { action: 'solicitar_lote', cantidad: cantidad, cupo_codigo: cupo, periodo_facturacion: periodo, fecha_caducidad: caducidad },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    $('#modal_solicitar').modal('hide');
                    $('#sol_cantidad, #sol_cupo_codigo, #sol_periodo_facturacion, #sol_fecha_caducidad').val('');
                    cargarMisSolicitudes();
                    mostrarToast('success', resp.mensaje);
                } else {
                    $('#alerta_solicitar').html('<div class="alert alert-danger mb-0">' + resp.mensaje + '</div>');
                }
            },
            error: function () {
                $('#alerta_solicitar').html('<div class="alert alert-danger mb-0">Error de conexión.</div>');
            },
            complete: function () {
                $('#btn_enviar_solicitud').prop('disabled', false).html('<i class="icon dripicons-mail"></i> Enviar Solicitud');
            }
        });
    });

    // ── Confirmar Aprobación / Rechazo ───────────────────────
    $('#btn_confirmar_accion').on('click', function () {
        var sol_id = $('#preview_sol_id').val();
        var accion = $('#preview_accion').val();
        var notas  = $('#preview_notas').val();
        var action = (accion === 'APPROVE') ? 'aprobar_solicitud' : 'rechazar_solicitud';

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Procesando...');
        $('#alerta_preview').html('');

        $.ajax({
            url: 'ajax/giftcard/giftcard.php',
            type: 'POST',
            data: { action: action, sol_id: sol_id, notas: notas },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    $('#modal_preview_sol').modal('hide');
                    cargarSolicitudes();
                    cargarLotes();
                    mostrarToast('success', resp.mensaje);
                } else {
                    $('#alerta_preview').html('<div class="alert alert-danger mb-0">' + resp.mensaje + '</div>');
                }
            },
            error: function () {
                $('#alerta_preview').html('<div class="alert alert-danger mb-0">Error de conexión.</div>');
            },
            complete: function () {
                $('#btn_confirmar_accion').prop('disabled', false);
            }
        });
    });

    // ── Limpiar modales al cerrar ────────────────────────────
    $('#modal_nuevo_lote').on('hidden.bs.modal',  function () { $('#alerta_lote').html(''); });
    $('#modal_solicitar').on('hidden.bs.modal',   function () { $('#alerta_solicitar').html(''); });
    $('#modal_preview_sol').on('hidden.bs.modal', function () { $('#alerta_preview').html(''); $('#preview_notas').val(''); });
});

// ─────────────────────────────────────────────────────────────
// FUNCIONES DE CARGA
// ─────────────────────────────────────────────────────────────

function cargarLotes() {
    $('#loader_lotes').html('<div class="text-center p-3"><span class="spinner-border"></span></div>');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=list_lotes',
        type: 'GET',
        success: function (response) {
            $('#loader_lotes').html(response);
            if ($.fn.dataTable.isDataTable('#table_lotes')) $('#table_lotes').DataTable().destroy();
            $('#table_lotes').dataTable({ order: [[0, 'desc']] });
        }
    });
}

function cargarSolicitudes() {
    $('#loader_solicitudes').html('<div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span></div>');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=list_solicitudes',
        type: 'GET',
        success: function (response) {
            $('#loader_solicitudes').html(response);
            if ($.fn.dataTable.isDataTable('#table_solicitudes')) $('#table_solicitudes').DataTable().destroy();
            $('#table_solicitudes').dataTable({ order: [[6, 'desc']], pageLength: 10 });
        }
    });
}

function cargarMisSolicitudes() {
    $('#loader_mis_solicitudes').html('<div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span></div>');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=mis_solicitudes',
        type: 'GET',
        success: function (response) {
            $('#loader_mis_solicitudes').html(response);
        }
    });
}

// ─────────────────────────────────────────────────────────────
// VER CÓDIGOS DE UN LOTE
// ─────────────────────────────────────────────────────────────

function ver_codigos(lgc_id, periodo) {
    lgc_id_actual = lgc_id;
    $('#titulo_codigos').text('Códigos del Lote — Período: ' + periodo);
    $('#loader_codigos').html('<div class="text-center p-4"><span class="spinner-border"></span></div>');
    $('#modal_codigos').modal('show');

    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=list_codigos&lgc_id=' + lgc_id,
        type: 'GET',
        success: function (response) {
            $('#loader_codigos').html(response);
            if ($.fn.dataTable.isDataTable('#table_codigos')) $('#table_codigos').DataTable().destroy();
            $('#table_codigos').dataTable({ pageLength: 25, scrollX: true });
        }
    });
}

// ─────────────────────────────────────────────────────────────
// PREVISUALIZAR SOLICITUD (Admin)
// ─────────────────────────────────────────────────────────────

function previsualizarSolicitud(sol_id, accion) {
    $('#preview_sol_id').val(sol_id);
    $('#preview_accion').val(accion);
    $('#preview_datos').html('<div class="text-center"><span class="spinner-border spinner-border-sm"></span></div>');
    $('#alerta_preview').html('');
    $('#preview_notas').val('');

    var esAprobar = (accion === 'APPROVE');
    $('#preview_titulo').text(esAprobar ? 'Aprobar Solicitud #' + sol_id : 'Rechazar Solicitud #' + sol_id);
    $('#preview_notas_label').text(esAprobar ? 'Observaciones (opcional)' : 'Motivo del rechazo (recomendado)');
    $('#btn_confirmar_accion')
        .removeClass('btn-success btn-danger')
        .addClass(esAprobar ? 'btn-success' : 'btn-danger')
        .html(esAprobar ? '<i class="icon dripicons-checkmark"></i> Aprobar' : '<i class="icon dripicons-cross"></i> Rechazar');

    $('#modal_preview_sol').modal('show');

    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=get_solicitud&sol_id=' + sol_id,
        type: 'GET',
        dataType: 'json',
        success: function (resp) {
            if (!resp.success) {
                $('#preview_datos').html('<div class="alert alert-danger">No se pudo cargar la solicitud.</div>');
                return;
            }
            var d = resp.data;
            var cupoTotal = (parseFloat(d.sol_cantidad) * parseFloat(d.sol_cupo_codigo)).toFixed(2);
            $('#preview_datos').html(
                '<table class="table table-sm table-bordered mb-0">' +
                '<tr><th class="bg-light" style="width:45%">Solicitante</th><td>' + escHtml(d.name_user) + '</td></tr>' +
                '<tr><th class="bg-light">Cantidad</th><td><strong>' + d.sol_cantidad + '</strong> códigos</td></tr>' +
                '<tr><th class="bg-light">Cupo por código</th><td>$' + parseFloat(d.sol_cupo_codigo).toFixed(2) + '</td></tr>' +
                '<tr><th class="bg-light">Cupo total</th><td><strong>$' + cupoTotal + '</strong></td></tr>' +
                '<tr><th class="bg-light">Período</th><td>' + formatFecha(d.sol_periodo_facturacion) + '</td></tr>' +
                '<tr><th class="bg-light">Caducidad</th><td>' + formatFecha(d.sol_fecha_caducidad) + '</td></tr>' +
                '<tr><th class="bg-light">Fecha solicitud</th><td>' + formatFechaHora(d.sol_fecha_solicitud) + '</td></tr>' +
                '</table>'
            );
        }
    });
}

// ─────────────────────────────────────────────────────────────
// VER HISTORIAL DE AUDITORÍA
// ─────────────────────────────────────────────────────────────

function verHistorial(sol_id) {
    $('#historial_body').html('<div class="text-center"><span class="spinner-border spinner-border-sm"></span></div>');
    $('#modal_historial').modal('show');

    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=ver_historial&sol_id=' + sol_id,
        type: 'GET',
        dataType: 'json',
        success: function (resp) {
            if (!resp.success || !resp.data.length) {
                $('#historial_body').html('<p class="text-muted text-center">Sin registros.</p>');
                return;
            }
            var html = '<div class="list-group">';
            resp.data.forEach(function (h) {
                var esAprobar = h.aph_accion === 'APPROVE';
                var badge = esAprobar ? 'badge-success' : 'badge-danger';
                var label = esAprobar ? 'Aprobado' : 'Rechazado';
                html += '<div class="list-group-item">' +
                    '<div class="d-flex justify-content-between align-items-center">' +
                    '<span><strong>' + escHtml(h.name_user) + '</strong> &nbsp;<span class="badge ' + badge + '">' + label + '</span></span>' +
                    '<small class="text-muted">' + formatFechaHora(h.aph_timestamp) + '</small>' +
                    '</div>' +
                    (h.aph_notas ? '<p class="mb-0 mt-1 text-muted small">' + escHtml(h.aph_notas) + '</p>' : '') +
                    '</div>';
            });
            html += '</div>';
            $('#historial_body').html(html);
        }
    });
}

// ─────────────────────────────────────────────────────────────
// UTILIDADES
// ─────────────────────────────────────────────────────────────

function escHtml(str) {
    return $('<div>').text(str).html();
}

function formatFecha(d) {
    if (!d) return '-';
    var p = d.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function formatFechaHora(d) {
    if (!d) return '-';
    var parts = d.split(' ');
    return formatFecha(parts[0]) + (parts[1] ? ' ' + parts[1].substring(0,5) : '');
}

function mostrarToast(tipo, mensaje) {
    var color = tipo === 'success' ? '#28a745' : '#dc3545';
    var toast = $('<div>')
        .css({ position:'fixed', bottom:'20px', right:'20px', zIndex:9999,
               background: color, color:'#fff', padding:'12px 20px',
               borderRadius:'8px', boxShadow:'0 4px 12px rgba(0,0,0,.2)',
               fontSize:'.9rem', maxWidth:'320px' })
        .text(mensaje);
    $('body').append(toast);
    setTimeout(function () { toast.fadeOut(400, function () { $(this).remove(); }); }, 4000);
}
