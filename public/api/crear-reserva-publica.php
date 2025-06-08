<?php
/**
 * API para crear reservas desde formularios públicos
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

// Verificar que los datos requeridos estén presentes
$required = ['nombre', 'telefono', 'email', 'fecha', 'hora', 'usuario_id'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        echo json_encode(['success' => false, 'message' => "Campo requerido faltante: $field"]);
        exit;
    }
}

// Validaciones de formato
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email no válido']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['fecha'])) {
    echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
    exit;
}

if (!preg_match('/^\d{2}:\d{2}$/', $data['hora'])) {
    echo json_encode(['success' => false, 'message' => 'Formato de hora inválido']);
    exit;
}

if ($data['fecha'] < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'La fecha no puede ser anterior a hoy']);
    exit;
}

// Limpiar y preparar datos
$nombre = trim($data['nombre']);
$telefono = trim($data['telefono']);
$email = trim($data['email']);
$fecha = $data['fecha'];
$hora = $data['hora'] . ':00'; // Convertir a formato MySQL
$mensaje = trim($data['mensaje'] ?? '');
$usuarioId = intval($data['usuario_id']);
$formularioId = isset($data['formulario_id']) ? intval($data['formulario_id']) : null;

try {
    $pdo = getPDO();
    
    // Verificar que el usuario existe
    $stmt = $pdo->prepare('SELECT id, email FROM usuarios WHERE id = ? AND activo = 1');
    $stmt->execute([$usuarioId]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no válido']);
        exit;
    }
    
    // Obtener configuración de horarios del usuario
    $stmt = $pdo->prepare("SELECT clave, valor FROM configuraciones WHERE clave LIKE 'horario_%' OR clave = 'modo_aceptacion'");
    $stmt->execute();
    $configuraciones = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Obtener día de la semana
    $diasMap = [1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 5 => 'vie', 6 => 'sab', 0 => 'dom'];
    $diaSemana = $diasMap[date('w', strtotime($fecha))];
    
    // Verificar que el día está activo
    $horarioConfig = $configuraciones["horario_{$diaSemana}"] ?? 'false|[]';
    $parts = explode('|', $horarioConfig, 2);
    $diaActivo = $parts[0] === 'true';
    
    if (!$diaActivo) {
        echo json_encode(['success' => false, 'message' => 'El día seleccionado no está disponible']);
        exit;
    }
    
    // Obtener ventanas horarias con capacidad
    $ventanas = [];
    if (isset($parts[1])) {
        $ventanasJson = json_decode($parts[1], true);
        if ($ventanasJson && is_array($ventanasJson)) {
            $ventanas = $ventanasJson;
            // Asegurar que todas las ventanas tengan capacidad
            foreach ($ventanas as &$ventana) {
                if (!isset($ventana['capacidad'])) {
                    $ventana['capacidad'] = 1;
                }
            }
        }
    }
    
    if (empty($ventanas)) {
        echo json_encode(['success' => false, 'message' => 'No hay horarios configurados para este día']);
        exit;
    }
    
    // Verificar que la hora solicitada está dentro de alguna ventana horaria
    $horaReservada = substr($hora, 0, 5); // HH:MM
    $ventanaValida = null;
    $capacidadMaxima = 0;
    
    foreach ($ventanas as $ventana) {
        if ($horaReservada >= $ventana['inicio'] && $horaReservada < $ventana['fin']) {
            $ventanaValida = $ventana;
            $capacidadMaxima = max($capacidadMaxima, $ventana['capacidad']);
        }
    }
    
    if (!$ventanaValida) {
        echo json_encode(['success' => false, 'message' => 'La hora seleccionada no está disponible']);
        exit;
    }
    
    // Verificar capacidad disponible para esta fecha y hora
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as reservas_existentes
        FROM reservas 
        WHERE usuario_id = ? 
        AND fecha = ? 
        AND TIME_FORMAT(hora, '%H:%i') = ? 
        AND estado IN ('pendiente', 'confirmada')
    ");
    $stmt->execute([$usuarioId, $fecha, $horaReservada]);
    $reservasExistentes = intval($stmt->fetchColumn());
    
    if ($reservasExistentes >= $capacidadMaxima) {
        echo json_encode([
            'success' => false, 
            'message' => "No hay cupos disponibles para esta hora. Capacidad máxima: $capacidadMaxima, reservas actuales: $reservasExistentes"
        ]);
        exit;
    }
    
    // Verificar duplicados por email o teléfono en la misma fecha y hora
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM reservas 
        WHERE usuario_id = ? 
        AND fecha = ? 
        AND TIME_FORMAT(hora, '%H:%i') = ?
        AND (email = ? OR telefono = ?)
        AND estado IN ('pendiente', 'confirmada')
    ");
    $stmt->execute([$usuarioId, $fecha, $horaReservada, $email, $telefono]);
    $duplicados = intval($stmt->fetchColumn());
    
    if ($duplicados > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya tienes una reserva para esta fecha y hora']);
        exit;
    }
    
    // Determinar modo de aceptación
    $modoAceptacion = $configuraciones['modo_aceptacion'] ?? 'manual';
    $estado = $modoAceptacion === 'automatico' ? 'confirmada' : 'pendiente';
    
    // Generar token de acceso
    $accessToken = bin2hex(random_bytes(32));
    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Insertar la reserva
    $stmt = $pdo->prepare("
        INSERT INTO reservas (
            usuario_id, nombre, telefono, email, fecha, hora, mensaje, estado, 
            formulario_id, access_token, token_expires, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $result = $stmt->execute([
        $usuarioId, $nombre, $telefono, $email, $fecha, $hora, $mensaje, $estado,
        $formularioId, $accessToken, $tokenExpiry
    ]);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Error al crear la reserva']);
        exit;
    }
    
    $reservaId = $pdo->lastInsertId();
    
    // Registrar origen de la reserva
    if ($formularioId) {
        $stmt = $pdo->prepare("
            INSERT INTO origen_reservas (
                reserva_id, formulario_id, origen, ip_address, user_agent, created_at
            ) VALUES (?, ?, 'formulario_publico', ?, ?, NOW())
        ");
        $stmt->execute([
            $reservaId, 
            $formularioId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    // Enviar email de confirmación (simulado - implementar según necesidades)
    $emailEnviado = true; // En producción, implementar envío real
    
    // Información adicional sobre capacidad
    $capacidadInfo = [
        'capacidad_total' => $capacidadMaxima,
        'reservas_previas' => $reservasExistentes,
        'reservas_restantes' => $capacidadMaxima - $reservasExistentes - 1
    ];
    
    echo json_encode([
        'success' => true,
        'id' => $reservaId,
        'message' => $estado === 'confirmada' 
            ? 'Reserva confirmada automáticamente' 
            : 'Reserva creada. Te contactaremos para confirmarla',
        'confirmacion_automatica' => $estado === 'confirmada',
        'token' => $accessToken,
        'email_enviado' => $emailEnviado,
        'capacidad_info' => $capacidadInfo,
        'datos' => [
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'fecha' => $fecha,
            'hora' => substr($hora, 0, 5),
            'mensaje' => $mensaje,
            'estado' => $estado
        ]
    ]);
    
} catch (\PDOException $e) {
    error_log('Error PDO al crear reserva pública: ' . $e->getMessage());
    
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Ya existe una reserva con esos datos']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
    }
} catch (\Exception $e) {
    error_log('Error general al crear reserva pública: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>