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
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="md:ml-64 flex flex-col flex-1">
            <!-- Barra superior para móvil -->
            <div class="md:hidden bg-blue-600 py-2 px-4 flex items-center justify-between shadow-sm">
                <div class="flex items-center">
                    <i class="ri-calendar-line text-white text-2xl"></i>
                    <h1 class="ml-2 text-xl font-bold text-white">ReservaBot</h1>
                </div>
                <button id="menuButton" class="text-white focus:outline-none">
                    <i class="ri-menu-line text-2xl"></i>
                </button>
            </div>

        <!-- Menú móvil -->
        <div id="mobileMenu" class="md:hidden bg-white shadow-md hidden">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/" class="<?php echo $currentPage === 'dashboard' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50'; ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="ri-home-line mr-2"></i>
                    Reservas
                </a>
                <a href="/calendar.php" class="<?php echo $currentPage === 'calendar' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50'; ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="ri-calendar-line mr-2"></i>
                    Calendario
                </a>
                
                <!-- NUEVAS OPCIONES DE MENÚ PARA WHATSAPP -->
                <div class="pt-2 mb-1">
                    <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        WhatsApp
                    </p>
                </div>
                
                <a href="/whatsapp.php" class="<?php echo $currentPage === 'whatsapp' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50'; ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="ri-whatsapp-line mr-2"></i>
                    Conexión WhatsApp
                </a>
                
                <a href="/autorespuestas.php" class="<?php echo $currentPage === 'autorespuestas' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50'; ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="ri-message-2-line mr-2"></i>
                    Respuestas Automáticas
                </a>
                
                <a href="/mensajes.php" class="<?php echo $currentPage === 'mensajes' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50'; ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="ri-chat-history-line mr-2"></i>
                    Historial de Mensajes
                </a>
                <!-- FIN DE LAS NUEVAS OPCIONES -->
                
                <div class="pt-2 mb-1">
                    <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Sistema
                    </p>
                </div>
                
                <a href="/config.php" class="<?php echo $currentPage === 'config' ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50'; ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="ri-settings-line mr-2"></i>
                    Configuración
                </a>
            </div>
        </div>