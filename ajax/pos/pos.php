<?php
session_start();
require_once('../../config/database.php');

header('Content-Type: application/json');

if (empty($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ----------------------------------------------------------
    // Buscar empleado por cédula
    // ----------------------------------------------------------
    case 'buscar':
        $cedula = mysqli_real_escape_string($mysqli, trim($_GET['cedula'] ?? ''));
        if ($cedula === '') {
            echo json_encode(['success' => false, 'mensaje' => 'Ingrese una cédula']);
            break;
        }

        $query = "SELECT p.per_id, p.per_nombre, p.per_documento, p.per_estado,
                         p.per_cupo_asignado, p.per_cupo_disponible,
                         c.cli_id, c.cli_descripcion, c.cli_tipo_beneficio, c.cli_valor_beneficio
                  FROM personal p
                  JOIN cliente c ON p.cli_id = c.cli_id
                  WHERE p.per_documento = '$cedula'
                  LIMIT 1";

        $result = mysqli_query($mysqli, $query);

        if (!$result) {
            echo json_encode(['success' => false, 'mensaje' => 'Error de consulta: ' . mysqli_error($mysqli)]);
            break;
        }

        if (mysqli_num_rows($result) === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
            break;
        }

        $data = mysqli_fetch_assoc($result);

        if ($data['per_estado'] === 'bloqueado') {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado bloqueado – no puede realizar consumos']);
            break;
        }
        if ($data['per_estado'] === 'inactivo') {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado inactivo – no puede realizar consumos']);
            break;
        }

        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ----------------------------------------------------------
    // Registrar venta
    // ----------------------------------------------------------
    case 'registrar':
        $per_id         = (int)($_POST['per_id'] ?? 0);
        $monto_convenio = (float)($_POST['monto_convenio'] ?? 0);
        $monto_externo  = (float)($_POST['monto_externo'] ?? 0);
        $id_user        = (int)$_SESSION['id_user'];
        $loc_id         = isset($_SESSION['loc_id']) && $_SESSION['loc_id'] ? (int)$_SESSION['loc_id'] : null;

        if ($per_id === 0 || $monto_convenio <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        // Validar empleado y cupo actual
        $qEmp = "SELECT per_nombre, per_estado, per_cupo_disponible FROM personal WHERE per_id = $per_id";
        $rEmp = mysqli_query($mysqli, $qEmp);

        if (!$rEmp || mysqli_num_rows($rEmp) === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
            break;
        }

        $emp = mysqli_fetch_assoc($rEmp);

        if ($emp['per_estado'] !== 'activo') {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no activo']);
            break;
        }

        if ($monto_convenio > (float)$emp['per_cupo_disponible']) {
            echo json_encode(['success' => false, 'mensaje' => 'El monto supera el cupo disponible ($' . number_format($emp['per_cupo_disponible'], 2) . ')']);
            break;
        }

        $valor_total = $monto_convenio + $monto_externo;
        $fecha       = date('Y-m-d');
        $hora        = date('H:i:s');
        $loc_sql     = $loc_id ? $loc_id : 'NULL';

        $insert = "INSERT INTO consumo (con_fecha, con_hora, con_valor_neto, con_valor_total,
                                        con_estado, id_user, loc_id, per_id,
                                        con_monto_convenio, con_monto_externo, con_voucher_impreso)
                   VALUES ('$fecha', '$hora', '$monto_convenio', '$valor_total',
                           'pendiente', $id_user, $loc_sql, $per_id,
                           '$monto_convenio', '$monto_externo', 0)";

        if (!mysqli_query($mysqli, $insert)) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al registrar: ' . mysqli_error($mysqli)]);
            break;
        }

        $con_id = mysqli_insert_id($mysqli);

        // Descontar cupo
        mysqli_query($mysqli, "UPDATE personal SET per_cupo_disponible = per_cupo_disponible - $monto_convenio WHERE per_id = $per_id");

        echo json_encode(['success' => true, 'con_id' => $con_id]);
        break;

    // ----------------------------------------------------------
    // Datos del voucher
    // ----------------------------------------------------------
    case 'voucher':
        $con_id = (int)($_GET['con_id'] ?? 0);
        if ($con_id === 0) {
            echo json_encode(['success' => false]);
            break;
        }

        $query = "SELECT c.con_id, c.con_fecha, c.con_hora, c.con_valor_neto,
                         c.con_valor_total, c.con_monto_convenio, c.con_monto_externo,
                         p.per_nombre, p.per_documento,
                         cl.cli_descripcion,
                         u.name_user AS cajero,
                         l.loc_direccion
                  FROM consumo c
                  JOIN personal p  ON c.per_id   = p.per_id
                  JOIN cliente  cl ON p.cli_id   = cl.cli_id
                  LEFT JOIN usuario u ON c.id_user  = u.id_user
                  LEFT JOIN local   l ON c.loc_id   = l.loc_id
                  WHERE c.con_id = $con_id";

        $r = mysqli_query($mysqli, $query);

        if ($r && mysqli_num_rows($r) > 0) {
            $data = mysqli_fetch_assoc($r);
            // Marcar voucher como impreso
            mysqli_query($mysqli, "UPDATE consumo SET con_voucher_impreso = 1 WHERE con_id = $con_id");
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Consumo no encontrado']);
        }
        break;

    // ----------------------------------------------------------
    // Historial de ventas del día del cajero actual
    // ----------------------------------------------------------
    case 'historial':
        $id_user = (int)$_SESSION['id_user'];
        $fecha   = date('Y-m-d');

        $query = "SELECT c.con_id, c.con_fecha, c.con_hora, c.con_valor_total,
                         c.con_monto_convenio, c.con_monto_externo, c.con_voucher_impreso,
                         p.per_nombre, p.per_documento, cl.cli_descripcion
                  FROM consumo c
                  JOIN personal p  ON c.per_id = p.per_id
                  JOIN cliente  cl ON p.cli_id = cl.cli_id
                  WHERE c.id_user = $id_user AND c.con_fecha = '$fecha'
                  ORDER BY c.con_id DESC";

        $r    = mysqli_query($mysqli, $query);
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) {
            $rows[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ----------------------------------------------------------
    // Historial con filtro de fechas (pantalla aparte)
    // ----------------------------------------------------------
    case 'historial_filtro':
        $permisos    = $_SESSION['permisos_acceso'] ?? '';
        $esAdmin     = in_array($permisos, ['Super Admin', 'Administrador']);
        $id_user     = (int)$_SESSION['id_user'];

        $fecha_inicio = mysqli_real_escape_string($mysqli, $_GET['fecha_inicio'] ?? date('Y-m-d'));
        $fecha_fin    = mysqli_real_escape_string($mysqli, $_GET['fecha_fin']    ?? date('Y-m-d'));

        // Validar formato fechas
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
            echo json_encode(['success' => false, 'mensaje' => 'Fechas inválidas']);
            break;
        }

        $where = "c.con_fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";

        if ($esAdmin) {
            // Admin puede filtrar por local; sin filtro ve todos
            if (!empty($_GET['loc_id'])) {
                $loc_id = (int)$_GET['loc_id'];
                $where .= " AND c.loc_id = $loc_id";
            }
        } else {
            // Cajero solo ve sus propias ventas
            $where .= " AND c.id_user = $id_user";
        }

        $query = "SELECT c.con_id, c.con_fecha, c.con_hora, c.con_valor_total,
                         c.con_monto_convenio, c.con_monto_externo, c.con_voucher_impreso,
                         p.per_nombre, p.per_documento, cl.cli_descripcion
                  FROM consumo c
                  JOIN personal p  ON c.per_id = p.per_id
                  JOIN cliente  cl ON p.cli_id = cl.cli_id
                  WHERE $where
                  ORDER BY c.con_fecha DESC, c.con_id DESC";

        $r    = mysqli_query($mysqli, $query);
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) {
            $rows[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}
