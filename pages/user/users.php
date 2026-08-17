<div class="content" data-layout="tabbed">
    <!-- PAGE HEADER -->
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator"><?php echo strtoupper($_GET['module']); ?></h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Usuarios</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a class="btn btn-info" href="?module=formulario&action=new" data-toggle="tooltip" style="color:white;">Agregar Usuario</a>
                </div>
            </div>

        </div>
    </header>
    <section class="page-content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header">Lista de Usuarios</h5>
                    <div class="table-responsive">
                        <div class="card-body" id="loader_usuarios">



                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal: Reasignar Local (US-02) -->
    <div class="modal fade" id="modal_reasignar_local" tabindex="-1" role="dialog" aria-labelledby="modal_reasignar_local_label" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal_reasignar_local_label">Cambiar Local del Cajero</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="reasignar_alert" style="display:none;"></div>
                    <input type="hidden" id="reasignar_id_user" value="">
                    <div class="form-group">
                        <label for="reasignar_loc_id">Seleccionar Local</label>
                        <select class="form-control" id="reasignar_loc_id">
                            <option value="">— Seleccione local —</option>
                            <?php
                            $qLocModal = mysqli_query($mysqli, "SELECT l.loc_id, l.loc_nombre, l.loc_direccion, m.mar_descripcion FROM local l JOIN marca m ON l.mar_id = m.mar_id WHERE l.loc_activo = 1 ORDER BY m.mar_descripcion ASC, l.loc_nombre ASC");
                            while ($locM = mysqli_fetch_assoc($qLocModal)) {
                                echo '<option value="' . $locM['loc_id'] . '">' . htmlspecialchars($locM['mar_descripcion']) . ' — ' . htmlspecialchars($locM['loc_nombre'] ?: $locM['loc_direccion']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn_guardar_reasignar">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="js/users.js"></script>
<script>
function cambiarLocal(id_user, loc_id_actual) {
    $('#reasignar_id_user').val(id_user);
    $('#reasignar_loc_id').val(loc_id_actual || '');
    $('#reasignar_alert').hide().html('');
    $('#modal_reasignar_local').modal('show');
}

$('#btn_guardar_reasignar').on('click', function () {
    var id_user = $('#reasignar_id_user').val();
    var loc_id  = $('#reasignar_loc_id').val();
    if (!loc_id) {
        $('#reasignar_alert').removeClass('alert-success').addClass('alert alert-warning').html('Debe seleccionar un local.').show();
        return;
    }
    $.ajax({
        type: 'POST',
        url: 'ajax/users/users.php?action=reasignar_local',
        data: { id_user: id_user, loc_id: loc_id },
        dataType: 'json',
        success: function (res) {
            if (res.success) {
                $('#reasignar_alert').removeClass('alert-warning').addClass('alert alert-success').html(res.mensaje).show();
                setTimeout(function () {
                    $('#modal_reasignar_local').modal('hide');
                    load_usuarios();
                }, 1000);
            } else {
                $('#reasignar_alert').removeClass('alert-success').addClass('alert alert-danger').html(res.mensaje).show();
            }
        },
        error: function () {
            $('#reasignar_alert').removeClass('alert-success').addClass('alert alert-danger').html('Error de comunicación con el servidor.').show();
        }
    });
});
</script>