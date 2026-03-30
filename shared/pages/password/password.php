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

                if (empty($_GET['alert'])) {
                    echo "";
                } elseif ($_GET['alert'] == 1) {
                    echo "<div class='alert alert-danger alert-dismissable'>
                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                        <h4>  <i class='icon fa fa-times-circle'></i> Error!</h4>
                    Contraseña
                    </div>";
                            } elseif ($_GET['alert'] == 2) {
                                echo "<div class='alert alert-danger alert-dismissable'>
                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                        <h4>  <i class='icon fa fa-times-circle'></i> Error!</h4>
                        La nueva contraseña  ingresada no coinciden .
                    </div>";
                            } elseif ($_GET['alert'] == 3) {
                                echo "<div class='alert alert-success alert-dismissable'>
                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                        <h4>  <i class='icon fa fa-check-circle'></i> Exito!</h4>
                    Contraseña cambiada con éxito.
                    </div>";
                }
                ?>
                <div class="card">
                    <h5 class="card-header">Cambio de Contraseña</h5>
                    <div class="card-body">
                        <div class="col-lg-12 offset-lg-3">
                            <form role="form" class="form-horizontal" method="POST" action="pages/password/proses.php">
                                <div class="box-body">

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Contraseña Antigua</label>
                                        <div class="col-sm-5">
                                            <input type="password" class="form-control" name="old_pass" autocomplete="off" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Contraseña Nueva</label>
                                        <div class="col-sm-5">
                                            <input type="password" class="form-control" name="new_pass" autocomplete="off" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Repetir contraseña nueva</label>
                                        <div class="col-sm-5">
                                            <input type="password" class="form-control" name="retype_pass" autocomplete="off" required>
                                        </div>
                                    </div>
                                </div><!-- /.box-body -->

                                <div class="box-footer bg-btn-action">
                                    <div class="form-group">
                                        <div class="col-sm-offset-2 col-sm-10">
                                            <input type="submit" class="btn btn-primary btn-submit" name="Guardar" value="Guardar">
                                        </div>
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