<?php

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

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

$nombre = trim($input['nombre'] ?? '');
$email = trim(strtolower($input['email'] ?? ''));
$telefono = trim($input['telefono'] ?? '');
$negocio = trim($input['negocio'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';
$plan = $input['plan'] ?? 'gratis';
$terminos = isset($input['terminos']) && $input['terminos'];

// Validaciones
$errors = [];

if (empty($nombre)) $errors[] = 'El nombre es obligatorio';
if (empty($email)) $errors[] = 'El email es obligatorio';
if (empty($telefono)) $errors[] = 'El teléfono es obligatorio';
if (empty($negocio)) $errors[] = 'El nombre del negocio es obligatorio';
if (empty($password)) $errors[] = 'La contraseña es obligatoria';
if (empty($confirmPassword)) $errors[] = 'La confirmación de contraseña es obligatoria';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El formato del email no es válido';
}

if (strlen($password) < 6) {
    $errors[] = 'La contraseña debe tener al menos 6 caracteres';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Las contraseñas no coinciden';
}

if (!$terminos) {
    $errors[] = 'Debes aceptar los términos y condiciones';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors),
        'errors' => $errors
    ]);
    exit;
}

try {
    // Verificar si el usuario ya existe
    $stmt = getPDO()->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe una cuenta con este email'
        ]);
        exit;
    }
    
    // Crear nuevo usuario
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $apiKey = bin2hex(random_bytes(32));
    
    $stmt = getPDO()->prepare("
        INSERT INTO usuarios (nombre, email, telefono, negocio, password_hash, plan, api_key, created_at, activo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 1)
    ");
    
    $stmt->execute([
        $nombre,
        $email,
        $telefono,
        $negocio,
        $passwordHash,
        $plan,
        $apiKey
    ]);
    
    $userId = getPDO()->lastInsertId();
    
    // Crear configuraciones iniciales para el usuario
    $configuracionesIniciales = [
        'app_name' => $negocio,
        'empresa_nombre' => $negocio,
        'empresa_telefono' => $telefono,
        'modo_aceptacion' => 'manual',
        'intervalo_reservas' => '30',
        'horario_lun' => 'true|[{"inicio":"09:00","fin":"18:00"}]',
        'horario_mar' => 'true|[{"inicio":"09:00","fin":"18:00"}]',
        'horario_mie' => 'true|[{"inicio":"09:00","fin":"18:00"}]',
        'horario_jue' => 'true|[{"inicio":"09:00","fin":"18:00"}]',
        'horario_vie' => 'true|[{"inicio":"09:00","fin":"18:00"}]',
        'horario_sab' => 'true|[{"inicio":"10:00","fin":"14:00"}]',
        'horario_dom' => 'false|[]'
    ];
    
    foreach ($configuracionesIniciales as $clave => $valor) {
        $stmt = getPDO()->prepare("
            INSERT INTO configuraciones_usuario (usuario_id, clave, valor) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $clave, $valor]);
    }
    
    // Log del registro
    error_log("Nuevo usuario registrado: $email (ID: $userId)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Cuenta creada exitosamente',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    error_log("Error en registro: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}