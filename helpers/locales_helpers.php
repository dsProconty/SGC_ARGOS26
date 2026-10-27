<?php
// Inactivar / reactivar locales (sucursales). Un local NUNCA se elimina: consumo, consumo_movimiento_local y
// usuario lo referencian por id. Inactivo = no vende (el POS lo rechaza), no sale en los selectores de
// asignación, pero sus ventas pasadas siguen en historial y reportes.
// Columnas y tabla de historial: migrations/bloque26_local_cierre.sql (el código las crea solo si faltan).
require_once __DIR__ . '/db_helpers.php';

function localesAsegurarEsquema($mysqli) {
    static $hecho = false;
    if ($hecho) { return; }
    $hecho = true;
    agregarColumnaSiNoExiste($mysqli, 'local', 'loc_motivo_cierre', 'VARCHAR(255) NULL');
    agregarColumnaSiNoExiste($mysqli, 'local', 'loc_fecha_cierre', 'DATE NULL');
    $existe = $mysqli->query(
        "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'local_trazabilidad'"
    );
    if ($existe && $existe->num_rows > 0) { return; }
    $mysqli->query(
        "CREATE TABLE IF NOT EXISTS local_trazabilidad (
            tra_id INT NOT NULL AUTO_INCREMENT,
            loc_id INT NOT NULL,
            id_user INT NOT NULL,
            tra_accion VARCHAR(20) NOT NULL,
            tra_motivo VARCHAR(255) NULL,
            tra_fecha_cierre DATE NULL,
            tra_fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (tra_id),
            KEY idx_ltra_loc (loc_id, tra_fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Historial de cierres y reaperturas de un local'"
    );
}

// true si el usuario de la sesión tiene un local asignado y ese local está inactivo.
// Super Admin sin local (modo pruebas) no entra aquí: su local se valida al elegirlo (resolverLocId).
function localSesionInactivo($mysqli) {
    $loc = (int)(isset($_SESSION['loc_id']) ? $_SESSION['loc_id'] : 0);
    if ($loc <= 0) { return false; }
    $fila = dbFila($mysqli, "SELECT loc_activo FROM local WHERE loc_id = $loc LIMIT 1");
    return $fila ? ((int)$fila['loc_activo'] === 0) : false;
}

// Fecha dd/mm/aaaa o aaaa-mm-dd -> aaaa-mm-dd válida y no futura, o null.
function localesFechaCierreValida($texto) {
    $texto = trim((string)$texto);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $texto, $m)) { return null; }
    if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) { return null; }
    if ($texto > date('Y-m-d')) { return null; }
    return $texto;
}

// Al inactivar hacen falta motivo (mín. 3 caracteres) y fecha de cierre válida y no futura.
// Devuelve '' si todo está bien o el mensaje para mostrar; $motivo y $fecha quedan limpios.
function localesValidarCierre($activo, &$motivo, &$fecha) {
    $motivo = trim((string)$motivo);
    if ($activo) { $motivo = ''; $fecha = ''; return ''; }
    if (function_exists('mb_strlen') ? mb_strlen($motivo, 'UTF-8') < 3 : strlen($motivo) < 3) {
        return 'Indique el motivo del cierre (mínimo 3 caracteres)';
    }
    if (function_exists('mb_substr')) { $motivo = mb_substr($motivo, 0, 255, 'UTF-8'); }
    $f = localesFechaCierreValida($fecha);
    if ($f === null) { return 'Indique una fecha de cierre válida (no puede ser futura)'; }
    $fecha = $f;
    return '';
}

// Cuántos usuarios activos tienen asignado cada local de la lista (ids ya enteros).
function localesUsuariosActivos($mysqli, $ids) {
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (!$ids) { return array(); }
    $r = $mysqli->query(
        "SELECT loc_id, COUNT(*) AS n FROM usuario
         WHERE loc_id IN (" . implode(',', $ids) . ") AND status = 'activo' GROUP BY loc_id"
    );
    $out = array();
    while ($r && ($f = $r->fetch_assoc())) { $out[(int)$f['loc_id']] = (int)$f['n']; }
    return $out;
}

// Cambia el estado de un local y deja el rastro. $activo 1|0. Devuelve true si cambió algo.
// Al inactivar exige motivo y fecha de cierre (ya validados por quien llama); al reactivar los limpia
// (quedan en el historial).
function localesAplicarEstado($mysqli, $loc_id, $activo, $motivo, $fecha, $id_user) {
    $loc_id = (int)$loc_id;
    $activo = $activo ? 1 : 0;
    $antes = dbFila($mysqli, "SELECT loc_activo, loc_motivo_cierre, loc_fecha_cierre FROM local WHERE loc_id = $loc_id LIMIT 1");
    if (!$antes) { return false; }
    $eraActivo = ((int)$antes['loc_activo'] === 1);
    $motivoEsc = mysqli_real_escape_string($mysqli, $motivo);
    if ($activo === 0) {
        $igual = !$eraActivo && (string)$antes['loc_motivo_cierre'] === (string)$motivo && (string)$antes['loc_fecha_cierre'] === (string)$fecha;
        if ($igual) { return false; }
        $mysqli->query("UPDATE local SET loc_activo = 0, loc_motivo_cierre = '$motivoEsc', loc_fecha_cierre = '" . mysqli_real_escape_string($mysqli, $fecha) . "' WHERE loc_id = $loc_id");
        $accion = $eraActivo ? 'inactivado' : 'motivo editado';
        $mysqli->query("INSERT INTO local_trazabilidad (loc_id, id_user, tra_accion, tra_motivo, tra_fecha_cierre)
                        VALUES ($loc_id, " . (int)$id_user . ", '$accion', '$motivoEsc', '" . mysqli_real_escape_string($mysqli, $fecha) . "')");
        return true;
    }
    if ($eraActivo) { return false; }
    $mysqli->query("UPDATE local SET loc_activo = 1, loc_motivo_cierre = NULL, loc_fecha_cierre = NULL WHERE loc_id = $loc_id");
    $mysqli->query("INSERT INTO local_trazabilidad (loc_id, id_user, tra_accion) VALUES ($loc_id, " . (int)$id_user . ", 'reactivado')");
    return true;
}
