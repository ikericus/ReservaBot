<?php
// src/domain/whatsapp/WhatsAppDomain.php

namespace ReservaBot\Domain\WhatsApp;

class WhatsAppDomain {
    private IWhatsAppRepository $whatsappRepository;
    private IWhatsAppServerManager $serverManager;
    
    public function __construct(
        IWhatsAppRepository $whatsappRepository,
        IWhatsAppServerManager $serverManager
    ) {
        $this->whatsappRepository = $whatsappRepository;
        $this->serverManager = $serverManager;
    }
    
    // ========== MÉTODOS DE CONFIGURACIÓN LOCAL (BD) ==========
    
    /**
     * Obtiene configuración local de WhatsApp
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
     * Actualiza actividad local
     */
    public function actualizarActividad(int $usuarioId): void {
        $config = $this->obtenerConfiguracion($usuarioId);
        $config->actualizarActividad();
        
        $this->whatsappRepository->guardarConfiguracion($config);
    }
    
    /**
     * Verifica si puede enviar mensajes (estado local)
     */
    public function puedeEnviarMensajes(int $usuarioId): bool {
        $config = $this->obtenerConfiguracion($usuarioId);
        return $config->estaConectado();
    }
    
    // ========== MÉTODOS DE CONVERSACIONES (BD) ==========
    
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
     * Cuenta conversaciones no leídas
     */
    public function contarNoLeidas(int $usuarioId): int {
        return $this->whatsappRepository->contarNoLeidas($usuarioId);
    }
    
    /**
     * Obtiene estadísticas locales
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
    
    
    // ========== MÉTODOS QUE USAN SERVIDOR EXTERNO ==========
    
    /**
     * Conecta con el servidor WhatsApp
     */
    public function conectarConServidor(int $usuarioId): array {
        try {
            $serverResponse = $this->serverManager->conectar($usuarioId);
            
            $config = $this->obtenerConfiguracion($usuarioId);
            
            // Actualizar estado local según respuesta del servidor
            if ($serverResponse['status'] === 'ready' && isset($serverResponse['phoneNumber'])) {
                $config->conectar($serverResponse['phoneNumber']);
            } elseif ($serverResponse['status'] === 'waiting_qr' && isset($serverResponse['qr'])) {
                $config->esperarQR($serverResponse['qr']);
            } else {
                $config->esperarQR('');
            }
            
            $this->whatsappRepository->guardarConfiguracion($config);
            
            return $serverResponse;
            
        } catch (\RuntimeException $e) {
            throw new \DomainException('Error conectando con servidor WhatsApp: ' . $e->getMessage());
        }
    }
    
    /**
     * Desconecta del servidor WhatsApp
     */
    public function desconectarDeServidor(int $usuarioId): array {
        try {
            $serverResponse = $this->serverManager->desconectar($usuarioId);
            
            // Actualizar estado local
            $config = $this->obtenerConfiguracion($usuarioId);
            $config->desconectar();
            $this->whatsappRepository->guardarConfiguracion($config);
            
            return $serverResponse;
            
        } catch (\Exception $e) {
            throw new \DomainException('Error desconectando del servidor: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtiene estado actual del servidor WhatsApp
     */
    public function obtenerEstadoServidor(int $usuarioId): array {
        try {
            $serverResponse = $this->serverManager->obtenerEstado($usuarioId);
            
            // Sincronizar estado local con servidor
            $config = $this->obtenerConfiguracion($usuarioId);
            
            if ($serverResponse['status'] === 'connected' && isset($serverResponse['phoneNumber'])) {
                $config->conectar($serverResponse['phoneNumber']);
            } elseif ($serverResponse['status'] === 'waiting_qr' && isset($serverResponse['qr'])) {
                $config->esperarQR($serverResponse['qr']);
            }
            
            $this->whatsappRepository->guardarConfiguracion($config);
            
            // Agregar mensaje descriptivo
            $serverResponse['message'] = match($serverResponse['status']) {
                'connected' => 'WhatsApp conectado y listo',
                'connecting' => 'Conectando a WhatsApp...',
                'waiting_qr' => 'Escanea el código QR con tu WhatsApp',
                'disconnected' => 'WhatsApp desconectado',
                default => 'Estado desconocido'
            };
            
            $serverResponse['serverConnected'] = true;
            
            return $serverResponse;
            
        } catch (\RuntimeException $e) {
            // Si falla servidor, usar datos locales
            $config = $this->obtenerConfiguracion($usuarioId);
            
            return [
                'success' => true,
                'status' => $config->getStatus(),
                'phoneNumber' => $config->getPhoneNumber(),
                'lastActivity' => $config->getLastActivity()?->format('Y-m-d H:i:s'),
                'message' => 'Mostrando último estado conocido (servidor no disponible)',
                'serverConnected' => false
            ];
        }
    }
    
    /**
     * Envía mensaje a través del servidor WhatsApp
     */
    public function enviarMensajePorServidor(
        int $usuarioId,
        string $telefono,
        string $mensaje,
        ?array $media = null
    ): array {
        if (!$this->puedeEnviarMensajes($usuarioId)) {
            throw new \DomainException('WhatsApp no está conectado');
        }
        
        try {
            $result = $this->serverManager->enviarMensaje($usuarioId, $telefono, $mensaje, $media);
            
            // Actualizar actividad local
            $this->actualizarActividad($usuarioId);
            
            return $result;
            
        } catch (\RuntimeException $e) {
            throw new \DomainException('Error enviando mensaje por servidor: ' . $e->getMessage());
        }
    }
}