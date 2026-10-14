<?php
// Ventas diferidas atadas a una marca (franquicia). Toda venta diferida pertenece a una marca; por defecto ARGOS HOME
// (se puede elegir otra al registrarla). La marca decide en qué columna del Estado de Cuenta sale cada cuota.
// Sin type hints ni sintaxis de PHP nuevo: producción corre PHP < 7.1.

if (!function_exists('vdMarcaPorDefecto')) {
    // mar_id de ARGOS HOME (por nombre: los ids no son portables entre entornos). null si no existe esa marca.
    function vdMarcaPorDefecto($mysqli) {
        $r = mysqli_query($mysqli, "SELECT mar_id FROM marca WHERE UPPER(TRIM(mar_descripcion)) = 'ARGOS HOME' ORDER BY mar_id ASC LIMIT 1");
        $row = $r ? mysqli_fetch_assoc($r) : null;
        return $row ? (int)$row['mar_id'] : null;
    }
}

if (!function_exists('vdMarcaSql')) {
    // Expresión SQL de la marca efectiva de una venta diferida: la suya, o ARGOS HOME si por alguna razón no tiene.
    function vdMarcaSql($alias) {
        return "COALESCE($alias.mar_id, (SELECT mm.mar_id FROM marca mm WHERE UPPER(TRIM(mm.mar_descripcion)) = 'ARGOS HOME' ORDER BY mm.mar_id ASC LIMIT 1))";
    }
}

if (!function_exists('vdAsegurarMarca')) {
    // venta_diferida.mar_id (migrations/bloque25). Se crea sola la primera vez y las ventas que ya existían
    // quedan en ARGOS HOME (la marca por defecto).
    function vdAsegurarMarca($mysqli) {
        static $hecho = false;
        if ($hecho) {
            return;
        }
        $hecho = true;
        $r = mysqli_query($mysqli, "SELECT COUNT(*) AS n FROM information_schema.COLUMNS
                                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'venta_diferida' AND COLUMN_NAME = 'mar_id'");
        $row = $r ? mysqli_fetch_assoc($r) : null;
        if ($row && (int)$row['n'] > 0) {
            return;
        }
        mysqli_query($mysqli, "ALTER TABLE venta_diferida ADD COLUMN mar_id INT NULL");
        $def = vdMarcaPorDefecto($mysqli);
        if ($def) {
            mysqli_query($mysqli, "UPDATE venta_diferida SET mar_id = $def WHERE mar_id IS NULL");
        }
    }
}
