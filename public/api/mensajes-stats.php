<?php
/**
 * API para obtener estadísticas de mensajes de WhatsApp
 */

// Incluir configuración y funciones
require_once '../db-config.php';
require_once '../functions.php';
require_once '../whatsapp-functions.php';

// Cabeceras para JSON
header('Content-Type: application/json');

try {
    // Parámetros de filtrado
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $chatId = isset($_GET['chat']) ? trim($_GET['chat']) : '';
    
    // Consulta base
    $queryEnviados = 'SELECT COUNT(*) FROM mensajes_whatsapp mw
                      JOIN chats_whatsapp cw ON mw.chat_id = cw.chat_id
                      WHERE mw.direction = "sent"';
                      
    $queryRecibidos = 'SELECT COUNT(*) FROM mensajes_whatsapp mw
                       JOIN chats_whatsapp cw ON mw.chat_id = cw.chat_id
                       WHERE mw.direction = "received"';
    
    // Condiciones de filtrado adicionales
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = '(mw.body LIKE ? OR cw.nombre LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($chatId)) {
        $where[] = 'mw.chat_id = ?';
        $params[] = $chatId;
    }
    
    // Aplicar condiciones si existen
    if (!empty($where)) {
        $whereClause = ' AND ' . implode(' AND ', $where);
        $queryEnviados .= $whereClause;
        $queryRecibidos .= $whereClause;
    }
    
    // Ejecutar consultas
    $stmtEnviados = $pdo->prepare($queryEnviados);
    $stmtEnviados->execute($params);
    $enviados = $stmtEnviados->fetchColumn();
    
    $stmtRecibidos = $pdo->prepare($queryRecibidos);
    $stmtRecibidos->execute($params);
    $recibidos = $stmtRecibidos->fetchColumn();
    
    // Devolver resultado
    echo json_encode([
        'success' => true,
        'stats' => [
            'enviados' => (int)$enviados,
            'recibidos' => (int)$recibidos,
            'total' => (int)$enviados + (int)$recibidos
        ]
    ]);
} catch (\PDOException $e) {
    error_log('Error al obtener estadísticas de mensajes: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al obtener estadísticas']);
}