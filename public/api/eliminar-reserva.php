<?php
// Cabeceras para JSON
header('Content-Type: application/json');

// Incluir configuración y funciones
require_once '../includes/db-config.php';
require_once '../includes/functions.php';

// Obtener los datos enviados
$data = json_decode(file_get_contents('php://input'), true);

// Verificar que el ID esté presente
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

try {
    // Preparar la consulta
    $stmt = $pdo->prepare('DELETE FROM reservas WHERE id = ?');
    
    // Ejecutar la consulta
    $result = $stmt->execute([$data['id']]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la reserva']);
    }
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>