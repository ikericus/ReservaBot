<?php
// public/pages/test.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Test ReservaBot</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 p-8'>
    <div class='max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6'>
        <h1 class='text-3xl font-bold mb-6'>🔍 Diagnóstico del Sistema</h1>";

echo "<div class='mb-4 p-3 bg-gray-50 rounded'>";
echo "<p class='text-sm'><strong>PROJECT_ROOT:</strong> " . PROJECT_ROOT . "</p>";
echo "</div>";

// ========== 1. TEST BOOTSTRAP ==========
echo "<div class='mb-6 p-4 bg-blue-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>1. Bootstrap</h2>";

try {
    require_once PROJECT_ROOT . '/config/bootstrap.php';
    echo "<p class='text-green-600'>✅ Bootstrap cargado</p>";
} catch (Exception $e) {
    echo "<p class='text-red-600'>❌ Error: " . $e->getMessage() . "</p>";
    echo "</div></div></body></html>";
    exit;
}
echo "</div>";

// ========== 2. TEST PDO ==========
echo "<div class='mb-6 p-4 bg-purple-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>2. Base de Datos</h2>";

try {
    $pdo = getPDO();
    if ($pdo) {
        echo "<p class='text-green-600'>✅ PDO disponible</p>";
        $stmt = $pdo->query("SELECT COUNT(*) FROM reservas");
        $count = $stmt->fetchColumn();
        echo "<p class='text-gray-700'>📊 Reservas: $count</p>";
    } else {
        echo "<p class='text-red-600'>❌ PDO no disponible</p>";
    }
} catch (Exception $e) {
    echo "<p class='text-red-600'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ========== 3. TEST FUNCIONES ==========
echo "<div class='mb-6 p-4 bg-yellow-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>3. Funciones</h2>";

$functions = ['isAuthenticated', 'getCurrentUserId', 'getContainer', 'hasContainer'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<p class='text-green-600'>✅ $func()</p>";
    } else {
        echo "<p class='text-red-600'>❌ $func()</p>";
    }
}
echo "</div>";

// ========== 4. TEST CONTAINER ==========
echo "<div class='mb-6 p-4 bg-green-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>4. Container</h2>";

try {
    if (hasContainer()) {
        echo "<p class='text-green-600'>✅ Container disponible</p>";
        $container = getContainer();
        $reservaUseCases = $container->getReservaDomain();
        echo "<p class='text-green-600'>✅ ReservaDomain OK</p>";
    } else {
        echo "<p class='text-red-600'>❌ Container NO disponible</p>";
    }
} catch (Exception $e) {
    echo "<p class='text-red-600'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre class='text-xs mt-2'>" . $e->getTraceAsString() . "</pre>";
}
echo "</div>";

// ========== 5. TEST CLASES ==========
echo "<div class='mb-6 p-4 bg-indigo-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>5. Clases</h2>";

$classes = [
    'ReservaBot\\Domain\\Reserva\\Reserva',
    'ReservaBot\\Config\\Container',
    'ReservaBot\\Infrastructure\\ReservaRepository',
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "<p class='text-green-600'>✅ $class</p>";
    } else {
        echo "<p class='text-red-600'>❌ $class</p>";
    }
}
echo "</div>";

// ========== 6. TEST ARCHIVOS ==========
echo "<div class='mb-6 p-4 bg-pink-50 rounded'>";
echo "<h2 class='text-xl font-bold mb-3'>6. Archivos</h2>";

$files = [
    'config/database.php' => PROJECT_ROOT . '/config/database.php',
    'src/domain/reserva/Reserva.php' => PROJECT_ROOT . '/src/domain/reserva/Reserva.php',
    'config/Container.php' => PROJECT_ROOT . '/config/Container.php',
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "<p class='text-green-600'>✅ $name</p>";
    } else {
        echo "<p class='text-red-600'>❌ $name</p>";
        echo "<p class='text-xs text-gray-500 ml-4'>$path</p>";
    }
}
echo "</div>";

echo "</div></body></html>";