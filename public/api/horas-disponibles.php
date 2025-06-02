<?php
/**
 * API para obtener horas disponibles para una fecha específica
 * ACTUALIZADA para soportar múltiples ventanas horarias
 */

// Cabeceras para JSON
header('Content-Type: application/json');

// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos
if (!isset($data['fecha']) || empty($data['fecha'])) {
    echo json_encode(['success' => false, 'message' => 'Fecha no proporcionada']);
    exit;
}

$fecha = $data['fecha'];

// Validar formato de fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
    exit;
}

// Verificar que la fecha no sea anterior a hoy
if ($fecha < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'La fecha no puede ser anterior a hoy']);
    exit;
}

try {
    // Obtener configuración de horarios
    $stmt = getPDO()->prepare("SELECT clave, valor FROM configuraciones WHERE clave LIKE 'horario_%'");
    $stmt->execute();
    $configHorarios = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Función para obtener el día de la semana
    function getDiaSemana($fecha) {
        $diasMap = [
            1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 
            5 => 'vie', 6 => 'sab', 0 => 'dom'
        ];
        $diaSemana = date('w', strtotime($fecha));
        return $diasMap[$diaSemana];
    }
    
    $diaSemana = getDiaSemana($fecha);
    
    // Obtener configuración del día
    $horarioConfig = $configHorarios["horario_{$diaSemana}"] ?? 'false|[]';
    $parts = explode('|', $horarioConfig, 2);
    $activo = $parts[0] === 'true';
    
    // Verificar si el día está activo
    if (!$activo) {
        echo json_encode(['success' => false, 'message' => 'El día seleccionado no está disponible']);
        exit;
    }
    
    // Obtener ventanas horarias
    $ventanas = [];
    if (isset($parts[1])) {
        // Intentar decodificar como JSON (nuevo formato)
        $ventanasJson = json_decode($parts[1], true);
        
        if ($ventanasJson && is_array($ventanasJson)) {
            $ventanas = $ventanasJson;
        } else {
            // Formato legacy: "09:00|18:00"
            $tiempos = explode('|', $parts[1]);
            if (count($tiempos) >= 2) {
                $ventanas = [
                    ['inicio' => $tiempos[0], 'fin' => $tiempos[1]]
                ];
            }
        }
    }
    
    // Si no hay ventanas válidas, usar valores por defecto
    if (empty($ventanas)) {
        $ventanas = [['inicio' => '09:00', 'fin' => '18:00']];
    }
    
    // Obtener intervalo de reservas
    $stmt = getPDO()->prepare("SELECT valor FROM configuraciones WHERE clave = 'intervalo_reservas'");
    $stmt->execute();
    $intervalo = intval($stmt->fetchColumn() ?: 30); // Por defecto 30 minutos
    
    // Generar todas las horas posibles para todas las ventanas
    $horasDisponibles = [];
    $horaInicioGlobal = null;
    $horaFinGlobal = null;
    
    foreach ($ventanas as $ventana) {
        $horaInicio = $ventana['inicio'];
        $horaFin = $ventana['fin'];
        
        // Validar formato de horas
        if (!preg_match('/^\d{2}:\d{2}$/', $horaInicio) || !preg_match('/^\d{2}:\d{2}$/', $horaFin)) {
            continue;
        }
        
        // Actualizar rango global para mostrar al usuario
        if ($horaInicioGlobal === null || $horaInicio < $horaInicioGlobal) {
            $horaInicioGlobal = $horaInicio;
        }
        if ($horaFinGlobal === null || $horaFin > $horaFinGlobal) {
            $horaFinGlobal = $horaFin;
        }
        
        // Generar horas para esta ventana
        $current = strtotime($horaInicio);
        $end = strtotime($horaFin);
        
        while ($current < $end) {
            $hora = date('H:i', $current);
            if (!in_array($hora, $horasDisponibles)) {
                $horasDisponibles[] = $hora;
            }
            $current += $intervalo * 60; // Convertir minutos a segundos
        }
    }
    
    // Ordenar las horas
    sort($horasDisponibles);
    
    // Obtener horas ya reservadas para esta fecha
    $stmt = getPDO()->prepare("SELECT TIME_FORMAT(hora, '%H:%i') as hora_reservada 
                           FROM reservas 
                           WHERE CAST(fecha AS CHAR) = CAST(? AS CHAR) 
                           AND estado IN ('pendiente', 'confirmada')");
    $stmt->execute([$fecha]);
    $horasReservadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Filtrar horas ya reservadas
    $horasDisponibles = array_diff($horasDisponibles, $horasReservadas);
    
    // Si es hoy, filtrar horas que ya pasaron
    if ($fecha === date('Y-m-d')) {
        $horaActual = date('H:i');
        $horasDisponibles = array_filter($horasDisponibles, function($hora) use ($horaActual) {
            return $hora > $horaActual;
        });
    }
    
    // Reindexar el array para mantener el orden correcto
    $horasDisponibles = array_values($horasDisponibles);
    
    // Preparar información de las ventanas horarias para mostrar al usuario
    $ventanasInfo = [];
    foreach ($ventanas as $ventana) {
        $ventanasInfo[] = $ventana['inicio'] . ' - ' . $ventana['fin'];
    }
    
    echo json_encode([
        'success' => true, 
        'horas' => $horasDisponibles,
        'dia_semana' => $diaSemana,
        'horario_inicio' => $horaInicioGlobal,
        'horario_fin' => $horaFinGlobal,
        'ventanas' => $ventanasInfo,
        'intervalo' => $intervalo,
        'total_ventanas' => count($ventanas)
    ]);
    
} catch (\PDOException $e) {
    error_log('Error al obtener horas disponibles: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al obtener las horas disponibles']);
} catch (\Exception $e) {
    error_log('Error general en horas disponibles: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>