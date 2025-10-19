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

// Manejar POST para acciones (marcar como leído)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (isset($data['action']) && $data['action'] === 'mark_as_read' && isset($data['phone_number'])) {
        try {
            $whatsappDomain = getContainer()->getWhatsAppDomain();
            $whatsappDomain->marcarComoLeida($data['phone_number'], $userId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Conversación marcada como leída'
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

// GET: Obtener conversaciones
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;

try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    
    $conversaciones = $whatsappDomain->obtenerConversaciones($userId, $limit);
    $noLeidas = $whatsappDomain->contarNoLeidas($userId);
    
    // Convertir a arrays
    $conversacionesArray = array_map(fn($c) => $c->toArray(), $conversaciones);
    
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