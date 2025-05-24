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

// Verificar que los datos se decodificaron correctamente
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit;
}

// Verificar que los datos requeridos estén presentes
if (!isset($data['nombre']) || !isset($data['telefono']) || !isset($data['fecha']) || !isset($data['hora'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
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
    // Verificar reservas existentes usando CAST para evitar problemas de collation
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM reservas 
        WHERE CAST(fecha AS CHAR) = CAST(? AS CHAR) 
        AND TIME_FORMAT(hora, "%H:%i") = CAST(? AS CHAR) 
        AND estado IN ("pendiente", "confirmada")
    ');
    $stmt->execute([$data['fecha'], $data['hora']]);
    $existeReserva = $stmt->fetchColumn();
    
    if ($existeReserva > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe una reserva para esa fecha y hora']);
        exit;
    }
    
    // Preparar la consulta de inserción
    $sql = 'INSERT INTO reservas (nombre, telefono, fecha, hora, mensaje, estado) VALUES (?, ?, ?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);
    
    // Establecer el estado predeterminado
    $estado = 'pendiente';
    
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
    echo json_encode([
        'success' => false, 
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (\Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>