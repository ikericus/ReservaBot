<?php
/**
 * Webhook para eventos de WhatsApp - Refactorizado
 * Usa las nuevas funciones centralizadas
 */

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/whatsapp-config.php';
require_once dirname(__DIR__) . '/includes/whatsapp-functions.php';

// Configurar headers
header('Content-Type: application/json');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Verificar Content-Type
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Content-Type debe ser application/json']);
    exit;
}

// Verificar webhook secret usando la configuración centralizada
$providedSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
$expectedSecret = WhatsAppConfig::WEBHOOK_SECRET;

if (empty($providedSecret) || !hash_equals($expectedSecret, $providedSecret)) {
    http_response_code(401);
    error_log("Webhook secret inválido desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    echo json_encode(['error' => 'Secret inválido']);
    exit;
}

// Leer y validar datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido: ' . json_last_error_msg()]);
    exit;
}

// Validar estructura básica
if (!isset($data['event']) || !isset($data['userId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan campos requeridos: event, userId']);
    exit;
}

$event = $data['event'];
$userId = (int)$data['userId'];
$eventData = $data['data'] ?? [];
$timestamp = $data['timestamp'] ?? time() * 1000;

// Log del evento
error_log("[WEBHOOK] Evento: {$event} | Usuario: {$userId} | Timestamp: {$timestamp}");

try {
    // Procesar evento usando funciones centralizadas
    $result = processWebhookEvent($event, $userId, $eventData, $timestamp);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Webhook procesado correctamente',
            'event' => $event,
            'userId' => $userId
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $result['message'],
            'event' => $event,
            'userId' => $userId
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error procesando webhook {$event}: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno procesando webhook'
    ]);
}

// ============================================================================
// FUNCIÓN PRINCIPAL DE PROCESAMIENTO DE WEBHOOKS
// ============================================================================

/**
 * Procesar evento de webhook
 */
