<?php
// CO-03: Widget de dashboard financiero (top deudores + confirmación de pagos)
// Visible solo para Super Admin / financiero / Operador (perfiles de gestión).
session_start();
require_once "../../config/database.php";
header('Content-Type: application/json');

if (empty($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

$rolesPermitidos = ['Super Admin', 'financiero', 'Operador'];
if (!in_array($_SESSION['permisos_acceso'] ?? '', $rolesPermitidos)) {
    echo json_encode(['success' => false, 'mensaje' => 'Sin permisos']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'resumen':
        // Cobrado confirmado vs. pendiente por confirmar (financiero), todo tipo de cartera
        $qResumen = "SELECT
                COALESCE(SUM(CASE WHEN c.car_estado = 'cobrada' THEN p.pag_monto ELSE 0 END), 0) AS total_confirmado,
                COALESCE(SUM(CASE WHEN c.car_estado = 'pendiente_confirmacion' THEN p.pag_monto ELSE 0 END), 0) AS total_pendiente
            FROM cartera c
            JOIN gestion g ON g.car_id = c.car_id AND g.pag_id IS NOT NULL
            JOIN pago p ON g.pag_id = p.pag_id
            WHERE c.car_estado IN ('cobrada', 'pendiente_confirmacion')";
        $rowResumen = mysqli_fetch_assoc(mysqli_query($mysqli, $qResumen));

        // Top 5 deudores: deuda pendiente actual (no cobrada aún) agrupada por cliente
        $qDeudores = "SELECT cli.cli_descripcion, SUM(c.cli_valor_pagar) AS deuda_pendiente
            FROM cartera c
            JOIN cliente cli ON c.cli_id = cli.cli_id
            WHERE c.car_estado IN ('sin_gestion', 'pendiente', 'compromiso', 'pendiente_confirmacion')
            GROUP BY c.cli_id
            ORDER BY deuda_pendiente DESC
            LIMIT 5";
        $resDeudores = mysqli_query($mysqli, $qDeudores);
        $deudores = [];
        while ($row = mysqli_fetch_assoc($resDeudores)) {
            $deudores[] = $row;
        }

        echo json_encode([
            'success'          => true,
            'total_confirmado' => (float)$rowResumen['total_confirmado'],
            'total_pendiente'  => (float)$rowResumen['total_pendiente'],
            'top_deudores'     => $deudores,
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
        break;
}
