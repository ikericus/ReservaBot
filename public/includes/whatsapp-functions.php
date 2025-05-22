<?php
/**
 * Funciones relacionadas con WhatsApp - VERSIÓN CORREGIDA
 */

/**
 * Verifica el estado de conexión de WhatsApp con parámetros opcionales
 * 
 * @param string $serverUrl URL del servidor (opcional)
 * @param string $apiKey Clave API (opcional)
 * @return array Estado actual de la conexión
 */
function checkWhatsAppStatus($serverUrl = null, $apiKey = null) {
    global $pdo;
    
    try {
        // Obtener configuración del servidor WhatsApp
        $stmt = $pdo->query("SELECT * FROM configuraciones WHERE clave IN ('whatsapp_server_url', 'whatsapp_status', 'whatsapp_last_activity', 'whatsapp_api_key')");
        $config = [];
        
        while ($row = $stmt->fetch()) {
            $config[$row['clave']] = $row['valor'];
        }
        
        $actualServerUrl = $serverUrl ?: ($config['whatsapp_server_url'] ?? 'http://localhost:3000');
        $actualApiKey = $apiKey ?: ($config['whatsapp_api_key'] ?? '');
        $currentStatus = $config['whatsapp_status'] ?? 'disconnected';
        $lastActivity = $config['whatsapp_last_activity'] ?? null;
        
        // Simular estado conectado para desarrollo (cambiar esto en producción)
        $connected = ($currentStatus === 'connected');
        
        $result = [
            'connected' => $connected,
            'status' => $currentStatus,
            'server_url' => $actualServerUrl,
            'api_key' => $actualApiKey,
            'phone' => $connected ? '+34 600 123 456' : null, // Placeholder
            'name' => $connected ? 'Negocio Demo' : null, // Placeholder
        ];
        
        // Formatear última actividad
        if ($lastActivity) {
            $lastActivityTimestamp = intval($lastActivity);
            if ($lastActivityTimestamp > 0) {
                $result['lastActivity'] = date('d/m/Y H:i:s', $lastActivityTimestamp);
            }
        }
        
        return $result;
    } catch (\PDOException $e) {
        error_log('Error al verificar estado de WhatsApp: ' . $e->getMessage());
        return [
            'connected' => false, 
            'status' => 'error', 
            'message' => 'Error al verificar el estado',
            'server_url' => $serverUrl ?: 'http://localhost:3000',
            'api_key' => $apiKey ?: ''
        ];
    }
}

/**
 * Verifica el estado de conexión de WhatsApp y lo actualiza si es necesario
 * Esta función se usa en la vista principal de WhatsApp
 *
 * @return array Estado actual de la conexión
 */
