<?php
// src/domain/reserva/ReservaDomain.php

namespace ReservaBot\Domain\Reserva;

use ReservaBot\Domain\Disponibilidad\IDisponibilidadRepository;
use DateTime;

class ReservaDomain {
    private IReservaRepository $reservaRepository;
    private IDisponibilidadRepository $disponibilidadRepository;
    
    public function __construct(
        IReservaRepository $reservaRepository,
        IDisponibilidadRepository $disponibilidadRepository
    ) {
        $this->reservaRepository = $reservaRepository;
        $this->disponibilidadRepository = $disponibilidadRepository;
    }
    
    /**
     * Obtiene todas las reservas de un usuario
     */
    public function obtenerTodasReservasUsuario(int $usuarioId): array {
        return $this->reservaRepository->obtenerPorUsuario($usuarioId);
    }
    
    /**
     * Obtiene reservas pendientes
     */
    public function obtenerReservasPendientes(int $usuarioId): array {
        return $this->reservaRepository->obtenerPorUsuarioYEstado($usuarioId, 'pendiente');
    }
    
    /**
     * Obtiene reservas confirmadas
     */
    public function obtenerReservasConfirmadas(int $usuarioId): array {
        return $this->reservaRepository->obtenerPorUsuarioYEstado($usuarioId, 'confirmada');
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
     * Verifica si una fecha y hora están disponibles
     */
    public function verificarDisponibilidad(
        DateTime $fecha, 
        string $hora, 
        int $usuarioId,
        ?int $excluirReservaId = null
    ): bool {
        // 1. Verificar si el horario está dentro de las horas de negocio
        if (!$this->disponibilidadRepository->estaDisponible($fecha, $hora, $usuarioId)) {
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
     * Verifica si un horario está dentro de las horas de negocio
     */
    public function verificarHorarioDisponible(DateTime $fecha, string $hora, int $usuarioId): bool {
        return $this->disponibilidadRepository->estaDisponible($fecha, $hora, $usuarioId);
    }
    
    /**
     * Obtiene horas disponibles para una fecha
     */
    public function obtenerHorasDisponibles(DateTime $fecha, int $usuarioId): array {
        // Obtener todas las horas configuradas para ese día
        $todasLasHoras = $this->disponibilidadRepository->obtenerHorasDelDia($fecha, $usuarioId);
        
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
     * Obtiene todas las horas del día según configuración
     */
    public function obtenerHorasDelDia(DateTime $fecha, int $usuarioId): array {
        return $this->disponibilidadRepository->obtenerHorasDelDia($fecha, $usuarioId);
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
     * Elimina una reserva
     */
    public function eliminarReserva(int $id, int $usuarioId): void {
        // Verificar que existe
        $this->obtenerReserva($id, $usuarioId);
        
        $this->reservaRepository->eliminar($id, $usuarioId);
    }

    /**
     * Modifica una reserva pública mediante token de acceso
     */
    public function modificarReservaPublica(
        int $reservaId,
        string $token,
        DateTime $nuevaFecha,
        string $nuevaHora
    ): Reserva {
        // Obtener reserva por ID y validar token
        $reserva = $this->reservaRepository->obtenerPorIdYToken($reservaId, $token);
        
        if (!$reserva) {
            throw new \DomainException('Reserva no encontrada o token inválido');
        }
        
        // Validar que no está cancelada
        if ($reserva->estaCancelada()) {
            throw new \DomainException('No se puede modificar una reserva cancelada');
        }
        
        // Validar plazo de 24h
        $fechaHoraReserva = clone $reserva->getFecha();
        $fechaHoraReserva->setTime(
            (int)substr($reserva->getHora(), 0, 2),
            (int)substr($reserva->getHora(), 3, 2)
        );
        
        $fechaLimite = clone $fechaHoraReserva;
        $fechaLimite->modify('-24 hours');
        
        if (new DateTime() >= $fechaLimite) {
            throw new \DomainException('No se puede modificar la reserva. El plazo límite ha expirado (24h antes de la cita)');
        }
        
        // Verificar disponibilidad del nuevo horario
        if (!$this->verificarDisponibilidad($nuevaFecha, $nuevaHora, $reserva->getUsuarioId(), $reservaId)) {
            throw new \DomainException('El nuevo horario no está disponible');
        }
        
        $reserva->modificar($nuevaFecha, $nuevaHora);
        
        return $this->reservaRepository->guardar($reserva);
    }

    /**
     * Cancela una reserva pública mediante token de acceso
     */
    public function cancelarReservaPublica(int $reservaId, string $token): Reserva {
        $reserva = $this->reservaRepository->obtenerPorIdYToken($reservaId, $token);
        
        if (!$reserva) {
            throw new \DomainException('Reserva no encontrada o token inválido');
        }
        
        if ($reserva->estaCancelada()) {
            throw new \DomainException('La reserva ya está cancelada');
        }
        
        // Validar plazo de 24h
        $fechaHoraReserva = clone $reserva->getFecha();
        $fechaHoraReserva->setTime(
            (int)substr($reserva->getHora(), 0, 2),
            (int)substr($reserva->getHora(), 3, 2)
        );
        
        $fechaLimite = clone $fechaHoraReserva;
        $fechaLimite->modify('-24 hours');
        
        if (new DateTime() >= $fechaLimite) {
            throw new \DomainException('No se puede cancelar la reserva. El plazo límite ha expirado (24h antes de la cita)');
        }
        
        $reserva->cancelar();
        
        return $this->reservaRepository->guardar($reserva);
    }
}