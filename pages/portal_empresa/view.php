<?php if (!isset($_SESSION['id_user']) || $_SESSION['permisos_acceso'] !== 'empresa_cliente') {
    echo "<meta http-equiv='refresh' content='0; url=index.php'>"; exit;
} ?>
<div class="content">
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">Portal Empresa</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=portal_empresa"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active">Autoservicio</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <section class="page-content container-fluid">

        <!-- TARJETAS RESUMEN -->
        <div class="row" id="row_resumen">
            <div class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body py-3">
                        <p class="text-muted mb-1"><small>Empleados Activos</small></p>
                        <h3 class="mb-0 text-primary" id="res_activos">—</h3>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body py-3">
                        <p class="text-muted mb-1"><small>Cupo Total Asignado</small></p>
                        <h3 class="mb-0 text-info" id="res_asignado">—</h3>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body py-3">
                        <p class="text-muted mb-1"><small>Total Consumido</small></p>
                        <h3 class="mb-0 text-danger" id="res_consumido">—</h3>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body py-3">
                        <p class="text-muted mb-1"><small>Cupo Disponible</small></p>
                        <h3 class="mb-0 text-success" id="res_disponible">—</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <ul class="nav nav-tabs mt-2" id="tabsPortal" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-nomina" data-toggle="tab" href="#pane-nomina" role="tab">
                    <i class="icon dripicons-user-group"></i> Nómina
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-historial" data-toggle="tab" href="#pane-historial" role="tab">
                    <i class="icon dripicons-clock"></i> Historial de Consumos
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ===== TAB NÓMINA ===== -->
            <div class="tab-pane fade show active" id="pane-nomina" role="tabpanel">
                <div class="card border-top-0 rounded-0">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <input type="text" id="buscar_nomina" class="form-control" placeholder="Buscar por nombre o cédula...">
                                    <div class="input-group-append">
                                        <button class="btn btn-secondary" id="btn_buscar_nomina">
                                            <i class="icon dripicons-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7 text-right">
                                <button class="btn btn-info mr-2" id="btn_nuevo_empleado" style="color:#fff;">
                                    <i class="icon dripicons-plus"></i> Nuevo Empleado
                                </button>
                                <button class="btn btn-success" id="btn_exportar_nomina">
                                    <i class="icon dripicons-download"></i> Exportar Excel
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="table_nomina">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Cédula</th>
                                        <th>Nombre</th>
                                        <th>N° Tarjeta</th>
                                        <th>Correo</th>
                                        <th>Estado</th>
                                        <th class="text-right">Cupo Asignado</th>
                                        <th class="text-right">Consumido</th>
                                        <th class="text-right">Disponible</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_nomina">
                                    <tr><td colspan="8" class="text-center py-4"><i class="icon dripicons-loading icon-spin"></i> Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== TAB HISTORIAL ===== -->
            <div class="tab-pane fade" id="pane-historial" role="tabpanel">
                <div class="card border-top-0 rounded-0">
                    <div class="card-body">
                        <!-- Filtros -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="mb-1"><small class="text-muted">Desde</small></label>
                                <input type="date" id="hist_desde" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="mb-1"><small class="text-muted">Hasta</small></label>
                                <input type="date" id="hist_hasta" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="mb-1"><small class="text-muted">Empleado</small></label>
                                <select id="hist_per_id" class="form-control form-control-sm">
                                    <option value="">Todos los empleados</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-secondary btn-sm btn-block" id="btn_filtrar_hist">
                                    <i class="icon dripicons-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col text-right">
                                <button class="btn btn-success btn-sm" id="btn_exportar_historial">
                                    <i class="icon dripicons-download"></i> Exportar Excel
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="table_historial">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Empleado</th>
                                        <th>Cédula</th>
                                        <th>Local</th>
                                        <th>Descripción</th>
                                        <th class="text-right">Convenio</th>
                                        <th class="text-right">Externo</th>
                                        <th class="text-right">Total</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_historial">
                                    <tr><td colspan="10" class="text-center text-muted py-4">Aplique filtros para ver resultados</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-right text-muted mt-2"><small>Total consumido: <strong id="total_historial">$0.00</strong></small></p>
                    </div>
                </div>
            </div>

        </div><!-- /tab-content -->
    </section>
