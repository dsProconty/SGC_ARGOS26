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
require_once '../../helpers/db_compat.php';
require_once '../../helpers/session_helpers.php';
mysqli_report(MYSQLI_REPORT_OFF); // PHP 8.1 lanza excepciones por defecto — lo desactivamos para manejar errores con if(!$result)
header('Content-Type: application/json');

if (empty($_SESSION['id_user']) || !esSuperAdmin($mysqli)) {
    echo json_encode(['success' => false, 'mensaje' => 'Acceso no autorizado']);
    exit;
}

require_once '../../helpers/locales_helpers.php';
localesAsegurarEsquema($mysqli);

// Comisión de Argos por marca (%): acepta coma o punto, 0 a 100, 2 decimales. false si no es válida.
function marcaComisionValida($valor) {
    $v = str_replace(',', '.', trim((string)$valor));
    if ($v === '' || !is_numeric($v)) {
        return false;
    }
    $v = round((float)$v, 2);
    if ($v < 0 || $v > 100) {
        return false;
    }
    return number_format($v, 2, '.', '');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ----------------------------------------------------------
    case 'list_marcas':
        $q = "SELECT m.mar_id, m.mar_descripcion, m.mar_comision,
                     COUNT(l.loc_id) AS total_sucursales,
                     COALESCE(SUM(l.loc_activo = 0), 0) AS total_inactivas
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
        $comision = marcaComisionValida($_POST['mar_comision'] ?? '');
        if ($comision === false) { ob_clean(); echo json_encode(['success' => false, 'mensaje' => 'Ingrese la comisión de Argos para esta marca: un porcentaje entre 0 y 100 (ej: 12.5)']); break; }
        $res_chk = mysqli_query($mysqli, "SELECT mar_id FROM marca WHERE mar_descripcion = '$nombre' LIMIT 1");
        if (!$res_chk) { ob_clean(); echo json_encode(['success' => false, 'mensaje' => 'Error BD: ' . mysqli_error($mysqli)]); break; }
        $chk = mysqli_fetch_assoc($res_chk);
        if ($chk) { ob_clean(); echo json_encode(['success' => false, 'mensaje' => 'Ya existe una marca con ese nombre']); break; }
        $ins = mysqli_query($mysqli, "INSERT INTO marca (mar_descripcion, mar_comision) VALUES ('$nombre', $comision)");
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
        $comision = marcaComisionValida($_POST['mar_comision'] ?? '');
        if ($comision === false) { echo json_encode(['success' => false, 'mensaje' => 'Ingrese la comisión de Argos para esta marca: un porcentaje entre 0 y 100 (ej: 12.5)']); break; }
        $dup = dbFila($mysqli, "SELECT mar_id FROM marca WHERE mar_descripcion = '$nombre' AND mar_id <> $mar_id LIMIT 1");
        if ($dup) { echo json_encode(['success' => false, 'mensaje' => 'Ya existe otra marca con ese nombre']); break; }
        mysqli_query($mysqli, "UPDATE marca SET mar_descripcion = '$nombre', mar_comision = $comision WHERE mar_id = $mar_id");
        echo json_encode(['success' => true]);
        break;

    // ----------------------------------------------------------
    case 'list_sucursales':
        $mar_id = (int)($_GET['mar_id'] ?? 0);
        $where  = $mar_id ? "WHERE l.mar_id = $mar_id" : '';
        $q = "SELECT l.loc_id, l.loc_nombre, l.loc_direccion, l.loc_provincia, l.loc_activo,
                     l.loc_motivo_cierre, l.loc_fecha_cierre,
                     (SELECT COUNT(*) FROM usuario u WHERE u.loc_id = l.loc_id AND u.status = 'activo') AS usuarios_activos,
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
        $row = dbFila($mysqli,
            "SELECT loc_id, loc_nombre, loc_direccion, loc_provincia, loc_activo, loc_motivo_cierre, loc_fecha_cierre, mar_id
             FROM local WHERE loc_id = $loc_id LIMIT 1");
        if (!$row) { echo json_encode(['success' => false, 'mensaje' => 'No encontrado']); break; }
        $row['historial'] = [];
        $rh = mysqli_query($mysqli,
            "SELECT t.tra_accion, t.tra_motivo, t.tra_fecha_cierre, t.tra_fecha, u.username
             FROM local_trazabilidad t LEFT JOIN usuario u ON u.id_user = t.id_user
             WHERE t.loc_id = $loc_id ORDER BY t.tra_id DESC LIMIT 10");
        while ($rh && ($h = mysqli_fetch_assoc($rh))) $row['historial'][] = $h;
        echo json_encode(['success' => true, 'data' => $row]);
        break;

    // ----------------------------------------------------------
    // Antes de inactivar: cuántos usuarios activos tienen asignado cada local elegido (solo informativo).
    case 'resumen_cierre':
        $ids = array_filter(array_map('intval', explode(',', (string)($_POST['ids'] ?? ''))));
        $porLocal = localesUsuariosActivos($mysqli, $ids);
        echo json_encode(['success' => true, 'locales' => count($ids), 'usuarios' => array_sum($porLocal)]);
        break;

    // ----------------------------------------------------------
    // Inactivar / reactivar uno o varios locales a la vez (motivo y fecha de cierre comunes al inactivar).
    case 'cambiar_estado_locales':
        $ids    = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)($_POST['ids'] ?? ''))))));
        $activo = (int)($_POST['activo'] ?? 1) === 1 ? 1 : 0;
        $motivo = $_POST['motivo'] ?? '';
        $fecha  = $_POST['fecha_cierre'] ?? '';
        if (!$ids) { echo json_encode(['success' => false, 'mensaje' => 'Seleccione al menos un local']); break; }
        if (count($ids) > 500) { echo json_encode(['success' => false, 'mensaje' => 'Demasiados locales en una sola operación']); break; }
        $err = localesValidarCierre($activo, $motivo, $fecha);
        if ($err !== '') { echo json_encode(['success' => false, 'mensaje' => $err]); break; }
        $cambiados = 0;
        foreach ($ids as $lid) {
            if (localesAplicarEstado($mysqli, $lid, $activo, $motivo, $fecha, (int)$_SESSION['id_user'])) $cambiados++;
        }
        echo json_encode(['success' => true, 'cambiados' => $cambiados, 'sin_cambio' => count($ids) - $cambiados]);
        break;

    // ----------------------------------------------------------
    case 'crear_sucursal':
        $mar_id    = (int)($_POST['mar_id'] ?? 0);
        $nombre    = mysqli_real_escape_string($mysqli, trim($_POST['loc_nombre']    ?? ''));
        $direccion = mysqli_real_escape_string($mysqli, trim($_POST['loc_direccion'] ?? ''));
        $provincia = mysqli_real_escape_string($mysqli, trim($_POST['loc_provincia'] ?? ''));
        $activo    = (int)($_POST['loc_activo'] ?? 1) === 0 ? 0 : 1;
        $motivo    = $_POST['loc_motivo_cierre'] ?? '';
        $fecha     = $_POST['loc_fecha_cierre'] ?? '';

        if (!$mar_id || !$nombre || !$direccion || !$provincia) {
            echo json_encode(['success' => false, 'mensaje' => 'Todos los campos son requeridos']);
            break;
        }
        $err = localesValidarCierre($activo, $motivo, $fecha);
        if ($err !== '') { echo json_encode(['success' => false, 'mensaje' => $err]); break; }
        // Se crea activa y, si corresponde, se inactiva con el mismo camino que usa el resto (deja historial).
        $q = "INSERT INTO local (mar_id, loc_nombre, loc_direccion, loc_provincia, loc_activo)
              VALUES ($mar_id, '$nombre', '$direccion', '$provincia', 1)";
        if (mysqli_query($mysqli, $q)) {
            $nuevo = (int)mysqli_insert_id($mysqli);
            if ($activo === 0) localesAplicarEstado($mysqli, $nuevo, 0, $motivo, $fecha, (int)$_SESSION['id_user']);
            echo json_encode(['success' => true, 'loc_id' => $nuevo]);
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
        $activo    = (int)($_POST['loc_activo'] ?? 1) === 0 ? 0 : 1;
        $motivo    = $_POST['loc_motivo_cierre'] ?? '';
        $fecha     = $_POST['loc_fecha_cierre'] ?? '';

        if (!$loc_id || !$mar_id || !$nombre || !$direccion || !$provincia) {
            echo json_encode(['success' => false, 'mensaje' => 'Todos los campos son requeridos']);
            break;
        }
        // Un local que ya estaba inactivo desde antes de existir motivo/fecha puede seguir editándose sin inventarlos.
        $actual = dbFila($mysqli, "SELECT loc_activo FROM local WHERE loc_id = $loc_id LIMIT 1");
        $legadoInactivo = $actual && (int)$actual['loc_activo'] === 0 && $activo === 0 && trim((string)$motivo) === '';
        if (!$legadoInactivo) {
            $err = localesValidarCierre($activo, $motivo, $fecha);
            if ($err !== '') { echo json_encode(['success' => false, 'mensaje' => $err]); break; }
        }
        // El estado se cambia aparte (localesAplicarEstado) para que quede en el historial.
        $q = "UPDATE local SET
                mar_id        = $mar_id,
                loc_nombre    = '$nombre',
                loc_direccion = '$direccion',
                loc_provincia = '$provincia'
              WHERE loc_id = $loc_id";
        if (mysqli_query($mysqli, $q)) {
            if (!$legadoInactivo) localesAplicarEstado($mysqli, $loc_id, $activo, $motivo, $fecha, (int)$_SESSION['id_user']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error: ' . mysqli_error($mysqli)]);
        }
        break;

    default:
        ob_clean();
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
}
