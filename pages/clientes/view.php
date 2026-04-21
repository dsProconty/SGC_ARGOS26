<div class="content">
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator" id="page_title">CLIENTES</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" id="breadcrumb_current">Clientes</li>
                        </ol>
                    </nav>
                </div>
                <div id="header_actions">
                    <button class="btn btn-primary" onclick="abrirModalNuevo()">
                        <i class="icon dripicons-plus"></i> Nuevo Cliente
                    </button>
                </div>
            </div>
        </div>
    </header>

    <section class="page-content container-fluid">

        <!-- ══════════════════════════════════════════════
             VISTA: LISTA DE CLIENTES
        ══════════════════════════════════════════════ -->
        <div id="vista_lista">
            <!-- Filtros -->
            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="form-inline">
                        <label class="mr-2 text-muted">Filtrar:</label>
                        <select class="form-control form-control-sm mr-2" id="filtro_beneficio" onchange="cargarClientes()">
                            <option value="">Todos los beneficios</option>
                            <option value="Cupo">Cupo</option>
                            <option value="Porcentaje">Porcentaje</option>
                        </select>
                        <select class="form-control form-control-sm mr-2" id="filtro_cartera" onchange="cargarClientes()">
                            <option value="">Toda la cartera</option>
                            <option value="30">30 días</option>
                            <option value="60">60 días</option>
                            <option value="90">90 días</option>
                            <option value="90+">90+ días</option>
                        </select>
                        <button class="btn btn-sm btn-outline-secondary" onclick="limpiarFiltros()">
                            <i class="icon dripicons-cross"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Listado de Clientes / Empresas</h5></div>
                <div class="card-body">
                    <div id="loader_lista" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div id="tabla_wrapper" style="display:none;">
                        <table id="table_clientes" class="table table-striped table-bordered table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Empresa / Cliente</th>
                                    <th>Ciudad</th>
                                    <th>Contacto</th>
                                    <th>Email</th>
                                    <th>Beneficio</th>
                                    <th>Cartera</th>
                                    <th>Personal</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_clientes"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div><!-- /vista_lista -->

        <!-- ══════════════════════════════════════════════
             VISTA: PERFIL 360° DEL CLIENTE
        ══════════════════════════════════════════════ -->
        <div id="vista_detalle" style="display:none;">
            <!-- Header del cliente -->
            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-secondary mr-3" onclick="volverLista()">
                            <i class="icon dripicons-arrow-thin-left"></i> Volver
                        </button>
                        <div>
                            <h5 class="mb-0" id="detalle_nombre"></h5>
                            <small class="text-muted" id="detalle_subtitulo"></small>
                        </div>
                        <div class="ml-auto">
                            <button class="btn btn-sm btn-primary" onclick="editarClienteActual()">
                                <i class="icon dripicons-document-edit"></i> Editar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-0" id="tabs_detalle">
                <li class="nav-item">
                    <a class="nav-link active" href="#" data-tab="info" onclick="cambiarTab(this,'info'); return false;">
                        <i class="icon dripicons-briefcase"></i> Información
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-tab="personal" onclick="cambiarTab(this,'personal'); return false;">
                        <i class="icon dripicons-user-group"></i> Personal <span class="badge badge-secondary ml-1" id="badge_personal">...</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-tab="consumos" onclick="cambiarTab(this,'consumos'); return false;">
                        <i class="icon dripicons-shopping-bag"></i> Consumos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-tab="estado_cuenta" onclick="cambiarTab(this,'estado_cuenta'); return false;">
                        <i class="icon dripicons-graph-bar"></i> Estados de Cuenta
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-tab="giftcards" onclick="cambiarTab(this,'giftcards'); return false;">
                        <i class="icon dripicons-card"></i> Gift Cards
                    </a>
                </li>
            </ul>

            <!-- TAB: INFORMACIÓN -->
            <div class="card tab-panel" id="tab_info">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Datos de contacto</h6>
                            <table class="table table-sm table-borderless">
                                <tr><th class="text-muted font-weight-normal" style="width:40%">N° Convenio</th><td id="inf_convenio"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Ciudad</th><td id="inf_ciudad"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Contacto</th><td id="inf_contacto"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Email principal</th><td id="inf_email"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Email secundario</th><td id="inf_email2"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Teléfono</th><td id="inf_telefono"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Teléfono alt.</th><td id="inf_telefono2"></td></tr>
                                <tr><th class="text-muted font-weight-normal">Día de corte</th><td id="inf_dia_corte"></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Configuración comercial</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 border-right">
                                            <div class="text-muted small">Tipo de beneficio</div>
                                            <div id="inf_tipo_beneficio" class="mt-1"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Valor</div>
                                            <div id="inf_valor_beneficio" class="mt-1 font-weight-bold"></div>
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="row text-center">
                                        <div class="col-6 border-right">
                                            <div class="text-muted small">Tipo de cartera</div>
                                            <div id="inf_tipo_cartera" class="mt-1"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Comisión</div>
                                            <div id="inf_comision" class="mt-1 font-weight-bold"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: PERSONAL -->
            <div class="card tab-panel" id="tab_personal" style="display:none;">
                <div class="card-body">
                    <div id="loader_personal" class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>
                    <div id="tabla_personal_wrapper" style="display:none;">
                        <table id="table_personal" class="table table-striped table-bordered" style="width:100%">
                            <thead><tr>
                                <th>#</th><th>Nombre</th><th>Documento</th>
                                <th>N° Tarjeta</th><th>Email</th><th>Estado</th>
                                <th>Cupo Asignado</th><th>Cupo Disponible</th>
                            </tr></thead>
                            <tbody id="tbody_personal"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: CONSUMOS -->
            <div class="card tab-panel" id="tab_consumos" style="display:none;">
                <div class="card-body">
                    <div id="loader_consumos" class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>
                    <div id="tabla_consumos_wrapper" style="display:none;">
                        <table id="table_consumos" class="table table-striped table-bordered" style="width:100%">
                            <thead><tr>
                                <th>Fecha</th><th>Empleado</th><th>Tarjeta</th>
                                <th>Local</th><th>Total</th><th>Convenio</th>
                                <th>Externo</th><th>Estado</th>
                            </tr></thead>
                            <tbody id="tbody_consumos"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: ESTADOS DE CUENTA -->
            <div class="card tab-panel" id="tab_estado_cuenta" style="display:none;">
                <div class="card-body">
                    <div id="loader_ec" class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>
                    <div id="tabla_ec_wrapper" style="display:none;">
                        <table id="table_ec" class="table table-striped table-bordered" style="width:100%">
                            <thead><tr>
                                <th>#</th><th>Período</th><th>Monto Total</th>
                                <th>Generado</th><th>Estado Envío</th><th>PDF</th>
                            </tr></thead>
                            <tbody id="tbody_ec"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: GIFT CARDS -->
            <div class="card tab-panel" id="tab_giftcards" style="display:none;">
                <div class="card-body">
                    <div id="loader_gc" class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>
                    <div id="tabla_gc_wrapper" style="display:none;">
                        <table id="table_gc" class="table table-striped table-bordered" style="width:100%">
                            <thead><tr>
                                <th>#</th><th>Fecha</th><th>Solicitante</th>
                                <th>Cantidad</th><th>Cupo c/código</th>
                                <th>Activos</th><th>Consumidos</th><th>Vencidos</th>
                            </tr></thead>
                            <tbody id="tbody_gc"></tbody>
                        </table>
                    </div>
                    <p id="gc_sin_datos" class="text-muted text-center py-3" style="display:none;">
                        Este cliente no tiene lotes de Gift Cards registrados.
                    </p>
                </div>
            </div>

        </div><!-- /vista_detalle -->

    </section>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL — CREAR / EDITAR CLIENTE