</div>

<!-- Modal Nuevo Empleado -->
<div class="modal fade" id="modal_nuevo_emp" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-plus"></i> Nuevo Empleado</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_nuevo_emp" class="mb-2" style="display:none;"></div>
                <div class="form-group">
                    <label>Nombres completos <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="new_per_nombre" placeholder="Ej: Juan Pérez Torres" maxlength="200">
                </div>
                <div class="form-group">
                    <label>Cédula <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="new_per_documento" placeholder="Ej: 0912345678" maxlength="20">
                </div>
                <div class="form-group">
                    <label>Correo electrónico</label>
                    <input type="email" class="form-control" id="new_per_correo" placeholder="correo@empresa.com" maxlength="150">
                </div>
                <div class="form-group">
                    <label>Cupo asignado ($) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                        <input type="number" class="form-control" id="new_per_cupo" min="0.01" step="0.01" placeholder="0.00">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btn_guardar_emp" style="color:#fff;">
                    <i class="icon dripicons-checkmark"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle Empleado -->
<div class="modal fade" id="modal_empleado" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-user"></i> <span id="modal_emp_nombre"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <p class="mb-1 text-muted"><small>Cédula</small></p>
                        <strong id="modal_emp_cedula"></strong>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted"><small>N° Tarjeta</small></p>
                        <code id="modal_emp_tarjeta" style="font-size:0.95rem; letter-spacing:1px;"></code>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1 text-muted"><small>Correo</small></p>
                        <strong id="modal_emp_correo"></strong>
                    </div>
                    <div class="col-md-2">
                        <p class="mb-1 text-muted"><small>Estado</small></p>
                        <strong id="modal_emp_estado"></strong>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 text-center">
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <small class="text-muted">Cupo Asignado</small>
                                <h5 class="mb-0 text-info" id="modal_emp_asignado"></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <small class="text-muted">Consumido</small>
                                <h5 class="mb-0 text-danger" id="modal_emp_consumido"></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <small class="text-muted">Disponible</small>
                                <h5 class="mb-0 text-success" id="modal_emp_disponible"></h5>
                            </div>
                        </div>
                    </div>
                </div>
                <h6 class="border-bottom pb-1">Últimos consumos</h6>
                <table class="table table-sm">
                    <thead class="thead-light">
                        <tr><th>Fecha</th><th>Descripción</th><th class="text-right">Total</th></tr>
                    </thead>
                    <tbody id="modal_emp_consumos"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/vendor/sheetjs/xlsx.full.min.js"></script>
<script>
var AJAX_URL = 'ajax/portal_empresa/portal_empresa.php';
var nominaData = [];

// ---- Resumen ----
function cargarResumen() {
    $.getJSON(AJAX_URL + '?action=resumen', function(r) {
        if (!r.success) return;
        var d = r.data;
        $('#res_activos').text(d.activos || 0);
        $('#res_asignado').text('$' + parseFloat(d.total_asignado || 0).toFixed(2));
        $('#res_consumido').text('$' + parseFloat(d.total_consumido || 0).toFixed(2));
        $('#res_disponible').text('$' + parseFloat(d.total_disponible || 0).toFixed(2));
    });
}

