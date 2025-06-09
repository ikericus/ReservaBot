<?php
/**
 * Funciones unificadas para WhatsApp
 * Combina funciones principales y auxiliares en un solo archivo simplificado
 */

require_once __DIR__ . '/whatsapp-config.php';
require_once __DIR__ . '/db-config.php';

// ============================================================================
// FUNCIONES DE CONEXIÓN Y ESTADO
// ============================================================================

/**
 * Obtener estado actual de WhatsApp
 */
function getWhatsAppStatus($userId = null) {
    try {
        $pdo = getPDO();
        
        $configKeys = ['whatsapp_server_url', 'whatsapp_status', 'whatsapp_last_activity', 'whatsapp_api_key'];
        $placeholders = str_repeat('?,', count($configKeys) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT clave, valor FROM configuraciones WHERE clave IN ($placeholders)");
        $stmt->execute($configKeys);
        
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[$row['clave']] = $row['valor'];
        }
        
        $serverUrl = $config['whatsapp_server_url'] ?? WhatsAppConfig::SERVER_URL;
        $status = $config['whatsapp_status'] ?? 'disconnected';
        $lastActivity = $config['whatsapp_last_activity'] ?? null;
        $apiKey = $config['whatsapp_api_key'] ?? '';
        
        $result = [
            'status' => $status,
            'server_url' => $serverUrl,
            'api_key' => $apiKey,
            'connected' => ($status === 'connected'),
            'lastActivity' => $lastActivity ? date('d/m/Y H:i:s', intval($lastActivity)) : null
        ];
        
        // Obtener configuración de notificaciones
        $stmt = $pdo->query("SELECT clave, valor FROM configuraciones WHERE clave LIKE 'whatsapp_notify_%'");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $key = str_replace('whatsapp_notify_', '', $row['clave']);
            $settings[$key] = $row['valor'];
        }
        $result['settings'] = $settings;
        
        // Si hay QR disponible
        if ($status === 'qr_ready') {
            $qrPath = __DIR__ . '/../uploads/whatsapp_qr.png';
            if (file_exists($qrPath)) {
                $result['qrCode'] = 'uploads/whatsapp_qr.png?' . filemtime($qrPath);
            }
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log('Error obteniendo estado WhatsApp: ' . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * Conectar WhatsApp
 */
function connectWhatsApp() {
    try {
        updateWhatsAppStatus('connecting');
        
        $serverUrl = getConfigValue('whatsapp_server_url', WhatsAppConfig::SERVER_URL);
        $response = makeHttpRequest($serverUrl . '/start', 'GET');
        
        if (!$response) {
            // Modo desarrollo - simular QR
            updateWhatsAppStatus('qr_ready');
            return ['success' => true, 'message' => 'Modo desarrollo: simulando conexión'];
        }
        
        // Procesar respuesta real
        if (isset($response['qrCode'])) {
            if (saveQRCode($response['qrCode'])) {
                updateWhatsAppStatus('qr_ready');
                return [
                    'success' => true, 
                    'qrCode' => 'uploads/whatsapp_qr.png?' . time()
                ];
            }
        }
        
        return ['success' => true, 'message' => 'Proceso de conexión iniciado'];
        
    } catch (Exception $e) {
        error_log('Error conectando WhatsApp: ' . $e->getMessage());
        updateWhatsAppStatus('error');
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Desconectar WhatsApp
 */
function disconnectWhatsApp() {
    try {
        $serverUrl = getConfigValue('whatsapp_server_url', WhatsAppConfig::SERVER_URL);
        makeHttpRequest($serverUrl . '/stop', 'GET');
        
        updateWhatsAppStatus('disconnected');
        removeQRCode();
        
        return ['success' => true, 'message' => 'WhatsApp desconectado'];
        
    } catch (Exception $e) {
        error_log('Error desconectando WhatsApp: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// ============================================================================
// FUNCIONES DE MENSAJERÍA
// ============================================================================

/**
 * Enviar mensaje de WhatsApp
 */
function sendWhatsAppMessage($phoneNumber, $message, $type = 'manual', $reservationId = null) {
    try {
        $status = getWhatsAppStatus();
        
        if ($status['status'] !== 'connected') {
            // Modo desarrollo - simular envío
            error_log("Simulando envío WhatsApp a $phoneNumber: $message");
            return ['success' => true, 'message' => 'Mensaje simulado (desarrollo)'];
        }
        
        $cleanPhone = formatPhoneNumber($phoneNumber);
        $serverUrl = $status['server_url'];
        
        $data = [
            'to' => $cleanPhone,
            'message' => $message,
            'type' => $type
        ];
        
        $response = makeHttpRequest($serverUrl . '/send', 'POST', $data);
        
        if ($response && isset($response['success']) && $response['success']) {
            // Guardar mensaje en base de datos si es necesario
            if ($reservationId) {
                logWhatsAppMessage($phoneNumber, $message, 'sent', $type, $reservationId);
            }
            
            return ['success' => true, 'message' => 'Mensaje enviado'];
        }
        
        throw new Exception($response['message'] ?? 'Error desconocido al enviar mensaje');
        
    } catch (Exception $e) {
        error_log('Error enviando mensaje WhatsApp: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Enviar confirmación de reserva
 */
function sendReservationConfirmation($reservationId) {
    $reservation = getReservationData($reservationId);
    if (!$reservation) {
        return ['success' => false, 'message' => 'Reserva no encontrada'];
    }
    
    $message = processMessageTemplate('confirmation', [
        'cliente' => $reservation['nombre'],
        'fecha' => formatDate($reservation['fecha']),
        'hora' => formatTime($reservation['hora']),
        'negocio' => $reservation['negocio']
    ], $reservation['usuario_id']);
    
    $result = sendWhatsAppMessage($reservation['telefono'], $message, 'confirmation', $reservationId);
    
    if ($result['success']) {
        markReservationMessageSent($reservationId, 'confirmacion');
    }
    
    return $result;
}

/**
 * Enviar recordatorio de reserva
 */
function sendReservationReminder($reservationId) {
    $reservation = getReservationData($reservationId);
    if (!$reservation) {
        return ['success' => false, 'message' => 'Reserva no encontrada'];
    }
    
    $message = processMessageTemplate('reminder', [
        'cliente' => $reservation['nombre'],
        'fecha' => formatDate($reservation['fecha']),
        'hora' => formatTime($reservation['hora']),
        'negocio' => $reservation['negocio']
    ], $reservation['usuario_id']);
    
    $result = sendWhatsAppMessage($reservation['telefono'], $message, 'reminder', $reservationId);
    
    if ($result['success']) {
        markReservationMessageSent($reservationId, 'recordatorio');
    }
    
    return $result;
}

/**
 * Procesar mensaje entrante
 */
function processIncomingMessage($messageData) {
    try {
        $chatId = $messageData['chatId'] ?? '';
        $message = $messageData['body'] ?? '';
        $senderName = $messageData['senderName'] ?? '';
        
        if (empty($chatId) || empty($message)) {
            return ['success' => false, 'message' => 'Datos incompletos'];
        }
        
        $phoneNumber = extractPhoneFromChatId($chatId);
        
        // Buscar respuesta automática
        $autoResponse = findAutoResponse($message);
        
        if ($autoResponse) {
            sendWhatsAppMessage($phoneNumber, $autoResponse, 'auto');
            return [
                'success' => true, 
                'message' => 'Respuesta automática enviada',
                'auto_response' => true
            ];
        }
        
        return ['success' => true, 'message' => 'Mensaje procesado'];
        
    } catch (Exception $e) {
        error_log('Error procesando mensaje entrante: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// ============================================================================
// FUNCIONES DE CONFIGURACIÓN
// ============================================================================

/**
 * Actualizar configuración de notificaciones
 */
function updateNotificationSetting($setting, $enabled) {
    try {
        if (!WhatsAppConfig::isValidNotificationSetting($setting)) {
            return ['success' => false, 'message' => 'Configuración no válida'];
        }
        
        $pdo = getPDO();
        $key = 'whatsapp_notify_' . $setting;
        $value = $enabled ? '1' : '0';
        
        $stmt = $pdo->prepare("
            INSERT INTO configuraciones (clave, valor) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE valor = ?
        ");
        $stmt->execute([$key, $value, $value]);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log('Error actualizando configuración: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error al guardar configuración'];
    }
}

/**
 * Manejar actualización de QR
 */
function handleQRUpdate($qrData) {
    try {
        if (empty($qrData)) {
            return ['success' => false, 'message' => 'QR no proporcionado'];
        }
        
        if (saveQRCode($qrData)) {
            updateWhatsAppStatus('qr_ready');
            return ['success' => true, 'message' => 'QR actualizado'];
        }
        
        return ['success' => false, 'message' => 'Error guardando QR'];
        
    } catch (Exception $e) {
        error_log('Error actualizando QR: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// ============================================================================
// FUNCIONES AUXILIARES
// ============================================================================

/**
 * Realizar petición HTTP con reintentos
 */
function makeHttpRequest($url, $method = 'GET', $data = null) {
    $lastError = null;
    
    for ($attempt = 1; $attempt <= WhatsAppConfig::MAX_RETRY_ATTEMPTS; $attempt++) {
        try {
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => WhatsAppConfig::REQUEST_TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => WhatsAppConfig::CONNECT_TIMEOUT,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => WhatsAppConfig::MAX_REDIRECTS,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'WhatsApp-Client/1.0',
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json']
            ]);
            
            if ($method === 'POST' && $data) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception("cURL Error: {$error}");
            }
            
            if ($httpCode >= 500) {
                throw new Exception("Server Error: HTTP {$httpCode}");
            }
            
            if ($httpCode >= 400) {
                throw new Exception("Client Error: HTTP {$httpCode}");
            }
            
            return json_decode($response, true);
            
        } catch (Exception $e) {
            $lastError = $e;
            
            if ($attempt < WhatsAppConfig::MAX_RETRY_ATTEMPTS && isRetryableError($e)) {
                sleep(WhatsAppConfig::RETRY_DELAY * $attempt);
                continue;
            }
            
            break;
        }
    }
    
    throw new Exception("Request failed after " . WhatsAppConfig::MAX_RETRY_ATTEMPTS . " attempts: " . $lastError->getMessage());
}

/**
 * Verificar si un error es recuperable
 */
function isRetryableError($exception) {
    $message = $exception->getMessage();
    $retryablePatterns = ['Connection timed out', 'Connection refused', 'Server Error: HTTP 5', 'cURL Error'];
    
    foreach ($retryablePatterns as $pattern) {
        if (strpos($message, $pattern) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Formatear número de teléfono
 */
function formatPhoneNumber($phone) {
    $clean = preg_replace('/[^0-9+]/', '', $phone);
    
    // Si empieza con +, mantenerlo
    if (strpos($clean, '+') === 0) {
        return ltrim($clean, '+');
    }
    
    // Si es español sin código de país
    if (strlen($clean) === 9 && ($clean[0] === '6' || $clean[0] === '7')) {
        return '34' . $clean;
    }
    
    // Si no empieza con +
    if (strlen($clean) > 9) {
        return $clean;
    }
    
    return $clean;
}

/**
 * Extraer teléfono de chatId
 */
function extractPhoneFromChatId($chatId) {
    $number = str_replace('@c.us', '', $chatId);
    return '+' . $number;
}

/**
 * Procesar plantilla de mensaje
 */
function processMessageTemplate($type, $variables = [], $userId = null) {
    try {
        $template = WhatsAppConfig::getDefaultTemplate($type);
        
        // Intentar obtener plantilla personalizada del usuario
        if ($userId) {
            $customTemplate = getConfigValue("whatsapp_{$type}_message", null, $userId);
            if ($customTemplate) {
                $template = $customTemplate;
            }
        }
        
        // Reemplazar variables
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        // Limpiar variables no reemplazadas
        $template = preg_replace('/\{[^}]+\}/', '', $template);
        
        return trim($template);
        
    } catch (Exception $e) {
        error_log("Error procesando plantilla: " . $e->getMessage());
        return "Mensaje automático";
    }
}

/**
 * Buscar respuesta automática
 */
function findAutoResponse($message) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT * FROM autorespuestas_whatsapp WHERE is_active = 1 ORDER BY created_at");
        
        while ($response = $stmt->fetch()) {
            $keyword = $response['keyword'];
            $isRegex = $response['is_regex'] == 1;
            
            if ($isRegex) {
                if (@preg_match('/' . $keyword . '/i', $message)) {
                    return $response['response'];
                }
            } else {
                if (stripos($message, $keyword) !== false) {
                    return $response['response'];
                }
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Error buscando respuesta automática: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtener datos de reserva
 */
function getReservationData($reservationId) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('
            SELECT r.*, u.id as usuario_id, u.negocio
            FROM reservas r 
            JOIN usuarios u ON r.usuario_id = u.id 
            WHERE r.id = ?
        ');
        $stmt->execute([$reservationId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error obteniendo datos de reserva: " . $e->getMessage());
        return null;
    }
}

/**
 * Marcar mensaje de reserva como enviado
 */
function markReservationMessageSent($reservationId, $type) {
    try {
        $pdo = getPDO();
        $field = "whatsapp_{$type}_enviada";
        $dateField = "whatsapp_{$type}_fecha";
        
        $stmt = $pdo->prepare("
            UPDATE reservas 
            SET $field = 1, $dateField = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$reservationId]);
        return true;
    } catch (Exception $e) {
        error_log("Error marcando mensaje como enviado: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualizar estado de WhatsApp
 */
function updateWhatsAppStatus($status) {
    try {
        if (!WhatsAppConfig::isValidStatus($status)) {
            throw new Exception("Estado no válido: $status");
        }
        
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_status', ?) 
            ON DUPLICATE KEY UPDATE valor = ?
        ");
        $stmt->execute([$status, $status]);
        
        // Actualizar timestamp de actividad
        $stmt = $pdo->prepare("
            INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_last_activity', ?) 
            ON DUPLICATE KEY UPDATE valor = ?
        ");
        $timestamp = time();
        $stmt->execute([$timestamp, $timestamp]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error actualizando estado: " . $e->getMessage());
        return false;
    }
}

/**
 * Guardar código QR
 */
function saveQRCode($qrData) {
    try {
        if (preg_match('/^data:image\/png;base64,(.*)$/', $qrData, $matches)) {
            $imageData = base64_decode($matches[1]);
            $qrPath = __DIR__ . '/../uploads/whatsapp_qr.png';
            
            $dir = dirname($qrPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            return file_put_contents($qrPath, $imageData) !== false;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error guardando QR: " . $e->getMessage());
        return false;
    }
}

/**
 * Eliminar código QR
 */
function removeQRCode() {
    $qrPath = __DIR__ . '/../uploads/whatsapp_qr.png';
    if (file_exists($qrPath)) {
        unlink($qrPath);
    }
}

/**
 * Obtener valor de configuración
 */
function getConfigValue($key, $default = null, $userId = null) {
    try {
        $pdo = getPDO();
        $table = $userId ? 'configuraciones_usuario' : 'configuraciones';
        $whereClause = $userId ? 'usuario_id = ? AND clave = ?' : 'clave = ?';
        $params = $userId ? [$userId, $key] : [$key];
        
        $stmt = $pdo->prepare("SELECT valor FROM $table WHERE $whereClause");
        $stmt->execute($params);
        
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Registrar mensaje en log
 */
function logWhatsAppMessage($phone, $message, $direction, $type = 'manual', $reservationId = null) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            INSERT INTO whatsapp_messages 
            (phone, message, direction, type, reservation_id, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$phone, $message, $direction, $type, $reservationId]);
        return true;
    } catch (Exception $e) {
        error_log("Error registrando mensaje: " . $e->getMessage());
        return false;
    }
}

/**
 * Formatear fecha para mostrar
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Formatear hora para mostrar
 */
function formatTime($time) {
    return date('H:i', strtotime($time));
}

/**
 * Generar JWT para autenticación
 */
function generateJWT($userId, $expiresIn = 3600) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'userId' => (int)$userId,
        'iat' => time(),
        'exp' => time() + $expiresIn
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, WhatsAppConfig::JWT_SECRET, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

/**
 * Obtener headers para peticiones autenticadas
 */
function getAuthHeaders($userId) {
    $token = generateJWT($userId);
    return [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: WhatsApp-Client/1.0'
    ];
}

/**
 * Verificar si WhatsApp está conectado para un usuario
 */
function isWhatsAppConnected($userId = null) {
    $status = getWhatsAppStatus($userId);
    return $status['connected'];
}

/**
 * Obtener estadísticas de WhatsApp
 */
function getWhatsAppStats($userId, $period = 'today') {
    try {
        $pdo = getPDO();
        
        switch ($period) {
            case 'week':
                $startDate = date('Y-m-d', strtotime('monday this week'));
                break;
            case 'month':
                $startDate = date('Y-m-01');
                break;
            default:
                $startDate = date('Y-m-d');
        }
        
        $endDate = date('Y-m-d 23:59:59');
        
        // Mensajes enviados
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM whatsapp_messages 
            WHERE direction = 'sent' AND created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $sent = $stmt->fetchColumn();
        
        // Mensajes recibidos
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM whatsapp_messages 
            WHERE direction = 'received' AND created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $received = $stmt->fetchColumn();
        
        // Conversaciones activas
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT phone) FROM whatsapp_messages 
            WHERE created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $activeChats = $stmt->fetchColumn();
        
        return [
            'sent' => (int)$sent,
            'received' => (int)$received,
            'active_chats' => (int)$activeChats,
            'period' => $period
        ];
        
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas: " . $e->getMessage());
        return [
            'sent' => 0,
            'received' => 0,
            'active_chats' => 0,
            'period' => $period
        ];
    }
}

/**
 * Limpiar datos antiguos (para mantenimiento)
 */
function cleanupOldWhatsAppData() {
    try {
        $pdo = getPDO();
        $cleaned = 0;
        
        // Limpiar mensajes antiguos (más de 1 año)
        $stmt = $pdo->prepare("
            DELETE FROM whatsapp_messages 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ");
        $stmt->execute();
        $cleaned += $stmt->rowCount();
        
        // Limpiar QR codes antiguos
        $qrPath = __DIR__ . '/../uploads/whatsapp_qr.png';
        if (file_exists($qrPath) && filemtime($qrPath) < (time() - 3600)) {
            unlink($qrPath);
            $cleaned++;
        }
        
        return $cleaned;
        
    } catch (Exception $e) {
        error_log("Error en limpieza: " . $e->getMessage());
        return 0;
    }
}

/**
 * Verificar salud del servidor WhatsApp
 */
function checkWhatsAppServerHealth() {
    try {
        $serverUrl = getConfigValue('whatsapp_server_url', WhatsAppConfig::SERVER_URL);
        $response = makeHttpRequest($serverUrl . '/health');
        
        return $response ?: ['status' => 'offline', 'error' => 'No se pudo conectar'];
        
    } catch (Exception $e) {
        return ['status' => 'offline', 'error' => $e->getMessage()];
    }
}

// ============================================================================
// FUNCIONES DE COMPATIBILIDAD (mantener nombres originales)
// ============================================================================

/**
 * Alias para compatibilidad con código existente
 */
function checkWhatsAppStatus($serverUrl = null, $apiKey = null) {
    return getWhatsAppStatus();
}

function getWhatsAppConnectionStatus() {
    return getWhatsAppStatus();
}

function updateWhatsAppNotificationSetting($setting, $enabled) {
    return updateNotificationSetting($setting, $enabled);
}

function processIncomingWhatsAppMessage($messageData) {
    return processIncomingMessage($messageData);
}

function sendWhatsAppMessage($to, $message, $isAuto = false) {
    $type = $isAuto ? 'auto' : 'manual';
    return sendWhatsAppMessage($to, $message, $type);
}

function formatWhatsappNumber($number) {
    return formatPhoneNumber($number);
}

function extractPhoneNumber($chatId) {
    return extractPhoneFromChatId($chatId);
}

function formatWhatsappId($chatId) {
    return extractPhoneFromChatId($chatId);
}

/**
 * Procesar evento de webhook (usada en webhook.php)
 */
function processWebhookEvent($event, $userId, $eventData, $timestamp) {
    try {
        switch ($event) {
            case 'qr_generated':
                return handleQRUpdate($eventData['qr'] ?? '');
                
            case 'connected':
                updateWhatsAppStatus('connected');
                return ['success' => true, 'message' => 'Conexión exitosa'];
                
            case 'disconnected':
                updateWhatsAppStatus('disconnected');
                removeQRCode();
                return ['success' => true, 'message' => 'Desconexión procesada'];
                
            case 'auth_failure':
                updateWhatsAppStatus('auth_failed');
                removeQRCode();
                return ['success' => true, 'message' => 'Fallo de autenticación procesado'];
                
            case 'message_received':
                return processIncomingMessage($eventData);
                
            case 'message_sent':
                $phoneNumber = extractPhoneFromChatId($eventData['to'] ?? '');
                logWhatsAppMessage($phoneNumber, $eventData['body'] ?? '', 'sent', 'outgoing');
                return ['success' => true, 'message' => 'Mensaje enviado procesado'];
                
            case 'status_change':
                $newStatus = $eventData['status'] ?? '';
                if (WhatsAppConfig::isValidStatus($newStatus)) {
                    updateWhatsAppStatus($newStatus);
                    return ['success' => true, 'message' => 'Estado actualizado'];
                }
                return ['success' => false, 'message' => 'Estado inválido'];
                
            default:
                error_log("Evento webhook desconocido: {$event}");
                return ['success' => false, 'message' => "Evento desconocido: {$event}"];
        }
        
    } catch (Exception $e) {
        error_log("Error en processWebhookEvent: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

?>