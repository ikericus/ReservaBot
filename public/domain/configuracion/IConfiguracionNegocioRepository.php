<?php
// domain/configuracion/IConfiguracionNegocioRepository.php

namespace ReservaBot\Domain\Configuracion;

interface IConfiguracionNegocioRepository {
    /**
     * Obtiene todas las configuraciones como array [clave => valor]
     */
    public function obtenerTodas(int $usuarioId): array;
    
    /**
     * Obtiene una configuración específica
     */
    public function obtener(string $clave, int $usuarioId): ?string;
    
    /**
     * Actualiza una configuración
     */
    public function actualizar(string $clave, string $valor, int $usuarioId): void;
    
    /**
     * Actualiza múltiples configuraciones
     */
    public function actualizarVarias(array $configuraciones, int $usuarioId): void;

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