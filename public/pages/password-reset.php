<?php
/**
 * Página para restablecer contraseña
 */

require_once dirname(__DIR__) . '/includes/db-config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$tokenValid = false;

// Verificar token si se proporciona
if (!empty($token)) {
    try {
        $stmt = getPDO()->prepare("
            SELECT id, email, nombre FROM usuarios 
            WHERE reset_token = ? AND reset_token_expiry > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $tokenValid = true;
        } else {
            $error = 'Token de restablecimiento inválido o expirado.';
        }
    } catch (Exception $e) {
        $error = 'Error al verificar el token.';
    }
}

// Procesar formulario de nueva contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Ambos campos son obligatorios';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } else {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = getPDO()->prepare("
                UPDATE usuarios 
                SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$passwordHash, $user['id']]);
            
            $success = 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión.';
            $tokenValid = false; // Evitar mostrar el formulario nuevamente
            
            error_log("Contraseña restablecida para usuario: {$user['email']} (ID: {$user['id']})");
        } catch (Exception $e) {
            error_log("Error restableciendo contraseña: " . $e->getMessage());
            $error = 'Error al restablecer la contraseña. Intenta nuevamente.';
        }
    }
}

// Procesar solicitud de restablecimiento (enviar email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($token)) {
    $email = trim(strtolower($_POST['email'] ?? ''));
    
    if (empty($email)) {
        $error = 'El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del email no es válido';
    } else {
        try {
            $stmt = getPDO()->prepare("SELECT id, nombre FROM usuarios WHERE email = ? AND activo = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generar token de restablecimiento
                $resetToken = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $stmt = getPDO()->prepare("
                    UPDATE usuarios 
                    SET reset_token = ?, reset_token_expiry = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$resetToken, $expiry, $user['id']]);
                
                // Aquí enviarías el email con el token
                sendPasswordResetEmail($email, $resetToken);
                
                $success = 'Se ha enviado un enlace de restablecimiento a tu email.';
                error_log("Solicitud de restablecimiento de contraseña para: $email");
            } else {
                // Por seguridad, mostrar el mismo mensaje aunque el email no exista
                $success = 'Se ha enviado un enlace de restablecimiento a tu email.';
            }
        } catch (Exception $e) {
            error_log("Error en solicitud de restablecimiento: " . $e->getMessage());
            $error = 'Error interno. Intenta nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - ReservaBot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center px-4">
    
    <!-- Animated Background Elements -->
    <div class="absolute inset-0">
        <div class="absolute top-20 left-20 w-64 h-64 bg-white/10 rounded-full floating"></div>
        <div class="absolute top-60 right-32 w-48 h-48 bg-white/5 rounded-full floating" style="animation-delay: -1s;"></div>
        <div class="absolute bottom-32 left-1/3 w-72 h-72 bg-white/10 rounded-full floating" style="animation-delay: -2s;"></div>
        <div class="absolute bottom-20 right-20 w-56 h-56 bg-white/5 rounded-full floating" style="animation-delay: -0.5s;"></div>
    </div>
    
    <div class="relative max-w-md w-full">
        <!-- Logo y título -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="ri-lock-unlock-line text-purple-600 text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">
                <?php echo $tokenValid ? 'Nueva Contraseña' : 'Restablecer Contraseña'; ?>
            </h1>
            <p class="text-blue-100">
                <?php echo $tokenValid ? 'Ingresa tu nueva contraseña' : 'Te ayudamos a recuperar tu cuenta'; ?>
            </p>
        </div>
        
        <!-- Formulario -->
        <div class="glass-effect rounded-2xl p-8 shadow-2xl">
            <?php if (!empty($error)): ?>
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="ri-error-warning-line text-red-400 mr-2"></i>
                        <span class="text-red-800 text-sm"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="ri-check-line text-green-400 mr-2"></i>
                        <span class="text-green-800 text-sm"><?php echo htmlspecialchars($success); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($tokenValid): ?>
                <!-- Formulario para nueva contraseña -->
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Nueva contraseña
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ri-lock-line text-gray-400"></i>
                            </div>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="Mínimo 6 caracteres"
                            >
                        </div>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirmar contraseña
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ri-lock-2-line text-gray-400"></i>
                            </div>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                required
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="Repite tu contraseña"
                            >
                        </div>
                    </div>
                    
                    <button
                        type="submit"
                        class="w-full gradient-bg text-white py-3 px-4 rounded-xl font-semibold hover:shadow-lg transition-all duration-300"
                    >
                        <i class="ri-save-line mr-2"></i>
                        Guardar Nueva Contraseña
                    </button>
                </form>
                
            <?php elseif (empty($success)): ?>
                <!-- Formulario para solicitar restablecimiento -->
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email de tu cuenta
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ri-mail-line text-gray-400"></i>
                            </div>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                required
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="tu@email.com"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            >
                        </div>
                    </div>
                    
                    <button
                        type="submit"
                        class="w-full gradient-bg text-white py-3 px-4 rounded-xl font-semibold hover:shadow-lg transition-all duration-300"
                    >
                        <i class="ri-mail-send-line mr-2"></i>
                        Enviar Enlace de Restablecimiento
                    </button>
                </form>
            <?php endif; ?>
            
            <!-- Enlaces -->
            <div class="mt-6 text-center space-y-2">
                <p class="text-gray-600 text-sm">
                    <a href="/login" class="text-purple-600 hover:text-purple-500 font-medium">
                        ← Volver al login
                    </a>
                </p>
                <?php if (!$tokenValid && empty($success)): ?>
                    <p class="text-gray-600 text-sm">
                        ¿No tienes cuenta?
                        <a href="/signup" class="text-purple-600 hover:text-purple-500 font-medium">
                            Regístrate aquí
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
