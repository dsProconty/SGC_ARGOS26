<?php
session_start();
require_once '../../config/database.php';
require_once '../../helpers/session_helpers.php';
require_once '../../services/cartera_service.php';

header('Content-Type: application/json');

if (empty($_SESSION['id_user']) || !esSuperAdmin($mysqli)) {
    echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    case 'get':
        // cartera_ultima_corrida es una marca interna (se muestra en su propia tarjeta), no un parámetro editable
        $q = "SELECT cfg_clave, cfg_valor, cfg_descripcion FROM configuracion WHERE cfg_clave <> 'cartera_ultima_corrida' ORDER BY cfg_clave ASC";
        $r = mysqli_query($mysqli, $q);
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'save':
        $clave = mysqli_real_escape_string($mysqli, trim($_POST['cfg_clave']  ?? ''));
        $valor = mysqli_real_escape_string($mysqli, trim($_POST['cfg_valor']  ?? ''));

        if ($clave === '' || $valor === '') {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        $q = "UPDATE configuracion SET cfg_valor = '$valor' WHERE cfg_clave = '$clave'";
        if (mysqli_query($mysqli, $q) && mysqli_affected_rows($mysqli) > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'No se encontró el parámetro']);
        }
        break;

    // ── Cartera de Cobranza: última actualización ─────────────────────────────────────
    case 'cartera_estado':
        $r = mysqli_query($mysqli, "SELECT cfg_valor FROM configuracion WHERE cfg_clave = 'cartera_ultima_corrida'");
        $row = $r ? mysqli_fetch_assoc($r) : null;
        echo json_encode(['success' => true, 'ultima' => $row ? $row['cfg_valor'] : '']);
        break;

    // ── Cartera de Cobranza: forzar la actualización (emergencia / el cron no corrió) ──
    // Solo Super Admin (ya validado arriba). Es la misma rutina del cron: segura de repetir, no pisa el estado
    // de gestión de las carteras del ciclo en curso.
    case 'actualizar_cartera':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
            break;
        }
        $res = cartera_actualizar($mysqli, 'manual');
        if ($res === null) {
            echo json_encode(['success' => false, 'mensaje' => 'Ya hay una actualización de cartera en curso (el cron o alguien más). Espera un momento y vuelve a intentar.']);
            break;
        }
        $r = mysqli_query($mysqli, "SELECT cfg_valor FROM configuracion WHERE cfg_clave = 'cartera_ultima_corrida'");
        $row = $r ? mysqli_fetch_assoc($r) : null;
        echo json_encode(['success' => true, 'resumen' => $res, 'ultima' => $row ? $row['cfg_valor'] : '']);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
}
