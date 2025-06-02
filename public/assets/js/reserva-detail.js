document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const confirmarBtn = document.getElementById('confirmarBtn');
    const cancelarStatusBtn = document.getElementById('cancelarStatusBtn');
    const mensajeBtn = document.getElementById('mensajeBtn');
    const eliminarBtn = document.getElementById('eliminarBtn');
    const mensajesPanel = document.getElementById('mensajesPanel');
    const cancelarMensajeBtn = document.getElementById('cancelarMensajeBtn');
    const enviarMensajeBtn = document.getElementById('enviarMensajeBtn');
    const mensajeTexto = document.getElementById('mensajeTexto');
    const deleteConfirmDialog = document.getElementById('deleteConfirmDialog');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    
    // Confirmar reserva
    if (confirmarBtn) {
        confirmarBtn.addEventListener('click', function() {
            if (confirm('¿Confirmar esta reserva?')) {
                fetch('api/actualizar-reserva', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: reservaId, estado: 'confirmada' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error al actualizar la reserva: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud');
                });
            }
        });
    }
    
    // Cambiar a estado pendiente
    if (cancelarStatusBtn) {
        cancelarStatusBtn.addEventListener('click', function() {
            if (confirm('¿Marcar esta reserva como pendiente?')) {
                fetch('api/actualizar-reserva', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: reservaId, estado: 'pendiente' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error al actualizar la reserva: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud');
                });
            }
        });
    }
    
    // Mostrar panel de mensaje
    mensajeBtn.addEventListener('click', function() {
        mensajesPanel.classList.remove('hidden');
        mensajeTexto.focus();
    });
    
    // Cancelar envío de mensaje
    cancelarMensajeBtn.addEventListener('click', function() {
        mensajesPanel.classList.add('hidden');
        mensajeTexto.value = '';
    });
    
    // Enviar mensaje (simulado)
    enviarMensajeBtn.addEventListener('click', function() {
        const mensaje = mensajeTexto.value.trim();
        if (mensaje) {
            // En una implementación real, aquí enviarías el mensaje
            alert(`Mensaje enviado: ${mensaje}`);
            mensajesPanel.classList.add('hidden');
            mensajeTexto.value = '';
        } else {
            alert('Por favor, escribe un mensaje antes de enviar.');
        }
    });
    
    // Mostrar diálogo de confirmación para eliminar
    eliminarBtn.addEventListener('click', function() {
        deleteConfirmDialog.classList.remove('hidden');
    });
    
    // Cancelar eliminación
    cancelDeleteBtn.addEventListener('click', function() {
        deleteConfirmDialog.classList.add('hidden');
    });
    
    // Confirmar eliminación
    confirmDeleteBtn.addEventListener('click', function() {
        fetch('api/eliminar-reserva', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: reservaId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `day?date=${reservaFecha}`;
            } else {
                alert('Error al eliminar la reserva: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        });
    });
});