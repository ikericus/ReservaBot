<?php
// domain/reserva/ReservaDomain.php

namespace ReservaBot\Domain\Reserva;

use ReservaBot\Domain\Configuracion\IConfiguracionNegocioRepository;
use ReservaBot\Domain\Email\IEmailRepository;
use ReservaBot\Domain\Email\EmailTemplates;
use DateTime;

class ReservaDomain {
    private IReservaRepository $reservaRepository;
    private IConfiguracionNegocioRepository $configuracionRepository;
    private ?IEmailRepository $emailRepository;
    private EmailTemplates $emailTemplates;
    
    public function __construct(
        IReservaRepository $reservaRepository,
        IConfiguracionNegocioRepository $configuracionRepository,
        ?IEmailRepository $emailRepository = null
    ) {
        $this->reservaRepository = $reservaRepository;
        $this->configuracionRepository = $configuracionRepository;
        $this->emailRepository = $emailRepository;
        $this->emailTemplates = new EmailTemplates($configuracionRepository);
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
        ?int $excluirReservaId = null ): bool {
            
        // 1. Verificar si el horario está dentro de las horas de negocio
        if (!$this->configuracionRepository->estaDisponible($fecha, $hora, $usuarioId)) {
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
        return $this->configuracionRepository->estaDisponible($fecha, $hora, $usuarioId);
    }
    
    /**
     * Obtiene horas disponibles para una fecha
     */
    public function obtenerHorasDisponibles(DateTime $fecha, int $usuarioId): array {
        // Obtener todas las horas configuradas para ese día
        $todasLasHoras = $this->configuracionRepository->obtenerHorasDelDia($fecha, $usuarioId);
        
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
        return $this->configuracionRepository->obtenerHorasDelDia($fecha, $usuarioId);
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
        ?string $notasInternas = null ): Reserva {

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
        $reserva = $this->reservaRepository->guardar($reserva);
        
        // Enviar email si la reserva tiene email
        $this->enviarEmailReserva($reserva);
        
        return $reserva;
    }
    
    /**
     * Verifica si existe una reserva duplicada para el mismo email o teléfono
     * en la misma fecha y hora
     */
    public function existeReservaDuplicada(
        string $email,
        string $telefono,
        DateTime $fecha,
        string $hora,
        int $usuarioId ): bool {
        // Obtener reservas activas para esa fecha
        $reservasEnFecha = $this->reservaRepository->obtenerPorFecha($fecha, $usuarioId);
        
        foreach ($reservasEnFecha as $reserva) {
            // Solo considerar reservas activas (pendientes o confirmadas)
            if (!$reserva->getEstado()->esActiva()) {
                continue;
            }
            
            // Verificar si la hora coincide
            if ($reserva->getHora() !== $hora) {
                continue;
            }
            
            // Verificar si el teléfono coincide
            if ($reserva->getTelefono()->getValue() === $telefono) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Crea una reserva desde formulario público con validaciones completas
     * Incluye verificación de duplicados, generación de token y envío de email
     */
    public function crearReservaPublica(
        string $nombre,
        string $telefono,
        string $email,
        DateTime $fecha,
        string $hora,
        int $usuarioId,
        string $mensaje = '',
        ?int $formularioId = null,
        bool $confirmacionAutomatica = false ): Reserva {
        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email no válido');
        }
        
        // Verificar disponibilidad del horario
        if (!$this->verificarDisponibilidad($fecha, $hora, $usuarioId)) {
            throw new \DomainException('El horario seleccionado no está disponible');
        }
        
        // Verificar que el horario está dentro de las horas de negocio
        if (!$this->verificarHorarioDisponible($fecha, $hora, $usuarioId)) {
            throw new \DomainException('La hora seleccionada está fuera del horario de atención');
        }
        
        // Verificar duplicados por email o teléfono
        if ($this->existeReservaDuplicada($email, $telefono, $fecha, $hora, $usuarioId)) {
            throw new \DomainException('Ya existe una reserva para esta fecha y hora con el mismo email o teléfono');
        }
        
        // Crear la reserva usando el factory específico para reservas públicas
        $reserva = Reserva::crearPublica(
            $nombre,
            $telefono,
            $email,
            $fecha,
            $hora,
            $usuarioId,
            $mensaje,
            $formularioId
        );
        
        // Si es confirmación automática, confirmar antes de guardar
        if ($confirmacionAutomatica) {
            $reserva->confirmar();
        }
        
        // Persistir la reserva con todos sus datos (incluyendo token)
        $reserva = $this->reservaRepository->guardar($reserva);
        
        // Registrar el origen de la reserva
        if ($formularioId) {
            $this->reservaRepository->registrarOrigenReserva(
                $reserva->getId(),
                $formularioId,
                'formulario_publico',
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
        }
        
        // Enviar email de confirmación
        $this->enviarEmailReserva($reserva);
        
        return $reserva;
    }
    
    /**
     * Envía email al cliente según el estado de la reserva
     * Método genérico que detecta automáticamente qué email enviar
     */
    private function enviarEmailReserva(Reserva $reserva): bool {
        // Si no hay repositorio de email configurado, log y retornar
        if (!$this->emailRepository) {
            error_log("EmailRepository no configurado. No se puede enviar email para reserva #{$reserva->getId()}");
            return false;
        }
        
        // Verificar que la reserva tiene email
        if (!$reserva->getEmail()) {
            // No es un error, simplemente no hay email (reservas del admin)
            return false;
        }
        
        try {
            // Generar URL de gestión usando el token (si existe)
            $gestionUrl = null;
            if ($reserva->getAccessToken()) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $gestionUrl = $protocol . $host . '/mi-reserva?token=' . $reserva->getAccessToken();
            }
            
            // Generar contenido del email usando templates
            $emailData = $this->emailTemplates->confirmacionReserva(
                $reserva->toArray(),
                $gestionUrl
            );
            
            // Enviar email
            $enviado = $this->emailRepository->enviar(
                $reserva->getEmail(),
                $emailData['asunto'],
                $emailData['cuerpo_texto'],
                $emailData['cuerpo_html']
            );
            
            if ($enviado) {
                debug_log("Email enviado exitosamente para reserva #{$reserva->getId()} - Estado: {$reserva->getEstado()->value}");
            } else {
                error_log("Error al enviar email para reserva #{$reserva->getId()}");
            }
            
            return $enviado;
            
        } catch (\Exception $e) {
            error_log("Excepción al enviar email para reserva #{$reserva->getId()}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Confirma una reserva
     */
    public function confirmarReserva(int $id, int $usuarioId): Reserva {
        $reserva = $this->obtenerReserva($id, $usuarioId);
        
        $reserva->confirmar();
        
        $reserva = $this->reservaRepository->guardar($reserva);
        
        // Enviar email de confirmación
        $this->enviarEmailReserva($reserva);
        
        return $reserva;
    }
    
    /**
     * Cancela una reserva
     */
    public function cancelarReserva(int $id, int $usuarioId): Reserva {
        $reserva = $this->obtenerReserva($id, $usuarioId);
        
        $reserva->cancelar();
        
        $reserva = $this->reservaRepository->guardar($reserva);
        
        // Enviar email de cancelación
        $this->enviarEmailReserva($reserva);
        
        return $reserva;
    }
    
    /**
     * Modifica una reserva existente
     */
    public function modificarReserva(
        int $id,
        int $usuarioId,
        DateTime $nuevaFecha,
        string $nuevaHora,
        ?string $nuevoMensaje = null ): Reserva {
        $reserva = $this->obtenerReserva($id, $usuarioId);
        
        // Verificar disponibilidad del nuevo horario (excluyendo esta reserva)
        if (!$this->verificarDisponibilidad($nuevaFecha, $nuevaHora, $usuarioId, $id)) {
            throw new \DomainException('El nuevo horario no está disponible');
        }
        
        $reserva->modificar($nuevaFecha, $nuevaHora, $nuevoMensaje);
        
        $reserva = $this->reservaRepository->guardar($reserva);
        
        // Enviar email de modificación
        $this->enviarEmailReserva($reserva);
        
        return $reserva;
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
     * Rechaza una reserva pendiente
     */
    public function rechazarReserva(int $id, int $usuarioId): Reserva {
        $reserva = $this->obtenerReserva($id, $usuarioId);
        
        $reserva->rechazar();
        
        $reserva = $this->reservaRepository->guardar($reserva);
        
        // Enviar email de rechazo
        $this->enviarEmailReserva($reserva);
        
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
        string $nuevaHora ): Reserva {
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
        
        $reserva = $this->reservaRepository->guardar($reserva);
        
        // Enviar email de modificación
        $this->enviarEmailReserva($reserva);
        
        return $reserva;
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
        
        $reserva = $this->reservaRepository->guardar($reserva);
        
        // Enviar email de cancelación
        $this->enviarEmailReserva($reserva);
        
        return $reserva;
    }

    /**
     * Obtiene horas disponibles con información detallada de capacidad
     */
    public function obtenerHorasDisponiblesConCapacidad(DateTime $fecha, int $usuarioId): array {
        $diaSemana = $this->obtenerDiaSemana($fecha);
        $horarioConfig = $this->configuracionRepository->obtenerHorarioDia($diaSemana, $usuarioId);
        
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
        $intervalo = $this->configuracionRepository->obtenerIntervalo($usuarioId);
        
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
     * Obtiene el intervalo de reservas configurado (en minutos)
     */
    public function obtenerIntervaloReservas(int $usuarioId): int {
        return $this->configuracionRepository->obtenerIntervalo($usuarioId);
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

    /**
     * Obtiene una reserva pública por su token de acceso único
     * Valida que el token sea válido y no haya expirado
     */
    public function obtenerReservaPorToken(string $token): Reserva {
        $reserva = $this->reservaRepository->obtenerPorToken($token);
        
        if (!$reserva) {
            throw new \DomainException('Enlace no válido o expirado');
        }
        
        return $reserva;
    }

    /**
     * Obtiene reserva con datos del formulario público (para mi-reserva.php)
     * Incluye información de marca/empresa del formulario
     */
    public function obtenerReservaPublicaConFormulario(string $token): array {
        $reserva = $this->obtenerReservaPorToken($token);
        
        // Obtener datos del formulario si existe
        $formularioData = null;
        if ($reserva->getFormularioId()) {
            try {
                // Aquí podrías llamar a FormularioPublicoDomain si existe
                // Por ahora retornamos null y se puede agregar después
                $formularioData = null;
            } catch (\Exception $e) {
                error_log('Error obteniendo formulario público: ' . $e->getMessage());
            }
        }
        
        return [
            'reserva' => $reserva->toArray(),
            'formulario' => $formularioData
        ];
    }

    /**
    * Obtiene el historial de cambios de reservas
    */
    public function obtenerHistorialCambios(int $usuarioId, ?int $limite = 50): array {
        $auditoria = $this->reservaRepository->obtenerHistorialAuditoria($usuarioId, $limite);
        
        // Formatear para vista
        return array_map(function($registro) {
            $descripcion = $this->generarDescripcionCambio($registro);
            
            return [
                'id' => $registro['id'],
                'reserva_id' => $registro['reserva_id'],
                'nombre_cliente' => $registro['nombre'],
                'telefono' => $registro['telefono'],
                'fecha_reserva' => $registro['fecha'],
                'hora_reserva' => $registro['hora'],
                'accion' => $registro['accion'],
                'descripcion' => $descripcion,
                'fecha_cambio' => $registro['created_at'],
                'estado_actual' => $registro['estado']
            ];
        }, $auditoria);
    }

    private function generarDescripcionCambio(array $registro): string {
        switch ($registro['accion']) {
            case 'creada':
                return 'Reserva creada';
            case 'confirmada':
                return 'Reserva confirmada';
            case 'rechazada':
                return 'Reserva rechazada';
            case 'cancelada':
                return 'Reserva cancelada';
            case 'modificada':
                $campo = $registro['campo_modificado'];
                $anterior = $registro['valor_anterior'];
                $nuevo = $registro['valor_nuevo'];
                return "Cambio de {$campo}: {$anterior} → {$nuevo}";
            default:
                return $registro['accion'];
        }
    }
}