<?php
// public/pages/home.php

/**
 * Página principal con redirección condicional
 * - Si el usuario no está logueado: muestra landing
 * - Si el usuario está logueado: redirige a reservas
 */

require_once dirname(__DIR__) . '/config/bootstrap.php';

// Verificar autenticación
if (isAuthenticated() && !isSessionExpired()) {
    // Usuario autenticado válido
    //updateLastActivity();
    header('Location: /mes');
    exit;
}

// Si sesión expirada, hacer logout
if (isAuthenticated() && isSessionExpired()) {
    logout();
}

// Usuario no autenticado o sesión expirada
header('Location: /landing');
exit;