<?php
// src/infrastructure/WhatsAppRepository.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\WhatsApp\IWhatsAppRepository;
use ReservaBot\Domain\WhatsApp\WhatsAppConfig;
use ReservaBot\Domain\WhatsApp\Conversacion;
use PDO;

class WhatsAppRepository implements IWhatsAppRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
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
    
    public function obtenerConversaciones(int $usuarioId, int $limit = 10): array {
        $sql = "SELECT * FROM whatsapp_conversaciones 
                WHERE usuario_id = ? 
                ORDER BY ultima_actividad DESC 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId, $limit]);
        
        $conversaciones = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $conversaciones[] = Conversacion::fromDatabase($data);
        }
        
        return $conversaciones;
    }
    
    public function obtenerConversacionPorWhatsappId(string $whatsappId, int $usuarioId): ?Conversacion {
        $sql = "SELECT * FROM whatsapp_conversaciones 
                WHERE whatsapp_id = ? AND usuario_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$whatsappId, $usuarioId]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? Conversacion::fromDatabase($data) : null;
    }
    
    public function guardarConversacion(Conversacion $conversacion): Conversacion {
        $data = $conversacion->toArray();
        
        if ($conversacion->getId() === null) {
            return $this->insertarConversacion($conversacion);
        } else {
            return $this->actualizarConversacion($conversacion);
        }
    }
    
    private function insertarConversacion(Conversacion $conversacion): Conversacion {
        $data = $conversacion->toArray();
        
        $sql = "INSERT INTO whatsapp_conversaciones 
                (usuario_id, whatsapp_id, nombre, telefono, ultimo_mensaje, ultima_actividad, no_leido)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['usuario_id'],
            $data['whatsapp_id'],
            $data['nombre'],
            $data['telefono'],
            $data['ultimo_mensaje'],
            $data['ultima_actividad'],
            $data['no_leido']
        ]);
        
        $id = (int)$this->pdo->lastInsertId();
        
        return $this->obtenerConversacionPorId($id);
    }
    
    private function actualizarConversacion(Conversacion $conversacion): Conversacion {
        $data = $conversacion->toArray();
        
        $sql = "UPDATE whatsapp_conversaciones 
                SET nombre = ?,
                    ultimo_mensaje = ?,
                    ultima_actividad = ?,
                    no_leido = ?
                WHERE id = ? AND usuario_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['nombre'],
            $data['ultimo_mensaje'],
            $data['ultima_actividad'],
            $data['no_leido'],
            $data['id'],
            $data['usuario_id']
        ]);
        
        return $conversacion;
    }
    
    private function obtenerConversacionPorId(int $id): Conversacion {
        $sql = "SELECT * FROM whatsapp_conversaciones WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return Conversacion::fromDatabase($data);
    }
    
    public function contarNoLeidas(int $usuarioId): int {
        $sql = "SELECT COUNT(*) FROM whatsapp_conversaciones 
                WHERE usuario_id = ? AND no_leido = 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        
        return (int)$stmt->fetchColumn();
    }
    
    public function obtenerEstadisticas(int $usuarioId): array {
        // Total conversaciones
        $sql = "SELECT COUNT(*) as total FROM whatsapp_conversaciones WHERE usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        $total = $stmt->fetchColumn();
        
        // Conversaciones activas (últimas 24h)
        $sql = "SELECT COUNT(*) as activas FROM whatsapp_conversaciones 
                WHERE usuario_id = ? AND ultima_actividad >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        $activas = $stmt->fetchColumn();
        
        // Mensajes enviados hoy (esto requeriría una tabla de mensajes, por ahora retornamos 0)
        $mensajesHoy = 0;
        
        return [
            'total_conversaciones' => (int)$total,
            'conversaciones_activas' => (int)$activas,
            'mensajes_hoy' => $mensajesHoy
        ];
    }
}