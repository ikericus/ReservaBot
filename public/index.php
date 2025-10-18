<?php
/**
 * Punto de entrada principal de ReservaBot
 * Todas las requests pasan por aquí y se enrutan al router
 */

// Configuración básica
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Iniciar sesión de forma temprana
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Incluir el router
require_once dirname(__DIR__) . '/config/router.php';

// Crear y ejecutar el router
$router = new Router();
$router->resolve();
?>