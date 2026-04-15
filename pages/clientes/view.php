<div class="content">
    <!-- PAGE HEADER -->
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">CLIENTES</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="?module=dashboard"><i class="icon dripicons-home"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Clientes</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="abrirModalNuevo()">
                        <i class="icon dripicons-plus"></i> Nuevo Cliente
                    </button>
                </div>
            </div>
        </div>
    </header>

    <section class="page-content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="mb-0">Listado de Clientes / Empresas</h5>
                        <span class="ml-2 text-muted" id="total_clientes"></span>
                    </div>
                    <div class="card-body">
                        <div id="loader_clientes" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                        </div>
                        <div id="tabla_clientes"></div>
                    </div>
                </div>
            </div>
        </div>
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
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCliente">
                <div class="modal-body">
                    <input type="hidden" id="cli_id" name="cli_id">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Nombre de la empresa / cliente <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cli_descripcion" name="cli_descripcion"
                                       placeholder="Ej: EMPRESA XYZ S.A." required autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>N° Convenio</label>
                                <input type="text" class="form-control" id="cli_numero_convenio" name="cli_numero_convenio"
                                       placeholder="Ej: CONV-001" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ciudad</label>
                                <input type="text" class="form-control" id="cli_ciudad" name="cli_ciudad"
                                       placeholder="Ej: Guayaquil" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Persona de contacto</label>
                                <input type="text" class="form-control" id="cli_contacto" name="cli_contacto"
                                       placeholder="Ej: Juan Pérez" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email principal</label>
                                <input type="email" class="form-control" id="cli_email" name="cli_email"
                                       placeholder="correo@empresa.com" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email secundario</label>
                                <input type="email" class="form-control" id="cli_email2" name="cli_email2"
                                       placeholder="correo2@empresa.com" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" class="form-control" id="cli_telefono" name="cli_telefono"
                                       placeholder="Ej: 0990000000" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Teléfono alternativo</label>
                                <input type="text" class="form-control" id="cli_telefono2" name="cli_telefono2"
                                       placeholder="Ej: 042000000" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Día de corte</label>
                                <select class="form-control" id="cli_dia_corte" name="cli_dia_corte">
                                    <option value="0">— Sin corte —</option>
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                        <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
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
                                <label id="label_valor_beneficio">Valor del beneficio</label>
                                <div class="input-group">
                                    <div class="input-group-prepend" id="prefix_beneficio">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" class="form-control" id="cli_valor_beneficio"
                                           name="cli_valor_beneficio" min="0" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo de cartera (días)</label>
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
                                    <input type="number" class="form-control" id="cli_comision"
                                           name="cli_comision" min="0" max="100" step="0.01"
                                           placeholder="0.00" value="0">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /.modal-body -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn_guardar_cliente">
                        <i class="icon dripicons-checkmark"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL — VER DETALLE CLIENTE
══════════════════════════════════════════════════ -->
<div class="modal fade" id="modalVerCliente" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="icon dripicons-briefcase"></i>
                    <span id="ver_nombre_empresa"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detalle_cliente_body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn_editar_desde_ver">
                    <i class="icon dripicons-document-edit"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════ -->
<script>
var _cliIdEnVista = null;

function cargarClientes() {
    $('#loader_clientes').show();
    $('#tabla_clientes').empty();

    $.get('ajax/clientes/clientes.php?action=list', function(html) {
        $('#loader_clientes').hide();
        $('#tabla_clientes').html(html);

        var dt = $('#table_clientes').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            columnDefs: [{ orderable: false, targets: [9] }],
            pageLength: 15,
            order: [[1, 'asc']]
        });

        $('#total_clientes').text('(' + dt.data().count() + ' registros)');
    });
}

function abrirModalNuevo() {
    $('#formCliente')[0].reset();
    $('#cli_id').val('');
    $('#modalClienteLabel').text('Nuevo Cliente');
    actualizarPrefijoBeneficio('');
    $('#modalCliente').modal('show');
}

function editarCliente(id) {
    $.getJSON('ajax/clientes/clientes.php?action=get&id=' + id, function(res) {
        if (!res.success) { alert('No se pudo cargar el cliente.'); return; }
        var d = res.data;
        $('#cli_id').val(d.cli_id);
        $('#cli_descripcion').val(d.cli_descripcion);
        $('#cli_numero_convenio').val(d.cli_numero_convenio || '');
        $('#cli_ciudad').val(d.cli_ciudad || '');
        $('#cli_contacto').val(d.cli_contacto || '');
        $('#cli_email').val(d.cli_email || '');
        $('#cli_email2').val(d.cli_email2 || '');
        $('#cli_telefono').val(d.cli_telefono || '');
        $('#cli_telefono2').val(d.cli_telefono2 || '');
        $('#cli_dia_corte').val(d.cli_dia_corte || '0');
        $('#cli_tipo_beneficio').val(d.cli_tipo_beneficio || '');
        $('#cli_valor_beneficio').val(d.cli_valor_beneficio || '');
        $('#cli_tipo_cartera').val(d.cli_tipo_cartera || '');
        $('#cli_comision').val(d.cli_comision || '0');
        actualizarPrefijoBeneficio(d.cli_tipo_beneficio || '');
        $('#modalClienteLabel').text('Editar Cliente');
        $('#modalVerCliente').modal('hide');
        $('#modalCliente').modal('show');
    });
}

