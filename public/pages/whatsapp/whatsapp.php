<?php
// pages/whatsapp/whatsapp.php

$currentPage = 'whatsapp';
$pageTitle = 'ReservaBot - WhatsApp';
$pageScript = 'whatsapp';

// Obtener usuario actual
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

// Obtener tab activo desde la URL
$tabActivo = isset($_GET['tab']) ? $_GET['tab'] : 'conversaciones';
$tabsValidos = ['conversaciones', 'configuracion'];
if (!in_array($tabActivo, $tabsValidos)) {
    $tabActivo = 'conversaciones';
}

// Obtener plan del usuario
$usuarioDomain = getContainer()->getUsuarioDomain();
$usuarioEntity = $usuarioDomain->obtenerPorId($userId);
$planUsuario = $usuarioEntity ? $usuarioEntity->getPlan() : 'gratis';

// Verificar si tiene acceso a WhatsApp (plan profesional o premium)
$tieneAccesoWhatsApp = in_array($planUsuario, ['estandar', 'premium']);

// Si tiene acceso, obtener configuración WhatsApp
$whatsappConfig = null;
$connectionStatus = 'disconnected';
$phoneNumber = null;
$lastActivity = null;

if ($tieneAccesoWhatsApp) {
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
}

include 'includes/header.php';
?>

