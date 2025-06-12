<?php
// conversaciones.php
// Página de conversaciones estilo WhatsApp

require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Configurar la página actual
$currentPage = 'conversaciones';
$pageTitle = 'ReservaBot - Conversaciones WhatsApp';
$pageScript = 'conversaciones';

// Obtener usuario actual
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

// Verificar estado de WhatsApp
$whatsappConfig = null;
try {
    $stmt = getPDO()->prepare('SELECT status, phone_number FROM whatsapp_config WHERE usuario_id = ?');
    $stmt->execute([$userId]);
    $whatsappConfig = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Error obteniendo configuración WhatsApp: ' . $e->getMessage());
}

$whatsappConnected = $whatsappConfig && in_array($whatsappConfig['status'], ['connected', 'ready']);
$phoneNumber = $whatsappConfig['phone_number'] ?? null;

// Incluir la cabecera - RUTA CORREGIDA
include dirname(__DIR__) . '/includes/header.php';
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

.typing-indicator {
    display: none;
    padding: 12px 16px;
    color: #6b7280;
    font-style: italic;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 12px;
    margin: 8px 0;
    animation: pulse 2s infinite;
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

.connection-status {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    color: #92400e;
    padding: 12px 16px;
    text-align: center;
    font-size: 14px;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.connection-status.connected {
    background: #d1fae5;
    border-color: #10b981;
    color: #065f46;
}

.connection-status.disconnected {
    background: #fee2e2;
    border-color: #ef4444;
    color: #991b1b;
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
        height: calc(100vh - 120px);
    }
    
    .conversations-sidebar {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 100%;
        z-index: 15;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        background: white;
    }
    
    .conversations-sidebar.show-mobile {
        transform: translateX(0);
    }
    
    .chat-area {
        width: 100%;
    }
    
    .mobile-back-btn {
        display: block;
    }
}

@media (min-width: 769px) {
    .mobile-back-btn {
        display: none;
    }
}

/* Mejor scroll */
.conversations-sidebar {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.conversations-sidebar::-webkit-scrollbar {
    width: 6px;
}

.conversations-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.conversations-sidebar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 3px;
}

.conversations-sidebar::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}

.messages-container {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.messages-container::-webkit-scrollbar {
    width: 6px;
}

.messages-container::-webkit-scrollbar-track {
    background: transparent;
}

.messages-container::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 3px;
}

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

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <i class="ri-chat-history-line text-green-500 mr-3 text-3xl"></i>
            Conversaciones WhatsApp
        </h1>
        <p class="text-gray-600 mt-1">Gestiona todas tus conversaciones de WhatsApp</p>
    </div>
    
    <div class="flex items-center space-x-4">
        <!-- Estado de conexión -->
        <div class="connection-status <?php echo $whatsappConnected ? 'connected' : 'disconnected'; ?>">
            <i class="ri-whatsapp-line mr-2"></i>
            <?php if ($whatsappConnected): ?>
                WhatsApp Conectado
                <?php if ($phoneNumber): ?>
                    (<?php echo htmlspecialchars($phoneNumber); ?>)
                <?php endif; ?>
            <?php else: ?>
                WhatsApp Desconectado
                <a href="/whatsapp" class="ml-2 underline hover:no-underline">Conectar</a>
            <?php endif; ?>
        </div>
    </div>
</div>

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
        <div class="chat-area flex-1 hidden md:flex" id="chatArea">
            
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
            
            <!-- Indicador de escritura -->
            <div class="typing-indicator" id="typingIndicator">
                <i class="ri-more-line"></i> El cliente está escribiendo...
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
                            <?php echo !$whatsappConnected ? 'disabled' : ''; ?>
                        ></textarea>
                    </div>
                    
                    <button 
                        id="sendButton"
                        class="send-button"
                        onclick="sendMessage()"
                        <?php echo !$whatsappConnected ? 'disabled' : ''; ?>
                    >
                        <i class="ri-send-plane-fill text-xl"></i>
                    </button>
                </div>
                
                <?php if (!$whatsappConnected): ?>
                <div class="mt-2 text-sm text-gray-500 text-center">
                    <i class="ri-error-warning-line mr-1"></i>
                    Conecta WhatsApp para enviar mensajes
                </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<!-- Chat móvil overlay -->
