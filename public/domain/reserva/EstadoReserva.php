<?php
// Domain/Reserva/EstadoReserva.php

namespace ReservaBot\Domain\Reserva;

enum EstadoReserva: string {
    case PENDIENTE = 'pendiente';
    case CONFIRMADA = 'confirmada';
    case CANCELADA = 'cancelada';
    case COMPLETADA = 'completada';
    
    public function esActiva(): bool {
        return in_array($this, [self::PENDIENTE, self::CONFIRMADA]);
    }
    
    public function getColor(): string {
        return match($this) {
            self::PENDIENTE => 'amber',
            self::CONFIRMADA => 'green',
            self::CANCELADA => 'red',
            self::COMPLETADA => 'gray'
        };
    }
    
    public function getLabel(): string {
        return match($this) {
            self::PENDIENTE => 'Pendiente',
            self::CONFIRMADA => 'Confirmada',
            self::CANCELADA => 'Cancelada',
            self::COMPLETADA => 'Completada'
        };
    }
}