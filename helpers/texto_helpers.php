<?php
// Conversión de texto entre UTF-8 y ISO-8859-1 (Latin-1), para los reportes de Excel y la lista de Gestiones.
//
// Reemplaza a utf8_decode() / utf8_encode(), obsoletas desde PHP 8.2 (cada llamada imprimía un aviso "Deprecated"
// que, con los errores visibles, se colaba dentro del Excel) y eliminadas en PHP 9. Da el mismo resultado:
// los caracteres que no existen en Latin-1 salen como "?". Un valor nulo se trata como texto vacío (en PHP 8.1+
// pasar null a esas funciones también genera un aviso).
//
// Usa mbstring (activo en el hosting); si no existiera, vuelve a las funciones antiguas.
// Sin type hints nullable ni short-list syntax: PHP < 7.1 en producción.

if (!function_exists('textoLatin1')) {
    // UTF-8 -> Latin-1 (antes utf8_decode)
    function textoLatin1($s) {
        if ($s === null) {
            return '';
        }
        $s = (string)$s;
        if ($s === '') {
            return '';
        }
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
        }
        return @utf8_decode($s);
    }
}

if (!function_exists('textoUtf8')) {
    // Latin-1 -> UTF-8 (antes utf8_encode)
    function textoUtf8($s) {
        if ($s === null) {
            return '';
        }
        $s = (string)$s;
        if ($s === '') {
            return '';
        }
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
        }
        return @utf8_encode($s);
    }
}

if (!function_exists('h')) {
    // Escapa texto de la base para imprimirlo dentro de HTML (también las comillas, para atributos).
    // ENT_SUBSTITUTE: un texto con UTF-8 inválido sale con "?" en lugar de quedar vacío (PHP < 8.1 devolvía '').
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
