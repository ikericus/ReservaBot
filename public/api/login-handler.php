<?php
// api/login-handler.php

// Solo procesar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login');
    exit;
}

logMessage("login-handler.php: Inicio de procesamiento de login");

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

logMessage("login-handler.php: Validaciones completadas");


try {
    $usuarioDomain = getContainer()->getUsuarioDomain();
    $usuario = $usuarioDomain->autenticar($email, $password);
    
    if (!$usuario) {
        $_SESSION['login_errors'] = 'Credenciales incorrectas';
        $_SESSION['login_email'] = $email;
        
        header('Location: /login');
        exit;
    }

    
    // Si marcó "recordar sesión", extender duración de cookie
    if ($remember) {
        $cookieLifetime = 30 * 24 * 60 * 60; // 30 días
        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), time() + $cookieLifetime,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Limpiar errores previos
    unset($_SESSION['login_errors'], $_SESSION['login_email'], $_SESSION['login_message']);
    
    // Generar datos de demo si es necesario
    handleDemoDataGeneration($email);
        
    // Crear sesión
    session_start();
    session_regenerate_id(true);
    
    $_SESSION['user_authenticated'] = true;
    $_SESSION['user_id'] = $usuario->getId();
    $_SESSION['user_email'] = $usuario->getEmail();
    $_SESSION['user_name'] = $usuario->getNombre();
    $_SESSION['user_role'] = 'user';
    $_SESSION['user_negocio'] = $usuario->getNegocio();
    $_SESSION['user_plan'] = $usuario->getPlan();
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    
    header('Location: /reservas');
    exit;
    
} catch (\DomainException $e) {
    session_start();
    $_SESSION['login_error'] = $e->getMessage();
    header('Location: /login');
    exit;
} catch (\Exception $e) {
    error_log('Error en login: ' . $e->getMessage());
    session_start();
    $_SESSION['login_error'] = 'Error interno del servidor';
    header('Location: /login');
    exit;
}