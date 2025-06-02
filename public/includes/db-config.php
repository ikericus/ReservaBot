<?php
// === ARCHIVO: includes/db-config.php ===
/**
 * Configuración de base de datos con función getPDO()
 */

// Inicializar PDO globalmente (solo una vez)
if (!isset($GLOBALS['pdo_initialized'])) {
    $GLOBALS['pdo_initialized'] = true;
    
    try {
        // Configuración de la conexión a la base de datos
        $host = 'localhost';
        $db   = 'u329673490_reservabot';
        $user = 'u329673490_reservabot';
        $pass = 'QFk[aas3f@';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        // Crear PDO y guardarlo en $GLOBALS para que sea accesible desde cualquier scope
        $GLOBALS['pdo'] = new PDO($dsn, $user, $pass, $options);
                
    } catch (PDOException $e) {
        error_log("db-config: Error inicializando PDO - " . $e->getMessage());
        $GLOBALS['pdo'] = null;
    }
}

/**
 * Función para obtener PDO - usa global $pdo
 * Esta función puede ser llamada desde cualquier scope
 */
function getPDO() {
    global $pdo; // Acceder a la variable global $pdo
    
    // Si $pdo no está definida localmente, intentar obtenerla de $GLOBALS
    if (!isset($pdo) && isset($GLOBALS['pdo'])) {
        $pdo = $GLOBALS['pdo'];
    }
    
    // Si aún no existe, intentar inicializar
    if (!isset($pdo) || $pdo === null) {
        error_log("getPDO(): PDO no disponible, intentando reinicializar...");
        
        // Forzar reinicialización
        unset($GLOBALS['pdo_initialized']);
        require_once __FILE__; // Re-incluir este archivo
        
        if (isset($GLOBALS['pdo'])) {
            $pdo = $GLOBALS['pdo'];
        }
    }
    
    return $pdo;
}

/**
 * Función de utilidad para verificar si PDO está disponible
 */
function isPDOAvailable() {
    $pdo = getPDO();
    
    if (!$pdo || !($pdo instanceof PDO)) {
        return false;
    }
    
    try {
        $pdo->query("SELECT 1");
        return true;
    } catch (Exception $e) {
        error_log("isPDOAvailable(): Error en test query - " . $e->getMessage());
        return false;
    }
}

/**
 * Función para obtener conexión con reintentos
 */
function getPDOWithRetry($maxRetries = 3) {
    for ($i = 0; $i < $maxRetries; $i++) {
        $pdo = getPDO();
        
        if ($pdo && isPDOAvailable()) {
            return $pdo;
        }
        
        if ($i < $maxRetries - 1) {
            error_log("getPDOWithRetry(): Intento " . ($i + 1) . " fallido, reintentando...");
            usleep(100000); // Esperar 0.1 segundos
            
            // Resetear para forzar nueva conexión
            $GLOBALS['pdo'] = null;
            unset($GLOBALS['pdo_initialized']);
        }
    }
    
    error_log("getPDOWithRetry(): Todos los intentos fallidos");
    return null;
}

// Establecer $pdo en el scope actual también (compatibilidad)
if (isset($GLOBALS['pdo'])) {
    $pdo = $GLOBALS['pdo'];
}
?>