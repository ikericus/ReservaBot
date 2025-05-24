<?php
/**
 * API para obtener horas disponibles para una fecha específica
 */

// Cabeceras para JSON
header('Content-Type: application/json');

// Incluir configuración y funciones
require_once '../includes/db-config.php';
require_once '../includes/functions.php';

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
    $stmt = $pdo->prepare("SELECT clave, valor FROM configuraciones WHERE clave LIKE 'horario_%'");
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
    $horarioConfig = $configHorarios["horario_{$diaSemana}"] ?? 'false|09:00|18:00';
    list($activo, $horaInicio, $horaFin) = explode('|', $horarioConfig);
    
    // Verificar si el día está activo
    if ($activo !== 'true') {
        echo json_encode(['success' => false, 'message' => 'El día seleccionado no está disponible']);
        exit;
    }
    
    // Obtener intervalo de reservas de la configuración
    $stmt = $pdo->prepare("SELECT valor FROM configuraciones WHERE clave = 'intervalo_reservas'");
    $stmt->execute();
    $intervalo = intval($stmt->fetchColumn() ?: 30); // Por defecto 30 minutos
    
    // Generar todas las horas posibles
    $horas = [];
    $current = strtotime($horaInicio);
    $end = strtotime($horaFin);
    
    while ($current < $end) {
        $horas[] = date('H:i', $current);
        $current += $intervalo * 60; // Convertir minutos a segundos
    }
    
    // Obtener horas ya reservadas para esta fecha
    $stmt = $pdo->prepare("SELECT TIME_FORMAT(hora, '%H:%i') as hora_reservada 
                           FROM reservas 
                           WHERE fecha = ? AND estado IN ('pendiente', 'confirmada')");
    $stmt->execute([$fecha]);
    $horasReservadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Filtrar horas ya reservadas
    $horasDisponibles = array_diff($horas, $horasReservadas);
    
    // Si es hoy, filtrar horas que ya pasaron
    if ($fecha === date('Y-m-d')) {
        $horaActual = date('H:i');
        $horasDisponibles = array_filter($horasDisponibles, function($hora) use ($horaActual) {
            return $hora > $horaActual;
        });
    }
    
    // Reindexar el array para mantener el orden correcto
    $horasDisponibles = array_values($horasDisponibles);
    
    echo json_encode([
        'success' => true, 
        'horas' => $horasDisponibles,
        'dia_semana' => $diaSemana,
        'horario_inicio' => $horaInicio,
        'horario_fin' => $horaFin,
        'intervalo' => $intervalo
    ]);
    
} catch (\PDOException $e) {
    error_log('Error al obtener horas disponibles: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al obtener las horas disponibles']);
} catch (\Exception $e) {
    error_log('Error general en horas disponibles: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>