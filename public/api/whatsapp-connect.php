<?php
// public/api/whatsapp-connect.php
// API para conectar WhatsApp con servidor Node.js

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

// Configuración del servidor WhatsApp
$WHATSAPP_SERVER_URL = 'http://server.reservabot.es:3001';
$JWT_SECRET = 'da3c7b9e13a38a0ea3dcbaaed1ec9ec1f0005f974adad7141b71a36e9f13e187';

// Función de log detallado
function logDebug($message, $data = null) {
    $logEntry = "[" . date('Y-m-d H:i:s') . "] WHATSAPP-CONNECT: " . $message;
    if ($data !== null) {
        $logEntry .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    error_log($logEntry);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logDebug("Invalid method", ['method' => $_SERVER['REQUEST_METHOD']]);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$user = getAuthenticatedUser();
if (!$user) {
    logDebug("User not authenticated");
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];
logDebug("Starting WhatsApp connection process", [
    'userId' => $userId,
    'server_url' => $WHATSAPP_SERVER_URL,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

try {
    $token = generateJWT($userId, $JWT_SECRET);    
    $headers = ["Authorization: Bearer " . $token];
        
    try {
        $statusResult = makeRequest($WHATSAPP_SERVER_URL . '/api/status', 'GET', null, $headers);
        logDebug("Status check result", $statusResult);
        
        if ($statusResult && $statusResult['success']) {
            if ($statusResult['status'] === 'connected') {
                logDebug("WhatsApp already connected, updating database");
                
                $phoneNumber = $statusResult['info']['phoneNumber'] ?? null;
                $stmt = getPDO()->prepare('
                    INSERT INTO whatsapp_config (usuario_id, status, phone_number, updated_at) 
                    VALUES (?, "connected", ?, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE 
                    status = "connected", 
                    phone_number = VALUES(phone_number),
                    updated_at = CURRENT_TIMESTAMP
                ');
                $stmt->execute([$userId, $phoneNumber]);
                
                logDebug("Database updated successfully", ['usuario_id' => $userId, "status" => "ready", "phone_number" => $phoneNumber]);
                
                echo json_encode([
                    'success' => true,
                    'status' => 'ready',
                    'message' => 'WhatsApp ya está conectado',
                    'phoneNumber' => $statusResult['info']['phoneNumber'] ?? null
                ]);
                exit;
            }
            
            if ($statusResult['status'] === 'waiting_qr' && !empty($statusResult['qr'])) {
                logDebug("QR already available, updating database");
                
                $stmt = getPDO()->prepare('
                    INSERT INTO whatsapp_config (usuario_id, status, qr_code, updated_at) 
                    VALUES (?, "waiting_qr", ?, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE 
                    status = "waiting_qr", 
                    qr_code = VALUES(qr_code),
                    updated_at = CURRENT_TIMESTAMP
                ');
                $stmt->execute([$userId, $statusResult['qr']]);
                
                logDebug("Database updated with QR code");
                
                echo json_encode([
                    'success' => true,
                    'status' => 'waiting_qr',
                    'message' => 'Escanea el código QR con tu WhatsApp',
                    'qr' => $statusResult['qr']
                ]);
                exit;
            }
            
            logDebug("Current server status does not require immediate action", ['status' => $statusResult['status']]);
        } else {
            logDebug("Status check failed or returned invalid response");
        }
    } catch (Exception $statusError) {
        logDebug("Status check error (continuing with new connection)", ['error' => $statusError->getMessage()]);
        // Continuar con nueva conexión aunque falle el status check
    }
    
    $connectData = ['userId' => $userId];
    
    try {
        $connectResult = makeRequest($WHATSAPP_SERVER_URL . '/api/connect', 'POST', $connectData, $headers);
        logDebug("Connect request result", $connectResult);
        
        if (!$connectResult || !$connectResult['success']) {
            $errorMsg = $connectResult['error'] ?? 'Error desconocido conectando al servidor WhatsApp';
            logDebug("Connect request failed", ['error' => $errorMsg, 'full_result' => $connectResult]);
            throw new Exception($errorMsg);
        }
        
        logDebug("Connect request successful");
        
    } catch (Exception $connectError) {
        logDebug("Connect request exception", [
            'error' => $connectError->getMessage(),
            'file' => $connectError->getFile(),
            'line' => $connectError->getLine()
        ]);
        throw $connectError;
    }
    
    try {
        $stmt = getPDO()->prepare('
            INSERT INTO whatsapp_config (usuario_id, status, updated_at) 
            VALUES (?, "connecting", CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE 
            status = "connecting", 
            phone_number = NULL,
            qr_code = NULL,
            updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([$userId]);
        logDebug("Database updated successfully", ['usuario_id' => $userId, "status" => "connecting"]);
        
    } catch (Exception $dbError) {
        logDebug("Database update error", ['error' => $dbError->getMessage()]);
        throw new Exception("Error actualizando base de datos: " . $dbError->getMessage());
    }
    
    logDebug("WhatsApp connection process completed successfully");
    
    echo json_encode([
        'success' => true,
        'status' => 'connecting',
        'message' => 'Proceso de conexión iniciado. Verifica el estado para obtener el código QR.'
    ]);
    
} catch (Exception $e) {
    logDebug("FATAL ERROR in connection process", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Actualizar BD con error
    try {
        $stmt = getPDO()->prepare('
            INSERT INTO whatsapp_config (usuario_id, status, updated_at) 
            VALUES (?, "error", CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE 
            status = "error", 
            updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([$userId]);
        logDebug("Error status saved to database");
    } catch (Exception $dbError) {
        logDebug("Failed to save error status to database", ['db_error' => $dbError->getMessage()]);
    }
    
    // Respuesta de error detallada
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'server_url' => $WHATSAPP_SERVER_URL,
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s'),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ];
    
    // En modo desarrollo, incluir más detalles
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $response['debug_info']['full_trace'] = $e->getTraceAsString();
        $response['debug_info']['server_response'] = $statusResult ?? null;
    }
    
    echo json_encode($response);
}
?>