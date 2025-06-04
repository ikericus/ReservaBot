<?php
// API para actualizar reservas públicas mediante token
header('Content-Type: application/json');

// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$data = $_POST;

// Verificar datos requeridos
if (!isset($data['token']) || !isset($data['reserva_id'])) {
    echo json_encode(['success' => false, 'message' => 'Token y ID de reserva requeridos']);
    exit;
}

$token = trim($data['token']);
$reservaId = intval($data['reserva_id']);
$action = $data['action'] ?? '';

// Validar token y obtener reserva
try {
    $stmt = getPDO()->prepare("
        SELECT * FROM reservas 
        WHERE id = ? 
        AND access_token = ? 
        AND token_expires > NOW() 
        AND estado IN ('pendiente', 'confirmada')
    ");
    $stmt->execute([$reservaId, $token]);
    $reserva = $stmt->fetch();
    
    if (!$reserva) {
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada o token inválido']);
        exit;
    }
    
    // Verificar que puede modificar (24h antes)
    $fechaHoraReserva = new DateTime($reserva['fecha'] . ' ' . $reserva['hora']);
    $fechaLimite = clone $fechaHoraReserva;
    $fechaLimite->sub(new DateInterval('PT24H'));
    $ahora = new DateTime();
    
    if ($ahora >= $fechaLimite) {
        echo json_encode(['success' => false, 'message' => 'No se puede modificar la reserva. El plazo límite ha expirado (24h antes de la cita)']);
        exit;
    }
    
} catch (Exception $e) {
    error_log('Error validando reserva para modificación: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al validar la reserva']);
    exit;
}

// Procesar acción
if ($action === 'modificar') {
    // Validar nuevos datos
    if (!isset($data['nueva_fecha']) || !isset($data['nueva_hora'])) {
        echo json_encode(['success' => false, 'message' => 'Nueva fecha y hora requeridas']);
        exit;
    }
    
    $nuevaFecha = trim($data['nueva_fecha']);
    $nuevaHora = trim($data['nueva_hora']);
    
    // Validar formato de fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $nuevaFecha)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
        exit;
    }
    
    // Validar formato de hora
    if (!preg_match('/^\d{2}:\d{2}$/', $nuevaHora)) {
        echo json_encode(['success' => false, 'message' => 'Formato de hora inválido']);
        exit;
    }
    
    // Validar que la nueva fecha no sea anterior a hoy
    if ($nuevaFecha < date('Y-m-d')) {
        echo json_encode(['success' => false, 'message' => 'La nueva fecha no puede ser anterior a hoy']);
        exit;
    }
    
    try {
        // Verificar que la nueva fecha/hora esté disponible
        $stmt = getPDO()->prepare("
            SELECT COUNT(*) FROM reservas 
            WHERE usuario_id = ? 
            AND fecha = ? 
            AND TIME_FORMAT(hora, '%H:%i') = ? 
            AND estado IN ('pendiente', 'confirmada') 
            AND id != ?
        ");
        $stmt->execute([$reserva['usuario_id'], $nuevaFecha, $nuevaHora, $reservaId]);
        $existeConflicto = $stmt->fetchColumn();
        
        if ($existeConflicto > 0) {
            echo json_encode(['success' => false, 'message' => 'La nueva fecha y hora ya están ocupadas']);
            exit;
        }
        
        // Verificar que la nueva fecha/hora esté dentro de horarios disponibles
        $diaSemana = date('w', strtotime($nuevaFecha));
        $diasMap = [1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 5 => 'vie', 6 => 'sab', 0 => 'dom'];
        $diaConfig = $diasMap[$diaSemana];
        
        $stmt = getPDO()->prepare("SELECT valor FROM configuraciones WHERE clave = ?");
        $stmt->execute(["horario_{$diaConfig}"]);
        $horarioConfig = $stmt->fetchColumn();
        
        if (!$horarioConfig) {
            echo json_encode(['success' => false, 'message' => 'Día no disponible para reservas']);
            exit;
        }
        
        $parts = explode('|', $horarioConfig, 2);
        $activo = $parts[0] === 'true';
        
        if (!$activo) {
            echo json_encode(['success' => false, 'message' => 'El día seleccionado no está disponible']);
            exit;
        }
        
        // Validar que la hora esté dentro del rango (simplificado)
        if (isset($parts[1])) {
            $ventanas = json_decode($parts[1], true);
            if (is_array($ventanas)) {
                $horaValida = false;
                foreach ($ventanas as $ventana) {
                    if ($nuevaHora >= $ventana['inicio'] && $nuevaHora < $ventana['fin']) {
                        $horaValida = true;
                        break;
                    }
                }
                if (!$horaValida) {
                    echo json_encode(['success' => false, 'message' => 'La hora seleccionada está fuera del horario disponible']);
                    exit;
                }
            }
        }
        
        // Actualizar la reserva
        $stmt = getPDO()->prepare("
            UPDATE reservas 
            SET fecha = ?, hora = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $result = $stmt->execute([$nuevaFecha, $nuevaHora . ':00', $reservaId]);
        
        if ($result) {
            // Log de la modificación
            error_log("Reserva modificada por cliente - ID: {$reservaId}, Nueva fecha: {$nuevaFecha}, Nueva hora: {$nuevaHora}");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Reserva modificada exitosamente',
                'nueva_fecha' => $nuevaFecha,
                'nueva_hora' => $nuevaHora
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la reserva en la base de datos']);
        }
        
    } catch (PDOException $e) {
        error_log('Error modificando reserva pública: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
    } catch (Exception $e) {
        error_log('Error general modificando reserva pública: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
    
} elseif ($action === 'cancelar') {
    // Cancelar reserva
    try {
        $stmt = getPDO()->prepare("
            UPDATE reservas 
            SET estado = 'cancelada', updated_at = NOW() 
            WHERE id = ?
        ");
        $result = $stmt->execute([$reservaId]);
        
        if ($result) {
            // Log de la cancelación
            error_log("Reserva cancelada por cliente - ID: {$reservaId}");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Reserva cancelada exitosamente'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al cancelar la reserva']);
        }
        
    } catch (PDOException $e) {
        error_log('Error cancelando reserva pública: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
    } catch (Exception $e) {
        error_log('Error general cancelando reserva pública: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>