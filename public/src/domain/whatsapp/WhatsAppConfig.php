<?php
// src/domain/whatsapp/WhatsAppConfig.php

namespace ReservaBot\Domain\WhatsApp;

use DateTime;

class WhatsAppConfig {
    private int $usuarioId;
    private ?string $phoneNumber;
    private string $status;
    private ?string $qrCode;
    private ?DateTime $lastActivity;
    private bool $autoConfirmacion;
    private bool $autoRecordatorio;
    private bool $autoBienvenida;
    
    private function __construct(
        int $usuarioId,
        ?string $phoneNumber,
        string $status,
        ?string $qrCode,
        ?DateTime $lastActivity,
        bool $autoConfirmacion,
        bool $autoRecordatorio,
        bool $autoBienvenida
    ) {
        $this->usuarioId = $usuarioId;
        $this->phoneNumber = $phoneNumber;
        $this->status = $status;
        $this->qrCode = $qrCode;
        $this->lastActivity = $lastActivity;
        $this->autoConfirmacion = $autoConfirmacion;
        $this->autoRecordatorio = $autoRecordatorio;
        $this->autoBienvenida = $autoBienvenida;
    }
    
    public static function crear(int $usuarioId): self {
        return new self(
            $usuarioId,
            null,
            'disconnected',
            null,
            null,
            false,
            false,
            false
        );
    }
    
    public static function fromDatabase(array $data): self {
        return new self(
            (int)$data['usuario_id'],
            $data['phone_number'] ?? null,
            $data['status'] ?? 'disconnected',
            $data['qr_code'] ?? null,
            isset($data['last_activity']) ? new DateTime($data['last_activity']) : null,
            (bool)($data['auto_confirmacion'] ?? false),
            (bool)($data['auto_recordatorio'] ?? false),
            (bool)($data['auto_bienvenida'] ?? false)
        );
    }
    
    // Comportamientos
    public function conectar(string $phoneNumber): void {
        $this->phoneNumber = $phoneNumber;
        $this->status = 'connected';
        $this->qrCode = null;
        $this->lastActivity = new DateTime();
    }
    
    public function desconectar(): void {
        $this->status = 'disconnected';
        $this->qrCode = null;
        $this->lastActivity = new DateTime();
    }
    
    public function esperarQR(string $qrCode): void {
        $this->status = 'connecting';
        $this->qrCode = $qrCode;
        $this->lastActivity = new DateTime();
    }
    
    public function actualizarActividad(): void {
        $this->lastActivity = new DateTime();
    }
    
    public function configurarMensajesAutomaticos(
        bool $confirmacion,
        bool $recordatorio,
        bool $bienvenida
    ): void {
        $this->autoConfirmacion = $confirmacion;
        $this->autoRecordatorio = $recordatorio;
        $this->autoBienvenida = $bienvenida;
    }
    
    // Getters
    public function getUsuarioId(): int { return $this->usuarioId; }
    public function getPhoneNumber(): ?string { return $this->phoneNumber; }
    public function getStatus(): string { return $this->status; }
    public function getQrCode(): ?string { return $this->qrCode; }
    public function getLastActivity(): ?DateTime { return $this->lastActivity; }
    public function getAutoConfirmacion(): bool { return $this->autoConfirmacion; }
    public function getAutoRecordatorio(): bool { return $this->autoRecordatorio; }
    public function getAutoBienvenida(): bool { return $this->autoBienvenida; }
    
    public function estaConectado(): bool {
        return in_array($this->status, ['connected', 'ready']);
    }
    
    public function estaConectando(): bool {
        return in_array($this->status, ['connecting', 'waiting_qr']);
    }
    
    public function toArray(): array {
        return [
            'usuario_id' => $this->usuarioId,
            'phone_number' => $this->phoneNumber,
            'status' => $this->status,
            'qr_code' => $this->qrCode,
            'last_activity' => $this->lastActivity?->format('Y-m-d H:i:s'),
            'auto_confirmacion' => $this->autoConfirmacion,
            'auto_recordatorio' => $this->autoRecordatorio,
            'auto_bienvenida' => $this->autoBienvenida
        ];
    }
}