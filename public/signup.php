<?php
// Iniciar sesión para manejar mensajes
session_start();

require_once 'includes/db-config.php';
require_once 'includes/auth.php';

// Si ya está autenticado, redirigir al inicio
if (isAuthenticated()) {
    header('Location: /');
    exit;
}

// Obtener mensajes y datos de la sesión
$errors = $_SESSION['register_errors'] ?? [];
$formData = $_SESSION['register_data'] ?? [];

// Limpiar mensajes de la sesión
unset($_SESSION['register_errors'], $_SESSION['register_data']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - ReservaBot</title>
    
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
        
        .plan-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .plan-card:hover {
            transform: translateY(-2px);
        }
        
        .plan-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
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
<body class="gradient-bg relative overflow-y-auto min-h-screen">
    
    <!-- Animated Background Elements -->
    <div class="fixed inset-0 pointer-events-none">
        <div class="absolute top-20 left-20 w-64 h-64 bg-white/10 rounded-full floating"></div>
        <div class="absolute top-60 right-32 w-48 h-48 bg-white/5 rounded-full floating" style="animation-delay: -1s;"></div>
        <div class="absolute bottom-32 left-1/3 w-72 h-72 bg-white/10 rounded-full floating" style="animation-delay: -2s;"></div>
        <div class="absolute bottom-20 right-20 w-56 h-56 bg-white/5 rounded-full floating" style="animation-delay: -0.5s;"></div>
    </div>
    
    <div class="relative min-h-screen flex items-center justify-center px-4 py-8">
        <div class="max-w-2xl w-full">
            
            <!-- Logo y título -->
            <div class="text-center mb-8">
                <div class="flex items-center justify-center mb-6">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="ri-calendar-line text-purple-600 text-2xl"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Únete a ReservaBot</h1>
                <p class="text-blue-100">Crea tu cuenta y automatiza las reservas de tu negocio</p>
            </div>
            
            <!-- Formulario de registro -->
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
                
                <form action="api/register-handler" method="POST" class="space-y-6" id="registroForm">
                    
                    <!-- Información personal -->
                    <div class="grid md:grid-cols-2 gap-6">
                        
                        <!-- Nombre completo -->
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre completo *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-user-line text-gray-400"></i>
                                </div>
                                <input
                                    type="text"
                                    id="nombre"
                                    name="nombre"
                                    required
                                    class="input-focus block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300"
                                    placeholder="Tu nombre completo"
                                    value="<?php echo htmlspecialchars($formData['nombre'] ?? ''); ?>"
                                >
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email *
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
                                    value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                                >
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Información del negocio -->
                    <div class="grid md:grid-cols-2 gap-6">
                        
                        <!-- Teléfono -->
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">
                                Teléfono *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-phone-line text-gray-400"></i>
                                </div>
                                <input
                                    type="tel"
                                    id="telefono"
                                    name="telefono"
                                    required
                                    class="input-focus block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300"
                                    placeholder="+34 600 123 456"
                                    value="<?php echo htmlspecialchars($formData['telefono'] ?? ''); ?>"
                                >
                            </div>
                        </div>
                        
                        <!-- Nombre del negocio -->
                        <div>
                            <label for="negocio" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre del negocio *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-store-line text-gray-400"></i>
                                </div>
                                <input
                                    type="text"
                                    id="negocio"
                                    name="negocio"
                                    required
                                    class="input-focus block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300"
                                    placeholder="Nombre de tu negocio"
                                    value="<?php echo htmlspecialchars($formData['negocio'] ?? ''); ?>"
                                >
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Contraseñas -->
                    <div class="grid md:grid-cols-2 gap-6">
                        
                        <!-- Contraseña -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Contraseña *
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
                                Confirmar contraseña *
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
                                    class="input-focus block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300"
                                    placeholder="Repite tu contraseña"
                                >
                                <div id="passwordMatch" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i class="ri-check-line text-green-500 hidden" id="matchIcon"></i>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Selección de plan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-4">
                            Elige tu plan inicial
                        </label>
                        <div class="grid md:grid-cols-3 gap-4">
                            
                            <!-- Plan Gratis -->
                            <div class="plan-card border-2 border-gray-200 rounded-xl p-4 selected" data-plan="gratis">
                                <input type="radio" name="plan" value="gratis" id="plan-gratis" class="hidden" checked>
                                <div class="text-center">
                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                        <i class="ri-gift-line text-gray-600"></i>
                                    </div>
                                    <h3 class="font-semibold text-gray-900">Gratis</h3>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">0€</p>
                                    <p class="text-sm text-gray-500">Básico</p>
                                </div>
                            </div>
                            
                            <!-- Plan Estándar -->
                            <div class="plan-card border-2 border-gray-200 rounded-xl p-4" data-plan="estandar">
                                <input type="radio" name="plan" value="estandar" id="plan-estandar" class="hidden">
                                <div class="text-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                        <i class="ri-star-line text-blue-600"></i>
                                    </div>
                                    <h3 class="font-semibold text-gray-900">Estándar</h3>
                                    <div class="mt-1">
                                        <span class="text-lg font-bold text-gray-400 line-through">9€</span>
                                        <span class="block text-sm font-bold text-red-600 mt-1">GRATIS EN BETA</span>
                                    </div>
                                    <p class="text-sm text-gray-500">Recomendado</p>
                                </div>
                            </div>
                            
                            <!-- Plan Premium -->
                            <div class="plan-card border-2 border-gray-300 rounded-xl p-4 opacity-75 cursor-not-allowed" data-plan="premium">
                                <div class="text-center">
                                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                        <i class="ri-vip-crown-line text-purple-600"></i>
                                    </div>
                                    <h3 class="font-semibold text-gray-900">Premium</h3>
                                    <div class="mt-1">
                                        <span class="text-lg font-bold text-gray-400 line-through">19€</span>
                                    </div>
                                    <p class="text-sm text-gray-500">Completo</p>
                                    <span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full mt-1">
                                        Próximamente
                                    </span>
                                </div>
                            </div>
                            
                        </div>
                        <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                            <p class="text-sm text-blue-800 text-center">
                                <i class="ri-information-line mr-1"></i>
                                Durante la fase beta, todos los planes están disponibles gratis.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Términos y condiciones -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input
                                id="terminos"
                                name="terminos"
                                type="checkbox"
                                required
                                class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                            >
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terminos" class="text-gray-700">
                                Acepto los 
                                <a href="#" class="text-purple-600 hover:text-purple-500 font-medium">términos y condiciones</a>
                                y la 
                                <a href="#" class="text-purple-600 hover:text-purple-500 font-medium">política de privacidad</a>
                                *
                            </label>
                        </div>
                    </div>
                    
                    <!-- Botón de registro -->
                    <button
                        type="submit"
                        class="btn-shine w-full gradient-bg text-white py-3 px-4 rounded-xl font-semibold text-lg hover:shadow-lg transition-all duration-300"
                        id="submitBtn"
                    >
                        <i class="ri-user-add-line mr-2"></i>
                        Crear mi cuenta
                    </button>
                    
                </form>
                
                <!-- Enlaces adicionales -->
                <div class="mt-6 text-center space-y-2">
                    <p class="text-gray-600 text-sm">
                        ¿Ya tienes cuenta?
                        <a href="/login" class="text-purple-600 hover:text-purple-500 font-medium">
                            Inicia sesión aquí
                        </a>
                    </p>
                </div>
                
            </div>
            
            <!-- Features destacadas -->
            <div class="mt-8 grid grid-cols-4 gap-4 text-center">
                <div class="text-white">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="ri-shield-check-line"></i>
                    </div>
                    <p class="text-xs text-blue-100">100% Seguro</p>
                </div>
                <div class="text-white">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="ri-time-line"></i>
                    </div>
                    <p class="text-xs text-blue-100">Setup 5 min</p>
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
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
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
        document.getElementById('confirm_password').addEventListener('input', function() {
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
        
        // Plan selection
        document.querySelectorAll('.plan-card').forEach(card => {
            card.addEventListener('click', function() {
                const planValue = this.dataset.plan;
                
                if (planValue === 'gratis' || planValue === 'estandar') {
                    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    document.querySelector(`input[value="${planValue}"]`).checked = true;
                } else {
                    alert('El plan Premium estará disponible próximamente.');
                }
            });
        });
        
        // Form submission
        document.getElementById('registroForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="ri-loader-line animate-spin mr-2"></i>Creando cuenta...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                if (window.location.pathname.includes('signup')) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 3000);
        });
    </script>
</body>
</html>