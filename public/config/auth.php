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

// ========== FUNCIONES DE SESIÓN ==========

function isAuthenticatedUser(): bool {
    return isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true;
}

function isAdminUser(): bool {
    // Lee de la sesión, NO decide
    return isAuthenticatedUser() 
        && ($_SESSION['is_admin'] ?? false) === true;
}

function isSessionExpired(): bool {
    if (!isAuthenticatedUser()) {
        return true;
    }
    
    $timeout = 24 * 60 * 60;
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    
    if ($lastActivity === 0) {
        return false;
    }
    
    return (time() - $lastActivity) > $timeout;
}


function setAuthenticatedUser(array $userData): void {
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);
    
    // Validar datos requeridos
    if (empty($userData['id']) || empty($userData['email'])) {
        throw new \InvalidArgumentException('Los campos id y email son obligatorios');
    }
    
    // Establecer datos de sesión
    $_SESSION['user_authenticated'] = true;
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['user_email'] = $userData['email'];
    $_SESSION['user_name'] = $userData['nombre'] ?? '';
    $_SESSION['user_plan'] = $userData['plan'] ?? 'basico';
    $_SESSION['is_admin'] = $userData['is_admin'] ?? false;
    $_SESSION['user_role'] = ($userData['is_admin'] ?? false) ? 'admin' : 'user';
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    
    debug_log("auth.php: Sesión creada para usuario {$userData['id']} - Admin: " . ($userData['is_admin'] ? 'Sí' : 'No'));
}

function getAuthenticatedUser(): ?array {
    if (!isAuthenticatedUser()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['user_email'] ?? '',
        'nombre' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user',
        'plan' => $_SESSION['user_plan'] ?? 'basico',
        'is_admin' => $_SESSION['is_admin'] ?? false, // ← De la sesión
        'login_time' => $_SESSION['login_time'] ?? '',
        'last_activity' => $_SESSION['last_activity'] ?? ''
    ];
}

function updateUserLastActivity(): void {
    if (isAuthenticatedUser()) {
        $_SESSION['last_activity'] = time();
    }
}
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

function extendSessionCookie(int $days = 30): void {
    $cookieLifetime = $days * 24 * 60 * 60;
    $params = session_get_cookie_params();
    
    setcookie(
        session_name(), 
        session_id(), 
        time() + $cookieLifetime,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
    
    debug_log("auth.php: Cookie de sesión extendida por {$days} días");
}

// ========== CSRF ==========

function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}