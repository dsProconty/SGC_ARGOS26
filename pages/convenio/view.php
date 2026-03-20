<div class="content">
    <!-- PAGE HEADER -->
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">CONVENIOS</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Convenios</li>
                        </ol>
                    </nav>
                </div>
                <div class="ml-auto">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#modal_convenio" onclick="abrirNuevo()">
                        <i class="icon dripicons-plus"></i> Registrar nuevo convenio
                    </button>
                </div>
            </div>
        </div>
    </header>

    <section class="container m-t-30">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="icon dripicons-document"></i> Lista de Convenios</span>
                <span id="div_contador" class="text-muted small"></span>
            </div>
            <div class="card-body p-0">
                <div id="div_loading" class="text-center p-5">
                    <span class="spinner-border text-primary"></span>
                    <p class="mt-2 text-muted">Cargando...</p>
                </div>
                <div id="div_tabla" style="display:none; overflow-x:auto;">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Nombre del convenio</th>
                                <th>Correo contacto</th>
                                <th>Tipo de beneficio</th>
                                <th>Valor / Cupo</th>
                                <th>Tipo cartera</th>
                                <th>Día de corte</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_convenios"></tbody>
                    </table>
                </div>
                <div id="div_vacio" class="text-center p-5 text-muted" style="display:none;">
                    <i class="icon dripicons-document" style="font-size:2rem;"></i>
                    <p class="mt-2">No hay convenios registrados</p>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- MODAL CREAR / EDITAR -->
