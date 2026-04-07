<?php
session_start();
require_once '../../config/database.php';

if (empty($_SESSION['id_user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

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
                    <th>No.</th>
                    <th>Fecha Creación</th>
                    <th>Período Facturación</th>
                    <th>Cantidad</th>
                    <th>Cupo x Código</th>
                    <th>Disponibles</th>
                    <th>Consumidos</th>
                    <th>Creado por</th>
                    <th>Acciones</th>
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

    case 'list_codigos':
        header('Content-Type: text/html');
        $lgc_id = (int)($_GET['lgc_id'] ?? 0);
        $query  = "SELECT cgc_id, cgc_codigo, cgc_cupo_inicial, cgc_cupo_disponible,
                          cgc_estado, cgc_fecha_activacion, cgc_fecha_caducidad, cgc_fecha_uso
                   FROM codigo_gift_card
                   WHERE lgc_id = $lgc_id
                   ORDER BY cgc_id ASC";
        $result = mysqli_query($mysqli, $query);
        $badges = ['activo' => 'success', 'consumido' => 'secondary', 'vencido' => 'warning', 'anulado' => 'danger'];
        ?>
        <table id="table_codigos" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Código</th>
                    <th>Cupo Inicial</th>
                    <th>Cupo Disponible</th>
                    <th>Estado</th>
                    <th>Activación</th>
                    <th>Caducidad</th>
                    <th>Fecha Uso</th>
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

    case 'crear_lote':
        header('Content-Type: application/json');
        $cantidad   = (int)($_POST['cantidad']              ?? 0);
        $cupo       = (float)($_POST['cupo_codigo']         ?? 0);
        $periodo    = mysqli_real_escape_string($mysqli, trim($_POST['periodo_facturacion']  ?? ''));
        $caducidad  = mysqli_real_escape_string($mysqli, trim($_POST['fecha_caducidad']      ?? ''));
        $id_user    = (int)$_SESSION['id_user'];

        if ($cantidad <= 0 || $cantidad > 1000 || $cupo <= 0 || !$periodo || !$caducidad) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos o inválidos']);
            break;
        }

        $sql = "INSERT INTO lote_gift_card (id_user, lgc_cantidad, lgc_cupo_codigo, lgc_periodo_facturacion)
                VALUES ($id_user, $cantidad, $cupo, '$periodo')";

        if (!mysqli_query($mysqli, $sql)) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al crear lote: ' . mysqli_error($mysqli)]);
            break;
        }

        $lgc_id = mysqli_insert_id($mysqli);
        $now    = date('Y-m-d H:i:s');
        $errors = 0;

        for ($i = 0; $i < $cantidad; $i++) {
            $codigo     = strtoupper(bin2hex(random_bytes(6)));
            $ins_codigo = "INSERT INTO codigo_gift_card
                               (lgc_id, cgc_codigo, cgc_cupo_inicial, cgc_cupo_disponible, cgc_estado, cgc_fecha_activacion, cgc_fecha_caducidad)
                           VALUES ($lgc_id, '$codigo', $cupo, $cupo, 'activo', '$now', '$caducidad')";
            if (!mysqli_query($mysqli, $ins_codigo)) {
                $errors++;
            }
        }

        $generados = $cantidad - $errors;
        echo json_encode(['success' => true, 'mensaje' => "Lote creado con $generados códigos generados."]);
        break;

    case 'validar_codigo':
        header('Content-Type: application/json');
        $codigo = mysqli_real_escape_string($mysqli, strtoupper(trim($_GET['codigo'] ?? '')));
        if ($codigo === '') {
            echo json_encode(['success' => false, 'mensaje' => 'Ingrese un código']);
            break;
        }

        $q = "SELECT cgc_id, cgc_codigo, cgc_cupo_disponible, cgc_estado, cgc_fecha_caducidad
              FROM codigo_gift_card WHERE cgc_codigo = '$codigo' LIMIT 1";
        $r = mysqli_query($mysqli, $q);

        if (!$r || mysqli_num_rows($r) === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Código no encontrado']);
            break;
        }

        $gc = mysqli_fetch_assoc($r);

        if ($gc['cgc_estado'] !== 'activo') {
            echo json_encode(['success' => false, 'mensaje' => 'Código ' . $gc['cgc_estado'] . ' — no disponible']);
            break;
        }

        if ($gc['cgc_fecha_caducidad'] && $gc['cgc_fecha_caducidad'] < date('Y-m-d')) {
            // Marcar como vencido automáticamente
            mysqli_query($mysqli, "UPDATE codigo_gift_card SET cgc_estado='vencido' WHERE cgc_id={$gc['cgc_id']}");
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
