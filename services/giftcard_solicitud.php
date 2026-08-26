<?php
/**
 * Reglas de creación de una solicitud de lote de Gift Cards.
 *
 * Fuente única de verdad para el modal "Crear Nuevos Lotes"
 * (ajax/giftcard/giftcard.php) y para la API externa (api/v1). Si las reglas
 * viven en dos sitios, se desincronizan en el primer cambio de requisitos.
 *
 * Sin type hints nullable ni short-list syntax: producción corre PHP < 7.1
 * (ver env.php) y ambas cosas son errores fatales ahí.
 */

if (!function_exists('gc_fecha_valida')) {
    /**
     * true solo si $valor es una fecha real en formato Y-m-d.
     * strtotime() es demasiado permisivo ('mañana', '31-31-31'...) y con
     * MySQL en modo estricto una fecha inválida aborta el INSERT con un
     * error genérico difícil de diagnosticar. Mejor rechazarla antes.
     */
    function gc_fecha_valida($valor)
    {
        if (!is_string($valor) || $valor === '') {
            return false;
        }
        $d = DateTime::createFromFormat('Y-m-d', $valor);
        return $d !== false && $d->format('Y-m-d') === $valor;
    }
}

if (!function_exists('gc_validar_solicitud')) {
    /**
     * Valida los datos de una solicitud y devuelve un array campo => mensaje.
     * Array vacío = todo correcto.
     *
     * $estricto añade las comprobaciones que SOLO aplica la API externa.
     * El modal se queda con las reglas de siempre a propósito: endurecerlas
     * para la UI cambiaría el comportamiento de una pantalla que hoy funciona
     * (de hecho ya existen solicitudes con la caducidad anterior al período,
     * ej. sol_id 7), y esa es una decisión de producto aparte.
     */
    function gc_validar_solicitud($datos, $estricto = false)
    {
        $errores = [];

        // ── Cantidad: entero entre 1 y 1000 ──────────────────────────────
        $cantidad_cruda = isset($datos['cantidad']) ? $datos['cantidad'] : null;
        if (!is_numeric($cantidad_cruda) || (int)$cantidad_cruda != (float)$cantidad_cruda) {
            $errores['cantidad'] = 'Debe ser un número entero.';
        } else {
            $cantidad = (int)$cantidad_cruda;
            if ($cantidad < 1 || $cantidad > 1000) {
                $errores['cantidad'] = 'Debe estar entre 1 y 1000.';
            }
        }

        // ── Cupo por código: mayor que cero ──────────────────────────────
        $cupo_crudo = isset($datos['cupo_codigo']) ? $datos['cupo_codigo'] : null;
        if (!is_numeric($cupo_crudo)) {
            $errores['cupo_codigo'] = 'Debe ser un número.';
        } elseif ((float)$cupo_crudo <= 0) {
            $errores['cupo_codigo'] = 'Debe ser mayor que cero.';
        }

        // ── Fechas ───────────────────────────────────────────────────────
        $periodo   = isset($datos['periodo_facturacion']) ? $datos['periodo_facturacion'] : null;
        $caducidad = isset($datos['fecha_caducidad']) ? $datos['fecha_caducidad'] : null;

        if (!gc_fecha_valida($periodo)) {
            $errores['periodo_facturacion'] = 'Fecha requerida en formato AAAA-MM-DD.';
        }
        if (!gc_fecha_valida($caducidad)) {
            $errores['fecha_caducidad'] = 'Fecha requerida en formato AAAA-MM-DD.';
        }

        if ($estricto && !isset($errores['periodo_facturacion']) && !isset($errores['fecha_caducidad'])) {
            if ($caducidad < $periodo) {
                $errores['fecha_caducidad'] = 'No puede ser anterior al período de facturación.';
            } elseif ($caducidad < date('Y-m-d')) {
                $errores['fecha_caducidad'] = 'No puede estar en el pasado.';
            }
        }

        return $errores;
    }
}

if (!function_exists('gc_expirar_codigos_vencidos')) {
    /**
     * Marca como 'vencido' cualquier código activo cuya fecha de caducidad
     * ya pasó. Sin cron garantizado en el servidor (mismo motivo que
     * api_purgar_auditoria en api/v1/lib.php), se llama al cargar cualquier
     * lista que muestre cgc_estado, para que nunca se vea "activo" un
     * código ya vencido (H-001/PV-B): antes cgc_estado solo se corregía al
     * buscar ESE código puntual en POS, no en las listas.
     */
    function gc_expirar_codigos_vencidos($mysqli)
    {
        @$mysqli->query(
            "UPDATE codigo_gift_card SET cgc_estado = 'vencido'
             WHERE cgc_estado = 'activo' AND cgc_fecha_caducidad IS NOT NULL
               AND cgc_fecha_caducidad < CURDATE()"
        );
    }
}

if (!function_exists('gc_crear_solicitud')) {
    /**
     * Inserta la solicitud en estado PENDING y devuelve su sol_id.
     * NO valida (llamar antes a gc_validar_solicitud) y NO abre transacción:
     * de eso se encarga quien llama, que puede necesitar agrupar más pasos.
     *
     * $cli_id puede ser null: las solicitudes nacidas en el Portal Empresa
     * resuelven el cliente vía usuario.cli_id (ver el COALESCE de
     * aprobar_solicitud). La API siempre lo pasa.
     *
     * Lanza Exception si el INSERT falla.
     */
    function gc_crear_solicitud($mysqli, $id_user, $cli_id, $datos)
    {
        $cantidad  = (int)$datos['cantidad'];
        $cupo      = (float)$datos['cupo_codigo'];
        $periodo   = $datos['periodo_facturacion'];
        $caducidad = $datos['fecha_caducidad'];

        $stmt = $mysqli->prepare(
            "INSERT INTO giftcard_solicitud
                (id_user, sol_cantidad, sol_cupo_codigo, sol_periodo_facturacion,
                 sol_fecha_caducidad, sol_estado, cli_id)
             VALUES (?, ?, ?, ?, ?, 'PENDING', ?)"
        );
        if (!$stmt) {
            throw new Exception('Funcionalidad no disponible. Ejecute la migración bloque3_giftcard_approval.sql.');
        }

        // bind_param exige variables por referencia; null se pasa igual y
        // mysqli lo convierte en NULL SQL al ser el tipo 'i'.
        $cli_id_bind = ($cli_id === null || $cli_id === 0) ? null : (int)$cli_id;
        $stmt->bind_param('iidssi', $id_user, $cantidad, $cupo, $periodo, $caducidad, $cli_id_bind);

        if (!$stmt->execute()) {
            throw new Exception('Error al crear la solicitud');
        }

        return $mysqli->insert_id;
    }
}
