<?php
/**
 * API para desconectar WhatsApp - Refactorizado
 * Usa las nuevas funciones centralizadas
 */

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/whatsapp-config.php';
require_once dirname(__DIR__) . '/includes/whatsapp-functions.php';

header('Content-Type: application/json');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
$user = getAuthenticatedUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

try {
    // Verificar estado actual
    $currentStatus = getWhatsAppStatus($userId);
    
    // Si ya está desconectado, no hacer nada
    if ($currentStatus['status'] === 'disconnected') {
        echo json_encode([
            'success' => true,
            'status' => 'disconnected',
            'message' => 'WhatsApp ya estaba desconectado'
        ]);
        exit;
    }
    
    // Intentar desconectar usando la función centralizada
    $disconnectResult = disconnectWhatsApp();
    
    if ($disconnectResult['success']) {
        echo json_encode([
            'success' => true,
            'status' => 'disconnected',
            'message' => $disconnectResult['message']
        ]);
    } else {
        // Aún así reportar éxito parcial si hay problemas con el servidor
        echo json_encode([
            'success' => true,
            'status' => 'disconnected',
            'message' => 'WhatsApp desconectado localmente',
            'warning' => $disconnectResult['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en whatsapp-disconnect: ' . $e->getMessage());
    
    // Intentar forzar desconexión local
    try {
        updateWhatsAppStatus('disconnected');
        removeQRCode();
        
        echo json_encode([
            'success' => true,
            'status' => 'disconnected',
            'message' => 'WhatsApp desconectado localmente',
            'warning' => 'Error comunicándose con servidor: ' . $e->getMessage()
        ]);
    } catch (Exception $cleanupError) {
        error_log('Error en limpieza de disconnect: ' . $cleanupError->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor'
        ]);
    }
}
?>