══════════════════════════════════════════════════ -->
<div class="modal fade" id="modalCliente" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalClienteLabel">Nuevo Cliente</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formCliente">
                <div class="modal-body">
                    <input type="hidden" id="cli_id" name="cli_id">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Empresa / Cliente <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cli_descripcion" name="cli_descripcion" required autocomplete="off" placeholder="Ej: EMPRESA XYZ S.A.">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>N° Convenio</label>
                                <input type="text" class="form-control" id="cli_numero_convenio" name="cli_numero_convenio" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ciudad</label>
                                <input type="text" class="form-control" id="cli_ciudad" name="cli_ciudad" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Persona de contacto</label>
                                <input type="text" class="form-control" id="cli_contacto" name="cli_contacto" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email principal</label>
                                <input type="email" class="form-control" id="cli_email" name="cli_email" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email secundario</label>
                                <input type="email" class="form-control" id="cli_email2" name="cli_email2" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" class="form-control" id="cli_telefono" name="cli_telefono" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Teléfono alternativo</label>
                                <input type="text" class="form-control" id="cli_telefono2" name="cli_telefono2" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Día de corte</label>
                                <select class="form-control" id="cli_dia_corte" name="cli_dia_corte">
                                    <option value="0">— Sin corte —</option>
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                        <option value="<?= $d ?>"><?= $d ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6 class="text-muted mb-3"><i class="icon dripicons-graph-bar"></i> Configuración Comercial</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo de beneficio</label>
                                <select class="form-control" id="cli_tipo_beneficio" name="cli_tipo_beneficio">
                                    <option value="">— Seleccionar —</option>
                                    <option value="Cupo">Cupo (monto fijo)</option>
                                    <option value="Porcentaje">Porcentaje (%)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label id="label_valor">Valor del beneficio</label>
                                <div class="input-group">
                                    <div class="input-group-prepend" id="prefix_ben"><span class="input-group-text">$</span></div>
                                    <input type="number" class="form-control" id="cli_valor_beneficio" name="cli_valor_beneficio" min="0" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo de cartera</label>
                                <select class="form-control" id="cli_tipo_cartera" name="cli_tipo_cartera">
                                    <option value="">— Seleccionar —</option>
                                    <option value="30">30 días</option>
                                    <option value="60">60 días</option>
                                    <option value="90">90 días</option>
                                    <option value="90+">90+ días</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Comisión (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cli_comision" name="cli_comision" min="0" max="100" step="0.01" placeholder="0.00" value="0">
                                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn_guardar">
                        <i class="icon dripicons-checkmark"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════ -->
<script>
var _cliId   = null;   // ID del cliente en vista detalle
var _cliData = null;   // Datos del cliente actual
var _tabsLoaded = {};  // tabs ya cargados para evitar re-fetch

var cartBadge = {'30':'success','60':'warning','90':'danger','90+':'dark'};

// ══════════════════════════════════════════════
// LISTA
// ══════════════════════════════════════════════
function cargarClientes() {
    var b = $('#filtro_beneficio').val();
    var c = $('#filtro_cartera').val();
    $('#loader_lista').show();
    $('#tabla_wrapper').hide();

    // Destruir DataTable si existe
    if ($.fn.DataTable.isDataTable('#table_clientes')) {
        $('#table_clientes').DataTable().destroy();
        $('#tbody_clientes').empty();
    }

    $.getJSON('ajax/clientes/clientes.php?action=list&beneficio='+encodeURIComponent(b)+'&cartera='+encodeURIComponent(c), function(res) {
        $('#loader_lista').hide();
        if (!res.success) { alert('Error al cargar clientes'); return; }

        var html = '';
        $.each(res.data, function(i, d) {
            var tipoBen = d.cli_tipo_beneficio
                ? '<span class="badge badge-' + (d.cli_tipo_beneficio==='Cupo'?'info':'primary') + '">'
                  + d.cli_tipo_beneficio
                  + (d.cli_valor_beneficio ? ' — ' + (d.cli_tipo_beneficio==='Cupo'?'$':'') + parseFloat(d.cli_valor_beneficio).toFixed(2) + (d.cli_tipo_beneficio==='Porcentaje'?'%':'') : '')
                  + '</span>'
                : '<span class="text-muted">—</span>';

            var tipoCart = d.cli_tipo_cartera
                ? '<span class="badge badge-' + (cartBadge[d.cli_tipo_cartera]||'secondary') + '">' + d.cli_tipo_cartera + ' días</span>'
                : '<span class="text-muted">—</span>';

            html += '<tr>'
                + '<td>' + (i+1) + '</td>'
                + '<td><strong>' + d.cli_descripcion + '</strong></td>'
                + '<td>' + (d.cli_ciudad||'—') + '</td>'
                + '<td>' + (d.cli_contacto||'—') + '</td>'
                + '<td>' + (d.cli_email ? '<a href="mailto:'+d.cli_email+'">'+d.cli_email+'</a>' : '—') + '</td>'
                + '<td>' + tipoBen + '</td>'
                + '<td>' + tipoCart + '</td>'
                + '<td class="text-center">'
                  + (d.total_personal > 0 ? '<span class="badge badge-secondary">'+d.total_personal+' emp.</span>' : '<span class="text-muted">0</span>')
                + '</td>'
                + '<td class="text-nowrap">'
                  + '<button class="btn btn-info btn-sm mr-1" onclick="verDetalle('+d.cli_id+')" title="Ver perfil">'
                  + '<i class="icon dripicons-user"></i></button>'
                  + '<button class="btn btn-primary btn-sm" onclick="editarCliente('+d.cli_id+')" title="Editar">'
                  + '<i class="icon dripicons-document-edit"></i></button>'
                + '</td>'
              + '</tr>';
        });

        $('#tbody_clientes').html(html);
        $('#tabla_wrapper').show();

        var dt = $('#table_clientes').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            columnDefs: [{ orderable: false, targets: [8] }],
            pageLength: 15,
            order: [[1,'asc']]
        });
    });
}

