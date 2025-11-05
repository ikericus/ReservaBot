<?php
// api/whatsapp-conversations-2.php
// Este endpoint obtiene conversaciones de WhatsApp y enriquece los nombres con datos de clientes

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

try {
    // Obtener parámetros
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $phoneFilter = isset($_GET['phone']) ? trim($_GET['phone']) : null;
    $messageLimit = isset($_GET['message_limit']) ? (int)$_GET['message_limit'] : 50;
    
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    $clienteDomain = getContainer()->getClienteDomain();
    
    if ($phoneFilter) {
        // Si se solicita una conversación específica, obtener sus mensajes
        $messages = $whatsappDomain->obtenerMensajesConversacion($userId, $phoneFilter, $messageLimit);
        
        // Intentar obtener nombre del cliente
        try {
            $clienteStats = $clienteDomain->obtenerDetalleCliente($phoneFilter, $userId);
            $clienteName = $clienteStats['cliente']->getNombre();
        } catch (\Exception $e) {
            $clienteName = formatPhoneNumber($phoneFilter);
        }
        
        // Formatear mensajes
        $formattedMessages = array_map(function($msg) {
            return [
                'messageId' => $msg->getMessageId(),
                'content' => $msg->getMessageText(),
                'direction' => $msg->getDirection(),
                'isOutgoing' => $msg->isSaliente(),
                'status' => $msg->getStatus(),
                'timestamp' => $msg->getTimestampReceived()->format('Y-m-d H:i:s'),
                'hasMedia' => $msg->hasMedia()
            ];
        }, $messages);
        
        echo json_encode([
            'success' => true,
            'messages' => $formattedMessages,
            'clientName' => $clienteName
        ]);
        
    } else {
        // Obtener lista de conversaciones
        $conversations = $whatsappDomain->obtenerConversaciones($userId, $limit);
        
        // Enriquecer cada conversación con el nombre del cliente
        $enrichedConversations = array_map(function($conv) use ($clienteDomain, $userId) {
            $phone = $conv['phone'];
            $name = $conv['name'] ?? formatPhoneNumber($phone);
            
            // Intentar obtener nombre real del cliente
            try {
                $clienteStats = $clienteDomain->obtenerDetalleCliente($phone, $userId);
                $cliente = $clienteStats['cliente'];
                
                // Usar el nombre del cliente si está disponible
                if ($cliente && !empty($cliente->getNombre())) {
                    $name = $cliente->getNombre();
                }
            } catch (\Exception $e) {
                // Si no se encuentra el cliente, mantener el nombre por defecto
                debug_log("No se encontró cliente para teléfono: $phone");
            }
            
            return [
                'phone' => $phone,
                'name' => $name,
                'lastMessage' => $conv['last_message'] ?? '',
                'lastMessageTime' => isset($conv['last_message_time']) ? 
                    formatMessageTime($conv['last_message_time']) : '',
                'unreadCount' => $conv['unread_count'] ?? 0,
                'lastMessageTimestamp' => $conv['last_message_time'] ?? null
            ];
        }, $conversations);
        
        // Ordenar por último mensaje (más reciente primero)
        usort($enrichedConversations, function($a, $b) {
            $timeA = $a['lastMessageTimestamp'] ?? '1970-01-01';
            $timeB = $b['lastMessageTimestamp'] ?? '1970-01-01';
            return strcmp($timeB, $timeA);
        });
        
        echo json_encode([
            'success' => true,
            'conversations' => $enrichedConversations
        ]);
    }
    
} catch (\Exception $e) {
    error_log('Error en whatsapp-conversations-enriched: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error obteniendo conversaciones: ' . $e->getMessage()
    ]);
}

/**
 * Formatea un número de teléfono para mostrar
 */
function formatPhoneNumber(string $phone): string {
    // Si el número no está en formato internacional, asumimos que es local
    if (!str_starts_with($phone, '+')) {
        $phone = '+' . $phone;
    }
    
    return "Contacto $phone";
}

/**
 * Formatea el tiempo del último mensaje
 */
function formatMessageTime(string $timestamp): string {
    try {
        $date = new DateTime($timestamp);
        $now = new DateTime();
        $diff = $now->getTimestamp() - $date->getTimestamp();
        $diffInHours = $diff / 3600;
        
        if ($diffInHours < 1) {
            $minutes = floor($diff / 60);
            return $minutes === 0 ? 'Ahora' : "Hace {$minutes}m";
        } elseif ($diffInHours < 24) {
            return $date->format('H:i');
        } elseif ($diffInHours < 48) {
            return 'Ayer ' . $date->format('H:i');
        } elseif ($diffInHours < 168) { // menos de 1 semana
            $days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            return $days[$date->format('w')];
        } else {
            return $date->format('d/m/Y');
        }
    } catch (\Exception $e) {
        return '';
    }
}