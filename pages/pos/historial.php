<?php
require_once 'config/database.php';
require_once 'helpers/session_helpers.php';
$esAdmin     = esSuperAdmin($mysqli) || tienePerfil($mysqli, 'Administrador');
$puedeAnular = esSuperAdmin($mysqli) || tienePermiso($mysqli, 'pos.anular');
$hoy         = date('Y-m-d');
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
                <div class="d-flex align-items-center">
                    <span id="div_contador" class="text-muted small mr-3"></span>
                    <button class="btn btn-sm btn-outline-danger mr-2" id="btn_exportar_pdf" style="display:none;" title="Exportar a PDF">
                        <i class="icon dripicons-document"></i> Exportar PDF
                    </button>
                    <button class="btn btn-sm btn-outline-success" id="btn_exportar_excel" style="display:none;" title="Exportar a Excel">
                        <i class="icon dripicons-download"></i> Exportar Excel
                    </button>
                </div>
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

<?php if ($puedeAnular): ?>
<!-- MODAL ANULAR VENTA (PV-F) -->
<div class="modal fade" id="modal_anular" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white"><i class="icon dripicons-warning"></i> Anular Venta #<span id="anular_con_id"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span class="text-white">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_anular"></div>
                <p class="text-muted mb-2">Esta acción no se puede deshacer. Si la venta usó cupo de convenio o Gift Card, el saldo se devuelve automáticamente.</p>
                <div class="form-group">
                    <label>Motivo de la anulación <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="anular_motivo" rows="3" placeholder="Ej: Producto no entregado, error de cobro..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn_confirmar_anular">
                    <i class="icon dripicons-checkmark"></i> Anular Venta
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL VER MOTIVO DE ANULACIÓN -->
<div class="modal fade" id="modal_ver_anulacion" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-information"></i> Detalle de Anulación</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="ver_anulacion_body">
                <div class="text-center"><span class="spinner-border spinner-border-sm"></span></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    var con_id_actual = null;
    var puedeAnular = <?php echo $puedeAnular ? 'true' : 'false'; ?>;
    var hoy = '<?= $hoy ?>';

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
        $('#btn_exportar_excel').hide();
        $('#btn_exportar_pdf').hide();
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

    // Datos en memoria para exportación Excel
    var _datosActuales = [];

    function renderTabla(data) {
        _datosActuales = data;
        var html = '';
        data.forEach(function (v) {
            var anulada = v.con_estado === 'anulado';
            var impreso = v.con_voucher_impreso == 1
                ? '<span class="badge badge-success">Impreso</span>'
                : '<span class="badge badge-secondary">Sin imprimir</span>';

            var acciones = '<button class="btn btn-xs btn-outline-secondary btn-reimprimir" data-id="' + v.con_id + '" style="font-size:11px;padding:1px 6px;" title="Reimprimir voucher">'
                + '<i class="icon dripicons-print"></i></button>';

            if (anulada) {
                acciones += ' <button class="btn btn-xs btn-outline-info btn-ver-anulacion" data-id="' + v.con_id + '" style="font-size:11px;padding:1px 6px;" title="Ver motivo de anulación">'
                    + '<i class="icon dripicons-information"></i></button>';
            } else if (puedeAnular && v.con_fecha === hoy) {
                acciones += ' <button class="btn btn-xs btn-outline-danger btn-anular" data-id="' + v.con_id + '" style="font-size:11px;padding:1px 6px;" title="Anular venta">'
                    + '<i class="icon dripicons-cross"></i></button>';
            }

            var rowClass = anulada ? ' class="table-secondary"' : '';
            var estadoTxt = anulada ? ' <span class="badge badge-danger">ANULADA</span>' : '';

            html += '<tr' + rowClass + '>'
                + '<td>#' + v.con_id + estadoTxt + '</td>'
                + '<td>' + v.con_fecha + '</td>'
                + '<td>' + v.con_hora + '</td>'
                + '<td><strong>' + htmlEsc(v.per_nombre) + '</strong><br><small class="text-muted">' + v.per_documento + '</small></td>'
                + '<td>' + htmlEsc(v.cli_descripcion) + '</td>'
                + '<td class="text-success">$' + parseFloat(v.con_monto_convenio).toFixed(2) + '</td>'
                + '<td class="text-warning">' + (parseFloat(v.con_monto_externo) > 0 ? '$' + parseFloat(v.con_monto_externo).toFixed(2) : '—') + '</td>'
                + '<td><strong>$' + parseFloat(v.con_valor_total).toFixed(2) + '</strong></td>'
                + '<td>' + impreso + '</td>'
                + '<td>' + acciones + '</td>'
                + '</tr>';
        });
        $('#tbody_ventas').html(html);
        $('#div_tabla').show();
        $('#btn_exportar_excel').show();
        $('#btn_exportar_pdf').show();
    }

    function renderResumen(data) {
        var vigentes = data.filter(function (v) { return v.con_estado !== 'anulado'; });
        var totalVentas   = vigentes.length;
        var totalConvenio = 0;
        var totalExterno  = 0;
        vigentes.forEach(function (v) {
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

    // PV-F: Anular venta del día con justificación
    $(document).on('click', '.btn-anular', function () {
        con_id_actual = $(this).data('id');
        $('#anular_con_id').text(con_id_actual);
        $('#anular_motivo').val('');
        $('#alerta_anular').html('');
        $('#modal_anular').modal('show');
    });

    $('#btn_confirmar_anular').on('click', function () {
        var motivo = $('#anular_motivo').val().trim();
        if (!motivo) {
            $('#alerta_anular').html('<div class="alert alert-danger">Indique el motivo de la anulación</div>');
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.ajax({
            url: 'ajax/pos/pos.php',
            type: 'POST',
            data: { action: 'anular_venta', con_id: con_id_actual, motivo: motivo },
            dataType: 'json',
            success: function (resp) {
                $btn.prop('disabled', false);
                if (resp.success) {
                    $('#modal_anular').modal('hide');
                    buscarVentas();
                } else {
                    $('#alerta_anular').html('<div class="alert alert-danger">' + htmlEsc(resp.mensaje || 'No se pudo anular la venta') + '</div>');
                }
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#alerta_anular').html('<div class="alert alert-danger">Error de conexión</div>');
            }
        });
    });

    // PV-F: Ver motivo/trazabilidad de una anulación
    $(document).on('click', '.btn-ver-anulacion', function () {
        var con_id = $(this).data('id');
        $('#ver_anulacion_body').html('<div class="text-center"><span class="spinner-border spinner-border-sm"></span></div>');
        $('#modal_ver_anulacion').modal('show');
        $.ajax({
            url: 'ajax/pos/pos.php',
            type: 'GET',
            data: { action: 'ver_anulacion', con_id: con_id },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    var d = resp.data;
                    $('#ver_anulacion_body').html(
                        '<table class="table table-sm table-borderless mb-0">'
                        + '<tr><td><strong>Anulada por</strong></td><td>' + htmlEsc(d.name_user) + '</td></tr>'
                        + '<tr><td><strong>Fecha</strong></td><td>' + htmlEsc(d.can_fecha) + '</td></tr>'
                        + '<tr><td><strong>Motivo</strong></td><td>' + htmlEsc(d.can_motivo) + '</td></tr>'
                        + '</table>'
                    );
                } else {
                    $('#ver_anulacion_body').html('<div class="alert alert-warning mb-0">' + htmlEsc(resp.mensaje || 'Sin registro de anulación') + '</div>');
                }
            },
            error: function () {
                $('#ver_anulacion_body').html('<div class="alert alert-danger mb-0">Error de conexión</div>');
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

    // PV-02: Exportar historial a Excel respetando el filtro activo
    $('#btn_exportar_excel').on('click', function () {
        if (!_datosActuales.length) return;

        var inicio = $('#f_inicio').val();
        var fin    = $('#f_fin').val();

        var filas = _datosActuales.map(function (v) {
            return {
                'N° Comprobante': '#' + v.con_id,
                'Fecha':          v.con_fecha,
                'Hora':           v.con_hora,
                'Empleado':       v.per_nombre,
                'Cédula':         v.per_documento,
                'Empresa':        v.cli_descripcion,
                'Convenio ($)':   parseFloat(v.con_monto_convenio).toFixed(2),
                'Externo ($)':    parseFloat(v.con_monto_externo).toFixed(2),
                'Total ($)':      parseFloat(v.con_valor_total).toFixed(2),
                'Voucher':        v.con_voucher_impreso == 1 ? 'Impreso' : 'Sin imprimir'
            };
        });

        var wb = new ExcelJS.Workbook();
        ArgosExport.rowsToSheet(wb, filas, 'Ventas');
        ArgosExport.download(wb, 'historial_ventas_' + inicio + '_a_' + fin + '.xlsx');
    });

    // PV-D: Exportar historial a PDF (imprimible) respetando el filtro activo,
    // para el cierre de caja. Mismo patrón que el PDF de Estado de Cuenta.
    $('#btn_exportar_pdf').on('click', function () {
        if (!_datosActuales.length) return;

        var inicio = $('#f_inicio').val();
        var fin    = $('#f_fin').val();
        var localTxt = $('#f_local option:selected').text() || 'Todos los locales';

        var filas = '';
        var totalConv = 0, totalExt = 0, totalGen = 0;
        _datosActuales.forEach(function (v) {
            var conv = parseFloat(v.con_monto_convenio) || 0;
            var ext  = parseFloat(v.con_monto_externo) || 0;
            var tot  = parseFloat(v.con_valor_total) || 0;
            totalConv += conv; totalExt += ext; totalGen += tot;
            filas += '<tr>'
                + '<td>#' + v.con_id + '</td>'
                + '<td>' + v.con_fecha + '</td>'
                + '<td>' + v.con_hora + '</td>'
                + '<td>' + htmlEsc(v.per_nombre) + '<br><small>' + v.per_documento + '</small></td>'
                + '<td>' + htmlEsc(v.cli_descripcion) + '</td>'
                + '<td class="text-right">$' + conv.toFixed(2) + '</td>'
                + '<td class="text-right">' + (ext > 0 ? '$' + ext.toFixed(2) : '—') + '</td>'
                + '<td class="text-right"><strong>$' + tot.toFixed(2) + '</strong></td>'
                + '</tr>';
        });

        var contenido = '<div class="hv-header">'
            + '<div class="hv-header-titulo">Historial de Ventas</div>'
            + '<div class="hv-header-sub">SGC ARGOS</div>'
            + '</div>'
            + '<div class="hv-meta">'
            + '<span><strong>Período:</strong> ' + inicio + ' al ' + fin + '</span>'
            + '<span><strong>Local:</strong> ' + localTxt + '</span>'
            + '<span><strong>Registros:</strong> ' + _datosActuales.length + '</span>'
            + '</div>'
            + '<table class="hv-table">'
            + '<thead><tr><th>#</th><th>Fecha</th><th>Hora</th><th>Empleado</th><th>Empresa</th>'
            + '<th class="text-right">Convenio</th><th class="text-right">Externo</th><th class="text-right">Total</th></tr></thead>'
            + '<tbody>' + filas + '</tbody>'
            + '<tfoot><tr><th colspan="5">TOTALES</th>'
            + '<th class="text-right">$' + totalConv.toFixed(2) + '</th>'
            + '<th class="text-right">$' + totalExt.toFixed(2) + '</th>'
            + '<th class="text-right">$' + totalGen.toFixed(2) + '</th></tr></tfoot>'
            + '</table>'
            + '<div class="hv-footer">Generado el ' + new Date().toLocaleString('es-EC') + '</div>';

        var estilos = ''
            + 'body{font-family:Arial,Helvetica,sans-serif;font-size:13px;padding:30px;color:#2c2c2c;}'
            + '.hv-header{border-bottom:3px solid #6d1b3a;padding-bottom:10px;margin-bottom:14px;}'
            + '.hv-header-titulo{font-size:22px;font-weight:700;color:#6d1b3a;}'
            + '.hv-header-sub{font-size:12px;letter-spacing:1px;color:#8a8a8a;text-transform:uppercase;}'
            + '.hv-meta{display:flex;gap:24px;background:#f7ecf0;border:1px solid #e6cfd8;border-radius:6px;'
            + 'padding:8px 14px;margin-bottom:16px;font-size:12.5px;color:#4a1226;}'
            + 'table.hv-table{width:100%;border-collapse:collapse;font-size:12.5px;}'
            + '.hv-table thead th{background:#6d1b3a;color:#fff;padding:8px 10px;text-align:left;font-weight:600;'
            + 'border:1px solid #6d1b3a;}'
            + '.hv-table tbody td{padding:7px 10px;border:1px solid #e6d3da;vertical-align:top;}'
            + '.hv-table tbody tr:nth-child(even){background:#faf3f6;}'
            + '.hv-table tbody tr:hover{background:#f2dbe4;}'
            + '.hv-table tbody small{color:#8a8a8a;}'
            + '.hv-table tfoot th{background:#4a1226;color:#fff;padding:9px 10px;border:1px solid #4a1226;font-size:13px;}'
            + '.hv-table .text-right{text-align:right;}'
            + '.hv-footer{margin-top:16px;font-size:11px;color:#aaa;text-align:right;}'
            + '@media print{.no-print{display:none} @page{margin:15mm;size:A4;}}';

        var ventana = window.open('', '_blank', 'width=900,height=800');
        ventana.document.write('<html><head><title>Historial de Ventas - SGC ARGOS</title>');
        ventana.document.write('<style>' + estilos + '</style>');
        ventana.document.write('</head><body>');
        ventana.document.write('<div class="no-print" style="text-align:right;margin-bottom:15px;">');
        ventana.document.write('<button onclick="window.print();" style="background:#6d1b3a;color:#fff;border:none;padding:8px 20px;border-radius:4px;font-size:14px;cursor:pointer;">');
        ventana.document.write('&#128196; Guardar / Imprimir PDF</button></div>');
        ventana.document.write(contenido);
        ventana.document.write('</body></html>');
        ventana.document.close();
    });

    function htmlEsc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

});
</script>
<script src="assets/vendor/exceljs/exceljs.min.js"></script>
<script src="js/export_theme.js"></script>
