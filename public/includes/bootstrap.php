<?php
// public/includes/bootstrap.php

/**
 * Bootstrap de la aplicación ReservaBot
 * Inicializa configuración, base de datos, autoload y contenedor de dependencias
 */

// ========== 1. CARGAR CONFIGURACIÓN DE BASE DE DATOS ==========

// $configPath = dirname(__DIR__, 2) . '/config/database.php';

$configPath = dirname(__DIR__) . '/config/database.php';

if (!file_exists($configPath)) {
    die("ERROR: config/database.php no encontrado en: $configPath");
}

try {
    $dbConfig = require_once $configPath;
    
    if (!is_array($dbConfig)) {
        die("ERROR: database.php no retorna un array. Retorna: " . gettype($dbConfig));
    }
} catch (Exception $e) {
    die("ERROR cargando database.php: " . $e->getMessage());
}

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
        
        error_log("Bootstrap: Conexión PDO establecida correctamente");
        
    } catch (PDOException $e) {
        error_log("Bootstrap: Error conectando a BD - " . $e->getMessage());
        $GLOBALS['pdo'] = null;
    }
}

/**
 * Helper para obtener PDO
 * Mantiene compatibilidad con código legacy
 */
function getPDO(): ?PDO {
    return $GLOBALS['pdo'] ?? null;
}

// ========== 3. INCLUIR HELPERS LEGACY ==========

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// ========== 4. AUTOLOAD DE CLASES DDD ==========

// Opción A: Autoload manual (sin Composer)
spl_autoload_register(function ($class) {
    // Convertir namespace a path
    // ReservaBot\Domain\Reserva\Reserva -> src/Domain/Reserva/Reserva.php
    $prefix = 'ReservaBot\\';
    $base_dir = __DIR__ . '/../../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // No es una clase de ReservaBot
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    } else {
        error_log("Bootstrap: Clase no encontrada - $file");
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
 * Obtener usuario actual (ya validado por router middleware)
 * @return array|null
 */
function getCurrentUser(): ?array {
    return $GLOBALS['currentUser'] ?? null;
}

/**
 * Obtener ID del usuario actual
 * @return int|null
 */
function getCurrentUserId(): ?int {
    $user = getCurrentUser();
    return $user ? (int)$user['id'] : null;
}

/**
 * Obtener token CSRF actual
 * @return string|null
 */
function getCsrfToken(): ?string {
    return $GLOBALS['csrfToken'] ?? null;
}

/**
 * Verificar si la aplicación está en modo debug
 * @return bool
 */
function isDebugMode(): bool {
    return ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
}

// ========== 7. LOG DE INICIALIZACIÓN COMPLETADA ==========

error_log("Bootstrap: Inicialización completada - PDO: " . (getPDO() ? 'OK' : 'FAIL') . " | Container: " . (hasContainer() ? 'OK' : 'FAIL'));