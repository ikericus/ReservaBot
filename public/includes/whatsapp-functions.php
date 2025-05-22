<?php
/**
 * Funciones relacionadas con WhatsApp
 */


 /**
 * Verifica el estado de conexión de WhatsApp y lo actualiza si es necesario
 * Esta función se usa en la vista principal de WhatsApp
 *
 * @return array Estado actual de la conexión
 */
function checkWhatsAppStatus() {
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
        $response = file_get_contents($serverUrl . '/start');
        
        if ($response === false) {
            throw new \Exception('No se pudo conectar al servidor WhatsApp');
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
        $response = file_get_contents($serverUrl . '/stop');
        
        if ($response === false) {
            throw new \Exception('No se pudo conectar al servidor WhatsApp');
        }
        
        // Actualizar estado en la base de datos
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
        $validSettings = ['reservas', 'confirmaciones', 'recordatorios', 'cancelaciones'];
        
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
            return ['success' => false, 'message' => 'WhatsApp no está conectado'];
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
        $response = file_get_contents($serverUrl . '/send', false, $context);
        
        if ($response === false) {
            throw new \Exception('Error al enviar el mensaje');
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Respuesta inválida del servidor');
        }
        
        if (isset($result['success']) && $result['success']) {
            // Registrar mensaje en la base de datos
            $chatId = $to . '@c.us';
            $timestamp = time();
            
            // Buscar o crear el chat
            $stmtChat = $pdo->prepare("SELECT chat_id FROM chats_whatsapp WHERE chat_id = ?");
            $stmtChat->execute([$chatId]);
            
            if (!$stmtChat->fetch()) {
                $stmtInsertChat = $pdo->prepare("INSERT INTO chats_whatsapp (chat_id, nombre, created_at) VALUES (?, ?, ?)");
                $stmtInsertChat->execute([$chatId, extractPhoneNumber($to), $timestamp]);
            }
            
            // Registrar el mensaje
            $stmtMsg = $pdo->prepare("INSERT INTO mensajes_whatsapp (chat_id, body, direction, timestamp, is_auto_response) VALUES (?, ?, ?, ?, ?)");
            $stmtMsg->execute([$chatId, $message, 'sent', $timestamp, $isAuto ? 1 : 0]);
            
            // Actualizar última actividad
            $stmtActivity = $pdo->prepare("INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_last_activity', ?) 
                                          ON DUPLICATE KEY UPDATE valor = ?");
            $stmtActivity->execute([$timestamp, $timestamp]);
            
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
 * Envía una notificación de WhatsApp para una reserva
 *
 * @param int $reservaId ID de la reserva
 * @param string $tipo Tipo de notificación (nueva_reserva, confirmacion, recordatorio, cancelacion)
 * @return array Resultado del envío
 */
function sendWhatsAppNotification($reservaId, $tipo) {
    global $pdo;
    
    try {
        // Comprobar si las notificaciones están habilitadas para este tipo
        $settingKey = '';
        $templateKey = '';
        
        switch ($tipo) {
            case 'nueva_reserva':
                $settingKey = 'whatsapp_notify_reservas';
                $templateKey = 'whatsapp_mensaje_nueva_reserva';
                break;
                
            case 'confirmacion':
                $settingKey = 'whatsapp_notify_confirmaciones';
                $templateKey = 'whatsapp_mensaje_confirmacion';
                break;
                
            case 'recordatorio':
                $settingKey = 'whatsapp_notify_recordatorios';
                $templateKey = 'whatsapp_mensaje_recordatorio';
                break;
                
            case 'cancelacion':
                $settingKey = 'whatsapp_notify_cancelaciones';
                $templateKey = 'whatsapp_mensaje_cancelacion';
                break;
                
            default:
                return ['success' => false, 'message' => 'Tipo de notificación no válido'];
        }
        
        // Comprobar si está habilitada
        $stmt = $pdo->prepare("SELECT valor FROM configuraciones WHERE clave = ?");
        $stmt->execute([$settingKey]);
        $isEnabled = $stmt->fetchColumn();
        
        if ($isEnabled !== '1') {
            return ['success' => false, 'message' => 'Este tipo de notificación está desactivada'];
        }
        
        // Obtener plantilla del mensaje
        $stmt->execute([$templateKey]);
        $messageTemplate = $stmt->fetchColumn();
        
        if (!$messageTemplate) {
            // Usar plantilla predeterminada si no hay una configurada
            switch ($tipo) {
                case 'nueva_reserva':
                    $messageTemplate = 'Has realizado una nueva reserva para el {fecha} a las {hora}. Te confirmaremos pronto.';
                    break;
                    
                case 'confirmacion':
                    $messageTemplate = 'Tu reserva para el {fecha} a las {hora} ha sido confirmada. ¡Te esperamos!';
                    break;
                    
                case 'recordatorio':
                    $messageTemplate = 'Recordatorio: Tienes una cita mañana {fecha} a las {hora}. ¡Te esperamos!';
                    break;
                    
                case 'cancelacion':
                    $messageTemplate = 'Tu reserva para el {fecha} a las {hora} ha sido cancelada.';
                    break;
            }
        }
        
        // Obtener datos de la reserva
        $stmt = $pdo->prepare("SELECT r.*, c.nombre as cliente_nombre, c.telefono, c.whatsapp_id 
                               FROM reservas r 
                               LEFT JOIN clientes c ON r.cliente_id = c.id 
                               WHERE r.id = ?");
        $stmt->execute([$reservaId]);
        $reserva = $stmt->fetch();
        
        if (!$reserva) {
            return ['success' => false, 'message' => 'Reserva no encontrada'];
        }
        
        // Comprobar si hay número de WhatsApp
        $whatsappId = $reserva['whatsapp_id'];
        if (empty($whatsappId)) {
            return ['success' => false, 'message' => 'El cliente no tiene número de WhatsApp'];
        }
        
        // Formatear fecha y hora
        $fecha = date('d/m/Y', strtotime($reserva['fecha']));
        $hora = date('H:i', strtotime($reserva['hora']));
        
        // Reemplazar variables en la plantilla
        $message = str_replace(
            ['{nombre}', '{fecha}', '{hora}'],
            [$reserva['cliente_nombre'], $fecha, $hora],
            $messageTemplate
        );
        
        // Enviar mensaje
        return sendWhatsAppMessage($whatsappId, $message, true);
    } catch (\Exception $e) {
        error_log('Error al enviar notificación WhatsApp: ' . $e->getMessage());
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
        
        // Normalizar formato de chatId
        if (strpos($chatId, '@') === false) {
            $chatId .= '@c.us';
        }
        
        // Registrar chat si no existe
        $stmtChat = $pdo->prepare("SELECT chat_id FROM chats_whatsapp WHERE chat_id = ?");
        $stmtChat->execute([$chatId]);
        
        if (!$stmtChat->fetch()) {
            $chatName = !empty($senderName) ? $senderName : extractPhoneNumber($chatId);
            $stmtInsertChat = $pdo->prepare("INSERT INTO chats_whatsapp (chat_id, nombre, created_at) VALUES (?, ?, ?)");
            $stmtInsertChat->execute([$chatId, $chatName, $timestamp]);
        } else if (!empty($senderName)) {
            // Actualizar nombre si se recibe uno nuevo
            $stmtUpdateChat = $pdo->prepare("UPDATE chats_whatsapp SET nombre = ? WHERE chat_id = ?");
            $stmtUpdateChat->execute([$senderName, $chatId]);
        }
        
        // Registrar mensaje recibido
        $stmtMsg = $pdo->prepare("INSERT INTO mensajes_whatsapp (chat_id, body, direction, timestamp) VALUES (?, ?, ?, ?)");
        $stmtMsg->execute([$chatId, $message, 'received', $timestamp]);
        
        // Actualizar última actividad
        $stmtActivity = $pdo->prepare("INSERT INTO configuraciones (clave, valor) VALUES ('whatsapp_last_activity', ?) 
                                      ON DUPLICATE KEY UPDATE valor = ?");
        $stmtActivity->execute([$timestamp, $timestamp]);
        
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