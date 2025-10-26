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

function isAuthenticated(): bool {
    return isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true;
}

function isAdmin(): bool {
    // Lee de la sesión, NO decide
    return isAuthenticated() 
        && ($_SESSION['is_admin'] ?? false) === true;
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
        'is_admin' => $_SESSION['is_admin'] ?? false, // ← De la sesión
        'login_time' => $_SESSION['login_time'] ?? '',
        'last_activity' => $_SESSION['last_activity'] ?? ''
    ];
}

function updateLastActivity(): void {
    if (isAuthenticated()) {
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

// ========== CSRF ==========

function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    logMessage("auth.php: CSRF token generado: " . $_SESSION['csrf_token']);
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