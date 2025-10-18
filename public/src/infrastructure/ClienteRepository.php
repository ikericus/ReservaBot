// src/infrastructure/ClienteRepository.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\Cliente\IClienteRepository;
use ReservaBot\Domain\Cliente\Cliente;
use PDO;

class ClienteRepository implements IClienteRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function obtenerEstadisticasCliente(string $telefono, int $usuarioId): ?Cliente {
        $sql = "SELECT 
                    telefono,
                    nombre as ultimo_nombre,
                    COUNT(id) as total_reservas,
                    SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                    MAX(fecha) as ultima_reserva,
                    MIN(created_at) as primer_contacto,
                    MAX(created_at) as ultimo_contacto
                FROM reservas 
                WHERE telefono = ? AND usuario_id = ?
                GROUP BY telefono, nombre";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$telefono, $usuarioId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return new Cliente(
            $data['telefono'],
            $data['ultimo_nombre'],
            (int)$data['total_reservas'],
            (int)$data['confirmadas'],
            (int)$data['pendientes'],
            (int)$data['canceladas'],
            $data['ultima_reserva'] ? new \DateTime($data['ultima_reserva']) : null,
            new \DateTime($data['primer_contacto']),
            new \DateTime($data['ultimo_contacto'])
        );
    }
    
    public function contarClientesUnicos(int $usuarioId, string $search = ''): int {
        $sql = "SELECT COUNT(DISTINCT telefono) 
                FROM reservas 
                WHERE usuario_id = ?";
        
        $params = [$usuarioId];
        
        if (!empty($search)) {
            $sql .= " AND (nombre LIKE ? OR telefono LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }
    
    public function obtenerClientesPaginados(
        int $usuarioId, 
        string $search, 
        int $page, 
        int $perPage
    ): array {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT 
                    telefono,
                    nombre as ultimo_nombre,
                    COUNT(id) as total_reservas,
                    SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                    MAX(fecha) as ultima_reserva,
                    MIN(created_at) as primer_contacto,
                    MAX(created_at) as ultimo_contacto
                FROM reservas 
                WHERE usuario_id = ?";
        
        $params = [$usuarioId];
        
        if (!empty($search)) {
            $sql .= " AND (nombre LIKE ? OR telefono LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " GROUP BY telefono, nombre 
                  ORDER BY ultimo_contacto DESC 
                  LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $clientes = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $clientes[] = new Cliente(
                $data['telefono'],
                $data['ultimo_nombre'],
                (int)$data['total_reservas'],
                (int)$data['confirmadas'],
                (int)$data['pendientes'],
                (int)$data['canceladas'],
                $data['ultima_reserva'] ? new \DateTime($data['ultima_reserva']) : null,
                new \DateTime($data['primer_contacto']),
                new \DateTime($data['ultimo_contacto'])
            );
        }
        
        return $clientes;
    }
}