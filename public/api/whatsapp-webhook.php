<?php
/**
 * Webhook para recibir eventos de WhatsApp desde el servidor Node.js
 */

// Incluir configuración y funciones
require_once '../db-config.php';
require_once '../functions.php';
require_once '../whatsapp-functions.php';

// Cabeceras para JSON
header('Content-Type: application/json');

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos de la solicitud
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit;
}

// Verificar tipo de evento
$eventType = $data['event'] ?? '';

switch ($eventType) {
    case 'message':
        // Procesar mensaje recibido
        $result = processIncomingWhatsAppMessage($data);
        echo json_encode($result);
        break;
        
    case 'connection_update':
        // Actualizar estado de conexión
        handleConnectionUpdate($data);
        break;
        
    case 'qr_update':
        // Actualizar código QR
        handleQRUpdate($data);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Tipo de evento desconocido']);
}

/**
 * Maneja eventos de actualización de estado de conexión
 */
function handleConnectionUpdate($data) {
    global $pdo;
    
    try {
        $status = $data['status'] ?? 'unknown';
        $timestamp = time();
        
        // Actualizar estado en la base de datos
        $stmt = $pdo->prepare("INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_status', ?) 
                              ON DUPLICATE KEY UPDATE valor = ?");
        $stmt->execute([$status, $status]);
        
        // Actualizar última actividad
        $stmtActivity = $pdo->prepare("INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_last_activity', ?) 
                                      ON DUPLICATE KEY UPDATE valor = ?");
        $stmtActivity->execute([$timestamp, $timestamp]);
        
        echo json_encode(['success