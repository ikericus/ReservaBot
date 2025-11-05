<?php
/**
 * Componente Modal de Conversación WhatsApp Reutilizable
 * Archivo: public/components/conversacion.php
 * 
 * Uso: include 'components/conversacion.php';
 * 
 * Requiere que se definan previamente las siguientes variables:
 * - $whatsappConnected (boolean)
 * - $userId (int) - ID del usuario actual
 * 
 * Variables opcionales:
 * - $clientPhone (string) - Teléfono del cliente
 * - $clientName (string) - Nombre del cliente
 */

// Variables por defecto si no se han definido
$clientPhone = $clientPhone ?? '';
$clientName = $clientName ?? 'Cliente';
$whatsappConnected = $whatsappConnected ?? false;
$userId = $userId ?? 0;
?>

<!-- Modal de chat WhatsApp -->
<div id="whatsappChatModal" class="whatsapp-chat-modal fixed inset-0 z-50 hidden items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-auto chat-modal-container">
        <div class="chat-container rounded-2xl overflow-hidden">
            
            <!-- Header del chat -->
            <div class="chat-header">
                <div class="chat-avatar" id="chatAvatar">
                    ?
                </div>
                
                <div class="flex-1">
                    <h3 class="font-semibold" id="chatClientName"><?php echo htmlspecialchars($clientName); ?></h3>
                    <p class="text-sm opacity-90" id="chatClientPhone"><?php echo htmlspecialchars($clientPhone); ?></p>
                </div>
                
                <button onclick="closeWhatsAppChat()" class="p-2 rounded-full hover:bg-white hover:bg-opacity-20 transition-colors" title="Cerrar">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            
            <!-- Área de mensajes -->
            <div class="messages-area" id="chatMessagesArea">
                <div class="empty-chat">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mb-4">
                        <i class="ri-chat-3-line text-gray-400 text-2xl"></i>
                    </div>
                    <p>Cargando mensajes...</p>
                </div>
            </div>
            
            <!-- Indicador de escritura -->
            <div class="typing-indicator" id="typingIndicator">
                <i class="ri-more-line"></i> Escribiendo...
            </div>
            
            <!-- Área de entrada -->
            <div class="chat-input-area">
                <div class="flex items-end space-x-3">
                    <div class="flex-1">
                        <textarea 
                            id="chatMessageInput"
                            class="chat-input"
                            placeholder="Escribe un mensaje..."
                            rows="1"
                            maxlength="1000"
                            <?php echo !$whatsappConnected ? 'disabled placeholder="WhatsApp no está conectado"' : ''; ?>
                        ></textarea>
                    </div>
                    
                    <button 
                        id="chatSendButton"
                        onclick="sendChatMessage()"
                        class="send-button"
                        <?php echo !$whatsappConnected ? 'disabled' : ''; ?>
                    >
                        <i class="ri-send-plane-fill"></i>
                    </button>
                </div>
                
                <?php if (!$whatsappConnected): ?>
                <div class="mt-2 text-sm text-gray-500 text-center">
                    <i class="ri-error-warning-line mr-1"></i>
                    <a href="/whatsapp" class="text-blue-600 hover:text-blue-700">Conecta WhatsApp</a> para enviar mensajes
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- CSS específico del componente -->
<style>
/* Estilos específicos para chat WhatsApp */
.whatsapp-chat-modal {
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
}

.chat-modal-container {
    max-height: 85vh;
    display: flex;
    flex-direction: column;
}

@media (min-width: 640px) {
    .chat-modal-container {
        max-height: 90vh;
    }
}

.chat-container {
    background: #efeae2;
    background-image: url("data:image/svg+xml,%3csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3e%3cg fill='none' fill-rule='evenodd'%3e%3cg fill='%23d1fae5' fill-opacity='0.1'%3e%3cpath d='m36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3e%3c/g%3e%3c/g%3e%3c/svg%3e");
    display: flex;
    flex-direction: column;
    height: 500px;
    max-height: calc(85vh - 1.5rem);
}

