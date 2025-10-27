<?php
// domain/cliente/Cliente.php

namespace ReservaBot\Domain\Cliente;

class Cliente {
    private string $telefono;
    private string $nombre;
    private int $totalReservas;
    private int $confirmadas;
    private int $pendientes;
    private int $canceladas;
    private ?\DateTime $ultimaReserva;
    private \DateTime $primerContacto;
    private \DateTime $ultimoContacto;
    
    public function __construct(
        string $telefono,
        string $nombre,
        int $totalReservas,
        int $confirmadas,
        int $pendientes,
        int $canceladas,
        ?\DateTime $ultimaReserva,
        \DateTime $primerContacto,
        \DateTime $ultimoContacto
    ) {
        $this->telefono = $telefono;
        $this->nombre = $nombre;
        $this->totalReservas = $totalReservas;
        $this->confirmadas = $confirmadas;
        $this->pendientes = $pendientes;
        $this->canceladas = $canceladas;
        $this->ultimaReserva = $ultimaReserva;
        $this->primerContacto = $primerContacto;
        $this->ultimoContacto = $ultimoContacto;
    }
    
    // Getters
    public function getTelefono(): string { return $this->telefono; }
    public function getNombre(): string { return $this->nombre; }
    public function getTotalReservas(): int { return $this->totalReservas; }
    public function getConfirmadas(): int { return $this->confirmadas; }
    public function getPendientes(): int { return $this->pendientes; }
    public function getCanceladas(): int { return $this->canceladas; }
    public function getUltimaReserva(): ?\DateTime { return $this->ultimaReserva; }
    public function getPrimerContacto(): \DateTime { return $this->primerContacto; }
    public function getUltimoContacto(): \DateTime { return $this->ultimoContacto; }
    
    public function toArray(): array {
        return [
            'telefono' => $this->telefono,
            'ultimo_nombre' => $this->nombre, // Legacy compatibility
            'total_reservas' => $this->totalReservas,
            'reservas_confirmadas' => $this->confirmadas,
            'reservas_pendientes' => $this->pendientes,
            'reservas_canceladas' => $this->canceladas,
            'ultima_reserva' => $this->ultimaReserva?->format('Y-m-d'),
            'primer_contacto' => $this->primerContacto->format('Y-m-d H:i:s'),
            'ultimo_contacto' => $this->ultimoContacto->format('Y-m-d H:i:s'),
        ];
    }
}