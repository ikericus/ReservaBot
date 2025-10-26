<?php
// public/pages/home.php

/**
 * Página principal con redirección condicional
 * - Si el usuario no está logueado: muestra landing
 * - Si el usuario está logueado: redirige a reservas
 */

// Verificar autenticación
if (isAuthenticatedUser() && !isSessionExpired()) {
    // Usuario autenticado válido
    header('Location: /reservas');
    exit;
}

// Si sesión expirada, hacer logout
if (isAuthenticatedUser() && isSessionExpired()) {
    logout();
}

// Usuario no autenticado o sesión expirada
header('Location: /landing');
exit;