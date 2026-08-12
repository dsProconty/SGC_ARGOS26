<?php
date_default_timezone_set('America/Guayaquil');
session_start();
require_once "../../config/database.php";
require_once "../../helpers/cupo_marca_helpers.php";
mysqli_query($mysqli, "SET time_zone = '-05:00'");

if (empty($_SESSION['id_user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── LIST — tabla HTML para DataTables ────────────────────────────────────
    case 'list':
        $filtro_beneficio = $_GET['beneficio'] ?? '';
        $filtro_cartera   = $_GET['cartera']   ?? '';
        $filtro_tipo      = $_GET['tipo']      ?? '';

        $where = [];
        if ($filtro_beneficio) $where[] = "cli_tipo_beneficio = '" . mysqli_real_escape_string($mysqli, $filtro_beneficio) . "'";
        if ($filtro_cartera)   $where[] = "cli_tipo_cartera   = '" . mysqli_real_escape_string($mysqli, $filtro_cartera)   . "'";
        $sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // El Tipo de Cliente (Empresarial / Gift Card) es un campo manual
        // (cliente.cli_tipo_cliente), elegido en el modal de Nuevo/Editar Cliente.
        $result = mysqli_query($mysqli,
            "SELECT c.cli_id, c.cli_descripcion, c.cli_ciudad, c.cli_contacto,
                    c.cli_email, c.cli_telefono, c.cli_numero_convenio,
                    c.cli_tipo_beneficio, c.cli_valor_beneficio,
                    c.cli_tipo_cartera, c.cli_dia_corte,
                    COALESCE(c.cli_tipo_cliente, 'Sin definir') AS cli_tipo_cliente,
                    (SELECT COUNT(*) FROM personal p WHERE p.cli_id = c.cli_id) AS total_personal,
                    (SELECT COUNT(*) FROM estado_cuenta ec WHERE ec.cli_id = c.cli_id) AS total_ec
             FROM cliente c $sql_where
             ORDER BY c.cli_descripcion ASC"
        );

        $rows = [];
        $kpis = ['total' => 0, 'empresarial' => 0, 'giftcard' => 0, 'sin_definir' => 0];
        while ($row = mysqli_fetch_assoc($result)) {
            $kpis['total']++;
            if      ($row['cli_tipo_cliente'] === 'Empresarial') $kpis['empresarial']++;
            elseif  ($row['cli_tipo_cliente'] === 'Gift Card')   $kpis['giftcard']++;
            else                                                 $kpis['sin_definir']++;

            $rows[] = $row;
        }

        if ($filtro_tipo) {
            $rows = array_values(array_filter($rows, function ($r) use ($filtro_tipo) {
                return $r['cli_tipo_cliente'] === $filtro_tipo;
            }));
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $rows, 'kpis' => $kpis]);
        break;

    // ── GET — datos JSON para modal editar ───────────────────────────────────
    case 'get':
        header('Content-Type: application/json');
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT * FROM cliente WHERE cli_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row  = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            echo json_encode(['success' => false, 'mensaje' => 'Cliente no encontrado']);
            break;
        }
        $row['cupo_por_marca'] = cupoMaximosPorMarca($mysqli, $id);
        echo json_encode(['success' => true, 'data' => $row]);
        break;

    // ── CREAR ─────────────────────────────────────────────────────────────────
    case 'crear':
        header('Content-Type: application/json');
        $desc = trim($_POST['cli_descripcion'] ?? '');
        if (empty($desc)) { echo json_encode(['success' => false, 'mensaje' => 'El nombre es requerido']); break; }

        $modo_cupo = ($_POST['cli_modo_cupo'] ?? 'global') === 'marca' ? 'marca' : 'global';

        $stmt = $mysqli->prepare(
            "INSERT INTO cliente
             (cli_descripcion, cli_numero_convenio, cli_ciudad, cli_contacto,
              cli_email, cli_email2, cli_telefono, cli_telefono2, cli_dia_corte,
              cli_tipo_beneficio, cli_valor_beneficio, cli_tipo_cartera, cli_comision, cli_tipo_cliente, cli_modo_cupo)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $conv  = trim($_POST['cli_numero_convenio'] ?? '') ?: null;
        $ciu   = trim($_POST['cli_ciudad']   ?? '') ?: null;
        $cont  = trim($_POST['cli_contacto'] ?? '') ?: null;
        $em1   = trim($_POST['cli_email']    ?? '') ?: null;
        $em2   = trim($_POST['cli_email2']   ?? '') ?: null;
        $tel1  = trim($_POST['cli_telefono'] ?? '') ?: null;
        $tel2  = trim($_POST['cli_telefono2']?? '') ?: null;
        $dia   = trim($_POST['cli_dia_corte']?? '0');
        $tben  = $_POST['cli_tipo_beneficio'] ?? null;
        $vben  = !empty($_POST['cli_valor_beneficio']) ? (float)$_POST['cli_valor_beneficio'] : null;
        $tcar  = $_POST['cli_tipo_cartera'] ?? null;
        $com   = !empty($_POST['cli_comision']) ? (float)$_POST['cli_comision'] : 0.00;
        $tcli  = trim($_POST['cli_tipo_cliente'] ?? '') ?: null;

        $stmt->bind_param('ssssssssssdsdss', $desc, $conv, $ciu, $cont, $em1, $em2, $tel1, $tel2, $dia, $tben, $vben, $tcar, $com, $tcli, $modo_cupo);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $mysqli->error]);
            break;
        }

        $nuevo_id = $mysqli->insert_id;
        if ($tben === 'Cupo' && $modo_cupo === 'marca') {
            $montos = json_decode($_POST['cupo_por_marca'] ?? '{}', true);
            cupoGuardarMaximosPorMarca($mysqli, $nuevo_id, is_array($montos) ? $montos : array());
        }

        echo json_encode(['success' => true, 'mensaje' => 'Cliente creado exitosamente', 'id' => $nuevo_id]);
        break;

    // ── EDITAR ────────────────────────────────────────────────────────────────
    case 'editar':
        header('Content-Type: application/json');
        $id   = (int)($_POST['cli_id'] ?? 0);
        $desc = trim($_POST['cli_descripcion'] ?? '');
        if (!$id || empty($desc)) { echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']); break; }

        $conv = trim($_POST['cli_numero_convenio'] ?? '') ?: null;
        $ciu  = trim($_POST['cli_ciudad']   ?? '') ?: null;
        $cont = trim($_POST['cli_contacto'] ?? '') ?: null;
        $em1  = trim($_POST['cli_email']    ?? '') ?: null;
        $em2  = trim($_POST['cli_email2']   ?? '') ?: null;
        $tel1 = trim($_POST['cli_telefono'] ?? '') ?: null;
        $tel2 = trim($_POST['cli_telefono2']?? '') ?: null;
        $dia  = trim($_POST['cli_dia_corte']?? '0');
        $tben = $_POST['cli_tipo_beneficio'] ?? null;
        $vben = !empty($_POST['cli_valor_beneficio']) ? (float)$_POST['cli_valor_beneficio'] : null;
        $tcar = $_POST['cli_tipo_cartera'] ?? null;
        $com  = !empty($_POST['cli_comision']) ? (float)$_POST['cli_comision'] : 0.00;
        $tcli = trim($_POST['cli_tipo_cliente'] ?? '') ?: null;
        $modo_cupo = ($_POST['cli_modo_cupo'] ?? 'global') === 'marca' ? 'marca' : 'global';

        $stmt = $mysqli->prepare(
            "UPDATE cliente SET
              cli_descripcion=?, cli_numero_convenio=?, cli_ciudad=?, cli_contacto=?,
              cli_email=?, cli_email2=?, cli_telefono=?, cli_telefono2=?, cli_dia_corte=?,
              cli_tipo_beneficio=?, cli_valor_beneficio=?, cli_tipo_cartera=?, cli_comision=?, cli_tipo_cliente=?, cli_modo_cupo=?
             WHERE cli_id=?"
        );
        $stmt->bind_param('ssssssssssdsdssi', $desc, $conv, $ciu, $cont, $em1, $em2, $tel1, $tel2, $dia, $tben, $vben, $tcar, $com, $tcli, $modo_cupo, $id);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $mysqli->error]);
            break;
        }

        if ($tben === 'Cupo' && $modo_cupo === 'marca') {
            $montos = json_decode($_POST['cupo_por_marca'] ?? '{}', true);
            cupoGuardarMaximosPorMarca($mysqli, $id, is_array($montos) ? $montos : array());
        } else {
            // El cliente ya no está en modo "marca" (o dejó de ser tipo Cupo) —
            // limpiar los topes por marca que pudiera tener de un estado anterior,
            // para que cliente_cupo_marca no quede con datos obsoletos.
            cupoGuardarMaximosPorMarca($mysqli, $id, array());
        }

        echo json_encode(['success' => true, 'mensaje' => 'Cliente actualizado']);
        break;

    // ── TAB: PERSONAL ─────────────────────────────────────────────────────────
    case 'personal_list':
        header('Content-Type: application/json');
        $cli_id = (int)($_GET['cli_id'] ?? 0);
        $stmt = $mysqli->prepare(
            "SELECT per_id, per_nombre, per_documento, per_numero_tarjeta,
                    per_correo, per_estado, per_cupo_asignado, per_cupo_disponible
             FROM personal WHERE cli_id = ? ORDER BY per_nombre ASC"
        );
        $stmt->bind_param('i', $cli_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ── CL-E: EDITAR EMPLEADO (desde la ficha del cliente, Super Admin) ────────
    case 'personal_editar':
        header('Content-Type: application/json');
        $per_id    = (int)($_POST['per_id']    ?? 0);
        $cli_id    = (int)($_POST['cli_id']    ?? 0);
        $nombre    = trim($_POST['per_nombre']    ?? '');
        $documento = trim($_POST['per_documento'] ?? '');
        $correo    = trim($_POST['per_correo']    ?? '');
        $modo      = cupoObtenerModo($mysqli, $cli_id);

        if (!$per_id || !$cli_id || !$nombre || !$documento) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        $stmt = $mysqli->prepare(
            "SELECT per_id, per_nombre, per_documento, per_correo, per_cupo_asignado, per_cupo_disponible
             FROM personal WHERE per_id = ? AND cli_id = ? LIMIT 1"
        );
        $stmt->bind_param('ii', $per_id, $cli_id);
        $stmt->execute();
        $emp_check = $stmt->get_result()->fetch_assoc();
        if (!$emp_check) { echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']); break; }

        // CL-G: la cédula no puede pertenecer a otro empleado (de esta empresa
        // u otra) — mismo control que ya existía al crear un empleado nuevo
        // desde Portal Empresa, que faltaba aquí al editar.
        $chkCed = $mysqli->prepare("SELECT per_id FROM personal WHERE per_documento = ? AND per_id != ? LIMIT 1");
        $chkCed->bind_param('si', $documento, $per_id);
        $chkCed->execute();
        if ($chkCed->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'mensaje' => 'Ya existe otro empleado registrado con esa cédula']);
            break;
        }

        $cupo = 0;
        $cupoPorMarca = [];
        if ($modo['modo'] === 'marca') {
            $val = cupoValidarPorMarca($mysqli, $cli_id, $_POST['cupo_por_marca'] ?? '{}', false);
            if (!$val['ok']) {
                echo json_encode(['success' => false, 'mensaje' => $val['mensaje']]);
                break;
            }
            $cupoPorMarca = $val['cupo_por_marca'];
        } else {
            $cupo = (float)($_POST['per_cupo_asignado'] ?? 0);
            if ($cupo <= 0) {
                echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
                break;
            }
            if ($modo['valor_global'] > 0 && $cupo > $modo['valor_global']) {
                echo json_encode(['success' => false, 'mensaje' => 'El cupo ($' . number_format($cupo, 2) . ') no puede ser mayor al cupo de la empresa ($' . number_format($modo['valor_global'], 2) . ')']);
                break;
            }
        }

        $id_user_sesion = (int)$_SESSION['id_user'];
        $cambios = [];
        if ($emp_check['per_nombre'] !== $nombre)       $cambios[] = ['campo' => 'per_nombre',    'label' => 'Nombre', 'anterior' => $emp_check['per_nombre'],    'nuevo' => $nombre];
        if ($emp_check['per_documento'] !== $documento) $cambios[] = ['campo' => 'per_documento', 'label' => 'Cédula', 'anterior' => $emp_check['per_documento'], 'nuevo' => $documento];
        if ($emp_check['per_correo'] !== $correo)       $cambios[] = ['campo' => 'per_correo',    'label' => 'Correo', 'anterior' => $emp_check['per_correo'],    'nuevo' => $correo];

        $cupo_disponible_nuevo = $emp_check['per_cupo_disponible'];
        if ($modo['modo'] === 'global') {
            $cupo_anterior = (float)$emp_check['per_cupo_asignado'];
            if (abs($cupo_anterior - $cupo) > 0.001) {
                $label_cupo = $cupo > $cupo_anterior ? 'Aumento de cupo' : 'Disminución de cupo';
                $cambios[] = ['campo' => 'per_cupo_asignado', 'label' => $label_cupo, 'anterior' => '$' . number_format($cupo_anterior, 2), 'nuevo' => '$' . number_format($cupo, 2)];
                $consumido = $cupo_anterior - (float)$emp_check['per_cupo_disponible'];
                $cupo_disponible_nuevo = max(0, $cupo - $consumido);
            }
        }

        $upd = $mysqli->prepare(
            "UPDATE personal SET per_nombre=?, per_documento=?, per_correo=?"
            . ($modo['modo'] === 'global' ? ", per_cupo_asignado=?, per_cupo_disponible=?" : '')
            . " WHERE per_id=? AND cli_id=?"
        );
        $correoParam = $correo !== '' ? $correo : null;
        if ($modo['modo'] === 'global') {
            $upd->bind_param('sssddii', $nombre, $documento, $correoParam, $cupo, $cupo_disponible_nuevo, $per_id, $cli_id);
        } else {
            $upd->bind_param('sssii', $nombre, $documento, $correoParam, $per_id, $cli_id);
        }
        if (!$upd->execute()) { echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar: ' . $mysqli->error]); break; }

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

        foreach ($cambios as $c) {
            $tra = $mysqli->prepare(
                "INSERT INTO personal_trazabilidad (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $anterior = $c['anterior'] ?? '';
            $nuevo    = $c['nuevo'] ?? '';
            $tra->bind_param('iissss', $per_id, $id_user_sesion, $c['campo'], $c['label'], $anterior, $nuevo);
            $tra->execute();
        }

        echo json_encode(['success' => true, 'cambios' => count($cambios)]);
        break;

    // ── CL-E: BLOQUEAR / ACTIVAR EMPLEADO (desde la ficha del cliente) ─────────
    case 'personal_cambiar_estado':
        header('Content-Type: application/json');
        $per_id       = (int)($_POST['per_id']    ?? 0);
        $cli_id       = (int)($_POST['cli_id']    ?? 0);
        $nuevo_estado = trim($_POST['per_estado'] ?? '');

        if (!$per_id || !$cli_id || !in_array($nuevo_estado, ['activo', 'bloqueado', 'inactivo'])) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
            break;
        }

        $stmt = $mysqli->prepare("SELECT per_id, per_estado FROM personal WHERE per_id = ? AND cli_id = ? LIMIT 1");
        $stmt->bind_param('ii', $per_id, $cli_id);
        $stmt->execute();
        $emp_check = $stmt->get_result()->fetch_assoc();
        if (!$emp_check) { echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']); break; }

        $upd = $mysqli->prepare("UPDATE personal SET per_estado = ? WHERE per_id = ?");
        $upd->bind_param('si', $nuevo_estado, $per_id);
        if (!$upd->execute()) { echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar']); break; }

        $id_user_sesion = (int)$_SESSION['id_user'];
        $label = $nuevo_estado === 'activo' ? 'Activación de empleado' : ($nuevo_estado === 'bloqueado' ? 'Bloqueo de empleado' : 'Inactivación de empleado');
        $tra = $mysqli->prepare(
            "INSERT INTO personal_trazabilidad (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
             VALUES (?, ?, 'per_estado', ?, ?, ?)"
        );
        $tra->bind_param('iisss', $per_id, $id_user_sesion, $label, $emp_check['per_estado'], $nuevo_estado);
        $tra->execute();

        echo json_encode(['success' => true, 'mensaje' => 'Estado actualizado']);
        break;

    // ── Cupo del convenio + valores actuales de un empleado, para el modal de edición ──
    case 'cupo_convenio_cliente':
        header('Content-Type: application/json');
        $cli_id_consulta = (int)($_GET['cli_id'] ?? 0);
        $per_id_consulta = (int)($_GET['per_id'] ?? 0);
        $modo = cupoObtenerModo($mysqli, $cli_id_consulta);
        if ($modo['modo'] !== 'marca') {
            echo json_encode(['success' => true, 'modo' => 'global']);
            break;
        }
        $marcas = cupoMarcasActivas($mysqli);
        $maximos = cupoMaximosPorMarca($mysqli, $cli_id_consulta);
        $actuales = [];
        if ($per_id_consulta) {
            $chkEmp = $mysqli->prepare("SELECT per_id FROM personal WHERE per_id = ? AND cli_id = ?");
            $chkEmp->bind_param('ii', $per_id_consulta, $cli_id_consulta);
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
        break;

    // ── CL-F: TRAZABILIDAD / AUDITORÍA DE EMPLEADO ─────────────────────────────
    case 'personal_trazabilidad_list':
        header('Content-Type: application/json');
        $per_id = (int)($_GET['per_id'] ?? 0);
        $stmt = $mysqli->prepare(
            "SELECT t.tra_id, t.tra_campo, t.tra_campo_label, t.tra_valor_anterior, t.tra_valor_nuevo,
                    DATE_FORMAT(t.tra_fecha, '%d/%m/%Y %H:%i') AS tra_fecha,
                    u.name_user
             FROM personal_trazabilidad t
             JOIN usuario u ON t.id_user = u.id_user
             WHERE t.per_id = ?
             ORDER BY t.tra_fecha DESC
             LIMIT 50"
        );
        $stmt->bind_param('i', $per_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ── CL-I: CARGA MASIVA DE PERSONAL ─────────────────────────────────────────
    // Filas ya vienen parseadas en JSON desde el navegador (SheetJS leyó el
    // Excel/CSV). En modo global: columna A cédula, B nombre, C cupo. En modo
    // marca: columna A cédula, B nombre, y una columna de cupo por cada marca
    // (fila['cupos_marca'] = {mar_id: monto}). Siempre para el cliente ya
    // elegido en la ficha — el archivo no trae empresa por fila.
    case 'personal_carga_masiva':
        header('Content-Type: application/json');
        $cli_id       = (int)($_POST['cli_id'] ?? 0);
        $accion       = trim($_POST['accion'] ?? '');
        $filas        = json_decode($_POST['filas'] ?? '[]', true);
        $soloPreview  = !empty($_POST['solo_preview']);

        if (!$cli_id || !in_array($accion, ['anadir', 'actualizar_cupo', 'bloquear']) || !is_array($filas) || !count($filas)) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            break;
        }

        $modo = cupoObtenerModo($mysqli, $cli_id);
        $cupo_max = $modo['valor_global'];
        $marcasCatalogo = $modo['modo'] === 'marca' ? cupoMarcasActivas($mysqli) : [];
        $nombresMarcaPorId = [];
        foreach ($marcasCatalogo as $m) { $nombresMarcaPorId[$m['mar_id']] = $m['mar_descripcion']; }

        $id_user_sesion = (int)$_SESSION['id_user'];
        $agregados = 0; $actualizados = 0; $bloqueados = 0; $omitidos = []; $detalle = [];

        foreach ($filas as $fila) {
            $cedula = trim((string)($fila['cedula'] ?? ''));
            $nombre = trim((string)($fila['nombre'] ?? ''));
            $cupo   = isset($fila['cupo']) ? (float)$fila['cupo'] : 0;

            if ($cedula === '') {
                $omitidos[] = ['cedula' => '(vacía)', 'motivo' => 'Fila sin cédula'];
                $detalle[]  = ['cedula' => '(vacía)', 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => 'Fila sin cédula — se omite', 'aplica' => false];
                continue;
            }

            $cuposMarcaFila = [];
            if ($modo['modo'] === 'marca' && $accion !== 'bloquear') {
                $rawCuposMarca = isset($fila['cupos_marca']) && is_array($fila['cupos_marca']) ? $fila['cupos_marca'] : [];
                $val = cupoValidarPorMarca($mysqli, $cli_id, json_encode($rawCuposMarca), false);
                if (!$val['ok']) {
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => $val['mensaje']];
                    $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => $val['mensaje'] . ' — se omite', 'aplica' => false];
                    continue;
                }
                // El helper devuelve el array de montos tal cual se lo pasaron (solo
                // ignora los <= 0 para efectos de validar el tope, no los quita del
                // array) — lo filtramos aquí para que count($cuposMarcaFila) refleje
                // cuántas marcas tienen realmente un monto positivo asignado.
                $cuposMarcaFila = [];
                foreach ($val['cupo_por_marca'] as $mar_id_val => $monto_val) {
                    if ((float)$monto_val > 0) {
                        $cuposMarcaFila[$mar_id_val] = (float)$monto_val;
                    }
                }
            }

            if ($accion === 'anadir') {
                if ($modo['modo'] === 'marca') {
                    if ($nombre === '' || !count($cuposMarcaFila)) {
                        $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Nombre o cupo inválido'];
                        $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => 'Nombre o cupo inválido — se omite', 'aplica' => false];
                        continue;
                    }
                } else {
                    if ($nombre === '' || $cupo <= 0) {
                        $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Nombre o cupo inválido'];
                        $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => 'Nombre o cupo inválido — se omite', 'aplica' => false];
                        continue;
                    }
                }

                $chk = $mysqli->prepare("SELECT per_id, per_estado FROM personal WHERE per_documento = ? LIMIT 1");
                $chk->bind_param('s', $cedula);
                $chk->execute();
                $existente = $chk->get_result()->fetch_assoc();
                if ($existente) {
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Ya existe (en esta u otra empresa)'];
                    $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $existente['per_estado'], 'resultado' => 'Ya existe (en esta u otra empresa) — no se hará nada', 'aplica' => false];
                    continue;
                }

                if ($modo['modo'] !== 'marca' && $cupo_max > 0 && $cupo > $cupo_max) {
                    // Llega aquí solo si la cédula NO existe (chequeo anterior), así
                    // que 'No existe' sigue siendo correcto para estado_actual — mismo
                    // orden que el archivo original: nombre/cupo → cédula → tope.
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Cupo excede el máximo de la empresa ($' . number_format($cupo_max, 2) . ')'];
                    $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => 'No existe', 'resultado' => 'Cupo excede el máximo de la empresa ($' . number_format($cupo_max, 2) . ') — se omite', 'aplica' => false];
                    continue;
                }

                if ($soloPreview) {
                    if ($modo['modo'] === 'marca') {
                        $resumenMarcas = [];
                        foreach ($cuposMarcaFila as $mar_id => $monto) {
                            $resumenMarcas[] = (isset($nombresMarcaPorId[(int)$mar_id]) ? $nombresMarcaPorId[(int)$mar_id] : ('marca #' . $mar_id)) . ' $' . number_format($monto, 2);
                        }
                        $detalle[] = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => 'No existe', 'resultado' => 'Se creará como empleado nuevo — ' . implode(', ', $resumenMarcas), 'aplica' => true];
                    } else {
                        $detalle[] = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => 'No existe', 'resultado' => 'Se creará como empleado nuevo, cupo $' . number_format($cupo, 2), 'aplica' => true];
                    }
                    continue;
                }

                $num_tarjeta = str_pad((string)mt_rand(1000, 9999), 4, '0') . str_pad((string)mt_rand(1000, 9999), 4, '0')
                             . str_pad((string)mt_rand(1000, 9999), 4, '0') . str_pad((string)mt_rand(1000, 9999), 4, '0');
                $cupoInsert = $modo['modo'] === 'marca' ? 0 : $cupo;
                $ins = $mysqli->prepare(
                    "INSERT INTO personal (per_nombre, per_documento, per_numero_tarjeta, cli_id, per_estado, per_cupo_asignado, per_cupo_disponible)
                     VALUES (?, ?, ?, ?, 'activo', ?, ?)"
                );
                $ins->bind_param('sssidd', $nombre, $cedula, $num_tarjeta, $cli_id, $cupoInsert, $cupoInsert);
                if ($ins->execute()) {
                    $nuevo_id = $mysqli->insert_id;
                    if ($modo['modo'] === 'marca') {
                        foreach ($cuposMarcaFila as $mar_id => $monto) {
                            cupoUpsertEmpleadoMarca($mysqli, $nuevo_id, $mar_id, $monto);
                        }
                        $cupoTxt = 'Alta por carga masiva (por marca)';
                    } else {
                        $cupoTxt = '$' . number_format($cupo, 2);
                    }
                    $tra = $mysqli->prepare(
                        "INSERT INTO personal_trazabilidad (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                         VALUES (?, ?, 'alta_masiva', 'Alta por carga masiva', '', ?)"
                    );
                    $tra->bind_param('iis', $nuevo_id, $id_user_sesion, $cupoTxt);
                    $tra->execute();
                    $agregados++;
                } else {
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Error al guardar'];
                }

            } elseif ($accion === 'actualizar_cupo') {
                if ($modo['modo'] !== 'marca' && $cupo <= 0) {
                    // Chequeo barato (sin BD) antes de la consulta de búsqueda —
                    // mismo orden que el archivo original. El modo marca no tiene
                    // un chequeo equivalente aquí (ya se validó vía cupoValidarPorMarca
                    // antes de este switch), así que esto no le aplica.
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Cupo inválido'];
                    $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => 'Cupo inválido — se omite', 'aplica' => false];
                    continue;
                }

                $find = $mysqli->prepare("SELECT per_id, per_estado, per_cupo_asignado, per_cupo_disponible FROM personal WHERE per_documento = ? AND cli_id = ? LIMIT 1");
                $find->bind_param('si', $cedula, $cli_id);
                $find->execute();
                $emp = $find->get_result()->fetch_assoc();
                if (!$emp) {
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => 'No encontrada en este cliente'];
                    $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => 'No encontrada en este cliente — se omite', 'aplica' => false];
                    continue;
                }

                if ($modo['modo'] === 'marca') {
                    // Actualización parcial: solo se tocan las marcas presentes con
                    // valor en el archivo (cuposMarcaFila ya viene filtrado de montos
                    // <= 0 por cupoValidarPorMarca) — una marca ausente en la fila deja
                    // el cupo existente sin tocar, no se pone en 0 (regla confirmada con
                    // el cliente).
                    if (!count($cuposMarcaFila)) {
                        $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Ninguna marca con cupo en el archivo'];
                        $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $emp['per_estado'], 'resultado' => 'Ninguna marca con cupo en el archivo — se omite', 'aplica' => false];
                        continue;
                    }

                    if ($soloPreview) {
                        $resumenMarcas = [];
                        foreach ($cuposMarcaFila as $mar_id => $monto) {
                            $resumenMarcas[] = (isset($nombresMarcaPorId[(int)$mar_id]) ? $nombresMarcaPorId[(int)$mar_id] : ('marca #' . $mar_id)) . ' → $' . number_format($monto, 2);
                        }
                        $detalle[] = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $emp['per_estado'], 'resultado' => 'Se actualizará: ' . implode(', ', $resumenMarcas), 'aplica' => true];
                        continue;
                    }

                    foreach ($cuposMarcaFila as $mar_id => $monto) {
                        $antes = cupoEmpleadoEnMarca($mysqli, $emp['per_id'], $mar_id);
                        cupoUpsertEmpleadoMarca($mysqli, $emp['per_id'], $mar_id, $monto);
                        $labelMarca = isset($nombresMarcaPorId[(int)$mar_id]) ? $nombresMarcaPorId[(int)$mar_id] : ('marca #' . $mar_id);
                        $campoTra = 'per_cupo_marca_' . $mar_id;
                        $antTxt = '$' . number_format($antes['asignado'], 2);
                        $nvoTxt = '$' . number_format($monto, 2);
                        $tra = $mysqli->prepare(
                            "INSERT INTO personal_trazabilidad (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                             VALUES (?, ?, ?, 'Cupo actualizado por carga masiva', ?, ?)"
                        );
                        $tra->bind_param('iisss', $emp['per_id'], $id_user_sesion, $campoTra, $antTxt, $nvoTxt);
                        $tra->execute();
                    }
                    $actualizados++;
                } else {
                    // cupo <= 0 ya se validó arriba (antes de la consulta $find), en
                    // el mismo punto donde lo hacía el archivo original.
                    if ($cupo_max > 0 && $cupo > $cupo_max) {
                        $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Cupo excede el máximo de la empresa ($' . number_format($cupo_max, 2) . ')'];
                        $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $emp['per_estado'], 'resultado' => 'Cupo excede el máximo de la empresa ($' . number_format($cupo_max, 2) . ') — se omite', 'aplica' => false];
                        continue;
                    }

                    $cupo_anterior   = (float)$emp['per_cupo_asignado'];
                    $consumido       = $cupo_anterior - (float)$emp['per_cupo_disponible'];
                    $cupo_disp_nuevo = max(0, $cupo - $consumido);

                    if ($soloPreview) {
                        $detalle[] = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $emp['per_estado'], 'resultado' => 'Se actualizará el cupo de $' . number_format($cupo_anterior, 2) . ' a $' . number_format($cupo, 2), 'aplica' => true];
                        continue;
                    }

                    $upd = $mysqli->prepare("UPDATE personal SET per_cupo_asignado = ?, per_cupo_disponible = ? WHERE per_id = ?");
                    $upd->bind_param('ddi', $cupo, $cupo_disp_nuevo, $emp['per_id']);
                    $upd->execute();

                    $antTxt = '$' . number_format($cupo_anterior, 2);
                    $nvoTxt = '$' . number_format($cupo, 2);
                    $tra = $mysqli->prepare(
                        "INSERT INTO personal_trazabilidad (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                         VALUES (?, ?, 'per_cupo_asignado', 'Cupo actualizado por carga masiva', ?, ?)"
                    );
                    $tra->bind_param('iiss', $emp['per_id'], $id_user_sesion, $antTxt, $nvoTxt);
                    $tra->execute();
                    $actualizados++;
                }

            } elseif ($accion === 'bloquear') {
                $find = $mysqli->prepare("SELECT per_id, per_estado FROM personal WHERE per_documento = ? AND cli_id = ? LIMIT 1");
                $find->bind_param('si', $cedula, $cli_id);
                $find->execute();
                $emp = $find->get_result()->fetch_assoc();
                if (!$emp) {
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => 'No encontrada en este cliente'];
                    $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => '—', 'resultado' => 'No encontrada en este cliente — se omite', 'aplica' => false];
                    continue;
                }
                if ($emp['per_estado'] === 'bloqueado') {
                    $omitidos[] = ['cedula' => $cedula, 'motivo' => 'Ya estaba bloqueada'];
                    $detalle[]  = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => 'bloqueado', 'resultado' => 'Esta persona ya se encuentra bloqueada — no se hará nada', 'aplica' => false];
                    continue;
                }

                if ($soloPreview) {
                    $detalle[] = ['cedula' => $cedula, 'nombre' => $nombre, 'estado_actual' => $emp['per_estado'], 'resultado' => 'Se bloqueará', 'aplica' => true];
                    continue;
                }

                $upd = $mysqli->prepare("UPDATE personal SET per_estado = 'bloqueado' WHERE per_id = ?");
                $upd->bind_param('i', $emp['per_id']);
                $upd->execute();

                $tra = $mysqli->prepare(
                    "INSERT INTO personal_trazabilidad (per_id, id_user, tra_campo, tra_campo_label, tra_valor_anterior, tra_valor_nuevo)
                     VALUES (?, ?, 'per_estado', 'Bloqueo por carga masiva', ?, 'bloqueado')"
                );
                $tra->bind_param('iis', $emp['per_id'], $id_user_sesion, $emp['per_estado']);
                $tra->execute();
                $bloqueados++;
            }
        }

        if ($soloPreview) {
            $aplicaran = 0;
            foreach ($detalle as $d) { if ($d['aplica']) $aplicaran++; }
            echo json_encode(['success' => true, 'preview' => true, 'detalle' => $detalle, 'total' => count($detalle), 'aplicaran' => $aplicaran]);
            break;
        }

        echo json_encode(['success' => true, 'resultados' => [
            'agregados' => $agregados, 'actualizados' => $actualizados, 'bloqueados' => $bloqueados, 'omitidos' => $omitidos
        ]]);
        break;

    // ── TAB: CONSUMOS ─────────────────────────────────────────────────────────
    case 'consumos_list':
        header('Content-Type: application/json');
        $cli_id = (int)($_GET['cli_id'] ?? 0);
        $stmt = $mysqli->prepare(
            "SELECT c.con_id, c.con_fecha, c.con_hora, p.per_nombre,
                    c.con_numero_tarjeta, c.con_valor_total, c.con_monto_convenio,
                    c.con_monto_externo, c.con_estado,
                    COALESCE(l.loc_nombre, l.loc_direccion, '—') AS local_nombre
             FROM consumo c
             JOIN personal p ON c.per_id = p.per_id
             LEFT JOIN local l ON c.loc_id = l.loc_id
             WHERE p.cli_id = ?
             ORDER BY c.con_fecha DESC, c.con_hora DESC
             LIMIT 300"
        );
        $stmt->bind_param('i', $cli_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ── TAB: ESTADOS DE CUENTA ────────────────────────────────────────────────
    case 'estado_cuenta_list':
        header('Content-Type: application/json');
        $cli_id = (int)($_GET['cli_id'] ?? 0);
        $stmt = $mysqli->prepare(
            "SELECT ec_id, ec_periodo_inicio, ec_periodo_fin, ec_monto_total,
                    ec_fecha_generacion, ec_estado_envio, ec_archivo_pdf
             FROM estado_cuenta WHERE cli_id = ? ORDER BY ec_id DESC"
        );
        $stmt->bind_param('i', $cli_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ── TAB: VENTAS DIFERIDAS (CL-D) ─────────────────────────────────────────
    case 'venta_diferida_list':
        header('Content-Type: application/json');
        $cli_id = (int)($_GET['cli_id'] ?? 0);
        $stmt = $mysqli->prepare(
            "SELECT vd.vd_id, vd.vd_descripcion, vd.vd_monto_total, vd.vd_num_cuotas,
                    vd.vd_cuotas_pagadas, vd.vd_monto_cuota, vd.vd_fecha_inicio, vd.vd_estado,
                    p.per_nombre, p.per_documento
             FROM venta_diferida vd
             JOIN personal p ON vd.per_id = p.per_id
             WHERE p.cli_id = ?
             ORDER BY vd.vd_id DESC"
        );
        $stmt->bind_param('i', $cli_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ── TAB: GIFT CARDS ───────────────────────────────────────────────────────
    case 'giftcard_list':
        header('Content-Type: application/json');
        $cli_id = (int)($_GET['cli_id'] ?? 0);
        $stmt = $mysqli->prepare(
            "SELECT lgc.lgc_id, lgc.lgc_fecha, lgc.lgc_cantidad, lgc.lgc_cupo_codigo,
                    lgc.lgc_periodo_facturacion,
                    u.name_user AS solicitante,
                    COUNT(cgc.cgc_id) AS total_generados,
                    SUM(cgc.cgc_estado = 'activo')    AS activos,
                    SUM(cgc.cgc_estado = 'consumido') AS consumidos,
                    SUM(cgc.cgc_estado = 'vencido')   AS vencidos,
                    SUM(cgc.cgc_estado = 'anulado')   AS anulados
             FROM lote_gift_card lgc
             JOIN usuario u ON lgc.id_user = u.id_user
             LEFT JOIN codigo_gift_card cgc ON lgc.lgc_id = cgc.lgc_id
             WHERE lgc.cli_id = ? OR u.cli_id = ?
             GROUP BY lgc.lgc_id
             ORDER BY lgc.lgc_fecha DESC"
        );
        $stmt->bind_param('ii', $cli_id, $cli_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'mensaje' => 'Acción no reconocida']);
        break;
}
?>
