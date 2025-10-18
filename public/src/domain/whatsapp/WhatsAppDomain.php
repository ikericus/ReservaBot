<?php
// src/domain/whatsapp/WhatsAppDomain.php

namespace ReservaBot\Domain\WhatsApp;

class WhatsAppDomain {
    private IWhatsAppRepository $whatsappRepository;
    
    public function __construct(IWhatsAppRepository $whatsappRepository) {
        $this->whatsappRepository = $whatsappRepository;
    }
    
    /**
     * Obtiene configuración de WhatsApp
     */
    public function obtenerConfiguracion(int $usuarioId): WhatsAppConfig {
        $config = $this->whatsappRepository->obtenerConfiguracion($usuarioId);
        
        if (!$config) {
            $config = WhatsAppConfig::crear($usuarioId);
            $config = $this->whatsappRepository->guardarConfiguracion($config);
        }
        
        return $config;
    }
    
    /**
     * Inicia proceso de conexión
     */
    public function iniciarConexion(int $usuarioId, string $qrCode): WhatsAppConfig {
        $config = $this->obtenerConfiguracion($usuarioId);
        $config->esperarQR($qrCode);
        
        return $this->whatsappRepository->guardarConfiguracion($config);
    }
    
    /**
     * Confirma conexión exitosa
     */
    public function confirmarConexion(int $usuarioId, string $phoneNumber): WhatsAppConfig {
        $config = $this->obtenerConfiguracion($usuarioId);
        $config->conectar($phoneNumber);
        
        return $this->whatsappRepository->guardarConfiguracion($config);
    }
    
    /**
     * Desconecta WhatsApp
     */
    public function desconectar(int $usuarioId): WhatsAppConfig {
        $config = $this->obtenerConfiguracion($usuarioId);
        $config->desconectar();
        
        return $this->whatsappRepository->guardarConfiguracion($config);
    }
    
    /**
     * Actualiza actividad
     */
    public function actualizarActividad(int $usuarioId): void {
        $config = $this->obtenerConfiguracion($usuarioId);
        $config->actualizarActividad();
        
        $this->whatsappRepository->guardarConfiguracion($config);
    }
    
    /**
     * Configura mensajes automáticos
     */
    public function configurarMensajesAutomaticos(
        int $usuarioId,
        bool $confirmacion,
        bool $recordatorio,
        bool $bienvenida
    ): WhatsAppConfig {
        $config = $this->obtenerConfiguracion($usuarioId);
        $config->configurarMensajesAutomaticos($confirmacion, $recordatorio, $bienvenida);
        
        return $this->whatsappRepository->guardarConfiguracion($config);
    }
    
    /**
     * Obtiene conversaciones recientes
     */
    public function obtenerConversaciones(int $usuarioId, int $limit = 10): array {
        return $this->whatsappRepository->obtenerConversaciones($usuarioId, $limit);
    }
    
    /**
     * Registra o actualiza conversación
     */
    public function registrarMensaje(
        int $usuarioId,
        string $whatsappId,
        string $telefono,
        string $mensaje,
        ?string $nombre = null
    ): Conversacion {
        $conversacion = $this->whatsappRepository->obtenerConversacionPorWhatsappId($whatsappId, $usuarioId);
        
        if (!$conversacion) {
            $conversacion = Conversacion::crear($usuarioId, $whatsappId, $nombre, $telefono, $mensaje);
        } else {
            $conversacion->actualizarMensaje($mensaje);
        }
        
        return $this->whatsappRepository->guardarConversacion($conversacion);
    }
    
    /**
     * Marca conversación como leída
     */
    public function marcarComoLeida(string $whatsappId, int $usuarioId): void {
        $conversacion = $this->whatsappRepository->obtenerConversacionPorWhatsappId($whatsappId, $usuarioId);
        
        if ($conversacion) {
            $conversacion->marcarComoLeido();
            $this->whatsappRepository->guardarConversacion($conversacion);
        }
    }
    
    /**
     * Obtiene contador de no leídas
     */
    public function contarNoLeidas(int $usuarioId): int {
        return $this->whatsappRepository->contarNoLeidas($usuarioId);
    }
    
    /**
     * Obtiene estadísticas
     */
    public function obtenerEstadisticas(int $usuarioId): array {
        $config = $this->obtenerConfiguracion($usuarioId);
        $stats = $this->whatsappRepository->obtenerEstadisticas($usuarioId);
        
        return [
            'conectado' => $config->estaConectado(),
            'phone_number' => $config->getPhoneNumber(),
            'conversaciones_totales' => $stats['total_conversaciones'] ?? 0,
            'mensajes_enviados_hoy' => $stats['mensajes_hoy'] ?? 0,
            'conversaciones_activas' => $stats['conversaciones_activas'] ?? 0,
            'no_leidas' => $this->contarNoLeidas($usuarioId)
        ];
    }
    
    /**
     * Verifica si puede enviar mensajes
     */
    public function puedeEnviarMensajes(int $usuarioId): bool {
        $config = $this->obtenerConfiguracion($usuarioId);
        return $config->estaConectado();
    }
}