<?php
// api/reserva-rechazar.php

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

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de reserva requerido']);
    exit;
}

try {
    $reservaDomain = getContainer()->getReservaDomain();
    
    // Rechazar reserva pendiente (cambia estado a "rechazada")
    $reserva = $reservaDomain->rechazarReserva((int)$data['id'], $userId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Reserva rechazada correctamente'
    ]);
    
} catch (\DomainException $e) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Exception $e) {
    error_log('Error rechazando reserva: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}