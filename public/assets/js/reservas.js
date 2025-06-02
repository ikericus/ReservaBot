// Funciones para los tabs
function showTab(tabName) {
    // Ocultar todos los contenidos
    document.getElementById('pendientesContent').classList.add('hidden');
    document.getElementById('confirmadasContent').classList.add('hidden');
    
    // Mostrar el contenido seleccionado
    document.getElementById(tabName + 'Content').classList.remove('hidden');
    
    // Actualizar estilos de tabs
    const tabs = ['pendientes', 'confirmadas'];
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
    // Referencias a elementos del DOM - Solo para reservas
    const pendientesTab = document.getElementById('pendientesTab');
    const confirmadasTab = document.getElementById('confirmadasTab');
    const pendientesContent = document.getElementById('pendientesContent');
    const confirmadasContent = document.getElementById('confirmadasContent');
    
    // Funcionalidad de tabs - Solo si existen los elementos
    if (pendientesTab && confirmadasTab && pendientesContent && confirmadasContent) {
        // Cambiar entre tabs
        pendientesTab.addEventListener('click', function() {
            pendientesTab.classList.add('border-blue-500', 'text-blue-600');
            pendientesTab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            
            confirmadasTab.classList.remove('border-blue-500', 'text-blue-600');
            confirmadasTab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            
            pendientesContent.classList.remove('hidden');
            pendientesContent.classList.add('block');
            
            confirmadasContent.classList.remove('block');
            confirmadasContent.classList.add('hidden');
        });
        
        confirmadasTab.addEventListener('click', function() {
            confirmadasTab.classList.add('border-blue-500', 'text-blue-600');
            confirmadasTab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            
            pendientesTab.classList.remove('border-blue-500', 'text-blue-600');
            pendientesTab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            
            confirmadasContent.classList.remove('hidden');
            confirmadasContent.classList.add('block');
            
            pendientesContent.classList.remove('block');
            pendientesContent.classList.add('hidden');
        });
    }
        
    // Botones de aceptar reservas
    document.querySelectorAll('.btn-aceptar').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
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
            const id = this.getAttribute('data-id');
            
            if (confirm('¿Estás seguro de eliminar esta reserva?')) {
                fetch('api/eliminar-reserva.php', {
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