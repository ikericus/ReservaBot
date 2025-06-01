<!-- Sidebar Mejorado -->
<div class="hidden md:flex md:flex-col md:w-64 md:fixed md:inset-y-0 sidebar-glass shadow-2xl">
    <!-- Header del sidebar con gradiente -->
    <div class="gradient-bg p-6 relative overflow-hidden">
        <!-- Elementos decorativos de fondo -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-16 -mt-16"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-12 -mb-12"></div>
        
        <div class="relative flex items-center floating-header">
            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center mr-4 brand-glow">
                <i class="ri-calendar-line text-white text-2xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white">ReservaBot</h1>
                <p class="text-blue-100 text-sm opacity-90">Panel de Control</p>
            </div>
        </div>
    </div>

    <!-- Navegación principal -->
    <div class="flex-1 flex flex-col pt-6 pb-4 overflow-y-auto">
        <nav class="flex-1 px-4 space-y-2">
            <!-- Sección principal -->
            <div class="space-y-1">
                <a href="/" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-home-line nav-icon mr-4 h-5 w-5 <?php echo $currentPage === 'dashboard' ? 'text-purple-600' : 'text-gray-400'; ?>"></i>
                    <span>Reservas</span>
                    <?php if ($currentPage === 'dashboard'): ?>
                        <div class="ml-auto w-2 h-2 bg-purple-600 rounded-full notification-badge"></div>
                    <?php endif; ?>
                </a>

                <a href="/calendario" class="nav-item <?php echo $currentPage === 'calendario' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-calendar-line nav-icon mr-4 h-5 w-5 <?php echo $currentPage === 'calendar' ? 'text-purple-600' : 'text-gray-400'; ?>"></i>
                    <span>Calendario</span>
                </a>

                <a href="/clientes" class="nav-item <?php echo $currentPage === 'clientes' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-user-line nav-icon mr-4 h-5 w-5 <?php echo $currentPage === 'clientes' ? 'text-purple-600' : 'text-gray-400'; ?>"></i>
                    <span>Clientes</span>
                </a>
            </div>

            <!-- Sección WhatsApp -->
            <div class="section-divider">
                <div class="section-label">
                    <i class="ri-whatsapp-line text-green-500 mr-1"></i>
                    WhatsApp
                </div>
            </div>

            <div class="space-y-1">
                <a href="/whatsapp" class="nav-item <?php echo $currentPage === 'whatsapp' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-whatsapp-line nav-icon mr-4 h-5 w-5 text-green-500"></i>
                    <span>Conexión</span>
                    <div class="ml-auto">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1"></span>
                            Online
                        </span>
                    </div>
                </a>

                <a href="/autorespuestas" class="nav-item <?php echo $currentPage === 'autorespuestas' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-message-2-line nav-icon mr-4 h-5 w-5 <?php echo $currentPage === 'autorespuestas' ? 'text-purple-600' : 'text-gray-400'; ?>"></i>
                    <span>Respuestas Auto</span>
                </a>

                <a href="/mensajes" class="nav-item <?php echo $currentPage === 'mensajes' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-chat-history-line nav-icon mr-4 h-5 w-5 <?php echo $currentPage === 'mensajes' ? 'text-purple-600' : 'text-gray-400'; ?>"></i>
                    <span>Historial</span>
                    <div class="ml-auto">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            24
                        </span>
                    </div>
                </a>
            </div>

            <!-- Sección Sistema -->
            <div class="section-divider">
                <div class="section-label">
                    <i class="ri-settings-4-line mr-1"></i>
                    Sistema
                </div>
            </div>

            <div class="space-y-1">
                <a href="/formularios" class="nav-item <?php echo $currentPage === 'formularios' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-survey-fill nav-icon mr-4 h-5 w-5 <?php echo $currentPage === 'formularios' ? 'text-purple-600' : 'text-gray-400'; ?>"></i>
                    <span>Formularios</span>
                </a>

                <a href="/configuracion" class="nav-item <?php echo $currentPage === 'config' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-settings-line nav-icon mr-4 h-5 w-5 <?php echo $currentPage === 'config' ? 'text-purple-600' : 'text-gray-400'; ?>"></i>
                    <span>Configuración</span>
                </a>
            </div>
        </nav>

        <!-- Tarjeta de estadísticas rápidas -->
        <div class="mx-4 mt-6 p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-100">
            <div class="flex items-center mb-2">
                <i class="ri-calendar-check-line text-blue-600 mr-2"></i>
                <span class="text-sm font-medium text-gray-700">Hoy</span>
            </div>
            <div class="text-2xl font-bold text-gray-900">8</div>
            <div class="text-xs text-gray-500">reservas confirmadas</div>
        </div>
    </div>

    <!-- Panel de usuario mejorado -->
    <div class="p-4 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50">
        <div class="flex items-center group cursor-pointer p-3 rounded-xl hover:bg-white hover:shadow-md transition-all duration-300">
            <div class="user-avatar h-10 w-10 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                A
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">Admin</p>
                <p class="text-xs text-gray-500 truncate">admin@reservabot.com</p>
            </div>
            <div class="flex items-center space-x-2">
                <button class="p-1 text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="ri-notification-line"></i>
                </button>
                <button class="p-1 text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="ri-settings-line"></i>
                </button>
                <button class="p-1 text-gray-400 hover:text-red-600 transition-colors">
                    <i class="ri-logout-box-line"></i>
                </button>
            </div>
        </div>
        
        <!-- Indicador de plan -->
        <div class="mt-3 text-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-purple-100 to-blue-100 text-purple-800">
                <i class="ri-vip-crown-line mr-1"></i>
                Plan Premium
            </span>
        </div>
    </div>
</div>