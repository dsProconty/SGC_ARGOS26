<?php
require_once "../../config/database.php";

$action = $_GET['action'];

switch ($action) {
    case 'list':
        $query = "SELECT * FROM usuario ORDER BY id_user DESC";

        $result = mysqli_query($mysqli, $query); ?>

        <table id="table_usuarios" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nombre de Usuario</th>
                    <th>Nombre</th>
                    <th>Permisos de acceso</th>
                    <th>Status</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_array($result)) { ?>
                    <tr>
                        <td><?php echo $no; ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td><?php echo $row['name_user']; ?></td>
                        <td><?php echo $row['permisos_acceso']; ?></td>
                        <td><?php echo $row['status']; ?></td>
                        <td>
                            <?php
                            if ($row['status'] == 'activo') { ?>
                                <a data-toggle="tooltip" data-placement="top" title="Bloqueado" class="btn btn-warning btn-md" onclick="bloquear_usuario(<?php echo $row['id_user'];?>)">
                                    <i style="color:#fff" class="icon dripicons-wrong"></i>
                                </a>
                            <?php
                            } else { ?>
                                <a data-toggle="tooltip" data-placement="top" title="activo" class="btn btn-success btn-md" onclick="desbloquear_usuario(<?php echo $row['id_user'];?>)">
                                    <i style="color:#fff" class="icon dripicons-checkmark"></i>
                                </a>
                            <?php
                            }
                            ?>

                            <a data-toggle='tooltip' data-placement='top' title='Modificar' class='btn btn-primary btn-md' href='?module=formulario&action=edit&id=<?php echo $row['id_user']?>'>
                                <i style='color:#fff' class='icon dripicons-document-edit'></i>
                            </a>
                        </td>
                    </tr>
                <?php
                    $no++;
                }
                ?>
            </tbody>
        </table>

<?php

        break;
    default:
        # code...
        break;
}

?>