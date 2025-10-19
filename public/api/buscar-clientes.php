<?php
// api/buscar-clientes.php

header('Content-Type: application/json');

$user = getAuthenticatedUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit;
}

if (!isset($data['telefono']) || empty(trim($data['telefono']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Teléfono requerido']);
    exit;
}

try {
    $clienteDomain = getContainer()->getClienteDomain();
    $usuarioId = $user['id'];
    $telefono = trim($data['telefono']);
    
    $clientes = $clienteDomain->buscarPorTelefono($telefono, $usuarioId, 10);
    
    echo json_encode([
        'success' => true,
        'clientes' => $clientes,
        'total' => count($clientes),
        'busqueda' => $telefono
    ]);
    
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Exception $e) {
    error_log('Error en búsqueda de clientes: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}