<?php
// api/reserva-publica-actualizar.php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = $_POST;

if (!isset($data['token'], $data['reserva_id'], $data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token, ID de reserva y acción requeridos']);
    exit;
}

$token = trim($data['token']);
$reservaId = (int)$data['reserva_id'];
$action = $data['action'];

try {
    $reservaDomain = getContainer()->getReservaDomain();
    
    if ($action === 'modificar') {
        if (!isset($data['nueva_fecha'], $data['nueva_hora'])) {
            throw new \InvalidArgumentException('Nueva fecha y hora requeridas');
        }
        
        $reserva = $reservaDomain->modificarReservaPublica(
            $reservaId,
            $token,
            new DateTime($data['nueva_fecha']),
            $data['nueva_hora']
        );
        
        error_log("Reserva modificada por cliente - ID: {$reservaId}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Reserva modificada exitosamente',
            'nueva_fecha' => $reserva->getFecha()->format('Y-m-d'),
            'nueva_hora' => $reserva->getHora()
        ]);
        
    } elseif ($action === 'cancelar') {
        $reserva = $reservaDomain->cancelarReservaPublica($reservaId, $token);
        
        error_log("Reserva cancelada por cliente - ID: {$reservaId}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Reserva cancelada exitosamente'
        ]);
        
    } else {
        throw new \InvalidArgumentException('Acción no válida');
    }
    
} catch (\DomainException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Exception $e) {
    error_log('Error en reserva pública: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}