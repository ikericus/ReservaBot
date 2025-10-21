<?php
// tests/bootstrap.php 

// Definir raíz del proyecto
define('PROJECT_ROOT', dirname(__DIR__) . '/public');

// ========== MODO TEST ==========
$GLOBALS['test_mode'] = true;

// ========== PDO MOCK PARA TESTS ==========
// Crear PDO en memoria para tests
try {
    $GLOBALS['pdo'] = new PDO('sqlite::memory:');
    $GLOBALS['pdo']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear estructura de tablas mínima para tests
    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS reservas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            fecha DATE NOT NULL,
            hora TIME NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            telefono VARCHAR(20) NOT NULL,
            email VARCHAR(255),
            mensaje TEXT,
            estado VARCHAR(20) DEFAULT 'pendiente',
            whatsapp_id VARCHAR(50),
            access_token VARCHAR(64),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS configuraciones_usuario (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            clave VARCHAR(50) NOT NULL,
            valor TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            plan VARCHAR(20) DEFAULT 'gratis',
            negocio VARCHAR(255),
            activo INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");
    
    // Usuario de prueba
    $GLOBALS['pdo']->exec("
        INSERT INTO usuarios (id, nombre, email, password_hash, negocio, plan)
        VALUES (1, 'Usuario Test', 'test@test.com', 'hash', 'Negocio Test', 'premium')
    ");
    
} catch (PDOException $e) {
    die("Error creando BD test: " . $e->getMessage());
}

function getPDO(): ?PDO {
    return $GLOBALS['pdo'] ?? null;
}

// ========== AUTH MOCK PARA TESTS ==========
if (!function_exists('getAuthenticatedUser')) {
    function getAuthenticatedUser(): ?array {
        return [
            'id' => 1,
            'email' => 'test@test.com',
            'nombre' => 'Usuario Test',
            'role' => 'user',
            'negocio' => 'Negocio Test',
            'plan' => 'premium'
        ];
    }
}

if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId(): int {
        return 1;
    }
}

if (!function_exists('isAuthenticated')) {
    function isAuthenticated(): bool {
        return true;
    }
}

// ========== FUNCIONES GLOBALES MÍNIMAS ==========
if (!function_exists('setFlashError')) {
    function setFlashError(string $msg): void {
        // No-op en tests
    }
}

if (!function_exists('setFlashSuccess')) {
    function setFlashSuccess(string $msg): void {
        // No-op en tests
    }
}

// ========== AUTOLOAD ==========
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

// ========== CONTENEDOR PARA TESTS ==========
require_once PROJECT_ROOT . '/config/container.php';

use ReservaBot\Config\Container;

if (!isset($GLOBALS['container'])) {
    $GLOBALS['container'] = Container::getInstance(getPDO());
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