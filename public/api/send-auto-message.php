<?php
// public/api/send-auto-message.php
// API para enviar mensajes automáticos desde el sistema (confirmaciones, recordatorios, etc.)

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validar datos requeridos
$requiredFields = ['usuario_id', 'tipo_mensaje', 'cliente_phone'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'error' => "Campo requerido: {$field}"]);
        exit;
    }
}

$userId = (int)$data['usuario_id'];
$tipoMensaje = $data['tipo_mensaje']; // confirmation, reminder, welcome, etc.
$clientePhone = $data['cliente_phone'];
$reservaData = $data['reserva_data'] ?? [];
$clienteNombre = $data['cliente_nombre'] ?? null;

try {
    // Verificar que el usuario existe y está activo
    $stmt = getPDO()->prepare('SELECT id, nombre, negocio FROM usuarios WHERE id = ? AND activo = 1');
    $stmt->execute([$userId]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'error' => 'Usuario no válido']);
        exit;
    }
    
    // Verificar que WhatsApp está conectado para este usuario
    $stmt = getPDO()->prepare('SELECT token, status FROM whatsapp_config WHERE usuario_id = ?');
    $stmt->execute([$userId]);
    $whatsappConfig = $stmt->fetch();
    
    if (!$whatsappConfig || $whatsappConfig['status'] !== 'connected' || !$whatsappConfig['token']) {
        echo json_encode(['success' => false, 'error' => 'WhatsApp no conectado para este usuario']);
        exit;
    }
    
    // Obtener configuración de mensajes automáticos
    $stmt = getPDO()->prepare('
        SELECT clave, valor 
        FROM configuraciones_usuario 
        WHERE usuario_id = ? AND clave LIKE "whatsapp_%"
    ');
    $stmt->execute([$userId]);
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Verificar si el tipo de mensaje automático está habilitado
    $autoConfigKey = "whatsapp_auto_{$tipoMensaje}";
    if (!isset($configs[$autoConfigKey]) || $configs[$autoConfigKey] !== 'true') {
        echo json_encode([
            'success' => false, 
            'error' => "Mensaje automático '{$tipoMensaje}' no está habilitado",
            'skipped' => true
        ]);
        exit;
    }
    
    // Obtener plantilla del mensaje
    $message = getMessageTemplate($tipoMensaje, $configs, $reservaData, $usuario, $clienteNombre);
    
    if (!$message) {
        echo json_encode(['success' => false, 'error' => 'No se pudo generar el mensaje']);
        exit;
    }
    
    // Enviar mensaje a través del servidor WhatsApp
    $response = callWhatsAppServer("POST", "/api/send-message", [
        'to' => $clientePhone,
        'message' => $message
    ], $whatsappConfig['token']);
    
    if ($response && $response['success']) {
        // Registrar mensaje enviado en BD
        $conversacionId = getOrCreateConversacion($userId, $clientePhone, $clienteNombre);
        
        $stmt = getPDO()->prepare('
            INSERT INTO mensajes (conversacion_id, tipo, contenido, enviado_por, mensaje_id, timestamp) 
            VALUES (?, "saliente", ?, "sistema", ?, NOW())
        ');
        $stmt->execute([
            $conversacionId,
            $message,
            $response['messageId'] ?? null
        ]);
        
        // Actualizar conversación
        $stmt = getPDO()->prepare('
            UPDATE conversaciones 
            SET ultimo_mensaje = ?, updated_at = NOW() 
            WHERE id = ?
        ');
        $stmt->execute([substr($message, 0, 255), $conversacionId]);
        
        // Log del mensaje automático enviado
        error_log("Mensaje automático '{$tipoMensaje}' enviado a {$clientePhone} para usuario {$userId}");
        
        echo json_encode([
            'success' => true,
            'messageId' => $response['messageId'] ?? null,
            'tipo_mensaje' => $tipoMensaje,
            'message' => 'Mensaje automático enviado correctamente'
        ]);
    } else {
        $errorMsg = $response['error'] ?? 'Error desconocido del servidor WhatsApp';
        error_log("Error enviando mensaje automático '{$tipoMensaje}' para usuario {$userId}: {$errorMsg}");
        
        echo json_encode([
            'success' => false,
            'error' => $errorMsg,
            'queued' => $response['queued'] ?? false
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en send-auto-message: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}

/**
 * Generar mensaje basado en plantilla
 */
function getMessageTemplate($tipo, $configs, $reservaData, $usuario, $clienteNombre) {
    $messageKey = "whatsapp_{$tipo}_message";
    $template = $configs[$messageKey] ?? null;
    
    if (!$template) {
        // Plantillas por defecto
        switch ($tipo) {
            case 'confirmation':
                $template = 'Hola {cliente_nombre}, tu reserva ha sido confirmada para el {fecha} a las {hora} en {negocio}. ¡Te esperamos!';
                break;
            case 'reminder':
                $template = 'Hola {cliente_nombre}, te recordamos que tienes una cita mañana {fecha} a las {hora} en {negocio}. ¡Te esperamos!';
                break;
            case 'welcome':
                $template = '¡Hola! Gracias por contactar a {negocio}. ¿En qué podemos ayudarte?';
                break;
            case 'cancellation':
                $template = 'Hola {cliente_nombre}, tu reserva del {fecha} a las {hora} ha sido cancelada. Si necesitas reagendar, contáctanos.';
                break;
            default:
                return null;
        }
    }
    
    // Reemplazar variables
    $variables = [
        '{cliente_nombre}' => $clienteNombre ?: 'Cliente',
        '{negocio}' => $usuario['negocio'] ?: $usuario['nombre'],
        '{fecha}' => isset($reservaData['fecha']) ? date('d/m/Y', strtotime($reservaData['fecha'])) : '',
        '{hora}' => isset($reservaData['hora']) ? substr($reservaData['hora'], 0, 5) : '',
        '{nombre_reserva}' => $reservaData['nombre'] ?? '',
        '{telefono_negocio}' => $configs['telefono_negocio'] ?? '',
        '{direccion_negocio}' => $configs['direccion_negocio'] ?? ''
    ];
    
    return str_replace(array_keys($variables), array_values($variables), $template);
}

/**
 * Función helper para conversaciones
 */
function getOrCreateConversacion($userId, $clientePhone, $clienteNombre = null) {
    $phoneNormalized = preg_replace('/[^\d]/', '', $clientePhone);
    
    $stmt = getPDO()->prepare('
        SELECT id FROM conversaciones 
        WHERE usuario_id = ? AND cliente_phone = ?
    ');
    $stmt->execute([$userId, $phoneNormalized]);
    $conversacion = $stmt->fetch();
    
    if ($conversacion) {
        if ($clienteNombre && !empty(trim($clienteNombre))) {
            $stmt = getPDO()->prepare('
                UPDATE conversaciones 
                SET cliente_nombre = ? 
                WHERE id = ? AND (cliente_nombre IS NULL OR cliente_nombre = "")
            ');
            $stmt->execute([trim($clienteNombre), $conversacion['id']]);
        }
        return $conversacion['id'];
    }
    
    $stmt = getPDO()->prepare('
        INSERT INTO conversaciones (usuario_id, cliente_phone, cliente_nombre) 
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$userId, $phoneNormalized, $clienteNombre ? trim($clienteNombre) : null]);
    
    return getPDO()->lastInsertId();
}

/**
 * Función para llamar al servidor WhatsApp
 */
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
// public/includes/whatsapp-helpers.php
// Funciones helper para integración WhatsApp con reservas

/**
 * Enviar confirmación automática de reserva
 */
function sendReservationConfirmation($reservaId) {
    try {
        // Obtener datos de la reserva
        $stmt = getPDO()->prepare('
            SELECT r.*, u.nombre as usuario_nombre, u.negocio, u.id as usuario_id
            FROM reservas r 
            JOIN usuarios u ON r.usuario_id = u.id 
            WHERE r.id = ?
        ');
        $stmt->execute([$reservaId]);
        $reserva = $stmt->fetch();
        
        if (!$reserva) {
            error_log("Reserva {$reservaId} no encontrada para confirmación WhatsApp");
            return false;
        }
        
        // Verificar si tiene teléfono
        if (empty($reserva['telefono'])) {
            error_log("Reserva {$reservaId} no tiene teléfono para confirmación WhatsApp");
            return false;
        }
        
        // Preparar datos para el mensaje automático
        $messageData = [
            'usuario_id' => $reserva['usuario_id'],
            'tipo_mensaje' => 'confirmation',
            'cliente_phone' => $reserva['telefono'],
            'cliente_nombre' => $reserva['nombre'],
            'reserva_data' => [
                'fecha' => $reserva['fecha'],
                'hora' => $reserva['hora'],
                'nombre' => $reserva['nombre']
            ]
        ];
        
        // Llamar a la API de mensaje automático
        $result = callLocalAPI('POST', '/api/send-auto-message', $messageData);
        
        if ($result && $result['success']) {
            // Marcar que se envió confirmación
            $stmt = getPDO()->prepare('
                UPDATE reservas 
                SET whatsapp_confirmacion_enviada = 1, whatsapp_confirmacion_fecha = NOW() 
                WHERE id = ?
            ');
            $stmt->execute([$reservaId]);
            
            error_log("Confirmación WhatsApp enviada para reserva {$reservaId}");
            return true;
        } else {
            error_log("Error enviando confirmación WhatsApp para reserva {$reservaId}: " . 
                     ($result['error'] ?? 'Error desconocido'));
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error en sendReservationConfirmation: " . $e->getMessage());
        return false;
    }
}

/**
 * Enviar recordatorio automático de reserva
 */
function sendReservationReminder($reservaId) {
    try {
        $stmt = getPDO()->prepare('
            SELECT r.*, u.nombre as usuario_nombre, u.negocio, u.id as usuario_id
            FROM reservas r 
            JOIN usuarios u ON r.usuario_id = u.id 
            WHERE r.id = ? AND r.estado = "confirmada"
        ');
        $stmt->execute([$reservaId]);
        $reserva = $stmt->fetch();
        
        if (!$reserva || empty($reserva['telefono'])) {
            return false;
        }
        
        // Verificar que la reserva es para mañana
        $fechaReserva = strtotime($reserva['fecha']);
        $manana = strtotime('+1 day', strtotime(date('Y-m-d')));
        
        if (date('Y-m-d', $fechaReserva) !== date('Y-m-d', $manana)) {
            return false; // No es para mañana
        }
        
        $messageData = [
            'usuario_id' => $reserva['usuario_id'],
            'tipo_mensaje' => 'reminder',
            'cliente_phone' => $reserva['telefono'],
            'cliente_nombre' => $reserva['nombre'],
            'reserva_data' => [
                'fecha' => $reserva['fecha'],
                'hora' => $reserva['hora'],
                'nombre' => $reserva['nombre']
            ]
        ];
        
        $result = callLocalAPI('POST', '/api/send-auto-message', $messageData);
        
        if ($result && $result['success']) {
            $stmt = getPDO()->prepare('
                UPDATE reservas 
                SET whatsapp_recordatorio_enviado = 1, whatsapp_recordatorio_fecha = NOW() 
                WHERE id = ?
            ');
            $stmt->execute([$reservaId]);
            
            error_log("Recordatorio WhatsApp enviado para reserva {$reservaId}");
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error en sendReservationReminder: " . $e->getMessage());
        return false;
    }
}

/**
 * Procesar respuestas automáticas inteligentes
 */
function processIntelligentAutoResponse($userId, $conversacionId, $messageBody, $clientePhone, $clienteNombre) {
    try {
        $messageBody = strtolower(trim($messageBody));
        
        // Obtener configuración de respuestas automáticas
        $stmt = getPDO()->prepare('
            SELECT clave, valor 
            FROM configuraciones_usuario 
            WHERE usuario_id = ? AND clave LIKE "whatsapp_auto_%"
        ');
        $stmt->execute([$userId]);
        $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Respuestas automáticas inteligentes
        $responses = [];
        
        // Saludo inicial
        if (preg_match('/\b(hola|buenos|buenas|hi|hello|ey|hey)\b/', $messageBody)) {
            $responses[] = [
                'tipo' => 'welcome',
                'prioridad' => 1,
                'condicion' => 'Es el primer mensaje o saludo'
            ];
        }
        
        // Consulta de precios
        if (preg_match('/\b(precio|precios|cuanto|coste|costo|tarifa|tarifas)\b/', $messageBody)) {
            $responses[] = [
                'tipo' => 'pricing',
                'mensaje' => 'Hola! Para consultas de precios y servicios, puedes llamarnos o consultar nuestra web. ¿En qué servicio estás interesado?',
                'prioridad' => 2
            ];
        }
        
        // Consulta de horarios
        if (preg_match('/\b(horario|horarios|hora|horas|abier|cerr|atencion)\b/', $messageBody)) {
            $horarios = getBusinessHours($userId);
            $responses[] = [
                'tipo' => 'hours',
                'mensaje' => "Nuestros horarios son:\n{$horarios}\n¿Te gustaría reservar una cita?",
                'prioridad' => 2
            ];
        }
        
        // Consulta sobre reservas
        if (preg_match('/\b(reserv|cita|turno|appointment|booking)\b/', $messageBody)) {
            $responses[] = [
                'tipo' => 'booking',
                'mensaje' => '¡Perfecto! Para reservar una cita puedes usar nuestro formulario online o decirme qué día y hora prefieres. ¿Cuándo te gustaría venir?',
                'prioridad' => 3
            ];
        }
        
        // Cancelación
        if (preg_match('/\b(cancel|cancelar|anular|cambiar|reprogramar)\b/', $messageBody)) {
            $responses[] = [
                'tipo' => 'cancellation',
                'mensaje' => 'Para cancelar o cambiar tu cita, por favor proporciona tu nombre y la fecha de la reserva. Te ayudo enseguida.',
                'prioridad' => 3
            ];
        }
        
        // Respuesta por defecto si no hay coincidencias específicas
        if (empty($responses)) {
            $responses[] = [
                'tipo' => 'default',
                'mensaje' => 'Gracias por tu mensaje. Te responderemos lo antes posible. Si es urgente, puedes llamarnos directamente.',
                'prioridad' => 0
            ];
        }
        
        // Seleccionar respuesta de mayor prioridad
        usort($responses, function($a, $b) {
            return $b['prioridad'] <=> $a['prioridad'];
        });
        
        $selectedResponse = $responses[0];
        
        // Enviar respuesta automática si está habilitada
        $autoResponseKey = 'whatsapp_auto_' . $selectedResponse['tipo'];
        if (isset($configs[$autoResponseKey]) && $configs[$autoResponseKey] === 'true') {
            $mensaje = $selectedResponse['mensaje'] ?? 
                      $configs["whatsapp_{$selectedResponse['tipo']}_message"] ?? 
                      'Gracias por contactarnos. Te responderemos pronto.';
            
            return sendQuickAutoResponse($userId, $clientePhone, $clienteNombre, $mensaje);
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error en processIntelligentAutoResponse: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener horarios del negocio formateados
 */
function getBusinessHours($userId) {
    try {
        $stmt = getPDO()->prepare('
            SELECT clave, valor 
            FROM configuraciones_usuario 
            WHERE usuario_id = ? AND clave LIKE "horario_%"
        ');
        $stmt->execute([$userId]);
        $horarios = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $dias = [
            'lun' => 'Lunes',
            'mar' => 'Martes', 
            'mie' => 'Miércoles',
            'jue' => 'Jueves',
            'vie' => 'Viernes',
            'sab' => 'Sábado',
            'dom' => 'Domingo'
        ];
        
        $horariosTexto = [];
        
        foreach ($dias as $dia => $nombre) {
            $config = $horarios["horario_{$dia}"] ?? 'false|[]';
            $parts = explode('|', $config, 2);
            $activo = $parts[0] === 'true';
            
            if ($activo && isset($parts[1])) {
                $ventanas = json_decode($parts[1], true);
                if ($ventanas && is_array($ventanas) && count($ventanas) > 0) {
                    $horario = $ventanas[0]['inicio'] . ' - ' . $ventanas[0]['fin'];
                    $horariosTexto[] = "{$nombre}: {$horario}";
                } else {
                    $horariosTexto[] = "{$nombre}: Cerrado";
                }
            } else {
                $horariosTexto[] = "{$nombre}: Cerrado";
            }
        }
        
        return implode("\n", $horariosTexto);
        
    } catch (Exception $e) {
        return "Consulta nuestros horarios llamando o visitando nuestra web.";
    }
}

/**
 * Enviar respuesta automática rápida
 */
function sendQuickAutoResponse($userId, $clientePhone, $clienteNombre, $mensaje) {
    $messageData = [
        'usuario_id' => $userId,
        'tipo_mensaje' => 'auto_response',
        'cliente_phone' => $clientePhone,
        'cliente_nombre' => $clienteNombre,
        'mensaje_personalizado' => $mensaje
    ];
    
    return callLocalAPI('POST', '/api/send-auto-message', $messageData);
}

/**
 * Llamar API local
 */
function callLocalAPI($method, $endpoint, $data = []) {
    $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
    $url = $baseUrl . $endpoint;
    
    $options = [
        'http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data),
            'timeout' => 30,
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) {
        return ['success' => false, 'error' => 'Error llamando API local'];
    }
    
    return json_decode($result, true);
}
?>