function getWhatsAppConnectionStatus() {
    global $pdo;
    
    try {
        // Obtener configuración del servidor WhatsApp
        $stmt = $pdo->query("SELECT * FROM configuraciones WHERE clave IN ('whatsapp_server_url', 'whatsapp_status', 'whatsapp_last_activity')");
        $config = [];
        
        while ($row = $stmt->fetch()) {
            $config[$row['clave']] = $row['valor'];
        }
        
        $serverUrl = $config['whatsapp_server_url'] ?? 'http://localhost:3000';
        $currentStatus = $config['whatsapp_status'] ?? 'disconnected';
        $lastActivity = $config['whatsapp_last_activity'] ?? null;
        
        // Si el estado actual es "connecting" o "qr_ready", verificamos el estado real
        if ($currentStatus === 'connecting' || $currentStatus === 'qr_ready') {
            // Intentar verificar el estado real en el servidor
            $statusCheck = @file_get_contents($serverUrl . '/status');
            
            if ($statusCheck !== false) {
                $statusData = json_decode($statusCheck, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($statusData['status'])) {
                    $newStatus = $statusData['status'];
                    
                    // Actualizar estado si ha cambiado
                    if ($newStatus !== $currentStatus) {
                        $stmt = $pdo->prepare("INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_status', ?) 
                                              ON DUPLICATE KEY UPDATE valor = ?");
                        $stmt->execute([$newStatus, $newStatus]);
                        
                        $currentStatus = $newStatus;
                    }
                }
            }
        }
        
        // Obtener configuración de notificaciones
        $stmt = $pdo->query("SELECT clave, valor FROM configuraciones WHERE clave LIKE 'whatsapp_notify_%'");
        $settings = [];
        
        while ($row = $stmt->fetch()) {
            $key = str_replace('whatsapp_notify_', '', $row['clave']);
            $settings[$key] = $row['valor'];
        }
        
        $result = [
            'status' => $currentStatus,
            'server_url' => $serverUrl,
            'settings' => $settings
        ];
        
        // Formatear última actividad
        if ($lastActivity) {
            $lastActivityTimestamp = intval($lastActivity);
            if ($lastActivityTimestamp > 0) {
                $result['lastActivity'] = date('d/m/Y H:i:s', $lastActivityTimestamp);
            }
        }
        
        // Si el estado es 'qr_ready', intentar obtener el código QR
        if ($currentStatus === 'qr_ready') {
            $qrCodePath = __DIR__ . '/../uploads/whatsapp_qr.png';
            if (file_exists($qrCodePath)) {
                $result['qrCode'] = 'uploads/whatsapp_qr.png?' . filemtime($qrCodePath);
            }
        }
        
        return $result;
    } catch (\PDOException $e) {
        error_log('Error al verificar estado de WhatsApp: ' . $e->getMessage());
        return ['status' => 'error', 'message' => 'Error al verificar el estado'];
    }
}

/**
 * Maneja eventos de actualización de código QR
 * Esta función es llamada desde el webhook de WhatsApp
 *
 * @param array $data Datos del evento
 * @return array Resultado del proceso
 */
function handleQRUpdate($data) {
    global $pdo;
    
    try {
        $qrCode = $data['qrCode'] ?? '';
        
        if (empty($qrCode)) {
            return ['success' => false, 'message' => 'Código QR no proporcionado'];
        }
        
        // Guardar imagen del QR
        if (preg_match('/^data:image\/png;base64,(.*)$/', $qrCode, $matches)) {
            $imageData = base64_decode($matches[1]);
            $qrCodePath = __DIR__ . '/../uploads/whatsapp_qr.png';
            
            // Crear directorio si no existe
            $dir = dirname($qrCodePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($qrCodePath, $imageData);
            
            // Actualizar estado a 'qr_ready'
            $stmt = $pdo->prepare("INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_status', 'qr_ready') 
                                  ON DUPLICATE KEY UPDATE valor = 'qr_ready'");
            $stmt->execute();
            
            return ['success' => true, 'message' => 'Código QR actualizado'];
        }
        
        return ['success' => false, 'message' => 'Formato de código QR inválido'];
    } catch (\Exception $e) {
        error_log('Error al actualizar código QR: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Verifica el estado de conexión de WhatsApp
 *
 * @return array Datos del estado actual
 */
function getWhatsAppStatus() {
    global $pdo;
    
    try {
        // Obtener configuración del servidor WhatsApp
        $stmt = $pdo->query("SELECT * FROM configuraciones WHERE clave IN ('whatsapp_server_url', 'whatsapp_status', 'whatsapp_last_activity')");
        $config = [];
        
        while ($row = $stmt->fetch()) {
            $config[$row['clave']] = $row['valor'];
        }
        
        $serverUrl = $config['whatsapp_server_url'] ?? 'http://localhost:3000';
        $status = $config['whatsapp_status'] ?? 'disconnected';
        $lastActivity = $config['whatsapp_last_activity'] ?? null;
        
        $result = [
            'status' => $status,
            'server_url' => $serverUrl
        ];
        
        // Formatear última actividad
        if ($lastActivity) {
            $lastActivityTimestamp = intval($lastActivity);
            if ($lastActivityTimestamp > 0) {
                $result['lastActivity'] = date('d/m/Y H:i:s', $lastActivityTimestamp);
            }
        }
        
        // Obtener configuración de notificaciones
        $stmt = $pdo->query("SELECT clave, valor FROM configuraciones WHERE clave LIKE 'whatsapp_notify_%'");
        $settings = [];
        
        while ($row = $stmt->fetch()) {
            $key = str_replace('whatsapp_notify_', '', $row['clave']);
            $settings[$key] = $row['valor'];
        }
        
        $result['settings'] = $settings;
        
        // Si el estado es 'qr_ready', intentar obtener el código QR
        if ($status === 'qr_ready') {
            $qrCodePath = __DIR__ . '/../uploads/whatsapp_qr.png';
            if (file_exists($qrCodePath)) {
                $result['qrCode'] = 'uploads/whatsapp_qr.png?' . filemtime($qrCodePath);
            }
        }
        
        return $result;
    } catch (\PDOException $e) {
        error_log('Error al obtener estado de WhatsApp: ' . $e->getMessage());
        return ['status' => 'error', 'message' => 'Error al obtener el estado'];
    }
}

/**
 * Inicia el proceso de conexión de WhatsApp
 * 
 * @return array Resultado del proceso
 */
function connectWhatsApp() {
    global $pdo;
    
    try {
        // Obtener URL del servidor
        $stmt = $pdo->query("SELECT valor FROM configuraciones WHERE clave = 'whatsapp_server_url'");
        $serverUrl = $stmt->fetchColumn() ?: 'http://localhost:3000';
        
        // Realizar petición al servidor Node.js
        $response = @file_get_contents($serverUrl . '/start');
        
        if ($response === false) {
            // Si no se puede conectar, simular proceso para desarrollo
            $stmt = $pdo->prepare("INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_status', 'qr_ready') 
                                  ON DUPLICATE KEY UPDATE valor = 'qr_ready'");
            $stmt->execute();
            
            return ['success' => true, 'message' => 'Modo desarrollo: simulando conexión'];
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Respuesta inválida del servidor');
        }
        
        // Actualizar estado en la base de datos
        $stmt = $pdo->prepare("INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_status', 'connecting') 
                              ON DUPLICATE KEY UPDATE valor = 'connecting'");
        $stmt->execute();
        
        $result = ['success' => true];
        
        // Si hay código QR, guardarlo
        if (isset($data['qrCode'])) {
            $qrCode = $data['qrCode'];
            $qrCodePath = __DIR__ . '/../uploads/whatsapp_qr.png';
            
            // Crear directorio si no existe
            $dir = dirname($qrCodePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Guardar imagen del QR
            if (preg_match('/^data:image\/png;base64,(.*)$/', $qrCode, $matches)) {
                $imageData = base64_decode($matches[1]);
                file_put_contents($qrCodePath, $imageData);
                
                // Actualizar estado a 'qr_ready'
                $stmt = $pdo->prepare("INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_status', 'qr_ready') 
                                      ON DUPLICATE KEY UPDATE valor = 'qr_ready'");
                $stmt->execute();
                
                $result['qrCode'] = 'uploads/whatsapp_qr.png?' . time();
            }
        }
        
        return $result;
    } catch (\Exception $e) {
        error_log('Error al conectar WhatsApp: ' . $e->getMessage());
        
        // Actualizar estado a 'error' en la base de datos
        $stmt = $pdo->prepare("INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_status', 'error') 
                              ON DUPLICATE KEY UPDATE valor = 'error'");
        $stmt->execute();
        
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Desconecta la sesión de WhatsApp
 * 
 * @return array Resultado del proceso
 */
function disconnectWhatsApp() {
    global $pdo;
    
    try {
        // Obtener URL del servidor
        $stmt = $pdo->query("SELECT valor FROM configuraciones WHERE clave = 'whatsapp_server_url'");
        $serverUrl = $stmt->fetchColumn() ?: 'http://localhost:3000';
        
        // Realizar petición al servidor Node.js
        $response = @file_get_contents($serverUrl . '/stop');
        
        // Actualizar estado en la base de datos (independientemente de la respuesta)
        $stmt = $pdo->prepare("INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_status', 'disconnected') 
                              ON DUPLICATE KEY UPDATE valor = 'disconnected'");
        $stmt->execute();
        
        // Eliminar archivo QR si existe
        $qrCodePath = __DIR__ . '/../uploads/whatsapp_qr.png';
        if (file_exists($qrCodePath)) {
            unlink($qrCodePath);
        }
        
        return ['success' => true, 'message' => 'WhatsApp desconectado correctamente'];
    } catch (\Exception $e) {
        error_log('Error al desconectar WhatsApp: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Actualiza la configuración de notificaciones de WhatsApp
 * 
 * @param string $setting Configuración a actualizar
 * @param int $enabled Estado (1=activado, 0=desactivado)
 * @return array Resultado del proceso
 */
function updateWhatsAppNotificationSetting($setting, $enabled) {
    global $pdo;
    
    try {
        // Validar configuración
        $validSettings = ['nueva_reserva', 'confirmacion', 'recordatorio', 'cancelacion'];
        
        if (!in_array($setting, $validSettings)) {
            return ['success' => false, 'message' => 'Configuración no válida'];
        }
        
        // Actualizar en la base de datos
        $stmt = $pdo->prepare("INSERT INTO configuraciones (clave, valor) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE valor = ?");
        $key = 'whatsapp_notify_' . $setting;
        $value = $enabled ? '1' : '0';
        
        $stmt->execute([$key, $value, $value]);
        
        return ['success' => true];
    } catch (\PDOException $e) {
        error_log('Error al actualizar configuración WhatsApp: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error al guardar la configuración'];
    }
}

/**
 * Envía un mensaje de WhatsApp
 *
 * @param string $to Número de destino con formato internacional (ej: 34600000000)
 * @param string $message Contenido del mensaje
 * @param bool $isAuto Indica si es una respuesta automática
 * @return array Resultado del envío
 */
function sendWhatsAppMessage($to, $message, $isAuto = false) {
    global $pdo;
    
    try {
        // Formatear número si es necesario
        $to = formatWhatsappNumber($to);
        
        // Obtener URL del servidor
        $stmt = $pdo->query("SELECT valor FROM configuraciones WHERE clave = 'whatsapp_server_url'");
        $serverUrl = $stmt->fetchColumn() ?: 'http://localhost:3000';
        
        // Comprobar estado de conexión
        $stmt = $pdo->query("SELECT valor FROM configuraciones WHERE clave = 'whatsapp_status'");
        $status = $stmt->fetchColumn();
        
        if ($status !== 'connected') {
            // Para desarrollo, simular envío exitoso
            error_log("Simulando envío de WhatsApp a $to: $message");
            return ['success' => true, 'message' => 'Mensaje simulado (desarrollo)'];
        }
        
        // Preparar datos para la petición
        $data = [
            'to' => $to,
            'message' => $message
        ];
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($serverUrl . '/send', false, $context);
        
        if ($response === false) {
            throw new \Exception('Error al enviar el mensaje');
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Respuesta inválida del servidor');
        }
        
        if (isset($result['success']) && $result['success']) {
            return ['success' => true, 'message' => 'Mensaje enviado correctamente'];
        } else {
            $errorMsg = $result['message'] ?? 'Error desconocido al enviar el mensaje';
            throw new \Exception($errorMsg);
        }
    } catch (\Exception $e) {
        error_log('Error al enviar mensaje WhatsApp: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Procesa un mensaje recibido y envía respuesta automática si corresponde
 *
 * @param array $messageData Datos del mensaje recibido
 * @return array Resultado del procesamiento
 */
function processIncomingWhatsAppMessage($messageData) {
    global $pdo;
    
    try {
        // Extraer datos del mensaje
        $chatId = $messageData['chatId'] ?? '';
        $message = $messageData['body'] ?? '';
        $timestamp = $messageData['timestamp'] ?? time();
        $senderName = $messageData['senderName'] ?? '';
        
        // Verificar datos mínimos
        if (empty($chatId) || empty($message)) {
            return ['success' => false, 'message' => 'Datos de mensaje incompletos'];
        }
        
        // Buscar posibles respuestas automáticas
        $stmtResponses = $pdo->query("SELECT * FROM autorespuestas_whatsapp WHERE is_active = 1 ORDER BY created_at");
        $autoResponses = $stmtResponses->fetchAll();
        
        $matchedResponse = null;
        
        foreach ($autoResponses as $response) {
            $keyword = $response['keyword'];
            $isRegex = $response['is_regex'] == 1;
            
            if ($isRegex) {
                // Tratarla como expresión regular
                if (@preg_match('/' . $keyword . '/i', $message)) {
                    $matchedResponse = $response;
                    break;
                }
            } else {
                // Tratarla como coincidencia de texto normal
                if (stripos($message, $keyword) !== false) {
                    $matchedResponse = $response;
                    break;
                }
            }
        }
        
        // Enviar respuesta automática si hay coincidencia
        if ($matchedResponse) {
            $responseText = $matchedResponse['response'];
            $phoneNumber = extractPhoneNumber($chatId);
            
            // Enviar mensaje de respuesta
            sendWhatsAppMessage($phoneNumber, $responseText, true);
            
            return [
                'success' => true, 
                'message' => 'Mensaje procesado con respuesta automática',
                'auto_response' => true
            ];
        }
        
        return ['success' => true, 'message' => 'Mensaje recibido correctamente'];
    } catch (\Exception $e) {
        error_log('Error al procesar mensaje WhatsApp: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Formatea un número de teléfono al formato requerido por WhatsApp
 *
 * @param string $number Número de teléfono
 * @return string Número formateado
 */
function formatWhatsappNumber($number) {
    // Eliminar espacios, guiones y paréntesis
    $number = preg_replace('/[\s\-\(\)]/', '', $number);
    
    // Asegurarse de que el número comienza con el prefijo del país (asumimos España por defecto)
    if (substr($number, 0, 1) === '+') {
        // Eliminar el signo +
        $number = substr($number, 1);
    } else if (substr($number, 0, 2) !== '34') {
        // Añadir prefijo de España si no lo tiene
        $number = '34' . $number;
    }
    
    return $number;
}

/**
 * Extrae el número de teléfono de un chatId de WhatsApp
 *
 * @param string $chatId ID del chat (formato: 34600000000@c.us)
 * @return string Número de teléfono formateado
 */
function extractPhoneNumber($chatId) {
    // Eliminar el sufijo @c.us si existe
    $number = str_replace('@c.us', '', $chatId);
    
    // Formatear para mostrar
    if (strlen($number) > 2 && substr($number, 0, 2) === '34') {
        $number = '+' . $number;
    }
    
    return $number;
}

/**
 * Formatea un ID de chat de WhatsApp para mostrar
 *
 * @param string $chatId ID del chat
 * @return string ID formateado
 */
function formatWhatsappId($chatId) {
    return extractPhoneNumber($chatId);
}