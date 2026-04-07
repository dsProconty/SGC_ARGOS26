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

        // FIX 3: Validate cupo does not exceed empresa max
        $empresa = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT cli_valor_beneficio FROM cliente WHERE cli_id = $cli_id LIMIT 1"));
        $cupo_max = (float)($empresa['cli_valor_beneficio'] ?? 0);
        if ($cupo_max > 0 && $cupo > $cupo_max) {
            echo json_encode(['success' => false, 'mensaje' => 'El cupo del empleado ($' . number_format($cupo, 2) . ') no puede ser mayor al cupo asignado a la empresa ($' . number_format($cupo_max, 2) . ')']);
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


    // ----------------------------------------------------------
    // Editar empleado
    // ----------------------------------------------------------
    case 'editar_empleado':
        $per_id    = (int)($_POST['per_id'] ?? 0);
        $nombre    = mysqli_real_escape_string($mysqli, trim($_POST['per_nombre']    ?? ''));
        $documento = mysqli_real_escape_string($mysqli, trim($_POST['per_documento'] ?? ''));
        $correo    = mysqli_real_escape_string($mysqli, trim($_POST['per_correo']    ?? ''));
        $cupo      = (float)($_POST['per_cupo'] ?? 0);

        if (!$per_id || !$nombre || !$documento || $cupo <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        // Validate belongs to this empresa
        $emp_check = mysqli_fetch_assoc(mysqli_query($mysqli,
            "SELECT per_id, per_nombre, per_documento, per_correo, per_cupo_asignado, per_cupo_disponible
             FROM personal WHERE per_id = $per_id AND cli_id = $cli_id LIMIT 1"));
        if (!$emp_check) {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
            break;
        }

        // FIX 3: Validate cupo max
        $empresa = mysqli_fetch_assoc(mysqli_query($mysqli,
            "SELECT cli_valor_beneficio FROM cliente WHERE cli_id = $cli_id LIMIT 1"));
        $cupo_max = (float)($empresa['cli_valor_beneficio'] ?? 0);
        if ($cupo_max > 0 && $cupo > $cupo_max) {
            echo json_encode(['success' => false,
                'mensaje' => 'El cupo ($' . number_format($cupo, 2) . ') no puede ser mayor al cupo de la empresa ($' . number_format($cupo_max, 2) . ')']);
            break;
        }

        // Detect and record changes for traceability
        $id_user_sesion = (int)$_SESSION['id_user'];
        $cambios = [];

        if ($emp_check['per_nombre'] !== $nombre) {
            $cambios[] = ['campo' => 'per_nombre', 'label' => 'Nombre', 'anterior' => $emp_check['per_nombre'], 'nuevo' => $nombre];
        }
        if ($emp_check['per_documento'] !== $documento) {
            $cambios[] = ['campo' => 'per_documento', 'label' => 'Cédula', 'anterior' => $emp_check['per_documento'], 'nuevo' => $documento];
        }
        if ($emp_check['per_correo'] !== $correo) {
            $cambios[] = ['campo' => 'per_correo', 'label' => 'Correo', 'anterior' => $emp_check['per_correo'], 'nuevo' => $correo];
        }
        $cupo_anterior = (float)$emp_check['per_cupo_asignado'];
        if (abs($cupo_anterior - $cupo) > 0.001) {
            $label_cupo = $cupo > $cupo_anterior ? 'Aumento de cupo' : 'Disminución de cupo';
            $cambios[] = ['campo' => 'per_cupo_asignado', 'label' => $label_cupo,
                'anterior' => '$' . number_format($cupo_anterior, 2), 'nuevo' => '$' . number_format($cupo, 2)];
        }

        // Adjust per_cupo_disponible proportionally if cupo changed
        $cupo_disponible_nuevo = $emp_check['per_cupo_disponible'];
        if (abs($cupo_anterior - $cupo) > 0.001) {
            $consumido = $cupo_anterior - (float)$emp_check['per_cupo_disponible'];
            $cupo_disponible_nuevo = max(0, $cupo - $consumido);
        }

        $correo_sql = $correo ? "'$correo'" : 'NULL';
        $q_update = "UPDATE personal SET
                        per_nombre = '$nombre',
                        per_documento = '$documento',
                        per_correo = $correo_sql,
                        per_cupo_asignado = $cupo,
                        per_cupo_disponible = $cupo_disponible_nuevo
                     WHERE per_id = $per_id AND cli_id = $cli_id";

        if (!mysqli_query($mysqli, $q_update)) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar: ' . mysqli_error($mysqli)]);
            break;
        }

        // Insert traceability records
        foreach ($cambios as $c) {
            $campo    = mysqli_real_escape_string($mysqli, $c['campo']);
            $label    = mysqli_real_escape_string($mysqli, $c['label']);
            $anterior = mysqli_real_escape_string($mysqli, $c['anterior'] ?? '');
            $nuevo    = mysqli_real_escape_string($mysqli, $c['nuevo'] ?? '');
            mysqli_query($mysqli, "INSERT INTO personal_trazabilidad
                (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                VALUES ($per_id, $id_user_sesion, '$campo', '$label', '$anterior', '$nuevo')");
        }

        echo json_encode(['success' => true, 'cambios' => count($cambios)]);
        break;

    // ----------------------------------------------------------
    // Cambiar estado empleado (suspender / activar)
    // ----------------------------------------------------------
    case 'cambiar_estado':
        $per_id      = (int)($_POST['per_id']    ?? 0);
        $nuevo_estado = mysqli_real_escape_string($mysqli, trim($_POST['per_estado'] ?? ''));

        if (!$per_id || !in_array($nuevo_estado, ['activo', 'suspendido', 'inactivo'])) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
            break;
        }

        $emp_check = mysqli_fetch_assoc(mysqli_query($mysqli,
            "SELECT per_id, per_estado FROM personal WHERE per_id = $per_id AND cli_id = $cli_id LIMIT 1"));
        if (!$emp_check) {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
            break;
        }

        $id_user_sesion = (int)$_SESSION['id_user'];
        $estado_anterior = $emp_check['per_estado'];

        mysqli_query($mysqli, "UPDATE personal SET per_estado = '$nuevo_estado' WHERE per_id = $per_id");

        // Record in traceability
        $label = $nuevo_estado === 'suspendido' ? 'Suspensión de tarjeta' : 'Activación de tarjeta';
        $ant_esc = mysqli_real_escape_string($mysqli, $estado_anterior);
        $nvo_esc = mysqli_real_escape_string($mysqli, $nuevo_estado);
        mysqli_query($mysqli, "INSERT INTO personal_trazabilidad
            (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
            VALUES ($per_id, $id_user_sesion, 'per_estado', '$label', '$ant_esc', '$nvo_esc')");

        echo json_encode(['success' => true]);
        break;

    // ----------------------------------------------------------
    // Trazabilidad de un empleado
    // ----------------------------------------------------------
    case 'trazabilidad':
        $per_id = (int)($_GET['per_id'] ?? 0);
        if (!$per_id) { echo json_encode(['success' => false, 'data' => []]); break; }

        // Verify belongs to empresa
        $chk = mysqli_fetch_assoc(mysqli_query($mysqli,
            "SELECT per_id FROM personal WHERE per_id = $per_id AND cli_id = $cli_id LIMIT 1"));
        if (!$chk) { echo json_encode(['success' => false, 'mensaje' => 'No autorizado']); break; }

        $q = "SELECT t.tra_id, t.tra_campo, t.tra_campo_label, t.tra_valor_anterior, t.tra_valor_nuevo,
                     DATE_FORMAT(t.tra_fecha, '%d/%m/%Y %H:%i') AS tra_fecha,
                     u.name_user
              FROM personal_trazabilidad t
              JOIN usuario u ON t.id_user = u.id_user
              WHERE t.per_id = $per_id
              ORDER BY t.tra_fecha DESC
              LIMIT 50";
        $res = mysqli_query($mysqli, $q);
        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
}
