<?php
// === ARCHIVO: public/includes/auth.php ===
/**
 * Sistema de autenticación para ReservaBot - Versión que maneja sesión ya activa
 */

// Solo configurar si la sesión NO está activa aún
if (session_status() === PHP_SESSION_NONE) {
    // La sesión no está iniciada, podemos configurar
    if (!headers_sent()) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '0');
        ini_set('session.cookie_secure', '0');
        
        // Configurar parámetros de cookie
        $cookieParams = [
            'lifetime' => 24 * 60 * 60, // 24 horas
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        
        if (function_exists('session_set_cookie_params')) {
            if (PHP_VERSION_ID >= 70300) {
                session_set_cookie_params($cookieParams);
            } else {
                session_set_cookie_params(
                    $cookieParams['lifetime'],
                    $cookieParams['path'],
                    $cookieParams['domain'],
                    $cookieParams['secure'],
                    $cookieParams['httponly']
                );
            }
        }
        
        session_start();
        error_log("AUTH: Sesión iniciada desde auth.php");
    }
} else {
    // La sesión ya está activa
    error_log("AUTH: Sesión ya estaba activa - ID: " . session_id());
}

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
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user',
        'negocio' => $_SESSION['user_negocio'] ?? '',
        'plan' => $_SESSION['user_plan'] ?? 'gratis',
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
    
    $timeout = 24 * 60 * 60; // 24 horas en segundos
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    
    // Si no hay última actividad, no está expirada (recién creada)
    if ($lastActivity === 0) {
        return false;
    }
    
    return (time() - $lastActivity) > $timeout;
}

/**
 * Autentica un usuario con email y contraseña
 */
function authenticateUser($email, $password) {
    global $pdo;
    
    // Limpiar email
    $email = trim(strtolower($email));
    
    // Log de intento de autenticación
    error_log("Intentando autenticar: $email");
    
    // Intentar con base de datos primero
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, nombre, email, password_hash, plan, negocio, activo 
                FROM usuarios 
                WHERE email = ? AND activo = 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                error_log("Autenticación exitosa desde BD para: $email");
                return createUserSession($user, 'database');
            } else if ($user) {
                error_log("Contraseña incorrecta para usuario BD: $email");
            } else {
                error_log("Usuario no encontrado en BD: $email");
            }
        } catch (Exception $e) {
            error_log("Error en autenticación BD: " . $e->getMessage());
        }
    }
    
    // Fallback: usuario hardcodeado
    $fallbackUsers = [
        'admin@reservabot.com' => [
            'password' => 'demo123',
            'name' => 'Administrador',
            'role' => 'admin',
            'negocio' => 'ReservaBot Admin',
            'plan' => 'premium'
        ]
    ];
    
    if (isset($fallbackUsers[$email])) {
        $fallbackUser = $fallbackUsers[$email];
        if ($password === $fallbackUser['password']) {
            error_log("Autenticación exitosa desde fallback para: $email");
            
            $userData = [
                'id' => 0,
                'email' => $email,
                'nombre' => $fallbackUser['name'],
                'negocio' => $fallbackUser['negocio'],
                'plan' => $fallbackUser['plan']
            ];
            return createUserSession($userData, 'fallback', $fallbackUser['role']);
        } else {
            error_log("Contraseña incorrecta para usuario fallback: $email");
        }
    } else {
        error_log("Usuario no encontrado en fallback: $email");
    }
    
    // Login fallido
    error_log("Login fallido para email: $email");
    return [
        'success' => false,
        'message' => 'Credenciales incorrectas'
    ];
}

/**
 * Crea la sesión del usuario
 */
function createUserSession($user, $source = 'database', $role = 'user') {
    // Solo regenerar ID si no se enviaron headers y la función existe
    if (!headers_sent() && function_exists('session_regenerate_id')) {
        try {
            session_regenerate_id(true);
            error_log("AUTH: Session ID regenerado");
        } catch (Exception $e) {
            error_log("AUTH: No se pudo regenerar session ID: " . $e->getMessage());
        }
    }
    
    // Establecer variables de sesión
    $_SESSION['user_authenticated'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['nombre'];
    $_SESSION['user_role'] = $role;
    $_SESSION['user_negocio'] = $user['negocio'];
    $_SESSION['user_plan'] = $user['plan'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    $_SESSION['auth_source'] = $source;
    
    // Log detallado
    error_log("Sesión creada ($source) para: " . $user['email'] . " - Session ID: " . session_id());
    error_log("Variables de sesión establecidas: " . json_encode(array_keys($_SESSION)));
    
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
        error_log("Cerrando sesión para: $email");
    }
    
    // Limpiar todas las variables de sesión
    $_SESSION = [];
    
    // Eliminar cookie de sesión solo si no se enviaron headers
    if (!headers_sent()) {
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir sesión
        if (function_exists('session_destroy')) {
            session_destroy();
        }
    } else {
        error_log("AUTH: No se puede destruir sesión - headers ya enviados");
    }
    
    error_log("Sesión cerrada");
}

/**
 * Middleware para proteger rutas
 */
function requireAuth($redirectTo = '/login') {
    error_log("requireAuth() ejecutándose - Session ID: " . session_id());
    error_log("Sesión actual: " . json_encode($_SESSION));
    
    updateLastActivity();
    
    if (!isAuthenticated()) {
        error_log("requireAuth(): Usuario no autenticado, redirigiendo");
        redirectToLogin($redirectTo);
        exit;
    }
    
    if (isSessionExpired()) {
        error_log("requireAuth(): Sesión expirada, cerrando sesión");
        logout();
        redirectToLogin($redirectTo, 'Tu sesión ha expirado.');
        exit;
    }
    
    error_log("requireAuth(): Acceso permitido");
}

/**
 * Redirige al login
 */
function redirectToLogin($loginUrl = '/login', $message = null) {
    // Asegurar que tenemos sesión activa para guardar el mensaje
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    
    if ($message) {
        $_SESSION['login_message'] = $message;
    }
    
    error_log("Redirigiendo al login: $loginUrl" . ($message ? " con mensaje: $message" : ""));
    
    // Si es petición AJAX o API, responder JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'error' => 'AUTHENTICATION_REQUIRED',
            'redirect' => $loginUrl
        ]);
        exit;
    }
    
    // Redirección normal solo si no se enviaron headers
    if (!headers_sent()) {
        header("Location: $loginUrl");
    } else {
        // Redirección con JavaScript si ya se enviaron headers
        echo "<script>window.location.href = '$loginUrl';</script>";
        echo "<meta http-equiv='refresh' content='0;url=$loginUrl'>";
    }
    exit;
}

/**
 * Genera token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verifica si es admin
 */
function isAdmin() {
    $user = getAuthenticatedUser();
    return $user && $user['role'] === 'admin';
}

/**
 * ID del usuario actual
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Hash de contraseña
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
?>