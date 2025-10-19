<?php
// public/config/bootstrap.php

// Definir raíz del proyecto (public/)
define('PROJECT_ROOT', dirname(__DIR__));

// ========== 1. CARGAR CONFIGURACIÓN ==========
$dbConfig = require PROJECT_ROOT . '/config/database.php';

// ========== 2. CREAR CONEXIÓN PDO ==========
if (!isset($GLOBALS['pdo'])) {
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
        $GLOBALS['pdo'] = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
    } catch (PDOException $e) {
        error_log("Bootstrap: Error BD - " . $e->getMessage());
        $GLOBALS['pdo'] = null;
    }
}

function getPDO(): ?PDO {
    return $GLOBALS['pdo'] ?? null;
}

// ========== 3. INCLUIR AUTH Y FUNCIONES ==========
require_once PROJECT_ROOT . '/config/auth.php';
require_once PROJECT_ROOT . '/includes/functions.php';

// ========== 4. AUTOLOAD ==========
spl_autoload_register(function ($class) {
    $prefix = 'ReservaBot\\';
    $base_dir = PROJECT_ROOT . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $parts = explode('\\', $relative_class);
    $filename = array_pop($parts);
    $path = strtolower(implode('/', $parts));
    
    $file = $base_dir . ($path ? $path . '/' : '') . $filename . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// ========== 5. CONTENEDOR ==========
require_once PROJECT_ROOT . '/config/container.php';

use ReservaBot\Config\Container;

if (!isset($GLOBALS['container'])) {
    try {
        $pdo = getPDO();
        if ($pdo) {
            $GLOBALS['container'] = Container::getInstance($pdo);
        }
    } catch (Exception $e) {
        error_log("Bootstrap: Error contenedor - " . $e->getMessage());
    }
}

function getContainer(): Container {
    if (!isset($GLOBALS['container'])) {
        throw new \RuntimeException('Contenedor no inicializado');
    }
    return $GLOBALS['container'];
}

function hasContainer(): bool {
    return isset($GLOBALS['container']);
}

function isDebugMode(): bool {
    return ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
}