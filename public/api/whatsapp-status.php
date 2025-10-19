<?php
// api/whatsapp-status.php

header('Content-Type: application/json');

$user = getAuthenticatedUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    
    $resultado = $whatsappDomain->obtenerEstadoServidor($userId);
    
    echo json_encode($resultado);
    
} catch (\Exception $e) {
    error_log('Error obteniendo estado WhatsApp: ' . $e->getMessage());
    
    // En caso de error, devolver estado local como fallback
    try {
        $config = $whatsappDomain->obtenerConfiguracion($userId);
        
        echo json_encode([
            'success' => true,
            'status' => $config->getStatus(),
            'phoneNumber' => $config->getPhoneNumber(),
            'lastActivity' => $config->getLastActivity()?->format('Y-m-d H:i:s'),
            'message' => 'Estado local (servidor no disponible)',
            'serverConnected' => false
        ]);
    } catch (\Exception $fallbackError) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor'
        ]);
    }
}