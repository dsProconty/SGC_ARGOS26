<?php
/**
 * Genera una credencial para la API externa de Gift Cards.
 *
 * Uso (desde la raíz del proyecto):
 *   php tools/crear_credencial_api.php --cli_id=1
 *
 * En local, dentro del contenedor:
 *   docker compose exec web php tools/crear_credencial_api.php --cli_id=1
 *
 * La credencial se identifica por su api_id y por el cliente al que pertenece;
 * no guarda un nombre propio. Si un cliente tiene varias, se distinguen por
 * api_id y api_creado.
 *
 * Solo crea la credencial. El usuario con el que se registran las solicitudes
 * es único para toda la API y lo crea la migración bloque14 (ver allí el
 * porqué); este script ya no genera usuarios.
 *
 * El secreto se muestra UNA SOLA VEZ: en la base solo queda su hash, así que
 * no hay forma de recuperarlo. Si se pierde, se revoca y se emite otro.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Este script solo se ejecuta por línea de comandos.\n");
}

require_once __DIR__ . '/../config/database.php';

// ── Argumentos ───────────────────────────────────────────────────────────
$opciones = getopt('', array('cli_id:'));

if (empty($opciones['cli_id'])) {
    exit("Uso: php tools/crear_credencial_api.php --cli_id=<id>\n\n" .
         "  --cli_id   Cliente al que quedará atada la credencial.\n");
}

$cli_id = (int)$opciones['cli_id'];

// ── Validar cliente ──────────────────────────────────────────────────────
$stmt = $mysqli->prepare("SELECT cli_id, cli_descripcion FROM cliente WHERE cli_id = ?");
$stmt->bind_param('i', $cli_id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();

if (!$cliente) {
    exit("ERROR: no existe el cliente con cli_id = $cli_id\n");
}

// ── Comprobar que la migración dejó el usuario de servicio ───────────────
// Se avisa aquí y no al primer POST del cliente: emitir un token que va a
// fallar en producción es peor que negarse a emitirlo.
$cfg = @$mysqli->query("SELECT cfg_valor FROM configuracion WHERE cfg_clave = 'api_id_user' LIMIT 1");
$fila_cfg = $cfg ? $cfg->fetch_assoc() : null;
if (!$fila_cfg || (int)$fila_cfg['cfg_valor'] <= 0) {
    exit("ERROR: falta configuracion.api_id_user.\n" .
         "Ejecute primero migrations/bloque14_api_externa.sql\n");
}
$id_user = (int)$fila_cfg['cfg_valor'];

try {
    // ── Credencial ───────────────────────────────────────────────────────
    $api_key = 'ak_' . bin2hex(random_bytes(16));
    $secreto = bin2hex(random_bytes(32));
    $hash    = password_hash($secreto, PASSWORD_DEFAULT);

    $sc = $mysqli->prepare(
        "INSERT INTO api_credencial (api_key, api_secret, cli_id)
         VALUES (?, ?, ?)"
    );
    if (!$sc) {
        throw new Exception('Falta la tabla api_credencial. Ejecute migrations/bloque14_api_externa.sql');
    }
    $sc->bind_param('ssi', $api_key, $hash, $cli_id);
    if (!$sc->execute()) {
        throw new Exception('No se pudo crear la credencial: ' . $mysqli->error);
    }
    $api_id = $mysqli->insert_id;

    $token = $api_key . '.' . $secreto;

    echo "\n";
    echo "  Credencial creada\n";
    echo "  ─────────────────────────────────────────────────────────────\n";
    echo "  api_id    : $api_id\n";
    echo "  Cliente   : {$cliente['cli_descripcion']} (cli_id $cli_id)\n";
    echo "  Usuario   : id_user $id_user (de servicio, compartido por toda la API)\n";
    echo "\n";
    echo "  TOKEN (se muestra una sola vez, guárdelo ahora):\n";
    echo "  $token\n";
    echo "\n";
    echo "  Uso:\n";
    echo "  curl -H \"Authorization: Bearer $token\" .../api/v1/ping\n";
    echo "\n";

} catch (Exception $e) {
    exit('ERROR: ' . $e->getMessage() . "\n");
}
