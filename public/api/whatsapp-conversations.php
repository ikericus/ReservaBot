<?php
// public/api/whatsapp-conversations.php
// API para obtener conversaciones de WhatsApp

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

// Solo permitir GET y POST
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
$user = getAuthenticatedUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

// Función de log detallado
function logConversations($message, $data = null) {
    $logEntry = "[" . date('Y-m-d H:i:s') . "] WHATSAPP-CONVERSATIONS: " . $message;
    if ($data !== null) {
        $logEntry .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    error_log($logEntry);
}

// Manejar POST requests (para marcar como leído, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (isset($data['action']) && $data['action'] === 'mark_as_read' && isset($data['phone_number'])) {
        $phoneNumber = trim($data['phone_number']);
        $success = markConversationAsRead($phoneNumber, $userId);
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Conversación marcada como leída' : 'Error marcando como leída'
        ]);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
    exit;
}

// Obtener parámetros de consulta para GET
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$unreadOnly = isset($_GET['unread_only']) ? (bool)$_GET['unread_only'] : false;
$includeMessages = isset($_GET['include_messages']) ? (bool)$_GET['include_messages'] : false;

logConversations("Obteniendo conversaciones", [
    'userId' => $userId,
    'limit' => $limit,
    'offset' => $offset,
    'search' => $search,
    'unread_only' => $unreadOnly,
    'include_messages' => $includeMessages
]);

try {
    $pdo = getPDO();
    
    // Construir consulta para obtener conversaciones agrupadas por teléfono
    $whereConditions = ['wm.usuario_id = ?'];
    $queryParams = [$userId];
    
    // Filtro de búsqueda
    if (!empty($search)) {
        $whereConditions[] = 'wm.phone_number LIKE ?';
        $searchParam = '%' . $search . '%';
        $queryParams[] = $searchParam;
    }
    
    // Filtro de no leídos
    $unreadCondition = $unreadOnly ? 'AND unread_count > 0' : '';
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Consulta principal: agrupar mensajes por teléfono para crear "conversaciones"
    $stmt = $pdo->prepare("
        SELECT 
            wm.phone_number,
            MAX(wm.id) as last_message_id,
            COUNT(*) as total_messages,
            SUM(CASE WHEN wm.direction = 'incoming' AND wm.status != 'read' THEN 1 ELSE 0 END) as unread_count,
            MAX(COALESCE(wm.timestamp_received, wm.timestamp_sent, wm.created_at)) as last_activity,
            (SELECT message_text FROM whatsapp_messages wm2 
             WHERE wm2.phone_number = wm.phone_number AND wm2.usuario_id = wm.usuario_id 
             ORDER BY COALESCE(wm2.timestamp_received, wm2.timestamp_sent, wm2.created_at) DESC 
             LIMIT 1) as last_message,
            (SELECT direction FROM whatsapp_messages wm2 
             WHERE wm2.phone_number = wm.phone_number AND wm2.usuario_id = wm.usuario_id 
             ORDER BY COALESCE(wm2.timestamp_received, wm2.timestamp_sent, wm2.created_at) DESC 
             LIMIT 1) as last_message_direction
        FROM whatsapp_messages wm
        WHERE {$whereClause}
        GROUP BY wm.phone_number
        HAVING 1=1 {$unreadCondition}
        ORDER BY last_activity DESC
        LIMIT ? OFFSET ?
    ");
    
    $queryParams[] = $limit;
    $queryParams[] = $offset;
    
    $stmt->execute($queryParams);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logConversations("Conversaciones obtenidas", ['count' => count($conversations)]);
    
    // Obtener total de conversaciones para paginación
    $countStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT wm.phone_number) as total
        FROM whatsapp_messages wm
        WHERE {$whereClause}
    ");
    
    // Remover los parámetros de limit y offset para el conteo
    $countParams = array_slice($queryParams, 0, -2);
    $countStmt->execute($countParams);
    $totalCount = (int)$countStmt->fetchColumn();
    
    // Formatear conversaciones para respuesta
    $formattedConversations = [];
    
    foreach ($conversations as $conv) {
        $formatted = [
            'id' => generateConversationId($conv['phone_number']), // ID virtual para compatibilidad
            'phone' => $conv['phone_number'],
            'name' => formatContactName($conv['phone_number']),
            'originalName' => null, // No tenemos nombres guardados en esta estructura
            'lastMessage' => $conv['last_message'] ?: 'Sin mensajes',
            'lastMessageDirection' => $conv['last_message_direction'],
            'unreadCount' => (int)$conv['unread_count'],
            'hasUnread' => (int)$conv['unread_count'] > 0,
            'totalMessages' => (int)$conv['total_messages'],
            'lastActivity' => $conv['last_activity'],
            'lastMessageTime' => formatTimeAgo($conv['last_activity']),
            'lastMessageTimeFormatted' => $conv['last_activity'] ? date('d/m/Y H:i', strtotime($conv['last_activity'])) : null
        ];
        
        // Incluir mensajes recientes si se solicita
        if ($includeMessages) {
            $formatted['recentMessages'] = getRecentMessages($conv['phone_number'], $userId, 5);
        }
        
        $formattedConversations[] = $formatted;
    }
    
    // Estadísticas adicionales
    $stats = [
        'total' => $totalCount,
        'returned' => count($formattedConversations),
        'hasMore' => ($offset + $limit) < $totalCount,
        'unreadTotal' => getTotalUnreadCount($userId)
    ];
    
    logConversations("Respuesta preparada", $stats);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'conversations' => $formattedConversations,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'total' => $totalCount,
            'hasMore' => $stats['hasMore'],
            'nextOffset' => $stats['hasMore'] ? $offset + $limit : null
        ],
        'stats' => $stats,
        'filters' => [
            'search' => $search,
            'unread_only' => $unreadOnly,
            'include_messages' => $includeMessages
        ]
    ]);
    
} catch (Exception $e) {
    logConversations("ERROR obteniendo conversaciones", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'debug_info' => [
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s'),
            'parameters' => [
                'limit' => $limit,
                'offset' => $offset,
                'search' => $search
            ]
        ]
    ]);
}

