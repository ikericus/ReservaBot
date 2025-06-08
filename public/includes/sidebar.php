<?php
// Obtener estadísticas en tiempo real
$user = getAuthenticatedUser();
$userId = $user['id'];

// Inicializar estadísticas
$estadisticas = [
    'hoy_confirmadas' => 0,
    'hoy_pendientes' => 0,
    'hoy_total' => 0,
    'semana_total' => 0,
    'mes_total' => 0,
    'proxima_reserva' => null
];

try {    
    // Reservas confirmadas de hoy
    $stmt = getPDO()->prepare("
        SELECT COUNT(*) 
        FROM reservas 
        WHERE usuario_id = ? 
        AND DATE(fecha) = CURDATE() 
        AND estado = 'confirmada'
    ");
    $stmt->execute([$userId]);
    $estadisticas['hoy_confirmadas'] = (int)$stmt->fetchColumn();
    
    // Reservas pendientes de hoy
    $stmt = getPDO()->prepare("
        SELECT COUNT(*) 
        FROM reservas 
        WHERE usuario_id = ? 
        AND DATE(fecha) = CURDATE() 
        AND estado = 'pendiente'
    ");
    $stmt->execute([$userId]);
    $estadisticas['hoy_pendientes'] = (int)$stmt->fetchColumn();
    
    // Total de hoy
    $estadisticas['hoy_total'] = $estadisticas['hoy_confirmadas'] + $estadisticas['hoy_pendientes'];
    
    // Reservas de esta semana
    $stmt = getPDO()->prepare("
        SELECT COUNT(*) 
        FROM reservas 
        WHERE usuario_id = ? 
        AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)
        AND estado IN ('confirmada', 'pendiente')
    ");
    $stmt->execute([$userId]);
    $estadisticas['semana_total'] = (int)$stmt->fetchColumn();
    
    // Reservas de este mes
    $stmt = getPDO()->prepare("
        SELECT COUNT(*) 
        FROM reservas 
        WHERE usuario_id = ? 
        AND YEAR(fecha) = YEAR(CURDATE()) 
        AND MONTH(fecha) = MONTH(CURDATE())
        AND estado IN ('confirmada', 'pendiente')
    ");
    $stmt->execute([$userId]);
    $estadisticas['mes_total'] = (int)$stmt->fetchColumn();
    
    // Próxima reserva confirmada
    $stmt = getPDO()->prepare("
        SELECT nombre, fecha, hora 
        FROM reservas 
        WHERE usuario_id = ? 
        AND CONCAT(fecha, ' ', hora) > NOW()
        AND estado = 'confirmada'
        ORDER BY fecha, hora 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $proximaReserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($proximaReserva) {
        $estadisticas['proxima_reserva'] = [
            'nombre' => $proximaReserva['nombre'],
            'fecha' => $proximaReserva['fecha'],
            'hora' => substr($proximaReserva['hora'], 0, 5)
        ];
    }
    
} catch (PDOException $e) {
    error_log('Error obteniendo estadísticas del sidebar: ' . $e->getMessage());
}
?>

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
                <a href="/" class="nav-item <?php echo $currentPage === 'reservas' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-home-line nav-icon mr-4 h-5 w-5 <?php echo $currentPage === 'reservas' ? 'text-purple-600' : 'text-gray-400'; ?>"></i>
                    <span>Reservas</span>
                    <?php if ($currentPage === 'reservas'): ?>
                        <div class="ml-auto w-2 h-2 bg-purple-600 rounded-full notification-badge"></div>
                    <?php endif; ?>
                    <?php if ($estadisticas['hoy_pendientes'] > 0): ?>
                        <span class="ml-auto bg-red-100 text-red-800 text-xs font-medium px-2 py-0.5 rounded-full">
                            <?php echo $estadisticas['hoy_pendientes']; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <a href="/calendario" class="nav-item <?php echo $currentPage === 'calendario' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-calendar-line nav-icon mr-4 h-5 w-5 <?php echo $currentPage === 'calendario' ? 'text-purple-600' : 'text-gray-400'; ?>"></i>
                    <span>Calendario</span>
                </a>

                <a href="/clientes" class="nav-item <?php echo $currentPage === 'clientes' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-user-line nav-icon mr-4 h-5 w-5 <?php echo $currentPage === 'clientes' ? 'text-purple-600' : 'text-gray-400'; ?>"></i>
                    <span>Clientes</span>
                </a>

                <a href="/formularios" class="nav-item <?php echo $currentPage === 'formularios' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-survey-fill nav-icon mr-4 h-5 w-5 <?php echo $currentPage === 'formularios' ? 'text-purple-600' : 'text-gray-400'; ?>"></i>
                    <span>Formularios</span>
                </a>

                <a href="/whatsapp" class="nav-item <?php echo $currentPage === 'whatsapp' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-whatsapp-line nav-icon mr-4 h-5 w-5 text-green-500"></i>
                    <span>WhatsApp</span>
                    <div class="ml-auto">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600" id="whatsapp-status-indicator">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-1"></span>
                            Offline
                        </span>
                    </div>
                </a>

                <a href="/configuracion" class="nav-item <?php echo $currentPage === 'configuracion' ? 'active text-purple-700 font-semibold' : 'text-gray-700 hover:text-purple-600'; ?> group flex items-center px-4 py-3 text-sm font-medium rounded-xl">
                    <i class="ri-settings-line nav-icon mr-4 h-5 w-5 <?php echo $currentPage === 'configuracion' ? 'text-purple-600' : 'text-gray-400'; ?>"></i>
                    <span>Configuración</span>
                </a>
            </div>
        </nav>

        <!-- Tarjetas de estadísticas mejoradas -->
        <div class="mx-4 mt-6 space-y-3">
            <!-- Estadísticas de hoy -->
            <div class="p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-100">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <i class="ri-calendar-check-line text-blue-600 mr-2"></i>
                        <span class="text-sm font-medium text-gray-700">Hoy</span>
                    </div>
                    <?php if ($estadisticas['hoy_pendientes'] > 0): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <?php echo $estadisticas['hoy_pendientes']; ?> pendientes
                        </span>
                    <?php endif; ?>
                </div>
                <div class="text-2xl font-bold text-gray-900"><?php echo $estadisticas['hoy_confirmadas']; ?></div>
                <div class="text-xs text-gray-500">
                    <?php if ($estadisticas['hoy_total'] > $estadisticas['hoy_confirmadas']): ?>
                        de <?php echo $estadisticas['hoy_total']; ?> reservas totales
                    <?php else: ?>
                        reservas confirmadas
                    <?php endif; ?>
                </div>
            </div>

            <!-- Próxima reserva -->
            <?php if ($estadisticas['proxima_reserva']): ?>
                <div class="p-3 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-100">
                    <div class="flex items-center mb-2">
                        <i class="ri-time-line text-green-600 mr-2"></i>
                        <span class="text-sm font-medium text-gray-700">Próxima</span>
                    </div>
                    <div class="text-sm font-semibold text-gray-900">
                        <?php echo htmlspecialchars($estadisticas['proxima_reserva']['nombre']); ?>
                    </div>
                    <div class="text-xs text-gray-500">
                        <?php 
                        $fecha = date('d/m', strtotime($estadisticas['proxima_reserva']['fecha']));
                        $hora = $estadisticas['proxima_reserva']['hora'];
                        echo "$fecha a las $hora";
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Resumen semanal/mensual (compacto) -->
            <div class="p-3 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border border-purple-100">
                <div class="flex items-center mb-2">
                    <i class="ri-bar-chart-line text-purple-600 mr-2"></i>
                    <span class="text-sm font-medium text-gray-700">Resumen</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <div class="font-semibold text-gray-900"><?php echo $estadisticas['semana_total']; ?></div>
                        <div class="text-gray-500">Esta semana</div>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900"><?php echo $estadisticas['mes_total']; ?></div>
                        <div class="text-gray-500">Este mes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

      <!-- Panel de usuario mejorado -->
    <div class="p-4 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50">
        <div class="relative">
            <!-- Usuario principal -->
            <div id="userMenuTrigger" class="flex items-center group cursor-pointer p-3 rounded-xl hover:bg-white hover:shadow-md transition-all duration-300">
                <div class="user-avatar h-10 w-10 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                    <?php 
                    $user = getAuthenticatedUser();
                    echo strtoupper(substr($user['nombre'] ?? 'U', 0, 1));
                    ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($user['negocio'] ?? 'Mi Negocio'); ?></p>
                    <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?></p>
                </div>
                <div class="flex items-center">
                    <i class="ri-arrow-up-s-line text-gray-400 transition-transform group-hover:rotate-180" id="userMenuIcon"></i>
                </div>
            </div>
            
            <!-- Menú desplegable del usuario -->
            <div id="userDropdownMenu" class="hidden absolute bottom-full left-0 right-0 mb-2 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                <a href="/perfil" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                    <i class="ri-user-line mr-3 text-gray-400"></i>
                    Mi Perfil
                </a>
                
                <a href="/configuracion" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                    <i class="ri-settings-line mr-3 text-gray-400"></i>
                    Configuración
                </a>
                
                <div class="border-t border-gray-100 my-1"></div>
                
                <a href="#" id="helpBtn" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                    <i class="ri-question-line mr-3 text-gray-400"></i>
                    Centro de Ayuda
                </a>
                
                <a href="mailto:soporte@reservabot.es" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                    <i class="ri-mail-line mr-3 text-gray-400"></i>
                    Contactar Soporte
                </a>
                
                <div class="border-t border-gray-100 my-1"></div>
                
                <a href="/logout" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                    <i class="ri-logout-box-line mr-3 text-red-500"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>
        
        <!-- Indicador de plan -->
        <div class="mt-3 text-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-purple-100 to-blue-100 text-purple-800">
                <i class="ri-vip-crown-line mr-1"></i>
                Plan <?php echo ucfirst($user['plan'] ?? 'Gratis'); ?>
            </span>
        </div>
    </div>
</div>