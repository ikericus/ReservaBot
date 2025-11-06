<?php
// infrastructure/WhatsAppRepository.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\WhatsApp\IWhatsAppRepository;
use ReservaBot\Domain\WhatsApp\WhatsAppConfig;
use ReservaBot\Domain\WhatsApp\WhatsAppMessage;
use ReservaBot\Domain\WhatsApp\WhatsAppMessageTemplate;
use PDO;

class WhatsAppRepository implements IWhatsAppRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    // ==================== CONFIGURACIÓN ====================
    
    public function obtenerConfiguracion(int $usuarioId): ?WhatsAppConfig {
        $sql = "SELECT * FROM whatsapp_config WHERE usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? WhatsAppConfig::fromDatabase($data) : null;
    }
    
    public function guardarConfiguracion(WhatsAppConfig $config): WhatsAppConfig {

        $data = $config->toArray();
        
        $sql = "INSERT INTO whatsapp_config 
                (usuario_id, phone_number, status, qr_code, last_activity, 
                 auto_confirmacion, auto_recordatorio, auto_bienvenida)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    phone_number = VALUES(phone_number),
                    status = VALUES(status),
                    qr_code = VALUES(qr_code),
                    last_activity = VALUES(last_activity),
                    auto_confirmacion = VALUES(auto_confirmacion),
                    auto_recordatorio = VALUES(auto_recordatorio),
                    auto_bienvenida = VALUES(auto_bienvenida)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['usuario_id'],
            $data['phone_number'],
            $data['status'],
            $data['qr_code'],
            $data['last_activity'],
            $data['auto_confirmacion'],
            $data['auto_recordatorio'],
            $data['auto_bienvenida']
        ]);
        
        return $config;
    }
    
    // ==================== MENSAJES ====================
    
    public function guardarMensaje(WhatsAppMessage $mensaje): WhatsAppMessage {
        if ($mensaje->getId() === null) {
            return $this->insertarMensaje($mensaje);
        } else {
            return $this->actualizarMensaje($mensaje);
        }
    }
    
    private function insertarMensaje(WhatsAppMessage $mensaje): WhatsAppMessage {
        $data = $mensaje->toArray();
        
        $sql = "INSERT INTO whatsapp_messages 
                (usuario_id, message_id, phone_number, message_text, direction, 
                 is_group, has_media, status, timestamp_received, timestamp_sent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['usuario_id'],
            $data['message_id'],
            $data['phone_number'],
            $data['message_text'],
            $data['direction'],
            $data['is_group'],
            $data['has_media'],
            $data['status'],
            $data['timestamp_received'],
            $data['timestamp_sent']
        ]);
        
        $id = (int)$this->pdo->lastInsertId();
        
        return $this->obtenerMensajePorId($id);
    }
    
    private function actualizarMensaje(WhatsAppMessage $mensaje): WhatsAppMessage {
        $data = $mensaje->toArray();
        
        $sql = "UPDATE whatsapp_messages 
                SET status = ?,
                    timestamp_sent = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['status'],
            $data['timestamp_sent'],
            $data['id']
        ]);
        
        return $mensaje;
    }
    
    private function obtenerMensajePorId(int $id): WhatsAppMessage {
        $sql = "SELECT * FROM whatsapp_messages WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return WhatsAppMessage::fromDatabase($data);
    }
    
    public function obtenerMensajesPorTelefono(int $usuarioId, string $phoneNumber, int $limit = 50): array {
        $sql = "SELECT * FROM whatsapp_messages 
                WHERE usuario_id = ? AND phone_number = ?
                ORDER BY timestamp_received DESC 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId, $phoneNumber, $limit]);
        
        $mensajes = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mensajes[] = WhatsAppMessage::fromDatabase($data);
        }
        
        return array_reverse($mensajes); // Más antiguos primero
    }
    
    public function obtenerMensajesPorUsuario(int $usuarioId, int $limit = 100): array {
        $sql = "SELECT * FROM whatsapp_messages 
                WHERE usuario_id = ?
                ORDER BY timestamp_received DESC 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId, $limit]);
        
        $mensajes = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mensajes[] = WhatsAppMessage::fromDatabase($data);
        }
        
        return $mensajes;
    }
    
    public function obtenerMensajePorMessageId(string $messageId, int $usuarioId): ?WhatsAppMessage {
        $sql = "SELECT * FROM whatsapp_messages 
                WHERE message_id = ? AND usuario_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$messageId, $usuarioId]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? WhatsAppMessage::fromDatabase($data) : null;
    }
    
    public function actualizarEstadoMensaje(string $messageId, int $usuarioId, string $estado): bool {
        $sql = "UPDATE whatsapp_messages 
                SET status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE message_id = ? AND usuario_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$estado, $messageId, $usuarioId]);
    }
    
    // ==================== CONVERSACIONES ====================
    
    /**
     * Obtiene lista de conversaciones (último mensaje por teléfono)
     */
    public function obtenerConversaciones(int $usuarioId, int $limit = 20): array {
        $sql = "SELECT 
                    phone_number,
                    MAX(id) as ultimo_id,
                    MAX(timestamp_received) as ultima_actividad,
                    SUM(CASE WHEN direction = 'incoming' AND status != 'read' THEN 1 ELSE 0 END) as no_leidos
                FROM whatsapp_messages 
                WHERE usuario_id = ? AND is_group = 0
                GROUP BY phone_number
                ORDER BY ultima_actividad DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId, $limit]);
        
        $conversaciones = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Obtener el último mensaje completo
            $sqlMsg = "SELECT * FROM whatsapp_messages WHERE id = ?";
            $stmtMsg = $this->pdo->prepare($sqlMsg);
            $stmtMsg->execute([$row['ultimo_id']]);
            $msgData = $stmtMsg->fetch(PDO::FETCH_ASSOC);
            
            $conversaciones[] = [
                'phone_number' => $row['phone_number'],
                'ultimo_mensaje' => WhatsAppMessage::fromDatabase($msgData),
                'no_leidos' => (int)$row['no_leidos'],
                'ultima_actividad' => $row['ultima_actividad']
            ];
        }
        
        return $conversaciones;
    }
    
    public function contarMensajesNoLeidos(int $usuarioId): int {
        $sql = "SELECT COUNT(*) FROM whatsapp_messages 
                WHERE usuario_id = ? 
                AND direction = 'incoming' 
                AND status != 'read'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        
        return (int)$stmt->fetchColumn();
    }
    
    public function marcarConversacionComoLeida(int $usuarioId, string $phoneNumber): bool {
        $sql = "UPDATE whatsapp_messages 
                SET status = 'read', updated_at = CURRENT_TIMESTAMP
                WHERE usuario_id = ? 
                AND phone_number = ? 
                AND direction = 'incoming'
                AND status != 'read'";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$usuarioId, $phoneNumber]);
    }
    
    // ==================== ESTADÍSTICAS ====================
    
    public function obtenerEstadisticas(int $usuarioId): array {
        // Total mensajes
        $sql = "SELECT COUNT(*) FROM whatsapp_messages WHERE usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        $totalMensajes = $stmt->fetchColumn();
        
        // Mensajes hoy
        $sql = "SELECT COUNT(*) FROM whatsapp_messages 
                WHERE usuario_id = ? AND DATE(timestamp_received) = CURDATE()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        $mensajesHoy = $stmt->fetchColumn();
        
        // Conversaciones únicas
        $sql = "SELECT COUNT(DISTINCT phone_number) FROM whatsapp_messages 
                WHERE usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        $totalConversaciones = $stmt->fetchColumn();
        
        // Conversaciones activas (últimas 24h)
        $sql = "SELECT COUNT(DISTINCT phone_number) FROM whatsapp_messages 
                WHERE usuario_id = ? 
                AND timestamp_received >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        $conversacionesActivas = $stmt->fetchColumn();
        
        return [
            'total_mensajes' => (int)$totalMensajes,
            'mensajes_hoy' => (int)$mensajesHoy,
            'total_conversaciones' => (int)$totalConversaciones,
            'conversaciones_activas' => (int)$conversacionesActivas
        ];
    }
    
    // ==================== PLANTILLAS DE MENSAJES ====================
    
    /**
     * Obtiene una plantilla específica
     */
    public function obtenerTemplate(int $usuarioId, string $tipoMensaje): ?WhatsAppMessageTemplate {
        $sql = "SELECT * FROM whatsapp_automessage_templates 
                WHERE usuario_id = ? AND tipo_mensaje = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId, $tipoMensaje]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? WhatsAppMessageTemplate::fromDatabase($data) : null;
    }
    
    /**
     * Obtiene todas las plantillas de un usuario
     */
    public function obtenerTemplates(int $usuarioId): array {
        $sql = "SELECT * FROM whatsapp_automessage_templates 
                WHERE usuario_id = ?
                ORDER BY tipo_mensaje";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        
        $templates = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $templates[$data['tipo_mensaje']] = WhatsAppMessageTemplate::fromDatabase($data);
        }
        
        return $templates;
    }
    
    /**
     * Guarda o actualiza una plantilla
     */
    public function guardarTemplate(WhatsAppMessageTemplate $template): WhatsAppMessageTemplate {
        $data = $template->toArray();
        
        $sql = "INSERT INTO whatsapp_automessage_templates 
                (usuario_id, tipo_mensaje, mensaje)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    mensaje = VALUES(mensaje),
                    updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['usuario_id'],
            $data['tipo_mensaje'],
            $data['mensaje']
        ]);
        
        // Si es nuevo, obtener el ID
        if ($template->getId() === null) {
            return $this->obtenerTemplate($data['usuario_id'], $data['tipo_mensaje']);
        }
        
        return $template;
    }
    
    /**
     * Elimina una plantilla
     */
    public function eliminarTemplate(int $usuarioId, string $tipoMensaje): bool {
        $sql = "DELETE FROM whatsapp_automessage_templates 
                WHERE usuario_id = ? AND tipo_mensaje = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$usuarioId, $tipoMensaje]);
    }
}