$(document).ready(function () {
    cargarVentasDiferidas();

    // Buscar empleado
    $('#btn_buscar_vd').on('click', buscarEmpleadoVD);
    $('#vd_cedula').on('keypress', function (e) { if (e.which === 13) buscarEmpleadoVD(); });

    // Calcular cuota al cambiar monto o num cuotas
    $('#vd_monto_total, #vd_num_cuotas').on('input', calcularCuota);

    // Guardar
    $('#btn_guardar_vd').on('click', guardarVD);

    // Confirmar pago de cuota
    $('#btn_confirmar_cuota').on('click', confirmarCuota);

    // Confirmar liquidación
    $('#btn_confirmar_liquidar').on('click', confirmarLiquidar);

    $('#modal_nueva_vd').on('hidden.bs.modal', function () {
        $('#card_empleado_vd, #btn_guardar_vd, #resumen_vd').hide();
        $('#vd_cedula, #vd_per_id, #vd_descripcion, #vd_monto_total, #vd_num_cuotas, #vd_fecha_inicio').val('');
        $('#alerta_vd').html('');
    });
});

function abrirModalNueva() {
    $('#modal_nueva_vd').modal('show');
}

function buscarEmpleadoVD() {
    var cedula = $('#vd_cedula').val().trim();
    if (!cedula) return;

    $('#btn_buscar_vd').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
    $('#alerta_vd').html('');

    $.ajax({
        url: 'ajax/venta_diferida/venta_diferida.php',
        type: 'GET',
        data: { action: 'buscar_empleado', cedula: cedula },
        dataType: 'json',
        success: function (resp) {
            if (resp.success) {
                $('#vd_per_id').val(resp.data.per_id);
                $('#vd_emp_nombre').text(resp.data.per_nombre);
                $('#vd_emp_empresa').text(resp.data.cli_descripcion);
                $('#card_empleado_vd').slideDown();
                $('#btn_guardar_vd').show();
            } else {
                $('#alerta_vd').html('<div class="alert alert-danger mb-2">' + resp.mensaje + '</div>');
                $('#card_empleado_vd').hide();
                $('#btn_guardar_vd').hide();
            }
        },
        error: function () {
            $('#alerta_vd').html('<div class="alert alert-danger mb-2">Error de conexión</div>');
        },
        complete: function () {
            $('#btn_buscar_vd').prop('disabled', false).html('<i class="icon dripicons-search"></i> Buscar');
        }
    });
}

function calcularCuota() {
    var monto  = parseFloat($('#vd_monto_total').val()) || 0;
    var cuotas = parseInt($('#vd_num_cuotas').val()) || 0;
    if (monto > 0 && cuotas > 0) {
        var cuota = (monto / cuotas).toFixed(2);
        $('#vd_cuota_calculada').text('$' + cuota);
        $('#resumen_vd').show();
    } else {
        $('#resumen_vd').hide();
    }
}

function guardarVD() {
    var per_id       = $('#vd_per_id').val();
    var descripcion  = $('#vd_descripcion').val().trim();
    var monto_total  = parseFloat($('#vd_monto_total').val()) || 0;
    var num_cuotas   = parseInt($('#vd_num_cuotas').val()) || 0;
    var fecha_inicio = $('#vd_fecha_inicio').val();

    if (!per_id || !descripcion || monto_total <= 0 || num_cuotas <= 0 || !fecha_inicio) {
        $('#alerta_vd').html('<div class="alert alert-warning mb-2">Complete todos los campos.</div>');
        return;
    }

    $('#btn_guardar_vd').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

    $.ajax({
        url: 'ajax/venta_diferida/venta_diferida.php',
        type: 'POST',
        data: {
            action:       'crear',
            per_id:       per_id,
            descripcion:  descripcion,
            monto_total:  monto_total,
            num_cuotas:   num_cuotas,
            fecha_inicio: fecha_inicio
        },
        dataType: 'json',
        success: function (resp) {
            if (resp.success) {
                $('#modal_nueva_vd').modal('hide');
                cargarVentasDiferidas();
            } else {
                $('#alerta_vd').html('<div class="alert alert-danger mb-2">' + resp.mensaje + '</div>');
            }
        },
        error: function () {
            $('#alerta_vd').html('<div class="alert alert-danger mb-2">Error de conexión</div>');
        },
        complete: function () {
            $('#btn_guardar_vd').prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar');
        }
    });
}

function pagar_cuota(vd_id, pagadas, total, monto_cuota) {
    var siguiente = pagadas + 1;
    $('#vd_id_pagar').val(vd_id);
    $('#detalle_cuota').html(
        'Se registrará el pago de la cuota <strong>' + siguiente + ' de ' + total + '</strong>.<br>' +
        'Monto: <strong>$' + monto_cuota + '</strong>'
    );
    $('#alerta_cuota').html('');
    $('#modal_pagar_cuota').modal('show');
}

function confirmarCuota() {
    var vd_id = $('#vd_id_pagar').val();
    $('#btn_confirmar_cuota').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
        url: 'ajax/venta_diferida/venta_diferida.php',
        type: 'POST',
        data: { action: 'pagar_cuota', vd_id: vd_id },
        dataType: 'json',
        success: function (resp) {
            if (resp.success) {
                $('#modal_pagar_cuota').modal('hide');
                cargarVentasDiferidas();
            } else {
                $('#alerta_cuota').html('<div class="alert alert-danger mb-0">' + resp.mensaje + '</div>');
            }
        },
        error: function () {
            $('#alerta_cuota').html('<div class="alert alert-danger mb-0">Error de conexión</div>');
        },
        complete: function () {
            $('#btn_confirmar_cuota').prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Confirmar Pago');
        }
    });
}

function liquidar(vd_id, cuotas_restantes, saldo) {
    $('#vd_id_liquidar').val(vd_id);
    $('#detalle_liquidar').html(
        'Se liquidará el saldo pendiente de <strong>' + cuotas_restantes + ' cuota(s)</strong>.<br>' +
        'Monto a liquidar: <strong class="text-warning">$' + saldo + '</strong><br><br>' +
        '<small class="text-muted">Esta acción marcará la venta como completada.</small>'
    );
    $('#alerta_liquidar').html('');
    $('#modal_liquidar').modal('show');
}

function confirmarLiquidar() {
    var vd_id = $('#vd_id_liquidar').val();
    $('#btn_confirmar_liquidar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
        url: 'ajax/venta_diferida/venta_diferida.php',
        type: 'POST',
        data: { action: 'liquidar', vd_id: vd_id },
        dataType: 'json',
        success: function (resp) {
            if (resp.success) {
                $('#modal_liquidar').modal('hide');
                cargarVentasDiferidas();
            } else {
                $('#alerta_liquidar').html('<div class="alert alert-danger mb-0">' + resp.mensaje + '</div>');
            }
        },
        error: function () {
            $('#alerta_liquidar').html('<div class="alert alert-danger mb-0">Error de conexión</div>');
        },
        complete: function () {
            $('#btn_confirmar_liquidar').prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Confirmar Liquidación');
        }
    });
}

function cargarVentasDiferidas() {
    $('#loader_vd').html('<div class="text-center p-3"><span class="spinner-border"></span></div>');
    $.ajax({
        url: 'ajax/venta_diferida/venta_diferida.php?action=list',
        type: 'GET',
        success: function (response) {
            $('#loader_vd').html(response);
            $('#table_vd').dataTable({ order: [[0, 'desc']] });
        }
    });
}
