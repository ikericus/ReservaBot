<?php
// api/whatsapp-restore-template.php

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

if (!isset($input['tipo_mensaje'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Falta parámetro: tipo_mensaje']);
    exit;
}

$tipoMensaje = $input['tipo_mensaje'];

// Validar tipo de mensaje
if (!in_array($tipoMensaje, ['confirmacion', 'recordatorio', 'bienvenida'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de mensaje inválido']);
    exit;
}

try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    $template = $whatsappDomain->restaurarTemplateDefault($userId, $tipoMensaje);
    
    echo json_encode([
        'success' => true,
        'message' => 'Plantilla restaurada al mensaje por defecto',
        'template' => $template->toArray()
    ]);
    
} catch (\Exception $e) {
    error_log('Error restaurando plantilla: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}