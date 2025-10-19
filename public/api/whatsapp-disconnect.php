<?php
// api/whatsapp-disconnect.php

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

try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    
    $resultado = $whatsappDomain->desconectarDeServidor($userId);
    
    echo json_encode($resultado);
    
} catch (\DomainException $e) {
    error_log('Error desconectando WhatsApp: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (\Exception $e) {
    error_log('Error inesperado en whatsapp-disconnect: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}