<!-- public/pages/landing.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReservaBot - Sistema Completo de Gestión de Citas</title>
    <meta name="description" content="Plataforma integral de gestión de citas con WhatsApp, calendario, agenda de clientes y comunicación automática. Para cualquier negocio que trabaje con citas.">
    
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
            background: white !important;
            backdrop-filter: none;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.2);
            visibility: hidden;
        }

        .mobile-menu.open {
            transform: translateX(0);
            visibility: visible;
        }
        
        /* Screenshot carousel */
        .screenshot-slide {
            transition: opacity 0.5s ease-in-out;
        }
        
        .screenshot-slide.hidden {
            display: none;
        }
        
        .screenshot-dot.active {
            background: white;
            transform: scale(1.2);
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
                        <a href="#funcionalidades" class="text-gray-700 hover:text-blue-600 transition-colors">Funcionalidades</a>
                        <a href="#planes" class="text-gray-700 hover:text-blue-600 transition-colors">Planes</a>
                        <button onclick="openContactModal()" class="text-gray-700 hover:text-blue-600 transition-colors">Contacto</button>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="/login" class="text-gray-700 hover:text-blue-600 transition-colors">Iniciar Sesión</a>
                    <a href="/login?user=demo@reservabot.es&pass=demo123" class="btn-shine gradient-bg text-white px-6 py-2 rounded-full hover:shadow-lg transition-all">
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
        <div id="mobileMenu" class="mobile-menu md:hidden fixed inset-y-0 right-0 w-80 shadow-xl z-50 border-l border-gray-200" style="background-color: white !important;">
            <div class="h-full flex flex-col" style="background-color: white !important;">
                <!-- Header del menú -->
                <div class="flex items-center justify-between p-6 border-b border-gray-100" style="background-color: white !important;">
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
                <div class="flex-1 px-6 py-4" style="background-color: white !important;">
                    <nav class="space-y-2">
                        <a href="#inicio" class="flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors group" onclick="closeMobileMenu()">
                            <i class="ri-home-line mr-3 text-gray-400 group-hover:text-blue-600"></i>
                            <span class="font-medium">Inicio</span>
                        </a>
                        <a href="#funcionalidades" class="flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors group" onclick="closeMobileMenu()">
                            <i class="ri-tools-line mr-3 text-gray-400 group-hover:text-blue-600"></i>
                            <span class="font-medium">Funcionalidades</span>
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
                <div class="p-6 border-t border-gray-100" style="background-color: #f9fafb !important;">
                    <div class="space-y-3">
                        <a href="/login" class="w-full flex items-center justify-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-white rounded-xl transition-colors border border-gray-200">
                            <i class="ri-login-circle-line mr-2"></i>
                            <span class="font-medium">Iniciar Sesión</span>
                        </a>
                        <a href="/login?user=demo@reservabot.es&pass=demo1234" class="w-full flex items-center justify-center gradient-bg text-white py-3 rounded-xl hover:shadow-lg transition-all font-semibold">
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
                        Gestión Completa de 
                        <span class="block text-yellow-300">Citas</span>
                        para tu Negocio
                    </h1>
                    
                    <p class="text-xl lg:text-2xl text-blue-100 mb-6 leading-relaxed">
                        Plataforma integral con WhatsApp, calendario, agenda de clientes y comunicación automática.
                    </p>
                    
                    <!-- Target específico -->
                    <div class="mb-8 p-4 bg-white/10 rounded-xl backdrop-blur-sm">
                        <p class="text-lg text-blue-100 mb-3">
                            <i class="ri-check-double-line text-yellow-300 mr-2"></i>
                            Ideal para:
                        </p>
                        <div class="grid grid-cols-2 gap-2 text-blue-100">
                            <div class="flex items-center">
                                <i class="ri-scissors-line mr-2 text-yellow-300"></i>
                                <span>Peluquerías</span>
                            </div>
                            <div class="flex items-center">
                                <i class="ri-hand-heart-line mr-2 text-yellow-300"></i>
                                <span>Estética</span>
                            </div>
                            <div class="flex items-center">
                                <i class="ri-heart-pulse-line mr-2 text-yellow-300"></i>
                                <span>Fisioterapia</span>
                            </div>
                            <div class="flex items-center">
                                <i class="ri-briefcase-line mr-2 text-yellow-300"></i>
                                <span>Consultoría</span>
                            </div>
                            <div class="flex items-center col-span-2">
                                <i class="ri-calendar-check-line mr-2 text-yellow-300"></i>
                                <span>Cualquier negocio que gestione citas</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4 mb-8">
                        <a href="/login?user=demo@reservabot.es&pass=demo1234" class="btn-shine bg-white text-purple-600 px-8 py-4 rounded-full font-semibold text-lg hover:shadow-xl transition-all">
                            <i class="ri-play-circle-line mr-2"></i>
                            Iniciar prueba
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
                    </div>
                </div>
                
                <div class="lg:text-right fade-in-up" style="animation-delay: 0.3s;">
                    <div class="relative">
                        <!-- App Screenshots Carousel -->
                        <div class="relative mx-auto max-w-sm">
                            <div id="screenshotCarousel" class="overflow-hidden rounded-2xl shadow-2xl">
                                <!-- Screenshot 1 - Calendario -->
                                <div class="screenshot-slide active">
                                    <img src="/uploads/1764403351596_image.png" alt="Calendario de reservas ReservaBot" class="w-full h-auto">
                                </div>
                                <!-- Screenshot 2 - Pendientes -->
                                <div class="screenshot-slide hidden">
                                    <img src="/uploads/1764403357831_image.png" alt="Gestión de reservas pendientes" class="w-full h-auto">
                                </div>
                                <!-- Screenshot 3 - Detalle reserva -->
                                <div class="screenshot-slide hidden">
                                    <img src="/uploads/1764403363698_image.png" alt="Detalle de reserva confirmada" class="w-full h-auto">
                                </div>
                            </div>
                            
                            <!-- Carousel Controls -->
                            <div class="flex justify-center mt-6 space-x-2">
                                <button onclick="showScreenshot(0)" class="screenshot-dot w-3 h-3 rounded-full bg-white transition-all active"></button>
                                <button onclick="showScreenshot(1)" class="screenshot-dot w-3 h-3 rounded-full bg-white/50 transition-all"></button>
                                <button onclick="showScreenshot(2)" class="screenshot-dot w-3 h-3 rounded-full bg-white/50 transition-all"></button>
                            </div>
                            
                            <div class="text-center mt-4">
                                <p class="text-white text-sm font-medium">
                                    <span id="screenshotLabel">Calendario de citas</span>
                                </p>
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
    <section id="funcionalidades" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                    Más que un <span class="gradient-text">chatbot</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Simplifica tu día a día y enfócate en lo que realmente importa: tus clientes.
                </p>
            </div>
            
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="space-y-8">
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="ri-phone-line text-red-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Reduce las llamadas constantes</h3>
                                <p class="text-gray-600">Deja que tus clientes reserven por WhatsApp, sin interrumpir tu trabajo con llamadas continuas.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="ri-time-line text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Disponible 24/7 para tus clientes</h3>
                                <p class="text-gray-600">Tus clientes pueden reservar incluso cuando duermes. El sistema trabaja por ti las 24 horas del día.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="ri-calendar-check-line text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Evita dobles reservas</h3>
                                <p class="text-gray-600">El calendario se actualiza automáticamente. Nunca más tendrás dos clientes a la misma hora.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="ri-user-heart-line text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Mejora la experiencia del cliente</h3>
                                <p class="text-gray-600">Respuestas instantáneas, confirmaciones automáticas y recordatorios que reducen los no-shows.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="lg:text-center">
                    <div class="bg-white rounded-2xl p-8 shadow-xl">
                        <div class="text-center mb-8">
                            <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-lightbulb-line text-white text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900">¿Sabías que...</h3>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="text-center p-4 bg-blue-50 rounded-xl">
                                <div class="text-3xl font-bold text-blue-600 mb-2">67%</div>
                                <p class="text-gray-600">de los profesionales pierden clientes por no contestar el teléfono</p>
                            </div>
                            
                            <div class="text-center p-4 bg-green-50 rounded-xl">
                                <div class="text-3xl font-bold text-green-600 mb-2">2 horas</div>
                                <p class="text-gray-600">diarias ahorras automatizando las reservas</p>
                            </div>
                            
                            <div class="text-center p-4 bg-purple-50 rounded-xl">
                                <div class="text-3xl font-bold text-purple-600 mb-2">95%</div>
                                <p class="text-gray-600">de las personas usa WhatsApp a diario</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="planes" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                    Planes adaptados a tu <span class="gradient-text">negocio</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Empieza gratis y escala según crezca tu negocio. Sin compromisos, cancela cuando quieras.
                </p>
            </div>
            
            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Plan basico -->
                <div class="bg-white rounded-2xl p-8 shadow-lg border-2 border-gray-100">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Básico</h3>
                        <p class="text-gray-600 mb-6">Para empezar sin compromisos</p>
                        <div class="mb-8">
                            <span class="text-5xl font-bold text-gray-900">0€</span>
                            <span class="text-gray-600">/mes</span>
                        </div>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Reservas por formulario web</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Calendario de reservas</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Agenda básica de clientes</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Confirmaciones automáticas</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-close-line text-red-500 mr-3"></i>
                            <span class="text-gray-400">Sin integración WhatsApp</span>
                        </li>
                    </ul>
                    
                    <a href="/signup?plan=profesional" class="w-full block text-center py-3 px-6 border-2 border-gray-300 rounded-full font-semibold text-gray-700 hover:border-gray-400 transition-all">
                        Empezar Gratis
                    </a>
                </div>
                
                <!-- Plan Profesional -->
                <div class="bg-white rounded-2xl p-8 shadow-xl border-2 border-blue-500 relative transform scale-105">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-blue-500 text-white px-6 py-2 rounded-full text-sm font-semibold">
                            Más Popular
                        </span>
                    </div>
                    
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Profesional</h3>
                        <p class="text-gray-600 mb-6">Para profesionales activos</p>
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
                            <span>Todo del plan Básico</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span><strong>Integración WhatsApp completa</strong></span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Agenda completa de clientes</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Comunicación directa con clientes</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Recordatorios automáticos</span>
                        </li>
                    </ul>
                    
                    <a href="/signup?plan=profesional" class="btn-shine w-full block text-center py-3 px-6 gradient-bg text-white rounded-full font-semibold hover:shadow-lg transition-all">
                        Empezar Beta Gratis
                    </a>
                </div>
                
                <!-- Plan Avanzado -->
                <div class="bg-white rounded-2xl p-8 shadow-lg border-2 border-gray-100">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Avanzado</h3>
                        <p class="text-gray-600 mb-6">Automatización completa con IA</p>
                        <div class="mb-8">
                            <div class="flex items-center justify-center gap-2 mb-2">
                                <span class="text-2xl font-bold price-old">19€</span>
                                <span class="price-beta">Próximamente</span>
                            </div>
                            <span class="text-gray-600">/mes</span>
                        </div>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Todo del plan Profesional</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span><strong>IA para reservas automáticas</strong></span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Respuestas automáticas inteligentes</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Gestión automática de cancelaciones</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Blacklist de clientes automática</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Analytics avanzados</span>
                        </li>
                    </ul>
                    
                    <div class="text-center">
                        <span class="inline-block bg-orange-100 text-orange-800 text-sm px-4 py-2 rounded-full font-semibold mb-4">
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
    <section class="py-20 bg-gray-50">
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
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <button class="w-full text-left flex justify-between items-center" onclick="toggleFAQ(this)">
                        <h3 class="text-lg font-semibold text-gray-900">¿ReservaBot es solo un chatbot de WhatsApp?</h3>
                        <i class="ri-arrow-down-s-line text-gray-600 transition-transform"></i>
                    </button>
                    <div class="mt-4 hidden">
                        <p class="text-gray-600">
                            No, ReservaBot es mucho más que un chatbot. Es una plataforma completa que incluye calendario de citas, agenda de clientes, formularios web, comunicación por WhatsApp, recordatorios automáticos y analytics. Es todo lo que necesitas para gestionar las citas de tu negocio.
                        </p>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <button class="w-full text-left flex justify-between items-center" onclick="toggleFAQ(this)">
                        <h3 class="text-lg font-semibold text-gray-900">¿Necesito instalar algo en mi teléfono?</h3>
                        <i class="ri-arrow-down-s-line text-gray-600 transition-transform"></i>
                    </button>
                    <div class="mt-4 hidden">
                        <p class="text-gray-600">
                            No necesitas instalar nada. ReservaBot funciona 100% en la nube. Solo necesitas tu WhatsApp normal (recomendamos WhatsApp Business) y acceso a internet. Todo se gestiona desde el panel web.
                        </p>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <button class="w-full text-left flex justify-between items-center" onclick="toggleFAQ(this)">
                        <h3 class="text-lg font-semibold text-gray-900">¿Funciona para cualquier tipo de negocio?</h3>
                        <i class="ri-arrow-down-s-line text-gray-600 transition-transform"></i>
                    </button>
                    <div class="mt-4 hidden">
                        <p class="text-gray-600">
                            ReservaBot está optimizado para negocios que trabajan con citas individuales: peluquerías, centros de estética, fisioterapeutas, psicólogos, consultores, coaches, etc. Es perfecto para profesionales que atienden un cliente a la vez.
                        </p>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <button class="w-full text-left flex justify-between items-center" onclick="toggleFAQ(this)">
                        <h3 class="text-lg font-semibold text-gray-900">¿Puedo personalizar los mensajes automáticos?</h3>
                        <i class="ri-arrow-down-s-line text-gray-600 transition-transform"></i>
                    </button>
                    <div class="mt-4 hidden">
                        <p class="text-gray-600">
                            Por supuesto. Puedes personalizar completamente todos los mensajes: saludos, confirmaciones, recordatorios, despedidas, etc. También puedes configurar tu horario de atención, tipos de servicios y precios.
                        </p>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <button class="w-full text-left flex justify-between items-center" onclick="toggleFAQ(this)">
                        <h3 class="text-lg font-semibold text-gray-900">¿Qué pasa cuando termine la fase beta?</h3>
                        <i class="ri-arrow-down-s-line text-gray-600 transition-transform"></i>
                    </button>
                    <div class="mt-4 hidden">
                        <p class="text-gray-600">
                            Los usuarios que se registren durante la beta mantendrán precios especiales de forma permanente. Te notificaremos con al menos 30 días de antelación antes de cualquier cambio en los precios.
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
                ¿Listo para automatizar tu agenda?
            </h2>
            <p class="text-xl lg:text-2xl text-blue-100 mb-8 max-w-3xl mx-auto">
                Únete a la beta gratuita y descubre cómo ReservaBot puede transformar la gestión de citas de tu negocio.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-8">
                <a href="/login?user=demo@reservabot.es&pass=demo1234" class="btn-shine bg-white text-purple-600 px-8 py-4 rounded-full font-bold text-lg hover:shadow-xl transition-all">
                    <i class="ri-rocket-line mr-2"></i>
                    Probar Beta Gratis
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
                        Plataforma completa de gestión de citas con WhatsApp, calendario, agenda de clientes y comunicación automática. Perfecto para profesionales que trabajan con citas individuales.
                    </p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-6">Producto</h3>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="#funcionalidades" class="hover:text-white transition-colors">Funcionalidades</a></li>
                        <li><a href="#planes" class="hover:text-white transition-colors">Precios</a></li>
                        <li><a href="/login?user=demo@reservabot.es&pass=demo1234" class="hover:text-white transition-colors">Demo</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">API</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-6">Empresa</h3>
                    <ul class="space-y-3 text-gray-400">
                        <li><button onclick="openContactModal()" class="hover:text-white transition-colors">Contacto</button></li>
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
    <div id="contactModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Contáctanos</h3>
                <button onclick="closeContactModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>
            <form id="contactForm" onsubmit="handleContactSubmit(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                        <input type="text" name="name" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asunto</label>
                        <input type="text" name="subject" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mensaje</label>
                        <textarea name="message" rows="4" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                </div>
                <button type="submit" id="submitBtn" class="w-full mt-6 gradient-bg text-white py-3 rounded-xl font-semibold hover:shadow-lg transition-all">
                    Enviar Mensaje
                </button>
                <div id="formMessage" class="mt-4 text-center hidden"></div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Mobile menu functions
        function openMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('mobileMenuOverlay');
            const menuIcon = document.getElementById('menuIcon');
            
            menu.style.visibility = 'visible';
            overlay.classList.remove('hidden');
            
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
            
            setTimeout(() => {
                menu.style.visibility = 'hidden';
                overlay.classList.add('hidden');
            }, 300);
        }
        
        // Contact modal functions
        function openContactModal() {
            document.getElementById('contactModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeContactModal() {
            document.getElementById('contactModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        
        // Screenshot carousel
        let currentScreenshot = 0;
        const screenshotLabels = [
            'Calendario de citas',
            'Gestión de reservas pendientes',
            'Detalle de reserva'
        ];
        
        function showScreenshot(index) {
            const slides = document.querySelectorAll('.screenshot-slide');
            const dots = document.querySelectorAll('.screenshot-dot');
            const label = document.getElementById('screenshotLabel');
            
            slides.forEach(slide => slide.classList.add('hidden'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            slides[index].classList.remove('hidden');
            dots[index].classList.add('active');
            label.textContent = screenshotLabels[index];
            
            currentScreenshot = index;
        }
        
        // Auto-rotate screenshots
        setInterval(() => {
            const nextIndex = (currentScreenshot + 1) % 3;
            showScreenshot(nextIndex);
        }, 4000);
        
        // Contact form submission
        async function handleContactSubmit(event) {
            event.preventDefault();
            
            const form = event.target;
            const submitBtn = document.getElementById('submitBtn');
            const formMessage = document.getElementById('formMessage');
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';
            formMessage.classList.add('hidden');
            
            try {
                const formData = new FormData(form);
                const data = {
                    name: formData.get('name'),
                    email: formData.get('email'),
                    subject: formData.get('subject') || 'Contacto desde landing',
                    message: formData.get('message')
                };
                
                const response = await fetch('/api/contacto-handler', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    formMessage.textContent = result.message || '¡Mensaje enviado correctamente!';
                    formMessage.className = 'mt-4 text-center text-green-600';
                    formMessage.classList.remove('hidden');
                    form.reset();
                } else {
                    formMessage.textContent = result.error || 'Error al enviar el mensaje';
                    formMessage.className = 'mt-4 text-center text-red-600';
                    formMessage.classList.remove('hidden');
                }
            } catch (error) {
                formMessage.textContent = 'Error de conexión. Inténtalo de nuevo.';
                formMessage.className = 'mt-4 text-center text-red-600';
                formMessage.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar Mensaje';
            }
        }
        
        // FAQ toggle function
        function toggleFAQ(button) {
            const content = button.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('mobileMenuOverlay');
            
            menu.classList.remove('open');
            menu.style.visibility = 'hidden';
            overlay.classList.add('hidden');
            overlay.style.opacity = '0';
            
            // Show first FAQ open by default
            const firstFAQ = document.querySelector('.space-y-6 > div:first-child');
            if (firstFAQ) {
                const button = firstFAQ.querySelector('button');
                const content = firstFAQ.querySelector('div:last-child');
                const icon = firstFAQ.querySelector('i');
                
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            }
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
    </script>
</body>
</html>