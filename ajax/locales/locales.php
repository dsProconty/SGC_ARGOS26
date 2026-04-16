<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(0);

// Capturar errores fatales y responder siempre con JSON válido (HTTP 200)
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        http_response_code(200);
        header('Content-Type: application/json');
        error_log('[locales fatal] ' . $e['message'] . ' in ' . $e['file'] . ':' . $e['line']);
        echo json_encode(['success' => false, 'mensaje' => 'Error interno del servidor (fatal). Revise los logs de PHP.']);
    }
    ob_end_flush();
});

session_start();
require_once '../../config/database.php';
mysqli_report(MYSQLI_REPORT_OFF); // PHP 8.1 lanza excepciones por defecto — lo desactivamos para manejar errores con if(!$result)
header('Content-Type: application/json');

if (empty($_SESSION['id_user']) || $_SESSION['permisos_acceso'] !== 'Super Admin') {
    echo json_encode(['success' => false, 'mensaje' => 'Acceso no autorizado']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ----------------------------------------------------------
    case 'list_marcas':
        $q = "SELECT m.mar_id, m.mar_descripcion,
                     COUNT(l.loc_id) AS total_sucursales
              FROM marca m
              LEFT JOIN local l ON l.mar_id = m.mar_id
              GROUP BY m.mar_id
              ORDER BY m.mar_descripcion ASC";
        $r = mysqli_query($mysqli, $q);
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ----------------------------------------------------------
    case 'crear_marca':
        $nombre = mysqli_real_escape_string($mysqli, trim($_POST['mar_descripcion'] ?? ''));
        if (!$nombre) { ob_clean(); echo json_encode(['success' => false, 'mensaje' => 'Nombre requerido']); break; }
        $res_chk = mysqli_query($mysqli, "SELECT mar_id FROM marca WHERE mar_descripcion = '$nombre' LIMIT 1");
        if (!$res_chk) { ob_clean(); echo json_encode(['success' => false, 'mensaje' => 'Error BD: ' . mysqli_error($mysqli)]); break; }
        $chk = mysqli_fetch_assoc($res_chk);
        if ($chk) { ob_clean(); echo json_encode(['success' => false, 'mensaje' => 'Ya existe una marca con ese nombre']); break; }
        $ins = mysqli_query($mysqli, "INSERT INTO marca (mar_descripcion) VALUES ('$nombre')");
        ob_clean();
        if (!$ins) {
            error_log('[locales crear_marca] INSERT failed: ' . mysqli_error($mysqli));
            // Mensaje seguro sin incluir el string de MySQL (puede tener Latin-1 que rompe json_encode en PHP 8)
            echo json_encode(['success' => false, 'mensaje' => 'No se pudo guardar la marca. Verifique que no existan conflictos en la base de datos o ejecute la migración fix_autoincrement.']);
            break;
        }
        echo json_encode(['success' => true, 'mar_id' => (int)mysqli_insert_id($mysqli)]);
        break;

    // ----------------------------------------------------------
    case 'editar_marca':
        $mar_id = (int)($_POST['mar_id'] ?? 0);
        $nombre = mysqli_real_escape_string($mysqli, trim($_POST['mar_descripcion'] ?? ''));
        if (!$mar_id || !$nombre) { echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']); break; }
        mysqli_query($mysqli, "UPDATE marca SET mar_descripcion = '$nombre' WHERE mar_id = $mar_id");
        echo json_encode(['success' => true]);
        break;

    // ----------------------------------------------------------
    case 'list_sucursales':
        $mar_id = (int)($_GET['mar_id'] ?? 0);
        $where  = $mar_id ? "WHERE l.mar_id = $mar_id" : '';
        $q = "SELECT l.loc_id, l.loc_nombre, l.loc_direccion, l.loc_provincia, l.loc_activo,
                     m.mar_id, m.mar_descripcion
              FROM local l
              JOIN marca m ON l.mar_id = m.mar_id
              $where
              ORDER BY m.mar_descripcion ASC, l.loc_nombre ASC";
        $r = mysqli_query($mysqli, $q);
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ----------------------------------------------------------
    case 'get_sucursal':
        $loc_id = (int)($_GET['loc_id'] ?? 0);
        $row = mysqli_fetch_assoc(mysqli_query($mysqli,
            "SELECT loc_id, loc_nombre, loc_direccion, loc_provincia, loc_activo, mar_id
             FROM local WHERE loc_id = $loc_id LIMIT 1"));
        if (!$row) { echo json_encode(['success' => false, 'mensaje' => 'No encontrado']); break; }
        echo json_encode(['success' => true, 'data' => $row]);
        break;

    // ----------------------------------------------------------
    case 'crear_sucursal':
        $mar_id    = (int)($_POST['mar_id'] ?? 0);
        $nombre    = mysqli_real_escape_string($mysqli, trim($_POST['loc_nombre']    ?? ''));
        $direccion = mysqli_real_escape_string($mysqli, trim($_POST['loc_direccion'] ?? ''));
        $provincia = mysqli_real_escape_string($mysqli, trim($_POST['loc_provincia'] ?? ''));
        $activo    = (int)($_POST['loc_activo'] ?? 1);

        if (!$mar_id || !$nombre || !$direccion || !$provincia) {
            echo json_encode(['success' => false, 'mensaje' => 'Todos los campos son requeridos']);
            break;
        }
        $q = "INSERT INTO local (mar_id, loc_nombre, loc_direccion, loc_provincia, loc_activo)
              VALUES ($mar_id, '$nombre', '$direccion', '$provincia', $activo)";
        if (mysqli_query($mysqli, $q)) {
            echo json_encode(['success' => true, 'loc_id' => mysqli_insert_id($mysqli)]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error: ' . mysqli_error($mysqli)]);
        }
        break;

    // ----------------------------------------------------------
    case 'editar_sucursal':
        $loc_id    = (int)($_POST['loc_id'] ?? 0);
        $mar_id    = (int)($_POST['mar_id'] ?? 0);
        $nombre    = mysqli_real_escape_string($mysqli, trim($_POST['loc_nombre']    ?? ''));
        $direccion = mysqli_real_escape_string($mysqli, trim($_POST['loc_direccion'] ?? ''));
        $provincia = mysqli_real_escape_string($mysqli, trim($_POST['loc_provincia'] ?? ''));
        $activo    = (int)($_POST['loc_activo'] ?? 1);

        if (!$loc_id || !$mar_id || !$nombre || !$direccion || !$provincia) {
            echo json_encode(['success' => false, 'mensaje' => 'Todos los campos son requeridos']);
            break;
        }
        $q = "UPDATE local SET
                mar_id        = $mar_id,
                loc_nombre    = '$nombre',
                loc_direccion = '$direccion',
                loc_provincia = '$provincia',
                loc_activo    = $activo
              WHERE loc_id = $loc_id";
        if (mysqli_query($mysqli, $q)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error: ' . mysqli_error($mysqli)]);
        }
        break;

    default:
        ob_clean();
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
}