@media (min-width: 640px) {
    .chat-container {
        max-height: calc(90vh - 2rem);
    }
}

.chat-header {
    background: #25d366;
    color: white;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
}

.chat-avatar {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
}

.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-height: 0; /* Importante para que funcione flex correctamente */
}

.message-bubble {
    max-width: 70%;
    margin-bottom: 4px;
    animation: messageSlideIn 0.3s ease-out;
}

.message-outgoing {
    background: #dcf8c6;
    border-radius: 12px 12px 4px 12px;
    margin-left: auto;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.message-incoming {
    background: white;
    border-radius: 12px 12px 12px 4px;
    margin-right: auto;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.message-text {
    padding: 8px 12px;
    word-wrap: break-word;
    line-height: 1.4;
}

.message-time {
    font-size: 11px;
    color: #667781;
    text-align: right;
    padding: 2px 12px 6px;
    opacity: 0.8;
}

.message-status {
    display: inline-block;
    margin-left: 4px;
    font-size: 12px;
}

.status-sent { color: #999; }
.status-delivered { color: #4fc3f7; }
.status-read { color: #4fc3f7; }
.status-failed { color: #f44336; }

.chat-input-area {
    background: white;
    border-top: 1px solid #e0e0e0;
    padding: 1rem;
    flex-shrink: 0;
}

.chat-input {
    border: 1px solid #e0e0e0;
    border-radius: 24px;
    padding: 12px 20px;
    outline: none;
    resize: none;
    max-height: 100px;
    min-height: 44px;
    width: 100%;
    font-family: inherit;
}

.chat-input:focus {
    border-color: #25d366;
    box-shadow: 0 0 0 2px rgba(37, 211, 102, 0.2);
}

.send-button {
    background: #25d366;
    color: white;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    flex-shrink: 0;
}

.send-button:hover:not(:disabled) {
    background: #128c7e;
    transform: scale(1.05);
}

.send-button:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.empty-chat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #667781;
    padding: 2rem;
    text-align: center;
    opacity: 0.7;
}

@keyframes messageSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.typing-indicator {
    display: none;
    padding: 8px 16px;
    color: #667781;
    font-style: italic;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 12px;
    margin: 8px 0;
    animation: pulse 2s infinite;
    max-width: 200px;
}

.whatsapp-button {
    background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
    box-shadow: 0 2px 8px rgba(37, 211, 102, 0.3);
    transition: all 0.3s ease;
}

.whatsapp-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
}

.whatsapp-button:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Scroll personalizado para mensajes */
.messages-area {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.messages-area::-webkit-scrollbar {
    width: 6px;
}

.messages-area::-webkit-scrollbar-track {
    background: transparent;
}

.messages-area::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 3px;
}

.messages-area::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}

.message-bubble.sending {
    opacity: 0.7;
}

.message-bubble.failed {
    border-left: 3px solid #f44336;
}

/* Responsive - Mobile con diseño flotante */
@media (max-width: 768px) {
    .whatsapp-chat-modal {
        padding: 0.75rem;
        align-items: flex-end;
    }
    
    .chat-modal-container {
        max-width: 100%;
        max-height: 95vh;
        width: 100%;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        animation: slideUp 0.3s ease-out;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(100px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .chat-container {
        height: 85vh;
        max-height: 85vh;
        border-radius: 1rem;
    }
    
    .messages-area {
        flex: 1;
        min-height: 0;
        padding: 0.75rem;
    }
    
    .chat-header {
        border-radius: 1rem 1rem 0 0;
    }
}

/* Para dispositivos muy pequeños o con pantallas cortas */
@media (max-width: 640px) and (max-height: 667px) {
    .whatsapp-chat-modal {
        padding: 0.5rem;
    }
    
    .chat-modal-container {
        max-height: 92vh;
    }
    
    .chat-container {
        height: 80vh;
        max-height: 80vh;
    }
    
    .chat-input-area {
        padding: 0.75rem;
    }
    
    .chat-header {
        padding: 0.75rem;
    }
    
    .chat-input {
        padding: 10px 16px;
        min-height: 40px;
    }
    
    .send-button {
        width: 40px;
        height: 40px;
    }
    
    .messages-area {
        padding: 0.5rem;
    }
}

/* Para pantallas muy cortas (landscape mode en móviles) */
@media (max-height: 500px) {
    .whatsapp-chat-modal {
        padding: 0.5rem;
    }
    
    .chat-modal-container {
        max-height: 95vh;
    }
    
    .chat-container {
        height: 90vh;
        max-height: 90vh;
    }
    
    .chat-header {
        padding: 0.5rem 0.75rem;
    }
    
    .chat-avatar {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }
    
    .messages-area {
        padding: 0.5rem;
    }
    
    .chat-input-area {
        padding: 0.5rem;
    }
    
    .chat-input {
        padding: 8px 14px;
        min-height: 36px;
    }
    
    .send-button {
        width: 36px;
        height: 36px;
    }
}
</style>

<!-- JavaScript del componente -->
<script>
// Definir variables globales inmediatamente
window.whatsappChat = null;

// Definir funciones globales inmediatamente para evitar errores de referencia
window.openWhatsAppChat = function(clientPhone, clientName) {
    console.log('openWhatsAppChat llamada con:', clientPhone, clientName);
    
    if (!window.whatsappChat) {
        window.whatsappChat = new WhatsAppChatComponent({
            clientPhone: clientPhone || '<?php echo addslashes($clientPhone); ?>',
            clientName: clientName || '<?php echo addslashes($clientName); ?>',
            whatsappConnected: <?php echo $whatsappConnected ? 'true' : 'false'; ?>,
            userId: <?php echo $userId; ?>
        });
    }
    window.whatsappChat.openChat(clientPhone, clientName);
};

window.closeWhatsAppChat = function() {
    if (window.whatsappChat) {
        window.whatsappChat.closeChat();
    }
};

window.sendChatMessage = function() {
    if (window.whatsappChat) {
        window.whatsappChat.sendMessage();
    }
};

// También crear alias en el scope global para compatibilidad
var openWhatsAppChat = window.openWhatsAppChat;
var closeWhatsAppChat = window.closeWhatsAppChat;
var sendChatMessage = window.sendChatMessage;

class WhatsAppChatComponent {
    constructor(options = {}) {
        this.clientPhone = options.clientPhone || '';
        this.clientName = options.clientName || 'Cliente';
        this.whatsappConnected = options.whatsappConnected || false;
        this.userId = options.userId || <?php echo $userId; ?>;
        this.messages = [];
        this.isLoading = false;
        this.refreshInterval = null;
        this.isOpen = false;
        
        console.log('Inicializando chat para:', this.clientPhone, this.clientName);
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupAutoResize();
        this.updateChatInfo();
    }

    bindEvents() {
        // Enter para enviar mensaje
        const messageInput = document.getElementById('chatMessageInput');
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }

        // Cerrar modal con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeChat();
            }
        });

        // Cerrar modal al hacer click fuera
        const modal = document.getElementById('whatsappChatModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal && this.isOpen) {
                    this.closeChat();
                }
            });
        }
    }

    setupAutoResize() {
        const textarea = document.getElementById('chatMessageInput');
        if (textarea) {
            textarea.addEventListener('input', () => {
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
            });
        }
    }

    updateChatInfo() {
        // Actualizar avatar con iniciales
        const avatarEl = document.getElementById('chatAvatar');
        if (avatarEl && this.clientName) {
            let initials = '?';
            if (!this.clientName.startsWith('Contacto ')) {
                const words = this.clientName.split(' ');
                if (words.length >= 2) {
                    initials = (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
                } else {
                    initials = this.clientName.substring(0, 2).toUpperCase();
                }
            }
            avatarEl.textContent = initials;
        }

        // Actualizar nombre y teléfono
        const nameEl = document.getElementById('chatClientName');
        const phoneEl = document.getElementById('chatClientPhone');
        
        if (nameEl) nameEl.textContent = this.clientName;
        if (phoneEl) phoneEl.textContent = this.clientPhone;
    }

    openChat(clientPhone = null, clientName = null) {
        console.log('Abriendo chat WhatsApp');
        
        // Actualizar información del cliente si se proporciona
        if (clientPhone) this.clientPhone = clientPhone;
        if (clientName) this.clientName = clientName;
        
        this.updateChatInfo();
        
        const modal = document.getElementById('whatsappChatModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            this.isOpen = true;
            
            // Focus en el input si WhatsApp está conectado
            if (this.whatsappConnected) {
                setTimeout(() => {
                    const input = document.getElementById('chatMessageInput');
                    if (input) input.focus();
                }, 100);
            }
            
            // Cargar mensajes
            this.loadMessagesFromServer();
            
            // Iniciar auto-refresh cada 10 segundos
            this.startAutoRefresh();
        }
    }

    closeChat() {
        console.log('Cerrando chat WhatsApp');
        
        const modal = document.getElementById('whatsappChatModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            this.isOpen = false;
            
            // Detener auto-refresh
            this.stopAutoRefresh();
        }
    }

    startAutoRefresh() {
        // Limpiar intervalo existente si lo hay
        this.stopAutoRefresh();
        
        // Refrescar cada 10 segundos
        this.refreshInterval = setInterval(() => {
            if (this.isOpen && !this.isLoading) {
                console.log('Auto-refresh de mensajes...');
                this.loadMessagesFromServer(true); // true = silencioso, sin mostrar loading
            }
        }, 10000); // 10 segundos
        
        console.log('Auto-refresh iniciado');
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
            console.log('Auto-refresh detenido');
        }
    }

    async loadMessagesFromServer(silent = false) {
        if (this.isLoading || !this.clientPhone) return;
        
        if (!silent) {
            console.log('Cargando mensajes del servidor...');
        }
        this.isLoading = true;
        
        try {
            const formattedPhone = this.formatPhoneNumber(this.clientPhone);
            const response = await fetch(`/api/whatsapp-conversations?phone=${formattedPhone}&message_limit=50`);
            const data = await response.json();

            if (data.success) {
                const oldMessagesCount = this.messages.length;
                if (data.messages.length > 0) {   
                    this.messages = data.messages;
                    
                    if (!silent) {
                        console.log('Mensajes cargados del servidor:', this.messages.length);
                    }
                    
                    // Solo scroll si hay mensajes nuevos
                    const shouldScroll = data.messages.length > oldMessagesCount;
                    this.renderMessages(shouldScroll);
                } else {
                    this.messages = [];
                    if (!silent) {
                        console.log('No existen mensajes para el cliente.');
                    }
                    this.renderMessages(false);
                }
            } else {
                this.messages = [];
                if (!silent) {
                    console.log('Error al cargar mensajes:', data.error);
                }
                this.renderMessages(false);
            }
            
        } catch (error) {
            if (!silent) {
                console.error('Error cargando mensajes del servidor:', error);
            }
            this.renderMessages(false);
        } finally {
            this.isLoading = false;
        }
    }

    renderMessages(autoScroll = true) {
        const container = document.getElementById('chatMessagesArea');
        if (!container) {
            console.error('No se encontró el contenedor de mensajes');
            return;
        }
        
        // Guardar posición de scroll antes de renderizar
        const wasAtBottom = this.isScrolledToBottom(container);
        
        if (this.messages.length === 0) {
            container.innerHTML = `
                <div class="empty-chat">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mb-4">
                        <i class="ri-chat-3-line text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-700 mb-2">No hay mensajes</h3>
                    <p class="text-center">Inicia la conversación enviando un mensaje</p>
                </div>
            `;
            return;
        }

        const html = this.messages.map(message => this.renderMessage(message)).join('');
        container.innerHTML = html;
        
        // Solo hacer scroll automático si estábamos en el fondo o es la primera carga
        if (autoScroll || wasAtBottom) {
            this.scrollToBottom();
        }
    }

    isScrolledToBottom(container) {
        if (!container) return true;
        const threshold = 50; // píxeles de tolerancia
        return container.scrollHeight - container.scrollTop - container.clientHeight < threshold;
    }

    renderMessage(message) {
        const time = this.formatMessageTime(message.timestamp);
        const statusIcon = this.getStatusIcon(message.status);
        
        return `
            <div class="message-bubble ${message.isOutgoing ? 'message-outgoing' : 'message-incoming'}" 
                 data-message-id="${message.messageId}">
                <div class="message-text">${this.escapeHtml(message.content)}</div>
                <div class="message-time">
                    ${time}
                    ${message.isOutgoing ? `<span class="message-status status-${message.status}">${statusIcon}</span>` : ''}
                </div>
            </div>
        `;
    }

    async sendMessage() {
        if (!this.whatsappConnected) {
            this.showNotification('WhatsApp no está conectado', 'error');
            return;
        }

        if (!this.clientPhone) {
            this.showNotification('No se ha especificado un número de teléfono', 'error');
            return;
        }

        const input = document.getElementById('chatMessageInput');
        const sendButton = document.getElementById('chatSendButton');
        
        if (!input || !sendButton) return;
        
        const message = input.value.trim();
        if (!message) return;
        
        console.log('Enviando mensaje:', message);
        
        // Deshabilitar input y botón
        input.disabled = true;
        sendButton.disabled = true;
        sendButton.innerHTML = '<div class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></div>';
        
        try {
            // Añadir mensaje temporal a la UI
            const tempMessage = {
                messageId: 'temp_' + Date.now(),
                content: message,
                direction: 'outgoing',
                isOutgoing: true,
                timestamp: new Date().toISOString(),
                status: 'sending'
            };
            
            this.addMessageToUI(tempMessage);
            input.value = '';
            input.style.height = 'auto';
            
            // Enviar mensaje
            const formattedPhone = this.formatPhoneNumber(this.clientPhone);
            const response = await fetch('/api/whatsapp-send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    to: formattedPhone,
                    message: message,
                    type: 'manual',
                    clientName: this.clientName
                })
            });

            const data = await response.json();
            console.log('Respuesta del servidor:', data);

            if (data.success) {
                // Actualizar mensaje temporal con datos reales
                this.updateMessageInUI(tempMessage.messageId, {
                    messageId: data.messageId || data.localMessageId,
                    status: 'sent'
                });
                
                this.showNotification('Mensaje enviado', 'success');
                
                // Añadir a la lista de mensajes
                const finalMessage = {
                    ...tempMessage,
                    messageId: data.messageId || data.localMessageId,
                    status: 'sent'
                };
                this.messages.push(finalMessage);
                
            } else if (data.queued) {
                this.updateMessageInUI(tempMessage.messageId, {
                    status: 'pending'
                });
                this.showNotification('Mensaje en cola (WhatsApp conectándose)', 'warning');
                
            } else {
                // Marcar mensaje como fallido
                this.updateMessageInUI(tempMessage.messageId, {
                    status: 'failed'
                });
                this.showNotification('Error: ' + (data.error || 'Error desconocido'), 'error');
            }

        } catch (error) {
            console.error('Error enviando mensaje:', error);
            this.showNotification('Error de conexión', 'error');
            
            // Marcar mensaje como fallido
            const tempElement = document.querySelector(`[data-message-id^="temp_"]`);
            if (tempElement) {
                this.updateMessageInUI(tempElement.dataset.messageId, {
                    status: 'failed'
                });
            }
            
        } finally {
            // Rehabilitar input y botón
            input.disabled = false;
            sendButton.disabled = false;
            sendButton.innerHTML = '<i class="ri-send-plane-fill"></i>';
            input.focus();
        }
    }

    addMessageToUI(message) {
        const container = document.getElementById('chatMessagesArea');
        if (!container) return;

        // Remover empty chat si existe
        const emptyChat = container.querySelector('.empty-chat');
        if (emptyChat) {
            emptyChat.remove();
        }

        const messageHtml = this.renderMessage(message);
        container.insertAdjacentHTML('beforeend', messageHtml);
        
        this.scrollToBottom();
    }

    updateMessageInUI(tempMessageId, updates) {
        const messageElement = document.querySelector(`[data-message-id="${tempMessageId}"]`);
        if (!messageElement) return;
        
        if (updates.messageId) {
            messageElement.setAttribute('data-message-id', updates.messageId);
        }
        
        if (updates.status) {
            const statusSpan = messageElement.querySelector('.message-status');
            if (statusSpan) {
                statusSpan.className = `message-status status-${updates.status}`;
                statusSpan.innerHTML = this.getStatusIcon(updates.status);
            }
            
            // Añadir clases visuales
            messageElement.classList.remove('sending', 'failed');
            if (updates.status === 'failed') {
                messageElement.classList.add('failed');
            } else if (updates.status === 'sending') {
                messageElement.classList.add('sending');
            }
        }
    }

    scrollToBottom() {
        const container = document.getElementById('chatMessagesArea');
        if (container) {
            setTimeout(() => {
                container.scrollTop = container.scrollHeight;
            }, 100);
        }
    }

    // Utilidades
    formatPhoneNumber(phone) {
        // Limpiar el número
        let clean = phone.replace(/[^\d]/g, '');
        
        // Si es un número español sin código de país, añadir 34
        if (clean.length === 9 && (clean.startsWith('6') || clean.startsWith('7') || clean.startsWith('9'))) {
            clean = '34' + clean;
        }
        
        return clean;
    }

    formatMessageTime(timestamp) {
        if (!timestamp) return '';
        
        const date = new Date(timestamp);
        const now = new Date();
        const diffInHours = (now - date) / (1000 * 60 * 60);
        
        if (diffInHours < 24) {
            return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        } else if (diffInHours < 48) {
            return 'Ayer ' + date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        } else {
            return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
        }
    }

    getStatusIcon(status) {
        const icons = {
            pending: '⏳',
            sending: '⏳',
            sent: '✓',
            delivered: '✓✓',
            read: '✓✓',
            failed: '❌'
        };
        return icons[status] || '';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/\n/g, '<br>');
    }

    showNotification(message, type = 'info') {
        // Crear notificación temporal
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 text-white ${
            type === 'success' ? 'bg-green-600' : 
            type === 'error' ? 'bg-red-600' : 
            type === 'warning' ? 'bg-yellow-600' : 'bg-blue-600'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Inicializar cuando se carga el DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando WhatsApp chat component');
    
    // Solo inicializar si tenemos datos del cliente y no existe ya una instancia
    const clientPhone = '<?php echo addslashes($clientPhone); ?>';
    if (clientPhone && !window.whatsappChat) {
        console.log('Inicializando chat para:', clientPhone);
        window.whatsappChat = new WhatsAppChatComponent({
            clientPhone: clientPhone,
            clientName: '<?php echo addslashes($clientName); ?>',
            whatsappConnected: <?php echo $whatsappConnected ? 'true' : 'false'; ?>,
            userId: <?php echo $userId; ?>
        });
    }
});

console.log('WhatsApp Chat Component cargado, funciones disponibles:', 
    typeof window.openWhatsAppChat, 
    typeof openWhatsAppChat
);
</script>
