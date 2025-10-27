<?php
// domain/whatsapp/IWhatsAppRepository.php

namespace ReservaBot\Domain\WhatsApp;

interface IWhatsAppRepository {
    /**
     * Obtiene la configuración de WhatsApp de un usuario
     */
    public function obtenerConfiguracion(int $usuarioId): ?WhatsAppConfig;
    
    /**
     * Guarda o actualiza configuración de WhatsApp
     */
    public function guardarConfiguracion(WhatsAppConfig $config): WhatsAppConfig;
    
    /**
     * Obtiene conversaciones recientes
     */
    public function obtenerConversaciones(int $usuarioId, int $limit = 10): array;
    
    /**
     * Obtiene una conversación por WhatsApp ID
     */
    public function obtenerConversacionPorWhatsappId(string $whatsappId, int $usuarioId): ?Conversacion;
    
    /**
     * Guarda o actualiza una conversación
     */
    public function guardarConversacion(Conversacion $conversacion): Conversacion;
    
    /**
     * Cuenta conversaciones no leídas
     */
    public function contarNoLeidas(int $usuarioId): int;
    
    /**
     * Obtiene estadísticas de mensajería
     */
    public function obtenerEstadisticas(int $usuarioId): array;
}