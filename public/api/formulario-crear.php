<?php
// api/formulario-crear.php

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
    
    if (!$data) {
        throw new InvalidArgumentException('Datos inválidos');
    }
    
    $usuario_id = $currentUser['id'];
    
    // Usar el dominio para crear el formulario
    $formularioDomain = getContainer()->getFormularioDomain();
    
    $formulario = $formularioDomain->crearFormulario([
        'nombre' => trim($data['nombre'] ?? ''),
        'descripcion' => !empty($data['descripcion']) ? trim($data['descripcion']) : null,
        'confirmacion_automatica' => $data['confirmacion_automatica'] ?? false,
        'activo' => true,
    ], $usuario_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Enlace de reserva creado correctamente',
        'data' => $formulario->toArray()
    ]);
    
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error de validación: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en crear formulario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear el formulario'
    ]);
}