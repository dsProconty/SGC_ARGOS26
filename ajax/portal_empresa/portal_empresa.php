<?php
date_default_timezone_set('America/Guayaquil');
session_start();
require_once '../../config/database.php';
require_once '../../helpers/cupo_marca_helpers.php';
mysqli_query($mysqli, "SET time_zone = '-05:00'");

if (empty($_SESSION['id_user'])) {
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
        $modo = cupoObtenerModo($mysqli, $cli_id);

        $qBase = "SELECT COUNT(*) AS total_empleados,
                         SUM(CASE WHEN per_estado = 'activo' THEN 1 ELSE 0 END) AS activos
                  FROM personal WHERE cli_id = $cli_id";
        $r = mysqli_fetch_assoc(mysqli_query($mysqli, $qBase));

        if ($modo['modo'] === 'marca') {
            $marcas = cupoMarcasActivas($mysqli);
            $qMarca = "SELECT pcm.mar_id, SUM(pcm.pcm_asignado) AS asignado, SUM(pcm.pcm_disponible) AS disponible
                       FROM personal_cupo_marca pcm
                       JOIN personal p ON pcm.per_id = p.per_id
                       WHERE p.cli_id = $cli_id
                       GROUP BY pcm.mar_id";
            $res = mysqli_query($mysqli, $qMarca);
            $sumasPorMarca = [];
            while ($row = mysqli_fetch_assoc($res)) {
                $sumasPorMarca[(int)$row['mar_id']] = [
                    'asignado' => (float)$row['asignado'],
                    'disponible' => (float)$row['disponible'],
                ];
            }
            $porMarca = [];
            $totalAsignado = 0.0;
            $totalDisponible = 0.0;
            foreach ($marcas as $m) {
                $s = isset($sumasPorMarca[$m['mar_id']]) ? $sumasPorMarca[$m['mar_id']] : ['asignado' => 0.0, 'disponible' => 0.0];
                $porMarca[] = [
                    'mar_id' => $m['mar_id'],
                    'mar_descripcion' => $m['mar_descripcion'],
                    'asignado' => $s['asignado'],
                    'disponible' => $s['disponible'],
                    'consumido' => $s['asignado'] - $s['disponible'],
                ];
                $totalAsignado += $s['asignado'];
                $totalDisponible += $s['disponible'];
            }
            $r['total_asignado']   = $totalAsignado;
            $r['total_disponible'] = $totalDisponible;
            $r['total_consumido']  = $totalAsignado - $totalDisponible;
            $r['modo'] = 'marca';
            $r['por_marca'] = $porMarca;
        } else {
            $qGlobal = "SELECT SUM(per_cupo_asignado) AS total_asignado,
                               SUM(per_cupo_disponible) AS total_disponible,
                               SUM(per_cupo_asignado - per_cupo_disponible) AS total_consumido
                        FROM personal WHERE cli_id = $cli_id";
            $rGlobal = mysqli_fetch_assoc(mysqli_query($mysqli, $qGlobal));
            $r['total_asignado']   = (float)$rGlobal['total_asignado'];
            $r['total_disponible'] = (float)$rGlobal['total_disponible'];
            $r['total_consumido']  = (float)$rGlobal['total_consumido'];
            $r['modo'] = 'global';
        }

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
        $modo = cupoObtenerModo($mysqli, $cli_id);
        // Dos formas de respuesta según el modo: global → {cupo}, marca → {por_marca:[...]} — el frontend debe revisar 'modo' antes de leer los campos.
        if ($modo['modo'] === 'marca') {
            $marcas = cupoMarcasActivas($mysqli);
            $maximos = cupoMaximosPorMarca($mysqli, $cli_id);
            $per_id_consulta = (int)($_GET['per_id'] ?? 0);
            $actuales = [];
            if ($per_id_consulta) {
                $chkEmp = $mysqli->prepare("SELECT per_id FROM personal WHERE per_id = ? AND cli_id = ?");
                $chkEmp->bind_param('ii', $per_id_consulta, $cli_id);
                $chkEmp->execute();
                if ($chkEmp->get_result()->fetch_assoc()) {
                    $actuales = cupoEmpleadoPorMarca($mysqli, $per_id_consulta);
                }
            }
            $porMarca = [];
            foreach ($marcas as $m) {
                $porMarca[] = [
                    'mar_id'          => $m['mar_id'],
                    'mar_descripcion' => $m['mar_descripcion'],
                    'monto_max'       => isset($maximos[$m['mar_id']]) ? $maximos[$m['mar_id']] : 0.0,
                    'monto_actual'    => isset($actuales[$m['mar_id']]) ? $actuales[$m['mar_id']]['asignado'] : 0.0,
                ];
            }
            echo json_encode(['success' => true, 'modo' => 'marca', 'por_marca' => $porMarca]);
        } else {
            echo json_encode(['success' => true, 'modo' => 'global', 'cupo' => $modo['valor_global']]);
        }
        break;

    // ----------------------------------------------------------
    // Crear nuevo empleado
    // ----------------------------------------------------------
    case 'crear_empleado':
        $nombre    = mysqli_real_escape_string($mysqli, trim($_POST['per_nombre']    ?? ''));
        $documento = mysqli_real_escape_string($mysqli, trim($_POST['per_documento'] ?? ''));
        $correo    = mysqli_real_escape_string($mysqli, trim($_POST['per_correo']    ?? ''));
        $modo      = cupoObtenerModo($mysqli, $cli_id);

        if (!$nombre || !$documento) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        $cupoPorMarca = [];
        $cupo = 0;
        if ($modo['modo'] === 'marca') {
            $cupoPorMarca = json_decode($_POST['cupo_por_marca'] ?? '{}', true);
            if (!is_array($cupoPorMarca)) { $cupoPorMarca = []; }
            $maximos = cupoMaximosPorMarca($mysqli, $cli_id);
            $algunaMarca = false;
            foreach ($cupoPorMarca as $mar_id => $monto) {
                $monto = (float)$monto;
                if ($monto <= 0) { continue; }
                $algunaMarca = true;
                if (!isset($maximos[(int)$mar_id])) {
                    echo json_encode(['success' => false, 'mensaje' => 'El convenio no tiene un tope configurado para esa marca — no se puede asignar cupo ahí']);
                    exit;
                }
                $tope = $maximos[(int)$mar_id];
                if ($tope > 0 && $monto > $tope) {
                    echo json_encode(['success' => false, 'mensaje' => 'El cupo asignado en una marca supera el máximo permitido por el convenio ($' . number_format($tope, 2) . ')']);
                    exit;
                }
            }
            if (!$algunaMarca) {
                echo json_encode(['success' => false, 'mensaje' => 'Asigne un cupo en al menos una marca']);
                break;
            }
        } else {
            $cupo = (float)($_POST['per_cupo'] ?? 0);
            if ($cupo <= 0) {
                echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
                break;
            }
            $cupo_max = $modo['valor_global'];
            if ($cupo_max > 0 && $cupo > $cupo_max) {
                echo json_encode(['success' => false, 'mensaje' => 'El cupo del empleado ($' . number_format($cupo, 2) . ') no puede ser mayor al cupo asignado a la empresa ($' . number_format($cupo_max, 2) . ')']);
                break;
            }
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

        if (!mysqli_query($mysqli, $q)) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al guardar: ' . mysqli_error($mysqli)]);
            break;
        }

        $nuevo_per_id = mysqli_insert_id($mysqli);
        if ($modo['modo'] === 'marca') {
            foreach ($cupoPorMarca as $mar_id => $monto) {
                $monto = (float)$monto;
                if ($monto > 0) {
                    cupoUpsertEmpleadoMarca($mysqli, $nuevo_per_id, $mar_id, $monto);
                }
            }
        }

        echo json_encode(['success' => true, 'per_id' => $nuevo_per_id]);
        break;


    // ----------------------------------------------------------
    // Editar empleado
    // ----------------------------------------------------------
    case 'editar_empleado':
        $per_id    = (int)($_POST['per_id'] ?? 0);
        $nombre    = mysqli_real_escape_string($mysqli, trim($_POST['per_nombre']    ?? ''));
        $documento = mysqli_real_escape_string($mysqli, trim($_POST['per_documento'] ?? ''));
        $correo    = mysqli_real_escape_string($mysqli, trim($_POST['per_correo']    ?? ''));
        $modo      = cupoObtenerModo($mysqli, $cli_id);

        if (!$per_id || !$nombre || !$documento) {
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

        // CL-G: crear_empleado ya validaba que la cédula no exista (en ninguna
        // empresa); aquí faltaba revalidar al cambiarla, lo que permitía
        // terminar con la misma cédula duplicada en dos empresas.
        $chkCed = mysqli_fetch_assoc(mysqli_query($mysqli,
            "SELECT per_id FROM personal WHERE per_documento = '$documento' AND per_id != $per_id LIMIT 1"));
        if ($chkCed) {
            echo json_encode(['success' => false, 'mensaje' => 'Ya existe otro empleado registrado con esa cédula']);
            break;
        }

        $cupo = 0;
        $cupoPorMarca = [];
        if ($modo['modo'] === 'marca') {
            $cupoPorMarca = json_decode($_POST['cupo_por_marca'] ?? '{}', true);
            if (!is_array($cupoPorMarca)) { $cupoPorMarca = []; }
            $maximos = cupoMaximosPorMarca($mysqli, $cli_id);
            foreach ($cupoPorMarca as $mar_id => $monto) {
                $monto = (float)$monto;
                if ($monto <= 0) { continue; }
                if (!isset($maximos[(int)$mar_id])) {
                    echo json_encode(['success' => false, 'mensaje' => 'El convenio no tiene un tope configurado para esa marca — no se puede asignar cupo ahí']);
                    exit;
                }
                $tope = $maximos[(int)$mar_id];
                if ($tope > 0 && $monto > $tope) {
                    echo json_encode(['success' => false, 'mensaje' => 'El cupo asignado en una marca supera el máximo permitido por el convenio ($' . number_format($tope, 2) . ')']);
                    exit;
                }
            }
        } else {
            $cupo = (float)($_POST['per_cupo'] ?? 0);
            if ($cupo <= 0) {
                echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
                break;
            }
            $cupo_max = $modo['valor_global'];
            if ($cupo_max > 0 && $cupo > $cupo_max) {
                echo json_encode(['success' => false,
                    'mensaje' => 'El cupo ($' . number_format($cupo, 2) . ') no puede ser mayor al cupo de la empresa ($' . number_format($cupo_max, 2) . ')']);
                break;
            }
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

        $cupo_disponible_nuevo = $emp_check['per_cupo_disponible'];
        if ($modo['modo'] === 'global') {
            $cupo_anterior = (float)$emp_check['per_cupo_asignado'];
            if (abs($cupo_anterior - $cupo) > 0.001) {
                $label_cupo = $cupo > $cupo_anterior ? 'Aumento de cupo' : 'Disminución de cupo';
                $cambios[] = ['campo' => 'per_cupo_asignado', 'label' => $label_cupo,
                    'anterior' => '$' . number_format($cupo_anterior, 2), 'nuevo' => '$' . number_format($cupo, 2)];
                $consumido = $cupo_anterior - (float)$emp_check['per_cupo_disponible'];
                $cupo_disponible_nuevo = max(0, $cupo - $consumido);
            }
        }

        $correo_sql = $correo ? "'$correo'" : 'NULL';
        $q_update = "UPDATE personal SET
                        per_nombre = '$nombre',
                        per_documento = '$documento',
                        per_correo = $correo_sql"
                  . ($modo['modo'] === 'global' ? ", per_cupo_asignado = $cupo, per_cupo_disponible = $cupo_disponible_nuevo" : '')
                  . " WHERE per_id = $per_id AND cli_id = $cli_id";

        if (!mysqli_query($mysqli, $q_update)) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar: ' . mysqli_error($mysqli)]);
            break;
        }

        if ($modo['modo'] === 'marca') {
            $marcasCatalogo = cupoMarcasActivas($mysqli);
            $nombresPorId = [];
            foreach ($marcasCatalogo as $m) { $nombresPorId[$m['mar_id']] = $m['mar_descripcion']; }
            foreach ($cupoPorMarca as $mar_id => $monto) {
                $mar_id = (int)$mar_id;
                $monto  = (float)$monto;
                if ($monto <= 0) { continue; }
                $antes = cupoEmpleadoEnMarca($mysqli, $per_id, $mar_id);
                if (abs($antes['asignado'] - $monto) > 0.001) {
                    $labelMarca = isset($nombresPorId[$mar_id]) ? $nombresPorId[$mar_id] : ('marca #' . $mar_id);
                    $cambios[] = [
                        'campo' => 'per_cupo_marca_' . $mar_id,
                        'label' => 'Cupo en ' . $labelMarca,
                        'anterior' => '$' . number_format($antes['asignado'], 2),
                        'nuevo'    => '$' . number_format($monto, 2),
                    ];
                }
                cupoUpsertEmpleadoMarca($mysqli, $per_id, $mar_id, $monto);
            }
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
