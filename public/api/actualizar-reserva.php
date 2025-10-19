<?php
// api/actualizar-reserva.php

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
    
    // Si solo cambia estado
    if (isset($data['estado']) && !isset($data['fecha'])) {
        $reservaId = (int)$data['id'];
        
        if ($data['estado'] === 'confirmada') {
            $reserva = $reservaDomain->confirmarReserva($reservaId, $userId);
        } elseif ($data['estado'] === 'cancelada') {
            $reserva = $reservaDomain->cancelarReserva($reservaId, $userId);
        } else {
            throw new \InvalidArgumentException('Estado no válido');
        }
    } else {
        // Modificación completa
        if (!isset($data['fecha'], $data['hora'])) {
            throw new \InvalidArgumentException('Fecha y hora requeridas');
        }
        
        $reserva = $reservaDomain->modificarReserva(
            (int)$data['id'],
            $userId,
            new DateTime($data['fecha']),
            $data['hora'],
            $data['mensaje'] ?? null
        );
        
        // Si además cambia el estado
        if (isset($data['estado'])) {
            if ($data['estado'] === 'confirmada') {
                $reserva = $reservaDomain->confirmarReserva($reserva->getId(), $userId);
            } elseif ($data['estado'] === 'cancelada') {
                $reserva = $reservaDomain->cancelarReserva($reserva->getId(), $userId);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'id' => $reserva->getId(),
        'reserva' => $reserva->toArray()
    ]);
    
} catch (\DomainException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Exception $e) {
    error_log('Error actualizando reserva: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}