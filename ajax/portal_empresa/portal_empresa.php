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

        $q = "SELECT p.per_id, p.per_nombre, p.per_documento, p.per_numero_tarjeta, p.per_correo,
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
                  AND (p.cli_id = $cli_id OR p.cli_id IS NULL)
                  AND c.per_id IN (SELECT per_id FROM personal WHERE cli_id = $cli_id)";
        if ($per_id) {
            $where .= " AND c.per_id = $per_id";
        }

        $q = "SELECT c.con_id, c.con_fecha, c.con_hora, p.per_nombre, p.per_documento,
                     c.con_descripcion, c.con_monto_convenio, c.con_monto_giftcard,
                     c.con_monto_externo, c.con_valor_total, c.con_estado,
                     l.loc_direccion AS loc_nombre
              FROM consumo c
              LEFT JOIN personal p  ON c.per_id = p.per_id
              LEFT JOIN local    l  ON c.loc_id  = l.loc_id
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
        $q = "SELECT p.per_id, p.per_nombre, p.per_documento, p.per_numero_tarjeta, p.per_correo,
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

    // ----------------------------------------------------------
    // Cupo del convenio (para pre-llenar el formulario)
    // ----------------------------------------------------------
    case 'cupo_convenio':
        $q = "SELECT cli_valor_beneficio FROM cliente WHERE cli_id = $cli_id LIMIT 1";
        $r = mysqli_fetch_assoc(mysqli_query($mysqli, $q));
        echo json_encode(['success' => true, 'cupo' => (float)($r['cli_valor_beneficio'] ?? 0)]);
        break;

    // ----------------------------------------------------------
    // Crear nuevo empleado
    // ----------------------------------------------------------
    case 'crear_empleado':
        $nombre    = mysqli_real_escape_string($mysqli, trim($_POST['per_nombre']    ?? ''));
        $documento = mysqli_real_escape_string($mysqli, trim($_POST['per_documento'] ?? ''));
        $correo    = mysqli_real_escape_string($mysqli, trim($_POST['per_correo']    ?? ''));
        $cupo      = (float)($_POST['per_cupo'] ?? 0);

        if (!$nombre || !$documento || $cupo <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        // Verificar que la cédula no exista ya
        $chk = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT per_id FROM personal WHERE per_documento = '$documento' LIMIT 1"));
        if ($chk) {
            echo json_encode(['success' => false, 'mensaje' => 'Ya existe un empleado con esa cédula']);
            break;
        }

        $correo_sql = $correo ? "'$correo'" : 'NULL';
        // Generar número de tarjeta único de 16 dígitos
        $num_tarjeta = str_pad(mt_rand(1000, 9999), 4, '0') .
                       str_pad(mt_rand(1000, 9999), 4, '0') .
                       str_pad(mt_rand(1000, 9999), 4, '0') .
                       str_pad(mt_rand(1000, 9999), 4, '0');
        $q = "INSERT INTO personal (per_nombre, per_documento, per_numero_tarjeta, per_correo, cli_id, per_estado, per_cupo_asignado, per_cupo_disponible)
              VALUES ('$nombre', '$documento', '$num_tarjeta', $correo_sql, $cli_id, 'activo', $cupo, $cupo)";

        if (mysqli_query($mysqli, $q)) {
            echo json_encode(['success' => true, 'per_id' => mysqli_insert_id($mysqli)]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error al guardar: ' . mysqli_error($mysqli)]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
}
