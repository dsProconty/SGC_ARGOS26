<div class="content">
    <!-- PAGE HEADER – igual que el resto del sistema -->
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
                <div class="ml-auto d-flex align-items-center">
                    <?php if (!empty($_SESSION['loc_id'])): ?>
                    <span class="badge badge-info p-2 mr-3" style="font-size:0.9rem;">
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
                    <?php endif; ?>
                    <a href="?module=pos_historial" class="btn btn-outline-secondary btn-sm">
                        <i class="icon dripicons-clockwise"></i> Ver Historial
                    </a>
                </div>
            </div>
        </div>
    </header>

    <section class="container m-t-30">
        <div class="row">

            <!-- ===== COL 1: BÚSQUEDA + DATOS EMPLEADO ===== -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <h5 class="card-header">
                        <i class="icon dripicons-search"></i> Buscar Empleado
                    </h5>
                    <div class="card-body">

                        <div class="input-group">
                            <input type="text" id="cedula_input" class="form-control form-control-lg"
                                   placeholder="Cédula del empleado o código Gift Card..."
                                   maxlength="50" autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn-primary" id="btn_buscar" type="button">
                                    <i class="icon dripicons-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                        <div id="alerta_busqueda" class="mt-2" style="display:none;"></div>

                        <!-- Estado vacío -->
                        <div id="div_empleado_vacio" class="text-center text-muted mt-4 py-3">
                            <i class="icon dripicons-user" style="font-size:2.5rem; opacity:0.25;"></i>
                            <p class="mt-2 mb-0" style="font-size:0.85rem;">Ingrese una cédula o código Gift Card</p>
                        </div>

                        <!-- Panel Gift Card (oculto hasta buscar GC) -->
                        <div id="div_giftcard" style="display:none;">
                            <hr class="mt-3 mb-3">
                            <div class="text-center">
                                <span class="badge badge-info p-2 mb-2" style="font-size:1rem;">
                                    <i class="icon dripicons-card"></i> GIFT CARD
                                </span>
                                <p class="mb-1"><small class="text-muted">Código</small></p>
                                <h5 class="font-weight-bold" id="gc_codigo_display"></h5>
                            </div>
                            <hr class="mb-3">
                            <div class="row text-center">
                                <div class="col-6">
                                    <p class="mb-1 text-muted"><small>Saldo Disponible</small></p>
                                    <h4 class="text-success font-weight-bold" id="gc_saldo_display">$0.00</h4>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1 text-muted"><small>Vence</small></p>
                                    <h5 class="text-info" id="gc_vence_display"></h5>
                                </div>
                            </div>
                            <input type="hidden" id="gc_cgc_id_hidden">
                            <input type="hidden" id="gc_saldo_hidden">
                        </div>

                        <!-- Datos del empleado (oculto hasta buscar) -->
                        <div id="div_empleado" style="display:none;">
                            <hr class="mt-3 mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-1"><small class="text-muted">Nombre</small></p>
                                    <p class="font-weight-bold mb-3" id="emp_nombre"></p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1"><small class="text-muted">Cédula</small></p>
                                    <p class="font-weight-bold mb-3" id="emp_cedula"></p>
                                </div>
                                <div class="col-8">
                                    <p class="mb-1"><small class="text-muted">Empresa / Convenio</small></p>
                                    <p class="text-primary mb-3" id="emp_empresa"></p>
                                </div>
                                <div class="col-4">
                                    <p class="mb-1"><small class="text-muted">Tipo de beneficio</small></p>
                                    <p class="mb-3" id="emp_tipo_beneficio"></p>
                                </div>
                            </div>
                            <hr class="mt-0 mb-3">
                            <!-- Cupos -->
                            <div class="row text-center" id="fila_cupo">
                                <div class="col-6">
                                    <p class="mb-1 text-muted"><small>Cupo Asignado</small></p>
                                    <h4 class="text-dark font-weight-bold" id="emp_cupo_asignado">$0.00</h4>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1 text-muted"><small>Cupo Disponible</small></p>
                                    <h4 class="text-success font-weight-bold" id="emp_cupo_disponible">$0.00</h4>
                                </div>
                            </div>
                            <!-- Descuento solo si tipo=Porcentaje -->
                            <div id="fila_descuento" style="display:none;" class="text-center mt-2">
                                <p class="mb-1 text-muted"><small>Descuento aplicable</small></p>
                                <h4 class="text-info font-weight-bold" id="emp_descuento"></h4>
                            </div>
                            <input type="hidden" id="per_id_hidden">
                            <input type="hidden" id="cupo_disponible_hidden">
                        </div>

                    </div>
                </div>
            </div>

            <!-- ===== COL 2: REGISTRAR VENTA ===== -->
            <div class="col-lg-6 mb-4">
                <div class="card" id="card_venta" style="opacity:0.45; pointer-events:none;">
                    <h5 class="card-header">
                        <i class="icon dripicons-shopping-bag"></i> Registrar Venta
                    </h5>
                    <div class="card-body">

                        <!-- Descripción -->
                        <div class="form-group">
                            <label for="con_descripcion">
                                Descripción del consumo <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="con_descripcion" class="form-control form-control-lg"
                                   placeholder="Ej: Almuerzo, cena, consumo en local..."
                                   maxlength="200" autocomplete="off">
                        </div>

                        <!-- Monto convenio + Pago externo en una sola línea -->
                        <div class="form-group">
                            <label>Monto <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-6">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" id="monto_convenio" class="form-control form-control-lg"
                                               placeholder="0.00" min="0.01" step="0.01">
                                    </div>
                                    <small class="text-muted">Cargo al convenio</small>
                                </div>
                                <div class="col-6">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" id="monto_externo" class="form-control form-control-lg"
                                               placeholder="0.00" min="0" step="0.01" value="0">
                                    </div>
                                    <small class="text-muted">Pago externo</small>
                                </div>
                            </div>
                            <small id="aviso_cupo" class="text-danger mt-1" style="display:none;"></small>
                        </div>

                        <!-- Aviso pago mixto -->
                        <div id="div_aviso_mixto" style="display:none;">
                            <div class="alert alert-warning">
                                <span class="icon"><i class="dripicons-warning"></i></span>
                                <span class="text">El monto supera el cupo. Complete el diferencial en "Pago externo".</span>
                            </div>
                        </div>

                        <!-- Resumen -->
                        <div id="div_resumen" class="alert alert-light border" style="display:none;">
                            <div class="row text-center mb-2">
                                <div class="col-4">
                                    <small class="text-muted d-block">Convenio</small>
                                    <strong class="text-success" id="res_convenio">$0.00</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Externo</small>
                                    <strong class="text-warning" id="res_externo">$0.00</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Total</small>
                                    <strong class="text-dark" id="res_total">$0.00</strong>
                                </div>
                            </div>
                            <hr class="my-1">
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block">Subtotal</small>
                                    <span class="text-secondary" id="res_subtotal">$0.00</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block" id="lbl_iva_pct">IVA 15%</small>
                                    <span class="text-secondary" id="res_iva">$0.00</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">IVA incluido</small>
                                    <span class="badge badge-secondary" style="font-size:0.75rem;">En precio</span>
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

        </div><!-- /row -->
    </section>