function verCliente(id) {
    _cliIdEnVista = id;
    $.getJSON('ajax/clientes/clientes.php?action=get&id=' + id, function(res) {
        if (!res.success) { alert('No se pudo cargar el cliente.'); return; }
        var d = res.data;
        $('#ver_nombre_empresa').text(d.cli_descripcion);

        var tipoBadge   = d.cli_tipo_beneficio === 'Cupo' ? 'info' : 'primary';
        var carteraBadges = {'30':'success','60':'warning','90':'danger','90+':'dark'};
        var carteraBadge = carteraBadges[d.cli_tipo_cartera] || 'secondary';
        var valorLabel  = d.cli_tipo_beneficio === 'Cupo'
            ? '$ ' + parseFloat(d.cli_valor_beneficio || 0).toFixed(2)
            : (d.cli_valor_beneficio || 0) + '%';

        function fila(label, valor) {
            return '<tr><th class="text-muted font-weight-normal" style="width:45%">' + label + '</th>'
                 + '<td><strong>' + valor + '</strong></td></tr>';
        }

        var html = '<div class="row">'
            + '<div class="col-md-6"><table class="table table-sm table-borderless">'
            + fila('N° Convenio', d.cli_numero_convenio || '—')
            + fila('Ciudad', d.cli_ciudad || '—')
            + fila('Contacto', d.cli_contacto || '—')
            + fila('Email principal', d.cli_email ? '<a href="mailto:'+d.cli_email+'">'+d.cli_email+'</a>' : '—')
            + fila('Email secundario', d.cli_email2 ? '<a href="mailto:'+d.cli_email2+'">'+d.cli_email2+'</a>' : '—')
            + fila('Teléfono', d.cli_telefono || '—')
            + fila('Teléfono alt.', d.cli_telefono2 || '—')
            + fila('Día de corte', d.cli_dia_corte == '0' ? '—' : 'Día ' + d.cli_dia_corte)
            + '</table></div>'
            + '<div class="col-md-6"><div class="card bg-light mb-3"><div class="card-body">'
            + '<h6 class="card-title text-muted">Configuración Comercial</h6>';

        if (d.cli_tipo_beneficio) {
            html += '<p><strong>Tipo beneficio:</strong> <span class="badge badge-' + tipoBadge + '">' + d.cli_tipo_beneficio + '</span></p>'
                 + '<p><strong>Valor:</strong> ' + valorLabel + '</p>';
        } else {
            html += '<p class="text-muted">Sin beneficio configurado</p>';
        }

        if (d.cli_tipo_cartera) {
            html += '<p><strong>Cartera:</strong> <span class="badge badge-' + carteraBadge + '">' + d.cli_tipo_cartera + ' días</span></p>';
        }

        html += '<p><strong>Comisión:</strong> ' + parseFloat(d.cli_comision || 0).toFixed(2) + '%</p>'
             + '</div></div></div></div>';

        $('#detalle_cliente_body').html(html);
        $('#modalVerCliente').modal('show');
    });
}

function actualizarPrefijoBeneficio(tipo) {
    if (tipo === 'Porcentaje') {
        $('#prefix_beneficio').html('<span class="input-group-text">%</span>');
        $('#label_valor_beneficio').text('Porcentaje de descuento');
    } else {
        $('#prefix_beneficio').html('<span class="input-group-text">$</span>');
        $('#label_valor_beneficio').text('Cupo máximo');
    }
}

$('#cli_tipo_beneficio').on('change', function() {
    actualizarPrefijoBeneficio($(this).val());
});

$('#btn_editar_desde_ver').on('click', function() {
    if (_cliIdEnVista) editarCliente(_cliIdEnVista);
});

$('#formCliente').on('submit', function(e) {
    e.preventDefault();
    var id     = $('#cli_id').val();
    var action = id ? 'editar' : 'crear';
    var btn    = $('#btn_guardar_cliente');

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

    $.post('ajax/clientes/clientes.php?action=' + action, $(this).serialize(), function(res) {
        btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar');
        if (res.success) {
            $('#modalCliente').modal('hide');
            cargarClientes();
        } else {
            alert(res.mensaje || 'Error al guardar');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar');
        alert('Error de conexión al servidor');
    });
});

$(document).ready(function() {
    cargarClientes();
});
</script>
