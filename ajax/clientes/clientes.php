<?php
require_once "../../config/database.php";

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── LIST — tabla HTML para DataTables ────────────────────────────────────
    case 'list':
        $filtro_beneficio = $_GET['beneficio'] ?? '';
        $filtro_cartera   = $_GET['cartera']   ?? '';

        $where = [];
        if ($filtro_beneficio) $where[] = "cli_tipo_beneficio = '" . mysqli_real_escape_string($mysqli, $filtro_beneficio) . "'";
        if ($filtro_cartera)   $where[] = "cli_tipo_cartera   = '" . mysqli_real_escape_string($mysqli, $filtro_cartera)   . "'";
        $sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $result = mysqli_query($mysqli,
            "SELECT c.cli_id, c.cli_descripcion, c.cli_ciudad, c.cli_contacto,
                    c.cli_email, c.cli_telefono, c.cli_numero_convenio,
                    c.cli_tipo_beneficio, c.cli_valor_beneficio,
                    c.cli_tipo_cartera, c.cli_dia_corte,
                    (SELECT COUNT(*) FROM personal p WHERE p.cli_id = c.cli_id) AS total_personal,
                    (SELECT COUNT(*) FROM estado_cuenta ec WHERE ec.cli_id = c.cli_id) AS total_ec
             FROM cliente c $sql_where
             ORDER BY c.cli_descripcion ASC"
        );
        header('Content-Type: application/json');
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ── GET — datos JSON para modal editar ───────────────────────────────────
    case 'get':
        header('Content-Type: application/json');
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT * FROM cliente WHERE cli_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row  = $stmt->get_result()->fetch_assoc();
        echo $row
            ? json_encode(['success' => true, 'data' => $row])
            : json_encode(['success' => false, 'mensaje' => 'Cliente no encontrado']);
        break;

    // ── CREAR ─────────────────────────────────────────────────────────────────
    case 'crear':
        header('Content-Type: application/json');
        $desc = trim($_POST['cli_descripcion'] ?? '');
        if (empty($desc)) { echo json_encode(['success' => false, 'mensaje' => 'El nombre es requerido']); break; }

        $stmt = $mysqli->prepare(
            "INSERT INTO cliente
             (cli_descripcion, cli_numero_convenio, cli_ciudad, cli_contacto,
              cli_email, cli_email2, cli_telefono, cli_telefono2, cli_dia_corte,
              cli_tipo_beneficio, cli_valor_beneficio, cli_tipo_cartera, cli_comision)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
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

        $stmt->bind_param('ssssssssssdsd', $desc, $conv, $ciu, $cont, $em1, $em2, $tel1, $tel2, $dia, $tben, $vben, $tcar, $com);
        echo $stmt->execute()
            ? json_encode(['success' => true,  'mensaje' => 'Cliente creado exitosamente', 'id' => $mysqli->insert_id])
            : json_encode(['success' => false, 'mensaje' => 'Error: ' . $mysqli->error]);
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

        $stmt = $mysqli->prepare(
            "UPDATE cliente SET
              cli_descripcion=?, cli_numero_convenio=?, cli_ciudad=?, cli_contacto=?,
              cli_email=?, cli_email2=?, cli_telefono=?, cli_telefono2=?, cli_dia_corte=?,
              cli_tipo_beneficio=?, cli_valor_beneficio=?, cli_tipo_cartera=?, cli_comision=?
             WHERE cli_id=?"
        );
        $stmt->bind_param('ssssssssssdsdi', $desc, $conv, $ciu, $cont, $em1, $em2, $tel1, $tel2, $dia, $tben, $vben, $tcar, $com, $id);
        echo $stmt->execute()
            ? json_encode(['success' => true,  'mensaje' => 'Cliente actualizado'])
            : json_encode(['success' => false, 'mensaje' => 'Error: ' . $mysqli->error]);
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
             WHERE u.cli_id = ?
             GROUP BY lgc.lgc_id
             ORDER BY lgc.lgc_fecha DESC"
        );
        $stmt->bind_param('i', $cli_id);
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
