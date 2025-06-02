document.addEventListener('DOMContentLoaded', function() {
    // Elementos UI
    const connectBtn = document.getElementById('connectBtn');
    const disconnectBtn = document.getElementById('disconnectBtn');
    const refreshBtn = document.getElementById('refreshBtn');
    const whatsappMessagesForm = document.getElementById('whatsappMessagesForm');
    
    // Event listeners
    if (connectBtn) {
        connectBtn.addEventListener('click', initiateConnection);
    }
    
    if (disconnectBtn) {
        disconnectBtn.addEventListener('click', disconnect);
    }
    
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshStatus);
    }
    
    if (whatsappMessagesForm) {
        whatsappMessagesForm.addEventListener('submit', saveMessagesConfig);
    }
    
    // Funciones
    function refreshStatus() {
        window.location.reload();
    }
    
    function initiateConnection() {
        setLoadingState(connectBtn, true, 'Conectando...');
        
        fetch('api/whatsapp-connect')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage('Iniciando conexión de WhatsApp...');
                    
                    // Recargar la página después de un breve delay para mostrar el QR
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showErrorMessage(data.message || 'Error al iniciar la conexión');
                    setLoadingState(connectBtn, false, 'Conectar WhatsApp');
                }
            })
            .catch(error => {
                console.error('Error al iniciar la conexión:', error);
                showErrorMessage('Error al iniciar la conexión de WhatsApp');
                setLoadingState(connectBtn, false, 'Conectar WhatsApp');
            });
    }
    
    function disconnect() {
        if (!confirm('¿Está seguro de que desea desconectar WhatsApp?')) {
            return;
        }
        
        setLoadingState(disconnectBtn, true, 'Desconectando...');
        
        fetch('api/whatsapp-disconnect')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(data.message || 'WhatsApp desconectado correctamente');
                    
                    // Recargar la página después de un breve delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showErrorMessage(data.message || 'Error al desconectar WhatsApp');
                    setLoadingState(disconnectBtn, false, 'Desconectar WhatsApp');
                }
            })
            .catch(error => {
                console.error('Error al desconectar:', error);
                showErrorMessage('Error al desconectar WhatsApp');
                setLoadingState(disconnectBtn, false, 'Desconectar WhatsApp');
            });
    }
    
    function saveMessagesConfig(e) {
        e.preventDefault();
        
        const submitBtn = whatsappMessagesForm.querySelector('button[type="submit"]');
        setLoadingState(submitBtn, true, 'Guardando...');
        
        // Recopilar datos del formulario
        const formData = new FormData(whatsappMessagesForm);
        const data = {};
        
        formData.forEach((value, key) => {
            if (key.startsWith('whatsapp_notify_')) {
                // Para checkboxes, si está en el FormData significa que está marcado
                data[key] = '1';
            } else {
                data[key] = value.trim();
            }
        });
        
        // Asegurar que los checkboxes no marcados se envíen como '0'
        const notificationFields = [
            'whatsapp_notify_nueva_reserva',
            'whatsapp_notify_confirmacion', 
            'whatsapp_notify_recordatorio',
            'whatsapp_notify_cancelacion'
        ];
        
        notificationFields.forEach(field => {
            if (!data[field]) {
                data[field] = '0';
            }
        });
        
        // Enviar configuración
        fetch('api/actualizar-configuracion', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showSuccessMessage('Configuración de mensajes guardada correctamente');
            } else {
                showErrorMessage(result.message || 'Error al guardar la configuración');
            }
        })
        .catch(error => {
            console.error('Error al guardar configuración:', error);
            showErrorMessage('Error al guardar la configuración');
        })
        .finally(() => {
            setLoadingState(submitBtn, false, 'Guardar configuración de mensajes');
        });
    }
    
    function setLoadingState(button, isLoading, text) {
        if (!button) return;
        
        button.disabled = isLoading;
        
        if (isLoading) {
            button.innerHTML = `<i class="ri-loader-line animate-spin mr-2"></i>${text}`;
        } else {
            const icon = button.classList.contains('bg-green-600') ? 'ri-whatsapp-line' : 
                        button.classList.contains('bg-red-600') ? 'ri-logout-box-line' : 'ri-save-line';
            button.innerHTML = `<i class="${icon} mr-2"></i>${text}`;
        }
    }
    
    function showSuccessMessage(message) {
        const successMessage = document.getElementById('successMessage');
        const successText = document.getElementById('successText');
        
        if (successMessage && successText) {
            successText.textContent = message;
            successMessage.classList.remove('hidden');
            
            setTimeout(() => {
                successMessage.classList.add('hidden');
            }, 4000);
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
            }, 4000);
        }
    }
    
    // Auto-refresh del estado de conexión si está en proceso de conexión
    if (typeof whatsappStatus !== 'undefined' && 
        (whatsappStatus.status === 'connecting' || whatsappStatus.status === 'qr_ready')) {
        
        // Verificar el estado cada 3 segundos
        const statusInterval = setInterval(() => {
            fetch('api/whatsapp-status')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'connected') {
                        clearInterval(statusInterval);
                        showSuccessMessage('¡WhatsApp conectado correctamente!');
                        
                        // Recargar la página después de mostrar el mensaje
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else if (data.status === 'error') {
                        clearInterval(statusInterval);
                        showErrorMessage('Error en la conexión de WhatsApp');
                    }
                })
                .catch(error => {
                    console.error('Error verificando estado:', error);
                });
        }, 3000);
        
        // Detener el polling después de 2 minutos
        setTimeout(() => {
            clearInterval(statusInterval);
        }, 2 * 60 * 1000);
    }
});