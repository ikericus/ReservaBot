// src/domain/configuracion/ConfiguracionNegocio.php

namespace ReservaBot\Domain\Configuracion;

class ConfiguracionNegocio {
    private string $clave;
    private string $valor;
    
    public function __construct(string $clave, string $valor) {
        $this->clave = $clave;
        $this->valor = $valor;
    }
    
    public function getClave(): string { return $this->clave; }
    public function getValor(): string { return $this->valor; }
    
    public function toArray(): array {
        return [
            'clave' => $this->clave,
            'valor' => $this->valor
        ];
    }
}