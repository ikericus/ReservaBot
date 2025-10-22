<?php
// public/config/admin-auth.php

/**
 * Sistema de autenticación para administradores
 * Solo el propietario del sistema puede acceder a las funciones admin
 */

// ========== VARIABLES DE ADMINISTRADOR ==========

// Email del propietario del sistema (configurable desde .env)
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL']);

// ========== FUNCIONES DE VERIFICACIÓN ==========

/**
 * Verifica si el usuario actual es administrador
 */
function isAdminUser(): bool {
    $user = getAuthenticatedUser();
    return $user && strtolower(trim($user['email'])) === strtolower(ADMIN_EMAIL);
}

/**
 * Requiere autenticación de admin
 * Si no es admin, redirige a la página principal
 */
function requireAdminAuth(): void {
    // Primero, verificar que esté autenticado
    updateLastActivity();
    
    if (!isAuthenticated()) {
        redirectToLogin('/admin');
        exit;
    }
    
    // Si sesión expirada
    if (isSessionExpired()) {
        logout();
        redirectToLogin('/admin', 'Tu sesión ha expirado.');
        exit;
    }
    
    // Verificar que sea admin
    if (!isAdminUser()) {
        http_response_code(403);
        error_log("Intento de acceso admin no autorizado desde: " . getAuthenticatedUser()['email']);
        
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Acceso Denegado</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gradient-to-r from-red-50 to-orange-50 min-h-screen flex items-center justify-center'>
            <div class='text-center'>
                <i class='ri-forbid-line text-red-600 text-8xl mb-4 block'></i>
                <h1 class='text-4xl font-bold text-gray-900 mb-2'>Acceso Denegado</h1>
                <p class='text-gray-600 mb-6'>No tienes permisos para acceder a esta sección.</p>
                <a href='/reservas' class='bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 inline-block'>
                    Volver a la aplicación
                </a>
            </div>
        </body>
        </html>";
        
        exit;
    }
}

/**
 * Obtiene información del administrador
 */
function getAdminUser(): ?array {
    $user = getAuthenticatedUser();
    
    if (!$user || strtolower(trim($user['email'])) !== strtolower(ADMIN_EMAIL)) {
        return null;
    }
    
    return array_merge($user, [
        'is_admin' => true,
        'admin_level' => 'super'
    ]);
}

/**
 * Log de actividad admin
 */
function logAdminActivity(string $action, string $description = '', array $data = []): void {
    $adminUser = getAdminUser();
    
    if (!$adminUser) {
        return;
    }
    
    try {
        $pdo = getPDO();
        if (!$pdo) return;
        
        $sql = "INSERT INTO admin_activity_log (admin_email, action, description, data, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $adminUser['email'],
            $action,
            $description,
            !empty($data) ? json_encode($data) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Error guardando log de admin: " . $e->getMessage());
    }
}
