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
     * Obtiene el modo de aceptación de reservas (manual/automatico)
     */
    public function obtenerModoAceptacion(int $usuarioId): string {
        $valor = $this->repository->obtener('modo_aceptacion', $usuarioId);
        return $valor ?: 'manual';
    }

    /**
     * Obtiene la configuración completa del negocio para uso público
     * Combina configuración general con configuración del formulario
     */
    public function obtenerConfiguracionNegocioPublica(int $usuarioId, ?array $formulario = null): array {
        $todasConfig = $this->repository->obtenerTodas($usuarioId);
        
        return [
            'nombre' => $todasConfig['empresa_nombre'] ?? ($formulario['empresa_nombre'] ?? $formulario['nombre'] ?? 'Mi Negocio'),
            'logo' => $todasConfig['empresa_imagen'] ?? ($formulario['empresa_logo'] ?? null),
            'telefono' => $todasConfig['empresa_telefono'] ?? ($formulario['telefono_contacto'] ?? null),
            'email' => $todasConfig['empresa_email'] ?? null,
            'direccion' => $todasConfig['empresa_direccion'] ?? ($formulario['direccion'] ?? null),
            'web' => $todasConfig['empresa_web'] ?? null,
            'color_primario' => $todasConfig['color_primario'] ?? ($formulario['color_primario'] ?? '#667eea'),
            'color_secundario' => $todasConfig['color_secundario'] ?? ($formulario['color_secundario'] ?? '#764ba2')
        ];
    }

    /**
     * Obtiene los horarios de toda la semana
     * Retorna un array con los 7 días de la semana y su configuración
     */
    public function obtenerHorariosSemana(int $usuarioId): array {
        $diasSemana = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];
        $horarios = [];
        
        foreach ($diasSemana as $dia) {
            try {
                $horarioDia = $this->repository->obtenerHorarioDia($dia, $usuarioId);
                
                // Obtener primera y última hora de las ventanas
                $inicio = '09:00';
                $fin = '18:00';
                
                if ($horarioDia['activo'] && !empty($horarioDia['ventanas'])) {
                    $inicios = array_column($horarioDia['ventanas'], 'inicio');
                    $fines = array_column($horarioDia['ventanas'], 'fin');
                    $inicio = min($inicios);
                    $fin = max($fines);
                }
                
                $horarios[$dia] = [
                    'activo' => $horarioDia['activo'],
                    'inicio' => $inicio,
                    'fin' => $fin,
                    'ventanas' => $horarioDia['ventanas'] ?? []
                ];
            } catch (Exception $e) {
                error_log("Error obteniendo horario de {$dia}: " . $e->getMessage());
                $horarios[$dia] = [
                    'activo' => false,
                    'inicio' => '09:00',
                    'fin' => '18:00',
                    'ventanas' => []
                ];
            }
        }
        
        return $horarios;
    }

    /**
     * Obtiene todas las configuraciones necesarias para el formulario público
     * Incluye horarios, intervalos, duración y modo de aceptación
     */
    public function obtenerConfiguracionFormularioPublico(int $usuarioId, ?array $formulario = null): array {
        return [
            'horarios' => $this->obtenerHorariosSemana($usuarioId),
            'intervalo' => $this->obtenerIntervaloReservas($usuarioId),
            'duracion' => $this->obtenerDuracionReserva($usuarioId),
            'modo_aceptacion' => $this->obtenerModoAceptacion($usuarioId),
            'negocio' => $this->obtenerConfiguracionNegocioPublica($usuarioId, $formulario)
        ];
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