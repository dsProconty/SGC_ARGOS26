<?php
require_once 'config/database.php';
$rMarcasCombo = mysqli_query($mysqli, "SELECT mar_id, mar_descripcion FROM marca ORDER BY mar_descripcion ASC");
?>
<div class="content">
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">Combos Especiales</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Combos Especiales</li>
                        </ol>
                    </nav>
                </div>
                <button class="btn btn-success" id="btn_nuevo_combo" style="color:#fff;">
                    <i class="icon dripicons-plus"></i> Nuevo Combo
                </button>
            </div>
        </div>
    </header>

    <section class="container m-t-30">

        <!-- ===== LISTA DE COMBOS ===== -->
        <div class="card mb-4">
            <h5 class="card-header"><i class="icon dripicons-tags"></i> Combos</h5>
            <div class="card-body p-0">
                <div id="combos_loading" class="text-center p-5 text-muted">
                    <span class="spinner-border spinner-border-sm"></span>
                </div>
                <div id="combos_vacio" class="text-center p-5 text-muted" style="display:none;">
                    Todavía no hay combos creados
                </div>
                <div id="combos_tabla_wrap" style="display:none; overflow-x:auto;">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Código</th>
                                <th>Franquicia</th>
                                <th>Valor</th>
                                <th>Vence</th>
                                <th>Estado</th>
                                <th>Usos</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tbody_combos"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== REPORTE DE MOVIMIENTOS ===== -->
        <div class="card">
            <h5 class="card-header"><i class="icon dripicons-to-do"></i> Movimientos</h5>
            <div class="card-body">
                <div class="row align-items-end mb-3">
                    <div class="col-md-3">
                        <label>Combo</label>
                        <select id="mov_combo" class="form-control">
                            <option value="">Todos los combos</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Fecha inicio</label>
                        <input type="date" id="mov_inicio" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Fecha fin</label>
                        <input type="date" id="mov_fin" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary btn-block" id="btn_buscar_mov">
                            <i class="icon dripicons-search"></i> Buscar
                        </button>
                    </div>
                </div>

                <div id="mov_loading" class="text-center p-4 text-muted" style="display:none;">
                    <span class="spinner-border spinner-border-sm"></span>
                </div>
                <div id="mov_vacio" class="text-center p-4 text-muted" style="display:none;">
                    Sin movimientos en el período
                </div>
                <div id="mov_tabla_wrap" style="display:none; overflow-x:auto;">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Combo</th>
                                <th>Tipo de pago</th>
                                <th>Empleado</th>
                                <th>Cajero</th>
                                <th>Local</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_mov"></tbody>
                    </table>
                </div>
            </div>
        </div>

    </section>
</div>

