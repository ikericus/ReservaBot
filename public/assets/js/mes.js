document.addEventListener('DOMContentLoaded', function() {
    // Verificar que tengamos los datos
    if (typeof window.reservasData === 'undefined' || typeof window.mesData === 'undefined') {
        console.error('No se encontraron datos necesarios');
        return;
    }
    
    // Variables globales
    const reservasPorFecha = window.reservasData;
    const mesInfo = window.mesData;
    
    // Nombres de días y meses
    const monthNames = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    
    // Referencias a elementos del DOM
    const monthTitle = document.getElementById('monthTitle');
    const mobileMonthDisplay = document.getElementById('mobileMonthDisplay');
    const calendarGrid = document.getElementById('calendarGrid');
    const mobileCalendarBody = document.getElementById('mobileCalendarBody');
    
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
     * Inicializa la vista de mes
     */
    function initMonthView() {
        // Actualizar el título del mes (desktop y móvil)
        updateMonthTitle();
        
        // Renderizar el grid del calendario desktop
        renderCalendarGrid();
        
        // Renderizar el grid del calendario móvil
        renderMobileCalendarGrid();
        
        console.log('Vista de mes inicializada para:', mesInfo.año, mesInfo.mes + 1);
    }
    
    /**
     * Actualiza el título del mes
     */
    function updateMonthTitle() {
        const titleText = `${monthNames[mesInfo.mes]} ${mesInfo.año}`;
        
        if (monthTitle) {
            monthTitle.textContent = titleText;
        }
        
        if (mobileMonthDisplay) {
            mobileMonthDisplay.textContent = titleText;
        }
    }
    
    /**
     * Renderiza el grid del calendario desktop
     */
    function renderCalendarGrid() {
        if (!calendarGrid) return;
        
        // Limpiar contenido anterior
        calendarGrid.innerHTML = '';
        
        // Obtener primer y último día del mes
        const firstDay = new Date(mesInfo.año, mesInfo.mes, 1);
        const lastDay = new Date(mesInfo.año, mesInfo.mes + 1, 0);
        
        // Ajustar el día de la semana (Lunes = 0, Domingo = 6)
        let firstDayOfWeek = firstDay.getDay() - 1;
        if (firstDayOfWeek < 0) firstDayOfWeek = 6;
        
        // Agregar días del mes anterior
        for (let i = 0; i < firstDayOfWeek; i++) {
            const prevMonthDay = new Date(mesInfo.año, mesInfo.mes, -firstDayOfWeek + i + 1);
            addCalendarDayCell(prevMonthDay, false);
        }
        
        // Agregar días del mes actual
        for (let i = 1; i <= lastDay.getDate(); i++) {
            const currentMonthDay = new Date(mesInfo.año, mesInfo.mes, i);
            addCalendarDayCell(currentMonthDay, true);
        }
        
        // Completar con días del mes siguiente
        const totalCellsSoFar = firstDayOfWeek + lastDay.getDate();
        const cellsNeeded = Math.ceil(totalCellsSoFar / 7) * 7;
        const nextMonthDays = cellsNeeded - totalCellsSoFar;
        
        for (let i = 1; i <= nextMonthDays; i++) {
            const nextMonthDay = new Date(mesInfo.año, mesInfo.mes + 1, i);
            addCalendarDayCell(nextMonthDay, false);
        }
    }
    
    /**
     * Renderiza el grid del calendario móvil
     */
    function renderMobileCalendarGrid() {
        if (!mobileCalendarBody) return;
        
        // Limpiar contenido anterior
        mobileCalendarBody.innerHTML = '';
        
        // Obtener primer y último día del mes
        const firstDay = new Date(mesInfo.año, mesInfo.mes, 1);
        const lastDay = new Date(mesInfo.año, mesInfo.mes + 1, 0);
        
        // Ajustar el día de la semana (Lunes = 0, Domingo = 6)
        let firstDayOfWeek = firstDay.getDay() - 1;
        if (firstDayOfWeek < 0) firstDayOfWeek = 6;
        
        // Agregar días del mes anterior
        for (let i = 0; i < firstDayOfWeek; i++) {
            const prevMonthDay = new Date(mesInfo.año, mesInfo.mes, -firstDayOfWeek + i + 1);
            addMobileCalendarDayCell(prevMonthDay, false);
        }
        
        // Agregar días del mes actual
        for (let i = 1; i <= lastDay.getDate(); i++) {
            const currentMonthDay = new Date(mesInfo.año, mesInfo.mes, i);
            addMobileCalendarDayCell(currentMonthDay, true);
        }
        
        // Completar con días del mes siguiente
        const totalCellsSoFar = firstDayOfWeek + lastDay.getDate();
        const cellsNeeded = Math.ceil(totalCellsSoFar / 7) * 7;
        const nextMonthDays = cellsNeeded - totalCellsSoFar;
        
        for (let i = 1; i <= nextMonthDays; i++) {
            const nextMonthDay = new Date(mesInfo.año, mesInfo.mes + 1, i);
            addMobileCalendarDayCell(nextMonthDay, false);
        }
    }
    
    /**
     * Agrega una celda de día al calendario desktop
     */
    function addCalendarDayCell(date, isCurrentMonth) {
        const formattedDate = formatDate(date);
        const dayReservas = getReservasByFecha(formattedDate);
        const todayClass = isToday(date);
        
        // Crear celda del día
        const dayCell = document.createElement('div');
        dayCell.className = `calendar-day-cell ${
            isCurrentMonth ? '' : 'other-month'
        } ${todayClass ? 'today' : ''}`;
        
        // Hacer la celda clickeable para ir a la vista de día
        dayCell.addEventListener('click', function() {
            window.location.href = `/dia?date=${formattedDate}`;
        });
        
        // Crear número del día
        const dayNumber = document.createElement('div');
        dayNumber.className = 'calendar-day-number';
        dayNumber.textContent = date.getDate();
        dayCell.appendChild(dayNumber);
        
        // Crear contenedor de reservas
        const reservationsContainer = document.createElement('div');
        reservationsContainer.className = 'calendar-day-reservations';
        dayCell.appendChild(reservationsContainer);
        
        // Mostrar reservas (máximo 3, luego mostrar "+X más")
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
                reservaItem.className = `calendar-reservation-item ${reserva.estado}`;
                
                // Hacer que el item sea clickeable y evitar propagación
                reservaItem.addEventListener('click', function(e) {
                    e.stopPropagation();
                    window.location.href = `/reserva?id=${reserva.id}`;
                });
                
                reservaItem.innerHTML = `
                    <span class="calendar-reservation-time">${reserva.hora.substring(0, 5)}</span>
                    <span class="calendar-reservation-client">${reserva.nombre}</span>
                `;
                
                reservationsContainer.appendChild(reservaItem);
            });
            
            // Mostrar indicador de reservas adicionales si las hay
            if (reservasRestantes > 0) {
                const moreItem = document.createElement('div');
                moreItem.className = 'calendar-more-reservations';
                moreItem.textContent = `+${reservasRestantes} más`;
                
                moreItem.addEventListener('click', function(e) {
                    e.stopPropagation();
                    window.location.href = `/dia?date=${formattedDate}`;
                });
                
                reservationsContainer.appendChild(moreItem);
            }
        }
        
        calendarGrid.appendChild(dayCell);
    }
    
    /**
     * Agrega una celda de día al calendario móvil
     */
    function addMobileCalendarDayCell(date, isCurrentMonth) {
        const formattedDate = formatDate(date);
        const dayReservas = getReservasByFecha(formattedDate);
        const todayClass = isToday(date);
        
        // Crear celda del día móvil
        const dayCell = document.createElement('div');
        dayCell.className = `mobile-day-cell ${
            isCurrentMonth ? '' : 'other-month'
        } ${todayClass ? 'today' : ''}`;
        
        // Hacer la celda clickeable para ir a la vista de día
        dayCell.addEventListener('click', function() {
            window.location.href = `/dia?date=${formattedDate}`;
        });
        
        // Crear número del día
        const dayNumber = document.createElement('div');
        dayNumber.className = 'mobile-day-number';
        dayNumber.textContent = date.getDate();
        dayCell.appendChild(dayNumber);
        
        // Agregar badge con total de reservas si las hay
        if (dayReservas.length > 0) {
            const totalBadge = document.createElement('div');
            totalBadge.className = 'mobile-total-badge';
            totalBadge.textContent = dayReservas.length;
            dayCell.appendChild(totalBadge);
        }
        
        // Crear indicadores de estado de reservas
        if (dayReservas.length > 0) {
            const indicatorsContainer = document.createElement('div');
            indicatorsContainer.className = 'mobile-day-indicators';
            
            // Contar reservas por estado
            const confirmadas = dayReservas.filter(r => r.estado === 'confirmada').length;
            const pendientes = dayReservas.filter(r => r.estado === 'pendiente').length;
            
            // Mostrar máximo 5 indicadores para no saturar
            const maxIndicators = 5;
            let indicatorsShown = 0;
            
            // Indicadores para confirmadas
            for (let i = 0; i < Math.min(confirmadas, maxIndicators - indicatorsShown); i++) {
                const indicator = document.createElement('div');
                indicator.className = 'mobile-indicator confirmada';
                indicator.textContent = ' '; // Contenido vacío para que se vea el punto
                indicatorsContainer.appendChild(indicator);
                indicatorsShown++;
            }
            
            // Indicadores para pendientes
            for (let i = 0; i < Math.min(pendientes, maxIndicators - indicatorsShown); i++) {
                const indicator = document.createElement('div');
                indicator.className = 'mobile-indicator pendiente';
                indicator.textContent = ' '; // Contenido vacío para que se vea el punto
                indicatorsContainer.appendChild(indicator);
                indicatorsShown++;
            }
            
            dayCell.appendChild(indicatorsContainer);
        }
        
        mobileCalendarBody.appendChild(dayCell);
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
                    window.location.href = `/mes?date=${mesInfo.mesAnterior}`;
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    window.location.href = `/mes?date=${mesInfo.mesSiguiente}`;
                    break;
                case 'd':
                case 'D':
                    e.preventDefault();
                    // Ir al día actual o primer día del mes
                    const today = new Date();
                    const todayFormatted = formatDate(today);
                    window.location.href = `/dia?date=${todayFormatted}`;
                    break;
                case 'w':
                case 'W':
                    e.preventDefault();
                    // Ir a la semana actual o primera semana del mes
                    const todayWeek = new Date();
                    const todayWeekFormatted = formatDate(todayWeek);
                    window.location.href = `/semana?date=${todayWeekFormatted}`;
                    break;
            }
        });
    }
    
    /**
     * Configurar tooltips para mejor UX
     */
    function setupTooltips() {
        // Agregar tooltips a los botones de navegación
        const prevBtns = document.querySelectorAll('a[href*="' + mesInfo.mesAnterior + '"]');
        const nextBtns = document.querySelectorAll('a[href*="' + mesInfo.mesSiguiente + '"]');
        
        prevBtns.forEach(btn => {
            btn.title = 'Mes anterior (←)';
        });
        
        nextBtns.forEach(btn => {
            btn.title = 'Mes siguiente (→)';
        });
        
        // Agregar tooltips a los enlaces de vista
        const dayLinks = document.querySelectorAll('a[href*="/dia"]');
        const weekLinks = document.querySelectorAll('a[href*="/semana"]');
        
        dayLinks.forEach(link => {
            link.title = 'Vista día (D)';
        });
        
        weekLinks.forEach(link => {
            link.title = 'Vista semana (W)';
        });
    }
    
    /**
     * Configurar efectos visuales adicionales
     */
    function setupVisualEffects() {
        // Agregar animación suave a los grids cuando se cargan
        const grids = [calendarGrid, mobileCalendarBody].filter(Boolean);
        
        grids.forEach(grid => {
            grid.style.opacity = '0';
            grid.style.transform = 'translateY(20px)';
            
            // Animar entrada
            setTimeout(() => {
                grid.style.transition = 'all 0.5s ease-out';
                grid.style.opacity = '1';
                grid.style.transform = 'translateY(0)';
            }, 100);
        });
    }
    
    // Inicializar la vista
    initMonthView();
    setupKeyboardNavigation();
    setupTooltips();
    setupVisualEffects();
    
    const totalReservas = Object.values(reservasPorFecha).reduce((total, reservas) => total + reservas.length, 0);
    console.log('Vista de mes cargada con', totalReservas, 'reservas');
    console.log('Reservas por fecha:', reservasPorFecha);
});