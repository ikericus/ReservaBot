<?php
// api/whatsapp-webhook.php

header('Content-Type: application/json');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Verificar Content-Type
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Content-Type debe ser application/json']);
    exit;
}

// Leer datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

debug_log("Webhook recibido: " . $input);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('Error decodificando JSON del webhook: ' . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

try {
    // Obtener handler desde container
    $webhookHandler = getContainer()->getWhatsAppWebhookHandler();
    
    // Validar secret
    $providedSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    
    if (!$webhookHandler->validarSecret($providedSecret)) {
        http_response_code(401);
        error_log("Webhook secret inválido desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        echo json_encode(['error' => 'Secret inválido']);
        exit;
    }
    
    // Procesar evento
    $resultado = $webhookHandler->procesarEvento($data);
    
    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook procesado correctamente',
        'event' => $data['event'] ?? null,
        'userId' => $data['userId'] ?? null,
        'result' => $resultado
    ]);
    
} catch (\InvalidArgumentException $e) {
    error_log('Error validando webhook: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (\Exception $e) {
    error_log('Error procesando webhook: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno procesando webhook'
    ]);
}