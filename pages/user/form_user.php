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
            </div>
        </div>
    </header>
    <section class="page-content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header"><?php if ($_GET['action'] == 'new') {
                                                echo "Ingreso de nuevo usuario";
                                            } else {
                                                echo "Modificación de usuario";
                                            } ?></h5>
                    <div class="card-body">
                        <?php
                        if ($_GET['action'] == 'new') { ?>
                            <div class="col-lg-12 offset-lg-3">
                                <form role="form" class="form-horizontal" method="POST" action="pages/user/proses.php?act=insert" enctype="multipart/form-data">
                                    <div class="box-body">

                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Nombre de usuario</label>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control" name="username" autocomplete="off" required>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Contraseña</label>
                                            <div class="col-sm-5">
                                                <input type="password" class="form-control" name="password" autocomplete="off" required>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Nombre</label>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control" name="name_user" autocomplete="off" required>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Permisos de acceso</label>
                                            <div class="col-sm-5">
                                                <select class="form-control" name="permisos_acceso" required>
                                                    <option value=""></option>
                                                    <option value="Super Admin">Super Admin</option>
                                                    <option value="Supervisor">Supervisor</option>
                                                    <option value="Operador">Operador</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div><!-- /.box body -->

                                    <div class="box-footer">
                                        <div class="form-group">
                                            <div class="col-sm-offset-2 col-sm-10">
                                                <input type="submit" class="btn btn-primary btn-submit" name="Guardar" value="Guardar">
                                                <a href="?module=usuarios" class="btn btn-default btn-reset">Cancelar</a>
                                            </div>
                                        </div>
                                    </div><!-- /.box footer -->
                                </form>
                            </div>

                        <?php
                        } else {
                            if (isset($_GET['id'])) {

                                $query = mysqli_query($mysqli, "SELECT * FROM usuario WHERE id_user='$_GET[id]'")
                                    or die('error: ' . mysqli_error($mysqli));
                                $data  = mysqli_fetch_assoc($query);
                            } ?>
                            <div class="col-lg-12 offset-lg-3">
                                <form role="form" class="form-horizontal" method="POST" action="pages/user/proses.php?act=update" enctype="multipart/form-data">
                                    <div class="box-body">

                                        <input type="hidden" name="id_user" value="<?php echo $data['id_user']; ?>">

                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Nombre de Usuario</label>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control" name="username" autocomplete="off" value="<?php echo $data['username']; ?>" required>
                                            </div>
                                        </div>



                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Nombre</label>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control" name="name_user" autocomplete="off" value="<?php echo $data['name_user']; ?>" required>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Email</label>
                                            <div class="col-sm-5">
                                                <input type="email" class="form-control" name="email" autocomplete="off" value="<?php echo $data['email']; ?>">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Telefono</label>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control" name="telefono" autocomplete="off" maxlength="13" onKeyPress="return goodchars(event,'0123456789',this)" value="<?php echo $data['telefono']; ?>">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Foto</label>
                                            <div class="col-sm-5">
                                                <input type="file" name="foto">
                                                <br />
                                                <?php
                                                if ($data['foto'] == "") { ?>
                                                    <img style="border:1px solid #eaeaea;border-radius:5px;" src="images/user/user-default.png" width="128">
                                                <?php
                                                } else { ?>
                                                    <img style="border:1px solid #eaeaea;border-radius:5px;" src="images/user/<?php echo $data['foto']; ?>" width="128">
                                                <?php
                                                }
                                                ?>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Permisos de acceso</label>
                                            <div class="col-sm-5">
                                                <select class="form-control" name="permisos_acceso" required>
                                                    <option value="<?php echo $data['permisos_acceso']; ?>"><?php echo $data['permisos_acceso']; ?></option>
                                                    <option value="Super Admin">Super Admin</option>
                                                    <option value="Supervisor">Supervisor</option>
                                                    <option value="CallCenter">Call Center</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div><!-- /.box body -->

                                    <div class="box-footer">
                                        <div class="form-group">
                                            <div class="col-sm-offset-2 col-sm-10">
                                                <input type="submit" class="btn btn-primary btn-submit" name="Guardar" value="Guardar">
                                                <a href="?module=usuarios" class="btn btn-default btn-reset">Cancelar</a>
                                            </div>
                                        </div>
                                    </div><!-- /.box footer -->
                                </form>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<script src="js\users.js"></script>