</div>

<!-- ===== MODAL VOUCHER ===== -->
<div class="modal fade" id="modal_voucher" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Voucher de Venta</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="voucher_content"></div>
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
    var modo            = null;   // 'empleado' | 'giftcard'
    var gc_cgc_id       = null;
    var gc_saldo        = 0;
    var IVA_PCT         = 15;     // se actualiza desde el servidor al cargar

    // Cargar % IVA configurado
    $.getJSON('ajax/pos/pos.php?action=get_config', function(r) {
        if (r.success) IVA_PCT = r.iva_porcentaje;
    });

    // -------------------------------------------------------
    // Buscar (cédula o Gift Card)
    // -------------------------------------------------------
    function buscar() {
        var input = $('#cedula_input').val().trim();
        if (input === '') return;
        $('#btn_buscar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        ocultarAlerta('alerta_busqueda');

        $.ajax({
            url: 'ajax/pos/pos.php',
            type: 'GET',
            data: { action: 'buscar', cedula: input },
            dataType: 'json',
            success: function (resp) {
                if (!resp.success) {
                    // Notificación según tipo de error de GC
                    var tipo_alerta = (resp.tipo === 'giftcard_vencida') ? 'warning' : 'danger';
                    mostrarAlerta('alerta_busqueda', tipo_alerta, resp.mensaje);
                    ocultarPaneles();
                    return;
                }
                if (resp.tipo === 'empleado') {
                    mostrarEmpleado(resp.data);
                } else if (resp.tipo === 'giftcard') {
                    mostrarGiftCard(resp.data);
                }
            },
            error: function () { mostrarAlerta('alerta_busqueda', 'danger', 'Error de conexión'); },
            complete: function () { $('#btn_buscar').prop('disabled', false).html('<i class="icon dripicons-search"></i> Buscar'); }
        });
    }

    $('#btn_buscar').on('click', buscar);
    $('#cedula_input').on('keypress', function (e) { if (e.which === 13) buscar(); });

    // -------------------------------------------------------
    // Mostrar empleado
    // -------------------------------------------------------
    function mostrarEmpleado(data) {
        modo            = 'empleado';
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

        // Labels para modo empleado
        $('small.text-muted').filter(function(){ return $(this).text() === 'Cargo al convenio'; }).text('Cargo al convenio');
        $('#monto_convenio').attr('placeholder', '0.00').attr('max', cupo_disponible.toFixed(2));

        $('#div_empleado_vacio').hide();
        $('#div_giftcard').hide();
        $('#div_empleado').slideDown();
        activarFormVenta();
    }

    // -------------------------------------------------------
    // Mostrar Gift Card
    // -------------------------------------------------------
    function mostrarGiftCard(data) {
        modo       = 'giftcard';
        gc_cgc_id  = data.cgc_id;
        gc_saldo   = parseFloat(data.saldo) || 0;

        $('#gc_codigo_display').text(data.cgc_codigo);
        $('#gc_saldo_display').text('$' + gc_saldo.toFixed(2));
        $('#gc_vence_display').text(data.fecha_caducidad);
        $('#gc_cgc_id_hidden').val(data.cgc_id);
        $('#gc_saldo_hidden').val(gc_saldo);

        // Ajustar labels del formulario para modo GC
        $('#lbl_monto_convenio').text('Monto a usar de Gift Card');
        $('#monto_convenio')
            .attr('placeholder', '0.00 (máx $' + gc_saldo.toFixed(2) + ')')
            .attr('max', gc_saldo.toFixed(2));

        $('#div_empleado_vacio').hide();
        $('#div_empleado').hide();
        $('#div_giftcard').slideDown();
        activarFormVenta();
    }

    function activarFormVenta() {
        $('#card_venta').css({ opacity: '1', 'pointer-events': 'auto' });
        $('#con_descripcion').val('').focus();
        $('#monto_convenio').val('');
        $('#monto_externo').val('0');
        $('#div_aviso_mixto').hide();
        $('#div_resumen').hide();
        ocultarAlerta('alerta_venta');
    }

    // -------------------------------------------------------
    // Validar montos al escribir
    // -------------------------------------------------------
    $('#monto_convenio').on('input', function () {
        var monto   = parseFloat($(this).val()) || 0;
        var limite  = (modo === 'giftcard') ? gc_saldo : cupo_disponible;

        if (monto <= 0) { $('#div_aviso_mixto').hide(); $('#div_resumen').hide(); return; }

        if (monto > limite) {
            if (modo === 'giftcard') {
                // En modo GC no se permite superar el saldo: se recorta al máximo
                $(this).val(limite.toFixed(2));
                monto = limite;
            }
            var dif = (monto - limite).toFixed(2);
            if (parseFloat(dif) > 0) {
                $('#aviso_cupo').text('Supera el cupo. Se usará $' + limite.toFixed(2) + '.').show();
                $('#monto_externo').val(dif);
                $('#div_aviso_mixto').slideDown();
                actualizarResumen(limite, parseFloat(dif));
            } else {
                $('#aviso_cupo').hide();
                $('#div_aviso_mixto').hide();
                $('#monto_externo').val(0);
                actualizarResumen(monto, 0);
            }
        } else {
            $('#aviso_cupo').hide();
            $('#div_aviso_mixto').hide();
            $('#monto_externo').val(0);
            actualizarResumen(monto, 0);
        }
        $('#div_resumen').slideDown();
    });

    $('#monto_externo').on('input', function () {
        var monto   = parseFloat($('#monto_convenio').val()) || 0;
        var limite  = (modo === 'giftcard') ? gc_saldo : cupo_disponible;
        var externo = parseFloat($(this).val()) || 0;
        actualizarResumen(Math.min(monto, limite), externo);
    });

    function actualizarResumen(principal, externo) {
        var total    = principal + externo;
        var subtotal = total / (1 + IVA_PCT / 100);
        var iva      = total - subtotal;
        $('#res_convenio').text('$' + principal.toFixed(2));
        $('#res_externo').text('$' + externo.toFixed(2));
        $('#res_total').text('$' + total.toFixed(2));
        $('#res_subtotal').text('$' + subtotal.toFixed(2));
        $('#res_iva').text('$' + iva.toFixed(2));
        $('#lbl_iva_pct').text('IVA ' + IVA_PCT + '%');
    }

    // -------------------------------------------------------
    // Confirmar venta
    // -------------------------------------------------------
    $('#btn_confirmar').on('click', function () {
        var descripcion = $('#con_descripcion').val().trim();
        var monto       = parseFloat($('#monto_convenio').val()) || 0;
        var externo     = parseFloat($('#monto_externo').val()) || 0;
        var limite      = (modo === 'giftcard') ? gc_saldo : cupo_disponible;
        var principal   = Math.min(monto, limite);

        if (!descripcion) { mostrarAlerta('alerta_venta', 'warning', 'Ingrese una descripción del consumo'); $('#con_descripcion').focus(); return; }
        if (principal <= 0) { mostrarAlerta('alerta_venta', 'warning', 'Ingrese un monto válido'); return; }

        $('#btn_confirmar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Procesando...');
        ocultarAlerta('alerta_venta');

        var postData = { con_descripcion: descripcion, monto_externo: externo.toFixed(2) };

        if (modo === 'giftcard') {
            postData.action         = 'registrar_giftcard';
            postData.cgc_id         = gc_cgc_id;
            postData.monto_giftcard = principal.toFixed(2);
        } else {
            postData.action         = 'registrar';
            postData.per_id         = per_id_actual;
            postData.monto_convenio = principal.toFixed(2);
        }

        $.ajax({
            url: 'ajax/pos/pos.php',
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    con_id_actual = resp.con_id;
                    cargarVoucher(resp.con_id, false);
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
    // Voucher
    // -------------------------------------------------------
    function cargarVoucher(con_id, reimprimir) {
        $.ajax({
            url: 'ajax/pos/pos.php',
            type: 'GET',
            data: { action: 'voucher', con_id: con_id },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    renderVoucher(resp.data, reimprimir);
                    $('#modal_voucher').modal('show');
                    if (!reimprimir) ocultarVenta();
                }
            }
        });
    }

    function renderVoucher(d, reimprimir) {
        var reimpresionBadge = reimprimir
            ? '<div class="text-center"><span class="badge badge-warning">REIMPRESIÓN</span><br><small>' + new Date().toLocaleString() + '</small></div><hr>'
            : '';
        var descripcionRow = d.con_descripcion
            ? '<tr><td><strong>Descripción</strong></td><td class="text-right">' + htmlEsc(d.con_descripcion) + '</td></tr>'
            : '';
        // Sección beneficiario (empleado o gift card)
        var beneficiarioSection = '';
        if (d.per_nombre) {
            beneficiarioSection = '<table class="table table-sm table-borderless mb-0" style="font-size:11px;">'
                + '<tr><td><strong>Empleado</strong></td><td class="text-right">' + htmlEsc(d.per_nombre) + '</td></tr>'
                + '<tr><td><strong>Cédula</strong></td><td class="text-right">' + d.per_documento + '</td></tr>'
                + '<tr><td><strong>Empresa</strong></td><td class="text-right">' + htmlEsc(d.cli_descripcion) + '</td></tr>'
                + '</table><hr>';
        } else if (d.con_giftcard_codigo) {
            beneficiarioSection = '<table class="table table-sm table-borderless mb-0" style="font-size:11px;">'
                + '<tr><td><strong>Gift Card</strong></td><td class="text-right"><code>' + d.con_giftcard_codigo + '</code></td></tr>'
                + '</table><hr>';
        }

        var html = '<div id="voucher_print" style="font-family:monospace; font-size:12px; padding:10px;">'
            + reimpresionBadge
            + '<div class="text-center mb-2"><strong>SGC ARGOS</strong><br><strong>COMPROBANTE DE CONSUMO</strong></div><hr>'
            + '<table class="table table-sm table-borderless mb-0" style="font-size:11px;">'
            + '<tr><td><strong>N° Comprobante</strong></td><td class="text-right">#' + d.con_id + '</td></tr>'
            + '<tr><td><strong>Fecha</strong></td><td class="text-right">' + d.con_fecha + '</td></tr>'
            + '<tr><td><strong>Hora</strong></td><td class="text-right">' + d.con_hora + '</td></tr>'
            + '<tr><td><strong>Local</strong></td><td class="text-right">' + (d.loc_direccion || 'N/A') + '</td></tr>'
            + '<tr><td><strong>Cajero</strong></td><td class="text-right">' + (d.cajero || 'N/A') + '</td></tr>'
            + '</table><hr>'
            + beneficiarioSection
            + '<table class="table table-sm table-borderless mb-0" style="font-size:11px;">'
            + descripcionRow
            + (parseFloat(d.con_monto_convenio) > 0 ? '<tr><td>Cargo convenio</td><td class="text-right">$' + parseFloat(d.con_monto_convenio).toFixed(2) + '</td></tr>' : '')
            + (parseFloat(d.con_monto_giftcard) > 0 ? '<tr><td>Gift Card</td><td class="text-right">$' + parseFloat(d.con_monto_giftcard).toFixed(2) + '</td></tr>' : '')
            + (parseFloat(d.con_monto_externo) > 0 ? '<tr><td>Pago externo</td><td class="text-right">$' + parseFloat(d.con_monto_externo).toFixed(2) + '</td></tr>' : '')
            + '<tr><td colspan="2"><hr style="margin:4px 0;"></td></tr>'
            + '<tr><td>Subtotal</td><td class="text-right">$' + parseFloat(d.con_valor_neto).toFixed(2) + '</td></tr>'
            + '<tr><td>IVA (' + IVA_PCT + '%)</td><td class="text-right">$' + parseFloat(d.con_iva || 0).toFixed(2) + '</td></tr>'
            + '<tr><td><strong>TOTAL</strong></td><td class="text-right"><strong>$' + parseFloat(d.con_valor_total).toFixed(2) + '</strong></td></tr>'
            + '</table><hr>'
            + '<div class="mt-3" style="font-size:10px;"><p class="mb-1">Firma: ___________________________</p><p class="mb-1">N° Cédula: ___________________________</p></div>'
            + '<div class="text-center mt-2" style="font-size:9px;">El comprobante firmado constituye respaldo legal del consumo</div>'
            + '</div>';
        $('#voucher_content').html(html);
        $('#btn_confirmar').prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Confirmar Venta');
    }

    $('#btn_imprimir_voucher').on('click', function () {
        var contenido = document.getElementById('voucher_print').innerHTML;
        var ventana = window.open('', '_blank', 'width=400,height=600');
        ventana.document.write('<html><head><title>Voucher SGC</title><link rel="stylesheet" href="assets/css/vendor/bootstrap.css"></head><body onload="window.print();window.close();">');
        ventana.document.write(contenido);
        ventana.document.write('</body></html>');
        ventana.document.close();
    });

    $('#btn_nueva').on('click', function () { ocultarVenta(); $('#cedula_input').val('').focus(); });

    function ocultarVenta() {
        ocultarPaneles();
        $('#card_venta').css({ opacity: '0.45', 'pointer-events': 'none' });
        per_id_actual = null; cupo_disponible = 0;
        gc_cgc_id = null; gc_saldo = 0; modo = null;
        ocultarAlerta('alerta_busqueda');
        ocultarAlerta('alerta_venta');
        $('#div_aviso_mixto').hide();
        $('#div_resumen').hide();
        $('#aviso_cupo').hide();
    }

    function ocultarPaneles() {
        $('#div_empleado').slideUp();
        $('#div_giftcard').slideUp();
        $('#div_empleado_vacio').show();
    }

    function mostrarAlerta(id, tipo, msg) { $('#' + id).html('<div class="alert alert-' + tipo + ' mb-0">' + msg + '</div>').show(); }
    function ocultarAlerta(id) { $('#' + id).hide().html(''); }
    function htmlEsc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    $('#cedula_input').focus();
});
</script>
