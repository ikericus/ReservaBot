<?php
// src/infrastructure/WhatsAppWebhookHandler.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\WhatsApp\WhatsAppDomain;

class WhatsAppWebhookHandler {
    private WhatsAppDomain $whatsappDomain;
    private string $webhookSecret;
    
    public function __construct(WhatsAppDomain $whatsappDomain, string $webhookSecret) {
        $this->whatsappDomain = $whatsappDomain;
        $this->webhookSecret = $webhookSecret;
    }
    
    /**
     * Valida el secret del webhook
     */
    public function validarSecret(string $providedSecret): bool {
        return !empty($providedSecret) && hash_equals($this->webhookSecret, $providedSecret);
    }
    
    /**
     * Procesa evento del webhook
     */
    public function procesarEvento(array $data): array {
        $event = $data['event'] ?? null;
        $userId = (int)($data['userId'] ?? 0);
        $eventData = $data['data'] ?? [];
        
        if (!$event || !$userId) {
            throw new \InvalidArgumentException('Evento o userId inválido');
        }
        
        error_log("Webhook recibido: {$event} para usuario {$userId}");
        
        switch ($event) {
            case 'qr_generated':
                return $this->handleQRGenerated($userId, $eventData);
                
            case 'connected':
                return $this->handleConnected($userId, $eventData);
                
            case 'disconnected':
                return $this->handleDisconnected($userId, $eventData);
                
            case 'auth_failure':
                return $this->handleAuthFailure($userId, $eventData);
                
            case 'message_received':
                return $this->handleMessageReceived($userId, $eventData);
                
            case 'message_sent':
                return $this->handleMessageSent($userId, $eventData);
                
            default:
                error_log("Evento webhook desconocido: {$event}");
                return ['processed' => false, 'reason' => 'Evento desconocido'];
        }
    }
    
    /**
     * QR generado
     */
    private function handleQRGenerated(int $userId, array $data): array {
        $qrCode = $data['qr'] ?? '';
        
        if (empty($qrCode)) {
            throw new \InvalidArgumentException('QR code vacío');
        }
        
        $this->whatsappDomain->iniciarConexion($userId, $qrCode);
        
        error_log("QR generado para usuario {$userId}");
        
        return ['processed' => true, 'event' => 'qr_generated'];
    }
    
    /**
     * WhatsApp conectado
     */
    private function handleConnected(int $userId, array $data): array {
        $phoneNumber = $data['phoneNumber'] ?? '';
        
        $this->whatsappDomain->confirmarConexion($userId, $phoneNumber);
        
        error_log("Usuario {$userId} conectado como {$phoneNumber}");
        
        return ['processed' => true, 'event' => 'connected'];
    }
    
    /**
     * WhatsApp desconectado
     */
    private function handleDisconnected(int $userId, array $data): array {
        $reason = $data['reason'] ?? 'unknown';
        
        $this->whatsappDomain->desconectar($userId);
        
        error_log("Usuario {$userId} desconectado: {$reason}");
        
        return ['processed' => true, 'event' => 'disconnected'];
    }
    
    /**
     * Fallo de autenticación
     */
    private function handleAuthFailure(int $userId, array $data): array {
        $error = $data['error'] ?? 'Authentication failed';
        
        // Marcar estado de error en configuración
        $config = $this->whatsappDomain->obtenerConfiguracion($userId);
        // Aquí podrías agregar un método en WhatsAppConfig para marcar error
        // Por ahora, desconectar
        $this->whatsappDomain->desconectar($userId);
        
        error_log("Fallo de autenticación para usuario {$userId}: {$error}");
        
        return ['processed' => true, 'event' => 'auth_failure'];
    }
    
    /**
     * Mensaje recibido
     */
    private function handleMessageReceived(int $userId, array $data): array {
        $from = $data['from'] ?? '';
        $body = $data['body'] ?? '';
        
        if (empty($from)) {
            error_log("Mensaje recibido sin remitente para usuario {$userId}");
            return ['processed' => false, 'reason' => 'Remitente vacío'];
        }
        
        // Extraer número de teléfono limpio
        $phoneNumber = $this->extractPhoneNumber($from);
        
        // Registrar mensaje en conversación
        $this->whatsappDomain->registrarMensaje(
            $userId,
            $from, // whatsappId completo
            $phoneNumber,
            $body
        );
        
        // Actualizar actividad
        $this->whatsappDomain->actualizarActividad($userId);
        
        error_log("Mensaje recibido de {$phoneNumber} para usuario {$userId}");
        
        return ['processed' => true, 'event' => 'message_received'];
    }
    
    /**
     * Mensaje enviado (confirmación)
     */
    private function handleMessageSent(int $userId, array $data): array {
        // Actualizar actividad
        $this->whatsappDomain->actualizarActividad($userId);
        
        error_log("Mensaje enviado por usuario {$userId}");
        
        return ['processed' => true, 'event' => 'message_sent'];
    }
    
    /**
     * Extrae número de teléfono de WhatsApp ID
     * Formato: "1234567890@c.us" o "1234567890@g.us"
     */
    private function extractPhoneNumber(string $whatsappId): string {
        if (strpos($whatsappId, '@') !== false) {
            return explode('@', $whatsappId)[0];
        }
        return $whatsappId;
    }
}