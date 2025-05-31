<?php
/**
 * Debug del router - verificar qué está pasando
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug del Router</h1>";

// Información de la request actual
echo "<h2>1. Información de la request</h2>";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'No definido') . "<br>";

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
echo "Path parseado: " . $path . "<br>";
echo "Path limpio: " . (rtrim($path, '/') ?: '/') . "<br>";

// Verificar archivos
echo "<h2>2. Verificación de archivos</h2>";
$archivos = [
    'router.php',
    'landing.php',
    'login.php',
    'dashboard.php'
];

foreach ($archivos as $archivo) {
    $rutaCompleta = __DIR__ . '/' . $archivo;
    echo "$archivo: ";
    if (file_exists($rutaCompleta)) {
        echo "✅ Existe";
        if (is_readable($rutaCompleta)) {
            echo " y es legible";
        } else {
            echo " pero NO es legible";
        }
        echo " (Ruta: $rutaCompleta)";
    } else {
        echo "❌ NO existe (Ruta esperada: $rutaCompleta)";
    }
    echo "<br>";
}

// Verificar contenido del directorio actual
echo "<h2>3. Contenido del directorio actual</h2>";
echo "Directorio actual: " . __DIR__ . "<br>";
$archivos = scandir(__DIR__);
echo "Archivos .php encontrados:<br>";
foreach ($archivos as $archivo) {
    if (pathinfo($archivo, PATHINFO_EXTENSION) === 'php') {
        echo "- $archivo<br>";
    }
}

// Test manual de rutas
echo "<h2>4. Test manual de rutas</h2>";

// Simular el router manualmente
require_once 'router.php';

class RouterDebug extends Router {
    public function debugRoutes() {
        // Hacer que las rutas sean accesibles
        $this->defineRoutes();
        return $this->routes;
    }
    
    public function testRoute($method, $path) {
        echo "<strong>Probando: $method $path</strong><br>";
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path)) {
                echo "✅ Ruta encontrada: {$route['method']} {$route['path']} -> {$route['file']}<br>";
                
                $filePath = __DIR__ . '/' . $route['file'];
                echo "Archivo esperado: $filePath<br>";
                echo "¿Existe?: " . (file_exists($filePath) ? "✅ Sí" : "❌ No") . "<br>";
                
                return $route;
            }
        }
        
        echo "❌ No se encontró ruta coincidente<br>";
        return null;
    }
}

$debugRouter = new RouterDebug();

// Mostrar todas las rutas registradas
echo "<h3>Rutas registradas:</h3>";
try {
    $routes = $debugRouter->debugRoutes();
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Método</th><th>Ruta</th><th>Archivo</th><th>Middlewares</th></tr>";
    foreach ($routes as $route) {
        echo "<tr>";
        echo "<td>{$route['method']}</td>";
        echo "<td>{$route['path']}</td>";
        echo "<td>{$route['file']}</td>";
        echo "<td>" . implode(', ', $route['middlewares']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error obteniendo rutas: " . $e->getMessage() . "<br>";
}

// Test de rutas específicas
echo "<h3>Test de rutas específicas:</h3>";
$debugRouter->testRoute('GET', '/landing');
echo "<br>";
$debugRouter->testRoute('GET', '/login');
echo "<br>";
$debugRouter->testRoute('GET', '/');

// Test de contenido del archivo
echo "<h2>5. Test de contenido del archivo landing.php</h2>";
$landingPath = __DIR__ . '/landing.php';
if (file_exists($landingPath)) {
    echo "Primeras 200 caracteres del archivo:<br>";
    echo "<pre>" . htmlspecialchars(substr(file_get_contents($landingPath), 0, 200)) . "</pre>";
} else {
    echo "❌ Archivo landing.php no encontrado<br>";
}

// Test de ejecución manual
echo "<h2>6. Test de ejecución manual</h2>";
echo "<a href='/landing' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
🔗 Probar /landing nuevamente</a><br><br>";

echo "<a href='?include_landing=1' style='background: #059669; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
🧪 Incluir landing.php directamente</a><br>";

if (isset($_GET['include_landing'])) {
    echo "<h3>Resultado de incluir landing.php directamente:</h3>";
    echo "<div style='border: 2px solid #ccc; padding: 10px; margin: 10px 0;'>";
    try {
        include 'landing.php';
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    echo "</div>";
}

?>

<hr>
<p><small>Elimina este archivo después de usar: router-debug.php</small></p>