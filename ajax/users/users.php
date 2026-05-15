<?php
if (session_status() === PHP_SESSION_NONE) session_start();
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
                    'super admin'     => 'danger',
                    'supervisor'      => 'warning',
                    'operador'        => 'info',
                    'cajero'          => 'primary',
                    'empresa_cliente' => 'success',
                    'administrador'   => 'danger',
                ];
                while ($row = mysqli_fetch_array($result)) {
                    $rol      = $row['permisos_acceso'];
                    $rolLower = strtolower($rol);
                    $color    = $badges[$rolLower] ?? 'secondary';
                    $label    = $rolLower === 'empresa_cliente' ? 'Empresa Cliente' : ($rolLower === 'cajero' ? 'Cajero' : $rol);

                    if ($rolLower === 'empresa_cliente' && $row['cli_descripcion']) {
                        $asignacion = '<small><i class="icon dripicons-briefcase"></i> ' . htmlspecialchars($row['cli_descripcion']) . '</small>';
                    } elseif ($rolLower === 'cajero' && $row['loc_direccion']) {
                        $asignacion = '<small><i class="icon dripicons-location"></i> ' . htmlspecialchars($row['loc_direccion']) . '</small>';
                    } elseif ($rolLower === 'cajero') {
                        $asignacion = '<small class="text-muted">Sin local asignado</small>';
                    } else {
                        $asignacion = '<span class="text-muted">—</span>';
                    }
                    ?>
                    <tr>
                        <td><?php echo $no; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($row['name_user']); ?>
                            <?php if ($rolLower === 'cajero'): ?>
                                <br><small class="text-muted">Cédula: <?php echo htmlspecialchars($row['username']); ?></small>
                            <?php endif; ?>
                        </td>
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
                            <a class="btn btn-info btn-sm mr-1" href="?module=formulario&action=edit&id=<?php echo $row['id_user']; ?>" title="Editar" style="color:#fff;">
                                <i class="icon dripicons-document-edit"></i>
                            </a>
                            <?php if ($rolLower === 'cajero'): ?>
                                <a class="btn btn-secondary btn-sm" onclick="cambiarLocal(<?php echo $row['id_user']; ?>, <?php echo (int)$row['loc_id']; ?>)" title="Cambiar Local" style="color:#fff;">
                                    <i class="icon dripicons-location"></i>
                                </a>
                            <?php endif; ?>
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

    // ── REASIGNAR LOCAL — US-02: cambio rápido de local para cajeros ──
    case 'reasignar_local':
        header('Content-Type: application/json');
        $id_user = (int)($_POST['id_user'] ?? 0);
        $loc_id  = (int)($_POST['loc_id']  ?? 0);

        if (!$id_user || !$loc_id) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        // Validar que el usuario existe
        $chkUser = $mysqli->prepare("SELECT id_user FROM usuario WHERE id_user = ?");
        $chkUser->bind_param('i', $id_user);
        $chkUser->execute();
        if (!$chkUser->get_result()->num_rows) {
            echo json_encode(['success' => false, 'mensaje' => 'Usuario no encontrado']);
            break;
        }

        // Validar que el local existe y está activo
        $chkLoc = $mysqli->prepare("SELECT loc_id FROM local WHERE loc_id = ? AND loc_activo = 1");
        $chkLoc->bind_param('i', $loc_id);
        $chkLoc->execute();
        if (!$chkLoc->get_result()->num_rows) {
            echo json_encode(['success' => false, 'mensaje' => 'Local no encontrado o inactivo']);
            break;
        }

        $stmt = $mysqli->prepare("UPDATE usuario SET loc_id = ? WHERE id_user = ?");
        $stmt->bind_param('ii', $loc_id, $id_user);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'mensaje' => 'Local actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar: ' . $mysqli->error]);
        }
        break;

    default:
        # code...
        break;
}

?>