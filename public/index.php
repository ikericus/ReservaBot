<?php
/**
 * Punto de entrada principal de ReservaBot
 * Versión final con todo integrado correctamente
 */

// ===========================================
// 1. CONFIGURACIÓN INICIAL DE ERRORES
// ===========================================
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ===========================================
// 2. INICIALIZACIÓN TEMPRANA DE SESIÓN
// ===========================================
// Inicializar sesión ANTES que cualquier otra cosa para evitar conflictos
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        // Configuraciones de sesión
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '0');
        ini_set('session.cookie_secure', '0'); // Cambiar a '1' con HTTPS
        ini_set('session.cookie_lifetime', '0');
        ini_set('session.gc_maxlifetime', '86400'); // 24 horas
        
        // Parámetros de cookie
        $cookieParams = [
            'lifetime' => 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => false, // true con HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params($cookieParams);
        } else {
            session_set_cookie_params(
                $cookieParams['lifetime'],
                $cookieParams['path'],
                $cookieParams['domain'],
                $cookieParams['secure'],
                $cookieParams['httponly']
            );
        }
        
        session_start();
        error_log("RESERVABOT: Sesión iniciada - ID: " . session_id());
    }
}

// ===========================================
// 3. SISTEMA DE DEBUG CENTRALIZADO
// ===========================================
require_once __DIR__ . '/includes/debug-system.php';

// Configurar debug para el punto de entrada
debug_configure([
    'enabled' => true,        // Cambiar a false en producción
    'show_panel' => false,    // No mostrar panel aquí, solo en router/páginas
    'log_to_file' => true
]);

debug_context('APPLICATION_ENTRY', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'timestamp' => date('Y-m-d H:i:s')
]);

debug_log("🚀 Iniciando aplicación ReservaBot");
debug_log("📍 Punto de entrada: " . $_SERVER['SCRIPT_NAME']);
debug_log("🔗 URI solicitada: " . $_SERVER['REQUEST_URI']);

// ===========================================
// 4. CARGAR Y EJECUTAR EL ROUTER
// ===========================================
debug_checkpoint('Cargando sistema de router');

// Verificar que el router existe
if (!file_exists(__DIR__ . '/router.php')) {
    debug_log("💥 ERROR CRÍTICO: router.php no encontrado", 'ERROR');
    
    // Mostrar error básico
    http_response_code(500);
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Error - ReservaBot</title>
        <style>body{font-family:Arial,sans-serif;text-align:center;margin-top:50px;}</style>
    </head>
    <body>
        <h1>Error del Sistema</h1>
        <p>El sistema de rutas no está disponible.</p>
        <p><small>Error: router.php no encontrado</small></p>
    </body>
    </html>";
    exit;
}

require_once __DIR__ . '/router.php';
debug_log("✅ Router cargado correctamente", 'SUCCESS');

debug_checkpoint('Ejecutando router');

// Crear y ejecutar el router
try {
    $router = new Router();
    debug_log("✅ Instancia de router creada", 'SUCCESS');
    
    $result = $router->resolve();
    debug_log("✅ Router ejecutado - Resultado: " . ($result ? 'exitoso' : 'fallido'), $result ? 'SUCCESS' : 'WARNING');
    
} catch (Exception $e) {
    debug_log("💥 Error ejecutando router: " . $e->getMessage(), 'ERROR');
    
    // Mostrar error detallado
    http_response_code(500);
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Error - ReservaBot</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-red-50 min-h-screen flex items-center justify-center'>
        <div class='max-w-md mx-auto bg-white rounded-lg shadow-lg p-6'>
            <div class='text-center'>
                <h1 class='text-2xl font-bold text-red-600 mb-4'>Error del Sistema</h1>
                <p class='text-gray-600 mb-4'>Ha ocurrido un error al procesar la solicitud.</p>
                <div class='bg-red-100 border border-red-300 rounded p-3 mb-4'>
                    <p class='text-sm text-red-800'>" . htmlspecialchars($e->getMessage()) . "</p>
                </div>
                <a href='/login' class='inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700'>
                    Ir al Login
                </a>
            </div>
        </div>
    </body>
    </html>";
    
    // Mostrar debug panel si está habilitado
    debug_show_panel();
    exit;
}

debug_checkpoint('Aplicación completada');
debug_log("🎉 Aplicación ReservaBot ejecutada correctamente", 'SUCCESS');
?>