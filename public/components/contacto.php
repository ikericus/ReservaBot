<?php
/**
 * Componente Modal de Contacto Reutilizable
 * Archivo: public/components/contact-modal.php
 * 
 * Uso: include 'components/contact-modal.php';
 */
?>

<!-- Contact Modal -->
<div id="contactModal" class="contact-modal-overlay fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" style="opacity: 0; pointer-events: none; transition: opacity 0.25s ease;">
    <div class="contact-modal-content bg-white rounded-2xl p-8 max-w-md w-full transform transition-all" style="transform: scale(0.95);">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900">Contáctanos</h3>
            <button onclick="closeContactModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        
        <form id="contactForm" class="space-y-4">
            <div>
                <label for="contactName" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                <input type="text" id="contactName" name="name" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" 
                       placeholder="Tu nombre">
            </div>
            
            <div>
                <label for="contactEmail" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" id="contactEmail" name="email" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" 
                       placeholder="tu@email.com">
            </div>
            
            <div>
                <label for="contactSubject" class="block text-sm font-medium text-gray-700 mb-2">Asunto</label>
                <select id="contactSubject" name="subject" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    <option value="consulta">Consulta general</option>
                    <option value="demo">Solicitar demo personalizada</option>
                    <option value="soporte">Soporte técnico</option>
                    <option value="ventas">Información de ventas</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            
            <div>
                <label for="contactMessage" class="block text-sm font-medium text-gray-700 mb-2">Mensaje</label>
                <textarea id="contactMessage" name="message" rows="4" required 
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none" 
                          placeholder="Cuéntanos en qué podemos ayudarte..."></textarea>
            </div>
            
            <button type="submit" 
                    class="w-full gradient-bg text-white py-3 px-4 rounded-xl font-semibold hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="ri-send-plane-line mr-2"></i>
                <span>Enviar Mensaje</span>
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                O escríbenos directamente a:
                <a href="mailto:contacto@reservabot.es" class="text-purple-600 font-medium hover:text-purple-700 transition-colors">contacto@reservabot.es</a>
            </p>
        </div>
    </div>
</div>

<!-- CSS específico del modal (si no está ya incluido) -->
<style>
.contact-modal-overlay.show {
    opacity: 1 !important;
    pointer-events: auto !important;
}

.contact-modal-overlay.show .contact-modal-content {
    transform: scale(1) !important;
}

.gradient-bg {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>

<!-- JavaScript del modal -->
<script>
// Variables globales del modal
let contactModalOpen = false;

// Funciones del modal de contacto
function openContactModal() {
    const modal = document.getElementById('contactModal');
    const content = modal.querySelector('.contact-modal-content');
    
    modal.style.display = 'flex';
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    contactModalOpen = true;
    
    // Focus en el primer campo
    setTimeout(() => {
        document.getElementById('contactName').focus();
    }, 100);
}

function closeContactModal() {
    const modal = document.getElementById('contactModal');
    
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
    contactModalOpen = false;
    
    // Ocultar completamente después de la animación
    setTimeout(() => {
        modal.style.display = 'none';
    }, 250);
}

// Cerrar modal al hacer clic fuera
document.getElementById('contactModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeContactModal();
    }
});

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && contactModalOpen) {
        closeContactModal();
    }
});

// Manejar envío del formulario
document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('span');
    const btnIcon = submitBtn.querySelector('i');
    const originalText = btnText.textContent;
    
    // Mostrar estado de carga
    btnIcon.className = 'ri-loader-4-line animate-spin mr-2';
    btnText.textContent = 'Enviando...';
    submitBtn.disabled = true;
    
    const formData = {
        name: document.getElementById('contactName').value,
        email: document.getElementById('contactEmail').value,
        subject: document.getElementById('contactSubject').value,
        message: document.getElementById('contactMessage').value
    };
    
    try {
        const response = await fetch('/api/contacto-handler', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            // Éxito
            showContactMessage('success', result.message);
            closeContactModal();
            this.reset();
        } else {
            // Error del servidor
            const errorMsg = result.errors ? result.errors.join('\n') : result.error;
            showContactMessage('error', errorMsg);
        }
        
    } catch (error) {
        console.error('Error:', error);
        showContactMessage('error', 'Error de conexión. Por favor, inténtalo de nuevo o escríbenos a contacto@reservabot.es');
    } finally {
        // Restaurar botón
        btnIcon.className = 'ri-send-plane-line mr-2';
        btnText.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// Función para mostrar mensajes de estado
function showContactMessage(type, message) {
    // Crear el elemento de notificación
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 translate-x-full`;
    
    if (type === 'success') {
        notification.className += ' bg-green-500 text-white';
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="ri-check-circle-line text-xl mr-3"></i>
                <div>
                    <div class="font-semibold">¡Éxito!</div>
                    <div class="text-sm opacity-90">${message}</div>
                </div>
            </div>
        `;
    } else {
        notification.className += ' bg-red-500 text-white';
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="ri-error-warning-line text-xl mr-3"></i>
                <div>
                    <div class="font-semibold">Error</div>
                    <div class="text-sm opacity-90">${message}</div>
                </div>
            </div>
        `;
    }
    
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Remover después de 5 segundos
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

// Hacer las funciones globales para compatibilidad
window.openContactModal = openContactModal;
window.closeContactModal = closeContactModal;
</script>