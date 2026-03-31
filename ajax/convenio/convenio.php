<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

// Solo Admin puede gestionar convenios
$permisos = $_SESSION['permisos_acceso'] ?? '';
if (!in_array($permisos, ['Super Admin', 'Administrador'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ----------------------------------------------------------
    // Listar todos los convenios
    // ----------------------------------------------------------
    case 'list':
        $query = "SELECT cli_id, cli_descripcion, cli_ciudad, cli_contacto,
                         cli_email, cli_email2, cli_telefono, cli_dia_corte,
                         cli_tipo_beneficio, cli_valor_beneficio, cli_tipo_cartera, cli_comision
                  FROM cliente
                  ORDER BY cli_descripcion ASC";

        $r    = mysqli_query($mysqli, $query);
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) {
            $rows[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ----------------------------------------------------------
    // Obtener un convenio por ID (para editar)
    // ----------------------------------------------------------
    case 'get':
        $cli_id = (int)($_GET['cli_id'] ?? 0);
        if ($cli_id <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID inválido']);
            break;
        }

        $query = "SELECT cli_id, cli_descripcion, cli_ciudad, cli_contacto,
                         cli_email, cli_email2, cli_telefono, cli_dia_corte,
                         cli_tipo_beneficio, cli_valor_beneficio, cli_tipo_cartera, cli_comision
                  FROM cliente WHERE cli_id = $cli_id LIMIT 1";

        $r   = mysqli_query($mysqli, $query);
        $row = mysqli_fetch_assoc($r);

        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Convenio no encontrado']);
        }
        break;

    // ----------------------------------------------------------
    // Crear nuevo convenio
    // ----------------------------------------------------------
    case 'crear':
        $descripcion    = mysqli_real_escape_string($mysqli, trim($_POST['cli_descripcion']    ?? ''));
        $email          = mysqli_real_escape_string($mysqli, trim($_POST['cli_email']          ?? ''));
        $email2         = mysqli_real_escape_string($mysqli, trim($_POST['cli_email2']         ?? ''));
        $ciudad         = mysqli_real_escape_string($mysqli, trim($_POST['cli_ciudad']         ?? ''));
        $contacto       = mysqli_real_escape_string($mysqli, trim($_POST['cli_contacto']       ?? ''));
        $telefono       = mysqli_real_escape_string($mysqli, trim($_POST['cli_telefono']       ?? ''));
        $dia_corte      = mysqli_real_escape_string($mysqli, trim($_POST['cli_dia_corte']      ?? '0'));
        $tipo_beneficio  = mysqli_real_escape_string($mysqli, trim($_POST['cli_tipo_beneficio'] ?? ''));
        $valor_beneficio = (float)($_POST['cli_valor_beneficio'] ?? 0);
        $tipo_cartera    = mysqli_real_escape_string($mysqli, trim($_POST['cli_tipo_cartera']   ?? ''));
        $comision        = (float)($_POST['cli_comision'] ?? 0);

        if (!$descripcion || !$email || !$tipo_beneficio || !$tipo_cartera) {
            echo json_encode(['success' => false, 'mensaje' => 'Campos requeridos incompletos']);
            break;
        }

        $query = "INSERT INTO cliente (cli_descripcion, cli_email, cli_email2, cli_ciudad, cli_contacto,
                                       cli_telefono, cli_dia_corte, cli_tipo_beneficio, cli_valor_beneficio, cli_tipo_cartera, cli_comision)
                  VALUES ('$descripcion', '$email', '$email2', '$ciudad', '$contacto',
                          '$telefono', '$dia_corte', '$tipo_beneficio', $valor_beneficio, '$tipo_cartera', $comision)";

        if (mysqli_query($mysqli, $query)) {
            echo json_encode(['success' => true, 'cli_id' => mysqli_insert_id($mysqli)]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error al crear: ' . mysqli_error($mysqli)]);
        }
        break;

    // ----------------------------------------------------------
    // Editar convenio existente
    // ----------------------------------------------------------
    case 'editar':
        $cli_id         = (int)($_POST['cli_id'] ?? 0);
        $descripcion    = mysqli_real_escape_string($mysqli, trim($_POST['cli_descripcion']    ?? ''));
        $email          = mysqli_real_escape_string($mysqli, trim($_POST['cli_email']          ?? ''));
        $email2         = mysqli_real_escape_string($mysqli, trim($_POST['cli_email2']         ?? ''));
        $ciudad         = mysqli_real_escape_string($mysqli, trim($_POST['cli_ciudad']         ?? ''));
        $contacto       = mysqli_real_escape_string($mysqli, trim($_POST['cli_contacto']       ?? ''));
        $telefono       = mysqli_real_escape_string($mysqli, trim($_POST['cli_telefono']       ?? ''));
        $dia_corte      = mysqli_real_escape_string($mysqli, trim($_POST['cli_dia_corte']      ?? '0'));
        $tipo_beneficio  = mysqli_real_escape_string($mysqli, trim($_POST['cli_tipo_beneficio'] ?? ''));
        $valor_beneficio = (float)($_POST['cli_valor_beneficio'] ?? 0);
        $tipo_cartera    = mysqli_real_escape_string($mysqli, trim($_POST['cli_tipo_cartera']   ?? ''));
        $comision        = (float)($_POST['cli_comision'] ?? 0);

        if ($cli_id <= 0 || !$descripcion || !$email || !$tipo_beneficio || !$tipo_cartera) {
            echo json_encode(['success' => false, 'mensaje' => 'Campos requeridos incompletos']);
            break;
        }

        $query = "UPDATE cliente SET
                    cli_descripcion     = '$descripcion',
                    cli_email           = '$email',
                    cli_email2          = '$email2',
                    cli_ciudad          = '$ciudad',
                    cli_contacto        = '$contacto',
                    cli_telefono        = '$telefono',
                    cli_dia_corte       = '$dia_corte',
                    cli_tipo_beneficio  = '$tipo_beneficio',
                    cli_valor_beneficio = $valor_beneficio,
                    cli_tipo_cartera    = '$tipo_cartera',
                    cli_comision        = $comision
                  WHERE cli_id = $cli_id";

        if (mysqli_query($mysqli, $query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar: ' . mysqli_error($mysqli)]);
        }
        break;

    // ----------------------------------------------------------
    // Eliminar convenio
    // ----------------------------------------------------------
    case 'eliminar':
        $cli_id = (int)($_POST['cli_id'] ?? 0);
        if ($cli_id <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID inválido']);
            break;
        }

        // Verificar que no tenga personal asociado
        $chk = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) AS total FROM personal WHERE cli_id = $cli_id"));
        if ($chk['total'] > 0) {
            echo json_encode(['success' => false, 'mensaje' => 'No se puede eliminar: el convenio tiene ' . $chk['total'] . ' empleado(s) asociado(s)']);
            break;
        }

        if (mysqli_query($mysqli, "DELETE FROM cliente WHERE cli_id = $cli_id")) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error al eliminar: ' . mysqli_error($mysqli)]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}
