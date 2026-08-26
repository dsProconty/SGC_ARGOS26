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

if (!function_exists('enviar_email_adjunto')) {
    /**
     * Igual que enviar_email() pero con un archivo adjunto (ej. el PDF del
     * estado de cuenta). mail() nativo no soporta adjuntos por sí solo: hay
     * que armar el mensaje multipart/mixed a mano (cuerpo HTML + adjunto en
     * base64), técnica estándar para no depender de una librería SMTP.
     */
    function enviar_email_adjunto($para, $asunto, $cuerpo_html, $ruta_archivo, $nombre_archivo) {
        if (!$para) {
            return false;
        }
        if (!$ruta_archivo || !is_file($ruta_archivo)) {
            return enviar_email($para, $asunto, $cuerpo_html);
        }

        $contenido = file_get_contents($ruta_archivo);
        if ($contenido === false) {
            return enviar_email($para, $asunto, $cuerpo_html);
        }

        $boundary = 'sgcargos-' . md5(uniqid('', true));

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "From: SGC ARGOS <no-reply@sgcargos.com>\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

        $cuerpo  = "--$boundary\r\n";
        $cuerpo .= "Content-Type: text/html; charset=utf-8\r\n";
        $cuerpo .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $cuerpo .= $cuerpo_html . "\r\n\r\n";

        $cuerpo .= "--$boundary\r\n";
        $cuerpo .= "Content-Type: application/pdf; name=\"$nombre_archivo\"\r\n";
        $cuerpo .= "Content-Transfer-Encoding: base64\r\n";
        $cuerpo .= "Content-Disposition: attachment; filename=\"$nombre_archivo\"\r\n\r\n";
        $cuerpo .= chunk_split(base64_encode($contenido)) . "\r\n";
        $cuerpo .= "--$boundary--";

        return @mail($para, $asunto, $cuerpo, $headers);
    }
}
