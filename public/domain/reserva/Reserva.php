<?php
// domain/reserva/Reserva.php

namespace ReservaBot\Domain\Reserva;

use ReservaBot\Domain\Shared\Telefono;
use DateTime;

class Reserva {
    private ?int $id;
    private string $nombre;
    private Telefono $telefono;
    private ?string $whatsappId;
    private DateTime $fecha;
    private string $hora; // HH:MM formato
    private string $mensaje;
    private ?string $notasInternas;
    private EstadoReserva $estado;
    private int $usuarioId;
    private DateTime $creadaEn;
    private ?string $email;
    private ?string $accessToken;
    private ?DateTime $tokenExpires;
    private ?int $formularioId;
    
    private function __construct(
        ?int $id,
        string $nombre,
        Telefono $telefono,
        ?string $whatsappId,
        DateTime $fecha,
        string $hora,
        string $mensaje,
        ?string $notasInternas,
        EstadoReserva $estado,
        int $usuarioId,
        ?DateTime $creadaEn = null,
        ?string $email = null,
        ?string $accessToken = null,
        ?DateTime $tokenExpires = null,
        ?int $formularioId = null
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->telefono = $telefono;
        $this->whatsappId = $whatsappId;
        $this->fecha = $fecha;
        $this->hora = $hora;
        $this->mensaje = $mensaje;
        $this->notasInternas = $notasInternas;
        $this->estado = $estado;
        $this->usuarioId = $usuarioId;
        $this->creadaEn = $creadaEn ?? new DateTime();
        $this->email = $email;
        $this->accessToken = $accessToken;
        $this->tokenExpires = $tokenExpires;
        $this->formularioId = $formularioId;
    }
    
    // Factory para crear nueva reserva
    public static function crear(
        string $nombre,
        string $telefono,
        DateTime $fecha,
        string $hora,
        int $usuarioId,
        string $mensaje = '',
        ?string $notasInternas = null
    ): self {
        // Validaciones
        self::validarNombre($nombre);
        self::validarHora($hora);
        self::validarFecha($fecha);
        
        $telefonoVO = new Telefono($telefono);
        
        return new self(
            null,
            trim($nombre),
            $telefonoVO,
            $telefonoVO->normalizarParaWhatsApp(),
            $fecha,
            $hora,
            trim($mensaje),
            $notasInternas,
            EstadoReserva::PENDIENTE,
            $usuarioId
        );
    }
    
    // Factory para crear reserva pública con email
    public static function crearPublica(
        string $nombre,
        string $telefono,
        string $email,
        DateTime $fecha,
        string $hora,
        int $usuarioId,
        string $mensaje = '',
        ?int $formularioId = null
    ): self {
        // Validaciones
        self::validarNombre($nombre);
        self::validarHora($hora);
        self::validarFecha($fecha);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email no válido');
        }
        
        $telefonoVO = new Telefono($telefono);
        
        // Generar token de acceso
        $accessToken = bin2hex(random_bytes(32));
        $tokenExpires = new DateTime('+30 days');
        
