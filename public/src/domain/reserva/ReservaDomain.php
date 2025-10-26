<?php
// src/domain/reserva/ReservaDomain.php

namespace ReservaBot\Domain\Reserva;

use ReservaBot\Domain\Disponibilidad\IDisponibilidadRepository;
use ReservaBot\Domain\Email\EmailTemplates;
use DateTime;

class ReservaDomain {
    private IReservaRepository $reservaRepository;
    private IDisponibilidadRepository $disponibilidadRepository;
    private EmailTemplates $emailTemplates;
    
    public function __construct(
        IReservaRepository $reservaRepository,
        IDisponibilidadRepository $disponibilidadRepository
    ) {
        $this->reservaRepository = $reservaRepository;
        $this->disponibilidadRepository = $disponibilidadRepository;
        $this->emailTemplates = new EmailTemplates();
    }
    
    /**
     * Obtiene todas las reservas de un usuario
     */
    public function obtenerTodasReservasUsuario(int $usuarioId): array {
        return $this->reservaRepository->obtenerPorUsuario($usuarioId);
    }
    
    /**
     * Obtiene reservas pendientes
     */
    public function obtenerReservasPendientes(int $usuarioId): array {
        return $this->reservaRepository->obtenerPorUsuarioYEstado($usuarioId, 'pendiente');
    }
    
    /**
     * Obtiene reservas confirmadas
     */
    public function obtenerReservasConfirmadas(int $usuarioId): array {
        return $this->reservaRepository->obtenerPorUsuarioYEstado($usuarioId, 'confirmada');
    }
    
    /**
     * Obtiene reservas por fecha
     */
    public function obtenerReservasPorFecha(DateTime $fecha, int $usuarioId): array {
        return $this->reservaRepository->obtenerPorFecha($fecha, $usuarioId);
    }
    
    /**
     * Obtiene reservas por rango de fechas
     */
    public function obtenerReservasPorRango(DateTime $desde, DateTime $hasta, int $usuarioId): array {
        return $this->reservaRepository->obtenerPorRangoFechas($desde, $hasta, $usuarioId);
    }
    
    /**
     * Verifica si una fecha y hora están disponibles
     */
    public function verificarDisponibilidad(
        DateTime $fecha, 
        string $hora, 
        int $usuarioId,
        ?int $excluirReservaId = null
    ): bool {
        // 1. Verificar si el horario está dentro de las horas de negocio
        if (!$this->disponibilidadRepository->estaDisponible($fecha, $hora, $usuarioId)) {
            return false;
        }
        
        // 2. Verificar que no haya otra reserva activa en ese horario
        return !$this->reservaRepository->existeReservaActiva(
            $fecha, 
            $hora, 
            $usuarioId,
            $excluirReservaId
        );
    }
    
    /**
     * Verifica si un horario está dentro de las horas de negocio
     */
    public function verificarHorarioDisponible(DateTime $fecha, string $hora, int $usuarioId): bool {
        return $this->disponibilidadRepository->estaDisponible($fecha, $hora, $usuarioId);
    }
    
    /**
     * Obtiene horas disponibles para una fecha
     */
    public function obtenerHorasDisponibles(DateTime $fecha, int $usuarioId): array {
        // Obtener todas las horas configuradas para ese día
        $todasLasHoras = $this->disponibilidadRepository->obtenerHorasDelDia($fecha, $usuarioId);
        
        // Obtener reservas existentes para esa fecha
        $reservasExistentes = $this->reservaRepository->obtenerPorFecha($fecha, $usuarioId);
        
        // Filtrar horas ocupadas
        $horasOcupadas = array_map(function($reserva) {
            return $reserva->getHora();
        }, array_filter($reservasExistentes, function($reserva) {
            return $reserva->getEstado()->esActiva();
        }));
        
        // Retornar solo horas disponibles
        return array_values(array_filter($todasLasHoras, function($hora) use ($horasOcupadas) {
            return !in_array($hora, $horasOcupadas);
        }));
    }
    
