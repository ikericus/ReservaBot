<?php
/**
 * Router centralizado para ReservaBot
 * Maneja todas las rutas de la aplicación
 */

class Router {
    private $routes = [];
    private $middlewares = [];
    private $currentRoute = null;
    
    public function __construct() {
        $this->defineRoutes();
    }
    
    /**
     * Definir todas las rutas de la aplicación
     */
    private function defineRoutes() {
                
        // Rutas públicas
        $this->addRoute('GET', '/landing', 'landing.php');

        $this->addRoute('GET',  '/login',                   'login.php');
        $this->addRoute('GET',  '/signup',                  'signup.php');
        $this->addRoute('GET',  '/logout',                  'logout.php');
        $this->addRoute('GET',  '/password-reset',          'password-reset.php');
        $this->addRoute('POST', '/password-reset',          'password-reset.php');                    
        $this->addRoute('POST', '/api/login-handler',       'api/login-handler.php');        
        $this->addRoute('POST', '/api/register-handler',    'api/register-handler.php');
        $this->addRoute('POST', '/api/login',               'api/login.php');
        $this->addRoute('POST', '/api/register',            'api/register.php');
        $this->addRoute('POST', '/api/logout',              'api/logout.php');

        // Formulario público de reservas
        $this->addRoute('GET',  '/reserva/{slug}',  'public-booking.php');
        $this->addRoute('POST', '/reserva/{slug}',  'public-booking.php');

        // Rutas protegidas        
        $this->addRoute('GET',  '/',                 'reservas.php',         ['auth']);
        $this->addRoute('GET',  '/calendario',       'calendario.php',       ['auth']);
        $this->addRoute('GET',  '/reservas',         'reservas.php',         ['auth']);
        $this->addRoute('GET',  '/clientes',         'clientes.php',         ['auth']);
        $this->addRoute('GET',  '/formularios',      'formularios.php',      ['auth']);
        $this->addRoute('GET',  '/notificaciones',   'notificaciones.php',   ['auth']);
        
        $this->addRoute('GET',  '/day',              'day.php',              ['auth']);
        $this->addRoute('GET',  '/cliente-detail',   'cliente-detail.php',   ['auth']);
        $this->addRoute('GET',  '/configuracion',    'configuracion.php',    ['auth']);
        $this->addRoute('GET',  '/perfil',           'perfil.php',           ['auth']);
        $this->addRoute('GET',  '/estadisticas',     'estadisticas.php',     ['auth']);
        $this->addRoute('GET',  '/perfil',           'perfil.php',           ['auth']);        


        $this->addRoute('POST',     '/api/actualizar-reserva',          'api/actualizar-reserva.php',       ['auth']);
        $this->addRoute('POST',     '/api/eliminar-reserva',            'api/eliminar-reserva.php',         ['auth']);
        $this->addRoute('POST',     '/api/actualizar-configuracion',    'api/actualizar-configuracion.php', ['auth']);
        $this->addRoute('GET',      '/api/reservas',                    'api/reservas.php',                 ['auth']);
        $this->addRoute('POST',     '/api/reservas',                    'api/reservas.php',                 ['auth']);
        $this->addRoute('PUT',      '/api/reservas/{id}',               'api/reservas.php',                 ['auth']);
        $this->addRoute('DELETE',   '/api/reservas/{id}',               'api/reservas.php',                 ['auth']);
        
        // Webhook para WhatsApp
        $this->addRoute('POST',     '/webhook/whatsapp',    'webhook/whatsapp.php');
        $this->addRoute('GET',      '/webhook/whatsapp',    'webhook/whatsapp.php');        
    }
    