<!-- <div class="md:hidden chat-area fixed inset-0 z-40 hidden" id="mobileChatArea">
    <div class="flex flex-col h-full bg-white">
        <div class="chat-header" id="mobileChatHeader">
            <button class="p-2 rounded-lg hover:bg-gray-100" onclick="closeMobileChat()">
                <i class="ri-arrow-left-line text-xl"></i>
            </button>
            
            <div class="conversation-avatar" id="mobileChatAvatar">
                <i class="ri-user-line"></i>
            </div>
            
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900" id="mobileChatName">Contacto</h3>
                <p class="text-sm text-gray-500" id="mobileChatPhone">+34 000 000 000</p>
            </div>
        </div>
        
        <div class="messages-container" id="mobileMessagesContainer">
            
        </div>
        
        <div class="chat-input-area" id="mobileChatInputArea">
            <div class="flex items-end space-x-3">
                <div class="flex-1">
                    <textarea 
                        id="mobileMessageInput"
                        class="chat-input"
                        placeholder="Escribe un mensaje..."
                        rows="1"
                        maxlength="1000"
                        <?php echo !$whatsappConnected ? 'disabled' : ''; ?>
                    ></textarea>
                </div>
                
                <button 
                    id="mobileSendButton"
                    class="send-button"
                    onclick="sendMessage(true)"
                    <?php echo !$whatsappConnected ? 'disabled' : ''; ?>
                >
                    <i class="ri-send-plane-fill text-xl"></i>
                </button>
            </div>
        </div>
    </div>
</div> -->

<script>
class ConversationsManager {
    constructor() {
        console.log('🚀 ConversationsManager constructor iniciado');
        
        this.conversations = [];
        this.currentConversation = null;
        this.isLoading = false;
        this.searchTerm = '';
        this.refreshInterval = null;
        this.whatsappConnected = <?php echo $whatsappConnected ? 'true' : 'false'; ?>;
        
        console.log('🔗 WhatsApp conectado:', this.whatsappConnected);
        
        this.init();
    }

    init() {
        console.log('⚡ Iniciando ConversationsManager...');
        
        this.bindEvents();
        this.loadConversations();
        this.setupAutoRefresh();
        this.setupTextareaAutoResize();
        
        console.log('✅ ConversationsManager inicializado correctamente');
    }

