<?php
// Suprimir warnings/notices en producción para no corromper respuestas JSON
error_reporting(0);
ob_start();

// Fecha/hora de Ecuador (sin horario de verano) para date() en PHP y para
// las columnas DATETIME DEFAULT CURRENT_TIMESTAMP (aph_timestamp,
// lgc_fecha, sol_fecha_solicitud), que sin esto usan el timezone del
// servidor de MySQL y quedaban una hora adelantadas.
date_default_timezone_set('America/Guayaquil');

session_start();
require_once '../../config/database.php';
require_once '../../services/giftcard_solicitud.php';
mysqli_query($mysqli, "SET time_zone = '-05:00'");

if (empty($_SESSION['id_user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

$action  = $_GET['action'] ?? $_POST['action'] ?? '';
$rol     = $_SESSION['permisos_acceso'] ?? '';
$id_user = (int)$_SESSION['id_user'];

// Acepta rol legacy O el perfil nuevo asignado (per_id -> perfil.per_nombre)
$nombrePerfilGC = $rol;
if (strtolower($rol) !== 'super admin') {
    $qPerfilGC2 = $mysqli->prepare(
        "SELECT p.per_nombre FROM usuario u JOIN perfil p ON u.per_id = p.per_id WHERE u.id_user = ?"
    );
    $qPerfilGC2->bind_param('i', $id_user);
    $qPerfilGC2->execute();
    $rowPerfilGC2 = $qPerfilGC2->get_result()->fetch_assoc();
    if ($rowPerfilGC2) {
        $nombrePerfilGC = $rowPerfilGC2['per_nombre'];
    }
}
$esSuperAdminGC = (strtolower($nombrePerfilGC) === 'super admin');

// "Cliente" de Gift Cards: empresa dueña de sus propios códigos (rol legacy
// o perfil 'Empresa Cliente'/'Cliente GiftCard'). Se identifica por NOMBRE
// DE PERFIL, no solo por tener cli_id — porque ahora personal interno
// (Financiero, Supervisor, etc.) también puede tener cli_id asignado para
// PER-01 (ver $esEmpresaStaffGC) sin ser el dueño de la empresa.
$rolesClienteExternoGC = ['cliente_giftcard', 'empresa_cliente', 'cliente giftcard', 'empresa cliente'];
$esClienteGC = !$esSuperAdminGC && (in_array(strtolower($rol), $rolesClienteExternoGC)
    || in_array(strtolower($nombrePerfilGC), $rolesClienteExternoGC));

// PER-01: personal interno con el módulo Gift Card habilitado y una empresa
// vinculada (cli_id) ve, en modo SOLO LECTURA, las solicitudes y lotes de esa
// empresa — nunca puede aprobar, rechazar ni crear lotes (exclusivo de
// $esSuperAdminGC).
$esEmpresaStaffGC = !$esSuperAdminGC && !$esClienteGC && !empty($_SESSION['cli_id']);
$cliIdSesionGC     = (int)($_SESSION['cli_id'] ?? 0);

// ─────────────────────────────────────────────
// Helper: envío de email
// ─────────────────────────────────────────────
function enviar_email($para, $asunto, $cuerpo) {
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
    $r = mysqli_query($mysqli, "SELECT u.email FROM usuario u
                                 LEFT JOIN perfil p ON u.per_id = p.per_id
                                 WHERE (u.permisos_acceso = 'Super Admin' OR p.per_nombre = 'Super Admin')
                                   AND u.email IS NOT NULL AND u.email <> '' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['email'];
    return '';
}

switch ($action) {

    // ══════════════════════════════════════════════════
    // LIST LOTES — Super Admin ve todos los lotes creados; personal interno
    // de solo lectura (PER-01, $esEmpresaStaffGC) solo ve los de su empresa.
    // ══════════════════════════════════════════════════
    case 'list_lotes':
        header('Content-Type: text/html');
        if (!$esSuperAdminGC && !$esEmpresaStaffGC) {
            echo '<div class="alert alert-danger">Sin permisos</div>';
            break;
        }
        $query = "SELECT l.lgc_id, l.lgc_cantidad, l.lgc_cupo_codigo, l.lgc_fecha, l.lgc_periodo_facturacion,
                         u.name_user,
                         COALESCE(c_directo.cli_descripcion, c_usuario.cli_descripcion, '—') AS cliente_nombre,
                         COALESCE(SUM(CASE WHEN c.cgc_estado = 'activo'    THEN 1 ELSE 0 END), 0) AS disponibles,
                         COALESCE(SUM(CASE WHEN c.cgc_estado = 'consumido' THEN 1 ELSE 0 END), 0) AS consumidos
                  FROM lote_gift_card l
                  JOIN usuario u ON l.id_user = u.id_user
                  LEFT JOIN cliente c_directo ON l.cli_id = c_directo.cli_id
                  LEFT JOIN cliente c_usuario ON u.cli_id = c_usuario.cli_id
                  LEFT JOIN codigo_gift_card c ON l.lgc_id = c.lgc_id";
        if ($esEmpresaStaffGC) {
            $query .= " WHERE COALESCE(l.cli_id, u.cli_id) = $cliIdSesionGC";
        }
        $query .= " GROUP BY l.lgc_id ORDER BY l.lgc_id DESC";
        $result = mysqli_query($mysqli, $query);
        ?>
        <table id="table_lotes" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>No.</th><th>Fecha Creación</th><th>Cliente</th><th>Período Facturación</th>
                    <th>Cantidad</th><th>Cupo x Código</th><th>Disponibles</th>
                    <th>Consumidos</th><th>Creado por</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $no; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['lgc_fecha'])); ?></td>
                    <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['lgc_periodo_facturacion'])); ?></td>
                    <td><?php echo $row['lgc_cantidad']; ?></td>
                    <td>$<?php echo number_format($row['lgc_cupo_codigo'], 2); ?></td>
                    <td><span class="badge badge-success"><?php echo $row['disponibles']; ?></span></td>
                    <td><span class="badge badge-secondary"><?php echo $row['consumidos']; ?></span></td>
                    <td><?php echo htmlspecialchars($row['name_user']); ?></td>
                    <td>
                        <a class="btn btn-info btn-md mr-1" title="Ver Códigos"
                           onclick="ver_codigos(<?php echo $row['lgc_id']; ?>, '<?php echo date('d/m/Y', strtotime($row['lgc_periodo_facturacion'])); ?>')">
                            <i style="color:#fff" class="icon dripicons-list"></i>
                        </a>
                        <?php if ($esSuperAdminGC): ?>
                        <a class="btn btn-outline-secondary btn-md" title="Ver historial de aprobación"
                           onclick="verHistorialLote(<?php echo $row['lgc_id']; ?>)">
                            <i class="icon dripicons-clock"></i>
                        </a>
                        <?php endif; ?>
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
        if (!$esSuperAdminGC && !$esEmpresaStaffGC) {
            echo '<div class="alert alert-danger">Sin permisos</div>';
            break;
        }
        $lgc_id = (int)($_GET['lgc_id'] ?? 0);
        if ($esEmpresaStaffGC) {
            // PER-01: solo lectura, y solo de los códigos de un lote de SU empresa.
            $chkLote = mysqli_query($mysqli, "SELECT COALESCE(l.cli_id, u.cli_id) AS lote_cli_id
                                               FROM lote_gift_card l JOIN usuario u ON l.id_user = u.id_user
                                               WHERE l.lgc_id = $lgc_id");
            $rowChk  = $chkLote ? mysqli_fetch_assoc($chkLote) : null;
            if (!$rowChk || (int)$rowChk['lote_cli_id'] !== $cliIdSesionGC) {
                echo '<div class="alert alert-danger">Sin permisos</div>';
                break;
            }
        }
        $result = mysqli_query($mysqli, "SELECT cgc_id, cgc_codigo, cgc_cupo_inicial, cgc_cupo_disponible,
                                           cgc_estado, cgc_fecha_activacion, cgc_fecha_caducidad, cgc_fecha_uso
                                    FROM codigo_gift_card WHERE lgc_id = $lgc_id ORDER BY cgc_id ASC");
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
                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) {
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
    // CREAR LOTE — GC-B: Super Admin arma la solicitud de un lote directo
    // desde el módulo Gift Card (en vez de que la origine el cliente final
    // desde Portal Empresa), asociándola a un cliente existente (elegido
    // en el selector) o a uno nuevo creado en el momento. NO se aprueba
    // sola: queda PENDING igual que cualquier otra solicitud, y sube a
    // "Aprobaciones Pendientes" para pasar por el mismo flujo de revisión
    // (aprobar_solicitud es quien genera los códigos y la cobranza).
    // ══════════════════════════════════════════════════
    case 'crear_lote':
        header('Content-Type: application/json');
        if (!$esSuperAdminGC) {
            echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']);
            break;
        }
        $cli_id         = (int)($_POST['cli_id'] ?? 0);
        $nuevo_cliente  = trim($_POST['nuevo_cliente'] ?? '');
        $cantidad       = (int)($_POST['cantidad'] ?? 0);
        $cupo           = (float)($_POST['cupo_codigo'] ?? 0);
        $periodo        = trim($_POST['periodo_facturacion'] ?? '');
        $caducidad      = trim($_POST['fecha_caducidad'] ?? '');

        // La elección del cliente es propia de este modal; el resto de reglas
        // se comparten con la API externa (services/giftcard_solicitud.php)
        // para que no se desincronicen. Modo NO estricto: la UI conserva
        // exactamente las validaciones que ya tenía.
        $datos_solicitud = [
            'cantidad'            => $cantidad,
            'cupo_codigo'         => $cupo,
            'periodo_facturacion' => $periodo,
            'fecha_caducidad'     => $caducidad,
        ];

        if ((!$cli_id && $nuevo_cliente === '') || gc_validar_solicitud($datos_solicitud, false)) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos o inválidos']);
            break;
        }

        $mysqli->begin_transaction();
        try {
            if (!$cli_id) {
                $sc = $mysqli->prepare("INSERT INTO cliente (cli_descripcion, cli_tipo_cliente) VALUES (?, 'Gift Card')");
                $sc->bind_param('s', $nuevo_cliente);
                if (!$sc->execute()) throw new Exception('Error al crear el cliente');
                $cli_id = $mysqli->insert_id;
            } else {
                $chkCli = $mysqli->prepare("SELECT cli_id FROM cliente WHERE cli_id = ?");
                $chkCli->bind_param('i', $cli_id);
                $chkCli->execute();
                if (!$chkCli->get_result()->fetch_assoc()) throw new Exception('Cliente no encontrado');
            }

            gc_crear_solicitud($mysqli, $id_user, $cli_id, $datos_solicitud);

            $mysqli->commit();
            echo json_encode(['success' => true, 'mensaje' => 'Solicitud creada, pendiente de aprobación.']);
        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
        }
        break;

    // ══════════════════════════════════════════════════
    // SOLICITAR LOTE — cliente solicita códigos (PENDING)
    // ══════════════════════════════════════════════════
    case 'solicitar_lote':
        header('Content-Type: application/json');
        if (!$esClienteGC) {
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
        if (!$stmt) {
            echo json_encode(['success' => false, 'mensaje' => 'Funcionalidad no disponible. Contacte al administrador para activar el módulo de solicitudes.']);
            break;
        }
        $stmt->bind_param('iidss', $id_user, $cantidad, $cupo, $periodo, $caducidad);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al registrar solicitud']);
            break;
        }
        $sol_id = $mysqli->insert_id;

        // Obtener datos del solicitante (bind_result, no requiere mysqlnd)
        $solicitante = ['name_user' => '', 'email' => ''];
        $ru = $mysqli->prepare("SELECT name_user FROM usuario WHERE id_user = ?");
        if ($ru) {
            $ru->bind_param('i', $id_user);
            $ru->execute();
            $name_user_val = '';
            $ru->bind_result($name_user_val);
            $ru->fetch();
            $ru->close();
            $solicitante['name_user'] = $name_user_val;
        }
        // Email: query directa con supresión de error si columna no existe
        $re = @$mysqli->query("SELECT email FROM usuario WHERE id_user = " . (int)$id_user . " LIMIT 1");
        if ($re && $row_re = mysqli_fetch_assoc($re)) $solicitante['email'] = $row_re['email'] ?? '';

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
        if (!$esSuperAdminGC) {
            echo '<div class="alert alert-danger">Sin permisos</div>';
            break;
        }
        // Solo PENDING: esta tabla es la cola de trabajo por revisar. El
        // historial de las ya aprobadas/rechazadas se ve desde "Lotes de
        // Gift Cards" (aprobadas) vía el ícono de reloj en Acciones; las
        // rechazadas no generan lote y no tienen otra vista por ahora.
        $query  = "SELECT s.sol_id, s.sol_cantidad, s.sol_cupo_codigo, s.sol_periodo_facturacion,
                          s.sol_fecha_caducidad, s.sol_fecha_solicitud,
                          u.name_user,
                          COALESCE(c_directo.cli_descripcion, c_usuario.cli_descripcion, '—') AS cliente_nombre
                   FROM giftcard_solicitud s
                   JOIN usuario u ON s.id_user = u.id_user
                   LEFT JOIN cliente c_directo ON s.cli_id = c_directo.cli_id
                   LEFT JOIN cliente c_usuario ON u.cli_id = c_usuario.cli_id
                   WHERE s.sol_estado = 'PENDING'
                   ORDER BY s.sol_fecha_solicitud ASC";
        $result = mysqli_query($mysqli, $query);
        ?>
        <table id="table_solicitudes" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>#</th><th>Solicitante</th><th>Cliente</th><th>Cantidad</th><th>Cupo x Cód.</th>
                    <th>Período</th><th>Caducidad</th><th>Fecha Solicitud</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['sol_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name_user']); ?></td>
                    <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                    <td><?php echo $row['sol_cantidad']; ?></td>
                    <td>$<?php echo number_format($row['sol_cupo_codigo'], 2); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['sol_periodo_facturacion'])); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['sol_fecha_caducidad'])); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['sol_fecha_solicitud'])); ?></td>
                    <td>
                        <button class="btn btn-sm btn-success mr-1" style="color:#fff !important;"
                            onclick="previsualizarSolicitud(<?php echo $row['sol_id']; ?>, 'APPROVE')"
                            title="Aprobar">
                            <i class="icon dripicons-checkmark" style="color:#fff !important;"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" style="color:#fff !important;"
                            onclick="previsualizarSolicitud(<?php echo $row['sol_id']; ?>, 'REJECT')"
                            title="Rechazar">
                            <i class="icon dripicons-cross" style="color:#fff !important;"></i>
                        </button>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php
        break;

    // ══════════════════════════════════════════════════
    // LIST SOLICITUDES EMPRESA — PER-01: personal interno de solo lectura
    // ve TODAS las solicitudes (cualquier estado) de la empresa que tiene
    // vinculada, sin poder aprobar ni rechazar.
    // ══════════════════════════════════════════════════
    case 'list_solicitudes_empresa':
        header('Content-Type: text/html');
        if (!$esEmpresaStaffGC) {
            echo '<div class="alert alert-danger">Sin permisos</div>';
            break;
        }
        $result = mysqli_query($mysqli, "SELECT s.sol_id, s.sol_cantidad, s.sol_cupo_codigo, s.sol_periodo_facturacion,
                                         s.sol_fecha_caducidad, s.sol_estado, s.sol_fecha_solicitud, u.name_user
                                  FROM giftcard_solicitud s JOIN usuario u ON s.id_user = u.id_user
                                  WHERE COALESCE(s.cli_id, u.cli_id) = $cliIdSesionGC
                                  ORDER BY s.sol_fecha_solicitud DESC");
        $badges = ['PENDING' => 'warning', 'APPROVED' => 'success', 'REJECTED' => 'danger'];
        $labels = ['PENDING' => 'Pendiente', 'APPROVED' => 'Aprobado', 'REJECTED' => 'Rechazado'];
        $rows   = [];
        if ($result) { while ($r = mysqli_fetch_assoc($result)) $rows[] = $r; }
        if (empty($rows)): ?>
            <div class="text-center py-5 text-muted">
                <i class="icon dripicons-inbox" style="font-size:2.5rem; display:block; margin-bottom:10px; opacity:.4;"></i>
                Tu empresa no tiene solicitudes de Gift Cards registradas.
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
                    <div class="col-sm-3"><small class="text-muted">Solicitante</small><br><strong><?php echo htmlspecialchars($row['name_user']); ?></strong></div>
                    <div class="col-sm-3"><small class="text-muted">Cantidad</small><br><strong><?php echo $row['sol_cantidad']; ?> códigos</strong></div>
                    <div class="col-sm-3"><small class="text-muted">Cupo x código</small><br><strong>$<?php echo number_format($row['sol_cupo_codigo'], 2); ?></strong></div>
                    <div class="col-sm-3"><small class="text-muted">Caducidad</small><br><strong><?php echo date('d/m/Y', strtotime($row['sol_fecha_caducidad'])); ?></strong></div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif;
        break;

    // ══════════════════════════════════════════════════
    // MIS SOLICITUDES — cliente ve sus propias solicitudes
    // ══════════════════════════════════════════════════
    case 'mis_solicitudes':
        header('Content-Type: text/html');
        $result = mysqli_query($mysqli, "SELECT sol_id, sol_cantidad, sol_cupo_codigo, sol_periodo_facturacion,
                                         sol_fecha_caducidad, sol_estado, sol_fecha_solicitud
                                  FROM giftcard_solicitud WHERE id_user = $id_user ORDER BY sol_fecha_solicitud DESC");
        $badges = ['PENDING' => 'warning', 'APPROVED' => 'success', 'REJECTED' => 'danger'];
        $labels = ['PENDING' => 'Pendiente', 'APPROVED' => 'Aprobado', 'REJECTED' => 'Rechazado'];
        $rows   = [];
        if ($result) { while ($r = mysqli_fetch_assoc($result)) $rows[] = $r; }
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
        if (!$esSuperAdminGC) { echo json_encode(['success' => false]); break; }
        $sol_id = (int)($_GET['sol_id'] ?? 0);
        $result = mysqli_query($mysqli, "SELECT s.sol_id, s.id_user, s.sol_cantidad, s.sol_cupo_codigo,
                                    s.sol_periodo_facturacion, s.sol_fecha_caducidad, s.sol_estado, s.sol_fecha_solicitud,
                                    u.name_user, COALESCE(s.cli_id, u.cli_id) AS cli_id, c.cli_descripcion
                                    FROM giftcard_solicitud s
                                    JOIN usuario u ON s.id_user = u.id_user
                                    LEFT JOIN cliente c ON c.cli_id = COALESCE(s.cli_id, u.cli_id)
                                    WHERE s.sol_id = $sol_id");
        $row = $result ? mysqli_fetch_assoc($result) : null;
        if (!$row) { echo json_encode(['success' => false, 'mensaje' => 'Solicitud no encontrada']); break; }
        echo json_encode(['success' => true, 'data' => $row]);
        break;

    // ══════════════════════════════════════════════════
    // GC-A: CREAR CLIENTE Y VINCULARLO AL SOLICITANTE
    // Permite, sin salir del modal de revisión de la solicitud, crear
    // el registro de cliente y vincularlo al usuario que la solicitó
    // cuando ese usuario aún no tiene cli_id asignado (usuario.cli_id
    // NULL). Sin este vínculo, al aprobar la solicitud no se registra
    // la cobranza del lote en Gestiones (ver aprobar_solicitud, paso 5).
    // ══════════════════════════════════════════════════
    case 'crear_cliente_solicitante':
        header('Content-Type: application/json');
        if (!$esSuperAdminGC) { echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']); break; }

        $sol_id = (int)($_POST['sol_id'] ?? 0);
        $desc   = trim($_POST['cli_descripcion'] ?? '');
        if (!$sol_id || $desc === '') { echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']); break; }

        $res_sol3 = mysqli_query($mysqli, "SELECT s.id_user, COALESCE(s.cli_id, u.cli_id) AS cli_id FROM giftcard_solicitud s JOIN usuario u ON s.id_user = u.id_user WHERE s.sol_id = $sol_id");
        $sol3 = $res_sol3 ? mysqli_fetch_assoc($res_sol3) : null;
        if (!$sol3) { echo json_encode(['success' => false, 'mensaje' => 'Solicitud no encontrada']); break; }
        if (!empty($sol3['cli_id'])) { echo json_encode(['success' => false, 'mensaje' => 'El solicitante ya tiene un cliente vinculado']); break; }

        $mysqli->begin_transaction();
        try {
            $sc = $mysqli->prepare("INSERT INTO cliente (cli_descripcion, cli_tipo_cliente) VALUES (?, 'Gift Card')");
            $sc->bind_param('s', $desc);
            if (!$sc->execute()) throw new Exception('Error al crear el cliente');
            $nuevo_cli_id = $mysqli->insert_id;

            $su = $mysqli->prepare("UPDATE usuario SET cli_id = ? WHERE id_user = ?");
            $su->bind_param('ii', $nuevo_cli_id, $sol3['id_user']);
            if (!$su->execute()) throw new Exception('Error al vincular el cliente al usuario');

            $mysqli->commit();
            echo json_encode(['success' => true, 'mensaje' => 'Cliente creado y vinculado', 'cli_id' => $nuevo_cli_id, 'cli_descripcion' => $desc]);
        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
        }
        break;

    // ══════════════════════════════════════════════════
    // APROBAR SOLICITUD — transacción atómica
    // ══════════════════════════════════════════════════
    case 'aprobar_solicitud':
        header('Content-Type: application/json');
        if (!$esSuperAdminGC) { echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']); break; }

        $sol_id = (int)($_POST['sol_id'] ?? 0);
        $notas  = trim($_POST['notas'] ?? '');

        $res_sol = mysqli_query($mysqli, "SELECT s.sol_id, s.id_user, s.sol_cantidad, s.sol_cupo_codigo,
                                    s.sol_periodo_facturacion, s.sol_fecha_caducidad,
                                    u.name_user, COALESCE(u.email,'') as email, COALESCE(s.cli_id, u.cli_id) AS cli_id
                                    FROM giftcard_solicitud s JOIN usuario u ON s.id_user = u.id_user
                                    WHERE s.sol_id = $sol_id AND s.sol_estado = 'PENDING'");
        $sol = $res_sol ? mysqli_fetch_assoc($res_sol) : null;

        if (!$sol) { echo json_encode(['success' => false, 'mensaje' => 'Solicitud no encontrada o ya procesada']); break; }

        $mysqli->begin_transaction();
        try {
            // 1. Crear lote (cli_id queda registrado en el lote, igual que en GC-B,
            //    para no depender solo de usuario.cli_id en consultas posteriores)
            $s1 = $mysqli->prepare("INSERT INTO lote_gift_card (id_user, lgc_cantidad, lgc_cupo_codigo, lgc_periodo_facturacion, cli_id) VALUES (?, ?, ?, ?, ?)");
            $s1->bind_param('iidsi', $sol['id_user'], $sol['sol_cantidad'], $sol['sol_cupo_codigo'], $sol['sol_periodo_facturacion'], $sol['cli_id']);
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

            // 5. CO-01: registrar cobranza del lote en Gestiones (car_tipo='GC'),
            // independiente de las carteras 30/60/90. Requiere que el cliente
            // que solicitó el lote tenga cli_id asignado (usuario.cli_id).
            if (!empty($sol['cli_id'])) {
                $valorLote = $sol['sol_cantidad'] * $sol['sol_cupo_codigo'];
                $s5 = $mysqli->prepare(
                    "INSERT INTO cartera (car_fecha_inicio, car_fecha_fin, car_fecha_ingreso, car_estado, car_tipo, cli_valor_pagar, cli_id, lgc_id)
                     VALUES (?, ?, CURDATE(), 'sin_gestion', 'GC', ?, ?, ?)"
                );
                $s5->bind_param('ssdii', $sol['sol_periodo_facturacion'], $sol['sol_periodo_facturacion'], $valorLote, $sol['cli_id'], $lgc_id);
                if (!$s5->execute()) throw new Exception('Error al registrar cobranza del lote');
            }

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
        if (!$esSuperAdminGC) { echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']); break; }

        $sol_id = (int)($_POST['sol_id'] ?? 0);
        $notas  = trim($_POST['notas'] ?? '');

        $res_sol2 = mysqli_query($mysqli, "SELECT s.sol_id, s.id_user, s.sol_cantidad, s.sol_cupo_codigo,
                                    s.sol_periodo_facturacion, s.sol_fecha_caducidad,
                                    u.name_user, COALESCE(u.email,'') as email
                                    FROM giftcard_solicitud s JOIN usuario u ON s.id_user = u.id_user
                                    WHERE s.sol_id = $sol_id AND s.sol_estado = 'PENDING'");
        $sol = $res_sol2 ? mysqli_fetch_assoc($res_sol2) : null;

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
        if (!$esSuperAdminGC) { echo json_encode(['success' => false]); break; }
        $sol_id = (int)($_GET['sol_id'] ?? 0);
        $res_h  = mysqli_query($mysqli, "SELECT h.aph_accion, h.aph_notas, h.aph_timestamp, u.name_user
                                    FROM giftcard_approval_history h JOIN usuario u ON h.admin_id = u.id_user
                                    WHERE h.sol_id = $sol_id ORDER BY h.aph_timestamp DESC");
        $rows = [];
        if ($res_h) { while ($r = mysqli_fetch_assoc($res_h)) $rows[] = $r; }
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ══════════════════════════════════════════════════
    // VER HISTORIAL DE UN LOTE — ícono de reloj en "Lotes de Gift Cards".
    // Un lote no guarda su propio historial: se resuelve la solicitud que
    // lo originó (giftcard_solicitud.sol_lgc_id) y se reutiliza su
    // historial de aprobación. Los lotes creados antes de este cambio (sin
    // solicitud asociada) devuelven data vacía.
    // ══════════════════════════════════════════════════
    case 'ver_historial_lote':
        header('Content-Type: application/json');
        if (!$esSuperAdminGC) { echo json_encode(['success' => false]); break; }
        $lgc_id  = (int)($_GET['lgc_id'] ?? 0);
        $res_sol = mysqli_query($mysqli, "SELECT sol_id FROM giftcard_solicitud WHERE sol_lgc_id = $lgc_id LIMIT 1");
        $solRow  = $res_sol ? mysqli_fetch_assoc($res_sol) : null;
        if (!$solRow) { echo json_encode(['success' => true, 'data' => []]); break; }
        $res_h = mysqli_query($mysqli, "SELECT h.aph_accion, h.aph_notas, h.aph_timestamp, u.name_user
                                   FROM giftcard_approval_history h JOIN usuario u ON h.admin_id = u.id_user
                                   WHERE h.sol_id = " . (int)$solRow['sol_id'] . " ORDER BY h.aph_timestamp DESC");
        $rows = [];
        if ($res_h) { while ($r = mysqli_fetch_assoc($res_h)) $rows[] = $r; }
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ══════════════════════════════════════════════════
    // VALIDAR CODIGO — POS. cgc_estado='activo' ya es la fuente de verdad
    // de que un código es utilizable: solo se inserta así al aprobar una
    // solicitud o al crear un lote directo (GC-B), en ambos casos por un
    // Super Admin. No se vuelve a exigir una giftcard_solicitud APPROVED
    // aquí porque los lotes creados directo (GC-B) no tienen solicitud.
    // ══════════════════════════════════════════════════
    case 'validar_codigo':
        header('Content-Type: application/json');
        $codigo = strtoupper(trim($_GET['codigo'] ?? ''));
        if ($codigo === '') { echo json_encode(['success' => false, 'mensaje' => 'Ingrese un código']); break; }

        $stmt = $mysqli->prepare(
            "SELECT cgc_id, cgc_codigo, cgc_cupo_inicial, cgc_cupo_disponible, cgc_estado, cgc_fecha_caducidad
             FROM codigo_gift_card WHERE cgc_codigo = ? LIMIT 1"
        );
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $gc = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$gc) {
            echo json_encode(['success' => false, 'mensaje' => 'Código no encontrado']);
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
            'saldo_original'  => (float)$gc['cgc_cupo_inicial'],
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
