<?php
/**
 * Utilidades comunes de la API externa v1.
 *
 * A diferencia de ajax/, aquí NO hay sesión: cada petición se autentica sola
 * con su token. Mezclar los dos modelos en el mismo archivo es como se cuelan
 * los agujeros de seguridad, por eso viven separados.
 *
 * Sin type hints nullable ni short-list syntax: PHP < 7.1 en producción.
 */

// Estado de la petición en curso, para poder auditarla al responder.
$GLOBALS['api_ctx'] = array(
    'api_id' => null,
    'metodo' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET',
    'ruta'   => '',
);

if (!function_exists('api_registrar_peticion')) {
    /**
     * Deja constancia de la llamada. Se hace en la respuesta (no al entrar)
     * para poder guardar también el código HTTP resultante.
     */
    function api_registrar_peticion($estado_http)
    {
        if (!isset($GLOBALS['mysqli']) || !$GLOBALS['mysqli']) {
            return;
        }
        $ctx  = $GLOBALS['api_ctx'];
        $stmt = @$GLOBALS['mysqli']->prepare(
            "INSERT INTO api_peticion (api_id, ape_metodo, ape_ruta, ape_estado_http, ape_ip)
             VALUES (?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            return; // La migración bloque14 aún no se ha ejecutado: no romper por el log.
        }
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        $stmt->bind_param('issis', $ctx['api_id'], $ctx['metodo'], $ctx['ruta'], $estado_http, $ip);
        @$stmt->execute();
    }
}

if (!function_exists('api_cabecera_extra')) {
    /**
     * Acumula cabeceras que se emitirán con la respuesta (X-RateLimit-*,
     * Retry-After...). Se guardan en vez de enviarse al momento porque la
     * respuesta puede salir por cualquiera de las muchas ramas de index.php.
     */
    function api_cabecera_extra($nombre, $valor)
    {
        if (!isset($GLOBALS['api_cabeceras'])) {
            $GLOBALS['api_cabeceras'] = array();
        }
        $GLOBALS['api_cabeceras'][$nombre] = $valor;
    }
}

if (!function_exists('api_responder')) {
    /** Emite la respuesta JSON y termina la petición. */
    function api_responder($estado_http, $cuerpo)
    {
        api_registrar_peticion($estado_http);

        // Descartar cualquier salida accidental previa (un warning, un BOM):
        // corrompería el JSON y el cliente externo vería un error ilegible.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($estado_http);
        header('Content-Type: application/json; charset=utf-8');
        if (!empty($GLOBALS['api_cabeceras'])) {
            foreach ($GLOBALS['api_cabeceras'] as $nombre => $valor) {
                header($nombre . ': ' . $valor);
            }
        }
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('api_error')) {
    /**
     * Error con formato estable para que el cliente pueda programar contra él.
     * $campos permite señalar qué campo concreto falló.
     *
     * Los mensajes son deliberadamente genéricos: nunca se filtra hacia fuera
     * el detalle interno de una excepción (podría revelar el esquema).
     */
    function api_error($estado_http, $codigo, $mensaje, $campos = null)
    {
        $error = array('codigo' => $codigo, 'mensaje' => $mensaje);
        if (!empty($campos)) {
            $error['campos'] = $campos;
        }
        api_responder($estado_http, array('error' => $error));
    }
}

if (!function_exists('api_leer_token')) {
    /**
     * Extrae el token de la cabecera Authorization.
     *
     * Se buscan varias fuentes a propósito: con PHP en CGI/FastCGI (habitual
     * en cPanel) Apache no expone Authorization en $_SERVER, y por eso el
     * .htaccess la copia a HTTP_AUTHORIZATION / REDIRECT_HTTP_AUTHORIZATION.
     * Sin este rodeo la API funciona en local y falla al desplegar.
     */
    function api_leer_token()
    {
        $cabecera = '';
        $fuentes  = array('HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION');
        foreach ($fuentes as $clave) {
            if (!empty($_SERVER[$clave])) {
                $cabecera = $_SERVER[$clave];
                break;
            }
        }
        if ($cabecera === '' && function_exists('apache_request_headers')) {
            foreach (apache_request_headers() as $nombre => $valor) {
                if (strtolower($nombre) === 'authorization') {
                    $cabecera = $valor;
                    break;
                }
            }
        }
        if ($cabecera === '' || stripos($cabecera, 'Bearer ') !== 0) {
            return '';
        }
        return trim(substr($cabecera, 7));
    }
}

if (!function_exists('api_autenticar')) {
    /**
     * Resuelve la credencial a partir del token, o corta la petición con 401.
     * Devuelve la fila de api_credencial. El cli_id que trae es lo que acota
     * todo lo que esta credencial puede ver y crear; el usuario con el que se
     * registran las solicitudes no sale de aquí (ver api_usuario_sistema).
     *
     * Formato del token: "<api_key>.<secreto>". La api_key identifica (búsqueda
     * indexada) y el secreto se verifica contra su hash.
     */
    function api_autenticar($mysqli)
    {
        $token = api_leer_token();
        if ($token === '') {
            api_error(401, 'NO_AUTENTICADO', 'Falta la cabecera Authorization: Bearer <token>.');
        }

        $punto = strpos($token, '.');
        if ($punto === false) {
            api_error(401, 'TOKEN_INVALIDO', 'Credenciales inválidas.');
        }
        $api_key = substr($token, 0, $punto);
        $secreto = substr($token, $punto + 1);

        $stmt = $mysqli->prepare(
            "SELECT c.api_id, c.api_secret, c.api_estado, c.cli_id,
                    cl.cli_descripcion
             FROM api_credencial c
             JOIN cliente cl ON cl.cli_id = c.cli_id
             WHERE c.api_key = ? LIMIT 1"
        );
        if (!$stmt) {
            api_error(500, 'ERROR_INTERNO', 'La API no está disponible. Ejecute la migración bloque14_api_externa.sql.');
        }
        $stmt->bind_param('s', $api_key);
        $stmt->execute();
        $cred = $stmt->get_result()->fetch_assoc();

        // Mismo mensaje para "no existe" y "secreto incorrecto": distinguirlos
        // permitiría averiguar qué api_key son válidas a base de probar.
        if (!$cred || !password_verify($secreto, $cred['api_secret'])) {
            api_error(401, 'TOKEN_INVALIDO', 'Credenciales inválidas.');
        }

        // Ya identificada: a partir de aquí las peticiones quedan auditadas
        // con su credencial, incluso las que terminen en error.
        $GLOBALS['api_ctx']['api_id'] = (int)$cred['api_id'];

        if ($cred['api_estado'] !== 'activo') {
            api_error(401, 'CREDENCIAL_REVOCADA', 'Esta credencial ha sido revocada.');
        }

        @$mysqli->query('UPDATE api_credencial SET api_ultimo_uso = NOW() WHERE api_id = ' . (int)$cred['api_id']);

        return $cred;
    }
}

if (!function_exists('api_usuario_sistema')) {
    /**
     * id_user con el que la API registra TODAS las solicitudes.
     *
     * Sale de configuracion y no de la credencial: la columna "solicitante" de
     * la cola de aprobaciones es informativa, y el cliente real se resuelve por
     * s.cli_id. Un usuario por empresa solo llenaría la tabla usuario de
     * cuentas falsas sin añadir información.
     *
     * Tampoco se busca por username: NO es único en esta base (hay dos 'admin'),
     * así que un LIMIT 1 por nombre podría devolver a cualquiera y las
     * solicitudes acabarían atribuidas a una persona real.
     *
     * Si falta la clave, se corta con 500: seguir adelante insertaría con
     * id_user = 0 y la FK lo rechazaría con un error mucho menos claro.
     */
    function api_usuario_sistema($mysqli)
    {
        static $id_user = null;
        if ($id_user !== null) {
            return $id_user;
        }

        $stmt = @$mysqli->prepare(
            "SELECT cfg_valor FROM configuracion WHERE cfg_clave = 'api_id_user' LIMIT 1"
        );
        if ($stmt) {
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            if ($fila && (int)$fila['cfg_valor'] > 0) {
                $id_user = (int)$fila['cfg_valor'];
                return $id_user;
            }
        }

        error_log('API v1: falta configuracion.api_id_user (migración bloque14).');
        api_error(500, 'ERROR_INTERNO', 'La API no está configurada correctamente.');
    }
}

if (!function_exists('api_leer_json')) {
    /** Lee y decodifica el cuerpo JSON de la petición, o corta con 400. */
    function api_leer_json()
    {
        $crudo = file_get_contents('php://input');
        if (trim($crudo) === '') {
            api_error(400, 'CUERPO_VACIO', 'Se esperaba un cuerpo JSON.');
        }
        $datos = json_decode($crudo, true);
        if (!is_array($datos)) {
            api_error(400, 'JSON_INVALIDO', 'El cuerpo no es un JSON válido.');
        }
        return $datos;
    }
}

if (!function_exists('api_verificar_limite')) {
    /**
     * Corta con 429 si la credencial superó $limite peticiones en los últimos
     * $ventana segundos. Se apoya en api_peticion, que ya se escribe para
     * auditoría (el índice (api_id, ape_fecha) existe justo para esta consulta).
     *
     * Las respuestas 429 se excluyen del recuento a propósito: si contaran,
     * un cliente que reintenta en bucle se mantendría bloqueado
     * indefinidamente en vez de recuperarse al pasar la ventana.
     *
     * $filtro_ruta acota el límite a un endpoint concreto (patrón LIKE).
     */
    function api_verificar_limite($mysqli, $api_id, $limite, $ventana, $filtro_ruta = null)
    {
        $sql = "SELECT COUNT(*) AS usadas FROM api_peticion
                WHERE api_id = ?
                  AND ape_fecha > DATE_SUB(NOW(), INTERVAL ? SECOND)
                  AND ape_estado_http <> 429";
        if ($filtro_ruta !== null) {
            $sql .= " AND ape_ruta LIKE ?";
        }

        $stmt = @$mysqli->prepare($sql);
        if (!$stmt) {
            return; // Sin tabla de auditoría no hay límite que aplicar; no romper por eso.
        }
        if ($filtro_ruta !== null) {
            $stmt->bind_param('iis', $api_id, $ventana, $filtro_ruta);
        } else {
            $stmt->bind_param('ii', $api_id, $ventana);
        }
        $stmt->execute();
        $fila   = $stmt->get_result()->fetch_assoc();
        $usadas = $fila ? (int)$fila['usadas'] : 0;

        $restantes = $limite - $usadas;
        if ($restantes < 0) {
            $restantes = 0;
        }
        api_cabecera_extra('X-RateLimit-Limit', $limite);
        api_cabecera_extra('X-RateLimit-Remaining', $restantes);

        if ($usadas >= $limite) {
            api_cabecera_extra('Retry-After', $ventana);
            api_error(429, 'LIMITE_EXCEDIDO',
                'Ha superado el límite de ' . $limite . ' peticiones por ' . $ventana . ' segundos. Reintente más tarde.');
        }
    }
}

if (!function_exists('api_purgar_auditoria')) {
    /**
     * Borra la auditoría antigua. Se dispara al azar en ~1 de cada 200
     * peticiones: sin un cron garantizado en el servidor, es la forma más
     * simple de que api_peticion no crezca sin fin. Si más adelante se
     * configura un cron en cPanel, conviene moverlo allí y quitar esto.
     *
     * 90 días, porque los códigos son dinero al portador y hay que poder
     * responder quién descargó qué y cuándo.
     */
    function api_purgar_auditoria($mysqli)
    {
        if (mt_rand(1, 200) !== 1) {
            return;
        }
        @$mysqli->query("DELETE FROM api_peticion WHERE ape_fecha < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    }
}

if (!function_exists('api_entero_param')) {
    /** Lee un entero de la query string con valor por defecto y tope. */
    function api_entero_param($nombre, $defecto, $minimo, $maximo)
    {
        if (!isset($_GET[$nombre]) || !is_numeric($_GET[$nombre])) {
            return $defecto;
        }
        $valor = (int)$_GET[$nombre];
        if ($valor < $minimo) {
            return $minimo;
        }
        if ($valor > $maximo) {
            return $maximo;
        }
        return $valor;
    }
}
