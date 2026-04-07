<?php
require_once 'config/database.php';
$esAdmin = in_array($_SESSION['permisos_acceso'], ['Super Admin', 'Administrador']);
$hoy     = date('Y-m-d');
?>
<div class="content">
    <!-- PAGE HEADER -->
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">Historial de Ventas</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item"><a href="?module=pos">Punto de Venta</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Historial</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <section class="container m-t-30">

        <!-- FILTROS -->
        <div class="card">
            <h5 class="card-header"><i class="icon dripicons-search"></i> Filtros</h5>
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label>Fecha inicio</label>
                        <input type="date" id="f_inicio" class="form-control" value="<?= $hoy ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Fecha fin</label>
                        <input type="date" id="f_fin" class="form-control" value="<?= $hoy ?>">
                    </div>
                    <?php if ($esAdmin): ?>
                    <div class="col-md-3">
                        <label>Local</label>
                        <select id="f_local" class="form-control">
                            <option value="">Todos los locales</option>
                            <?php
                            $rLoc = mysqli_query($mysqli, "SELECT l.loc_id, l.loc_direccion, m.mar_descripcion FROM local l JOIN marca m ON l.mar_id = m.mar_id ORDER BY m.mar_descripcion, l.loc_direccion");
                            while ($loc = mysqli_fetch_assoc($rLoc)) {
                                echo '<option value="' . $loc['loc_id'] . '">' . htmlspecialchars($loc['mar_descripcion'] . ' – ' . $loc['loc_direccion']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3 mt-3 mt-md-0">
                        <button class="btn btn-primary btn-block" id="btn_filtrar">
                            <i class="icon dripicons-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RESUMEN -->
        <div class="row" id="div_resumen" style="display:none;">
            <div class="col-md-4">
                <div class="card widget-inline">
                    <div class="card-body text-center">
                        <h2 class="text-primary font-weight-bold" id="res_total_ventas">0</h2>
                        <p class="text-muted mb-0">Total ventas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card widget-inline">
                    <div class="card-body text-center">
                        <h2 class="text-success font-weight-bold" id="res_monto_convenio">$0.00</h2>
                        <p class="text-muted mb-0">Monto convenio</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card widget-inline">
                    <div class="card-body text-center">
                        <h2 class="text-warning font-weight-bold" id="res_monto_externo">$0.00</h2>
                        <p class="text-muted mb-0">Pago externo</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="icon dripicons-list"></i> Ventas</span>
                <span id="div_contador" class="text-muted small"></span>
            </div>
            <div class="card-body p-0">
                <div id="div_loading" class="text-center p-5 text-muted" style="display:none;">
                    <span class="spinner-border"></span><p class="mt-2">Cargando...</p>
                </div>
                <div id="div_vacio" class="text-center p-5 text-muted">
                    <i class="icon dripicons-search" style="font-size:2rem;"></i>
                    <p class="mt-2">Seleccione un rango de fechas y presione Buscar</p>
                </div>
                <div id="div_tabla" style="display:none; overflow-x:auto;">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Empleado</th>
                                <th>Empresa</th>
                                <th>Convenio</th>
                                <th>Externo</th>
                                <th>Total</th>
                                <th>Voucher</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tbody_ventas"></tbody>
                    </table>
                </div>
            </div>
        </div>

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

    var con_id_actual = null;

    // Buscar al cargar con fecha de hoy
    buscarVentas();

    $('#btn_filtrar').on('click', buscarVentas);

    function buscarVentas() {
        var inicio = $('#f_inicio').val();
        var fin    = $('#f_fin').val();

        if (!inicio || !fin) {
            alert('Seleccione ambas fechas');
            return;
        }

        $('#div_vacio').hide();
        $('#div_tabla').hide();
        $('#div_resumen').hide();
        $('#div_loading').show();

        var data = { action: 'historial_filtro', fecha_inicio: inicio, fecha_fin: fin };

        <?php if ($esAdmin): ?>
        var local = $('#f_local').val();
        if (local) data.loc_id = local;
        <?php endif; ?>

        $.ajax({
            url: 'ajax/pos/pos.php',
            type: 'GET',
            data: data,
            dataType: 'json',
            success: function (resp) {
                $('#div_loading').hide();
                if (!resp.success) { $('#div_vacio').show(); return; }

                if (resp.data.length === 0) {
                    $('#div_vacio').html('<i class="icon dripicons-shopping-bag" style="font-size:2rem;"></i><p class="mt-2">Sin ventas en el período</p>').show();
                    return;
                }

                renderTabla(resp.data);
                renderResumen(resp.data);
                $('#div_contador').text(resp.data.length + ' registros');
            },
            error: function () {
                $('#div_loading').hide();
                $('#div_vacio').html('<div class="alert alert-danger m-3">Error de conexión</div>').show();
            }
        });
    }

    function renderTabla(data) {
        var html = '';
        data.forEach(function (v) {
            var impreso = v.con_voucher_impreso == 1
                ? '<span class="badge badge-success">Impreso</span>'
                : '<span class="badge badge-secondary">Sin imprimir</span>';
            html += '<tr>'
                + '<td>#' + v.con_id + '</td>'
                + '<td>' + v.con_fecha + '</td>'
                + '<td>' + v.con_hora + '</td>'
                + '<td><strong>' + v.per_nombre + '</strong><br><small class="text-muted">' + v.per_documento + '</small></td>'
                + '<td>' + v.cli_descripcion + '</td>'
                + '<td class="text-success">$' + parseFloat(v.con_monto_convenio).toFixed(2) + '</td>'
                + '<td class="text-warning">' + (parseFloat(v.con_monto_externo) > 0 ? '$' + parseFloat(v.con_monto_externo).toFixed(2) : '—') + '</td>'
                + '<td><strong>$' + parseFloat(v.con_valor_total).toFixed(2) + '</strong></td>'
                + '<td>' + impreso + '</td>'
                + '<td><button class="btn btn-xs btn-outline-secondary btn-reimprimir" data-id="' + v.con_id + '" style="font-size:11px;padding:1px 6px;">'
                + '<i class="icon dripicons-print"></i></button></td>'
                + '</tr>';
        });
        $('#tbody_ventas').html(html);
        $('#div_tabla').show();
    }

    function renderResumen(data) {
        var totalVentas   = data.length;
        var totalConvenio = 0;
        var totalExterno  = 0;
        data.forEach(function (v) {
            totalConvenio += parseFloat(v.con_monto_convenio) || 0;
            totalExterno  += parseFloat(v.con_monto_externo)  || 0;
        });
        $('#res_total_ventas').text(totalVentas);
        $('#res_monto_convenio').text('$' + totalConvenio.toFixed(2));
        $('#res_monto_externo').text('$' + totalExterno.toFixed(2));
        $('#div_resumen').show();
    }

    // Reimprimir voucher
    $(document).on('click', '.btn-reimprimir', function () {
        con_id_actual = $(this).data('id');
        $.ajax({
            url: 'ajax/pos/pos.php',
            type: 'GET',
            data: { action: 'voucher', con_id: con_id_actual },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    renderVoucher(resp.data, true);
                    $('#modal_voucher').modal('show');
                }
            }
        });
    });

    function renderVoucher(d, reimprimir) {
        var reimpresionBadge = reimprimir ? '<div class="text-center"><span class="badge badge-warning">REIMPRESIÓN</span><br><small>' + new Date().toLocaleString() + '</small></div><hr>' : '';
        var html = '<div id="voucher_print" style="font-family:monospace;font-size:12px;padding:10px;">'
            + reimpresionBadge
            + '<div class="text-center mb-2"><strong>SGC ARGOS</strong><br><strong>COMPROBANTE DE CONSUMO</strong></div><hr>'
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
            + '<div class="mt-3" style="font-size:10px;"><p class="mb-1">Firma empleado: ___________________________</p><p class="mb-1">N° Cédula: ___________________________</p></div>'
            + '<div class="text-center mt-2" style="font-size:9px;">El comprobante firmado constituye respaldo legal del consumo</div>'
            + '</div>';
        $('#voucher_content').html(html);
    }

    $('#btn_imprimir_voucher').on('click', function () {
        var contenido = document.getElementById('voucher_print').innerHTML;
        var ventana = window.open('', '_blank', 'width=400,height=600');
        ventana.document.write('<html><head><title>Voucher SGC</title>');
        ventana.document.write('<link rel="stylesheet" href="css/bootstrap.min.css">');
        ventana.document.write('</head><body onload="window.print();window.close();">');
        ventana.document.write(contenido);
        ventana.document.write('</body></html>');
        ventana.document.close();
    });

});
</script>
