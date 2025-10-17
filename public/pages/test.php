<?php
// public/pages/test.php

/**
 * Página de diagnóstico del sistema
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test ReservaBot</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 p-8'>
    <div class='max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6'>
        <h1 class='text-3xl font-bold mb-6'>🔍 Diagnóstico del Sistema</h1>";

// ========== 1. TEST BOOTSTRAP ==========
echo "<div class='mb-6 p-4 bg-blue-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>1. Bootstrap</h2>";

try {
    require_once dirname(__DIR__) . '/includes/bootstrap.php';
    echo "<p class='text-green-600'>✅ Bootstrap cargado correctamente</p>";
} catch (Exception $e) {
    echo "<p class='text-red-600'>❌ Error: " . $e->getMessage() . "</p>";
    echo "</div></div></body></html>";
    exit;
}
echo "</div>";

// ========== 2. TEST PDO ==========
echo "<div class='mb-6 p-4 bg-purple-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>2. Conexión Base de Datos</h2>";

try {
    $pdo = getPDO();
    if ($pdo) {
        echo "<p class='text-green-600'>✅ PDO disponible</p>";
        
        // Test query
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas");
        $count = $stmt->fetchColumn();
        echo "<p class='text-gray-700'>📊 Total reservas en BD: $count</p>";
    } else {
        echo "<p class='text-red-600'>❌ PDO no disponible</p>";
    }
} catch (Exception $e) {
    echo "<p class='text-red-600'>❌ Error BD: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ========== 3. TEST FUNCIONES AUTH ==========
echo "<div class='mb-6 p-4 bg-yellow-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>3. Funciones de Autenticación</h2>";

if (function_exists('isAuthenticated')) {
    echo "<p class='text-green-600'>✅ isAuthenticated() existe</p>";
    echo "<p class='text-gray-700'>Estado: " . (isAuthenticated() ? 'Autenticado' : 'No autenticado') . "</p>";
} else {
    echo "<p class='text-red-600'>❌ isAuthenticated() NO existe</p>";
}

if (function_exists('isSessionExpired')) {
    echo "<p class='text-green-600'>✅ isSessionExpired() existe</p>";
} else {
    echo "<p class='text-red-600'>❌ isSessionExpired() NO existe</p>";
}

if (function_exists('updateLastActivity')) {
    echo "<p class='text-green-600'>✅ updateLastActivity() existe</p>";
} else {
    echo "<p class='text-red-600'>❌ updateLastActivity() NO existe</p>";
}

if (function_exists('getCurrentUserId')) {
    echo "<p class='text-green-600'>✅ getCurrentUserId() existe</p>";
    $userId = getCurrentUserId();
    echo "<p class='text-gray-700'>User ID: " . ($userId ?? 'null') . "</p>";
} else {
    echo "<p class='text-red-600'>❌ getCurrentUserId() NO existe</p>";
}

echo "</div>";

// ========== 4. TEST CONTAINER ==========
echo "<div class='mb-6 p-4 bg-green-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>4. Contenedor de Dependencias</h2>";

try {
    if (hasContainer()) {
        echo "<p class='text-green-600'>✅ Container disponible</p>";
        
        $container = getContainer();
        echo "<p class='text-green-600'>✅ getContainer() funciona</p>";
        
        $reservaUseCases = $container->getReservaUseCases();
        echo "<p class='text-green-600'>✅ ReservaUseCases obtenido</p>";
        
    } else {
        echo "<p class='text-red-600'>❌ Container NO disponible</p>";
    }
} catch (Exception $e) {
    echo "<p class='text-red-600'>❌ Error Container: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ========== 5. TEST AUTOLOAD ==========
echo "<div class='mb-6 p-4 bg-indigo-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>5. Autoload de Clases</h2>";

$classes = [
    'ReservaBot\\Domain\\Reserva\\Reserva',
    'ReservaBot\\Domain\\Reserva\\EstadoReserva',
    'ReservaBot\\Infrastructure\\Container',
    'ReservaBot\\Infrastructure\\ReservaRepository',
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "<p class='text-green-600'>✅ $class</p>";
    } else {
        echo "<p class='text-red-600'>❌ $class NO encontrada</p>";
    }
}

echo "</div>";

// ========== 6. TEST ARCHIVOS ==========
echo "<div class='mb-6 p-4 bg-pink-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>6. Archivos del Sistema</h2>";

$files = [
    '/config/database.php' => __DIR__ . '/../../config/database.php',
    '/.env' => __DIR__ . '/../../.env',
    '/src/Domain/Reserva/Reserva.php' => __DIR__ . '/../../src/Domain/Reserva/Reserva.php',
    '/src/Infrastructure/Container.php' => __DIR__ . '/../../src/Infrastructure/Container.php',
    '/includes/auth.php' => __DIR__ . '/../includes/auth.php',
    '/includes/bootstrap.php' => __DIR__ . '/../includes/bootstrap.php',
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "<p class='text-green-600'>✅ $name</p>";
    } else {
        echo "<p class='text-red-600'>❌ $name NO existe</p>";
        echo "<p class='text-xs text-gray-500 ml-4'>Buscado en: $path</p>";
    }
}

echo "</div>";

// ========== 7. INFO SESIÓN ==========
echo "<div class='mb-6 p-4 bg-gray-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>7. Información de Sesión</h2>";
echo "<p class='text-gray-700'>Session ID: " . session_id() . "</p>";
echo "<p class='text-gray-700'>Session Status: " . session_status() . "</p>";
echo "<pre class='text-xs bg-white p-2 rounded mt-2 overflow-auto'>";
print_r($_SESSION);
echo "</pre>";
echo "</div>";

// ========== 8. INFO PHP ==========
echo "<div class='mb-6 p-4 bg-orange-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>8. Información PHP</h2>";
echo "<p class='text-gray-700'>PHP Version: " . phpversion() . "</p>";
echo "<p class='text-gray-700'>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p class='text-gray-700'>Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "</div>";

echo "</div></body></html>";