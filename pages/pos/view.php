<div class="content">
    <!-- PAGE HEADER -->
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">PUNTO DE VENTA</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Punto de Venta</li>
                        </ol>
                    </nav>
                </div>
                <?php if (!empty($_SESSION['loc_id'])): ?>
                <div class="ml-auto">
                    <span class="badge badge-info p-2" style="font-size:0.9rem;">
                        <i class="icon dripicons-location"></i>
                        <?php
                            require_once 'config/database.php';
                            $loc_id = (int)$_SESSION['loc_id'];
                            $rLoc = mysqli_query($mysqli, "SELECT l.loc_direccion, m.mar_descripcion FROM local l JOIN marca m ON l.mar_id = m.mar_id WHERE l.loc_id = $loc_id");
                            if ($rowLoc = mysqli_fetch_assoc($rLoc)) {
                                echo htmlspecialchars($rowLoc['mar_descripcion'] . ' – ' . $rowLoc['loc_direccion']);
                            }
                        ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="container m-t-30">
        <div class="row">

            <!-- COLUMNA IZQUIERDA: Búsqueda y venta -->
            <div class="col-lg-7">

                <!-- BÚSQUEDA POR CÉDULA -->
                <div class="card">
                    <h5 class="card-header"><i class="icon dripicons-search"></i> Buscar Empleado</h5>
                    <div class="card-body">
                        <div class="input-group">
                            <input type="text" id="cedula_input" class="form-control form-control-lg"
                                   placeholder="Ingrese cédula del empleado..."
                                   maxlength="20" autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn-primary" id="btn_buscar" type="button">
                                    <i class="icon dripicons-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                        <div id="alerta_busqueda" class="mt-2" style="display:none;"></div>
                    </div>
                </div>

                <!-- DATOS DEL EMPLEADO (oculto hasta buscar) -->
                <div class="card" id="card_empleado" style="display:none;">
                    <h5 class="card-header bg-success text-white"><i class="icon dripicons-user"></i> Datos del Empleado</h5>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><small class="text-muted">Nombre</small></p>
                                <h5 id="emp_nombre" class="font-weight-bold"></h5>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><small class="text-muted">Cédula</small></p>
                                <h5 id="emp_cedula"></h5>
                            </div>
                            <div class="col-md-6 mt-2">
                                <p class="mb-1"><small class="text-muted">Empresa / Convenio</small></p>
                                <h6 id="emp_empresa" class="text-primary"></h6>
                            </div>
                            <div class="col-md-6 mt-2">
                                <p class="mb-1"><small class="text-muted">Tipo de beneficio</small></p>
                                <h6 id="emp_tipo_beneficio"></h6>
                            </div>
                        </div>
                        <!-- CUPO -->
                        <hr>
                        <div class="row text-center" id="fila_cupo">
                            <div class="col-6">
                                <p class="mb-0 text-muted small">Cupo Asignado</p>
                                <h4 class="text-dark font-weight-bold" id="emp_cupo_asignado">$0.00</h4>
                            </div>
                            <div class="col-6">
                                <p class="mb-0 text-muted small">Cupo Disponible</p>
                                <h4 class="text-success font-weight-bold" id="emp_cupo_disponible">$0.00</h4>
                            </div>
                        </div>
                        <!-- DESCUENTO (solo si tipo=Porcentaje) -->
                        <div id="fila_descuento" style="display:none;" class="text-center mt-2">
                            <p class="mb-0 text-muted small">Descuento aplicable</p>
                            <h4 class="text-info font-weight-bold" id="emp_descuento"></h4>
                        </div>
                        <input type="hidden" id="per_id_hidden">
                        <input type="hidden" id="cupo_disponible_hidden">
                    </div>
                </div>

                <!-- FORMULARIO DE VENTA (oculto hasta buscar) -->
                <div class="card" id="card_venta" style="display:none;">
                    <h5 class="card-header"><i class="icon dripicons-shopping-bag"></i> Registrar Venta</h5>
                    <div class="card-body">

                        <!-- Monto convenio -->
                        <div class="form-group">
                            <label for="monto_convenio">Monto a cargar al convenio <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                <input type="number" id="monto_convenio" class="form-control form-control-lg"
                                       placeholder="0.00" min="0.01" step="0.01">
                            </div>
                            <small id="aviso_cupo" class="text-danger" style="display:none;"></small>
                        </div>

                        <!-- Pago mixto (oculto por defecto) -->
                        <div id="div_pago_mixto" style="display:none;">
                            <div class="alert alert-warning">
                                <i class="icon dripicons-warning"></i>
                                El monto supera el cupo disponible. Ingrese el diferencial con otro medio de pago.
                            </div>
                            <div class="form-group">
                                <label for="monto_externo">Monto pago externo <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" id="monto_externo" class="form-control"
                                           placeholder="0.00" min="0" step="0.01" value="0">
                                </div>
                            </div>
                        </div>

                        <!-- Resumen -->
                        <div id="div_resumen" class="alert alert-light border" style="display:none;">
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted">Cargo convenio</small>
                                    <h5 class="text-success" id="res_convenio">$0.00</h5>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Pago externo</small>
                                    <h5 class="text-warning" id="res_externo">$0.00</h5>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Total</small>
                                    <h5 class="text-dark font-weight-bold" id="res_total">$0.00</h5>
                                </div>
                            </div>
                        </div>

                        <div id="alerta_venta" class="mt-2" style="display:none;"></div>

                        <button class="btn btn-success btn-lg btn-block mt-3" id="btn_confirmar">
                            <i class="icon dripicons-checkmark"></i> Confirmar Venta
                        </button>
                        <button class="btn btn-outline-secondary btn-block mt-2" id="btn_nueva">
                            <i class="icon dripicons-return"></i> Nueva búsqueda
                        </button>
                    </div>
                </div>
            </div>

            <!-- Link a historial completo -->
            <div class="col-lg-5">
                <div class="card text-center p-4">
                    <i class="icon dripicons-clockwise" style="font-size:2.5rem; color:#6c757d;"></i>
                    <h5 class="mt-3">Historial de Ventas</h5>
                    <p class="text-muted">Consulta y filtra las ventas por fecha desde el módulo de historial.</p>
                    <a href="?module=pos_historial" class="btn btn-outline-primary">
                        <i class="icon dripicons-exit"></i> Ver Historial
                    </a>
                </div>
            </div>

        </div><!-- /row -->
    </section>