<!-- ===== MODAL NUEVO / EDITAR COMBO ===== -->
<div class="modal fade" id="modal_combo" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_combo_titulo">Nuevo Combo</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_combo"></div>
                <input type="hidden" id="combo_ce_id">
                <div class="form-group">
                    <label>Nombre <span class="text-danger">*</span></label>
                    <input type="text" id="combo_nombre" class="form-control" maxlength="150" placeholder="Ej: Pack Familiar">
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <input type="text" id="combo_descripcion" class="form-control" maxlength="255" placeholder="Qué incluye el combo">
                </div>
                <div class="row">
                    <div class="col-6 form-group">
                        <label>Código único <span class="text-danger">*</span></label>
                        <input type="text" id="combo_codigo" class="form-control" maxlength="50" placeholder="Ej: WK-2026-014" style="text-transform:uppercase;">
                    </div>
                    <div class="col-6 form-group">
                        <label>Precio <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                            <input type="number" id="combo_valor" class="form-control" min="0.01" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 form-group">
                        <label>Fecha de caducidad <span class="text-danger">*</span></label>
                        <input type="date" id="combo_fecha_caducidad" class="form-control">
                    </div>
                    <div class="col-6 form-group">
                        <label>Franquicia <span class="text-danger">*</span></label>
                        <select id="combo_mar_id" class="form-control">
                            <option value="">Seleccione...</option>
                            <?php while ($m = mysqli_fetch_assoc($rMarcasCombo)): ?>
                            <option value="<?= (int)$m['mar_id'] ?>"><?= htmlspecialchars($m['mar_descripcion']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <small class="text-muted" id="aviso_codigo_bloqueado" style="display:none;">El código no se puede cambiar una vez creado el combo.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn_guardar_combo">
                    <i class="icon dripicons-checkmark"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    var combos_cache = [];

    cargarCombos();
    $('#mov_inicio').val('<?= date('Y-m-01') ?>');
    $('#mov_fin').val('<?= date('Y-m-d') ?>');
    buscarMovimientos();

    function cargarCombos() {
        $('#combos_loading').show();
        $('#combos_vacio, #combos_tabla_wrap').hide();
        $.getJSON('ajax/combos/combos.php', { action: 'list' }, function (resp) {
            $('#combos_loading').hide();
            if (!resp.success || resp.data.length === 0) {
                $('#combos_vacio').show();
                return;
            }
            combos_cache = resp.data;
            renderCombos(resp.data);
            renderSelectCombos(resp.data);
            $('#combos_tabla_wrap').show();
        });
    }

    function renderCombos(data) {
        var html = '';
        data.forEach(function (c) {
            var badge = c.ce_estado === 'vigente'
                ? '<span class="badge badge-success">Vigente</span>'
                : '<span class="badge badge-secondary">Vencido</span>';
            html += '<tr>'
                + '<td><strong>' + htmlEsc(c.ce_nombre) + '</strong>' + (c.ce_descripcion ? '<br><small class="text-muted">' + htmlEsc(c.ce_descripcion) + '</small>' : '') + '</td>'
                + '<td><code>' + htmlEsc(c.ce_codigo) + '</code></td>'
                + '<td>' + htmlEsc(c.mar_descripcion) + '</td>'
                + '<td>$' + parseFloat(c.ce_valor).toFixed(2) + '</td>'
                + '<td>' + c.ce_fecha_caducidad + '</td>'
                + '<td>' + badge + '</td>'
                + '<td>' + c.total_usos + '</td>'
                + '<td><button class="btn btn-xs btn-outline-warning btn-editar-combo" data-id="' + c.ce_id + '" style="font-size:11px;padding:1px 6px;" title="Editar">'
                + '<i class="icon dripicons-pencil"></i></button></td>'
                + '</tr>';
        });
        $('#tbody_combos').html(html);
    }

    function renderSelectCombos(data) {
        var opts = '<option value="">Todos los combos</option>';
        data.forEach(function (c) {
            opts += '<option value="' + c.ce_id + '">' + htmlEsc(c.ce_nombre) + ' (' + htmlEsc(c.ce_codigo) + ')</option>';
        });
        $('#mov_combo').html(opts);
    }

    // ------------------------------------------------------------
    // Crear / editar combo
    // ------------------------------------------------------------
    $('#btn_nuevo_combo').on('click', function () {
        $('#modal_combo_titulo').text('Nuevo Combo');
        $('#combo_ce_id').val('');
        $('#combo_nombre, #combo_descripcion, #combo_codigo, #combo_valor, #combo_fecha_caducidad').val('');
        $('#combo_mar_id').val('');
        $('#combo_codigo').prop('disabled', false);
        $('#aviso_codigo_bloqueado').hide();
        $('#alerta_combo').html('');
        $('#modal_combo').modal('show');
    });

    $(document).on('click', '.btn-editar-combo', function () {
        var ce_id = $(this).data('id');
        var c = combos_cache.filter(function (x) { return x.ce_id == ce_id; })[0];
        if (!c) return;
        $('#modal_combo_titulo').text('Editar Combo');
        $('#combo_ce_id').val(c.ce_id);
        $('#combo_nombre').val(c.ce_nombre);
        $('#combo_descripcion').val(c.ce_descripcion || '');
        $('#combo_codigo').val(c.ce_codigo).prop('disabled', true);
        $('#combo_valor').val(c.ce_valor);
        $('#combo_fecha_caducidad').val(c.ce_fecha_caducidad);
        $('#combo_mar_id').val(c.mar_id);
        $('#aviso_codigo_bloqueado').show();
        $('#alerta_combo').html('');
        $('#modal_combo').modal('show');
    });

    $('#btn_guardar_combo').on('click', function () {
        var ce_id  = $('#combo_ce_id').val();
        var editar = !!ce_id;
        var data = {
            action: editar ? 'editar' : 'crear',
            nombre: $('#combo_nombre').val().trim(),
            descripcion: $('#combo_descripcion').val().trim(),
            codigo: $('#combo_codigo').val().trim(),
            valor: $('#combo_valor').val(),
            fecha_caducidad: $('#combo_fecha_caducidad').val(),
            mar_id: $('#combo_mar_id').val()
        };
        if (editar) data.ce_id = ce_id;

        if (!data.nombre || !data.codigo || !data.valor || !data.fecha_caducidad || !data.mar_id) {
            $('#alerta_combo').html('<div class="alert alert-danger">Complete todos los campos obligatorios</div>');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);
        $.ajax({
            url: 'ajax/combos/combos.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                $btn.prop('disabled', false);
                if (resp.success) {
                    $('#modal_combo').modal('hide');
                    cargarCombos();
                } else {
                    $('#alerta_combo').html('<div class="alert alert-danger">' + htmlEsc(resp.mensaje || 'No se pudo guardar el combo') + '</div>');
                }
            },
            error: function () {
                $btn.prop('disabled', false);
                $('#alerta_combo').html('<div class="alert alert-danger">Error de conexión</div>');
            }
        });
    });

    // ------------------------------------------------------------
    // Movimientos
    // ------------------------------------------------------------
    $('#btn_buscar_mov').on('click', buscarMovimientos);

    function buscarMovimientos() {
        var inicio = $('#mov_inicio').val();
        var fin    = $('#mov_fin').val();
        if (!inicio || !fin) return;

        $('#mov_vacio, #mov_tabla_wrap').hide();
        $('#mov_loading').show();

        var data = { action: 'movimientos', fecha_inicio: inicio, fecha_fin: fin };
        var ce_id = $('#mov_combo').val();
        if (ce_id) data.ce_id = ce_id;

        $.getJSON('ajax/combos/combos.php', data, function (resp) {
            $('#mov_loading').hide();
            if (!resp.success || resp.data.length === 0) {
                $('#mov_vacio').show();
                return;
            }
            renderMovimientos(resp.data);
            $('#mov_tabla_wrap').show();
        });
    }

    function renderMovimientos(data) {
        var html = '';
        data.forEach(function (m) {
            var anulado = m.con_estado === 'anulado';
            var tipoPago = m.per_nombre
                ? '<span class="badge badge-success">Crédito</span>'
                : '<span class="badge badge-secondary">Contado</span>';
            var monto = (parseFloat(m.con_monto_convenio) || 0) + (parseFloat(m.con_monto_externo) || 0);
            var empleadoCell = m.per_nombre
                ? htmlEsc(m.per_nombre) + '<br><small class="text-muted">' + htmlEsc(m.per_documento) + '</small>'
                : '—';
            html += '<tr' + (anulado ? ' class="table-secondary"' : '') + '>'
                + '<td>#' + m.con_id + (anulado ? ' <span class="badge badge-danger">ANULADA</span>' : '') + '</td>'
                + '<td>' + m.con_fecha + '</td>'
                + '<td>' + m.con_hora + '</td>'
                + '<td>' + htmlEsc(m.ce_nombre) + '<br><small class="text-muted"><code>' + htmlEsc(m.ce_codigo) + '</code></small></td>'
                + '<td>' + tipoPago + '</td>'
                + '<td>' + empleadoCell + '</td>'
                + '<td>' + htmlEsc(m.cajero_nombre || '—') + '</td>'
                + '<td>' + htmlEsc(m.local_nombre || '—') + '</td>'
                + '<td><strong>$' + monto.toFixed(2) + '</strong></td>'
                + '</tr>';
        });
        $('#tbody_mov').html(html);
    }

    function htmlEsc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
});
</script>
