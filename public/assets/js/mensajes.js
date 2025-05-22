document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const filterForm = document.getElementById('filterForm');
    const statsRecibidos = document.getElementById('statsRecibidos');
    const statsEnviados = document.getElementById('statsEnviados');
    
    // Cargar estadísticas al inicio
    loadStats();
    
    // Event listener para el formulario de filtros
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(filterForm);
            const searchParams = new URLSearchParams();
            
            // Construir parámetros de búsqueda
            for (const [key, value] of formData.entries()) {
                if (value.trim() !== '') {
                    searchParams.append(key, value);
                }
            }
            
            // Redirigir con los filtros aplicados
            window.location.href = 'mensajes.php?' + searchParams.toString();
        });
    }
    
    // Función para cargar estadísticas
    function loadStats() {
        // Obtener parámetros actuales de la URL
        const urlParams = new URLSearchParams(window.location.search);
        const search = urlParams.get('search') || '';
        const chat = urlParams.get('chat') || '';
        
        // Construir URL para la API de estadísticas
        const apiUrl = 'api/mensajes-stats.php?' + new URLSearchParams({
            search: search,
            chat: chat
        }).toString();
        
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.stats) {
                    updateStatsDisplay(data.stats);
                } else {
                    console.error('Error al cargar estadísticas:', data.message);
                    showErrorStats();
                }
            })
            .catch(error => {
                console.error('Error en la petición de estadísticas:', error);
                showErrorStats();
            });
    }
    
    // Función para actualizar la visualización de estadísticas
    function updateStatsDisplay(stats) {
        if (statsRecibidos) {
            const recibidosCount = statsRecibidos.querySelector('.text-2xl');
            if (recibidosCount) {
                recibidosCount.textContent = numberWithCommas(stats.recibidos || 0);
            }
        }
        
        if (statsEnviados) {
            const enviadosCount = statsEnviados.querySelector('.text-2xl');
            if (enviadosCount) {
                enviadosCount.textContent = numberWithCommas(stats.enviados || 0);
            }
        }
    }
    
    // Función para mostrar error en estadísticas
    function showErrorStats() {
        if (statsRecibidos) {
            const recibidosCount = statsRecibidos.querySelector('.text-2xl');
            if (recibidosCount) {
                recibidosCount.textContent = 'Error';
            }
        }
        
        if (statsEnviados) {
            const enviadosCount = statsEnviados.querySelector('.text-2xl');
            if (enviadosCount) {
                enviadosCount.textContent = 'Error';
            }
        }
    }
    
    // Función para formatear números con comas
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Event listeners para abrir detalles de mensaje (si se implementa modal)
    const messageRows = document.querySelectorAll('tbody tr');
    messageRows.forEach(row => {
        // Hacer las filas clickables para ver detalles
        row.style.cursor = 'pointer';
        row.addEventListener('click', function() {
            const messageCell = this.querySelector('td:nth-child(4)'); // Columna del mensaje
            const fullMessage = messageCell.querySelector('.text-sm').textContent;
            
            // Si el mensaje está truncado, mostrar el completo
            if (fullMessage.length > 50) {
                showMessageDetail(this);
            }
        });
    });
    
    // Función para mostrar detalles del mensaje (implementación futura)
    function showMessageDetail(row) {
        const cells = row.querySelectorAll('td');
        if (cells.length < 5) return;
        
        const datetime = cells[0].textContent.trim();
        const chatName = cells[1].querySelector('.text-sm').textContent.trim();
        const chatId = cells[1].querySelector('.text-xs').textContent.trim();
        const direction = cells[2].querySelector('span').textContent.trim();
        const message = cells[3].querySelector('.text-sm').textContent.trim();
        const isAuto = cells[4].querySelector('span') ? 'Sí' : 'No';
        
        // Mostrar modal con detalles (si existe)
        const modal = document.getElementById('messageDetailModal');
        if (modal) {
            document.getElementById('messageDetailChat').textContent = `${chatName} (${chatId})`;
            document.getElementById('messageDetailTime').textContent = datetime;
            document.getElementById('messageDetailBody').textContent = message;
            
            const directionElement = document.getElementById('messageDetailDirection');
            directionElement.innerHTML = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                direction === 'Enviado' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'
            }">${direction}</span>`;
            
            modal.classList.remove('hidden');
        }
    }
    
    // Event listener para cerrar modal de detalles
    const closeDetailBtn = document.getElementById('closeDetailBtn');
    if (closeDetailBtn) {
        closeDetailBtn.addEventListener('click', function() {
            const modal = document.getElementById('messageDetailModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        });
    }
    
    // Cerrar modal al hacer click fuera de él
    const messageDetailModal = document.getElementById('messageDetailModal');
    if (messageDetailModal) {
        messageDetailModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    }
    
    // Auto-actualización de estadísticas cada 30 segundos
    setInterval(loadStats, 30000);
    
    // Función para actualizar la página automáticamente cada 2 minutos
    // (opcional, comentado por defecto)
    /*
    setInterval(function() {
        if (document.hidden) return; // No actualizar si la pestaña no está visible
        
        const currentUrl = new URL(window.location);
        const currentPage = parseInt(currentUrl.searchParams.get('page')) || 1;
        
        // Solo auto-actualizar la primera página para evitar confusión
        if (currentPage === 1) {
            loadStats();
        }
    }, 120000); // 2 minutos
    */
});