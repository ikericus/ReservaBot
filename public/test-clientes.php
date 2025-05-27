<?php
/**
 * Test directo del archivo clientes.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test directo de clientes.php</h1>";
echo "<pre>";

// 1. Verificar que el archivo existe
$clientesPath = __DIR__ . '/clientes.php';
echo "Archivo: $clientesPath\n";
echo "Existe: " . (file_exists($clientesPath) ? "✅ SÍ" : "❌ NO") . "\n";

if (file_exists($clientesPath)) {
    $fileSize = filesize($clientesPath);
    echo "Tamaño: $fileSize bytes\n";
    
    // 2. Mostrar primeras líneas
    echo "\n=== PRIMERAS 20 LÍNEAS ===\n";
    $lines = file($clientesPath, FILE_IGNORE_NEW_LINES);
    for ($i = 0; $i < min(20, count($lines)); $i++) {
        printf("%3d: %s\n", $i + 1, htmlspecialchars($lines[$i]));
    }
    
    // 3. Verificar sintaxis PHP
    echo "\n=== VERIFICACIÓN DE SINTAXIS ===\n";
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($clientesPath) . " 2>&1", $output, $return_var);
    
    if ($return_var === 0) {
        echo "✅ Sintaxis PHP correcta\n";
    } else {
        echo "❌ Error de sintaxis:\n";
        foreach ($output as $line) {
            echo "   $line\n";
        }
    }
    
    // 4. Simular las variables globales que necesita
    echo "\n=== SIMULANDO VARIABLES REQUERIDAS ===\n";
    
    // Simular usuario autenticado
    $GLOBALS['currentUser'] = [
        'id' => 1,
        'email' => 'admin@reservabot.com',
        'name' => 'Administrador',
        'role' => 'user',
        'negocio' => 'Test Negocio',
        'plan' => 'premium'
    ];
    
    $GLOBALS['csrfToken'] = 'test-token-123';
    echo "Variables globales establecidas ✅\n";
    
    // 5. Incluir dependencias que probablemente necesite
    echo "\n=== INCLUYENDO DEPENDENCIAS ===\n";
    try {
        if (file_exists(__DIR__ . '/includes/db-config.php')) {
            require_once __DIR__ . '/includes/db-config.php';
            echo "✅ db-config.php incluido\n";
        }
        
        if (file_exists(__DIR__ . '/includes/auth.php')) {
            require_once __DIR__ . '/includes/auth.php';
            echo "✅ auth.php incluido\n";
        }
    } catch (Exception $e) {
        echo "❌ Error incluyendo dependencias: " . $e->getMessage() . "\n";
    }
    
    // 6. Intentar incluir el archivo
    echo "\n=== INCLUYENDO ARCHIVO ===\n";
    ob_start();
    
    try {
        include $clientesPath;
        $output = ob_get_contents();
        ob_end_clean();
        
        echo "✅ Archivo incluido sin errores\n";
        echo "📏 Output generado: " . strlen($output) . " bytes\n";
        
        if (strlen($output) > 0) {
            echo "✅ El archivo genera contenido\n";
            echo "\n=== OUTPUT GENERADO ===\n";
            echo htmlspecialchars(substr($output, 0, 500));
            if (strlen($output) > 500) {
                echo "\n... (truncado, total: " . strlen($output) . " bytes)";
            }
        } else {
            echo "❌ El archivo NO genera contenido (pantalla en blanco)\n";
        }
        
    } catch (ParseError $e) {
        ob_end_clean();
        echo "❌ Error de sintaxis: " . $e->getMessage() . "\n";
        echo "   Archivo: " . $e->getFile() . "\n";
        echo "   Línea: " . $e->getLine() . "\n";
        
    } catch (Error $e) {
        ob_end_clean();
        echo "❌ Error fatal: " . $e->getMessage() . "\n";
        echo "   Archivo: " . $e->getFile() . "\n";
        echo "   Línea: " . $e->getLine() . "\n";
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Excepción: " . $e->getMessage() . "\n";
        echo "   Archivo: " . $e->getFile() . "\n";
        echo "   Línea: " . $e->getLine() . "\n";
    }
    
    // 7. Verificar último error PHP
    $lastError = error_get_last();
    if ($lastError) {
        echo "\n=== ÚLTIMO ERROR PHP ===\n";
        echo "Tipo: " . $lastError['type'] . "\n";
        echo "Mensaje: " . $lastError['message'] . "\n";
        echo "Archivo: " . $lastError['file'] . "\n";
        echo "Línea: " . $lastError['line'] . "\n";
    }
    
} else {
    echo "❌ No se puede continuar porque el archivo no existe\n";
}

echo "</pre>";

echo "<p><a href='/clientes'>← Probar clientes via router</a></p>";
echo "<p><a href='/debug-auth'>← Debug auth</a></p>";
?>