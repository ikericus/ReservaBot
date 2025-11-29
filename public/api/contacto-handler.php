<?php
// api/contacto-handler.php

/**
 * Manejador del formulario de contacto
 */
header('Content-Type: application/json');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Validar datos de entrada
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Fallback para form-data
    $input = $_POST;
}

$nombre = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$asunto = trim($input['subject'] ?? '');
$mensaje = trim($input['message'] ?? '');

// Validaciones básicas
$errores = [];

if (empty($nombre)) {
    $errores[] = 'El nombre es requerido';
}

if (empty($email)) {
    $errores[] = 'El email es requerido';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El email no es válido';
}

if (empty($mensaje)) {
    $errores[] = 'El mensaje es requerido';
}

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errores]);
    exit;
}

try {
    $container = getContainer();
    $emailRepository = $container->getEmailRepository();
    $emailTemplates = new \ReservaBot\Domain\Email\EmailTemplates();
    
    // Generar contenido del email
    $emailData = $emailTemplates->contactoWeb($nombre, $email, $asunto, $mensaje);
    
    // Email de destino
    $emailContacto = $_ENV['CONTACT_EMAIL'] ?? 'contacto@reservabot.es';
    
    // Opciones: responder al email del cliente
    $opciones = [
        'reply_to' => $email,
        'reply_to_name' => $nombre
    ];
    
    // Enviar usando EmailRepository
    $enviado = $emailRepository->enviar(
        $emailContacto,
        $emailData['asunto'],
        $emailData['cuerpo_texto'],
        $emailData['cuerpo_html'],
        $opciones
    );
    
    if ($enviado) {
        echo json_encode([
            'success' => true, 
            'message' => 'Mensaje enviado correctamente. Te responderemos pronto.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Error al enviar el mensaje. Inténtalo de nuevo.'
        ]);
    }
    
} catch (\Exception $e) {
    error_log("Error en contacto-handler: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error interno del servidor'
    ]);
}