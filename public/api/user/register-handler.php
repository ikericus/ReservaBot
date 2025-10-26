<?php
// api/register-handler.php

// Solo procesar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /signup');
    exit;
}

// Obtener datos del formulario
$nombre = trim($_POST['nombre'] ?? '');
$email = trim(strtolower($_POST['email'] ?? ''));
$telefono = trim($_POST['telefono'] ?? '');
$negocio = trim($_POST['negocio'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$plan = $_POST['plan'] ?? 'gratis';
$terminos = isset($_POST['terminos']);

// Validaciones
$errors = [];

if (empty($nombre)) $errors[] = 'El nombre es obligatorio';
if (empty($email)) $errors[] = 'El email es obligatorio';
if (empty($telefono)) $errors[] = 'El teléfono es obligatorio';
if (empty($negocio)) $errors[] = 'El nombre del negocio es obligatorio';
if (empty($password)) $errors[] = 'La contraseña es obligatoria';
if (empty($confirmPassword)) $errors[] = 'La confirmación de contraseña es obligatoria';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El formato del email no es válido';
}

if (strlen($password) < 6) {
    $errors[] = 'La contraseña debe tener al menos 6 caracteres';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Las contraseñas no coinciden';
}

if (!$terminos) {
    $errors[] = 'Debes aceptar los términos y condiciones';
}

// Si hay errores, redirigir con errores
if (!empty($errors)) {
    error_log("Errores de registro: " . implode('; ', $errors));
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_data'] = $_POST;
    header('Location: /signup');
    exit;
}

try {

    $usuarioDomain = getContainer()->getUsuarioDomain();

    // Registrar usuario (ya valida email duplicado internamente)
    $usuario = $usuarioDomain->registrar(
        $nombre,
        $email,
        $telefono,
        $negocio,
        $password,
        $plan
    );

    $enviado = $usuarioDomain->iniciarVerificacionCorreo($usuario->getId());
    
    if (!$enviado) {
        $_SESSION['login_errors'] = "No se pudo enviar email de verificación a {$email}. Por favor, contacta al soporte.";
        error_log("Advertencia: No se pudo enviar email de verificación a {$email}");
        // No bloqueamos el registro si falla el email
    }

    $_SESSION['login_message'] = "¡Cuenta creada exitosamente! Revisa tu correo para verificar tu cuenta.";
    
    header('Location: /login');

    exit;
    
} catch (Exception $e) {
    error_log("Error en registro: " . $e->getMessage());
    $_SESSION['register_errors'] = ['Error interno del servidor. Por favor, intenta nuevamente.'];
    $_SESSION['register_data'] = $_POST;
    header('Location: /signup');
    exit;
}
?>