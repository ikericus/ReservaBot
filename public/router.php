<?php
/**
 * Router centralizado para ReservaBot - Versión final
 * Usa el sistema de debug centralizado, sin constantes viejas
 */

// El sistema de debug ya fue inicializado en index.php
// Solo configuramos el contexto específico del router
debug_context('ROUTER', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'timestamp' => date('Y-m-d H:i:s')
]);

// Configurar debug específico para el router
debug_configure([
    'enabled' => true,
    'show_panel' => true,
    'panel_position' => 'top-right'
]);

class Router {
    private $routes = [];
    private $middlewares = [];
    private $currentRoute = null;
    
    public function __construct() {
        debug_log("🚀 Router iniciado");
        $this->defineRoutes();
    }
    
    private function defineRoutes() {
        debug_log("📋 Definiendo rutas...");
        
        // Rutas de debug (sin middleware)
        $this->addRoute('GET', '/debug-auth', 'debug-auth.php');
        $this->addRoute('GET', '/test-clientes', 'test-clientes.php');
        
        // Rutas principales
        $this->addRoute('GET', '/', 'dashboard.php', ['auth']);
        $this->addRoute('GET', '/dashboard', 'dashboard.php', ['auth']);
        $this->addRoute('GET', '/landing', 'landing.php');
        
        // Rutas de autenticación
        $this->addRoute('GET', '/login', 'login.php');
        $this->addRoute('POST', '/login-handler', 'login-handler.php');
        $this->addRoute('GET', '/signup', 'signup.php');
        $this->addRoute('POST', '/signup', 'register-handler.php');
        $this->addRoute('POST', '/register-handler', 'register-handler.php');
        $this->addRoute('GET', '/logout', 'logout.php');
        $this->addRoute('GET', '/password-reset', 'password-reset.php');
        $this->addRoute('POST', '/password-reset', 'password-reset.php');
        
        // Rutas protegidas
        $this->addRoute('GET', '/reservas', 'reservas.php', ['auth']);
        $this->addRoute('GET', '/clientes', 'clientes.php', ['auth']);
        $this->addRoute('GET', '/cliente-detail', 'cliente-detail.php', ['auth']);
        $this->addRoute('GET', '/configuracion', 'configuracion.php', ['auth']);
        $this->addRoute('GET', '/perfil', 'perfil.php', ['auth']);
        $this->addRoute('GET', '/estadisticas', 'estadisticas.php', ['auth']);
        
        // API routes
        $this->addRoute('POST', '/api/login', 'api/login.php');
        $this->addRoute('POST', '/api/register', 'api/register.php');
        $this->addRoute('POST', '/api/logout', 'api/logout.php');
        $this->addRoute('GET', '/api/reservas', 'api/reservas.php', ['auth']);
        $this->addRoute('POST', '/api/reservas', 'api/reservas.php', ['auth']);
        $this->addRoute('PUT', '/api/reservas/{id}', 'api/reservas.php', ['auth']);
        $this->addRoute('DELETE', '/api/reservas/{id}', 'api/reservas.php', ['auth']);
        
        // Webhook para WhatsApp
        $this->addRoute('POST', '/webhook/whatsapp', 'webhook/whatsapp.php');
        $this->addRoute('GET', '/webhook/whatsapp', 'webhook/whatsapp.php');
        
        // Formulario público de reservas
        $this->addRoute('GET', '/reserva/{slug}', 'public-booking.php');
        $this->addRoute('POST', '/reserva/{slug}', 'public-booking.php');
        
        debug_log("✅ " . count($this->routes) . " rutas definidas", 'SUCCESS');
    }
    
