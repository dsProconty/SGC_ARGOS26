<?php
// Bloqueo / reactivación de Gift Cards ya aprobadas (por lote o por códigos sueltos).
//
// Un código bloqueado conserva su saldo y su caducidad; solo cambia cgc_estado a 'bloqueado', y el POS
// (que únicamente acepta 'activo') lo rechaza. Reactivar lo devuelve al estado que le corresponde HOY:
// 'consumido' si no tiene saldo, 'vencido' si ya pasó la caducidad, 'activo' en los demás casos.
// Bloquear NO toca la cartera de Cobranza del lote: la empresa sigue debiendo lo que se le facturó.
// Cada acción queda en giftcard_bloqueo_historial (quién, cuándo, motivo, cuántos códigos).
//
// Sin type hints ni sintaxis de PHP nuevo: producción corre PHP < 7.1.

if (!function_exists('gcBloqueoAsegurarEsquema')) {
    function gcBloqueoAsegurarEsquema($mysqli) {
        mysqli_query($mysqli, "CREATE TABLE IF NOT EXISTS giftcard_bloqueo_historial (
            gbh_id       INT AUTO_INCREMENT PRIMARY KEY,
            lgc_id       INT NOT NULL,
            gbh_accion   VARCHAR(12) NOT NULL,
            gbh_alcance  VARCHAR(10) NOT NULL,
            gbh_cantidad INT NOT NULL DEFAULT 0,
            gbh_codigos  TEXT NULL,
            gbh_motivo   VARCHAR(255) NULL,
            id_user      INT NOT NULL,
            gbh_fecha    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_gbh_lote (lgc_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

if (!function_exists('gcBloqueosDeLote')) {
    // Historial de bloqueos/reactivaciones de un lote, más reciente primero
    function gcBloqueosDeLote($mysqli, $lgc_id) {
        gcBloqueoAsegurarEsquema($mysqli);
        $lgc_id = (int)$lgc_id;
        $filas  = array();
        $r = mysqli_query($mysqli, "SELECT h.gbh_accion, h.gbh_alcance, h.gbh_cantidad, h.gbh_codigos, h.gbh_motivo, h.gbh_fecha, u.name_user
                                    FROM giftcard_bloqueo_historial h LEFT JOIN usuario u ON u.id_user = h.id_user
                                    WHERE h.lgc_id = $lgc_id ORDER BY h.gbh_id DESC");
        if ($r) {
            while ($f = mysqli_fetch_assoc($r)) {
                $filas[] = $f;
            }
        }
        return $filas;
    }
}

if (!function_exists('gcBloqueoCambiar')) {
    /**
     * @param array|null $cgcIds ids de codigo_gift_card a tocar; null = todo el lote
     * @param bool       $bloquear true = bloquear (solo códigos 'activo'); false = reactivar (solo 'bloqueado')
     * @return array ['success' => bool, 'mensaje' => string, 'afectados' => int]
     */
    function gcBloqueoCambiar($mysqli, $lgc_id, $cgcIds, $bloquear, $motivo, $id_user) {
        gcBloqueoAsegurarEsquema($mysqli);
        $lgc_id  = (int)$lgc_id;
        $id_user = (int)$id_user;
        $motivo  = trim((string)$motivo);
        if (function_exists('mb_substr')) {
            $motivo = mb_substr($motivo, 0, 255, 'UTF-8');
        } else {
            $motivo = substr($motivo, 0, 255);
        }

        if ($bloquear && (function_exists('mb_strlen') ? mb_strlen($motivo, 'UTF-8') : strlen($motivo)) < 3) {
            return array('success' => false, 'mensaje' => 'Indique el motivo del bloqueo (mínimo 3 caracteres)', 'afectados' => 0);
        }
        $rLote = mysqli_query($mysqli, "SELECT lgc_id FROM lote_gift_card WHERE lgc_id = $lgc_id LIMIT 1");
        if (!$rLote || !mysqli_fetch_assoc($rLote)) {
            return array('success' => false, 'mensaje' => 'Lote no encontrado', 'afectados' => 0);
        }

        $filtroIds = '';
        $alcance   = 'lote';
        if (is_array($cgcIds)) {
            $ids = array();
            foreach ($cgcIds as $i) {
                $i = (int)$i;
                if ($i > 0) {
                    $ids[$i] = $i;
                }
            }
            if (!count($ids)) {
                return array('success' => false, 'mensaje' => 'Seleccione al menos un código', 'afectados' => 0);
            }
            $filtroIds = ' AND cgc_id IN (' . implode(',', $ids) . ')';
            $alcance   = 'codigos';
        }

        $desde = $bloquear ? 'activo' : 'bloqueado';
        if ($bloquear) {
            $set = "cgc_estado = 'bloqueado'";
        } else {
            $set = "cgc_estado = CASE WHEN cgc_cupo_disponible <= 0 THEN 'consumido'
                                      WHEN cgc_fecha_caducidad IS NOT NULL AND cgc_fecha_caducidad < CURDATE() THEN 'vencido'
                                      ELSE 'activo' END";
        }

        // Códigos que se van a tocar (para el historial y para contar con exactitud)
        $rSel = mysqli_query($mysqli, "SELECT cgc_codigo FROM codigo_gift_card
                                       WHERE lgc_id = $lgc_id AND cgc_estado = '$desde'$filtroIds ORDER BY cgc_id");
        $codigos = array();
        if ($rSel) {
            while ($f = mysqli_fetch_assoc($rSel)) {
                $codigos[] = $f['cgc_codigo'];
            }
        }
        if (!count($codigos)) {
            return array('success' => false, 'afectados' => 0,
                'mensaje' => $bloquear ? 'No hay códigos activos para bloquear' : 'No hay códigos bloqueados para reactivar');
        }

        mysqli_query($mysqli, "START TRANSACTION");
        $ok = mysqli_query($mysqli, "UPDATE codigo_gift_card SET $set WHERE lgc_id = $lgc_id AND cgc_estado = '$desde'$filtroIds");
        $afectados = $ok ? mysqli_affected_rows($mysqli) : 0;
        if ($ok) {
            $lista = ($alcance === 'codigos') ? implode(', ', array_slice($codigos, 0, 100)) . (count($codigos) > 100 ? ', …' : '') : '';
            $accion = $bloquear ? 'bloqueado' : 'reactivado';
            $stmt = mysqli_prepare($mysqli, "INSERT INTO giftcard_bloqueo_historial (lgc_id, gbh_accion, gbh_alcance, gbh_cantidad, gbh_codigos, gbh_motivo, id_user)
                                             VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ississi', $lgc_id, $accion, $alcance, $afectados, $lista, $motivo, $id_user);
                $ok = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } else {
                $ok = false;
            }
        }
        if (!$ok) {
            mysqli_query($mysqli, "ROLLBACK");
            return array('success' => false, 'mensaje' => 'No se pudo guardar el cambio', 'afectados' => 0);
        }
        mysqli_query($mysqli, "COMMIT");

        $txt = $bloquear ? 'bloqueado' : 'reactivado';
        return array('success' => true, 'afectados' => $afectados,
            'mensaje' => ($afectados === 1 ? '1 código ' : $afectados . ' códigos ') . ($afectados === 1 ? $txt : $txt . 's') . ' correctamente');
    }
}
