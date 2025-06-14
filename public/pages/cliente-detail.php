<?php

// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Configurar la página actual
$currentPage = 'clientes';
$pageTitle = 'ReservaBot - Detalle de Cliente';
$pageScript = 'cliente-detail';

// Obtener teléfono de la URL
$telefono = isset($_GET['telefono']) ? trim($_GET['telefono']) : '';

if (empty($telefono)) {
    header('Location: /clientes');
    exit;
}

// Obtener usuario actual para verificar conexión WhatsApp
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

// Obtener información del cliente
try {
    // Datos generales del cliente
    $stmt = getPDO()->prepare("SELECT 
        telefono,
        nombre as ultimo_nombre,
        COUNT(id) as total_reservas,
        SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as reservas_confirmadas,
        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as reservas_pendientes,
        SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as reservas_canceladas,
        MAX(fecha) as ultima_reserva,
        MIN(created_at) as primer_contacto,
        MAX(created_at) as ultimo_contacto
        FROM reservas 
        WHERE telefono = ?
        GROUP BY telefono");
    
    $stmt->execute([$telefono]);
    $cliente = $stmt->fetch();
    
    if (!$cliente) {
        header('Location: /clientes');
        exit;
    }
    
    // Obtener historial de reservas
    $stmt = getPDO()->prepare("SELECT * FROM reservas WHERE telefono = ? ORDER BY fecha DESC, hora DESC");
    $stmt->execute([$telefono]);
    $reservas = $stmt->fetchAll();
    
    // Obtener mensajes de WhatsApp si existen
    $mensajes = [];
    try {
        // Formatear número para búsqueda
        $telefonoFormateado = preg_replace('/[^\d]/', '', $telefono);
        if (strlen($telefonoFormateado) === 9 && (substr($telefonoFormateado, 0, 1) === '6' || substr($telefonoFormateado, 0, 1) === '7' || substr($telefonoFormateado, 0, 1) === '9')) {
            $telefonoFormateado = '34' . $telefonoFormateado;
        }
        
        $stmt = getPDO()->prepare("SELECT * FROM whatsapp_messages 
                               WHERE usuario_id = ? AND phone_number = ?
                               ORDER BY timestamp_sent DESC 
                               LIMIT 50");
        $stmt->execute([$userId, $telefonoFormateado]);
        $mensajes = $stmt->fetchAll();
        
    } catch (\PDOException $e) {
        // Si las tablas de WhatsApp no existen, continuar sin mensajes
        $mensajes = [];
    }
    
} catch (\PDOException $e) {
    error_log('Error al obtener datos del cliente: ' . $e->getMessage());
    header('Location: /clientes');
    exit;
}

// Incluir la cabecera
include 'includes/header.php';
?>

<style>
/* Estilos específicos para chat WhatsApp */
.whatsapp-chat-modal {
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
}

.chat-container {
    background: #efeae2;
    background-image: url("data:image/svg+xml,%3csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3e%3cg fill='none' fill-rule='evenodd'%3e%3cg fill='%23d1fae5' fill-opacity='0.1'%3e%3cpath d='m36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3e%3c/g%3e%3c/g%3e%3c/svg%3e");
    height: 500px;
    display: flex;
    flex-direction: column;
}

.chat-header {
    background: #25d366;
    color: white;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
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
}

.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
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

/* Responsive */
@media (max-width: 768px) {
    .whatsapp-chat-modal .fixed {
        inset: 0;
        margin: 0;
    }
    
    .chat-container {
        height: 100vh;
        border-radius: 0;
    }
}
</style>

<div class="flex items-center mb-6">
    <a href="/clientes" class="mr-4 p-2 rounded-full hover:bg-gray-100">
        <i class="ri-arrow-left-line text-gray-600 text-xl"></i>
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Detalle de Cliente</h1>
</div>

<!-- Información del cliente -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="flex items-start justify-between">
        <div class="flex items-center">
            <div class="flex-shrink-0 h-16 w-16">
                <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="ri-user-line text-blue-600 text-2xl"></i>
                </div>
            </div>
            <div class="ml-6">
                <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($cliente['ultimo_nombre']); ?></h2>
                <p class="text-gray-600"><?php echo htmlspecialchars($cliente['telefono']); ?></p>
                <p class="text-sm text-gray-500 mt-1">
                    Cliente desde <?php echo date('d/m/Y', strtotime($cliente['primer_contacto'])); ?>
                </p>
            </div>
        </div>
        
        <div class="flex space-x-2">
            <button 
                onclick="openWhatsAppChat()"
                class="whatsapp-button inline-flex items-center px-3 py-2 border border-green-300 shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 <?php echo !$whatsappConnected ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                <?php echo !$whatsappConnected ? 'disabled title="WhatsApp no está conectado"' : ''; ?>
            >
                <i class="ri-whatsapp-line mr-2"></i>
                Abrir Chat WhatsApp
            </button>
            <a href="/reserva-form?telefono=<?php echo urlencode($cliente['telefono']); ?>&nombre=<?php echo urlencode($cliente['ultimo_nombre']); ?>" 
               class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="ri-add-line mr-2"></i>
                Nueva Reserva
            </a>
        </div>
    </div>
</div>

<!-- Estadísticas del cliente -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="ri-calendar-line text-2xl text-blue-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Reservas</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $cliente['total_reservas']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="ri-check-line text-2xl text-green-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Confirmadas</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $cliente['reservas_confirmadas']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="ri-time-line text-2xl text-amber-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Pendientes</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $cliente['reservas_pendientes']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="ri-message-line text-2xl text-purple-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Mensajes</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo count($mensajes); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs para historial -->
<div class="border-b border-gray-200 mb-6">
    <nav class="-mb-px flex space-x-8">
        <button 
            id="reservasTab" 
            class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
        >
            <i class="ri-calendar-line mr-2"></i>
            Historial de Reservas (<?php echo count($reservas); ?>)
        </button>
        
        <button 
            id="mensajesTab" 
            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
        >
            <i class="ri-message-line mr-2"></i>
            Mensajes WhatsApp (<?php echo count($mensajes); ?>)
        </button>
    </nav>
</div>

<!-- Contenido de reservas -->
<div id="reservasContent" class="space-y-4">
    <?php if (empty($reservas)): ?>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <i class="ri-calendar-line text-gray-400 text-4xl"></i>
            <p class="mt-2 text-gray-500">No hay reservas para este cliente</p>
        </div>
    <?php else: ?>
        <?php foreach ($reservas as $reserva): ?>
            <div class="bg-white rounded-lg shadow-sm border-l-4 <?php echo $reserva['estado'] === 'confirmada' ? 'border-green-500' : ($reserva['estado'] === 'pendiente' ? 'border-amber-500' : 'border-red-500'); ?>">
                <div class="p-4">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($reserva['fecha'])); ?> - <?php echo substr($reserva['hora'], 0, 5); ?>
                                </h3>
                                <span class="ml-3 px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php echo $reserva['estado'] === 'confirmada' ? 'bg-green-100 text-green-800' : 
                                              ($reserva['estado'] === 'pendiente' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucfirst($reserva['estado']); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($reserva['mensaje'])): ?>
                                <p class="text-sm text-gray-600 mb-2">
                                    <i class="ri-message-2-line mr-1"></i>
                                    <?php echo htmlspecialchars($reserva['mensaje']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <p class="text-xs text-gray-500">
                                Creada el <?php echo date('d/m/Y H:i', strtotime($reserva['created_at'])); ?>
                            </p>
                        </div>
                        
                        <div class="flex space-x-2">
                            <a href="/reserva-detail?id=<?php echo $reserva['id']; ?>" 
                               class="inline-flex items-center p-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="ri-eye-line"></i>
                            </a>
                            <a href="/reserva-form?id=<?php echo $reserva['id']; ?>" 
                               class="inline-flex items-center p-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="ri-edit-line"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Contenido de mensajes WhatsApp (versión simplificada para el tab) -->
<div id="mensajesContent" class="hidden">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <?php if (empty($mensajes)): ?>
            <div class="text-center py-8">
                <i class="ri-chat-3-line text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-700 mb-2">No hay mensajes</h3>
                <p class="text-gray-500 mb-4">Aún no hay conversación con este cliente por WhatsApp</p>
                <?php if ($whatsappConnected): ?>
                    <button 
                        onclick="openWhatsAppChat()"
                        class="whatsapp-button text-white px-4 py-2 rounded-lg font-medium inline-flex items-center"
                    >
                        <i class="ri-whatsapp-line mr-2"></i>
                        Iniciar Conversación
                    </button>
                <?php else: ?>
                    <a href="/whatsapp" class="text-blue-600 hover:text-blue-700 font-medium">
                        Conectar WhatsApp primero →
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="mb-4 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Conversación WhatsApp</h3>
                <p class="text-sm text-gray-600">
                    <?php echo count($mensajes); ?> mensajes intercambiados
                </p>
                <button 
                    onclick="openWhatsAppChat()"
                    class="mt-3 whatsapp-button text-white px-4 py-2 rounded-lg font-medium inline-flex items-center"
                >
                    <i class="ri-whatsapp-line mr-2"></i>
                    Abrir Chat Completo
                </button>
            </div>
            
            <!-- Vista previa de mensajes recientes -->
            <div class="space-y-3 max-h-96 overflow-y-auto">
                <?php foreach (array_slice($mensajes, 0, 10) as $mensaje): ?>
                    <div class="flex <?php echo $mensaje['direction'] === 'outgoing' ? 'justify-end' : 'justify-start'; ?>">
                        <div class="max-w-xs lg:max-w-md">
                            <div class="<?php echo $mensaje['direction'] === 'outgoing' 
                                ? 'bg-green-100 text-green-800 rounded-br-none' 
                                : 'bg-gray-100 text-gray-800 rounded-bl-none'; ?> rounded-lg px-4 py-2">
                                
                                <p class="text-sm"><?php echo nl2br(htmlspecialchars($mensaje['message_text'])); ?></p>
                            </div>
                            
                            <p class="text-xs text-gray-500 mt-1 <?php echo $mensaje['direction'] === 'outgoing' ? 'text-right' : 'text-left'; ?>">
                                <?php echo date('d/m/Y H:i', strtotime($mensaje['timestamp_sent'])); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($mensajes) > 10): ?>
                    <div class="text-center py-3 border-t">
                        <p class="text-sm text-gray-500">
                            +<?php echo count($mensajes) - 10; ?> mensajes más
                        </p>
                        <button 
                            onclick="openWhatsAppChat()"
                            class="text-green-600 hover:text-green-700 text-sm font-medium"
                        >
                            Ver conversación completa →
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de chat WhatsApp -->
<div id="whatsappChatModal" class="whatsapp-chat-modal fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl mx-auto">
        <div class="chat-container rounded-lg overflow-hidden">
            
            <!-- Header del chat -->
            <div class="chat-header">
                <button onclick="closeWhatsAppChat()" class="p-1 rounded-full hover:bg-white hover:bg-opacity-20 transition-colors">
                    <i class="ri-arrow-left-line text-xl"></i>
                </button>
                
                <div class="chat-avatar">
                    <?php 
                    $initials = '?';
                    if (!empty($cliente['ultimo_nombre']) && !str_starts_with($cliente['ultimo_nombre'], 'Contacto ')) {
                        $words = explode(' ', $cliente['ultimo_nombre']);
                        if (count($words) >= 2) {
                            $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                        } else {
                            $initials = strtoupper(substr($cliente['ultimo_nombre'], 0, 2));
                        }
                    }
                    echo $initials;
                    ?>
                </div>
                
                <div class="flex-1">
                    <h3 class="font-semibold"><?php echo htmlspecialchars($cliente['ultimo_nombre']); ?></h3>
                    <p class="text-sm opacity-90"><?php echo htmlspecialchars($cliente['telefono']); ?></p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button onclick="refreshMessages()" class="p-2 rounded-full hover:bg-white hover:bg-opacity-20 transition-colors" title="Actualizar mensajes">
                        <i class="ri-refresh-line"></i>
                    </button>
                </div>
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

<script>
class ClientWhatsAppChat {
    constructor() {
        this.clientPhone = '<?php echo addslashes($telefono); ?>';
        this.clientName = '<?php echo addslashes($cliente['ultimo_nombre']); ?>';
        this.whatsappConnected = <?php echo $whatsappConnected ? 'true' : 'false'; ?>;
        this.messages = [];
        this.isLoading = false;
        
        console.log('Inicializando chat para cliente:', this.clientPhone, this.clientName);
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupAutoResize();
        
        // Cargar mensajes del PHP
        this.loadInitialMessages();
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
            if (e.key === 'Escape') {
                this.closeChat();
            }
        });

        // Cerrar modal al hacer click fuera
        const modal = document.getElementById('whatsappChatModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
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

    openChat() {
        console.log('Abriendo chat WhatsApp');
        
        const modal = document.getElementById('whatsappChatModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Focus en el input si WhatsApp está conectado
            if (this.whatsappConnected) {
                setTimeout(() => {
                    const input = document.getElementById('chatMessageInput');
                    if (input) input.focus();
                }, 100);
            }
            
            // Cargar mensajes más recientes
            this.loadMessagesFromServer();
        }
    }

    closeChat() {
        console.log('Cerrando chat WhatsApp');
        
        const modal = document.getElementById('whatsappChatModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    loadInitialMessages() {
        console.log('Cargando mensajes iniciales desde PHP');
        
        // Cargar mensajes del PHP
        const phpMessages = <?php echo json_encode($mensajes); ?>;
        
        if (phpMessages && phpMessages.length > 0) {
            this.messages = phpMessages.map(msg => ({
                messageId: msg.message_id,
                content: msg.message_text,
                direction: msg.direction,
                isOutgoing: msg.direction === 'outgoing',
                timestamp: msg.timestamp_sent,
                status: msg.status || 'sent'
            })).reverse(); // Invertir para mostrar más recientes al final
            
            console.log('Mensajes iniciales cargados:', this.messages.length);
        } else {
            this.messages = [];
            console.log('No hay mensajes iniciales');
        }
        
        this.renderMessages();
    }

    async loadMessagesFromServer() {
        if (this.isLoading) return;
        
        console.log('Cargando mensajes del servidor...');
        this.isLoading = true;
        
        try {
            const formattedPhone = this.formatPhoneNumber(this.clientPhone);
            const response = await fetch(`/api/whatsapp-conversations?search=${formattedPhone}&include_messages=true&limit=1`);
            const data = await response.json();
            
            if (data.success && data.conversations.length > 0) {
                const conversation = data.conversations[0];
                
                if (conversation.recentMessages && conversation.recentMessages.length > 0) {
                    this.messages = conversation.recentMessages.map(msg => ({
                        messageId: msg.messageId,
                        content: msg.content,
                        direction: msg.direction || (msg.isOutgoing ? 'outgoing' : 'incoming'),
                        isOutgoing: msg.isOutgoing,
                        timestamp: msg.timestamp,
                        status: msg.status || 'sent'
                    }));
                    
                    console.log('Mensajes actualizados del servidor:', this.messages.length);
                    this.renderMessages();
                } else {
                    console.log('No se encontraron mensajes nuevos en el servidor');
                }
            } else {
                console.log('No se encontró conversación en el servidor');
            }
        } catch (error) {
            console.error('Error cargando mensajes del servidor:', error);
        } finally {
            this.isLoading = false;
        }
    }

    renderMessages() {
        console.log('Renderizando mensajes:', this.messages.length);
        
        const container = document.getElementById('chatMessagesArea');
        if (!container) {
            console.error('No se encontró el contenedor de mensajes');
            return;
        }
        
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
        
        // Scroll al final
        this.scrollToBottom();
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

    async refreshMessages() {
        console.log('Refrescando mensajes...');
        await this.loadMessagesFromServer();
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

// Instancia global del chat
let clientWhatsAppChat;

// Funciones globales
function openWhatsAppChat() {
    if (!clientWhatsAppChat) {
        clientWhatsAppChat = new ClientWhatsAppChat();
    }
    clientWhatsAppChat.openChat();
}

function closeWhatsAppChat() {
    if (clientWhatsAppChat) {
        clientWhatsAppChat.closeChat();
    }
}

function sendChatMessage() {
    if (clientWhatsAppChat) {
        clientWhatsAppChat.sendMessage();
    }
}

function refreshMessages() {
    if (clientWhatsAppChat) {
        clientWhatsAppChat.refreshMessages();
    }
}

// JavaScript para los tabs (mantener funcionalidad existente)
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar chat
    clientWhatsAppChat = new ClientWhatsAppChat();
    
    // Gestión de tabs
    const reservasTab = document.getElementById('reservasTab');
    const mensajesTab = document.getElementById('mensajesTab');
    const reservasContent = document.getElementById('reservasContent');
    const mensajesContent = document.getElementById('mensajesContent');
    
    if (reservasTab && mensajesTab) {
        reservasTab.addEventListener('click', function() {
            // Activar tab de reservas
            reservasTab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            reservasTab.classList.add('border-blue-500', 'text-blue-600');
            
            // Desactivar tab de mensajes
            mensajesTab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            mensajesTab.classList.remove('border-blue-500', 'text-blue-600');
            
            // Mostrar contenido de reservas
            reservasContent.classList.remove('hidden');
            if (mensajesContent) mensajesContent.classList.add('hidden');
        });
        
        mensajesTab.addEventListener('click', function() {
            // Activar tab de mensajes
            mensajesTab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            mensajesTab.classList.add('border-blue-500', 'text-blue-600');
            
            // Desactivar tab de reservas
            reservasTab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            reservasTab.classList.remove('border-blue-500', 'text-blue-600');
            
            // Mostrar contenido de mensajes
            if (mensajesContent) mensajesContent.classList.remove('hidden');
            reservasContent.classList.add('hidden');
        });
    }
});
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>
            </div>