function limpiarFiltros() {
    $('#filtro_beneficio, #filtro_cartera').val('');
    cargarClientes();
}

// ══════════════════════════════════════════════
// VISTA DETALLE 360°
// ══════════════════════════════════════════════
function verDetalle(id) {
    _cliId = id;
    _tabsLoaded = {};

    $.getJSON('ajax/clientes/clientes.php?action=get&id='+id, function(res) {
        if (!res.success) { alert('Error al cargar el cliente'); return; }
        _cliData = res.data;
        var d = res.data;

        // Header
        $('#detalle_nombre').text(d.cli_descripcion);
        var subtitulo = [];
        if (d.cli_ciudad)           subtitulo.push(d.cli_ciudad);
        if (d.cli_numero_convenio)  subtitulo.push('Conv. ' + d.cli_numero_convenio);
        if (d.cli_tipo_cartera)     subtitulo.push('Cartera ' + d.cli_tipo_cartera + ' días');
        $('#detalle_subtitulo').text(subtitulo.join(' · '));

        // Tab Info
        $('#inf_convenio').text(d.cli_numero_convenio || '—');
        $('#inf_ciudad').text(d.cli_ciudad || '—');
        $('#inf_contacto').text(d.cli_contacto || '—');
        $('#inf_email').html(d.cli_email ? '<a href="mailto:'+d.cli_email+'">'+d.cli_email+'</a>' : '—');
        $('#inf_email2').html(d.cli_email2 ? '<a href="mailto:'+d.cli_email2+'">'+d.cli_email2+'</a>' : '—');
        $('#inf_telefono').text(d.cli_telefono || '—');
        $('#inf_telefono2').text(d.cli_telefono2 || '—');
        $('#inf_dia_corte').text(d.cli_dia_corte && d.cli_dia_corte != '0' ? 'Día ' + d.cli_dia_corte : '—');

        if (d.cli_tipo_beneficio) {
            var bc = d.cli_tipo_beneficio === 'Cupo' ? 'info' : 'primary';
            $('#inf_tipo_beneficio').html('<span class="badge badge-'+bc+'">'+d.cli_tipo_beneficio+'</span>');
            var val = d.cli_tipo_beneficio === 'Cupo'
                ? '$ ' + parseFloat(d.cli_valor_beneficio||0).toFixed(2)
                : parseFloat(d.cli_valor_beneficio||0).toFixed(2) + '%';
            $('#inf_valor_beneficio').text(val);
        } else {
            $('#inf_tipo_beneficio').html('<span class="text-muted">—</span>');
            $('#inf_valor_beneficio').text('—');
        }
        if (d.cli_tipo_cartera) {
            $('#inf_tipo_cartera').html('<span class="badge badge-'+(cartBadge[d.cli_tipo_cartera]||'secondary')+'">'+d.cli_tipo_cartera+' días</span>');
        } else {
            $('#inf_tipo_cartera').html('<span class="text-muted">—</span>');
        }
        $('#inf_comision').text(parseFloat(d.cli_comision||0).toFixed(2) + '%');

        // Resetear tabs
        $('#tabs_detalle .nav-link').removeClass('active');
        $('#tabs_detalle .nav-link[data-tab="info"]').addClass('active');
        $('.tab-panel').hide();
        $('#tab_info').show();

        // Cambiar vista
        $('#vista_lista').hide();
        $('#vista_detalle').show();
        $('#page_title').text(d.cli_descripcion);
        $('#breadcrumb_current').text(d.cli_descripcion);
        $('#header_actions').hide();

        // Cargar personal inmediatamente (badge)
        cargarTabPersonal();
    });
}

