<?php
// src/infrastructure/Container.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\Reserva\ReservaDomain;
use PDO;

class Container {
    private static ?Container $instance = null;
    private array $services = [];
    private PDO $pdo;
    
    private function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public static function getInstance(PDO $pdo): Container {
        if (self::$instance === null) {
            self::$instance = new Container($pdo);
        }
        return self::$instance;
    }
    
    // Exponer Domain directamente
    public function getReservaDomain(): ReservaDomain {
        if (!isset($this->services['reservaDomain'])) {
            $this->services['reservaDomain'] = new ReservaDomain(
                $this->getReservaRepository(),
                $this->getConfiguracionRepository()
            );
        }
        return $this->services['reservaDomain'];
    }
    
    private function getReservaRepository(): ReservaRepository {
        if (!isset($this->services['reservaRepository'])) {
            $this->services['reservaRepository'] = new ReservaRepository($this->pdo);
        }
        return $this->services['reservaRepository'];
    }
    
    private function getConfiguracionRepository(): ConfiguracionRepository {
        if (!isset($this->services['configuracionRepository'])) {
            $this->services['configuracionRepository'] = new ConfiguracionRepository($this->pdo);
        }
        return $this->services['configuracionRepository'];
    }
}