<?php
// public/config/bootstrap.php

/**
 * Bootstrap de la aplicación ReservaBot
 * Inicializa configuración, base de datos, autoload y contenedor de dependencias
 */

// ========== 1. CARGAR CONFIGURACIÓN DE BASE DE DATOS ==========

$dbConfig = require __DIR__ . '/database.php';

// ========== 2. CREAR CONEXIÓN PDO ==========

if (!isset($GLOBALS['pdo'])) {
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
        
        $GLOBALS['pdo'] = new PDO(
            $dsn,
            $dbConfig['username'],
            $dbConfig['password'],
            $dbConfig['options']
        );
        
    } catch (PDOException $e) {
        error_log("Bootstrap: Error conectando a BD - " . $e->getMessage());
        $GLOBALS['pdo'] = null;
    }
}

/**
 * Helper para obtener PDO
 */
function getPDO(): ?PDO {
    return $GLOBALS['pdo'] ?? null;
}

// ========== 3. INCLUIR HELPERS LEGACY ==========

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// ========== 4. AUTOLOAD DE CLASES DDD ==========

spl_autoload_register(function ($class) {
    $prefix = 'ReservaBot\\';
    $base_dir = dirname(__DIR__) . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    
    // Convertir solo directorios a minúsculas, archivos mantienen case original
    $parts = explode('\\', $relative_class);
    $filename = array_pop($parts);
    $path = strtolower(implode('/', $parts));
    
    $file = $base_dir . ($path ? $path . '/' : '') . $filename . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// ========== 5. INICIALIZAR CONTENEDOR DE DEPENDENCIAS ==========

use ReservaBot\Infrastructure\Container;

if (!isset($GLOBALS['container'])) {
    try {
        $pdo = getPDO();
        
        if ($pdo) {
            $GLOBALS['container'] = Container::getInstance($pdo);
        }
    } catch (Exception $e) {
        error_log("Bootstrap: Error inicializando contenedor - " . $e->getMessage());
    }
}

/**
 * Helper para obtener el contenedor de dependencias
 */
function getContainer(): Container {
    if (!isset($GLOBALS['container'])) {
        throw new \RuntimeException('Contenedor de dependencias no inicializado');
    }
    return $GLOBALS['container'];
}

/**
 * Helper para verificar si el contenedor está disponible
 */
function hasContainer(): bool {
    return isset($GLOBALS['container']);
}

/**
 * Verificar si la aplicación está en modo debug
 */
function isDebugMode(): bool {
    return ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
}