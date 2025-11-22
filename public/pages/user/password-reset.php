<?php
/**
 * Página para restablecer contraseña
 */

$token = $_GET['token'] ?? '';
$errors = [];
$success = '';
$tokenValid = false;
$user = null;

// Verificar token si se proporciona
if (!empty($token)) {
    try {
        // En modo desarrollo, permitir tokens de prueba
        $isTestToken = strpos($token, 'test') !== false;
        
        if (isDevelopment() && $isTestToken) {
            // Token de prueba en desarrollo
            $tokenValid = true;
            $user = [
                'id' => 1,
                'nombre' => 'Usuario de Prueba',
                'email' => 'demo@dev.reservabot.es'
            ];
            debug_log("Usando token de prueba en desarrollo: $token");
        } else {
            // Validación normal de token
            $usuarioDomain = getContainer()->getUsuarioDomain();
            $user = $usuarioDomain->validarTokenRestablecimiento($token);
            
            if ($user) {
                $tokenValid = true;
            } else {
                $errors[] = 'Token de restablecimiento inválido o expirado.';
            }
        }
    } catch (\DomainException $e) {
        $errors[] = $e->getMessage();
    } catch (\Exception $e) {
        error_log('Error al verificar token: ' . $e->getMessage());
        $errors[] = 'Error al verificar el token.';
    }
}

// Procesar formulario de nueva contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $errors[] = 'La contraseña es obligatoria';
    }
    
    if (empty($confirmPassword)) {
        $errors[] = 'La confirmación de contraseña es obligatoria';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Las contraseñas no coinciden';
    }
    
    if (empty($errors)) {
        try {            
            if (isDevelopment()) {
                // Simulación exitosa en desarrollo
                debug_log("Simulando restablecimiento exitoso para token de prueba");
                $_SESSION['login_message'] = 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión.';
                header('Location: /login');
                exit;
            } else {
                // Proceso real de restablecimiento
                $usuarioDomain = getContainer()->getUsuarioDomain();
                $usuarioDomain->restablecerContrasena($token, $password);
                
                $_SESSION['login_message'] = 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión.';
                header('Location: /login');
                exit;
            }
            
        } catch (\DomainException $e) {
            $errors[] = $e->getMessage();
        } catch (\Exception $e) {
            error_log("Error restableciendo contraseña: " . $e->getMessage());
            $errors[] = 'Error al restablecer la contraseña. Intenta nuevamente.';
        }
    }
}

