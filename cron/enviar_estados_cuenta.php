<?php
// Script pensado para un Cron Job real de cPanel (HostPapa), reemplazando el
// "piggyback" en content.php como disparador principal de la generación y
// envío automático de estados de cuenta por fecha de corte.
//
// El piggyback en content.php se deja como respaldo (por si el cron no llega
// a configurarse o falla un día): ambos caminos comparten la misma marca en
// la tabla `configuracion` (ec_auto_ultima_corrida), así que no se duplican
// envíos si los dos llegan a correr el mismo día.
//
// Configuración en cPanel > Cron Jobs:
//   Comando:  php /home/sgipro/public_html/SGC_ARGOS26/cron/enviar_estados_cuenta.php
//   Horario:  0 6 * * *   (una vez al día, 6:00 AM)
//
// Si el hosting expone varios binarios de PHP (ej. ea-php70), usar el que
// coincida con la versión configurada para el dominio en MultiPHP Manager.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Este script solo puede ejecutarse desde línea de comandos (cron).');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/estado_cuenta_service.php';

ec_generar_y_enviar_automaticos($mysqli);

echo date('Y-m-d H:i:s') . " - Proceso de estados de cuenta automáticos ejecutado.\n";
