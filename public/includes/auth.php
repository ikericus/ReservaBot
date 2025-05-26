<?php
// === ARCHIVO: includes/auth.php ===
/**
 * Sistema de autenticación para ReservaBot
 * Maneja login, logout, verificación de sesiones y seguridad
 */

// Configuración de seguridad
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// Configurar sesión segura
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuración de usuarios (en producción, esto debería estar en base de datos)
// Por ahora usamos un array para el MVP
const USERS = [
    'admin@reservabot.com' => [
        'password_hash' => '$2y$12$LQv3c1yqBWVHxkjrjQG.ROinVIc8/6XJPb8T.Zj8s8qBsHwqQf8.W', // demo123
        'name' => 'Administrador',
        'role' => 'admin',
        'created_at' => '2024-01-01 00:00:00'
    ],
    // Agregar más usuarios aquí si es necesario
    'demo@reservabot.com' => [
        'password_hash' => '$2y$12$LQv3c1yqBWVHxkjrjQG.ROinVIc8/6XJPb8T.Zj8s8qBsHwqQf8.W', // demo123
        'name' => 'Usuario Demo',
        'role' => 'user',
        'created_at' => '2024-01-01 00:00:00'
    ]
];

/**
 * Verifica si el usuario está autenticado
 */
function isAuthenticated() {
    return isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true;
}

/**
 * Obtiene los datos del usuario autenticado
 */
function getAuthenticatedUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'email' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user',
        'login_time' => $_SESSION['login_time'] ?? '',
        'last_activity' => $_SESSION['last_activity'] ?? ''
    ];
}

/**
 * Actualiza la última actividad del usuario
 */
function updateLastActivity() {
    if (isAuthenticated()) {
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Verifica si la sesión ha expirado
 */
function isSessionExpired() {
    if (!isAuthenticated()) {
        return true;
    }
    
    $timeout = 1440 * 60; // 24 horas en segundos
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    
    return (time() - $lastActivity) > $timeout;
}

/**
 * Autentica un usuario con email y contraseña
 */
function authenticateUser($email, $password) {
    // Limpiar email
    $email = trim(strtolower($email));
    
    // Verificar que el usuario existe
    if (!isset(USERS[$email])) {
        return [
            'success' => false,
            'message' => 'Credenciales incorrectas',
            'error_code' => 'INVALID_CREDENTIALS'
        ];
    }
    
    $user = USERS[$email];
    
    // Verificar contraseña
    if (!password_verify($password, $user['password_hash'])) {
        // Log intento fallido
        error_log("Login fallido para email: $email desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        return [
            'success' => false,
            'message' => 'Credenciales incorrectas',
            'error_code' => 'INVALID_CREDENTIALS'
        ];
    }
    
    // Login exitoso - crear sesión
    session_regenerate_id(true); // Prevenir session fixation
    
    $_SESSION['user_authenticated'] = true;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Log login exitoso
    error_log("Login exitoso para email: $email desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    return [
        'success' => true,
        'message' => 'Login exitoso',
        'user' => getAuthenticatedUser()
    ];
}

/**
 * Cierra la sesión del usuario
 */
function logout() {
    if (isAuthenticated()) {
        $email = $_SESSION['user_email'] ?? 'unknown';
        error_log("Logout para email: $email desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    
    // Limpiar todas las variables de sesión
    $_SESSION = [];
    
    // Eliminar cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir sesión
    session_destroy();
}

/**
 * Middleware para proteger rutas que requieren autenticación
 */
function requireAuth($redirectTo = '/login.php') {
    // Actualizar última actividad
    updateLastActivity();
    
    // Verificar si está autenticado
    if (!isAuthenticated()) {
        redirectToLogin($redirectTo);
        exit;
    }
    
    // Verificar si la sesión ha expirado
    if (isSessionExpired()) {
        logout();
        redirectToLogin($redirectTo, 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
        exit;
    }
}

/**
 * Redirige al login con mensaje opcional
 */
function redirectToLogin($loginUrl = '/login.php', $message = null) {
    if ($message) {
        $_SESSION['login_message'] = $message;
    }
    
    // Si es una petición AJAX, devolver JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'AUTHENTICATION_REQUIRED',
            'message' => 'Sesión expirada. Por favor, recarga la página.',
            'redirect' => $loginUrl
        ]);
        exit;
    }
    
    // Redirigir normalmente
    header("Location: $loginUrl");
    exit;
}

/**
 * Genera un token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica un token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Función helper para generar hash de contraseña
 * (usar solo durante desarrollo para crear nuevos usuarios)
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Obtiene información de la sesión para debugging
 */
function getSessionInfo() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'user' => getAuthenticatedUser(),
        'session_id' => session_id(),
        'login_time' => date('Y-m-d H:i:s', $_SESSION['login_time'] ?? 0),
        'last_activity' => date('Y-m-d H:i:s', $_SESSION['last_activity'] ?? 0),
        'time_remaining' => max(0, 1440 * 60 - (time() - ($_SESSION['last_activity'] ?? 0))),
        'csrf_token' => $_SESSION['csrf_token'] ?? null
    ];
}

// === ARCHIVO: login-handler.php ===
<?php
/**
 * Procesamiento del formulario de login
 */

require_once 'includes/db-config.php';
require_once 'includes/auth.php';

// Solo procesar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php');
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
    header('Location: /login.php');
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
    unset($_SESSION['login_errors'], $_SESSION['login_email'], $_SESSION['login_message']);
    
    // Redirigir al dashboard
    $redirectTo = $_SESSION['intended_url'] ?? '/';
    unset($_SESSION['intended_url']);
    
    header("Location: $redirectTo");
    exit;
    
} else {
    // Login fallido
    $_SESSION['login_errors'] = [$authResult['message']];
    $_SESSION['login_email'] = $email;
    
    header('Location: /login.php');
    exit;
}

// === ARCHIVO: logout.php ===
<?php
/**
 * Página de logout
 */

require_once 'includes/auth.php';

// Cerrar sesión
logout();

// Redirigir al login con mensaje
$_SESSION['login_message'] = 'Has cerrado sesión correctamente';
header('Location: /login.php');
exit;

// === ARCHIVO: api/auth-check.php ===
<?php
/**
 * API para verificar estado de autenticación (para AJAX)
 */

require_once '../includes/auth.php';

header('Content-Type: application/json');

// Verificar si está autenticado
if (!isAuthenticated()) {
    echo json_encode([
        'authenticated' => false,
        'message' => 'No autenticado'
    ]);
    exit;
}

// Verificar si la sesión ha expirado
if (isSessionExpired()) {
    logout();
    echo json_encode([
        'authenticated' => false,
        'expired' => true,
        'message' => 'Sesión expirada'
    ]);
    exit;
}

// Actualizar actividad y devolver info
updateLastActivity();

echo json_encode([
    'authenticated' => true,
    'user' => getAuthenticatedUser(),
    'session_info' => getSessionInfo()
]);

// === ARCHIVO: middleware/auth-middleware.php ===
<?php
/**
 * Middleware de autenticación para incluir en todas las páginas protegidas
 */

require_once __DIR__ . '/../includes/auth.php';

// Aplicar middleware de autenticación
requireAuth();

// Actualizar última actividad
updateLastActivity();

// Verificar CSRF en peticiones POST (opcional, para mayor seguridad)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        // Por ahora solo log, en producción podrías ser más estricto
        error_log('CSRF token inválido desde IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
}

// Hacer disponible la información del usuario para las páginas
$currentUser = getAuthenticatedUser();
$csrfToken = generateCSRFToken();
?>