<?php
// domain/cliente/ClienteDomain.php

namespace ReservaBot\Domain\Cliente;

use ReservaBot\Domain\Reserva\IReservaRepository;

class ClienteDomain {
    private IClienteRepository $clienteRepository;
    private IReservaRepository $reservaRepository;
    
    public function __construct(
        IClienteRepository $clienteRepository,
        IReservaRepository $reservaRepository
    ) {
        $this->clienteRepository = $clienteRepository;
        $this->reservaRepository = $reservaRepository;
    }
    
    /**
     * Obtiene estadísticas de cliente con sus reservas
     */
    public function obtenerDetalleCliente(string $telefono, int $usuarioId): array {
        $stats = $this->clienteRepository->obtenerEstadisticasCliente($telefono, $usuarioId);
        
        if (!$stats) {
            throw new \DomainException('Cliente no encontrado');
        }
        
        // Obtener historial de reservas
        $reservas = $this->reservaRepository->obtenerPorTelefono($telefono, $usuarioId);
        
        return [
            'cliente' => $stats,
            'reservas' => array_map(fn($r) => $r->toArray(), $reservas)
        ];
    }
    
    /**
     * Lista clientes con búsqueda y paginación
     */
    public function listarClientes(
        int $usuarioId, 
        string $search = '', 
        int $page = 1, 
        int $perPage = 20
    ): array {
        $total = $this->clienteRepository->contarClientesUnicos($usuarioId, $search);
        $clientes = $this->clienteRepository->obtenerClientesPaginados(
            $usuarioId, 
            $search, 
            $page, 
            $perPage
        );
        
        return [
            'clientes' => array_map(fn($c) => $c->toArray(), $clientes),
            'total' => $total,
            'pagina' => $page,
            'por_pagina' => $perPage,
            'total_paginas' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Busca clientes por teléfono (autocompletado)
     * Si no encuentra por teléfono, busca por nombre
     */
    public function buscarPorTelefono(string $telefono, int $usuarioId, int $limite = 10): array {
        if (strlen($telefono) < 3) {
            throw new \InvalidArgumentException('Teléfono demasiado corto');
        }
        
        // Buscar por teléfono primero
        $clientes = $this->clienteRepository->buscarPorTelefonoConEstadisticas(
            $telefono, 
            $usuarioId, 
            $limite
        );
        
        // Si no hay resultados, buscar por nombre
        if (empty($clientes)) {
            $clientes = $this->clienteRepository->buscarPorNombreConEstadisticas(
                $telefono, 
                $usuarioId, 
                min(5, $limite)
            );
        }
        
        return array_map(function($cliente) {
            $arr = $cliente->toArray();
            // Formatear última reserva para API
            if ($arr['ultima_reserva']) {
                $arr['last_reserva'] = date('d/m/Y', strtotime($arr['ultima_reserva']));
            } else {
                $arr['last_reserva'] = null;
            }
            return $arr;
        }, $clientes);
    }
}