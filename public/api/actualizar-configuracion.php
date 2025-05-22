<?php
// Cabeceras para JSON
header('Content-Type: application/json');

// Incluir configuración y funciones
require_once '../includes/db-config.php';
require_once '../includes/functions.php';

// Obtener los datos enviados
$data = json_decode(file_get_contents('php://input'), true);

// Validar los datos
if (empty($data)) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos']);
    exit;
}

try {
    // Iniciar una transacción
    $pdo->beginTransaction();
    
    // Preparar la consulta para actualizar o insertar
    $stmt = $pdo->prepare('
        INSERT INTO configuracion (clave, valor) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE valor = ?
    ');
    
    // Procesar cada elemento de configuración
    foreach ($data as $clave => $valor) {
        $stmt->execute([$clave, $valor, $valor]);
    }
    
    // Confirmar la transacción
    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch (\PDOException $e) {
    // Revertir la transacción si hay un error
    $pdo->rollBack();
    
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>