<?php
// public/index.php

/**
 * Punto de entrada principal de ReservaBot
 */

define('PROJECT_ROOT', __DIR__);

// ✅ MOSTRAR TODOS LOS ERRORES EN PANTALLA
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Iniciar sesión temprana
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once PROJECT_ROOT . '/config/router.php';

// Ejecutar
$router = new Router();
$router->resolve();