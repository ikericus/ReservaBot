<?php
// Cabeceras para JSON
header('Content-Type: application/json');

// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Obtener los datos enviados
$data = json_decode(file_get_contents('php://input'), true);

// Al inicio, después de obtener $data, agregar:
if (isset($data['estado']) && !isset($data['nombre'])) {
    // Es una actualización simple de solo estado
    // Obtener datos actuales de la reserva para mantener el resto
    $stmt = getPDO()->prepare('SELECT * FROM reservas WHERE id = ?');
    $stmt->execute([$data['id']]);
    $reservaActual = $stmt->fetch();
    
    if ($reservaActual) {
        $data['nombre'] = $reservaActual['nombre'];
        $data['telefono'] = $reservaActual['telefono'];
        $data['fecha'] = $reservaActual['fecha'];
        $data['hora'] = substr($reservaActual['hora'], 0, 5); // HH:MM sin segundos
        $data['mensaje'] = $reservaActual['mensaje'] ?? '';
    }
}

// Verificar que los datos requeridos estén presentes
if (!isset($data['id']) || !isset($data['nombre']) || !isset($data['telefono']) || !isset($data['fecha']) || !isset($data['hora'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

// Validar los datos
if (empty($data['nombre']) || empty($data['telefono']) || empty($data['fecha']) || empty($data['hora'])) {
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

try {
    // Preparar la consulta
    $sql = 'UPDATE reservas SET nombre = ?, telefono = ?, fecha = ?, hora = ?, mensaje = ?, estado = ? WHERE id = ?';
    $stmt = getPDO()->prepare($sql);
    
    // Establecer el estado predeterminado si no se proporciona
    $estado = isset($data['estado']) ? $data['estado'] : 'pendiente';
    
    // Convertir la hora al formato de MySQL (HH:MM:SS)
    $hora = $data['hora'] . ':00';
    
    // Ejecutar la consulta
    $result = $stmt->execute([
        $data['nombre'],
        $data['telefono'],
        $data['fecha'],
        $hora,
        $data['mensaje'] ?? '',
        $estado,
        $data['id']
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'id' => $data['id']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la reserva']);
    }
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>