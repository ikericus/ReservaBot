<?php
/**
 * Configuración centralizada para WhatsApp
 * Solo contiene constantes y configuraciones, sin funciones auxiliares
 */

class WhatsAppConfig {
    
    // === CONFIGURACIÓN DEL SERVIDOR ===
    const SERVER_URL = 'http://server.reservabot.es:3001';
    const WEBAPP_URL = 'http://server.reservabot.es';
    
    // === SEGURIDAD ===
    const JWT_SECRET = 'da3c7b9e13a38a0ea3dcbaaed1ec9ec1f0005f974adad7141b71a36e9f13e187';
    const WEBHOOK_SECRET = 'c4f20ece15858d35db6d02e55269de628df3ea8c66246d75a07ce77c9c3c4810';
    
    // === TIMEOUTS Y LÍMITES ===
    const REQUEST_TIMEOUT = 30;        // segundos
    const MAX_RETRY_ATTEMPTS = 3;
    const RETRY_DELAY = 2;             // segundos
    const CONNECT_TIMEOUT = 10;        // segundos
    const MAX_REDIRECTS = 3;
    
    // === ESTADOS VÁLIDOS ===
    const VALID_STATUSES = [
        'disconnected',
        'connecting', 
        'waiting_qr',
        'qr_ready',
        'ready',
        'connected',
        'auth_failed',
        'server_error',
        'error'
    ];
    
    // === ENDPOINTS DE API ===
    const ENDPOINTS = [
        'connect' => '/api/connect',
        'disconnect' => '/api/disconnect', 
        'status' => '/api/status',
        'send' => '/api/send',
        'chats' => '/api/chats',
        'info' => '/api/info',
        'health' => '/health',
        'start' => '/start',
        'stop' => '/stop'
    ];
    
    // === TIPOS DE MENSAJE ===
    const MESSAGE_TYPES = [
        'welcome',
        'confirmation', 
        'reminder',
        'cancellation',
        'modification',
        'manual',
        'auto'
    ];
    
    // === CONFIGURACIÓN DE BASE DE DATOS ===
    const DB_TABLES = [
        'config' => 'whatsapp_config',
        'messages' => 'whatsapp_messages',
        'contacts' => 'whatsapp_contacts',
        'conversations' => 'conversaciones',
        'autoresponses' => 'autorespuestas_whatsapp'
    ];
    
    // === PLANTILLAS DE MENSAJE POR DEFECTO ===
    const DEFAULT_TEMPLATES = [
        'welcome' => '¡Hola {cliente}! Gracias por contactarnos. ¿En qué podemos ayudarte?',
        'confirmation' => 'Hola {cliente}, tu reserva ha sido confirmada para el {fecha} a las {hora}. ¡Te esperamos en {negocio}!',
        'reminder' => 'Hola {cliente}, te recordamos tu cita de mañana {fecha} a las {hora} en {negocio}. Si necesitas cambiarla, contáctanos.',
        'cancellation' => 'Hola {cliente}, tu reserva del {fecha} a las {hora} ha sido cancelada. Disculpa las molestias.',
        'modification' => 'Hola {cliente}, tu reserva ha sido modificada. Nueva fecha: {fecha} a las {hora}.'
    ];
    
    // === CONFIGURACIONES DE NOTIFICACIÓN ===
    const NOTIFICATION_SETTINGS = [
        'nueva_reserva',
        'confirmacion', 
        'recordatorio',
        'cancelacion'
    ];
    
    // === CÓDIGOS DE PAÍS COMUNES ===
    const COUNTRY_CODES = [
        'ES' => '34',  // España
        'MX' => '52',  // México
        'AR' => '54',  // Argentina
        'CO' => '57',  // Colombia
        'PE' => '51',  // Perú
        'CL' => '56',  // Chile
    ];
    
    /**
     * Obtener toda la configuración como array
     */
    public static function getAll() {
        return [
            'server_url' => self::SERVER_URL,
            'webapp_url' => self::WEBAPP_URL,
            'jwt_secret' => self::JWT_SECRET,
            'webhook_secret' => self::WEBHOOK_SECRET,
            'request_timeout' => self::REQUEST_TIMEOUT,
            'max_retry_attempts' => self::MAX_RETRY_ATTEMPTS,
            'retry_delay' => self::RETRY_DELAY,
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'max_redirects' => self::MAX_REDIRECTS,
            'valid_statuses' => self::VALID_STATUSES,
            'endpoints' => self::ENDPOINTS,
            'message_types' => self::MESSAGE_TYPES,
            'db_tables' => self::DB_TABLES,
            'default_templates' => self::DEFAULT_TEMPLATES,
            'notification_settings' => self::NOTIFICATION_SETTINGS,
            'country_codes' => self::COUNTRY_CODES
        ];
    }
    
    /**
     * Obtener URL de un endpoint específico
     */
    public static function getEndpointUrl($endpoint) {
        if (!isset(self::ENDPOINTS[$endpoint])) {
            throw new InvalidArgumentException("Endpoint desconocido: {$endpoint}");
        }
        
        return self::SERVER_URL . self::ENDPOINTS[$endpoint];
    }
    
    /**
     * Validar si un estado es válido
     */
    public static function isValidStatus($status) {
        return in_array($status, self::VALID_STATUSES);
    }
    
    /**
     * Validar si un tipo de mensaje es válido
     */
    public static function isValidMessageType($type) {
        return in_array($type, self::MESSAGE_TYPES);
    }
    
    /**
     * Validar si una configuración de notificación es válida
     */
    public static function isValidNotificationSetting($setting) {
        return in_array($setting, self::NOTIFICATION_SETTINGS);
    }
    
    /**
     * Obtener plantilla de mensaje por defecto
     */
    public static function getDefaultTemplate($type) {
        return self::DEFAULT_TEMPLATES[$type] ?? 'Mensaje automático de {negocio}';
    }
    
    /**
     * Obtener código de país por código ISO
     */
    public static function getCountryCode($isoCode) {
        return self::COUNTRY_CODES[strtoupper($isoCode)] ?? null;
    }
    
    /**
     * Validar configuración básica
     */
    public static function validate() {
        $errors = [];
        
        // Verificar URLs
        if (!filter_var(self::SERVER_URL, FILTER_VALIDATE_URL)) {
            $errors[] = 'SERVER_URL no es una URL válida';
        }
        
        if (!filter_var(self::WEBAPP_URL, FILTER_VALIDATE_URL)) {
            $errors[] = 'WEBAPP_URL no es una URL válida';
        }
        
        // Verificar secrets
        if (strlen(self::JWT_SECRET) < 32) {
            $errors[] = 'JWT_SECRET debe tener al menos 32 caracteres';
        }
        
        if (strlen(self::WEBHOOK_SECRET) < 32) {
            $errors[] = 'WEBHOOK_SECRET debe tener al menos 32 caracteres';
        }
        
        // Verificar timeouts
        if (self::REQUEST_TIMEOUT <= 0) {
            $errors[] = 'REQUEST_TIMEOUT debe ser mayor que 0';
        }
        
        if (self::MAX_RETRY_ATTEMPTS <= 0) {
            $errors[] = 'MAX_RETRY_ATTEMPTS debe ser mayor que 0';
        }
        
        return $errors;
    }
}

// Validar configuración al cargar el archivo
$configErrors = WhatsAppConfig::validate();
if (!empty($configErrors)) {
    error_log('Errores de configuración WhatsApp: ' . implode(', ', $configErrors));
}

?>