<?php
// public/api/whatsapp-webhook.php
// Endpoint para recibir eventos del servidor WhatsApp

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Método no permitido']));
}

// Verificar Content-Type
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') === false) {
    http_response_code(400);
    exit(json_encode(['error' => 'Content-Type debe ser application/json']));
}

// Verificar webhook secret
$providedSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
$expectedSecret = 'c4f20ece15858d35db6d02e55269de628df3ea8c66246d75a07ce77c9c3c4810';

if (empty($providedSecret) || $providedSecret !== $expectedSecret) {
    http_response_code(401);
    logApp("Webhook secret inválido desde IP: " . $_SERVER['REMOTE_ADDR'], 'WARNING');
    exit(json_encode(['error' => 'Secret inválido']));
}

// Leer datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    exit(json_encode(['error' => 'JSON inválido']));
}

// Validar estructura básica
if (!isset($data['event']) || !isset($data['userId'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Faltan campos requeridos: event, userId']));
}

$event = $data['event'];
$userId = (int)$data['userId'];
$eventData = $data['data'] ?? [];
$timestamp = $data['timestamp'] ?? time() * 1000;

// Log del evento recibido
error_log("Webhook recibido: {$event} para usuario {$userId} - " . json_encode(['event' => $event, 'userId' => $userId, 'timestamp' => $timestamp]));

try {
    // Obtener conexión PDO usando tu función existente
    $pdo = getPDO();
    // Procesar según el tipo de evento
    switch ($event) {
        case 'qr_generated':
            handleQRGenerated($userId, $eventData);
            break;
            
        case 'connected':
            handleConnected($userId, $eventData);
            break;
            
        case 'disconnected':
            handleDisconnected($userId, $eventData);
            break;
            
        case 'auth_failure':
            handleAuthFailure($userId, $eventData);
            break;
            
        case 'message_received':
            handleMessageReceived($userId, $eventData);
            break;
            
        case 'message_sent':
            handleMessageSent($userId, $eventData);
            break;
            
        default:
            error_log("WARNING: Evento webhook desconocido: {$event}");
            break;
    }
    
    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook procesado correctamente',
        'event' => $event,
        'userId' => $userId
    ]);
    
} catch (Exception $e) {
    error_log("Error procesando webhook {$event}: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno procesando webhook'
    ]);
}

// ========== FUNCIONES DE MANEJO DE EVENTOS ==========

function handleQRGenerated($userId, $data) {
    $qrCode = $data['qr'] ?? '';
    
    if (empty($qrCode)) {
        throw new Exception('QR code vacío');
    }
    
    // Actualizar estado en base de datos usando tu tabla existente
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        INSERT INTO whatsapp_config (usuario_id, status, qr_code, updated_at) 
        VALUES (?, 'waiting_qr', ?, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE 
        status = 'waiting_qr', 
        qr_code = VALUES(qr_code),
        updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$userId, $qrCode]);
    
    error_log("QR generado para usuario {$userId}");
}

function handleConnected($userId, $data) {
    $phoneNumber = $data['phoneNumber'] ?? '';
    $name = $data['name'] ?? '';
    
    // Actualizar estado en base de datos
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        INSERT INTO whatsapp_config (usuario_id, status, phone_number, qr_code, updated_at, last_activity) 
        VALUES (?, 'ready', ?, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE 
        status = 'ready',
        phone_number = VALUES(phone_number),
        qr_code = NULL,
        last_activity = CURRENT_TIMESTAMP,
        updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$userId, $phoneNumber]);
    
    error_log("Usuario {$userId} conectado como {$phoneNumber} ({$name})");
}

function handleDisconnected($userId, $data) {
    $reason = $data['reason'] ?? 'unknown';
    
    // Actualizar estado en base de datos
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        UPDATE whatsapp_config 
        SET status = 'disconnected',
            qr_code = NULL,
            updated_at = CURRENT_TIMESTAMP
        WHERE usuario_id = ?
    ");
    $stmt->execute([$userId]);
    
    error_log("Usuario {$userId} desconectado: {$reason}");
}

function handleAuthFailure($userId, $data) {
    $error = $data['error'] ?? 'Authentication failed';
    
    // Actualizar estado en base de datos
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        UPDATE whatsapp_config 
        SET status = 'auth_failed',
            qr_code = NULL,
            updated_at = CURRENT_TIMESTAMP
        WHERE usuario_id = ?
    ");
    $stmt->execute([$userId]);
    
    error_log("Fallo de autenticación para usuario {$userId}: {$error}");
}

function handleMessageReceived($userId, $data) {
    $messageId = $data['id'] ?? '';
    $from = $data['from'] ?? '';
    $body = $data['body'] ?? '';
    $timestamp = $data['timestamp'] ?? time();
    $isGroupMsg = $data['isGroupMsg'] ?? false;
    $hasMedia = $data['hasMedia'] ?? false;
    
    // Extraer número de teléfono limpio
    $phoneNumber = extractPhoneNumber($from);
    
    // Actualizar última actividad
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        UPDATE whatsapp_config 
        SET last_activity = CURRENT_TIMESTAMP 
        WHERE usuario_id = ?
    ");
    $stmt->execute([$userId]);
    
    // Verificar si existe tabla de mensajes
    try {
        $stmt = $pdo->prepare("
            INSERT INTO whatsapp_messages (
                usuario_id, message_id, phone_number, message_text, 
                direction, is_group, has_media, timestamp_received, created_at
            ) VALUES (?, ?, ?, ?, 'incoming', ?, ?, FROM_UNIXTIME(?), NOW())
            ON DUPLICATE KEY UPDATE
            message_text = VALUES(message_text),
            is_group = VALUES(is_group),
            has_media = VALUES(has_media)
        ");
        
        $stmt->execute([
            $userId, $messageId, $phoneNumber, $body, 
            $isGroupMsg ? 1 : 0, $hasMedia ? 1 : 0, $timestamp
        ]);
    } catch (Exception $e) {
        // Si no existe la tabla, solo log el mensaje
        error_log("Mensaje recibido (tabla no disponible): {$phoneNumber} -> {$body}");
    }
    
    error_log("Mensaje recibido de {$phoneNumber} para usuario {$userId}");
}

function handleMessageSent($userId, $data) {
    $messageId = $data['id'] ?? '';
    $to = $data['to'] ?? '';
    $body = $data['body'] ?? '';
    $timestamp = $data['timestamp'] ?? time();
    
    // Extraer número de teléfono limpio
    $phoneNumber = extractPhoneNumber($to);
    
    // Actualizar última actividad
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        UPDATE whatsapp_config 
        SET last_activity = CURRENT_TIMESTAMP 
        WHERE usuario_id = ?
    ");
    $stmt->execute([$userId]);
    
    error_log("Mensaje enviado a {$phoneNumber} por usuario {$userId}");
}

// Función helper para extraer número de teléfono
function extractPhoneNumber($whatsappId) {
    // WhatsApp ID format: "1234567890@c.us" o "1234567890@g.us"
    if (strpos($whatsappId, '@') !== false) {
        return explode('@', $whatsappId)[0];
    }
    return $whatsappId;
}
?>