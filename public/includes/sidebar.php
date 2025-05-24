<!-- Sidebar -->
<div class="hidden md:flex md:flex-col md:w-64 md:fixed md:inset-y-0 bg-white shadow-md">
    <div class="flex items-center justify-center h-16 bg-blue-600">
        <i class="ri-calendar-line text-white text-2xl mr-2"></i>
        <h1 class="text-xl font-bold text-white">ReservaBot</h1>
    </div>
    <div class="flex flex-col flex-grow pt-5">
        <nav class="flex-1 px-2 space-y-1">
            <a href="/" class="<?php echo $currentPage === 'dashboard' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                <i class="ri-home-line mr-3 h-5 w-5 <?php echo $currentPage === 'dashboard' ? 'text-blue-500' : 'text-gray-400'; ?>"></i>
                Reservas
            </a>
            <a href="/calendar" class="<?php echo $currentPage === 'calendar' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                <i class="ri-calendar-line mr-3 h-5 w-5 <?php echo $currentPage === 'calendar' ? 'text-blue-500' : 'text-gray-400'; ?>"></i>
                Calendario
            </a>
            
            <!-- NUEVAS OPCIONES DE MENÚ PARA WHATSAPP -->
            <div class="pt-4 mb-1">
                <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    WhatsApp
                </p>
            </div>
            
            <a href="/whatsapp" class="<?php echo $currentPage === 'whatsapp' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                <i class="ri-whatsapp-line mr-3 h-5 w-5 <?php echo $currentPage === 'whatsapp' ? 'text-blue-500' : 'text-gray-400'; ?>"></i>
                Conexión
            </a>
            
            <a href="/autorespuestas" class="<?php echo $currentPage === 'autorespuestas' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                <i class="ri-message-2-line mr-3 h-5 w-5 <?php echo $currentPage === 'autorespuestas' ? 'text-blue-500' : 'text-gray-400'; ?>"></i>
                Respuestas Auto
            </a>
            
            <a href="/mensajes" class="<?php echo $currentPage === 'mensajes' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                <i class="ri-chat-history-line mr-3 h-5 w-5 <?php echo $currentPage === 'mensajes' ? 'text-blue-500' : 'text-gray-400'; ?>"></i>
                Historial
            </a>
            <!-- FIN DE LAS NUEVAS OPCIONES -->
            
            <div class="pt-4 mb-1">
                <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Sistema
                </p>
            </div>
            
            <a href="/formularios" class="<?php echo $currentPage === 'formularios' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                <i class="ri-survey-fill mr-3 h-5 w-5 <?php echo $currentPage === 'formularios' ? 'text-blue-500' : 'text-gray-400'; ?>"></i>
                Formularios
            </a>
            <a href="/config" class="<?php echo $currentPage === 'config' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                <i class="ri-settings-line mr-3 h-5 w-5 <?php echo $currentPage === 'config' ? 'text-blue-500' : 'text-gray-400'; ?>"></i>
                Configuración
            </a>
        </nav>
    </div>
    <div class="p-4 border-t border-gray-200">
        <div class="flex items-center">
            <img class="h-8 w-8 rounded-full" src="https://via.placeholder.com/32" alt="Avatar">
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-700">Admin</p>
                <p class="text-xs text-gray-500">Ver perfil</p>
            </div>
        </div>
    </div>
</div>