// ---- Nómina ----
function cargarNomina(buscar) {
    var url = AJAX_URL + '?action=nomina';
    if (buscar) url += '&buscar=' + encodeURIComponent(buscar);
    $('#tbody_nomina').html('<tr><td colspan="9" class="text-center py-3"><i class="icon dripicons-loading icon-spin"></i> Cargando...</td></tr>');
    $.getJSON(url, function(r) {
        if (!r.success) { $('#tbody_nomina').html('<tr><td colspan="9" class="text-center text-danger">' + r.mensaje + '</td></tr>'); return; }
        nominaData = r.data;
        var html = '';
        if (!r.data.length) {
            html = '<tr><td colspan="9" class="text-center text-muted py-3">Sin resultados</td></tr>';
        } else {
            r.data.forEach(function(e) {
                var badgeColor = e.per_estado === 'activo' ? 'success' : 'secondary';
                html += '<tr>'
                    + '<td>' + (e.per_documento || '—') + '</td>'
                    + '<td>' + e.per_nombre + '</td>'
                    + '<td><code>' + (e.per_numero_tarjeta ? maskTarjeta(e.per_numero_tarjeta) : '—') + '</code></td>'
                    + '<td>' + (e.per_correo || '—') + '</td>'
                    + '<td><span class="badge badge-' + badgeColor + '">' + e.per_estado + '</span></td>'
                    + '<td class="text-right">$' + parseFloat(e.per_cupo_asignado || 0).toFixed(2) + '</td>'
                    + '<td class="text-right text-danger">$' + parseFloat(e.consumido || 0).toFixed(2) + '</td>'
                    + '<td class="text-right text-success">$' + parseFloat(e.per_cupo_disponible || 0).toFixed(2) + '</td>'
                    + '<td class="text-center"><button class="btn btn-sm btn-outline-primary" onclick="verEmpleado(' + e.per_id + ')">'
                    + '<i class="icon dripicons-user"></i> Ver</button></td>'
                    + '</tr>';
            });
        }
        $('#tbody_nomina').html(html);

        // Poblar select historial
        var opts = '<option value="">Todos los empleados</option>';
        r.data.forEach(function(e) { opts += '<option value="' + e.per_id + '">' + e.per_nombre + '</option>'; });
        $('#hist_per_id').html(opts);
    });
}

// ---- Detalle empleado ----
function verEmpleado(per_id) {
    $.getJSON(AJAX_URL + '?action=detalle_empleado&per_id=' + per_id, function(r) {
        if (!r.success) { alert(r.mensaje); return; }
        var d = r.data;
        var consumido = parseFloat(d.per_cupo_asignado || 0) - parseFloat(d.per_cupo_disponible || 0);
        $('#modal_emp_nombre').text(d.per_nombre);
        $('#modal_emp_cedula').text(d.per_documento || '—');
        $('#modal_emp_tarjeta').text(d.per_numero_tarjeta ? maskTarjeta(d.per_numero_tarjeta) : '—');
        $('#modal_emp_correo').text(d.per_correo || '—');
        $('#modal_emp_estado').text(d.per_estado);
        $('#modal_emp_asignado').text('$' + parseFloat(d.per_cupo_asignado || 0).toFixed(2));
        $('#modal_emp_consumido').text('$' + consumido.toFixed(2));
        $('#modal_emp_disponible').text('$' + parseFloat(d.per_cupo_disponible || 0).toFixed(2));

        var rows = '';
        if (r.consumos.length) {
            r.consumos.forEach(function(c) {
                rows += '<tr><td>' + c.con_fecha + '</td><td>' + (c.con_descripcion || '—') + '</td>'
                      + '<td class="text-right">$' + parseFloat(c.con_valor_total).toFixed(2) + '</td></tr>';
            });
        } else {
            rows = '<tr><td colspan="3" class="text-center text-muted">Sin consumos registrados</td></tr>';
        }
        $('#modal_emp_consumos').html(rows);
        $('#modal_empleado').modal('show');
    });
}

// ---- Historial ----
function cargarHistorial() {
    var desde  = $('#hist_desde').val();
    var hasta  = $('#hist_hasta').val();
    var per_id = $('#hist_per_id').val();
    var url    = AJAX_URL + '?action=historial&desde=' + desde + '&hasta=' + hasta;
    if (per_id) url += '&per_id=' + per_id;

    $('#tbody_historial').html('<tr><td colspan="10" class="text-center py-3"><i class="icon dripicons-loading icon-spin"></i> Cargando...</td></tr>');
    $.getJSON(url, function(r) {
        if (!r.success) { $('#tbody_historial').html('<tr><td colspan="10" class="text-center text-danger">' + r.mensaje + '</td></tr>'); return; }
        var html = '';
        var total = 0;
        if (!r.data.length) {
            html = '<tr><td colspan="10" class="text-center text-muted py-3">Sin resultados en el período</td></tr>';
        } else {
            r.data.forEach(function(c) {
                total += parseFloat(c.con_valor_total || 0);
                html += '<tr>'
                    + '<td>' + c.con_fecha + '</td>'
                    + '<td>' + (c.con_hora ? c.con_hora.substring(0,5) : '—') + '</td>'
                    + '<td>' + c.per_nombre + '</td>'
                    + '<td>' + (c.per_documento || '—') + '</td>'
                    + '<td>' + (c.loc_nombre || '—') + '</td>'
                    + '<td>' + (c.con_descripcion || '—') + '</td>'
                    + '<td class="text-right">$' + parseFloat(c.con_monto_convenio || 0).toFixed(2) + '</td>'
                    + '<td class="text-right">$' + parseFloat(c.con_monto_externo || 0).toFixed(2) + '</td>'
                    + '<td class="text-right"><strong>$' + parseFloat(c.con_valor_total || 0).toFixed(2) + '</strong></td>'
                    + '<td><span class="badge badge-secondary">' + (c.con_estado || '') + '</span></td>'
                    + '</tr>';
            });
        }
        $('#tbody_historial').html(html);
        $('#total_historial').text('$' + total.toFixed(2));
    });
}

