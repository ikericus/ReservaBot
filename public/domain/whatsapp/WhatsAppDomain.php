<?php
// domain/whatsapp/WhatsAppDomain.php

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
    
    // ==================== CONFIGURACIÓN ====================
    
    /**
     * Obtiene configuración de WhatsApp
     */
    public function obtenerConfiguracion(int $usuarioId): WhatsAppConfig {
        try {            
            $config = $this->whatsappRepository->obtenerConfiguracion($usuarioId);
            
            if (!$config) {
                debug_log("No se encontró configuración, creando nueva para usuario ID: $usuarioId");
                $config = WhatsAppConfig::crear($usuarioId);
                $config = $this->whatsappRepository->guardarConfiguracion($config);
            }
            
            return $config;
        } catch (\Exception $e) {
            error_log('Error obteniendo configuración de WhatsApp: ' . $e->getMessage());
            throw new \RuntimeException('Error obteniendo configuración de WhatsApp: ' . $e->getMessage());
        }
    }
    
    /**
     * Inicia proceso de conexión
     */
    public function iniciarConexion(int $usuarioId, string $qrCode): WhatsAppConfig {
        debug_log("Iniciando conexión de WhatsApp para usuario ID: $usuarioId con QR Code");
        $config = $this->obtenerConfiguracion($usuarioId);
        $config->esperarQR($qrCode);
        
        return $this->whatsappRepository->guardarConfiguracion($config);
    }
    
    /**
     * Confirma conexión exitosa
     */
    public function confirmarConexion(int $usuarioId, string $phoneNumber): WhatsAppConfig {
        debug_log("Confirmando conexión de WhatsApp para usuario ID: $usuarioId con número: $phoneNumber");
        $config = $this->obtenerConfiguracion($usuarioId);
        $config->conectar($phoneNumber);
        
        return $this->whatsappRepository->guardarConfiguracion($config);
    }
    
    /**
     * Desconecta WhatsApp
     */
    public function desconectar(int $usuarioId): WhatsAppConfig {
        debug_log("Desconectando WhatsApp para usuario ID: $usuarioId");
        $config = $this->obtenerConfiguracion($usuarioId);
        $config->desconectar();
        
        return $this->whatsappRepository->guardarConfiguracion($config);
    }
    
    /**
     * Actualiza actividad
     */
    public function actualizarActividad(int $usuarioId): void {
        debug_log("Actualizando actividad de WhatsApp para usuario ID: $usuarioId");
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
        debug_log("Configurando mensajes automáticos para usuario ID: $usuarioId");
        $config = $this->obtenerConfiguracion($usuarioId);
        $config->configurarMensajesAutomaticos($confirmacion, $recordatorio, $bienvenida);
        
        // Crear plantillas por defecto si se activa una opción y no existe
        if ($confirmacion && !$this->whatsappRepository->obtenerTemplate($usuarioId, 'confirmacion')) {
            $template = WhatsAppMessageTemplate::crear($usuarioId, 'confirmacion');
            $this->whatsappRepository->guardarTemplate($template);
        }
        
        if ($recordatorio && !$this->whatsappRepository->obtenerTemplate($usuarioId, 'recordatorio')) {
            $template = WhatsAppMessageTemplate::crear($usuarioId, 'recordatorio');
            $this->whatsappRepository->guardarTemplate($template);
        }
        
        if ($bienvenida && !$this->whatsappRepository->obtenerTemplate($usuarioId, 'bienvenida')) {
            $template = WhatsAppMessageTemplate::crear($usuarioId, 'bienvenida');
            $this->whatsappRepository->guardarTemplate($template);
        }
        
        return $this->whatsappRepository->guardarConfiguracion($config);
    }
    
    // ==================== MENSAJES ====================
    
    /**
     * Registra mensaje entrante
     */
    public function registrarMensajeEntrante(
        int $usuarioId,
        string $messageId,
        string $telefono,
        string $mensaje,
        bool $isGroup = false,
        bool $hasMedia = false
    ): WhatsAppMessage {
        debug_log("Registrando mensaje entrante - messageId: $messageId, teléfono: $telefono");
        
        $whatsappMessage = WhatsAppMessage::crearEntrante(
            $usuarioId,
            $messageId,
            $telefono,
            $mensaje,
            $isGroup,
            $hasMedia
        );
        
        return $this->whatsappRepository->guardarMensaje($whatsappMessage);
    }
    
    /**
     * Registra mensaje saliente
     */
    public function registrarMensajeSaliente(
        int $usuarioId,
        string $messageId,
        string $telefono,
        string $mensaje,
        bool $hasMedia = false
    ): WhatsAppMessage {
        debug_log("Registrando mensaje saliente - messageId: $messageId, teléfono: $telefono");
        
        $whatsappMessage = WhatsAppMessage::crearSaliente(
            $usuarioId,
            $messageId,
            $telefono,
            $mensaje,
            $hasMedia
        );
        
        return $this->whatsappRepository->guardarMensaje($whatsappMessage);
    }
    
    /**
     * Obtiene mensajes de una conversación específica
     */
    public function obtenerMensajesConversacion(int $usuarioId, string $phoneNumber, int $limit = 50): array {
        return $this->whatsappRepository->obtenerMensajesPorTelefono($usuarioId, $phoneNumber, $limit);
    }
    
    /**
     * Actualiza estado de un mensaje
     */
    public function actualizarEstadoMensaje(string $messageId, int $usuarioId, string $estado): bool {
        debug_log("Actualizando estado de mensaje $messageId a $estado");
        return $this->whatsappRepository->actualizarEstadoMensaje($messageId, $usuarioId, $estado);
    }
    
    // ==================== CONVERSACIONES ====================
    
    /**
     * Obtiene conversaciones recientes
     */
    public function obtenerConversaciones(int $usuarioId, int $limit = 10): array {
        return $this->whatsappRepository->obtenerConversaciones($usuarioId, $limit);
    }
    
    /**
     * Marca conversación como leída
     */
    public function marcarComoLeida(string $phoneNumber, int $usuarioId): bool {
        debug_log("Marcando conversación con $phoneNumber como leída para usuario ID: $usuarioId");
        return $this->whatsappRepository->marcarConversacionComoLeida($usuarioId, $phoneNumber);
    }
    
    /**
     * Obtiene contador de no leídas
     */
    public function contarNoLeidas(int $usuarioId): int {
        return $this->whatsappRepository->contarMensajesNoLeidos($usuarioId);
    }
    
    // ==================== ESTADÍSTICAS ====================
    
    /**
     * Obtiene estadísticas
     */
    public function obtenerEstadisticas(int $usuarioId): array {
        $config = $this->obtenerConfiguracion($usuarioId);
        $stats = $this->whatsappRepository->obtenerEstadisticas($usuarioId);
        
        return [
            'conectado' => $config->estaConectado(),
            'phone_number' => $config->getPhoneNumber(),
            'total_mensajes' => $stats['total_mensajes'] ?? 0,
            'mensajes_enviados_hoy' => $stats['mensajes_hoy'] ?? 0,
            'conversaciones_totales' => $stats['total_conversaciones'] ?? 0,
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
    
    // ==================== SERVIDOR WHATSAPP ====================
    
    /**
     * Conecta WhatsApp
     */
    public function conectarWhatsApp(int $usuarioId): array {
        try {
            debug_log("Conectando WhatsApp para usuario ID: $usuarioId");
            $serverResponse = $this->serverManager->conectar($usuarioId);
            
            $config = $this->obtenerConfiguracion($usuarioId);
            
            // Actualizar estado según respuesta del servidor
            if ($serverResponse['status'] === 'ready' && isset($serverResponse['phoneNumber'])) {
                $config->conectar($serverResponse['phoneNumber']);
            } elseif ($serverResponse['status'] === 'waiting_qr' && isset($serverResponse['qr'])) {
                $config->esperarQR($serverResponse['qr']);
            } else {
                $config->esperarQR('');
            }

            debug_log("Actualizando configuración local de WhatsApp para usuario ID: $usuarioId");
            
            $this->whatsappRepository->guardarConfiguracion($config);
            
            debug_log("Configuración local actualizada exitosamente para usuario ID: $usuarioId");

            return $serverResponse;
            
        } catch (\RuntimeException $e) {
            error_log('Error conectando WhatsApp: ' . $e->getMessage());
            throw new \DomainException('Error conectando WhatsApp: ' . $e->getMessage());
        }
    }

    /**
     * Desconecta WhatsApp
     */
    public function desconectarWhatsApp(int $usuarioId): array {
        try {
            $serverResponse = $this->serverManager->desconectar($usuarioId);
            
            $config = $this->obtenerConfiguracion($usuarioId);
            $config->desconectar();
            $this->whatsappRepository->guardarConfiguracion($config);
            
            return $serverResponse;
            
        } catch (\Exception $e) {
            throw new \DomainException('Error desconectando: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene estado de WhatsApp
     */
    public function obtenerEstadoWhatsApp(int $usuarioId): array {
        try {
            $serverResponse = $this->serverManager->obtenerEstado($usuarioId);
            debug_log("Respuesta del servidor WhatsApp: " . json_encode($serverResponse));
            
            // Sincronizar con BD local
            $config = $this->obtenerConfiguracion($usuarioId);
            
            if ($serverResponse['status'] === 'connected' && isset($serverResponse['phoneNumber'])) {
                $config->conectar($serverResponse['phoneNumber']);
            } elseif ($serverResponse['status'] === 'waiting_qr' && isset($serverResponse['qr'])) {
                $config->esperarQR($serverResponse['qr']);
            } elseif ($serverResponse['status'] === 'disconnected') {
                $config->desconectar();
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
            
            return $serverResponse;
            
        } catch (\RuntimeException $e) {
            // Si falla servidor, usar datos locales
            $config = $this->obtenerConfiguracion($usuarioId);
            
            return [
                'success' => true,
                'status' => $config->getStatus(),
                'phoneNumber' => $config->getPhoneNumber(),
                'lastActivity' => $config->getLastActivity()?->format('Y-m-d H:i:s'),
                'message' => 'Mostrando último estado conocido',
                'serverConnected' => false
            ];
        }
    }

    /**
     * Envía mensaje WhatsApp
     */
    public function enviarMensajeWhatsApp(
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
            
            // Registrar mensaje en BD si fue exitoso
            if ($result['success'] ?? false) {
                $messageId = $result['messageId'] ?? uniqid('msg_');
                $this->registrarMensajeSaliente(
                    $usuarioId,
                    $messageId,
                    $telefono,
                    $mensaje,
                    $media !== null
                );
            }
            
            // Actualizar actividad
            $this->actualizarActividad($usuarioId);
            
            return $result;
            
        } catch (\RuntimeException $e) {
            throw new \DomainException('Error enviando mensaje: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene un mensaje por su messageId
     */
    public function obtenerMensajePorMessageId(string $messageId, int $usuarioId): ?WhatsAppMessage {
        return $this->whatsappRepository->obtenerMensajePorMessageId($messageId, $usuarioId);
    }
    
    // ==================== PLANTILLAS DE MENSAJES ====================
    
    /**
     * Obtiene una plantilla específica o crea una por defecto
     */
    public function obtenerTemplate(int $usuarioId, string $tipoMensaje): WhatsAppMessageTemplate {
        $template = $this->whatsappRepository->obtenerTemplate($usuarioId, $tipoMensaje);
        
        if (!$template) {
            // Crear plantilla por defecto
            $template = WhatsAppMessageTemplate::crear($usuarioId, $tipoMensaje);
            $template = $this->whatsappRepository->guardarTemplate($template);
        }
        
        return $template;
    }
    
    /**
     * Obtiene todas las plantillas de un usuario
     */
    public function obtenerTemplates(int $usuarioId): array {
        return $this->whatsappRepository->obtenerTemplates($usuarioId);
    }
    
    /**
     * Guarda o actualiza una plantilla
     */
    public function guardarTemplate(int $usuarioId, string $tipoMensaje, string $mensaje): WhatsAppMessageTemplate {
        $template = $this->whatsappRepository->obtenerTemplate($usuarioId, $tipoMensaje);
        
        if ($template) {
            $template->actualizarMensaje($mensaje);
        } else {
            $template = WhatsAppMessageTemplate::crear($usuarioId, $tipoMensaje, $mensaje);
        }
        
        return $this->whatsappRepository->guardarTemplate($template);
    }
    
    /**
     * Restaura una plantilla a su mensaje por defecto
     */
    public function restaurarTemplateDefault(int $usuarioId, string $tipoMensaje): WhatsAppMessageTemplate {
        $mensajeDefault = WhatsAppMessageTemplate::getMensajeDefault($tipoMensaje);
        
        if (!$mensajeDefault) {
            throw new \InvalidArgumentException('Tipo de mensaje inválido');
        }
        
        return $this->guardarTemplate($usuarioId, $tipoMensaje, $mensajeDefault);
    }
    
    /**
     * Procesa y envía un mensaje automático usando la plantilla
     */
    public function enviarMensajeAutomatico(
        int $usuarioId,
        string $tipoMensaje,
        string $telefono,
        array $datos
    ): array {
        $template = $this->obtenerTemplate($usuarioId, $tipoMensaje);
        $mensajeProcesado = $template->procesarMensaje($datos);
        
        return $this->enviarMensajeWhatsApp($usuarioId, $telefono, $mensajeProcesado);
    }
}