function processWebhookEvent($event, $userId, $eventData, $timestamp) {
    try {
        switch ($event) {
            case 'qr_generated':
                return handleQRGenerated($userId, $eventData);
                
            case 'connected':
                return handleConnected($userId, $eventData);
                
            case 'disconnected':
                return handleDisconnected($userId, $eventData);
                
            case 'auth_failure':
                return handleAuthFailure($userId, $eventData);
                
            case 'message_received':
                return handleMessageReceived($userId, $eventData);
                
            case 'message_sent':
                return handleMessageSent($userId, $eventData);
                
            case 'status_change':
                return handleStatusChange($userId, $eventData);
                
            default:
                error_log("Evento webhook desconocido: {$event}");
                return ['success' => false, 'message' => "Evento desconocido: {$event}"];
        }
        
    } catch (Exception $e) {
        error_log("Error en processWebhookEvent: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// ============================================================================
// HANDLERS ESPECÍFICOS DE EVENTOS
// ============================================================================

/**
 * Manejar generación de QR
 */
function handleQRGenerated($userId, $data) {
    $qrCode = $data['qr'] ?? '';
    
    if (empty($qrCode)) {
        return ['success' => false, 'message' => 'QR code vacío'];
    }
    
    // Usar función centralizada para manejar QR
    $result = handleQRUpdate($qrCode);
    
    if ($result['success']) {
        error_log("QR generado para usuario {$userId}");
    }
    
    return $result;
}

/**
 * Manejar conexión exitosa
 */
function handleConnected($userId, $data) {
    $phoneNumber = $data['phoneNumber'] ?? '';
    $name = $data['name'] ?? '';
    
    try {
        // Actualizar estado usando función centralizada
        updateWhatsAppStatus('connected');
        
        // Actualizar datos específicos en BD
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            INSERT INTO whatsapp_config (usuario_id, status, phone_number, qr_code, updated_at, last_activity) 
            VALUES (?, 'connected', ?, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE 
            status = 'connected',
            phone_number = VALUES(phone_number),
            qr_code = NULL,
            last_activity = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$userId, $phoneNumber]);
        
        // Limpiar QR code
        removeQRCode();
        
        error_log("Usuario {$userId} conectado como {$phoneNumber} ({$name})");
        
        return ['success' => true, 'message' => 'Conexión exitosa'];
        
    } catch (Exception $e) {
        error_log("Error en handleConnected: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Manejar desconexión
 */
function handleDisconnected($userId, $data) {
    $reason = $data['reason'] ?? 'unknown';
    
    try {
        // Usar función centralizada
        updateWhatsAppStatus('disconnected');
        removeQRCode();
        
        error_log("Usuario {$userId} desconectado: {$reason}");
        
        return ['success' => true, 'message' => 'Desconexión procesada'];
        
    } catch (Exception $e) {
        error_log("Error en handleDisconnected: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Manejar fallo de autenticación
 */
function handleAuthFailure($userId, $data) {
    $error = $data['error'] ?? 'Authentication failed';
    
    try {
        // Usar función centralizada
        updateWhatsAppStatus('auth_failed');
        removeQRCode();
        
        error_log("Fallo de autenticación para usuario {$userId}: {$error}");
        
        return ['success' => true, 'message' => 'Fallo de autenticación procesado'];
        
    } catch (Exception $e) {
        error_log("Error en handleAuthFailure: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Manejar mensaje recibido
 */
function handleMessageReceived($userId, $data) {
    $messageId = $data['id'] ?? '';
    $from = $data['from'] ?? '';
    $body = $data['body'] ?? '';
    $timestamp = $data['timestamp'] ?? time();
    $isGroupMsg = $data['isGroupMsg'] ?? false;
    $hasMedia = $data['hasMedia'] ?? false;
    
    try {
        // Extraer número de teléfono
        $phoneNumber = extractPhoneFromChatId($from);
        
        // Actualizar última actividad
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            UPDATE whatsapp_config 
            SET last_activity = CURRENT_TIMESTAMP 
            WHERE usuario_id = ?
        ");
        $stmt->execute([$userId]);
        
        // Registrar mensaje
        logWhatsAppMessage($phoneNumber, $body, 'received', 'incoming');
        
        // Procesar posible respuesta automática usando función centralizada
        $autoResponseResult = processIncomingMessage([
            'chatId' => $from,
            'body' => $body,
            'senderName' => $data['senderName'] ?? ''
        ]);
        
        error_log("Mensaje recibido de {$phoneNumber} para usuario {$userId}");
        
        return ['success' => true, 'message' => 'Mensaje procesado'];
        
    } catch (Exception $e) {
        error_log("Error en handleMessageReceived: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Manejar mensaje enviado
 */
function handleMessageSent($userId, $data) {
    $messageId = $data['id'] ?? '';
    $to = $data['to'] ?? '';
    $body = $data['body'] ?? '';
    $timestamp = $data['timestamp'] ?? time();
    
    try {
        // Extraer número de teléfono
        $phoneNumber = extractPhoneFromChatId($to);
        
        // Actualizar última actividad
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            UPDATE whatsapp_config 
            SET last_activity = CURRENT_TIMESTAMP 
            WHERE usuario_id = ?
        ");
        $stmt->execute([$userId]);
        
        // Registrar mensaje
        logWhatsAppMessage($phoneNumber, $body, 'sent', 'outgoing');
        
        error_log("Mensaje enviado a {$phoneNumber} por usuario {$userId}");
        
        return ['success' => true, 'message' => 'Mensaje enviado procesado'];
        
    } catch (Exception $e) {
        error_log("Error en handleMessageSent: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Manejar cambio de estado general
 */
function handleStatusChange($userId, $data) {
    $newStatus = $data['status'] ?? '';
    
    if (empty($newStatus) || !WhatsAppConfig::isValidStatus($newStatus)) {
        return ['success' => false, 'message' => 'Estado inválido'];
    }
    
    try {
        updateWhatsAppStatus($newStatus);
        
        error_log("Estado cambiado para usuario {$userId}: {$newStatus}");
        
        return ['success' => true, 'message' => 'Estado actualizado'];
        
    } catch (Exception $e) {
        error_log("Error en handleStatusChange: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>