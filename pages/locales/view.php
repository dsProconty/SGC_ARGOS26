<?php
if (!isset($_SESSION['id_user']) || $_SESSION['permisos_acceso'] !== 'Super Admin') {
    echo "<meta http-equiv='refresh' content='0; url=index.php'>"; exit;
}
?>
<div class="content">
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">Locales Comerciales</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active">Locales</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <section class="page-content container-fluid">

        <!-- TABS -->
        <ul class="nav nav-tabs" id="tabsLocales" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-marcas" data-toggle="tab" href="#pane-marcas" role="tab">
                    <i class="icon dripicons-store"></i> Marcas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-sucursales" data-toggle="tab" href="#pane-sucursales" role="tab">
                    <i class="icon dripicons-location"></i> Sucursales
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ===== TAB MARCAS ===== -->
            <div class="tab-pane fade show active" id="pane-marcas" role="tabpanel">
                <div class="card border-top-0 rounded-0">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col text-right">
                                <button class="btn btn-info" id="btn_nueva_marca" style="color:#fff;">
                                    <i class="icon dripicons-plus"></i> Nueva Marca
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm" id="table_marcas">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nombre de la Marca</th>
                                        <th class="text-center">Sucursales</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_marcas">
                                    <tr><td colspan="4" class="text-center py-4"><i class="icon dripicons-loading icon-spin"></i> Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== TAB SUCURSALES ===== -->
            <div class="tab-pane fade" id="pane-sucursales" role="tabpanel">
                <div class="card border-top-0 rounded-0">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <select class="form-control" id="filtro_marca">
                                    <option value="">Todas las marcas</option>
                                </select>
                            </div>
                            <div class="col text-right">
                                <button class="btn btn-info" id="btn_nueva_sucursal" style="color:#fff;">
                                    <i class="icon dripicons-plus"></i> Nueva Sucursal
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm" id="table_sucursales">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Marca</th>
                                        <th>Nombre / Descripción</th>
                                        <th>Dirección</th>
                                        <th>Provincia</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_sucursales">
                                    <tr><td colspan="7" class="text-center py-4"><i class="icon dripicons-loading icon-spin"></i> Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /tab-content -->
    </section>
</div>

<!-- ===== MODAL MARCA ===== -->
<div class="modal fade" id="modal_marca" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_marca_titulo"><i class="icon dripicons-store"></i> Nueva Marca</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="marca_id">
                <div id="alerta_marca" class="mb-2" style="display:none;"></div>
                <div class="form-group">
                    <label>Nombre de la marca <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="marca_nombre" maxlength="200" placeholder="Ej: Pizza Hut">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btn_guardar_marca" style="color:#fff;">
                    <i class="icon dripicons-checkmark"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL SUCURSAL ===== -->
<div class="modal fade" id="modal_sucursal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_suc_titulo"><i class="icon dripicons-location"></i> Nueva Sucursal</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="suc_id">
                <div id="alerta_suc" class="mb-2" style="display:none;"></div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Marca <span class="text-danger">*</span></label>
                            <select class="form-control" id="suc_mar_id">
                                <option value="">— Seleccione marca —</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nombre / Descripción <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="suc_nombre" maxlength="255" placeholder="Ej: Pizza Hut Quicentro Sur">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Dirección <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="suc_direccion" maxlength="255" placeholder="Ej: Av. Morán Valverde y Teniente Hugo Ortiz">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Provincia <span class="text-danger">*</span></label>
                            <select class="form-control" id="suc_provincia">
                                <option value="">— Seleccione —</option>
                                <option value="sierra">Sierra</option>
                                <option value="costa">Costa</option>
                                <option value="oriente">Oriente</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Estado</label>
                            <select class="form-control" id="suc_activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btn_guardar_suc" style="color:#fff;">
                    <i class="icon dripicons-checkmark"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var AJAX_URL = 'ajax/locales/locales.php';

