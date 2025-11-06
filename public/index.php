<?php
// public/index.php

/**
 * Punto de entrada principal de ReservaBot
 */

// Mostrar todos los errores en pantalla
    
    // DESARROLLLO
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    
    // PRODUCCIÓN 
    // ini_set('display_errors', 0);
    // error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

// Capturar errores PHP (notices, warnings, fatales, etc.)
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno] en $errfile:$errline - $errstr");
});

// Capturar excepciones no capturadas
set_exception_handler(function ($exception) {
    error_log("Excepción no capturada: " . $exception->getMessage() . " en " . $exception->getFile() . ":" . $exception->getLine());
});

// Capturar shutdown (para errores fatales)
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        error_log("Fatal error: {$error['message']} en {$error['file']}:{$error['line']}");
    }
});

// Incluir router
require_once  __DIR__ . '/config/router.php';

// Ejecutar
$router = new Router();
$router->resolve();
?>

<script>
    if ("serviceWorker" in navigator) {
        navigator.serviceWorker.register("/sw.js")
            .then(() => console.log("Service Worker registrado"));
    }
</script>