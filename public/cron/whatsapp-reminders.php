<?php
// public/cron/whatsapp-reminders.php
// Cron job para enviar recordatorios automáticos de WhatsApp

// Solo permitir ejecución desde línea de comandos o localhost
if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/whatsapp-helpers.php';

// Log inicio del cron
error_log('[CRON] Iniciando envío de recordatorios WhatsApp - ' . date('Y-m-d H:i:s'));

try {
    // Buscar reservas para mañana que necesiten recordatorio
    $fechaManana = date('Y-m-d', strtotime('+1 day'));
    
    $stmt = getPDO()->prepare('
        SELECT r.id, r.nombre, r.telefono, r.fecha, r.hora, r.usuario_id,
               u.negocio, u.nombre as usuario_nombre,
               wc.status as whatsapp_status
        FROM reservas r
        JOIN usuarios u ON r.usuario_id = u.id
        LEFT JOIN whatsapp_config wc ON u.id = wc.usuario_id
        WHERE r.fecha = ?
        AND r.estado = "confirmada"
        AND (r.whatsapp_recordatorio_enviado IS NULL OR r.whatsapp_recordatorio_enviado = 0)
        AND r.telefono IS NOT NULL
        AND r.telefono != ""
        AND wc.status = "connected"
        ORDER BY r.fecha, r.hora
    ');
    $stmt->execute([$fechaManana]);
    $reservas = $stmt->fetchAll();
    
    $enviados = 0;
    $errores = 0;
    $omitidos = 0;
    
    foreach ($reservas as $reserva) {
        try {
            // Verificar si el recordatorio automático está habilitado para este usuario
            $stmt = getPDO()->prepare('
                SELECT valor 
                FROM configuraciones_usuario 
                WHERE usuario_id = ? AND clave = "whatsapp_auto_reminder"
            ');
            $stmt->execute([$reserva['usuario_id']]);
            $autoReminderEnabled = $stmt->fetchColumn();
            
            if ($autoReminderEnabled !== 'true') {
                $omitidos++;
                error_log("[CRON] Recordatorio omitido para reserva {$reserva['id']} - función deshabilitada");
                continue;
            }
            
            // Verificar hora de envío (no enviar muy tarde o muy temprano)
            $horaActual = date('H');
            if ($horaActual < 9 || $horaActual > 20) {
                error_log("[CRON] Recordatorio pospuesto para reserva {$reserva['id']} - fuera de horario");
                continue;
            }
            
            // Enviar recordatorio
            $resultado = sendReservationReminder($reserva['id']);
            
            if ($resultado) {
                $enviados++;
                error_log("[CRON] Recordatorio enviado para reserva {$reserva['id']} - {$reserva['nombre']} ({$reserva['telefono']})");
            } else {
                $errores++;
                error_log("[CRON] Error enviando recordatorio para reserva {$reserva['id']}");
            }
            
            // Pequeña pausa entre envíos para no saturar
            sleep(2);
            
        } catch (Exception $e) {
            $errores++;
            error_log("[CRON] Error procesando reserva {$reserva['id']}: " . $e->getMessage());
        }
    }
    
    // Log resumen
    $totalProcesadas = count($reservas);
    error_log("[CRON] Recordatorios WhatsApp completados - Total: {$totalProcesadas}, Enviados: {$enviados}, Errores: {$errores}, Omitidos: {$omitidos}");
    
    // Guardar estadísticas en BD
    $stmt = getPDO()->prepare('
        INSERT INTO cron_logs (tipo, fecha, total_procesados, exitosos, errores, omitidos, detalles) 
        VALUES ("whatsapp_reminders", NOW(), ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $totalProcesadas, 
        $enviados, 
        $errores, 
        $omitidos,
        json_encode(['fecha_recordatorio' => $fechaManana])
    ]);
    
} catch (Exception $e) {
    error_log('[CRON] Error crítico en recordatorios WhatsApp: ' . $e->getMessage());
    
    // Notificar error crítico por email si está configurado
    $adminEmail = $_ENV['ADMIN_EMAIL'] ?? null;
    if ($adminEmail) {
        $subject = 'Error crítico en cron de recordatorios WhatsApp';
        $message = "Error en el cron job de recordatorios WhatsApp:\n\n" . $e->getMessage() . "\n\nFecha: " . date('Y-m-d H:i:s');
        @mail($adminEmail, $subject, $message);
    }
}

error_log('[CRON] Finalizando envío de recordatorios WhatsApp - ' . date('Y-m-d H:i:s'));
?>

<?php
// public/cron/whatsapp-confirmations.php
// Cron job para enviar confirmaciones automáticas de reservas nuevas

if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/whatsapp-helpers.php';

error_log('[CRON] Iniciando envío de confirmaciones WhatsApp - ' . date('Y-m-d H:i:s'));

try {
    // Buscar reservas creadas en las últimas 2 horas que necesiten confirmación
    $stmt = getPDO()->prepare('
        SELECT r.id, r.nombre, r.telefono, r.fecha, r.hora, r.usuario_id,
               u.negocio, u.nombre as usuario_nombre,
               wc.status as whatsapp_status
        FROM reservas r
        JOIN usuarios u ON r.usuario_id = u.id
        LEFT JOIN whatsapp_config wc ON u.id = wc.usuario_id
        WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
        AND r.estado IN ("pendiente", "confirmada")
        AND (r.whatsapp_confirmacion_enviada IS NULL OR r.whatsapp_confirmacion_enviada = 0)
        AND r.telefono IS NOT NULL
        AND r.telefono != ""
        AND wc.status = "connected"
        ORDER BY r.created_at DESC
    ');
    $stmt->execute();
    $reservas = $stmt->fetchAll();
    
    $enviados = 0;
    $errores = 0;
    $omitidos = 0;
    
    foreach ($reservas as $reserva) {
        try {
            // Verificar si la confirmación automática está habilitada
            $stmt = getPDO()->prepare('
                SELECT valor 
                FROM configuraciones_usuario 
                WHERE usuario_id = ? AND clave = "whatsapp_auto_confirmation"
            ');
            $stmt->execute([$reserva['usuario_id']]);
            $autoConfirmationEnabled = $stmt->fetchColumn();
            
            if ($autoConfirmationEnabled !== 'true') {
                $omitidos++;
                continue;
            }
            
            // Enviar confirmación
            $resultado = sendReservationConfirmation($reserva['id']);
            
            if ($resultado) {
                $enviados++;
                error_log("[CRON] Confirmación enviada para reserva {$reserva['id']} - {$reserva['nombre']}");
            } else {
                $errores++;
            }
            
            sleep(1);
            
        } catch (Exception $e) {
            $errores++;
            error_log("[CRON] Error procesando confirmación para reserva {$reserva['id']}: " . $e->getMessage());
        }
    }
    
    $totalProcesadas = count($reservas);
    error_log("[CRON] Confirmaciones WhatsApp completadas - Total: {$totalProcesadas}, Enviados: {$enviados}, Errores: {$errores}, Omitidos: {$omitidos}");
    
    // Guardar estadísticas
    $stmt = getPDO()->prepare('
        INSERT INTO cron_logs (tipo, fecha, total_procesados, exitosos, errores, omitidos) 
        VALUES ("whatsapp_confirmations", NOW(), ?, ?, ?, ?)
    ');
    $stmt->execute([$totalProcesadas, $enviados, $errores, $omitidos]);
    
} catch (Exception $e) {
    error_log('[CRON] Error crítico en confirmaciones WhatsApp: ' . $e->getMessage());
}

error_log('[CRON] Finalizando envío de confirmaciones WhatsApp - ' . date('Y-m-d H:i:s'));
?>

<?php
// public/cron/cleanup-sessions.php
// Cron job para limpiar sesiones antiguas de WhatsApp

if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

error_log('[CRON] Iniciando limpieza de sesiones WhatsApp - ' . date('Y-m-d H:i:s'));

try {
    // Limpiar configuraciones muy antiguas sin actividad
    $stmt = getPDO()->prepare('
        UPDATE whatsapp_config 
        SET status = "disconnected", qr_code = NULL, token = NULL 
        WHERE status != "connected" 
        AND updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ');
    $stmt->execute();
    $configsLimpiadas = $stmt->rowCount();
    
    // Limpiar mensajes muy antiguos (más de 6 meses)
    $stmt = getPDO()->prepare('
        DELETE FROM mensajes 
        WHERE timestamp < DATE_SUB(NOW(), INTERVAL 6 MONTH)
    ');
    $stmt->execute();
    $mensajesLimpiados = $stmt->rowCount();
    
    // Limpiar conversaciones sin mensajes
    $stmt = getPDO()->prepare('
        DELETE c FROM conversaciones c 
        LEFT JOIN mensajes m ON c.id = m.conversacion_id 
        WHERE m.id IS NULL 
        AND c.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ');
    $stmt->execute();
    $conversacionesLimpiadas = $stmt->rowCount();
    
    // Limpiar logs de cron antiguos (más de 3 meses)
    $stmt = getPDO()->prepare('
        DELETE FROM cron_logs 
        WHERE fecha < DATE_SUB(NOW(), INTERVAL 3 MONTH)
    ');
    $stmt->execute();
    $logsLimpiados = $stmt->rowCount();
    
    error_log("[CRON] Limpieza completada - Configs: {$configsLimpiadas}, Mensajes: {$mensajesLimpiados}, Conversaciones: {$conversacionesLimpiadas}, Logs: {$logsLimpiados}");
    
    // Guardar estadísticas de limpieza
    $stmt = getPDO()->prepare('
        INSERT INTO cron_logs (tipo, fecha, total_procesados, exitosos, errores, detalles) 
        VALUES ("cleanup", NOW(), ?, ?, 0, ?)
    ');
    $stmt->execute([
        $configsLimpiadas + $mensajesLimpiados + $conversacionesLimpiadas + $logsLimpiados,
        $configsLimpiadas + $mensajesLimpiados + $conversacionesLimpiadas + $logsLimpiados,
        json_encode([
            'configs_limpiadas' => $configsLimpiadas,
            'mensajes_limpiados' => $mensajesLimpiados,
            'conversaciones_limpiadas' => $conversacionesLimpiadas,
            'logs_limpiados' => $logsLimpiados
        ])
    ]);
    
} catch (Exception $e) {
    error_log('[CRON] Error en limpieza: ' . $e->getMessage());
}

error_log('[CRON] Finalizando limpieza de sesiones WhatsApp - ' . date('Y-m-d H:i:s'));
?>

<?php
// public/monitor/whatsapp-health.php
// Monitor de salud del servidor WhatsApp

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

// Verificar autenticación básica para el monitor
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$auth || $auth !== 'Bearer ' . ($_ENV['MONITOR_TOKEN'] ?? 'monitor-secret')) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $health = [
        'timestamp' => date('c'),
        'status' => 'healthy',
        'services' => [],
        'metrics' => [],
        'alerts' => []
    ];
    
    // 1. Verificar servidor WhatsApp
    $whatsappServer = checkWhatsAppServer();
    $health['services']['whatsapp_server'] = $whatsappServer;
    
    // 2. Verificar base de datos
    $database = checkDatabase();
    $health['services']['database'] = $database;
    
    // 3. Verificar conexiones activas de WhatsApp
    $activeConnections = checkActiveConnections();
    $health['services']['active_connections'] = $activeConnections;
    
    // 4. Métricas de rendimiento
    $health['metrics'] = getPerformanceMetrics();
    
    // 5. Verificar alertas
    $health['alerts'] = checkAlerts();
    
    // Determinar estado general
    $allHealthy = true;
    foreach ($health['services'] as $service) {
        if ($service['status'] !== 'healthy') {
            $allHealthy = false;
            break;
        }
    }
    
    $health['status'] = $allHealthy ? 'healthy' : 'degraded';
    
    // Agregar alertas críticas si hay problemas
    if (!$allHealthy) {
        $health['alerts'][] = [
            'level' => 'critical',
            'message' => 'Uno o más servicios no están funcionando correctamente',
            'timestamp' => date('c')
        ];
    }
    
    http_response_code($allHealthy ? 200 : 503);
    echo json_encode($health, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}

function checkWhatsAppServer() {
    $whatsappServerUrl = $_ENV['WHATSAPP_SERVER_URL'] ?? 'http://localhost:3001';
    
    $startTime = microtime(true);
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $result = @file_get_contents($whatsappServerUrl . '/health', false, $context);
    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($result === false) {
        return [
            'status' => 'unhealthy',
            'message' => 'Servidor WhatsApp no responde',
            'response_time_ms' => null,
            'last_check' => date('c')
        ];
    }
    
    $healthData = json_decode($result, true);
    
    return [
        'status' => 'healthy',
        'message' => 'Servidor WhatsApp funcionando correctamente',
        'response_time_ms' => $responseTime,
        'server_info' => $healthData ?? null,
        'last_check' => date('c')
    ];
}

function checkDatabase() {
    try {
        $startTime = microtime(true);
        $stmt = getPDO()->query('SELECT 1');
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'status' => 'healthy',
            'message' => 'Base de datos funcionando correctamente',
            'response_time_ms' => $responseTime,
            'last_check' => date('c')
        ];
    } catch (Exception $e) {
        return [
            'status' => 'unhealthy',
            'message' => 'Error en base de datos: ' . $e->getMessage(),
            'response_time_ms' => null,
            'last_check' => date('c')
        ];
    }
}

function checkActiveConnections() {
    try {
        $stmt = getPDO()->query('
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = "connected" THEN 1 ELSE 0 END) as connected,
                SUM(CASE WHEN status = "connecting" THEN 1 ELSE 0 END) as connecting,
                SUM(CASE WHEN status = "disconnected" THEN 1 ELSE 0 END) as disconnected
            FROM whatsapp_config
        ');
        $stats = $stmt->fetch();
        
        return [
            'status' => 'healthy',
            'message' => 'Conexiones WhatsApp monitoreadas',
            'connections' => [
                'total' => (int)$stats['total'],
                'connected' => (int)$stats['connected'],
                'connecting' => (int)$stats['connecting'], 
                'disconnected' => (int)$stats['disconnected']
            ],
            'last_check' => date('c')
        ];
    } catch (Exception $e) {
        return [
            'status' => 'unhealthy',
            'message' => 'Error verificando conexiones: ' . $e->getMessage(),
            'last_check' => date('c')
        ];
    }
}

function getPerformanceMetrics() {
    try {
        // Mensajes enviados hoy
        $stmt = getPDO()->query('
            SELECT COUNT(*) 
            FROM mensajes m
            JOIN conversaciones c ON m.conversacion_id = c.id
            WHERE m.tipo = "saliente" 
            AND DATE(m.timestamp) = CURDATE()
        ');
        $mensajesHoy = (int)$stmt->fetchColumn();
        
        // Mensajes recibidos hoy
        $stmt = getPDO()->query('
            SELECT COUNT(*) 
            FROM mensajes m
            JOIN conversaciones c ON m.conversacion_id = c.id
            WHERE m.tipo = "entrante" 
            AND DATE(m.timestamp) = CURDATE()
        ');
        $recibidosHoy = (int)$stmt->fetchColumn();
        
        // Conversaciones activas (con actividad en las últimas 24h)
        $stmt = getPDO()->query('
            SELECT COUNT(DISTINCT conversacion_id) 
            FROM mensajes 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ');
        $conversacionesActivas = (int)$stmt->fetchColumn();
        
        // Errores recientes en cron jobs
        $stmt = getPDO()->query('
            SELECT SUM(errores) 
            FROM cron_logs 
            WHERE fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ');
        $erroresCron = (int)$stmt->fetchColumn();
        
        return [
            'mensajes_enviados_hoy' => $mensajesHoy,
            'mensajes_recibidos_hoy' => $recibidosHoy,
            'conversaciones_activas_24h' => $conversacionesActivas,
            'errores_cron_24h' => $erroresCron,
            'fecha_reporte' => date('c')
        ];
    } catch (Exception $e) {
        return [
            'error' => 'No se pudieron obtener métricas: ' . $e->getMessage()
        ];
    }
}

function checkAlerts() {
    $alerts = [];
    
    try {
        // Verificar conexiones perdidas recientemente
        $stmt = getPDO()->query('
            SELECT COUNT(*) 
            FROM whatsapp_config 
            WHERE status = "disconnected" 
            AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ');
        $desconexionesRecientes = (int)$stmt->fetchColumn();
        
        if ($desconexionesRecientes > 0) {
            $alerts[] = [
                'level' => 'warning',
                'message' => "{$desconexionesRecientes} conexiones WhatsApp perdidas en la última hora",
                'timestamp' => date('c')
            ];
        }
        
        // Verificar errores altos en cron jobs
        $stmt = getPDO()->query('
            SELECT SUM(errores) as total_errores, COUNT(*) as total_jobs
            FROM cron_logs 
            WHERE fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ');
        $cronStats = $stmt->fetch();
        
        if ($cronStats['total_errores'] > 0) {
            $errorRate = ($cronStats['total_errores'] / max($cronStats['total_jobs'], 1)) * 100;
            if ($errorRate > 20) {
                $alerts[] = [
                    'level' => 'critical',
                    'message' => "Alta tasa de errores en cron jobs: {$errorRate}%",
                    'timestamp' => date('c')
                ];
            }
        }
        
        // Verificar mensajes no leídos acumulados
        $stmt = getPDO()->query('
            SELECT SUM(no_leidos) 
            FROM conversaciones 
            WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ');
        $mensajesNoLeidos = (int)$stmt->fetchColumn();
        
        if ($mensajesNoLeidos > 100) {
            $alerts[] = [
                'level' => 'warning',
                'message' => "{$mensajesNoLeidos} mensajes no leídos acumulados",
                'timestamp' => date('c')
            ];
        }
        
    } catch (Exception $e) {
        $alerts[] = [
            'level' => 'error',
            'message' => 'Error verificando alertas: ' . $e->getMessage(),
            'timestamp' => date('c')
        ];
    }
    
    return $alerts;
}
?>

-- Crear tabla para logs de cron jobs
CREATE TABLE IF NOT EXISTS cron_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo VARCHAR(50) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_procesados INT DEFAULT 0,
    exitosos INT DEFAULT 0,
    errores INT DEFAULT 0,
    omitidos INT DEFAULT 0,
    detalles JSON NULL,
    tiempo_ejecucion DECIMAL(8,3) NULL,
    INDEX idx_tipo_fecha (tipo, fecha),
    INDEX idx_fecha (fecha)
);

-- Añadir campos para tracking de WhatsApp en reservas
ALTER TABLE reservas 
ADD COLUMN whatsapp_confirmacion_enviada BOOLEAN DEFAULT FALSE,
ADD COLUMN whatsapp_confirmacion_fecha TIMESTAMP NULL,
ADD COLUMN whatsapp_recordatorio_enviado BOOLEAN DEFAULT FALSE,
ADD COLUMN whatsapp_recordatorio_fecha TIMESTAMP NULL;