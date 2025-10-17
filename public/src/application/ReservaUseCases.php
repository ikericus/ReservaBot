<?php
// public/src/application/RreservaUseCases.php

namespace ReservaBot\Application\Reserva;

use ReservaBot\Domain\Reserva\ReservaDomain;
use ReservaBot\Domain\Reserva\Reserva;
use DateTime;

class ReservaUseCases {
    private ReservaDomain $reservaDomain;
    
    public function __construct(ReservaDomain $reservaDomain) {
        $this->reservaDomain = $reservaDomain;
    }
    
    public function crearReserva(
        string $nombre,
        string $telefono,
        DateTime $fecha,
        string $hora,
        int $usuarioId,
        string $mensaje = '',
        ?string $notasInternas = null
    ): Reserva {
        return $this->reservaDomain->crearReserva(
            $nombre,
            $telefono,
            $fecha,
            $hora,
            $usuarioId,
            $mensaje,
            $notasInternas
        );
    }
    
    public function confirmarReserva(int $id, int $usuarioId): Reserva {
        return $this->reservaDomain->confirmarReserva($id, $usuarioId);
    }
    
    public function cancelarReserva(int $id, int $usuarioId): Reserva {
        return $this->reservaDomain->cancelarReserva($id, $usuarioId);
    }
    
    public function modificarReserva(
        int $id,
        int $usuarioId,
        string $nombre,
        string $telefono,
        DateTime $fecha,
        string $hora,
        string $mensaje = ''
    ): Reserva {
        $reserva = $this->reservaDomain->obtenerReserva($id, $usuarioId);
        
        if ($reserva->getFecha()->format('Y-m-d') !== $fecha->format('Y-m-d') 
            || $reserva->getHora() !== $hora) {
            $this->reservaDomain->modificarReserva($id, $usuarioId, $fecha, $hora, $mensaje);
        }
        
        return $this->reservaDomain->obtenerReserva($id, $usuarioId);
    }
    
    public function obtenerReserva(int $id, int $usuarioId): Reserva {
        return $this->reservaDomain->obtenerReserva($id, $usuarioId);
    }
    
    public function obtenerReservasPorFecha(DateTime $fecha, int $usuarioId): array {
        return $this->reservaDomain->obtenerReservasPorFecha($fecha, $usuarioId);
    }
    
    public function obtenerReservasPorMes(int $mes, int $anio, int $usuarioId): array {
        $desde = new DateTime("$anio-$mes-01");
        $hasta = new DateTime($desde->format('Y-m-t'));
        
        return $this->reservaDomain->obtenerReservasPorRango($desde, $hasta, $usuarioId);
    }
    
    public function obtenerHorasDisponibles(DateTime $fecha, int $usuarioId): array {
        return $this->reservaDomain->obtenerHorasDisponibles($fecha, $usuarioId);
    }
    
    public function verificarDisponibilidad(
        DateTime $fecha, 
        string $hora, 
        int $usuarioId,
        ?int $excluirReservaId = null
    ): bool {
        return $this->reservaDomain->verificarDisponibilidad(
            $fecha, 
            $hora, 
            $usuarioId,
            $excluirReservaId
        );
    }
    
    public function eliminarReserva(int $id, int $usuarioId): void {
        $this->reservaDomain->eliminarReserva($id, $usuarioId);
    }
}