<style>
/* Tabs Styling */
.tabs {
    display: flex;
    gap: 0.25rem;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 1.5rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.tabs::-webkit-scrollbar {
    display: none;
}

.tab-button {
    padding: 0.75rem 1rem;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #6b7280;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
    text-decoration: none;
}

.tab-button:hover {
    color: #374151;
    background: #f9fafb;
}

.tab-button.active {
    color: #25d366;
    border-bottom-color: #25d366;
    background: linear-gradient(to bottom, rgba(37, 211, 102, 0.05), transparent);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Plan upgrade card */
.upgrade-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 1rem;
    padding: 2rem;
    color: white;
    text-align: center;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.upgrade-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    backdrop-filter: blur(10px);
}

.upgrade-button {
    background: white;
    color: #667eea;
    padding: 0.875rem 2rem;
    border-radius: 0.5rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
    text-decoration: none;
}

.upgrade-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

/* WhatsApp specific styles */
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

.connection-compact {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.pulse-animation {
    animation: pulse-custom 2s infinite;
}

@keyframes pulse-custom {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    body {
        overflow-x: hidden;
    }
    
    .max-w-6xl {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .tabs {
        gap: 0.25rem;
        margin-left: -0.75rem;
        margin-right: -0.75rem;
        padding: 0 0.75rem;
    }
    
    .tab-button {
        padding: 0.625rem 0.75rem;
        font-size: 0.875rem;
        gap: 0.375rem;
    }
    
    .tab-button i {
        font-size: 1rem;
    }
    
    .upgrade-card {
        padding: 1.5rem;
    }
    
    .upgrade-icon {
        width: 60px;
        height: 60px;
        margin-bottom: 1rem;
    }
    
    .upgrade-button {
        width: 100%;
        justify-content: center;
        padding: 0.75rem 1.5rem;
    }
    
    .whatsapp-card {
        border-radius: 0.75rem;
        padding: 1rem;
    }
    
    .grid.gap-6 {
        gap: 1rem;
    }
    
    h2.text-xl {
        font-size: 1.125rem;
    }
    
    .qr-container {
        min-height: 250px;
        padding: 1rem;
    }
}

@media (max-width: 640px) {
    .tab-button span.hide-mobile {
        display: none;
    }
    
    .tab-button {
        justify-content: center;
        min-width: 3rem;
    }
}
</style>

<?php if (!$tieneAccesoWhatsApp): ?>
    <!-- Mensaje de upgrade de plan -->
    <div class="max-w-4xl mx-auto px-4">
        <div class="upgrade-card">
            <div class="upgrade-icon">
                <i class="ri-whatsapp-line text-5xl"></i>
            </div>
            
            <h2 class="text-2xl md:text-3xl font-bold mb-3">
                Integra WhatsApp con ReservaBot
            </h2>
            
            <p class="text-lg mb-6 opacity-95 max-w-2xl mx-auto">
                Para automatizar la comunicación con tus clientes mediante WhatsApp, necesitas cambiar al plan Profesional o Avanzado.
            </p>
            
            <div class="bg-white bg-opacity-10 rounded-lg p-4 mb-6 max-w-xl mx-auto backdrop-filter backdrop-blur-sm">
                <h3 class="font-semibold mb-3 text-lg">Funcionalidades incluidas:</h3>
                <ul class="text-left space-y-2 text-sm">
                    <li class="flex items-center">
                        <i class="ri-check-line mr-2 text-green-300"></i>
                        Mensajes de confirmación automáticos
                    </li>
                    <li class="flex items-center">
                        <i class="ri-check-line mr-2 text-green-300"></i>
                        Recordatorios antes de cada cita
                    </li>
                    <li class="flex items-center">
                        <i class="ri-check-line mr-2 text-green-300"></i>
                        Mensajes de bienvenida personalizados
                    </li>
                    <li class="flex items-center">
                        <i class="ri-check-line mr-2 text-green-300"></i>
                        Gestión de conversaciones en tiempo real
                    </li>
                    <li class="flex items-center">
                        <i class="ri-check-line mr-2 text-green-300"></i>
                        Envío masivo de mensajes
                    </li>
                </ul>
            </div>
            
            <a href="/perfil?tab=plan" class="upgrade-button">
                <i class="ri-vip-crown-line"></i>
                <span>Cambiar de Plan</span>
            </a>
            
            <p class="mt-4 text-sm opacity-80">
                Durante la fase beta, el plan Profesional es <strong>gratis</strong> 🎉
            </p>
        </div>
        
        <div class="mt-6 text-center">
            <p class="text-gray-600">
                ¿Tienes dudas? 
                <a href="mailto:soporte@reservabot.es" class="text-blue-600 hover:text-blue-700 font-medium">
                    Contacta con soporte
                </a>
            </p>
        </div>
    </div>
    
<?php else: ?>
    <!-- Usuario con acceso - Mostrar tabs y contenido -->
    <div class="max-w-6xl mx-auto px-4">
        <!-- Tabs Navigation -->
        <div class="tabs">
            <a href="/whatsapp?tab=conversaciones" class="tab-button <?php echo $tabActivo === 'conversaciones' ? 'active' : ''; ?>">
                <i class="ri-chat-history-line"></i>
                <span>Conversaciones</span>
            </a>
            <a href="/whatsapp?tab=configuracion" class="tab-button <?php echo $tabActivo === 'configuracion' ? 'active' : ''; ?>">
                <i class="ri-settings-3-line"></i>
                <span class="hide-mobile">Configuración</span>
                <span class="hidden">Config</span>
            </a>
        </div>

        <!-- TAB 1: Conversaciones -->
        <div class="tab-content <?php echo $tabActivo === 'conversaciones' ? 'active' : ''; ?>">
            <?php
            // Incluir el contenido de la página de conversaciones
            $conversationsPageContent = true; // Flag para saber que estamos incluyendo
            include __DIR__ . '/conversaciones.php';
            ?>
        </div>

        <!-- TAB 2: Configuración -->
        <div class="tab-content <?php echo $tabActivo === 'configuracion' ? 'active' : ''; ?>">
            <div class="grid gap-6 md:grid-cols-2">
                
                <!-- Card de estado de conexión -->
                <div class="whatsapp-card rounded-xl shadow-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="ri-smartphone-line mr-2 text-green-600"></i>
                        Estado de Conexión
                    </h2>
                    
                    <?php if ($connectionStatus === 'connected' || $connectionStatus === 'ready'): ?>
                        <!-- Estado conectado -->
                        <div class="connection-compact rounded-xl p-6 text-center">
                            <div class="flex items-center justify-center space-x-3 mb-3">
                                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                                <h3 class="font-medium text-gray-900">WhatsApp Conectado</h3>
                            </div>
                            
                            <?php if ($phoneNumber): ?>
                                <div class="flex items-center justify-center space-x-2 bg-white bg-opacity-60 px-3 py-2 rounded-full mb-4 mx-auto max-w-fit">
                                    <i class="ri-phone-line text-green-600"></i>
                                    <span class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($phoneNumber); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($lastActivity): ?>
                                <p class="text-xs text-gray-600 mb-4">
                                    Última actividad: <?php echo $lastActivity->format('d/m/Y H:i'); ?>
                                </p>
                            <?php endif; ?>
                            
                            <button id="disconnectBtn" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors font-medium text-sm w-full md:w-auto">
                                <i class="ri-logout-box-line mr-2"></i>
                                Desconectar
                            </button>
                        </div>
                        
                    <?php elseif ($connectionStatus === 'connecting' || $connectionStatus === 'waiting_qr'): ?>
                        <!-- Estado conectando/QR -->
                        <div id="qrState" class="text-center">
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
                        
                    <?php else: ?>
                        <!-- Estado desconectado -->
                        <div id="disconnectedState" class="text-center py-8">
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
                    <?php endif; ?>
                </div>
                
                <!-- Card de mensajes automáticos -->
                <div class="whatsapp-card rounded-xl shadow-lg p-6 <?php echo ($connectionStatus !== 'connected' && $connectionStatus !== 'ready') ? 'opacity-50 pointer-events-none' : ''; ?>">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="ri-message-line text-green-600 mr-2"></i>
                        Mensajes Automáticos
                    </h3>
                    <p class="text-gray-600 mb-6">Configura qué mensajes se enviarán automáticamente a tus clientes.</p>
                    
                    <div class="space-y-4">
                        <label class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                            <input type="checkbox" id="autoConfirmation" <?php echo ($whatsappConfig['auto_confirmacion'] ?? false) ? 'checked' : ''; ?> class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-4 mt-1 flex-shrink-0">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900">Confirmación de reservas</h4>
                                <p class="text-sm text-gray-600 mt-1">Enviar confirmación automática cuando se cree una nueva reserva</p>
                                <p class="text-xs text-gray-500 mt-1">Ejemplo: "Tu reserva ha sido confirmada para el {fecha} a las {hora}"</p>
                            </div>
                        </label>
                        
                        <label class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                            <input type="checkbox" id="autoReminders" <?php echo ($whatsappConfig['auto_recordatorio'] ?? false) ? 'checked' : ''; ?> class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-4 mt-1 flex-shrink-0">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900">Recordatorios automáticos</h4>
                                <p class="text-sm text-gray-600 mt-1">Enviar recordatorio 24 horas antes de la cita</p>
                                <p class="text-xs text-gray-500 mt-1">Se envía automáticamente a las 10:00 AM del día anterior</p>
                            </div>
                        </label>
                        
                        <label class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                            <input type="checkbox" id="autoWelcome" <?php echo ($whatsappConfig['auto_bienvenida'] ?? false) ? 'checked' : ''; ?> class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-4 mt-1 flex-shrink-0">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900">Mensaje de bienvenida</h4>
                                <p class="text-sm text-gray-600 mt-1">Responder automáticamente cuando un cliente escriba por primera vez</p>
                                <p class="text-xs text-gray-500 mt-1">Solo se envía una vez por cliente nuevo</p>
                            </div>
                        </label>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <button onclick="saveAutoMessageConfig()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium w-full md:w-auto">
                            <i class="ri-save-line mr-2"></i>
                            Guardar Configuración
                        </button>
                        <a href="/configuracion#mensajes" class="mt-3 md:mt-0 md:ml-3 inline-block text-blue-600 hover:text-blue-700 text-sm font-medium">
                            Personalizar mensajes →
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Funcionalidades próximas -->
            <div class="mt-6">
                <div class="whatsapp-card rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Próximamente</h3>
                    
                    <div class="p-4 rounded-lg opacity-60 bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-100">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-blue-500 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="ri-robot-line text-white text-lg"></i>
                            </div>
                            <div class="flex-1 min-w-0">
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
    </div>
    
    <!-- Modal de confirmación para desconectar -->
    <div id="disconnectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">¿Desconectar WhatsApp?</h3>
            <p class="text-gray-600 mb-6">Esta acción desvinculará tu cuenta de WhatsApp de ReservaBot. Podrás volver a conectarla cuando quieras.</p>
            
            <div class="flex flex-col md:flex-row space-y-3 md:space-y-0 md:space-x-3">
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
    // Solo inicializar el manager si tiene acceso y no estamos en la tab de conversaciones
    <?php if ($tabActivo === 'configuracion'): ?>
        class WhatsAppManager {
            constructor() {
                this.currentStatus = '<?php echo $connectionStatus; ?>';
                this.statusInterval = null;
                this.elements = {};
                
                this.init();
            }

            init() {
                this.cacheElements();
                this.bindEvents();
                this.updateUI();
                
                this.checkStatus().then(() => {
                    if (this.currentStatus === 'connecting' || this.currentStatus === 'waiting_qr') {
                        this.startStatusCheck();
                    }
                });
            }

            cacheElements() {
                this.elements = {
                    connectBtn: document.getElementById('connectBtn'),
                    disconnectBtn: document.getElementById('disconnectBtn'),
                    refreshQrBtn: document.getElementById('refreshQrBtn'),
                    disconnectModal: document.getElementById('disconnectModal'),
                    confirmDisconnect: document.getElementById('confirmDisconnect'),
                    cancelDisconnect: document.getElementById('cancelDisconnect'),
                    qrContainer: document.getElementById('qrContainer')
                };
            }

            bindEvents() {
                this.elements.connectBtn?.addEventListener('click', () => this.connect());
                this.elements.disconnectBtn?.addEventListener('click', () => this.showDisconnectModal());
                this.elements.refreshQrBtn?.addEventListener('click', () => this.refreshQR());
                this.elements.confirmDisconnect?.addEventListener('click', () => this.disconnect());
                this.elements.cancelDisconnect?.addEventListener('click', () => this.hideDisconnectModal());
                
                this.elements.disconnectModal?.addEventListener('click', (e) => {
                    if (e.target === this.elements.disconnectModal) {
                        this.hideDisconnectModal();
                    }
                });
            }

            updateUI() {
                // La UI ya está renderizada desde PHP según el estado
            }

            startStatusCheck() {
                if (this.statusInterval) return;
                console.log('Iniciando verificación de estado...');
                this.statusInterval = setInterval(() => this.checkStatus(), 3000);
            }

            stopStatusCheck() {
                if (this.statusInterval) {
                    clearInterval(this.statusInterval);
                    this.statusInterval = null;
                }
            }

            async checkStatus() {
                try {
                    const response = await fetch('/api/whatsapp-status');
                    const data = await response.json();
                    
                    if (data.success && data.status !== this.currentStatus) {
                        // Estado cambió, recargar página para actualizar UI
                        if (data.status === 'connected' || data.status === 'ready') {
                            this.showNotification('¡WhatsApp conectado correctamente!', 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else if (data.qr && this.elements.qrContainer) {
                            this.updateQR(data.qr);
                        }
                    }
                } catch (error) {
                    console.error('Error verificando estado:', error);
                }
            }

            async connect() {
                try {
                    this.setButtonLoading(this.elements.connectBtn, true, 'Conectando...');
                    
                    const response = await fetch('/api/whatsapp-connect', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showNotification('Iniciando conexión de WhatsApp...', 'info');
                        setTimeout(() => window.location.reload(), 1000);
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
                        this.hideDisconnectModal();
                        this.showNotification('WhatsApp desconectado correctamente', 'success');
                        setTimeout(() => window.location.reload(), 1000);
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
                if (this.elements.qrContainer) {
                    this.elements.qrContainer.innerHTML = `
                        <div class="text-center">
                            <div class="pulse-animation mb-4">
                                <i class="ri-qr-code-line text-gray-400 text-6xl"></i>
                            </div>
                            <p class="text-gray-500">Generando código QR...</p>
                        </div>
                    `;
                }
                this.connect();
            }

            updateQR(qrDataUrl) {
                if (this.elements.qrContainer) {
                    this.elements.qrContainer.innerHTML = `
                        <div class="bg-white p-4 rounded-lg shadow-sm inline-block">
                            <img src="${qrDataUrl}" alt="Código QR WhatsApp" class="w-full max-w-xs mx-auto rounded-lg">
                        </div>
                    `;
                }
            }

            showDisconnectModal() {
                this.elements.disconnectModal?.classList.remove('hidden');
                this.elements.disconnectModal?.classList.add('flex');
            }

            hideDisconnectModal() {
                this.elements.disconnectModal?.classList.add('hidden');
                this.elements.disconnectModal?.classList.remove('flex');
            }

            setButtonLoading(button, loading, text) {
                if (!button) return;
                
                button.disabled = loading;
                
                if (loading) {
                    button.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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

            destroy() {
                this.stopStatusCheck();
            }
        }

        // Funciones globales
        async function saveAutoMessageConfig() {
            const config = {
                confirmacion: document.getElementById('autoConfirmation')?.checked || false,
                recordatorio: document.getElementById('autoReminders')?.checked || false,
                bienvenida: document.getElementById('autoWelcome')?.checked || false
            };
            
            try {
                const response = await fetch('/api/whatsapp-save-auto-message-config', {
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

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            window.whatsappManager = new WhatsAppManager();
            
            window.addEventListener('beforeunload', () => {
                window.whatsappManager?.destroy();
            });
        });

        window.saveAutoMessageConfig = saveAutoMessageConfig;
    <?php endif; ?>
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>