    private function addRoute($method, $path, $file, $middlewares = []) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'file' => $file,
            'middlewares' => $middlewares,
            'pattern' => $this->pathToPattern($path)
        ];
    }
    
    private function pathToPattern($path) {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    public function resolve() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getCurrentPath();
        
        debug_log("🔍 Resolviendo: $method $path");
        
        // Buscar ruta coincidente
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                debug_log("✅ Ruta encontrada: {$route['path']} -> {$route['file']}", 'SUCCESS');
                $this->currentRoute = $route;
                
                array_shift($matches);
                $route['params'] = $matches;
                
                return $this->executeRoute($route);
            }
        }
        
        debug_log("❌ Ruta no encontrada: $method $path", 'ERROR');
        return $this->handleNotFound();
    }
    
    private function getCurrentPath() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($path, '/') ?: '/';
    }
    
    private function executeRoute($route) {
        try {
            debug_log("🔧 Ejecutando ruta: {$route['file']}");
            
            // Aplicar middlewares
            foreach ($route['middlewares'] as $middleware) {
                debug_log("🛡️ Aplicando middleware: $middleware");
                if (!$this->applyMiddleware($middleware)) {
                    debug_log("❌ Middleware $middleware bloqueó la ejecución", 'ERROR');
                    return false;
                }
                debug_log("✅ Middleware $middleware exitoso", 'SUCCESS');
            }
            
            // Establecer parámetros
            if (!empty($route['params'])) {
                $GLOBALS['route_params'] = $route['params'];
                debug_log("📝 Parámetros establecidos", 'INFO', $route['params']);
            }
            
            // Verificar archivo
            $filePath = __DIR__ . '/' . $route['file'];
            debug_log("📄 Verificando archivo: $filePath");
            
            if (!debug_check_file($filePath, $route['file'])) {
                return $this->handleNotFound();
            }
            
            debug_log("✅ Incluyendo archivo...");
            
            // Incluir el archivo
            require_once $filePath;
            
            debug_log("🎉 Archivo incluido exitosamente", 'SUCCESS');
            return true;
            
        } catch (Exception $e) {
            debug_log("💥 Error ejecutando ruta: " . $e->getMessage(), 'ERROR');
            return $this->handleError($e);
        }
    }
    
    private function applyMiddleware($middleware) {
        switch ($middleware) {
            case 'auth':
                return $this->authMiddleware();
            default:
                return true;
        }
    }
    
    private function authMiddleware() {
        try {
            debug_context('AUTH_MIDDLEWARE');
            debug_log("🔐 === INICIO MIDDLEWARE AUTH ===");
            
            // Verificar archivos
            $dbConfigPath = __DIR__ . '/includes/db-config.php';
            $authPath = __DIR__ . '/includes/auth.php';
            
            if (!debug_check_file($dbConfigPath, 'db-config.php')) {
                throw new Exception("db-config.php no encontrado");
            }
            
            if (!debug_check_file($authPath, 'auth.php')) {
                throw new Exception("auth.php no encontrado");
            }
            
            // Incluir archivos
            debug_log("📥 Incluyendo dependencias...");
            require_once $dbConfigPath;
            require_once $authPath;
            debug_log("✅ Dependencias incluidas", 'SUCCESS');
            
            // Verificar funciones
            $funciones = ['updateLastActivity', 'isAuthenticated', 'isSessionExpired', 'getAuthenticatedUser', 'generateCSRFToken'];
            $allFunctionsOk = true;
            foreach ($funciones as $funcion) {
                if (!debug_check_function($funcion)) {
                    $allFunctionsOk = false;
                }
            }
            
            if (!$allFunctionsOk) {
                throw new Exception("Funciones de autenticación faltantes");
            }
            
            // Estado de sesión
            debug_log("🔍 Session status: " . session_status(), 'INFO');
            debug_log("🔍 Session ID: " . session_id(), 'INFO');
            debug_log("🔍 Variables de sesión: " . json_encode(array_keys($_SESSION)), 'INFO');
            
            // Actualizar actividad
            debug_checkpoint('Actualizando actividad');
            updateLastActivity();
            
            // Verificar autenticación
            debug_checkpoint('Verificando autenticación');
            $isAuth = isAuthenticated();
            debug_log("🔍 isAuthenticated(): " . ($isAuth ? "TRUE" : "FALSE"), $isAuth ? 'SUCCESS' : 'ERROR');
            
            if (!$isAuth) {
                debug_log("❌ Usuario no autenticado, redirigiendo al login", 'ERROR');
                $this->redirectToLogin();
                return false;
            }
            
            // Verificar expiración
            debug_checkpoint('Verificando expiración');
            $isExpired = isSessionExpired();
            debug_log("⏱️ isSessionExpired(): " . ($isExpired ? "TRUE" : "FALSE"), $isExpired ? 'ERROR' : 'SUCCESS');
            
            if ($isExpired) {
                debug_log("⏰ Sesión expirada, cerrando y redirigiendo", 'ERROR');
                logout();
                $this->redirectToLogin('Tu sesión ha expirado.');
                return false;
            }
            
            // Obtener usuario
            debug_checkpoint('Obteniendo usuario');
            $user = getAuthenticatedUser();
            if (!$user) {
                throw new Exception("No se pudieron obtener datos del usuario");
            }
            
            debug_log("👤 Usuario autenticado: " . $user['email'] . " (" . $user['name'] . ")", 'SUCCESS');
            
            // Variables globales
            $GLOBALS['currentUser'] = $user;
            $GLOBALS['csrfToken'] = generateCSRFToken();
            
            debug_check_global('currentUser');
            debug_check_global('csrfToken');
            
            debug_log("🎉 === MIDDLEWARE AUTH EXITOSO ===", 'SUCCESS');
            return true;
            
        } catch (Exception $e) {
            debug_log("💥 === ERROR EN MIDDLEWARE AUTH ===", 'ERROR');
            debug_log("💥 Error: " . $e->getMessage(), 'ERROR');
            debug_log("💥 Archivo: " . $e->getFile(), 'ERROR');
            debug_log("💥 Línea: " . $e->getLine(), 'ERROR');
            throw $e;
        }
    }
    
    private function redirectToLogin($message = null) {
        if ($message) {
            $_SESSION['login_message'] = $message;
        }
        
        debug_log("🔄 Redirigiendo al login" . ($message ? " - $message" : ""));
        
        if ($this->isAjaxRequest() || $this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'AUTHENTICATION_REQUIRED',
                'message' => 'Sesión expirada. Por favor, recarga la página.',
                'redirect' => '/login'
            ]);
            exit;
        }
        
        header('Location: /login');
        exit;
    }
    
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    private function isApiRequest() {
        return strpos($this->getCurrentPath(), '/api/') === 0;
    }
    
    private function handleNotFound() {
        http_response_code(404);
        debug_log("🔍 404 - Página no encontrada", 'ERROR');
        
        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'NOT_FOUND',
                'message' => 'Endpoint no encontrado'
            ]);
            exit;
        }
        
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Página no encontrada - ReservaBot</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen flex items-center justify-center'>
            <div class='text-center'>
                <h1 class='text-6xl font-bold text-gray-800 mb-4'>404</h1>
                <h2 class='text-2xl font-semibold text-gray-600 mb-4'>Página no encontrada</h2>
                <p class='text-gray-500 mb-8'>La página que buscas no existe.</p>
                <a href='/' class='bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors mr-4'>
                    Volver al dashboard
                </a>
                <a href='/debug-auth' class='bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors'>
                    Debug Auth
                </a>
            </div>
        </body>
        </html>";
        
        return false;
    }
    
    private function handleError(Exception $e) {
        http_response_code(500);
        debug_log("💥 Error 500: " . $e->getMessage(), 'ERROR');
        
        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'INTERNAL_ERROR',
                'message' => 'Error interno del servidor',
                'debug' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
            exit;
        }
        
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Error - ReservaBot</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen p-8'>
            <div class='max-w-4xl mx-auto'>
                <div class='bg-red-50 border border-red-200 rounded-lg p-6'>
                    <h1 class='text-2xl font-bold text-red-800 mb-4'>Error del Router</h1>
                    <div class='space-y-4'>
                        <div>
                            <h3 class='font-semibold text-red-700'>Mensaje:</h3>
                            <p class='text-red-600 font-mono text-sm bg-red-100 p-3 rounded mt-1'>" . htmlspecialchars($e->getMessage()) . "</p>
                        </div>
                        <div>
                            <h3 class='font-semibold text-red-700'>Archivo:</h3>
                            <p class='text-red-600 font-mono text-sm'>" . htmlspecialchars($e->getFile()) . "</p>
                        </div>
                        <div>
                            <h3 class='font-semibold text-red-700'>Línea:</h3>
                            <p class='text-red-600 font-mono text-sm'>" . $e->getLine() . "</p>
                        </div>
                    </div>
                </div>
                <div class='text-center mt-6'>
                    <a href='/' class='bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 mr-4'>
                        Volver al dashboard
                    </a>
                    <a href='/debug-auth' class='bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700'>
                        Debug Auth
                    </a>
                </div>
            </div>
        </body>
        </html>";
        
        return false;
    }
    
    // Métodos helper estáticos
    public static function getParam($index = 0) {
        return $GLOBALS['route_params'][$index] ?? null;
    }
    
    public static function getParams() {
        return $GLOBALS['route_params'] ?? [];
    }
    
    public static function url($path) {
        return rtrim($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'], '/') . $path;
    }
}

// Funciones helper globales
function route_param($index = 0) {
    return Router::getParam($index);
}

function url($path) {
    return Router::url($path);
}

// No ejecutar automáticamente el router aquí
// El router se ejecuta desde index.php
?>