<?php
// Compatibilidad de mysqli entre las versiones de PHP del hosting.
//
// Desde PHP 8.1 mysqli lanza excepciones (mysqli_sql_exception) ante cualquier error de SQL; hasta PHP 8.0 la
// consulta fallida solo devolvía false y la página seguía. Todo el código del sistema está escrito para el segundo
// comportamiento (mysqli_query(...) or die, if (!$result)...), así que se fija explícitamente: así una consulta que
// falla se comporta igual en PHP 7.0 que en 8.4, en lugar de tumbar la página con un error fatal.
//
// Se incluye justo después de config/database.php (ese archivo no va en git: es propio de cada servidor).
// Sin type hints nullable ni short-list syntax: PHP < 7.1 en producción.

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

if (!function_exists('dbFila')) {
    // Primera fila de una consulta como arreglo asociativo, o null si no hay filas o la consulta falló.
    // Reemplaza a mysqli_fetch_assoc(mysqli_query(...)): en PHP 8, si la consulta falla, esa forma lanza
    // un TypeError (mysqli_fetch_assoc recibe false).
    function dbFila($mysqli, $sql) {
        $r = mysqli_query($mysqli, $sql);
        if (!$r) {
            return null;
        }
        $f = mysqli_fetch_assoc($r);
        return $f ? $f : null;
    }
}
