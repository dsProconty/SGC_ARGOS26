<?php
// Envío de correo compartido entre Gift Card (aprobación/rechazo) y Estados
// de Cuenta (envío automático/manual). Antes vivía duplicado dentro de
// ajax/giftcard/giftcard.php; se centraliza aquí para que ambos módulos usen
// la misma función.
//
// Usa SMTP autenticado (PHPMailer) contra una cuenta de correo real,
// configurada por variables de entorno (.env, mismo mecanismo que
// DEPLOY_WEBHOOK_TOKEN — ver env.php). El mail() nativo de PHP en hosting
// compartido no autentica contra ningún servidor: muchos proveedores de
// correo (Gmail, Microsoft, etc.) lo descartan en silencio sin rebote ni
// entrega a spam, así que devolver true no garantizaba que el correo
// llegara. Si no hay credenciales SMTP configuradas todavía (.env sin
// MAIL_SMTP_HOST), se cae de vuelta a mail() para no dejar el sistema sin
// poder enviar nada mientras se configura la cuenta real.
//
// Sin type hints nullable ni short-list syntax: PHP < 7.1 en producción.

require_once __DIR__ . '/../env.php';
require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('mail_smtp_configurado')) {
    function mail_smtp_configurado() {
        return (bool)env('MAIL_SMTP_HOST');
    }
}

if (!function_exists('mail_enviar_legacy')) {
    /** Envío por mail() nativo — solo como respaldo si no hay SMTP configurado. */
    function mail_enviar_legacy($para, $asunto, $cuerpo_html, $ruta_adjunto, $nombre_adjunto) {
        $remitente = env('MAIL_FROM_ADDRESS', 'no-reply@sgcargos.com');
        $nombre    = env('MAIL_FROM_NAME', 'SGC ARGOS');

        if (!$ruta_adjunto || !is_file($ruta_adjunto)) {
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "From: $nombre <$remitente>\r\n";
            return @mail($para, $asunto, $cuerpo_html, $headers);
        }

        $contenido = file_get_contents($ruta_adjunto);
        if ($contenido === false) {
            return mail_enviar_legacy($para, $asunto, $cuerpo_html, null, null);
        }

        $boundary = 'sgcargos-' . md5(uniqid('', true));

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "From: $nombre <$remitente>\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

        $cuerpo  = "--$boundary\r\n";
        $cuerpo .= "Content-Type: text/html; charset=utf-8\r\n";
        $cuerpo .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $cuerpo .= $cuerpo_html . "\r\n\r\n";

        $cuerpo .= "--$boundary\r\n";
        $cuerpo .= "Content-Type: application/pdf; name=\"$nombre_adjunto\"\r\n";
        $cuerpo .= "Content-Transfer-Encoding: base64\r\n";
        $cuerpo .= "Content-Disposition: attachment; filename=\"$nombre_adjunto\"\r\n\r\n";
        $cuerpo .= chunk_split(base64_encode($contenido)) . "\r\n";
        $cuerpo .= "--$boundary--";

        return @mail($para, $asunto, $cuerpo, $headers);
    }
}

if (!function_exists('mail_enviar_smtp')) {
    /** Envío por SMTP autenticado vía PHPMailer. Devuelve false si falla. */
    function mail_enviar_smtp($para, $asunto, $cuerpo_html, $ruta_adjunto, $nombre_adjunto) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = env('MAIL_SMTP_HOST');
            $mail->Port       = (int)env('MAIL_SMTP_PORT', 587);
            $mail->SMTPAuth   = true;
            $mail->Username   = env('MAIL_SMTP_USER');
            $mail->Password   = env('MAIL_SMTP_PASS');
            $mail->SMTPSecure = env('MAIL_SMTP_SECURE', 'tls');
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 15;

            $remitente = env('MAIL_FROM_ADDRESS') ? env('MAIL_FROM_ADDRESS') : env('MAIL_SMTP_USER');
            $mail->setFrom($remitente, env('MAIL_FROM_NAME', 'SGC ARGOS'));
            $mail->addAddress($para);

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpo_html;

            if ($ruta_adjunto && is_file($ruta_adjunto)) {
                $mail->addAttachment($ruta_adjunto, $nombre_adjunto ? $nombre_adjunto : basename($ruta_adjunto));
            }

            return $mail->send();
        } catch (Exception $e) {
            error_log('SMTP mail error a ' . $para . ': ' . $mail->ErrorInfo);
            return false;
        }
    }
}

if (!function_exists('enviar_email')) {
    function enviar_email($para, $asunto, $cuerpo) {
        if (!$para) {
            return false;
        }
        if (mail_smtp_configurado()) {
            return mail_enviar_smtp($para, $asunto, $cuerpo, null, null);
        }
        return mail_enviar_legacy($para, $asunto, $cuerpo, null, null);
    }
}

if (!function_exists('enviar_email_adjunto')) {
    /** Igual que enviar_email() pero con un archivo adjunto (ej. el PDF del estado de cuenta). */
    function enviar_email_adjunto($para, $asunto, $cuerpo_html, $ruta_archivo, $nombre_archivo) {
        if (!$para) {
            return false;
        }
        if (mail_smtp_configurado()) {
            return mail_enviar_smtp($para, $asunto, $cuerpo_html, $ruta_archivo, $nombre_archivo);
        }
        return mail_enviar_legacy($para, $asunto, $cuerpo_html, $ruta_archivo, $nombre_archivo);
    }
}
