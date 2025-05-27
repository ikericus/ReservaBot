<?php
/**
 * Inicializador temprano de sesiones
 * Incluir ANTES que cualquier otro archivo para evitar conflictos
 */

// Solo ejecutar si no se ha iniciado ya
if (session_status() === PHP_SESSION_NONE) {
    
    // Configurar sesiones ANTES de iniciar
    if (!headers_sent()) {
        // Configuraciones básicas de sesión
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '0');
        ini_set('session.cookie_secure', '0'); // Cambiar a '1' si tienes HTTPS
        ini_set('session.cookie_lifetime', '0'); // Hasta que se cierre el navegador
        ini_set('session.gc_maxlifetime', '86400'); // 24 horas
        
        // Configurar parámetros de cookie de sesión
        $cookieParams = [
            'lifetime' => 24 * 60 * 60, // 24 horas
            'path' => '/',
            'domain' => '',
            'secure' => false, // Cambiar a true si usas HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        
        // Aplicar configuración según versión de PHP
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
        
        // Iniciar la sesión
        session_start();
        
        error_log("SESSION_INIT: Sesión iniciada correctamente - ID: " . session_id());
        
    } else {
        error_log("SESSION_INIT: No se puede iniciar sesión - headers ya enviados");
    }
    
} else {
    error_log("SESSION_INIT: Sesión ya estaba iniciada - ID: " . session_id());
}
?>