    /**
     * Obtiene todas las horas del día según configuración
     */
    public function obtenerHorasDelDia(DateTime $fecha, int $usuarioId): array {
        return $this->disponibilidadRepository->obtenerHorasDelDia($fecha, $usuarioId);
    }
    
    /**
     * Crea una nueva reserva validando disponibilidad
     */
    public function crearReserva(
        string $nombre,
        string $telefono,
        DateTime $fecha,
        string $hora,
        int $usuarioId,
        string $mensaje = '',
        ?string $notasInternas = null
    ): Reserva {
        // Verificar disponibilidad
        if (!$this->verificarDisponibilidad($fecha, $hora, $usuarioId)) {
            throw new \DomainException('El horario seleccionado no está disponible');
        }
        
        // Crear entidad
        $reserva = Reserva::crear(
            $nombre,
            $telefono,
            $fecha,
            $hora,
            $usuarioId,
            $mensaje,
            $notasInternas
        );
        
        // Persistir
        return $this->reservaRepository->guardar($reserva);
    }
    
    /**
     * Confirma una reserva
     */
    public function confirmarReserva(int $id, int $usuarioId): Reserva {
        $reserva = $this->obtenerReserva($id, $usuarioId);
        
        $reserva->confirmar();
        
        return $this->reservaRepository->guardar($reserva);
    }
    
    /**
     * Cancela una reserva
     */
    public function cancelarReserva(int $id, int $usuarioId): Reserva {
        $reserva = $this->obtenerReserva($id, $usuarioId);
        
        $reserva->cancelar();
        
        return $this->reservaRepository->guardar($reserva);
    }
    
    /**
     * Modifica una reserva existente
     */
    public function modificarReserva(
        int $id,
        int $usuarioId,
        DateTime $nuevaFecha,
        string $nuevaHora,
        ?string $nuevoMensaje = null
    ): Reserva {
        $reserva = $this->obtenerReserva($id, $usuarioId);
        
        // Verificar disponibilidad del nuevo horario (excluyendo esta reserva)
        if (!$this->verificarDisponibilidad($nuevaFecha, $nuevaHora, $usuarioId, $id)) {
            throw new \DomainException('El nuevo horario no está disponible');
        }
        
        $reserva->modificar($nuevaFecha, $nuevaHora, $nuevoMensaje);
        
        return $this->reservaRepository->guardar($reserva);
    }
    
    /**
     * Obtiene una reserva verificando que pertenece al usuario
     */
    public function obtenerReserva(int $id, int $usuarioId): Reserva {
        $reserva = $this->reservaRepository->obtenerPorId($id, $usuarioId);
        
        if (!$reserva) {
            throw new \DomainException('Reserva no encontrada');
        }
        
        return $reserva;
    }
    
    /**
     * Elimina una reserva
     */
    public function eliminarReserva(int $id, int $usuarioId): void {
        // Verificar que existe
        $this->obtenerReserva($id, $usuarioId);
        
        $this->reservaRepository->eliminar($id, $usuarioId);
    }

    /**
     * Modifica una reserva pública mediante token de acceso
     */
    public function modificarReservaPublica(
        int $reservaId,
        string $token,
        DateTime $nuevaFecha,
        string $nuevaHora
    ): Reserva {
        // Obtener reserva por ID y validar token
        $reserva = $this->reservaRepository->obtenerPorIdYToken($reservaId, $token);
        
        if (!$reserva) {
            throw new \DomainException('Reserva no encontrada o token inválido');
        }
        
        // Validar que no está cancelada
        if ($reserva->estaCancelada()) {
            throw new \DomainException('No se puede modificar una reserva cancelada');
        }
        
        // Validar plazo de 24h
        $fechaHoraReserva = clone $reserva->getFecha();
        $fechaHoraReserva->setTime(
            (int)substr($reserva->getHora(), 0, 2),
            (int)substr($reserva->getHora(), 3, 2)
        );
        
        $fechaLimite = clone $fechaHoraReserva;
        $fechaLimite->modify('-24 hours');
        
        if (new DateTime() >= $fechaLimite) {
            throw new \DomainException('No se puede modificar la reserva. El plazo límite ha expirado (24h antes de la cita)');
        }
        
        // Verificar disponibilidad del nuevo horario
        if (!$this->verificarDisponibilidad($nuevaFecha, $nuevaHora, $reserva->getUsuarioId(), $reservaId)) {
            throw new \DomainException('El nuevo horario no está disponible');
        }
        
        $reserva->modificar($nuevaFecha, $nuevaHora);
        
        return $this->reservaRepository->guardar($reserva);
    }