function volverLista() {
    $('#vista_detalle').hide();
    $('#vista_lista').show();
    $('#page_title').text('CLIENTES');
    $('#breadcrumb_current').text('Clientes');
    $('#header_actions').show();
    _cliId = null;
    _cliData = null;
}

function editarClienteActual() {
    if (_cliId) editarCliente(_cliId);
}

// ══════════════════════════════════════════════
// TABS
// ══════════════════════════════════════════════
function cambiarTab(el, tab) {
    $('#tabs_detalle .nav-link').removeClass('active');
    $(el).addClass('active');
    $('.tab-panel').hide();
    $('#tab_' + tab).show();

    if (!_tabsLoaded[tab]) {
        if      (tab === 'personal')      cargarTabPersonal();
        else if (tab === 'consumos')      cargarTabConsumos();
        else if (tab === 'estado_cuenta') cargarTabEC();
        else if (tab === 'giftcards')     cargarTabGiftCards();
    }
}

// — Personal ──────────────────────────────────────────────────────────────────
function cargarTabPersonal() {
    if (_tabsLoaded['personal']) return;
    $('#loader_personal').show();
    $('#tabla_personal_wrapper').hide();

    $.getJSON('ajax/clientes/clientes.php?action=personal_list&cli_id='+_cliId, function(res) {
        $('#loader_personal').hide();
        if (!res.success) return;

        $('#badge_personal').text(res.data.length);
        var html = '';
        $.each(res.data, function(i, p) {
            var estadoBadge = {activo:'success', bloqueado:'warning', inactivo:'secondary'}[p.per_estado] || 'secondary';
            html += '<tr>'
                + '<td>' + (i+1) + '</td>'
                + '<td>' + p.per_nombre + '</td>'
                + '<td>' + (p.per_documento || '—') + '</td>'
                + '<td><code>' + (p.per_numero_tarjeta || '—') + '</code></td>'
                + '<td>' + (p.per_correo || '—') + '</td>'
                + '<td><span class="badge badge-'+estadoBadge+'">'+p.per_estado+'</span></td>'
                + '<td class="text-right">$ ' + parseFloat(p.per_cupo_asignado||0).toFixed(2) + '</td>'
                + '<td class="text-right">$ ' + parseFloat(p.per_cupo_disponible||0).toFixed(2) + '</td>'
              + '</tr>';
        });
        $('#tbody_personal').html(html);

        if ($.fn.DataTable.isDataTable('#table_personal')) $('#table_personal').DataTable().destroy();
        $('#table_personal').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            pageLength: 10, order: [[1,'asc']]
        });

        $('#tabla_personal_wrapper').show();
        _tabsLoaded['personal'] = true;
    });
}