// ============================================================
// MARCAS
// ============================================================
function cargarMarcas() {
    $.getJSON(AJAX_URL + '?action=list_marcas', function(r) {
        if (!r.success) return;
        var html = '';
        var opts = '<option value="">Todas las marcas</option>';
        var optsModal = '<option value="">— Seleccione marca —</option>';
        if (!r.data.length) {
            html = '<tr><td colspan="4" class="text-center text-muted py-3">Sin marcas registradas</td></tr>';
        } else {
            r.data.forEach(function(m) {
                html += '<tr>'
                    + '<td>' + m.mar_id + '</td>'
                    + '<td><strong>' + m.mar_descripcion + '</strong></td>'
                    + '<td class="text-center"><span class="badge badge-info">' + m.total_sucursales + '</span></td>'
                    + '<td class="text-center">'
                    + '<a onclick="editarMarca(' + m.mar_id + ',\'' + m.mar_descripcion.replace(/'/g,"\\\'") + '\')" style="color:#f0ad4e; font-size:17px; cursor:pointer; margin-right:6px;" title="Editar"><i class="icon dripicons-pencil"></i></a>'
                    + '</td>'
                    + '</tr>';
                opts += '<option value="' + m.mar_id + '">' + m.mar_descripcion + '</option>';
                optsModal += '<option value="' + m.mar_id + '">' + m.mar_descripcion + '</option>';
            });
        }
        $('#tbody_marcas').html(html);
        $('#filtro_marca').html(opts);
        $('#suc_mar_id').html(optsModal);
    });
}

$('#btn_nueva_marca').on('click', function() {
    $('#marca_id').val('');
    $('#marca_nombre').val('');
    $('#alerta_marca').hide().html('');
    $('#modal_marca_titulo').html('<i class="icon dripicons-store"></i> Nueva Marca');
    $('#modal_marca').modal('show');
});

function editarMarca(id, nombre) {
    $('#marca_id').val(id);
    $('#marca_nombre').val(nombre);
    $('#alerta_marca').hide().html('');
    $('#modal_marca_titulo').html('<i class="icon dripicons-pencil"></i> Editar Marca');
    $('#modal_marca').modal('show');
}

$('#btn_guardar_marca').on('click', function() {
    var id     = $('#marca_id').val();
    var nombre = $('#marca_nombre').val().trim();
    if (!nombre) { alertaMarca('warning', 'Ingrese el nombre de la marca'); return; }

    var btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');
    $.ajax({
        url: AJAX_URL, type: 'POST',
        data: { action: id ? 'editar_marca' : 'crear_marca', mar_id: id, mar_descripcion: nombre },
        dataType: 'json',
        success: function(r) {
            if (r.success) { $('#modal_marca').modal('hide'); cargarMarcas(); cargarSucursales(); }
            else alertaMarca('danger', r.mensaje);
        },
        error: function() { alertaMarca('danger', 'Error de conexión'); },
        complete: function() { btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar'); }
    });
});

function alertaMarca(tipo, msg) {
    $('#alerta_marca').html('<div class="alert alert-' + tipo + ' mb-0">' + msg + '</div>').show();
}

// ============================================================
// SUCURSALES
// ============================================================
function cargarSucursales(mar_id) {
    var url = AJAX_URL + '?action=list_sucursales';
    if (mar_id) url += '&mar_id=' + mar_id;
    $('#tbody_sucursales').html('<tr><td colspan="7" class="text-center py-3"><i class="icon dripicons-loading icon-spin"></i> Cargando...</td></tr>');
    $.getJSON(url, function(r) {
        if (!r.success) return;
        var html = '';
        if (!r.data.length) {
            html = '<tr><td colspan="7" class="text-center text-muted py-3">Sin sucursales registradas</td></tr>';
        } else {
            r.data.forEach(function(s) {
                var badge = s.loc_activo == 1 ? 'badge-success' : 'badge-secondary';
                var estado = s.loc_activo == 1 ? 'Activo' : 'Inactivo';
                var provincia = s.loc_provincia ? s.loc_provincia.charAt(0).toUpperCase() + s.loc_provincia.slice(1) : '—';
                html += '<tr>'
                    + '<td>' + s.loc_id + '</td>'
                    + '<td><span class="badge badge-dark">' + s.mar_descripcion + '</span></td>'
                    + '<td><strong>' + s.loc_nombre + '</strong></td>'
                    + '<td>' + (s.loc_direccion || '—') + '</td>'
                    + '<td>' + provincia + '</td>'
                    + '<td class="text-center"><span class="badge ' + badge + '">' + estado + '</span></td>'
                    + '<td class="text-center">'
                    + '<a onclick="editarSucursal(' + s.loc_id + ')" style="color:#f0ad4e; font-size:17px; cursor:pointer;" title="Editar"><i class="icon dripicons-pencil"></i></a>'
                    + '</td>'
                    + '</tr>';
            });
        }
        $('#tbody_sucursales').html(html);
    });
}

$('#filtro_marca').on('change', function() {
    cargarSucursales($(this).val());
});

$('#btn_nueva_sucursal').on('click', function() {
    $('#suc_id').val('');
    $('#suc_mar_id, #suc_nombre, #suc_direccion, #suc_provincia').val('');
    $('#suc_activo').val('1');
    $('#alerta_suc').hide().html('');
    $('#modal_suc_titulo').html('<i class="icon dripicons-location"></i> Nueva Sucursal');
    // Pre-select filtro marca if active
    var marFiltro = $('#filtro_marca').val();
    if (marFiltro) $('#suc_mar_id').val(marFiltro);
    $('#modal_sucursal').modal('show');
});

function editarSucursal(loc_id) {
    $.getJSON(AJAX_URL + '?action=get_sucursal&loc_id=' + loc_id, function(r) {
        if (!r.success) { alert(r.mensaje); return; }
        var s = r.data;
        $('#suc_id').val(s.loc_id);
        $('#suc_mar_id').val(s.mar_id);
        $('#suc_nombre').val(s.loc_nombre);
        $('#suc_direccion').val(s.loc_direccion);
        $('#suc_provincia').val(s.loc_provincia);
        $('#suc_activo').val(s.loc_activo);
        $('#alerta_suc').hide().html('');
        $('#modal_suc_titulo').html('<i class="icon dripicons-pencil"></i> Editar Sucursal');
        $('#modal_sucursal').modal('show');
    });
}

$('#btn_guardar_suc').on('click', function() {
    var id        = $('#suc_id').val();
    var mar_id    = $('#suc_mar_id').val();
    var nombre    = $('#suc_nombre').val().trim();
    var direccion = $('#suc_direccion').val().trim();
    var provincia = $('#suc_provincia').val();
    var activo    = $('#suc_activo').val();

    if (!mar_id)    { alertaSuc('warning', 'Seleccione una marca'); return; }
    if (!nombre)    { alertaSuc('warning', 'Ingrese el nombre de la sucursal'); return; }
    if (!direccion) { alertaSuc('warning', 'Ingrese la dirección'); return; }
    if (!provincia) { alertaSuc('warning', 'Seleccione la provincia'); return; }

    var btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');
    $.ajax({
        url: AJAX_URL, type: 'POST',
        data: { action: id ? 'editar_sucursal' : 'crear_sucursal', loc_id: id, mar_id: mar_id, loc_nombre: nombre, loc_direccion: direccion, loc_provincia: provincia, loc_activo: activo },
        dataType: 'json',
        success: function(r) {
            if (r.success) { $('#modal_sucursal').modal('hide'); cargarSucursales($('#filtro_marca').val()); }
            else alertaSuc('danger', r.mensaje);
        },
        error: function() { alertaSuc('danger', 'Error de conexión'); },
        complete: function() { btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar'); }
    });
});

function alertaSuc(tipo, msg) {
    $('#alerta_suc').html('<div class="alert alert-' + tipo + ' mb-0">' + msg + '</div>').show();
}

// ---- Tab switch carga sucursales ----
$('#tab-sucursales').on('shown.bs.tab', function() { cargarSucursales(); });

// ---- Init ----
cargarMarcas();
</script>
