<?php
// src/Domain/Reserva/ReservaDomain.php

namespace ReservaBot\Domain\Reserva;

use ReservaBot\Domain\Configuracion\IConfiguracionRepository;
use DateTime;

class ReservaDomain {
    private IReservaRepository $reservaRepository;
    private IConfiguracionRepository $configuracionRepository;
    
    public function __construct(
        IReservaRepository $reservaRepository,
        IConfiguracionRepository $configuracionRepository
    ) {
        $this->reservaRepository = $reservaRepository;
        $this->configuracionRepository = $configuracionRepository;
    }
    
    /**
     * Obtiene reservas pendientes
     */
    public function obtenerReservasPendientes(int $usuarioId): array {
        return $this->reservaRepository->findByUsuarioAndEstado($usuarioId, 'pendiente');
    }
    
    /**
     * Obtiene reservas confirmadas
     */
    public function obtenerReservasConfirmadas(int $usuarioId): array {
        return $this->reservaRepository->findByUsuarioAndEstado($usuarioId, 'confirmada');
    }
    
    /**
     * Verifica si una fecha y hora están disponibles
     */
    public function verificarDisponibilidad(
        DateTime $fecha, 
        string $hora, 
        int $usuarioId,
        ?int $excluirReservaId = null
    ): bool {
        // 1. Verificar si el horario está dentro de las horas de negocio
        if (!$this->configuracionRepository->estaDisponible($fecha, $hora, $usuarioId)) {
            return false;
        }
        
        // 2. Verificar que no haya otra reserva activa en ese horario
        return !$this->reservaRepository->existeReservaActiva(
            $fecha, 
            $hora, 
            $usuarioId,
            $excluirReservaId
        );
    }
    
    /**
     * Crea una nueva reserva validando disponibilidad
     */
    public function crearReserva(
        string $nombre,
        string $telefono,
        DateTime $fecha,
        string $hora,
        int $usuarioId,
        string $mensaje = '',
        ?string $notasInternas = null
    ): Reserva {
        // Verificar disponibilidad
        if (!$this->verificarDisponibilidad($fecha, $hora, $usuarioId)) {
            throw new \DomainException('El horario seleccionado no está disponible');
        }
        
        // Crear entidad
        $reserva = Reserva::crear(
            $nombre,
            $telefono,
            $fecha,
            $hora,
            $usuarioId,
            $mensaje,
            $notasInternas
        );
        
        // Persistir
        return $this->reservaRepository->guardar($reserva);
    }
    
    /**
     * Confirma una reserva
     */
    public function confirmarReserva(int $id, int $usuarioId): Reserva {
        $reserva = $this->obtenerReserva($id, $usuarioId);
        
        $reserva->confirmar();
        
        return $this->reservaRepository->guardar($reserva);
    }
    
    /**
     * Cancela una reserva
     */
    public function cancelarReserva(int $id, int $usuarioId): Reserva {
        $reserva = $this->obtenerReserva($id, $usuarioId);
        
        $reserva->cancelar();
        
        return $this->reservaRepository->guardar($reserva);
    }
    
    /**
     * Modifica una reserva existente
     */
    public function modificarReserva(
        int $id,
        int $usuarioId,
        DateTime $nuevaFecha,
        string $nuevaHora,
        ?string $nuevoMensaje = null
    ): Reserva {
        $reserva = $this->obtenerReserva($id, $usuarioId);
        
        // Verificar disponibilidad del nuevo horario (excluyendo esta reserva)
        if (!$this->verificarDisponibilidad($nuevaFecha, $nuevaHora, $usuarioId, $id)) {
            throw new \DomainException('El nuevo horario no está disponible');
        }
        
        $reserva->modificar($nuevaFecha, $nuevaHora, $nuevoMensaje);
        
        return $this->reservaRepository->guardar($reserva);
    }
    
    /**
     * Obtiene una reserva verificando que pertenece al usuario
     */
    public function obtenerReserva(int $id, int $usuarioId): Reserva {
        $reserva = $this->reservaRepository->obtenerPorId($id, $usuarioId);
        
        if (!$reserva) {
            throw new \DomainException('Reserva no encontrada');
        }
        
        return $reserva;
    }
    
    /**
     * Obtiene reservas por fecha
     */
    public function obtenerReservasPorFecha(DateTime $fecha, int $usuarioId): array {
        return $this->reservaRepository->obtenerPorFecha($fecha, $usuarioId);
    }
    
    /**
     * Obtiene reservas por rango de fechas
     */
    public function obtenerReservasPorRango(DateTime $desde, DateTime $hasta, int $usuarioId): array {
        return $this->reservaRepository->obtenerPorRangoFechas($desde, $hasta, $usuarioId);
    }
    
    /**
     * Obtiene horas disponibles para una fecha
     */
    public function obtenerHorasDisponibles(DateTime $fecha, int $usuarioId): array {
        // Obtener todas las horas configuradas para ese día
        $todasLasHoras = $this->configuracionRepository->obtenerHorasDelDia($fecha, $usuarioId);
        
        // Obtener reservas existentes para esa fecha
        $reservasExistentes = $this->reservaRepository->obtenerPorFecha($fecha, $usuarioId);
        
        // Filtrar horas ocupadas
        $horasOcupadas = array_map(function($reserva) {
            return $reserva->getHora();
        }, array_filter($reservasExistentes, function($reserva) {
            return $reserva->getEstado()->esActiva();
        }));
        
        // Retornar solo horas disponibles
        return array_values(array_filter($todasLasHoras, function($hora) use ($horasOcupadas) {
            return !in_array($hora, $horasOcupadas);
        }));
    }
    
    /**
     * Elimina una reserva
     */
    public function eliminarReserva(int $id, int $usuarioId): void {
        // Verificar que existe
        $this->obtenerReserva($id, $usuarioId);
        
        $this->reservaRepository->eliminar($id, $usuarioId);
    }
}