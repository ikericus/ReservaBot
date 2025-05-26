<?php
/**
 * Procesamiento del formulario de registro
 */

require_once 'includes/db-config.php';
require_once 'includes/auth.php';

// Solo procesar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /signup.php');
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
    session_start();
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_data'] = $_POST;
    header('Location: /signup.php');
    exit;
}

try {
    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        session_start();
        $_SESSION['register_errors'] = ['Ya existe una cuenta con este email'];
        $_SESSION['register_data'] = $_POST;
        header('Location: /signup.php');
        exit;
    }
    
    // Crear nuevo usuario
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $apiKey = bin2hex(random_bytes(32));
    $verificationToken = bin2hex(random_bytes(32));
    
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nombre, email, telefono, negocio, password_hash, plan, api_key, verificacion_token, created_at, activo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)
    ");
    
    $stmt->execute([
        $nombre,
        $email,
        $telefono,
        $negocio,
        $passwordHash,
        $plan,
        $apiKey,
        $verificationToken
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Las configuraciones iniciales se crean automáticamente por el trigger
    
    // Aquí podrías enviar email de verificación
    sendVerificationEmail($email, $verificationToken);
    
    // Auto-login después del registro
    session_start();
    session_regenerate_id(true);
    $_SESSION['user_authenticated'] = true;
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $nombre;
    $_SESSION['user_role'] = 'user';
    $_SESSION['user_negocio'] = $negocio;
    $_SESSION['user_plan'] = $plan;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Limpiar datos del formulario
    unset($_SESSION['register_errors'], $_SESSION['register_data']);
    
    // Mensaje de bienvenida
    $_SESSION['welcome_message'] = "¡Bienvenido a ReservaBot, $nombre! Tu cuenta ha sido creada exitosamente.";
    
    // Log del registro
    error_log("Nuevo usuario registrado: $email (ID: $userId)");
    
    // Redirigir al dashboard
    header('Location: /');
    exit;
    
} catch (Exception $e) {
    error_log("Error en registro: " . $e->getMessage());
    session_start();
    $_SESSION['register_errors'] = ['Error interno del servidor. Por favor, intenta nuevamente.'];
    $_SESSION['register_data'] = $_POST;
    header('Location: /signup.php');
    exit;
}
?>