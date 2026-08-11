// CO-03: Widget financiero del dashboard (top deudores + confirmación de pagos)
$(document).ready(function () {
    if ($('#panel_financiero').length === 0) return; // widget no renderizado (sin permisos)

    $.ajax({
        type: 'GET',
        url: 'ajax/dashboard/financiero.php?action=resumen',
        dataType: 'json',
        success: function (resp) {
            if (!resp.success) return;

            $('#fin_confirmado').text('$' + Number(resp.total_confirmado).toFixed(2));
            $('#fin_pendiente').text('$' + Number(resp.total_pendiente).toFixed(2));

            var $tbody = $('#tabla_top_deudores tbody').empty();
            if (!resp.top_deudores || resp.top_deudores.length === 0) {
                $tbody.append('<tr><td colspan="2" class="text-center text-muted">Sin deuda pendiente</td></tr>');
                return;
            }
            resp.top_deudores.forEach(function (d) {
                $tbody.append(
                    '<tr><td>' + d.cli_descripcion + '</td>' +
                    '<td class="text-right">$' + Number(d.deuda_pendiente).toFixed(2) + '</td></tr>'
                );
            });
        }
    });
});
