
// ===== ARCHIVO: public/login-handler.php =====
<?php
/**
 * Procesamiento del formulario de login
 */

require_once '../includes/db-config.php';
require_once '../includes/auth.php';

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
    session_start();
    $_SESSION['login_errors'] = $errors;
    $_SESSION['login_email'] = $email;
    header('Location: /login');
    exit;
}

// Intentar autenticar
$authResult = authenticateUser($email, $password);

if ($authResult['success']) {
    // Login exitoso
    
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
    session_start();
    unset($_SESSION['login_errors'], $_SESSION['login_email'], $_SESSION['login_message']);
    
    // Redirigir al dashboard
    $redirectTo = $_SESSION['intended_url'] ?? '/';
    unset($_SESSION['intended_url']);
    
    header("Location: $redirectTo");
    exit;
    
} else {
    // Login fallido
    session_start();
    $_SESSION['login_errors'] = [$authResult['message']];
    $_SESSION['login_email'] = $email;
    
    header('Location: /login');
    exit;
}