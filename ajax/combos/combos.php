<?php
date_default_timezone_set('America/Guayaquil');
session_start();
require_once "../../config/database.php";
require_once "../../helpers/session_helpers.php";
mysqli_query($mysqli, "SET time_zone = '-05:00'");

header('Content-Type: application/json');

if (empty($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── LIST — combos con franquicia y estado calculado ──────────────────────
    case 'list':
        $query = "SELECT ce.ce_id, ce.ce_nombre, ce.ce_descripcion, ce.ce_codigo, ce.ce_valor,
                         ce.ce_fecha_caducidad, ce.mar_id, m.mar_descripcion,
                         (CASE WHEN ce.ce_fecha_caducidad < CURDATE() THEN 'vencido' ELSE 'vigente' END) AS ce_estado,
                         (SELECT COUNT(*) FROM consumo c WHERE c.con_combo_id = ce.ce_id AND c.con_estado != 'anulado') AS total_usos
                  FROM combo_especial ce
                  JOIN marca m ON ce.mar_id = m.mar_id
                  ORDER BY ce.ce_fecha_creacion DESC";
        $r = mysqli_query($mysqli, $query);
        if (!$r) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al listar combos: ' . mysqli_error($mysqli)]);
            break;
        }
        $data = [];
        while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ── GET — un combo para editar ────────────────────────────────────────────
    case 'get':
        $ce_id = (int)($_GET['ce_id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT ce_id, ce_nombre, ce_descripcion, ce_codigo, ce_valor, ce_fecha_caducidad, mar_id FROM combo_especial WHERE ce_id = ?");
        $stmt->bind_param('i', $ce_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        echo $row
            ? json_encode(['success' => true, 'data' => $row])
            : json_encode(['success' => false, 'mensaje' => 'Combo no encontrado']);
        break;

    // ── CREAR ─────────────────────────────────────────────────────────────────
    case 'crear':
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $codigo      = strtoupper(trim($_POST['codigo'] ?? ''));
        $valor       = (float)($_POST['valor'] ?? 0);
        $caducidad   = trim($_POST['fecha_caducidad'] ?? '');
        $mar_id      = (int)($_POST['mar_id'] ?? 0);

        if ($nombre === '' || $codigo === '' || $valor <= 0 || $caducidad === '' || $mar_id === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Complete todos los campos obligatorios']);
            break;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $caducidad)) {
            echo json_encode(['success' => false, 'mensaje' => 'Fecha de caducidad inválida']);
            break;
        }

        $chk = $mysqli->prepare("SELECT ce_id FROM combo_especial WHERE ce_codigo = ?");
        $chk->bind_param('s', $codigo);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'mensaje' => 'Ya existe un combo con ese código']);
            break;
        }

        $idUserSesion = (int)$_SESSION['id_user'];
        $stmt = $mysqli->prepare(
            "INSERT INTO combo_especial (ce_nombre, ce_descripcion, ce_codigo, ce_valor, ce_fecha_caducidad, mar_id, id_user_creador)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssdsii', $nombre, $descripcion, $codigo, $valor, $caducidad, $mar_id, $idUserSesion);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al crear el combo']);
            break;
        }
        echo json_encode(['success' => true, 'mensaje' => 'Combo creado correctamente', 'ce_id' => $mysqli->insert_id]);
        break;

    // ── EDITAR — el código no se puede cambiar (ya pudo usarse en ventas) ────
    case 'editar':
        $ce_id       = (int)($_POST['ce_id'] ?? 0);
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $valor       = (float)($_POST['valor'] ?? 0);
        $caducidad   = trim($_POST['fecha_caducidad'] ?? '');
        $mar_id      = (int)($_POST['mar_id'] ?? 0);

        if (!$ce_id || $nombre === '' || $valor <= 0 || $caducidad === '' || $mar_id === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Complete todos los campos obligatorios']);
            break;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $caducidad)) {
            echo json_encode(['success' => false, 'mensaje' => 'Fecha de caducidad inválida']);
            break;
        }

        $stmt = $mysqli->prepare(
            "UPDATE combo_especial SET ce_nombre=?, ce_descripcion=?, ce_valor=?, ce_fecha_caducidad=?, mar_id=? WHERE ce_id=?"
        );
        $stmt->bind_param('ssdsii', $nombre, $descripcion, $valor, $caducidad, $mar_id, $ce_id);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar el combo']);
            break;
        }
        echo json_encode(['success' => true, 'mensaje' => 'Combo actualizado correctamente']);
        break;

    // ── MOVIMIENTOS — reporte general o filtrado por combo ───────────────────
    case 'movimientos':
        $ce_id        = (int)($_GET['ce_id'] ?? 0);
        $fecha_inicio = mysqli_real_escape_string($mysqli, $_GET['fecha_inicio'] ?? date('Y-m-01'));
        $fecha_fin    = mysqli_real_escape_string($mysqli, $_GET['fecha_fin']    ?? date('Y-m-d'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
            echo json_encode(['success' => false, 'mensaje' => 'Fechas inválidas']);
            break;
        }

        $where = "c.con_combo_id IS NOT NULL AND c.con_fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        if ($ce_id) {
            $where .= " AND c.con_combo_id = $ce_id";
        }

        $query = "SELECT c.con_id, c.con_fecha, c.con_hora, c.con_estado,
                         c.con_monto_convenio, c.con_monto_externo,
                         ce.ce_nombre, ce.ce_codigo,
                         p.per_nombre, p.per_documento,
                         cl.cli_descripcion,
                         u.name_user AS cajero_nombre,
                         COALESCE(l.loc_nombre, l.loc_direccion) AS local_nombre
                  FROM consumo c
                  JOIN combo_especial ce ON c.con_combo_id = ce.ce_id
                  LEFT JOIN personal p  ON c.per_id = p.per_id
                  LEFT JOIN cliente  cl ON p.cli_id = cl.cli_id
                  LEFT JOIN usuario  u  ON c.id_user = u.id_user
                  LEFT JOIN local    l  ON c.loc_id  = l.loc_id
                  WHERE $where
                  ORDER BY c.con_fecha DESC, c.con_id DESC";
        $r = mysqli_query($mysqli, $query);
        if (!$r) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al consultar movimientos: ' . mysqli_error($mysqli)]);
            break;
        }
        $data = [];
        while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}
