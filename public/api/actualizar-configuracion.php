<?php
// api/actualizar-configuracion.php

header('Content-Type: application/json');

$user = getAuthenticatedUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$userId = $user['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit;
}

if (empty($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos para actualizar']);
    exit;
}

try {
    $configuracionDomain = getContainer()->getConfiguracionDomain();
    
    // Delegar toda la validación y actualización al dominio
    $resultado = $configuracionDomain->actualizarMultiples($data, $userId);
    
    debug_log('Configuraciones actualizadas para usuario: ' . $userId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Configuración actualizada correctamente'
    ]);
    
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\DomainException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Exception $e) {
    error_log('Error actualizando configuración: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}