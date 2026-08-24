<?php
/**
 * API externa de Gift Cards — v1
 *
 *   GET  /api/v1/ping                     ¿Mi credencial es válida?
 *   POST /api/v1/lotes                    Crear una solicitud (queda PENDING)
 *   GET  /api/v1/lotes/{sol_id}           Estado de una solicitud
 *   GET  /api/v1/lotes/{sol_id}/codigos   Códigos generados (solo si APPROVED)
 *
 * La API NO aprueba nada: las solicitudes entran como PENDING y las revisa un
 * Super Admin en "Aprobaciones Pendientes", igual que las del portal web.
 *
 * REGLA TRANSVERSAL: toda consulta filtra por el cli_id de la credencial
 * dentro del WHERE, nunca comprobando después de leer. Los códigos de Gift
 * Card son dinero al portador y un fallo de scoping deja que un cliente lea
 * los de otro.
 */

// Ni warnings ni notices en la salida: corromperían el JSON. Se registran
// en el error_log del servidor, que es donde hay que mirarlos.
error_reporting(0);
ini_set('display_errors', '0');
ob_start();

date_default_timezone_set('America/Guayaquil');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/giftcard_solicitud.php';
require_once __DIR__ . '/lib.php';

mysqli_query($mysqli, "SET time_zone = '-05:00'");

// ─────────────────────────────────────────────────────────────────────────
// Routing
// ─────────────────────────────────────────────────────────────────────────
$ruta   = isset($_GET['_ruta']) ? trim($_GET['_ruta'], '/') : '';
$metodo = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

$GLOBALS['api_ctx']['ruta']   = substr($ruta, 0, 255);
$GLOBALS['api_ctx']['metodo'] = $metodo;

$segmentos = ($ruta === '') ? array() : explode('/', $ruta);

$cred    = api_autenticar($mysqli);
$cli_id  = (int)$cred['cli_id'];
$api_id  = (int)$cred['api_id'];

// Límite general: 60 peticiones por minuto y credencial. Sin esto, un bucle
// defectuoso en el sistema del cliente puede llenar "Aprobaciones Pendientes"
// de miles de solicitudes y dejar el módulo inservible.
api_verificar_limite($mysqli, $api_id, 60, 60);

api_purgar_auditoria($mysqli);

$recurso = isset($segmentos[0]) ? $segmentos[0] : '';

// ═════════════════════════════════════════════════════════════════════════
// GET /ping — verificar credencial. Existe porque la primera pregunta de
// toda integración es "¿mi token está bien?", y sin esto se contesta a mano.
// ═════════════════════════════════════════════════════════════════════════
if ($recurso === 'ping' && count($segmentos) === 1) {
    if ($metodo !== 'GET') {
        api_error(405, 'METODO_NO_PERMITIDO', 'Use GET.');
    }
    api_responder(200, array(
        'ok'      => true,
        'cliente' => array('cli_id' => $cli_id, 'nombre' => $cred['cli_descripcion']),
        'fecha'   => date('c'),
    ));
}

if ($recurso !== 'lotes') {
    api_error(404, 'NO_ENCONTRADO', 'Recurso no encontrado.');
}

