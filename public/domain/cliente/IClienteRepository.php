<?php
// domain/cliente/IClienteRepository.php

namespace ReservaBot\Domain\Cliente;

interface IClienteRepository {
    /**
     * Obtiene estadísticas de un cliente por teléfono
     */
    public function obtenerEstadisticasCliente(string $telefono, int $usuarioId): ?Cliente;
    
    /**
     * Cuenta clientes únicos con búsqueda opcional
     */
    public function contarClientesUnicos(int $usuarioId, string $search = ''): int;
    
    /**
     * Obtiene lista paginada de clientes
     */
    public function obtenerClientesPaginados(
        int $usuarioId, 
        string $search, 
        int $page, 
        int $perPage
    ): array;
    
    /**
     * Busca clientes por teléfono con estadísticas
     */
    public function buscarPorTelefonoConEstadisticas(
        string $telefono, 
        int $usuarioId, 
        int $limite = 10
    ): array;
    
    /**
     * Busca clientes por nombre con estadísticas
     */
    public function buscarPorNombreConEstadisticas(
        string $nombre, 
        int $usuarioId, 
        int $limite = 5
    ): array;
}