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
        
    </div>
</div>

<script>

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