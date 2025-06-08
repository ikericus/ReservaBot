<?php
/**
 * API para obtener horas disponibles para una fecha específica
 * ACTUALIZADA para soportar múltiples ventanas horarias y capacidad de reservas simultáneas
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
$usuarioId = $data['usuario_id'] ?? null;

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
    $whereClause = $usuarioId ? 'WHERE usuario_id = ?' : "WHERE clave LIKE 'horario_%' OR clave = 'intervalo_reservas'";
    $params = $usuarioId ? [$usuarioId] : [];
    
    if ($usuarioId) {
        $stmt = getPDO()->prepare("SELECT clave, valor FROM configuraciones_usuario WHERE usuario_id = ? AND (clave LIKE 'horario_%' OR clave = 'intervalo_reservas')");
        $stmt->execute([$usuarioId]);
    } else {
        $stmt = getPDO()->prepare("SELECT clave, valor FROM configuraciones WHERE clave LIKE 'horario_%' OR clave = 'intervalo_reservas'");
        $stmt->execute();
    }
    
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
        // Intentar decodificar como JSON (formato nuevo con capacidad)
        $ventanasJson = json_decode($parts[1], true);
        
        if ($ventanasJson && is_array($ventanasJson)) {
            $ventanas = $ventanasJson;
            // Asegurar que todas las ventanas tengan capacidad
            foreach ($ventanas as &$ventana) {
                if (!isset($ventana['capacidad'])) {
                    $ventana['capacidad'] = 1;
                }
            }
        } else {
            // Formato legacy: "09:00|18:00"
            $tiempos = explode('|', $parts[1]);
            if (count($tiempos) >= 2) {
                $ventanas = [
                    ['inicio' => $tiempos[0], 'fin' => $tiempos[1], 'capacidad' => 1]
                ];
            }
        }
    }
    
    // Si no hay ventanas válidas, usar valores por defecto
    if (empty($ventanas)) {
        $ventanas = [['inicio' => '09:00', 'fin' => '18:00', 'capacidad' => 1]];
    }
    
    // Obtener intervalo de reservas
    $intervalo = intval($configHorarios['intervalo_reservas'] ?? 30); // Por defecto 30 minutos
    
    // Obtener reservas existentes para esta fecha
    $whereUsuario = $usuarioId ? 'AND usuario_id = ?' : '';
    $paramsReservas = $usuarioId ? [$fecha, $usuarioId] : [$fecha];
    
    $stmt = getPDO()->prepare("
        SELECT TIME_FORMAT(hora, '%H:%i') as hora_reservada, COUNT(*) as cantidad
        FROM reservas 
        WHERE fecha = ? $whereUsuario
        AND estado IN ('pendiente', 'confirmada')
        GROUP BY TIME_FORMAT(hora, '%H:%i')
    ");
    $stmt->execute($paramsReservas);
    $reservasExistentes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Generar todas las horas posibles para todas las ventanas
    $horasDisponibles = [];
    $horaInicioGlobal = null;
    $horaFinGlobal = null;
    $ventanasInfo = [];
    $horasConCapacidad = []; // Array para almacenar info de capacidad
    
    foreach ($ventanas as $ventana) {
        $horaInicio = $ventana['inicio'];
        $horaFin = $ventana['fin'];
        $capacidad = intval($ventana['capacidad'] ?? 1);
        
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
        
        // Añadir información de la ventana
        $ventanasInfo[] = $horaInicio . ' - ' . $horaFin . ' (máx. ' . $capacidad . ' reservas)';
        
        // Generar horas para esta ventana
        $current = strtotime($horaInicio);
        $end = strtotime($horaFin);
        
        while ($current < $end) {
            $hora = date('H:i', $current);
            
            // Verificar capacidad disponible para esta hora
            $reservasEnHora = intval($reservasExistentes[$hora] ?? 0);
            $disponible = $reservasEnHora < $capacidad;
            
            if ($disponible) {
                if (!in_array($hora, $horasDisponibles)) {
                    $horasDisponibles[] = $hora;
                }
                
                // Almacenar información de capacidad
                if (!isset($horasConCapacidad[$hora])) {
                    $horasConCapacidad[$hora] = [
                        'capacidad_total' => $capacidad,
                        'reservas_actuales' => $reservasEnHora,
                        'disponibles' => $capacidad - $reservasEnHora
                    ];
                } else {
                    // Si la hora ya existe en otra ventana, usar la mayor capacidad
                    if ($capacidad > $horasConCapacidad[$hora]['capacidad_total']) {
                        $horasConCapacidad[$hora]['capacidad_total'] = $capacidad;
                        $horasConCapacidad[$hora]['disponibles'] = $capacidad - $reservasEnHora;
                    }
                }
            }
            
            $current += $intervalo * 60; // Convertir minutos a segundos
        }
    }
    
    // Ordenar las horas
    sort($horasDisponibles);
    
    // Si es hoy, filtrar horas que ya pasaron
    if ($fecha === date('Y-m-d')) {
        $horaActual = date('H:i');
        $horasDisponibles = array_filter($horasDisponibles, function($hora) use ($horaActual) {
            return $hora > $horaActual;
        });
    }
    
    // Reindexar el array para mantener el orden correcto
    $horasDisponibles = array_values($horasDisponibles);
    
    // Preparar información adicional sobre capacidad
    $resumenCapacidad = [];
    foreach ($horasDisponibles as $hora) {
        if (isset($horasConCapacidad[$hora])) {
            $info = $horasConCapacidad[$hora];
            $resumenCapacidad[$hora] = [
                'total' => $info['capacidad_total'],
                'ocupadas' => $info['reservas_actuales'],
                'libres' => $info['disponibles']
            ];
        }
    }
    
    echo json_encode([
        'success' => true, 
        'horas' => $horasDisponibles,
        'dia_semana' => $diaSemana,
        'horario_inicio' => $horaInicioGlobal,
        'horario_fin' => $horaFinGlobal,
        'ventanas' => $ventanasInfo,
        'intervalo' => $intervalo,
        'total_ventanas' => count($ventanas),
        'capacidad_info' => $resumenCapacidad,
        'tiene_capacidad_multiple' => array_filter($ventanas, function($v) { return ($v['capacidad'] ?? 1) > 1; }) ? true : false
    ]);
    
} catch (\PDOException $e) {
    error_log('Error al obtener horas disponibles: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al obtener las horas disponibles']);
} catch (\Exception $e) {
    error_log('Error general en horas disponibles: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>