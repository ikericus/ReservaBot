<?php
// test-whatsapp-connection.php

$whatsappServerUrl = 'http://37.59.109.167:3001';

echo "<h2>Test de conexión WhatsApp Server</h2>";

// Test 1: Health check
echo "<h3>1. Health Check:</h3>";
$healthResponse = file_get_contents($whatsappServerUrl . '/health');
if ($healthResponse) {
    echo "✅ Conexión exitosa<br>";
    echo "<pre>" . $healthResponse . "</pre>";
} else {
    echo "❌ No se pudo conectar<br>";
}

// Test 2: Crear token JWT (necesario para autenticación)
echo "<h3>2. Token JWT:</h3>";
$jwtSecret = 'da3c7b9e13a38a0ea3dcbaaed1ec9ec1f0005f974adad7141b71a36e9f13e187'; // El mismo del .env

$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
$payload = json_encode(['userId' => 1, 'iat' => time(), 'exp' => time() + 3600]);

$base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
$base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

$signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $jwtSecret, true);
$base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

$jwt = $base64Header . "." . $base64Payload . "." . $base64Signature;

echo "Token generado: " . substr($jwt, 0, 50) . "...<br>";

// Test 3: Probar endpoint con autenticación
echo "<h3>3. Test API con autenticación:</h3>";
$statusUrl = $whatsappServerUrl . '/api/status';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer " . $jwt . "\r\n" .
                   "Content-Type: application/json\r\n"
    ]
]);

$statusResponse = file_get_contents($statusUrl, false, $context);
if ($statusResponse) {
    echo "✅ API autenticada funciona<br>";
    echo "<pre>" . $statusResponse . "</pre>";
} else {
    echo "❌ Error en autenticación API<br>";
}
?>