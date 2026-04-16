<?php
require_once "../../config/database.php";
$rol        = $_SESSION['permisos_acceso'] ?? '';
$es_admin   = in_array($rol, ['Super Admin', 'Supervisor']);

// Cargar lista de usuarios para admins
$usuarios = [];
if ($es_admin) {
    $res = mysqli_query($mysqli, "SELECT id_user, name_user, username FROM usuario WHERE status='activo' ORDER BY name_user ASC");
    while ($row = mysqli_fetch_assoc($res)) $usuarios[] = $row;
}
?>
<div class="content" data-layout="tabbed">
    <!-- PAGE HEADER -->
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator">Cambiar Contraseña</h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Usuarios</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </header>
    <section class="page-content container-fluid">
        <div class="row">
            <div class="col-12">
                <?php
                if (!empty($_GET['alert'])) {
                    if ($_GET['alert'] == 1) {
                        echo "<div class='alert alert-danger alert-dismissable'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                            <h4><i class='icon fa fa-times-circle'></i> Error</h4>
                            La contraseña antigua es incorrecta.
                        </div>";
                    } elseif ($_GET['alert'] == 2) {
                        echo "<div class='alert alert-danger alert-dismissable'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                            <h4><i class='icon fa fa-times-circle'></i> Error</h4>
                            Las nuevas contraseñas no coinciden.
                        </div>";
                    } elseif ($_GET['alert'] == 3) {
                        echo "<div class='alert alert-success alert-dismissable'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                            <h4><i class='icon fa fa-check-circle'></i> Éxito</h4>
                            Contraseña cambiada correctamente.
                        </div>";
                    }
                }
                ?>
                <div class="card">
                    <h5 class="card-header">
                        <?php echo $es_admin ? 'Restablecer Contraseña de Usuario' : 'Cambio de Contraseña'; ?>
                    </h5>
                    <div class="card-body">
                        <div class="col-lg-8 offset-lg-2">
                            <form role="form" class="form-horizontal" method="POST" action="pages/password/proses.php">

                                <?php if ($es_admin): ?>
                                    <!-- Admins pueden seleccionar cualquier usuario -->
                                    <div class="alert alert-info">
                                        <i class="icon dripicons-information"></i>
                                        Como administrador puedes restablecer la contraseña de cualquier usuario sin conocer la contraseña anterior.
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label">Usuario a modificar <span class="text-danger">*</span></label>
                                        <div class="col-sm-6">
                                            <select class="form-control" name="target_user_id" required>
                                                <option value="">— Seleccione usuario —</option>
                                                <?php foreach ($usuarios as $u): ?>
                                                    <option value="<?php echo $u['id_user']; ?>">
                                                        <?php echo htmlspecialchars($u['name_user'] . ' (' . $u['username'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Usuarios normales deben ingresar contraseña antigua -->
                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label">Contraseña Antigua <span class="text-danger">*</span></label>
                                        <div class="col-sm-6">
                                            <input type="password" class="form-control" name="old_pass" autocomplete="off" required>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Contraseña Nueva <span class="text-danger">*</span></label>
                                    <div class="col-sm-6">
                                        <input type="password" class="form-control" name="new_pass" autocomplete="off" required>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Repetir contraseña nueva <span class="text-danger">*</span></label>
                                    <div class="col-sm-6">
                                        <input type="password" class="form-control" name="retype_pass" autocomplete="off" required>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-6 offset-sm-4">
                                        <input type="submit" class="btn btn-primary btn-submit" name="Guardar" value="Guardar">
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
