<?php
/**
 * API para obtener estado de WhatsApp - Refactorizado
 * Usa las nuevas funciones centralizadas
 */

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/whatsapp-config.php';
require_once dirname(__DIR__) . '/includes/whatsapp-functions.php';

header('Content-Type: application/json');

// Verificar autenticación
$user = getAuthenticatedUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

try {
    // Obtener estado completo usando la función centralizada
    $status = getWhatsAppStatus($userId);
    
    // Verificar salud del servidor
    $serverHealth = checkWhatsAppServerHealth();
    $serverConnected = ($serverHealth['status'] !== 'offline');
    
    // Preparar respuesta completa
    $response = [
        'success' => true,
        'status' => $status['status'],
        'connected' => $status['connected'],
        'serverConnected' => $serverConnected,
        'lastActivity' => $status['lastActivity']
    ];
    
    // Incluir número de teléfono si está disponible
    if (!empty($status['phoneNumber'])) {
        $response['phoneNumber'] = $status['phoneNumber'];
    }
    
    // Incluir QR si está disponible
    if (!empty($status['qrCode'])) {
        $response['qr'] = $status['qrCode'];
        $response['qrCode'] = $status['qrCode']; // Compatibilidad
    }
    
    // Incluir configuración de notificaciones
    if (!empty($status['settings'])) {
        $response['settings'] = $status['settings'];
    }
    
    // Mensajes descriptivos según estado
    $statusMessages = [
        'ready' => 'WhatsApp conectado y listo',
        'connected' => 'WhatsApp conectado y listo',
        'connecting' => 'Conectando a WhatsApp...',
        'waiting_qr' => 'Escanea el código QR con tu WhatsApp',
        'qr_ready' => 'Escanea el código QR con tu WhatsApp',
        'disconnected' => 'WhatsApp desconectado',
        'server_error' => 'Error de conexión con servidor WhatsApp',
        'auth_failed' => 'Error de autenticación con WhatsApp',
        'error' => 'Error en la conexión de WhatsApp'
    ];
    
    $response['message'] = $statusMessages[$status['status']] ?? 'Estado desconocido';
    
    // Información adicional de servidor si no está conectado
    if (!$serverConnected) {
        $response['serverError'] = $serverHealth['error'] ?? 'Servidor no disponible';
        
        // Si había conexión previa pero ahora no hay servidor, marcar como server_error
        if (in_array($status['status'], ['ready', 'connected', 'connecting', 'waiting_qr'])) {
            updateWhatsAppStatus('server_error');
            $response['status'] = 'server_error';
            $response['message'] = 'Error de conexión con servidor WhatsApp';
            $response['connected'] = false;
        }
    }
    
    // Información de debug si está habilitado
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $response['debug'] = [
            'server_url' => WhatsAppConfig::SERVER_URL,
            'server_health' => $serverHealth,
            'config_errors' => WhatsAppConfig::validate()
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Error en whatsapp-status: ' . $e->getMessage());
    
    // Intentar obtener al menos datos básicos
    try {
        $fallbackStatus = [
            'success' => true,
            'status' => 'error',
            'connected' => false,
            'serverConnected' => false,
            'message' => 'Error obteniendo estado: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ];
        
        // Intentar obtener datos básicos de BD
        $basicStatus = getConfigValue('whatsapp_status', 'disconnected');
        if ($basicStatus) {
            $fallbackStatus['status'] = $basicStatus;
            $fallbackStatus['message'] = 'Estado básico desde BD: ' . $basicStatus;
        }
        
        echo json_encode($fallbackStatus);
        
    } catch (Exception $fallbackError) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor'
        ]);
    }
}
?>