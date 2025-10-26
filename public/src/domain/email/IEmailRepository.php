<?php
// src/domain/email/IEmailRepository.php

namespace ReservaBot\Domain\Email;

interface IEmailRepository {
    /**
     * Envía un email
     * 
     * @param string $destinatario Email del destinatario
     * @param string $asunto Asunto del email
     * @param string $cuerpoTexto Contenido en texto plano
     * @param string|null $cuerpoHtml Contenido en HTML (opcional)
     * @param array $opciones Opciones adicionales (reply_to, cc, bcc, etc.)
     * @return bool True si se envió correctamente
     */
    public function enviar(
        string $destinatario,
        string $asunto,
        string $cuerpoTexto,
        ?string $cuerpoHtml = null,
        array $opciones = []
    ): bool;
}