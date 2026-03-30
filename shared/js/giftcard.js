var lgc_id_actual = null;

$(document).ready(function () {
    cargarLotes();

    $('#btn_exportar_excel').on('click', function () {
        var tabla = document.getElementById('table_codigos');
        if (!tabla) return;

        // Clonar tabla limpiando badges/code para tener texto plano
        var clone = tabla.cloneNode(true);
        $(clone).find('.badge, code').each(function () {
            $(this).replaceWith($(this).text());
        });

        var wb  = XLSX.utils.book_new();
        var ws  = XLSX.utils.table_to_sheet(clone);
        var titulo = $('#titulo_codigos').text().replace('Códigos del Lote — Período: ', '').replace(/\//g, '-');
        XLSX.utils.book_append_sheet(wb, ws, 'Gift Cards');
        XLSX.writeFile(wb, 'giftcards_' + titulo + '.xlsx');
    });

    $('#btn_crear_lote').on('click', function () {
        var cantidad = parseInt($('#lgc_cantidad').val()) || 0;
        var cupo     = parseFloat($('#lgc_cupo_codigo').val()) || 0;
        var periodo  = $('#lgc_periodo_facturacion').val();

        var caducidad = $('#lgc_fecha_caducidad').val();

        if (cantidad <= 0 || cupo <= 0 || !periodo || !caducidad) {
            $('#alerta_lote').html('<div class="alert alert-warning mb-0">Complete todos los campos incluyendo la fecha de caducidad.</div>');
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

    $('#modal_nuevo_lote').on('hidden.bs.modal', function () {
        $('#alerta_lote').html('');
    });
});

function cargarLotes() {
    $('#loader_lotes').html('<div class="text-center p-3"><span class="spinner-border"></span></div>');
    $.ajax({
        url: 'ajax/giftcard/giftcard.php?action=list_lotes',
        type: 'GET',
        success: function (response) {
            $('#loader_lotes').html(response);
            $('#table_lotes').dataTable({ order: [[0, 'desc']] });
        }
    });
}

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
            $('#table_codigos').dataTable({ pageLength: 25, scrollX: true });
        }
    });
}
