document.addEventListener('DOMContentLoaded', function() {
    // Verificar que tengamos los datos
    if (typeof window.reservasData === 'undefined' || typeof window.semanaData === 'undefined') {
        console.error('No se encontraron datos necesarios');
        return;
    }
    
    // Variables globales
    const reservasPorFecha = window.reservasData;
    const semanaInfo = window.semanaData;
    
    // Nombres de días y meses
    const dayNames = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    const dayNamesShort = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const monthNames = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
    ];
    
    // Referencias a elementos del DOM
    const weekTitle = document.getElementById('weekTitle');
    const weekDaysGrid = document.getElementById('weekDaysGrid');
    
    /**
     * Formatea una fecha como YYYY-MM-DD
     */
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    /**
     * Obtiene las reservas para una fecha específica
     */
    function getReservasByFecha(fecha) {
        return reservasPorFecha[fecha] || [];
    }
    
    /**
     * Verifica si una fecha es hoy
     */
    function isToday(date) {
        const today = new Date();
        return date.getDate() === today.getDate() && 
               date.getMonth() === today.getMonth() && 
               date.getFullYear() === today.getFullYear();
    }
    
    /**
     * Capitaliza la primera letra de una cadena
     */
    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    /**
     * Inicializa la vista de semana
     */
    function initWeekView() {
        // Actualizar el título de la semana
        updateWeekTitle();
        
        // Renderizar el grid de la semana
        renderWeekGrid();
        
        console.log('Vista de semana inicializada desde:', semanaInfo.inicioSemana, 'hasta:', semanaInfo.finSemana);
    }
    
    /**
     * Actualiza el título de la semana
     */
    function updateWeekTitle() {
        if (!weekTitle) return;
        
        const inicioSemana = new Date(semanaInfo.inicioSemana + 'T00:00:00');
        const finSemana = new Date(semanaInfo.finSemana + 'T00:00:00');
        
        const inicioFormatted = `${inicioSemana.getDate()} ${monthNames[inicioSemana.getMonth()].substring(0, 3)}`;
        const finFormatted = `${finSemana.getDate()} ${monthNames[finSemana.getMonth()].substring(0, 3)}`;
        
        weekTitle.textContent = `${inicioFormatted} - ${finFormatted} ${finSemana.getFullYear()}`;
    }
    
    /**
     * Renderiza el grid de la semana
     */
    function renderWeekGrid() {
        if (!weekDaysGrid) return;
        
        // Limpiar contenido anterior
        weekDaysGrid.innerHTML = '';
        
        const inicioSemana = new Date(semanaInfo.inicioSemana + 'T00:00:00');
        
        // Generar los 7 días de la semana
        for (let i = 0; i < 7; i++) {
            const currentDay = new Date(inicioSemana);
            currentDay.setDate(currentDay.getDate() + i);
            
            renderWeekDayCell(currentDay);
        }
    }
    
    /**
     * Renderiza una celda de día en la vista de semana
     */
    function renderWeekDayCell(date) {
        const formattedDate = formatDate(date);
        const dayReservas = getReservasByFecha(formattedDate);
        const todayClass = isToday(date);
        
        // Crear la celda del día
        const dayCell = document.createElement('div');
        dayCell.className = `week-day-cell ${todayClass ? 'today' : ''}`;
        
        // Hacer la celda clickeable para ir a la vista de día
        dayCell.addEventListener('click', function() {
            window.location.href = `/dia?date=${formattedDate}`;
        });
        
        // Crear el número del día
        const dayNumber = document.createElement('div');
        dayNumber.className = 'week-day-number';
        dayNumber.textContent = date.getDate();
        dayCell.appendChild(dayNumber);
        
        // Crear contenedor de reservas
        const reservationsContainer = document.createElement('div');
        reservationsContainer.className = 'week-day-reservations';
        dayCell.appendChild(reservationsContainer);
        
        // Mostrar reservas (máximo 3, luego mostrar "+" con el resto)
        if (dayReservas.length > 0) {
            // Ordenar reservas por hora
            const reservasOrdenadas = [...dayReservas].sort((a, b) => {
                return a.hora.localeCompare(b.hora);
            });
            
            const maxReservasVisibles = 3;
            const reservasVisibles = reservasOrdenadas.slice(0, maxReservasVisibles);
            const reservasRestantes = reservasOrdenadas.length - maxReservasVisibles;
            
            // Mostrar reservas visibles
            reservasVisibles.forEach(reserva => {
                const reservaItem = document.createElement('div');
                reservaItem.className = `week-reservation-item ${reserva.estado}`;
                
                // Hacer que el item sea clickeable y evitar propagación
                reservaItem.addEventListener('click', function(e) {
                    e.stopPropagation();
                    window.location.href = `/reserva?id=${reserva.id}`;
                });
                
                reservaItem.innerHTML = `
                    <div class="week-reservation-time">${reserva.hora.substring(0, 5)}</div>
                    <div class="week-reservation-client">${reserva.nombre}</div>
                `;
                
                reservationsContainer.appendChild(reservaItem);
            });
            
            // Mostrar indicador de reservas adicionales si las hay
            if (reservasRestantes > 0) {
                const moreItem = document.createElement('div');
                moreItem.className = 'week-reservation-item';
                moreItem.style.background = '#e5e7eb';
                moreItem.style.color = '#6b7280';
                moreItem.style.textAlign = 'center';
                moreItem.style.fontSize = '0.7rem';
                moreItem.textContent = `+${reservasRestantes} más`;
                
                moreItem.addEventListener('click', function(e) {
                    e.stopPropagation();
                    window.location.href = `/dia?date=${formattedDate}`;
                });
                
                reservationsContainer.appendChild(moreItem);
            }
        }
        
        // Agregar estadísticas al final de la celda
        if (dayReservas.length > 0) {
            const statsContainer = document.createElement('div');
            statsContainer.className = 'week-day-stats';
            
            const confirmadas = dayReservas.filter(r => r.estado === 'confirmada').length;
            const pendientes = dayReservas.filter(r => r.estado === 'pendiente').length;
            
            if (confirmadas > 0) {
                const confirmadasBadge = document.createElement('div');
                confirmadasBadge.className = 'week-stat-badge confirmada';
                confirmadasBadge.textContent = `${confirmadas}C`;
                statsContainer.appendChild(confirmadasBadge);
            }
            
            if (pendientes > 0) {
                const pendientesBadge = document.createElement('div');
                pendientesBadge.className = 'week-stat-badge pendiente';
                pendientesBadge.textContent = `${pendientes}P`;
                statsContainer.appendChild(pendientesBadge);
            }
            
            dayCell.appendChild(statsContainer);
        }
        
        weekDaysGrid.appendChild(dayCell);
    }
    
    /**
     * Configurar navegación por teclado
     */
    function setupKeyboardNavigation() {
        document.addEventListener('keydown', function(e) {
            // Solo funcionar si no estamos en un input o textarea
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            
            switch (e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    window.location.href = `/semana?date=${semanaInfo.semanaAnterior}`;
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    window.location.href = `/semana?date=${semanaInfo.semanaSiguiente}`;
                    break;
                case 'd':
                case 'D':
                    e.preventDefault();
                    window.location.href = `/dia?date=${semanaInfo.inicioSemana}`;
                    break;
                case 'm':
                case 'M':
                    e.preventDefault();
                    window.location.href = `/mes?date=${semanaInfo.inicioSemana}`;
                    break;
            }
        });
    }
    
    /**
     * Configurar tooltips para mejor UX
     */
    function setupTooltips() {
        // Agregar tooltips a los botones de navegación
        const prevBtn = document.querySelector('a[href*="' + semanaInfo.semanaAnterior + '"]');
        const nextBtn = document.querySelector('a[href*="' + semanaInfo.semanaSiguiente + '"]');
        
        if (prevBtn) {
            prevBtn.title = 'Semana anterior (←)';
        }
        
        if (nextBtn) {
            nextBtn.title = 'Semana siguiente (→)';
        }
        
        // Agregar tooltips a los enlaces de vista
        const dayLink = document.querySelector('a[href*="/dia"]');
        const monthLink = document.querySelector('a[href*="/mes"]');
        
        if (dayLink) {
            dayLink.title = 'Vista día (D)';
        }
        
        if (monthLink) {
            monthLink.title = 'Vista mes (M)';
        }
    }
    
    // Inicializar la vista
    initWeekView();
    setupKeyboardNavigation();
    setupTooltips();
    
    const totalReservas = Object.values(reservasPorFecha).reduce((total, reservas) => total + reservas.length, 0);
    console.log('Vista de semana cargada con', totalReservas, 'reservas');
});