<?php
if (!isset($_SESSION['id_user']) || $_SESSION['permisos_acceso'] !== 'Super Admin') {
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit;
}
?>

<style>
    /* ── Locales Module ─────────────────────────────── */
    :root {
        --arg-blue: #399AF2;
        --arg-teal: #2fbfa0;
        --arg-red: #ff5c75;
        --arg-yellow: #FFCE67;
        --arg-dark: #2c3e50;
        --arg-muted: #8899aa;
        --arg-bg: #f4f7fb;
        --arg-border: #e4ecf3;
    }

    #locales-wrap {
        font-family: inherit;
    }

    /* Header strip */
    .loc-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .loc-header-left h2 {
        font-size: 1rem;
        font-weight: 100;
        color: var(--arg-dark);
        margin: 0;
        letter-spacing: .2px;
    }

    .loc-header-left p {
        margin: 2px 0 0;
        font-size: .78rem;
        color: var(--arg-muted);
    }

    /* Search */
    .loc-search-wrap {
        position: relative;
        width: 240px;
    }

    .loc-search-wrap input {
        border: 1.5px solid var(--arg-border);
        border-radius: 8px;
        padding: 7px 12px 7px 34px;
        font-size: .83rem;
        width: 100%;
        outline: none;
        transition: border .2s;
        background: #fff;
    }

    .loc-search-wrap input:focus {
        border-color: var(--arg-blue);
    }

    .loc-search-wrap .search-ico {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--arg-muted);
        font-size: 14px;
        pointer-events: none;
    }

    /* Buttons */
    .btn-loc-primary {
        background: var(--arg-blue);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: .82rem;
        font-weight: 100;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background .18s, transform .1s;
    }

    .btn-loc-primary:hover {
        background: #2487e0;
        transform: translateY(-1px);
    }

    .btn-loc-teal {
        background: var(--arg-teal);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: .82rem;
        font-weight: 100;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background .18s, transform .1s;
    }

    .btn-loc-teal:hover {
        background: #27aa8d;
        transform: translateY(-1px);
    }

    /* Main table */
    .loc-table-wrap {
        background: #fff;
        border-radius: 12px;
        border: 1px solid var(--arg-border);
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(57, 154, 242, .07);
    }

    .loc-table {
        width: 100%;
        border-collapse: collapse;
    }

    .loc-table thead tr {
        background: var(--arg-bg);
        border-bottom: 2px solid var(--arg-border);
    }

    .loc-table thead th {
        padding: 11px 16px;
        font-size: .73rem;
        font-weight: 100;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: var(--arg-muted);
    }

    /* Marca row */
    .loc-marca-row {
        border-bottom: 1px solid var(--arg-border);
        cursor: pointer;
        transition: background .15s;
    }

    .loc-marca-row:hover {
        background: #f7faff;
    }

    .loc-marca-row.expanded {
        background: #f0f7ff;
        border-bottom: none;
    }

    .loc-marca-row td {
        padding: 13px 16px;
        vertical-align: middle;
    }

    .marca-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .marca-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--arg-blue);
        flex-shrink: 0;
    }

    .marca-name {
        font-weight: 400;
        font-size: .85rem;
        color: var(--arg-dark);
    }

    .marca-count {
        background: var(--arg-blue);
        color: #fff;
        border-radius: 20px;
        padding: 2px 9px;
        font-size: .72rem;
        font-weight: 400;
    }

    .chevron-ico {
        transition: transform .25s;
        color: var(--arg-muted);
        font-size: 13px;
    }

    .expanded .chevron-ico {
        transform: rotate(90deg);
    }

    /* Detail row */
    .loc-detail-row {
        display: none;
        background: #f8fbff;
    }

    .loc-detail-row.open {
        display: table-row;
    }

    .loc-detail-row td {
        padding: 0 !important;
        border-bottom: 2px solid var(--arg-blue) !important;
    }

    .detail-inner {
        padding: 0 16px 16px 36px;
        animation: fadeSlide .22s ease;
    }

    @keyframes fadeSlide {
        from {
            opacity: 0;
            transform: translateY(-6px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Sub table */
    .sub-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .sub-table thead tr {
        border-bottom: 1px solid #dde9f7;
    }

    .sub-table thead th {
        padding: 8px 12px;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #8899bb;
    }

    .sub-table tbody tr {
        border-bottom: 1px solid #eaf3fd;
        transition: background .12s;
    }

    .sub-table tbody tr:hover {
        background: #eef5ff;
    }

    .sub-table tbody td {
        padding: 9px 12px;
        font-size: .82rem;
        color: #556;
        font-weight: 400;
        vertical-align: middle;
    }

    /* Badges */
    .badge-activo {
        background: #e8f8f3;
        color: var(--arg-teal);
        border-radius: 20px;
        padding: 3px 10px;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .3px;
    }

    .badge-inactivo {
        background: #f0f0f0;
        color: #999;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .3px;
    }


    /* Edit icon */
    .btn-edit-suc {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--arg-yellow);
        font-size: 16px;
        padding: 3px 6px;
        border-radius: 6px;
        transition: background .15s;
    }

    .btn-edit-suc:hover {
        background: #fff8e6;
    }

    /* Empty state */
    .empty-subs {
        text-align: center;
        padding: 20px;
        color: var(--arg-muted);
        font-size: .82rem;
    }

    /* Modal overrides */
    .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 8px 40px rgba(0, 0, 0, .15);
    }

    .modal-header {
        border-bottom: 1px solid var(--arg-border);
        padding: 16px 20px;
    }

    .modal-title {
        font-weight: 700;
        font-size: .95rem;
        color: var(--arg-dark);
    }

    .modal-footer {
        border-top: 1px solid var(--arg-border);
    }

    /* Form validation */
    .field-error {
        border-color: var(--arg-red) !important;
    }

    .field-msg {
        font-size: .73rem;
        margin-top: 3px;
        display: none;
    }

    .field-msg.show {
        display: block;
    }

    .field-msg.err {
        color: var(--arg-red);
    }

    .field-msg.ok {
        color: var(--arg-teal);
    }

    /* No results */
    .loc-empty {
        text-align: center;
        padding: 48px 20px;
        color: var(--arg-muted);
    }

    .loc-empty i {
        font-size: 2.5rem;
        display: block;
        margin-bottom: 10px;
        opacity: .4;
    }
</style>

<div class="content">
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">Locales Comerciales</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i
                                        class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active">Locales</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <section class="page-content container-fluid">
        <div id="locales-wrap">

            <!-- Header -->
            <div class="loc-header">
                <div class="loc-header-left">
                    <h2>Marcas y Sucursales</h2>
                    <p>Haz clic en una marca para ver sus sucursales</p>
                </div>
                <div class="d-flex align-items-center" style="gap:10px; flex-wrap:wrap;">
                    <div class="loc-search-wrap">
                        <i class="icon dripicons-search search-ico"></i>
                        <input type="text" id="buscador_marcas" placeholder="Buscar marca...">
                    </div>
                    <button class="btn-loc-primary" id="btn_nueva_marca">
                        <i class="icon dripicons-plus"></i> Nueva Marca
                    </button>
                    <button class="btn-loc-teal" id="btn_nueva_sucursal">
                        <i class="icon dripicons-plus"></i> Nueva Sucursal
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="loc-table-wrap">
                <table class="loc-table" id="tabla_marcas">
                    <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Marca</th>
                            <th style="width:130px;">Sucursales</th>
                            <th style="width:100px; text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody_marcas">
                        <tr>
                            <td colspan="4" class="loc-empty"><i class="icon dripicons-loading icon-spin"></i>
                                Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </section>
</div>

<!-- ═══ MODAL MARCA ═══ -->
<div class="modal fade" id="modal_marca" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titulo_modal_marca">
                    <i class="icon dripicons-store" style="color:var(--arg-blue)"></i> Nueva Marca
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="marca_id">
                <div class="form-group mb-3">
                    <label style="font-size:.82rem; font-weight:600;">Nombre de la marca <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="marca_nombre" maxlength="200"
                        placeholder="Ej: Pizza Hut">
                    <div class="field-msg err" id="err_marca_nombre">Ingrese el nombre de la marca</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info btn-sm" id="btn_guardar_marca"
                    style="color:#fff; min-width:90px;">
                    <i class="icon dripicons-checkmark"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL SUCURSAL ═══ -->
<div class="modal fade" id="modal_sucursal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titulo_modal_suc">
                    <i class="icon dripicons-location" style="color:var(--arg-teal)"></i> Nueva Sucursal
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="suc_id">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label style="font-size:.82rem; font-weight:600;">Marca <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="suc_mar_id">
                                <option value="">— Seleccione marca —</option>
                            </select>
                            <div class="field-msg err" id="err_suc_marca">Seleccione una marca</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label style="font-size:.82rem; font-weight:600;">Nombre / Descripción <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="suc_nombre" maxlength="255"
                                placeholder="Ej: Quicentro Sur">
                            <div class="field-msg err" id="err_suc_nombre">Mínimo 3 caracteres</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label style="font-size:.82rem; font-weight:600;">Dirección <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="suc_direccion" maxlength="255"
                                placeholder="Ej: Av. Morán Valverde y Teniente Hugo Ortiz">
                            <div class="field-msg err" id="err_suc_direccion">Ingrese la dirección</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label style="font-size:.82rem; font-weight:600;">Provincia / Región <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="suc_provincia">
                                <option value="">— Seleccione —</option>
                                <option value="sierra">Sierra</option>
                                <option value="costa">Costa</option>
                                <option value="oriente">Oriente</option>
                                <option value="galapagos">Galápagos</option>
                            </select>
                            <div class="field-msg err" id="err_suc_provincia">Seleccione una región</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label style="font-size:.82rem; font-weight:600;">Estado</label>
                            <select class="form-control" id="suc_activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info btn-sm" id="btn_guardar_suc"
                    style="color:#fff; min-width:100px;">
                    <i class="icon dripicons-checkmark"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    var AJAX_URL = 'ajax/locales/locales.php';
    var marcasData = [];
    var expandedId = null;

    // ═══════════════════════════════════════════════
    // CARGA DE MARCAS
    // ═══════════════════════════════════════════════
    function cargarMarcas() {
        $.getJSON(AJAX_URL + '?action=list_marcas', function (r) {
            if (!r.success) return;
            marcasData = r.data;

            // Poblar selector de marcas en modal sucursal
            var opts = '<option value="">— Seleccione marca —</option>';
            r.data.forEach(function (m) {
                opts += '<option value="' + m.mar_id + '">' + m.mar_descripcion + '</option>';
            });
            $('#suc_mar_id').html(opts);

            renderMarcas(r.data);
        });
    }

    function renderMarcas(data) {
        var html = '';
        if (!data.length) {
            html = '<tr><td colspan="4"><div class="loc-empty"><i class="icon dripicons-store"></i>Sin marcas registradas</div></td></tr>';
        } else {
            data.forEach(function (m) {
                html +=
                    '<tr class="loc-marca-row" data-mar-id="' + m.mar_id + '" id="marca-row-' + m.mar_id + '">' +
                    '  <td><i class="icon dripicons-chevron-right chevron-ico"></i></td>' +
                    '  <td>' +
                    '    <div class="marca-pill">' +
                    '      <span class="marca-dot"></span>' +
                    '      <span class="marca-name">' + m.mar_descripcion + '</span>' +
                    '    </div>' +
                    '  </td>' +
                    '  <td><span class="marca-count">' + m.total_sucursales + ' sucursal' + (m.total_sucursales != 1 ? 'es' : '') + '</span></td>' +
                    '  <td class="text-center">' +
                    '    <a onclick="event.stopPropagation(); editarMarca(' + m.mar_id + ',\'' + m.mar_descripcion.replace(/'/g, "\\'") + '\')" style="color:var(--arg-yellow); font-size:17px; cursor:pointer;" title="Editar marca"><i class="icon dripicons-pencil"></i></a>' +
                    '  </td>' +
                    '</tr>' +
                    '<tr class="loc-detail-row" id="detail-row-' + m.mar_id + '">' +
                    '  <td colspan="4">' +
                    '    <div class="detail-inner" id="detail-inner-' + m.mar_id + '">' +
                    '      <div class="text-center py-3 text-muted"><i class="icon dripicons-loading icon-spin"></i> Cargando sucursales...</div>' +
                    '    </div>' +
                    '  </td>' +
                    '</tr>';
            });
        }
        $('#tbody_marcas').html(html);

        // Re-expand if there was one open
        if (expandedId) {
            $('#marca-row-' + expandedId).addClass('expanded');
            $('#detail-row-' + expandedId).addClass('open');
        }
    }

    // ═══════════════════════════════════════════════
    // EXPAND / COLLAPSE (acordeón exclusivo)
    // ═══════════════════════════════════════════════
    $(document).on('click', '.loc-marca-row', function () {
        var marId = $(this).data('mar-id');

        if (expandedId && expandedId !== marId) {
            $('#marca-row-' + expandedId).removeClass('expanded');
            $('#detail-row-' + expandedId).removeClass('open');
        }

        if (expandedId === marId) {
            $(this).removeClass('expanded');
            $('#detail-row-' + marId).removeClass('open');
            expandedId = null;
        } else {
            $(this).addClass('expanded');
            $('#detail-row-' + marId).addClass('open');
            expandedId = marId;
            cargarSucursales(marId);
        }
    });

    // ═══════════════════════════════════════════════
    // CARGA SUCURSALES DE UNA MARCA
    // ═══════════════════════════════════════════════
    function cargarSucursales(marId) {
        $.getJSON(AJAX_URL + '?action=list_sucursales&mar_id=' + marId, function (r) {
            var html = '';
            if (!r.success || !r.data.length) {
                html = '<div class="empty-subs"><i class="icon dripicons-location" style="font-size:1.5rem; opacity:.3; display:block; margin-bottom:6px;"></i>Sin sucursales. Usa "+ Nueva Sucursal" para agregar.</div>';
            } else {
                html = '<table class="sub-table">' +
                    '<thead><tr>' +
                    '<th>#</th><th>Nombre / Descripción</th><th>Dirección</th><th>Región</th><th>Estado</th><th style="text-align:center">Editar</th>' +
                    '</tr></thead><tbody>';

                r.data.forEach(function (s, i) {
                    var badge = s.loc_activo == 1
                        ? '<span class="badge-activo">● Activo</span>'
                        : '<span class="badge-inactivo">● Inactivo</span>';
                    var nombre = s.loc_nombre || '<em style="color:#aaa;">Sin descripción</em>';
                    var region = s.loc_provincia
                        ? s.loc_provincia.charAt(0).toUpperCase() + s.loc_provincia.slice(1)
                        : '—';
                    html += '<tr>' +
                        '<td style="color:var(--arg-muted); font-size:.75rem;">' + (i + 1) + '</td>' +
                        '<td>' + nombre + '</td>' +
                        '<td>' + (s.loc_direccion || '—') + '</td>' +
                        '<td>' + region + '</td>' +
                        '<td>' + badge + '</td>' +

                        '<td style="text-align:center"><button class="btn-edit-suc" onclick="editarSucursal(' + s.loc_id + ')" title="Editar"><i class="icon dripicons-pencil"></i></button></td>' +
                        '</tr>';
                });
                html += '</tbody></table>';
            }
            $('#detail-inner-' + marId).html(html);
        });
    }

    // ═══════════════════════════════════════════════
    // BUSCADOR
    // ═══════════════════════════════════════════════
    $('#buscador_marcas').on('input', function () {
        var q = $(this).val().toLowerCase().trim();
        if (!q) { renderMarcas(marcasData); return; }
        var filtered = marcasData.filter(function (m) {
            return m.mar_descripcion.toLowerCase().indexOf(q) >= 0;
        });
        renderMarcas(filtered);
    });

    // ═══════════════════════════════════════════════
    // MODAL MARCA
    // ═══════════════════════════════════════════════
    $('#btn_nueva_marca').on('click', function () {
        $('#marca_id').val('');
        $('#marca_nombre').val('').removeClass('field-error');
        $('#err_marca_nombre').removeClass('show');
        $('#titulo_modal_marca').html('<i class="icon dripicons-store" style="color:var(--arg-blue)"></i> Nueva Marca');
        $('#modal_marca').modal('show');
    });

    function editarMarca(id, nombre) {
        $('#marca_id').val(id);
        $('#marca_nombre').val(nombre).removeClass('field-error');
        $('#err_marca_nombre').removeClass('show');
        $('#titulo_modal_marca').html('<i class="icon dripicons-pencil" style="color:var(--arg-yellow)"></i> Editar Marca');
        $('#modal_marca').modal('show');
    }

    $('#btn_guardar_marca').on('click', function () {
        var nombre = $('#marca_nombre').val().trim();
        var valid = true;

        if (!nombre) {
            $('#marca_nombre').addClass('field-error');
            $('#err_marca_nombre').addClass('show');
            valid = false;
        } else {
            $('#marca_nombre').removeClass('field-error');
            $('#err_marca_nombre').removeClass('show');
        }
        if (!valid) return;

        var id = $('#marca_id').val();
        var btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: AJAX_URL, type: 'POST',
            data: { action: id ? 'editar_marca' : 'crear_marca', mar_id: id, mar_descripcion: nombre },
            dataType: 'json',
            success: function (r) {
                if (r.success) { $('#modal_marca').modal('hide'); cargarMarcas(); }
                else alert(r.mensaje);
            },
            error: function () { alert('Error de conexión'); },
            complete: function () { btn.prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar'); }
        });
    });

    // ═══════════════════════════════════════════════
    // MODAL SUCURSAL
    // ═══════════════════════════════════════════════
    function resetSucModal() {
        $('#suc_id').val('');
        $('#suc_mar_id, #suc_nombre, #suc_direccion, #suc_provincia').val('');
        $('#suc_activo').val('1');
        $('.field-error').removeClass('field-error');
        $('.field-msg').removeClass('show');
    }

    $('#btn_nueva_sucursal').on('click', function () {
        resetSucModal();
        $('#titulo_modal_suc').html('<i class="icon dripicons-location" style="color:var(--arg-teal)"></i> Nueva Sucursal');
        // Pre-select marca si hay una expandida
        if (expandedId) $('#suc_mar_id').val(expandedId);
        $('#modal_sucursal').modal('show');
    });

    function editarSucursal(loc_id) {
        $.getJSON(AJAX_URL + '?action=get_sucursal&loc_id=' + loc_id, function (r) {
            if (!r.success) { alert(r.mensaje); return; }
            resetSucModal();
            var s = r.data;
            $('#suc_id').val(s.loc_id);
            $('#suc_mar_id').val(s.mar_id);
            $('#suc_nombre').val(s.loc_nombre || '');
            $('#suc_direccion').val(s.loc_direccion || '');
            $('#suc_provincia').val(s.loc_provincia || '');
            $('#suc_activo').val(s.loc_activo);
            $('#titulo_modal_suc').html('<i class="icon dripicons-pencil" style="color:var(--arg-yellow)"></i> Editar Sucursal');
            $('#modal_sucursal').modal('show');
        });
    }

    $('#btn_guardar_suc').on('click', function () {
        var mar_id = $('#suc_mar_id').val();
        var nombre = $('#suc_nombre').val().trim();
        var direccion = $('#suc_direccion').val().trim();
        var provincia = $('#suc_provincia').val();
        var valid = true;

        // Validaciones front-end
        if (!mar_id) {
            $('#suc_mar_id').addClass('field-error'); $('#err_suc_marca').addClass('show'); valid = false;
        } else { $('#suc_mar_id').removeClass('field-error'); $('#err_suc_marca').removeClass('show'); }

        if (nombre.length < 3) {
            $('#suc_nombre').addClass('field-error'); $('#err_suc_nombre').addClass('show'); valid = false;
        } else { $('#suc_nombre').removeClass('field-error'); $('#err_suc_nombre').removeClass('show'); }

        if (!direccion) {
            $('#suc_direccion').addClass('field-error'); $('#err_suc_direccion').addClass('show'); valid = false;
        } else { $('#suc_direccion').removeClass('field-error'); $('#err_suc_direccion').removeClass('show'); }

        if (!provincia) {
            $('#suc_provincia').addClass('field-error'); $('#err_suc_provincia').addClass('show'); valid = false;
        } else { $('#suc_provincia').removeClass('field-error'); $('#err_suc_provincia').removeClass('show'); }

        if (!valid) return;

        var id = $('#suc_id').val();
        var btn = $(this).prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm"></span> Guardando...')
            .css('background', '#2aab8f');

        $.ajax({
            url: AJAX_URL, type: 'POST',
            data: {
                action: id ? 'editar_sucursal' : 'crear_sucursal',
                loc_id: id, mar_id: mar_id,
                loc_nombre: nombre, loc_direccion: direccion,
                loc_provincia: provincia, loc_activo: $('#suc_activo').val()
            },
            dataType: 'json',
            success: function (r) {
                if (r.success) {
                    $('#modal_sucursal').modal('hide');
                    cargarMarcas();
                    // Reabrir la marca correspondiente
                    setTimeout(function () {
                        expandedId = parseInt(mar_id);
                        $('#marca-row-' + mar_id).addClass('expanded');
                        $('#detail-row-' + mar_id).addClass('open');
                        cargarSucursales(mar_id);
                    }, 300);
                } else { alert(r.mensaje); }
            },
            error: function () { alert('Error de conexión'); },
            complete: function () {
                btn.prop('disabled', false)
                    .html('<i class="icon dripicons-checkmark"></i> Guardar')
                    .css('background', '');
            }
        });
    });

    // ── Limpiar errores on-the-fly al escribir ──
    $('#suc_nombre').on('input', function () {
        if ($(this).val().trim().length >= 3) { $(this).removeClass('field-error'); $('#err_suc_nombre').removeClass('show'); }
    });
    $('#suc_direccion').on('input', function () {
        if ($(this).val().trim()) { $(this).removeClass('field-error'); $('#err_suc_direccion').removeClass('show'); }
    });
    $('#suc_mar_id, #suc_provincia').on('change', function () {
        if ($(this).val()) { $(this).removeClass('field-error'); $(this).next('.field-msg').removeClass('show'); }
    });
    $('#marca_nombre').on('input', function () {
        if ($(this).val().trim()) { $(this).removeClass('field-error'); $('#err_marca_nombre').removeClass('show'); }
    });

    // ── Init ──
    cargarMarcas();
</script>