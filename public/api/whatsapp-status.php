<?php
// public/api/whatsapp-status.php
// API para obtener estado de WhatsApp del servidor Node.js

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

// Configuración del servidor WhatsApp
$WHATSAPP_SERVER_URL = 'http://37.59.109.167:3001';
$JWT_SECRET = 'da3c7b9e13a38a0ea3dcbaaed1ec9ec1f0005f974adad7141b71a36e9f13e187'; // Del .env del servidor Node.js

$user = getAuthenticatedUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

try {
    // Obtener estado de la BD local
    $stmt = getPDO()->prepare('SELECT * FROM whatsapp_config WHERE usuario_id = ?');
    $stmt->execute([$userId]);
    $localConfig = $stmt->fetch();
    
    // Obtener estado del servidor Node.js
    $token = generateJWT($userId, $JWT_SECRET);
    $headers = ["Authorization: Bearer " . $token];
    
    $serverStatus = makeRequest($WHATSAPP_SERVER_URL . '/api/status', 'GET', null, $headers);
    
    $status = 'disconnected';
    $phoneNumber = null;
    $qr = null;
    $info = null;
    $lastActivity = null;
    
    if ($serverStatus && $serverStatus['success']) {
        // Usar datos del servidor como fuente de verdad
        $status = $serverStatus['status'];
        $qr = $serverStatus['qr'] ?? null;
        $info = $serverStatus['info'] ?? null;
        $phoneNumber = $info['phoneNumber'] ?? null;
        
        // Actualizar BD local con datos del servidor
        if ($status === 'ready' && $phoneNumber) {
            $stmt = getPDO()->prepare('
                INSERT INTO whatsapp_config (usuario_id, status, phone_number, last_activity, updated_at) 
                VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                phone_number = VALUES(phone_number),
                last_activity = CURRENT_TIMESTAMP,
                qr_code = NULL,
                updated_at = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$userId, $status, $phoneNumber]);
        } elseif ($status === 'waiting_qr' && $qr) {
            $stmt = getPDO()->prepare('
                INSERT INTO whatsapp_config (usuario_id, status, qr_code, updated_at) 
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                qr_code = VALUES(qr_code),
                updated_at = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$userId, $status, $qr]);
        } else {
            $stmt = getPDO()->prepare('
                INSERT INTO whatsapp_config (usuario_id, status, updated_at) 
                VALUES (?, ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                updated_at = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$userId, $status]);
        }
        
    } else {
        // Si no hay conexión con servidor, usar datos locales
        if ($localConfig) {
            $status = $localConfig['status'];
            $phoneNumber = $localConfig['phone_number'];
            $qr = $localConfig['qr_code'];
            $lastActivity = $localConfig['last_activity'];
        }
        
        // Marcar como error de servidor si había conexión previa
        if ($localConfig && in_array($localConfig['status'], ['ready', 'connecting', 'waiting_qr'])) {
            $stmt = getPDO()->prepare('
                UPDATE whatsapp_config 
                SET status = "server_error", updated_at = CURRENT_TIMESTAMP 
                WHERE usuario_id = ?
            ');
            $stmt->execute([$userId]);
            $status = 'server_error';
        }
    }
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'status' => $status,
        'phoneNumber' => $phoneNumber,
        'lastActivity' => $lastActivity,
        'serverConnected' => $serverStatus && $serverStatus['success']
    ];
    
    // Incluir QR solo si está disponible
    if ($qr) {
        $response['qr'] = $qr;
    }
    
    // Incluir info adicional si está disponible
    if ($info) {
        $response['info'] = $info;
    }
    
    // Mensajes según estado
    switch ($status) {
        case 'ready':
            $response['message'] = 'WhatsApp conectado y listo';
            break;
        case 'connecting':
            $response['message'] = 'Conectando a WhatsApp...';
            break;
        case 'waiting_qr':
            $response['message'] = 'Escanea el código QR con tu WhatsApp';
            break;
        case 'disconnected':
            $response['message'] = 'WhatsApp desconectado';
            break;
        case 'server_error':
            $response['message'] = 'Error de conexión con servidor WhatsApp';
            break;
        case 'auth_failed':
            $response['message'] = 'Error de autenticación con WhatsApp';
            break;
        default:
            $response['message'] = 'Estado desconocido';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Error en whatsapp-status: ' . $e->getMessage());
    
    // Intentar obtener al menos datos locales
    try {
        if ($localConfig) {
            echo json_encode([
                'success' => true,
                'status' => $localConfig['status'] ?? 'disconnected',
                'phoneNumber' => $localConfig['phone_number'],
                'lastActivity' => $localConfig['last_activity'],
                'message' => 'Error del servidor, mostrando último estado conocido',
                'serverConnected' => false,
                'error' => $e->getMessage()
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'status' => 'disconnected',
                'message' => 'WhatsApp no configurado',
                'serverConnected' => false
            ]);
        }
    } catch (Exception $dbError) {
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor'
        ]);
    }
}
?>