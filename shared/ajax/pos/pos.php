<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Leer IVA configurado
$cfgRow = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT cfg_valor FROM configuracion WHERE cfg_clave = 'iva_porcentaje' LIMIT 1"));
$IVA_PCT = $cfgRow ? (float)$cfgRow['cfg_valor'] : 15.0;

function calcularIva(float $total, float $pct): array {
    $subtotal = round($total / (1 + $pct / 100), 2);
    $iva      = round($total - $subtotal, 2);
    return ['subtotal' => $subtotal, 'iva' => $iva];
}

switch ($action) {

    // ----------------------------------------------------------
    // Configuración (IVA)
    // ----------------------------------------------------------
    case 'get_config':
        echo json_encode(['success' => true, 'iva_porcentaje' => $IVA_PCT]);
        break;

    // ----------------------------------------------------------
    // Buscar por cédula O código Gift Card
    // ----------------------------------------------------------
    case 'buscar':
        $input = mysqli_real_escape_string($mysqli, strtoupper(trim($_GET['cedula'] ?? '')));
        if ($input === '') {
            echo json_encode(['success' => false, 'mensaje' => 'Ingrese una cédula o código Gift Card']);
            break;
        }

        // --- Intentar como cédula (solo dígitos) ---
        if (preg_match('/^\d+$/', $input)) {
            $query = "SELECT p.per_id, p.per_nombre, p.per_documento, p.per_estado,
                             p.per_cupo_asignado, p.per_cupo_disponible,
                             c.cli_id, c.cli_descripcion, c.cli_tipo_beneficio, c.cli_valor_beneficio
                      FROM personal p
                      JOIN cliente c ON p.cli_id = c.cli_id
                      WHERE p.per_documento = '$input'
                      LIMIT 1";
            $result = mysqli_query($mysqli, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $data = mysqli_fetch_assoc($result);
                if ($data['per_estado'] === 'bloqueado') {
                    echo json_encode(['success' => false, 'mensaje' => 'Empleado bloqueado – no puede realizar consumos']);
                    break;
                }
                if ($data['per_estado'] === 'inactivo') {
                    echo json_encode(['success' => false, 'mensaje' => 'Empleado inactivo – no puede realizar consumos']);
                    break;
                }
                echo json_encode(['success' => true, 'tipo' => 'empleado', 'data' => $data]);
                break;
            }
        }

        // --- Intentar como código Gift Card ---
        $qGC = "SELECT cgc_id, cgc_codigo, cgc_cupo_disponible, cgc_estado, cgc_fecha_caducidad
                FROM codigo_gift_card WHERE cgc_codigo = '$input' LIMIT 1";
        $rGC = mysqli_query($mysqli, $qGC);

        if ($rGC && mysqli_num_rows($rGC) > 0) {
            $gc = mysqli_fetch_assoc($rGC);

            // Verificar caducidad
            if ($gc['cgc_fecha_caducidad'] && $gc['cgc_fecha_caducidad'] < date('Y-m-d')) {
                mysqli_query($mysqli, "UPDATE codigo_gift_card SET cgc_estado='vencido' WHERE cgc_id={$gc['cgc_id']}");
                echo json_encode([
                    'success' => false,
                    'tipo'    => 'giftcard_vencida',
                    'mensaje' => 'Gift Card vencida el ' . date('d/m/Y', strtotime($gc['cgc_fecha_caducidad']))
                ]);
                break;
            }

            if ($gc['cgc_estado'] === 'consumido') {
                echo json_encode(['success' => false, 'tipo' => 'giftcard_consumida', 'mensaje' => 'Gift Card ya fue consumida en su totalidad']);
                break;
            }

            if ($gc['cgc_estado'] !== 'activo') {
                echo json_encode(['success' => false, 'tipo' => 'giftcard_invalida', 'mensaje' => 'Gift Card no disponible (estado: ' . $gc['cgc_estado'] . ')']);
                break;
            }

            echo json_encode([
                'success' => true,
                'tipo'    => 'giftcard',
                'data'    => [
                    'cgc_id'          => (int)$gc['cgc_id'],
                    'cgc_codigo'      => $gc['cgc_codigo'],
                    'saldo'           => (float)$gc['cgc_cupo_disponible'],
                    'fecha_caducidad' => $gc['cgc_fecha_caducidad']
                        ? date('d/m/Y', strtotime($gc['cgc_fecha_caducidad']))
                        : 'Sin caducidad'
                ]
            ]);
            break;
        }

        echo json_encode(['success' => false, 'mensaje' => 'Cédula o código Gift Card no encontrado']);
        break;

    // ----------------------------------------------------------
    // Registrar consumo solo con Gift Card (sin empleado)
    // ----------------------------------------------------------
    case 'registrar_giftcard':
        $cgc_id         = (int)($_POST['cgc_id']          ?? 0);
        $monto_giftcard = (float)($_POST['monto_giftcard'] ?? 0);
        $monto_externo  = (float)($_POST['monto_externo']  ?? 0);
        $con_descripcion = mysqli_real_escape_string($mysqli, trim($_POST['con_descripcion'] ?? ''));
        $id_user        = (int)$_SESSION['id_user'];
        $loc_id         = isset($_SESSION['loc_id']) && $_SESSION['loc_id'] ? (int)$_SESSION['loc_id'] : null;

        if ($cgc_id === 0 || $monto_giftcard <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        $qGC = "SELECT cgc_codigo, cgc_cupo_disponible, cgc_estado, cgc_fecha_caducidad
                FROM codigo_gift_card WHERE cgc_id = $cgc_id AND cgc_estado = 'activo'";
        $rGC = mysqli_query($mysqli, $qGC);

        if (!$rGC || mysqli_num_rows($rGC) === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Gift Card no válida o ya no disponible']);
            break;
        }

        $gc = mysqli_fetch_assoc($rGC);

        if ($gc['cgc_fecha_caducidad'] && $gc['cgc_fecha_caducidad'] < date('Y-m-d')) {
            mysqli_query($mysqli, "UPDATE codigo_gift_card SET cgc_estado='vencido' WHERE cgc_id=$cgc_id");
            echo json_encode(['success' => false, 'mensaje' => 'Gift Card vencida']);
            break;
        }

        if ($monto_giftcard > (float)$gc['cgc_cupo_disponible']) {
            echo json_encode(['success' => false, 'mensaje' => 'Monto supera el saldo disponible ($' . number_format($gc['cgc_cupo_disponible'], 2) . ')']);
            break;
        }

        $valor_total   = $monto_giftcard + $monto_externo;
        $iva_calc      = calcularIva($valor_total, $IVA_PCT);
        $valor_neto    = $iva_calc['subtotal'];
        $valor_iva     = $iva_calc['iva'];
        $fecha         = date('Y-m-d');
        $hora          = date('H:i:s');
        $loc_sql       = $loc_id ? $loc_id : 'NULL';
        $gc_codigo_sql = "'" . mysqli_real_escape_string($mysqli, $gc['cgc_codigo']) . "'";
        $desc_sql      = $con_descripcion !== '' ? "'$con_descripcion'" : 'NULL';

        $insert = "INSERT INTO consumo (con_fecha, con_hora, con_valor_neto, con_iva, con_valor_total,
                                        con_estado, con_descripcion, id_user, loc_id,
                                        con_monto_convenio, con_monto_externo, con_voucher_impreso,
                                        con_giftcard_codigo, con_monto_giftcard)
                   VALUES ('$fecha', '$hora', '$valor_neto', '$valor_iva', '$valor_total',
                           'pendiente', $desc_sql, $id_user, $loc_sql,
                           '0', '$monto_externo', 0,
                           $gc_codigo_sql, '$monto_giftcard')";

        if (!mysqli_query($mysqli, $insert)) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al registrar: ' . mysqli_error($mysqli)]);
            break;
        }

        $con_id = mysqli_insert_id($mysqli);

        // Descontar saldo gift card
        $nuevo_saldo  = (float)$gc['cgc_cupo_disponible'] - $monto_giftcard;
        $nuevo_estado = $nuevo_saldo <= 0 ? 'consumido' : 'activo';
        $fecha_uso    = $nuevo_estado === 'consumido' ? "'" . date('Y-m-d H:i:s') . "'" : 'NULL';
        mysqli_query($mysqli, "UPDATE codigo_gift_card
                               SET cgc_cupo_disponible = $nuevo_saldo,
                                   cgc_estado = '$nuevo_estado',
                                   cgc_fecha_uso = $fecha_uso
                               WHERE cgc_id = $cgc_id");

        echo json_encode(['success' => true, 'con_id' => $con_id]);
        break;

    // ----------------------------------------------------------
    // Registrar venta
    // ----------------------------------------------------------
    case 'registrar':
        $per_id         = (int)($_POST['per_id'] ?? 0);
        $monto_convenio = (float)($_POST['monto_convenio'] ?? 0);
        $monto_externo  = (float)($_POST['monto_externo']  ?? 0);
        $monto_giftcard = (float)($_POST['monto_giftcard'] ?? 0);
        $cgc_id         = (int)($_POST['cgc_id']           ?? 0);
        $id_user        = (int)$_SESSION['id_user'];
        $loc_id         = isset($_SESSION['loc_id']) && $_SESSION['loc_id'] ? (int)$_SESSION['loc_id'] : null;

        if ($per_id === 0 || $monto_convenio <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        // Validar empleado y cupo
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

        // Validar gift card si aplica
        $gc_codigo_sql = 'NULL';
        if ($monto_giftcard > 0 && $cgc_id > 0) {
            $qGC = "SELECT cgc_codigo, cgc_cupo_disponible, cgc_estado, cgc_fecha_caducidad
                    FROM codigo_gift_card WHERE cgc_id = $cgc_id AND cgc_estado = 'activo'";
            $rGC = mysqli_query($mysqli, $qGC);

            if (!$rGC || mysqli_num_rows($rGC) === 0) {
                echo json_encode(['success' => false, 'mensaje' => 'Gift card no válida o ya no disponible']);
                break;
            }

            $gc = mysqli_fetch_assoc($rGC);

            if ($gc['cgc_fecha_caducidad'] && $gc['cgc_fecha_caducidad'] < date('Y-m-d')) {
                echo json_encode(['success' => false, 'mensaje' => 'Gift card vencida']);
                break;
            }

            if ($monto_giftcard > (float)$gc['cgc_cupo_disponible']) {
                echo json_encode(['success' => false, 'mensaje' => 'Monto gift card supera el saldo disponible ($' . number_format($gc['cgc_cupo_disponible'], 2) . ')']);
                break;
            }

            $gc_codigo_sql = "'" . mysqli_real_escape_string($mysqli, $gc['cgc_codigo']) . "'";
        } else {
            $monto_giftcard = 0;
            $cgc_id         = 0;
        }

        $valor_total = $monto_convenio + $monto_giftcard + $monto_externo;
        $iva_calc    = calcularIva($valor_total, $IVA_PCT);
        $valor_neto  = $iva_calc['subtotal'];
        $valor_iva   = $iva_calc['iva'];
        $fecha       = date('Y-m-d');
        $hora        = date('H:i:s');
        $loc_sql     = $loc_id ? $loc_id : 'NULL';

        $insert = "INSERT INTO consumo (con_fecha, con_hora, con_valor_neto, con_iva, con_valor_total,
                                        con_estado, id_user, loc_id, per_id,
                                        con_monto_convenio, con_monto_externo, con_voucher_impreso,
                                        con_giftcard_codigo, con_monto_giftcard)
                   VALUES ('$fecha', '$hora', '$valor_neto', '$valor_iva', '$valor_total',
                           'pendiente', $id_user, $loc_sql, $per_id,
                           '$monto_convenio', '$monto_externo', 0,
                           $gc_codigo_sql, '$monto_giftcard')";

        if (!mysqli_query($mysqli, $insert)) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al registrar: ' . mysqli_error($mysqli)]);
            break;
        }

        $con_id = mysqli_insert_id($mysqli);

        // Descontar cupo del empleado
        mysqli_query($mysqli, "UPDATE personal SET per_cupo_disponible = per_cupo_disponible - $monto_convenio WHERE per_id = $per_id");

        // Descontar saldo de gift card
        if ($monto_giftcard > 0 && $cgc_id > 0) {
            $nuevo_saldo = (float)$gc['cgc_cupo_disponible'] - $monto_giftcard;
            $nuevo_estado = $nuevo_saldo <= 0 ? 'consumido' : 'activo';
            $fecha_uso_sql = $nuevo_estado === 'consumido' ? "'" . date('Y-m-d H:i:s') . "'" : 'NULL';
            mysqli_query($mysqli, "UPDATE codigo_gift_card
                                   SET cgc_cupo_disponible = $nuevo_saldo,
                                       cgc_estado = '$nuevo_estado',
                                       cgc_fecha_uso = $fecha_uso_sql
                                   WHERE cgc_id = $cgc_id");
        }

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

        $query = "SELECT c.con_id, c.con_fecha, c.con_hora, c.con_valor_neto, c.con_iva,
                         c.con_valor_total, c.con_monto_convenio, c.con_monto_externo,
                         c.con_giftcard_codigo, c.con_monto_giftcard, c.con_descripcion,
                         p.per_nombre, p.per_documento,
                         cl.cli_descripcion,
                         u.name_user AS cajero,
                         l.loc_direccion
                  FROM consumo c
                  LEFT JOIN personal p  ON c.per_id = p.per_id
                  LEFT JOIN cliente  cl ON p.cli_id = cl.cli_id
                  LEFT JOIN usuario  u  ON c.id_user = u.id_user
                  LEFT JOIN local    l  ON c.loc_id  = l.loc_id
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
