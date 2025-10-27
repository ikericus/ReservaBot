<?php
// domain/configuracion/ConfiguracionDomain.php

namespace ReservaBot\Domain\Configuracion;

class ConfiguracionDomain {
    private IConfiguracionNegocioRepository $repository;
    
    public function __construct(IConfiguracionNegocioRepository $repository) {
        $this->repository = $repository;
    }
    
    public function obtenerConfiguraciones(int $usuarioId): array {
        return $this->repository->obtenerTodas($usuarioId);
    }
    
    public function actualizarConfiguracion(string $clave, string $valor, int $usuarioId): void {
        $this->repository->actualizar($clave, $valor, $usuarioId);
    }
    
    public function actualizarMultiples(array $configuraciones, int $usuarioId): void {
        $this->repository->actualizarVarias($configuraciones, $usuarioId);
    }
}