<?php
// Carga variables de entorno desde /.env (archivo fuera de git, igual que config/database.php)
// sin depender de una librería externa. Si el archivo no existe, env() simplemente
// devuelve $default y quien la llama decide qué hacer (normalmente: fallar cerrado).
//
// Sin type hints ni short-list syntax a propósito: el servidor de producción
// corre una versión de PHP anterior a 7.1 y ambas cosas son fatales ahí.

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        static $loaded = false;

        if (!$loaded) {
            $envFile = __DIR__ . '/.env';
            if (is_file($envFile) && is_readable($envFile)) {
                foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                        continue;
                    }
                    $parts = explode('=', $line, 2);
                    $name  = trim($parts[0]);
                    $value = trim(trim($parts[1]), "\"'");
                    if (getenv($name) === false) {
                        putenv("$name=$value");
                    }
                }
            }
            $loaded = true;
        }

        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}
