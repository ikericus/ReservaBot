<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReservaBot - Automatiza tus Reservas con WhatsApp</title>
    <meta name="description" content="Sistema inteligente de reservas con integración WhatsApp. Automatiza tu negocio, reduce llamadas y mejora la experiencia de tus clientes.">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Iconos -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .feature-card {
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
        
        .modal {
            transition: opacity 0.25s ease;
        }
        
        .modal.show {
            opacity: 1;
            pointer-events: auto;
        }
        
        .modal.hide {
            opacity: 0;
            pointer-events: none;
        }
        
        .price-old {
            text-decoration: line-through;
            color: #9ca3af;
        }
        
        .price-beta {
            background: linear-gradient(45deg, #ef4444, #dc2626);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Mobile menu styles */
        .mobile-menu {
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            background: white;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            visibility: hidden;
        }
        
        .mobile-menu.open {
            transform: translateX(0);
            visibility: visible;
        }
    </style>
</head>
<body class="overflow-x-hidden">
    
    <!-- Navigation -->
    <nav class="fixed w-full z-50 bg-white/90 backdrop-blur-md border-b border-gray-200/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center">
                            <i class="ri-calendar-line text-white text-xl"></i>
                        </div>
                        <span class="ml-3 text-xl font-bold gradient-text">ReservaBot</span>
                    </div>
                </div>
                
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-8">
                        <a href="#inicio" class="text-gray-700 hover:text-blue-600 transition-colors">Inicio</a>
                        <a href="#planes" class="text-gray-700 hover:text-blue-600 transition-colors">Planes</a>
                        <button onclick="openContactModal()" class="text-gray-700 hover:text-blue-600 transition-colors">Contacto</button>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="/login" class="text-gray-700 hover:text-blue-600 transition-colors">Iniciar Sesión</a>
                    <a href="/login" class="btn-shine gradient-bg text-white px-6 py-2 rounded-full hover:shadow-lg transition-all">
                        Iniciar Demo
                    </a>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobileMenuBtn" class="text-gray-700 hover:text-blue-600">
                        <i class="ri-menu-line text-2xl" id="menuIcon"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobileMenu" class="mobile-menu md:hidden fixed inset-y-0 right-0 w-80 bg-white shadow-xl z-50 border-l border-gray-200">
            <div class="h-full flex flex-col bg-white">
                <!-- Header del menú -->
                <div class="flex items-center justify-between p-6 border-b border-gray-100">
                    <div class="flex items-center">
                        <div class="w-8 h-8 gradient-bg rounded-lg flex items-center justify-center">
                            <i class="ri-calendar-line text-white text-lg"></i>
                        </div>
                        <span class="ml-2 text-lg font-bold gradient-text">ReservaBot</span>
                    </div>
                    <button id="closeMobileMenu" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                
                <!-- Navegación principal -->
                <div class="flex-1 px-6 py-4">
                    <nav class="space-y-2">
                        <a href="#inicio" class="flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors group" onclick="closeMobileMenu()">
                            <i class="ri-home-line mr-3 text-gray-400 group-hover:text-blue-600"></i>
                            <span class="font-medium">Inicio</span>
                        </a>
                        <a href="#planes" class="flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors group" onclick="closeMobileMenu()">
                            <i class="ri-price-tag-3-line mr-3 text-gray-400 group-hover:text-blue-600"></i>
                            <span class="font-medium">Planes</span>
                        </a>
                        <button onclick="openContactModal(); closeMobileMenu();" class="w-full flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors group text-left">
                            <i class="ri-mail-line mr-3 text-gray-400 group-hover:text-blue-600"></i>
                            <span class="font-medium">Contacto</span>
                        </button>
                    </nav>
                </div>
                
                <!-- Acciones del footer -->
                <div class="p-6 border-t border-gray-100 bg-gray-50">
                    <div class="space-y-3">
                        <a href="/login" class="w-full flex items-center justify-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-white rounded-xl transition-colors border border-gray-200">
                            <i class="ri-login-circle-line mr-2"></i>
                            <span class="font-medium">Iniciar Sesión</span>
                        </a>
                        <a href="/login" class="w-full flex items-center justify-center gradient-bg text-white py-3 rounded-xl hover:shadow-lg transition-all font-semibold">
                            <i class="ri-play-circle-line mr-2"></i>
                            <span>Iniciar Demo</span>
                        </a>
                    </div>
                    
                    <!-- Info adicional -->
                    <div class="mt-4 text-center">
                        <p class="text-xs text-gray-500">
                            <i class="ri-shield-check-line mr-1"></i>
                            100% Gratis en Beta
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu overlay -->
        <div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden transition-opacity duration-300" style="opacity: 0;" onclick="closeMobileMenu()"></div>
    </nav>

    <!-- Hero Section -->
    <section id="inicio" class="min-h-screen gradient-bg relative overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0">
            <div class="absolute top-20 left-20 w-72 h-72 bg-white/10 rounded-full floating"></div>
            <div class="absolute top-40 right-20 w-96 h-96 bg-white/5 rounded-full floating" style="animation-delay: -1s;"></div>
            <div class="absolute bottom-20 left-1/2 w-80 h-80 bg-white/10 rounded-full floating" style="animation-delay: -2s;"></div>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-32 pb-20">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="text-white fade-in-up">
                    <h1 class="text-5xl lg:text-7xl font-bold leading-tight mb-6">
                        Automatiza tus
                        <span class="block text-yellow-300">Reservas</span>
                        con WhatsApp
                    </h1>
                    
                    <p class="text-xl lg:text-2xl text-blue-100 mb-8 leading-relaxed">
                        Reduce llamadas, mejora la experiencia del cliente y gestiona tu negocio 24/7 con nuestro sistema inteligente de reservas.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 mb-8">
                        <a href="/login" class="btn-shine bg-white text-purple-600 px-8 py-4 rounded-full font-semibold text-lg hover:shadow-xl transition-all">
                            <i class="ri-play-circle-line mr-2"></i>
                            Iniciar Demo
                        </a>
                        <button onclick="openContactModal()" class="glass-effect text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-white/20 transition-all">
                            <i class="ri-mail-line mr-2"></i>
                            Contactar
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-8 text-blue-100">
                        <div class="flex items-center">
                            <i class="ri-check-line text-green-300 mr-2"></i>
                            <span>Sin instalación</span>
                        </div>
                        <div class="flex items-center">
                            <i class="ri-check-line text-green-300 mr-2"></i>
                            <span>Gratis en Beta</span>
                        </div>
                        <div class="flex items-center">
                            <i class="ri-check-line text-green-300 mr-2"></i>
                            <span>Soporte incluido</span>
                        </div>
                    </div>
                </div>
                
                <div class="lg:text-right fade-in-up" style="animation-delay: 0.3s;">
                    <div class="relative">
                        <!-- Phone mockup -->
                        <div class="relative mx-auto w-80 h-96 bg-gray-900 rounded-3xl p-2">
                            <div class="w-full h-full bg-white rounded-2xl p-4 overflow-hidden">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                            <i class="ri-whatsapp-line text-white text-sm"></i>
                                        </div>
                                        <span class="ml-2 font-semibold text-sm">Barbería Style</span>
                                    </div>
                                    <span class="text-xs text-gray-500">Online</span>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="flex justify-start">
                                        <div class="bg-gray-100 rounded-2xl rounded-bl-md px-4 py-2 max-w-xs">
                                            <p class="text-sm">¡Hola! 👋 ¿Te gustaría reservar una cita?</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-end">
                                        <div class="bg-blue-500 text-white rounded-2xl rounded-br-md px-4 py-2 max-w-xs">
                                            <p class="text-sm">Sí, para mañana por la tarde</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-start">
                                        <div class="bg-gray-100 rounded-2xl rounded-bl-md px-4 py-2 max-w-xs">
                                            <p class="text-sm">Perfecto! Tengo disponible 16:00, 17:30 y 18:00. ¿Cuál prefieres?</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-end">
                                        <div class="bg-blue-500 text-white rounded-2xl rounded-br-md px-4 py-2 max-w-xs">
                                            <p class="text-sm">17:30 está perfecto</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-start">
                                        <div class="bg-green-100 rounded-2xl rounded-bl-md px-4 py-2 max-w-xs">
                                            <p class="text-sm">¡Reserva confirmada! ✅ Te esperamos mañana a las 17:30</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Floating elements -->
                        <div class="absolute -top-4 -right-4 w-16 h-16 bg-yellow-400 rounded-full flex items-center justify-center floating">
                            <i class="ri-calendar-check-line text-white text-2xl"></i>
                        </div>
                        <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-green-400 rounded-full flex items-center justify-center floating" style="animation-delay: -1s;">
                            <i class="ri-message-2-line text-white text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="planes" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                    Planes adaptados a tu <span class="gradient-text">negocio</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Elige el plan perfecto para tu tipo de negocio. Sin compromisos, cancela cuando quieras.
                </p>
            </div>
            
            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Plan Gratis -->
                <div class="bg-white rounded-2xl p-8 shadow-lg border-2 border-gray-100">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Gratis</h3>
                        <p class="text-gray-600 mb-6">Perfecto para empezar</p>
                        <div class="mb-8">
                            <span class="text-5xl font-bold text-gray-900">0€</span>
                            <span class="text-gray-600">/mes</span>
                        </div>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Reservas manuales y por formulario</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Confirmación automática básica</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Calendario de reservas</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Agenda de clientes básica</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-close-line text-red-500 mr-3"></i>
                            <span class="text-gray-400">Sin integración WhatsApp</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Soporte por email</span>
                        </li>
                    </ul>
                    
                    <a href="/signup?plan=gratis" class="w-full block text-center py-3 px-6 border-2 border-gray-300 rounded-full font-semibold text-gray-700 hover:border-gray-400 transition-all">
                        Empezar Gratis
                    </a>
                </div>
                
                <!-- Plan Estándar -->
                <div class="bg-white rounded-2xl p-8 shadow-xl border-2 border-blue-500 relative transform scale-105">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-blue-500 text-white px-6 py-2 rounded-full text-sm font-semibold">
                            Recomendado
                        </span>
                    </div>
                    
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Estándar</h3>
                        <p class="text-gray-600 mb-6">Para negocios activos</p>
                        <div class="mb-8">
                            <div class="flex items-center justify-center gap-2 mb-2">
                                <span class="text-2xl font-bold price-old">9€</span>
                                <span class="price-beta">Gratis en Beta</span>
                            </div>
                            <span class="text-gray-600">/mes</span>
                        </div>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Todo del plan Gratis</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Agenda de clientes completa</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Integración WhatsApp</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Comunicación con clientes</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Confirmaciones automáticas</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-close-line text-red-500 mr-3"></i>
                            <span class="text-gray-400">Sin IA automática</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Soporte prioritario</span>
                        </li>
                    </ul>
                    
                    <a href="/signup?plan=estandar" class="btn-shine w-full block text-center py-3 px-6 gradient-bg text-white rounded-full font-semibold hover:shadow-lg transition-all">
                        Empezar Gratis
                    </a>
                </div>
                
                <!-- Plan Premium -->
                <div class="bg-white rounded-2xl p-8 shadow-lg border-2 border-gray-100">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Premium</h3>
                        <p class="text-gray-600 mb-6">Automatización completa</p>
                        <div class="mb-8">
                            <div class="flex items-center justify-center gap-2 mb-2">
                                <span class="text-2xl font-bold price-old">19€</span>
                                <span class="price-beta">Gratis en Beta</span>
                            </div>
                            <span class="text-gray-600">/mes</span>
                        </div>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Todo del plan Estándar</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>IA para reservas automáticas</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Respuestas automáticas</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Recordatorios automáticos</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Blacklist de clientes</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Analytics avanzados</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Soporte 24/7</span>
                        </li>
                    </ul>
                    
                    <div class="text-center">
                        <span class="inline-block bg-red-100 text-red-800 text-sm px-4 py-2 rounded-full font-semibold mb-4">
                            Próximamente
                        </span>
                        <button disabled class="w-full py-3 px-6 border-2 border-gray-300 rounded-full font-semibold text-gray-400 cursor-not-allowed opacity-60">
                            No Disponible
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-12">
                <p class="text-gray-600 mb-4">¿Tienes dudas sobre los planes?</p>
                <button onclick="openContactModal()" class="text-blue-600 hover:text-blue-700 font-semibold">
                    Escríbenos y te ayudamos →
                </button>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                    Preguntas <span class="gradient-text">frecuentes</span>
                </h2>
                <p class="text-xl text-gray-600">
                    Resolvemos las dudas más comunes sobre ReservaBot
                </p>
            </div>
            
            <div class="space-y-6">
                <div class="bg-gray-50 rounded-2xl p-6">
                    <button class="w-full text-left flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">¿Necesito instalar algo en mi teléfono?</h3>
                        <i class="ri-arrow-down-s-line text-gray-600"></i>
                    </button>
                    <div class="mt-4">
                        <p class="text-gray-600">
                            No, ReservaBot funciona 100% en la nube. Solo necesitas tu WhatsApp normal y acceso a internet. Todo se gestiona desde el panel web.
                        </p>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-2xl p-6">
                    <button class="w-full text-left flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">¿Funciona con WhatsApp Business?</h3>
                        <i class="ri-arrow-down-s-line text-gray-600"></i>
                    </button>
                    <div class="mt-4">
                        <p class="text-gray-600">
                            Sí, ReservaBot es compatible tanto con WhatsApp normal como con WhatsApp Business. Recomendamos Business para funciones adicionales.
                        </p>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-2xl p-6">
                    <button class="w-full text-left flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">¿Puedo personalizar los mensajes automáticos?</h3>
                        <i class="ri-arrow-down-s-line text-gray-600"></i>
                    </button>
                    <div class="mt-4">
                        <p class="text-gray-600">
                            Por supuesto. Puedes personalizar completamente todos los mensajes, desde saludos hasta confirmaciones y recordatorios.
                        </p>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-2xl p-6">
                    <button class="w-full text-left flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">¿Hay límite en el número de clientes?</h3>
                        <i class="ri-arrow-down-s-line text-gray-600"></i>
                    </button>
                    <div class="mt-4">
                        <p class="text-gray-600">
                            No hay límite en el número de clientes que puedes gestionar. Solo limitamos las reservas por mes según el plan elegido.
                        </p>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-2xl p-6">
                    <button class="w-full text-left flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">¿Qué pasa cuando termine la fase beta?</h3>
                        <i class="ri-arrow-down-s-line text-gray-600"></i>
                    </button>
                    <div class="mt-4">
                        <p class="text-gray-600">
                            Los usuarios que se registren durante la beta mantendrán precios especiales. Te notificaremos con tiempo suficiente antes de cualquier cambio.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="demo" class="py-20 gradient-bg relative overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute top-0 left-0 w-full h-full bg-black/20"></div>
        </div>
        
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl lg:text-6xl font-bold text-white mb-6">
                ¿Listo para automatizar tu negocio?
            </h2>
            <p class="text-xl lg:text-2xl text-blue-100 mb-8 max-w-3xl mx-auto">
                Prueba ReservaBot gratis y descubre cómo puede transformar la gestión de reservas de tu negocio
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-8">
                <a href="/login" class="btn-shine bg-white text-purple-600 px-8 py-4 rounded-full font-bold text-lg hover:shadow-xl transition-all">
                    <i class="ri-rocket-line mr-2"></i>
                    Iniciar Demo Gratis
                </a>
                <button onclick="openContactModal()" class="glass-effect text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-white/20 transition-all">
                    <i class="ri-mail-line mr-2"></i>
                    Contáctanos
                </button>
            </div>
            
            <div class="flex flex-wrap justify-center items-center gap-8 text-blue-100">
                <div class="flex items-center">
                    <i class="ri-check-line text-green-300 mr-2"></i>
                    <span>Setup en 5 minutos</span>
                </div>
                <div class="flex items-center">
                    <i class="ri-check-line text-green-300 mr-2"></i>
                    <span>Sin tarjeta de crédito</span>
                </div>
                <div class="flex items-center">
                    <i class="ri-check-line text-green-300 mr-2"></i>
                    <span>Soporte incluido</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-4 gap-8 mb-8">
                <div class="lg:col-span-2">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center">
                            <i class="ri-calendar-line text-white text-xl"></i>
                        </div>
                        <span class="ml-3 text-2xl font-bold">ReservaBot</span>
                    </div>
                    <p class="text-gray-400 text-lg leading-relaxed max-w-md">
                        Automatiza las reservas de tu negocio con WhatsApp. La solución más completa para gestionar citas, clientes y comunicaciones.
                    </p>
                    <div class="flex space-x-4 mt-6">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                            <i class="ri-twitter-line"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                            <i class="ri-facebook-line"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                            <i class="ri-linkedin-line"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                            <i class="ri-instagram-line"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-6">Producto</h3>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="#planes" class="hover:text-white transition-colors">Características</a></li>
                        <li><a href="#planes" class="hover:text-white transition-colors">Precios</a></li>
                        <li><a href="/login" class="hover:text-white transition-colors">Demo</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">API</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-6">Soporte</h3>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Centro de Ayuda</a></li>
                        <li><button onclick="openContactModal()" class="hover:text-white transition-colors">Contacto</button></li>
                        <li><a href="#" class="hover:text-white transition-colors">Tutoriales</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Estado del Sistema</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400">
                    © 2025 ReservaBot. Todos los derechos reservados.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">Privacidad</a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">Términos</a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">Cookies</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Contact Modal -->
    <div id="contactModal" class="modal hide fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full transform transition-all">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Contáctanos</h3>
                <button onclick="closeContactModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>
            
            <form id="contactForm" class="space-y-4">
                <div>
                    <label for="contactName" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                    <input type="text" id="contactName" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Tu nombre">
                </div>
                
                <div>
                    <label for="contactEmail" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="contactEmail" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="tu@email.com">
                </div>
                
                <div>
                    <label for="contactSubject" class="block text-sm font-medium text-gray-700 mb-2">Asunto</label>
                    <select id="contactSubject" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="consulta">Consulta general</option>
                        <option value="demo">Solicitar demo personalizada</option>
                        <option value="soporte">Soporte técnico</option>
                        <option value="ventas">Información de ventas</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                
                <div>
                    <label for="contactMessage" class="block text-sm font-medium text-gray-700 mb-2">Mensaje</label>
                    <textarea id="contactMessage" rows="4" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Cuéntanos en qué podemos ayudarte..."></textarea>
                </div>
                
                <button type="submit" class="w-full gradient-bg text-white py-3 px-4 rounded-xl font-semibold hover:shadow-lg transition-all">
                    <i class="ri-send-plane-line mr-2"></i>
                    Enviar Mensaje
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    O escríbenos directamente a:
                    <a href="mailto:contacto@reservabot.io" class="text-purple-600 font-medium">contacto@reservabot.io</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Mobile menu functions
        function openMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('mobileMenuOverlay');
            const menuIcon = document.getElementById('menuIcon');
            
            // Mostrar el menú y overlay
            menu.style.visibility = 'visible';
            overlay.classList.remove('hidden');
            
            // Pequeño delay para la animación
            setTimeout(() => {
                menu.classList.add('open');
                overlay.style.opacity = '1';
            }, 10);
            
            menuIcon.classList.remove('ri-menu-line');
            menuIcon.classList.add('ri-close-line');
            document.body.style.overflow = 'hidden';
        }
        
        function closeMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('mobileMenuOverlay');
            const menuIcon = document.getElementById('menuIcon');
            
            menu.classList.remove('open');
            overlay.style.opacity = '0';
            menuIcon.classList.remove('ri-close-line');
            menuIcon.classList.add('ri-menu-line');
            document.body.style.overflow = 'auto';
            
            // Ocultar menú y overlay después de la animación
            setTimeout(() => {
                menu.style.visibility = 'hidden';
                overlay.classList.add('hidden');
            }, 300);
        }
        
        // Asegurar que el menú esté cerrado al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('mobileMenuOverlay');
            
            menu.classList.remove('open');
            menu.style.visibility = 'hidden';
            overlay.classList.add('hidden');
            overlay.style.opacity = '0';
        });
        
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            const menu = document.getElementById('mobileMenu');
            if (menu.classList.contains('open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });
        
        document.getElementById('closeMobileMenu').addEventListener('click', closeMobileMenu);
        
        // Contact Modal Functions
        function openContactModal() {
            const modal = document.getElementById('contactModal');
            modal.classList.remove('hide');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closeContactModal() {
            const modal = document.getElementById('contactModal');
            modal.classList.remove('show');
            modal.classList.add('hide');
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside
        document.getElementById('contactModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeContactModal();
            }
        });
        
        // Handle contact form submission
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                name: document.getElementById('contactName').value,
                email: document.getElementById('contactEmail').value,
                subject: document.getElementById('contactSubject').value,
                message: document.getElementById('contactMessage').value
            };
            
            // Create mailto link
            const mailtoLink = `mailto:contacto@reservabot.io?subject=${encodeURIComponent('Contacto Web - ' + formData.subject)}&body=${encodeURIComponent(`Nombre: ${formData.name}\nEmail: ${formData.email}\n\nMensaje:\n${formData.message}`)}`;
            
            // Open email client
            window.location.href = mailtoLink;
            
            // Show success message
            alert('Se ha abierto tu cliente de email. Si no se abre automáticamente, puedes escribirnos a contacto@reservabot.io');
            
            // Close modal and reset form
            closeContactModal();
            this.reset();
        });
        
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    // Close mobile menu if open
                    closeMobileMenu();
                }
            });
        });
        
        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 100) {
                nav.classList.add('bg-white/95');
                nav.classList.remove('bg-white/90');
            } else {
                nav.classList.add('bg-white/90');
                nav.classList.remove('bg-white/95');
            }
        });
        
        // FAQ Accordion
        document.querySelectorAll('.space-y-6 button').forEach(button => {
            button.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const icon = this.querySelector('i');
                
                if (content.style.display === 'none' || !content.style.display) {
                    content.style.display = 'block';
                    icon.classList.remove('ri-arrow-down-s-line');
                    icon.classList.add('ri-arrow-up-s-line');
                } else {
                    content.style.display = 'none';
                    icon.classList.remove('ri-arrow-up-s-line');
                    icon.classList.add('ri-arrow-down-s-line');
                }
            });
        });
        
        // Initialize FAQ state - Show first FAQ open by default
        document.querySelectorAll('.space-y-6 > div > div:last-child').forEach((content, index) => {
            if (index === 0) {
                content.style.display = 'block';
                const button = content.previousElementSibling.querySelector('i');
                button.classList.remove('ri-arrow-down-s-line');
                button.classList.add('ri-arrow-up-s-line');
            } else {
                content.style.display = 'none';
            }
        });
    </script>
</body>
</html>