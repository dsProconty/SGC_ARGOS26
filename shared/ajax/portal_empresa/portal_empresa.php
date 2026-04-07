<?php
session_start();
require_once '../../config/database.php';

if (empty($_SESSION['id_user']) || $_SESSION['permisos_acceso'] !== 'empresa_cliente') {
    echo json_encode(['success' => false, 'mensaje' => 'Acceso no autorizado']);
    exit;
}

$cli_id = (int)($_SESSION['cli_id'] ?? 0);
if (!$cli_id) {
    echo json_encode(['success' => false, 'mensaje' => 'Usuario sin empresa asignada']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ----------------------------------------------------------
    // Resumen general de la empresa
    // ----------------------------------------------------------
    case 'resumen':
        $q = "SELECT
                COUNT(*) AS total_empleados,
                SUM(per_cupo_asignado)  AS total_asignado,
                SUM(per_cupo_disponible) AS total_disponible,
                SUM(per_cupo_asignado - per_cupo_disponible) AS total_consumido,
                SUM(CASE WHEN per_estado = 'activo' THEN 1 ELSE 0 END) AS activos
              FROM personal
              WHERE cli_id = $cli_id";
        $r = mysqli_fetch_assoc(mysqli_query($mysqli, $q));
        echo json_encode(['success' => true, 'data' => $r]);
        break;

    // ----------------------------------------------------------
    // Listado de nómina (empleados)
    // ----------------------------------------------------------
    case 'nomina':
        $buscar = isset($_GET['buscar']) ? mysqli_real_escape_string($mysqli, trim($_GET['buscar'])) : '';
        $where  = "p.cli_id = $cli_id";
        if ($buscar) {
            $where .= " AND (p.per_nombre LIKE '%$buscar%' OR p.per_documento LIKE '%$buscar%')";
        }

        $q = "SELECT p.per_id, p.per_nombre, p.per_documento, p.per_correo,
                     p.per_estado, p.per_cupo_asignado, p.per_cupo_disponible,
                     (p.per_cupo_asignado - p.per_cupo_disponible) AS consumido
              FROM personal p
              WHERE $where
              ORDER BY p.per_nombre ASC";
        $result = mysqli_query($mysqli, $q);
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ----------------------------------------------------------
    // Historial de consumos de los empleados de la empresa
    // ----------------------------------------------------------
    case 'historial':
        $desde  = isset($_GET['desde'])  ? mysqli_real_escape_string($mysqli, $_GET['desde'])  : date('Y-m-01');
        $hasta  = isset($_GET['hasta'])  ? mysqli_real_escape_string($mysqli, $_GET['hasta'])  : date('Y-m-d');
        $per_id = isset($_GET['per_id']) ? (int)$_GET['per_id'] : 0;

        $where = "c.con_fecha BETWEEN '$desde' AND '$hasta'
                  AND p.cli_id = $cli_id";
        if ($per_id) {
            $where .= " AND c.per_id = $per_id";
        }

        $q = "SELECT c.con_id, c.con_fecha, c.con_hora, p.per_nombre, p.per_documento,
                     c.con_descripcion, c.con_monto_convenio, c.con_monto_giftcard,
                     c.con_monto_externo, c.con_valor_total, c.con_estado,
                     l.loc_nombre
              FROM consumo c
              JOIN personal p  ON c.per_id = p.per_id
              LEFT JOIN local l ON c.loc_id = l.loc_id
              WHERE $where
              ORDER BY c.con_fecha DESC, c.con_hora DESC";
        $result = mysqli_query($mysqli, $q);
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ----------------------------------------------------------
    // Detalle de un empleado (para modal)
    // ----------------------------------------------------------
    case 'detalle_empleado':
        $per_id = (int)($_GET['per_id'] ?? 0);
        $q = "SELECT p.per_id, p.per_nombre, p.per_documento, p.per_correo,
                     p.per_estado, p.per_cupo_asignado, p.per_cupo_disponible
              FROM personal p
              WHERE p.per_id = $per_id AND p.cli_id = $cli_id LIMIT 1";
        $row = mysqli_fetch_assoc(mysqli_query($mysqli, $q));
        if (!$row) { echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']); exit; }

        // Últimos 5 consumos
        $qc = "SELECT con_fecha, con_descripcion, con_valor_total
               FROM consumo WHERE per_id = $per_id
               ORDER BY con_fecha DESC, con_hora DESC LIMIT 5";
        $rc = mysqli_query($mysqli, $qc);
        $consumos = [];
        while ($c = mysqli_fetch_assoc($rc)) { $consumos[] = $c; }

        echo json_encode(['success' => true, 'data' => $row, 'consumos' => $consumos]);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
}