        return new self(
            null,
            trim($nombre),
            $telefonoVO,
            $telefonoVO->normalizarParaWhatsApp(),
            $fecha,
            $hora,
            trim($mensaje),
            null, // notas internas
            EstadoReserva::PENDIENTE,
            $usuarioId,
            null, // creadaEn
            $email,
            $accessToken,
            $tokenExpires,
            $formularioId
        );
    }
    
    // Factory para reconstruir desde BD
    public static function fromDatabase(array $data): self {
        return new self(
            (int)$data['id'],
            $data['nombre'],
            new Telefono($data['telefono']),
            $data['whatsapp_id'] ?? null,
            new DateTime($data['fecha']),
            substr($data['hora'], 0, 5), // HH:MM
            $data['mensaje'] ?? '',
            $data['notas_internas'] ?? null,
            EstadoReserva::from($data['estado']),
            (int)$data['usuario_id'],
            isset($data['created_at']) ? new DateTime($data['created_at']) : null,
            $data['email'] ?? null,
            $data['access_token'] ?? null,
            isset($data['token_expires']) ? new DateTime($data['token_expires']) : null,
            isset($data['formulario_id']) ? (int)$data['formulario_id'] : null
        );
    }
    
    // Comportamientos de dominio
    public function confirmar(): void {
        if ($this->estado === EstadoReserva::CANCELADA) {
            throw new \DomainException('No se puede confirmar una reserva cancelada');
        }
        $this->estado = EstadoReserva::CONFIRMADA;
    }
    
    public function cancelar(): void {
        $this->estado = EstadoReserva::CANCELADA;
    }
    
    public function modificar(DateTime $nuevaFecha, string $nuevaHora, ?string $nuevoMensaje = null): void {
        self::validarHora($nuevaHora);
        self::validarFecha($nuevaFecha);
        
        $this->fecha = $nuevaFecha;
        $this->hora = $nuevaHora;
        
        if ($nuevoMensaje !== null) {
            $this->mensaje = trim($nuevoMensaje);
        }
    }
    
    public function agregarNotaInterna(string $nota): void {
        $this->notasInternas = $this->notasInternas 
            ? $this->notasInternas . "\n" . $nota 
            : $nota;
    }
    
    // Getters
    public function getId(): ?int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function getTelefono(): Telefono { return $this->telefono; }
    public function getWhatsappId(): ?string { return $this->whatsappId; }
    public function getFecha(): DateTime { return $this->fecha; }
    public function getHora(): string { return $this->hora; }
    public function getHoraCompleta(): string { return $this->hora . ':00'; } // Para BD
    public function getMensaje(): string { return $this->mensaje; }
    public function getNotasInternas(): ?string { return $this->notasInternas; }
    public function getEstado(): EstadoReserva { return $this->estado; }
    public function getUsuarioId(): int { return $this->usuarioId; }
    public function getCreadaEn(): DateTime { return $this->creadaEn; }
    public function getEmail(): ?string { return $this->email; }
    public function getAccessToken(): ?string { return $this->accessToken; }
    public function getTokenExpires(): ?DateTime { return $this->tokenExpires; }
    public function getFormularioId(): ?int { return $this->formularioId; }
    
    public function estaConfirmada(): bool {
        return $this->estado === EstadoReserva::CONFIRMADA;
    }
    
    public function estaPendiente(): bool {
        return $this->estado === EstadoReserva::PENDIENTE;
    }
    
    public function estaCancelada(): bool {
        return $this->estado === EstadoReserva::CANCELADA;
    }
    
    // Validaciones privadas
    private static function validarNombre(string $nombre): void {
        if (empty(trim($nombre))) {
            throw new \InvalidArgumentException('El nombre no puede estar vacío');
        }
    }
    
    private static function validarHora(string $hora): void {
        if (!preg_match('/^\d{2}:\d{2}$/', $hora)) {
            throw new \InvalidArgumentException('Formato de hora inválido. Use HH:MM');
        }
    }
    
    private static function validarFecha(DateTime $fecha): void {
        $hoy = new DateTime('today');
        if ($fecha < $hoy) {
            throw new \InvalidArgumentException('La fecha no puede ser anterior a hoy');
        }
    }
    
    // Convertir a array para BD
    public function toArray(): array {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'telefono' => $this->telefono->getValue(),
            'whatsapp_id' => $this->whatsappId,
            'fecha' => $this->fecha->format('Y-m-d'),
            'hora' => $this->getHoraCompleta(),
            'mensaje' => $this->mensaje,
            'notas_internas' => $this->notasInternas,
            'estado' => $this->estado->value,
            'usuario_id' => $this->usuarioId,
            'email' => $this->email,
            'access_token' => $this->accessToken,
            'token_expires' => $this->tokenExpires?->format('Y-m-d H:i:s'),
            'formulario_id' => $this->formularioId
        ];
    }
}