<?php
// config/container.php

namespace ReservaBot\Config;

use ReservaBot\Domain\Admin\AdminDomain;
use ReservaBot\Domain\Reserva\ReservaDomain;
use ReservaBot\Domain\Cliente\ClienteDomain;
use ReservaBot\Domain\Configuracion\ConfiguracionDomain;
use ReservaBot\Domain\WhatsApp\WhatsAppDomain;
use ReservaBot\Domain\Formulario\FormularioDomain;
use ReservaBot\Domain\Usuario\UsuarioDomain;
use ReservaBot\Infrastructure\ReservaRepository;
use ReservaBot\Infrastructure\ClienteRepository;
use ReservaBot\Infrastructure\ConfiguracionNegocioRepository;
use ReservaBot\Infrastructure\WhatsAppRepository;
use ReservaBot\Infrastructure\WhatsAppWebhookHandler;
use ReservaBot\Infrastructure\WhatsAppServerManager;
use ReservaBot\Infrastructure\FormularioRepository;
use ReservaBot\Infrastructure\AdminRepository;
use ReservaBot\Infrastructure\UsuarioRepository;
use ReservaBot\Infrastructure\EmailRepository;

class Container {
    private static ?Container $instance = null;
    private array $services = [];
    private ConnectionPool $pool;
    
    private function __construct(ConnectionPool $pool) {
        $this->pool = $pool;
    }
    
    public static function getInstance(ConnectionPool $pool): Container {
        if (self::$instance === null) {
            self::$instance = new Container($pool);
        }
        return self::$instance;
    }



    
    // ==================== DOMAINS ====================

    public function getAdminDomain(): AdminDomain {
        if (!isset($this->services['adminDomain'])) {
            $this->services['adminDomain'] = new AdminDomain(
                $this->getAdminRepository()
            );
        }
        return $this->services['adminDomain'];
    }

    public function getReservaDomain(): ReservaDomain {
        if (!isset($this->services['reservaDomain'])) {
            $this->services['reservaDomain'] = new ReservaDomain(
                $this->getReservaRepository(),
                $this->getConfiguracionDomain(),
                $this->getEmailRepository()
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
                $this->getConfiguracionNegocioRepository(),
                $this->getEmailRepository()
            );
        }
        return $this->services['configuracionDomain'];
    }
    
    public function getWhatsAppDomain(): WhatsAppDomain {
        if (!isset($this->services['whatsappDomain'])) {
            $this->services['whatsappDomain'] = new WhatsAppDomain(
                $this->getWhatsAppRepository(),
                $this->getWhatsAppServerManager()
            );
        }
        return $this->services['whatsappDomain'];
    }
        
    public function getFormularioDomain(): FormularioDomain {
        if (!isset($this->services['formularioDomain'])) {
            $this->services['formularioDomain'] = new FormularioDomain(
                $this->getFormularioRepository()
            );
        }
        return $this->services['formularioDomain'];
    }

    public function getUsuarioDomain(): UsuarioDomain {
        if (!isset($this->services['usuarioDomain'])) {
            $this->services['usuarioDomain'] = new UsuarioDomain(
                $this->getUsuarioRepository(),
                $this->getConfiguracionDomain(),
                $this->getEmailRepository()
            );
        }
        return $this->services['usuarioDomain'];
    }




    
    // ==================== REPOSITORIES ====================
    
    private function getReservaRepository(): ReservaRepository {
        if (!isset($this->services['reservaRepository'])) {
            $this->services['reservaRepository'] = new ReservaRepository($this->pool);
        }
        return $this->services['reservaRepository'];
    }
    
    private function getClienteRepository(): ClienteRepository {
        if (!isset($this->services['clienteRepository'])) {
            $this->services['clienteRepository'] = new ClienteRepository($this->pool);
        }
        return $this->services['clienteRepository'];
    }
    
    private function getConfiguracionNegocioRepository(): ConfiguracionNegocioRepository {
        if (!isset($this->services['configuracionNegocioRepository'])) {
            $this->services['configuracionNegocioRepository'] = new ConfiguracionNegocioRepository($this->pool);
        }
        return $this->services['configuracionNegocioRepository'];
    }
    
    private function getWhatsAppRepository(): WhatsAppRepository {
        if (!isset($this->services['whatsappRepository'])) {
            $this->services['whatsappRepository'] = new WhatsAppRepository($this->pool);
        }
        return $this->services['whatsappRepository'];
    }
    
    private function getFormularioRepository(): FormularioRepository {
        if (!isset($this->services['formularioRepository'])) {
            $this->services['formularioRepository'] = new FormularioRepository($this->pool);
        }
        return $this->services['formularioRepository'];
    }

    private function getAdminRepository(): AdminRepository {
        if (!isset($this->services['adminRepository'])) {
            $this->services['adminRepository'] = new AdminRepository($this->pool);
        }
        return $this->services['adminRepository'];
    }
    
    private function getUsuarioRepository(): UsuarioRepository {
        if (!isset($this->services['usuarioRepository'])) {
            $this->services['usuarioRepository'] = new UsuarioRepository($this->pool);
        }
        return $this->services['usuarioRepository'];
    }

    public function getEmailRepository(): EmailRepository {
        if (!isset($this->services['emailRepository'])) {
            $this->services['emailRepository'] = new EmailRepository();
        }
        return $this->services['emailRepository'];
    }




    // ==================== SERVICIOS EXTERNOS ====================

    public function getWhatsAppServerManager(): WhatsAppServerManager {
        if (!isset($this->services['whatsappServerManager'])) {
            $serverUrl = $_ENV['WHATSAPP_SERVER_URL'];
            $jwtSecret = $_ENV['JWT_SECRET'];
            
            $this->services['whatsappServerManager'] = new WhatsAppServerManager(
                $serverUrl,
                $jwtSecret
            );
        }
        return $this->services['whatsappServerManager'];
    }

    public function getWhatsAppWebhookHandler(): WhatsAppWebhookHandler {
        if (!isset($this->services['whatsappWebhookHandler'])) {
            $webhookSecret = $_ENV['WEBHOOK_SECRET'];
            
            $this->services['whatsappWebhookHandler'] = new WhatsAppWebhookHandler(
                $this->getWhatsAppDomain(),
                $webhookSecret
            );
        }
        return $this->services['whatsappWebhookHandler'];
    }
}