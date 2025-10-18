<?php
// src/infrastructure/ConfiguracionNegocioRepository.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\Configuracion\IConfiguracionNegocioRepository;
use PDO;

class ConfiguracionNegocioRepository implements IConfiguracionNegocioRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function obtenerTodas(int $usuarioId): array {
        // Usa tabla configuraciones_usuario
        $sql = "SELECT clave, valor FROM configuraciones_usuario WHERE usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['clave']] = $row['valor'];
        }
        
        return $config;
    }
    
    public function obtener(string $clave, int $usuarioId): ?string {
        $sql = "SELECT valor FROM configuraciones_usuario 
                WHERE clave = ? AND usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$clave, $usuarioId]);
        
        $valor = $stmt->fetchColumn();
        return $valor !== false ? $valor : null;
    }
    
    public function actualizar(string $clave, string $valor, int $usuarioId): void {
        $sql = "INSERT INTO configuraciones_usuario (usuario_id, clave, valor) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE valor = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId, $clave, $valor, $valor]);
    }
    
    public function actualizarVarias(array $configuraciones, int $usuarioId): void {
        $this->pdo->beginTransaction();
        
        try {
            foreach ($configuraciones as $clave => $valor) {
                $this->actualizar($clave, $valor, $usuarioId);
            }
            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}