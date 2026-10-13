<?php
// Disparo MANUAL de la cartera de Cobranza (solo Super Admin con sesión iniciada).
// El disparo automático es el cron: cron/actualizar_cartera.php (ver ese archivo para la configuración en cPanel).
// La lógica vive en services/cartera_service.php.
session_start();
require_once('../config/database.php');
require_once('../helpers/session_helpers.php');
require_once('../services/cartera_service.php');

ini_set('max_execution_time', 0);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'carga_cartera':
        if (empty($_SESSION['id_user']) || !esSuperAdmin($mysqli)) {
            http_response_code(403);
            echo 'Solo un Super Admin con sesión iniciada puede actualizar la cartera.';
            break;
        }
        $r = cartera_actualizar($mysqli);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Cartera actualizada: {$r['convenios']} convenios, {$r['creada']} creadas, "
           . "{$r['ciclo_nuevo']} con ciclo nuevo, {$r['refrescada']} refrescadas, {$r['sin_consumos']} sin consumos en la ventana.\n";
        break;
    default:
        http_response_code(404);
        break;
}
