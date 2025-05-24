<?php
// Página pública para reservas de clientes
require_once 'includes/db-config.php';
require_once 'includes/functions.php';

// Obtener el formulario por slug
$slug = $_GET['f'] ?? '';
$formulario = null;
$error = '';

if (empty($slug)) {
    $error = 'Enlace no válido';
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM formularios_publicos WHERE slug = ? AND activo = 1");
        $stmt->execute([$slug]);
        $formulario = $stmt->fetch();
        
        if (!$formulario) {
            $error = 'Formulario no encontrado o no disponible';
        }
    } catch (Exception $e) {
        $error = 'Error al cargar el formulario';
    }
}

// Obtener configuración de horarios
$horarios = [];
$diasSemana = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];

if ($formulario) {
    try {
        $stmt = $pdo->prepare("SELECT clave, valor FROM configuraciones WHERE clave LIKE 'horario_%'");
        $stmt->execute();
        $configHorarios = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        foreach ($diasSemana as $dia) {
            $horarioConfig = $configHorarios["horario_{$dia}"] ?? 'false|09:00|18:00';
            list($activo, $horaInicio, $horaFin) = explode('|', $horarioConfig);
            
            $horarios[$dia] = [
                'activo' => $activo === 'true',
                'inicio' => $horaInicio,
                'fin' => $horaFin
            ];
        }
    } catch (Exception $e) {
        // Si hay error, usar horarios por defecto
        foreach ($diasSemana as $dia) {
            $horarios[$dia] = [
                'activo' => in_array($dia, ['lun', 'mar', 'mie', 'jue', 'vie']),
                'inicio' => '09:00',
                'fin' => '18:00'
            ];
        }
    }
}

// Función para obtener el día de la semana en formato español
function getDiaSemana($fecha) {
    $diasMap = [
        1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 
        5 => 'vie', 6 => 'sab', 0 => 'dom'
    ];
    $diaSemana = date('w', strtotime($fecha));
    return $diasMap[$diaSemana];
}

// Función para generar horarios disponibles para una fecha
function getHorariosDisponibles($fecha, $horarios, $pdo) {
    $diaSemana = getDiaSemana($fecha);
    
    // Verificar si el día está activo
    if (!$horarios[$diaSemana]['activo']) {
        return [];
    }
    
    $horaInicio = $horarios[$diaSemana]['inicio'];
    $horaFin = $horarios[$diaSemana]['fin'];
    
    // Generar todas las horas posibles en intervalos de 30 minutos
    $horas = [];
    $current = strtotime($horaInicio);
    $end = strtotime($horaFin);
    
    while ($current < $end) {
        $horas[] = date('H:i', $current);
        $current += 30 * 60; // 30 minutos
    }
    
    // Obtener horas ya reservadas para esta fecha
    try {
        $stmt = $pdo->prepare("SELECT TIME_FORMAT(hora, '%H:%i') as hora_reservada 
                               FROM reservas 
                               WHERE fecha = ? AND estado IN ('pendiente', 'confirmada')");
        $stmt->execute([$fecha]);
        $horasReservadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Filtrar horas ya reservadas
        $horas = array_diff($horas, $horasReservadas);
    } catch (Exception $e) {
        // Si hay error, devolver todas las horas
    }
    
    return array_values($horas);
}

// Procesar envío de reserva
$mensaje = '';
$reservaExitosa = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $formulario) {
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $comentarios = trim($_POST['comentarios'] ?? '');
    
    // Validaciones básicas
    if (empty($nombre) || empty($telefono) || empty($fecha) || empty($hora)) {
        $mensaje = 'Por favor completa todos los campos obligatorios';
    } elseif ($fecha < date('Y-m-d')) {
        $mensaje = 'La fecha no puede ser anterior a hoy';
    } else {
        // Validar que el día esté activo
        $diaSemana = getDiaSemana($fecha);
        if (!$horarios[$diaSemana]['activo']) {
            $mensaje = 'El día seleccionado no está disponible para reservas';
        } else {
            // Validar que la hora esté dentro del horario de atención
            $horaInicio = $horarios[$diaSemana]['inicio'];
            $horaFin = $horarios[$diaSemana]['fin'];
            
            if ($hora < $horaInicio || $hora >= $horaFin) {
                $mensaje = 'La hora seleccionada está fuera del horario de atención';
            } else {
                // Verificar que la hora no esté ya reservada
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas 
                                           WHERE fecha = ? AND TIME_FORMAT(hora, '%H:%i') = ? 
                                           AND estado IN ('pendiente', 'confirmada')");
                    $stmt->execute([$fecha, $hora]);
                    $yaReservada = $stmt->fetchColumn();
                    
                    if ($yaReservada > 0) {
                        $mensaje = 'La hora seleccionada ya no está disponible. Por favor elige otra hora.';
                    } else {
                        // Crear la reserva
                        $estado = $formulario['confirmacion_automatica'] ? 'confirmada' : 'pendiente';
                        
                        $stmt = $pdo->prepare("INSERT INTO reservas (nombre, telefono, fecha, hora, mensaje, estado, created_at) 
                                               VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        
                        $stmt->execute([$nombre, $telefono, $fecha, $hora . ':00', $comentarios, $estado]);
                        $reservaId = $pdo->lastInsertId();
                        
                        // Registrar el origen de la reserva (opcional)
                        try {
                            $stmtOrigen = $pdo->prepare("INSERT INTO origen_reservas (reserva_id, formulario_id, origen, ip_address, user_agent) 
                                                         VALUES (?, ?, 'formulario_publico', ?, ?)");
                            $stmtOrigen->execute([
                                $reservaId, 
                                $formulario['id'], 
                                $_SERVER['REMOTE_ADDR'] ?? null,
                                $_SERVER['HTTP_USER_AGENT'] ?? null
                            ]);
                        } catch (Exception $e) {
                            // Si falla el registro del origen, no es crítico
                            error_log('Error registrando origen de reserva: ' . $e->getMessage());
                        }
                        
                        $reservaExitosa = true;
                        $mensaje = $formulario['confirmacion_automatica'] 
                            ? 'Tu reserva ha sido confirmada. ¡Te esperamos!' 
                            : 'Tu solicitud de reserva ha sido recibida. Te contactaremos pronto para confirmarla.';
                    }
                } catch (Exception $e) {
                    $mensaje = 'Error al procesar la reserva. Por favor intenta nuevamente.';
                }
            }
        }
    }
}