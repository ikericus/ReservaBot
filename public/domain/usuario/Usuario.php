<?php
// domain/usuario/Usuario.php

namespace ReservaBot\Domain\Usuario;

class Usuario {
    private int $id;
    private string $nombre;
    private string $email;
    private string $telefono;
    private string $plan;
    private string $passwordHash;
    private ?string $apiKey;
    private bool $activo;
    private ?string $resetToken;
    private ?\DateTime $resetTokenExpiry;
    private ?string $verificacionToken;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;
    
    public function __construct(
        int $id,
        string $nombre,
        string $email,
        string $telefono,
        string $plan,
        string $passwordHash,
        ?string $apiKey = null,
        bool $activo = true,
        ?string $resetToken = null,
        ?\DateTime $resetTokenExpiry = null,
        ?string $verificacionToken = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->telefono = $telefono;
        $this->plan = $plan;
        $this->passwordHash = $passwordHash;
        $this->apiKey = $apiKey;
        $this->activo = $activo;
        $this->resetToken = $resetToken;
        $this->resetTokenExpiry = $resetTokenExpiry;
        $this->verificacionToken = $verificacionToken;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
    }
    
    // Getters
    public function getId(): int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function getEmail(): string { return $this->email; }
    public function getTelefono(): string { return $this->telefono; }
    public function getPlan(): string { return $this->plan; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getApiKey(): ?string { return $this->apiKey; }
    public function isActivo(): bool { return $this->activo; }
    public function getResetToken(): ?string { return $this->resetToken; }
    public function getResetTokenExpiry(): ?\DateTime { return $this->resetTokenExpiry; }
    public function getVerificacionToken(): ?string { return $this->verificacionToken; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }
    
    public function verificarPassword(string $password): bool {
        return password_verify($password, $this->passwordHash);
    }
    
    public function tokenRestablecimientoValido(): bool {
        if (!$this->resetTokenExpiry) {
            return false;
        }
        
        $now = new \DateTime();
        return $this->resetTokenExpiry > $now;
    }
    
    public function tokenVerificacionValido(): bool {
        if (!$this->verificacionToken) {
            return false;
        }
        
        // Token válido si fue actualizado hace menos de 24 horas
        $hace24Horas = new \DateTime('-24 hours');
        return $this->updatedAt > $hace24Horas;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'plan' => $this->plan,
            'activo' => $this->activo,
            'api_key' => $this->apiKey,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }
}