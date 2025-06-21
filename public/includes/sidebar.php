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

// Obtener estado de WhatsApp
$whatsappStatus = 'disconnected';
$whatsappStatusText = 'Desconectado';
try {
    $stmt = getPDO()->prepare('SELECT status FROM whatsapp_config WHERE usuario_id = ?');
    $stmt->execute([$userId]);
    $whatsappConfig = $stmt->fetch();
    
    if ($whatsappConfig) {
        $whatsappStatus = $whatsappConfig['status'];
        $statusLabels = [
            'connected' => 'Conectado',
            'ready' => 'Conectado',
            'connecting' => 'Conectando...',
            'waiting_qr' => 'Esperando QR...',
            'disconnected' => 'Desconectado'
        ];
        $whatsappStatusText = $statusLabels[$whatsappStatus] ?? 'Desconectado';
    }
} catch (PDOException $e) {
    error_log('Error obteniendo estado WhatsApp en sidebar: ' . $e->getMessage());
}

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
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium transition-all duration-300 
                                   <?php 
                                   $statusClasses = [
                                       'connected' => 'bg-green-100 text-green-800',
                                       'ready' => 'bg-green-100 text-green-800',
                                       'connecting' => 'bg-yellow-100 text-yellow-800',
                                       'waiting_qr' => 'bg-yellow-100 text-yellow-800',
                                       'disconnected' => 'bg-gray-100 text-gray-600'
                                   ];
                                   echo $statusClasses[$whatsappStatus] ?? 'bg-gray-100 text-gray-600';
                                   ?>" 
                               id="whatsapp-status-indicator"
                               data-status="<?php echo $whatsappStatus; ?>">
                            <span class="w-1.5 h-1.5 rounded-full mr-1 transition-colors duration-300
                                        <?php 
                                        $dotClasses = [
                                            'connected' => 'bg-green-500 animate-pulse',
                                            'ready' => 'bg-green-500 animate-pulse',
                                            'connecting' => 'bg-yellow-500 animate-pulse',
                                            'waiting_qr' => 'bg-yellow-500 animate-pulse',
                                            'disconnected' => 'bg-gray-400'
                                        ];
                                        echo $dotClasses[$whatsappStatus] ?? 'bg-gray-400';
                                        ?>" 
                                  id="whatsapp-status-dot"></span>
                            <span id="whatsapp-status-text"><?php echo $whatsappStatusText; ?></span>
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

<script>
// Gestor de estado de WhatsApp en el sidebar
class SidebarWhatsAppStatus {
    constructor() {
        this.statusIndicator = document.getElementById('whatsapp-status-indicator');
        this.statusDot = document.getElementById('whatsapp-status-dot');
        this.statusText = document.getElementById('whatsapp-status-text');
        this.currentStatus = this.statusIndicator?.dataset.status || 'disconnected';
        this.updateInterval = null;
        
        this.init();
    }

    init() {
        // Solo verificar estado si no estamos en la página de WhatsApp (para evitar duplicar requests)
        if (window.location.pathname !== '/whatsapp') {
            this.startStatusCheck();
        }
        
        // Escuchar eventos globales de cambio de estado desde la página de WhatsApp
        window.addEventListener('whatsappStatusChanged', (event) => {
            this.updateStatus(event.detail.status);
        });
    }

    startStatusCheck() {
        // Verificar cada 30 segundos (menos frecuente que en la página principal)
        this.updateInterval = setInterval(() => this.checkStatus(), 30000);
    }

    stopStatusCheck() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }

    async checkStatus() {
        try {
            const response = await fetch('/api/whatsapp-status');
            const data = await response.json();
            
            if (data.success && data.status !== this.currentStatus) {
                this.updateStatus(data.status);
            }
        } catch (error) {
            console.error('Error verificando estado WhatsApp en sidebar:', error);
        }
    }

    updateStatus(newStatus) {
        this.currentStatus = newStatus;
        
        if (!this.statusIndicator || !this.statusDot || !this.statusText) return;
        
        const statusConfig = {
            ready: {
                classes: 'bg-green-100 text-green-800',
                dotClasses: 'bg-green-500 animate-pulse',
                text: 'Conectado'
            },
            connected: {
                classes: 'bg-green-100 text-green-800',
                dotClasses: 'bg-green-500 animate-pulse',
                text: 'Conectado'
            },
            connecting: {
                classes: 'bg-yellow-100 text-yellow-800',
                dotClasses: 'bg-yellow-500 animate-pulse',
                text: 'Conectando...'
            },
            waiting_qr: {
                classes: 'bg-yellow-100 text-yellow-800',
                dotClasses: 'bg-yellow-500 animate-pulse',
                text: 'Esperando QR...'
            },
            disconnected: {
                classes: 'bg-gray-100 text-gray-600',
                dotClasses: 'bg-gray-400',
                text: 'Desconectado'
            }
        };
        
        const config = statusConfig[newStatus] || statusConfig.disconnected;
        
        // Actualizar clases del indicador principal
        this.statusIndicator.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium transition-all duration-300 ' + config.classes;
        
        // Actualizar punto de estado
        this.statusDot.className = 'w-1.5 h-1.5 rounded-full mr-1 transition-colors duration-300 ' + config.dotClasses;
        
        // Actualizar texto
        this.statusText.textContent = config.text;
        
        // Actualizar data attribute
        this.statusIndicator.dataset.status = newStatus;
        
        console.log('Estado WhatsApp actualizado en sidebar:', newStatus);
    }

    destroy() {
        this.stopStatusCheck();
    }
}

