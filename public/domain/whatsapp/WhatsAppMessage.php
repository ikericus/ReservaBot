<?php
// domain/whatsapp/WhatsAppMessage.php

namespace ReservaBot\Domain\WhatsApp;

use DateTime;

class WhatsAppMessage {
    private ?int $id;
    private int $usuarioId;
    private string $messageId;
    private string $phoneNumber;
    private string $messageText;
    private string $direction; // 'incoming' | 'outgoing'
    private bool $isGroup;
    private bool $hasMedia;
    private string $status; // 'pending' | 'sent' | 'delivered' | 'read' | 'failed'
    private DateTime $timestampReceived;
    private ?DateTime $timestampSent;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    
    private function __construct(
        ?int $id,
        int $usuarioId,
        string $messageId,
        string $phoneNumber,
        string $messageText,
        string $direction,
        bool $isGroup,
        bool $hasMedia,
        string $status,
        DateTime $timestampReceived,
        ?DateTime $timestampSent,
        DateTime $createdAt,
        DateTime $updatedAt
    ) {
        $this->id = $id;
        $this->usuarioId = $usuarioId;
        $this->messageId = $messageId;
        $this->phoneNumber = $phoneNumber;
        $this->messageText = $messageText;
        $this->direction = $direction;
        $this->isGroup = $isGroup;
        $this->hasMedia = $hasMedia;
        $this->status = $status;
        $this->timestampReceived = $timestampReceived;
        $this->timestampSent = $timestampSent;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
    
    public static function crearEntrante(
        int $usuarioId,
        string $messageId,
        string $phoneNumber,
        string $messageText,
        bool $isGroup = false,
        bool $hasMedia = false
    ): self {
        $now = new DateTime();
        return new self(
            null,
            $usuarioId,
            $messageId,
            $phoneNumber,
            $messageText,
            'incoming',
            $isGroup,
            $hasMedia,
            'read',
            $now,
            null,
            $now,
            $now
        );
    }
    
    public static function crearSaliente(
        int $usuarioId,
        string $messageId,
        string $phoneNumber,
        string $messageText,
        bool $hasMedia = false
    ): self {
        $now = new DateTime();
        return new self(
            null,
            $usuarioId,
            $messageId,
            $phoneNumber,
            $messageText,
            'outgoing',
            false,
            $hasMedia,
            'pending',
            $now,
            null,
            $now,
            $now
        );
    }
    
    public static function fromDatabase(array $data): self {
        return new self(
            (int)$data['id'],
            (int)$data['usuario_id'],
            $data['message_id'],
            $data['phone_number'],
            $data['message_text'],
            $data['direction'],
            (bool)$data['is_group'],
            (bool)$data['has_media'],
            $data['status'],
            new DateTime($data['timestamp_received']),
            $data['timestamp_sent'] ? new DateTime($data['timestamp_sent']) : null,
            new DateTime($data['created_at']),
            new DateTime($data['updated_at'])
        );
    }
    
    // Comportamientos
    public function marcarComoEnviado(): void {
        $this->status = 'sent';
        $this->timestampSent = new DateTime();
        $this->updatedAt = new DateTime();
    }
    
    public function marcarComoEntregado(): void {
        $this->status = 'delivered';
        $this->updatedAt = new DateTime();
    }
    
    public function marcarComoLeido(): void {
        $this->status = 'read';
        $this->updatedAt = new DateTime();
    }
    
    public function marcarComoFallido(): void {
        $this->status = 'failed';
        $this->updatedAt = new DateTime();
    }
    
    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUsuarioId(): int { return $this->usuarioId; }
    public function getMessageId(): string { return $this->messageId; }
    public function getPhoneNumber(): string { return $this->phoneNumber; }
    public function getMessageText(): string { return $this->messageText; }
    public function getDirection(): string { return $this->direction; }
    public function isGroup(): bool { return $this->isGroup; }
    public function hasMedia(): bool { return $this->hasMedia; }
    public function getStatus(): string { return $this->status; }
    public function getTimestampReceived(): DateTime { return $this->timestampReceived; }
    public function getTimestampSent(): ?DateTime { return $this->timestampSent; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): DateTime { return $this->updatedAt; }
    
    public function isEntrante(): bool { return $this->direction === 'incoming'; }
    public function isSaliente(): bool { return $this->direction === 'outgoing'; }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuarioId,
            'message_id' => $this->messageId,
            'phone_number' => $this->phoneNumber,
            'message_text' => $this->messageText,
            'direction' => $this->direction,
            'is_group' => $this->isGroup,
            'has_media' => $this->hasMedia,
            'status' => $this->status,
            'timestamp_received' => $this->timestampReceived->format('Y-m-d H:i:s'),
            'timestamp_sent' => $this->timestampSent?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }
}