<?php
// Lógica compartida del módulo Estado de Cuenta: generación, detalle/resumen
// financiero, envío por correo y el disparador de generación+envío
// automático por fecha de corte (cli_dia_corte).
//
// Sin type hints nullable ni short-list syntax: PHP < 7.1 en producción.

require_once __DIR__ . '/../helpers/mail_helpers.php';

if (!function_exists('ec_generar_estado_cuenta')) {
    /**
     * Inserta un estado_cuenta para el cliente/período dados, sumando
     * consumos regulares + cuotas de venta diferida que caen en el rango.
     * Devuelve el ec_id insertado, o false si falló.
     */
    function ec_generar_estado_cuenta($mysqli, $cli_id, $periodo_inicio, $periodo_fin) {
        $cli_id         = (int)$cli_id;
        $periodo_inicio = mysqli_real_escape_string($mysqli, $periodo_inicio);
        $periodo_fin    = mysqli_real_escape_string($mysqli, $periodo_fin);

        $q_total = "SELECT COALESCE(SUM(con.con_valor_total), 0) AS total
                    FROM consumo con
                    JOIN personal p ON con.per_id = p.per_id
                    WHERE p.cli_id = $cli_id
                      AND con.con_fecha BETWEEN '$periodo_inicio' AND '$periodo_fin'";
        $r_total = mysqli_query($mysqli, $q_total);
        $total   = (float)mysqli_fetch_assoc($r_total)['total'];

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
            return false;
        }
        return mysqli_insert_id($mysqli);
    }
}

if (!function_exists('ec_obtener_detalle')) {
    /**
     * Arma el detalle completo de un estado_cuenta (consumos regulares +
     * cuotas de venta diferida, marcas, pivote por beneficiario/marca,
     * saldos acumulados) y el resumen financiero (venta neta, IVA, comisión,
     * total a pagar) con la misma fórmula que renderEC() en js/estado_cuenta.js.
     * Devuelve null si el ec_id no existe.
     */
    function ec_obtener_detalle($mysqli, $ec_id) {
        $ec_id = (int)$ec_id;
        $q_ec  = "SELECT ec.*, c.cli_descripcion, c.cli_email, c.cli_contacto, c.cli_telefono, c.cli_comision
                   FROM estado_cuenta ec
                   JOIN cliente c ON ec.cli_id = c.cli_id
                   WHERE ec.ec_id = $ec_id";
        $r_ec  = mysqli_query($mysqli, $q_ec);

        if (!$r_ec || mysqli_num_rows($r_ec) === 0) {
            return null;
        }

        $ec     = mysqli_fetch_assoc($r_ec);
        $cli_id = (int)$ec['cli_id'];
        $p_ini  = $ec['ec_periodo_inicio'];
        $p_fin  = $ec['ec_periodo_fin'];

        // Detalle de consumos regulares
        $q_det = "SELECT con.con_fecha, con.con_hora, p.per_nombre, p.per_documento,
                         p.per_numero_tarjeta, l.loc_direccion, m.mar_descripcion, con.con_valor_neto, con.con_iva,
                         con.con_valor_total, con.con_monto_convenio, con.con_monto_externo,
                         con.con_descripcion, 'consumo' AS origen
                  FROM consumo con
                  JOIN personal p ON con.per_id = p.per_id
                  LEFT JOIN local l ON con.loc_id = l.loc_id
                  LEFT JOIN marca m ON l.mar_id = m.mar_id
                  WHERE p.cli_id = $cli_id
                    AND con.con_fecha BETWEEN '$p_ini' AND '$p_fin'";
        $r_det = mysqli_query($mysqli, $q_det);

        $detalles = array();
        while ($d = mysqli_fetch_assoc($r_det)) {
            $detalles[] = $d;
        }

        // Detalle de cuotas de ventas diferidas que caen en el período
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
                     NULL                          AS mar_descripcion,
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

        usort($detalles, function ($a, $b) {
            return strcmp($a['con_fecha'] . ($a['con_hora'] ? $a['con_hora'] : ''), $b['con_fecha'] . ($b['con_hora'] ? $b['con_hora'] : ''));
        });

        $q_marcas = "SELECT DISTINCT COALESCE(m.mar_id, 0) AS mar_id,
                            COALESCE(m.mar_descripcion, 'Sin local asignado') AS mar_descripcion
                     FROM consumo con
                     JOIN personal per ON con.per_id = per.per_id
                     LEFT JOIN local l ON con.loc_id = l.loc_id
                     LEFT JOIN marca m ON l.mar_id = m.mar_id
                     WHERE per.cli_id = $cli_id
                       AND con.con_fecha BETWEEN '$p_ini' AND '$p_fin'
                     ORDER BY mar_descripcion ASC";
        $r_marcas = mysqli_query($mysqli, $q_marcas);
        $marcas   = array();
        while ($m = mysqli_fetch_assoc($r_marcas)) {
            $marcas[] = $m;
        }

        $q_pivot = "SELECT per.per_id, per.per_nombre, per.per_documento,
                           COALESCE(m.mar_id, 0) AS mar_id, SUM(con.con_valor_total) AS total_marca
                    FROM consumo con
                    JOIN personal per ON con.per_id = per.per_id
                    LEFT JOIN local l ON con.loc_id = l.loc_id
                    LEFT JOIN marca m ON l.mar_id = m.mar_id
                    WHERE per.cli_id = $cli_id
                      AND con.con_fecha BETWEEN '$p_ini' AND '$p_fin'
                    GROUP BY per.per_id, COALESCE(m.mar_id, 0)
                    ORDER BY per.per_nombre ASC";
        $r_pivot    = mysqli_query($mysqli, $q_pivot);
        $pivot_rows = array();
        while ($row = mysqli_fetch_assoc($r_pivot)) {
            $pivot_rows[] = $row;
        }

        $q_saldo = "SELECT per.per_id, SUM(con.con_valor_total) AS saldo_acumulado
                    FROM consumo con
                    JOIN personal per ON con.per_id = per.per_id
                    WHERE per.cli_id = $cli_id
                    GROUP BY per.per_id";
        $r_saldo = mysqli_query($mysqli, $q_saldo);
        $saldos  = array();
        while ($row = mysqli_fetch_assoc($r_saldo)) {
            $saldos[$row['per_id']] = (float)$row['saldo_acumulado'];
        }

        // Resumen financiero — misma fórmula que renderEC() en js/estado_cuenta.js
        $venta_neta  = 0;
        $total_iva   = 0;
        $total_venta = 0;
        foreach ($detalles as $d) {
            $venta_neta  += (float)$d['con_valor_neto'];
            $total_iva   += (float)$d['con_iva'];
            $total_venta += (float)$d['con_valor_total'];
        }
        $comision_pct   = (float)$ec['cli_comision'];
        $comision_monto = $venta_neta * $comision_pct / 100;
        $total_pagar    = $total_venta - $comision_monto;

        return array(
            'ec'         => $ec,
            'detalles'   => $detalles,
            'marcas'     => $marcas,
            'pivot_rows' => $pivot_rows,
            'saldos'     => $saldos,
            'resumen'    => array(
                'venta_neta'     => $venta_neta,
                'iva'            => $total_iva,
                'total_venta'    => $total_venta,
                'comision_pct'   => $comision_pct,
                'comision_monto' => $comision_monto,
                'total_pagar'    => $total_pagar,
            ),
        );
    }
}