// Gestor del menú desplegable de usuario
class UserDropdownMenu {
    constructor() {
        this.trigger = document.getElementById('userMenuTrigger');
        this.menu = document.getElementById('userDropdownMenu');
        this.icon = document.getElementById('userMenuIcon');
        this.isOpen = false;
        
        this.init();
    }

    init() {
        if (!this.trigger || !this.menu) return;
        
        // Evento de clic en el trigger
        this.trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggle();
        });
        
        // Cerrar el menú al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!this.trigger.contains(e.target) && !this.menu.contains(e.target)) {
                this.close();
            }
        });
        
        // Cerrar con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
        
        // Prevenir que el menú se cierre al hacer clic dentro del mismo
        this.menu.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        if (this.isOpen) return;
        
        this.menu.classList.remove('hidden');
        this.menu.classList.add('animate-fadeIn');
        this.icon.style.transform = 'rotate(180deg)';
        this.isOpen = true;
        
        // Añadir animación de entrada
        setTimeout(() => {
            this.menu.classList.remove('animate-fadeIn');
        }, 200);
    }

    close() {
        if (!this.isOpen) return;
        
        this.menu.classList.add('animate-fadeOut');
        this.icon.style.transform = 'rotate(0deg)';
        this.isOpen = false;
        
        // Ocultar después de la animación
        setTimeout(() => {
            this.menu.classList.add('hidden');
            this.menu.classList.remove('animate-fadeOut');
        }, 150);
    }
}

// Inicializar cuando el DOM esté listo
// Solución súper simple - reemplaza la inicialización del menú de usuario:
// SOLUCIÓN MÁS SIMPLE Y DEFINITIVA - Reemplaza todo el JavaScript del menú:

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar WhatsApp status
    window.sidebarWhatsAppStatus = new SidebarWhatsAppStatus();
    
    // ========== MENÚ DE USUARIO - SÚPER SIMPLE ==========
    const userTrigger = document.getElementById('userMenuTrigger');
    const userMenu = document.getElementById('userDropdownMenu');
    const userIcon = document.getElementById('userMenuIcon');
    
    if (userTrigger && userMenu && userIcon) {
        let isMenuOpen = false;
        
        // Toggle del menú
        function toggleMenu() {
            isMenuOpen = !isMenuOpen;
            if (isMenuOpen) {
                userMenu.style.display = 'block';
                userMenu.classList.remove('hidden');
                userIcon.style.transform = 'rotate(180deg)';
            } else {
                userMenu.style.display = 'none';
                userMenu.classList.add('hidden');
                userIcon.style.transform = 'rotate(0deg)';
            }
        }
        
        // Cerrar menú
        function closeMenu() {
            if (isMenuOpen) {
                isMenuOpen = false;
                userMenu.style.display = 'none';
                userMenu.classList.add('hidden');
                userIcon.style.transform = 'rotate(0deg)';
            }
        }
        
        // Click en el trigger
        userTrigger.onclick = function(e) {
            e.stopPropagation();
            toggleMenu();
        };
        
        // Click fuera para cerrar
        document.onclick = function(e) {
            if (!userTrigger.contains(e.target) && !userMenu.contains(e.target)) {
                closeMenu();
            }
        };
        
        // Escape para cerrar
        document.onkeydown = function(e) {
            if (e.key === 'Escape') {
                closeMenu();
            }
        };
        
        // Prevenir cierre al click en el menú
        userMenu.onclick = function(e) {
            e.stopPropagation();
        };
    }
    
    // Manejar botón de ayuda
    const helpBtn = document.getElementById('helpBtn');
    if (helpBtn) {
        helpBtn.onclick = function(e) {
            e.preventDefault();
            console.log('Abriendo centro de ayuda...');
        };
    }
});

// Cleanup al salir
window.addEventListener('beforeunload', () => {
    window.sidebarWhatsAppStatus?.destroy();
});

// Función global para actualizar desde otras páginas
window.updateSidebarWhatsAppStatus = function(status) {
    window.sidebarWhatsAppStatus?.updateStatus(status);
    
    // Emitir evento para mantener sincronización
    window.dispatchEvent(new CustomEvent('whatsappStatusChanged', {
        detail: { status: status }
    }));
};

// Agregar estilos CSS para las animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }
    
    .animate-fadeIn {
        animation: fadeIn 0.2s ease-out forwards;
    }
    
    .animate-fadeOut {
        animation: fadeOut 0.15s ease-in forwards;
    }
    
    #userMenuIcon {
        transition: transform 0.2s ease;
    }
    
    #userDropdownMenu {
        z-index: 1000 !important;
    }
`;
document.head.appendChild(style);
</script>