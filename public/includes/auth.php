<?php
// === ARCHIVO: public/includes/auth.php ===
/**
 * Sistema de autenticación para ReservaBot
 * Versión actualizada con soporte de base de datos y multi-tenancy
 */

// Configuración de seguridad
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// Configurar sesión segura
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Usuarios hardcodeados (mantener para compatibilidad y emergencia)
const FALLBACK_USERS = [
    'admin@reservabot.com' => [
        'password_hash' => '$2y$12$LQv3c1yqBWVHxkjrjQG.ROinVIc8/6XJPb8T.Zj8s8qBsHwqQf8.W', // demo123
        'name' => 'Administrador',
        'role' => 'admin',
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
        
        // Actualizar en base de datos cada 5 minutos
        $lastUpdate = $_SESSION['last_db_update'] ?? 0;
        if (time() - $lastUpdate > 300) { // 5 minutos
            global $pdo;
            try {
                if (isset($_SESSION['user_id']) && $pdo) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET last_activity = NOW() WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $_SESSION['last_db_update'] = time();
                }
            } catch (Exception $e) {
                error_log("Error actualizando última actividad: " . $e->getMessage());
            }
        }
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
    
    return (time() - $lastActivity) > $timeout;
}

/**
 * Autentica un usuario con email y contraseña
 * Prioriza base de datos, fallback a usuarios hardcodeados
 */
function authenticateUser($email, $password) {
    global $pdo;
    
    // Limpiar email
    $email = trim(strtolower($email));
    
    // Primero intentar con base de datos (si está disponible)
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, nombre, email, password_hash, plan, negocio, activo, intentos_login, ultimo_intento_login 
                FROM usuarios 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                return authenticateFromDatabase($user, $password, $pdo);
            }
        } catch (Exception $e) {
            error_log("Error en autenticación DB: " . $e->getMessage());
            // Continuar con fallback
        }
    }
    
    // Fallback a usuarios hardcodeados
    return authenticateFromFallback($email, $password);
}

/**
 * Autenticación desde base de datos
 */
