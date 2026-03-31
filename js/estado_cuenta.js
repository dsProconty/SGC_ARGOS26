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
    var filas       = '';
    var ventaNeta   = 0;
    var totalIva    = 0;
    var totalVenta  = 0;
    var comisionPct = parseFloat(ec.cli_comision || 0);

    detalles.forEach(function (d) {
        var neto  = parseFloat(d.con_valor_neto  || 0);
        var iva   = parseFloat(d.con_iva         || 0);
        var total = parseFloat(d.con_valor_total || 0);
        ventaNeta  += neto;
        totalIva   += iva;
        totalVenta += total;
        var esDiferida = d.origen === 'diferida';
        filas += '<tr' + (esDiferida ? ' class="table-warning"' : '') + '>' +
            '<td>' + d.con_fecha + '</td>' +
            '<td>' + (d.con_hora ? d.con_hora.substring(0,5) : '-') + '</td>' +
            '<td>' + d.per_nombre + '</td>' +
            '<td>' + (d.per_documento || '-') + '</td>' +
            '<td>' + (d.per_numero_tarjeta ? maskTarjeta(d.per_numero_tarjeta) : '-') + '</td>' +
            '<td>' + (d.loc_direccion || '-') + '</td>' +
            '<td>' + (d.con_descripcion || '-') + '</td>' +
            '<td class="text-right">$' + neto.toFixed(2)  + '</td>' +
            '<td class="text-right">$' + iva.toFixed(2)   + '</td>' +
            '<td class="text-right"><strong>$' + total.toFixed(2) + '</strong></td>' +
            '</tr>';
    });

    var comisionMonto = totalVenta * comisionPct / 100;
    var totalPagar    = totalVenta - comisionMonto;

    // Tabla resumen financiero (igual a la imagen)
    var resumen =
        '<table class="table table-bordered mt-3" style="font-size:13px; max-width:320px; margin-left:auto;">' +
        '  <tbody>' +
        '    <tr><td><strong>VENTA NETA</strong></td><td class="text-right"><strong>$' + ventaNeta.toFixed(2) + '</strong></td></tr>' +
        '    <tr><td><strong>IVA</strong></td><td class="text-right"><strong>$' + totalIva.toFixed(2) + '</strong></td></tr>' +
        '    <tr class="table-active"><td><strong>TOTAL VENTA</strong></td><td class="text-right"><strong>$' + totalVenta.toFixed(2) + '</strong></td></tr>' +
        '    <tr><td><strong>COMISIÓN (' + comisionPct.toFixed(2) + '%)</strong></td><td class="text-right"><strong>$' + comisionMonto.toFixed(2) + '</strong></td></tr>' +
        '    <tr class="table-dark"><td><strong>TOTAL A PAGAR</strong></td><td class="text-right"><strong>$' + totalPagar.toFixed(2) + '</strong></td></tr>' +
        '  </tbody>' +
        '</table>';

    var html =
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
        '      <strong>Período:</strong> ' + formatDate(ec.ec_periodo_inicio) + ' al ' + formatDate(ec.ec_periodo_fin) + '<br>' +
        '      <strong>Generado:</strong> ' + formatDatetime(ec.ec_fecha_generacion) +
        '    </div>' +
        '  </div>' +
        '  <hr>' +
        '  <table class="table table-sm table-bordered" style="font-size:12px;">' +
        '    <thead class="thead-light">' +
        '      <tr>' +
        '        <th>Fecha</th><th>Hora</th><th>Nombres</th><th>Cédula</th><th>Tarjeta/Convenio</th><th>Local</th><th>Descripción</th>' +
        '        <th class="text-right">Neto</th><th class="text-right">IVA</th><th class="text-right">Total</th>' +
        '      </tr>' +
        '    </thead>' +
        '    <tbody>' + filas + '</tbody>' +
        '    <tfoot>' +
        '      <tr class="table-active">' +
        '        <td colspan="7" class="text-right"><strong>TOTALES</strong></td>' +
        '        <td class="text-right"><strong>$' + ventaNeta.toFixed(2) + '</strong></td>' +
        '        <td class="text-right"><strong>$' + totalIva.toFixed(2)  + '</strong></td>' +
        '        <td class="text-right"><strong>$' + totalVenta.toFixed(2) + '</strong></td>' +
        '      </tr>' +
        '    </tfoot>' +
        '  </table>' +
        resumen +
        '  <div class="mt-4 row">' +
        '    <div class="col-6"><p>Firma autorizada: ___________________________</p></div>' +
        '    <div class="col-6"><p>Sello: ___________________________</p></div>' +
        '  </div>' +
        '</div>';

    return html;
}

function maskTarjeta(num) {
    if (!num || num.length < 8) return num;
    return num.substring(0, 4) + '****' + num.substring(num.length - 4);
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
