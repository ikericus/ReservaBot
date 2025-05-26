<?php
/**
 * Verificación de email
 */

require_once 'includes/db-config.php';

$token = $_GET['token'] ?? '';
$message = '';
$success = false;

if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, email, nombre FROM usuarios 
            WHERE verificacion_token = ? AND email_verificado = 0
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Verificar email
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET email_verificado = 1, verificacion_token = NULL, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            $message = '¡Email verificado exitosamente! Ya puedes usar todas las funciones de ReservaBot.';
            $success = true;
            
            error_log("Email verificado para usuario: {$user['email']} (ID: {$user['id']})");
        } else {
            $message = 'Token de verificación inválido o ya utilizado.';
        }
    } catch (Exception $e) {
        error_log("Error en verificación de email: " . $e->getMessage());
        $message = 'Error al verificar el email. Por favor, intenta nuevamente.';
    }
} else {
    $message = 'Token de verificación no proporcionado.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Email - ReservaBot</title>
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
        <div class="glass-effect rounded-2xl p-8 shadow-2xl text-center">
            <div class="w-16 h-16 mx-auto mb-4 <?php echo $success ? 'bg-green-100' : 'bg-red-100'; ?> rounded-full flex items-center justify-center">
                <i class="<?php echo $success ? 'ri-check-line text-green-600' : 'ri-error-warning-line text-red-600'; ?> text-2xl"></i>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-900 mb-4">
                <?php echo $success ? 'Email Verificado' : 'Error de Verificación'; ?>
            </h1>
            
            <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($message); ?></p>
            
            <div class="space-y-3">
                <?php if ($success): ?>
                    <a href="/login.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all font-semibold">
                        <i class="ri-login-circle-line mr-2"></i>
                        Iniciar Sesión
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-semibold">
                        <i class="ri-arrow-left-line mr-2"></i>
                        Volver al Login
                    </a>
                <?php endif; ?>
                
                <div>
                    <a href="/signup.php" class="text-purple-600 hover:text-purple-500 text-sm font-medium">
                        ¿No tienes cuenta? Regístrate aquí
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
