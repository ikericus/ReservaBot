<?php
// public/config/auth.php

/**
 * Sistema de autenticación para ReservaBot
 */

// Iniciar sesión si no está activa
if (session_status() !== PHP_SESSION_ACTIVE) {
    $cookieParams = [
        'lifetime' => 24 * 60 * 60,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
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
    
    session_start();
}

// ========== FUNCIONES DE VERIFICACIÓN ==========

function isAuthenticated(): bool {
    return isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true;
}

function isSessionExpired(): bool {
    if (!isAuthenticated()) {
        return true;
    }
    
    $timeout = 24 * 60 * 60;
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    
    if ($lastActivity === 0) {
        return false;
    }
    
    return (time() - $lastActivity) > $timeout;
}

function isAdmin(): bool {
    $user = getAuthenticatedUser();
    return $user && $user['role'] === 'admin';
}

// ========== FUNCIONES DE DATOS ==========

function getAuthenticatedUser(): ?array {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['user_email'] ?? '',
        'nombre' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user',
        'negocio' => $_SESSION['user_negocio'] ?? '',
        'plan' => $_SESSION['user_plan'] ?? 'gratis',
        'login_time' => $_SESSION['login_time'] ?? '',
        'last_activity' => $_SESSION['last_activity'] ?? '',
        'is_admin' => isAdminUser() 
}

function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function updateLastActivity(): void {
    if (isAuthenticated()) {
        $_SESSION['last_activity'] = time();
    }
}

// ========== AUTENTICACIÓN ==========

// function authenticateUser(string $email, string $password): array {
//     $email = trim(strtolower($email));
//     $pdo = getPDO();
    
//     // Intentar BD
//     if ($pdo) {
//         try {
//             $stmt = $pdo->prepare("
//                 SELECT id, nombre, email, password_hash, plan, negocio, activo 
//                 FROM usuarios 
//                 WHERE email = ? AND activo = 1
//             ");
//             $stmt->execute([$email]);
//             $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
//             if ($user && password_verify($password, $user['password_hash'])) {
//                 return createUserSession($user, 'database');
//             }
//         } catch (Exception $e) {
//             error_log("Error autenticación BD: " . $e->getMessage());
//         }
//     }
    
//     // Fallback usuario demo
//     $fallbackUsers = [
//         'admin@reservabot.com' => [
//             'password' => 'demo123',
//             'name' => 'Administrador',
//             'role' => 'admin',
//             'negocio' => 'ReservaBot Admin',
//             'plan' => 'premium'
//         ]
//     ];
    
//     if (isset($fallbackUsers[$email]) && $password === $fallbackUsers[$email]['password']) {
//         $userData = [
//             'id' => 0,
//             'email' => $email,
//             'nombre' => $fallbackUsers[$email]['name'],
//             'negocio' => $fallbackUsers[$email]['negocio'],
//             'plan' => $fallbackUsers[$email]['plan']
//         ];
//         return createUserSession($userData, 'fallback', $fallbackUsers[$email]['role']);
//     }
    
//     return [
//         'success' => false,
//         'message' => 'Credenciales incorrectas'
//     ];
// }

// function createUserSession(array $user, string $source = 'database', string $role = 'user'): array {
//     session_regenerate_id(true);
    
//     $_SESSION['user_authenticated'] = true;
//     $_SESSION['user_id'] = $user['id'];
//     $_SESSION['user_email'] = $user['email'];
//     $_SESSION['user_name'] = $user['nombre'];
//     $_SESSION['user_role'] = $role;
//     $_SESSION['user_negocio'] = $user['negocio'];
//     $_SESSION['user_plan'] = $user['plan'];
//     $_SESSION['login_time'] = time();
//     $_SESSION['last_activity'] = time();
//     $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
//     $_SESSION['auth_source'] = $source;
    
//     return [
//         'success' => true,
//         'message' => 'Login exitoso',
//         'user' => getAuthenticatedUser()
//     ];
// }

function logout(): void {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    session_destroy();
}

// ========== CSRF ==========

function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

// ========== LEGACY (Mantener por compatibilidad) ==========

function redirectToLogin(string $loginUrl = '/login', ?string $message = null): void {
    if ($message) {
        $_SESSION['login_message'] = $message;
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'AUTHENTICATION_REQUIRED',
            'redirect' => $loginUrl
        ]);
        exit;
    }
    
    header("Location: $loginUrl");
    exit;
}