document.addEventListener('DOMContentLoaded', function() {
    // Confirmar reserva
    document.querySelectorAll('.btn-confirmar').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            
            if (confirm('¿Confirmar esta reserva?')) {
                fetch('api/actualizar-reserva', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id, estado: 'confirmada' })
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
    });
    
    // Enviar mensaje (simulado)
    document.querySelectorAll('.btn-mensaje').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            // Redirige a la vista de detalle donde se puede enviar un mensaje
            window.location.href = `reserva-detail?id=${id}&action=message`;
        });
    });
    
    // Eliminar reserva
    document.querySelectorAll('.btn-eliminar').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            
            if (confirm('¿Estás seguro de eliminar esta reserva? Esta acción no se puede deshacer.')) {
                fetch('api/eliminar-reserva', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error al eliminar la reserva: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud');
                });
            }
        });
    });
});