<?php
// src/infrastructure/AdminRepository.php

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
        // En lugar de activity_log, usamos sesiones_usuario y last_activity de usuarios
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
                WHERE u.last_activity IS NOT NULL AND u.activo = 1
                GROUP BY u.id
                ORDER BY u.last_activity DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function contarLoginsHoy(): int {
        // Contar sesiones creadas hoy
        $sql = "SELECT COUNT(DISTINCT usuario_id) 
                FROM sesiones_usuario 
                WHERE DATE(created_at) = CURDATE()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarUsuariosActivosUltimaHora(): int {
        // Usuarios con last_activity en la última hora
        $sql = "SELECT COUNT(*) 
                FROM usuarios 
                WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                AND activo = 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function obtenerEstadisticasRecursos(): array {
        // En lugar de activity_log, usamos formularios_publicos como "recursos"
        $sql = "SELECT 
                    CONCAT('Formulario: ', nombre) as resource,
                    COUNT(DISTINCT r.id) as total_accesos,
                    COUNT(DISTINCT DATE(r.created_at)) as dias_accedidos
                FROM formularios_publicos f
                LEFT JOIN reservas r ON f.id = r.formulario_id
                WHERE f.activo = 1 AND r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY f.id
                ORDER BY total_accesos DESC
                LIMIT 20";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si no hay datos, devolver datos de ejemplo
        if (empty($result)) {
            return [
                ['resource' => 'Formulario: Citas', 'total_accesos' => 0, 'dias_accedidos' => 0],
                ['resource' => 'Dashboard', 'total_accesos' => 0, 'dias_accedidos' => 0]
            ];
        }
        
        return $result;
    }
    
    public function obtenerErroresRecientes(int $limit): array {
        // Si no existe tabla system_logs, devolver array vacío
        $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'system_logs' LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            return []; // La tabla no existe
        }
        
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
                    u.id,
                    u.email,
                    u.nombre,
                    u.plan,
                    u.created_at,
                    u.last_activity,
                    (SELECT COUNT(*) FROM reservas WHERE usuario_id = u.id) as total_reservas,
                    0 as whatsapp_conectado
                FROM usuarios u
                WHERE u.activo = 1
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
                    r.nombre as cliente_nombre,
                    r.telefono as cliente_telefono,
                    r.fecha as fecha_inicio,
                    r.hora,
                    r.estado,
                    r.created_at,
                    'Reserva' as servicio
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
    // Nota: Adaptado a la estructura actual sin tabla whatsapp_config
    
    public function contarUsuariosWhatsAppConectados(): int {
        // Contar usuarios_whatsapp registrados
        $sql = "SELECT COUNT(DISTINCT usuario_id) 
                FROM usuarios_whatsapp 
                WHERE usuario_id IS NOT NULL";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $count = (int)$stmt->fetchColumn();
        return $count > 0 ? $count : 0;
    }
    
    public function contarUsuariosWhatsAppRegistrados(): int {
        $sql = "SELECT COUNT(DISTINCT usuario_id) FROM usuarios_whatsapp";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function obtenerUltimosUsuariosWhatsApp(int $limit): array {
        $sql = "SELECT 
                    uw.usuario_id,
                    u.email,
                    u.nombre,
                    uw.telefono as phone_number,
                    'connected' as status,
                    uw.last_active,
                    (SELECT COUNT(*) FROM autorespuestas_whatsapp WHERE usuario_id = uw.usuario_id) as total_conversaciones
                FROM usuarios_whatsapp uw
                LEFT JOIN usuarios u ON uw.usuario_id = u.id
                WHERE uw.usuario_id IS NOT NULL
                ORDER BY uw.last_active DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function contarMensajesEnviados(): int {
        // Contar mensajes_whatsapp donde from_me = 1
        $sql = "SELECT COUNT(*) 
                FROM mensajes_whatsapp 
                WHERE from_me = 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarMensajesRecibidos(): int {
        // Contar mensajes_whatsapp donde from_me = 0
        $sql = "SELECT COUNT(*) 
                FROM mensajes_whatsapp 
                WHERE from_me = 0";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarMensajesEnviadosHoy(): int {
        $sql = "SELECT COUNT(*) 
                FROM mensajes_whatsapp 
                WHERE from_me = 1 
                AND DATE(FROM_UNIXTIME(timestamp)) = CURDATE()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarMensajesRecibidosHoy(): int {
        $sql = "SELECT COUNT(*) 
                FROM mensajes_whatsapp 
                WHERE from_me = 0 
                AND DATE(FROM_UNIXTIME(timestamp)) = CURDATE()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    public function contarMensajesHoy(): int {
        // Total de mensajes hoy (enviados + recibidos)
        return $this->contarMensajesEnviadosHoy() + $this->contarMensajesRecibidosHoy();
    }
    
    public function obtenerVolumenMensajesPor7Dias(): array {
        $sql = "SELECT 
                    DATE(FROM_UNIXTIME(timestamp)) as fecha,
                    SUM(CASE WHEN from_me = 1 THEN 1 ELSE 0 END) as enviados,
                    SUM(CASE WHEN from_me = 0 THEN 1 ELSE 0 END) as recibidos
                FROM mensajes_whatsapp 
                WHERE timestamp >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))
                GROUP BY DATE(FROM_UNIXTIME(timestamp))
                ORDER BY fecha ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerNumerosMasActivos(int $limit): array {
        // Obtener números más activos de mensajes_whatsapp
        $sql = "SELECT 
                    chat_id as telefono,
                    COUNT(*) as total_conversaciones,
                    COUNT(DISTINCT usuario_id) as total_usuarios
                FROM mensajes_whatsapp
                GROUP BY chat_id
                ORDER BY total_conversaciones DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si no hay datos, devolver array vacío
        return $result ?: [];
    }
}