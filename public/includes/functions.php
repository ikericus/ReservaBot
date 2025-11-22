<?php

function isDevelopment(): bool {
    return ($_ENV['APP_ENV'] ?? 'production') === 'development';
}

/**
 * Datos de usario demo
 */

function isDemoUser(string $email): bool {
    return strtolower(trim($email)) === 'demo@reservabot.es';
}

function handleDemoDataGeneration(string $email): bool {
    if (!isDemoUser($email)) {
        return false;
    }
    
    $pdo = getPDO();
    if (!$pdo) {
        $_SESSION['demo_message'] = 'Error: No se pudo conectar a la base de datos.';
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("CALL GenerateDemoData(CURDATE())");
        $stmt->execute();
        $stmt->closeCursor();
        
        $_SESSION['demo_message'] = "✅ Datos de demo generados correctamente.";
        return true;
    } catch (Exception $e) {
        error_log("Error generando datos demo: " . $e->getMessage());
        $_SESSION['demo_message'] = '⚠️ Error al generar datos de demo.';
        return false;
    }
}


/**
 * Sistema de mensajes flash
 */

function setFlashError(string $mensaje): void {
    $_SESSION['flash_error'] = $mensaje;
}

function setFlashSuccess(string $mensaje): void {
    $_SESSION['flash_success'] = $mensaje;
}

function setFlashInfo(string $mensaje): void {
    $_SESSION['flash_info'] = $mensaje;
}

function getFlashMessages(): array {
    $messages = [
        'error' => $_SESSION['flash_error'] ?? null,
        'success' => $_SESSION['flash_success'] ?? null,
        'info' => $_SESSION['flash_info'] ?? null,
    ];
    
    // Limpiar después de leer
    unset($_SESSION['flash_error'], $_SESSION['flash_success'], $_SESSION['flash_info']);
    
    return array_filter($messages);
}

/**
 * Método para log
 */

function debug_log($msg) {
    $path = PROJECT_ROOT . '/../debug.log';
    error_log(date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, 3, $path);
}


/**
 * Función para formatear la fecha en formato legible
 */

function formatearFecha($fecha) {
    $timestamp = strtotime($fecha);
    $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $dia = date('j', $timestamp);
    $mes = $meses[date('n', $timestamp) - 1];
    $anio = date('Y', $timestamp);
    
    return "$dia de $mes de $anio";
}

?>