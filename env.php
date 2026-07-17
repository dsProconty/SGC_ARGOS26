<?php
// Carga variables de entorno desde /.env (archivo fuera de git, igual que config/database.php)
// sin depender de una librería externa. Si el archivo no existe, env() simplemente
// devuelve $default y quien la llama decide qué hacer (normalmente: fallar cerrado).

if (!function_exists('env')) {
    function env(string $key, ?string $default = null): ?string
    {
        static $loaded = false;

        if (!$loaded) {
            $envFile = __DIR__ . '/.env';
            if (is_file($envFile) && is_readable($envFile)) {
                foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                        continue;
                    }
                    [$name, $value] = explode('=', $line, 2);
                    $name  = trim($name);
                    $value = trim(trim($value), "\"'");
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
