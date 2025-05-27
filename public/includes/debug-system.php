<?php
/**
 * Sistema de debug centralizado para ReservaBot
 * Un solo sistema que funciona para router, páginas individuales, y cualquier contexto
 */

class ReservaBotDebugger {
    private static $instance = null;
    private static $logs = [];
    private static $startTime;
    private static $contexts = [];
    private static $config = [
        'enabled' => true,
        'show_panel' => true,
        'log_to_file' => true,
        'max_logs' => 500,
        'panel_position' => 'top-right' // top-right, top-left, bottom-right, bottom-left
    ];
    
    /**
     * Singleton - Una sola instancia para toda la aplicación
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        self::$startTime = microtime(true);
        
        // Configurar manejo de errores si está habilitado
        if (self::$config['enabled']) {
            set_error_handler([self::class, 'errorHandler']);
            set_exception_handler([self::class, 'exceptionHandler']);
            register_shutdown_function([self::class, 'shutdownHandler']);
        }
    }
    
    /**
     * Configurar el debugger
     */
    public static function configure($options = []) {
        self::$config = array_merge(self::$config, $options);
    }
    
    /**
     * Inicializar contexto (Router, Página, API, etc.)
     */
    public static function context($name, $data = []) {
        if (!self::$config['enabled']) return;
        
        self::$contexts[$name] = [
            'start_time' => microtime(true),
            'data' => $data
        ];
        
        self::log("🚀 Iniciando contexto: $name", 'CONTEXT', $data);
    }
    
    /**
     * Log principal - funciona para todo
     */
    public static function log($message, $level = 'INFO', $data = null) {
        if (!self::$config['enabled']) return;
        
        $timestamp = date('H:i:s.') . substr(microtime(), 2, 3);
        $memory = round(memory_get_usage() / 1024 / 1024, 2) . 'MB';
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        
        // Encontrar el caller real (no este archivo)
        $caller = 'unknown';
        foreach ($trace as $frame) {
            if (isset($frame['file']) && basename($frame['file']) !== 'debug-system.php') {
                $caller = basename($frame['file']) . ':' . ($frame['line'] ?? '?');
                break;
            }
        }
        
        $logEntry = [
            'time' => $timestamp,
            'level' => $level,
            'message' => $message,
            'caller' => $caller,
            'memory' => $memory,
            'data' => $data,
            'context' => self::getCurrentContext()
        ];
        
        self::$logs[] = $logEntry;
        
        // Limitar logs si hay demasiados
        if (count(self::$logs) > self::$config['max_logs']) {
            array_shift(self::$logs);
        }
        
        // Log a archivo PHP si está habilitado
        if (self::$config['log_to_file']) {
            $contextStr = $logEntry['context'] ? "[{$logEntry['context']}] " : '';
            error_log("RESERVABOT_DEBUG: {$contextStr}[{$level}] $message - {$caller}");
        }
    }
    
    /**
     * Checkpoint - marcar puntos importantes
     */
    public static function checkpoint($name, $data = null) {
        if (!self::$config['enabled']) return;
        
        $elapsed = round((microtime(true) - self::$startTime) * 1000, 2);
        self::log("🚩 Checkpoint '$name' ({$elapsed}ms)", 'CHECKPOINT', $data);
    }
    
    /**
     * Verificar archivo
     */
    public static function checkFile($path, $description = null) {
        if (!self::$config['enabled']) return false;
        
        $desc = $description ?: basename($path);
        
        if (file_exists($path)) {
            if (is_readable($path)) {
                self::log("✅ $desc existe y es legible", 'CHECK');
                return true;
            } else {
                self::log("⚠️ $desc existe pero NO es legible", 'WARNING');
                return false;
            }
        } else {
            self::log("❌ $desc NO existe: $path", 'ERROR');
            return false;
        }
    }
    
    /**
     * Verificar función
     */
    public static function checkFunction($name) {
        if (!self::$config['enabled']) return function_exists($name);
        
        if (function_exists($name)) {
            self::log("✅ Función $name() disponible", 'CHECK');
            return true;
        } else {
            self::log("❌ Función $name() NO disponible", 'ERROR');
            return false;
        }
    }
    
