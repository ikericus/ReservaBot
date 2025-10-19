<?php
// api/horas-disponibles.php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['fecha']) || empty($data['fecha'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Fecha no proporcionada']);
    exit;
}

if (!isset($data['usuario_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
    exit;
}

$fecha = $data['fecha'];
$usuarioId = (int)$data['usuario_id'];

// Validar formato
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
    exit;
}

// Validar que no sea pasada
if ($fecha < date('Y-m-d')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La fecha no puede ser anterior a hoy']);
    exit;
}

try {
    $fechaObj = new DateTime($fecha);
    $reservaDomain = getContainer()->getReservaDomain();
    
    // Usar método del dominio que encapsula toda la lógica
    $resultado = $reservaDomain->obtenerHorasDisponiblesConCapacidad($fechaObj, $usuarioId);
    
    echo json_encode(array_merge(['success' => true], $resultado));
    
} catch (\DomainException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Exception $e) {
    error_log('Error obteniendo horas disponibles: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}