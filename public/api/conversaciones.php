<?php
// public/api/conversaciones.php
// API para obtener lista de conversaciones del usuario

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

$user = getAuthenticatedUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

// Parámetros opcionales
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    // Construir consulta con búsqueda opcional
    $whereClause = 'WHERE c.usuario_id = ?';
    $params = [$userId];
    
    if (!empty($search)) {
        $whereClause .= ' AND (c.cliente_nombre LIKE ? OR c.cliente_phone LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Contar total para paginación
    $countQuery = "SELECT COUNT(*) FROM conversaciones c $whereClause";
    $stmt = getPDO()->prepare($countQuery);
    $stmt->execute($params);
    $totalConversaciones = $stmt->fetchColumn();
    
    // Obtener conversaciones con información de último mensaje
    $query = "
        SELECT 
            c.id,
            c.cliente_phone,
            c.cliente_nombre,
            c.ultimo_mensaje,
            c.no_leidos,
            c.created_at,
            c.updated_at,
            (SELECT COUNT(*) FROM mensajes m WHERE m.conversacion_id = c.id) as total_mensajes,
            (SELECT m.timestamp FROM mensajes m WHERE m.conversacion_id = c.id ORDER BY m.timestamp DESC LIMIT 1) as ultimo_mensaje_timestamp,
            (SELECT m.tipo FROM mensajes m WHERE m.conversacion_id = c.id ORDER BY m.timestamp DESC LIMIT 1) as ultimo_mensaje_tipo
        FROM conversaciones c 
        $whereClause
        ORDER BY c.updated_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $fullParams = array_merge($params, [$limit, $offset]);
    $stmt = getPDO()->prepare($query);
    $stmt->execute($fullParams);
    $conversaciones = $stmt->fetchAll();
    
    // Formatear resultados
    $formattedConversaciones = array_map(function($conv) {
        return [
            'id' => (int)$conv['id'],
            'cliente_phone' => $conv['cliente_phone'],
            'cliente_nombre' => $conv['cliente_nombre'] ?: $conv['cliente_phone'],
            'ultimo_mensaje' => $conv['ultimo_mensaje'],
            'no_leidos' => (int)$conv['no_leidos'],
            'total_mensajes' => (int)$conv['total_mensajes'],
            'ultimo_mensaje_timestamp' => $conv['ultimo_mensaje_timestamp'],
            'ultimo_mensaje_tipo' => $conv['ultimo_mensaje_tipo'],
            'created_at' => $conv['created_at'],
            'updated_at' => $conv['updated_at'],
            'tiempo_relativo' => getTimeAgo($conv['updated_at'])
        ];
    }, $conversaciones);
    
    echo json_encode([
        'success' => true,
        'conversaciones' => $formattedConversaciones,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$totalConversaciones,
            'totalPages' => ceil($totalConversaciones / $limit),
            'hasNext' => $page < ceil($totalConversaciones / $limit),
            'hasPrev' => $page > 1
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error obteniendo conversaciones: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}

function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'hace ' . $time . ' segundos';
    if ($time < 3600) return 'hace ' . floor($time/60) . ' minutos';
    if ($time < 86400) return 'hace ' . floor($time/3600) . ' horas';
    if ($time < 2592000) return 'hace ' . floor($time/86400) . ' días';
    
    return date('d/m/Y', strtotime($datetime));
}
?>

<?php
// public/api/mensajes.php
// API para obtener mensajes de una conversación específica

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

$user = getAuthenticatedUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$conversacionId = $_GET['conversacion_id'] ?? null;
if (!$conversacionId) {
    echo json_encode(['success' => false, 'error' => 'ID de conversación requerido']);
    exit;
}

$userId = $user['id'];
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 50;
$offset = ($page - 1) * $limit;

try {
    // Verificar que la conversación pertenece al usuario
    $stmt = getPDO()->prepare('
        SELECT id, cliente_phone, cliente_nombre 
        FROM conversaciones 
        WHERE id = ? AND usuario_id = ?
    ');
    $stmt->execute([$conversacionId, $userId]);
    $conversacion = $stmt->fetch();
    
    if (!$conversacion) {
        echo json_encode(['success' => false, 'error' => 'Conversación no encontrada']);
        exit;
    }
    
    // Contar total de mensajes
    $stmt = getPDO()->prepare('SELECT COUNT(*) FROM mensajes WHERE conversacion_id = ?');
    $stmt->execute([$conversacionId]);
    $totalMensajes = $stmt->fetchColumn();
    
    // Obtener mensajes (más recientes primero, luego invertir para mostrar cronológicamente)
    $stmt = getPDO()->prepare('
        SELECT 
            id,
            tipo,
            contenido,
            enviado_por,
            mensaje_id,
            timestamp,
            leido,
            DATE_FORMAT(timestamp, "%Y-%m-%d %H:%i:%s") as formatted_timestamp
        FROM mensajes 
        WHERE conversacion_id = ? 
        ORDER BY timestamp DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->execute([$conversacionId, $limit, $offset]);
    $mensajes = $stmt->fetchAll();
    
    // Invertir orden para mostrar cronológicamente (más antiguos primero)
    $mensajes = array_reverse($mensajes);
    
    // Formatear mensajes
    $formattedMensajes = array_map(function($msg) {
        return [
            'id' => (int)$msg['id'],
            'tipo' => $msg['tipo'],
            'contenido' => $msg['contenido'],
            'enviado_por' => $msg['enviado_por'],
            'mensaje_id' => $msg['mensaje_id'],
            'timestamp' => $msg['timestamp'],
            'formatted_timestamp' => $msg['formatted_timestamp'],
            'leido' => (bool)$msg['leido'],
            'tiempo_relativo' => getTimeAgo($msg['timestamp'])
        ];
    }, $mensajes);
    
    // Marcar mensajes como leídos
    $stmt = getPDO()->prepare('
        UPDATE mensajes 
        SET leido = TRUE 
        WHERE conversacion_id = ? AND tipo = "entrante" AND leido = FALSE
    ');
    $stmt->execute([$conversacionId]);
    
    // Resetear contador de no leídos
    $stmt = getPDO()->prepare('
        UPDATE conversaciones 
        SET no_leidos = 0 
        WHERE id = ?
    ');
    $stmt->execute([$conversacionId]);
    
    echo json_encode([
        'success' => true,
        'conversacion' => [
            'id' => (int)$conversacion['id'],
            'cliente_phone' => $conversacion['cliente_phone'],
            'cliente_nombre' => $conversacion['cliente_nombre'] ?: $conversacion['cliente_phone']
        ],
        'mensajes' => $formattedMensajes,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$totalMensajes,
            'totalPages' => ceil($totalMensajes / $limit),
            'hasNext' => $page < ceil($totalMensajes / $limit),
            'hasPrev' => $page > 1
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error obteniendo mensajes: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}

function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'hace ' . $time . ' segundos';
    if ($time < 3600) return 'hace ' . floor($time/60) . ' minutos';
    if ($time < 86400) return 'hace ' . floor($time/3600) . ' horas';
    if ($time < 2592000) return 'hace ' . floor($time/86400) . ' días';
    
    return date('d/m/Y H:i', strtotime($datetime));
}
?>

<?php
// public/api/whatsapp-contacts.php
// API para obtener contactos de WhatsApp

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

$user = getAuthenticatedUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

try {
    // Verificar que WhatsApp está conectado
    $stmt = getPDO()->prepare('SELECT token FROM whatsapp_config WHERE usuario_id = ? AND status = "connected"');
    $stmt->execute([$userId]);
    $config = $stmt->fetch();
    
    if (!$config || !$config['token']) {
        echo json_encode([
            'success' => false,
            'error' => 'WhatsApp no conectado'
        ]);
        exit;
    }
    
    // Llamar al servidor WhatsApp para obtener contactos
    $response = callWhatsAppServer("GET", "/api/contacts/{$userId}", [], $config['token']);
    
    if ($response && $response['success']) {
        echo json_encode([
            'success' => true,
            'contacts' => $response['contacts'] ?? [],
            'total' => $response['total'] ?? count($response['contacts'] ?? [])
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $response['error'] ?? 'Error obteniendo contactos del servidor WhatsApp'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en whatsapp-contacts: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}

function callWhatsAppServer($method, $endpoint, $data = [], $token = null) {
    $whatsappServerUrl = $_ENV['WHATSAPP_SERVER_URL'] ?? 'http://localhost:3001';
    $url = $whatsappServerUrl . $endpoint;
    
    $headers = [
        'Content-Type: application/json',
        'User-Agent: ReservaBot-WebApp/1.0'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    $options = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => json_encode($data),
            'timeout' => 30,
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) {
        return ['success' => false, 'error' => 'No se pudo conectar con el servidor WhatsApp'];
    }
    
    $response = json_decode($result, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Respuesta inválida del servidor WhatsApp'];
    }
    
    return $response;
}
?>

<?php
// public/api/whatsapp-auto-config.php
// API para configurar respuestas automáticas

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

$user = getAuthenticatedUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener configuración actual
    try {
        $stmt = getPDO()->prepare('
            SELECT clave, valor 
            FROM configuraciones_usuario 
            WHERE usuario_id = ? AND clave LIKE "whatsapp_auto_%"
        ');
        $stmt->execute([$userId]);
        $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Configuraciones por defecto
        $defaultConfigs = [
            'whatsapp_auto_welcome' => 'false',
            'whatsapp_auto_confirmation' => 'false',
            'whatsapp_auto_reminder' => 'false',
            'whatsapp_welcome_message' => '¡Hola! Gracias por contactarnos. ¿En qué podemos ayudarte?',
            'whatsapp_confirmation_message' => 'Tu reserva ha sido confirmada para el {fecha} a las {hora}. ¡Te esperamos!',
            'whatsapp_reminder_message' => 'Recordatorio: Tienes una cita mañana {fecha} a las {hora}. ¡Te esperamos!'
        ];
        
        foreach ($defaultConfigs as $key => $defaultValue) {
            if (!isset($configs[$key])) {
                $configs[$key] = $defaultValue;
            }
        }
        
        echo json_encode([
            'success' => true,
            'config' => $configs
        ]);
        
    } catch (Exception $e) {
        error_log('Error obteniendo configuración automática: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor'
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Actualizar configuración
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Datos no válidos']);
        exit;
    }
    
    try {
        $allowedKeys = [
            'whatsapp_auto_welcome',
            'whatsapp_auto_confirmation', 
            'whatsapp_auto_reminder',
            'whatsapp_welcome_message',
            'whatsapp_confirmation_message',
            'whatsapp_reminder_message'
        ];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                // Validar mensajes
                if (strpos($key, '_message') !== false) {
                    if (empty(trim($value)) || strlen($value) > 1000) {
                        echo json_encode([
                            'success' => false, 
                            'error' => "Mensaje '{$key}' inválido (máximo 1000 caracteres)"
                        ]);
                        exit;
                    }
                }
                
                // Validar booleanos
                if (strpos($key, 'auto_') !== false) {
                    $value = $value === 'true' || $value === true ? 'true' : 'false';
                }
                
                $stmt = getPDO()->prepare('
                    INSERT INTO configuraciones_usuario (usuario_id, clave, valor) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE valor = ?
                ');
                $stmt->execute([$userId, $key, $value, $value]);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Configuración actualizada correctamente'
        ]);
        
    } catch (Exception $e) {
        error_log('Error actualizando configuración automática: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor'
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>

<?php
// public/api/whatsapp-stats.php
// API para obtener estadísticas de WhatsApp

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

$user = getAuthenticatedUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $user['id'];
$periodo = $_GET['periodo'] ?? 'hoy'; // hoy, semana, mes

try {
    $stats = [];
    
    // Definir rangos de fecha según el período
    switch ($periodo) {
        case 'hoy':
            $fechaInicio = date('Y-m-d 00:00:00');
            $fechaFin = date('Y-m-d 23:59:59');
            break;
        case 'semana':
            $fechaInicio = date('Y-m-d 00:00:00', strtotime('monday this week'));
            $fechaFin = date('Y-m-d 23:59:59');
            break;
        case 'mes':
            $fechaInicio = date('Y-m-01 00:00:00');
            $fechaFin = date('Y-m-t 23:59:59');
            break;
        default:
            $fechaInicio = date('Y-m-d 00:00:00');
            $fechaFin = date('Y-m-d 23:59:59');
    }
    
    // Mensajes enviados
    $stmt = getPDO()->prepare('
        SELECT COUNT(*) 
        FROM mensajes m
        JOIN conversaciones c ON m.conversacion_id = c.id
        WHERE c.usuario_id = ? 
        AND m.tipo = "saliente" 
        AND m.timestamp BETWEEN ? AND ?
    ');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $stats['mensajes_enviados'] = (int)$stmt->fetchColumn();
    
    // Mensajes recibidos
    $stmt = getPDO()->prepare('
        SELECT COUNT(*) 
        FROM mensajes m
        JOIN conversaciones c ON m.conversacion_id = c.id
        WHERE c.usuario_id = ? 
        AND m.tipo = "entrante" 
        AND m.timestamp BETWEEN ? AND ?
    ');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $stats['mensajes_recibidos'] = (int)$stmt->fetchColumn();
    
    // Conversaciones activas (con mensajes en el período)
    $stmt = getPDO()->prepare('
        SELECT COUNT(DISTINCT c.id) 
        FROM conversaciones c
        JOIN mensajes m ON m.conversacion_id = c.id
        WHERE c.usuario_id = ? 
        AND m.timestamp BETWEEN ? AND ?
    ');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $stats['conversaciones_activas'] = (int)$stmt->fetchColumn();
    
    // Nuevos contactos (primeros mensajes)
    $stmt = getPDO()->prepare('
        SELECT COUNT(*) 
        FROM conversaciones c
        WHERE c.usuario_id = ? 
        AND c.created_at BETWEEN ? AND ?
    ');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $stats['nuevos_contactos'] = (int)$stmt->fetchColumn();
    
    // Mensajes no leídos
    $stmt = getPDO()->prepare('
        SELECT SUM(no_leidos) 
        FROM conversaciones 
        WHERE usuario_id = ?
    ');
    $stmt->execute([$userId]);
    $stats['mensajes_no_leidos'] = (int)$stmt->fetchColumn();
    
    // Tiempo de respuesta promedio (en minutos)
    $stmt = getPDO()->prepare('
        SELECT AVG(
            TIMESTAMPDIFF(MINUTE, 
                (SELECT timestamp FROM mensajes m1 
                 WHERE m1.conversacion_id = m.conversacion_id 
                 AND m1.tipo = "entrante" 
                 AND m1.timestamp < m.timestamp 
                 ORDER BY m1.timestamp DESC LIMIT 1),
                m.timestamp
            )
        ) as tiempo_respuesta_promedio
        FROM mensajes m
        JOIN conversaciones c ON m.conversacion_id = c.id
        WHERE c.usuario_id = ? 
        AND m.tipo = "saliente" 
        AND m.enviado_por = "usuario"
        AND m.timestamp BETWEEN ? AND ?
    ');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $tiempoRespuesta = $stmt->fetchColumn();
    $stats['tiempo_respuesta_promedio'] = $tiempoRespuesta ? round($tiempoRespuesta, 1) : 0;
    
    echo json_encode([
        'success' => true,
        'periodo' => $periodo,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log('Error obteniendo estadísticas WhatsApp: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>