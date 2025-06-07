<?php
/**
 * Manejador del formulario de contacto
 * Archivo: public/contacto-handler.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

// Validaciones
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

// Incluir las funciones de email
require_once dirname(__DIR__) . '/includes/email-functions.php';

try {
    $enviado = enviarEmailContactoWeb($nombre, $email, $asunto, $mensaje);
    
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
    
} catch (Exception $e) {
    error_log("Error en contacto-handler: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error interno del servidor'
    ]);
}
?>