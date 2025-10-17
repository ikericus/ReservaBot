<?php
// src/Domain/Shared/Telefono.php

namespace ReservaBot\Domain\Shared;

class Telefono {
    private string $value;
    
    public function __construct(string $telefono) {
        $this->value = $this->validar($telefono);
    }
    
    private function validar(string $telefono): string {
        $limpio = trim($telefono);
        
        if (empty($limpio)) {
            throw new \InvalidArgumentException('El teléfono no puede estar vacío');
        }
        
        // Validación básica: debe tener al menos dígitos
        if (!preg_match('/\d/', $limpio)) {
            throw new \InvalidArgumentException('El teléfono debe contener números');
        }
        
        return $limpio;
    }
    
    public function getValue(): string {
        return $this->value;
    }
    
    public function normalizarParaWhatsApp(): string {
        // Remover caracteres que no sean números o +
        $normalizado = preg_replace('/[^\d+]/', '', $this->value);
        
        // Si ya tiene +, mantenerlo
        if (strpos($normalizado, '+') === 0) {
            return $normalizado;
        }
        
        // Si empieza con 34, agregar +
        if (strpos($normalizado, '34') === 0 && strlen($normalizado) >= 11) {
            return '+' . $normalizado;
        }
        
        // Si empieza con 6, 7, 8 o 9 (españoles) y tiene 9 dígitos, agregar +34
        if (preg_match('/^[6789]/', $normalizado) && strlen($normalizado) === 9) {
            return '+34' . $normalizado;
        }
        
        // Si tiene más de 9 dígitos sin prefijo, asumir que tiene código país
        if (strlen($normalizado) > 9) {
            return '+' . $normalizado;
        }
        
        // Por defecto, si tiene 9 dígitos asumir España
        if (strlen($normalizado) === 9) {
            return '+34' . $normalizado;
        }
        
        return $normalizado;
    }
    
    public function __toString(): string {
        return $this->value;
    }
}