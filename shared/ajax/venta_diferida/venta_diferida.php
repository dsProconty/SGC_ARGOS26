<?php
session_start();
require_once('../../config/database.php');

header('Content-Type: application/json');

if (empty($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

$action  = $_GET['action'] ?? $_POST['action'] ?? '';
$id_user = (int)$_SESSION['id_user'];

switch ($action) {

    case 'list':
        header('Content-Type: text/html');
        $query = "SELECT vd.vd_id, vd.vd_descripcion, vd.vd_monto_total, vd.vd_num_cuotas,
                         vd.vd_cuotas_pagadas, vd.vd_monto_cuota, vd.vd_fecha_inicio, vd.vd_estado,
                         p.per_nombre, p.per_documento,
                         c.cli_descripcion,
                         u.name_user AS registrado_por
                  FROM venta_diferida vd
                  JOIN personal p  ON vd.per_id  = p.per_id
                  JOIN cliente  c  ON p.cli_id   = c.cli_id
                  JOIN usuario  u  ON vd.id_user = u.id_user
                  ORDER BY vd.vd_id DESC";
        $result = mysqli_query($mysqli, $query);
        $badges = ['activo' => 'success', 'completado' => 'primary', 'cancelado' => 'danger'];
        ?>
        <table id="table_vd" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Empleado</th>
                    <th>Empresa</th>
                    <th>Descripción</th>
                    <th>Monto Total</th>
                    <th>Cuota</th>
                    <th>Avance</th>
                    <th>Inicio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) {
                    $color    = $badges[$row['vd_estado']] ?? 'secondary';
                    $pagadas  = (int)$row['vd_cuotas_pagadas'];
                    $total    = (int)$row['vd_num_cuotas'];
                    $pct      = $total > 0 ? round($pagadas / $total * 100) : 0;
                ?>
                <tr>
                    <td><?php echo $no; ?></td>
                    <td><?php echo htmlspecialchars($row['per_nombre']); ?><br><small><?php echo $row['per_documento']; ?></small></td>
                    <td><?php echo htmlspecialchars($row['cli_descripcion']); ?></td>
                    <td><?php echo htmlspecialchars($row['vd_descripcion']); ?></td>
                    <td>$<?php echo number_format($row['vd_monto_total'], 2); ?></td>
                    <td>$<?php echo number_format($row['vd_monto_cuota'], 2); ?></td>
                    <td>
                        <div style="position:relative;">
                            <div class="progress" style="height:18px;" title="<?php echo "$pagadas de $total cuotas"; ?>">
                                <div class="progress-bar bg-success" style="width:<?php echo $pct; ?>%"></div>
                            </div>
                            <small style="position:absolute;top:0;left:0;right:0;text-align:center;line-height:18px;color:#212529;font-weight:600;">
                                <?php echo "$pagadas/$total"; ?>
                            </small>
                        </div>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($row['vd_fecha_inicio'])); ?></td>
                    <td><span class="badge badge-<?php echo $color; ?>"><?php echo $row['vd_estado']; ?></span></td>
                    <td>
                        <?php if ($row['vd_estado'] === 'activo') {
                            $cuotas_restantes = $total - $pagadas;
                            $saldo_pendiente  = round($cuotas_restantes * (float)$row['vd_monto_cuota'], 2);
                        ?>
                        <a class="btn btn-success btn-md mr-1" title="Registrar pago de cuota"
                           onclick="pagar_cuota(<?php echo $row['vd_id']; ?>, <?php echo $pagadas; ?>, <?php echo $total; ?>, '<?php echo number_format($row['vd_monto_cuota'], 2); ?>')">
                            <i style="color:#fff" class="icon dripicons-checkmark"></i>
                        </a>
                        <a class="btn btn-warning btn-md" title="Liquidar deuda completa"
                           onclick="liquidar(<?php echo $row['vd_id']; ?>, <?php echo $cuotas_restantes; ?>, '<?php echo number_format($saldo_pendiente, 2); ?>')">
                            <i style="color:#fff" class="icon dripicons-wallet"></i>
                        </a>
                        <?php } ?>
                    </td>
                </tr>
                <?php $no++; } ?>
            </tbody>
        </table>
        <?php
        break;

    case 'buscar_empleado':
        $cedula = mysqli_real_escape_string($mysqli, trim($_GET['cedula'] ?? ''));
        if ($cedula === '') {
            echo json_encode(['success' => false, 'mensaje' => 'Ingrese una cédula']);
            break;
        }
        $query  = "SELECT p.per_id, p.per_nombre, p.per_documento, p.per_estado,
                          c.cli_descripcion
                   FROM personal p
                   JOIN cliente c ON p.cli_id = c.cli_id
                   WHERE p.per_documento = '$cedula'
                   LIMIT 1";
        $result = mysqli_query($mysqli, $query);
        if (!$result || mysqli_num_rows($result) === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
            break;
        }
        $data = mysqli_fetch_assoc($result);
        if ($data['per_estado'] === 'bloqueado') {
            echo json_encode(['success' => false, 'mensaje' => 'Empleado bloqueado']);
            break;
        }
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'crear':
        $per_id       = (int)($_POST['per_id']       ?? 0);
        $descripcion  = mysqli_real_escape_string($mysqli, trim($_POST['descripcion']  ?? ''));
        $monto_total  = (float)($_POST['monto_total'] ?? 0);
        $num_cuotas   = (int)($_POST['num_cuotas']   ?? 0);
        $fecha_inicio = mysqli_real_escape_string($mysqli, trim($_POST['fecha_inicio'] ?? ''));

        if ($per_id === 0 || $descripcion === '' || $monto_total <= 0 || $num_cuotas <= 0 || !$fecha_inicio) {
            echo json_encode(['success' => false, 'mensaje' => 'Complete todos los campos']);
            break;
        }

        $monto_cuota = round($monto_total / $num_cuotas, 2);

        $sql = "INSERT INTO venta_diferida
                    (per_id, id_user, vd_descripcion, vd_monto_total, vd_num_cuotas, vd_cuotas_pagadas, vd_monto_cuota, vd_fecha_inicio, vd_estado)
                VALUES
                    ($per_id, $id_user, '$descripcion', $monto_total, $num_cuotas, 0, $monto_cuota, '$fecha_inicio', 'activo')";

        if (!mysqli_query($mysqli, $sql)) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al guardar: ' . mysqli_error($mysqli)]);
            break;
        }

        echo json_encode(['success' => true, 'mensaje' => 'Venta diferida registrada correctamente']);
        break;

    case 'pagar_cuota':
        $vd_id = (int)($_POST['vd_id'] ?? 0);
        if ($vd_id === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID inválido']);
            break;
        }

        $check = mysqli_query($mysqli, "SELECT vd_cuotas_pagadas, vd_num_cuotas FROM venta_diferida WHERE vd_id = $vd_id AND vd_estado = 'activo'");
        if (!$check || mysqli_num_rows($check) === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Venta no encontrada o ya finalizada']);
            break;
        }

        $row     = mysqli_fetch_assoc($check);
        $pagadas = (int)$row['vd_cuotas_pagadas'] + 1;
        $total   = (int)$row['vd_num_cuotas'];
        $estado  = ($pagadas >= $total) ? 'completado' : 'activo';

        mysqli_query($mysqli, "UPDATE venta_diferida SET vd_cuotas_pagadas = $pagadas, vd_estado = '$estado' WHERE vd_id = $vd_id");

        $msg = $estado === 'completado' ? 'Última cuota pagada. Venta completada.' : "Cuota $pagadas de $total registrada.";
        echo json_encode(['success' => true, 'mensaje' => $msg]);
        break;

    case 'liquidar':
        $vd_id = (int)($_POST['vd_id'] ?? 0);
        if ($vd_id === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID inválido']);
            break;
        }

        $check = mysqli_query($mysqli, "SELECT vd_num_cuotas FROM venta_diferida WHERE vd_id = $vd_id AND vd_estado = 'activo'");
        if (!$check || mysqli_num_rows($check) === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Venta no encontrada o ya finalizada']);
            break;
        }

        $total = (int)mysqli_fetch_assoc($check)['vd_num_cuotas'];

        mysqli_query($mysqli, "UPDATE venta_diferida SET vd_cuotas_pagadas = $total, vd_estado = 'completado' WHERE vd_id = $vd_id");

        echo json_encode(['success' => true, 'mensaje' => 'Deuda liquidada correctamente.']);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}
?>
