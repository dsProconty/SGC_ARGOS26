<?php
// Comprobación de claves al iniciar sesión y al cambiarlas.
//
// Por qué hay varias formas de la clave (todas salen de lo que la persona escribió):
//  1) Tal como se escribió (trim). Es la forma correcta y la que se guarda de ahora en adelante.
//  2) La forma antigua del login: htmlspecialchars + strip_tags + stripslashes. Desde PHP 8.1 htmlspecialchars
//     convierte también el apóstrofe (') en &#039;, y eso dejaba sin entrar a quien tuviera uno en la clave. Se
//     fijan las banderas (ENT_COMPAT) para que dé SIEMPRE el mismo resultado que daba PHP < 8.1.
//  3) La forma antigua del alta/cambio de clave: mysqli_real_escape_string (el hash quedó guardado con las
//     barras que ese escape agrega a ', " y \).
// Con esto nadie queda bloqueado al subir de versión de PHP, y las cuentas migradas (bcrypt) siguen entrando.
//
// Sin type hints nullable ni short-list syntax: PHP < 7.1 en producción.

if (!function_exists('clavesCandidatas')) {
    function clavesCandidatas($mysqli, $entrada) {
        $crudo = trim((string)$entrada);
        if ($crudo === '') {
            return array();
        }
        $formas = array(
            $crudo,
            stripslashes(strip_tags(htmlspecialchars($crudo, ENT_COMPAT | ENT_HTML401, 'UTF-8'))),
            mysqli_real_escape_string($mysqli, $crudo),
        );
        $salida = array();
        foreach ($formas as $f) {
            // htmlspecialchars devuelve '' con UTF-8 inválido: una forma vacía no puede validar nada
            if ($f !== '' && !in_array($f, $salida, true)) {
                $salida[] = $f;
            }
        }
        return $salida;
    }
}

if (!function_exists('claveCoincide')) {
    // $guardado = usuario.password (bcrypt de la migración o MD5 de las cuentas nuevas)
    function claveCoincide($guardado, $candidatas) {
        $guardado = (string)$guardado;
        $esBcrypt = (strpos($guardado, '$2y$') === 0 || strpos($guardado, '$2a$') === 0 || strpos($guardado, '$2b$') === 0);
        foreach ($candidatas as $c) {
            if ($esBcrypt) {
                if (password_verify($c, $guardado)) {
                    return true;
                }
            } elseif (hash_equals($guardado, md5($c))) {
                return true;
            }
        }
        return false;
    }
}
