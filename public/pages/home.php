<?php
/**
 * Página principal con redirección condicional
 * - Si el usuario no está logueado: muestra landing
 * - Si el usuario está logueado: redirige a reservas
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../includes/db-config.php';
require_once __DIR__ . '/../includes/auth.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar estado de autenticación
$isAuthenticated = isAuthenticated();
$isSessionExpired = $isAuthenticated ? isSessionExpired() : false;

// Si la sesión está expirada, hacer logout
if ($isSessionExpired) {
    logout();
    $isAuthenticated = false;
}

// Si está autenticado y la sesión es válida, redirigir a reservas
if ($isAuthenticated && !$isSessionExpired) {
    // Actualizar última actividad
    updateLastActivity();
    
    // Redirigir a la página de reservas
    header('Location: /reservas');
    exit;
}

// Si no está autenticado, mostrar landing
//require_once __DIR__ . '/pages/landing.php';

// Redirigir a la página de landing
header('Location: /landing');
?>