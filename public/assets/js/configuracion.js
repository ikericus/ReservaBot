document.addEventListener('DOMContentLoaded', function() {
    // === CÓDIGO DE CONFIGURACIÓN - Solo se ejecuta si estamos en config.php ===
    
    // Referencias a elementos del DOM de configuración
    const configForm = document.getElementById('configForm');
    const toggleModo = document.getElementById('toggleModo');
    const modoLabel = document.getElementById('modoLabel');
    const modoDescription = document.getElementById('modoDescription');
    const toggleDayCheckboxes = document.querySelectorAll('.toggle-day');
    const saveSuccessMessage = document.getElementById('saveSuccessMessage');
    
    // Solo ejecutar código de configuración si los elementos existen
    if (configForm && toggleModo && modoLabel && modoDescription) {
        // Estado actual del modo
        let modoAceptacion = modoLabel.textContent.trim() === 'Automático' ? 'automatico' : 'manual';
        
        // Toggle modo aceptación
        toggleModo.addEventListener('click', function() {
            const toggleButton = toggleModo.querySelector('span');
            
            if (toggleButton.classList.contains('translate-x-1')) {
                // Cambiar a modo automático
                toggleButton.classList.remove('translate-x-1');
                toggleButton.classList.add('translate-x-6');
                toggleModo.classList.remove('bg-gray-200');
                toggleModo.classList.add('bg-blue-600');
                modoLabel.textContent = 'Automático';
                modoDescription.textContent = 'Las reservas se aceptan automáticamente en horarios disponibles';
                modoAceptacion = 'automatico';
            } else {
                // Cambiar a modo manual
                toggleButton.classList.remove('translate-x-6');
                toggleButton.classList.add('translate-x-1');
                toggleModo.classList.remove('bg-blue-600');
                toggleModo.classList.add('bg-gray-200');
                modoLabel.textContent = 'Manual';
                modoDescription.textContent = 'Las reservas requieren aprobación manual';
                modoAceptacion = 'manual';
            }
        });
        
        // Event listeners para toggle de días - Solo si existen
        if (toggleDayCheckboxes.length > 0) {
            toggleDayCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const dia = this.getAttribute('data-dia');
                    const inicioInput = document.querySelector(`[name="horario_${dia}_inicio"]`);
                    const finInput = document.querySelector(`[name="horario_${dia}_fin"]`);
                    
                    if (this.checked) {
                        if (inicioInput) inicioInput.disabled = false;
                        if (finInput) finInput.disabled = false;
                    } else {
                        if (inicioInput) inicioInput.disabled = true;
                        if (finInput) finInput.disabled = true;
                    }
                });
            });
        }
        
        // Event listener para envío del formulario de configuración
        configForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Recopilar datos
            const formData = new FormData();
            
            // Mensajes
            const mensajeBienvenida = document.getElementById('mensajeBienvenida');
            const mensajeConfirmacion = document.getElementById('mensajeConfirmacion');
            const mensajePendiente = document.getElementById('mensajePendiente');
            const intervaloReservas = document.getElementById('intervaloReservas');
            
            if (mensajeBienvenida) formData.append('mensaje_bienvenida', mensajeBienvenida.value);
            if (mensajeConfirmacion) formData.append('mensaje_confirmacion', mensajeConfirmacion.value);
            if (mensajePendiente) formData.append('mensaje_pendiente', mensajePendiente.value);
            
            // Modo de aceptación
            formData.append('modo_aceptacion', modoAceptacion);
            
            // Intervalo de reservas
            if (intervaloReservas) formData.append('intervalo_reservas', intervaloReservas.value);
            
            // Horarios
            const diasSemana = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];
            
            diasSemana.forEach(dia => {
                const activoCheckbox = document.querySelector(`[name="horario_${dia}_activo"]`);
                const inicioInput = document.querySelector(`[name="horario_${dia}_inicio"]`);
                const finInput = document.querySelector(`[name="horario_${dia}_fin"]`);
                
                if (activoCheckbox && inicioInput && finInput) {
                    const activo = activoCheckbox.checked;
                    const inicio = inicioInput.value;
                    const fin = finInput.value;
                    
                    formData.append(`horario_${dia}`, `${activo ? 'true' : 'false'}|${inicio}|${fin}`);
                }
            });
            
            // Convertir FormData a objeto
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            // Enviar la solicitud
            fetch('api/actualizar-configuracion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    if (saveSuccessMessage) {
                        saveSuccessMessage.classList.remove('hidden');
                        
                        // Ocultar el mensaje después de 3 segundos
                        setTimeout(() => {
                            saveSuccessMessage.classList.add('hidden');
                        }, 3000);
                    }
                } else {
                    alert('Error al guardar la configuración: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        });
    }
}); 