if (!function_exists('ec_enviar_correo')) {
    /**
     * Envía por correo el resumen del estado de cuenta al cli_email
     * registrado. Actualiza ec_estado_envio/ec_fecha_envio/ec_reintentos
     * según el resultado. Usado tanto por el botón manual "Enviar" como por
     * la generación automática por fecha de corte.
     */
    function ec_enviar_correo($mysqli, $ec_id) {
        $ec_id = (int)$ec_id;
        $data  = ec_obtener_detalle($mysqli, $ec_id);

        if (!$data) {
            return array('success' => false, 'mensaje' => 'Estado de cuenta no encontrado');
        }

        $ec    = $data['ec'];
        $email = trim($ec['cli_email'] ? $ec['cli_email'] : '');

        if (!$email) {
            mysqli_query($mysqli, "UPDATE estado_cuenta SET ec_estado_envio = 'error' WHERE ec_id = $ec_id");
            return array('success' => false, 'mensaje' => 'El cliente no tiene un correo registrado');
        }

        $r = $data['resumen'];

        $asunto = 'Estado de Cuenta - ' . $ec['cli_descripcion'] . ' ('
            . date('d/m/Y', strtotime($ec['ec_periodo_inicio'])) . ' al '
            . date('d/m/Y', strtotime($ec['ec_periodo_fin'])) . ')';

        $cuerpo = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#2c2c2c;max-width:600px;margin:0 auto;">'
            . '<div style="background-color:#6d1b3a;color:#fff;padding:16px 20px;">'
            . '<h2 style="margin:0;">SGC ARGOS</h2>'
            . '<p style="margin:4px 0 0;">Estado de Cuenta</p>'
            . '</div>'
            . '<div style="padding:20px;border:1px solid #e6d3da;border-top:none;">'
            . '<p>Estimado(a) <strong>' . htmlspecialchars($ec['cli_descripcion']) . '</strong>,</p>'
            . '<p>Le compartimos el resumen de su estado de cuenta correspondiente al período <strong>'
            . date('d/m/Y', strtotime($ec['ec_periodo_inicio'])) . '</strong> al <strong>'
            . date('d/m/Y', strtotime($ec['ec_periodo_fin'])) . '</strong>.</p>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:16px;font-size:13px;">'
            . '<tr><td style="padding:6px 0;">Venta neta</td><td style="text-align:right;">$' . number_format($r['venta_neta'], 2) . '</td></tr>'
            . '<tr><td style="padding:6px 0;">IVA</td><td style="text-align:right;">$' . number_format($r['iva'], 2) . '</td></tr>'
            . '<tr style="border-top:1px solid #e6d3da;"><td style="padding:6px 0;"><strong>Total venta</strong></td><td style="text-align:right;"><strong>$' . number_format($r['total_venta'], 2) . '</strong></td></tr>'
            . '<tr><td style="padding:6px 0;">Comisión (' . number_format($r['comision_pct'], 2) . '%)</td><td style="text-align:right;">$' . number_format($r['comision_monto'], 2) . '</td></tr>'
            . '<tr style="background-color:#4a1226;color:#fff;"><td style="padding:8px 6px;"><strong>TOTAL A PAGAR</strong></td><td style="text-align:right;padding:8px 6px;"><strong>$' . number_format($r['total_pagar'], 2) . '</strong></td></tr>'
            . '</table>'
            . '<p style="margin-top:20px;">Para ver el detalle completo de consumos y descargar el documento, ingrese al sistema SGC ARGOS.</p>'
            . '<p style="color:#888;font-size:12px;margin-top:24px;">Este es un correo automático, por favor no responda a este mensaje.</p>'
            . '</div></div>';

        $enviado = enviar_email($email, $asunto, $cuerpo);

        if ($enviado) {
            mysqli_query($mysqli, "UPDATE estado_cuenta SET ec_estado_envio = 'enviado', ec_fecha_envio = NOW() WHERE ec_id = $ec_id");
            return array('success' => true, 'mensaje' => 'Estado de cuenta enviado a ' . $email);
        }

        mysqli_query($mysqli, "UPDATE estado_cuenta SET ec_estado_envio = 'error', ec_reintentos = ec_reintentos + 1 WHERE ec_id = $ec_id");
        return array('success' => false, 'mensaje' => 'No se pudo enviar el correo a ' . $email);
    }
}

