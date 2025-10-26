<?php
// src/infrastructure/UsuarioRepository.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\Usuario\IUsuarioRepository;
use ReservaBot\Domain\Usuario\Usuario;
use PDO;

class UsuarioRepository implements IUsuarioRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function obtenerPorEmail(string $email): ?Usuario {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $this->mapearDesdeArray($row) : null;
    }
    
    public function obtenerPorId(int $id): ?Usuario {
        $sql = "SELECT * FROM usuarios WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $this->mapearDesdeArray($row) : null;
    }
    
    public function obtenerPorResetToken(string $token): ?Usuario {
        $sql = "SELECT * FROM usuarios WHERE reset_token = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$token]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $this->mapearDesdeArray($row) : null;
    }
    
    public function crear(
        string $nombre,
        string $email,
        string $telefono,
        string $negocio,
        string $passwordHash,
        string $plan = 'gratis'
    ): Usuario {
        $apiKey = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO usuarios 
                (nombre, email, telefono, negocio, password_hash, plan, api_key, created_at, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 1)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $nombre,
            $email,
            $telefono,
            $negocio,
            $passwordHash,
            $plan,
            $apiKey
        ]);
        
        $id = (int) $this->pdo->lastInsertId();
        
        return $this->obtenerPorId($id);
    }
    
    public function actualizar(int $id, array $datos): void {
        $campos = [];
        $valores = [];
        
        foreach ($datos as $campo => $valor) {
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
        
        $valores[] = $id;
        
        $sql = "UPDATE usuarios SET " . implode(', ', $campos) . ", updated_at = NOW() WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($valores);
    }
    
    public function actualizarPassword(int $id, string $passwordHash): void {
        $sql = "UPDATE usuarios SET password_hash = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$passwordHash, $id]);
    }
    
    public function establecerResetToken(int $id, string $token, \DateTime $expiry): void {
        $sql = "UPDATE usuarios 
                SET reset_token = ?, reset_token_expiry = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $token,
            $expiry->format('Y-m-d H:i:s'),
            $id
        ]);
    }
    
    public function limpiarResetToken(int $id): void {
        $sql = "UPDATE usuarios 
                SET reset_token = NULL, reset_token_expiry = NULL, updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
    }
    
    public function emailExiste(string $email, ?int $excluirId = null): bool {
        if ($excluirId) {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email, $excluirId]);
        } else {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
        }
        
        return $stmt->fetchColumn() > 0;
    }

     /** Verifica si un usuario es administrador. Por ahora usa email hardcodeado de .env */
    public function esAdmin(int $usuarioId): bool {
        $usuario = $this->obtenerPorId($usuarioId);
        
        if (!$usuario) {
            return false;
        }
        
        // Obtener email de admin desde .env
        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? '';
        
        if (empty($adminEmail)) {
            error_log('ADMIN_EMAIL no está configurado en .env');
            return false;
        }
        
        $esAdmin = strtolower(trim($usuario->getEmail())) === strtolower(trim($adminEmail));
                
        return $esAdmin;
    }

    
    private function mapearDesdeArray(array $row): Usuario {
        return new Usuario(
            (int) $row['id'],
            $row['nombre'],
            $row['email'],
            $row['telefono'] ?? '',
            $row['negocio'] ?? '',
            $row['plan'] ?? 'gratis',
            $row['password_hash'],
            $row['api_key'] ?? null,
            (bool) $row['activo'],
            $row['reset_token'] ?? null,
            $row['reset_token_expiry'] ? new \DateTime($row['reset_token_expiry']) : null,
            $row['verificacion_token'] ?? null,
            new \DateTime($row['created_at']),
            new \DateTime($row['updated_at'])
        );
    }
}