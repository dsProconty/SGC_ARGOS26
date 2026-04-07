<?php
session_start();
require_once '../../config/database.php';

if (empty($_SESSION['id_user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

$action  = $_GET['action'] ?? $_POST['action'] ?? '';
$rol     = $_SESSION['permisos_acceso'] ?? '';
$id_user = (int)$_SESSION['id_user'];

// ─────────────────────────────────────────────
// Helper: envío de email
// ─────────────────────────────────────────────
function enviar_email(string $para, string $asunto, string $cuerpo): void {
    if (!$para) return;
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: SGC ARGOS <no-reply@sgcargos.com>\r\n";
    @mail($para, $asunto, $cuerpo, $headers);
}

// ─────────────────────────────────────────────
// Helper: obtener email del Super Admin
// ─────────────────────────────────────────────
function get_superadmin_email($mysqli): string {
    $r = mysqli_query($mysqli, "SELECT email FROM usuario WHERE permisos_acceso='Super Admin' AND email IS NOT NULL AND email <> '' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['email'];
    return '';
}

switch ($action) {

    // ══════════════════════════════════════════════════
    // LIST LOTES — Super Admin ve todos los lotes creados
    // ══════════════════════════════════════════════════
    case 'list_lotes':
        header('Content-Type: text/html');
        $query = "SELECT l.lgc_id, l.lgc_cantidad, l.lgc_cupo_codigo, l.lgc_fecha, l.lgc_periodo_facturacion,
                         u.name_user,
                         COALESCE(SUM(CASE WHEN c.cgc_estado = 'activo'    THEN 1 ELSE 0 END), 0) AS disponibles,
                         COALESCE(SUM(CASE WHEN c.cgc_estado = 'consumido' THEN 1 ELSE 0 END), 0) AS consumidos
                  FROM lote_gift_card l
                  JOIN usuario u ON l.id_user = u.id_user
                  LEFT JOIN codigo_gift_card c ON l.lgc_id = c.lgc_id
                  GROUP BY l.lgc_id
                  ORDER BY l.lgc_id DESC";
        $result = mysqli_query($mysqli, $query);
        ?>
        <table id="table_lotes" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>No.</th><th>Fecha Creación</th><th>Período Facturación</th>
                    <th>Cantidad</th><th>Cupo x Código</th><th>Disponibles</th>
                    <th>Consumidos</th><th>Creado por</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $no; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['lgc_fecha'])); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['lgc_periodo_facturacion'])); ?></td>
                    <td><?php echo $row['lgc_cantidad']; ?></td>
                    <td>$<?php echo number_format($row['lgc_cupo_codigo'], 2); ?></td>
                    <td><span class="badge badge-success"><?php echo $row['disponibles']; ?></span></td>
                    <td><span class="badge badge-secondary"><?php echo $row['consumidos']; ?></span></td>
                    <td><?php echo htmlspecialchars($row['name_user']); ?></td>
                    <td>
                        <a class="btn btn-info btn-md" title="Ver Códigos"
                           onclick="ver_codigos(<?php echo $row['lgc_id']; ?>, '<?php echo date('d/m/Y', strtotime($row['lgc_periodo_facturacion'])); ?>')">
                            <i style="color:#fff" class="icon dripicons-list"></i>
                        </a>
                    </td>
                </tr>
                <?php $no++; } ?>
            </tbody>
        </table>
        <?php
        break;

    // ══════════════════════════════════════════════════
    // LIST CODIGOS — códigos de un lote
    // ══════════════════════════════════════════════════
    case 'list_codigos':
        header('Content-Type: text/html');
        $lgc_id = (int)($_GET['lgc_id'] ?? 0);
        $stmt   = $mysqli->prepare("SELECT cgc_id, cgc_codigo, cgc_cupo_inicial, cgc_cupo_disponible,
                                           cgc_estado, cgc_fecha_activacion, cgc_fecha_caducidad, cgc_fecha_uso
                                    FROM codigo_gift_card WHERE lgc_id = ? ORDER BY cgc_id ASC");
        $stmt->bind_param('i', $lgc_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $badges = ['activo' => 'success', 'consumido' => 'secondary', 'vencido' => 'warning', 'anulado' => 'danger'];
        ?>
        <table id="table_codigos" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>No.</th><th>Código</th><th>Cupo Inicial</th><th>Cupo Disponible</th>
                    <th>Estado</th><th>Activación</th><th>Caducidad</th><th>Fecha Uso</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = $result->fetch_assoc()) {
                    $color = $badges[$row['cgc_estado']] ?? 'info'; ?>
                <tr>
                    <td><?php echo $no; ?></td>
                    <td><code><?php echo htmlspecialchars($row['cgc_codigo']); ?></code></td>
                    <td>$<?php echo number_format($row['cgc_cupo_inicial'], 2); ?></td>
                    <td>$<?php echo number_format($row['cgc_cupo_disponible'], 2); ?></td>
                    <td><span class="badge badge-<?php echo $color; ?>"><?php echo $row['cgc_estado']; ?></span></td>
                    <td><?php echo $row['cgc_fecha_activacion'] ? date('d/m/Y H:i', strtotime($row['cgc_fecha_activacion'])) : '-'; ?></td>
                    <td>
                        <?php if ($row['cgc_fecha_caducidad']): ?>
                            <?php $vencido = $row['cgc_fecha_caducidad'] < date('Y-m-d'); ?>
                            <span class="badge badge-<?php echo $vencido ? 'danger' : 'info'; ?>">
                                <?php echo date('d/m/Y', strtotime($row['cgc_fecha_caducidad'])); ?>
                            </span>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td><?php echo $row['cgc_fecha_uso'] ? date('d/m/Y H:i', strtotime($row['cgc_fecha_uso'])) : '-'; ?></td>
                </tr>
                <?php $no++; } ?>
            </tbody>
        </table>
        <?php
        break;

    // ══════════════════════════════════════════════════
    // CREAR LOTE — Super Admin crea lote directo
    // ══════════════════════════════════════════════════
    case 'crear_lote':
        header('Content-Type: application/json');
        if ($rol !== 'Super Admin') {
            echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']);
            break;
        }
        $cantidad  = (int)($_POST['cantidad'] ?? 0);
        $cupo      = (float)($_POST['cupo_codigo'] ?? 0);
        $periodo   = trim($_POST['periodo_facturacion'] ?? '');
        $caducidad = trim($_POST['fecha_caducidad'] ?? '');

        if ($cantidad <= 0 || $cantidad > 1000 || $cupo <= 0 || !$periodo || !$caducidad) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos o inválidos']);
            break;
        }

        $stmt = $mysqli->prepare("INSERT INTO lote_gift_card (id_user, lgc_cantidad, lgc_cupo_codigo, lgc_periodo_facturacion) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iids', $id_user, $cantidad, $cupo, $periodo);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al crear lote']);
            break;
        }
        $lgc_id = $mysqli->insert_id;
        $now    = date('Y-m-d H:i:s');
        $errors = 0;

        $ins = $mysqli->prepare("INSERT INTO codigo_gift_card (lgc_id, cgc_codigo, cgc_cupo_inicial, cgc_cupo_disponible, cgc_estado, cgc_fecha_activacion, cgc_fecha_caducidad) VALUES (?, ?, ?, ?, 'activo', ?, ?)");
        for ($i = 0; $i < $cantidad; $i++) {
            $codigo = strtoupper(bin2hex(random_bytes(6)));
            $ins->bind_param('isddss', $lgc_id, $codigo, $cupo, $cupo, $now, $caducidad);
            if (!$ins->execute()) $errors++;
        }
        $generados = $cantidad - $errors;
        echo json_encode(['success' => true, 'mensaje' => "Lote creado con $generados códigos generados."]);
        break;

    // ══════════════════════════════════════════════════
    // SOLICITAR LOTE — cliente solicita códigos (PENDING)
    // ══════════════════════════════════════════════════
    case 'solicitar_lote':
        header('Content-Type: application/json');
        $roles_cliente = ['cliente_giftcard', 'empresa_cliente'];
        if (!in_array($rol, $roles_cliente)) {
            echo json_encode(['success' => false, 'mensaje' => 'Sin permisos para solicitar lotes']);
            break;
        }
        $cantidad  = (int)($_POST['cantidad'] ?? 0);
        $cupo      = (float)($_POST['cupo_codigo'] ?? 0);
        $periodo   = trim($_POST['periodo_facturacion'] ?? '');
        $caducidad = trim($_POST['fecha_caducidad'] ?? '');

        if ($cantidad <= 0 || $cantidad > 1000 || $cupo <= 0 || !$periodo || !$caducidad) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos o inválidos']);
            break;
        }

        $stmt = $mysqli->prepare("INSERT INTO giftcard_solicitud (id_user, sol_cantidad, sol_cupo_codigo, sol_periodo_facturacion, sol_fecha_caducidad, sol_estado) VALUES (?, ?, ?, ?, ?, 'PENDING')");
        $stmt->bind_param('iidss', $id_user, $cantidad, $cupo, $periodo, $caducidad);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al registrar solicitud']);
            break;
        }
        $sol_id = $mysqli->insert_id;

        $ru = $mysqli->prepare("SELECT name_user, email FROM usuario WHERE id_user = ?");
        $ru->bind_param('i', $id_user);
        $ru->execute();
        $solicitante = $ru->get_result()->fetch_assoc();

        $admin_email  = get_superadmin_email($mysqli);
        $asunto_admin = "Nueva solicitud de Gift Cards #$sol_id";
        $cuerpo_admin = "<h3>Nueva Solicitud de Gift Cards</h3>
            <p><strong>Solicitante:</strong> " . htmlspecialchars($solicitante['name_user']) . "</p>
            <p><strong>Cantidad:</strong> $cantidad códigos</p>
            <p><strong>Cupo por código:</strong> \$$cupo</p>
            <p><strong>Período:</strong> " . date('d/m/Y', strtotime($periodo)) . "</p>
            <p><strong>Caducidad:</strong> " . date('d/m/Y', strtotime($caducidad)) . "</p>
            <p>Ingrese al sistema para aprobar o rechazar esta solicitud.</p>";
        enviar_email($admin_email, $asunto_admin, $cuerpo_admin);

        $asunto_sol = "Solicitud de Gift Cards recibida — #$sol_id";
        $cuerpo_sol = "<h3>Tu solicitud fue recibida</h3>
            <p>Hola <strong>" . htmlspecialchars($solicitante['name_user']) . "</strong>,</p>
            <p>Tu solicitud de <strong>$cantidad códigos</strong> de Gift Card está siendo revisada por el administrador.</p>
            <p>Te notificaremos cuando sea aprobada o rechazada.</p>
            <p><em>SGC ARGOS</em></p>";
        enviar_email($solicitante['email'] ?? '', $asunto_sol, $cuerpo_sol);

        echo json_encode(['success' => true, 'mensaje' => 'Solicitud enviada. El administrador la revisará pronto.']);
        break;

    // ══════════════════════════════════════════════════
    // LIST SOLICITUDES — Super Admin ve todas las solicitudes
    // ══════════════════════════════════════════════════
    case 'list_solicitudes':
        header('Content-Type: text/html');
        if ($rol !== 'Super Admin') {
            echo '<div class="alert alert-danger">Sin permisos</div>';
            break;
        }
        $query  = "SELECT s.sol_id, s.sol_cantidad, s.sol_cupo_codigo, s.sol_periodo_facturacion,
                          s.sol_fecha_caducidad, s.sol_estado, s.sol_fecha_solicitud,
                          u.name_user, u.email
                   FROM giftcard_solicitud s
                   JOIN usuario u ON s.id_user = u.id_user
                   ORDER BY FIELD(s.sol_estado,'PENDING','APPROVED','REJECTED'), s.sol_fecha_solicitud DESC";
        $result = mysqli_query($mysqli, $query);
        $badges = ['PENDING' => 'warning', 'APPROVED' => 'success', 'REJECTED' => 'danger'];
        $labels = ['PENDING' => 'Pendiente', 'APPROVED' => 'Aprobado', 'REJECTED' => 'Rechazado'];
        ?>
        <table id="table_solicitudes" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>#</th><th>Solicitante</th><th>Cantidad</th><th>Cupo x Cód.</th>
                    <th>Período</th><th>Caducidad</th><th>Fecha Solicitud</th><th>Estado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) {
                $b = $badges[$row['sol_estado']] ?? 'secondary';
                $l = $labels[$row['sol_estado']] ?? $row['sol_estado']; ?>
                <tr>
                    <td><?php echo $row['sol_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name_user']); ?></td>
                    <td><?php echo $row['sol_cantidad']; ?></td>
                    <td>$<?php echo number_format($row['sol_cupo_codigo'], 2); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['sol_periodo_facturacion'])); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['sol_fecha_caducidad'])); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['sol_fecha_solicitud'])); ?></td>
                    <td><span class="badge badge-<?php echo $b; ?>"><?php echo $l; ?></span></td>
                    <td>
                        <?php if ($row['sol_estado'] === 'PENDING'): ?>
                        <button class="btn btn-sm btn-success mr-1" style="color:#fff;"
                            onclick="previsualizarSolicitud(<?php echo $row['sol_id']; ?>, 'APPROVE')"
                            title="Aprobar">
                            <i class="icon dripicons-checkmark"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" style="color:#fff;"
                            onclick="previsualizarSolicitud(<?php echo $row['sol_id']; ?>, 'REJECT')"
                            title="Rechazar">
                            <i class="icon dripicons-cross"></i>
                        </button>
                        <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary"
                            onclick="verHistorial(<?php echo $row['sol_id']; ?>)"
                            title="Ver historial">
                            <i class="icon dripicons-clock"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php
        break;

    // ══════════════════════════════════════════════════
    // MIS SOLICITUDES — cliente ve sus propias solicitudes
    // ══════════════════════════════════════════════════
    case 'mis_solicitudes':
        header('Content-Type: text/html');
        $stmt = $mysqli->prepare("SELECT sol_id, sol_cantidad, sol_cupo_codigo, sol_periodo_facturacion,
                                         sol_fecha_caducidad, sol_estado, sol_fecha_solicitud
                                  FROM giftcard_solicitud WHERE id_user = ? ORDER BY sol_fecha_solicitud DESC");
        $stmt->bind_param('i', $id_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $badges = ['PENDING' => 'warning', 'APPROVED' => 'success', 'REJECTED' => 'danger'];
        $labels = ['PENDING' => 'Pendiente', 'APPROVED' => 'Aprobado', 'REJECTED' => 'Rechazado'];
        $rows   = $result->fetch_all(MYSQLI_ASSOC);
        if (empty($rows)): ?>
            <div class="text-center py-5 text-muted">
                <i class="icon dripicons-inbox" style="font-size:2.5rem; display:block; margin-bottom:10px; opacity:.4;"></i>
                No tienes solicitudes aún. Usa el botón <strong>Solicitar Códigos</strong> para comenzar.
            </div>
        <?php else: ?>
        <div class="gc-timeline">
        <?php foreach ($rows as $row):
            $b       = $badges[$row['sol_estado']] ?? 'secondary';
            $l       = $labels[$row['sol_estado']] ?? $row['sol_estado'];
            $color_l = $row['sol_estado'] === 'APPROVED' ? '#28a745' : ($row['sol_estado'] === 'REJECTED' ? '#dc3545' : '#ffc107'); ?>
            <div class="gc-timeline-item mb-3 p-3 border rounded" style="border-left: 4px solid <?php echo $color_l; ?> !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Solicitud #<?php echo $row['sol_id']; ?></strong>
                        &nbsp;<span class="badge badge-<?php echo $b; ?>"><?php echo $l; ?></span>
                    </div>
                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($row['sol_fecha_solicitud'])); ?></small>
                </div>
                <div class="mt-2 row">
                    <div class="col-sm-3"><small class="text-muted">Cantidad</small><br><strong><?php echo $row['sol_cantidad']; ?> códigos</strong></div>
                    <div class="col-sm-3"><small class="text-muted">Cupo x código</small><br><strong>$<?php echo number_format($row['sol_cupo_codigo'], 2); ?></strong></div>
                    <div class="col-sm-3"><small class="text-muted">Período</small><br><strong><?php echo date('d/m/Y', strtotime($row['sol_periodo_facturacion'])); ?></strong></div>
                    <div class="col-sm-3"><small class="text-muted">Caducidad</small><br><strong><?php echo date('d/m/Y', strtotime($row['sol_fecha_caducidad'])); ?></strong></div>
                </div>
                <?php if ($row['sol_estado'] === 'PENDING'): ?>
                <div class="mt-2"><small class="text-warning"><i class="icon dripicons-clock"></i> En espera de aprobación del administrador.</small></div>
                <?php elseif ($row['sol_estado'] === 'APPROVED'): ?>
                <div class="mt-2"><small class="text-success"><i class="icon dripicons-checkmark"></i> ¡Aprobada! Tus códigos ya están activos y listos para usar en el POS.</small></div>
                <?php elseif ($row['sol_estado'] === 'REJECTED'): ?>
                <div class="mt-2"><small class="text-danger"><i class="icon dripicons-cross"></i> Rechazada. Contacta al administrador para más información.</small></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif;
        break;

    // ══════════════════════════════════════════════════
    // GET SOLICITUD — datos para modal de preview
    // ══════════════════════════════════════════════════
    case 'get_solicitud':
        header('Content-Type: application/json');
        if ($rol !== 'Super Admin') { echo json_encode(['success' => false]); break; }
        $sol_id = (int)($_GET['sol_id'] ?? 0);
        $stmt   = $mysqli->prepare("SELECT s.*, u.name_user, u.email
                                    FROM giftcard_solicitud s JOIN usuario u ON s.id_user = u.id_user
                                    WHERE s.sol_id = ?");
        $stmt->bind_param('i', $sol_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) { echo json_encode(['success' => false, 'mensaje' => 'Solicitud no encontrada']); break; }
        echo json_encode(['success' => true, 'data' => $row]);
        break;

    // ══════════════════════════════════════════════════
    // APROBAR SOLICITUD — transacción atómica
    // ══════════════════════════════════════════════════
    case 'aprobar_solicitud':
        header('Content-Type: application/json');
        if ($rol !== 'Super Admin') { echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']); break; }

        $sol_id = (int)($_POST['sol_id'] ?? 0);
        $notas  = trim($_POST['notas'] ?? '');

        $stmt = $mysqli->prepare("SELECT s.*, u.name_user, u.email FROM giftcard_solicitud s JOIN usuario u ON s.id_user = u.id_user WHERE s.sol_id = ? AND s.sol_estado = 'PENDING'");
        $stmt->bind_param('i', $sol_id);
        $stmt->execute();
        $sol = $stmt->get_result()->fetch_assoc();

        if (!$sol) { echo json_encode(['success' => false, 'mensaje' => 'Solicitud no encontrada o ya procesada']); break; }

        $mysqli->begin_transaction();
        try {
            // 1. Crear lote
            $s1 = $mysqli->prepare("INSERT INTO lote_gift_card (id_user, lgc_cantidad, lgc_cupo_codigo, lgc_periodo_facturacion) VALUES (?, ?, ?, ?)");
            $s1->bind_param('iids', $sol['id_user'], $sol['sol_cantidad'], $sol['sol_cupo_codigo'], $sol['sol_periodo_facturacion']);
            if (!$s1->execute()) throw new Exception('Error al crear lote');
            $lgc_id = $mysqli->insert_id;

            // 2. Generar códigos activos
            $now = date('Y-m-d H:i:s');
            $s2  = $mysqli->prepare("INSERT INTO codigo_gift_card (lgc_id, cgc_codigo, cgc_cupo_inicial, cgc_cupo_disponible, cgc_estado, cgc_fecha_activacion, cgc_fecha_caducidad) VALUES (?, ?, ?, ?, 'activo', ?, ?)");
            for ($i = 0; $i < $sol['sol_cantidad']; $i++) {
                $codigo = strtoupper(bin2hex(random_bytes(6)));
                $s2->bind_param('isddss', $lgc_id, $codigo, $sol['sol_cupo_codigo'], $sol['sol_cupo_codigo'], $now, $sol['sol_fecha_caducidad']);
                if (!$s2->execute()) throw new Exception('Error al generar código');
            }

            // 3. Actualizar solicitud → APPROVED
            $s3 = $mysqli->prepare("UPDATE giftcard_solicitud SET sol_estado='APPROVED', sol_lgc_id=? WHERE sol_id=?");
            $s3->bind_param('ii', $lgc_id, $sol_id);
            if (!$s3->execute()) throw new Exception('Error al actualizar solicitud');

            // 4. Historial de auditoría
            $s4 = $mysqli->prepare("INSERT INTO giftcard_approval_history (sol_id, admin_id, aph_accion, aph_notas) VALUES (?, ?, 'APPROVE', ?)");
            $s4->bind_param('iis', $sol_id, $id_user, $notas);
            if (!$s4->execute()) throw new Exception('Error al registrar historial');

            $mysqli->commit();

            // 5. Emails (fuera de transacción)
            $admin_email = get_superadmin_email($mysqli);
            enviar_email($admin_email, "Solicitud #$sol_id aprobada",
                "<h3>Confirmación de Aprobación</h3><p>Aprobaste la solicitud <strong>#$sol_id</strong> de {$sol['name_user']}.</p><p>Se generaron <strong>{$sol['sol_cantidad']} códigos</strong> de \${$sol['sol_cupo_codigo']} c/u.</p>");

            enviar_email($sol['email'], "¡Tu solicitud de Gift Cards fue aprobada! — #$sol_id",
                "<h3>¡Buenas noticias!</h3><p>Hola <strong>{$sol['name_user']}</strong>,</p><p>Tu solicitud de <strong>{$sol['sol_cantidad']} códigos</strong> de Gift Card ha sido <strong>aprobada</strong>.</p><p>Tus códigos ya están <strong>activos y listos para usar</strong> en el punto de venta.</p><p><em>SGC ARGOS</em></p>");

            echo json_encode(['success' => true, 'mensaje' => "Solicitud aprobada. Se generaron {$sol['sol_cantidad']} códigos."]);

        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
        }
        break;

    // ══════════════════════════════════════════════════
    // RECHAZAR SOLICITUD — transacción atómica
    // ══════════════════════════════════════════════════
    case 'rechazar_solicitud':
        header('Content-Type: application/json');
        if ($rol !== 'Super Admin') { echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']); break; }

        $sol_id = (int)($_POST['sol_id'] ?? 0);
        $notas  = trim($_POST['notas'] ?? '');

        $stmt = $mysqli->prepare("SELECT s.*, u.name_user, u.email FROM giftcard_solicitud s JOIN usuario u ON s.id_user = u.id_user WHERE s.sol_id = ? AND s.sol_estado = 'PENDING'");
        $stmt->bind_param('i', $sol_id);
        $stmt->execute();
        $sol = $stmt->get_result()->fetch_assoc();

        if (!$sol) { echo json_encode(['success' => false, 'mensaje' => 'Solicitud no encontrada o ya procesada']); break; }

        $mysqli->begin_transaction();
        try {
            $s1 = $mysqli->prepare("UPDATE giftcard_solicitud SET sol_estado='REJECTED' WHERE sol_id=?");
            $s1->bind_param('i', $sol_id);
            if (!$s1->execute()) throw new Exception('Error al rechazar solicitud');

            $s2 = $mysqli->prepare("INSERT INTO giftcard_approval_history (sol_id, admin_id, aph_accion, aph_notas) VALUES (?, ?, 'REJECT', ?)");
            $s2->bind_param('iis', $sol_id, $id_user, $notas);
            if (!$s2->execute()) throw new Exception('Error al registrar historial');

            $mysqli->commit();

            $admin_email = get_superadmin_email($mysqli);
            enviar_email($admin_email, "Solicitud #$sol_id rechazada",
                "<h3>Confirmación de Rechazo</h3><p>Rechazaste la solicitud <strong>#$sol_id</strong> de {$sol['name_user']}.</p>" . ($notas ? "<p><strong>Motivo:</strong> $notas</p>" : ''));

            $motivo_html = $notas ? "<p><strong>Motivo:</strong> $notas</p>" : '<p>Para más información, contacta al administrador.</p>';
            enviar_email($sol['email'], "Solicitud de Gift Cards rechazada — #$sol_id",
                "<h3>Solicitud no aprobada</h3><p>Hola <strong>{$sol['name_user']}</strong>,</p><p>Lamentamos informarte que tu solicitud <strong>#$sol_id</strong> fue <strong>rechazada</strong>.</p>$motivo_html<p><em>SGC ARGOS</em></p>");

            echo json_encode(['success' => true, 'mensaje' => 'Solicitud rechazada correctamente.']);

        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
        }
        break;

    // ══════════════════════════════════════════════════
    // VER HISTORIAL — auditoría de una solicitud
    // ══════════════════════════════════════════════════
    case 'ver_historial':
        header('Content-Type: application/json');
        if ($rol !== 'Super Admin') { echo json_encode(['success' => false]); break; }
        $sol_id = (int)($_GET['sol_id'] ?? 0);
        $stmt   = $mysqli->prepare("SELECT h.aph_accion, h.aph_notas, h.aph_timestamp, u.name_user
                                    FROM giftcard_approval_history h JOIN usuario u ON h.admin_id = u.id_user
                                    WHERE h.sol_id = ? ORDER BY h.aph_timestamp DESC");
        $stmt->bind_param('i', $sol_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ══════════════════════════════════════════════════
    // VALIDAR CODIGO — POS (requiere solicitud APPROVED)
    // ══════════════════════════════════════════════════
    case 'validar_codigo':
        header('Content-Type: application/json');
        $codigo = strtoupper(trim($_GET['codigo'] ?? ''));
        if ($codigo === '') { echo json_encode(['success' => false, 'mensaje' => 'Ingrese un código']); break; }

        // Solo permite usar códigos cuya solicitud fue APPROVED
        $stmt = $mysqli->prepare(
            "SELECT c.cgc_id, c.cgc_codigo, c.cgc_cupo_disponible, c.cgc_estado, c.cgc_fecha_caducidad
             FROM codigo_gift_card c
             JOIN lote_gift_card l ON c.lgc_id = l.lgc_id
             JOIN giftcard_solicitud s ON l.lgc_id = s.sol_lgc_id
             WHERE c.cgc_codigo = ? AND s.sol_estado = 'APPROVED'
             LIMIT 1"
        );
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $gc = $stmt->get_result()->fetch_assoc();

        if (!$gc) {
            // Verificar si el código existe pero la solicitud no está aprobada
            $chk = $mysqli->prepare("SELECT cgc_estado FROM codigo_gift_card WHERE cgc_codigo = ? LIMIT 1");
            $chk->bind_param('s', $codigo);
            $chk->execute();
            $existe = $chk->get_result()->fetch_assoc();
            if ($existe) {
                echo json_encode(['success' => false, 'mensaje' => 'Código no disponible — solicitud pendiente de aprobación']);
            } else {
                echo json_encode(['success' => false, 'mensaje' => 'Código no encontrado']);
            }
            break;
        }

        if ($gc['cgc_estado'] !== 'activo') {
            echo json_encode(['success' => false, 'mensaje' => 'Código ' . $gc['cgc_estado'] . ' — no disponible']);
            break;
        }

        if ($gc['cgc_fecha_caducidad'] && $gc['cgc_fecha_caducidad'] < date('Y-m-d')) {
            $upd = $mysqli->prepare("UPDATE codigo_gift_card SET cgc_estado='vencido' WHERE cgc_id=?");
            $upd->bind_param('i', $gc['cgc_id']);
            $upd->execute();
            echo json_encode(['success' => false, 'mensaje' => 'Código vencido desde ' . date('d/m/Y', strtotime($gc['cgc_fecha_caducidad']))]);
            break;
        }

        echo json_encode([
            'success'         => true,
            'cgc_id'          => $gc['cgc_id'],
            'cgc_codigo'      => $gc['cgc_codigo'],
            'saldo'           => (float)$gc['cgc_cupo_disponible'],
            'fecha_caducidad' => $gc['cgc_fecha_caducidad'] ? date('d/m/Y', strtotime($gc['cgc_fecha_caducidad'])) : 'Sin caducidad'
        ]);
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}
?>