    /**
     * Verificar variable
     */
    public static function checkVar($name, $value = null, $global = false) {
        if (!self::$config['enabled']) return;
        
        $varName = $global ? "GLOBALS['$name']" : "\$$name";
        
        if ($global) {
            $exists = isset($GLOBALS[$name]);
            $val = $GLOBALS[$name] ?? null;
        } else {
            $exists = isset($value);
            $val = $value;
        }
        
        if ($exists && $val !== null) {
            $type = gettype($val);
            $preview = is_array($val) ? count($val) . ' elementos' : 
                      (is_string($val) ? substr($val, 0, 50) . (strlen($val) > 50 ? '...' : '') : 
                       (is_object($val) ? get_class($val) : $val));
            
            self::log("✅ $varName definida ($type): $preview", 'CHECK');
        } else {
            self::log("❌ $varName NO definida", 'WARNING');
        }
    }
    
    /**
     * Obtener contexto actual
     */
    private static function getCurrentContext() {
        if (empty(self::$contexts)) return null;
        return implode(' > ', array_keys(self::$contexts));
    }
    
    /**
     * Mostrar panel de debug
     */
    public static function showPanel() {
        if (!self::$config['enabled'] || !self::$config['show_panel'] || empty(self::$logs)) {
            return;
        }
        
        $totalTime = round((microtime(true) - self::$startTime) * 1000, 2);
        $logCount = count(self::$logs);
        $contexts = implode(', ', array_keys(self::$contexts));
        
        // Determinar posición
        $positions = [
            'top-right' => 'top: 10px; right: 10px;',
            'top-left' => 'top: 10px; left: 10px;',
            'bottom-right' => 'bottom: 10px; right: 10px;',
            'bottom-left' => 'bottom: 10px; left: 10px;'
        ];
        
        $position = $positions[self::$config['panel_position']] ?? $positions['top-right'];
        
        echo "
        <div id='reservabot-debug-panel' style='position: fixed; {$position} background: rgba(0,0,0,0.95); color: #00ff00; padding: 15px; border-radius: 8px; font-family: \"Courier New\", monospace; font-size: 11px; max-width: 600px; max-height: 80vh; overflow-y: auto; z-index: 99999; border: 2px solid #333; box-shadow: 0 4px 20px rgba(0,0,0,0.8);'>
            
            <div style='color: #ffff00; font-weight: bold; margin-bottom: 10px; text-align: center; border-bottom: 1px solid #555; padding-bottom: 8px;'>
                🐛 RESERVABOT DEBUG
                <div style='font-size: 10px; color: #aaa; margin-top: 3px;'>
                    {$logCount} logs • {$totalTime}ms • {$contexts}
                </div>
            </div>
            
            <div style='max-height: 400px; overflow-y: auto;'>";
        
        foreach (self::$logs as $index => $log) {
            $bgColor = $index % 2 == 0 ? 'rgba(255,255,255,0.05)' : 'transparent';
            $levelColor = self::getLevelColor($log['level']);
            
            echo "<div style='margin-bottom: 8px; padding: 6px; background: $bgColor; border-radius: 3px; border-left: 3px solid $levelColor;'>";
            
            // Header con tiempo, contexto y memoria
            echo "<div style='color: #888; font-size: 10px; display: flex; justify-content: space-between; margin-bottom: 2px;'>";
            echo "<span>[{$log['time']}]";
            if ($log['context']) echo " <span style='color: #66aaff;'>[{$log['context']}]</span>";
            echo " {$log['caller']}</span>";
            echo "<span>{$log['memory']}</span>";
            echo "</div>";
            
            // Mensaje principal
            echo "<div style='color: $levelColor; margin: 2px 0;'>";
            echo "<span style='color: #666; font-size: 9px;'>[{$log['level']}]</span> " . htmlspecialchars($log['message']);
            echo "</div>";
            
            // Data adicional si existe
            if ($log['data']) {
                echo "<div style='color: #999; font-size: 9px; margin-top: 3px; padding: 3px; background: rgba(255,255,255,0.05); border-radius: 2px;'>";
                echo "📊 " . htmlspecialchars(json_encode($log['data'], JSON_UNESCAPED_UNICODE));
                echo "</div>";
            }
            
            echo "</div>";
        }
        
        echo "
            </div>
            
            <div style='margin-top: 15px; text-align: center; border-top: 1px solid #555; padding-top: 10px;'>
                <button onclick='document.getElementById(\"reservabot-debug-panel\").style.display=\"none\"' 
                        style='background: #ff4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-right: 5px; font-size: 10px;'>
                    Cerrar
                </button>
                <button onclick='toggleReservaBotDebug()' 
                        style='background: #4444ff; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-right: 5px; font-size: 10px;'>
                    Minimizar
                </button>
                <button onclick='copyReservaBotLogs()' 
                        style='background: #44aa44; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-right: 5px; font-size: 10px;'>
                    Copiar
                </button>
                <button onclick='clearReservaBotLogs()' 
                        style='background: #aa4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 10px;'>
                    Limpiar
                </button>
            </div>
        </div>
        
        <script>
        function toggleReservaBotDebug() {
            const panel = document.getElementById('reservabot-debug-panel');
            const isRight = panel.style.right;
            const isLeft = panel.style.left;
            
            if (isRight) {
                panel.style.right = panel.style.right === '10px' ? '-580px' : '10px';
            } else {
                panel.style.left = panel.style.left === '10px' ? '-580px' : '10px';
            }
        }
        
        function copyReservaBotLogs() {
            const logs = " . json_encode(array_map(function($log) {
                return "[{$log['time']}] [{$log['level']}] {$log['message']} ({$log['caller']})";
            }, self::$logs)) . ";
            
            const text = logs.join('\\n');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => alert('Logs copiados'));
            }
        }
        
        function clearReservaBotLogs() {
            if (confirm('¿Limpiar todos los logs?')) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'clear_debug_logs=1'
                }).then(() => location.reload());
            }
        }
        </script>";
    }
    
    /**
     * Colores por nivel
     */
    private static function getLevelColor($level) {
        $colors = [
            'ERROR' => '#ff4444',
            'WARNING' => '#ffaa44', 
            'INFO' => '#00ff00',
            'SUCCESS' => '#44ff44',
            'DEBUG' => '#4444ff',
            'CONTEXT' => '#ff44ff',
            'CHECKPOINT' => '#44ffff',
            'CHECK' => '#aaff44'
        ];
        
        return $colors[$level] ?? '#00ff00';
    }
    
    /**
     * Manejadores de errores
     */
    public static function errorHandler($severity, $message, $file, $line) {
        $types = [E_ERROR => 'ERROR', E_WARNING => 'WARNING', E_NOTICE => 'NOTICE'];
        $type = $types[$severity] ?? 'PHP_ERROR';
        
        self::log("💥 PHP $type: $message en " . basename($file) . ":$line", 'ERROR');
        return $severity !== E_ERROR;
    }
    
    public static function exceptionHandler($exception) {
        self::log("💥 Exception: " . $exception->getMessage() . " en " . basename($exception->getFile()) . ":" . $exception->getLine(), 'ERROR');
        self::showPanel();
    }
    
    public static function shutdownHandler() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::log("💀 Fatal Error: {$error['message']} en " . basename($error['file']) . ":{$error['line']}", 'ERROR');
        }
        
        // Mostrar panel automáticamente al final
        if (self::$config['show_panel']) {
            self::showPanel();
        }
    }
    
    /**
     * Limpiar logs (para AJAX)
     */
    public static function clearLogs() {
        self::$logs = [];
    }
}

