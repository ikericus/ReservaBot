<?php
// api/crear-reserva-publica.php
header('Content-Type: application/json');

// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email-functions.php';

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
if (!isset($data['nombre']) || !isset($data['email']) || !isset($data['telefono']) || !isset($data['fecha']) || !isset($data['hora'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

if (!isset($data['usuario_id']) || empty($data['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
    exit;
}

if (!isset($data['formulario_id']) || empty($data['formulario_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de formulario requerido']);
    exit;
}

// Validar los datos
if (empty(trim($data['nombre'])) || empty(trim($data['telefono'])) || empty($data['fecha']) || empty($data['hora'])) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben estar completos']);
    exit;
}

// Validar el formato de la fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['fecha'])) {
    echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
    exit;
}

// Validar el formato de la hora
if (!preg_match('/^\d{2}:\d{2}$/', $data['hora'])) {
    echo json_encode(['success' => false, 'message' => 'Formato de hora inválido']);
    exit;
}

// Validar que la fecha no sea anterior a hoy
if ($data['fecha'] < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'La fecha no puede ser anterior a hoy']);
    exit;
}

try {
    // Verificar que no exista ya una reserva para esa fecha y hora
    $stmt = getPDO()->prepare('SELECT COUNT(*) FROM reservas WHERE CAST(fecha AS CHAR) = CAST(? AS CHAR) AND TIME_FORMAT(hora, "%H:%i") = CAST(? AS CHAR) AND estado IN ("pendiente", "confirmada")');
    $stmt->execute([$data['fecha'], $data['hora']]);
    $existeReserva = $stmt->fetchColumn();
    
    if ($existeReserva > 0) {
        echo json_encode(['success' => false, 'message' => 'La hora seleccionada ya no está disponible. Por favor elige otra hora.']);
        exit;
    }
    
    // Obtener la configuración específica del formulario
    $stmt = getPDO()->prepare("SELECT confirmacion_automatica FROM formularios_publicos WHERE id = ?");
    $stmt->execute([intval($data['formulario_id'])]);
    $confirmacionAutomatica = $stmt->fetchColumn();

    // Determinar el estado de la reserva basado en la configuración del formulario
    $estado = ($confirmacionAutomatica == 1) ? 'confirmada' : 'pendiente';
    
    $sql = 'INSERT INTO reservas (usuario_id, nombre, email, telefono, fecha, hora, mensaje, estado, access_token, token_expires, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())';
    $stmt = getPDO()->prepare($sql);
    
    // Convertir la hora al formato de MySQL (HH:MM:SS)
    $hora = $data['hora'] . ':00';
    
    // Limpiar y preparar datos
    $nombre = trim($data['nombre']);
    $email = trim($data['email']);
    $telefono = trim($data['telefono']);
    $mensaje = isset($data['mensaje']) ? trim($data['mensaje']) : '';
    
    // Generar token de acceso único para el cliente
    $accessToken = bin2hex(random_bytes(32)); // 64 caracteres hex

    // Ejecutar la consulta
    $result = $stmt->execute([
        intval($data['usuario_id']),
        $nombre,
        $email,
        $telefono,
        $data['fecha'],
        $hora,
        $mensaje,
        $estado,
        $accessToken
    ]);
    
    if ($result) {
        $id = getPDO()->lastInsertId();
        
        // Obtener información del formulario para el email
        $stmt = getPDO()->prepare("SELECT nombre FROM formularios_publicos WHERE id = ?");
        $stmt->execute([intval($data['formulario_id'])]);
        $formularioNombre = $stmt->fetchColumn() ?: 'Reserva';
        
        // Enviar email de confirmación
        $emailEnviado = false;
        try {
            $emailEnviado = enviarEmailConfirmacion([
                'id' => $id,
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'fecha' => $data['fecha'],
                'hora' => $data['hora'],
                'mensaje' => $mensaje,
                'estado' => $estado,
                'access_token' => $accessToken,
                'formulario_nombre' => $formularioNombre,
                'usuario_id' => intval($data['usuario_id'])
            ]);
        } catch (Exception $e) {
            error_log('Error enviando email de confirmación: ' . $e->getMessage());
            // No fallar la reserva por error de email
        }
        
        // Respuesta de éxito con información del email
        echo json_encode([
            'success' => true, 
            'id' => $id,
            'token' => $accessToken,
            'message' => $estado === 'confirmada' 
                ? 'Tu reserva ha sido confirmada automáticamente. ¡Te esperamos!' 
                : 'Tu solicitud de reserva ha sido recibida. Te contactaremos pronto para confirmarla.',
            'estado' => $estado,
            'confirmacion_automatica' => $confirmacionAutomatica == 1,
            'email_enviado' => $emailEnviado,
            'datos' => [
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'fecha' => $data['fecha'],
                'hora' => $data['hora'],
                'mensaje' => $mensaje
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear la reserva en la base de datos']);
    }
    
} catch (\PDOException $e) {
    error_log('Error al crear reserva pública: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (\Exception $e) {
    error_log('Error general al crear reserva pública: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>