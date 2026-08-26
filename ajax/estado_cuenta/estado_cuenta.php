<?php
session_start();
require_once('../../config/database.php');
require_once('../../services/estado_cuenta_service.php');

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
        $query = "SELECT ec.ec_id, ec.ec_periodo_inicio, ec.ec_periodo_fin, ec.ec_monto_total,
                         ec.ec_fecha_generacion, ec.ec_estado_envio,
                         c.cli_descripcion
                  FROM estado_cuenta ec
                  JOIN cliente c ON ec.cli_id = c.cli_id
                  ORDER BY ec.ec_id DESC";
        $result = mysqli_query($mysqli, $query);
        $badges = ['pendiente' => 'warning', 'enviado' => 'success', 'error' => 'danger'];
        ?>
        <table id="table_ec" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Cliente</th>
                    <th>Período</th>
                    <th>Total Consumos</th>
                    <th>Generado</th>
                    <th>Estado Envío</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) {
                    $color = $badges[$row['ec_estado_envio']] ?? 'secondary';
                ?>
                <tr>
                    <td><?php echo $no; ?></td>
                    <td><?php echo htmlspecialchars($row['cli_descripcion']); ?></td>
                    <td>
                        <?php echo date('d/m/Y', strtotime($row['ec_periodo_inicio'])); ?> —
                        <?php echo date('d/m/Y', strtotime($row['ec_periodo_fin'])); ?>
                    </td>
                    <td>$<?php echo number_format($row['ec_monto_total'], 2); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['ec_fecha_generacion'])); ?></td>
                    <td><span class="badge badge-<?php echo $color; ?>" id="badge_envio_<?php echo $row['ec_id']; ?>"><?php echo $row['ec_estado_envio']; ?></span></td>
                    <td>
                        <a class="btn btn-info btn-md" title="Ver estado de cuenta"
                           onclick="ver_ec(<?php echo $row['ec_id']; ?>)">
                            <i style="color:#fff" class="icon dripicons-document"></i>
                        </a>
                        <a class="btn btn-success btn-md" title="Enviar por correo"
                           onclick="enviarEC(<?php echo $row['ec_id']; ?>)">
                            <i style="color:#fff" class="icon dripicons-mail"></i>
                        </a>
                    </td>
                </tr>
                <?php $no++; } ?>
            </tbody>
        </table>
        <?php
        break;

    case 'generar':
        $cli_id         = (int)($_POST['cli_id']          ?? 0);
        $periodo_inicio = trim($_POST['periodo_inicio'] ?? '');
        $periodo_fin    = trim($_POST['periodo_fin']    ?? '');

        if ($cli_id === 0 || !$periodo_inicio || !$periodo_fin || $periodo_fin < $periodo_inicio) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos o fechas inválidas']);
            break;
        }

        $ec_id = ec_generar_estado_cuenta($mysqli, $cli_id, $periodo_inicio, $periodo_fin);

        if (!$ec_id) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al guardar: ' . mysqli_error($mysqli)]);
            break;
        }

        echo json_encode(['success' => true, 'ec_id' => $ec_id, 'mensaje' => 'Estado de cuenta generado']);
        break;

    case 'ver':
        $ec_id = (int)($_GET['ec_id'] ?? 0);
        $data  = ec_obtener_detalle($mysqli, $ec_id);

        if (!$data) {
            echo json_encode(['success' => false, 'mensaje' => 'Estado de cuenta no encontrado']);
            break;
        }

        echo json_encode([
            'success'    => true,
            'ec'         => $data['ec'],
            'detalles'   => $data['detalles'],
            'marcas'     => $data['marcas'],
            'pivot_rows' => $data['pivot_rows'],
            'saldos'     => $data['saldos'],
        ]);
        break;

    case 'enviar':
        $ec_id = (int)($_POST['ec_id'] ?? $_GET['ec_id'] ?? 0);

        if ($ec_id <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Estado de cuenta inválido']);
            break;
        }

        $resultado = ec_enviar_correo($mysqli, $ec_id);
        echo json_encode($resultado);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}
?>
