<?php
// api/whatsapp-update-status.php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'JSON inválido']);
    exit;
}

if (!isset($input['messageId']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros: messageId, status']);
    exit;
}

$messageId = $input['messageId'];
$status = $input['status'];

// Validar estado
$validStatuses = ['pending', 'sent', 'delivered', 'read', 'failed'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Estado inválido']);
    exit;
}

try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    $updated = $whatsappDomain->actualizarEstadoMensaje($messageId, $userId, $status);
    
    echo json_encode([
        'success' => $updated,
        'message' => $updated ? 'Estado actualizado correctamente' : 'Mensaje no encontrado'
    ]);
    
} catch (\Exception $e) {
    error_log('Error actualizando estado de mensaje: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}