function authenticateFromDatabase($user, $password, $pdo) {
    $email = $user['email'];
    
    // Verificar si la cuenta está activa
    if (!$user['activo']) {
        return [
            'success' => false,
            'message' => 'Cuenta desactivada. Contacta al soporte.',
            'error_code' => 'ACCOUNT_DISABLED'
        ];
    }
    
    // Verificar bloqueo por intentos fallidos
    $intentosMaximos = 5;
    $tiempoBloqueo = 15 * 60; // 15 minutos
    
    if ($user['intentos_login'] >= $intentosMaximos) {
        $ultimoIntento = strtotime($user['ultimo_intento_login']);
        if (time() - $ultimoIntento < $tiempoBloqueo) {
            $tiempoRestante = $tiempoBloqueo - (time() - $ultimoIntento);
            return [
                'success' => false,
                'message' => 'Cuenta bloqueada temporalmente. Intenta en ' . ceil($tiempoRestante / 60) . ' minutos.',
                'error_code' => 'ACCOUNT_LOCKED'
            ];
        } else {
            // Resetear intentos si ha pasado el tiempo de bloqueo
            try {
                $stmt = $pdo->prepare("UPDATE usuarios SET intentos_login = 0 WHERE id = ?");
                $stmt->execute([$user['id']]);
            } catch (Exception $e) {
                error_log("Error reseteando intentos: " . $e->getMessage());
            }
        }
    }
    
    // Verificar contraseña
    if (!password_verify($password, $user['password_hash'])) {
        // Incrementar intentos fallidos
        try {
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET intentos_login = intentos_login + 1, ultimo_intento_login = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
        } catch (Exception $e) {
            error_log("Error incrementando intentos: " . $e->getMessage());
        }
        
        error_log("Login fallido para email: $email desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        return [
            'success' => false,
            'message' => 'Credenciales incorrectas',
            'error_code' => 'INVALID_CREDENTIALS'
        ];
    }
    
    // Login exitoso - crear sesión
    session_regenerate_id(true);
    
    $_SESSION['user_authenticated'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['nombre'];
    $_SESSION['user_role'] = 'user'; // Todos los usuarios DB son 'user'
    $_SESSION['user_negocio'] = $user['negocio'];
    $_SESSION['user_plan'] = $user['plan'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Resetear intentos de login y actualizar última actividad
    try {
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET intentos_login = 0, ultimo_intento_login = NULL, last_activity = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
    } catch (Exception $e) {
        error_log("Error actualizando login exitoso: " . $e->getMessage());
    }
    
    // Log login exitoso
    error_log("Login exitoso para email: $email (ID: {$user['id']}) desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    return [
        'success' => true,
        'message' => 'Login exitoso',
        'user' => getAuthenticatedUser()
    ];
}

/**
 * Autenticación fallback con usuarios hardcodeados
 */
function authenticateFromFallback($email, $password) {
    // Verificar que el usuario existe en fallback
    if (!isset(FALLBACK_USERS[$email])) {
        return [
            'success' => false,
            'message' => 'Credenciales incorrectas',
            'error_code' => 'INVALID_CREDENTIALS'
        ];
    }
    
    $user = FALLBACK_USERS[$email];
    
    // Verificar contraseña
    if (!password_verify($password, $user['password_hash'])) {
        error_log("Login fallido (fallback) para email: $email desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        return [
            'success' => false,
            'message' => 'Credenciales incorrectas',
            'error_code' => 'INVALID_CREDENTIALS'
        ];
    }
    
    // Login exitoso - crear sesión
    session_regenerate_id(true);
    
    $_SESSION['user_authenticated'] = true;
    $_SESSION['user_id'] = 0; // ID especial para usuarios fallback
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_negocio'] = 'ReservaBot Admin';
    $_SESSION['user_plan'] = 'premium';
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Log login exitoso
    error_log("Login exitoso (fallback) para email: $email desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
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
        $userId = $_SESSION['user_id'] ?? null;
        
        // Actualizar última actividad en base de datos (solo para usuarios DB)
        if ($userId && $userId > 0) {
            global $pdo;
            try {
                if (isset($pdo)) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET last_activity = NOW() WHERE id = ?");
                    $stmt->execute([$userId]);
                }
            } catch (Exception $e) {
                error_log("Error actualizando logout: " . $e->getMessage());
            }
        }
        
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
        session_start();
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
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Obtiene configuración específica del usuario autenticado
 * Fallback a tabla global si no existe configuración de usuario
 */
function getUserConfig($key, $default = null) {
    if (!isAuthenticated()) {
        return $default;
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'] ?? 0;
    
    // Para usuarios fallback (ID 0), usar tabla global
    if ($userId == 0 || !isset($pdo)) {
        return getGlobalConfig($key, $default);
    }
    
    try {
        // Primero buscar configuración específica del usuario
        $stmt = $pdo->prepare("
            SELECT valor FROM configuraciones_usuario 
            WHERE usuario_id = ? AND clave = ?
        ");
        $stmt->execute([$userId, $key]);
        $result = $stmt->fetchColumn();
        
        if ($result !== false) {
            return $result;
        }
        
        // Si no existe, buscar en configuración global
        return getGlobalConfig($key, $default);
        
    } catch (Exception $e) {
        error_log("Error obteniendo configuración de usuario: " . $e->getMessage());
        return getGlobalConfig($key, $default);
    }
}

/**
 * Obtiene configuración global (tabla configuraciones)
 */
function getGlobalConfig($key, $default = null) {
    global $pdo;
    
    if (!isset($pdo)) {
        return $default;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT valor FROM configuraciones WHERE clave = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        
        return $result !== false ? $result : $default;
    } catch (Exception $e) {
        error_log("Error obteniendo configuración global: " . $e->getMessage());
        return $default;
    }
}

/**
 * Establece configuración específica del usuario autenticado
 */
function setUserConfig($key, $value) {
    if (!isAuthenticated()) {
        return false;
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'] ?? 0;
    
    // Para usuarios fallback (ID 0), usar tabla global
    if ($userId == 0 || !isset($pdo)) {
        return setGlobalConfig($key, $value);
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO configuraciones_usuario (usuario_id, clave, valor, updated_at) 
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()
        ");
        $stmt->execute([$userId, $key, $value]);
        return true;
    } catch (Exception $e) {
        error_log("Error guardando configuración de usuario: " . $e->getMessage());
        return false;
    }
}

/**
 * Establece configuración global
 */
function setGlobalConfig($key, $value) {
    global $pdo;
    
    if (!isset($pdo)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO configuraciones (clave, valor, updated_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()
        ");
        $stmt->execute([$key, $value]);
        return true;
    } catch (Exception $e) {
        error_log("Error guardando configuración global: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica si el usuario actual tiene permisos de administrador
 */
function isAdmin() {
    $user = getAuthenticatedUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Obtiene el ID del usuario actual (0 para usuarios fallback)
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Verifica si un usuario puede acceder a un recurso específico
 */
function canAccessResource($resourceUserId) {
    $currentUserId = getCurrentUserId();
    
    // Admins pueden acceder a todo
    if (isAdmin()) {
        return true;
    }
    
    // Usuarios pueden acceder solo a sus propios recursos
    return $currentUserId == $resourceUserId;
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
        'time_remaining' => max(0, 24 * 60 * 60 - (time() - ($_SESSION['last_activity'] ?? 0))),
        'csrf_token' => $_SESSION['csrf_token'] ?? null,
        'is_fallback_user' => ($_SESSION['user_id'] ?? null) == 0
    ];
}

/**
 * Función para migrar usuario fallback a base de datos (útil para setup inicial)
 */
function migrateFallbackUserToDatabase($email, $password) {
    global $pdo;
    
    if (!isset(FALLBACK_USERS[$email]) || !isset($pdo)) {
        return false;
    }
    
    $user = FALLBACK_USERS[$email];
    
    try {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO usuarios (nombre, email, password_hash, plan, activo, email_verificado, api_key)
            VALUES (?, ?, ?, 'premium', 1, 1, ?)
        ");
        
        $apiKey = bin2hex(random_bytes(32));
        $stmt->execute([
            $user['name'],
            $email,
            $user['password_hash'],
            $apiKey
        ]);
        
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error migrando usuario fallback: " . $e->getMessage());
        return false;
    }
}
?>