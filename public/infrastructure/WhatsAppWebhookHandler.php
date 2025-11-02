<?php
// infrastructure/WhatsAppWebhookHandler.php

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
        
        debug_log("Webhook recibido: {$event} para usuario {$userId}");
        
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
                
            case 'message_ack':
                return $this->handleMessageAck($userId, $eventData);
                
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
        
        // Desconectar
        $this->whatsappDomain->desconectar($userId);
        
        error_log("Fallo de autenticación para usuario {$userId}: {$error}");
        
        return ['processed' => true, 'event' => 'auth_failure'];
    }
    
    /**
     * Mensaje recibido
     * ⭐ MODIFICADO: Usa registrarMensajeEntrante
     */
    private function handleMessageReceived(int $userId, array $data): array {
        $from = $data['from'] ?? '';
        $body = $data['body'] ?? '';
        $messageId = $data['messageId'] ?? $data['id'] ?? uniqid('msg_');
        
        if (empty($from)) {
            error_log("Mensaje recibido sin remitente para usuario {$userId}");
            return ['processed' => false, 'reason' => 'Remitente vacío'];
        }
        
        // Extraer número de teléfono limpio
        $phoneNumber = $this->extractPhoneNumber($from);
        
        // Información adicional
        $isGroup = $data['isGroup'] ?? false;
        $hasMedia = $data['hasMedia'] ?? false;
        
        // ⭐ CAMBIO IMPORTANTE: Registrar mensaje usando el nuevo método
        $mensaje = $this->whatsappDomain->registrarMensajeEntrante(
            $userId,
            $messageId,
            $phoneNumber,
            $body,
            $isGroup,
            $hasMedia
        );
        
        // Actualizar actividad
        $this->whatsappDomain->actualizarActividad($userId);
        
        error_log("Mensaje recibido de {$phoneNumber} para usuario {$userId} (ID: {$messageId})");
        
        return [
            'processed' => true, 
            'event' => 'message_received',
            'messageId' => $mensaje->getId(),
            'phone' => $phoneNumber
        ];
    }
    
    /**
     * Mensaje enviado (confirmación)
     * ⭐ MODIFICADO: Puede registrar mensaje saliente si no existe
     */
    private function handleMessageSent(int $userId, array $data): array {
        $to = $data['to'] ?? '';
        $body = $data['body'] ?? '';
        $messageId = $data['messageId'] ?? $data['id'] ?? null;
        
        if ($messageId && $to) {
            $phoneNumber = $this->extractPhoneNumber($to);
            $hasMedia = $data['hasMedia'] ?? false;
            
            // Verificar si el mensaje ya existe (fue registrado al enviar)
            $mensajeExistente = $this->whatsappDomain->obtenerMensajePorMessageId($messageId, $userId);
            
            if (!$mensajeExistente) {
                // Si no existe, registrarlo ahora
                $this->whatsappDomain->registrarMensajeSaliente(
                    $userId,
                    $messageId,
                    $phoneNumber,
                    $body,
                    $hasMedia
                );
                
                error_log("Mensaje saliente registrado retroactivamente para usuario {$userId} (ID: {$messageId})");
            } else {
                // Si existe, actualizar estado a 'sent'
                $this->whatsappDomain->actualizarEstadoMensaje($messageId, $userId, 'sent');
                
                error_log("Estado de mensaje actualizado a 'sent' para usuario {$userId} (ID: {$messageId})");
            }
        }
        
        // Actualizar actividad
        $this->whatsappDomain->actualizarActividad($userId);
        
        error_log("Mensaje enviado por usuario {$userId}");
        
        return ['processed' => true, 'event' => 'message_sent'];
    }
    
    /**
     * Actualización de estado de mensaje (ACK)
     * ⭐ NUEVO MÉTODO
     */
    private function handleMessageAck(int $userId, array $data): array {
        $messageId = $data['messageId'] ?? $data['id'] ?? null;
        $ack = $data['ack'] ?? null;
        
        if (!$messageId || $ack === null) {
            error_log("Message ACK incompleto para usuario {$userId}");
            return ['processed' => false, 'reason' => 'Datos incompletos'];
        }
        
        // Mapear ACK de WhatsApp a nuestros estados
        // 0 = ERROR, 1 = PENDING, 2 = SERVER_ACK (sent), 3 = DELIVERY_ACK (delivered), 4 = READ, 5 = PLAYED
        $statusMap = [
            0 => 'failed',     // ACK_ERROR
            1 => 'pending',    // ACK_PENDING
            2 => 'sent',       // ACK_SERVER
            3 => 'delivered',  // ACK_DEVICE
            4 => 'read',       // ACK_READ
            5 => 'read'        // ACK_PLAYED (para mensajes de voz)
        ];
        
        $newStatus = $statusMap[$ack] ?? 'pending';
        
        // Actualizar estado del mensaje
        $updated = $this->whatsappDomain->actualizarEstadoMensaje($messageId, $userId, $newStatus);
        
        if ($updated) {
            debug_log("Estado de mensaje {$messageId} actualizado a '{$newStatus}' (ACK: {$ack}) para usuario {$userId}");
        } else {
            debug_log("No se pudo actualizar estado de mensaje {$messageId} para usuario {$userId}");
        }
        
        return [
            'processed' => true, 
            'event' => 'message_ack',
            'messageId' => $messageId,
            'status' => $newStatus,
            'ack' => $ack,
            'updated' => $updated
        ];
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