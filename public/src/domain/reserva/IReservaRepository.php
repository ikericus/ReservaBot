<?php
// src/Domain/Reserva/IReservaRepository.php

namespace ReservaBot\Domain\Reserva;

use DateTime;

interface IReservaRepository {
    /**
     * Guarda una reserva (crear o actualizar)
     */
    public function guardar(Reserva $reserva): Reserva;
    
    /**
     * Obtiene una reserva por ID
     */
    public function obtenerPorId(int $id, int $usuarioId): ?Reserva;
    
    /**
     * Obtiene todas las reservas de un usuario
     */
    public function obtenerPorUsuario(int $usuarioId): array;
        
    /**
     * Obtiene todas las reservas de un usuario y estado
     */
    public function obtenerPorUsuarioYEstado(int $usuarioId, string $estado): array
    /**
     * Obtiene reservas por fecha
     */
    public function obtenerPorFecha(DateTime $fecha, int $usuarioId): array;
    
    /**
     * Obtiene reservas por rango de fechas
     */
    public function obtenerPorRangoFechas(DateTime $desde, DateTime $hasta, int $usuarioId): array;
    
    /**
     * Verifica si existe una reserva en fecha y hora específica (solo activas)
     */
    public function existeReservaActiva(DateTime $fecha, string $hora, int $usuarioId, ?int $excluirId = null): bool;
    
    /**
     * Obtiene reservas por WhatsApp ID
     */
    public function obtenerPorWhatsappId(string $whatsappId, int $usuarioId): array;
    
    /**
     * Elimina una reserva
     */
    public function eliminar(int $id, int $usuarioId): void;
    
    /**
     * Obtiene estadísticas de reservas
     */
    public function obtenerEstadisticas(int $usuarioId, ?DateTime $desde = null, ?DateTime $hasta = null): array;
}