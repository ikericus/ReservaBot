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
                        <a href="#precios" class="text-gray-700 hover:text-blue-600 transition-colors">Precios</a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="/login" class="text-gray-700 hover:text-blue-600 transition-colors">Iniciar Sesión</a>
                    <a href="#demo" class="btn-shine gradient-bg text-white px-6 py-2 rounded-full hover:shadow-lg transition-all">
                        Demo Gratis
                    </a>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button class="text-gray-700 hover:text-blue-600">
                        <i class="ri-menu-line text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
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
                        <!-- Reservas<span class="block text-yellow-300">Reservas</span> -->
                        con WhatsApp
                    </h1>
                    
                    <p class="text-xl lg:text-2xl text-blue-100 mb-8 leading-relaxed">
                        Reduce llamadas, mejora la experiencia del cliente y gestiona tu negocio 24/7 con nuestro sistema inteligente de reservas.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 mb-8">
                        <a href="#demo" class="btn-shine bg-white text-purple-600 px-8 py-4 rounded-full font-semibold text-lg hover:shadow-xl transition-all">
                            <i class="ri-play-circle-line mr-2"></i>
                            Ver Demo
                        </a>
                        <a href="#demo" class="glass-effect text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-white/20 transition-all">
                            <i class="ri-information-line mr-2"></i>
                            Saber Más
                        </a>
                    </div>
                    
                    <div class="flex items-center space-x-8 text-blue-100">
                        <div class="flex items-center">
                            <i class="ri-check-line text-green-300 mr-2"></i>
                            <span>Sin instalación</span>
                        </div>
                        <div class="flex items-center">
                            <i class="ri-check-line text-green-300 mr-2"></i>
                            <span>100% en español</span>
                        </div>
                        <div class="flex items-center">
                            <i class="ri-check-line text-green-300 mr-2"></i>
                            <span>Soporte 24/7</span>
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
    <section id="caracteristicas" class="py-20 bg-gray-50">
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
                            <span>Hasta 50 reservas/mes</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>1 formulario de reserva</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Calendario básico</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Gestión de clientes</span>
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
                    
                    <button class="w-full py-3 px-6 border-2 border-gray-300 rounded-full font-semibold text-gray-700 hover:border-gray-400 transition-all">
                        Empezar Gratis
                    </button>
                </div>
                
                <!-- Plan Estándar -->
                <div class="bg-white rounded-2xl p-8 shadow-xl border-2 border-blue-500 relative transform scale-105">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-blue-500 text-white px-6 py-2 rounded-full text-sm font-semibold">
                            Más Popular
                        </span>
                    </div>
                    
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Estándar</h3>
                        <p class="text-gray-600 mb-6">Para negocios activos</p>
                        <div class="mb-8">
                            <span class="text-5xl font-bold gradient-text">29€</span>
                            <span class="text-gray-600">/mes</span>
                        </div>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Hasta 300 reservas/mes</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Formularios ilimitados</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Integración WhatsApp</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Base de datos de clientes</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Confirmaciones automáticas</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-close-line text-red-500 mr-3"></i>
                            <span class="text-gray-400">Sin respuestas automáticas</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Soporte prioritario</span>
                        </li>
                    </ul>
                    
                    <button class="btn-shine w-full py-3 px-6 gradient-bg text-white rounded-full font-semibold hover:shadow-lg transition-all">
                        Probar 14 Días Gratis
                    </button>
                </div>
                
                <!-- Plan Premium -->
                <div class="bg-white rounded-2xl p-8 shadow-lg border-2 border-gray-100">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Premium</h3>
                        <p class="text-gray-600 mb-6">Automatización completa</p>
                        <div class="mb-8">
                            <span class="text-5xl font-bold text-gray-900">59€</span>
                            <span class="text-gray-600">/mes</span>
                        </div>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Reservas ilimitadas</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-3"></i>
                            <span>Todo del plan Estándar</span>
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
                    
                    <button class="w-full py-3 px-6 border-2 border-gray-300 rounded-full font-semibold text-gray-700 hover:border-gray-400 transition-all">
                        Contactar Ventas
                    </button>
                </div>
            </div>
            
            <div class="text-center mt-12">
                <p class="text-gray-600 mb-4">¿Necesitas algo diferente?</p>
                <a href="#contacto" class="text-blue-600 hover:text-blue-700 font-semibold">
                    Hablemos de un plan personalizado →
                </a>
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
                        <h3 class="text-lg font-semibold text-gray-900">¿Ofrecen soporte en español?</h3>
                        <i class="ri-arrow-down-s-line text-gray-600"></i>
                    </button>
                    <div class="mt-4">
                        <p class="text-gray-600">
                            Sí, somos una empresa española y todo nuestro soporte es en español. Entendemos las necesidades del mercado local.
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
                Únete a miles de negocios que ya han revolucionado su gestión de reservas con ReservaBot
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-8">
                <a href="#signup" class="btn-shine bg-white text-purple-600 px-8 py-4 rounded-full font-bold text-lg hover:shadow-xl transition-all">
                    <i class="ri-rocket-line mr-2"></i>
                    Empezar Prueba Gratuita
                </a>
                <a href="#contact" class="glass-effect text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-white/20 transition-all">
                    <i class="ri-phone-line mr-2"></i>
                    Hablar con un Experto
                </a>
            </div>
            
            <div class="flex flex-wrap justify-center items-center gap-8 text-blue-100">
                <div class="flex items-center">
                    <i class="ri-check-line text-green-300 mr-2"></i>
                    <span>14 días gratis</span>
                </div>
                <div class="flex items-center">
                    <i class="ri-check-line text-green-300 mr-2"></i>
                    <span>Sin tarjeta de crédito</span>
                </div>
                <div class="flex items-center">
                    <i class="ri-check-line text-green-300 mr-2"></i>
                    <span>Configuración incluida</span>
                </div>
                <div class="flex items-center">
                    <i class="ri-check-line text-green-300 mr-2"></i>
                    <span>Soporte en español</span>
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
                        <li><a href="#" class="hover:text-white transition-colors">Características</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Precios</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Demo</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">API</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-6">Soporte</h3>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Centro de Ayuda</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contacto</a></li>
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

    <!-- Scripts -->
    <script>
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
        
        // Mobile menu toggle
        const mobileMenuBtn = document.querySelector('.md\\:hidden button');
        const mobileMenu = document.createElement('div');
        mobileMenu.className = 'md:hidden bg-white border-t border-gray-200 px-4 py-2 space-y-2 hidden';
        mobileMenu.innerHTML = `
            <a href="#inicio" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Inicio</a>
            <a href="#precios" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Precios</a>
        `;
        
        if (mobileMenuBtn) {
            mobileMenuBtn.parentNode.parentNode.parentNode.appendChild(mobileMenu);
            
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
        
        // Add entrance animations on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, observerOptions);
        
        // Observe all feature cards and sections
        document.querySelectorAll('.feature-card, .grid > div, section > div').forEach(el => {
            observer.observe(el);
        });
        
        // Parallax effect for floating elements
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            
            document.querySelectorAll('.floating').forEach(element => {
                element.style.transform = `translateY(${rate}px)`;
            });
        });
        
        // Add typing effect to hero title
        const heroTitle = document.querySelector('h1');
        if (heroTitle) {
            const originalText = heroTitle.innerHTML;
            heroTitle.innerHTML = '';
            
            setTimeout(() => {
                let i = 0;
                const typeWriter = () => {
                    if (i < originalText.length) {
                        heroTitle.innerHTML += originalText.charAt(i);
                        i++;
                        setTimeout(typeWriter, 50);
                    }
                };
                typeWriter();
            }, 500);
        }
        
        // Add counter animation for stats
        const animateCounters = () => {
            const counters = document.querySelectorAll('.text-5xl');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent);
                const duration = 2000;
                const start = performance.now();
                
                const updateCounter = (currentTime) => {
                    const elapsed = currentTime - start;
                    const progress = Math.min(elapsed / duration, 1);
                    const current = Math.floor(progress * target);
                    
                    counter.textContent = current + counter.textContent.replace(/\d+/, '');
                    
                    if (progress < 1) {
                        requestAnimationFrame(updateCounter);
                    }
                };
                
                requestAnimationFrame(updateCounter);
            });
        };
        
        // Trigger counter animation when pricing section is visible
        const pricingSection = document.getElementById('precios');
        if (pricingSection) {
            const pricingObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCounters();
                        pricingObserver.unobserve(entry.target);
                    }
                });
            });
            pricingObserver.observe(pricingSection);
        }
    </script>
</body>
</html>