// — Consumos ──────────────────────────────────────────────────────────────────
function cargarTabConsumos() {
    if (_tabsLoaded['consumos']) return;
    $('#loader_consumos').show();
    $('#tabla_consumos_wrapper').hide();

    $.getJSON('ajax/clientes/clientes.php?action=consumos_list&cli_id='+_cliId, function(res) {
        $('#loader_consumos').hide();
        if (!res.success) return;

        var html = '';
        $.each(res.data, function(i, c) {
            var estColor = {pendiente:'warning', exitoso:'success', rechazado:'danger', anulado:'secondary'}[c.con_estado] || 'secondary';
            html += '<tr>'
                + '<td>' + c.con_fecha + ' <small class="text-muted">' + (c.con_hora||'') + '</small></td>'
                + '<td>' + c.per_nombre + '</td>'
                + '<td><code>' + c.con_numero_tarjeta + '</code></td>'
                + '<td>' + c.local_nombre + '</td>'
                + '<td class="text-right font-weight-bold">$ ' + parseFloat(c.con_valor_total||0).toFixed(2) + '</td>'
                + '<td class="text-right">$ ' + parseFloat(c.con_monto_convenio||0).toFixed(2) + '</td>'
                + '<td class="text-right">$ ' + parseFloat(c.con_monto_externo||0).toFixed(2) + '</td>'
                + '<td><span class="badge badge-'+estColor+'">'+c.con_estado+'</span></td>'
              + '</tr>';
        });
        $('#tbody_consumos').html(html);

        if ($.fn.DataTable.isDataTable('#table_consumos')) $('#table_consumos').DataTable().destroy();
        $('#table_consumos').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            pageLength: 15, order: [[0,'desc']]
        });

        $('#tabla_consumos_wrapper').show();
        _tabsLoaded['consumos'] = true;
    });
}

