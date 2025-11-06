<?php
// domain/configuracion/ConfiguracionDomain.php

namespace ReservaBot\Domain\Configuracion;

use ReservaBot\Domain\Email\IEmailRepository;
use ReservaBot\Domain\Email\EmailTemplates;

class ConfiguracionDomain {
    private IConfiguracionNegocioRepository $repository;
    private ?IEmailRepository $emailRepository;
    private ?EmailTemplates $emailTemplates;
    
    public function __construct(
        IConfiguracionNegocioRepository $repository,
        ?IEmailRepository $emailRepository = null
    ) {
        $this->repository = $repository;
        $this->emailRepository = $emailRepository;
        
        // Inicializar EmailTemplates si tenemos el repository
        if ($emailRepository) {
            $this->emailTemplates = new EmailTemplates($repository);
        }
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
    
    /**
     * Envía un email de prueba al usuario con la configuración actual de su negocio
     * 
     * @param string $emailDestino Email del usuario donde enviar la prueba
     * @param int $usuarioId ID del usuario para obtener su configuración
     * @param string $nombreUsuario Nombre del usuario para personalizar el email
     * @return bool True si se envió correctamente
     * @throws \Exception Si no hay EmailRepository configurado o si hay error al enviar
     */
    public function enviarEmailPrueba(string $emailDestino, int $usuarioId, string $nombreUsuario): bool {
        if (!$this->emailRepository || !$this->emailTemplates) {
            throw new \Exception('EmailRepository no está configurado');
        }
        
        try {
            // Generar email usando el template
            $emailData = $this->emailTemplates->emailPruebaConfiguracion($nombreUsuario, $usuarioId);
            
            // Enviar el email
            $resultado = $this->emailRepository->enviar(
                $emailDestino,
                $emailData['asunto'],
                $emailData['cuerpo_texto'],
                $emailData['cuerpo_html']
            );
            
            if (!$resultado) {
                error_log("Error al enviar email de prueba a {$emailDestino}");
            }
            
            return $resultado;
            
        } catch (\Exception $e) {
            error_log("Excepción al enviar email de prueba: " . $e->getMessage());
            throw $e;
        }
    }
}