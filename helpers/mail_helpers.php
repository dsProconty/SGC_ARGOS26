<?php
// Envío de correo compartido entre Gift Card (aprobación/rechazo) y Estados
// de Cuenta (envío automático/manual). Antes vivía duplicado dentro de
// ajax/giftcard/giftcard.php; se centraliza aquí para que ambos módulos usen
// la misma función.
//
// Sin type hints nullable ni short-list syntax: PHP < 7.1 en producción.

if (!function_exists('enviar_email')) {
    function enviar_email($para, $asunto, $cuerpo) {
        if (!$para) {
            return false;
        }
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: SGC ARGOS <no-reply@sgcargos.com>\r\n";
        return @mail($para, $asunto, $cuerpo, $headers);
    }
}
