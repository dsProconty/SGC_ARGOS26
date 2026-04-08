<?php
if (!isset($_SESSION['id_user']) || $_SESSION['permisos_acceso'] !== 'Super Admin') {
    echo "<meta http-equiv='refresh' content='0; url=index.php'>"; exit;
}

$modulos_categorias = [
    'General'         => ['dashboard' => 'Dashboard'],
    'Operaciones'     => ['gestiones' => 'Gestiones', 'pos' => 'Punto de Venta', 'venta_diferida' => 'Ventas Diferidas', 'convenios' => 'Convenios'],
    'Finanzas'        => ['giftcard' => 'Gift Cards', 'estado_cuenta' => 'Estados de Cuenta', 'portal_empresa' => 'Portal Empresa / Nómina'],
    'Administración'  => ['usuarios' => 'Gestión de Usuarios', 'perfiles' => 'Perfiles y Permisos', 'configuracion' => 'Configuración', 'locales' => 'Locales Comerciales', 'clientes' => 'Clientes', 'reportes' => 'Reportes'],
];
?>
<div class="content">
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">Perfiles y Permisos</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active">Perfiles</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <button class="btn btn-info" id="btn_nuevo_perfil" style="color:#fff;">
                        <i class="icon dripicons-plus"></i> Nuevo Perfil
                    </button>
                </div>
            </div>
        </div>
    </header>

    <section class="page-content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header">Perfiles de Acceso</h5>
                    <div class="card-body p-0">
                        <div id="tabla_perfiles_wrap" class="p-3">
                            <div class="text-center p-4"><span class="spinner-border"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- ══ MODAL CREAR / EDITAR PERFIL ══════════════════════════ -->
<div class="modal fade" id="modal_perfil" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_perfil_titulo"><i class="icon dripicons-user-group"></i> Nuevo Perfil</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="alerta_perfil"></div>
                <input type="hidden" id="per_id_edit">
                <div class="form-group">
                    <label>Nombre del perfil <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="per_nombre" maxlength="100" placeholder="Ej: Supervisor Regional">
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <input type="text" class="form-control" id="per_descripcion" maxlength="255" placeholder="Ej: Acceso a ventas y reportes de su región">
                </div>
                <hr>
                <label class="font-weight-bold mb-3"><i class="icon dripicons-lock-open"></i> Módulos habilitados</label>
                <small class="text-muted d-block mb-3">
                    <i class="icon dripicons-information"></i>
                    "Cambiar Contraseña" siempre está disponible para todos los perfiles.
                </small>
                <?php foreach ($modulos_categorias as $cat => $mods): ?>
                <div class="mb-3">
                    <p class="text-muted mb-1" style="font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px;"><?php echo $cat; ?></p>
                    <div class="row">
                        <?php foreach ($mods as $key => $label): ?>
                        <div class="col-md-4 col-sm-6 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input chk-modulo" id="mod_<?php echo $key; ?>" value="<?php echo $key; ?>">
                                <label class="custom-control-label" for="mod_<?php echo $key; ?>">
                                    <?php echo $label; ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btn_guardar_perfil" style="color:#fff;">
                    <i class="icon dripicons-checkmark"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL VER USUARIOS DEL PERFIL ════════════════════════ -->
<div class="modal fade" id="modal_usuarios_perfil" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="icon dripicons-user-group"></i> Usuarios con este perfil</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="usuarios_perfil_body">
                <div class="text-center"><span class="spinner-border spinner-border-sm"></span></div>
            </div>
        </div>
    </div>
</div>

<script>
var AJAX = 'ajax/perfiles/perfiles.php';

$(document).ready(function () {
    cargarPerfiles();

    // Nuevo perfil
    $('#btn_nuevo_perfil').on('click', function () {
        abrirModalPerfil(null);
    });

    // Guardar perfil
    $('#btn_guardar_perfil').on('click', function () {
        guardarPerfil();
    });

    // Limpiar al cerrar
    $('#modal_perfil').on('hidden.bs.modal', function () {
        $('#alerta_perfil').html('');
        $('#per_id_edit').val('');
        $('#per_nombre').val('');
        $('#per_descripcion').val('');
        $('.chk-modulo').prop('checked', false);
        $('#modal_perfil_titulo').html('<i class="icon dripicons-user-group"></i> Nuevo Perfil');
    });
});

