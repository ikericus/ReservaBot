<?php
// infrastructure/ReservaRepository.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\Reserva\IReservaRepository;
use ReservaBot\Domain\Reserva\Reserva;
use ReservaBot\Domain\Reserva\EstadoReserva;
use DateTime;
use PDO;

class ReservaRepository implements IReservaRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function guardar(Reserva $reserva): Reserva {
        if ($reserva->getId() === null) {
            return $this->insertar($reserva);
        } else {
            return $this->actualizar($reserva);
        }
    }
    
    private function insertar(Reserva $reserva): Reserva {
        $sql = "INSERT INTO reservas 
                (nombre, telefono, whatsapp_id, fecha, hora, mensaje, notas_internas, estado, usuario_id, email, access_token, token_expires, formulario_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $reserva->getNombre(),
            $reserva->getTelefono()->getValue(),
            $reserva->getWhatsappId(),
            $reserva->getFecha()->format('Y-m-d'),
            $reserva->getHoraCompleta(),
            $reserva->getMensaje(),
            $reserva->getNotasInternas(),
            $reserva->getEstado()->value,
            $reserva->getUsuarioId(),
            $reserva->getEmail(),
            $reserva->getAccessToken(),
            $reserva->getTokenExpires()?->format('Y-m-d H:i:s'),
            $reserva->getFormularioId()
        ]);
        
        $id = (int)$this->pdo->lastInsertId();
        
        return $this->obtenerPorId($id, $reserva->getUsuarioId());
    }
    
    private function actualizar(Reserva $reserva): Reserva {
        $sql = "UPDATE reservas 
                SET nombre = ?, 
                    telefono = ?, 
                    whatsapp_id = ?,
                    fecha = ?, 
                    hora = ?, 
                    mensaje = ?, 
                    notas_internas = ?,
                    estado = ?,
                    email = ?,
                    access_token = ?,
                    token_expires = ?,
                    formulario_id = ?
                WHERE id = ? AND usuario_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $reserva->getNombre(),
            $reserva->getTelefono()->getValue(),
            $reserva->getWhatsappId(),
            $reserva->getFecha()->format('Y-m-d'),
            $reserva->getHoraCompleta(),
            $reserva->getMensaje(),
            $reserva->getNotasInternas(),
            $reserva->getEstado()->value,
            $reserva->getEmail(),
            $reserva->getAccessToken(),
            $reserva->getTokenExpires()?->format('Y-m-d H:i:s'),
            $reserva->getFormularioId(),
            $reserva->getId(),
            $reserva->getUsuarioId()
        ]);
        
        return $reserva;
    }
    
    public function obtenerPorId(int $id, int $usuarioId): ?Reserva {
        $sql = "SELECT * FROM reservas WHERE id = ? AND usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $usuarioId]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? Reserva::fromDatabase($data) : null;
    }
    
    public function obtenerPorUsuario(int $usuarioId): array {
        $sql = "SELECT * FROM reservas WHERE usuario_id = ? ORDER BY fecha DESC, hora DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        
        $reservas = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reservas[] = Reserva::fromDatabase($data);
        }
        
        return $reservas;
    }
    
    public function obtenerPorUsuarioYEstado(int $usuarioId, string $estado): array {
        $sql = "SELECT * FROM reservas 
                WHERE usuario_id = ? AND estado = ? 
                ORDER BY fecha, hora";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId, $estado]);
        
        $reservas = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reservas[] = Reserva::fromDatabase($data);
        }
        
        return $reservas;
    }

    public function obtenerPorFecha(DateTime $fecha, int $usuarioId): array {
        $sql = "SELECT * FROM reservas 
                WHERE fecha = ? AND usuario_id = ? 
                ORDER BY hora ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$fecha->format('Y-m-d'), $usuarioId]);
        
        $reservas = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reservas[] = Reserva::fromDatabase($data);
        }
        
        return $reservas;
    }
    
    public function obtenerPorRangoFechas(DateTime $desde, DateTime $hasta, int $usuarioId): array {
        $sql = "SELECT * FROM reservas 
                WHERE fecha BETWEEN ? AND ? 
                AND usuario_id = ? 
                ORDER BY fecha ASC, hora ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $desde->format('Y-m-d'),
            $hasta->format('Y-m-d'),
            $usuarioId
        ]);
        
        $reservas = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reservas[] = Reserva::fromDatabase($data);
        }
        
        return $reservas;
    }

    public function obtenerPorToken(string $token): ?Reserva {
        $sql = "SELECT * FROM reservas 
                WHERE access_token = ? 
                AND token_expires > NOW()
                AND estado IN (?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $token,
            EstadoReserva::PENDIENTE->value,
            EstadoReserva::CONFIRMADA->value
        ]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? Reserva::fromDatabase($data) : null;
    }
    
    public function existeReservaActiva(
        DateTime $fecha, 
        string $hora, 
        int $usuarioId, 
        ?int $excluirId = null ): bool {
        $sql = "SELECT COUNT(*) FROM reservas 
                WHERE fecha = ? 
                AND SUBSTR(hora, 1, 5) = ?
                AND usuario_id = ?
                AND estado IN (?, ?)";
        
        $params = [
            $fecha->format('Y-m-d'),
            $hora,
            $usuarioId,
            EstadoReserva::PENDIENTE->value,
            EstadoReserva::CONFIRMADA->value
        ];
        
        if ($excluirId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    public function obtenerPorWhatsappId(string $whatsappId, int $usuarioId): array {
        $sql = "SELECT * FROM reservas 
                WHERE whatsapp_id = ? AND usuario_id = ? 
                ORDER BY fecha DESC, hora DESC 
                LIMIT 10";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$whatsappId, $usuarioId]);
        
        $reservas = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reservas[] = Reserva::fromDatabase($data);
        }
        
        return $reservas;
    }
    
    public function eliminar(int $id, int $usuarioId): void {
        $sql = "DELETE FROM reservas WHERE id = ? AND usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $usuarioId]);
    }
    
    public function obtenerEstadisticas(
        int $usuarioId, 
        ?DateTime $desde = null, 
        ?DateTime $hasta = null ): array {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) as confirmadas,
                    SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) as canceladas
                FROM reservas 
                WHERE usuario_id = ?";
        
        $params = [
            EstadoReserva::CONFIRMADA->value,
            EstadoReserva::PENDIENTE->value,
            EstadoReserva::CANCELADA->value,
            $usuarioId
        ];
        
        if ($desde !== null) {
            $sql .= " AND fecha >= ?";
            $params[] = $desde->format('Y-m-d');
        }
        
        if ($hasta !== null) {
            $sql .= " AND fecha <= ?";
            $params[] = $hasta->format('Y-m-d');
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPorTelefono(string $telefono, int $usuarioId): array {
        $sql = "SELECT * FROM reservas 
                WHERE telefono = ? AND usuario_id = ? 
                ORDER BY fecha DESC, hora DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$telefono, $usuarioId]);
        
        $reservas = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reservas[] = Reserva::fromDatabase($data);
        }
        
        return $reservas;
    }
    
    public function obtenerPorIdYToken(int $id, string $token): ?Reserva {
        $sql = "SELECT * FROM reservas 
                WHERE id = ? 
                AND access_token = ? 
                AND token_expires > NOW()
                AND estado IN (?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $id, 
            $token,
            EstadoReserva::PENDIENTE->value,
            EstadoReserva::CONFIRMADA->value
        ]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? Reserva::fromDatabase($data) : null;
    }
    
    public function registrarOrigenReserva(
        int $reservaId,
        ?int $formularioId,
        string $origen,
        ?string $ipAddress,
        ?string $userAgent ): void {
        try {
            $sql = "INSERT INTO reservas_origen (
                        reserva_id, formulario_id, origen, ip_address, user_agent, created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $reservaId,
                $formularioId,
                $origen,
                $ipAddress,
                $userAgent
            ]);
        } catch (\PDOException $e) {
            // No es crítico si falla el registro del origen
            error_log("Error al registrar origen de reserva: " . $e->getMessage());
        }
    }
}