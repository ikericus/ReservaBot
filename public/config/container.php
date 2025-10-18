<?php
// config/container.php

namespace ReservaBot\Config;

use ReservaBot\Domain\Reserva\ReservaDomain;
use ReservaBot\Domain\Cliente\ClienteDomain;
use ReservaBot\Domain\Configuracion\ConfiguracionDomain;
use ReservaBot\Domain\WhatsApp\WhatsAppDomain;
use ReservaBot\Infrastructure\ReservaRepository;
use ReservaBot\Infrastructure\ClienteRepository;
use ReservaBot\Infrastructure\DisponibilidadRepository;
use ReservaBot\Infrastructure\ConfiguracionNegocioRepository;
use ReservaBot\Infrastructure\WhatsAppRepository;
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
    
    // ==================== DOMAINS ====================
    
    public function getReservaDomain(): ReservaDomain {
        if (!isset($this->services['reservaDomain'])) {
            $this->services['reservaDomain'] = new ReservaDomain(
                $this->getReservaRepository(),
                $this->getDisponibilidadRepository()
            );
        }
        return $this->services['reservaDomain'];
    }
    
    public function getClienteDomain(): ClienteDomain {
        if (!isset($this->services['clienteDomain'])) {
            $this->services['clienteDomain'] = new ClienteDomain(
                $this->getClienteRepository(),
                $this->getReservaRepository()
            );
        }
        return $this->services['clienteDomain'];
    }
    
    public function getConfiguracionDomain(): ConfiguracionDomain {
        if (!isset($this->services['configuracionDomain'])) {
            $this->services['configuracionDomain'] = new ConfiguracionDomain(
                $this->getConfiguracionNegocioRepository()
            );
        }
        return $this->services['configuracionDomain'];
    }
    
    public function getWhatsAppDomain(): WhatsAppDomain {
        if (!isset($this->services['whatsappDomain'])) {
            $this->services['whatsappDomain'] = new WhatsAppDomain(
                $this->getWhatsAppRepository()
            );
        }
        return $this->services['whatsappDomain'];
    }
    
    // ==================== REPOSITORIES ====================
    
    private function getReservaRepository(): ReservaRepository {
        if (!isset($this->services['reservaRepository'])) {
            $this->services['reservaRepository'] = new ReservaRepository($this->pdo);
        }
        return $this->services['reservaRepository'];
    }
    
    private function getClienteRepository(): ClienteRepository {
        if (!isset($this->services['clienteRepository'])) {
            $this->services['clienteRepository'] = new ClienteRepository($this->pdo);
        }
        return $this->services['clienteRepository'];
    }
    
    private function getDisponibilidadRepository(): DisponibilidadRepository {
        if (!isset($this->services['disponibilidadRepository'])) {
            $this->services['disponibilidadRepository'] = new DisponibilidadRepository($this->pdo);
        }
        return $this->services['disponibilidadRepository'];
    }
    
    private function getConfiguracionNegocioRepository(): ConfiguracionNegocioRepository {
        if (!isset($this->services['configuracionNegocioRepository'])) {
            $this->services['configuracionNegocioRepository'] = 
                new ConfiguracionNegocioRepository($this->pdo);
        }
        return $this->services['configuracionNegocioRepository'];
    }
    
    private function getWhatsAppRepository(): WhatsAppRepository {
        if (!isset($this->services['whatsappRepository'])) {
            $this->services['whatsappRepository'] = new WhatsAppRepository($this->pdo);
        }
        return $this->services['whatsappRepository'];
    }
}