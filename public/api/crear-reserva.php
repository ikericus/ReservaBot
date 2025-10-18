<?php
// api/crear-reserva.php

header('Content-Type: application/json');

// Obtener usuario autenticado
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

// Establecer estado según rol
$estadoDefault = isAdmin() ? 'confirmada' : 'pendiente';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener datos
$data = $_POST;

// Función de respuesta
function jsonResponse($success, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Validar datos requeridos
if (!isset($data['nombre'], $data['telefono'], $data['fecha'], $data['hora'])) {
    jsonResponse(false, ['error' => 'Faltan datos requeridos'], 400);
}

if (empty(trim($data['nombre'])) || empty(trim($data['telefono'])) || empty($data['fecha']) || empty($data['hora'])) {
    jsonResponse(false, ['error' => 'Todos los campos obligatorios deben estar completos'], 400);
}

// Validar formatos
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['fecha'])) {
    jsonResponse(false, ['error' => 'Formato de fecha inválido'], 400);
}

if (!preg_match('/^\d{2}:\d{2}$/', $data['hora'])) {
    jsonResponse(false, ['error' => 'Formato de hora inválido'], 400);
}

// Validar fecha no anterior
if ($data['fecha'] < date('Y-m-d')) {
    jsonResponse(false, ['error' => 'La fecha no puede ser anterior a hoy'], 400);
}

try {
    $reservaDomain = getContainer()->getReservaDomain();
    
    // Preparar datos
    $nombre = trim($data['nombre']);
    $telefono = trim($data['telefono']);
    $mensaje = isset($data['mensaje']) ? trim($data['mensaje']) : '';
    $estado = isset($data['estado']) ? trim($data['estado']) : $estadoDefault;
    $notasInternas = isset($data['notas_internas']) ? trim($data['notas_internas']) : null;
    
    $fecha = new DateTime($data['fecha']);
    $hora = $data['hora'];
    
    // Crear reserva usando el dominio
    $reserva = $reservaDomain->crearReserva(
        $nombre,
        $telefono,
        $fecha,
        $hora,
        $userId,
        $mensaje,
        $notasInternas
    );
    
    // Si el estado no es pendiente, actualizarlo
    if ($estado === 'confirmada' && isAdmin()) {
        $reserva = $reservaDomain->confirmarReserva($reserva->getId(), $userId);
    }
    
    error_log("Reserva creada - ID: {$reserva->getId()}, Teléfono: {$telefono}");
    
    jsonResponse(true, [
        'reserva' => $reserva->toArray(),
        'message' => 'Reserva creada correctamente',
        'redirect' => "/reserva?id={$reserva->getId()}"
    ]);
    
} catch (\DomainException $e) {
    error_log('Error dominio al crear reserva: ' . $e->getMessage());
    jsonResponse(false, ['error' => $e->getMessage()], 400);
} catch (\InvalidArgumentException $e) {
    error_log('Error validación al crear reserva: ' . $e->getMessage());
    jsonResponse(false, ['error' => $e->getMessage()], 400);
} catch (\Exception $e) {
    error_log('Error general al crear reserva: ' . $e->getMessage());
    jsonResponse(false, ['error' => 'Error interno del servidor'], 500);
}