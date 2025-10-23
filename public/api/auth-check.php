<?php
/*

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar si está autenticado
if (!isAuthenticated()) {
    echo json_encode([
        'authenticated' => false,
        'message' => 'No autenticado'
    ]);
    exit;
}

// Verificar si la sesión ha expirado
if (isSessionExpired()) {
    logout();
    echo json_encode([
        'authenticated' => false,
        'expired' => true,
        'message' => 'Sesión expirada'
    ]);
    exit;
}

// Actualizar actividad y devolver info
updateLastActivity();

echo json_encode([
    'authenticated' => true,
    'user' => getAuthenticatedUser(),
    'session_info' => getSessionInfo(),
    'csrf_token' => generateCSRFToken()
]);

*/
