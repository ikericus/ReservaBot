<?php
// public/api/whatsapp-stats.php
// API para obtener estadísticas de WhatsApp

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

// Solo permitir POST y GET
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'])) {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
$user = getAuthenticatedUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

// Obtener parámetros
$periodo = $_GET['periodo'] ?? $_POST['periodo'] ?? 'hoy';
$validPeriodos = ['hoy', 'semana', 'mes'];

if (!in_array($periodo, $validPeriodos)) {
    $periodo = 'hoy';
}

try {
    $pdo = getPDO();
    
    // Calcular fechas según el período
    switch ($periodo) {
        case 'semana':
            $fechaInicio = date('Y-m-d', strtotime('monday this week'));
            $fechaFin = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'mes':
            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-t');
            break;
        default: // hoy
            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d');
    }
    
    $fechaInicioFull = $fechaInicio . ' 00:00:00';
    $fechaFinFull = $fechaFin . ' 23:59:59';
    
    // Estadísticas básicas de mensajes
    $stats = [];
    
    // 1. Mensajes enviados
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as total
        FROM mensajes m
        JOIN conversaciones c ON m.conversacion_id = c.id
        WHERE c.usuario_id = ? 
        AND m.tipo = "saliente" 
        AND m.timestamp BETWEEN ? AND ?
    ');
    $stmt->execute([$userId, $fechaInicioFull, $fechaFinFull]);
    $stats['mensajes_enviados'] = (int)$stmt->fetchColumn();
    
    // 2. Mensajes recibidos
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as total
        FROM mensajes m
        JOIN conversaciones c ON m.conversacion_id = c.id
        WHERE c.usuario_id = ? 
        AND m.tipo = "entrante" 
        AND m.timestamp BETWEEN ? AND ?
    ');
    $stmt->execute([$userId, $fechaInicioFull, $fechaFinFull]);
    $stats['mensajes_recibidos'] = (int)$stmt->fetchColumn();
    
    // 3. Conversaciones activas
    $stmt = $pdo->prepare('
        SELECT COUNT(DISTINCT c.id) as total
        FROM conversaciones c
        JOIN mensajes m ON m.conversacion_id = c.id
        WHERE c.usuario_id = ? 
        AND m.timestamp BETWEEN ? AND ?
    ');
    $stmt->execute([$userId, $fechaInicioFull, $fechaFinFull]);
    $stats['conversaciones_activas'] = (int)$stmt->fetchColumn();
    
    // 4. Total de conversaciones
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as total
        FROM conversaciones 
        WHERE usuario_id = ?
    ');
    $stmt->execute([$userId]);
    $stats['total_conversaciones'] = (int)$stmt->fetchColumn();
    
    // 5. Mensajes no leídos
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as total
        FROM mensajes m
        JOIN conversaciones c ON m.conversacion_id = c.id
        WHERE c.usuario_id = ? 
        AND m.tipo = "entrante" 
        AND m.leido = 0
    ');
    $stmt->execute([$userId]);
    $stats['mensajes_no_leidos'] = (int)$stmt->fetchColumn();
    
    // 6. Estado de conexión de WhatsApp
    $stmt = $pdo->prepare('
        SELECT status, phone_number, last_activity 
        FROM whatsapp_config 
        WHERE usuario_id = ?
    ');
    $stmt->execute([$userId]);
    $whatsappConfig = $stmt->fetch();
    
    $stats['whatsapp_status'] = $whatsappConfig ? $whatsappConfig['status'] : 'disconnected';
    $stats['whatsapp_phone'] = $whatsappConfig ? $whatsappConfig['phone_number'] : null;
    $stats['whatsapp_last_activity'] = $whatsappConfig ? $whatsappConfig['last_activity'] : null;
    
    // 7. Conversaciones recientes (últimas 5)
    $stmt = $pdo->prepare('
        SELECT 
            cliente_phone,
            cliente_nombre,
            ultimo_mensaje,
            no_leidos,
            updated_at
        FROM conversaciones 
        WHERE usuario_id = ? 
        ORDER BY updated_at DESC 
        LIMIT 5
    ');
    $stmt->execute([$userId]);
    $stats['conversaciones_recientes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 8. Estadísticas de reservas con WhatsApp (si aplica)
    $stmt = $pdo->prepare('
        SELECT 
            COUNT(*) as total_reservas,
            SUM(CASE WHEN whatsapp_confirmacion_enviada = 1 THEN 1 ELSE 0 END) as confirmaciones_enviadas,
            SUM(CASE WHEN whatsapp_recordatorio_enviado = 1 THEN 1 ELSE 0 END) as recordatorios_enviados
        FROM reservas 
        WHERE usuario_id = ? 
        AND fecha BETWEEN ? AND ?
    ');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $reservasStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats['reservas'] = [
        'total' => (int)$reservasStats['total_reservas'],
        'confirmaciones_enviadas' => (int)$reservasStats['confirmaciones_enviadas'],
        'recordatorios_enviados' => (int)$reservasStats['recordatorios_enviados']
    ];
    
    // 9. Distribución de mensajes por día (para gráficos)
    $stmt = $pdo->prepare('
        SELECT 
            DATE(m.timestamp) as fecha,
            SUM(CASE WHEN m.tipo = "saliente" THEN 1 ELSE 0 END) as enviados,
            SUM(CASE WHEN m.tipo = "entrante" THEN 1 ELSE 0 END) as recibidos
        FROM mensajes m
        JOIN conversaciones c ON m.conversacion_id = c.id
        WHERE c.usuario_id = ? 
        AND m.timestamp BETWEEN ? AND ?
        GROUP BY DATE(m.timestamp)
        ORDER BY fecha ASC
    ');
    $stmt->execute([$userId, $fechaInicioFull, $fechaFinFull]);
    $stats['distribucion_diaria'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 10. Top 5 contactos más activos
    $stmt = $pdo->prepare('
        SELECT 
            c.cliente_phone,
            c.cliente_nombre,
            COUNT(m.id) as total_mensajes,
            SUM(CASE WHEN m.tipo = "entrante" THEN 1 ELSE 0 END) as mensajes_recibidos,
            SUM(CASE WHEN m.tipo = "saliente" THEN 1 ELSE 0 END) as mensajes_enviados,
            MAX(m.timestamp) as ultimo_mensaje
        FROM conversaciones c
        JOIN mensajes m ON m.conversacion_id = c.id
        WHERE c.usuario_id = ? 
        AND m.timestamp BETWEEN ? AND ?
        GROUP BY c.id
        ORDER BY total_mensajes DESC
        LIMIT 5
    ');
    $stmt->execute([$userId, $fechaInicioFull, $fechaFinFull]);
    $stats['contactos_activos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 11. Horarios de mayor actividad
    $stmt = $pdo->prepare('
        SELECT 
            HOUR(m.timestamp) as hora,
            COUNT(*) as total_mensajes
        FROM mensajes m
        JOIN conversaciones c ON m.conversacion_id = c.id
        WHERE c.usuario_id = ? 
        AND m.timestamp BETWEEN ? AND ?
        GROUP BY HOUR(m.timestamp)
        ORDER BY hora ASC
    ');
    $stmt->execute([$userId, $fechaInicioFull, $fechaFinFull]);
    $stats['actividad_por_hora'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 12. Autorespuestas activas
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as total
        FROM autorespuestas_whatsapp 
        WHERE usuario_id = ? AND is_active = 1
    ');
    $stmt->execute([$userId]);
    $stats['autorespuestas_activas'] = (int)$stmt->fetchColumn();
    
    // Formatear fechas para respuesta
    foreach ($stats['conversaciones_recientes'] as &$conv) {
        if ($conv['updated_at']) {
            $conv['updated_at_formatted'] = date('d/m/Y H:i', strtotime($conv['updated_at']));
        }
    }
    
    foreach ($stats['contactos_activos'] as &$contacto) {
        if ($contacto['ultimo_mensaje']) {
            $contacto['ultimo_mensaje_formatted'] = date('d/m/Y H:i', strtotime($contacto['ultimo_mensaje']));
        }
        // Formatear nombre del contacto
        if (empty($contacto['cliente_nombre']) || $contacto['cliente_nombre'] === $contacto['cliente_phone']) {
            $contacto['cliente_nombre'] = 'Contacto ' . substr($contacto['cliente_phone'], -4);
        }
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'periodo' => $periodo,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'stats' => $stats,
        'resumen' => [
            'whatsapp_conectado' => $stats['whatsapp_status'] === 'ready',
            'actividad_total' => $stats['mensajes_enviados'] + $stats['mensajes_recibidos'],
            'tasa_respuesta' => $stats['mensajes_recibidos'] > 0 
                ? round(($stats['mensajes_enviados'] / $stats['mensajes_recibidos']) * 100, 1) 
                : 0,
            'promedio_mensajes_por_conversacion' => $stats['conversaciones_activas'] > 0 
                ? round(($stats['mensajes_enviados'] + $stats['mensajes_recibidos']) / $stats['conversaciones_activas'], 1) 
                : 0
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error obteniendo estadísticas WhatsApp: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'debug_info' => [
            'user_id' => $userId,
            'periodo' => $periodo,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?>