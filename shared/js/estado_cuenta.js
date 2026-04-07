$(document).ready(function () {
    cargarEstadosCuenta();

    $('#btn_generar_ec').on('click', generarEC);

    $('#btn_imprimir_ec').on('click', function () {
        var contenido = document.getElementById('ec_content').innerHTML;
        var ventana   = window.open('', '_blank', 'width=800,height=700');
        ventana.document.write('<html><head><title>Estado de Cuenta</title>');
        ventana.document.write('<link rel="stylesheet" href="assets/vendor/bootstrap/dist/css/bootstrap.min.css">');
        ventana.document.write('<style>body{font-family:Arial,sans-serif;font-size:13px;padding:20px;} @media print{.no-print{display:none}}</style>');
        ventana.document.write('</head><body onload="window.print();window.close();">');
        ventana.document.write(contenido);
        ventana.document.write('</body></html>');
        ventana.document.close();
    });

    $('#modal_generar_ec').on('hidden.bs.modal', function () {
        $('#ec_cli_id').val('');
        $('#ec_periodo_inicio, #ec_periodo_fin').val('');
        $('#alerta_ec').html('');
    });
});

function cargarEstadosCuenta() {
    $('#loader_ec').html('<div class="text-center p-3"><span class="spinner-border"></span></div>');
    $.ajax({
        url: 'ajax/estado_cuenta/estado_cuenta.php?action=list',
        type: 'GET',
        success: function (response) {
            $('#loader_ec').html(response);
            $('#table_ec').dataTable({ order: [[0, 'desc']] });
        }
    });
}

function generarEC() {
    var cli_id          = $('#ec_cli_id').val();
    var periodo_inicio  = $('#ec_periodo_inicio').val();
    var periodo_fin     = $('#ec_periodo_fin').val();

    if (!cli_id || !periodo_inicio || !periodo_fin) {
        $('#alerta_ec').html('<div class="alert alert-warning mb-2">Complete todos los campos.</div>');
        return;
    }

    if (periodo_fin < periodo_inicio) {
        $('#alerta_ec').html('<div class="alert alert-warning mb-2">La fecha fin debe ser mayor o igual a la fecha inicio.</div>');
        return;
    }

    $('#btn_generar_ec').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Generando...');
    $('#alerta_ec').html('');

    $.ajax({
        url: 'ajax/estado_cuenta/estado_cuenta.php',
        type: 'POST',
        data: { action: 'generar', cli_id: cli_id, periodo_inicio: periodo_inicio, periodo_fin: periodo_fin },
        dataType: 'json',
        success: function (resp) {
            if (resp.success) {
                $('#modal_generar_ec').modal('hide');
                cargarEstadosCuenta();
                ver_ec(resp.ec_id);
            } else {
                $('#alerta_ec').html('<div class="alert alert-danger mb-2">' + resp.mensaje + '</div>');
            }
        },
        error: function () {
            $('#alerta_ec').html('<div class="alert alert-danger mb-2">Error de conexión</div>');
        },
        complete: function () {
            $('#btn_generar_ec').prop('disabled', false).html('<i class="icon dripicons-document"></i> Generar');
        }
    });
}

function ver_ec(ec_id) {
    $('#ec_content').html('<div class="text-center p-4"><span class="spinner-border"></span></div>');
    $('#modal_ver_ec').modal('show');

    $.ajax({
        url: 'ajax/estado_cuenta/estado_cuenta.php?action=ver&ec_id=' + ec_id,
        type: 'GET',
        dataType: 'json',
        success: function (resp) {
            if (resp.success) {
                $('#ec_content').html(renderEC(resp.ec, resp.detalles));
            } else {
                $('#ec_content').html('<div class="alert alert-danger">' + resp.mensaje + '</div>');
            }
        },
        error: function () {
            $('#ec_content').html('<div class="alert alert-danger">Error de conexión</div>');
        }
    });
}

function renderEC(ec, detalles) {
    var filas = '';
    var subtotal = 0;

    detalles.forEach(function (d) {
        var total = parseFloat(d.con_valor_total);
        subtotal += total;
        filas += '<tr>' +
            '<td>' + d.con_fecha + '</td>' +
            '<td>' + d.con_hora + '</td>' +
            '<td>' + d.per_nombre + '<br><small>' + d.per_documento + '</small></td>' +
            '<td>' + (d.loc_direccion || '-') + '</td>' +
            '<td class="text-right">$' + parseFloat(d.con_monto_convenio).toFixed(2) + '</td>' +
            '<td class="text-right">$' + parseFloat(d.con_monto_externo || 0).toFixed(2) + '</td>' +
            '<td class="text-right"><strong>$' + total.toFixed(2) + '</strong></td>' +
            '</tr>';
    });

    var html = '' +
        '<div style="font-family:Arial,sans-serif; font-size:13px;">' +
        '  <div class="text-center mb-3">' +
        '    <h4 class="mb-0">SGC ARGOS</h4>' +
        '    <h5>ESTADO DE CUENTA</h5>' +
        '  </div>' +
        '  <hr>' +
        '  <div class="row mb-3">' +
        '    <div class="col-6">' +
        '      <strong>Cliente:</strong> ' + ec.cli_descripcion + '<br>' +
        '      <strong>Contacto:</strong> ' + (ec.cli_contacto || '-') + '<br>' +
        '      <strong>Teléfono:</strong> ' + (ec.cli_telefono || '-') + '<br>' +
        '      <strong>Email:</strong> '    + (ec.cli_email    || '-') +
        '    </div>' +
        '    <div class="col-6 text-right">' +
        '      <strong>No. Estado:</strong> #' + ec.ec_id + '<br>' +
        '      <strong>Período:</strong> '     + formatDate(ec.ec_periodo_inicio) + ' al ' + formatDate(ec.ec_periodo_fin) + '<br>' +
        '      <strong>Generado:</strong> '    + formatDatetime(ec.ec_fecha_generacion) +
        '    </div>' +
        '  </div>' +
        '  <hr>' +
        '  <table class="table table-sm table-bordered" style="font-size:12px;">' +
        '    <thead class="thead-light">' +
        '      <tr>' +
        '        <th>Fecha</th><th>Hora</th><th>Empleado</th><th>Local</th>' +
        '        <th class="text-right">Convenio</th><th class="text-right">Externo</th><th class="text-right">Total</th>' +
        '      </tr>' +
        '    </thead>' +
        '    <tbody>' + filas + '</tbody>' +
        '    <tfoot>' +
        '      <tr class="table-active">' +
        '        <td colspan="6" class="text-right"><strong>TOTAL DEL PERÍODO</strong></td>' +
        '        <td class="text-right"><strong>$' + subtotal.toFixed(2) + '</strong></td>' +
        '      </tr>' +
        '    </tfoot>' +
        '  </table>' +
        '  <div class="mt-4 row">' +
        '    <div class="col-6">' +
        '      <p>Firma autorizada: ___________________________</p>' +
        '    </div>' +
        '    <div class="col-6">' +
        '      <p>Sello: ___________________________</p>' +
        '    </div>' +
        '  </div>' +
        '</div>';

    return html;
}

function formatDate(str) {
    if (!str) return '-';
    var p = str.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function formatDatetime(str) {
    if (!str) return '-';
    var parts = str.split(' ');
    return formatDate(parts[0]) + ' ' + (parts[1] ? parts[1].substring(0,5) : '');
}
