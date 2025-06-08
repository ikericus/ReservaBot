<?php
// includes/whatsapp-helpers.php
// Funciones auxiliares para WhatsApp

/**
 * Envía un recordatorio de reserva por WhatsApp
 */
function sendReservationReminder($reservaId) {
    try {
        $pdo = getPDO();
        
        // Obtener datos de la reserva
        $stmt = $pdo->prepare('
            SELECT r.*, u.id as usuario_id, u.negocio
            FROM reservas r 
            JOIN usuarios u ON r.usuario_id = u.id 
            WHERE r.id = ?
        ');
        $stmt->execute([$reservaId]);
        $reserva = $stmt->fetch();
        
        if (!$reserva) {
            error_log("Reserva {$reservaId} no encontrada para recordatorio");
            return false;
        }
        
        // Verificar que WhatsApp esté conectado
        $stmt = $pdo->prepare('
            SELECT status FROM whatsapp_config 
            WHERE usuario_id = ? AND status = "connected"
        ');
        $stmt->execute([$reserva['usuario_id']]);
        if (!$stmt->fetch()) {
            error_log("WhatsApp no conectado para usuario {$reserva['usuario_id']}");
            return false;
        }
        
        // Obtener plantilla de recordatorio
        $mensaje = getMessageTemplate($reserva['usuario_id'], 'reminder', [
            'cliente' => $reserva['nombre'],
            'fecha' => formatDate($reserva['fecha']),
            'hora' => formatTime($reserva['hora']),
            'negocio' => $reserva['negocio']
        ]);
        
        // Enviar mensaje
        $resultado = sendWhatsAppMessage(
            $reserva['usuario_id'],
            $reserva['telefono'],
            $mensaje,
            'reminder',
            $reservaId
        );
        
        if ($resultado) {
            // Marcar recordatorio como enviado
            $stmt = $pdo->prepare('
                UPDATE reservas 
                SET whatsapp_recordatorio_enviado = 1, 
                    whatsapp_recordatorio_fecha = NOW() 
                WHERE id = ?
            ');
            $stmt->execute([$reservaId]);
            
            error_log("Recordatorio enviado para reserva {$reservaId}");
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error enviando recordatorio reserva {$reservaId}: " . $e->getMessage());
        return false;
    }
}

/**
 * Envía una confirmación de reserva por WhatsApp
 */
function sendReservationConfirmation($reservaId) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare('
            SELECT r.*, u.id as usuario_id, u.negocio
            FROM reservas r 
            JOIN usuarios u ON r.usuario_id = u.id 
            WHERE r.id = ?
        ');
        $stmt->execute([$reservaId]);
        $reserva = $stmt->fetch();
        
        if (!$reserva) {
            return false;
        }
        
        // Verificar WhatsApp conectado
        $stmt = $pdo->prepare('
            SELECT status FROM whatsapp_config 
            WHERE usuario_id = ? AND status = "connected"
        ');
        $stmt->execute([$reserva['usuario_id']]);
        if (!$stmt->fetch()) {
            return false;
        }
        
        // Obtener plantilla de confirmación
        $mensaje = getMessageTemplate($reserva['usuario_id'], 'confirmation', [
            'cliente' => $reserva['nombre'],
            'fecha' => formatDate($reserva['fecha']),
            'hora' => formatTime($reserva['hora']),
            'negocio' => $reserva['negocio']
        ]);
        
        // Enviar mensaje
        $resultado = sendWhatsAppMessage(
            $reserva['usuario_id'],
            $reserva['telefono'],
            $mensaje,
            'confirmation',
            $reservaId
        );
        
        if ($resultado) {
            // Marcar confirmación como enviada
            $stmt = $pdo->prepare('
                UPDATE reservas 
                SET whatsapp_confirmacion_enviada = 1, 
                    whatsapp_confirmacion_fecha = NOW() 
                WHERE id = ?
            ');
            $stmt->execute([$reservaId]);
            
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error enviando confirmación reserva {$reservaId}: " . $e->getMessage());
        return false;
    }
}

/**
 * Envía un mensaje de bienvenida automático
 */
function sendWelcomeMessage($usuarioId, $phoneNumber, $clientName = null) {
    try {
        // Verificar si ya se envió mensaje de bienvenida a este número
        $pdo = getPDO();
        $stmt = $pdo->prepare('
            SELECT id FROM conversaciones 
            WHERE usuario_id = ? AND cliente_phone = ? AND welcome_sent = 1
        ');
        $stmt->execute([$usuarioId, $phoneNumber]);
        
        if ($stmt->fetch()) {
            return false; // Ya se envió bienvenida
        }
        
        // Obtener plantilla de bienvenida
        $mensaje = getMessageTemplate($usuarioId, 'welcome', [
            'cliente' => $clientName ?: 'Cliente'
        ]);
        
        // Enviar mensaje
        $resultado = sendWhatsAppMessage(
            $usuarioId,
            $phoneNumber,
            $mensaje,
            'welcome'
        );
        
        if ($resultado) {
            // Marcar bienvenida como enviada
            $stmt = $pdo->prepare('
                UPDATE conversaciones 
                SET welcome_sent = 1 
                WHERE usuario_id = ? AND cliente_phone = ?
            ');
            $stmt->execute([$usuarioId, $phoneNumber]);
        }
        
        return $resultado;
        
    } catch (Exception $e) {
        error_log("Error enviando mensaje de bienvenida: " . $e->getMessage());
        return false;
    }
}

/**
 * Función principal para enviar mensajes WhatsApp
 */
function sendWhatsAppMessage($usuarioId, $phoneNumber, $mensaje, $tipo = 'manual', $reservaId = null, $clientName = null) {
    try {
        // Preparar datos para envío
        $data = [
            'to' => formatPhoneNumber($phoneNumber),
            'message' => $mensaje,
            'type' => $tipo,
            'clientName' => $clientName,
            'reservationId' => $reservaId
        ];
        
        // URL de la API de envío
        $apiUrl = ($_ENV['APP_URL'] ?? 'http://localhost') . '/api/send-whatsapp.php';
        
        // Crear contexto para petición HTTP
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'X-User-ID: ' . $usuarioId,
                    'X-Internal-Request: true'
                ],
                'content' => json_encode($data),
                'timeout' => 30
            ]
        ]);
        
        // Realizar petición
        $response = file_get_contents($apiUrl, false, $context);
        
        if ($response === false) {
            error_log("Error conectando con API de WhatsApp");
            return false;
        }
        
        $result = json_decode($response, true);
        
        if ($result && $result['success']) {
            error_log("Mensaje WhatsApp enviado exitosamente: {$tipo} a {$phoneNumber}");
            return true;
        } else {
            error_log("Error enviando mensaje WhatsApp: " . ($result['error'] ?? 'Error desconocido'));
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error en sendWhatsAppMessage: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene y procesa plantilla de mensaje
 */
function getMessageTemplate($usuarioId, $tipo, $variables = []) {
    try {
        $pdo = getPDO();
        
        // Primero intentar obtener plantilla personalizada del usuario
        $stmt = $pdo->prepare('
            SELECT valor 
            FROM configuraciones_usuario 
            WHERE usuario_id = ? AND clave = ?
        ');
        $stmt->execute([$usuarioId, "whatsapp_{$tipo}_message"]);
        $template = $stmt->fetchColumn();
        
        // Si no hay personalizada, usar por defecto
        if (!$template) {
            $defaultTemplates = [
                'welcome' => '¡Hola{cliente_nombre}! Gracias por contactarnos. ¿En qué podemos ayudarte?',
                'confirmation' => 'Hola {cliente}, tu reserva ha sido confirmada para el {fecha} a las {hora}. ¡Te esperamos en {negocio}!',
                'reminder' => 'Hola {cliente}, te recordamos tu cita de mañana {fecha} a las {hora} en {negocio}. Si necesitas cambiarla, contáctanos.',
                'cancellation' => 'Hola {cliente}, tu reserva del {fecha} a las {hora} ha sido cancelada. Disculpa las molestias.',
                'modification' => 'Hola {cliente}, tu reserva ha sido modificada. Nueva fecha: {fecha} a las {hora}.'
            ];
            
            $template = $defaultTemplates[$tipo] ?? 'Mensaje automático de {negocio}';
        }
        
        // Procesar variables en la plantilla
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        // Limpiar variables no reemplazadas
        $template = preg_replace('/\{[^}]+\}/', '', $template);
        
        return trim($template);
        
    } catch (Exception $e) {
        error_log("Error obteniendo plantilla de mensaje: " . $e->getMessage());
        return "Mensaje automático"; // Fallback
    }
}

/**
 * Crear o actualizar conversación
 */
function createOrUpdateConversation($usuarioId, $phoneNumber, $clientName = null, $lastMessage = null, $messageType = 'entrante') {
    try {
        $pdo = getPDO();
        
        // Buscar conversación existente
        $stmt = $pdo->prepare('
            SELECT id FROM conversaciones 
            WHERE usuario_id = ? AND cliente_phone = ?
        ');
        $stmt->execute([$usuarioId, $phoneNumber]);
        $conversation = $stmt->fetch();
        
        if ($conversation) {
            // Actualizar conversación existente
            $updateData = ['updated_at' => date('Y-m-d H:i:s')];
            $updateFields = ['updated_at = ?'];
            $updateParams = [date('Y-m-d H:i:s')];
            
            if ($lastMessage) {
                $updateFields[] = 'ultimo_mensaje = ?';
                $updateParams[] = $lastMessage;
            }
            
            if ($messageType === 'entrante') {
                $updateFields[] = 'no_leidos = no_leidos + 1';
            }
            
            if ($clientName && $clientName !== $phoneNumber) {
                $updateFields[] = 'cliente_nombre = ?';
                $updateParams[] = $clientName;
            }
            
            $updateParams[] = $conversation['id'];
            
            $stmt = $pdo->prepare('
                UPDATE conversaciones 
                SET ' . implode(', ', $updateFields) . '
                WHERE id = ?
            ');
            $stmt->execute($updateParams);
            
            return $conversation['id'];
        } else {
            // Crear nueva conversación
            $stmt = $pdo->prepare('
                INSERT INTO conversaciones 
                (usuario_id, cliente_phone, cliente_nombre, ultimo_mensaje, no_leidos, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ');
            
            $noLeidos = ($messageType === 'entrante') ? 1 : 0;
            $nombre = $clientName ?: $phoneNumber;
            
            $stmt->execute([
                $usuarioId,
                $phoneNumber,
                $nombre,
                $lastMessage,
                $noLeidos
            ]);
            
            return $pdo->lastInsertId();
        }
        
    } catch (Exception $e) {
        error_log("Error creando/actualizando conversación: " . $e->getMessage());
        return false;
    }
}

/**
 * Guardar mensaje en base de datos
 */
function saveMessage($conversationId, $content, $type, $messageId = null, $senderType = 'sistema') {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare('
            INSERT INTO mensajes 
            (conversacion_id, tipo, contenido, enviado_por, mensaje_id, timestamp, leido) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ');
        
        $leido = ($type === 'saliente') ? 1 : 0;
        $messageId = $messageId ?: generateMessageId();
        
        $stmt->execute([
            $conversationId,
            $type,
            $content,
            $senderType,
            $messageId,
            $leido
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (Exception $e) {
        error_log("Error guardando mensaje: " . $e->getMessage());
        return false;
    }
}

/**
 * Formatear número de teléfono
 */
function formatPhoneNumber($phone) {
    // Limpiar el número
    $clean = preg_replace('/[^\d]/', '', $phone);
    
    // Si empieza con 00, remover
    if (substr($clean, 0, 2) === '00') {
        $clean = substr($clean, 2);
    }
    
    // Si es un número español sin código de país, añadir 34
    if (strlen($clean) === 9 && (substr($clean, 0, 1) === '6' || substr($clean, 0, 1) === '7')) {
        $clean = '34' . $clean;
    }
    
    return $clean;
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
 * Generar ID único para mensaje
 */
function generateMessageId() {
    return 'msg_' . uniqid() . '_' . substr(md5(microtime()), 0, 8);
}

/**
 * Verificar si un usuario tiene WhatsApp conectado
 */
function isWhatsAppConnected($usuarioId) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('
            SELECT id FROM whatsapp_config 
            WHERE usuario_id = ? AND status = "connected"
        ');
        $stmt->execute([$usuarioId]);
        return (bool)$stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Obtener estadísticas de WhatsApp para un usuario
 */
function getWhatsAppStats($usuarioId, $periodo = 'hoy') {
    try {
        $pdo = getPDO();
        
        switch ($periodo) {
            case 'semana':
                $fechaInicio = date('Y-m-d', strtotime('monday this week'));
                break;
            case 'mes':
                $fechaInicio = date('Y-m-01');
                break;
            default:
                $fechaInicio = date('Y-m-d');
        }
        
        $fechaFin = date('Y-m-d 23:59:59');
        
        // Mensajes enviados
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM mensajes m
            JOIN conversaciones c ON m.conversacion_id = c.id
            WHERE c.usuario_id = ? AND m.tipo = "saliente" 
            AND m.timestamp BETWEEN ? AND ?
        ');
        $stmt->execute([$usuarioId, $fechaInicio, $fechaFin]);
        $enviados = $stmt->fetchColumn();
        
        // Mensajes recibidos
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM mensajes m
            JOIN conversaciones c ON m.conversacion_id = c.id
            WHERE c.usuario_id = ? AND m.tipo = "entrante" 
            AND m.timestamp BETWEEN ? AND ?
        ');
        $stmt->execute([$usuarioId, $fechaInicio, $fechaFin]);
        $recibidos = $stmt->fetchColumn();
        
        // Conversaciones activas
        $stmt = $pdo->prepare('
            SELECT COUNT(DISTINCT c.id) FROM conversaciones c
            JOIN mensajes m ON m.conversacion_id = c.id
            WHERE c.usuario_id = ? AND m.timestamp BETWEEN ? AND ?
        ');
        $stmt->execute([$usuarioId, $fechaInicio, $fechaFin]);
        $activas = $stmt->fetchColumn();
        
        return [
            'enviados' => (int)$enviados,
            'recibidos' => (int)$recibidos,
            'conversaciones_activas' => (int)$activas,
            'periodo' => $periodo
        ];
        
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas WhatsApp: " . $e->getMessage());
        return [
            'enviados' => 0,
            'recibidos' => 0,
            'conversaciones_activas' => 0,
            'periodo' => $periodo
        ];
    }
}

/**
 * Limpiar sesiones antiguas (para cron de limpieza)
 */
function cleanupOldWhatsAppData() {
    try {
        $pdo = getPDO();
        $cleaned = 0;
        
        // Limpiar QR codes antiguos (más de 1 hora)
        $stmt = $pdo->prepare('
            UPDATE whatsapp_config 
            SET qr_code = NULL 
            WHERE status = "connecting" 
            AND updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ');
        $stmt->execute();
        $cleaned += $stmt->rowCount();
        
        // Limpiar mensajes muy antiguos (más de 1 año)
        $stmt = $pdo->prepare('
            DELETE FROM mensajes 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ');
        $stmt->execute();
        $cleaned += $stmt->rowCount();
        
        // Limpiar conversaciones sin mensajes
        $stmt = $pdo->prepare('
            DELETE c FROM conversaciones c 
            LEFT JOIN mensajes m ON c.id = m.conversacion_id 
            WHERE m.id IS NULL AND c.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ');
        $stmt->execute();
        $cleaned += $stmt->rowCount();
        
        return $cleaned;
        
    } catch (Exception $e) {
        error_log("Error limpiando datos WhatsApp: " . $e->getMessage());
        return 0;
    }
}

/**
 * Verificar conexión con servidor Node.js
 */
function checkWhatsAppServerHealth() {
    $serverUrl = $_ENV['WHATSAPP_SERVER_URL'] ?? 'http://localhost:3001';
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $result = @file_get_contents($serverUrl . '/health', false, $context);
    
    if ($result === false) {
        return ['status' => 'offline', 'error' => 'No se pudo conectar'];
    }
    
    $data = json_decode($result, true);
    return $data ?: ['status' => 'unknown', 'error' => 'Respuesta inválida'];
}

?>