    bindEvents() {
        console.log('🎯 Vinculando eventos...');
        
        // Búsqueda
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                console.log('🔍 Búsqueda:', e.target.value);
                this.searchTerm = e.target.value.toLowerCase();
                this.filterConversations();
            });
            console.log('✅ Evento de búsqueda vinculado');
        } else {
            console.error('❌ No se encontró el input de búsqueda');
        }

        // Enter para enviar mensaje
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    console.log('⌨️ Enter presionado en mensaje desktop');
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            console.log('✅ Evento Enter desktop vinculado');
        }

        const mobileMessageInput = document.getElementById('mobileMessageInput');
        if (mobileMessageInput) {
            mobileMessageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    console.log('⌨️ Enter presionado en mensaje móvil');
                    e.preventDefault();
                    this.sendMessage(true);
                }
            });
            console.log('✅ Evento Enter móvil vinculado');
        }

        // Cleanup al salir
        window.addEventListener('beforeunload', () => {
            console.log('🧹 Limpiando antes de salir...');
            this.cleanup();
        });
        
        console.log('✅ Todos los eventos vinculados');
    }

    async loadConversations() {
        console.log('📥 Iniciando carga de conversaciones...');
        
        if (this.isLoading) {
            console.log('⏳ Ya hay una carga en progreso, saltando...');
            return;
        }
        
        this.isLoading = true;
        console.log('🔄 Estado de carga: true');
        
        try {
            console.log('🌐 Realizando fetch a /api/whatsapp-conversations...');
            const response = await fetch('/api/whatsapp-conversations?include_messages=true&limit=50');
            
            console.log('📡 Response status:', response.status);
            console.log('📡 Response headers:', [...response.headers.entries()]);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('📊 Datos recibidos:', data);
            
            if (data.success) {
                console.log('✅ API devolvió success=true');
                console.log('📝 Número de conversaciones:', data.conversations ? data.conversations.length : 'undefined');
                
                if (data.conversations) {
                    this.conversations = data.conversations;
                    console.log('💾 Conversaciones guardadas en this.conversations:', this.conversations);
                    this.renderConversations();
                } else {
                    console.warn('⚠️ data.conversations es undefined');
                    this.conversations = [];
                    this.renderConversations();
                }
            } else {
                console.error('❌ API devolvió success=false:', data.error);
                this.showError('Error cargando conversaciones: ' + (data.error || 'Error desconocido'));
            }
        } catch (error) {
            console.error('💥 Error en loadConversations:', error);
            console.error('📋 Stack trace:', error.stack);
            this.showError('Error de conexión al cargar conversaciones: ' + error.message);
        } finally {
            this.isLoading = false;
            console.log('🔄 Estado de carga: false');
        }
    }

    renderConversations() {
        console.log('🎨 Iniciando renderizado de conversaciones...');
        console.log('📊 Conversaciones a renderizar:', this.conversations.length);
        
        const container = document.getElementById('conversationsList');
        if (!container) {
            console.error('❌ No se encontró el contenedor conversationsList');
            return;
        }
        
        console.log('📦 Contenedor encontrado:', container);
        
        if (this.conversations.length === 0) {
            console.log('📭 No hay conversaciones, mostrando estado vacío');
            container.innerHTML = `
                <div class="no-conversations">
                    <i class="ri-chat-3-line text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No hay conversaciones</h3>
                    <p class="text-center text-gray-500 px-4">
                        Las conversaciones aparecerán aquí cuando recibas mensajes de WhatsApp.
                    </p>
                    <a href="/whatsapp" class="mt-4 inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="ri-whatsapp-line mr-2"></i>
                        Configurar WhatsApp
                    </a>
                </div>
            `;
            console.log('✅ Estado vacío renderizado');
            return;
        }

        console.log('🔄 Generando HTML para cada conversación...');
        const html = this.conversations.map((conv, index) => {
            console.log(`📝 Renderizando conversación ${index}:`, conv);
            return this.renderConversationItem(conv);
        }).join('');
        
        console.log('📄 HTML generado (primeros 500 chars):', html.substring(0, 500));
        
        container.innerHTML = html;
        console.log('✅ HTML insertado en el contenedor');
        
        // Verificar que se insertó correctamente
        const insertedItems = container.querySelectorAll('.conversation-item');
        console.log('🔍 Elementos conversation-item insertados:', insertedItems.length);
    }

    renderConversationItem(conversation) {
        console.log('🖼️ Renderizando item de conversación:', conversation);
        
        const isActive = this.currentConversation && this.currentConversation.phone === conversation.phone;
        const lastMessagePreview = this.truncateMessage(conversation.lastMessage, 50);
        const initials = this.getContactInitials(conversation.name);
        
        console.log('📋 Datos del item:', {
            isActive,
            lastMessagePreview,
            initials,
            phone: conversation.phone,
            name: conversation.name
        });
        
        const html = `
            <div class="conversation-item ${isActive ? 'active' : ''}" 
                 onclick="window.conversationsManager.selectConversation('${conversation.phone}')"
                 data-phone="${conversation.phone}">
                
                <div class="flex items-center">
                    <div class="conversation-avatar mr-3">
                        ${initials}
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-medium text-gray-900 truncate">${conversation.name}</h4>
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
        
        console.log('✅ HTML del item generado');
        return html;
    }

    async selectConversation(phoneNumber) {
        console.log('📞 Seleccionando conversación:', phoneNumber);
        
        const conversation = this.conversations.find(c => c.phone === phoneNumber);
        if (!conversation) {
            console.error('❌ Conversación no encontrada:', phoneNumber);
            return;
        }
        
        console.log('✅ Conversación encontrada:', conversation);

        // Marcar conversación anterior como inactiva
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        console.log('🔄 Conversaciones anteriores desmarcadas');

        // Marcar nueva conversación como activa
        const conversationElement = document.querySelector(`[data-phone="${phoneNumber}"]`);
        if (conversationElement) {
            conversationElement.classList.add('active');
            console.log('✅ Conversación marcada como activa');
        } else {
            console.error('❌ No se encontró el elemento de conversación');
        }

        this.currentConversation = conversation;
        console.log('💾 Conversación actual actualizada');
        
        // Cargar mensajes de la conversación
        console.log('📥 Cargando mensajes...');
        await this.loadMessages(phoneNumber);
        
        // Mostrar área de chat
        console.log('🖼️ Mostrando área de chat...');
        this.showChatArea();
        
        // Marcar conversación como leída
        if (conversation.unreadCount > 0) {
            console.log('📖 Marcando como leída...');
            this.markAsRead(phoneNumber);
        }

        // En móvil, mostrar chat overlay
        if (window.innerWidth < 768) {
            console.log('📱 Mostrando chat móvil...');
            this.showMobileChat();
        }
        
        console.log('✅ Conversación seleccionada correctamente');
    }

    async loadMessages(phoneNumber) {
        console.log('📨 Cargando mensajes para:', phoneNumber);
        
        try {
            const response = await fetch(`/api/whatsapp-conversations?search=${phoneNumber}&include_messages=true&limit=1`);
            console.log('📡 Response status para mensajes:', response.status);
            
            const data = await response.json();
            console.log('📊 Datos de mensajes recibidos:', data);
            
            if (data.success && data.conversations.length > 0) {
                const conversation = data.conversations[0];
                console.log('💬 Mensajes encontrados:', conversation.recentMessages?.length || 0);
                this.renderMessages(conversation.recentMessages || []);
                this.updateChatHeader(conversation);
            } else {
                console.warn('⚠️ No se encontraron mensajes para la conversación');
            }
        } catch (error) {
            console.error('💥 Error cargando mensajes:', error);
        }
    }

    renderMessages(messages) {
        console.log('💬 Renderizando mensajes:', messages.length);
        
        const container = document.getElementById('messagesContainer');
        const mobileContainer = document.getElementById('mobileMessagesContainer');
        
        if (!container) {
            console.error('❌ No se encontró messagesContainer');
            return;
        }
        
        if (messages.length === 0) {
            console.log('📭 No hay mensajes, mostrando estado vacío');
            const emptyHtml = `
                <div class="empty-chat">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="ri-chat-3-line text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-500">No hay mensajes en esta conversación</p>
                </div>
            `;
            container.innerHTML = emptyHtml;
            if (mobileContainer) mobileContainer.innerHTML = emptyHtml;
            console.log('✅ Estado vacío de mensajes renderizado');
            return;
        }

        console.log('🔄 Generando HTML de mensajes...');
        const html = messages.map(message => {
            console.log('📝 Renderizando mensaje:', message);
            return this.renderMessage(message);
        }).join('');
        
        container.innerHTML = html;
        if (mobileContainer) mobileContainer.innerHTML = html;
        console.log('✅ Mensajes renderizados');
        
        // Scroll al final
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
        console.log('📋 Actualizando header del chat:', conversation);
        
        const name = conversation.name;
        const phone = conversation.phone;
        const initials = this.getContactInitials(name);

        // Desktop
        const chatName = document.getElementById('chatName');
        const chatPhone = document.getElementById('chatPhone');
        const chatAvatar = document.getElementById('chatAvatar');
        
        if (chatName) chatName.textContent = name;
        if (chatPhone) chatPhone.textContent = phone;
        if (chatAvatar) chatAvatar.textContent = initials;

        // Mobile
        const mobileChatName = document.getElementById('mobileChatName');
        const mobileChatPhone = document.getElementById('mobileChatPhone');
        const mobileChatAvatar = document.getElementById('mobileChatAvatar');
        
        if (mobileChatName) mobileChatName.textContent = name;
        if (mobileChatPhone) mobileChatPhone.textContent = phone;
        if (mobileChatAvatar) mobileChatAvatar.textContent = initials;
        
        console.log('✅ Header del chat actualizado');
    }

    showChatArea() {
        console.log('🖼️ Mostrando área de chat...');
        
        const chatHeader = document.getElementById('chatHeader');
        const chatInputArea = document.getElementById('chatInputArea');
        
        if (chatHeader) {
            chatHeader.style.display = 'flex';
            console.log('✅ Header del chat mostrado');
        }
        
        if (chatInputArea) {
            chatInputArea.style.display = 'block';
            console.log('✅ Área de input mostrada');
        }
        
        const emptyChat = document.querySelector('.empty-chat');
        if (emptyChat) {
            emptyChat.style.display = 'none';
            console.log('✅ Estado vacío ocultado');
        }
    }

    showMobileChat() {
        console.log('📱 Mostrando chat móvil...');
        
        const mobileChatArea = document.getElementById('mobileChatArea');
        const conversationsSidebar = document.getElementById('conversationsSidebar');
        
        if (mobileChatArea) {
            mobileChatArea.classList.remove('hidden');
            console.log('✅ Área de chat móvil mostrada');
        }
        
        if (conversationsSidebar) {
            conversationsSidebar.classList.add('show-mobile');
            console.log('✅ Sidebar móvil mostrado');
        }
        
        document.body.style.overflow = 'hidden';
        console.log('✅ Scroll del body deshabilitado');
    }

    closeMobileChat() {
        console.log('📱 Cerrando chat móvil...');
        
        const mobileChatArea = document.getElementById('mobileChatArea');
        const conversationsSidebar = document.getElementById('conversationsSidebar');
        
        if (mobileChatArea) {
            mobileChatArea.classList.add('hidden');
        }
        
        if (conversationsSidebar) {
            conversationsSidebar.classList.remove('show-mobile');
        }
        
        document.body.style.overflow = '';
        console.log('✅ Chat móvil cerrado');
    }

    async sendMessage(isMobile = false) {
        console.log('📤 Enviando mensaje (móvil:', isMobile, ')');
        
        if (!this.currentConversation || !this.whatsappConnected) {
            console.log('❌ No se puede enviar: conversación actual =', this.currentConversation, ', WhatsApp conectado =', this.whatsappConnected);
            return;
        }

        const inputId = isMobile ? 'mobileMessageInput' : 'messageInput';
        const buttonId = isMobile ? 'mobileSendButton' : 'sendButton';
        const input = document.getElementById(inputId);
        const button = document.getElementById(buttonId);
        
        const message = input.value.trim();
        console.log('📝 Mensaje a enviar:', message);
        
        if (!message) {
            console.log('❌ Mensaje vacío');
            return;
        }

        // Deshabilitar input y botón
        input.disabled = true;
        button.disabled = true;
        button.innerHTML = '<div class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full"></div>';
        console.log('🔒 Input y botón deshabilitados');

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
            
            console.log('🔄 Añadiendo mensaje temporal:', tempMessage);
            this.addMessageToUI(tempMessage);
            input.value = '';

            // Enviar mensaje
            console.log('🌐 Enviando a API...');
            const response = await fetch('/api/send-whatsapp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    to: this.currentConversation.phone,
                    message: message,
                    type: 'manual'
                })
            });

            const data = await response.json();
            console.log('📊 Respuesta de envío:', data);

            if (data.success) {
                // Actualizar mensaje temporal con datos reales
                this.updateMessageInUI(tempMessage.messageId, {
                    messageId: data.messageId,
                    status: 'sent'
                });
                
                this.showNotification('Mensaje enviado', 'success');
                console.log('✅ Mensaje enviado correctamente');
            } else {
                // Marcar mensaje como fallido
                this.updateMessageInUI(tempMessage.messageId, {
                    status: 'failed'
                });
                
                this.showNotification('Error enviando mensaje: ' + data.error, 'error');
                console.log('❌ Error enviando mensaje:', data.error);
            }

        } catch (error) {
            console.error('💥 Error en sendMessage:', error);
            this.showNotification('Error de conexión', 'error');
        } finally {
            // Rehabilitar input y botón
            input.disabled = false;
            button.disabled = false;
            button.innerHTML = '<i class="ri-send-plane-fill text-xl"></i>';
            input.focus();
            console.log('🔓 Input y botón rehabilitados');
        }
    }

    addMessageToUI(message) {
        console.log('➕ Añadiendo mensaje a UI:', message);
        
        const containers = ['messagesContainer', 'mobileMessagesContainer'];
        
        containers.forEach(containerId => {
            const container = document.getElementById(containerId);
            if (!container) return;

            // Remover empty chat si existe
            const emptyChat = container.querySelector('.empty-chat');
            if (emptyChat) {
                emptyChat.remove();
                console.log('🗑️ Estado vacío removido de', containerId);
            }

            const messageHtml = this.renderMessage(message);
            container.insertAdjacentHTML('beforeend', messageHtml);
            console.log('✅ Mensaje añadido a', containerId);
        });

        this.scrollToBottom();
    }

    updateMessageInUI(tempMessageId, updates) {
        console.log('🔄 Actualizando mensaje en UI:', tempMessageId, updates);
        
        const messageElements = document.querySelectorAll(`[data-message-id="${tempMessageId}"]`);
        console.log('🔍 Elementos encontrados:', messageElements.length);
        
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
                
                // Añadir clase visual según estado
                element.classList.remove('sending', 'failed');
                if (updates.status === 'failed') {
                    element.classList.add('failed');
                }
            }
        });
        
        console.log('✅ Mensaje actualizado en UI');
    }

    async markAsRead(phoneNumber) {
        console.log('📖 Marcando como leído:', phoneNumber);
        
        try {
            await fetch('/api/whatsapp-conversations', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'mark_as_read',
                    phone_number: phoneNumber
                })
            });

            // Actualizar UI
            const conversation = this.conversations.find(c => c.phone === phoneNumber);
            if (conversation) {
                conversation.unreadCount = 0;
                const element = document.querySelector(`[data-phone="${phoneNumber}"]`);
                if (element) {
                    const badge = element.querySelector('.unread-badge');
                    if (badge) badge.remove();
                }
            }
            console.log('✅ Marcado como leído');
        } catch (error) {
            console.error('💥 Error marcando como leído:', error);
        }
    }

    filterConversations() {
        console.log('🔍 Filtrando conversaciones con término:', this.searchTerm);
        
        const filtered = this.conversations.filter(conv => {
            return conv.name.toLowerCase().includes(this.searchTerm) ||
                   conv.phone.includes(this.searchTerm) ||
                   conv.lastMessage.toLowerCase().includes(this.searchTerm);
        });

        console.log('📊 Conversaciones filtradas:', filtered.length);

        const container = document.getElementById('conversationsList');
        if (!container) {
            console.error('❌ No se encontró contenedor para filtrado');
            return;
        }
        
        const html = filtered.map(conv => this.renderConversationItem(conv)).join('');
        container.innerHTML = html || '<div class="no-conversations"><p>No se encontraron conversaciones</p></div>';
        console.log('✅ Conversaciones filtradas renderizadas');
    }

    setupAutoRefresh() {
        console.log('🔄 Configurando auto-refresh...');
        // Refrescar conversaciones cada 30 segundos
        this.refreshInterval = setInterval(() => {
            console.log('🔄 Auto-refresh ejecutándose...');
            this.loadConversations();
        }, 30000);
        console.log('✅ Auto-refresh configurado (30s)');
    }

    setupTextareaAutoResize() {
        console.log('📏 Configurando auto-resize de textareas...');
        
        const textareas = ['messageInput', 'mobileMessageInput'];
        
        textareas.forEach(id => {
            const textarea = document.getElementById(id);
            if (textarea) {
                textarea.addEventListener('input', () => {
                    textarea.style.height = 'auto';
                    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
                });
                console.log('✅ Auto-resize configurado para', id);
            }
        });
    }

    scrollToBottom() {
        const containers = ['messagesContainer', 'mobileMessagesContainer'];
        
        containers.forEach(containerId => {
            const container = document.getElementById(containerId);
            if (container) {
                setTimeout(() => {
                    container.scrollTop = container.scrollHeight;
                }, 100);
            }
        });
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
        console.log('🔔 Mostrando notificación:', type, message);
        
        // Crear notificación temporal
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 text-white ${
            type === 'success' ? 'bg-green-600' : 
            type === 'error' ? 'bg-red-600' : 'bg-blue-600'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    showError(message) {
        console.log('❌ Mostrando error:', message);
        
        const container = document.getElementById('conversationsList');
        if (!container) {
            console.error('❌ No se encontró contenedor para mostrar error');
            return;
        }
        
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
        console.log('✅ Error mostrado en UI');
    }

    cleanup() {
        console.log('🧹 Limpiando recursos...');
        
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            console.log('✅ Interval de refresh limpiado');
        }
    }
}

// Funciones globales
window.sendMessage = function(isMobile = false) {
    console.log('🌐 Función global sendMessage llamada');
    window.conversationsManager.sendMessage(isMobile);
};

window.closeMobileChat = function() {
    console.log('🌐 Función global closeMobileChat llamada');
    window.conversationsManager.closeMobileChat();
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM listo, inicializando ConversationsManager...');
    window.conversationsManager = new ConversationsManager();
});
</script>

<?php 
// Incluir el pie de página - RUTA CORREGIDA
include dirname(__DIR__) . '/includes/footer.php'; 
?>