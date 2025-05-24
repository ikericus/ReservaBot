<?php
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

// Obtener los datos enviados
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log para debugging (opcional, comentar en producción)
// error_log('Datos recibidos en crear-reserva: ' . $input);

// Verificar que los datos se decodificaron correctamente
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit;
}

// Verificar que los datos requeridos estén presentes
if (!isset($data['nombre']) || !isset($data['telefono']) || !isset($data['fecha']) || !isset($data['hora'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos: ' . implode(', ', array_diff(['nombre', 'telefono', 'fecha', 'hora'], array_keys($data)))]);
    exit;
}

// Validar los datos
if (empty(trim($data['nombre'])) || empty(trim($data['telefono'])) || empty($data['fecha']) || empty($data['hora'])) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben estar completos']);
    exit;
}

// Validar el formato de la fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['fecha'])) {
    echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido (debe ser YYYY-MM-DD)']);
    exit;
}

// Validar el formato de la hora
if (!preg_match('/^\d{2}:\d{2}$/', $data['hora'])) {
    echo json_encode(['success' => false, 'message' => 'Formato de hora inválido (debe ser HH:MM)']);
    exit;
}

// Validar que la fecha no sea anterior a hoy
if ($data['fecha'] < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'La fecha no puede ser anterior a hoy']);
    exit;
}

try {
    // Verificar que no exista ya una reserva para esa fecha y hora
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservas WHERE fecha = ? AND TIME_FORMAT(hora, "%H:%i") = ? AND estado IN ("pendiente", "confirmada")');
    $stmt->execute([$data['fecha'], $data['hora']]);
    $existeReserva = $stmt->fetchColumn();
    
    if ($existeReserva > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe una reserva para esa fecha y hora']);
        exit;
    }
    
    // Preparar la consulta
    $sql = 'INSERT INTO reservas (nombre, telefono, fecha, hora, mensaje, estado, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())';
    $stmt = $pdo->prepare($sql);
    
    // Establecer el estado predeterminado si no se proporciona
    $estado = isset($data['estado']) ? $data['estado'] : 'pendiente';
    
    // Convertir la hora al formato de MySQL (HH:MM:SS)
    $hora = $data['hora'] . ':00';
    
    // Limpiar y preparar datos
    $nombre = trim($data['nombre']);
    $telefono = trim($data['telefono']);
    $mensaje = isset($data['mensaje']) ? trim($data['mensaje']) : '';
    
    // Ejecutar la consulta
    $result = $stmt->execute([
        $nombre,
        $telefono,
        $data['fecha'],
        $hora,
        $mensaje,
        $estado
    ]);
    
    if ($result) {
        $id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true, 
            'id' => $id,
            'message' => 'Reserva creada correctamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear la reserva en la base de datos']);
    }
    
} catch (\PDOException $e) {
    // Log del error real (no mostrar al usuario por seguridad)
    error_log('Error al crear reserva: ' . $e->getMessage());
    
    // Mensajes de error específicos según el código
    if ($e->getCode() == 23000) { // Violación de restricción única
        echo json_encode(['success' => false, 'message' => 'Ya existe una reserva con esos datos']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos. Por favor, intente nuevamente.']);
    }
} catch (\Exception $e) {
    error_log('Error general al crear reserva: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>