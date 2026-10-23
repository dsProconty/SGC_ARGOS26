<?php
// Script pensado para un Cron Job real de cPanel (HostPapa): actualiza la cartera de Cobranza
// (tabla `cartera`) a partir de los consumos. No trae nada de afuera; solo suma lo que ya está en la base.
//
// Configuración en cPanel > Cron Jobs:
//   Comando:  php /home/sgipro/public_html/SGC_ARGOS26/cron/actualizar_cartera.php
//   Horario:  30 5 * * *   (una vez al día, 5:30 AM)
//
// Es seguro correrlo todos los días: si el convenio sigue en el mismo ciclo de corte solo se refresca el
// monto y NO se toca el estado de gestión (pendiente / cobrada / compromiso). Ver services/cartera_service.php.
//
// Si el hosting expone varios binarios de PHP (ej. ea-php70), usar el que coincida con la versión
// configurada para el dominio en MultiPHP Manager.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Este script solo puede ejecutarse desde línea de comandos (cron).');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/db_compat.php';
require_once __DIR__ . '/../services/cartera_service.php';

$r = cartera_actualizar($mysqli, 'cron');
if ($r === null) {
    echo date('Y-m-d H:i:s') . " - Ya hay una actualización de cartera en curso; no se ejecutó otra.
";
    exit;
}

echo date('Y-m-d H:i:s') . " - Cartera actualizada: {$r['convenios']} convenios, {$r['creada']} creadas, "
   . "{$r['ciclo_nuevo']} con ciclo nuevo, {$r['refrescada']} refrescadas, {$r['sin_consumos']} sin consumos en la ventana.\n";
