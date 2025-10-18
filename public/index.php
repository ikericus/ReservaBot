<?php
// public/index.php

/**
 * Punto de entrada principal de ReservaBot
 */

// Definir raíz del proyecto (public/)
define('PROJECT_ROOT', __DIR__);

// Mostrar todos los errores en pantalla
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Incluir router
require_once PROJECT_ROOT . '/config/router.php';

// Ejecutar
$router = new Router();
$router->resolve();