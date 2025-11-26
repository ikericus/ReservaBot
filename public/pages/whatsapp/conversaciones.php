<?php
// pages/whatsapp/conversaciones.php

// NOTA: Esta página solo se muestra cuando WhatsApp está conectado (control en whatsapp.php)
// Por tanto, no es necesario verificar la conexión aquí para deshabilitar controles

// Solo configurar si no estamos siendo incluidos desde whatsapp.php
if (!isset($conversationsPageContent)) {
    $currentPage = 'conversaciones';
    $pageTitle = 'ReservaBot - Conversaciones WhatsApp';
    $pageScript = 'conversaciones';
    
    // Obtener usuario actual
    $currentUser = getAuthenticatedUser();
    $userId = $currentUser['id'];
    
    // Verificar estado de WhatsApp usando la capa de dominio
    $whatsappConnected = false;
    $phoneNumber = null;
    
    try {
        $whatsappDomain = getContainer()->getWhatsAppDomain();
        $config = $whatsappDomain->obtenerConfiguracion($userId);
        
        $whatsappConnected = $config->estaConectado();
        $phoneNumber = $config->getPhoneNumber();
        
    } catch (\Exception $e) {
        error_log('Error obteniendo configuración WhatsApp: ' . $e->getMessage());
        setFlashError('Error al verificar estado de WhatsApp');
    }
    
    include 'includes/header.php';
}
?>

