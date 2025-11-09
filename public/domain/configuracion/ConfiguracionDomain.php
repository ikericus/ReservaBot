<?php
// domain/configuracion/ConfiguracionDomain.php

namespace ReservaBot\Domain\Configuracion;

use ReservaBot\Domain\Email\IEmailRepository;
use ReservaBot\Domain\Email\EmailTemplates;
use DateTime;

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
    
    /**
     * Obtiene el repository (necesario para EmailTemplates en ReservaDomain)
     */
    public function getRepository(): IConfiguracionNegocioRepository {
        return $this->repository;
    }
    
    /**
     * Obtiene todas las configuraciones del usuario
     */
    public function obtenerConfiguraciones(int $usuarioId): array {
        return $this->repository->obtenerTodas($usuarioId);
    }
    
    /**
     * Actualiza una configuración específica
     */
    public function actualizarConfiguracion(string $clave, string $valor, int $usuarioId): void {
        $this->repository->actualizar($clave, $valor, $usuarioId);
    }
    
    /**
     * Actualiza múltiples configuraciones
     */
    public function actualizarMultiples(array $configuraciones, int $usuarioId): void {
        $this->repository->actualizarVarias($configuraciones, $usuarioId);
    }
    
    /**
     * Obtiene el intervalo de reservas configurado (en minutos)
     */
    public function obtenerIntervaloReservas(int $usuarioId): int {
        return $this->repository->obtenerIntervalo($usuarioId);
    }
    
    /**
     * Obtiene la duración de las reservas configurada (en minutos)
     */
    public function obtenerDuracionReserva(int $usuarioId): int {
        return $this->repository->obtenerDuracionReserva($usuarioId);
    }
    
    /**
     * Verifica si un horario está disponible según configuración del negocio
     */
    public function verificarHorarioDisponible(DateTime $fecha, string $hora, int $usuarioId): bool {
        return $this->repository->estaDisponible($fecha, $hora, $usuarioId);
    }
    
    /**
     * Obtiene todas las horas configuradas para un día
     */
    public function obtenerHorasDelDia(DateTime $fecha, int $usuarioId): array {
        return $this->repository->obtenerHorasDelDia($fecha, $usuarioId);
    }
    
    /**
     * Obtiene el horario configurado para un día de la semana
     */
    public function obtenerHorarioDia(string $dia, int $usuarioId): array {
        return $this->repository->obtenerHorarioDia($dia, $usuarioId);
    }
    
    /**
     * Calcula todas las franjas horarias bloqueadas por una reserva
     * Considera la duración de la reserva y el intervalo
     * 
     * Ejemplo: Si una reserva dura 60 min y el intervalo es 15 min,
     * una reserva a las 11:00 bloqueará: 11:00, 11:15, 11:30, 11:45
     */
    public function calcularFranjasBloqueadas(
        string $horaInicio, 
        int $usuarioId
    ): array {
        $duracionMinutos = $this->obtenerDuracionReserva($usuarioId);
        $intervaloMinutos = $this->obtenerIntervaloReservas($usuarioId);
        
        $franjas = [];
        $horaActual = strtotime($horaInicio);
        $horaFin = $horaActual + ($duracionMinutos * 60);
        
        // Generar todas las franjas desde inicio hasta fin
        while ($horaActual < $horaFin) {
            $franjas[] = date('H:i', $horaActual);
            $horaActual += $intervaloMinutos * 60;
        }
        
        return $franjas;
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
                        
            // Email de contacto
            $emailContacto = $_ENV['CONTACT_EMAIL'] ?? 'contacto@reservabot.es';
            
            // Enviar el email
            $resultado = $this->emailRepository->enviar(
                $emailDestino,
                $emailData['asunto'],
                $emailData['cuerpo_texto'],
                $emailData['cuerpo_html'],
                $emailData['opciones']
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