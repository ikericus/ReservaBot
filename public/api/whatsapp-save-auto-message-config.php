<?php
// api/whatsapp-save-auto-message-config.php

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

if (!isset($input['confirmacion']) || !isset($input['recordatorio']) || !isset($input['bienvenida'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros: confirmacion, recordatorio, bienvenida']);
    exit;
}

// Validar que sean booleanos
$confirmacion = filter_var($input['confirmacion'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
$recordatorio = filter_var($input['recordatorio'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
$bienvenida = filter_var($input['bienvenida'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

if ($confirmacion === null || $recordatorio === null || $bienvenida === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Los parámetros deben ser booleanos']);
    exit;
}

try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    $config = $whatsappDomain->configurarMensajesAutomaticos(
        $userId,
        $confirmacion,
        $recordatorio,
        $bienvenida
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Configuración guardada correctamente',
        'config' => [
            'confirmacion' => $config->getMensajesAutomaticosConfirmacion(),
            'recordatorio' => $config->getMensajesAutomaticosRecordatorio(),
            'bienvenida' => $config->getMensajesAutomaticosBienvenida()
        ]
    ]);
    
} catch (\Exception $e) {
    error_log('Error guardando configuración de mensajes automáticos: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}