<style>
/* Estilos específicos para conversaciones tipo WhatsApp */
.conversations-container {
    height: calc(100vh - 180px);
    max-height: 700px;
    background: white;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.conversations-sidebar {
    background: #f8fafc;
    border-right: 1px solid #e5e7eb;
    overflow-y: auto;
    height: 100%;
}

.chat-area {
    background: #efeae2;
    background-image: url("data:image/svg+xml,%3csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3e%3cg fill='none' fill-rule='evenodd'%3e%3cg fill='%23d1fae5' fill-opacity='0.1'%3e%3cpath d='m36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3e%3c/g%3e%3c/g%3e%3c/svg%3e");
    display: flex;
    flex-direction: column;
    height: 100%;
}

.conversation-item {
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 1rem;
}

.conversation-item:hover {
    background-color: #f1f5f9;
}

.conversation-item.active {
    background-color: #ecfdf5;
    border-left: 4px solid #10b981;
}

.conversation-avatar {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 18px;
    flex-shrink: 0;
}

.message-bubble {
    max-width: 70%;
    margin-bottom: 8px;
    animation: messageSlideIn 0.3s ease-out;
}

.message-outgoing {
    background: #dcf8c6;
    border-radius: 12px 12px 4px 12px;
    margin-left: auto;
}

.message-incoming {
    background: white;
    border-radius: 12px 12px 12px 4px;
    margin-right: auto;
}

.message-text {
    padding: 8px 12px;
    word-wrap: break-word;
}

.message-time {
    font-size: 11px;
    color: #6b7280;
    text-align: right;
    padding: 2px 12px 6px;
}

.message-status {
    display: inline-block;
    margin-left: 4px;
    font-size: 12px;
}

.status-sent { color: #9ca3af; }
.status-delivered { color: #10b981; }
.status-read { color: #3b82f6; }
.status-failed { color: #ef4444; }

.chat-input-area {
    background: white;
    border-top: 1px solid #e5e7eb;
    padding: 16px;
}

.chat-input {
    border: 1px solid #d1d5db;
    border-radius: 24px;
    padding: 12px 20px;
    outline: none;
    resize: none;
    max-height: 120px;
    min-height: 48px;
    width: 100%;
    overflow: hidden;
}

.chat-input:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.send-button {
    background: #10b981;
    color: white;
    border-radius: 50%;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.send-button:hover:not(:disabled) {
    background: #059669;
    transform: scale(1.05);
}

.send-button:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
}

.empty-chat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    padding: 2rem;
    text-align: center;
}

.no-conversations {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6b7280;
    padding: 2rem;
    text-align: center;
}

.unread-badge {
    background: #10b981;
    color: white;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    padding: 2px 6px;
    min-width: 20px;
    text-align: center;
}

.search-box {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    background: white;
}

.search-input {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid #d1d5db;
    border-radius: 20px;
    outline: none;
    font-size: 14px;
}

.search-input:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
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

.chat-header {
    background: white;
    border-bottom: 1px solid #e5e7eb;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .conversations-container {
        height: calc(100vh - 140px);
        flex-direction: column;
    }
    
    .conversations-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .chat-area {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 50;
        background: white;
        overflow: hidden;
    }
    
    .chat-area.mobile-active {
        display: flex;
    }
    
    .conversations-sidebar.mobile-hidden {
        display: none;
    }
    
    .mobile-back-btn {
        display: flex;
    }
    
    .message-bubble {
        max-width: 85%;
    }
}

@media (min-width: 769px) {
    .mobile-back-btn {
        display: none;
    }
    
    .chat-area {
        display: flex !important;
    }
    
    .conversations-sidebar {
        display: flex !important;
    }
}

/* Scroll personalizado */
.conversations-sidebar,
.messages-container {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.conversations-sidebar::-webkit-scrollbar,
.messages-container::-webkit-scrollbar {
    width: 6px;
}

.conversations-sidebar::-webkit-scrollbar-track,
.messages-container::-webkit-scrollbar-track {
    background: transparent;
}

.conversations-sidebar::-webkit-scrollbar-thumb,
.messages-container::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 3px;
}

.conversations-sidebar::-webkit-scrollbar-thumb:hover,
.messages-container::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}

.message-bubble.sending {
    opacity: 0.6;
}

.message-bubble.failed {
    border-left: 3px solid #ef4444;
}
</style>

<!-- Contenedor principal de conversaciones -->
<div class="conversations-container">
    <div class="flex h-full">
        
        <!-- Columna 1: Lista de conversaciones -->
        <div class="conversations-sidebar w-full md:w-1/3 lg:w-96 flex flex-col" id="conversationsSidebar">
            
            <!-- Búsqueda -->
            <div class="search-box">
                <input 
                    type="text" 
                    id="searchInput"
                    class="search-input" 
                    placeholder="Buscar conversaciones..."
                    autocomplete="off"
                >
            </div>
            
            <!-- Lista de conversaciones -->
            <div class="flex-1 overflow-y-auto" id="conversationsList">
                <div class="no-conversations">
                    <div class="animate-spin w-8 h-8 border-2 border-green-500 border-t-transparent rounded-full mb-4"></div>
                    <p>Cargando conversaciones...</p>
                </div>
            </div>
            
        </div>
        
        <!-- Columna 2: Área de chat -->
        <div class="chat-area flex-1" id="chatArea">
            
            <!-- Header del chat -->
            <div class="chat-header" id="chatHeader" style="display: none;">
                <button class="mobile-back-btn p-2 rounded-lg hover:bg-gray-100" onclick="closeMobileChat()">
                    <i class="ri-arrow-left-line text-xl"></i>
                </button>
                
                <div class="conversation-avatar" id="chatAvatar">
                    <i class="ri-user-line"></i>
                </div>
                
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900" id="chatName">Contacto</h3>
                    <p class="text-sm text-gray-500" id="chatPhone">+34 000 000 000</p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button class="p-2 rounded-lg hover:bg-gray-100" title="Información del contacto">
                        <i class="ri-information-line text-xl text-gray-600"></i>
                    </button>
                </div>
            </div>
            
            <!-- Mensajes -->
            <div class="messages-container" id="messagesContainer">
                <div class="empty-chat">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="ri-chat-3-line text-gray-400 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-700 mb-2">Selecciona una conversación</h3>
                    <p class="text-center max-w-md">Elige una conversación de la lista para empezar a chatear con tus clientes.</p>
                </div>
            </div>
            
            <!-- Área de entrada de mensaje -->
            <div class="chat-input-area" id="chatInputArea" style="display: none;">
                <div class="flex items-end space-x-3">
                    <div class="flex-1">
                        <textarea 
                            id="messageInput"
                            class="chat-input"
                            placeholder="Escribe un mensaje..."
                            rows="1"
                            maxlength="1000"
                        ></textarea>
                    </div>
                    
                    <button 
                        id="sendButton"
                        class="send-button"
                        onclick="sendMessage()"
                    >
                        <i class="ri-send-plane-fill text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script>
class ConversationsManager {
    constructor() {
        console.log('🚀 ConversationsManager iniciado');
        
        this.conversations = [];
        this.currentConversation = null;
        this.isLoading = false;
        this.searchTerm = '';
        this.refreshInterval = null;
        this.isMobile = window.innerWidth < 768;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadConversations();
        this.setupAutoRefresh();
        this.setupTextareaAutoResize();
        this.handleResize();
    }

    bindEvents() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchTerm = e.target.value.toLowerCase();
                this.filterConversations();
            });
        }

        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }

        window.addEventListener('resize', () => this.handleResize());
        window.addEventListener('beforeunload', () => this.cleanup());
    }

    handleResize() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth < 768;
        
        if (wasMobile !== this.isMobile) {
            if (!this.isMobile) {
                this.showDesktopView();
            } else {
                this.showMobileList();
            }
        }
    }

    showDesktopView() {
        const sidebar = document.getElementById('conversationsSidebar');
        const chatArea = document.getElementById('chatArea');
        
        sidebar?.classList.remove('mobile-hidden');
        chatArea?.classList.remove('mobile-active');
    }

    showMobileList() {
        const sidebar = document.getElementById('conversationsSidebar');
        const chatArea = document.getElementById('chatArea');
        
        sidebar?.classList.remove('mobile-hidden');
        chatArea?.classList.remove('mobile-active');
    }

    showMobileChat() {
        const sidebar = document.getElementById('conversationsSidebar');
        const chatArea = document.getElementById('chatArea');
        
        sidebar?.classList.add('mobile-hidden');
        chatArea?.classList.add('mobile-active');
    }

    closeMobileChat() {
        this.showMobileList();
    }

    async loadConversations() {
        console.log('📥 Cargando conversaciones con nombres de clientes...');
        
        if (this.isLoading) return;
        
        this.isLoading = true;
        
        try {
            const response = await fetch('/api/whatsapp-conversations?limit=50');
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('📊 Conversaciones recibidas (con nombres):', data);
            
            if (data.success && data.conversations) {
                this.conversations = data.conversations;
                console.log('✅ Conversaciones con nombres de clientes cargadas:', this.conversations);
                this.renderConversations();
            } else {
                this.showError('Error cargando conversaciones: ' + (data.error || 'Error desconocido'));
            }
        } catch (error) {
            console.error('💥 Error en loadConversations:', error);
            this.showError('Error de conexión: ' + error.message);
        } finally {
            this.isLoading = false;
        }
    }

    renderConversations() {
        const container = document.getElementById('conversationsList');
        if (!container) return;
        
        if (this.conversations.length === 0) {
            container.innerHTML = `
                <div class="no-conversations">
                    <i class="ri-chat-3-line text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No hay conversaciones</h3>
                    <p class="text-center text-gray-500 px-4">
                        Las conversaciones aparecerán aquí cuando recibas mensajes de WhatsApp.
                    </p>
                </div>
            `;
            return;
        }

        const html = this.conversations.map(conv => this.renderConversationItem(conv)).join('');
        container.innerHTML = html;
    }

    renderConversationItem(conversation) {
        const isActive = this.currentConversation && this.currentConversation.phone === conversation.phone;
        const lastMessagePreview = this.truncateMessage(conversation.lastMessage, 50);
        const initials = this.getContactInitials(conversation.name);
        
        return `
            <div class="conversation-item ${isActive ? 'active' : ''}" 
                 onclick="window.conversationsManager.selectConversation('${conversation.phone}')"
                 data-phone="${conversation.phone}">
                
                <div class="flex items-center">
                    <div class="conversation-avatar mr-3">
                        ${initials}
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-medium text-gray-900 truncate">${this.escapeHtml(conversation.name)}</h4>
                            <span class="text-xs text-gray-500 flex-shrink-0 ml-2">${conversation.lastMessageTime || ''}</span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-600 truncate">${lastMessagePreview}</p>
                            ${conversation.unreadCount > 0 ? `<span class="unread-badge ml-2">${conversation.unreadCount}</span>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async selectConversation(phoneNumber) {
        const conversation = this.conversations.find(c => c.phone === phoneNumber);
        if (!conversation) return;

        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
        });

        const conversationElement = document.querySelector(`[data-phone="${phoneNumber}"]`);
        conversationElement?.classList.add('active');

        this.currentConversation = conversation;
        
        await this.loadMessages(phoneNumber);
        this.showChatArea();
        
        if (conversation.unreadCount > 0) {
            this.markAsRead(phoneNumber);
        }

        if (this.isMobile) {
            this.showMobileChat();
        }
    }

    async loadMessages(phoneNumber) {
        try {
            const response = await fetch(`/api/whatsapp-conversations?phone=${phoneNumber}&message_limit=50`);
            const data = await response.json();
            
            if (data.success && data.messages) {
                this.renderMessages(data.messages);
                
                // Si viene nombre del cliente, actualizar la conversación
                const conversation = this.conversations.find(c => c.phone === phoneNumber);
                if (conversation) {
                    // Si el API devuelve un nombre de cliente, actualizarlo
                    if (data.clientName) {
                        conversation.name = data.clientName;
                    }
                    this.updateChatHeader(conversation);
                }
            }
        } catch (error) {
            console.error('💥 Error cargando mensajes:', error);
        }
    }

    renderMessages(messages) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        if (messages.length === 0) {
            container.innerHTML = `
                <div class="empty-chat">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="ri-chat-3-line text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-500">No hay mensajes en esta conversación</p>
                </div>
            `;
            return;
        }

        const html = messages.map(message => this.renderMessage(message)).join('');
        container.innerHTML = html;
        this.scrollToBottom();
    }

    renderMessage(message) {
        const isOutgoing = message.isOutgoing || message.direction === 'outgoing';
        const time = this.formatMessageTime(message.timestamp);
        const statusIcon = this.getStatusIcon(message.status);
        
        return `
            <div class="message-bubble ${isOutgoing ? 'message-outgoing' : 'message-incoming'}" data-message-id="${message.messageId}">
                <div class="message-text">${this.escapeHtml(message.content)}</div>
                <div class="message-time">
                    ${time}
                    ${isOutgoing ? `<span class="message-status status-${message.status}">${statusIcon}</span>` : ''}
                </div>
            </div>
        `;
    }

    updateChatHeader(conversation) {
        const chatName = document.getElementById('chatName');
        const chatPhone = document.getElementById('chatPhone');
        const chatAvatar = document.getElementById('chatAvatar');
        
        if (chatName) chatName.textContent = conversation.name;
        if (chatPhone) chatPhone.textContent = conversation.phone;
        if (chatAvatar) chatAvatar.textContent = this.getContactInitials(conversation.name);
    }

    showChatArea() {
        const chatHeader = document.getElementById('chatHeader');
        const chatInputArea = document.getElementById('chatInputArea');
        
        if (chatHeader) chatHeader.style.display = 'flex';
        if (chatInputArea) chatInputArea.style.display = 'block';
        
        const emptyChat = document.querySelector('.empty-chat');
        if (emptyChat) emptyChat.style.display = 'none';
    }

    async sendMessage() {
        if (!this.currentConversation) return;

        const input = document.getElementById('messageInput');
        const button = document.getElementById('sendButton');
        
        const message = input.value.trim();
        if (!message) return;

        input.disabled = true;
        button.disabled = true;
        button.innerHTML = '<div class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full"></div>';

        try {
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

            const response = await fetch('/api/whatsapp-send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    to: this.currentConversation.phone,
                    message: message
                })
            });

            const data = await response.json();

            if (data.success) {
                this.updateMessageInUI(tempMessage.messageId, {
                    messageId: data.messageId,
                    status: 'sent'
                });
                this.showNotification('Mensaje enviado', 'success');
            } else {
                this.updateMessageInUI(tempMessage.messageId, { status: 'failed' });
                this.showNotification('Error: ' + data.error, 'error');
            }

        } catch (error) {
            console.error('💥 Error en sendMessage:', error);
            this.showNotification('Error de conexión', 'error');
        } finally {
            input.disabled = false;
            button.disabled = false;
            button.innerHTML = '<i class="ri-send-plane-fill text-xl"></i>';
            input.focus();
        }
    }

    addMessageToUI(message) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;

        const emptyChat = container.querySelector('.empty-chat');
        if (emptyChat) emptyChat.remove();

        const messageHtml = this.renderMessage(message);
        container.insertAdjacentHTML('beforeend', messageHtml);
        this.scrollToBottom();
    }

    updateMessageInUI(tempMessageId, updates) {
        const messageElements = document.querySelectorAll(`[data-message-id="${tempMessageId}"]`);
        
        messageElements.forEach(element => {
            if (updates.messageId) {
                element.setAttribute('data-message-id', updates.messageId);
            }
            
            if (updates.status) {
                const statusSpan = element.querySelector('.message-status');
                if (statusSpan) {
                    statusSpan.className = `message-status status-${updates.status}`;
                    statusSpan.innerHTML = this.getStatusIcon(updates.status);
                }
                
                element.classList.remove('sending', 'failed');
                if (updates.status === 'failed') {
                    element.classList.add('failed');
                }
            }
        });
    }

    async markAsRead(phoneNumber) {
        try {
            await fetch('/api/whatsapp-conversations', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'mark_as_read',
                    phone_number: phoneNumber
                })
            });

            const conversation = this.conversations.find(c => c.phone === phoneNumber);
            if (conversation) {
                conversation.unreadCount = 0;
                const element = document.querySelector(`[data-phone="${phoneNumber}"]`);
                const badge = element?.querySelector('.unread-badge');
                if (badge) badge.remove();
            }
        } catch (error) {
            console.error('💥 Error marcando como leído:', error);
        }
    }

    filterConversations() {
        const filtered = this.conversations.filter(conv => {
            return conv.name.toLowerCase().includes(this.searchTerm) ||
                   conv.phone.includes(this.searchTerm) ||
                   conv.lastMessage.toLowerCase().includes(this.searchTerm);
        });

        const container = document.getElementById('conversationsList');
        if (!container) return;
        
        const html = filtered.map(conv => this.renderConversationItem(conv)).join('');
        container.innerHTML = html || '<div class="no-conversations"><p>No se encontraron conversaciones</p></div>';
    }

    setupAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            this.loadConversations();
        }, 30000);
    }

    setupTextareaAutoResize() {
        const textarea = document.getElementById('messageInput');
        if (textarea) {
            textarea.addEventListener('input', () => {
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
            });
        }
    }

    scrollToBottom() {
        const container = document.getElementById('messagesContainer');
        if (container) {
            setTimeout(() => {
                container.scrollTop = container.scrollHeight;
            }, 100);
        }
    }

    // Utilidades
    getContactInitials(name) {
        if (!name || name.startsWith('Contacto ')) {
            return '?';
        }
        
        const words = name.split(' ');
        if (words.length >= 2) {
            return (words[0][0] + words[1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    }

    truncateMessage(message, length) {
        if (!message) return 'Sin mensajes';
        return message.length > length ? message.substring(0, length) + '...' : message;
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
        return div.innerHTML;
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 text-white ${
            type === 'success' ? 'bg-green-600' : 
            type === 'error' ? 'bg-red-600' : 'bg-blue-600'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    showError(message) {
        const container = document.getElementById('conversationsList');
        if (!container) return;
        
        container.innerHTML = `
            <div class="no-conversations">
                <i class="ri-error-warning-line text-6xl text-red-300 mb-4"></i>
                <h3 class="text-lg font-medium text-red-700 mb-2">Error</h3>
                <p class="text-center text-red-600 px-4">${message}</p>
                <button onclick="window.conversationsManager.loadConversations()" 
                        class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Reintentar
                </button>
            </div>
        `;
    }

    cleanup() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    }
}

// Funciones globales
window.sendMessage = function() {
    window.conversationsManager?.sendMessage();
};

window.closeMobileChat = function() {
    window.conversationsManager?.closeMobileChat();
};

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando ConversationsManager con nombres de clientes...');
    window.conversationsManager = new ConversationsManager();
});
</script>

<?php
if (!isset($conversationsPageContent)) {
    include 'includes/footer.php';
}
?>