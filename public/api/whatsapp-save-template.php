<?php
// api/whatsapp-save-template.php

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

if (!isset($input['tipo_mensaje']) || !isset($input['mensaje'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros: tipo_mensaje, mensaje']);
    exit;
}

$tipoMensaje = $input['tipo_mensaje'];
$mensaje = trim($input['mensaje']);

// Validar tipo de mensaje
if (!in_array($tipoMensaje, ['confirmacion', 'recordatorio', 'bienvenida'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de mensaje inválido']);
    exit;
}

// Validar que el mensaje no esté vacío
if (empty($mensaje)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El mensaje no puede estar vacío']);
    exit;
}

// Validar longitud del mensaje (max 1000 caracteres)
if (strlen($mensaje) > 1000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El mensaje no puede superar los 1000 caracteres']);
    exit;
}

try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    $template = $whatsappDomain->guardarTemplate($userId, $tipoMensaje, $mensaje);
    
    echo json_encode([
        'success' => true,
        'message' => 'Plantilla guardada correctamente',
        'template' => $template->toArray()
    ]);
    
} catch (\Exception $e) {
    error_log('Error guardando plantilla: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}