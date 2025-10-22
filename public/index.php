<?php
// public/index.php

/**
 * Punto de entrada principal de ReservaBot
 */

// Mostrar todos los errores en pantalla
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Incluir router
require_once  __DIR__ . '/config/router.php';

logMessage("Pasando por Index.php...");

// Ejecutar
$router = new Router();
$router->resolve();