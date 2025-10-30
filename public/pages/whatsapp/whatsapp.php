<?php
// pages/whatsapp.php

$currentPage = 'whatsapp';
$pageTitle = 'ReservaBot - WhatsApp';
$pageScript = 'whatsapp';

// Obtener usuario actual
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

// Obtener configuración WhatsApp
$whatsappConfig = null;
$connectionStatus = 'disconnected';
$phoneNumber = null;
$lastActivity = null;

try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    $config = $whatsappDomain->obtenerConfiguracion($userId);
    
    $whatsappConfig = $config->toArray();
    $connectionStatus = $config->getStatus();
    $phoneNumber = $config->getPhoneNumber();
    $lastActivity = $config->getLastActivity();
} catch (Exception $e) {
    setFlashError('Error al cargar configuración de WhatsApp: ' . $e->getMessage());
}

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

/* Configuración compacta para estado conectado */
.connection-compact {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border: 1px solid rgba(16, 185, 129, 0.2);
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

<!-- Layout dinámico basado en estado de conexión -->
<div id="mainLayout" class="grid gap-6 items-start <?php echo ($connectionStatus === 'connected' || $connectionStatus === 'ready') ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1 lg:grid-cols-3'; ?>">
    
    <!-- Primera columna -->
    <div id="firstColumn" class="<?php echo ($connectionStatus === 'connected' || $connectionStatus === 'ready') ? '' : 'lg:col-span-2'; ?> space-y-6">
        
        <!-- Configuración principal de conexión (solo cuando NO está conectado) -->
        <div id="mainConnectionCard" class="whatsapp-card rounded-xl shadow-lg p-6 <?php echo ($connectionStatus === 'connected' || $connectionStatus === 'ready') ? 'hidden' : ''; ?>">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Configuración de Conexión</h2>
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
        </div>
        
        <!-- Conversaciones recientes -->
        <div id="conversationsCard" class="whatsapp-card rounded-xl shadow-lg p-6 <?php echo ($connectionStatus !== 'connected' && $connectionStatus !== 'ready') ? 'opacity-50' : ''; ?>">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="ri-chat-history-line mr-2 text-green-600"></i>
                    Conversaciones Recientes
                </h3>
                <a href="/conversaciones" class="text-green-600 hover:text-green-700 text-sm font-medium">
                    Ver todas →
                </a>
            </div>
            
            <div id="conversationsPreview" class="space-y-2">
                <div class="text-center text-gray-500 py-8">
                    <i class="ri-chat-3-line text-4xl mb-3"></i>
                    <p class="text-sm">Conecta WhatsApp para ver conversaciones</p>
                </div>
            </div>
        </div>
        
        <!-- Envío rápido de mensaje -->
        <div id="quickMessageSection" class="whatsapp-card rounded-xl shadow-lg p-6 <?php echo ($connectionStatus !== 'connected' && $connectionStatus !== 'ready') ? 'opacity-50 pointer-events-none' : ''; ?>">
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
    
    <!-- Segunda columna -->
    <div id="secondColumn" class="space-y-6">
        
        <!-- Configuración compacta de conexión (solo cuando SÍ está conectado) -->
        <div id="compactConnectionCard" class="connection-compact whatsapp-card rounded-xl shadow-lg p-6 <?php echo ($connectionStatus !== 'connected' && $connectionStatus !== 'ready') ? 'hidden' : ''; ?>">
            <div class="text-center">
                <div class="flex items-center justify-center space-x-3 mb-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <h3 class="font-medium text-gray-900">WhatsApp Conectado</h3>
                </div>
                
                <?php if ($phoneNumber): ?>
                    <div class="flex items-center justify-center space-x-2 bg-white bg-opacity-60 px-3 py-1 rounded-full mb-4">
                        <i class="ri-phone-line text-green-600"></i>
                        <span class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($phoneNumber); ?></span>
                    </div>
                <?php endif; ?>
                
                <button id="disconnectBtn" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors font-medium text-sm">
                    <i class="ri-logout-box-line mr-2"></i>
                    Desconectar
                </button>
            </div>
        </div>
        
        <!-- Configuración de mensajes automáticos -->
        <div id="autoMessagesSection" class="whatsapp-card rounded-xl shadow-lg p-6 <?php echo ($connectionStatus !== 'connected' && $connectionStatus !== 'ready') ? 'opacity-50 pointer-events-none' : ''; ?>">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="ri-message-line text-green-600 mr-2"></i>
                Mensajes Automáticos
            </h3>
            <p class="text-gray-600 mb-6">Configura qué mensajes se enviarán automáticamente a tus clientes.</p>
            
            <div class="space-y-4">
                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                    <input type="checkbox" id="autoConfirmation" <?php echo ($whatsappConfig['auto_confirmacion'] ?? false) ? 'checked' : ''; ?> class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-4">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">Confirmación de reservas</h4>
                        <p class="text-sm text-gray-600">Enviar confirmación automática cuando se cree una nueva reserva</p>
                        <p class="text-xs text-gray-500 mt-1">Ejemplo: "Tu reserva ha sido confirmada para el {fecha} a las {hora}"</p>
                    </div>
                </label>
                
                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                    <input type="checkbox" id="autoReminders" <?php echo ($whatsappConfig['auto_recordatorio'] ?? false) ? 'checked' : ''; ?> class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-4">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">Recordatorios automáticos</h4>
                        <p class="text-sm text-gray-600">Enviar recordatorio 24 horas antes de la cita</p>
                        <p class="text-xs text-gray-500 mt-1">Se envía automáticamente a las 10:00 AM del día anterior</p>
                    </div>
                </label>
                
                <label class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                    <input type="checkbox" id="autoWelcome" <?php echo ($whatsappConfig['auto_bienvenida'] ?? false) ? 'checked' : ''; ?> class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-4">
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
        
        <!-- Funcionalidades próximas (solo IA) -->
        <div class="whatsapp-card rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Próximamente</h3>
            
            <div class="feature-item p-4 rounded-lg opacity-60 bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-100">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-blue-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="ri-robot-line text-white text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-900 mb-1">IA para Reservas Automáticas</h4>
                        <p class="text-xs text-gray-600 mb-2">Inteligencia artificial que gestiona reservas automáticamente via WhatsApp</p>
                        <div class="flex items-center text-xs text-purple-600">
                            <i class="ri-time-line mr-1"></i>
                            <span>Próximo trimestre</span>
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
    class WhatsAppManager {
        constructor() {
            this.currentStatus = document.querySelector('[data-status]')?.dataset.status || 'disconnected';
            this.statusInterval = null;
            this.elements = {};
            
            this.init();
        }

        init() {
            this.cacheElements();
            this.bindEvents();
            this.updateUI();
            
            this.checkStatus().then(() => {
                // Ahora sí inicializar con el estado correcto
                if (this.currentStatus === 'connecting' || this.currentStatus === 'waiting_qr') {
                    this.startStatusCheck();
                } else if (this.currentStatus === 'connected' || this.currentStatus === 'ready') {
                    this.loadConnectedData();
                }
            });
            
            // Asegurar que la UI esté correcta desde el inicio
            this.updateLayout();
            this.updateConnectionCards();
        }

        cacheElements() {
            this.elements = {
                // Layout
                mainLayout: document.getElementById('mainLayout'),
                firstColumn: document.getElementById('firstColumn'),
                secondColumn: document.getElementById('secondColumn'),
                
                // Cards de conexión
                mainConnectionCard: document.getElementById('mainConnectionCard'),
                compactConnectionCard: document.getElementById('compactConnectionCard'),
                
                // Estados
                disconnectedState: document.getElementById('disconnectedState'),
                qrState: document.getElementById('qrState'),
                
                // Botones principales
                connectBtn: document.getElementById('connectBtn'),
                disconnectBtn: document.getElementById('disconnectBtn'),
                refreshQrBtn: document.getElementById('refreshQrBtn'),
                                
                // Modal
                disconnectModal: document.getElementById('disconnectModal'),
                confirmDisconnect: document.getElementById('confirmDisconnect'),
                cancelDisconnect: document.getElementById('cancelDisconnect'),
                
                // Mensaje rápido
                quickMessagePhone: document.getElementById('quickMessagePhone'),
                quickMessageText: document.getElementById('quickMessageText'),
                sendQuickBtn: document.getElementById('sendQuickBtn'),
                charCount: document.getElementById('charCount'),
                
                // Estado y estadísticas
                statusIndicator: document.querySelector('.status-indicator'),
                statusText: document.querySelector('.status-indicator + span'),
                qrContainer: document.getElementById('qrContainer'),
                
                // Conversaciones
                conversationsPreview: document.getElementById('conversationsPreview')
            };
        }

        bindEvents() {
            // Botones principales
            this.elements.connectBtn?.addEventListener('click', () => this.connect());
            this.elements.disconnectBtn?.addEventListener('click', () => this.showDisconnectModal());
            this.elements.refreshQrBtn?.addEventListener('click', () => this.refreshQR());
            
            // Modal
            this.elements.confirmDisconnect?.addEventListener('click', () => this.disconnect());
            this.elements.cancelDisconnect?.addEventListener('click', () => this.hideDisconnectModal());
            
            // Mensaje rápido
            this.elements.sendQuickBtn?.addEventListener('click', () => this.sendQuickMessage());
            this.elements.quickMessageText?.addEventListener('input', (e) => this.updateCharCount(e));
            
            // Cerrar modal al hacer click fuera
            this.elements.disconnectModal?.addEventListener('click', (e) => {
                if (e.target === this.elements.disconnectModal) {
                    this.hideDisconnectModal();
                }
            });
        }

        // =============== GESTIÓN DE ESTADO ===============

        updateStatus(newStatus, phoneNumber = null) {
            console.log('Actualizando estado:', this.currentStatus, '->', newStatus);
            this.currentStatus = newStatus;
            this.updateUI(phoneNumber);
            
            // Gestionar verificación automática
            if (newStatus === 'connecting') {
                this.startStatusCheck();
            } else if (newStatus === 'connected' || newStatus === 'ready') {
                this.stopStatusCheck();
                this.loadConnectedData();
                this.showNotification('¡WhatsApp conectado correctamente!', 'success');
            } else if (newStatus === 'disconnected') {
                this.stopStatusCheck();
            }
        } 

        updateUI(phoneNumber = null) {
            // Actualizar indicador visual
            this.updateStatusIndicator();
            
            // Reorganizar layout según estado
            this.updateLayout();
            
            // Mostrar/ocultar estados de conexión
            this.showCorrectState();
            
            // Mostrar/ocultar tarjetas de conexión
            this.updateConnectionCards();
            
            // Actualizar número de teléfono si se proporciona
            if (phoneNumber && (this.currentStatus === 'connected' || this.currentStatus === 'ready')) {
                this.updatePhoneNumber(phoneNumber);
            }
            
            // Habilitar/deshabilitar secciones
            this.toggleSections();
        }

        updateConnectionCards() {
            const isConnected = this.currentStatus === 'connected' || this.currentStatus === 'ready';
            
            if (isConnected) {
                // Ocultar configuración principal y mostrar compacta
                this.elements.mainConnectionCard?.classList.add('hidden');
                this.elements.compactConnectionCard?.classList.remove('hidden');
            } else {
                // Mostrar configuración principal y ocultar compacta
                this.elements.mainConnectionCard?.classList.remove('hidden');
                this.elements.compactConnectionCard?.classList.add('hidden');
            }
        }

        updateLayout() {
            const isConnected = this.currentStatus === 'connected' || this.currentStatus === 'ready';
            
            if (isConnected) {
                // Cambiar a layout de 2 columnas
                this.elements.mainLayout.className = 'grid gap-6 items-start grid-cols-1 lg:grid-cols-2';
                this.elements.firstColumn.className = 'space-y-6';
            } else {
                // Cambiar a layout de 3 columnas (2+1)
                this.elements.mainLayout.className = 'grid gap-6 items-start grid-cols-1 lg:grid-cols-3';
                this.elements.firstColumn.className = 'lg:col-span-2 space-y-6';
            }
        }

        updateStatusIndicator() {
            if (!this.elements.statusIndicator || !this.elements.statusText) return;
            
            const statusConfig = {
                ready: { class: 'connected', text: 'Conectado' },
                connected: { class: 'connected', text: 'Conectado' },
                connecting: { class: 'connecting', text: 'Conectando...' },
                waiting_qr: { class: 'connecting', text: 'Esperando QR...' },
                disconnected: { class: 'disconnected', text: 'Desconectado' }
            };
            
            const config = statusConfig[this.currentStatus] || statusConfig.disconnected;
            
            this.elements.statusIndicator.className = `status-indicator w-3 h-3 rounded-full ${config.class}`;
            this.elements.statusText.textContent = config.text;
        }

        showCorrectState() {
            // Ocultar todos los estados de conexión principal
            [this.elements.disconnectedState, this.elements.qrState]
                .forEach(el => el?.classList.add('hidden'));
            
            // Mostrar el estado correcto
            switch (this.currentStatus) {
                case 'disconnected':
                    this.elements.disconnectedState?.classList.remove('hidden');
                    break;
                case 'connecting':
                case 'waiting_qr':
                    this.elements.qrState?.classList.remove('hidden');
                    break;
            }
        }

        updatePhoneNumber(phoneNumber) {
            const phoneElements = document.querySelectorAll('#compactConnectionCard .text-green-800');
            phoneElements.forEach(el => {
                if (el.textContent.includes('34') || el.textContent === '') {
                    el.textContent = phoneNumber;
                }
            });
        }

        toggleSections() {
            const dependentSections = [
                'autoMessagesSection', 'conversationsCard', 'quickMessageSection'
            ];
            
            const isActive = this.currentStatus === 'connected' || this.currentStatus === 'ready';
            
            dependentSections.forEach(sectionId => {
                const section = document.getElementById(sectionId);
                if (section) {
                    section.classList.toggle('opacity-50', !isActive);
                    section.classList.toggle('pointer-events-none', !isActive);
                }
            });
        }

        // =============== VERIFICACIÓN DE ESTADO ===============

        startStatusCheck() {
            if (this.statusInterval) return;
            
            console.log('Iniciando verificación de estado...');
            this.statusInterval = setInterval(() => this.checkStatus(), 3000);
        }

        stopStatusCheck() {
            if (this.statusInterval) {
                clearInterval(this.statusInterval);
                this.statusInterval = null;
                console.log('Deteniendo verificación de estado...');
            }
        }

        async checkStatus() {
            try {
                const response = await fetch('/api/whatsapp-status');
                const data = await response.json();
                
                if (data.success) {
                    // Solo actualizar si hay cambio de estado
                    if (data.status !== this.currentStatus) {
                        this.updateStatus(data.status, data.phoneNumber);
                    }
                    
                    // Actualizar QR si está disponible
                    if (data.qr && (this.currentStatus === 'connecting' || this.currentStatus === 'waiting_qr')) {
                        this.updateQR(data.qr);
                    }
                }
            } catch (error) {
                console.error('Error verificando estado:', error);
            }
        }

        // =============== CONEXIÓN/DESCONEXIÓN ===============

        async connect() {
            try {
                this.setButtonLoading(this.elements.connectBtn, true, 'Conectando...');
                
                const response = await fetch('/api/whatsapp-connect', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.updateStatus('connecting');
                    this.showNotification('Iniciando conexión de WhatsApp...', 'info');
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            } catch (error) {
                console.error('Error conectando:', error);
                this.showNotification('Error al conectar: ' + error.message, 'error');
                this.setButtonLoading(this.elements.connectBtn, false, '<i class="ri-qr-code-line mr-2"></i>Conectar WhatsApp');
            }
        }

        async disconnect() {
            try {
                this.setButtonLoading(this.elements.confirmDisconnect, true, 'Desconectando...');
                
                const response = await fetch('/api/whatsapp-disconnect', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.updateStatus('disconnected');
                    this.hideDisconnectModal();
                    this.showNotification('WhatsApp desconectado correctamente', 'success');
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            } catch (error) {
                console.error('Error desconectando:', error);
                this.showNotification('Error al desconectar: ' + error.message, 'error');
            } finally {
                this.setButtonLoading(this.elements.confirmDisconnect, false, 'Desconectar');
            }
        }

        refreshQR() {
            this.showQRLoading();
            this.connect(); // Reiniciar conexión para nuevo QR
        }

        updateQR(qrDataUrl) {
            if (this.elements.qrContainer) {
                this.elements.qrContainer.innerHTML = `
                    <div class="bg-white p-4 rounded-lg shadow-sm inline-block">
                        <img src="${qrDataUrl}" alt="Código QR WhatsApp" class="w-full max-w-xs mx-auto rounded-lg">
                    </div>
                `;
                this.elements.qrContainer.classList.add('active');
            }
        }

        showQRLoading() {
            if (this.elements.qrContainer) {
                this.elements.qrContainer.innerHTML = `
                    <div class="text-center">
                        <div class="pulse-animation mb-4">
                            <i class="ri-qr-code-line text-gray-400 text-6xl"></i>
                        </div>
                        <p class="text-gray-500">Generando código QR...</p>
                    </div>
                `;
                this.elements.qrContainer.classList.remove('active');
            }
        }

        // =============== MENSAJES ===============

        async sendQuickMessage() {
            const phone = this.elements.quickMessagePhone?.value.trim();
            const message = this.elements.quickMessageText?.value.trim();
            
            if (!this.validateQuickMessage(phone, message)) return;
            
            try {
                this.setButtonLoading(this.elements.sendQuickBtn, true, 'Enviando...');
                
                const response = await fetch('/api/whatsapp-send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ to: phone, message: message })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification('Mensaje enviado correctamente', 'success');
                    this.clearQuickMessageForm();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('Error enviando mensaje:', error);
                this.showNotification('Error enviando mensaje: ' + error.message, 'error');
            } finally {
                this.setButtonLoading(this.elements.sendQuickBtn, false, '<i class="ri-send-plane-fill mr-2"></i>Enviar Mensaje');
            }
        }

        validateQuickMessage(phone, message) {
            if (!phone || !message) {
                this.showNotification('Por favor, completa todos los campos', 'warning');
                return false;
            }
            
            if (!/^\d{8,15}$/.test(phone.replace(/[^\d]/g, ''))) {
                this.showNotification('Formato de teléfono inválido. Usa solo números (ej: 34612345678)', 'error');
                return false;
            }
            
            if (message.length > 1000) {
                this.showNotification('El mensaje no puede tener más de 1000 caracteres', 'error');
                return false;
            }
            
            return true;
        }

        clearQuickMessageForm() {
            if (this.elements.quickMessagePhone) this.elements.quickMessagePhone.value = '';
            if (this.elements.quickMessageText) this.elements.quickMessageText.value = '';
            if (this.elements.charCount) this.elements.charCount.textContent = '0/1000';
        }

        updateCharCount(e) {
            const count = e.target.value.length;
            if (this.elements.charCount) {
                this.elements.charCount.textContent = `${count}/1000`;
                this.elements.charCount.classList.toggle('text-red-500', count > 1000);
            }
            
            if (count > 1000) {
                e.target.value = e.target.value.substring(0, 1000);
                if (this.elements.charCount) this.elements.charCount.textContent = '1000/1000';
            }
        }

        // =============== DATOS Y ESTADÍSTICAS ===============

        async loadConnectedData() {
            try {
                await Promise.all([
                    this.loadConversations(),
                    this.loadAutoMessageConfig()
                ]);
            } catch (error) {
                console.error('Error cargando datos:', error);
            }
        }

        async loadConversations() {
            try {
                const response = await fetch('/api/whatsapp-conversations?limit=4');
                const data = await response.json();
                
                if (!this.elements.conversationsPreview) return;
                
                if (data.success && data.conversations.length > 0) {
                    this.elements.conversationsPreview.innerHTML = data.conversations.map(conv => `
                        <div class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-medium text-sm text-gray-900">${conv.name || conv.phone}</span>
                                <span class="text-xs text-gray-500">${conv.lastMessageTime}</span>
                            </div>
                            <p class="text-sm text-gray-600 truncate">${conv.lastMessage}</p>
                        </div>
                    `).join('');
                } else {
                    this.elements.conversationsPreview.innerHTML = `
                        <div class="text-center text-gray-500 py-8">
                            <i class="ri-chat-3-line text-4xl mb-3"></i>
                            <p class="text-sm">No hay conversaciones recientes</p>
                            <p class="text-xs text-gray-400 mt-1">Las conversaciones aparecerán aquí cuando recibas mensajes</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error cargando conversaciones:', error);
            }
        }

        async loadAutoMessageConfig() {
            // La configuración ya viene desde PHP, no necesitamos cargarla desde API
            // Los checkboxes ya están pre-marcados según el estado de la BD
        }

        // =============== MODAL ===============

        showDisconnectModal() {
            this.elements.disconnectModal?.classList.remove('hidden');
            this.elements.disconnectModal?.classList.add('flex');
        }

        hideDisconnectModal() {
            this.elements.disconnectModal?.classList.add('hidden');
            this.elements.disconnectModal?.classList.remove('flex');
        }

        // =============== UTILIDADES ===============

        setButtonLoading(button, loading, text) {
            if (!button) return;
            
            button.disabled = loading;
            
            if (loading) {
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
        }

        showNotification(message, type = 'info') {
            // Crear o obtener elemento de notificación
            let notification = document.getElementById('notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'notification';
                notification.className = 'fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 hidden';
                document.body.appendChild(notification);
            }
            
            const colors = {
                success: 'bg-green-100 text-green-800',
                error: 'bg-red-100 text-red-800',
                warning: 'bg-yellow-100 text-yellow-800',
                info: 'bg-blue-100 text-blue-800'
            };
            
            const icons = {
                success: 'ri-check-line',
                error: 'ri-error-warning-line',
                warning: 'ri-alert-line',
                info: 'ri-information-line'
            };
            
            notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${colors[type] || colors.info}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="${icons[type] || icons.info} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            notification.classList.remove('hidden');
            
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 5000);
        }

        // =============== CLEANUP ===============

        destroy() {
            this.stopStatusCheck();
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
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(config)
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.whatsappManager?.showNotification('Configuración guardada correctamente', 'success');
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Error:', error);
            window.whatsappManager?.showNotification('Error guardando configuración: ' + error.message, 'error');
        }
    }

    // =============== INICIALIZACIÓN ===============

    document.addEventListener('DOMContentLoaded', function() {
        // Obtener estado inicial del PHP
        const statusFromPHP = '<?php echo $connectionStatus; ?>';
        
        // Agregar atributo data para que el manager lo pueda leer
        const statusContainer = document.querySelector('.status-indicator')?.parentElement;
        if (statusContainer) {
            statusContainer.setAttribute('data-status', statusFromPHP);
        }
        
        // Inicializar manager
        window.whatsappManager = new WhatsAppManager();
        
        // Cleanup al salir
        window.addEventListener('beforeunload', () => {
            window.whatsappManager?.destroy();
        });
    });

    // Exponer funciones globales para usar desde HTML
    window.saveAutoMessageConfig = saveAutoMessageConfig;
</script>

<?php include 'includes/footer.php'; ?>