<?php
// Procesar login si se envía el formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones básicas
    if (empty($email) || empty($password)) {
        $error = 'Por favor completa todos los campos';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del email no es válido';
    } else {
        // Aquí iría la validación real con base de datos
        // Por ahora, credenciales de ejemplo
        if ($email === 'admin@reservabot.com' && $password === 'demo123') {
            // Login exitoso - redirigir al dashboard
            session_start();
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_email'] = $email;
            header('Location: /');
            exit;
        } else {
            $error = 'Credenciales incorrectas';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - ReservaBot</title>
    
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
                        <i class="ri-calendar-line text-purple-600 text-2xl"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Bienvenido a ReservaBot</h1>
                <p class="text-blue-100">Inicia sesión para gestionar tus reservas</p>
            </div>
            
            <!-- Formulario de login -->
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
                
                <form method="POST" class="space-y-6">
                    
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
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
                    
                    <!-- Contraseña -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Contraseña
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
                                placeholder="Tu contraseña"
                            >
                            <button
                                type="button"
                                id="togglePassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center"
                            >
                                <i class="ri-eye-off-line text-gray-400 hover:text-gray-600" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Recordar sesión -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input
                                id="remember"
                                name="remember"
                                type="checkbox"
                                class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                            >
                            <label for="remember" class="ml-2 block text-sm text-gray-700">
                                Recordar sesión
                            </label>
                        </div>
                        
                        <div class="text-sm">
                            <a href="#" class="text-purple-600 hover:text-purple-500 font-medium">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                    </div>
                    
                    <!-- Botón de login -->
                    <button
                        type="submit"
                        class="btn-shine w-full gradient-bg text-white py-3 px-4 rounded-xl font-semibold text-lg hover:shadow-lg transition-all duration-300"
                    >
                        <i class="ri-login-circle-line mr-2"></i>
                        Iniciar Sesión
                    </button>
                    
                </form>
                
                <!-- Demo info -->
                <div class="mt-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
                    <p class="text-blue-800 text-sm text-center">
                        <i class="ri-information-line mr-1"></i>
                        <strong>Demo:</strong> admin@reservabot.com / demo123
                    </p>
                </div>
                
                <!-- Enlaces adicionales -->
                <div class="mt-6 text-center space-y-2">
                    <p class="text-gray-600 text-sm">
                        ¿No tienes cuenta?
                        <a href="/signup" class="text-purple-600 hover:text-purple-500 font-medium">
                            Regístrate aquí
                        </a>
                    </p>
                    <p class="text-gray-600 text-sm">
                        <a href="/landing" class="text-gray-500 hover:text-gray-700">
                            ← Volver al inicio
                        </a>
                    </p>
                </div>
                
            </div>
            
            <!-- Features destacadas -->
            <div class="mt-8 grid grid-cols-3 gap-4 text-center">
                <div class="text-white">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="ri-shield-check-line"></i>
                    </div>
                    <p class="text-xs text-blue-100">100% Seguro</p>
                </div>
                <div class="text-white">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="ri-whatsapp-line"></i>
                    </div>
                    <p class="text-xs text-blue-100">WhatsApp</p>
                </div>
                <div class="text-white">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="ri-support-line"></i>
                    </div>
                    <p class="text-xs text-blue-100">Soporte 24/7</p>
                </div>
            </div>
            
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
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
        
        // Auto-focus en el primer campo
        document.getElementById('email').focus();
        
        // Efecto de parallax suave en elementos flotantes
        document.addEventListener('mousemove', function(e) {
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            document.querySelectorAll('.floating').forEach((element, index) => {
                const speed = (index + 1) * 0.5;
                const x = (mouseX - 0.5) * speed;
                const y = (mouseY - 0.5) * speed;
                
                element.style.transform = `translate(${x}px, ${y}px)`;
            });
        });
        
        // Validación en tiempo real
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        
        emailInput.addEventListener('blur', function() {
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
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password.length > 0 && password.length < 6) {
                this.classList.add('border-orange-300', 'focus:ring-orange-500');
                this.classList.remove('border-gray-300', 'focus:ring-purple-500');
            } else {
                this.classList.remove('border-orange-300', 'focus:ring-orange-500');
                this.classList.add('border-gray-300', 'focus:ring-purple-500');
            }
        });
        
        // Efecto de loading en el botón al enviar
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="ri-loader-line animate-spin mr-2"></i>Iniciando sesión...';
            submitBtn.disabled = true;
            
            // Si hay error, restaurar el botón después de un momento
            setTimeout(() => {
                if (window.location.pathname === '/login') {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 2000);
        });
    </script>
</body>
</html>