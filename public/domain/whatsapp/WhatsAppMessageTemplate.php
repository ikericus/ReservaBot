<?php
// domain/whatsapp/WhatsAppMessageTemplate.php

namespace ReservaBot\Domain\WhatsApp;

use DateTime;

class WhatsAppMessageTemplate {
    private ?int $id;
    private int $usuarioId;
    private string $tipoMensaje; // 'confirmacion' | 'recordatorio' | 'bienvenida'
    private string $mensaje;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    
    // Mensajes por defecto
    private const MENSAJES_DEFAULT = [
        'confirmacion' => "¡Hola {nombre_cliente}! ✅\n\nTu reserva ha sido confirmada:\n📅 Fecha: {fecha}\n⏰ Hora: {hora}\n\n¡Te esperamos en {negocio}!",
        'recordatorio' => "¡Hola {nombre_cliente}! 👋\n\nTe recordamos tu cita de mañana:\n📅 Fecha: {fecha}\n⏰ Hora: {hora}\n\n¡Nos vemos en {negocio}!",
        'bienvenida' => "¡Hola! 👋 Bienvenido/a a {negocio}.\n\nEnseguida leeré tu mensaje."
    ];
    
    private function __construct(
        ?int $id,
        int $usuarioId,
        string $tipoMensaje,
        string $mensaje,
        DateTime $createdAt,
        DateTime $updatedAt
    ) {
        $this->id = $id;
        $this->usuarioId = $usuarioId;
        $this->tipoMensaje = $tipoMensaje;
        $this->mensaje = $mensaje;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
    
    public static function crear(int $usuarioId, string $tipoMensaje, ?string $mensaje = null): self {
        if (!in_array($tipoMensaje, ['confirmacion', 'recordatorio', 'bienvenida'])) {
            throw new \InvalidArgumentException('Tipo de mensaje inválido');
        }
        
        $now = new DateTime();
        $mensajeFinal = $mensaje ?? self::MENSAJES_DEFAULT[$tipoMensaje];
        
        return new self(
            null,
            $usuarioId,
            $tipoMensaje,
            $mensajeFinal,
            $now,
            $now
        );
    }
    
    public static function fromDatabase(array $data): self {
        return new self(
            (int)$data['id'],
            (int)$data['usuario_id'],
            $data['tipo_mensaje'],
            $data['mensaje'],
            new DateTime($data['created_at']),
            new DateTime($data['updated_at'])
        );
    }
    
    // Comportamientos
    public function actualizarMensaje(string $nuevoMensaje): void {
        $this->mensaje = $nuevoMensaje;
        $this->updatedAt = new DateTime();
    }
    
    /**
     * Reemplaza los placeholders del mensaje con datos reales
     */
    public function procesarMensaje(array $datos): string {
        $mensaje = $this->mensaje;
        
        // Placeholders disponibles
        $placeholders = [
            '{nombre_cliente}' => $datos['nombre_cliente'] ?? '',
            '{fecha}' => $datos['fecha'] ?? '',
            '{hora}' => $datos['hora'] ?? '',
            '{duracion}' => $datos['duracion'] ?? '',
            '{negocio}' => $datos['negocio'] ?? ''
        ];
        
        foreach ($placeholders as $placeholder => $valor) {
            $mensaje = str_replace($placeholder, $valor, $mensaje);
        }
        
        return $mensaje;
    }
    
    /**
     * Obtiene mensaje por defecto para un tipo
     */
    public static function getMensajeDefault(string $tipoMensaje): ?string {
        return self::MENSAJES_DEFAULT[$tipoMensaje] ?? null;
    }
    
    /**
     * Obtiene todos los placeholders disponibles
     */
    public static function getPlaceholdersDisponibles(): array {
        return [
            'nombre_cliente' => 'Nombre del cliente',
            'fecha' => 'Fecha de la cita (formato: dd/mm/aaaa)',
            'hora' => 'Hora de la cita (formato: HH:MM)',
            'duracion' => 'Duración de la cita',
            'negocio' => 'Nombre del negocio'
        ];
    }
    
    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUsuarioId(): int { return $this->usuarioId; }
    public function getTipoMensaje(): string { return $this->tipoMensaje; }
    public function getMensaje(): string { return $this->mensaje; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): DateTime { return $this->updatedAt; }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuarioId,
            'tipo_mensaje' => $this->tipoMensaje,
            'mensaje' => $this->mensaje,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }
}