// ===============================================
// FUNCIONES GLOBALES PARA USAR EN CUALQUIER LADO
// ===============================================

/**
 * Configurar debug globalmente
 */
function debug_configure($options = []) {
    ReservaBotDebugger::configure($options);
}

/**
 * Inicializar contexto
 */
function debug_context($name, $data = []) {
    ReservaBotDebugger::context($name, $data);
}

/**
 * Log general - usar en cualquier archivo
 */
function debug_log($message, $level = 'INFO', $data = null) {
    ReservaBotDebugger::log($message, $level, $data);
}

/**
 * Checkpoint
 */
function debug_checkpoint($name, $data = null) {
    ReservaBotDebugger::checkpoint($name, $data);
}

/**
 * Verificaciones rápidas
 */
function debug_check_file($path, $desc = null) {
    return ReservaBotDebugger::checkFile($path, $desc);
}

function debug_check_function($name) {
    return ReservaBotDebugger::checkFunction($name);
}

function debug_check_var($name, $value = null) {
    ReservaBotDebugger::checkVar($name, $value);
}

function debug_check_global($name) {
    ReservaBotDebugger::checkVar($name, null, true);
}

/**
 * Mostrar panel manualmente
 */
function debug_show_panel() {
    ReservaBotDebugger::showPanel();
}

// Auto-inicializar el debugger
ReservaBotDebugger::getInstance();

// Manejar limpieza de logs via POST
if (isset($_POST['clear_debug_logs'])) {
    ReservaBotDebugger::clearLogs();
    exit('OK');
}
?>