// Procesar solicitud de restablecimiento (enviar email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($token)) {
    $email = trim(strtolower($_POST['email'] ?? ''));
    
    if (empty($email)) {
        $errors[] = 'El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del email no es válido';
    }
    
    if (empty($errors)) {
        try {
            $usuarioDomain = getContainer()->getUsuarioDomain();
            $enviado = $usuarioDomain->solicitarRestablecimientoContrasena($email);
            
            // Por seguridad, siempre mostramos el mismo mensaje
            $success = 'Si existe una cuenta con este email, recibirás un enlace de restablecimiento en breve.';
            
            if ($enviado) {
                debug_log("Email de restablecimiento enviado a: $email");
            }
            
        } catch (\Exception $e) {
            error_log("Error en solicitud de restablecimiento: " . $e->getMessage());
            $errors[] = 'Error interno. Intenta nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tokenValid ? 'Nueva Contraseña' : 'Restablecer Contraseña'; ?> - ReservaBot</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Iconos -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
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
        
        .input-focus:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }
        
        .btn-shine {
            position: relative;
            overflow: hidden;
        }
        
        .btn-shine::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-shine:hover::before {
            left: 100%;
        }
        
        .strength-meter {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background-color: #ef4444; width: 25%; }
        .strength-fair { background-color: #f59e0b; width: 50%; }
        .strength-good { background-color: #10b981; width: 75%; }
        .strength-strong { background-color: #059669; width: 100%; }
    </style>
</head>
<body class="min-h-screen gradient-bg relative overflow-hidden">
    
    <!-- Animated Background Elements -->
    <div class="absolute inset-0">
        <div class="absolute top-20 left-20 w-64 h-64 bg-white/10 rounded-full floating"></div>
        <div class="absolute top-60 right-32 w-48 h-48 bg-white/5 rounded-full floating" style="animation-delay: -1s;"></div>
        <div class="absolute bottom-32 left-1/3 w-72 h-72 bg-white/10 rounded-full floating" style="animation-delay: -2s;"></div>
        <div class="absolute bottom-20 right-20 w-56 h-56 bg-white/5 rounded-full floating" style="animation-delay: -0.5s;"></div>
    </div>
    
    <div class="relative min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full">
            
            <!-- Logo y título -->
            <div class="text-center mb-8">
                <div class="flex items-center justify-center mb-6">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="ri-lock-unlock-line text-purple-600 text-2xl"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">
                    <?php echo $tokenValid ? 'Nueva Contraseña' : 'Restablecer Contraseña'; ?>
                </h1>
                <p class="text-blue-100">
                    <?php echo $tokenValid ? 'Ingresa tu nueva contraseña segura' : 'Te ayudamos a recuperar tu cuenta'; ?>
                </p>
            </div>
            
            <!-- Formulario -->
            <div class="glass-effect rounded-2xl p-8 shadow-2xl">
                
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="ri-error-warning-line text-red-400 mr-2 mt-0.5"></i>
                            <div>
                                <?php foreach ($errors as $error): ?>
                                    <p class="text-red-800 text-sm"><?php echo htmlspecialchars($error); ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="ri-check-line text-green-400 mr-2 mt-0.5"></i>
                            <div>
                                <p class="text-green-800 text-sm font-medium mb-1"><?php echo htmlspecialchars($success); ?></p>
                                <p class="text-green-700 text-xs">Revisa tu bandeja de entrada y la carpeta de spam.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($tokenValid): ?>
                    <!-- Formulario para nueva contraseña -->
                    <form method="POST" class="space-y-6" id="resetForm">
                        
                        <!-- Info del usuario -->
                        <?php if ($user): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <i class="ri-user-line text-blue-400 mr-2"></i>
                                    <div>
                                        <p class="text-blue-800 text-sm font-medium"><?php echo htmlspecialchars($user['nombre']); ?></p>
                                        <p class="text-blue-600 text-xs"><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Nueva contraseña -->
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
                                    class="input-focus block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300"
                                    placeholder="Mínimo 6 caracteres"
                                >
                                <button
                                    type="button"
                                    id="togglePassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                >
                                    <i class="ri-eye-off-line text-gray-400 hover:text-gray-600" id="eyeIcon"></i>
                                </button>
                            </div>
                            <!-- Indicador de fortaleza -->
                            <div class="mt-2">
                                <div class="h-1 bg-gray-200 rounded-full">
                                    <div id="strengthMeter" class="strength-meter bg-gray-200"></div>
                                </div>
                                <p id="strengthText" class="text-xs text-gray-500 mt-1">Ingresa una contraseña</p>
                            </div>
                        </div>
                        
                        <!-- Confirmar contraseña -->
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
                                    class="input-focus block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300"
                                    placeholder="Repite tu contraseña"
                                >
                                <div id="passwordMatch" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i class="text-gray-400 hidden" id="matchIcon"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botón de guardar -->
                        <button
                            type="submit"
                            class="btn-shine w-full gradient-bg text-white py-3 px-4 rounded-xl font-semibold text-lg hover:shadow-lg transition-all duration-300"
                            id="submitBtn"
                        >
                            <i class="ri-save-line mr-2"></i>
                            Guardar Nueva Contraseña
                        </button>
                    </form>
                    
                <?php elseif (empty($success)): ?>
                    <!-- Formulario para solicitar restablecimiento -->
                    <form method="POST" class="space-y-6" id="requestForm">
                        
                        <!-- Instrucciones -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-blue-800 text-sm">
                                <i class="ri-information-line mr-1"></i>
                                Ingresa tu email y te enviaremos un enlace para restablecer tu contraseña.
                            </p>
                        </div>
                        
                        <!-- Email -->
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
                                    class="input-focus block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300"
                                    placeholder="tu@email.com"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                >
                            </div>
                        </div>
                        
                        <!-- Botón de enviar -->
                        <button
                            type="submit"
                            class="btn-shine w-full gradient-bg text-white py-3 px-4 rounded-xl font-semibold text-lg hover:shadow-lg transition-all duration-300"
                            id="requestBtn"
                        >
                            <i class="ri-mail-send-line mr-2"></i>
                            Enviar Enlace de Restablecimiento
                        </button>
                    </form>
                <?php endif; ?>
                
                <!-- Enlaces -->
                <div class="mt-6 text-center space-y-2">
                    <p class="text-gray-600 text-sm">
                        <a href="/login" class="text-purple-600 hover:text-purple-500 font-medium transition-colors">
                            <i class="ri-arrow-left-line mr-1"></i>
                            Volver al login
                        </a>
                    </p>
                    <?php if (!$tokenValid && empty($success)): ?>
                        <p class="text-gray-600 text-sm">
                            ¿No tienes cuenta?
                            <a href="/signup" class="text-purple-600 hover:text-purple-500 font-medium transition-colors">
                                Regístrate aquí
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
                
            </div>
            
        </div>
    </div>
    
    <script>
        <?php if ($tokenValid): ?>
        // Toggle password visibility
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('ri-eye-off-line');
                eyeIcon.classList.add('ri-eye-line');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('ri-eye-line');
                eyeIcon.classList.add('ri-eye-off-line');
            }
        });
        
        // Password strength checker
        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthMeter = document.getElementById('strengthMeter');
            const strengthText = document.getElementById('strengthText');
            
            strengthMeter.className = 'strength-meter';
            
            if (password.length === 0) {
                strengthText.textContent = 'Ingresa una contraseña';
                strengthText.className = 'text-xs text-gray-500 mt-1';
                return;
            }
            
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                strengthMeter.classList.add('strength-weak');
                strengthText.textContent = 'Contraseña débil';
                strengthText.className = 'text-xs text-red-500 mt-1';
            } else if (strength <= 3) {
                strengthMeter.classList.add('strength-fair');
                strengthText.textContent = 'Contraseña regular';
                strengthText.className = 'text-xs text-orange-500 mt-1';
            } else if (strength <= 4) {
                strengthMeter.classList.add('strength-good');
                strengthText.textContent = 'Contraseña buena';
                strengthText.className = 'text-xs text-green-600 mt-1';
            } else {
                strengthMeter.classList.add('strength-strong');
                strengthText.textContent = 'Contraseña excelente';
                strengthText.className = 'text-xs text-green-600 mt-1';
            }
        });
        
        // Password confirmation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchIcon = document.getElementById('matchIcon');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    matchIcon.classList.remove('hidden', 'ri-close-line', 'text-red-500');
                    matchIcon.classList.add('ri-check-line', 'text-green-500');
                    this.classList.remove('border-red-300');
                    this.classList.add('border-green-300');
                } else {
                    matchIcon.classList.remove('hidden', 'ri-check-line', 'text-green-500');
                    matchIcon.classList.add('ri-close-line', 'text-red-500');
                    this.classList.remove('border-green-300');
                    this.classList.add('border-red-300');
                }
            } else {
                matchIcon.classList.add('hidden');
                this.classList.remove('border-red-300', 'border-green-300');
            }
        });
        
        // Form submission
        document.getElementById('resetForm')?.addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="ri-loader-line animate-spin mr-2"></i>Guardando...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                if (window.location.pathname.includes('password-reset')) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 3000);
        });
        
        // Auto-focus
        document.getElementById('password')?.focus();
        <?php else: ?>
        // Form submission para solicitud
        document.getElementById('requestForm')?.addEventListener('submit', function() {
            const requestBtn = document.getElementById('requestBtn');
            const originalText = requestBtn.innerHTML;
            
            requestBtn.innerHTML = '<i class="ri-loader-line animate-spin mr-2"></i>Enviando...';
            requestBtn.disabled = true;
            
            setTimeout(() => {
                if (window.location.pathname.includes('password-reset')) {
                    requestBtn.innerHTML = originalText;
                    requestBtn.disabled = false;
                }
            }, 3000);
        });
        
        // Auto-focus
        document.getElementById('email')?.focus();
        
        // Validación en tiempo real del email
        document.getElementById('email')?.addEventListener('blur', function() {
            const email = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.classList.add('border-red-300', 'focus:ring-red-500');
                this.classList.remove('border-gray-300', 'focus:ring-purple-500');
            } else {
                this.classList.remove('border-red-300', 'focus:ring-red-500');
                this.classList.add('border-gray-300', 'focus:ring-purple-500');
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>