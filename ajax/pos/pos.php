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
        // 'archivado' se excluye directo del WHERE (no con un mensaje como
        // bloqueado/inactivo): para el cajero tiene que verse igual que si la
        // cédula no existiera, no como una tarjeta rechazada.
        if (preg_match('/^\d+$/', $input)) {
            $query = "SELECT p.per_id, p.per_nombre, p.per_documento, p.per_estado,
                             p.per_cupo_asignado, p.per_cupo_disponible,
                             c.cli_id, c.cli_descripcion, c.cli_tipo_beneficio, c.cli_valor_beneficio
                      FROM personal p
                      JOIN cliente c ON p.cli_id = c.cli_id
                      WHERE (p.per_documento = '$input' OR p.per_numero_tarjeta = '$input')
                        AND p.per_estado != 'archivado'
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
    // Combos Especiales: buscar por código B2B. Independiente de la
    // búsqueda de empleado/Gift Card — se puede usar junto con cualquiera
    // de las dos. Reutilizable por cualquier cliente hasta su vencimiento;
    // solo válido en locales de la misma franquicia del combo.
    // ----------------------------------------------------------
    case 'buscar_combo':
        $codigo = strtoupper(trim($_GET['codigo'] ?? ''));
        if ($codigo === '') {
            echo json_encode(['success' => false, 'mensaje' => 'Ingrese el código del combo']);
            break;
        }

        $stmt = $mysqli->prepare(
            "SELECT ce_id, ce_nombre, ce_descripcion, ce_valor, ce_fecha_caducidad, mar_id FROM combo_especial WHERE ce_codigo = ?"
        );
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $combo = $stmt->get_result()->fetch_assoc();

        if (!$combo) {
            echo json_encode(['success' => false, 'mensaje' => 'Código de combo no encontrado']);
            break;
        }
        if ($combo['ce_fecha_caducidad'] < date('Y-m-d')) {
            echo json_encode(['success' => false, 'mensaje' => 'Este combo venció el ' . date('d/m/Y', strtotime($combo['ce_fecha_caducidad']))]);
            break;
        }

        $loc_id_actual = resolverLocId($mysqli);
        $mar_id_actual = $loc_id_actual ? cupoMarcaDeLocal($mysqli, $loc_id_actual) : null;
        if ($mar_id_actual === null || (int)$combo['mar_id'] !== $mar_id_actual) {
            echo json_encode(['success' => false, 'mensaje' => 'Este combo no aplica en este local']);
            break;
        }

        echo json_encode([
            'success' => true,
            'data'    => [
                'ce_id'          => (int)$combo['ce_id'],
                'ce_nombre'      => $combo['ce_nombre'],
                'ce_descripcion' => $combo['ce_descripcion'],
                'ce_valor'       => (float)$combo['ce_valor'],
            ]
        ]);
        break;

    // ----------------------------------------------------------
    // Combos Especiales: registrar de contado, sin empleado en el sistema.
    // ----------------------------------------------------------
    case 'registrar_combo_contado':
        $combo_id = (int)($_POST['combo_id'] ?? 0);
        $id_user  = (int)$_SESSION['id_user'];
        $loc_id   = resolverLocId($mysqli);

        if ($combo_id === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        $stmt = $mysqli->prepare("SELECT ce_id, ce_nombre, ce_valor, ce_fecha_caducidad, mar_id FROM combo_especial WHERE ce_id = ?");
        $stmt->bind_param('i', $combo_id);
        $stmt->execute();
        $combo = $stmt->get_result()->fetch_assoc();

        if (!$combo) { echo json_encode(['success' => false, 'mensaje' => 'Combo no encontrado']); break; }
        if ($combo['ce_fecha_caducidad'] < date('Y-m-d')) {
            echo json_encode(['success' => false, 'mensaje' => 'Este combo ya venció']);
            break;
        }
        $mar_id_actual = $loc_id ? cupoMarcaDeLocal($mysqli, $loc_id) : null;
        if ($mar_id_actual === null || (int)$combo['mar_id'] !== $mar_id_actual) {
            echo json_encode(['success' => false, 'mensaje' => 'Este combo no aplica en este local']);
            break;
        }

        // El valor SIEMPRE se toma del combo en el servidor, nunca de lo que
        // mande el navegador — evita que se manipule el monto desde el cliente.
        $valor_total = (float)$combo['ce_valor'];
        $iva_calc    = calcularIva($valor_total, $IVA_PCT);
        $valor_neto  = $iva_calc['subtotal'];
        $valor_iva   = $iva_calc['iva'];
        $fecha       = date('Y-m-d');
        $hora        = date('H:i:s');
        $loc_sql     = $loc_id ? $loc_id : 'NULL';
        $desc_sql    = "'" . mysqli_real_escape_string($mysqli, $combo['ce_nombre']) . "'";

        $insert = "INSERT INTO consumo (con_fecha, con_hora, con_valor_neto, con_iva, con_valor_total,
                                        con_estado, con_descripcion, id_user, loc_id,
                                        con_monto_convenio, con_monto_externo, con_voucher_impreso, con_combo_id)
                   VALUES ('$fecha', '$hora', '$valor_neto', '$valor_iva', '$valor_total',
                           'pendiente', $desc_sql, $id_user, $loc_sql,
                           '0', '$valor_total', 0, $combo_id)";

        if (!mysqli_query($mysqli, $insert)) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al registrar: ' . mysqli_error($mysqli)]);
            break;
        }

        echo json_encode(['success' => true, 'con_id' => mysqli_insert_id($mysqli)]);
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
        $combo_id       = (int)($_POST['combo_id'] ?? 0);
        $monto_convenio = (float)($_POST['monto_convenio'] ?? 0);
        $monto_externo  = (float)($_POST['monto_externo']  ?? 0);
        $monto_giftcard = (float)($_POST['monto_giftcard'] ?? 0);
        $cgc_id         = (int)($_POST['cgc_id']           ?? 0);
        $id_user        = (int)$_SESSION['id_user'];
        $loc_id         = resolverLocId($mysqli);

        // Combos Especiales a crédito: el valor y el resto de medios de pago
        // SIEMPRE se fuerzan desde el servidor cuando hay un combo — nunca lo
        // que mande el navegador — y un combo no se combina con gift card ni
        // pago externo en la misma venta.
        if ($combo_id > 0) {
            $stmtCombo = $mysqli->prepare("SELECT ce_id, ce_valor, ce_fecha_caducidad, mar_id FROM combo_especial WHERE ce_id = ?");
            $stmtCombo->bind_param('i', $combo_id);
            $stmtCombo->execute();
            $combo = $stmtCombo->get_result()->fetch_assoc();

            if (!$combo) { echo json_encode(['success' => false, 'mensaje' => 'Combo no encontrado']); break; }
            if ($combo['ce_fecha_caducidad'] < date('Y-m-d')) {
                echo json_encode(['success' => false, 'mensaje' => 'Este combo ya venció']);
                break;
            }
            $marIdComboLocal = $loc_id ? cupoMarcaDeLocal($mysqli, $loc_id) : null;
            if ($marIdComboLocal === null || (int)$combo['mar_id'] !== $marIdComboLocal) {
                echo json_encode(['success' => false, 'mensaje' => 'Este combo no aplica en este local']);
                break;
            }

            $monto_convenio = (float)$combo['ce_valor'];
            $monto_externo  = 0;
            $monto_giftcard = 0;
            $cgc_id         = 0;
        }

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

        $combo_id_sql = $combo_id > 0 ? $combo_id : 'NULL';

        $insert = "INSERT INTO consumo (con_fecha, con_hora, con_valor_neto, con_iva, con_valor_total,
                                        con_estado, id_user, loc_id, per_id,
                                        con_monto_convenio, con_monto_externo, con_voucher_impreso,
                                        con_giftcard_codigo, con_monto_giftcard, con_combo_id)
                   VALUES ('$fecha', '$hora', '$valor_neto', '$valor_iva', '$valor_total',
                           'pendiente', $id_user, $loc_sql, $per_id,
                           '$monto_convenio', '$monto_externo', 0,
                           $gc_codigo_sql, '$monto_giftcard', $combo_id_sql)";

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
                         l.loc_direccion,
                         ce.ce_nombre AS combo_nombre, ce.ce_codigo AS combo_codigo
                  FROM consumo c
                  LEFT JOIN personal p  ON c.per_id = p.per_id
                  LEFT JOIN cliente  cl ON p.cli_id = cl.cli_id
                  LEFT JOIN usuario  u  ON c.id_user = u.id_user
                  LEFT JOIN local    l  ON c.loc_id  = l.loc_id
                  LEFT JOIN combo_especial ce ON c.con_combo_id = ce.ce_id
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
                         c.con_monto_convenio, c.con_monto_externo, c.con_monto_giftcard,
                         c.con_giftcard_codigo, c.con_voucher_impreso,
                         p.per_nombre, p.per_documento,
                         COALESCE(cl.cli_descripcion, clgc.cli_descripcion) AS cli_descripcion
                  FROM consumo c
                  LEFT JOIN personal p  ON c.per_id = p.per_id
                  LEFT JOIN cliente  cl ON p.cli_id = cl.cli_id
                  LEFT JOIN codigo_gift_card cgc ON c.con_giftcard_codigo = cgc.cgc_codigo
                  LEFT JOIN lote_gift_card   lgc ON cgc.lgc_id = lgc.lgc_id
                  LEFT JOIN cliente clgc ON lgc.cli_id = clgc.cli_id
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
        $id_user = (int)$_SESSION['id_user'];
        // Ver todos los locales/cajeros (no solo las propias ventas) requiere ser
        // admin O tener el permiso puntual de mover ventas de local — antes esto
        // se decidía con $_SESSION['permisos_acceso'] crudo, que no reconoce
        // perfiles personalizados con permisos granulares asignados.
        $puedeVerTodo = esSuperAdmin($mysqli) || tienePerfil($mysqli, 'Administrador') || tienePermiso($mysqli, 'pos.mover_local');

        $fecha_inicio = mysqli_real_escape_string($mysqli, $_GET['fecha_inicio'] ?? date('Y-m-d'));
        $fecha_fin    = mysqli_real_escape_string($mysqli, $_GET['fecha_fin']    ?? date('Y-m-d'));

        // Validar formato fechas
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
            echo json_encode(['success' => false, 'mensaje' => 'Fechas inválidas']);
            break;
        }

        $where = "c.con_fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";

        if ($puedeVerTodo) {
            // Puede filtrar por local y por cajero; sin filtro ve todos
            if (!empty($_GET['loc_id'])) {
                $loc_id = (int)$_GET['loc_id'];
                $where .= " AND c.loc_id = $loc_id";
            }
            if (!empty($_GET['id_user_cajero'])) {
                $idUserCajero = (int)$_GET['id_user_cajero'];
                $where .= " AND c.id_user = $idUserCajero";
            }
        } else {
            // Cajero solo ve sus propias ventas
            $where .= " AND c.id_user = $id_user";
        }

        // LEFT JOIN: una venta de Gift Card (registrar_giftcard) no tiene per_id
        // (no hay empleado de por medio), pero toda Gift Card sí pertenece a un
        // convenio (el que la compró) — se resuelve por el lote que la generó
        // para que la empresa dueña del convenio siga apareciendo en el historial.
        $query = "SELECT c.con_id, c.con_fecha, c.con_hora, c.con_valor_total, c.con_estado, c.loc_id,
                         c.con_monto_convenio, c.con_monto_externo, c.con_monto_giftcard,
                         c.con_giftcard_codigo, c.con_voucher_impreso,
                         p.per_nombre, p.per_documento,
                         COALESCE(cl.cli_descripcion, clgc.cli_descripcion) AS cli_descripcion,
                         u.name_user AS cajero_nombre,
                         l.loc_direccion AS local_nombre,
                         (SELECT COUNT(*) FROM consumo_movimiento_local cml WHERE cml.con_id = c.con_id) AS con_veces_movida
                  FROM consumo c
                  LEFT JOIN personal p  ON c.per_id = p.per_id
                  LEFT JOIN cliente  cl ON p.cli_id = cl.cli_id
                  LEFT JOIN codigo_gift_card cgc ON c.con_giftcard_codigo = cgc.cgc_codigo
                  LEFT JOIN lote_gift_card   lgc ON cgc.lgc_id = lgc.lgc_id
                  LEFT JOIN cliente clgc ON lgc.cli_id = clgc.cli_id
                  LEFT JOIN usuario  u ON c.id_user = u.id_user
                  LEFT JOIN local    l ON c.loc_id  = l.loc_id
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
                    con_giftcard_codigo, con_monto_giftcard, loc_id
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

            // Devolver cupo de convenio consumido (por marca si el convenio está en ese modo)
            $montoConvenio = (float)$con['con_monto_convenio'];
            if ($montoConvenio > 0 && $con['per_id']) {
                $perCli = $mysqli->prepare("SELECT cli_id FROM personal WHERE per_id = ?");
                $perCli->bind_param('i', $con['per_id']);
                $perCli->execute();
                $cliDeEmpleado = $perCli->get_result()->fetch_assoc();
                $modoAnulacion = $cliDeEmpleado ? cupoObtenerModo($mysqli, $cliDeEmpleado['cli_id']) : ['modo' => 'global'];

                if ($modoAnulacion['modo'] === 'marca') {
                    // Usa el loc_id GUARDADO en la venta original (con.loc_id), NUNCA
                    // resolverLocId() del cajero que está anulando — la anulación puede
                    // hacerse desde otro local/terminal, y el cupo debe devolverse a la
                    // marca donde se cobró originalmente, no a la del cajero actual.
                    $marDeVenta = $con['loc_id'] ? cupoMarcaDeLocal($mysqli, $con['loc_id']) : null;
                    if ($marDeVenta === null) throw new Exception('No se pudo determinar la marca de la venta original para devolver el cupo. Contacte a soporte.');
                    cupoDevolverEmpleadoMarca($mysqli, $con['per_id'], $marDeVenta, $montoConvenio);
                } else {
                    $updCupo = $mysqli->prepare(
                        "UPDATE personal SET per_cupo_disponible = LEAST(per_cupo_asignado, per_cupo_disponible + ?) WHERE per_id = ?"
                    );
                    $updCupo->bind_param('di', $montoConvenio, $con['per_id']);
                    if (!$updCupo->execute()) throw new Exception('Error al devolver el cupo');
                }
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

    // ----------------------------------------------------------
    // Mover transacción de local: locales candidatos (misma franquicia
    // que el local actual del consumo, sin incluirlo a él mismo).
    // ----------------------------------------------------------
    case 'locales_para_mover':
        if (!tienePermiso($mysqli, 'pos.mover_local')) {
            echo json_encode(['success' => false, 'mensaje' => 'Sin permisos para mover ventas de local']);
            break;
        }
        $con_id = (int)($_GET['con_id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT loc_id FROM consumo WHERE con_id = ? LIMIT 1");
        $stmt->bind_param('i', $con_id);
        $stmt->execute();
        $con = $stmt->get_result()->fetch_assoc();
        if (!$con) { echo json_encode(['success' => false, 'mensaje' => 'Venta no encontrada']); break; }

        $marOrigen = cupoMarcaDeLocal($mysqli, $con['loc_id']);
        if ($marOrigen === null) {
            echo json_encode(['success' => false, 'mensaje' => 'No se pudo determinar la franquicia del local actual']);
            break;
        }

        $stmt = $mysqli->prepare(
            "SELECT l.loc_id, l.loc_direccion, m.mar_descripcion
             FROM local l JOIN marca m ON l.mar_id = m.mar_id
             WHERE l.mar_id = ? AND l.loc_activo = 1 AND l.loc_id != ?
             ORDER BY l.loc_direccion ASC"
        );
        $stmt->bind_param('ii', $marOrigen, $con['loc_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $locales = [];
        $marDescripcion = null;
        while ($row = $res->fetch_assoc()) {
            $marDescripcion = $row['mar_descripcion'];
            $locales[] = ['loc_id' => (int)$row['loc_id'], 'loc_direccion' => $row['loc_direccion']];
        }
        if ($marDescripcion === null) {
            // No hubo otro local de esa marca; igual devolvemos el nombre de la marca.
            $mR = $mysqli->prepare("SELECT mar_descripcion FROM marca WHERE mar_id = ?");
            $mR->bind_param('i', $marOrigen);
            $mR->execute();
            $mRow = $mR->get_result()->fetch_assoc();
            $marDescripcion = $mRow ? $mRow['mar_descripcion'] : '';
        }
        echo json_encode(['success' => true, 'mar_descripcion' => $marDescripcion, 'locales' => $locales]);
        break;

    // ----------------------------------------------------------
    // Mover transacción de local: corrige una venta que quedó registrada
    // en el local equivocado (cajero cambiado de local sin avisar).
    // Solo entre locales de la misma franquicia; motivo obligatorio;
    // si ya salió en un Estado de Cuenta enviado, avisa antes de mover.
    // ----------------------------------------------------------
    case 'mover_local':
        if (!tienePermiso($mysqli, 'pos.mover_local')) {
            echo json_encode(['success' => false, 'mensaje' => 'Sin permisos para mover ventas de local']);
            break;
        }
        $con_id        = (int)($_POST['con_id'] ?? 0);
        $loc_id_destino = (int)($_POST['loc_id_destino'] ?? 0);
        $motivo        = trim($_POST['motivo'] ?? '');
        $confirmar     = !empty($_POST['confirmar']);

        if (!$con_id || !$loc_id_destino || $motivo === '') {
            echo json_encode(['success' => false, 'mensaje' => 'Indique el local destino y el motivo']);
            break;
        }

        $stmt = $mysqli->prepare("SELECT con_id, con_fecha, con_estado, per_id, loc_id FROM consumo WHERE con_id = ? LIMIT 1");
        $stmt->bind_param('i', $con_id);
        $stmt->execute();
        $con = $stmt->get_result()->fetch_assoc();

        if (!$con) { echo json_encode(['success' => false, 'mensaje' => 'Venta no encontrada']); break; }
        if ($con['con_estado'] === 'anulado') {
            echo json_encode(['success' => false, 'mensaje' => 'Esta venta está anulada, no se puede mover']);
            break;
        }
        if ((int)$con['loc_id'] === $loc_id_destino) {
            echo json_encode(['success' => false, 'mensaje' => 'Elija un local distinto al actual']);
            break;
        }

        $marOrigen  = cupoMarcaDeLocal($mysqli, $con['loc_id']);
        $marDestino = cupoMarcaDeLocal($mysqli, $loc_id_destino);
        if ($marOrigen === null || $marDestino === null || $marOrigen !== $marDestino) {
            echo json_encode(['success' => false, 'mensaje' => 'Solo se puede mover a un local de la misma franquicia']);
            break;
        }

        // Aviso si ya salió en un Estado de Cuenta enviado al convenio — mismo
        // criterio de fecha/cliente que usa ec_generar_estado_cuenta().
        if ($con['per_id']) {
            $stmt = $mysqli->prepare(
                "SELECT ec.ec_fecha_envio
                 FROM estado_cuenta ec
                 JOIN personal p ON p.cli_id = ec.cli_id
                 WHERE p.per_id = ?
                   AND ec.ec_estado_envio = 'enviado'
                   AND ? BETWEEN ec.ec_periodo_inicio AND ec.ec_periodo_fin
                 ORDER BY ec.ec_fecha_envio DESC LIMIT 1"
            );
            $stmt->bind_param('is', $con['per_id'], $con['con_fecha']);
            $stmt->execute();
            $ecRow = $stmt->get_result()->fetch_assoc();
            if ($ecRow && !$confirmar) {
                $fechaEnvio = date('d/m/Y', strtotime($ecRow['ec_fecha_envio']));
                echo json_encode([
                    'success' => false,
                    'requiere_confirmacion' => true,
                    'mensaje' => "Esta venta ya salió en un Estado de Cuenta enviado el $fechaEnvio. ¿Moverla igual?"
                ]);
                break;
            }
        }

        $mysqli->begin_transaction();
        try {
            $upd = $mysqli->prepare("UPDATE consumo SET loc_id = ? WHERE con_id = ?");
            $upd->bind_param('ii', $loc_id_destino, $con_id);
            if (!$upd->execute()) throw new Exception('Error al mover la venta');

            $idUserSesion = (int)$_SESSION['id_user'];
            $locOrigen    = (int)$con['loc_id'];
            $ins = $mysqli->prepare(
                "INSERT INTO consumo_movimiento_local (con_id, id_user, loc_id_origen, loc_id_destino, cml_motivo)
                 VALUES (?, ?, ?, ?, ?)"
            );
            if (!$ins) throw new Exception('Ejecute la migración migrations/bloque17_mover_transaccion_local.sql en phpMyAdmin.');
            $ins->bind_param('iiiis', $con_id, $idUserSesion, $locOrigen, $loc_id_destino, $motivo);
            if (!$ins->execute()) throw new Exception('Error al registrar el movimiento');

            $mysqli->commit();
            echo json_encode(['success' => true, 'mensaje' => 'Venta movida correctamente']);
        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------------
    // Mover transacción de local: ver quién movió una venta y por qué
    // ----------------------------------------------------------
    case 'ver_movimiento_local':
        $con_id = (int)($_GET['con_id'] ?? 0);
        $stmt = $mysqli->prepare(
            "SELECT cml.cml_motivo, cml.cml_fecha, u.name_user,
                    lo.loc_direccion AS local_origen, ld.loc_direccion AS local_destino
             FROM consumo_movimiento_local cml
             JOIN usuario u ON cml.id_user = u.id_user
             JOIN local lo ON cml.loc_id_origen = lo.loc_id
             JOIN local ld ON cml.loc_id_destino = ld.loc_id
             WHERE cml.con_id = ? ORDER BY cml.cml_fecha DESC LIMIT 1"
        );
        if (!$stmt) { echo json_encode(['success' => false]); break; }
        $stmt->bind_param('i', $con_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        echo $row
            ? json_encode(['success' => true, 'data' => $row])
            : json_encode(['success' => false, 'mensaje' => 'Sin registro de movimiento']);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}
