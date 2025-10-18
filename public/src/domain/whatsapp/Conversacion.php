<?php
// src/domain/whatsapp/Conversacion.php

namespace ReservaBot\Domain\WhatsApp;

use DateTime;

class Conversacion {
    private ?int $id;
    private int $usuarioId;
    private string $whatsappId;
    private ?string $nombre;
    private string $telefono;
    private string $ultimoMensaje;
    private DateTime $ultimaActividad;
    private bool $noLeido;
    
    private function __construct(
        ?int $id,
        int $usuarioId,
        string $whatsappId,
        ?string $nombre,
        string $telefono,
        string $ultimoMensaje,
        DateTime $ultimaActividad,
        bool $noLeido
    ) {
        $this->id = $id;
        $this->usuarioId = $usuarioId;
        $this->whatsappId = $whatsappId;
        $this->nombre = $nombre;
        $this->telefono = $telefono;
        $this->ultimoMensaje = $ultimoMensaje;
        $this->ultimaActividad = $ultimaActividad;
        $this->noLeido = $noLeido;
    }
    
    public static function crear(
        int $usuarioId,
        string $whatsappId,
        ?string $nombre,
        string $telefono,
        string $mensaje
    ): self {
        return new self(
            null,
            $usuarioId,
            $whatsappId,
            $nombre,
            $telefono,
            $mensaje,
            new DateTime(),
            true
        );
    }
    
    public static function fromDatabase(array $data): self {
        return new self(
            (int)$data['id'],
            (int)$data['usuario_id'],
            $data['whatsapp_id'],
            $data['nombre'] ?? null,
            $data['telefono'],
            $data['ultimo_mensaje'],
            new DateTime($data['ultima_actividad']),
            (bool)($data['no_leido'] ?? false)
        );
    }
    
    // Comportamientos
    public function actualizarMensaje(string $mensaje): void {
        $this->ultimoMensaje = $mensaje;
        $this->ultimaActividad = new DateTime();
        $this->noLeido = true;
    }
    
    public function marcarComoLeido(): void {
        $this->noLeido = false;
    }
    
    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUsuarioId(): int { return $this->usuarioId; }
    public function getWhatsappId(): string { return $this->whatsappId; }
    public function getNombre(): ?string { return $this->nombre; }
    public function getTelefono(): string { return $this->telefono; }
    public function getUltimoMensaje(): string { return $this->ultimoMensaje; }
    public function getUltimaActividad(): DateTime { return $this->ultimaActividad; }
    public function isNoLeido(): bool { return $this->noLeido; }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuarioId,
            'whatsapp_id' => $this->whatsappId,
            'nombre' => $this->nombre,
            'telefono' => $this->telefono,
            'ultimo_mensaje' => $this->ultimoMensaje,
            'ultima_actividad' => $this->ultimaActividad->format('Y-m-d H:i:s'),
            'no_leido' => $this->noLeido
        ];
    }
}