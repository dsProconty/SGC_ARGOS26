<?php
require_once "../../config/database.php";

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── LIST — tabla HTML para DataTables ────────────────────────────────────
    case 'list':
        $result = mysqli_query($mysqli,
            "SELECT cli_id, cli_descripcion, cli_ciudad, cli_contacto,
                    cli_email, cli_telefono, cli_numero_convenio,
                    cli_tipo_beneficio, cli_valor_beneficio,
                    cli_tipo_cartera, cli_comision
             FROM cliente
             ORDER BY cli_descripcion ASC"
        );
        ?>
        <table id="table_clientes" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Empresa / Cliente</th>
                    <th>Ciudad</th>
                    <th>Contacto</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Convenio</th>
                    <th>Tipo Beneficio</th>
                    <th>Cartera</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)):
                    $tipo_badge = $row['cli_tipo_beneficio'] === 'Cupo' ? 'info' : 'primary';
                    $cartera_badge = [
                        '30'  => 'success',
                        '60'  => 'warning',
                        '90'  => 'danger',
                        '90+' => 'dark',
                    ][$row['cli_tipo_cartera']] ?? 'secondary';
                ?>
                <tr>
                    <td><?php echo $no; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['cli_descripcion']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['cli_ciudad'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($row['cli_contacto'] ?? '—'); ?></td>
                    <td>
                        <?php if ($row['cli_email']): ?>
                            <a href="mailto:<?php echo htmlspecialchars($row['cli_email']); ?>">
                                <?php echo htmlspecialchars($row['cli_email']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['cli_telefono'] ?? '—'); ?></td>
                    <td>
                        <?php if ($row['cli_numero_convenio']): ?>
                            <code><?php echo htmlspecialchars($row['cli_numero_convenio']); ?></code>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['cli_tipo_beneficio']): ?>
                            <span class="badge badge-<?php echo $tipo_badge; ?>">
                                <?php echo htmlspecialchars($row['cli_tipo_beneficio']); ?>
                                <?php if ($row['cli_tipo_beneficio'] === 'Cupo' && $row['cli_valor_beneficio']): ?>
                                    — $<?php echo number_format($row['cli_valor_beneficio'], 2); ?>
                                <?php elseif ($row['cli_tipo_beneficio'] === 'Porcentaje' && $row['cli_valor_beneficio']): ?>
                                    — <?php echo $row['cli_valor_beneficio']; ?>%
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['cli_tipo_cartera']): ?>
                            <span class="badge badge-<?php echo $cartera_badge; ?>">
                                <?php echo htmlspecialchars($row['cli_tipo_cartera']); ?> días
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <button class="btn btn-info btn-sm mr-1"
                                onclick="verCliente(<?php echo $row['cli_id']; ?>)"
                                title="Ver detalle">
                            <i class="icon dripicons-information"></i>
                        </button>
                        <button class="btn btn-primary btn-sm"
                                onclick="editarCliente(<?php echo $row['cli_id']; ?>)"
                                title="Editar">
                            <i class="icon dripicons-document-edit"></i>
                        </button>
                    </td>
                </tr>
                <?php $no++; endwhile; ?>
            </tbody>
        </table>
        <?php
        break;

    // ── GET — datos JSON para modal editar / ver ─────────────────────────────
    case 'get':
        header('Content-Type: application/json');
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT * FROM cliente WHERE cli_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row  = $stmt->get_result()->fetch_assoc();
        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Cliente no encontrado']);
        }
        break;

    // ── CREAR ─────────────────────────────────────────────────────────────────
    case 'crear':
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método inválido']); break;
        }
        $desc       = trim($_POST['cli_descripcion'] ?? '');
        if (empty($desc)) {
            echo json_encode(['success' => false, 'mensaje' => 'El nombre del cliente es requerido']); break;
        }
        $convenio   = trim($_POST['cli_numero_convenio'] ?? '') ?: null;
        $ciudad     = trim($_POST['cli_ciudad'] ?? '') ?: null;
        $contacto   = trim($_POST['cli_contacto'] ?? '') ?: null;
        $email      = trim($_POST['cli_email'] ?? '') ?: null;
        $email2     = trim($_POST['cli_email2'] ?? '') ?: null;
        $tel        = trim($_POST['cli_telefono'] ?? '') ?: null;
        $tel2       = trim($_POST['cli_telefono2'] ?? '') ?: null;
        $dia_corte  = trim($_POST['cli_dia_corte'] ?? '0');
        $tipo_ben   = $_POST['cli_tipo_beneficio'] ?? null;
        $val_ben    = !empty($_POST['cli_valor_beneficio']) ? (float)$_POST['cli_valor_beneficio'] : null;
        $tipo_cart  = $_POST['cli_tipo_cartera'] ?? null;
        $comision   = !empty($_POST['cli_comision']) ? (float)$_POST['cli_comision'] : 0.00;

        $stmt = $mysqli->prepare(
            "INSERT INTO cliente
             (cli_descripcion, cli_numero_convenio, cli_ciudad, cli_contacto,
              cli_email, cli_email2, cli_telefono, cli_telefono2, cli_dia_corte,
              cli_tipo_beneficio, cli_valor_beneficio, cli_tipo_cartera, cli_comision)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('ssssssssssdsd',
            $desc, $convenio, $ciudad, $contacto,
            $email, $email2, $tel, $tel2, $dia_corte,
            $tipo_ben, $val_ben, $tipo_cart, $comision
        );
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'mensaje' => 'Cliente creado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error al crear: ' . $mysqli->error]);
        }
        break;

    // ── EDITAR ────────────────────────────────────────────────────────────────
    case 'editar':
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método inválido']); break;
        }
        $id         = (int)($_POST['cli_id'] ?? 0);
        $desc       = trim($_POST['cli_descripcion'] ?? '');
        if (!$id || empty($desc)) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']); break;
        }
        $convenio   = trim($_POST['cli_numero_convenio'] ?? '') ?: null;
        $ciudad     = trim($_POST['cli_ciudad'] ?? '') ?: null;
        $contacto   = trim($_POST['cli_contacto'] ?? '') ?: null;
        $email      = trim($_POST['cli_email'] ?? '') ?: null;
        $email2     = trim($_POST['cli_email2'] ?? '') ?: null;
        $tel        = trim($_POST['cli_telefono'] ?? '') ?: null;
        $tel2       = trim($_POST['cli_telefono2'] ?? '') ?: null;
        $dia_corte  = trim($_POST['cli_dia_corte'] ?? '0');
        $tipo_ben   = $_POST['cli_tipo_beneficio'] ?? null;
        $val_ben    = !empty($_POST['cli_valor_beneficio']) ? (float)$_POST['cli_valor_beneficio'] : null;
        $tipo_cart  = $_POST['cli_tipo_cartera'] ?? null;
        $comision   = !empty($_POST['cli_comision']) ? (float)$_POST['cli_comision'] : 0.00;

        $stmt = $mysqli->prepare(
            "UPDATE cliente SET
              cli_descripcion=?, cli_numero_convenio=?, cli_ciudad=?, cli_contacto=?,
              cli_email=?, cli_email2=?, cli_telefono=?, cli_telefono2=?, cli_dia_corte=?,
              cli_tipo_beneficio=?, cli_valor_beneficio=?, cli_tipo_cartera=?, cli_comision=?
             WHERE cli_id=?"
        );
        $stmt->bind_param('ssssssssssdsdi',
            $desc, $convenio, $ciudad, $contacto,
            $email, $email2, $tel, $tel2, $dia_corte,
            $tipo_ben, $val_ben, $tipo_cart, $comision, $id
        );
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'mensaje' => 'Cliente actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar: ' . $mysqli->error]);
        }
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'mensaje' => 'Acción no reconocida']);
        break;
}
?>
