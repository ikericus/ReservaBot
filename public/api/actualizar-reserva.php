<?php
// Cabeceras para JSON
header('Content-Type: application/json');

// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Obtener los datos enviados
$data = json_decode(file_get_contents('php://input'), true);

// Verificar que los datos sean válidos
if (!isset($data['id']) || !isset($data['estado'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Validar que el estado sea válido
if (!in_array($data['estado'], ['pendiente', 'confirmada'])) {
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit;
}

try {
    // Preparar la consulta
    $stmt = getPDO()->prepare('UPDATE reservas SET estado = ? WHERE id = ?');
    
    // Ejecutar la consulta
    $result = $stmt->execute([$data['estado'], $data['id']]);
    
    // Verificar el resultado
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró la reserva o no se realizaron cambios']);
    }
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>