// ── Cargar tabla de perfiles ─────────────────────────────────
function cargarPerfiles() {
    $('#tabla_perfiles_wrap').html('<div class="text-center p-4"><span class="spinner-border"></span></div>');
    $.ajax({
        url: AJAX + '?action=list', type: 'GET', dataType: 'json',
        success: function (r) {
            if (!r.success) { $('#tabla_perfiles_wrap').html('<div class="alert alert-danger">Error al cargar perfiles.</div>'); return; }
            renderTabla(r.data);
        }
    });
}

function renderTabla(perfiles) {
    if (!perfiles.length) {
        $('#tabla_perfiles_wrap').html('<p class="text-muted text-center py-4">No hay perfiles creados.</p>');
        return;
    }
    var html = '<div class="table-responsive"><table class="table table-hover table-bordered mb-0" id="table_perfiles"><thead class="thead-light"><tr>' +
        '<th>#</th><th>Perfil</th><th>Descripción</th><th class="text-center">Módulos</th>' +
        '<th class="text-center">Usuarios</th><th class="text-center">Estado</th><th class="text-center">Acciones</th>' +
        '</tr></thead><tbody>';

    perfiles.forEach(function (p) {
        var badge   = p.per_activo == 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-secondary">Inactivo</span>';
        var sistema = p.per_es_sistema == 1 ? '<span class="badge badge-light border ml-1" title="Perfil de sistema" style="font-size:.65rem;">SISTEMA</span>' : '';
        var btnDel  = p.per_es_sistema == 1 ? '' :
            '<button class="btn btn-sm btn-outline-danger ml-1" title="Eliminar" onclick="eliminarPerfil(' + p.per_id + ')"><i class="icon dripicons-trash"></i></button>';
        var toggleLbl = p.per_activo == 1 ? 'Desactivar' : 'Activar';
        var toggleIco = p.per_activo == 1 ? 'dripicons-cross' : 'dripicons-checkmark';

        html += '<tr>' +
            '<td>' + p.per_id + '</td>' +
            '<td><strong>' + esc(p.per_nombre) + '</strong>' + sistema + '</td>' +
            '<td><small class="text-muted">' + esc(p.per_descripcion || '—') + '</small></td>' +
            '<td class="text-center"><span class="badge badge-info">' + p.total_modulos + '</span></td>' +
            '<td class="text-center">' +
                '<a href="#" class="text-dark" onclick="verUsuariosPerfil(' + p.per_id + ', \'' + esc(p.per_nombre) + '\'); return false;">' +
                '<span class="badge badge-secondary">' + p.total_usuarios + '</span></a>' +
            '</td>' +
            '<td class="text-center">' + badge + '</td>' +
            '<td class="text-center">' +
                '<button class="btn btn-sm btn-warning mr-1" title="Editar" onclick="abrirModalPerfil(' + p.per_id + ')"><i class="icon dripicons-pencil"></i></button>' +
                '<button class="btn btn-sm btn-outline-secondary mr-1" title="' + toggleLbl + '" onclick="toggleActivo(' + p.per_id + ')"><i class="icon ' + toggleIco + '"></i></button>' +
                btnDel +
            '</td>' +
        '</tr>';
    });
    html += '</tbody></table></div>';
    $('#tabla_perfiles_wrap').html(html);
    if ($.fn.dataTable.isDataTable('#table_perfiles')) $('#table_perfiles').DataTable().destroy();
    $('#table_perfiles').dataTable({ pageLength: 25, order: [[0, 'asc']], language: { url: '' } });
}

// ── Abrir modal crear/editar ─────────────────────────────────
function abrirModalPerfil(per_id) {
    $('.chk-modulo').prop('checked', false);
    $('#per_id_edit').val('');
    $('#per_nombre').val('');
    $('#per_descripcion').val('');
    $('#alerta_perfil').html('');

    if (!per_id) {
        $('#modal_perfil_titulo').html('<i class="icon dripicons-plus"></i> Nuevo Perfil');
        $('#modal_perfil').modal('show');
        return;
    }

    $('#modal_perfil_titulo').html('<i class="icon dripicons-pencil"></i> Editar Perfil');
    $.ajax({
        url: AJAX + '?action=get&per_id=' + per_id, type: 'GET', dataType: 'json',
        success: function (r) {
            if (!r.success) { alert('Error al cargar perfil'); return; }
            $('#per_id_edit').val(r.perfil.per_id);
            $('#per_nombre').val(r.perfil.per_nombre);
            $('#per_descripcion').val(r.perfil.per_descripcion || '');
            r.modulos.forEach(function (m) { $('#mod_' + m).prop('checked', true); });
            $('#modal_perfil').modal('show');
        }
    });
}

