<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ReservaBot'; ?></title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Iconos -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="assets/css/styles.css">
    
    <link rel="icon" type="image/png" sizes="16x16"  href="/favicon-16x16.png">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#ffffff">

    <style>
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
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .sidebar-glass {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .nav-item {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .nav-item:hover::before {
            left: 100%;
        }
        
        .nav-item:hover {
            transform: translateX(4px);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%);
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-right: 3px solid #667eea;
        }
        
        .floating-header {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-3px); }
        }
        
        .mobile-menu-bg {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .mobile-menu-content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
        }
        
        .nav-icon {
            transition: all 0.3s ease;
        }
        
        .nav-item:hover .nav-icon {
            transform: scale(1.1);
            filter: drop-shadow(0 2px 4px rgba(102, 126, 234, 0.3));
        }
        
        .nav-item.active .nav-icon {
            color: #667eea;
        }
        
        .section-divider {
            position: relative;
            margin: 1rem 0;
        }
        
        .section-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 1rem;
            right: 1rem;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.3), transparent);
        }
        
        .section-label {
            background: white;
            padding: 0 0.5rem;
            margin-left: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #9CA3AF;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .user-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .notification-badge {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .mobile-nav-item {
            transition: all 0.3s ease;
        }
        
        .mobile-nav-item:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, transparent 100%);
            transform: translateX(8px);
        }
        
        .brand-glow {
            filter: drop-shadow(0 4px 8px rgba(102, 126, 234, 0.3));
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="md:ml-64 flex flex-col flex-1">
            <!-- Barra superior para móvil -->
            <div class="md:hidden glass-effect shadow-lg">
                <div class="px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center floating-header">
                        <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center mr-3 brand-glow">
                            <i class="ri-calendar-line text-white text-xl"></i>
                        </div>
                        <h1 class="text-xl font-bold gradient-text">ReservaBot</h1>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <!-- Botón nueva reserva móvil -->
                        <a href="/reserva-form" class="relative p-2 text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all shadow-md hover:shadow-lg transform hover:scale-105">
                            <i class="ri-add-line text-lg"></i>
                        </a>
                                                
                        <!-- Notificaciones móvil -->
                        <!-- <a href="/notificaciones" class="relative p-2 text-gray-600 hover:text-purple-600 transition-colors">
                            <i class="ri-notification-line text-xl"></i>
                            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full notification-badge"></span>
                        </a> -->
                        
                        <!-- Botón de menú móvil -->
                        <button id="menuButton" class="p-2 text-gray-600 hover:text-purple-600 transition-colors focus:outline-none">
                            <i class="ri-menu-3-line text-2xl"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Menú móvil mejorado -->
            <div id="mobileMenu" class="md:hidden fixed inset-0 z-50 hidden">
                <div class="mobile-menu-bg absolute inset-0" onclick="closeMobileMenu()"></div>
                <div class="mobile-menu-content absolute right-0 top-0 h-full w-80 max-w-full shadow-2xl">
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center mr-3">
                                    <i class="ri-calendar-line text-white text-lg"></i>
                                </div>
                                <div>
                                    <div class="font-bold gradient-text"><?php echo htmlspecialchars($user['negocio'] ?? 'Mi Negocio'); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?></div>
                                </div>
                            </div>
                            <button id="closeMobileMenu" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <i class="ri-close-line text-xl"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="py-4 max-h-full overflow-y-auto">
                        <nav class="space-y-1 px-4">
                            <a href="/" class="mobile-nav-item <?php echo $currentPage === 'reservas' ? 'bg-blue-50 text-blue-700 border-r-3 border-blue-500' : 'text-gray-700'; ?> flex items-center px-3 py-3 rounded-lg font-medium">
                                <i class="ri-home-line mr-3 text-lg nav-icon"></i>
                                Reservas
                            </a>
                            <a href="/dia" class="mobile-nav-item <?php echo $currentPage === 'calendario' ? 'bg-blue-50 text-blue-700 border-r-3 border-blue-500' : 'text-gray-700'; ?> flex items-center px-3 py-3 rounded-lg font-medium">
                                <i class="ri-calendar-line mr-3 text-lg nav-icon"></i>
                                Calendario
                            </a>
                            <a href="/clientes" class="mobile-nav-item <?php echo $currentPage === 'clientes' ? 'bg-blue-50 text-blue-700 border-r-3 border-blue-500' : 'text-gray-700'; ?> flex items-center px-3 py-3 rounded-lg font-medium">
                                <i class="ri-user-line mr-3 text-lg nav-icon"></i>
                                Clientes
                            </a>
                            
                            <a href="/formularios" class="mobile-nav-item <?php echo $currentPage === 'formularios' ? 'bg-blue-50 text-blue-700 border-r-3 border-blue-500' : 'text-gray-700'; ?> flex items-center px-3 py-3 rounded-lg font-medium">
                                <i class="ri-survey-fill mr-3 text-lg nav-icon"></i>
                                Formularios
                            </a>
                            
                            <a href="/whatsapp" class="mobile-nav-item <?php echo $currentPage === 'whatsapp' ? 'bg-blue-50 text-blue-700 border-r-3 border-blue-500' : 'text-gray-700'; ?> flex items-center px-3 py-3 rounded-lg font-medium">
                                <i class="ri-whatsapp-line mr-3 text-lg nav-icon text-green-500"></i>
                                WhatsApp
                                <div class="ml-auto">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-1"></span>
                                        Offline
                                    </span>
                                </div>
                            </a>
                            
                            <a href="/configuracion" class="mobile-nav-item <?php echo $currentPage === 'configuracion' ? 'bg-blue-50 text-blue-700 border-r-3 border-blue-500' : 'text-gray-700'; ?> flex items-center px-3 py-3 rounded-lg font-medium">
                                <i class="ri-settings-line mr-3 text-lg nav-icon"></i>
                                Configuración
                            </a>
                        </nav>
                        
                        <!-- Usuario móvil -->
                        <div class="mt-8 px-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center p-3 rounded-lg bg-gradient-to-r from-blue-50 to-purple-50">
                                <div class="user-avatar h-10 w-10 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                                    <?php 
                                    $user = getAuthenticatedUser();
                                    echo strtoupper(substr($user['nombre'] ?? 'U', 0, 1));
                                    ?>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($user['negocio'] ?? 'Mi Negocio'); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?></p>
                                </div>
                                <div class="relative">
                                    <button id="mobileUserMenuBtn" class="text-gray-400 hover:text-gray-600 p-1 rounded-md transition-colors">
                                        <i class="ri-more-2-line text-lg"></i>
                                    </button>
                                    
                                    <!-- Menú desplegable del usuario móvil -->
                                    <div id="mobileUserDropdown" class="hidden absolute right-0 bottom-full mb-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                                        <a href="/perfil" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors" onclick="closeMobileMenu()">
                                            <i class="ri-user-line mr-3 text-gray-400"></i>
                                            Mi Perfil
                                        </a>
                                        
                                        <a href="/configuracion" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors" onclick="closeMobileMenu()">
                                            <i class="ri-settings-line mr-3 text-gray-400"></i>
                                            Configuración
                                        </a>
                                        
                                        <div class="border-t border-gray-100 my-1"></div>
                                        
                                        <a href="/logout" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors" onclick="closeMobileMenu()">
                                            <i class="ri-logout-box-line mr-3 text-red-500"></i>
                                            Cerrar Sesión
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Información del plan -->
                            <div class="mt-3 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-purple-100 to-blue-100 text-purple-800">
                                    <i class="ri-vip-crown-line mr-1"></i>
                                    Plan <?php echo ucfirst($user['plan'] ?? 'Gratis'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barra de navegación superior para desktop (opcional) -->
            <div class="hidden md:block bg-white/80 backdrop-blur-md border-b border-gray-100 shadow-sm">
                <div class="px-6 py-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <!-- Breadcrumb mejorado -->
                            <nav class="flex items-center space-x-2 text-sm">
                                <a href="/" class="text-gray-500 hover:text-purple-600 transition-colors">
                                    <i class="ri-home-line"></i>
                                </a>
                                <i class="ri-arrow-right-s-line text-gray-400"></i>
                                <span class="text-gray-900 font-medium">
                                    <?php 
                                    $pageNames = [
                                        'reservas' => 'Reservas',
                                        'calendario' => 'Calendario',
                                        'clientes' => 'Clientes',
                                        'whatsapp' => 'WhatsApp',
                                        'formularios' => 'Formularios',
                                        'configuracion' => 'Configuración',                                        
                                        'conversaciones' => 'Conversaciones'
                                    ];
                                    echo $pageNames[$currentPage] ?? 'Reservas';
                                    ?>
                                </span>
                            </nav>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <!-- Búsqueda rápida -->
                            <!-- <div class="relative hidden lg:block">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-search-line text-gray-400"></i>
                                </div>
                                <input
                                    type="text"
                                    placeholder="Buscar reservas, clientes..."
                                    class="block w-64 pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-gray-50 text-sm transition-all"
                                >
                            </div> -->
                            
                            <!-- Botón Nueva Reserva -->
                            <a href="/reserva-form" class="btn-nueva-reserva inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white text-sm font-medium rounded-lg hover:from-purple-700 hover:to-blue-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <i class="ri-add-line mr-2 text-lg"></i>
                                Nueva Reserva
                            </a>

                            <!-- Notificaciones -->
                            <!-- <a href="/notificaciones" class="relative p-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-all">
                                <i class="ri-notification-line text-xl"></i>
                                <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full notification-badge"></span>
                            </a> -->
                            
                            <!-- Estado de WhatsApp -->
                            <!-- <div class="flex items-center space-x-2 px-3 py-1 bg-green-50 rounded-lg">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                <span class="text-sm font-medium text-green-800">WhatsApp Online</span>
                            </div> -->
                            
                            <!-- Usuario -->
                            <!-- <div class="user-avatar h-8 w-8 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                <?php 
                                $user = getAuthenticatedUser();
                                echo strtoupper(substr($user['nombre'] ?? 'U', 0, 1));
                                ?>
                            </div>
                            <div class="hidden xl:block">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['negocio'] ?? 'Mi Negocio'); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?></p>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido principal con padding mejorado -->
            <main class="flex-1 overflow-auto">
                <div class="p-4 md:p-6 lg:p-8">