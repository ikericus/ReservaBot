<?php
// api/admin/whatsapp-debug.php

header('Content-Type: application/json');

use ReservaBot\Utils\WhatsAppDebugHelper;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$serverUrl = $_ENV['WHATSAPP_SERVER_URL'] ?? 'http://localhost:3001';
$jwtSecret = $_ENV['JWT_SECRET'] ?? '';
$webhookSecret = $_ENV['WEBHOOK_SECRET'] ?? '';

try {
    switch ($action) {
        
        // Verificar salud del servidor
        case 'health':
            $health = WhatsAppDebugHelper::checkServerHealth($serverUrl);
            
            echo json_encode([
                'success' => true,
                'health' => $health
            ]);
            break;
        
        // Probar endpoint de la API
        case 'test-api':
            $userId = (int)($_POST['userId'] ?? 0);
            $endpoint = $_POST['endpoint'] ?? '';
            $method = $_POST['method'] ?? 'GET';
            $body = isset($_POST['body']) ? json_decode($_POST['body'], true) : null;
            
            if (!$userId) {
                throw new \InvalidArgumentException('userId requerido');
            }
            
            if (!$endpoint) {
                throw new \InvalidArgumentException('endpoint requerido');
            }
            
            // Generar token JWT
            $token = null;
            if ($endpoint !== '/health') {
                $token = WhatsAppDebugHelper::generateJWT($userId, $jwtSecret);
            }
            
            // Hacer llamada al servidor
            $result = WhatsAppDebugHelper::callWhatsAppServer(
                $serverUrl,
                $endpoint,
                $method,
                $body,
                $token
            );
            
            echo json_encode([
                'success' => true,
                'result' => $result
            ]);
            break;
        
        // Generar token JWT
        case 'generate-jwt':
            $userId = (int)($_POST['userId'] ?? 0);
            $expiry = (int)($_POST['expiry'] ?? 3600);
            
            if (!$userId) {
                throw new \InvalidArgumentException('userId requerido');
            }
            
            if (empty($jwtSecret)) {
                throw new \Exception('JWT_SECRET no configurado');
            }
            
            $token = WhatsAppDebugHelper::generateJWT($userId, $jwtSecret, $expiry);
            
            // Decodificar para mostrar payload
            $parts = explode('.', $token);
            $payload = json_decode(base64_decode($parts[1]), true);
            
            echo json_encode([
                'success' => true,
                'token' => $token,
                'payload' => $payload
            ]);
            break;
        
        // Simular webhook
        case 'simulate-webhook':
            $userId = (int)($_POST['userId'] ?? 0);
            $event = $_POST['event'] ?? '';
            $data = isset($_POST['data']) ? json_decode($_POST['data'], true) : [];
            
            if (!$userId) {
                throw new \InvalidArgumentException('userId requerido');
            }
            
            if (!$event) {
                throw new \InvalidArgumentException('event requerido');
            }
            
            if (empty($webhookSecret)) {
                throw new \Exception('WEBHOOK_SECRET no configurado');
            }
            
            // Construir payload del webhook
            $payload = [
                'userId' => $userId,
                'event' => $event,
                'data' => $data,
                'timestamp' => date('c')
            ];
            
            // Llamar al webhook endpoint
            $webhookUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
                        . '://' . $_SERVER['HTTP_HOST'] . '/api/whatsapp-webhook';
            
            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'X-Webhook-Secret: ' . $webhookSecret
                    ],
                    'content' => json_encode($payload),
                    'timeout' => 30,
                    'ignore_errors' => true
                ]
            ];
            
            $context = stream_context_create($options);
            $startTime = microtime(true);
            $result = @file_get_contents($webhookUrl, false, $context);
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            // Parsear status code
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0] ?? '', $matches);
            $statusCode = (int)($matches[1] ?? 0);
            
            $response = json_decode($result, true) ?? ['raw' => $result];
            
            echo json_encode([
                'success' => $statusCode >= 200 && $statusCode < 300,
                'statusCode' => $statusCode,
                'responseTime' => round($responseTime, 2),
                'response' => $response,
                'payload' => $payload
            ]);
            break;
        
        // Obtener datos de ejemplo para webhook
        case 'webhook-example':
            $event = $_GET['event'] ?? '';
            
            if (!$event) {
                throw new \InvalidArgumentException('event requerido');
            }
            
            $example = WhatsAppDebugHelper::getExampleWebhookData($event);
            
            echo json_encode([
                'success' => true,
                'data' => $example
            ]);
            break;
        
        default:
            throw new \InvalidArgumentException('Acción no válida');
    }
    
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (\Exception $e) {
    error_log("WhatsApp Debug API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno: ' . $e->getMessage()
    ]);
}