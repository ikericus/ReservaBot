<?php
// infrastructure/WhatsAppServerManager.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\WhatsApp\IWhatsAppServerManager;

class WhatsAppServerManager implements IWhatsAppServerManager {
    private string $serverUrl;
    private string $jwtSecret;
    
    public function __construct(string $serverUrl, string $jwtSecret) {
        $this->serverUrl = $serverUrl;
        $this->jwtSecret = $jwtSecret;
    }
    
    /**
     * Conecta con el servidor WhatsApp
     * 
     * @param int $usuarioId ID del usuario
     * @param string|null $phoneNumber Número de teléfono a conectar (opcional)
     * @return array Respuesta con estado de conexión
     */
    public function conectar(int $usuarioId, ?string $phoneNumber = null): array {
        // Verificar estado actual
        $statusResponse = $this->llamarAPI('/api/status', 'GET', null, $usuarioId);
        
        if ($statusResponse['success'] && $statusResponse['status'] === 'ready') {
            return [
                'success' => true,
                'status' => 'ready',
                'phoneNumber' => $statusResponse['info']['phoneNumber'] ?? null
            ];
        }
        
        if ($statusResponse['status'] === 'waiting_qr' && !empty($statusResponse['qr'])) {
            return [
                'success' => true,
                'status' => 'waiting_qr',
                'qr' => $statusResponse['qr']
            ];
        }
        
        // Preparar datos para iniciar nueva conexión
        $requestData = ['userId' => $usuarioId];
        
        // Agregar el número de teléfono si está disponible
        if ($phoneNumber) {
            $requestData['phoneNumber'] = $this->formatearTelefono($phoneNumber);
        }
        
        // Iniciar nueva conexión
        $connectResponse = $this->llamarAPI('/api/connect', 'POST', $requestData, $usuarioId);
        
        if (!$connectResponse['success']) {
            throw new \RuntimeException($connectResponse['error'] ?? 'Error conectando al servidor WhatsApp');
        }
        
        return [
            'success' => true,
            'status' => 'connecting',
            'phoneNumber' => $phoneNumber // Retornar el número que se intenta conectar
        ];
    }
    
    public function desconectar(int $usuarioId): array {
        try {
            $response = $this->llamarAPI('/api/disconnect', 'POST', ['userId' => $usuarioId], $usuarioId);
            
            return [
                'success' => true,
                'message' => 'Desconectado del servidor'
            ];
        } catch (\Exception $e) {
            // No lanzar excepción si falla el servidor
            return [
                'success' => true,
                'warning' => 'Error en servidor remoto: ' . $e->getMessage()
            ];
        }
    }
    
    public function obtenerEstado(int $usuarioId): array {
        $response = $this->llamarAPI('/api/status', 'GET', null, $usuarioId);
        
        $status = $response['status'] ?? 'disconnected';
        
        // Unificar 'ready' a 'connected'
        if ($status === 'ready') {
            $status = 'connected';
        }
        
        return [
            'success' => true,
            'status' => $status,
            'qr' => $response['qr'] ?? null,
            'info' => $response['info'] ?? null,
            'phoneNumber' => $response['info']['me']['user'] ?? null
        ];
    }
    
    public function enviarMensaje(int $usuarioId, string $telefono, string $mensaje, ?array $media = null): array {
        $data = [
            'to' => $this->formatearTelefono($telefono),
            'message' => $mensaje
        ];
        
        if ($media) {
            $data['media'] = $media;
        }
        
        $response = $this->llamarAPI('/api/send', 'POST', $data, $usuarioId);
        
        if (!$response['success']) {
            throw new \RuntimeException($response['error'] ?? 'Error enviando mensaje');
        }
        
        return [
            'success' => true,
            'messageId' => $response['messageId'] ?? null,
            'timestamp' => $response['timestamp'] ?? time()
        ];
    }
    
    public function estaDisponible(): bool {
        return $this->obtenerJson('/health') !== null;
    }

    public function verificarSalud(): ?array {
        return $this->obtenerJson('/health');
    }
    
    /**
     * Método privado que obtiene la respuesta de un endpoint en formato array
     */
    private function obtenerJson(string $endpoint, int $timeout = 5): ?array {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => $timeout,
                    'ignore_errors' => true
                ]
            ]);

            $result = @file_get_contents($this->serverUrl . $endpoint, false, $context);

            if ($result === false) {
                return null;
            }

            $data = json_decode($result, true);
            return json_last_error() === JSON_ERROR_NONE ? $data : null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Llama al API del servidor Node.js
     */
    private function llamarAPI(string $endpoint, string $method, ?array $data, int $usuarioId): array {
        $url = $this->serverUrl . $endpoint;
        $token = $this->generarJWT($usuarioId);
         
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];
        
        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $data ? json_encode($data) : null,
                'timeout' => 30,
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            throw new \RuntimeException('No se pudo conectar con el servidor WhatsApp');
        }
        
        $response = json_decode($result, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Respuesta inválida del servidor WhatsApp');
        }

        return $response;
    }


    /**
     * Genera JWT para autenticación
     */
    private function generarJWT(int $usuarioId): string {
        $header = $this->base64url_encode(json_encode(['typ' => 'JWT','alg' => 'HS256']));
        $payload = $this->base64url_encode(json_encode([
            'userId' => $usuarioId,
            'iat' => time(),
            'exp' => time() + 3600
        ]));

        $signature = hash_hmac('sha256', "$header.$payload", $this->jwtSecret, true);
        $signature = $this->base64url_encode($signature);

        return "$header.$payload.$signature";
    }    

    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Formatea número de teléfono para WhatsApp
     * 
     * @param string $telefono Número de teléfono a formatear
     * @return string Número formateado
     */
    private function formatearTelefono(string $telefono): string {
        // Eliminar todos los caracteres no numéricos
        $clean = preg_replace('/[^\d]/', '', $telefono);
        
        // Si empieza con 00, remover
        if (substr($clean, 0, 2) === '00') {
            $clean = substr($clean, 2);
        }
        
        // Si es español sin código de país, agregar 34
        if (strlen($clean) === 9 && in_array(substr($clean, 0, 1), ['6', '7', '9'])) {
            $clean = '34' . $clean;
        }
        
        return $clean;
    }
}