// ── Guardar perfil ───────────────────────────────────────────
function guardarPerfil() {
    var nombre = $.trim($('#per_nombre').val());
    if (!nombre) {
        $('#alerta_perfil').html('<div class="alert alert-warning mb-2">El nombre del perfil es requerido.</div>');
        return;
    }

    var modulos = [];
    $('.chk-modulo:checked').each(function () { modulos.push($(this).val()); });

    var per_id = $('#per_id_edit').val();
    var action = per_id ? 'editar' : 'crear';
    var data   = { action: action, nombre: nombre, descripcion: $('#per_descripcion').val(), 'modulos[]': modulos };
    if (per_id) data.per_id = per_id;

    $('#btn_guardar_perfil').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');
    $('#alerta_perfil').html('');

    $.ajax({
        url: AJAX, type: 'POST', dataType: 'json', data: data,
        success: function (r) {
            if (r.success) {
                $('#modal_perfil').modal('hide');
                cargarPerfiles();
                mostrarToast('success', r.mensaje);
            } else {
                $('#alerta_perfil').html('<div class="alert alert-danger mb-2">' + r.mensaje + '</div>');
            }
        },
        error: function () { $('#alerta_perfil').html('<div class="alert alert-danger mb-2">Error de conexión.</div>'); },
        complete: function () { $('#btn_guardar_perfil').prop('disabled', false).html('<i class="icon dripicons-checkmark"></i> Guardar'); }
    });
}

// ── Toggle activo/inactivo ───────────────────────────────────
function toggleActivo(per_id) {
    $.ajax({
        url: AJAX, type: 'POST', dataType: 'json',
        data: { action: 'toggle_activo', per_id: per_id },
        success: function (r) {
            if (r.success) { cargarPerfiles(); mostrarToast('success', r.mensaje); }
            else { mostrarToast('error', r.mensaje); }
        }
    });
}

// ── Eliminar perfil ──────────────────────────────────────────
function eliminarPerfil(per_id) {
    if (!confirm('¿Eliminar este perfil? Esta acción no se puede deshacer.')) return;
    $.ajax({
        url: AJAX, type: 'POST', dataType: 'json',
        data: { action: 'eliminar', per_id: per_id },
        success: function (r) {
            if (r.success) { cargarPerfiles(); mostrarToast('success', r.mensaje); }
            else { mostrarToast('error', r.mensaje); }
        }
    });
}

// ── Ver usuarios del perfil ──────────────────────────────────
function verUsuariosPerfil(per_id, nombre) {
    $('#modal_usuarios_perfil .modal-title').html('<i class="icon dripicons-user-group"></i> Usuarios — ' + esc(nombre));
    $('#usuarios_perfil_body').html('<div class="text-center"><span class="spinner-border spinner-border-sm"></span></div>');
    $('#modal_usuarios_perfil').modal('show');

    $.ajax({
        url: 'ajax/users/users.php?action=list_by_perfil&per_id=' + per_id,
        type: 'GET', dataType: 'json',
        success: function (r) {
            if (!r.success || !r.data.length) {
                $('#usuarios_perfil_body').html('<p class="text-muted text-center py-3">Ningún usuario tiene este perfil asignado.</p>');
                return;
            }
            var html = '<ul class="list-group list-group-flush">';
            r.data.forEach(function (u) {
                html += '<li class="list-group-item d-flex align-items-center"><i class="icon dripicons-user mr-2 text-muted"></i>' +
                    '<div><strong>' + esc(u.name_user) + '</strong><br><small class="text-muted">' + esc(u.username) + '</small></div></li>';
            });
            $('#usuarios_perfil_body').html(html + '</ul>');
        }
    });
}

// ── Utilidades ───────────────────────────────────────────────
function esc(s) { return s ? $('<div>').text(s).html() : ''; }

function mostrarToast(tipo, msg) {
    var t = $('<div>').css({
        position:'fixed', bottom:'20px', right:'20px', zIndex:9999,
        background: tipo === 'success' ? '#28a745' : '#dc3545',
        color:'#fff', padding:'12px 20px', borderRadius:'8px',
        boxShadow:'0 4px 12px rgba(0,0,0,.2)', fontSize:'.9rem', maxWidth:'320px'
    }).text(msg);
    $('body').append(t);
    setTimeout(function () { t.fadeOut(400, function () { $(this).remove(); }); }, 4000);
}
</script>
