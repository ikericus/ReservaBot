// Funciones para los tabs
function showTab(tabName) {
    // Ocultar todos los contenidos
    document.getElementById('pendientesContent').classList.add('hidden');
    document.getElementById('historialContent').classList.add('hidden');
    
    // Mostrar el contenido seleccionado
    document.getElementById(tabName + 'Content').classList.remove('hidden');
    
    // Actualizar estilos de tabs
    const tabs = ['pendientes', 'historial'];
    tabs.forEach(tab => {
        const tabButton = document.getElementById(tab + 'Tab');
        if (tab === tabName) {
            tabButton.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            tabButton.classList.add('border-blue-500', 'text-blue-600');
        } else {
            tabButton.classList.remove('border-blue-500', 'text-blue-600');
            tabButton.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        }
    });
}

// Funciones para aceptar/rechazar reservas
document.addEventListener('DOMContentLoaded', function() {
    // Hacer las tarjetas clickeables para navegar al detalle
    // Solo las de pendientes (no los botones)
    document.querySelectorAll('#pendientesContent .mobile-card, #pendientesContent .desktop-view > div').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function(e) {
            // No navegar si se clickeó un botón
            if (e.target.closest('button')) {
                return;
            }
            const reservaId = this.dataset.id;
            if (reservaId) {
                window.location.href = `/reserva?id=${reservaId}`;
            }
        });
    });
    
    // Las tarjetas del historial ya tienen onclick en el HTML, pero agregamos estilo
    document.querySelectorAll('#historialContent .mobile-card, #historialContent .desktop-view > div').forEach(card => {
        card.style.cursor = 'pointer';
    });
        
    // Botones de aceptar reservas
    document.querySelectorAll('.btn-aceptar').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Evitar que el click llegue al card
            const id = this.getAttribute('data-id');
            
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
                    // Recargar la página para ver los cambios
                    window.location.reload();
                } else {
                    alert('Error al actualizar la reserva: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        });
    });
    
    // Botones de rechazar/cancelar reservas
    document.querySelectorAll('.btn-rechazar, .btn-cancelar').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Evitar que el click llegue al card
            const id = this.getAttribute('data-id');
            
            if (confirm('¿Estás seguro de eliminar esta reserva?')) {
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
                        // Recargar la página para ver los cambios
                        window.location.reload();
                    } else {
                        alert('Error al eliminar la reserva: ' + data.message');
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