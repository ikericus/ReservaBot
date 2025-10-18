// src/domain/cliente/ClienteDomain.php

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
}