<?php
// utils/WhatsAppDebugHelper.php

namespace ReservaBot\Utils;

/**
 * Utilidades para debugging de WhatsApp
 * Solo métodos esenciales, sin logging temporal
 */
class WhatsAppDebugHelper {
    
    /**
     * Genera un token JWT para el servidor Node.js
     */
    public static function generateJWT(int $userId, string $secret, int $expirySeconds = 3600): string {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        
        $payload = base64_encode(json_encode([
            'userId' => $userId,
            'iat' => time(),
            'exp' => time() + $expirySeconds
        ]));
        
        // Generar signature HMAC SHA256
        $signature = hash_hmac('sha256', "$header.$payload", $secret, true);
        $signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Hace una llamada HTTP al servidor Node.js
     */
    public static function callWhatsAppServer(
        string $serverUrl,
        string $endpoint,
        string $method = 'GET',
        ?array $body = null,
        ?string $token = null
    ): array {
        $url = rtrim($serverUrl, '/') . '/' . ltrim($endpoint, '/');
        
        $headers = ['Content-Type: application/json'];
        
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        
        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'timeout' => 30,
                'ignore_errors' => true
            ]
        ];
        
        if ($body && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $options['http']['content'] = json_encode($body);
        }
        
        $context = stream_context_create($options);
        
        $startTime = microtime(true);
        $result = @file_get_contents($url, false, $context);
        $responseTime = (microtime(true) - $startTime) * 1000;
        
        // Parsear status code
        $statusLine = $http_response_header[0] ?? '';
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches);
        $statusCode = (int)($matches[1] ?? 0);
        
        $response = [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'statusCode' => $statusCode,
            'responseTime' => round($responseTime, 2),
            'body' => null,
            'error' => null
        ];
        
        if ($result === false) {
            $response['error'] = 'No se pudo conectar con el servidor';
            error_log("WhatsApp API Error: No connection to $url");
            return $response;
        }
        
        $decoded = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $response['body'] = $decoded;
        } else {
            $response['body'] = $result;
            $response['error'] = 'Respuesta no es JSON válido';
            error_log("WhatsApp API Error: Invalid JSON from $url");
        }
        
        return $response;
    }
    
    /**
     * Verifica el health del servidor
     */
    public static function checkServerHealth(string $serverUrl): array {
        $result = self::callWhatsAppServer($serverUrl, '/health', 'GET');
        
        return [
            'online' => $result['success'],
            'responseTime' => $result['responseTime'],
            'data' => $result['body'],
            'error' => $result['error']
        ];
    }
    
    /**
     * Formatea un número de teléfono
     */
    public static function formatPhoneNumber(string $phone): string {
        $clean = preg_replace('/[^\d]/', '', $phone);
        
        if (substr($clean, 0, 2) === '00') {
            $clean = substr($clean, 2);
        }
        
        // Si es español sin código de país, agregar 34
        if (strlen($clean) === 9 && in_array(substr($clean, 0, 1), ['6', '7', '9'])) {
            $clean = '34' . $clean;
        }
        
        return $clean;
    }
    
    /**
     * Valida un número de teléfono
     */
    public static function validatePhoneNumber(string $phone): bool {
        $clean = preg_replace('/[^\d]/', '', $phone);
        return strlen($clean) >= 8 && strlen($clean) <= 15;
    }
    
    /**
     * Datos de ejemplo para diferentes tipos de eventos de webhook
     */
    public static function getExampleWebhookData(string $event): array {
        $examples = [
            'qr_generated' => [
                'qr' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
            ],
            'connected' => [
                'phoneNumber' => '34612345678',
                'pushname' => 'Usuario Test'
            ],
            'disconnected' => [
                'reason' => 'logout'
            ],
            'auth_failure' => [
                'error' => 'Authentication failed'
            ],
            'message_received' => [
                'from' => '34612345678@c.us',
                'body' => 'Mensaje de prueba',
                'timestamp' => time()
            ],
            'message_sent' => [
                'to' => '34612345678@c.us',
                'messageId' => 'test_' . uniqid()
            ]
        ];
        
        return $examples[$event] ?? [];
    }
    
    /**
     * Compara estados de WhatsApp entre BD local y servidor
     */
    public static function compareStates(array $localState, array $serverState): array {
        $differences = [];
        
        $fieldsToCompare = ['status', 'phoneNumber'];
        
        foreach ($fieldsToCompare as $field) {
            $localValue = $localState[$field] ?? null;
            $serverValue = $serverState[$field] ?? null;
            
            if ($localValue !== $serverValue) {
                $differences[] = [
                    'field' => $field,
                    'local' => $localValue,
                    'server' => $serverValue
                ];
            }
        }
        
        return $differences;
    }
}