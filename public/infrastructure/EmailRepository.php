<?php
// infrastructure/EmailRepository.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\Email\IEmailRepository;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carga manual de PHPMailer (ajusta la ruta si es necesario)
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer/src/SMTP.php';

class EmailRepository implements IEmailRepository {
    private string $fromEmail;
    private string $fromName;

    public function __construct() {
        $this->fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@reservabot.es';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'ReservaBot';
    }

    public function enviar(
        string $destinatario,
        string $asunto,
        string $cuerpoTexto,
        ?string $cuerpoHtml = null,
        array $opciones = []
    ): bool {
        $mail = new PHPMailer(true);

        try {
            // SMTP CONFIG HOSTINGER
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.hostinger.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'] ?? 'noreply@reservabot.es';
            $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? 'CAMBIAR';
            // $mail->SMTPSecure = 'ssl'; // Hostinger usa SSL en 465
            // $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Remitente
            $fromEmail = $opciones['from_email'] ?? $this->fromEmail;
            $fromName  = $opciones['from_name'] ?? $this->fromName;
            $mail->setFrom($fromEmail, $fromName);

            // Destinatario
            $mail->addAddress($destinatario);

            // Reply-To
            if (!empty($opciones['reply_to'])) {
                $mail->addReplyTo(
                    $opciones['reply_to'],
                    $opciones['reply_to_name'] ?? null
                );
            }

            // CC / BCC
            if (!empty($opciones['cc'])) {
                $mail->addCC($opciones['cc']);
            }
            if (!empty($opciones['bcc'])) {
                $mail->addBCC($opciones['bcc']);
            }

            // Contenido
            $mail->Subject = $asunto;

            if ($cuerpoHtml) {
                $mail->isHTML(true);
                $mail->Body    = $cuerpoHtml;
                $mail->AltBody = $cuerpoTexto;
            } else {
                $mail->Body = $cuerpoTexto;
            }

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("❌ Error enviando email a {$destinatario}: {$mail->ErrorInfo} ({$e->getMessage()})");
            return false;
        }
    }
}