</div>

<!-- MODAL VOUCHER -->
<div class="modal fade" id="modal_voucher" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Voucher de Venta</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="voucher_content">
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="btn_imprimir_voucher">
                    <i class="icon dripicons-print"></i> Imprimir
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    var per_id_actual   = null;
    var cupo_disponible = 0;
    var con_id_actual   = null;

    // -------------------------------------------------------
    // Buscar empleado
    // -------------------------------------------------------
    function buscarEmpleado() {
        var cedula = $('#cedula_input').val().trim();
        if (cedula === '') return;

        $('#btn_buscar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        ocultarAlerta('alerta_busqueda');

        $.ajax({
            url: 'ajax/pos/pos.php',
            type: 'GET',
            data: { action: 'buscar', cedula: cedula },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    mostrarEmpleado(resp.data);
                } else {
                    mostrarAlerta('alerta_busqueda', 'danger', resp.mensaje);
                    ocultarVenta();
                }
            },
            error: function () {
                mostrarAlerta('alerta_busqueda', 'danger', 'Error de conexión');
            },
            complete: function () {
                $('#btn_buscar').prop('disabled', false).html('<i class="icon dripicons-search"></i> Buscar');
            }
        });
    }

    $('#btn_buscar').on('click', buscarEmpleado);
    $('#cedula_input').on('keypress', function (e) {
        if (e.which === 13) buscarEmpleado();
    });

    // -------------------------------------------------------
    // Mostrar datos del empleado
    // -------------------------------------------------------
    function mostrarEmpleado(data) {
        per_id_actual   = data.per_id;
        cupo_disponible = parseFloat(data.per_cupo_disponible) || 0;

        $('#emp_nombre').text(data.per_nombre);
        $('#emp_cedula').text(data.per_documento);
        $('#emp_empresa').text(data.cli_descripcion);
        $('#per_id_hidden').val(data.per_id);
        $('#cupo_disponible_hidden').val(cupo_disponible);

        if (data.cli_tipo_beneficio === 'Porcentaje') {
            $('#emp_tipo_beneficio').html('<span class="badge badge-info">Descuento ' + data.cli_valor_beneficio + '%</span>');
            $('#fila_cupo').hide();
            $('#fila_descuento').show();
            $('#emp_descuento').text(data.cli_valor_beneficio + '%');
        } else {
            $('#emp_tipo_beneficio').html('<span class="badge badge-success">Cupo</span>');
            $('#fila_cupo').show();
            $('#fila_descuento').hide();
            $('#emp_cupo_asignado').text('$' + parseFloat(data.per_cupo_asignado || 0).toFixed(2));
            $('#emp_cupo_disponible').text('$' + cupo_disponible.toFixed(2));
        }

        $('#card_empleado').slideDown();
        $('#card_venta').slideDown();
        $('#monto_convenio').val('').focus();
        $('#div_pago_mixto').hide();
        $('#div_resumen').hide();
        ocultarAlerta('alerta_venta');
    }

    // -------------------------------------------------------
    // Validar monto mientras escribe
    // -------------------------------------------------------
    $('#monto_convenio').on('input', function () {
        var monto = parseFloat($(this).val()) || 0;

        if (monto <= 0) {
            $('#div_pago_mixto').hide();
            $('#div_resumen').hide();
            return;
        }

        if (monto > cupo_disponible) {
            var diferencial = (monto - cupo_disponible).toFixed(2);
            $('#aviso_cupo').text('Supera el cupo. Se usará $' + cupo_disponible.toFixed(2) + ' del convenio.').show();
            $('#monto_externo').val(diferencial);
            $('#div_pago_mixto').slideDown();
            actualizarResumen(cupo_disponible, parseFloat(diferencial));
        } else {
            $('#aviso_cupo').hide();
            $('#div_pago_mixto').hide();
            $('#monto_externo').val(0);
            actualizarResumen(monto, 0);
        }

        $('#div_resumen').slideDown();
    });

    $('#monto_externo').on('input', function () {
        var monto     = parseFloat($('#monto_convenio').val()) || 0;
        var externo   = parseFloat($(this).val()) || 0;
        var convenio  = Math.min(monto, cupo_disponible);
        actualizarResumen(convenio, externo);
    });

    function actualizarResumen(convenio, externo) {
        $('#res_convenio').text('$' + convenio.toFixed(2));
        $('#res_externo').text('$' + externo.toFixed(2));
        $('#res_total').text('$' + (convenio + externo).toFixed(2));
    }

    // -------------------------------------------------------
    // Confirmar venta
    // -------------------------------------------------------
    $('#btn_confirmar').on('click', function () {
        var monto    = parseFloat($('#monto_convenio').val()) || 0;
        var externo  = parseFloat($('#monto_externo').val()) || 0;
        var convenio = Math.min(monto, cupo_disponible);

        if (!per_id_actual || convenio <= 0) {
            mostrarAlerta('alerta_venta', 'warning', 'Ingrese un monto válido');
            return;
        }

        $('#btn_confirmar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Procesando...');
        ocultarAlerta('alerta_venta');

        $.ajax({
            url: 'ajax/pos/pos.php',
            type: 'POST',
            data: {
                action:          'registrar',
                per_id:          per_id_actual,
                monto_convenio:  convenio.toFixed(2),
                monto_externo:   externo.toFixed(2)
            },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    con_id_actual = resp.con_id;
                    cargarVoucher(resp.con_id);
                } else {
                    mostrarAlerta('alerta_venta', 'danger', resp.mensaje);
                    $('#btn_confirmar').prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Confirmar Venta');
                }
            },
            error: function () {
                mostrarAlerta('alerta_venta', 'danger', 'Error de conexión');
                $('#btn_confirmar').prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Confirmar Venta');
            }
        });
    });

    // -------------------------------------------------------
    // Cargar y mostrar voucher
    // -------------------------------------------------------
    function cargarVoucher(con_id, reimprimir) {
        reimprimir = reimprimir || false;

        $.ajax({
            url: 'ajax/pos/pos.php',
            type: 'GET',
            data: { action: 'voucher', con_id: con_id },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    renderVoucher(resp.data, reimprimir);
                    $('#modal_voucher').modal('show');
                }
            }
        });
    }

    function renderVoucher(d, reimprimir) {
        var reimpresionBadge = reimprimir ? '<div class="text-center"><span class="badge badge-warning">REIMPRESIÓN</span><br><small>' + new Date().toLocaleString() + '</small></div><hr>' : '';

        var html = '<div id="voucher_print" style="font-family:monospace; font-size:12px; padding:10px;">'
            + reimpresionBadge
            + '<div class="text-center mb-2"><strong>SGC ARGOS</strong><br>'
            + '<strong>COMPROBANTE DE CONSUMO</strong></div>'
            + '<hr>'
            + '<table class="table table-sm table-borderless" style="font-size:11px;">'
            + '<tr><td><strong>N° Comprobante</strong></td><td class="text-right">#' + d.con_id + '</td></tr>'
            + '<tr><td><strong>Fecha</strong></td><td class="text-right">' + d.con_fecha + '</td></tr>'
            + '<tr><td><strong>Hora</strong></td><td class="text-right">' + d.con_hora + '</td></tr>'
            + '<tr><td><strong>Local</strong></td><td class="text-right">' + (d.loc_direccion || 'N/A') + '</td></tr>'
            + '<tr><td><strong>Cajero</strong></td><td class="text-right">' + (d.cajero || 'N/A') + '</td></tr>'
            + '</table><hr>'
            + '<table class="table table-sm table-borderless" style="font-size:11px;">'
            + '<tr><td><strong>Empleado</strong></td><td class="text-right">' + d.per_nombre + '</td></tr>'
            + '<tr><td><strong>Cédula</strong></td><td class="text-right">' + d.per_documento + '</td></tr>'
            + '<tr><td><strong>Empresa</strong></td><td class="text-right">' + d.cli_descripcion + '</td></tr>'
            + '</table><hr>'
            + '<table class="table table-sm table-borderless" style="font-size:11px;">'
            + '<tr><td>Cargo convenio</td><td class="text-right">$' + parseFloat(d.con_monto_convenio).toFixed(2) + '</td></tr>'
            + (parseFloat(d.con_monto_externo) > 0 ? '<tr><td>Pago externo</td><td class="text-right">$' + parseFloat(d.con_monto_externo).toFixed(2) + '</td></tr>' : '')
            + '<tr><td><strong>TOTAL</strong></td><td class="text-right"><strong>$' + parseFloat(d.con_valor_total).toFixed(2) + '</strong></td></tr>'
            + '</table><hr>'
            + '<div class="mt-3" style="font-size:10px;">'
            + '<p class="mb-1">Firma empleado: ___________________________</p>'
            + '<p class="mb-1">N° Cédula: ___________________________</p>'
            + '</div>'
            + '<div class="text-center mt-2" style="font-size:9px;">El comprobante firmado constituye respaldo legal del consumo</div>'
            + '</div>';

        $('#voucher_content').html(html);

        // Reset botón confirmar
        $('#btn_confirmar').prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Confirmar Venta');
        // Ocultar venta para nueva búsqueda
        if (!reimprimir) {
            ocultarVenta();
        }
    }

    // -------------------------------------------------------
    // Imprimir voucher
    // -------------------------------------------------------
    $('#btn_imprimir_voucher').on('click', function () {
        var contenido = document.getElementById('voucher_print').innerHTML;
        var ventana = window.open('', '_blank', 'width=400,height=600');
        ventana.document.write('<html><head><title>Voucher SGC</title>');
        ventana.document.write('<link rel="stylesheet" href="css/bootstrap.min.css">');
        ventana.document.write('</head><body onload="window.print();window.close();">');
        ventana.document.write(contenido);
        ventana.document.write('</body></html>');
        ventana.document.close();

        if (con_id_actual) {
            $.post('ajax/pos/pos.php', { action: 'registrar', mark_printed: con_id_actual });
        }
    });

    // -------------------------------------------------------
    // Nueva búsqueda
    // -------------------------------------------------------
    $('#btn_nueva').on('click', function () {
        ocultarVenta();
        $('#cedula_input').val('').focus();
    });

    function ocultarVenta() {
        $('#card_empleado').slideUp();
        $('#card_venta').slideUp();
        per_id_actual = null;
        cupo_disponible = 0;
        ocultarAlerta('alerta_busqueda');
        ocultarAlerta('alerta_venta');
        $('#div_pago_mixto').hide();
        $('#div_resumen').hide();
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------
    function mostrarAlerta(id, tipo, msg) {
        $('#' + id).html('<div class="alert alert-' + tipo + ' mb-0">' + msg + '</div>').show();
    }
    function ocultarAlerta(id) {
        $('#' + id).hide().html('');
    }

    $('#cedula_input').focus();
});
</script>