// ═════════════════════════════════════════════════════════════════════════
// POST /lotes — crear solicitud de lote
// ═════════════════════════════════════════════════════════════════════════
if (count($segmentos) === 1 && $metodo === 'POST') {
    $datos = api_leer_json();

    // El cliente NO viaja en el cuerpo: sale de la credencial. Si alguien lo
    // manda, se ignora en silencio (no es un error del que deba enterarse,
    // pero tampoco debe tener efecto alguno).
    $errores = gc_validar_solicitud($datos, true);
    if (!empty($errores)) {
        api_error(400, 'DATOS_INVALIDOS', 'Revise los campos indicados.', $errores);
    }

    // El usuario se resuelve aquí y no al autenticar: solo este endpoint
    // escribe, y los GET no tienen por qué pagar la consulta.
    $id_user = api_usuario_sistema($mysqli);

    // Sin transacción: gc_crear_solicitud es un único INSERT, que InnoDB ya
    // ejecuta de forma atómica. Envolverlo solo tendría sentido si hubiera
    // más pasos que agrupar.
    try {
        $sol_id = gc_crear_solicitud($mysqli, $id_user, $cli_id, $datos);
    } catch (Exception $e) {
        error_log('API v1 POST /lotes: ' . $e->getMessage());
        api_error(500, 'ERROR_INTERNO', 'No se pudo registrar la solicitud.');
    }

    // PUNTO DE EXTENSIÓN: aquí es donde se enchufa el aviso al Super Admin
    // (email y/o webhook). Dejado pendiente a propósito — ver decisión 4.

    api_responder(201, array(
        'sol_id'              => (int)$sol_id,
        'estado'              => 'PENDING',
        'cantidad'            => (int)$datos['cantidad'],
        'cupo_codigo'         => round((float)$datos['cupo_codigo'], 2),
        'periodo_facturacion' => $datos['periodo_facturacion'],
        'fecha_caducidad'     => $datos['fecha_caducidad'],
        'cliente'             => array('cli_id' => $cli_id, 'nombre' => $cred['cli_descripcion']),
        'mensaje'             => 'Solicitud registrada. Queda pendiente de aprobación.',
    ));
}

// A partir de aquí todas las rutas son /lotes/{sol_id}[/codigos]
if (count($segmentos) < 2) {
    api_error(405, 'METODO_NO_PERMITIDO', 'Use POST para crear una solicitud.');
}

$sol_id = $segmentos[1];
if (!ctype_digit($sol_id)) {
    api_error(404, 'NO_ENCONTRADO', 'Identificador de solicitud inválido.');
}
$sol_id = (int)$sol_id;

/**
 * Carga la solicitud SOLO si pertenece al cliente de la credencial.
 *
 * Se usa COALESCE(s.cli_id, u.cli_id) igual que aprobar_solicitud: las
 * solicitudes creadas desde el Portal Empresa dejan s.cli_id en NULL y
 * resuelven el cliente por el usuario. Así la empresa ve todas las suyas,
 * hayan nacido por API o a mano.
 *
 * Si no es suya devolvemos 404, no 403: un 403 confirmaría que ese sol_id
 * existe y pertenece a alguien.
 */
$stmt = $mysqli->prepare(
    "SELECT s.sol_id, s.sol_cantidad, s.sol_cupo_codigo, s.sol_periodo_facturacion,
            s.sol_fecha_caducidad, s.sol_estado, s.sol_fecha_solicitud, s.sol_lgc_id
     FROM giftcard_solicitud s
     JOIN usuario u ON u.id_user = s.id_user
     WHERE s.sol_id = ? AND COALESCE(s.cli_id, u.cli_id) = ?
     LIMIT 1"
);
$stmt->bind_param('ii', $sol_id, $cli_id);
$stmt->execute();
$sol = $stmt->get_result()->fetch_assoc();

if (!$sol) {
    api_error(404, 'NO_ENCONTRADO', 'Solicitud no encontrada.');
}

// ═════════════════════════════════════════════════════════════════════════
// GET /lotes/{sol_id} — estado de la solicitud
// ═════════════════════════════════════════════════════════════════════════
if (count($segmentos) === 2) {
    if ($metodo !== 'GET') {
        api_error(405, 'METODO_NO_PERMITIDO', 'Use GET.');
    }

    $respuesta = array(
        'sol_id'              => (int)$sol['sol_id'],
        'estado'              => $sol['sol_estado'],
        'cantidad'            => (int)$sol['sol_cantidad'],
        'cupo_codigo'         => (float)$sol['sol_cupo_codigo'],
        'periodo_facturacion' => $sol['sol_periodo_facturacion'],
        'fecha_caducidad'     => $sol['sol_fecha_caducidad'],
        'fecha_solicitud'     => $sol['sol_fecha_solicitud'],
        'cliente'             => array('cli_id' => $cli_id, 'nombre' => $cred['cli_descripcion']),
    );

    if ($sol['sol_estado'] === 'APPROVED' && $sol['sol_lgc_id']) {
        $respuesta['lote'] = array(
            'lgc_id'      => (int)$sol['sol_lgc_id'],
            'codigos_url' => 'lotes/' . $sol_id . '/codigos',
        );
    }

    api_responder(200, $respuesta);
}

