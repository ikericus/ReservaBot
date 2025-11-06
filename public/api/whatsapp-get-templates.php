<?php
// api/whatsapp-get-templates.php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    
    // Obtener todas las plantillas o crear por defecto si no existen
    $templates = [
        'confirmacion' => $whatsappDomain->obtenerTemplate($userId, 'confirmacion'),
        'recordatorio' => $whatsappDomain->obtenerTemplate($userId, 'recordatorio'),
        'bienvenida' => $whatsappDomain->obtenerTemplate($userId, 'bienvenida')
    ];
    
    // Convertir a arrays
    $templatesData = [];
    foreach ($templates as $tipo => $template) {
        $templatesData[$tipo] = $template->toArray();
    }
    
    // Obtener placeholders disponibles
    $placeholders = \ReservaBot\Domain\WhatsApp\WhatsAppMessageTemplate::getPlaceholdersDisponibles();
    
    echo json_encode([
        'success' => true,
        'templates' => $templatesData,
        'placeholders' => $placeholders
    ]);
    
} catch (\Exception $e) {
    error_log('Error obteniendo plantillas: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}