<?php
/**
 * API para actualizar configuración del sistema
 * ACTUALIZADA para soportar capacidad de reservas simultáneas
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

// Obtener los datos enviados
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verificar que los datos se decodificaron correctamente
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit;
}

// Verificar que hay datos para procesar
if (empty($data)) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos para actualizar']);
    exit;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();
    
    $configuracionesActualizadas = [];
    
    foreach ($data as $clave => $valor) {
        // Validar clave de configuración
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $clave)) {
            throw new Exception("Clave de configuración inválida: $clave");
        }
        
        // Validaciones específicas según el tipo de configuración
        switch ($clave) {
            case 'intervalo_reservas':
                $intervalosValidos = [15, 30, 45, 60, 90, 120];
                if (!in_array(intval($valor), $intervalosValidos)) {
                    throw new Exception('Intervalo de reservas no válido');
                }
                break;
                
            default:
                // Validar horarios de días de la semana con capacidad
                if (preg_match('/^horario_(lun|mar|mie|jue|vie|sab|dom)$/', $clave)) {
                    if (!validateHorarioConfig($valor)) {
                        throw new Exception("Configuración de horario inválida para $clave");
                    }
                }
                break;
        }
        
        // Verificar si la configuración ya existe
        $stmt = $pdo->prepare('SELECT id FROM configuraciones WHERE clave = ?');
        $stmt->execute([$clave]);
        $existe = $stmt->fetchColumn();
        
        if ($existe) {
            // Actualizar configuración existente
            $stmt = $pdo->prepare('UPDATE configuraciones SET valor = ?, updated_at = NOW() WHERE clave = ?');
            $result = $stmt->execute([$valor, $clave]);
        } else {
            // Crear nueva configuración
            $stmt = $pdo->prepare('INSERT INTO configuraciones (clave, valor, created_at, updated_at) VALUES (?, ?, NOW(), NOW())');
            $result = $stmt->execute([$clave, $valor]);
        }
        
        if ($result) {
            $configuracionesActualizadas[] = $clave;
        } else {
            throw new Exception("Error al actualizar configuración: $clave");
        }
    }
    
    $pdo->commit();
    
    // Log de auditoría
    error_log('Configuraciones actualizadas: ' . implode(', ', $configuracionesActualizadas));
    
    echo json_encode([
        'success' => true,
        'message' => 'Configuración actualizada correctamente',
        'configuraciones_actualizadas' => $configuracionesActualizadas,
        'total' => count($configuracionesActualizadas)
    ]);
    
} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Error al actualizar configuración: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al actualizar la configuración: ' . $e->getMessage()
    ]);
}

/**
 * Validar configuración de horario con capacidad
 */
function validateHorarioConfig($horarioConfig) {
    // Formato esperado: "true|[{'inicio':'09:00','fin':'18:00','capacidad':1}]" o "false|[]"
    $parts = explode('|', $horarioConfig, 2);
    
    if (count($parts) !== 2) {
        return false;
    }
    
    $activo = $parts[0];
    $ventanasJson = $parts[1];
    
    // Validar estado activo
    if (!in_array($activo, ['true', 'false'])) {
        return false;
    }
    
    // Si está inactivo, puede tener array vacío
    if ($activo === 'false') {
        return $ventanasJson === '[]';
    }
    
    // Validar estructura de ventanas
    $ventanas = json_decode($ventanasJson, true);
    
    if (!is_array($ventanas)) {
        return false;
    }
    
    // Debe haber al menos una ventana si está activo
    if (empty($ventanas)) {
        return false;
    }
    
    // Validar cada ventana
    foreach ($ventanas as $ventana) {
        if (!is_array($ventana)) {
            return false;
        }
        
        // Verificar campos requeridos
        if (!isset($ventana['inicio']) || !isset($ventana['fin'])) {
            return false;
        }
        
        // Validar formato de horas
        if (!preg_match('/^\d{2}:\d{2}$/', $ventana['inicio']) || 
            !preg_match('/^\d{2}:\d{2}$/', $ventana['fin'])) {
            return false;
        }
        
        // Validar capacidad
        $capacidad = $ventana['capacidad'] ?? 1;
        if (!is_numeric($capacidad) || $capacidad < 1 || $capacidad > 50) {
            return false;
        }
        
        // Validar que hora inicio < hora fin
        if (strtotime($ventana['inicio']) >= strtotime($ventana['fin'])) {
            return false;
        }
    }
    
    return true;
}
?>