// — Estados de Cuenta ─────────────────────────────────────────────────────────
function cargarTabEC() {
    if (_tabsLoaded['estado_cuenta']) return;
    $('#loader_ec').show();
    $('#tabla_ec_wrapper').hide();

    $.getJSON('ajax/clientes/clientes.php?action=estado_cuenta_list&cli_id='+_cliId, function(res) {
        $('#loader_ec').hide();
        if (!res.success) return;

        var html = '';
        $.each(res.data, function(i, e) {
            var envBadge = {pendiente:'warning', enviado:'success', error:'danger'}[e.ec_estado_envio] || 'secondary';
            var pdf = e.ec_archivo_pdf
                ? '<a href="' + e.ec_archivo_pdf + '" target="_blank" class="btn btn-xs btn-outline-danger"><i class="icon dripicons-document"></i> PDF</a>'
                : '<span class="text-muted">—</span>';
            html += '<tr>'
                + '<td>' + e.ec_id + '</td>'
                + '<td>' + e.ec_periodo_inicio + ' → ' + e.ec_periodo_fin + '</td>'
                + '<td class="text-right font-weight-bold">$ ' + parseFloat(e.ec_monto_total||0).toFixed(2) + '</td>'
                + '<td>' + e.ec_fecha_generacion + '</td>'
                + '<td><span class="badge badge-'+envBadge+'">'+e.ec_estado_envio+'</span></td>'
                + '<td>' + pdf + '</td>'
              + '</tr>';
        });
        $('#tbody_ec').html(html);

        if ($.fn.DataTable.isDataTable('#table_ec')) $('#table_ec').DataTable().destroy();
        $('#table_ec').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            pageLength: 10, order: [[0,'desc']]
        });

        $('#tabla_ec_wrapper').show();
        _tabsLoaded['estado_cuenta'] = true;
    });
}

