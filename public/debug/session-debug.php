<?php
/**
 * Debug específico para problemas de sesiones
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug de Sesiones</h1>";

// Información de sesión antes de iniciar
echo "<h2>1. Configuración de sesiones</h2>";
echo "session.save_path: " . session_save_path() . "<br>";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "<br>";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "<br>";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "<br>";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "<br>";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "<br>";
echo "session.use_strict_mode: " . ini_get('session.use_strict_mode') . "<br>";

// Iniciar sesión manualmente
echo "<h2>2. Estado de la sesión</h2>";
echo "Estado antes de session_start(): " . session_status() . "<br>";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
    echo "✅ session_start() ejecutado<br>";
} else {
    echo "ℹ️ Sesión ya estaba activa<br>";
}

echo "Session ID: " . session_id() . "<br>";
echo "Estado después: " . session_status() . "<br>";

// Información de cookies
echo "<h2>3. Cookies de sesión</h2>";
$sessionName = session_name();
echo "Nombre de cookie de sesión: $sessionName<br>";

if (isset($_COOKIE[$sessionName])) {
    echo "✅ Cookie de sesión encontrada: " . $_COOKIE[$sessionName] . "<br>";
} else {
    echo "❌ Cookie de sesión NO encontrada<br>";
}

echo "Todas las cookies:<br>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

// Test de escritura/lectura de sesión
echo "<h2>4. Test de escritura/lectura</h2>";

// Escribir datos de test
$_SESSION['test_time'] = time();
$_SESSION['test_string'] = 'Esta es una prueba';
$_SESSION['test_array'] = ['a' => 1, 'b' => 2];

echo "✅ Datos escritos en sesión<br>";
echo "Variables de sesión actuales:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test de persistencia
echo "<h2>5. Test de persistencia</h2>";
echo "<a href='?check_session=1' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
🔄 Recargar y verificar sesión</a><br><br>";

if (isset($_GET['check_session'])) {
    echo "<strong>Resultado después de recargar:</strong><br>";
    if (isset($_SESSION['test_time'])) {
        echo "✅ Sesión persistente - test_time: " . $_SESSION['test_time'] . "<br>";
        echo "✅ Sesión persistente - test_string: " . $_SESSION['test_string'] . "<br>";
    } else {
        echo "❌ Sesión NO persistente - datos perdidos<br>";
    }
}

// Login manual de prueba
echo "<h2>6. Login manual de prueba</h2>";

if (isset($_GET['do_login'])) {
    // Hacer login manual
    require_once 'includes/auth.php';
    
    $result = authenticateUser('admin@reservabot.com', 'demo123');
    if ($result['success']) {
        echo "✅ Login exitoso<br>";
        echo "Variables de sesión después del login:<br>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        
        echo "<a href='?check_after_login=1' style='background: #059669; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
        🔍 Verificar sesión después del login</a><br>";
    } else {
        echo "❌ Login falló: " . $result['message'] . "<br>";
    }
} else {
    echo "<a href='?do_login=1' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
    🔐 Hacer login de prueba</a><br>";
}

if (isset($_GET['check_after_login'])) {
    require_once 'includes/auth.php';
    echo "<strong>Estado después del login:</strong><br>";
    echo "isAuthenticated(): " . (isAuthenticated() ? "✅ true" : "❌ false") . "<br>";
    if (isAuthenticated()) {
        $user = getAuthenticatedUser();
        echo "Usuario: " . ($user['name'] ?? 'N/A') . "<br>";
    }
}

// Información del servidor
echo "<h2>7. Información del servidor</h2>";
echo "Servidor: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido') . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Host: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'Sí' : 'No') . "<br>";
echo "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "<br>";

// Permisos de directorio de sesiones
echo "<h2>8. Permisos de directorios</h2>";
$sessionPath = session_save_path();
if (empty($sessionPath)) {
    $sessionPath = sys_get_temp_dir();
}

echo "Directorio de sesiones: $sessionPath<br>";
echo "Existe: " . (is_dir($sessionPath) ? "✅ Sí" : "❌ No") . "<br>";
echo "Escribible: " . (is_writable($sessionPath) ? "✅ Sí" : "❌ No") . "<br>";

// Soluciones propuestas
echo "<h2>9. Soluciones propuestas</h2>";
echo "<div style='background: #fef3c7; border: 1px solid #f59e0b; padding: 15px; border-radius: 5px;'>";
echo "<strong>Si las sesiones no persisten, prueba:</strong><br>";
echo "1. <a href='?fix_session=1'>Configurar sesiones manualmente</a><br>";
echo "2. <a href='?test_simple_auth=1'>Usar autenticación simple sin configuración avanzada</a><br>";
echo "</div>";

if (isset($_GET['fix_session'])) {
    echo "<h3>Aplicando configuración de sesión simplificada...</h3>";
    
    // Resetear configuración de sesión
    ini_set('session.cookie_httponly', '0');
    ini_set('session.cookie_secure', '0');
    ini_set('session.use_strict_mode', '0');
    
    echo "✅ Configuración simplificada aplicada<br>";
    echo "Recarga la página para probar<br>";
}

?>

<hr>
<p><a href="/login-debug.php">← Login Debug</a> | <a href="/middleware-debug.php">← Middleware Debug</a></p>
<p><small>Elimina este archivo después de usar: session-debug.php</small></p>