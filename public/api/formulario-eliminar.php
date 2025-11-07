<?php
// api/formulario-eliminar.php

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

// Verificar autenticación
$currentUser = getAuthenticatedUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autenticado'
    ]);
    exit;
}

try {
    // Obtener datos del request
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($data['id'] ?? 0);
    $usuario_id = $currentUser['id'];
    
    if ($id <= 0) {
        throw new InvalidArgumentException('ID de formulario inválido');
    }
    
    // Usar el dominio para eliminar el formulario
    $formularioDomain = getContainer()->getFormularioDomain();
    
    $eliminado = $formularioDomain->eliminarFormulario($id, $usuario_id);
    
    if ($eliminado) {
        echo json_encode([
            'success' => true,
            'message' => 'Enlace eliminado correctamente'
        ]);
    } else {
        throw new InvalidArgumentException('Formulario no encontrado o no tienes permisos');
    }
    
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en eliminar formulario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar el enlace'
    ]);
}