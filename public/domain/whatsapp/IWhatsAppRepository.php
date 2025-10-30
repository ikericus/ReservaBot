<?php
// domain/whatsapp/IWhatsAppRepository.php

namespace ReservaBot\Domain\WhatsApp;

interface IWhatsAppRepository {
    // Configuración
    public function obtenerConfiguracion(int $usuarioId): ?WhatsAppConfig;
    public function guardarConfiguracion(WhatsAppConfig $config): WhatsAppConfig;
    
    // Mensajes
    public function guardarMensaje(WhatsAppMessage $mensaje): WhatsAppMessage;
    public function obtenerMensajesPorTelefono(int $usuarioId, string $phoneNumber, int $limit = 50): array;
    public function obtenerMensajesPorUsuario(int $usuarioId, int $limit = 100): array;
    public function obtenerMensajePorMessageId(string $messageId, int $usuarioId): ?WhatsAppMessage;
    public function actualizarEstadoMensaje(string $messageId, int $usuarioId, string $estado): bool;
    
    // Conversaciones (agrupadas por teléfono)
    public function obtenerConversaciones(int $usuarioId, int $limit = 20): array;
    public function contarMensajesNoLeidos(int $usuarioId): int;
    public function marcarConversacionComoLeida(int $usuarioId, string $phoneNumber): bool;
    
    // Estadísticas
    public function obtenerEstadisticas(int $usuarioId): array;
}