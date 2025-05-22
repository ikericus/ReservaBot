document.addEventListener('DOMContentLoaded', function() {

    // Referencias a elementos del DOM
    const configForm = document.getElementById('configForm');
    const toggleModo = document.getElementById('toggleModo');
    const modoLabel = document.getElementById('modoLabel');
    const modoDescription = document.getElementById('modoDescription');
    const toggleDayCheckboxes = document.querySelectorAll('.toggle-day');
    const saveSuccessMessage = document.getElementById('saveSuccessMessage');
    
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
    
    // Event listeners para toggle de días
    toggleDayCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const dia = this.getAttribute('data-dia');
            const inicioInput = document.querySelector(`[name="horario_${dia}_inicio"]`);
            const finInput = document.querySelector(`[name="horario_${dia}_fin"]`);
            
            if (this.checked) {
                inicioInput.disabled = false;
                finInput.disabled = false;
            } else {
                inicioInput.disabled = true;
                finInput.disabled = true;
            }
        });
    });
    
    // Event listener para envío del formulario
    configForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Recopilar datos
        const formData = new FormData();
        
        // Mensajes
        formData.append('mensaje_bienvenida', document.getElementById('mensajeBienvenida').value);
        formData.append('mensaje_confirmacion', document.getElementById('mensajeConfirmacion').value);
        formData.append('mensaje_pendiente', document.getElementById('mensajePendiente').value);
        
        // Modo de aceptación
        formData.append('modo_aceptacion', modoAceptacion);
        
        // Intervalo de reservas
        formData.append('intervalo_reservas', document.getElementById('intervaloReservas').value);
        
        // Horarios
        const diasSemana = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];
        
        diasSemana.forEach(dia => {
            const activo = document.querySelector(`[name="horario_${dia}_activo"]`).checked;
            const inicio = document.querySelector(`[name="horario_${dia}_inicio"]`).value;
            const fin = document.querySelector(`[name="horario_${dia}_fin"]`).value;
            
            formData.append(`horario_${dia}`, `${activo ? 'true' : 'false'}|${inicio}|${fin}`);
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
                saveSuccessMessage.classList.remove('hidden');
                
                // Ocultar el mensaje después de 3 segundos
                setTimeout(() => {
                    saveSuccessMessage.classList.add('hidden');
                }, 3000);
            } else {
                alert('Error al guardar la configuración: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        });
    });
});