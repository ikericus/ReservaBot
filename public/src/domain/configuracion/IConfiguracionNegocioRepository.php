<?php
// src/domain/configuracion/IConfiguracionNegocioRepository.php

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
}