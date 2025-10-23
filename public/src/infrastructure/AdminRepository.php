<?php
// public/src/infrastructure/AdminRepository.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\Admin\IAdminRepository;
use PDO;

class AdminRepository implements IAdminRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    // =============== ACTIVIDAD ===============
    
    public function obtenerUltimosAccesos(int $limit): array {
        $sql = "SELECT 
                    u.id,
                    u.email,
                    u.nombre,
                    u.plan,
                    u.last_activity,
                    COUNT(r.id) as total_reservas,
                    COUNT(DISTINCT DATE(r.created_at)) as dias_activo
                FROM usuarios u
                LEFT JOIN reservas r ON u.id = r.usuario_id
                WHERE u.last_activity IS NOT NULL
                GROUP BY u.id
                ORDER BY u.last_activity DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function contarLoginsHoy(): int {
        $sql = "SELECT COUNT(*) as total 
                FROM activity_log 
                WHERE event_type = 'login' 
                AND DATE(created_at) = CURDATE()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarUsuariosActivosUltimaHora(): int {
        $sql = "SELECT COUNT(DISTINCT usuario_id) as total 
                FROM activity_log 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function obtenerEstadisticasRecursos(): array {
        $sql = "SELECT 
                    resource,
                    COUNT(*) as total_accesos,
                    COUNT(DISTINCT DATE(created_at)) as dias_accedidos
                FROM activity_log 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY resource
                ORDER BY total_accesos DESC
                LIMIT 20";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerErroresRecientes(int $limit): array {
        $sql = "SELECT 
                    id,
                    nivel,
                    mensaje,
                    archivo,
                    linea,
                    created_at
                FROM system_logs 
                WHERE nivel IN ('ERROR', 'CRITICAL')
                ORDER BY created_at DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // =============== USUARIOS ===============
    
    public function obtenerUltimosUsuarios(int $limit): array {
        $sql = "SELECT 
                    id,
                    email,
                    nombre,
                    plan,
                    created_at,
                    last_activity,
                    (SELECT COUNT(*) FROM reservas WHERE usuario_id = u.id) as total_reservas,
                    (SELECT COUNT(*) FROM whatsapp_config WHERE usuario_id = u.id AND status IN ('connected', 'ready')) as whatsapp_conectado
                FROM usuarios u
                WHERE activo = 1
                ORDER BY created_at DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function contarTotalUsuarios(): int {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE activo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarUsuariosPorPlan(): array {
        $sql = "SELECT 
                    plan,
                    COUNT(*) as total,
                    COUNT(DISTINCT usuario_id) as usuarios_unicos
                FROM usuarios
                WHERE activo = 1
                GROUP BY plan
                ORDER BY total DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerUsuariosMasActivos(int $limit): array {
        $sql = "SELECT 
                    u.id,
                    u.email,
                    u.nombre,
                    u.plan,
                    COUNT(r.id) as total_reservas,
                    COUNT(DISTINCT DATE(r.created_at)) as dias_con_reservas,
                    MAX(r.created_at) as ultima_reserva
                FROM usuarios u
                LEFT JOIN reservas r ON u.id = r.usuario_id
                WHERE u.activo = 1
                GROUP BY u.id
                HAVING total_reservas > 0
                ORDER BY total_reservas DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function contarUsuariosActivosUltimos30Dias(): int {
        $sql = "SELECT COUNT(DISTINCT usuario_id) 
                FROM reservas 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarNuevosUsuariosHoy(): int {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    // =============== RESERVAS ===============
    
    public function obtenerUltimasReservas(int $limit): array {
        $sql = "SELECT 
                    r.id,
                    r.usuario_id,
                    u.nombre as usuario_nombre,
                    u.email as usuario_email,
                    r.cliente_nombre,
                    r.cliente_telefono,
                    r.fecha_inicio,
                    r.fecha_fin,
                    r.estado,
                    r.created_at,
                    (SELECT nombre FROM servicios WHERE id = r.servicio_id) as servicio
                FROM reservas r
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                ORDER BY r.created_at DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function contarTotalReservas(): int {
        $sql = "SELECT COUNT(*) FROM reservas";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarReservasHoy(): int {
        $sql = "SELECT COUNT(*) FROM reservas WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarReservasSemana(): int {
        $sql = "SELECT COUNT(*) FROM reservas WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarReservasMes(): int {
        $sql = "SELECT COUNT(*) FROM reservas WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function obtenerVolumenReservasPor30Dias(): array {
        $sql = "SELECT 
                    DATE(created_at) as fecha,
                    COUNT(*) as cantidad
                FROM reservas 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY fecha ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerVolumenReservasPorHoraHoy(): array {
        $sql = "SELECT 
                    HOUR(created_at) as hora,
                    COUNT(*) as cantidad
                FROM reservas 
                WHERE DATE(created_at) = CURDATE()
                GROUP BY HOUR(created_at)
                ORDER BY hora ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerDistribucionEstadoReservas(): array {
        $sql = "SELECT 
                    estado,
                    COUNT(*) as total
                FROM reservas 
                GROUP BY estado
                ORDER BY total DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // =============== WHATSAPP ===============
    
    public function contarUsuariosWhatsAppConectados(): int {
        $sql = "SELECT COUNT(*) 
                FROM whatsapp_config 
                WHERE status IN ('connected', 'ready')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarUsuariosWhatsAppRegistrados(): int {
        $sql = "SELECT COUNT(DISTINCT usuario_id) FROM whatsapp_config";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function obtenerUltimosUsuariosWhatsApp(int $limit): array {
        $sql = "SELECT 
                    w.usuario_id,
                    u.email,
                    u.nombre,
                    w.phone_number,
                    w.status,
                    w.last_activity,
                    (SELECT COUNT(*) FROM whatsapp_conversaciones WHERE usuario_id = w.usuario_id) as total_conversaciones
                FROM whatsapp_config w
                LEFT JOIN usuarios u ON w.usuario_id = u.id
                WHERE w.phone_number IS NOT NULL
                ORDER BY w.last_activity DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function contarMensajesEnviados(): int {
        $sql = "SELECT COUNT(*) 
                FROM whatsapp_conversaciones 
                WHERE id IS NOT NULL";
        
        // Nota: Esto necesitaría una tabla de mensajes completa
        // Por ahora retornamos un valor base
        return 0;
    }
    
    public function contarMensajesRecibidos(): int {
        $sql = "SELECT COUNT(*) 
                FROM whatsapp_conversaciones 
                WHERE no_leido = 1 OR no_leido = 0";
        
        return 0;
    }
    
    public function contarMensajesEnviadosHoy(): int {
        // Requeriría tabla de mensajes con timestamps
        return 0;
    }
    
    public function contarMensajesRecibidosHoy(): int {
        // Requeriría tabla de mensajes con timestamps
        return 0;
    }
    
    public function contarMensajesHoy(): int {
        // Requeriría tabla de mensajes con timestamps
        return 0;
    }
    
    public function obtenerVolumenMensajesPor7Dias(): array {
        // Requeriría tabla de mensajes con timestamps
        return [];
    }
    
    public function obtenerNumerosMasActivos(int $limit): array {
        $sql = "SELECT 
                    telefono,
                    COUNT(*) as total_conversaciones,
                    COUNT(DISTINCT usuario_id) as total_usuarios
                FROM whatsapp_conversaciones
                GROUP BY telefono
                ORDER BY total_conversaciones DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}