<?php
// src/domain/formulario/IFormularioRespository.php

namespace ReservaBot\Domain\Formulario;

/**
 * Interfaz para el repositorio de Formularios
 * 
 * Define los métodos de persistencia necesarios para
 * la gestión de formularios públicos de reservas.
 */
interface IFormularioRepository
{
    /**
     * Guardar un nuevo formulario en la base de datos
     * 
     * @param Formulario $formulario
     * @return Formulario El formulario con el ID asignado
     * @throws \PDOException Si hay error en la base de datos
     */
    public function guardar(Formulario $formulario): Formulario;

    /**
     * Actualizar un formulario existente
     * 
     * @param Formulario $formulario
     * @throws \PDOException Si hay error en la base de datos
     */
    public function actualizar(Formulario $formulario): void;

    /**
     * Obtener un formulario por su slug (para acceso público)
     * 
     * @param string $slug
     * @return Formulario|null
     */
    public function obtenerPorSlug(string $slug): ?Formulario;

    /**
     * Obtener un formulario por ID y usuario (para admin)
     * 
     * @param int $id
     * @param int $usuarioId
     * @return Formulario|null
     */
    public function obtenerPorId(int $id, int $usuarioId): ?Formulario;

    /**
     * Obtener todos los formularios de un usuario
     * 
     * @param int $usuarioId
     * @return Formulario[] Array de entidades Formulario
     */
    public function obtenerPorUsuario(int $usuarioId): array;

    /**
     * Eliminar un formulario
     * 
     * @param int $id
     * @param int $usuarioId
     * @return bool True si se eliminó, false si no existía
     * @throws \PDOException Si hay error en la base de datos
     */
    public function eliminar(int $id, int $usuarioId): bool;

    /**
     * Verificar si un slug ya existe
     * 
     * @param string $slug
     * @return bool
     */
    public function existeSlug(string $slug): bool;

    /**
     * Obtener estadísticas de un formulario
     * 
     * @param int $formularioId
     * @return array [
     *   'total_reservas' => int,
     *   'reservas_pendientes' => int,
     *   'reservas_confirmadas' => int,
     *   'reservas_canceladas' => int,
     *   'ultima_reserva' => ?string (fecha)
     * ]
     */
    public function obtenerEstadisticas(int $formularioId): array;
}