<?php
/**
 * Página de debug para probar autenticación sin middleware
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Auth - ReservaBot</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Debug de Autenticación</h1>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">1. Estado básico</h2>
            <div class="space-y-2 text-sm">
                <p><strong>Session Status:</strong> <?php echo session_status(); ?></p>
                <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                <p><strong>Request URI:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
                <p><strong>Script Name:</strong> <?php echo $_SERVER['SCRIPT_NAME']; ?></p>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">2. Variables de sesión</h2>
            <pre class="bg-gray-100 p-4 rounded text-xs overflow-auto"><?php 
                echo htmlspecialchars(print_r($_SESSION, true)); 
            ?></pre>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">3. Test de archivos requeridos</h2>
            <?php
            $archivos = [
                'includes/db-config.php',
                'includes/auth.php'
            ];
            
            foreach ($archivos as $archivo) {
                $ruta = __DIR__ . '/' . $archivo;
                echo "<p>";
                echo "<strong>$archivo:</strong> ";
                if (file_exists($ruta)) {
                    echo "<span class='text-green-600'>✅ Existe</span>";
                    if (is_readable($ruta)) {
                        echo " <span class='text-green-600'>y es legible</span>";
                    } else {
                        echo " <span class='text-red-600'>pero NO es legible</span>";
                    }
                } else {
                    echo "<span class='text-red-600'>❌ NO existe</span>";
                }
                echo "<br><small class='text-gray-500'>Ruta: $ruta</small>";
                echo "</p>";
            }
            ?>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">4. Test de inclusión de archivos</h2>
            <?php
            try {
                echo "<p><strong>Incluyendo db-config.php...</strong></p>";
                require_once __DIR__ . '/includes/db-config.php';
                echo "<p class='text-green-600'>✅ db-config incluido correctamente</p>";
                
                echo "<p><strong>Incluyendo auth.php...</strong></p>";
                require_once __DIR__ . '/includes/auth.php';
                echo "<p class='text-green-600'>✅ auth incluido correctamente</p>";
                
            } catch (Exception $e) {
                echo "<p class='text-red-600'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">5. Test de funciones</h2>
            <?php
            if (isset($pdo)) {
                echo "<p class='text-green-600'>✅ Variable \$pdo disponible</p>";
            } else {
                echo "<p class='text-red-600'>❌ Variable \$pdo NO disponible</p>";
            }
            
            $funciones = ['isAuthenticated', 'getAuthenticatedUser', 'updateLastActivity', 'isSessionExpired'];
            
            foreach ($funciones as $funcion) {
                echo "<p>";
                echo "<strong>$funcion():</strong> ";
                if (function_exists($funcion)) {
                    echo "<span class='text-green-600'>✅ Existe</span>";
                    
                    // Probar función si es segura
                    try {
                        if ($funcion === 'isAuthenticated') {
                            $result = isAuthenticated();
                            echo " - Resultado: " . ($result ? "TRUE" : "FALSE");
                        } elseif ($funcion === 'isSessionExpired') {
                            $result = isSessionExpired();
                            echo " - Resultado: " . ($result ? "TRUE (expirada)" : "FALSE (válida)");
                        }
                    } catch (Exception $e) {
                        echo " <span class='text-red-600'>- Error al ejecutar: " . $e->getMessage() . "</span>";
                    }
                } else {
                    echo "<span class='text-red-600'>❌ NO existe</span>";
                }
                echo "</p>";
            }
            ?>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">6. Test completo de autenticación</h2>
            <?php
            try {
                if (function_exists('isAuthenticated') && function_exists('getAuthenticatedUser')) {
                    if (isAuthenticated()) {
                        echo "<p class='text-green-600'>✅ Usuario está autenticado</p>";
                        
                        $user = getAuthenticatedUser();
                        if ($user) {
                            echo "<div class='bg-green-50 p-4 rounded mt-2'>";
                            echo "<strong>Datos del usuario:</strong><br>";
                            echo "<pre class='text-xs'>" . htmlspecialchars(print_r($user, true)) . "</pre>";
                            echo "</div>";
                        } else {
                            echo "<p class='text-red-600'>❌ No se pudieron obtener datos del usuario</p>";
                        }
                    } else {
                        echo "<p class='text-yellow-600'>⚠️ Usuario NO está autenticado</p>";
                    }
                } else {
                    echo "<p class='text-red-600'>❌ Funciones de autenticación no disponibles</p>";
                }
            } catch (Exception $e) {
                echo "<p class='text-red-600'>❌ Error en test de autenticación: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
        
        <div class="text-center">
            <a href="/login" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 mr-4">
                Ir al Login
            </a>
            <a href="/" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">
                Probar Dashboard (/)
            </a>
        </div>
    </div>
</body>
</html>