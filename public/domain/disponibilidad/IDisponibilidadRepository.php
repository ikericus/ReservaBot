<?php
// domain/disponibilidad/IDisponibilidadRepository.php

namespace ReservaBot\Domain\Disponibilidad;

use DateTime;

interface IDisponibilidadRepository {
    /**
     * Verifica si una fecha y hora están disponibles según configuración de horarios
     */
    public function estaDisponible(DateTime $fecha, string $hora, int $usuarioId): bool;
    
    /**
     * Obtiene todas las horas disponibles para un día específico
     * Retorna array de strings en formato HH:MM
     */
    public function obtenerHorasDelDia(DateTime $fecha, int $usuarioId): array;
    
    /**
     * Obtiene la configuración de horario para un día específico
     * Retorna ['activo' => bool, 'ventanas' => [['inicio' => 'HH:MM', 'fin' => 'HH:MM']]]
     */
    public function obtenerHorarioDia(string $dia, int $usuarioId): array;
    
    /**
     * Obtiene el intervalo de tiempo entre citas (en minutos)
     */
    public function obtenerIntervalo(int $usuarioId): int;
}