    /**
     * Cancela una reserva pública mediante token de acceso
     */
    public function cancelarReservaPublica(int $reservaId, string $token): Reserva {
        $reserva = $this->reservaRepository->obtenerPorIdYToken($reservaId, $token);
        
        if (!$reserva) {
            throw new \DomainException('Reserva no encontrada o token inválido');
        }
        
        if ($reserva->estaCancelada()) {
            throw new \DomainException('La reserva ya está cancelada');
        }
        
        // Validar plazo de 24h
        $fechaHoraReserva = clone $reserva->getFecha();
        $fechaHoraReserva->setTime(
            (int)substr($reserva->getHora(), 0, 2),
            (int)substr($reserva->getHora(), 3, 2)
        );
        
        $fechaLimite = clone $fechaHoraReserva;
        $fechaLimite->modify('-24 hours');
        
        if (new DateTime() >= $fechaLimite) {
            throw new \DomainException('No se puede cancelar la reserva. El plazo límite ha expirado (24h antes de la cita)');
        }
        
        $reserva->cancelar();
        
        return $this->reservaRepository->guardar($reserva);
    }

     /**
     * Envía email de confirmación de reserva
     */
    public function enviarConfirmacion(int $reservaId): bool {
        $reserva = $this->repository->obtenerPorId($reservaId);
        
        if (!$reserva) {
            throw new \DomainException('Reserva no encontrada');
        }
        
        // Generar URL de gestión
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $gestionUrl = $protocol . $host . '/mi-reserva?token=' . $reserva['access_token'];
        
        // Generar contenido del email
        $email = $this->emailTemplates->confirmacionReserva($reserva, $gestionUrl);
        
        // Enviar
        return $this->emailRepository->enviar(
            $reserva['email'],
            $email['asunto'],
            $email['cuerpo_texto'],
            $email['cuerpo_html']
        );
    }

