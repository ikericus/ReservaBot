<?php
// api/login-handler.php

// Solo procesar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login');
    exit;
}

// Obtener datos del formulario
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

// Validaciones básicas
$errors = [];

if (empty($email)) {
    $errors[] = 'El email es obligatorio';
}

if (empty($password)) {
    $errors[] = 'La contraseña es obligatoria';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El formato del email no es válido';
}

// Si hay errores, redirigir con errores
if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
    $_SESSION['login_email'] = $email;
    header('Location: /login');
    exit;
}

try {
    $usuarioDomain = getContainer()->getUsuarioDomain();
    
    $usuario = $usuarioDomain->autenticar($email, $password);
    
    // Limpiar errores previos
    unset($_SESSION['login_errors'], $_SESSION['login_email'], $_SESSION['login_message']);
    
    // Generar datos de demo si es necesario
    handleDemoDataGeneration($email);
            
    // Verificar si es admin
    $esAdmin = $usuarioDomain->esAdministrador($usuario->getId());

    // crear usuario autenticado en sesión
    setAuthenticatedUser([
        'id' => $usuario->getId(),
        'email' => $usuario->getEmail(),
        'nombre' => $usuario->getNombre(),
        'negocio' => $usuario->getNegocio(),
        'plan' => $usuario->getPlan(),
        'is_admin' => $esAdmin
    ]);
    
    // Extender cookie si marcó "recordar"
    if ($remember) {
        extendSessionCookie(30);
    }

    header('Location: /reservas');
    exit;
    
} catch (\DomainException $e) {
    $_SESSION['login_errors'] = $e->getMessage();
    $_SESSION['login_email'] = $email;
    header('Location: /login');
    exit;
} catch (\Exception $e) {
    error_log('Error en login: ' . $e->getMessage());
    $_SESSION['login_errors'] = 'Error interno del servidor';
    header('Location: /login');
    exit;
}