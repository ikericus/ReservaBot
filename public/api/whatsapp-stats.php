<?php
// api/whatsapp-stats.php

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

try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    
    $stats = $whatsappDomain->obtenerEstadisticas($userId);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (\Exception $e) {
    error_log('Error obteniendo estadísticas WhatsApp: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}