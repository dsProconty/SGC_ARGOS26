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
                    <td><span class="badge badge-<?php echo $color; ?>"><?php echo $row['ec_estado_envio']; ?></span></td>
                    <td>
                        <a class="btn btn-info btn-md" title="Ver estado de cuenta"
                           onclick="ver_ec(<?php echo $row['ec_id']; ?>)">
                            <i style="color:#fff" class="icon dripicons-document"></i>
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
        $periodo_inicio = mysqli_real_escape_string($mysqli, trim($_POST['periodo_inicio'] ?? ''));
        $periodo_fin    = mysqli_real_escape_string($mysqli, trim($_POST['periodo_fin']    ?? ''));

        if ($cli_id === 0 || !$periodo_inicio || !$periodo_fin || $periodo_fin < $periodo_inicio) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos o fechas inválidas']);
            break;
        }

        // Sumar consumos del período para ese cliente
        $q_total = "SELECT COALESCE(SUM(con.con_valor_total), 0) AS total
                    FROM consumo con
                    JOIN personal p ON con.per_id = p.per_id
                    WHERE p.cli_id = $cli_id
                      AND con.con_fecha BETWEEN '$periodo_inicio' AND '$periodo_fin'";
        $r_total = mysqli_query($mysqli, $q_total);
        $total   = (float)mysqli_fetch_assoc($r_total)['total'];

        $sql = "INSERT INTO estado_cuenta (cli_id, ec_periodo_inicio, ec_periodo_fin, ec_monto_total, ec_estado_envio)
                VALUES ($cli_id, '$periodo_inicio', '$periodo_fin', $total, 'pendiente')";

        if (!mysqli_query($mysqli, $sql)) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al guardar: ' . mysqli_error($mysqli)]);
            break;
        }

        $ec_id = mysqli_insert_id($mysqli);
        echo json_encode(['success' => true, 'ec_id' => $ec_id, 'mensaje' => 'Estado de cuenta generado']);
        break;

    case 'ver':
        $ec_id  = (int)($_GET['ec_id'] ?? 0);
        $q_ec   = "SELECT ec.*, c.cli_descripcion, c.cli_email, c.cli_contacto, c.cli_telefono
                   FROM estado_cuenta ec
                   JOIN cliente c ON ec.cli_id = c.cli_id
                   WHERE ec.ec_id = $ec_id";
        $r_ec   = mysqli_query($mysqli, $q_ec);

        if (!$r_ec || mysqli_num_rows($r_ec) === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Estado de cuenta no encontrado']);
            break;
        }

        $ec = mysqli_fetch_assoc($r_ec);

        // Detalle de consumos
        $q_det = "SELECT con.con_fecha, con.con_hora, p.per_nombre, p.per_documento,
                         l.loc_direccion, con.con_valor_total, con.con_monto_convenio, con.con_monto_externo
                  FROM consumo con
                  JOIN personal p ON con.per_id = p.per_id
                  LEFT JOIN local l ON con.loc_id = l.loc_id
                  WHERE p.cli_id = {$ec['cli_id']}
                    AND con.con_fecha BETWEEN '{$ec['ec_periodo_inicio']}' AND '{$ec['ec_periodo_fin']}'
                  ORDER BY con.con_fecha ASC, con.con_hora ASC";
        $r_det = mysqli_query($mysqli, $q_det);

        $detalles = [];
        while ($d = mysqli_fetch_assoc($r_det)) {
            $detalles[] = $d;
        }

        echo json_encode(['success' => true, 'ec' => $ec, 'detalles' => $detalles]);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}
?>