    /**
     * Obtiene horas disponibles con información detallada de capacidad
     */
    public function obtenerHorasDisponiblesConCapacidad(DateTime $fecha, int $usuarioId): array {
        $diaSemana = $this->obtenerDiaSemana($fecha);
        $horarioConfig = $this->disponibilidadRepository->obtenerHorarioDia($diaSemana, $usuarioId);
        
        // Asegurar que todas las ventanas tengan capacidad
        foreach ($horarioConfig['ventanas'] as &$ventana) {
            if (!isset($ventana['capacidad'])) {
                $ventana['capacidad'] = 1;
            }
        }
        
        if (!$horarioConfig['activo']) {
            throw new \DomainException('El día seleccionado no está disponible');
        }
        
        $ventanas = $horarioConfig['ventanas'];
        
        if (empty($ventanas)) {
            throw new \DomainException('No hay horarios configurados para este día');
        }
        
        // Obtener intervalo de reservas
        $intervalo = $this->disponibilidadRepository->obtenerIntervalo($usuarioId);
        
        // Obtener todas las horas posibles del día
        $todasLasHoras = $this->generarHorasPorVentanas($ventanas, $intervalo);
        
        // Obtener reservas existentes para la fecha
        $reservasExistentes = $this->reservaRepository->obtenerPorFecha($fecha, $usuarioId);
        
        // Contar reservas activas por hora
        $reservasPorHora = $this->contarReservasPorHora($reservasExistentes);
        
        // Calcular disponibilidad por hora
        $horasConCapacidad = [];
        $horasDisponibles = [];
        
        foreach ($todasLasHoras as $hora) {
            $capacidadTotal = $this->obtenerCapacidadParaHora($hora, $ventanas);
            $reservasActuales = $reservasPorHora[$hora] ?? 0;
            $disponibles = $capacidadTotal - $reservasActuales;
            
            if ($disponibles > 0) {
                $horasDisponibles[] = $hora;
            }
            
            $horasConCapacidad[$hora] = [
                'total' => $capacidadTotal,
                'ocupadas' => $reservasActuales,
                'libres' => $disponibles
            ];
        }
        
        // Si es hoy, filtrar horas pasadas
        if ($fecha->format('Y-m-d') === date('Y-m-d')) {
            $horaActual = date('H:i');
            $horasDisponibles = array_filter($horasDisponibles, fn($h) => $h > $horaActual);
            $horasDisponibles = array_values($horasDisponibles);
        }
        
        // Calcular rangos globales
        $horaInicioGlobal = null;
        $horaFinGlobal = null;
        $ventanasInfo = [];
        
        foreach ($ventanas as $ventana) {
            $ventanasInfo[] = $ventana['inicio'] . ' - ' . $ventana['fin'] . 
                            ' (máx. ' . $ventana['capacidad'] . ' reservas)';
            
            if ($horaInicioGlobal === null || $ventana['inicio'] < $horaInicioGlobal) {
                $horaInicioGlobal = $ventana['inicio'];
            }
            if ($horaFinGlobal === null || $ventana['fin'] > $horaFinGlobal) {
                $horaFinGlobal = $ventana['fin'];
            }
        }
        
        // Detectar si hay capacidad múltiple
        $tieneCapacidadMultiple = false;
        foreach ($ventanas as $ventana) {
            if ($ventana['capacidad'] > 1) {
                $tieneCapacidadMultiple = true;
                break;
            }
        }
        
        return [
            'horas' => $horasDisponibles,
            'dia_semana' => $diaSemana,
            'horario_inicio' => $horaInicioGlobal,
            'horario_fin' => $horaFinGlobal,
            'ventanas' => $ventanasInfo,
            'intervalo' => $intervalo,
            'total_ventanas' => count($ventanas),
            'capacidad_info' => $horasConCapacidad,
            'tiene_capacidad_multiple' => $tieneCapacidadMultiple
        ];
    }

    /**
     * Genera todas las horas posibles según ventanas e intervalo
     */
    private function generarHorasPorVentanas(array $ventanas, int $intervalo): array {
        $horas = [];
        
        foreach ($ventanas as $ventana) {
            $inicio = strtotime($ventana['inicio']);
            $fin = strtotime($ventana['fin']);
            
            $current = $inicio;
            while ($current < $fin) {
                $hora = date('H:i', $current);
                if (!in_array($hora, $horas)) {
                    $horas[] = $hora;
                }
                $current += $intervalo * 60;
            }
        }
        
        sort($horas);
        return $horas;
    }

    /**
     * Cuenta reservas activas agrupadas por hora
     */
    private function contarReservasPorHora(array $reservas): array {
        $contador = [];
        
        foreach ($reservas as $reserva) {
            if ($reserva->getEstado()->esActiva()) {
                $hora = $reserva->getHora();
                $contador[$hora] = ($contador[$hora] ?? 0) + 1;
            }
        }
        
        return $contador;
    }

    /**
     * Obtiene la capacidad total para una hora específica
     */
    private function obtenerCapacidadParaHora(string $hora, array $ventanas): int {
        $capacidadMaxima = 0;
        
        foreach ($ventanas as $ventana) {
            if ($hora >= $ventana['inicio'] && $hora < $ventana['fin']) {
                $capacidadMaxima = max($capacidadMaxima, $ventana['capacidad']);
            }
        }
        
        return max($capacidadMaxima, 1); // Mínimo 1
    }

    /**
     * Obtiene el día de la semana en formato corto
     */
    private function obtenerDiaSemana(DateTime $fecha): string {
        $diasMap = [
            1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue',
            5 => 'vie', 6 => 'sab', 0 => 'dom'
        ];
        return $diasMap[(int)$fecha->format('w')];
    }
}