// ═════════════════════════════════════════════════════════════════════════
// GET /lotes/{sol_id}/codigos — códigos generados, paginados
// ═════════════════════════════════════════════════════════════════════════
if (count($segmentos) === 3 && $segmentos[2] === 'codigos') {
    if ($metodo !== 'GET') {
        api_error(405, 'METODO_NO_PERMITIDO', 'Use GET.');
    }

    // Límite propio, más estricto que el general: este endpoint entrega
    // códigos con saldo, y una descarga masiva repetida es justo el patrón
    // que querríamos frenar y poder detectar en la auditoría.
    api_verificar_limite($mysqli, $api_id, 10, 60, '%/codigos');

    gc_expirar_codigos_vencidos($mysqli); // H-001/PV-B

    // 409 y no 404: la solicitud existe, pero todavía no hay nada que dar.
    // El cliente debe reintentar más tarde, no corregir la petición.
    if ($sol['sol_estado'] !== 'APPROVED' || !$sol['sol_lgc_id']) {
        api_error(409, 'LOTE_NO_DISPONIBLE',
            'La solicitud está en estado ' . $sol['sol_estado'] . '. Los códigos existen solo tras la aprobación.');
    }

    $lgc_id      = (int)$sol['sol_lgc_id'];
    $pagina      = api_entero_param('pagina', 1, 1, 1000000);
    $por_pagina  = api_entero_param('por_pagina', 100, 1, 500);
    $desplazado  = ($pagina - 1) * $por_pagina;

    $stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM codigo_gift_card WHERE lgc_id = ?");
    $stmt->bind_param('i', $lgc_id);
    $stmt->execute();
    $fila  = $stmt->get_result()->fetch_assoc();
    $total = (int)$fila['total'];

    $stmt = $mysqli->prepare(
        "SELECT cgc_codigo, cgc_cupo_inicial, cgc_cupo_disponible, cgc_estado,
                cgc_fecha_activacion, cgc_fecha_caducidad, cgc_fecha_uso
         FROM codigo_gift_card
         WHERE lgc_id = ?
         ORDER BY cgc_id ASC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param('iii', $lgc_id, $por_pagina, $desplazado);
    $stmt->execute();
    $res = $stmt->get_result();

    $codigos = array();
    while ($row = $res->fetch_assoc()) {
        $codigos[] = array(
            'codigo'           => $row['cgc_codigo'],
            'cupo_inicial'     => (float)$row['cgc_cupo_inicial'],
            'cupo_disponible'  => (float)$row['cgc_cupo_disponible'],
            'estado'           => $row['cgc_estado'],
            'fecha_activacion' => $row['cgc_fecha_activacion'],
            'fecha_caducidad'  => $row['cgc_fecha_caducidad'],
            'fecha_uso'        => $row['cgc_fecha_uso'],
        );
    }

    api_responder(200, array(
        'sol_id'  => $sol_id,
        'lgc_id'  => $lgc_id,
        'codigos' => $codigos,
        'meta'    => array(
            'total'         => $total,
            'pagina'        => $pagina,
            'por_pagina'    => $por_pagina,
            'total_paginas' => $por_pagina > 0 ? (int)ceil($total / $por_pagina) : 0,
        ),
    ));
}

api_error(404, 'NO_ENCONTRADO', 'Recurso no encontrado.');
