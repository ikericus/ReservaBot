<?php
// public/includes/bootstrap.php

/**
 * Bootstrap de la aplicación ReservaBot
 */

// Mostrar errores en pantalla
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ========== 1. CARGAR CONFIGURACIÓN DE BASE DE DATOS ==========

$configPath = dirname(__DIR__) . '/config/database.php';

if (!file_exists($configPath)) {
    die("ERROR: config/database.php no encontrado en: $configPath");
}

try {
    $dbConfig = require_once $configPath;
    
    if (!is_array($dbConfig)) {
        die("ERROR: database.php no retorna un array. Retorna: " . gettype($dbConfig));
    }
    
    echo "<pre>DEBUG Config:\n";
    print_r([
        'host' => $dbConfig['host'],
        'database' => $dbConfig['database'],
        'username' => $dbConfig['username'],
        'password_length' => strlen($dbConfig['password'] ?? '')
    ]);
    echo "</pre>";
    
} catch (Exception $e) {
    die("ERROR cargando database.php: " . $e->getMessage());
}

// ========== 2. CREAR CONEXIÓN PDO ==========

if (!isset($GLOBALS['pdo'])) {
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
        
        echo "<p>Intentando conectar con DSN: $dsn</p>";
        
        $GLOBALS['pdo'] = new PDO(
            $dsn,
            $dbConfig['username'],
            $dbConfig['password'],
            $dbConfig['options']
        );
        
        echo "<p style='color:green;'>✅ PDO conectado exitosamente</p>";
        
    } catch (PDOException $e) {
        echo "<div style='background:red;color:white;padding:20px;'>";
        echo "<h2>❌ Error PDO</h2>";
        echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Código:</strong> " . $e->getCode() . "</p>";
        echo "</div>";
        $GLOBALS['pdo'] = null;
    }
}

function getPDO(): ?PDO {
    return $GLOBALS['pdo'] ?? null;
}

// ========== 3. INCLUIR HELPERS LEGACY ==========

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// ... resto del código igual ...
// ========== 4. AUTOLOAD DE CLASES DDD ==========

// Opción A: Autoload manual (sin Composer)
spl_autoload_register(function ($class) {
    $prefix = 'ReservaBot\\';
    $base_dir = dirname(__DIR__) . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    
    // ✨ Convertir a minúsculas para que coincida con tu estructura
    $relative_class = strtolower($relative_class);
    
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    } else {
        error_log("Autoload: Clase no encontrada - $file");
    }
});

// Opción B: Si usas Composer (descomenta esto y comenta lo de arriba)
/*
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}
*/

// ========== 5. INICIALIZAR CONTENEDOR DE DEPENDENCIAS ==========

use ReservaBot\Infrastructure\Container;

if (!isset($GLOBALS['container'])) {
    try {
        $pdo = getPDO();
        
        if ($pdo) {
            $GLOBALS['container'] = Container::getInstance($pdo);
            error_log("Bootstrap: Contenedor de dependencias inicializado");
        } else {
            error_log("Bootstrap: No se pudo inicializar contenedor - PDO no disponible");
        }
    } catch (Exception $e) {
        error_log("Bootstrap: Error inicializando contenedor - " . $e->getMessage());
    }
}

/**
 * Helper para obtener el contenedor de dependencias
 * @return Container
 * @throws RuntimeException si el contenedor no está inicializado
 */
function getContainer(): Container {
    if (!isset($GLOBALS['container'])) {
        throw new \RuntimeException('Contenedor de dependencias no inicializado');
    }
    return $GLOBALS['container'];
}

/**
 * Helper para verificar si el contenedor está disponible
 * @return bool
 */
function hasContainer(): bool {
    return isset($GLOBALS['container']);
}

// ========== 6. HELPERS ADICIONALES ==========

/**
 * Verificar si la aplicación está en modo debug
 * @return bool
 */
function isDebugMode(): bool {
    return ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
}

// ========== 7. LOG DE INICIALIZACIÓN COMPLETADA ==========

error_log("Bootstrap: Inicialización completada - PDO: " . (getPDO() ? 'OK' : 'FAIL') . " | Container: " . (hasContainer() ? 'OK' : 'FAIL'));