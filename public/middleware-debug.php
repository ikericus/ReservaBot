<?php
/**
 * Debug del middleware de autenticación
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug del Middleware</h1>";

// Simular una sesión autenticada
session_start();

// Verificar si hay sesión activa
echo "<h2>1. Estado de la sesión</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Variables de sesión:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Cargar archivos necesarios
echo "<h2>2. Cargando archivos</h2>";
try {
    require_once 'includes/db-config.php';
    echo "✅ db-config.php cargado<br>";
} catch (Exception $e) {
    echo "❌ Error cargando db-config.php: " . $e->getMessage() . "<br>";
}

try {
    require_once 'includes/auth.php';
    echo "✅ auth.php cargado<br>";
} catch (Exception $e) {
    echo "❌ Error cargando auth.php: " . $e->getMessage() . "<br>";
}

// Test de funciones de autenticación
echo "<h2>3. Test de funciones de autenticación</h2>";

echo "isAuthenticated(): " . (isAuthenticated() ? "✅ true" : "❌ false") . "<br>";

if (isAuthenticated()) {
    echo "getAuthenticatedUser():<br>";
    echo "<pre>";
    print_r(getAuthenticatedUser());
    echo "</pre>";
    
    echo "isSessionExpired(): " . (isSessionExpired() ? "❌ true (EXPIRADA)" : "✅ false (VÁLIDA)") . "<br>";
    
    echo "getCurrentUserId(): " . getCurrentUserId() . "<br>";
    echo "isAdmin(): " . (isAdmin() ? "✅ true" : "❌ false") . "<br>";
} else {
    echo "❌ Usuario no autenticado<br>";
}

// Test del middleware completo
echo "<h2>4. Test del middleware completo</h2>";
try {
    // Simular el comportamiento del middleware sin el exit
    
    // Actualizar última actividad
    updateLastActivity();
    echo "✅ updateLastActivity() ejecutado<br>";
    
    // Verificar autenticación
    if (!isAuthenticated()) {
        echo "❌ PROBLEMA: isAuthenticated() devuelve false<br>";
        echo "El middleware redirigiría al login aquí<br>";
    } else {
        echo "✅ Usuario autenticado<br>";
        
        // Verificar expiración
        if (isSessionExpired()) {
            echo "❌ PROBLEMA: Sesión expirada<br>";
            echo "El middleware cerraría sesión y redirigiría aquí<br>";
        } else {
            echo "✅ Sesión válida<br>";
            echo "✅ El middleware permitiría acceso al dashboard<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error en test de middleware: " . $e->getMessage() . "<br>";
}

// Información adicional
echo "<h2>5. Información adicional</h2>";
echo "Tiempo actual: " . time() . "<br>";
echo "Último activity: " . ($_SESSION['last_activity'] ?? 'No definido') . "<br>";
echo "Login time: " . ($_SESSION['login_time'] ?? 'No definido') . "<br>";

if (isset($_SESSION['last_activity'])) {
    $timeDiff = time() - $_SESSION['last_activity'];
    echo "Diferencia tiempo: $timeDiff segundos<br>";
    echo "Límite expiración: " . (24 * 60 * 60) . " segundos (24 horas)<br>";
    echo "¿Expirada?: " . ($timeDiff > (24 * 60 * 60) ? "❌ SÍ" : "✅ NO") . "<br>";
}

// Test manual de acceso al dashboard
echo "<h2>6. Test de acceso al dashboard</h2>";
if (file_exists('index.php')) {
    echo "✅ index.php existe<br>";
    echo "<a href='/' style='background: #059669; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
    🚀 Probar acceso al dashboard</a><br>";
} else {
    echo "❌ index.php no existe<br>";
}

echo "<h2>7. Test específico del middleware</h2>";
if (file_exists('middleware/auth-middleware.php')) {
    echo "✅ middleware/auth-middleware.php existe<br>";
    
    echo "<strong>Contenido del middleware:</strong><br>";
    echo "<textarea style='width: 100%; height: 200px;'>";
    echo htmlspecialchars(file_get_contents('middleware/auth-middleware.php'));
    echo "</textarea>";
} else {
    echo "❌ middleware/auth-middleware.php no existe<br>";
}

?>

<hr>
<p><a href="/login-debug.php">← Volver al login debug</a></p>
<p><small>Elimina este archivo después de usar: middleware-debug.php</small></p>