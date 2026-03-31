<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['id_user']) || $_SESSION['permisos_acceso'] !== 'Super Admin') {
    echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    case 'get':
        $q = "SELECT cfg_clave, cfg_valor, cfg_descripcion FROM configuracion ORDER BY cfg_clave ASC";
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

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
}
