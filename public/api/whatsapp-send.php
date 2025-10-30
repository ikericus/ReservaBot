<?php
// api/whatsapp-send.php

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

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos JSON inválidos']);
    exit;
}

// Validar datos requeridos
if (!isset($data['to']) || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros requeridos: to, message']);
    exit;
}

$to = trim($data['to']);
$message = trim($data['message']);
$media = $data['media'] ?? null;

if (empty($to)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Número de teléfono requerido']);
    exit;
}

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Mensaje requerido']);
    exit;
}

try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    
    // Este método ahora registra automáticamente el mensaje en la BD
    $resultado = $whatsappDomain->enviarMensajeWhatsApp(
        $userId,
        $to,
        $message,
        $media
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado correctamente',
        'messageId' => $resultado['messageId'] ?? null,
        'timestamp' => $resultado['timestamp'] ?? time(),
        'to' => $to
    ]);
    
} catch (\DomainException $e) {
    error_log('Error enviando mensaje WhatsApp: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (\Exception $e) {
    error_log('Error inesperado en whatsapp-send: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}