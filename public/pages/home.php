<?php
// public/pages/home.php

/**
 * Página principal con redirección condicional
 * - Si el usuario no está logueado: muestra landing
 * - Si el usuario está logueado: redirige a reservas
 */

// Verificar autenticación
if (isAuthenticated() && !isSessionExpired()) {
    // Usuario autenticado válido
    //updateLastActivity();
    header('Location: /reservas');
    exit;
}

// Si sesión expirada, hacer logout
if (isAuthenticated() && isSessionExpired()) {
    logout();
}

// Usuario no autenticado o sesión expirada
header('Location: /landing');
exit;