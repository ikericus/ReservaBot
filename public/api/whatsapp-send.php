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
    // 1. Verificar que WhatsApp esté conectado
    logSendMessage("Verificando estado de WhatsApp");
    
    $stmt = getPDO()->prepare('SELECT status, phone_number FROM whatsapp_config WHERE usuario_id = ?');
    $stmt->execute([$userId]);
    $config = $stmt->fetch();
    
    if (!$config || $config['status'] !== 'ready') {
        logSendMessage("WhatsApp no está conectado", ['status' => $config['status'] ?? 'no_config']);
        echo json_encode([
            'success' => false,
            'error' => 'WhatsApp no está conectado',
            'status' => $config['status'] ?? 'disconnected'
        ]);
        exit;
    }
    
    // 2. Generar JWT token para autenticación
    logSendMessage("Generando token JWT");
    $token = generateJWT($userId, $JWT_SECRET);
    $headers = ["Authorization: Bearer " . $token];
    
    // 3. Preparar datos para el servidor Node.js
    $sendData = [
        'to' => $formattedPhone,
        'message' => $message
    ];
    
    // Añadir media si está presente
    if (isset($data['media']) && !empty($data['media'])) {
        $sendData['media'] = $data['media'];
        logSendMessage("Incluyendo media en el mensaje", ['media_type' => $data['media']['mimetype'] ?? 'unknown']);
    }
    
    logSendMessage("Enviando petición al servidor Node.js", [
        'url' => $WHATSAPP_SERVER_URL . '/api/send',
        'to' => $formattedPhone,
        'message_preview' => substr($message, 0, 50) . '...'
    ]);
    
    // 4. Enviar mensaje al servidor Node.js
    try {
        $response = makeRequest($WHATSAPP_SERVER_URL . '/api/send', 'POST', $sendData, $headers);
        logSendMessage("Respuesta del servidor Node.js", $response);
        
        if (!$response || !$response['success']) {
            $errorMsg = $response['error'] ?? 'Error desconocido del servidor WhatsApp';
            logSendMessage("Error del servidor Node.js", ['error' => $errorMsg, 'full_response' => $response]);
            
            // Si el mensaje fue añadido a la cola, no es un error total
            if (isset($response['queued']) && $response['queued']) {
                echo json_encode([
                    'success' => true,
                    'queued' => true,
                    'message' => 'Mensaje añadido a la cola (WhatsApp conectándose)'
                ]);
                exit;
            }
            
            throw new Exception($errorMsg);
        }
        
        logSendMessage("Mensaje enviado correctamente por Node.js", [
            'messageId' => $response['messageId'] ?? 'unknown',
            'timestamp' => $response['timestamp'] ?? 'unknown'
        ]);
        
    } catch (Exception $sendError) {
        logSendMessage("Error enviando mensaje", [
            'error' => $sendError->getMessage(),
            'file' => $sendError->getFile(),
            'line' => $sendError->getLine()
        ]);
        throw $sendError;
    }
    
    // 5. Guardar mensaje en base de datos local (opcional)
    try {
        logSendMessage("Guardando mensaje en base de datos local");
        
        // Crear o actualizar conversación
        $conversationId = createOrUpdateConversation($userId, $formattedPhone, $clientName, $message, 'saliente');
        
        if ($conversationId) {
            // Guardar mensaje individual
            $messageId = $response['messageId'] ?? generateMessageId();
            saveMessage($conversationId, $message, 'saliente', $messageId, 'usuario');
            
            logSendMessage("Mensaje guardado en BD", ['conversation_id' => $conversationId, 'message_id' => $messageId]);
        }
        
        // Si está relacionado con una reserva, actualizar estado
        if ($reservationId && $type !== 'manual') {
            updateReservationMessageStatus($reservationId, $type);
            logSendMessage("Estado de reserva actualizado", ['reservation_id' => $reservationId, 'type' => $type]);
        }
        
    } catch (Exception $dbError) {
        // No fallar por errores de BD, solo log
        logSendMessage("Error guardando en BD (no crítico)", ['error' => $dbError->getMessage()]);
    }
    
    // 6. Respuesta exitosa
    logSendMessage("Proceso completado exitosamente");
    
    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado correctamente',
        'messageId' => $response['messageId'] ?? null,
        'timestamp' => $response['timestamp'] ?? time(),
        'to' => $formattedPhone,
        'type' => $type
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
            'to' => $formattedPhone
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
 * Crear o actualizar conversación en la base de datos
 */
function createOrUpdateConversation($usuarioId, $phoneNumber, $clientName = null, $lastMessage = null, $messageType = 'saliente') {
    try {
        $pdo = getPDO();
        
        // Buscar conversación existente
        $stmt = $pdo->prepare('
            SELECT id FROM conversaciones 
            WHERE usuario_id = ? AND cliente_phone = ?
        ');
        $stmt->execute([$usuarioId, $phoneNumber]);
        $conversation = $stmt->fetch();
        
        if ($conversation) {
            // Actualizar conversación existente
            $stmt = $pdo->prepare('
                UPDATE conversaciones 
                SET ultimo_mensaje = ?, 
                    updated_at = CURRENT_TIMESTAMP,
                    cliente_nombre = COALESCE(?, cliente_nombre)
                WHERE id = ?
            ');
            $stmt->execute([$lastMessage, $clientName, $conversation['id']]);
            
            return $conversation['id'];
        } else {
            // Crear nueva conversación
            $stmt = $pdo->prepare('
                INSERT INTO conversaciones 
                (usuario_id, cliente_phone, cliente_nombre, ultimo_mensaje, no_leidos, created_at, updated_at) 
                VALUES (?, ?, ?, ?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ');
            
            $nombre = $clientName ?: $phoneNumber;
            $stmt->execute([$usuarioId, $phoneNumber, $nombre, $lastMessage]);
            
            return $pdo->lastInsertId();
        }
        
    } catch (Exception $e) {
        logSendMessage("Error creando/actualizando conversación", ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Guardar mensaje en base de datos
 */
function saveMessage($conversationId, $content, $type, $messageId = null, $senderType = 'usuario') {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare('
            INSERT INTO mensajes 
            (conversacion_id, tipo, contenido, enviado_por, mensaje_id, timestamp, leido) 
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, 1)
        ');
        
        $messageId = $messageId ?: generateMessageId();
        
        $stmt->execute([
            $conversationId,
            $type,
            $content,
            $senderType,
            $messageId
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (Exception $e) {
        logSendMessage("Error guardando mensaje", ['error' => $e->getMessage()]);
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
                return; // No hacer nada para tipos desconocidos
        }
        
        $stmt->execute([$reservationId]);
        return true;
        
    } catch (Exception $e) {
        logSendMessage("Error actualizando estado de reserva", ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Generar ID único para mensaje
 */
function generateMessageId() {
    return 'msg_' . uniqid() . '_' . substr(md5(microtime()), 0, 8);
}

logSendMessage("Request processing completed");
?>