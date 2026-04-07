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

        // Sumar consumos regulares del período para ese cliente
        $q_total = "SELECT COALESCE(SUM(con.con_valor_total), 0) AS total
                    FROM consumo con
                    JOIN personal p ON con.per_id = p.per_id
                    WHERE p.cli_id = $cli_id
                      AND con.con_fecha BETWEEN '$periodo_inicio' AND '$periodo_fin'";
        $r_total = mysqli_query($mysqli, $q_total);
        $total   = (float)mysqli_fetch_assoc($r_total)['total'];

        // Sumar cuotas de ventas diferidas que caen en el período
        $q_vd_total = "WITH RECURSIVE seq (n) AS (
                           SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 60
                       )
                       SELECT COALESCE(SUM(vd.vd_monto_cuota), 0) AS total
                       FROM venta_diferida vd
                       JOIN personal p ON vd.per_id = p.per_id
                       JOIN seq s ON s.n <= vd.vd_num_cuotas
                       WHERE p.cli_id = $cli_id
                         AND vd.vd_estado != 'cancelado'
                         AND DATE_ADD(vd.vd_fecha_inicio, INTERVAL (s.n - 1) MONTH)
                             BETWEEN '$periodo_inicio' AND '$periodo_fin'";
        $r_vd  = mysqli_query($mysqli, $q_vd_total);
        $total += (float)mysqli_fetch_assoc($r_vd)['total'];

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
        $q_ec   = "SELECT ec.*, c.cli_descripcion, c.cli_email, c.cli_contacto, c.cli_telefono, c.cli_comision
                   FROM estado_cuenta ec
                   JOIN cliente c ON ec.cli_id = c.cli_id
                   WHERE ec.ec_id = $ec_id";
        $r_ec   = mysqli_query($mysqli, $q_ec);

        if (!$r_ec || mysqli_num_rows($r_ec) === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Estado de cuenta no encontrado']);
            break;
        }

        $ec     = mysqli_fetch_assoc($r_ec);
        $cli_id = (int)$ec['cli_id'];
        $p_ini  = $ec['ec_periodo_inicio'];
        $p_fin  = $ec['ec_periodo_fin'];

        // Detalle de consumos regulares
        $q_det = "SELECT con.con_fecha, con.con_hora, p.per_nombre, p.per_documento,
                         p.per_numero_tarjeta, l.loc_direccion, con.con_valor_neto, con.con_iva,
                         con.con_valor_total, con.con_monto_convenio, con.con_monto_externo,
                         con.con_descripcion, 'consumo' AS origen
                  FROM consumo con
                  JOIN personal p ON con.per_id = p.per_id
                  LEFT JOIN local l ON con.loc_id = l.loc_id
                  WHERE p.cli_id = $cli_id
                    AND con.con_fecha BETWEEN '$p_ini' AND '$p_fin'";
        $r_det = mysqli_query($mysqli, $q_det);

        $detalles = [];
        while ($d = mysqli_fetch_assoc($r_det)) {
            $detalles[] = $d;
        }

        // Detalle de cuotas de ventas diferidas que caen en el período
        // Cada cuota cae en el mes de: DATE_ADD(vd_fecha_inicio, INTERVAL (n-1) MONTH)
        $q_vd = "WITH RECURSIVE seq (n) AS (
                     SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 60
                 )
                 SELECT
                     DATE_ADD(vd.vd_fecha_inicio, INTERVAL (s.n - 1) MONTH) AS con_fecha,
                     NULL                          AS con_hora,
                     p.per_nombre,
                     p.per_documento,
                     p.per_numero_tarjeta,
                     NULL                          AS loc_direccion,
                     ROUND(vd.vd_monto_cuota / (1 + COALESCE((SELECT cfg_valor FROM configuracion WHERE cfg_clave='iva_porcentaje' LIMIT 1), 0) / 100), 2) AS con_valor_neto,
                     ROUND(vd.vd_monto_cuota - ROUND(vd.vd_monto_cuota / (1 + COALESCE((SELECT cfg_valor FROM configuracion WHERE cfg_clave='iva_porcentaje' LIMIT 1), 0) / 100), 2), 2) AS con_iva,
                     vd.vd_monto_cuota             AS con_valor_total,
                     vd.vd_monto_cuota             AS con_monto_convenio,
                     NULL                          AS con_monto_externo,
                     CONCAT(vd.vd_descripcion, ' – Cuota ', s.n, '/', vd.vd_num_cuotas) AS con_descripcion,
                     'diferida'                    AS origen
                 FROM venta_diferida vd
                 JOIN personal p ON vd.per_id = p.per_id
                 JOIN seq s ON s.n <= vd.vd_num_cuotas
                 WHERE p.cli_id = $cli_id
                   AND vd.vd_estado != 'cancelado'
                   AND DATE_ADD(vd.vd_fecha_inicio, INTERVAL (s.n - 1) MONTH) BETWEEN '$p_ini' AND '$p_fin'
                 ORDER BY con_fecha ASC";
        $r_vd = mysqli_query($mysqli, $q_vd);

        while ($d = mysqli_fetch_assoc($r_vd)) {
            $detalles[] = $d;
        }

        // Ordenar todo por fecha
        usort($detalles, function($a, $b) {
            return strcmp($a['con_fecha'] . ($a['con_hora'] ?? ''), $b['con_fecha'] . ($b['con_hora'] ?? ''));
        });

        echo json_encode(['success' => true, 'ec' => $ec, 'detalles' => $detalles]);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}
?>
