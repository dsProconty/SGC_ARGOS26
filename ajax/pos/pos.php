<?php
date_default_timezone_set('America/Guayaquil');
session_start();
require_once '../../config/database.php';
require_once '../../helpers/session_helpers.php';
require_once '../../helpers/cupo_marca_helpers.php';
mysqli_query($mysqli, "SET time_zone = '-05:00'");

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

// Acepta rol legacy (permisos_acceso) O el perfil nuevo asignado (per_id -> perfil.per_nombre)
function esSuperAdmin($mysqli) {
    if (strtolower($_SESSION['permisos_acceso'] ?? '') === 'super admin') {
        return true;
    }
    if (empty($_SESSION['id_user'])) {
        return false;
    }
    $stmt = $mysqli->prepare('SELECT p.per_nombre FROM usuario u JOIN perfil p ON u.per_id = p.per_id WHERE u.id_user = ?');
    $stmt->bind_param('i', $_SESSION['id_user']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row && strtolower($row['per_nombre']) === 'super admin';
}

// Resuelve el local a usar en el consumo. Un cajero con local fijo siempre
// usa su propio loc_id (no se puede suplantar). Solo si la sesión NO tiene
// un local asignado Y el usuario es Super Admin se permite elegir uno vía
// POST, validado contra la tabla local para evitar IDs inventados.
function resolverLocId($mysqli) {
    if (!empty($_SESSION['loc_id'])) {
        return (int)$_SESSION['loc_id'];
    }
    if (!esSuperAdmin($mysqli)) {
        return null;
    }
    $posted = (int)($_POST['loc_id'] ?? 0);
    if ($posted <= 0) {
        return null;
    }
    $stmt = $mysqli->prepare('SELECT loc_id FROM local WHERE loc_id = ? AND loc_activo = 1');
    $stmt->bind_param('i', $posted);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int)$row['loc_id'] : null;
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

        // --- Intentar como cédula o número de tarjeta (solo dígitos) ---
        // El cajero puede buscar por cédula (10 dígitos) o escaneando la
        // tarjeta del empleado (16 dígitos, per_numero_tarjeta) — antes solo
        // se buscaba por cédula, así que un empleado bloqueado localizado por
        // su tarjeta caía en "no encontrado" en vez de avisar que está bloqueado.
        if (preg_match('/^\d+$/', $input)) {
            $query = "SELECT p.per_id, p.per_nombre, p.per_documento, p.per_estado,
                             p.per_cupo_asignado, p.per_cupo_disponible,
                             c.cli_id, c.cli_descripcion, c.cli_tipo_beneficio, c.cli_valor_beneficio
                      FROM personal p
                      JOIN cliente c ON p.cli_id = c.cli_id
                      WHERE p.per_documento = '$input' OR p.per_numero_tarjeta = '$input'
                      LIMIT 1";
            $result = mysqli_query($mysqli, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $data = mysqli_fetch_assoc($result);
                if ($data['per_estado'] === 'suspendido') {
                    echo json_encode(['success' => false, 'mensaje' => 'La tarjeta de este empleado se encuentra suspendida. Por favor contactar con ' . $data['cli_descripcion'] . '.']);
                    exit;
                }
                if ($data['per_estado'] === 'bloqueado') {
                    echo json_encode(['success' => false, 'mensaje' => 'Esta persona se encuentra bloqueada. Por favor contactar con ' . $data['cli_descripcion'] . '.']);
                    break;
                }
                if ($data['per_estado'] === 'inactivo') {
                    echo json_encode(['success' => false, 'mensaje' => 'Esta persona se encuentra inactiva. Por favor contactar con ' . $data['cli_descripcion'] . '.']);
                    break;
                }
                $modo = cupoObtenerModo($mysqli, $data['cli_id']);
                $data['cli_modo_cupo'] = $modo['modo'];
                if ($modo['modo'] === 'marca') {
                    $loc_id_actual = resolverLocId($mysqli);
                    $mar_id_actual = $loc_id_actual ? cupoMarcaDeLocal($mysqli, $loc_id_actual) : null;
                    if ($mar_id_actual === null) {
                        $data['per_cupo_asignado']   = 0;
                        $data['per_cupo_disponible'] = 0;
                        $data['marca_no_resuelta']   = true;
                    } else {
                        $cupoMarca = cupoEmpleadoEnMarca($mysqli, $data['per_id'], $mar_id_actual);
                        $data['per_cupo_asignado']   = $cupoMarca['asignado'];
                        $data['per_cupo_disponible'] = $cupoMarca['disponible'];
                    }
                }

                echo json_encode(['success' => true, 'tipo' => 'empleado', 'data' => $data]);
                break;
            }
        }

        // --- Intentar como código Gift Card ---
        $qGC = "SELECT cgc_id, cgc_codigo, cgc_cupo_inicial, cgc_cupo_disponible, cgc_estado, cgc_fecha_caducidad
                FROM codigo_gift_card WHERE cgc_codigo = '$input' LIMIT 1";
        $rGC = mysqli_query($mysqli, $qGC);

        if ($rGC && mysqli_num_rows($rGC) > 0) {
            $gc = mysqli_fetch_assoc($rGC);

            // Verificar caducidad — se muestra cupo/saldo igual que una tarjeta
            // válida, para que el cajero vea el cuadro completo, no solo "vencida".
            if ($gc['cgc_fecha_caducidad'] && $gc['cgc_fecha_caducidad'] < date('Y-m-d')) {
                mysqli_query($mysqli, "UPDATE codigo_gift_card SET cgc_estado='vencido' WHERE cgc_id={$gc['cgc_id']}");
                echo json_encode([
                    'success'         => false,
                    'tipo'            => 'giftcard_vencida',
                    'mensaje'         => 'CADUCADA el ' . date('d/m/Y', strtotime($gc['cgc_fecha_caducidad'])),
                    'saldo_original'  => (float)$gc['cgc_cupo_inicial'],
                    'saldo'           => (float)$gc['cgc_cupo_disponible'],
                    'fecha_caducidad' => date('d/m/Y', strtotime($gc['cgc_fecha_caducidad'])),
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
                    'saldo_original'  => (float)$gc['cgc_cupo_inicial'],
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
        $loc_id         = resolverLocId($mysqli);

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
        // cgc_fecha_uso registra el último uso, sea parcial o total — antes solo
        // se guardaba cuando la tarjeta quedaba en 0, dejando NULL cualquier
        // consumo parcial aunque sí se haya usado hoy.
        $fecha_uso    = "'" . date('Y-m-d H:i:s') . "'";
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
        $loc_id         = resolverLocId($mysqli);

        if ($per_id === 0 || $monto_convenio <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        // Validar empleado y cupo
        $qEmp = "SELECT per_nombre, per_estado, per_cupo_disponible, cli_id FROM personal WHERE per_id = $per_id";
        $rEmp = mysqli_query($mysqli, $qEmp);

        if (!$rEmp || mysqli_num_rows($rEmp) === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
            break;
        }

        $emp = mysqli_fetch_assoc($rEmp);

        if ($emp['per_estado'] === 'suspendido') {
            echo json_encode(['success' => false, 'mensaje' => 'La tarjeta de este empleado se encuentra suspendida.']);
            exit;
        }
        if ($emp['per_estado'] !== 'activo') {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no activo']);
            break;
        }

        $modoCupoEmp = cupoObtenerModo($mysqli, $emp['cli_id']);
        $mar_id_venta = null;
        if ($modoCupoEmp['modo'] === 'marca') {
            $mar_id_venta = $loc_id ? cupoMarcaDeLocal($mysqli, $loc_id) : null;
            if ($mar_id_venta === null) {
                echo json_encode(['success' => false, 'mensaje' => 'No se pudo determinar la marca del local para validar el cupo. Contacte a soporte.']);
                break;
            }
            $cupoMarcaEmp = cupoEmpleadoEnMarca($mysqli, $per_id, $mar_id_venta);
            if ($monto_convenio > $cupoMarcaEmp['disponible']) {
                echo json_encode(['success' => false, 'mensaje' => 'El monto supera el cupo disponible en esta marca ($' . number_format($cupoMarcaEmp['disponible'], 2) . ')']);
                break;
            }
        } else {
            if ($monto_convenio > (float)$emp['per_cupo_disponible']) {
                echo json_encode(['success' => false, 'mensaje' => 'El monto supera el cupo disponible ($' . number_format($emp['per_cupo_disponible'], 2) . ')']);
                break;
            }
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

        // Descontar cupo del empleado (por marca si el convenio está en ese modo)
        if ($modoCupoEmp['modo'] === 'marca') {
            cupoDescontarEmpleadoMarca($mysqli, $per_id, $mar_id_venta, $monto_convenio);
        } else {
            mysqli_query($mysqli, "UPDATE personal SET per_cupo_disponible = per_cupo_disponible - $monto_convenio WHERE per_id = $per_id");
        }

        // Descontar saldo de gift card
        if ($monto_giftcard > 0 && $cgc_id > 0) {
            $nuevo_saldo = (float)$gc['cgc_cupo_disponible'] - $monto_giftcard;
            $nuevo_estado = $nuevo_saldo <= 0 ? 'consumido' : 'activo';
            // cgc_fecha_uso registra el último uso, sea parcial o total.
            $fecha_uso_sql = "'" . date('Y-m-d H:i:s') . "'";
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

        $query = "SELECT c.con_id, c.con_fecha, c.con_hora, c.con_valor_total, c.con_estado,
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

    // ----------------------------------------------------------
    // PV-F: Anular venta el mismo día, con justificación obligatoria.
    // Devuelve automáticamente el cupo de convenio o el saldo de Gift
    // Card que se haya usado en esa venta, y deja registro de quién y
    // por qué (consumo_anulacion).
    // ----------------------------------------------------------
    case 'anular_venta':
        if (!tienePermiso($mysqli, 'pos.anular')) {
            echo json_encode(['success' => false, 'mensaje' => 'Sin permisos para anular ventas']);
            break;
        }
        $con_id = (int)($_POST['con_id'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');

        if (!$con_id || $motivo === '') {
            echo json_encode(['success' => false, 'mensaje' => 'Indique el motivo de la anulación']);
            break;
        }

        $stmt = $mysqli->prepare(
            "SELECT con_id, con_fecha, con_estado, per_id, con_monto_convenio,
                    con_giftcard_codigo, con_monto_giftcard
             FROM consumo WHERE con_id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $con_id);
        $stmt->execute();
        $con = $stmt->get_result()->fetch_assoc();

        if (!$con) { echo json_encode(['success' => false, 'mensaje' => 'Venta no encontrada']); break; }
        if ($con['con_fecha'] !== date('Y-m-d')) {
            echo json_encode(['success' => false, 'mensaje' => 'Solo se pueden anular ventas del mismo día']);
            break;
        }
        if ($con['con_estado'] === 'anulado') {
            echo json_encode(['success' => false, 'mensaje' => 'Esta venta ya estaba anulada']);
            break;
        }

        $mysqli->begin_transaction();
        try {
            $upd = $mysqli->prepare("UPDATE consumo SET con_estado = 'anulado' WHERE con_id = ?");
            $upd->bind_param('i', $con_id);
            if (!$upd->execute()) throw new Exception('Error al anular la venta');

            // Devolver cupo de convenio consumido
            $montoConvenio = (float)$con['con_monto_convenio'];
            if ($montoConvenio > 0 && $con['per_id']) {
                $updCupo = $mysqli->prepare(
                    "UPDATE personal SET per_cupo_disponible = LEAST(per_cupo_asignado, per_cupo_disponible + ?) WHERE per_id = ?"
                );
                $updCupo->bind_param('di', $montoConvenio, $con['per_id']);
                if (!$updCupo->execute()) throw new Exception('Error al devolver el cupo');
            }

            // Devolver saldo de Gift Card consumido
            $montoGC = (float)$con['con_monto_giftcard'];
            if ($montoGC > 0 && !empty($con['con_giftcard_codigo'])) {
                $gc = $mysqli->prepare("SELECT cgc_id, cgc_cupo_inicial, cgc_cupo_disponible FROM codigo_gift_card WHERE cgc_codigo = ? LIMIT 1");
                $gc->bind_param('s', $con['con_giftcard_codigo']);
                $gc->execute();
                $gcRow = $gc->get_result()->fetch_assoc();
                if ($gcRow) {
                    $nuevoSaldo = min((float)$gcRow['cgc_cupo_inicial'], (float)$gcRow['cgc_cupo_disponible'] + $montoGC);
                    $nuevoEstado = $nuevoSaldo > 0 ? 'activo' : 'consumido';
                    $updGC = $mysqli->prepare("UPDATE codigo_gift_card SET cgc_cupo_disponible = ?, cgc_estado = ? WHERE cgc_id = ?");
                    $updGC->bind_param('dsi', $nuevoSaldo, $nuevoEstado, $gcRow['cgc_id']);
                    if (!$updGC->execute()) throw new Exception('Error al devolver el saldo de la Gift Card');
                }
            }

            $idUserSesion = (int)$_SESSION['id_user'];
            $ins = $mysqli->prepare("INSERT INTO consumo_anulacion (con_id, id_user, can_motivo) VALUES (?, ?, ?)");
            if (!$ins) throw new Exception('Ejecute la migración bloque12_anulacion_consumo.sql en phpMyAdmin.');
            $ins->bind_param('iis', $con_id, $idUserSesion, $motivo);
            if (!$ins->execute()) throw new Exception('Error al registrar la anulación');

            $mysqli->commit();
            echo json_encode(['success' => true, 'mensaje' => 'Venta anulada correctamente']);
        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------------
    // PV-F: ver quién anuló una venta y por qué (trazabilidad visible)
    // ----------------------------------------------------------
    case 'ver_anulacion':
        $con_id = (int)($_GET['con_id'] ?? 0);
        $stmt = $mysqli->prepare(
            "SELECT ca.can_motivo, ca.can_fecha, u.name_user
             FROM consumo_anulacion ca JOIN usuario u ON ca.id_user = u.id_user
             WHERE ca.con_id = ? ORDER BY ca.can_fecha DESC LIMIT 1"
        );
        if (!$stmt) { echo json_encode(['success' => false]); break; }
        $stmt->bind_param('i', $con_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        echo $row
            ? json_encode(['success' => true, 'data' => $row])
            : json_encode(['success' => false, 'mensaje' => 'Sin registro de anulación']);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}
