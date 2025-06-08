<?php
// public/api/whatsapp-connect.php
// API para conectar WhatsApp con servidor Node.js

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

// Configuración del servidor WhatsApp
$WHATSAPP_SERVER_URL = 'http://37.59.109.167:3001';
$JWT_SECRET = 'da3c7b9e13a38a0ea3dcbaaed1ec9ec1f0005f974adad7141b71a36e9f13e187'; // Del .env del servidor Node.js

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$user = getAuthenticatedUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

try {
    // Verificar si ya está conectado en el servidor
    $token = generateJWT($userId, $JWT_SECRET);
    $headers = ["Authorization: Bearer " . $token];
    
    $statusResult = makeRequest($WHATSAPP_SERVER_URL . '/api/status', 'GET', null, $headers);
    
    if ($statusResult && $statusResult['success']) {
        if ($statusResult['status'] === 'ready') {
            // Ya está conectado
            $stmt = getPDO()->prepare('
                INSERT INTO whatsapp_config (usuario_id, status, phone_number, updated_at) 
                VALUES (?, "ready", ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE 
                status = "ready", 
                phone_number = VALUES(phone_number),
                updated_at = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$userId, $statusResult['info']['phoneNumber'] ?? null]);
            
            echo json_encode([
                'success' => true,
                'status' => 'ready',
                'message' => 'WhatsApp ya está conectado',
                'phoneNumber' => $statusResult['info']['phoneNumber'] ?? null
            ]);
            exit;
        }
        
        if ($statusResult['status'] === 'waiting_qr' && !empty($statusResult['qr'])) {
            // Ya está esperando QR
            $stmt = getPDO()->prepare('
                INSERT INTO whatsapp_config (usuario_id, status, qr_code, updated_at) 
                VALUES (?, "waiting_qr", ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE 
                status = "waiting_qr", 
                qr_code = VALUES(qr_code),
                updated_at = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$userId, $statusResult['qr']]);
            
            echo json_encode([
                'success' => true,
                'status' => 'waiting_qr',
                'message' => 'Escanea el código QR con tu WhatsApp',
                'qr' => $statusResult['qr']
            ]);
            exit;
        }
    }
    
    // Iniciar nueva conexión
    $connectData = ['userId' => $userId];
    $connectResult = makeRequest($WHATSAPP_SERVER_URL . '/api/connect', 'POST', $connectData, $headers);
    
    if (!$connectResult || !$connectResult['success']) {
        throw new Exception($connectResult['error'] ?? 'Error conectando al servidor WhatsApp');
    }
    
    // Actualizar base de datos
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
    
    echo json_encode([
        'success' => true,
        'status' => 'connecting',
        'message' => 'Proceso de conexión iniciado. Verifica el estado para obtener el código QR.'
    ]);
    
} catch (Exception $e) {
    error_log('Error en whatsapp-connect: ' . $e->getMessage());
    
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
    } catch (Exception $dbError) {
        error_log('Error actualizando BD: ' . $dbError->getMessage());
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>