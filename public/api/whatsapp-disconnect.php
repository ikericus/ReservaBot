<?php
// public/api/whatsapp-disconnect.php
// API para desconectar WhatsApp del servidor Node.js

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
    // Verificar estado actual en BD
    $stmt = getPDO()->prepare('SELECT status FROM whatsapp_config WHERE usuario_id = ?');
    $stmt->execute([$userId]);
    $config = $stmt->fetch();
    
    if (!$config || $config['status'] === 'disconnected') {
        echo json_encode([
            'success' => true,
            'message' => 'WhatsApp ya estaba desconectado'
        ]);
        exit;
    }
    
    // Desconectar del servidor Node.js
    $token = generateJWT($userId, $JWT_SECRET);
    $headers = ["Authorization: Bearer " . $token];
    $disconnectData = ['userId' => $userId];
    
    $result = makeRequest($WHATSAPP_SERVER_URL . '/api/disconnect', 'POST', $disconnectData, $headers);
    
    // Actualizar BD independientemente del resultado del servidor
    // (para limpiar estado local)
    $stmt = getPDO()->prepare('
        UPDATE whatsapp_config 
        SET status = "disconnected", 
            phone_number = NULL, 
            qr_code = NULL,
            token = NULL,
            last_activity = NULL,
            updated_at = CURRENT_TIMESTAMP 
        WHERE usuario_id = ?
    ');
    $stmt->execute([$userId]);
    
    if ($result && $result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'WhatsApp desconectado correctamente del servidor'
        ]);
    } else {
        // Aún así reportar éxito si se limpió la BD local
        echo json_encode([
            'success' => true,
            'message' => 'WhatsApp desconectado localmente. ' . ($result['error'] ?? 'Error en servidor remoto'),
            'warning' => 'Posible error en servidor remoto'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en whatsapp-disconnect: ' . $e->getMessage());
    
    // Intentar limpiar BD de todas formas
    try {
        $stmt = getPDO()->prepare('
            UPDATE whatsapp_config 
            SET status = "disconnected", 
                phone_number = NULL, 
                qr_code = NULL,
                token = NULL,
                updated_at = CURRENT_TIMESTAMP 
            WHERE usuario_id = ?
        ');
        $stmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'WhatsApp desconectado localmente',
            'warning' => 'Error comunicándose con servidor: ' . $e->getMessage()
        ]);
    } catch (Exception $dbError) {
        error_log('Error actualizando BD en disconnect: ' . $dbError->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor'
        ]);
    }
}
?>