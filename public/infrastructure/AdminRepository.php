<?php
// infrastructure/AdminRepository.php

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
                    COUNT(DISTINCT r.id) as total_reservas,
                    COUNT(DISTINCT DATE(r.created_at)) as dias_activo
                FROM usuarios u
                LEFT JOIN reservas r ON u.id = r.usuario_id
                WHERE u.last_activity IS NOT NULL AND u.activo = 1
                GROUP BY u.id, u.email, u.nombre, u.plan, u.last_activity
                ORDER BY u.last_activity DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function contarLoginsHoy(): int {
        $sql = "SELECT COUNT(DISTINCT usuario_id) 
                FROM sesiones_usuario 
                WHERE DATE(created_at) = CURDATE()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarUsuariosActivosUltimaHora(): int {
        $sql = "SELECT COUNT(*) 
                FROM usuarios 
                WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                AND activo = 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function obtenerEstadisticasRecursos(): array {
        // Usar formularios públicos y sus reservas como métrica de recursos
        $sql = "SELECT 
                    CONCAT('Formulario: ', f.nombre) as resource,
                    COUNT(DISTINCT r.id) as total_accesos,
                    COUNT(DISTINCT DATE(r.created_at)) as dias_accedidos
                FROM formularios_publicos f
                LEFT JOIN reservas r ON f.id = r.formulario_id
                WHERE f.activo = 1 AND r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY f.id, f.nombre
                ORDER BY total_accesos DESC
                LIMIT 20";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($result)) {
            return [
                ['resource' => 'Dashboard', 'total_accesos' => 0, 'dias_accedidos' => 0],
                ['resource' => 'Reservas', 'total_accesos' => 0, 'dias_accedidos' => 0]
            ];
        }
        
        return $result;
    }
    
    public function obtenerErroresRecientes(int $limit): array {
        // La tabla system_logs no existe en el esquema, retornamos vacío
        return [];
    }
    
    // =============== USUARIOS ===============
    
    public function obtenerUltimosUsuarios(int $limit): array {
        $sql = "SELECT 
                    u.id,
                    u.email,
                    u.nombre,
                    u.plan,
                    u.created_at,
                    u.last_activity,
                    COUNT(DISTINCT r.id) as total_reservas,
                    CASE WHEN wc.status = 'connected' THEN 1 ELSE 0 END as whatsapp_conectado
                FROM usuarios u
                LEFT JOIN reservas r ON u.id = r.usuario_id
                LEFT JOIN whatsapp_config wc ON u.id = wc.usuario_id
                WHERE u.activo = 1
                GROUP BY u.id, u.email, u.nombre, u.plan, u.created_at, u.last_activity, wc.status
                ORDER BY u.created_at DESC
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
                    COUNT(*) as total
                FROM usuarios
                WHERE activo = 1
                GROUP BY plan
                ORDER BY 
                    CASE plan
                        WHEN 'premium' THEN 1
                        WHEN 'estandar' THEN 2
                        WHEN 'gratis' THEN 3
                    END";
        
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
                    COUNT(DISTINCT r.id) as total_reservas,
                    COUNT(DISTINCT DATE(r.created_at)) as dias_con_reservas,
                    MAX(r.created_at) as ultima_reserva
                FROM usuarios u
                INNER JOIN reservas r ON u.id = r.usuario_id
                WHERE u.activo = 1
                GROUP BY u.id, u.email, u.nombre, u.plan
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
                    r.nombre as cliente_nombre,
                    r.telefono as cliente_telefono,
                    CONCAT(r.fecha, ' ', r.hora) as fecha_inicio,
                    r.fecha,
                    r.hora,
                    r.estado,
                    r.created_at,
                    COALESCE(r.mensaje, 'Sin servicio especificado') as servicio
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
                ORDER BY 
                    CASE estado
                        WHEN 'confirmada' THEN 1
                        WHEN 'pendiente' THEN 2
                        WHEN 'cancelada' THEN 3
                    END";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // =============== WHATSAPP ===============
    
    public function contarUsuariosWhatsAppConectados(): int {
        $sql = "SELECT COUNT(*) 
                FROM whatsapp_config 
                WHERE status = 'connected'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarUsuariosWhatsAppRegistrados(): int {
        $sql = "SELECT COUNT(*) 
                FROM whatsapp_config 
                WHERE phone_number IS NOT NULL";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function obtenerUltimosUsuariosWhatsApp(int $limit): array {
        $sql = "SELECT 
                    wc.usuario_id,
                    u.email,
                    u.nombre,
                    wc.phone_number,
                    wc.status,
                    wc.last_activity,
                    COUNT(DISTINCT c.id) as total_conversaciones
                FROM whatsapp_config wc
                INNER JOIN usuarios u ON wc.usuario_id = u.id
                LEFT JOIN conversaciones c ON wc.usuario_id = c.usuario_id
                WHERE wc.phone_number IS NOT NULL
                GROUP BY wc.usuario_id, u.email, u.nombre, wc.phone_number, wc.status, wc.last_activity
                ORDER BY wc.last_activity DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function contarMensajesEnviados(): int {
        $sql = "SELECT COUNT(*) 
                FROM whatsapp_messages 
                WHERE direction = 'outgoing'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarMensajesRecibidos(): int {
        $sql = "SELECT COUNT(*) 
                FROM whatsapp_messages 
                WHERE direction = 'incoming'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarMensajesEnviadosHoy(): int {
        $sql = "SELECT COUNT(*) 
                FROM whatsapp_messages 
                WHERE direction = 'outgoing' 
                AND DATE(created_at) = CURDATE()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarMensajesRecibidosHoy(): int {
        $sql = "SELECT COUNT(*) 
                FROM whatsapp_messages 
                WHERE direction = 'incoming' 
                AND DATE(created_at) = CURDATE()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarMensajesHoy(): int {
        $sql = "SELECT COUNT(*) 
                FROM whatsapp_messages 
                WHERE DATE(created_at) = CURDATE()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function obtenerVolumenMensajesPor7Dias(): array {
        $sql = "SELECT 
                    DATE(created_at) as fecha,
                    SUM(CASE WHEN direction = 'outgoing' THEN 1 ELSE 0 END) as enviados,
                    SUM(CASE WHEN direction = 'incoming' THEN 1 ELSE 0 END) as recibidos
                FROM whatsapp_messages 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY fecha ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerNumerosMasActivos(int $limit): array {
        $sql = "SELECT 
                    wm.phone_number as telefono,
                    COUNT(DISTINCT wm.id) as total_conversaciones,
                    COUNT(DISTINCT wm.usuario_id) as total_usuarios
                FROM whatsapp_messages wm
                GROUP BY wm.phone_number
                ORDER BY total_conversaciones DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}