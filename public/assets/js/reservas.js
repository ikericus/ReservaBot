// Funciones para los tabs
function showTab(tabName) {
    // Ocultar todos los contenidos
    document.getElementById('pendientesContent').classList.add('hidden');
    document.getElementById('confirmadasContent').classList.add('hidden');
    document.getElementById('historialContent').classList.add('hidden');
    
    // Mostrar el contenido seleccionado
    document.getElementById(tabName + 'Content').classList.remove('hidden');
    
    // Actualizar estilos de tabs
    const tabs = ['pendientes', 'confirmadas', 'historial'];
    tabs.forEach(tab => {
        const tabButton = document.getElementById(tab + 'Tab');
        if (tab === tabName) {
            tabButton.className = 'border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center';
        } else {
            tabButton.className = 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center';
        }
    });
}

// Funciones para aceptar/rechazar reservas
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const pendientesTab = document.getElementById('pendientesTab');
    const confirmadasTab = document.getElementById('confirmadasTab');
    const historialTab = document.getElementById('historialTab');
    const pendientesContent = document.getElementById('pendientesContent');
    const confirmadasContent = document.getElementById('confirmadasContent');
    const historialContent = document.getElementById('historialContent');
    
    // Funcionalidad de tabs - Solo si existen los elementos
    if (pendientesTab && confirmadasTab && historialTab && pendientesContent && confirmadasContent && historialContent) {
        // Tab Pendientes
        pendientesTab.addEventListener('click', function() {
            showTab('pendientes');
        });
        
        // Tab Confirmadas
        confirmadasTab.addEventListener('click', function() {
            showTab('confirmadas');
        });
        
        // Tab Historial
        historialTab.addEventListener('click', function() {
            showTab('historial');
        });
    }
    
    // Hacer las tarjetas clickeables para navegar al detalle
    // Solo las de pendientes y confirmadas (no los botones)
    document.querySelectorAll('#pendientesContent .mobile-card, #pendientesContent .desktop-view > div, #confirmadasContent .mobile-card, #confirmadasContent .desktop-view > div').forEach(card => {
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