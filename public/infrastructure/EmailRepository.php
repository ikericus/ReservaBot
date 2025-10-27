<?php
// infrastructure/EmailRepository.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\Email\IEmailRepository;

class EmailRepository implements IEmailRepository {
    private string $fromEmail;
    private string $fromName;
    
    public function __construct() {
        $this->fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@reservabot.com';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'ReservaBot';
    }
    
    /**
     * Envía un email genérico
     */
    public function enviar(
        string $destinatario,
        string $asunto,
        string $cuerpoTexto,
        ?string $cuerpoHtml = null,
        array $opciones = []
    ): bool {
        try {
            // Obtener remitente (puede ser sobreescrito por opciones)
            $fromEmail = $opciones['from_email'] ?? $this->fromEmail;
            $fromName = $opciones['from_name'] ?? $this->fromName;
            
            // Headers base
            $headers = [];
            $headers[] = "From: {$fromName} <{$fromEmail}>";
            $headers[] = "X-Mailer: PHP/" . phpversion();
            $headers[] = "MIME-Version: 1.0";
            
            // Reply-To
            if (isset($opciones['reply_to'])) {
                if (isset($opciones['reply_to_name'])) {
                    $headers[] = "Reply-To: {$opciones['reply_to_name']} <{$opciones['reply_to']}>";
                } else {
                    $headers[] = "Reply-To: {$opciones['reply_to']}";
                }
            }
            
            // CC y BCC
            if (isset($opciones['cc'])) {
                $headers[] = "Cc: {$opciones['cc']}";
            }
            if (isset($opciones['bcc'])) {
                $headers[] = "Bcc: {$opciones['bcc']}";
            }
            
            // Construir mensaje
            if ($cuerpoHtml) {
                // Multipart: texto plano + HTML
                $boundary = uniqid('np');
                $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
                
                $message = "--{$boundary}\r\n";
                $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
                $message .= $cuerpoTexto . "\r\n\r\n";
                
                $message .= "--{$boundary}\r\n";
                $message .= "Content-Type: text/html; charset=UTF-8\r\n";
                $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
                $message .= $cuerpoHtml . "\r\n\r\n";
                
                $message .= "--{$boundary}--";
            } else {
                // Solo texto plano
                $headers[] = "Content-Type: text/plain; charset=UTF-8";
                $message = $cuerpoTexto;
            }
            
            // Enviar email
            $headersString = implode("\r\n", $headers);
            $resultado = mail($destinatario, $asunto, $message, $headersString);
            
            // Log
            if ($resultado) {
                debug_log("Email enviado a {$destinatario}: {$asunto}");
            } else {
                error_log("Error al enviar email a {$destinatario}: {$asunto}");
            }
            
            return $resultado;
            
        } catch (\Exception $e) {
            error_log("Excepción al enviar email: " . $e->getMessage());
            return false;
        }
    }
}