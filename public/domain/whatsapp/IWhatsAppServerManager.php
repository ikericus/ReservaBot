<?php
// domain/whatsapp/IWhatsAppServerManager.php

namespace ReservaBot\Domain\WhatsApp;

interface IWhatsAppServerManager {
    /**
     * Conecta con el servidor WhatsApp
     */
    public function conectar(int $usuarioId): array;
    
    /**
     * Desconecta del servidor WhatsApp
     */
    public function desconectar(int $usuarioId): array;
    
    /**
     * Obtiene estado actual
     */
    public function obtenerEstado(int $usuarioId): array;
    
    /**
     * Envía mensaje
     */
    public function enviarMensaje(int $usuarioId, string $telefono, string $mensaje, ?array $media = null): array;
    
    /**
     * Verifica si el servidor está disponible
     */
    public function estaDisponible(): bool;
}