// ---- Exportar Excel nómina ----
$('#btn_exportar_nomina').on('click', function() {
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.table_to_sheet(document.getElementById('table_nomina'));
    XLSX.utils.book_append_sheet(wb, ws, 'Nomina');
    XLSX.writeFile(wb, 'nomina_' + new Date().toISOString().slice(0,10) + '.xlsx');
});

// ---- Exportar Excel historial ----
$('#btn_exportar_historial').on('click', function() {
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.table_to_sheet(document.getElementById('table_historial'));
    XLSX.utils.book_append_sheet(wb, ws, 'Historial');
    XLSX.writeFile(wb, 'historial_consumos_' + new Date().toISOString().slice(0,10) + '.xlsx');
});

// ---- Nuevo empleado ----
$('#btn_nuevo_empleado').on('click', function() {
    $('#new_per_nombre, #new_per_documento, #new_per_correo').val('');
    $('#new_per_cupo').val('');
    $('#alerta_nuevo_emp').hide().html('');
    // Pre-cargar cupo del convenio
    $.getJSON(AJAX_URL + '?action=cupo_convenio', function(r) {
        if (r.success && r.cupo > 0) $('#new_per_cupo').val(r.cupo);
    });
    $('#modal_nuevo_emp').modal('show');
});

$('#btn_guardar_emp').on('click', function() {
    var nombre   = $('#new_per_nombre').val().trim();
    var cedula   = $('#new_per_documento').val().trim();
    var correo   = $('#new_per_correo').val().trim();
    var cupo     = parseFloat($('#new_per_cupo').val()) || 0;

    if (!nombre)      { alertaEmp('warning', 'Ingrese el nombre del empleado'); return; }
    if (!cedula)      { alertaEmp('warning', 'Ingrese la cédula'); return; }
    if (cupo <= 0)    { alertaEmp('warning', 'Ingrese un cupo válido'); return; }

    var btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

    $.ajax({
        url: AJAX_URL,
        type: 'POST',
        data: { action: 'crear_empleado', per_nombre: nombre, per_documento: cedula, per_correo: correo, per_cupo: cupo },
        dataType: 'json',
        success: function(r) {
            if (r.success) {
                $('#modal_nuevo_emp').modal('hide');
                cargarResumen();
                cargarNomina();
            } else {
                alertaEmp('danger', r.mensaje);
            }
        },
        error: function() { alertaEmp('danger', 'Error de conexión'); },
        complete: function() {
            btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar');
        }
    });
});

function alertaEmp(tipo, msg) {
    $('#alerta_nuevo_emp').html('<div class="alert alert-' + tipo + ' mb-0">' + msg + '</div>').show();
}

// ---- Eventos ----
$('#btn_buscar_nomina').on('click', function() { cargarNomina($('#buscar_nomina').val()); });
$('#buscar_nomina').on('keypress', function(e) { if (e.which === 13) cargarNomina($(this).val()); });
$('#btn_filtrar_hist').on('click', cargarHistorial);
$('#tab-historial').on('shown.bs.tab', function() { if ($('#tbody_historial tr td[colspan]').length) cargarHistorial(); });

// ---- Helpers ----
function maskTarjeta(num) {
    if (!num || num.length < 8) return num;
    return num.substring(0, 4) + '****' + num.substring(num.length - 4);
}

// ---- Init ----
cargarResumen();
cargarNomina();
</script>
