</main>
        </div>
    </div>
    
    <script>
    // Script común para el menú móvil
    document.addEventListener('DOMContentLoaded', function() {
        const menuButton = document.getElementById('menuButton');
        const mobileMenu = document.getElementById('mobileMenu');
        
        if (menuButton && mobileMenu) {
            menuButton.addEventListener('click', function() {
                if (mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.remove('hidden');
                } else {
                    mobileMenu.classList.add('hidden');
                }
            });
        }
        
        // Menú de usuario en sidebar
        const userMenuTrigger = document.getElementById('userMenuTrigger');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        const userMenuIcon = document.getElementById('userMenuIcon');
        
        if (userMenuTrigger && userDropdownMenu) {
            userMenuTrigger.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (userDropdownMenu.classList.contains('hidden')) {
                    userDropdownMenu.classList.remove('hidden');
                    userMenuIcon.classList.add('rotate-180');
                } else {
                    userDropdownMenu.classList.add('hidden');
                    userMenuIcon.classList.remove('rotate-180');
                }
            });
            
            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (!userMenuTrigger.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                    userDropdownMenu.classList.add('hidden');
                    userMenuIcon.classList.remove('rotate-180');
                }
            });
        }
        
        // Botón de ayuda
        const helpBtn = document.getElementById('helpBtn');
        if (helpBtn) {
            helpBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showHelpDialog();
            });
        }
    });
    
    // Función para mostrar diálogo de ayuda
    function showHelpDialog() {
        const helpContent = `
            <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <div class="flex items-center mb-4">
                        <i class="ri-question-line text-blue-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900">Centro de Ayuda</h3>
                    </div>
                    
                    <div class="space-y-4 mb-6">
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <h4 class="font-medium text-blue-900 mb-2">Recursos Disponibles:</h4>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• Documentación completa en línea</li>
                                <li>• Videos tutoriales paso a paso</li>
                                <li>• Soporte por email 24/7</li>
                                <li>• Chat en vivo (horario comercial)</li>
                            </ul>
                        </div>
                        
                        <div class="text-sm text-gray-600">
                            <p><strong>¿Necesitas ayuda inmediata?</strong></p>
                            <p>Envíanos un email a <a href="mailto:soporte@reservabot.es" class="text-blue-600 hover:underline">soporte@reservabot.es</a> y te responderemos en menos de 24 horas.</p>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3">
                        <a href="mailto:soporte@reservabot.es" class="flex-1 bg-blue-600 text-white text-center py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="ri-mail-line mr-1"></i>
                            Contactar Soporte
                        </a>
                        <button onclick="closeHelpDialog()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', helpContent);
    }
    
    // Función para cerrar diálogo de ayuda
    function closeHelpDialog() {
        const dialog = document.querySelector('.fixed.inset-0.bg-black.bg-opacity-50');
        if (dialog) {
            dialog.remove();
        }
    }
    </script>
    
    <?php if (isset($pageScript)): ?>
    <script src="assets/js/<?php echo $pageScript; ?>.js"></script>
    <?php endif; ?>
</body>
</html>