// — Gift Cards ─────────────────────────────────────────────────────────────────
function cargarTabGiftCards() {
    if (_tabsLoaded['giftcards']) return;
    $('#loader_gc').show();
    $('#tabla_gc_wrapper, #gc_sin_datos').hide();

    $.getJSON('ajax/clientes/clientes.php?action=giftcard_list&cli_id='+_cliId, function(res) {
        $('#loader_gc').hide();
        if (!res.success) return;

        if (!res.data.length) {
            $('#gc_sin_datos').show();
        } else {
            var html = '';
            $.each(res.data, function(i, g) {
                html += '<tr>'
                    + '<td>' + g.lgc_id + '</td>'
                    + '<td>' + g.lgc_fecha + '</td>'
                    + '<td>' + g.solicitante + '</td>'
                    + '<td class="text-center">' + g.lgc_cantidad + '</td>'
                    + '<td class="text-right">$ ' + parseFloat(g.lgc_cupo_codigo||0).toFixed(2) + '</td>'
                    + '<td class="text-center"><span class="badge badge-success">' + (g.activos||0) + '</span></td>'
                    + '<td class="text-center"><span class="badge badge-secondary">' + (g.consumidos||0) + '</span></td>'
                    + '<td class="text-center"><span class="badge badge-warning">' + (g.vencidos||0) + '</span></td>'
                  + '</tr>';
            });
            $('#tbody_gc').html(html);

            if ($.fn.DataTable.isDataTable('#table_gc')) $('#table_gc').DataTable().destroy();
            $('#table_gc').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                pageLength: 10, order: [[1,'desc']]
            });
            $('#tabla_gc_wrapper').show();
        }
        _tabsLoaded['giftcards'] = true;
    });
}

// ══════════════════════════════════════════════
// MODAL CREAR / EDITAR
// ══════════════════════════════════════════════
function abrirModalNuevo() {
    $('#formCliente')[0].reset();
    $('#cli_id').val('');
    $('#modalClienteLabel').text('Nuevo Cliente');
    actualizarPrefijo('');
    $('#modalCliente').modal('show');
}

function editarCliente(id) {
    $.getJSON('ajax/clientes/clientes.php?action=get&id='+id, function(res) {
        if (!res.success) { alert('Error al cargar cliente'); return; }
        var d = res.data;
        $('#cli_id').val(d.cli_id);
        $('#cli_descripcion').val(d.cli_descripcion);
        $('#cli_numero_convenio').val(d.cli_numero_convenio||'');
        $('#cli_ciudad').val(d.cli_ciudad||'');
        $('#cli_contacto').val(d.cli_contacto||'');
        $('#cli_email').val(d.cli_email||'');
        $('#cli_email2').val(d.cli_email2||'');
        $('#cli_telefono').val(d.cli_telefono||'');
        $('#cli_telefono2').val(d.cli_telefono2||'');
        $('#cli_dia_corte').val(d.cli_dia_corte||'0');
        $('#cli_tipo_beneficio').val(d.cli_tipo_beneficio||'');
        $('#cli_valor_beneficio').val(d.cli_valor_beneficio||'');
        $('#cli_tipo_cartera').val(d.cli_tipo_cartera||'');
        $('#cli_comision').val(d.cli_comision||'0');
        actualizarPrefijo(d.cli_tipo_beneficio||'');
        $('#modalClienteLabel').text('Editar Cliente');
        $('#modalCliente').modal('show');
    });
}

function actualizarPrefijo(tipo) {
    if (tipo === 'Porcentaje') {
        $('#prefix_ben').html('<span class="input-group-text">%</span>');
        $('#label_valor').text('Porcentaje de descuento');
    } else {
        $('#prefix_ben').html('<span class="input-group-text">$</span>');
        $('#label_valor').text('Cupo máximo');
    }
}

$('#cli_tipo_beneficio').on('change', function() { actualizarPrefijo($(this).val()); });

$('#formCliente').on('submit', function(e) {
    e.preventDefault();
    var id     = $('#cli_id').val();
    var action = id ? 'editar' : 'crear';
    var btn    = $('#btn_guardar');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

    $.post('ajax/clientes/clientes.php?action='+action, $(this).serialize(), function(res) {
        btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar');
        if (res.success) {
            $('#modalCliente').modal('hide');
            if (_cliId) {
                // Estamos en vista detalle — refrescar nombre si editamos el actual
                _tabsLoaded = {};
                verDetalle(id || res.id);
            } else {
                cargarClientes();
            }
        } else {
            alert(res.mensaje || 'Error al guardar');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar');
        alert('Error de conexión');
    });
});

// ══════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════
$(document).ready(function() {
    cargarClientes();
});
</script>
