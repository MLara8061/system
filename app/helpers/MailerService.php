<?php
/**
 * MailerService — Wrapper centralizado para envío de emails vía PHPMailer SMTP.
 * Lee configuración desde variables de entorno (archivo .env).
 *
 * Variables requeridas en .env:
 *   MAIL_HOST        smtp.hostinger.com
 *   MAIL_PORT        587
 *   MAIL_USERNAME    noreply@activosamerimed.com
 *   MAIL_PASSWORD    tu_contraseña
 *   MAIL_FROM        noreply@activosamerimed.com
 *   MAIL_FROM_NAME   AmeriMed Activos
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

// Cargar PHPMailer si no está cargado
if (!class_exists(PHPMailer::class)) {
    $phpmailerBase = dirname(__DIR__, 2) . '/lib/PHPMailer/';
    require_once $phpmailerBase . 'Exception.php';
    require_once $phpmailerBase . 'PHPMailer.php';
    require_once $phpmailerBase . 'SMTP.php';
}

class MailerService
{
    /**
     * Envía un email HTML usando SMTP.
     *
     * @param string $to         Dirección del destinatario
     * @param string $toName     Nombre del destinatario
     * @param string $subject    Asunto (texto plano, sin codificar)
     * @param string $htmlBody   Cuerpo HTML del mensaje
     * @return bool              true si se envió con éxito
     */
    public static function send(string $to, string $toName, string $subject, string $htmlBody): bool
    {
        $host     = getenv('MAIL_HOST')      ?: 'smtp.hostinger.com';
        $port     = (int)(getenv('MAIL_PORT') ?: 587);
        $username = getenv('MAIL_USERNAME')  ?: getenv('MAIL_FROM') ?: '';
        $password = getenv('MAIL_PASSWORD')  ?: '';
        $from     = getenv('MAIL_FROM')      ?: $username;
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Sistema de Soporte';

        // Si no hay contraseña configurada, intentar con mail() como fallback
        if (empty($password)) {
            return self::fallbackMail($to, $subject, $htmlBody, $from, $fromName);
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $username;
            $mail->Password   = $password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $port;
            $mail->CharSet    = PHPMailer::CHARSET_UTF8;

            $mail->setFrom($from, $fromName);
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", $htmlBody));

            $mail->send();
            return true;
        } catch (MailerException $e) {
            error_log('[MailerService] Error enviando email a ' . $to . ': ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            error_log('[MailerService] Error inesperado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fallback a mail() nativo cuando MAIL_PASSWORD no está configurado.
     */
    private static function fallbackMail(string $to, string $subject, string $htmlBody, string $from, string $fromName): bool
    {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
        $subjectEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        return @mail($to, $subjectEncoded, $htmlBody, $headers);
    }
}