// ========== FUNCIONES AUXILIARES ==========

/**
 * Obtener mensajes recientes de una conversación por teléfono
 */
function getRecentMessages($phoneNumber, $userId, $limit = 5) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("
            SELECT 
                id,
                message_id,
                message_text,
                direction,
                is_group,
                has_media,
                status,
                timestamp_received,
                timestamp_sent,
                created_at
            FROM whatsapp_messages
            WHERE usuario_id = ? AND phone_number = ?
            ORDER BY COALESCE(timestamp_received, timestamp_sent, created_at) DESC
            LIMIT ?
        ");
        
        $stmt->execute([$userId, $phoneNumber, $limit]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear mensajes
        $formattedMessages = [];
        foreach ($messages as $msg) {
            $timestamp = $msg['timestamp_received'] ?: $msg['timestamp_sent'] ?: $msg['created_at'];
            
            $formattedMessages[] = [
                'id' => (int)$msg['id'],
                'messageId' => $msg['message_id'],
                'content' => $msg['message_text'],
                'direction' => $msg['direction'],
                'isGroup' => (bool)$msg['is_group'],
                'hasMedia' => (bool)$msg['has_media'],
                'status' => $msg['status'],
                'timestamp' => $timestamp,
                'timeFormatted' => $timestamp ? date('H:i', strtotime($timestamp)) : '',
                'isOutgoing' => $msg['direction'] === 'outgoing',
                'isRead' => $msg['status'] === 'read'
            ];
        }
        
        // Devolver en orden cronológico (más antiguos primero)
        return array_reverse($formattedMessages);
        
    } catch (Exception $e) {
        logConversations("Error obteniendo mensajes recientes", [
            'phone_number' => $phoneNumber,
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        return [];
    }
}

/**
 * Obtener total de mensajes no leídos del usuario
 */
function getTotalUnreadCount($userId) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM whatsapp_messages
            WHERE usuario_id = ? 
            AND direction = 'incoming' 
            AND status != 'read'
        ");
        
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
        
    } catch (Exception $e) {
        logConversations("Error obteniendo total no leídos", [
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        return 0;
    }
}

/**
 * Marcar conversación como leída por número de teléfono
 */
function markConversationAsRead($phoneNumber, $userId) {
    try {
        $pdo = getPDO();
        
        // Marcar todos los mensajes entrantes como leídos
        $stmt = $pdo->prepare("
            UPDATE whatsapp_messages 
            SET status = 'read', updated_at = CURRENT_TIMESTAMP
            WHERE usuario_id = ? 
            AND phone_number = ? 
            AND direction = 'incoming' 
            AND status != 'read'
        ");
        
        $stmt->execute([$userId, $phoneNumber]);
        $affectedRows = $stmt->rowCount();
        
        logConversations("Mensajes marcados como leídos", [
            'phone_number' => $phoneNumber,
            'user_id' => $userId,
            'affected_rows' => $affectedRows
        ]);
        
        return $affectedRows >= 0; // Retorna true incluso si no hay mensajes que marcar
        
    } catch (Exception $e) {
        logConversations("Error marcando como leída", [
            'phone_number' => $phoneNumber,
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Formatear tiempo relativo (ej: "hace 2 horas")
 */
function formatTimeAgo($datetime) {
    if (!$datetime) return 'Nunca';
    
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Ahora';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "Hace {$minutes} min";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Hace {$hours}h";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "Hace {$days}d";
    } else {
        return date('d/m/Y', $timestamp);
    }
}

/**
 * Formatear nombre de contacto
 */
function formatContactName($phoneNumber) {
    // Aquí puedes agregar lógica para obtener nombres de una tabla de contactos
    // Por ahora, generar un nombre amigable basado en el número
    
    if (strlen($phoneNumber) >= 4) {
        return 'Contacto ' . substr($phoneNumber, -4);
    }
    
    return 'Contacto ' . $phoneNumber;
}

/**
 * Generar ID de conversación virtual para compatibilidad
 */
function generateConversationId($phoneNumber) {
    // Generar un ID consistente basado en el número de teléfono
    return crc32($phoneNumber);
}

/**
 * Obtener estadísticas de una conversación específica
 */
function getConversationStats($phoneNumber, $userId) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_messages,
                SUM(CASE WHEN direction = 'incoming' THEN 1 ELSE 0 END) as incoming_count,
                SUM(CASE WHEN direction = 'outgoing' THEN 1 ELSE 0 END) as outgoing_count,
                SUM(CASE WHEN direction = 'incoming' AND status != 'read' THEN 1 ELSE 0 END) as unread_count,
                MIN(COALESCE(timestamp_received, timestamp_sent, created_at)) as first_message,
                MAX(COALESCE(timestamp_received, timestamp_sent, created_at)) as last_message
            FROM whatsapp_messages
            WHERE usuario_id = ? AND phone_number = ?
        ");
        
        $stmt->execute([$userId, $phoneNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        logConversations("Error obteniendo estadísticas de conversación", [
            'phone_number' => $phoneNumber,
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

logConversations("Request processing completed");
?>