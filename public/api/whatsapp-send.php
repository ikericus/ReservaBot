<?php
// public/api/send-whatsapp.php
// API para enviar mensajes WhatsApp a través del servidor Node.js

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

// Configuración del servidor WhatsApp
$WHATSAPP_SERVER_URL = 'http://server.reservabot.es:3001';
$JWT_SECRET = 'da3c7b9e13a38a0ea3dcbaaed1ec9ec1f0005f974adad7141b71a36e9f13e187';

// Función de log detallado
function logSendMessage($message, $data = null) {
    $logEntry = "[" . date('Y-m-d H:i:s') . "] SEND-WHATSAPP: " . $message;
    if ($data !== null) {
        $logEntry .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    error_log($logEntry);
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logSendMessage("Método no permitido", ['method' => $_SERVER['REQUEST_METHOD']]);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
$user = getAuthenticatedUser();
if (!$user) {
    logSendMessage("Usuario no autenticado");
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

// Leer datos de la petición
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    logSendMessage("JSON inválido", ['error' => json_last_error_msg(), 'input' => substr($input, 0, 200)]);
    echo json_encode(['success' => false, 'error' => 'Datos JSON inválidos']);
    exit;
}

// Validar datos requeridos
if (!isset($data['to']) || !isset($data['message'])) {
    logSendMessage("Faltan parámetros requeridos", $data);
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros requeridos: to, message']);
    exit;
}

$to = trim($data['to']);
$message = trim($data['message']);
$type = $data['type'] ?? 'manual';
$reservationId = $data['reservationId'] ?? null;
$clientName = $data['clientName'] ?? null;

// Validar número de teléfono
if (empty($to)) {
    logSendMessage("Número de teléfono vacío");
    echo json_encode(['success' => false, 'error' => 'Número de teléfono requerido']);
    exit;
}

// Validar mensaje
if (empty($message)) {
    logSendMessage("Mensaje vacío");
    echo json_encode(['success' => false, 'error' => 'Mensaje requerido']);
    exit;
}

// Formatear número de teléfono
$formattedPhone = formatPhoneNumber($to);

logSendMessage("Iniciando envío de mensaje", [
    'userId' => $userId,
    'to' => $to,
    'formatted_to' => $formattedPhone,
    'type' => $type,
    'message_length' => strlen($message),
    'reservation_id' => $reservationId
]);

try {
    // 1. Verificar que WhatsApp esté conectado (opcional)
    // logSendMessage("Verificando estado de WhatsApp");
    
    // $stmt = getPDO()->prepare('SELECT status FROM whatsapp_config WHERE usuario_id = ?');
    // $stmt->execute([$userId]);
    // $config = $stmt->fetch();
    
    // if (!$config || !in_array($config['status'], ['connected', 'ready'])) {
    //     logSendMessage("WhatsApp no está conectado", ['status' => $config['status'] ?? 'no_config']);
    //     echo json_encode([
    //         'success' => false,
    //         'error' => 'WhatsApp no está conectado',
    //         'status' => $config['status'] ?? 'disconnected'
    //     ]);
    //     exit;
    // }
    
    // 2. Generar JWT token para autenticación
    logSendMessage("Generando token JWT");
    $token = generateJWT($userId, $JWT_SECRET);
    $headers = ["Authorization: Bearer " . $token];
    
    // 3. Preparar datos para el servidor Node.js
    $sendData = [
        'to' => $formattedPhone,
        'message' => $message
    ];
    
    // Determinar si hay media
    $hasMedia = false;
    if (isset($data['media']) && !empty($data['media'])) {
        $sendData['media'] = $data['media'];
        $hasMedia = true;
        logSendMessage("Incluyendo media en el mensaje", ['media_type' => $data['media']['mimetype'] ?? 'unknown']);
    }
    
    logSendMessage("Enviando petición al servidor Node.js", [
        'url' => $WHATSAPP_SERVER_URL . '/api/send',
        'to' => $formattedPhone,
        'message_preview' => substr($message, 0, 50) . '...',
        'has_media' => $hasMedia
    ]);
    
    // 4. Guardar mensaje en BD ANTES de enviar (con status pending)
    $localMessageId = generateMessageId();
    $savedMessageId = null;
    
    try {
        $savedMessageId = saveOutgoingMessage(
            $userId, 
            $formattedPhone, 
            $message, 
            $localMessageId, 
            $hasMedia,
            'pending' // Estado inicial
        );
        
        logSendMessage("Mensaje guardado en BD con estado pending", [
            'local_message_id' => $localMessageId,
            'saved_message_id' => $savedMessageId
        ]);
        
    } catch (Exception $dbError) {
        logSendMessage("Error guardando mensaje en BD (continuando)", ['error' => $dbError->getMessage()]);
    }
    
    // 5. Enviar mensaje al servidor Node.js
    try {
        $response = makeRequest($WHATSAPP_SERVER_URL . '/api/send', 'POST', $sendData, $headers);
        logSendMessage("Respuesta del servidor Node.js", $response);
        
        if (!$response || !$response['success']) {
            $errorMsg = $response['error'] ?? 'Error desconocido del servidor WhatsApp';
            logSendMessage("Error del servidor Node.js", ['error' => $errorMsg, 'full_response' => $response]);
            
            // Actualizar estado del mensaje a failed si se guardó
            if ($savedMessageId) {
                updateMessageStatus($savedMessageId, 'failed');
            }
            
            // Si el mensaje fue añadido a la cola, no es un error total
            if (isset($response['queued']) && $response['queued']) {
                // Actualizar estado a pending (mantener como está)
                echo json_encode([
                    'success' => true,
                    'queued' => true,
                    'message' => 'Mensaje añadido a la cola (WhatsApp conectándose)',
                    'localMessageId' => $localMessageId
                ]);
                exit;
            }
            
            throw new Exception($errorMsg);
        }
        
        // 6. Mensaje enviado correctamente
        $serverMessageId = $response['messageId'] ?? $localMessageId;
        $timestamp = $response['timestamp'] ?? time();
        
        logSendMessage("Mensaje enviado correctamente por Node.js", [
            'server_messageId' => $serverMessageId,
            'local_messageId' => $localMessageId,
            'timestamp' => $timestamp
        ]);
        
        // 7. Actualizar mensaje en BD con datos del servidor
        if ($savedMessageId) {
            updateSentMessage($savedMessageId, $serverMessageId, $timestamp, 'sent');
            logSendMessage("Mensaje actualizado en BD con datos del servidor");
        }
        
    } catch (Exception $sendError) {
        logSendMessage("Error enviando mensaje", [
            'error' => $sendError->getMessage(),
            'file' => $sendError->getFile(),
            'line' => $sendError->getLine()
        ]);
        
        // Actualizar estado del mensaje a failed si se guardó
        if ($savedMessageId) {
            updateMessageStatus($savedMessageId, 'failed');
        }
        
        throw $sendError;
    }
    
    // 8. Si está relacionado con una reserva, actualizar estado
    if ($reservationId && $type !== 'manual') {
        try {
            updateReservationMessageStatus($reservationId, $type);
            logSendMessage("Estado de reserva actualizado", ['reservation_id' => $reservationId, 'type' => $type]);
        } catch (Exception $reservaError) {
            logSendMessage("Error actualizando estado de reserva (no crítico)", ['error' => $reservaError->getMessage()]);
        }
    }
    
    // 9. Respuesta exitosa
    logSendMessage("Proceso completado exitosamente");
    
    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado correctamente',
        'messageId' => $serverMessageId ?? $localMessageId,
        'localMessageId' => $localMessageId,
        'timestamp' => $timestamp ?? time(),
        'to' => $formattedPhone,
        'type' => $type,
        'hasMedia' => $hasMedia
    ]);
    
} catch (Exception $e) {
    logSendMessage("ERROR en proceso de envío", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Determinar tipo de error para respuesta más específica
    $errorType = 'UNKNOWN_ERROR';
    $userMessage = 'Error interno del servidor';
    
    if (strpos($e->getMessage(), 'cURL') !== false || strpos($e->getMessage(), 'conexión') !== false) {
        $errorType = 'CONNECTION_ERROR';
        $userMessage = 'Error de conexión con el servidor WhatsApp';
    } elseif (strpos($e->getMessage(), 'Token') !== false || strpos($e->getMessage(), 'Unauthorized') !== false) {
        $errorType = 'AUTH_ERROR';
        $userMessage = 'Error de autenticación con el servidor WhatsApp';
    } elseif (strpos($e->getMessage(), 'no conectado') !== false) {
        $errorType = 'NOT_CONNECTED';
        $userMessage = 'WhatsApp no está conectado';
    }
    
    echo json_encode([
        'success' => false,
        'error' => $userMessage,
        'error_type' => $errorType,
        'debug_info' => [
            'server_url' => $WHATSAPP_SERVER_URL,
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $formattedPhone,
            'local_message_id' => $localMessageId ?? null
        ]
    ]);
}

// ========== FUNCIONES AUXILIARES ==========

/**
 * Formatear número de teléfono para WhatsApp
 */
function formatPhoneNumber($phone) {
    // Limpiar el número
    $clean = preg_replace('/[^\d]/', '', $phone);
    
    // Eliminar prefijos internacionales comunes
    if (substr($clean, 0, 2) === '00') {
        $clean = substr($clean, 2);
    } elseif (substr($clean, 0, 1) === '+') {
        $clean = substr($clean, 1);
    }
    
    // Si es un número español sin código de país, añadir 34
    if (strlen($clean) === 9 && (substr($clean, 0, 1) === '6' || substr($clean, 0, 1) === '7' || substr($clean, 0, 1) === '9')) {
        $clean = '34' . $clean;
    }
    
    logSendMessage("Número formateado", ['original' => $phone, 'formatted' => $clean]);
    return $clean;
}

/**
 * Guardar mensaje saliente en whatsapp_messages
 */
function saveOutgoingMessage($userId, $phoneNumber, $messageText, $messageId, $hasMedia = false, $status = 'pending') {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare('
            INSERT INTO whatsapp_messages (
                usuario_id, 
                message_id, 
                phone_number, 
                message_text, 
                direction, 
                is_group, 
                has_media, 
                status, 
                timestamp_sent,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, "outgoing", 0, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');
        
        $stmt->execute([
            $userId,
            $messageId,
            $phoneNumber,
            $messageText,
            $hasMedia ? 1 : 0,
            $status
        ]);
        
        $insertedId = $pdo->lastInsertId();
        
        logSendMessage("Mensaje guardado en whatsapp_messages", [
            'id' => $insertedId,
            'message_id' => $messageId,
            'phone_number' => $phoneNumber,
            'status' => $status,
            'has_media' => $hasMedia
        ]);
        
        return $insertedId;
        
    } catch (Exception $e) {
        logSendMessage("Error guardando mensaje saliente", [
            'error' => $e->getMessage(),
            'user_id' => $userId,
            'phone_number' => $phoneNumber,
            'message_id' => $messageId
        ]);
        throw $e;
    }
}

/**
 * Actualizar mensaje enviado con datos del servidor
 */
function updateSentMessage($messageDbId, $serverMessageId, $timestamp, $status = 'sent') {
    try {
        $pdo = getPDO();
        
        // Convertir timestamp si viene en milisegundos
        if (is_numeric($timestamp) && $timestamp > 1000000000000) {
            $timestamp = $timestamp / 1000;
        }
        
        $timestampFormatted = date('Y-m-d H:i:s', $timestamp);
        
        $stmt = $pdo->prepare('
            UPDATE whatsapp_messages 
            SET message_id = ?, 
                status = ?, 
                timestamp_sent = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        
        $stmt->execute([
            $serverMessageId,
            $status,
            $timestampFormatted,
            $messageDbId
        ]);
        
        logSendMessage("Mensaje actualizado con datos del servidor", [
            'db_id' => $messageDbId,
            'server_message_id' => $serverMessageId,
            'status' => $status,
            'timestamp' => $timestampFormatted
        ]);
        
        return true;
        
    } catch (Exception $e) {
        logSendMessage("Error actualizando mensaje enviado", [
            'error' => $e->getMessage(),
            'db_id' => $messageDbId,
            'server_message_id' => $serverMessageId
        ]);
        return false;
    }
}

/**
 * Actualizar solo el estado de un mensaje
 */
function updateMessageStatus($messageDbId, $status) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare('
            UPDATE whatsapp_messages 
            SET status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        
        $stmt->execute([$status, $messageDbId]);
        
        logSendMessage("Estado de mensaje actualizado", [
            'db_id' => $messageDbId,
            'new_status' => $status
        ]);
        
        return true;
        
    } catch (Exception $e) {
        logSendMessage("Error actualizando estado de mensaje", [
            'error' => $e->getMessage(),
            'db_id' => $messageDbId,
            'status' => $status
        ]);
        return false;
    }
}

/**
 * Actualizar estado de mensaje de reserva
 */
function updateReservationMessageStatus($reservationId, $type) {
    try {
        $pdo = getPDO();
        
        switch ($type) {
            case 'confirmation':
                $stmt = $pdo->prepare('
                    UPDATE reservas 
                    SET whatsapp_confirmacion_enviada = 1, 
                        whatsapp_confirmacion_fecha = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ');
                break;
                
            case 'reminder':
                $stmt = $pdo->prepare('
                    UPDATE reservas 
                    SET whatsapp_recordatorio_enviado = 1, 
                        whatsapp_recordatorio_fecha = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ');
                break;
                
            case 'cancellation':
                $stmt = $pdo->prepare('
                    UPDATE reservas 
                    SET whatsapp_cancelacion_enviada = 1, 
                        whatsapp_cancelacion_fecha = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ');
                break;
                
            default:
                logSendMessage("Tipo de mensaje de reserva desconocido", ['type' => $type]);
                return false;
        }
        
        $stmt->execute([$reservationId]);
        
        logSendMessage("Estado de reserva actualizado", [
            'reservation_id' => $reservationId,
            'type' => $type,
            'affected_rows' => $stmt->rowCount()
        ]);
        
        return true;
        
    } catch (Exception $e) {
        logSendMessage("Error actualizando estado de reserva", [
            'error' => $e->getMessage(),
            'reservation_id' => $reservationId,
            'type' => $type
        ]);
        return false;
    }
}

/**
 * Generar ID único para mensaje
 */
function generateMessageId() {
    return 'msg_' . uniqid() . '_' . substr(md5(microtime()), 0, 8);
}

/**
 * Verificar si un mensaje ya existe en la base de datos
 */
function messageExists($userId, $messageId) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare('
            SELECT id FROM whatsapp_messages 
            WHERE usuario_id = ? AND message_id = ?
        ');
        
        $stmt->execute([$userId, $messageId]);
        
        return $stmt->fetchColumn() !== false;
        
    } catch (Exception $e) {
        logSendMessage("Error verificando existencia de mensaje", [
            'error' => $e->getMessage(),
            'user_id' => $userId,
            'message_id' => $messageId
        ]);
        return false;
    }
}

/**
 * Obtener estadísticas de mensajes del usuario
 */
function getUserMessageStats($userId, $phoneNumber = null) {
    try {
        $pdo = getPDO();
        
        $whereClause = 'usuario_id = ?';
        $params = [$userId];
        
        if ($phoneNumber) {
            $whereClause .= ' AND phone_number = ?';
            $params[] = $phoneNumber;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_messages,
                SUM(CASE WHEN direction = 'outgoing' THEN 1 ELSE 0 END) as sent_messages,
                SUM(CASE WHEN direction = 'incoming' THEN 1 ELSE 0 END) as received_messages,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_messages,
                SUM(CASE WHEN has_media = 1 THEN 1 ELSE 0 END) as media_messages
            FROM whatsapp_messages 
            WHERE {$whereClause}
        ");
        
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        logSendMessage("Error obteniendo estadísticas de mensajes", [
            'error' => $e->getMessage(),
            'user_id' => $userId,
            'phone_number' => $phoneNumber
        ]);
        return null;
    }
}

logSendMessage("Request processing completed");
?>