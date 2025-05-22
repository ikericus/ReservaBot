document.addEventListener('DOMContentLoaded', function() {
    // Elementos UI
    const qrContainer = document.getElementById('qrContainer');
    const qrCode = document.getElementById('qrCode');
    const statusContainer = document.getElementById('statusContainer');
    const statusBadge = document.getElementById('statusBadge');
    const statusText = document.getElementById('statusText');
    const connectBtn = document.getElementById('connectBtn');
    const disconnectBtn = document.getElementById('disconnectBtn');
    const refreshBtn = document.getElementById('refreshBtn');
    const lastActivity = document.getElementById('lastActivity');
    const notificationSettings = document.querySelectorAll('.notification-setting');
    
    // Inicialización
    checkStatus();
    
    // Event listeners
    if (connectBtn) {
        connectBtn.addEventListener('click', initiateConnection);
    }
    
    if (disconnectBtn) {
        disconnectBtn.addEventListener('click', disconnect);
    }
    
    if (refreshBtn) {
        refreshBtn.addEventListener('click', checkStatus);
    }
    
    if (notificationSettings) {
        notificationSettings.forEach(setting => {
            const toggle = setting.querySelector('.toggle-input');
            const id = toggle.id;
            
            toggle.addEventListener('change', function() {
                const isEnabled = this.checked;
                updateNotificationSetting(id, isEnabled);
            });
        });
    }
    
    // Funciones
    function checkStatus() {
        fetch('api/whatsapp-status.php')
            .then(response => response.json())
            .then(data => {
                updateStatusUI(data);
            })
            .catch(error => {
                console.error('Error al verificar el estado:', error);
                showErrorMessage('Error al verificar el estado de la conexión');
            });
    }
    
    function initiateConnection() {
        fetch('api/whatsapp-connect.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.qrCode) {
                        showQRCode(data.qrCode);
                    }
                    
                    // Iniciar polling para verificar el estado
                    startStatusPolling();
                } else {
                    showErrorMessage(data.message || 'Error al iniciar la conexión');
                }
            })
            .catch(error => {
                console.error('Error al iniciar la conexión:', error);
                showErrorMessage('Error al iniciar la conexión de WhatsApp');
            });
    }
    
    function disconnect() {
        if (!confirm('¿Está seguro de que desea desconectar WhatsApp?')) {
            return;
        }
        
        fetch('api/whatsapp-disconnect.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(data.message || 'WhatsApp desconectado correctamente');
                    updateStatusUI({ status: 'disconnected' });
                } else {
                    showErrorMessage(data.message || 'Error al desconectar WhatsApp');
                }
            })
            .catch(error => {
                console.error('Error al desconectar:', error);
                showErrorMessage('Error al desconectar WhatsApp');
            });
    }
    
    function updateNotificationSetting(settingId, isEnabled) {
        const formData = new FormData();
        formData.append('setting', settingId);
        formData.append('enabled', isEnabled ? 1 : 0);
        
        fetch('api/whatsapp-settings.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage('Configuración actualizada correctamente');
                } else {
                    showErrorMessage(data.message || 'Error al actualizar la configuración');
                }
            })
            .catch(error => {
                console.error('Error al actualizar configuración:', error);
                showErrorMessage('Error al actualizar la configuración');
            });
    }
    
    function showQRCode(qrCodeData) {
        if (qrContainer && qrCode) {
            qrCode.src = qrCodeData;
            qrContainer.classList.remove('hidden');
        }
    }
    
    function hideQRCode() {
        if (qrContainer) {
            qrContainer.classList.add('hidden');
        }
    }
    
    function updateStatusUI(data) {
        if (!statusBadge || !statusText) return;
        
        // Actualizar texto y color del estado
        let badgeClass = '';
        let statusMessage = '';
        
        switch (data.status) {
            case 'connected':
                badgeClass = 'bg-green-100 text-green-800';
                statusMessage = 'Conectado';
                hideQRCode();
                if (connectBtn) connectBtn.classList.add('hidden');
                if (disconnectBtn) disconnectBtn.classList.remove('hidden');
                break;
                
            case 'connecting':
                badgeClass = 'bg-yellow-100 text-yellow-800';
                statusMessage = 'Conectando...';
                if (connectBtn) connectBtn.classList.add('hidden');
                if (disconnectBtn) disconnectBtn.classList.remove('hidden');
                break;
                
            case 'disconnected':
                badgeClass = 'bg-gray-100 text-gray-800';
                statusMessage = 'Desconectado';
                hideQRCode();
                if (connectBtn) connectBtn.classList.remove('hidden');
                if (disconnectBtn) disconnectBtn.classList.add('hidden');
                break;
                
            case 'qr_ready':
                badgeClass = 'bg-blue-100 text-blue-800';
                statusMessage = 'Esperando escaneo QR';
                if (data.qrCode) {
                    showQRCode(data.qrCode);
                }
                if (connectBtn) connectBtn.classList.add('hidden');
                if (disconnectBtn) disconnectBtn.classList.remove('hidden');
                break;
                
            case 'error':
                badgeClass = 'bg-red-100 text-red-800';
                statusMessage = 'Error: ' + (data.message || 'Desconocido');
                if (connectBtn) connectBtn.classList.remove('hidden');
                if (disconnectBtn) disconnectBtn.classList.add('hidden');
                break;
                
            default:
                badgeClass = 'bg-gray-100 text-gray-800';
                statusMessage = 'Estado desconocido';
                if (connectBtn) connectBtn.classList.remove('hidden');
                if (disconnectBtn) disconnectBtn.classList.add('hidden');
        }
        
        // Remover clases anteriores
        statusBadge.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full';
        statusBadge.classList.add(...badgeClass.split(' '));
        statusText.textContent = statusMessage;
        
        // Actualizar última actividad si está disponible
        if (lastActivity && data.lastActivity) {
            lastActivity.textContent = data.lastActivity;
            lastActivity.parentElement.classList.remove('hidden');
        } else if (lastActivity) {
            lastActivity.parentElement.classList.add('hidden');
        }
        
        // Actualizar configuración de notificaciones
        if (data.settings && notificationSettings) {
            notificationSettings.forEach(setting => {
                const toggle = setting.querySelector('.toggle-input');
                const id = toggle.id;
                
                if (data.settings[id] !== undefined) {
                    toggle.checked = data.settings[id] === '1';
                }
            });
        }
    }
    
    function startStatusPolling() {
        const pollInterval = setInterval(() => {
            fetch('api/whatsapp-status.php')
                .then(response => response.json())
                .then(data => {
                    updateStatusUI(data);
                    
                    // Si el estado es 'connected' o 'error', detener el polling
                    if (data.status === 'connected' || data.status === 'error') {
                        clearInterval(pollInterval);
                    }
                })
                .catch(error => {
                    console.error('Error en polling de estado:', error);
                    clearInterval(pollInterval);
                });
        }, 3000); // Verificar cada 3 segundos
        
        // Detener el polling después de 2 minutos si no hay cambios
        setTimeout(() => {
            clearInterval(pollInterval);
        }, 2 * 60 * 1000);
    }
    
    function showSuccessMessage(message) {
        const successMessage = document.getElementById('successMessage');
        const successText = document.getElementById('successText');
        
        if (successMessage && successText) {
            successText.textContent = message;
            successMessage.classList.remove('hidden');
            
            setTimeout(() => {
                successMessage.classList.add('hidden');
            }, 3000);
        }
    }
    
    function showErrorMessage(message) {
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        
        if (errorMessage && errorText) {
            errorText.textContent = message;
            errorMessage.classList.remove('hidden');
            
            setTimeout(() => {
                errorMessage.classList.add('hidden');
            }, 3000);
        }
    }
});