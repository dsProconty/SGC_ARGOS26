<?php
require_once "../../config/database.php";

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $query = "SELECT u.*, c.cli_descripcion, l.loc_direccion,
                         COALESCE(p.per_nombre, u.permisos_acceso) AS perfil_nombre
                  FROM usuario u
                  LEFT JOIN cliente c ON u.cli_id = c.cli_id
                  LEFT JOIN local   l ON u.loc_id  = l.loc_id
                  LEFT JOIN perfil  p ON p.per_id  = u.per_id
                  ORDER BY u.id_user DESC";

        $result = mysqli_query($mysqli, $query); ?>

        <table id="table_usuarios" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Perfil</th>
                    <th>Asignación</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $badges = [
                    'Super Admin'     => 'danger',
                    'Supervisor'      => 'warning',
                    'Operador'        => 'info',
                    'cajero'          => 'primary',
                    'empresa_cliente' => 'success',
                ];
                while ($row = mysqli_fetch_array($result)) {
                    $rol    = $row['permisos_acceso'];
                    $color  = $badges[$rol] ?? 'secondary';
                    $label  = $rol === 'empresa_cliente' ? 'Empresa Cliente' : ($rol === 'cajero' ? 'Cajero' : $rol);

                    if ($rol === 'empresa_cliente' && $row['cli_descripcion']) {
                        $asignacion = '<small><i class="icon dripicons-briefcase"></i> ' . htmlspecialchars($row['cli_descripcion']) . '</small>';
                    } elseif ($rol === 'cajero' && $row['loc_direccion']) {
                        $asignacion = '<small><i class="icon dripicons-location"></i> ' . htmlspecialchars($row['loc_direccion']) . '</small>';
                    } else {
                        $asignacion = '<span class="text-muted">—</span>';
                    }
                    ?>
                    <tr>
                        <td><?php echo $no; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['name_user']); ?></td>
                        <td><span class="badge badge-<?php echo $color; ?>"><?php echo htmlspecialchars($row['perfil_nombre'] ?? $label); ?></span></td>
                        <td><?php echo $asignacion; ?></td>
                        <td>
                            <?php if ($row['status'] === 'activo'): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Bloqueado</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if ($row['status'] === 'activo'): ?>
                                <a class="btn btn-danger btn-sm mr-1" onclick="bloquear_usuario(<?php echo $row['id_user']; ?>)" title="Bloquear" style="color:#fff;">
                                    <i class="dripicons-wrong"></i>
                                </a>
                            <?php else: ?>
                                <a class="btn btn-warning btn-sm mr-1" onclick="desbloquear_usuario(<?php echo $row['id_user']; ?>)" title="Activar" style="color:#212529;">
                                    <i class="dripicons-checkmark"></i>
                                </a>
                            <?php endif; ?>
                            <a class="btn btn-info btn-sm" href="?module=formulario&action=edit&id=<?php echo $row['id_user']; ?>" title="Editar" style="color:#fff;">
                                <i class="icon dripicons-document-edit"></i>
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
    // ── LIST BY PERFIL — usuarios de un perfil (usado en perfiles/view.php) ──
    case 'list_by_perfil':
        header('Content-Type: application/json');
        $per_id = (int)($_GET['per_id'] ?? 0);
        if (!$per_id) {
            echo json_encode(['success' => false, 'mensaje' => 'per_id requerido']);
            break;
        }
        $stmt = $mysqli->prepare("SELECT id_user, name_user, username FROM usuario WHERE per_id = ? ORDER BY name_user ASC");
        $stmt->bind_param('i', $per_id);
        $stmt->execute();
        $res  = $stmt->get_result();
        $data = [];
        while ($row = $res->fetch_assoc()) $data[] = $row;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    default:
        # code...
        break;
}

?>