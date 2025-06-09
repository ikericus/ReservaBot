<?php
/**
 * API para conectar WhatsApp - Refactorizado
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

// Log detallado para debugging
function logDebug($message, $data = null) {
    $logEntry = "[" . date('Y-m-d H:i:s') . "] WHATSAPP-CONNECT: " . $message;
    if ($data !== null) {
        $logEntry .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    error_log($logEntry);
}

logDebug("Starting WhatsApp connection process", [
    'userId' => $userId,
    'server_url' => WhatsAppConfig::SERVER_URL,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

try {
    // 1. Verificar prerequisites básicos
    logDebug("Step 1: Checking prerequisites");
    
    $requiredExtensions = ['curl', 'json', 'openssl'];
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            throw new Exception("Extensión PHP requerida no encontrada: {$ext}");
        }
    }
    
    $requiredFunctions = ['curl_init', 'json_encode', 'hash_hmac'];
    foreach ($requiredFunctions as $func) {
        if (!function_exists($func)) {
            throw new Exception("Función PHP requerida no encontrada: {$func}");
        }
    }
    
    logDebug("Prerequisites check passed");
    
    // 2. Verificar conectividad básica del servidor
    logDebug("Step 2: Testing server connectivity");
    
    $serverHealth = checkWhatsAppServerHealth();
    if ($serverHealth['status'] === 'offline') {
        logDebug("Server health check failed", $serverHealth);
        throw new Exception("Servidor WhatsApp no disponible: " . $serverHealth['error']);
    }
    
    logDebug("Server connectivity OK", $serverHealth);
    
    // 3. Verificar estado actual
    logDebug("Step 3: Checking current status");
    
    $currentStatus = getWhatsAppStatus($userId);
    logDebug("Current status", $currentStatus);
    
    // Si ya está conectado, retornar información actual
    if ($currentStatus['status'] === 'connected' || $currentStatus['status'] === 'ready') {
        logDebug("Already connected, returning current info");
        
        echo json_encode([
            'success' => true,
            'status' => 'ready',
            'message' => 'WhatsApp ya está conectado',
            'phoneNumber' => $currentStatus['phoneNumber'] ?? null,
            'lastActivity' => $currentStatus['lastActivity'] ?? null
        ]);
        exit;
    }
    
    // Si hay QR disponible, devolverlo
    if ($currentStatus['status'] === 'qr_ready' && !empty($currentStatus['qrCode'])) {
        logDebug("QR already available");
        
        echo json_encode([
            'success' => true,
            'status' => 'waiting_qr',
            'message' => 'Escanea el código QR con tu WhatsApp',
            'qrCode' => $currentStatus['qrCode']
        ]);
        exit;
    }
    
    // 4. Iniciar proceso de conexión
    logDebug("Step 4: Starting connection process");
    
    $connectionResult = connectWhatsApp();
    logDebug("Connection result", $connectionResult);
    
    if (!$connectionResult['success']) {
        throw new Exception($connectionResult['message']);
    }
    
    // 5. Verificar resultado y preparar respuesta
    logDebug("Step 5: Preparing response");
    
    $response = [
        'success' => true,
        'status' => 'connecting',
        'message' => 'Proceso de conexión iniciado correctamente'
    ];
    
    // Si hay QR en el resultado, incluirlo
    if (isset($connectionResult['qrCode'])) {
        $response['status'] = 'waiting_qr';
        $response['qrCode'] = $connectionResult['qrCode'];
        $response['message'] = 'Escanea el código QR con tu WhatsApp';
    }
    
    logDebug("Connection process completed successfully", $response);
    echo json_encode($response);
    
} catch (Exception $e) {
    logDebug("FATAL ERROR in connection process", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Actualizar estado a error
    updateWhatsAppStatus('error');
    
    // Preparar respuesta de error
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'server_url' => WhatsAppConfig::SERVER_URL,
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s'),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ];
    
    // En modo desarrollo, incluir más detalles
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $response['debug_info']['full_trace'] = $e->getTraceAsString();
        $response['debug_info']['config_validation'] = WhatsAppConfig::validate();
    }
    
    http_response_code(500);
    echo json_encode($response);
}

logDebug("Request processing completed");
?>