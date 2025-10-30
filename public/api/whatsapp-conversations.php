<?php
// api/whatsapp-conversations.php

header('Content-Type: application/json');

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$user = getAuthenticatedUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

// GET con parámetro 'phone': Obtener mensajes de una conversación específica
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['phone'])) {
    try {
        $phoneNumber = $_GET['phone'];
        $limit = isset($_GET['message_limit']) ? max(1, min(100, (int)$_GET['message_limit'])) : 50;
        
        $whatsappDomain = getContainer()->getWhatsAppDomain();
        $mensajes = $whatsappDomain->obtenerMensajesConversacion($userId, $phoneNumber, $limit);
        
        // Transformar mensajes para frontend
        $mensajesArray = array_map(function($msg) {
            return [
                'messageId' => $msg->getMessageId(),
                'content' => $msg->getMessageText(),
                'direction' => $msg->getDirection(),
                'isOutgoing' => $msg->isSaliente(),
                'timestamp' => $msg->getTimestampReceived()->format('Y-m-d H:i:s'),
                'status' => $msg->getStatus(),
                'hasMedia' => $msg->hasMedia()
            ];
        }, $mensajes);
        
        echo json_encode([
            'success' => true,
            'messages' => $mensajesArray,
            'phone' => $phoneNumber,
            'total' => count($mensajesArray)
        ]);
        exit;
        
    } catch (\Exception $e) {
        error_log('Error obteniendo mensajes: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error obteniendo mensajes']);
        exit;
    }
}

// Manejar POST para acciones (marcar como leído)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (isset($data['action']) && $data['action'] === 'mark_as_read' && isset($data['phone_number'])) {
        try {
            $whatsappDomain = getContainer()->getWhatsAppDomain();
            $result = $whatsappDomain->marcarComoLeida($data['phone_number'], $userId);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Conversación marcada como leída' : 'No se pudo marcar como leída'
            ]);
        } catch (\Exception $e) {
            error_log('Error marcando como leída: ' . $e->getMessage());
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al marcar como leída'
            ]);
        }
        exit;
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
    exit;
}

// GET: Obtener lista de conversaciones
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;

try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    
    $conversaciones = $whatsappDomain->obtenerConversaciones($userId, $limit);
    $noLeidas = $whatsappDomain->contarNoLeidas($userId);
    
    // Las conversaciones vienen como arrays con esta estructura:
    // [
    //   'phone_number' => '34612345678',
    //   'ultimo_mensaje' => WhatsAppMessage object,
    //   'no_leidos' => 5,
    //   'ultima_actividad' => '2025-10-30 10:30:00'
    // ]
    
    // Transformar para el frontend
    $conversacionesArray = array_map(function($conv) {
        $ultimoMsg = $conv['ultimo_mensaje'];
        $phoneNumber = $conv['phone_number'];
        
        // Formatear tiempo
        $timestamp = new DateTime($conv['ultima_actividad']);
        $now = new DateTime();
        $diff = $now->diff($timestamp);
        
        if ($diff->days == 0) {
            $timeStr = $timestamp->format('H:i');
        } elseif ($diff->days == 1) {
            $timeStr = 'Ayer';
        } else {
            $timeStr = $timestamp->format('d/m/Y');
        }
        
        return [
            'phone' => $phoneNumber,
            'name' => 'Contacto ' . substr($phoneNumber, -4), // TODO: Buscar nombre real en tabla de contactos
            'lastMessage' => $ultimoMsg->getMessageText(),
            'lastMessageTime' => $timeStr,
            'unreadCount' => $conv['no_leidos'],
            'timestamp' => $conv['ultima_actividad']
        ];
    }, $conversaciones);
    
    echo json_encode([
        'success' => true,
        'conversations' => $conversacionesArray,
        'total' => count($conversacionesArray),
        'unread_count' => $noLeidas
    ]);
    
} catch (\Exception $e) {
    error_log('Error obteniendo conversaciones: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}