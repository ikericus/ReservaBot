<?php
// public/config/router.php

// Definir raíz del proyecto (public/)
define('PROJECT_ROOT', dirname(__DIR__));

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
        $this->addRoute('GET',      '/test',                        'pages/test.php',        ['auth', 'admin']);
        $this->addRoute('GET',      '/',                            'pages/home.php');
        $this->addRoute('GET',      '/landing',                     'pages/landing.php');
        $this->addRoute('GET',      '/reservar',                    'pages/reservar.php');
        $this->addRoute('POST',     '/reservar',                    'pages/reservar.php');
        $this->addRoute('GET',      '/mi-reserva',                  'pages/mi-reserva.php');
        $this->addRoute('GET',      '/login',                       'pages/user/login.php');
        $this->addRoute('GET',      '/signup',                      'pages/user/signup.php');
        $this->addRoute('GET',      '/logout',                      'pages/user/logout.php');
        $this->addRoute('GET',      '/password-reset',              'pages/user/password-reset.php');
        $this->addRoute('POST',     '/password-reset',              'pages/user/password-reset.php');    
        $this->addRoute('GET',      '/verificar-email',             'pages/user/verificar-email.php');               
        $this->addRoute('POST',     '/api/login-handler',           'api/user/login-handler.php');        
        $this->addRoute('POST',     '/api/register-handler',        'api/user/register-handler.php');
        $this->addRoute('POST',     '/api/register',                'api/user/register.php');
        $this->addRoute('POST',     '/api/contacto-handler',        'api/contacto-handler.php');   

        // Admin
        $this->addRoute('GET',      '/admin',                       'pages/admin/dashboard.php',        ['auth', 'admin']);
        $this->addRoute('GET',      '/admin/dashboard',             'pages/admin/dashboard.php',        ['auth', 'admin']);
        $this->addRoute('GET',      '/admin/actividad',             'pages/admin/actividad.php',        ['auth', 'admin']);
        $this->addRoute('GET',      '/admin/usuarios',              'pages/admin/usuarios.php',         ['auth', 'admin']);
        $this->addRoute('GET',      '/admin/reservas',              'pages/admin/reservas.php',         ['auth', 'admin']);
        $this->addRoute('GET',      '/admin/whatsapp',              'pages/admin/whatsapp.php',         ['auth', 'admin']);
        $this->addRoute('GET',      '/admin/logs',                  'pages/admin/logs.php',             ['auth', 'admin']);

        // Rutas protegidas            
        $this->addRoute('GET',      '/dia',                             'pages/dia.php',                        ['auth']);        
        $this->addRoute('GET',      '/semana',                          'pages/semana.php',                     ['auth']);        
        $this->addRoute('GET',      '/mes',                             'pages/mes.php',                        ['auth']);
        $this->addRoute('GET',      '/reservas',                        'pages/reservas.php',                   ['auth']);
        $this->addRoute('GET',      '/clientes',                        'pages/clientes.php',                   ['auth']);
        $this->addRoute('GET',      '/formularios',                     'pages/formularios.php',                ['auth']);
        $this->addRoute('POST',     '/formularios',                     'pages/formularios.php',                ['auth']);
        $this->addRoute('GET',      '/notificaciones',                  'pages/notificaciones.php',             ['auth']);        
        $this->addRoute('GET',      '/configuracion',                   'pages/configuracion.php',              ['auth']);
        $this->addRoute('GET',      '/perfil',                          'pages/perfil.php',                     ['auth']);
        $this->addRoute('GET',      '/estadisticas',                    'pages/estadisticas.php',               ['auth']);
        $this->addRoute('GET',      '/day',                             'pages/day.php',                        ['auth']);
        $this->addRoute('GET',      '/cliente-detail',                  'pages/cliente-detail.php',             ['auth']);        
        $this->addRoute('GET',      '/reserva',                         'pages/reserva.php',                    ['auth']);        
        $this->addRoute('GET',      '/reserva-form',                    'pages/reserva-form.php',               ['auth']);    
        $this->addRoute('GET',      '/whatsapp',                        'pages/whatsapp.php',                   ['auth']);          
        $this->addRoute('GET',      '/conversaciones',                  'pages/conversaciones.php',             ['auth']);  
        $this->addRoute('POST',     '/api/crear-reserva',               'api/crear-reserva.php',                ['auth']);
        $this->addRoute('POST',     '/api/actualizar-reserva',          'api/actualizar-reserva.php',           ['auth']);        
        $this->addRoute('POST',     '/api/crear-reserva-publica',       'api/crear-reserva-publica.php',        ['auth']);
        $this->addRoute('POST',     '/api/eliminar-reserva',            'api/eliminar-reserva.php',             ['auth']);
        $this->addRoute('POST',     '/api/horas-disponibles',           'api/horas-disponibles.php',            ['auth']);
        $this->addRoute('POST',     '/api/actualizar-configuracion',    'api/actualizar-configuracion.php',     ['auth']);
        $this->addRoute('POST',     '/api/whatsapp-connect',            'api/whatsapp-connect.php',             ['auth']);
        $this->addRoute('GET',      '/api/whatsapp-status',             'api/whatsapp-status.php',              ['auth']);
        $this->addRoute('POST',     '/api/whatsapp-disconnect',         'api/whatsapp-disconnect.php',          ['auth']);
        $this->addRoute('POST',     '/api/whatsapp-stats',              'api/whatsapp-stats.php',               ['auth']);
        $this->addRoute('POST',     '/api/send-whatsapp',               'api/whatsapp-send.php',                ['auth']);
        $this->addRoute('GET',      '/api/whatsapp-conversations',      'api/whatsapp-conversations.php',       ['auth']);
        $this->addRoute('POST',     '/api/buscar-clientes',             'api/buscar-clientes.php',              ['auth']);
        
        // Webhook para WhatsApp
        $this->addRoute('POST',     '/api/whatsapp-webhook',            'api/whatsapp-webhook.php');
        $this->addRoute('GET',      '/api/whatsapp-webhook',            'api/whatsapp-webhook.php');        
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
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                $this->currentRoute = $route;
                array_shift($matches);
                $route['params'] = $matches;
                
                return $this->executeRoute($route);
            }
        }
        
        return $this->handleNotFound();
    }
    
    private function getCurrentPath() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($path, '/') ?: '/';
    }
    
    private function executeRoute($route) {
        try {
            // Cargar bootstrap SIEMPRE antes de ejecutar cualquier ruta
            // Esto asegura que todas las rutas tengan acceso a funciones y configuración
            if (!function_exists('getPDO')) {
                require_once PROJECT_ROOT . '/config/bootstrap.php';
            }

            debug_log("Router: Resolviendo ruta - {$route['path']}");
            
            foreach ($route['middlewares'] as $middleware) {
                if (!$this->applyMiddleware($middleware)) {
                    debug_log("Router: Middleware {$middleware} bloqueó la ruta - {$route['path']}");
                    return false;
                }
            }

            debug_log("Router: Cargando ruta - {$route['path']}");
            
            if (!empty($route['params'])) {
                $GLOBALS['route_params'] = $route['params'];
            }
            
            $filePath = PROJECT_ROOT . '/' . $route['file'];
            if (!file_exists($filePath)) {
                error_log("Router: Archivo no encontrado - " . $filePath);
                return $this->handleNotFound();
            }

            debug_log("Router: Cargando fichero - " . $filePath);
            require_once $filePath;
            return true;
            
        } catch (Exception $e) {
            error_log("Router: Error - " . $e->getMessage());
            return $this->handleError($e);
        }
    }
    
    private function applyMiddleware($middleware) {
        debug_log("Router: Aplicando middleware - {$middleware}");
        switch ($middleware) {
            case 'auth':
                return $this->authMiddleware();
            case 'admin':
                return $this->adminMiddleware();
            default:
                return true;
        }
    }
    
    /**
     * Middleware de autenticación
     */
    private function authMiddleware() {
        // Bootstrap ya está cargado por executeRoute
        debug_log("Router: Verificando autenticación de usuario");
        updateLastActivity();
        
        if (!isAuthenticated()) {
            $this->redirectToLogin();
            return false;
        }
        debug_log("Router: Usuario autenticado");
        if (isSessionExpired()) {
            logout();
            $this->redirectToLogin('Tu sesión ha expirado.');
            return false;
        }
        debug_log("Router: Sesión válida");
        $GLOBALS['currentUser'] = getAuthenticatedUser();
        $GLOBALS['csrfToken'] = generateCSRFToken();
        debug_log("Router: CSRF token generado");
        return true;
    }

    private function adminMiddleware() {       
        debug_log("Router: Verificando acceso de administrador");

        return isAdmin();
    }
    
    private function redirectToLogin($message = null) {
        if ($message) {
            $_SESSION['login_message'] = $message;
        }
        
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
            <title>404 - ReservaBot</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen flex items-center justify-center'>
            <div class='text-center'>
                <h1 class='text-6xl font-bold text-gray-800 mb-4'>404</h1>
                <h2 class='text-2xl font-semibold text-gray-600 mb-4'>Página no encontrada</h2>
                <a href='/' class='bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700'>
                    Volver al inicio
                </a>
            </div>
        </body>
        </html>";
        
        return false;
    }
    
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
            <title>Error - ReservaBot</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen flex items-center justify-center'>
            <div class='text-center'>
                <h1 class='text-6xl font-bold text-red-600 mb-4'>Error</h1>
                <h2 class='text-2xl font-semibold text-gray-600 mb-4'>Algo salió mal</h2>
                <a href='/' class='bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700'>
                    Volver al inicio
                </a>
            </div>
        </body>
        </html>";
        
        return false;
    }
    
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

function route_param($index = 0) {
    return Router::getParam($index);
}

function url($path) {
    return Router::url($path);
}