<div class="modal fade" id="modal_convenio" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_titulo">Registrar nuevo convenio</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cli_id">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nombre del convenio <span class="text-danger">*</span></label>
                            <input type="text" id="cli_descripcion" class="form-control" maxlength="50" placeholder="Nombre de la empresa">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Correo de contacto <span class="text-danger">*</span></label>
                            <input type="email" id="cli_email" class="form-control" maxlength="50" placeholder="correo@empresa.com">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Correo secundario</label>
                            <input type="email" id="cli_email2" class="form-control" maxlength="50" placeholder="correo2@empresa.com (opcional)">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Ciudad</label>
                            <input type="text" id="cli_ciudad" class="form-control" maxlength="100" placeholder="Ciudad">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Contacto</label>
                            <input type="text" id="cli_contacto" class="form-control" maxlength="150" placeholder="Nombre del contacto">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" id="cli_telefono" class="form-control" maxlength="50" placeholder="0999999999">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Día de corte <span class="text-danger">*</span></label>
                            <input type="number" id="cli_dia_corte" class="form-control" min="1" max="31" placeholder="Ej: 15">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tipo de beneficio <span class="text-danger">*</span></label>
                            <select id="cli_tipo_beneficio" class="form-control">
                                <option value="">-- Seleccione --</option>
                                <option value="Cupo">Cupo</option>
                                <option value="Porcentaje">Porcentaje</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3" id="div_valor_beneficio" style="display:none;">
                        <div class="form-group">
                            <label id="lbl_valor_beneficio">Monto del cupo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend" id="pre_valor"><span class="input-group-text">$</span></div>
                                <input type="number" id="cli_valor_beneficio" class="form-control" min="0" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tipo de cartera <span class="text-danger">*</span></label>
                            <select id="cli_tipo_cartera" class="form-control">
                                <option value="">-- Seleccione --</option>
                                <option value="30">30 días</option>
                                <option value="60">60 días</option>
                                <option value="90">90 días</option>
                                <option value="90+">90+ días</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div id="alerta_modal" class="mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn_guardar">
                    <i class="icon dripicons-checkmark"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    cargarConvenios();

    // Mostrar/ocultar campo valor según tipo de beneficio
    $('#cli_tipo_beneficio').on('change', function () {
        var tipo = $(this).val();
        if (tipo === 'Cupo') {
            $('#lbl_valor_beneficio').html('Monto del cupo <span class="text-danger">*</span>');
            $('#pre_valor').html('<span class="input-group-text">$</span>');
            $('#cli_valor_beneficio').attr('placeholder', '0.00');
            $('#div_valor_beneficio').slideDown();
        } else if (tipo === 'Porcentaje') {
            $('#lbl_valor_beneficio').html('Porcentaje de descuento <span class="text-danger">*</span>');
            $('#pre_valor').html('<span class="input-group-text">%</span>');
            $('#cli_valor_beneficio').attr('placeholder', 'Ej: 10');
            $('#div_valor_beneficio').slideDown();
        } else {
            $('#div_valor_beneficio').slideUp();
        }
    });

    // -------------------------------------------------------
    // Cargar tabla de convenios
    // -------------------------------------------------------
    function cargarConvenios() {
        $('#div_loading').show();
        $('#div_tabla').hide();
        $('#div_vacio').hide();

        $.ajax({
            url: 'ajax/convenio/convenio.php',
            type: 'GET',
            data: { action: 'list' },
            dataType: 'json',
            success: function (resp) {
                $('#div_loading').hide();
                if (!resp.success || resp.data.length === 0) {
                    $('#div_vacio').show();
                    return;
                }
                renderTabla(resp.data);
                $('#div_contador').text(resp.data.length + ' convenios');
            },
            error: function () {
                $('#div_loading').hide();
                $('#div_vacio').html('<div class="alert alert-danger m-3">Error al cargar convenios</div>').show();
            }
        });
    }

    function renderTabla(data) {
        var html = '';
        data.forEach(function (c, i) {
            var beneficio = c.cli_tipo_beneficio === 'Cupo'
                ? '<span class="badge badge-success">Cupo $' + parseFloat(c.cli_valor_beneficio || 0).toFixed(2) + '</span>'
                : (c.cli_tipo_beneficio === 'Porcentaje'
                    ? '<span class="badge badge-info">Descuento ' + c.cli_valor_beneficio + '%</span>'
                    : '<span class="badge badge-secondary">Sin definir</span>');

            var cartera = c.cli_tipo_cartera
                ? '<span class="badge badge-light border">' + c.cli_tipo_cartera + ' días</span>'
                : '—';

            html += '<tr>'
                + '<td>' + (i + 1) + '</td>'
                + '<td><strong>' + htmlEsc(c.cli_descripcion) + '</strong>'
                + (c.cli_ciudad ? '<br><small class="text-muted">' + htmlEsc(c.cli_ciudad) + '</small>' : '')
                + '</td>'
                + '<td>' + (c.cli_email ? htmlEsc(c.cli_email) : '—') + '</td>'
                + '<td>' + beneficio + '</td>'
                + '<td>' + (c.cli_valor_beneficio ? (c.cli_tipo_beneficio === 'Cupo' ? '$' + parseFloat(c.cli_valor_beneficio).toFixed(2) : c.cli_valor_beneficio + '%') : '—') + '</td>'
                + '<td>' + cartera + '</td>'
                + '<td>' + (c.cli_dia_corte && c.cli_dia_corte != '0' ? 'Día ' + c.cli_dia_corte : '—') + '</td>'
                + '<td>'
                + '<button class="btn btn-sm btn-primary btn-editar mr-1" data-id="' + c.cli_id + '" title="Editar">'
                + '<i class="icon dripicons-document-edit"></i></button>'
                + '</td>'
                + '</tr>';
        });
        $('#tbody_convenios').html(html);
        $('#div_tabla').show();
    }

    // -------------------------------------------------------
    // Abrir modal nuevo
    // -------------------------------------------------------
    window.abrirNuevo = function () {
        $('#modal_titulo').text('Registrar nuevo convenio');
        $('#cli_id').val('');
        $('#cli_descripcion, #cli_email, #cli_email2, #cli_ciudad, #cli_contacto, #cli_telefono, #cli_valor_beneficio').val('');
        $('#cli_dia_corte').val('');
        $('#cli_tipo_beneficio, #cli_tipo_cartera').val('');
        $('#div_valor_beneficio').hide();
        ocultarAlerta();
    };

    // -------------------------------------------------------
    // Abrir modal editar
    // -------------------------------------------------------
    $(document).on('click', '.btn-editar', function () {
        var id = $(this).data('id');
        $.ajax({
            url: 'ajax/convenio/convenio.php',
            type: 'GET',
            data: { action: 'get', cli_id: id },
            dataType: 'json',
            success: function (resp) {
                if (!resp.success) return;
                var c = resp.data;
                $('#modal_titulo').text('Editar convenio');
                $('#cli_id').val(c.cli_id);
                $('#cli_descripcion').val(c.cli_descripcion);
                $('#cli_email').val(c.cli_email);
                $('#cli_email2').val(c.cli_email2);
                $('#cli_ciudad').val(c.cli_ciudad);
                $('#cli_contacto').val(c.cli_contacto);
                $('#cli_telefono').val(c.cli_telefono);
                $('#cli_dia_corte').val(c.cli_dia_corte);
                $('#cli_tipo_beneficio').val(c.cli_tipo_beneficio).trigger('change');
                $('#cli_valor_beneficio').val(c.cli_valor_beneficio);
                $('#cli_tipo_cartera').val(c.cli_tipo_cartera);
                ocultarAlerta();
                $('#modal_convenio').modal('show');
            }
        });
    });

    // -------------------------------------------------------
    // Guardar (crear o editar)
    // -------------------------------------------------------
    $('#btn_guardar').on('click', function () {
        var descripcion    = $('#cli_descripcion').val().trim();
        var email          = $('#cli_email').val().trim();
        var dia_corte      = $('#cli_dia_corte').val().trim();
        var tipo_beneficio = $('#cli_tipo_beneficio').val();
        var tipo_cartera   = $('#cli_tipo_cartera').val();
        var valor_beneficio = $('#cli_valor_beneficio').val().trim();

        if (!descripcion) { mostrarAlerta('danger', 'El nombre del convenio es requerido'); return; }
        if (!email)        { mostrarAlerta('danger', 'El correo de contacto es requerido'); return; }
        if (!dia_corte)    { mostrarAlerta('danger', 'El día de corte es requerido'); return; }
        if (!tipo_beneficio) { mostrarAlerta('danger', 'Seleccione el tipo de beneficio'); return; }
        if (!tipo_cartera) { mostrarAlerta('danger', 'Seleccione el tipo de cartera'); return; }
        if (tipo_beneficio && !valor_beneficio) { mostrarAlerta('danger', 'Ingrese el valor del beneficio'); return; }

        var payload = {
            action:            $('#cli_id').val() ? 'editar' : 'crear',
            cli_id:            $('#cli_id').val(),
            cli_descripcion:   descripcion,
            cli_email:         email,
            cli_email2:        $('#cli_email2').val().trim(),
            cli_ciudad:        $('#cli_ciudad').val().trim(),
            cli_contacto:      $('#cli_contacto').val().trim(),
            cli_telefono:      $('#cli_telefono').val().trim(),
            cli_dia_corte:     dia_corte,
            cli_tipo_beneficio: tipo_beneficio,
            cli_valor_beneficio: valor_beneficio,
            cli_tipo_cartera:  tipo_cartera
        };

        $('#btn_guardar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

        $.ajax({
            url: 'ajax/convenio/convenio.php',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    $('#modal_convenio').modal('hide');
                    cargarConvenios();
                } else {
                    mostrarAlerta('danger', resp.mensaje);
                }
            },
            error: function () { mostrarAlerta('danger', 'Error de conexión'); },
            complete: function () {
                $('#btn_guardar').prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar');
            }
        });
    });

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------
    function mostrarAlerta(tipo, msg) {
        $('#alerta_modal').html('<div class="alert alert-' + tipo + ' mb-0">' + msg + '</div>').show();
    }
    function ocultarAlerta() {
        $('#alerta_modal').hide().html('');
    }
    function htmlEsc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

});
</script>
