<?php
// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Configurar la página actual
$currentPage = 'whatsapp';
$pageTitle = 'ReservaBot - WhatsApp';
$pageScript = 'whatsapp';

// Obtener usuario actual
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

// Obtener configuración WhatsApp actual
$whatsappConfig = null;
try {
    $stmt = getPDO()->prepare('SELECT * FROM whatsapp_config WHERE usuario_id = ?');
    $stmt->execute([$userId]);
    $whatsappConfig = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Error obteniendo configuración WhatsApp: ' . $e->getMessage());
}

// Inicializar variables
$connectionStatus = $whatsappConfig['status'] ?? 'disconnected';
$phoneNumber = $whatsappConfig['phone_number'] ?? null;
$lastActivity = $whatsappConfig['last_activity'] ?? null;

// Incluir la cabecera
include 'includes/header.php';
?>

<style>
/* Estilos específicos para la página de WhatsApp */
.whatsapp-card {
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
}

.whatsapp-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.status-indicator {
    animation: pulse 2s infinite;
}

.status-indicator.connected {
    background: linear-gradient(135deg, #10b981, #059669);
}

.status-indicator.connecting {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.status-indicator.disconnected {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.qr-container {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: 2px dashed #cbd5e1;
    transition: all 0.3s ease;
}

.qr-container.active {
    border-color: #10b981;
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.whatsapp-button {
    background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
    box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
    transition: all 0.3s ease;
}

.whatsapp-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
}

.whatsapp-button:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.feature-item {
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.feature-item:hover {
    border-left-color: #25d366;
    background: rgba(37, 211, 102, 0.05);
    transform: translateX(5px);
}

.stats-card {
    background: linear-gradient(135deg, rgba(37, 211, 102, 0.1) 0%, rgba(18, 140, 126, 0.05) 100%);
    border: 1px solid rgba(37, 211, 102, 0.2);
}

.pulse-animation {
    animation: pulse-custom 2s infinite;
}

@keyframes pulse-custom {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.fade-in {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .grid-cols-1 {
        gap: 1rem;
    }
    
    .whatsapp-card {
        padding: 1rem;
    }
    
    .qr-container {
        min-height: 250px;
    }
}
</style>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <i class="ri-whatsapp-line text-green-500 mr-3 text-3xl"></i>
            WhatsApp
        </h1>
        <p class="text-gray-600 mt-1">Conecta tu WhatsApp para automatizar la comunicación con tus clientes</p>
    </div>
    
    <!-- Estado de conexión -->
    <div class="flex items-center space-x-3">
        <div class="status-indicator w-3 h-3 rounded-full <?php echo $connectionStatus; ?>"></div>
        <span class="text-sm font-medium text-gray-700 capitalize">
            <?php 
            $statusLabels = [
                'connected' => 'Conectado',
                'connecting' => 'Conectando...',
                'disconnected' => 'Desconectado'
            ];
            echo $statusLabels[$connectionStatus] ?? 'Desconectado';
            ?>
        </span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Panel principal de configuración -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Tarjeta de conexión principal -->
        <div class="whatsapp-card rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Configuración de Conexión</h2>
                <?php if ($connectionStatus === 'connected' && $phoneNumber): ?>
                    <div class="flex items-center space-x-2 bg-green-50 px-3 py-1 rounded-full">
                        <i class="ri-phone-line text-green-600"></i>
                        <span class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($phoneNumber); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Estado desconectado -->
            <div id="disconnectedState" class="text-center py-8 <?php echo $connectionStatus !== 'disconnected' ? 'hidden' : ''; ?>">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-smartphone-line text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Conecta tu WhatsApp</h3>
                <p class="text-gray-600 mb-6 max-w-md mx-auto">
                    Para comenzar a automatizar la comunicación con tus clientes, conecta tu cuenta de WhatsApp escaneando el código QR.
                </p>
                <button id="connectBtn" class="whatsapp-button text-white px-6 py-3 rounded-lg font-medium inline-flex items-center">
                    <i class="ri-qr-code-line mr-2"></i>
                    Conectar WhatsApp
                </button>
            </div>
            
            <!-- Estado conectando/QR -->
            <div id="qrState" class="text-center <?php echo $connectionStatus !== 'connecting' ? 'hidden' : ''; ?>">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Escanea el código QR</h3>
                <div id="qrContainer" class="qr-container rounded-xl p-8 mb-6 flex items-center justify-center min-h-[300px]">
                    <div class="text-center">
                        <div class="pulse-animation mb-4">
                            <i class="ri-qr-code-line text-gray-400 text-6xl"></i>
                        </div>
                        <p class="text-gray-500">Generando código QR...</p>
                    </div>
                </div>
                
                <div class="bg-blue-50 rounded-lg p-4 mb-4">
                    <h4 class="font-medium text-blue-900 mb-2">Instrucciones:</h4>
                    <ol class="text-sm text-blue-800 space-y-1 text-left max-w-md mx-auto">
                        <li>1. Abre WhatsApp en tu teléfono</li>
                        <li>2. Ve a <strong>Configuración → Dispositivos vinculados</strong></li>
                        <li>3. Toca <strong>"Vincular un dispositivo"</strong></li>
                        <li>4. Escanea este código QR con tu teléfono</li>
                    </ol>
                </div>
                
                <button id="refreshQrBtn" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                    <i class="ri-refresh-line mr-1"></i>
                    Actualizar código QR
                </button>
            </div>
            
            <!-- Estado conectado -->
            <div id="connectedState" class="<?php echo $connectionStatus !== 'connected' ? 'hidden' : ''; ?>">
                <div class="text-center py-6 mb-6">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-whatsapp-line text-green-600 text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">¡WhatsApp conectado correctamente!</h3>
                    <p class="text-gray-600 mb-4">Tu cuenta está lista para enviar y recibir mensajes automáticamente.</p>
                    
                    <?php if ($lastActivity): ?>
                        <p class="text-sm text-gray-500">
                            Última actividad: <?php echo date('d/m/Y H:i', strtotime($lastActivity)); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="bg-red-50 rounded-lg p-4 text-center">
                    <button id="disconnectBtn" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors font-medium">
                        <i class="ri-logout-box-line mr-2"></i>
                        Desconectar WhatsApp
                    </button>
                    <p class="text-sm text-red-600 mt-2">Esto desvinculará tu cuenta de WhatsApp de ReservaBot</p>
                </div>
            </div>
        </div>
        
        <!-- Configuración de mensajes automáticos -->
        <div id="autoMessagesSection" class="whatsapp-card rounded-xl shadow-lg p-6 <?php echo $connectionStatus !== 'connected' ? 'opacity-50 pointer-events-none' : ''; ?>">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="ri-message-line text-green-600 mr-2"></i>
                Mensajes Automáticos
            </h3>
            <p class="text-gray-600 mb-6">Configura qué mensajes se enviarán automáticamente a tus clientes.</p>
            
            <div class="space-y-4">
                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                    <input type="checkbox" id="autoConfirmation" class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-4">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">Confirmación de reservas</h4>
                        <p class="text-sm text-gray-600">Enviar confirmación automática cuando se cree una nueva reserva</p>
                        <p class="text-xs text-gray-500 mt-1">Ejemplo: "Tu reserva ha sido confirmada para el {fecha} a las {hora}"</p>
                    </div>
                </label>
                
                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                    <input type="checkbox" id="autoReminders" class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-4">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">Recordatorios automáticos</h4>
                        <p class="text-sm text-gray-600">Enviar recordatorio 24 horas antes de la cita</p>
                        <p class="text-xs text-gray-500 mt-1">Se envía automáticamente a las 10:00 AM del día anterior</p>
                    </div>
                </label>
                
                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                    <input type="checkbox" id="autoWelcome" class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-4">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">Mensaje de bienvenida</h4>
                        <p class="text-sm text-gray-600">Responder automáticamente cuando un cliente escriba por primera vez</p>
                        <p class="text-xs text-gray-500 mt-1">Solo se envía una vez por cliente nuevo</p>
                    </div>
                </label>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-200">
                <button onclick="saveAutoMessageConfig()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    <i class="ri-save-line mr-2"></i>
                    Guardar Configuración
                </button>
                <a href="/configuracion#mensajes" class="ml-3 text-blue-600 hover:text-blue-700 text-sm font-medium">
                    Personalizar mensajes →
                </a>
            </div>
        </div>
        
        <!-- Nueva sección: Envío rápido de mensaje -->
        <div id="quickMessageSection" class="whatsapp-card rounded-xl shadow-lg p-6 <?php echo $connectionStatus !== 'connected' ? 'opacity-50 pointer-events-none' : ''; ?>">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="ri-send-plane-line text-green-600 mr-2"></i>
                Envío Rápido
            </h3>
            <p class="text-gray-600 mb-4">Envía un mensaje rápido a cualquier cliente.</p>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Número de teléfono</label>
                    <input type="tel" id="quickMessagePhone" placeholder="Ej: 34612345678" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <p class="text-xs text-gray-500 mt-1">Incluye código de país (ej: 34 para España)</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mensaje</label>
                    <textarea id="quickMessageText" rows="3" placeholder="Escribe tu mensaje aquí..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 resize-none"></textarea>
                    <p class="text-xs text-gray-500 mt-1 flex justify-between">
                        <span>Máximo 1000 caracteres</span>
                        <span id="charCount">0/1000</span>
                    </p>
                </div>
                
                <button onclick="sendQuickMessage()" id="sendQuickBtn" 
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors font-medium flex items-center justify-center">
                    <i class="ri-send-plane-fill mr-2"></i>
                    Enviar Mensaje
                </button>
            </div>
        </div>
    </div>
    
    <!-- Panel lateral -->
    <div class="space-y-6">
        
        <!-- Estadísticas de WhatsApp -->
        <div id="statsCard" class="stats-card whatsapp-card rounded-xl shadow-lg p-6 <?php echo $connectionStatus !== 'connected' ? 'opacity-50' : ''; ?>">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="ri-bar-chart-line mr-2 text-green-600"></i>
                Estadísticas de Hoy
            </h3>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Mensajes enviados</span>
                    <span class="text-lg font-bold text-gray-900" id="messagesSent">0</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Mensajes recibidos</span>
                    <span class="text-lg font-bold text-gray-900" id="messagesReceived">0</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Conversaciones activas</span>
                    <span class="text-lg font-bold text-gray-900" id="activeChats">0</span>
                </div>
            </div>
            
            <!-- Gráfico simple de estadísticas -->
            <div id="statsChart" class="mt-4 pt-4 border-t border-green-200">
                <!-- El gráfico se renderiza dinámicamente -->
            </div>
            
            <div class="mt-4 pt-4 border-t border-green-200">
                <a href="/estadisticas?filter=whatsapp" class="text-green-600 hover:text-green-700 text-sm font-medium inline-flex items-center">
                    Ver estadísticas completas
                    <i class="ri-arrow-right-line ml-1"></i>
                </a>
            </div>
        </div>
        
        <!-- Preview de conversaciones recientes -->
        <div id="conversationsCard" class="whatsapp-card rounded-xl shadow-lg p-6 <?php echo $connectionStatus !== 'connected' ? 'opacity-50' : ''; ?>">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="ri-chat-history-line mr-2 text-green-600"></i>
                    Conversaciones Recientes
                </h3>
                <a href="/mensajes" class="text-green-600 hover:text-green-700 text-sm font-medium">
                    Ver todas →
                </a>
            </div>
            
            <div id="conversationsPreview" class="space-y-2">
                <div class="text-center text-gray-500 py-4">
                    <i class="ri-chat-3-line text-2xl mb-2"></i>
                    <p class="text-sm">Conecta WhatsApp para ver conversaciones</p>
                </div>
            </div>
        </div>
        
        <!-- Enlaces rápidos -->
        <div class="whatsapp-card rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Enlaces Rápidos</h3>
            
            <div class="space-y-3">
                <a href="/mensajes" class="feature-item p-3 rounded-lg block">
                    <div class="flex items-center">
                        <i class="ri-chat-history-line text-green-600 mr-3"></i>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Historial Completo</h4>
                            <p class="text-xs text-gray-600">Todas las conversaciones</p>
                        </div>
                    </div>
                </a>
                
                <a href="/configuracion#whatsapp" class="feature-item p-3 rounded-lg block">
                    <div class="flex items-center">
                        <i class="ri-settings-line text-green-600 mr-3"></i>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Configuración Avanzada</h4>
                            <p class="text-xs text-gray-600">Personalizar mensajes</p>
                        </div>
                    </div>
                </a>
                
                <a href="/clientes" class="feature-item p-3 rounded-lg block">
                    <div class="flex items-center">
                        <i class="ri-user-line text-green-600 mr-3"></i>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Base de Clientes</h4>
                            <p class="text-xs text-gray-600">Gestionar contactos</p>
                        </div>
                    </div>
                </a>
                
                <button onclick="testWhatsAppConnection()" class="feature-item p-3 rounded-lg block w-full text-left">
                    <div class="flex items-center">
                        <i class="ri-test-tube-line text-green-600 mr-3"></i>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Probar Conexión</h4>
                            <p class="text-xs text-gray-600">Verificar estado del servidor</p>
                        </div>
                    </div>
                </button>
            </div>
        </div>
        
        <!-- Estado del servidor -->
        <div id="serverStatusCard" class="whatsapp-card rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Estado del Servidor</h3>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Servidor WhatsApp</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800" id="serverStatus">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1"></span>
                        Online
                    </span>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Última verificación</span>
                    <span class="text-xs text-gray-500" id="lastCheck">--</span>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Tiempo de respuesta</span>
                    <span class="text-xs text-gray-500" id="responseTime">-- ms</span>
                </div>
            </div>
            
            <button onclick="refreshServerStatus()" class="mt-4 w-full text-sm text-green-600 hover:text-green-700 font-medium">
                <i class="ri-refresh-line mr-1"></i>
                Actualizar Estado
            </button>
        </div>
        
        <!-- Funcionalidades próximas -->
        <div class="whatsapp-card rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Próximamente</h3>
            
            <div class="space-y-3">
                <div class="feature-item p-3 rounded-lg opacity-60">
                    <div class="flex items-center">
                        <i class="ri-robot-line text-gray-400 mr-3"></i>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">IA para Reservas</h4>
                            <p class="text-xs text-gray-400">Reservas automáticas con IA</p>
                        </div>
                    </div>
                </div>
                
                <div class="feature-item p-3 rounded-lg opacity-60">
                    <div class="flex items-center">
                        <i class="ri-team-line text-gray-400 mr-3"></i>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Grupos WhatsApp</h4>
                            <p class="text-xs text-gray-400">Gestión de grupos</p>
                        </div>
                    </div>
                </div>
                
                <div class="feature-item p-3 rounded-lg opacity-60">
                    <div class="flex items-center">
                        <i class="ri-file-line text-gray-400 mr-3"></i>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Multimedia</h4>
                            <p class="text-xs text-gray-400">Envío de archivos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para desconectar -->
<div id="disconnectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">¿Desconectar WhatsApp?</h3>
        <p class="text-gray-600 mb-6">Esta acción desvinculará tu cuenta de WhatsApp de ReservaBot. Podrás volver a conectarla cuando quieras.</p>
        
        <div class="flex space-x-3">
            <button id="confirmDisconnect" class="flex-1 bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors">
                Desconectar
            </button>
            <button id="cancelDisconnect" class="flex-1 bg-gray-200 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-300 transition-colors">
                Cancelar
            </button>
        </div>
    </div>
</div>

<script>
    // JavaScript para whatsapp.php - Versión corregida
// Variables globales
let currentStatus = '<?php echo $connectionStatus; ?>';
let checkStatusInterval = null;

// Elementos del DOM - se inicializarán en DOMContentLoaded
let connectBtn, disconnectBtn, refreshQrBtn, disconnectModal, confirmDisconnect, cancelDisconnect;
let quickMessageText, charCount;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar elementos del DOM
    connectBtn = document.getElementById('connectBtn');
    disconnectBtn = document.getElementById('disconnectBtn');
    refreshQrBtn = document.getElementById('refreshQrBtn');
    disconnectModal = document.getElementById('disconnectModal');
    confirmDisconnect = document.getElementById('confirmDisconnect');
    cancelDisconnect = document.getElementById('cancelDisconnect');
    quickMessageText = document.getElementById('quickMessageText');
    charCount = document.getElementById('charCount');
    
    // Verificar que currentStatus esté definido
    if (typeof currentStatus === 'undefined' || !currentStatus) {
        currentStatus = 'disconnected';
        console.warn('currentStatus no definido, usando valor por defecto: disconnected');
    }
    
    // Event listeners principales
    if (connectBtn) {
        connectBtn.addEventListener('click', connectWhatsApp);
    }
    
    if (disconnectBtn) {
        disconnectBtn.addEventListener('click', () => {
            disconnectModal.classList.remove('hidden');
            disconnectModal.classList.add('flex');
        });
    }
    
    if (refreshQrBtn) {
        refreshQrBtn.addEventListener('click', refreshQR);
    }
    
    if (confirmDisconnect) {
        confirmDisconnect.addEventListener('click', disconnectWhatsApp);
    }
    
    if (cancelDisconnect) {
        cancelDisconnect.addEventListener('click', () => {
            disconnectModal.classList.add('hidden');
            disconnectModal.classList.remove('flex');
        });
    }
    
    // Contador de caracteres para mensaje rápido
    if (quickMessageText && charCount) {
        quickMessageText.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = `${count}/1000`;
            
            if (count > 1000) {
                charCount.classList.add('text-red-500');
                this.value = this.value.substring(0, 1000);
                charCount.textContent = '1000/1000';
            } else {
                charCount.classList.remove('text-red-500');
            }
        });
    }
    
    // Inicialización según estado actual
    initializePage();
    
    // Auto-verificar estado del servidor cada 2 minutos si está conectado
    if (currentStatus === 'connected' || currentStatus === 'ready') {
        setInterval(refreshServerStatus, 120000);
    }
});

// Inicializar página según estado
function initializePage() {
    console.log('Inicializando página con estado:', currentStatus);
    
    if (currentStatus === 'connecting' || currentStatus === 'waiting_qr') {
        startStatusCheck();
    } else if (currentStatus === 'connected' || currentStatus === 'ready') {
        loadInitialData();
        loadAutoMessageConfig();
        loadStats();
    }
    
    // Actualizar estado del servidor
    refreshServerStatus();
}

// =============== FUNCIONES DE CONEXIÓN ===============

async function connectWhatsApp() {
    try {
        updateButtonState(connectBtn, true, 'Conectando...');
        
        const response = await fetch('/api/whatsapp-connect', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateConnectionStatus('connecting');
            startStatusCheck();
            showNotification('Iniciando conexión de WhatsApp...', 'info');
        } else {
            showNotification('Error al conectar: ' + (data.error || 'Error desconocido'), 'error');
            updateButtonState(connectBtn, false, '<i class="ri-qr-code-line mr-2"></i>Conectar WhatsApp');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al conectar con el servidor', 'error');
        updateButtonState(connectBtn, false, '<i class="ri-qr-code-line mr-2"></i>Conectar WhatsApp');
    }
}

async function disconnectWhatsApp() {
    try {
        updateButtonState(confirmDisconnect, true, 'Desconectando...');
        
        const response = await fetch('/api/whatsapp-disconnect', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateConnectionStatus('disconnected');
            stopStatusCheck();
            disconnectModal.classList.add('hidden');
            disconnectModal.classList.remove('flex');
            showNotification('WhatsApp desconectado correctamente', 'success');
        } else {
            showNotification('Error al desconectar: ' + (data.error || 'Error desconocido'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al desconectar', 'error');
    } finally {
        updateButtonState(confirmDisconnect, false, 'Desconectar');
    }
}

// =============== FUNCIONES DE ESTADO ===============

async function checkStatus() {
    try {
        const response = await fetch('/api/whatsapp-status');
        const data = await response.json();
        
        if (data.success && data.status !== currentStatus) {
            updateConnectionStatus(data.status, data.phoneNumber);
            
            if (data.status === 'ready' || data.status === 'connected') {
                stopStatusCheck();
                showNotification('¡WhatsApp conectado correctamente!', 'success');
                loadInitialData();
                loadAutoMessageConfig();
                loadStats();
            } else if (data.status === 'disconnected') {
                stopStatusCheck();
            }
        }
        
        // Actualizar QR si está disponible
        if (data.qr && (currentStatus === 'connecting' || currentStatus === 'waiting_qr')) {
            updateQRCode(data.qr);
        }
    } catch (error) {
        console.error('Error checking status:', error);
    }
}

function updateConnectionStatus(status, phoneNumber = null) {
    currentStatus = status;
    console.log('Actualizando estado a:', status);
    
    // Ocultar todos los estados
    const disconnectedState = document.getElementById('disconnectedState');
    const qrState = document.getElementById('qrState');
    const connectedState = document.getElementById('connectedState');
    
    if (disconnectedState) disconnectedState.classList.add('hidden');
    if (qrState) qrState.classList.add('hidden');
    if (connectedState) connectedState.classList.add('hidden');
    
    // Actualizar indicador de estado
    const statusIndicator = document.querySelector('.status-indicator');
    if (statusIndicator) {
        const statusClass = (status === 'ready' || status === 'connected') ? 'connected' : status;
        statusIndicator.className = `status-indicator w-3 h-3 rounded-full ${statusClass}`;
    }
    
    const statusLabels = {
        'ready': 'Conectado',
        'connected': 'Conectado',
        'connecting': 'Conectando...',
        'waiting_qr': 'Esperando QR...',
        'disconnected': 'Desconectado'
    };
    
    const statusText = statusIndicator ? statusIndicator.nextElementSibling : null;
    if (statusText) {
        statusText.textContent = statusLabels[status] || 'Desconectado';
    }
    
    // Mostrar estado correspondiente
    switch (status) {
        case 'disconnected':
            if (disconnectedState) disconnectedState.classList.remove('hidden');
            if (connectBtn) {
                updateButtonState(connectBtn, false, '<i class="ri-qr-code-line mr-2"></i>Conectar WhatsApp');
            }
            break;
            
        case 'connecting':
        case 'waiting_qr':
            if (qrState) qrState.classList.remove('hidden');
            break;
            
        case 'ready':
        case 'connected':
            if (connectedState) connectedState.classList.remove('hidden');
            if (phoneNumber) {
                const phoneElements = document.querySelectorAll('#connectedState .text-green-800');
                phoneElements.forEach(el => {
                    if (el.textContent.includes('34') || el.textContent === '') {
                        el.textContent = phoneNumber;
                    }
                });
            }
            break;
    }
    
    // Actualizar estado de secciones dependientes
    const dependentSections = [
        'autoMessagesSection',
        'statsCard',
        'conversationsCard',
        'quickMessageSection'
    ];
    
    dependentSections.forEach(sectionId => {
        const section = document.getElementById(sectionId);
        if (section) {
            if (status === 'ready' || status === 'connected') {
                section.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                section.classList.add('opacity-50', 'pointer-events-none');
            }
        }
    });
}

function updateQRCode(qrDataUrl) {
    const qrContainer = document.getElementById('qrContainer');
    if (qrContainer) {
        qrContainer.innerHTML = `
            <div class="bg-white p-4 rounded-lg shadow-sm inline-block">
                <img src="${qrDataUrl}" alt="Código QR WhatsApp" class="w-full max-w-xs mx-auto rounded-lg">
            </div>
        `;
        qrContainer.classList.add('active');
    }
}

function startStatusCheck() {
    if (checkStatusInterval) return;
    checkStatusInterval = setInterval(checkStatus, 3000); // Cada 3 segundos
    console.log('Iniciando verificación de estado WhatsApp...');
}

function stopStatusCheck() {
    if (checkStatusInterval) {
        clearInterval(checkStatusInterval);
        checkStatusInterval = null;
        console.log('Deteniendo verificación de estado WhatsApp...');
    }
}

function refreshQR() {
    const qrContainer = document.getElementById('qrContainer');
    if (qrContainer) {
        qrContainer.innerHTML = `
            <div class="text-center">
                <div class="pulse-animation mb-4">
                    <i class="ri-qr-code-line text-gray-400 text-6xl"></i>
                </div>
                <p class="text-gray-500">Actualizando código QR...</p>
            </div>
        `;
        qrContainer.classList.remove('active');
    }
    
    // Reiniciar conexión para generar nuevo QR
    connectWhatsApp();
}

// =============== FUNCIONES DE ENVÍO ===============

async function sendQuickMessage() {
    const phoneInput = document.getElementById('quickMessagePhone');
    const messageInput = document.getElementById('quickMessageText');
    const sendBtn = document.getElementById('sendQuickBtn');
    
    if (!phoneInput || !messageInput || !sendBtn) return;
    
    const phone = phoneInput.value.trim();
    const message = messageInput.value.trim();
    
    if (!phone || !message) {
        showNotification('Por favor, completa todos los campos', 'warning');
        return;
    }
    
    // Validar formato de teléfono
    if (!/^\d{8,15}$/.test(phone.replace(/[^\d]/g, ''))) {
        showNotification('Formato de teléfono inválido. Usa solo números (ej: 34612345678)', 'error');
        return;
    }
    
    if (message.length > 1000) {
        showNotification('El mensaje no puede tener más de 1000 caracteres', 'error');
        return;
    }
    
    try {
        updateButtonState(sendBtn, true, 'Enviando...');
        
        const response = await fetch('/api/send-whatsapp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                to: phone,
                message: message,
                clientName: null
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Mensaje enviado correctamente', 'success');
            phoneInput.value = '';
            messageInput.value = '';
            if (charCount) charCount.textContent = '0/1000';
            
            // Actualizar estadísticas
            setTimeout(loadStats, 1000);
        } else {
            if (data.queued) {
                showNotification('Mensaje añadido a la cola (WhatsApp no conectado)', 'warning');
            } else {
                showNotification('Error enviando mensaje: ' + data.error, 'error');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error enviando mensaje', 'error');
    } finally {
        updateButtonState(sendBtn, false, '<i class="ri-send-plane-fill mr-2"></i>Enviar Mensaje');
    }
}

// =============== FUNCIONES DE CONFIGURACIÓN ===============

async function loadAutoMessageConfig() {
    try {
        const response = await fetch('/api/get-auto-message-config');
        const data = await response.json();
        
        if (data.success) {
            const checkboxes = {
                'autoConfirmation': data.config.confirmacion || false,
                'autoReminders': data.config.recordatorio || false,
                'autoWelcome': data.config.bienvenida || false
            };
            
            Object.entries(checkboxes).forEach(([id, checked]) => {
                const checkbox = document.getElementById(id);
                if (checkbox) checkbox.checked = checked;
            });
        }
    } catch (error) {
        console.error('Error cargando configuración:', error);
    }
}

async function saveAutoMessageConfig() {
    const config = {
        confirmacion: document.getElementById('autoConfirmation')?.checked || false,
        recordatorio: document.getElementById('autoReminders')?.checked || false,
        bienvenida: document.getElementById('autoWelcome')?.checked || false
    };
    
    try {
        const response = await fetch('/api/save-auto-message-config', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(config)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Configuración guardada correctamente', 'success');
        } else {
            showNotification('Error guardando configuración: ' + data.error, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error guardando configuración', 'error');
    }
}

// =============== FUNCIONES DE ESTADÍSTICAS ===============

async function loadStats() {
    try {
        const response = await fetch('/api/whatsapp-stats');
        const data = await response.json();
        
        if (data.success) {
            updateStatsDisplay(data.stats);
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

function updateStatsDisplay(stats) {
    const elements = {
        'messagesSent': stats.messagesSent || 0,
        'messagesReceived': stats.messagesReceived || 0,
        'activeChats': stats.activeChats || 0
    };
    
    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    });
}

async function loadInitialData() {
    try {
        await Promise.all([
            loadStats(),
            loadConversationsPreview(),
            refreshServerStatus()
        ]);
    } catch (error) {
        console.error('Error cargando datos iniciales:', error);
    }
}

async function loadConversationsPreview() {
    try {
        const response = await fetch('/api/whatsapp-conversations?limit=3');
        const data = await response.json();
        
        const container = document.getElementById('conversationsPreview');
        if (!container) return;
        
        if (data.success && data.conversations.length > 0) {
            container.innerHTML = data.conversations.map(conv => `
                <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="flex justify-between items-start mb-1">
                        <span class="font-medium text-sm">${conv.name || conv.phone}</span>
                        <span class="text-xs text-gray-500">${conv.lastMessageTime}</span>
                    </div>
                    <p class="text-sm text-gray-600 truncate">${conv.lastMessage}</p>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="text-center text-gray-500 py-4">
                    <i class="ri-chat-3-line text-2xl mb-2"></i>
                    <p class="text-sm">No hay conversaciones recientes</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error cargando conversaciones:', error);
    }
}

// =============== FUNCIONES DE SERVIDOR ===============

async function testWhatsAppConnection() {
    showNotification('Probando conexión...', 'info');
    
    try {
        const startTime = Date.now();
        const response = await fetch('/api/whatsapp-status');
        const responseTime = Date.now() - startTime;
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(`Conexión OK (${responseTime}ms)`, 'success');
            updateServerStatus('online', responseTime);
        } else {
            showNotification('Error en la conexión: ' + data.error, 'error');
            updateServerStatus('error', responseTime);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('No se pudo conectar con el servidor', 'error');
        updateServerStatus('offline', null);
    }
}

function updateServerStatus(status, responseTime) {
    const serverStatusEl = document.getElementById('serverStatus');
    const lastCheckEl = document.getElementById('lastCheck');
    const responseTimeEl = document.getElementById('responseTime');
    
    if (serverStatusEl) {
        const statusConfig = {
            online: {
                class: 'bg-green-100 text-green-800',
                text: 'Online',
                icon: 'bg-green-500'
            },
            offline: {
                class: 'bg-red-100 text-red-800',
                text: 'Offline',
                icon: 'bg-red-500'
            },
            error: {
                class: 'bg-yellow-100 text-yellow-800',
                text: 'Error',
                icon: 'bg-yellow-500'
            }
        };
        
        const config = statusConfig[status] || statusConfig.offline;
        serverStatusEl.className = `inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${config.class}`;
        serverStatusEl.innerHTML = `<span class="w-1.5 h-1.5 ${config.icon} rounded-full mr-1"></span>${config.text}`;
    }
    
    if (lastCheckEl) {
        lastCheckEl.textContent = new Date().toLocaleTimeString('es-ES', { 
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit'
        });
    }
    
    if (responseTimeEl) {
        responseTimeEl.textContent = responseTime ? `${responseTime} ms` : '-- ms';
    }
}

async function refreshServerStatus() {
    await testWhatsAppConnection();
}

// =============== FUNCIONES HELPER ===============

function updateButtonState(button, loading, text) {
    if (!button) return;
    
    if (loading) {
        button.disabled = true;
        if (typeof text === 'string' && !text.includes('<')) {
            button.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                ${text}
            `;
        } else {
            button.innerHTML = text;
        }
    } else {
        button.disabled = false;
        button.innerHTML = text;
    }
}

function showNotification(message, type = 'info') {
    // Buscar elementos de notificación existentes
    let successMessage = document.getElementById('successMessage');
    let errorMessage = document.getElementById('errorMessage');
    
    // Si no existen, crearlos dinámicamente
    if (!successMessage) {
        successMessage = createNotificationElement('successMessage', 'success');
    }
    if (!errorMessage) {
        errorMessage = createNotificationElement('errorMessage', 'error');
    }
    
    const isSuccess = type === 'success' || type === 'info';
    const messageEl = isSuccess ? successMessage : errorMessage;
    const textEl = messageEl.querySelector('[id$="Text"]');
    
    if (textEl) {
        textEl.textContent = message;
        messageEl.classList.remove('hidden');
        
        setTimeout(() => {
            messageEl.classList.add('hidden');
        }, 5000);
    } else {
        // Fallback
        console.log(`${type.toUpperCase()}:`, message);
        if (type === 'error') {
            alert('Error: ' + message);
        }
    }
}

function createNotificationElement(id, type) {
    const isSuccess = type === 'success';
    const bgColor = isSuccess ? 'bg-green-100' : 'bg-red-100';
    const textColor = isSuccess ? 'text-green-800' : 'text-red-800';
    const icon = isSuccess ? 'ri-check-line' : 'ri-error-warning-line';
    
    const element = document.createElement('div');
    element.id = id;
    element.className = `hidden fixed top-4 right-4 ${bgColor} ${textColor} px-4 py-3 rounded-lg shadow-lg z-50`;
    element.innerHTML = `
        <div class="flex items-center">
            <i class="${icon} mr-2"></i>
            <span id="${id.replace('Message', 'Text')}"></span>
        </div>
    `;
    
    document.body.appendChild(element);
    return element;
}

// Cleanup al salir de la página
window.addEventListener('beforeunload', function() {
    stopStatusCheck();
});

// Exponer funciones globales necesarias
window.sendQuickMessage = sendQuickMessage;
window.saveAutoMessageConfig = saveAutoMessageConfig;
window.testWhatsAppConnection = testWhatsAppConnection;
window.refreshServerStatus = refreshServerStatus;
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>