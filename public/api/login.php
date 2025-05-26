<?php
// ===== ARCHIVO: public/api/login.php =====
<?php
header('Content-Type: application/json');
require_once '../includes/db-config.php';
require_once '../includes/auth.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON o POST
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$remember = isset($input['remember']) && $input['remember'];

// Validaciones
if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email y contraseña son obligatorios'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'El formato del email no es válido'
    ]);
    exit;
}

// Intentar autenticar
$authResult = authenticateUser($email, $password);

if ($authResult['success']) {
    // Si marcó "recordar sesión", extender duración de cookie
    if ($remember) {
        $cookieLifetime = 30 * 24 * 60 * 60; // 30 días
        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), time() + $cookieLifetime,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Login exitoso',
        'user' => $authResult['user'],
        'redirect' => '/'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $authResult['message']
    ]);
}