    /**
     * Agregar una ruta
     */
    private function addRoute($method, $path, $file, $middlewares = []) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'file' => $file,
            'middlewares' => $middlewares,
            'pattern' => $this->pathToPattern($path)
        ];
    }
    
    /**
     * Convertir path con parámetros a patrón regex
     */
    private function pathToPattern($path) {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Resolver la ruta actual
     */
    public function resolve() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getCurrentPath();
        
        // Log de la request
        error_log("Router: Procesando {$method} {$path}");
        
        // Buscar ruta coincidente
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                $this->currentRoute = $route;
                
                // Extraer parámetros de la URL
                array_shift($matches); // Remover match completo
                $route['params'] = $matches;
                
                return $this->executeRoute($route);
            }
        }
        
        // Ruta no encontrada
        return $this->handleNotFound();
    }
    
    /**
     * Obtener path actual limpio
     */
    private function getCurrentPath() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($path, '/') ?: '/';
    }
    
    /**
     * Ejecutar la ruta encontrada
     */
    private function executeRoute($route) {
        try {
            // Aplicar middlewares
            foreach ($route['middlewares'] as $middleware) {
                if (!$this->applyMiddleware($middleware)) {
                    return false; // Middleware bloqueó la ejecución
                }
            }
            
            // Establecer parámetros globales para el archivo
            if (!empty($route['params'])) {
                $GLOBALS['route_params'] = $route['params'];
            }
            
            // Verificar que el archivo existe
            $filePath = __DIR__ . '/' . $route['file'];
            if (!file_exists($filePath)) {
                error_log("Router: Archivo no encontrado - " . $filePath);
                return $this->handleNotFound();
            }
            
            // Log de la ruta ejecutada
            error_log("Router: Ejecutando {$route['method']} {$route['path']} -> {$route['file']}");
            
            // Incluir el archivo
            require_once $filePath;
            return true;
            
        } catch (Exception $e) {
            error_log("Router: Error ejecutando ruta - " . $e->getMessage());
            return $this->handleError($e);
        }
    }
    
    /**
     * Aplicar middleware específico
     */
    private function applyMiddleware($middleware) {
        switch ($middleware) {
            case 'auth':
                return $this->authMiddleware();
            default:
                return true;
        }
    }
    
    /**
     * Middleware de autenticación
     */
    private function authMiddleware() {
        require_once __DIR__ . '/includes/db-config.php';
        require_once __DIR__ . '/includes/auth.php';
        
        error_log("Router: Aplicando middleware de autenticación");
        
        // Actualizar última actividad
        updateLastActivity();
        
        // Verificar autenticación
        if (!isAuthenticated()) {
            error_log("Router: Usuario no autenticado, redirigiendo");
            $this->redirectToLogin();
            return false;
        }
        
        // Verificar expiración
        if (isSessionExpired()) {
            error_log("Router: Sesión expirada, cerrando sesión");
            logout();
            $this->redirectToLogin('Tu sesión ha expirado.');
            return false;
        }
        
        // Hacer disponible el usuario actual
        $GLOBALS['currentUser'] = getAuthenticatedUser();
        $GLOBALS['csrfToken'] = generateCSRFToken();
        
        error_log("Router: Middleware de autenticación exitoso para " . $GLOBALS['currentUser']['email']);
        return true;
    }
    
    /**
     * Redirigir al login
     */
    private function redirectToLogin($message = null) {
        if ($message) {
            $_SESSION['login_message'] = $message;
        }
        
        error_log("Router: Redirigiendo al login" . ($message ? " - $message" : ""));
        
        // Si es petición AJAX o API, responder JSON
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
        
        // Redirección normal
        header('Location: /login');
        exit;
    }
    
    /**
     * Verificar si es petición AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Verificar si es petición API
     */
    private function isApiRequest() {
        return strpos($this->getCurrentPath(), '/api/') === 0;
    }
    
    /**
     * Manejar 404
     */
    private function handleNotFound() {
        http_response_code(404);
        error_log("Router: 404 - " . $this->getCurrentPath());
        
        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'NOT_FOUND',
                'message' => 'Endpoint no encontrado'
            ]);
            exit;
        }
        
        // Página 404 personalizada
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
                <a href='/' class='bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors'>
                    Volver al inicio
                </a>
            </div>
        </body>
        </html>";
        
        return false;
    }
    
    /**
     * Manejar errores
     */
    private function handleError(Exception $e) {
        http_response_code(500);
        error_log("Router: Error 500 - " . $e->getMessage());
        
        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'INTERNAL_ERROR',
                'message' => 'Error interno del servidor'
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
        <body class='bg-gray-100 min-h-screen flex items-center justify-center'>
            <div class='text-center'>
                <h1 class='text-6xl font-bold text-red-600 mb-4'>Error</h1>
                <h2 class='text-2xl font-semibold text-gray-600 mb-4'>Algo salió mal</h2>
                <p class='text-gray-500 mb-8'>Ha ocurrido un error interno. Por favor, intenta nuevamente.</p>
                <a href='/' class='bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors'>
                    Volver al inicio
                </a>
            </div>
        </body>
        </html>";
        
        return false;
    }
    
    /**
     * Obtener parámetro de ruta
     */
    public static function getParam($index = 0) {
        return $GLOBALS['route_params'][$index] ?? null;
    }
    
    /**
     * Obtener todos los parámetros
     */
    public static function getParams() {
        return $GLOBALS['route_params'] ?? [];
    }
    
    /**
     * Generar URL
     */
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

// Ejecutar el router si se incluye directamente
if (basename($_SERVER['SCRIPT_NAME']) === 'router.php') {
    $router = new Router();
    $router->resolve();
}
?>