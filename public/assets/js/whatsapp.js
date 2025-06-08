// JavaScript para whatsapp.php - Versión limpia y funcional
document.addEventListener('DOMContentLoaded', function() {
    let currentStatus = '<?php echo $connectionStatus; ?>';
    let checkStatusInterval = null;
    
    // Elementos del DOM
    const connectBtn = document.getElementById('connectBtn');
    const disconnectBtn = document.getElementById('disconnectBtn');
    const refreshQrBtn = document.getElementById('refreshQrBtn');
    const disconnectModal = document.getElementById('disconnectModal');
    const confirmDisconnect = document.getElementById('confirmDisconnect');
    const cancelDisconnect = document.getElementById('cancelDisconnect');
    const quickMessageText = document.getElementById('quickMessageText');
    const charCount = document.getElementById('charCount');
    
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
    if (currentStatus === 'connected') {
        setInterval(refreshServerStatus, 120000);
    }
});

// Inicializar página según estado
function initializePage() {
    if (currentStatus === 'connecting') {
        startStatusCheck();
    } else if (currentStatus === 'connected') {
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
            
            if (data.status === 'ready') {
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
    
    // Ocultar todos los estados
    document.getElementById('disconnectedState').classList.add('hidden');
    document.getElementById('qrState').classList.add('hidden');
    document.getElementById('connectedState').classList.add('hidden');
    
    // Actualizar indicador de estado
    const statusIndicator = document.querySelector('.status-indicator');
    statusIndicator.className = `status-indicator w-3 h-3 rounded-full ${status === 'ready' ? 'connected' : status}`;
    
    const statusLabels = {
        'ready': 'Conectado',
        'connected': 'Conectado',
        'connecting': 'Conectando...',
        'waiting_qr': 'Esperando QR...',
        'disconnected': 'Desconectado'
    };
    
    const statusText = statusIndicator.nextElementSibling;
    statusText.textContent = statusLabels[status] || 'Desconectado';
    
    // Mostrar estado correspondiente
    switch (status) {
        case 'disconnected':
            document.getElementById('disconnectedState').classList.remove('hidden');
            if (connectBtn) {
                updateButtonState(connectBtn, false, '<i class="ri-qr-code-line mr-2"></i>Conectar WhatsApp');
            }
            break;
            
        case 'connecting':
        case 'waiting_qr':
            document.getElementById('qrState').classList.remove('hidden');
            break;
            
        case 'ready':
        case 'connected':
            document.getElementById('connectedState').classList.remove('hidden');
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