if (!function_exists('ec_generar_y_enviar_automaticos')) {
    /**
     * Genera y envía automáticamente el estado de cuenta de cada cliente
     * cuyo día de corte (cliente.cli_dia_corte) es hoy. El servidor no tiene
     * cron real, así que esto se dispara desde content.php en cada carga de
     * módulo de un usuario autenticado; una marca en `configuracion`
     * garantiza que la corrida real (consultar clientes, generar, enviar)
     * ocurra como máximo una vez por día, no en cada request. Si más
     * adelante se configura un cron en cPanel, conviene moverlo allí.
     */
    function ec_generar_y_enviar_automaticos($mysqli) {
        $hoy = date('Y-m-d');

        $r     = mysqli_query($mysqli, "SELECT cfg_valor FROM configuracion WHERE cfg_clave = 'ec_auto_ultima_corrida'");
        $row   = $r ? mysqli_fetch_assoc($r) : null;
        $marca = $row ? $row['cfg_valor'] : '';
        if ($marca === $hoy) {
            return;
        }

        // Marcar primero: evita que dos requests casi simultáneas dupliquen envíos.
        mysqli_query($mysqli, "INSERT INTO configuracion (cfg_clave, cfg_valor, cfg_descripcion)
                                VALUES ('ec_auto_ultima_corrida', '" . mysqli_real_escape_string($mysqli, $hoy) . "', 'Última fecha en que corrió el envío automático de estados de cuenta')
                                ON DUPLICATE KEY UPDATE cfg_valor = VALUES(cfg_valor)");

        $diaHoy       = (int)date('j');
        $ultimoDiaMes = (int)date('t');

        $res = mysqli_query($mysqli, "SELECT cli_id, cli_dia_corte FROM cliente WHERE cli_dia_corte IS NOT NULL AND cli_dia_corte != '0' AND cli_dia_corte != ''");
        if (!$res) {
            return;
        }

        while ($cli = mysqli_fetch_assoc($res)) {
            $dia = (int)$cli['cli_dia_corte'];
            if ($dia <= 0) {
                continue;
            }
            // Si el día de corte no existe en el mes actual (ej. 31 en febrero), correr el último día del mes.
            $diaEfectivo = min($dia, $ultimoDiaMes);
            if ($diaEfectivo !== $diaHoy) {
                continue;
            }

            $cli_id = (int)$cli['cli_id'];

            // No duplicar si ya existe un estado de cuenta generado hoy para este cliente.
            $chk = mysqli_query($mysqli, "SELECT ec_id FROM estado_cuenta WHERE cli_id = $cli_id AND ec_periodo_fin = '$hoy' LIMIT 1");
            if ($chk && mysqli_num_rows($chk) > 0) {
                continue;
            }

            $periodo_inicio = date('Y-m-d', strtotime('-1 month', strtotime($hoy)));
            $periodo_fin    = $hoy;

            $ec_id = ec_generar_estado_cuenta($mysqli, $cli_id, $periodo_inicio, $periodo_fin);
            if ($ec_id) {
                ec_enviar_correo($mysqli, $ec_id);
            }
        }
    }
}
