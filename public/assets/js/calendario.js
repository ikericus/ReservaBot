document.addEventListener('DOMContentLoaded', function() {
    // Verificar que tengamos los datos de reservas
    if (typeof window.reservas === 'undefined') {
        console.error('No se encontraron datos de reservas');
        window.reservas = [];
    }
    
    // Variables globales
    let currentDate = new Date();
    let currentMobileDate = new Date();
    let currentMobileView = 'month';
    let currentWeekStart = null;
    
    // Actualizar con fecha de demo si es necesario
    if (window.location.href.includes('demo')) {
        currentDate = new Date(2025, 4, 1);
        currentMobileDate = new Date(2025, 4, 1);
    }
    
    // Referencias a elementos del DOM
    const calendarGrid = document.getElementById('calendarGrid');
    const currentMonthDisplay = document.getElementById('currentMonthDisplay');
    const prevMonthButton = document.getElementById('prevMonth');
    const nextMonthButton = document.getElementById('nextMonth');
    
    // Nombres de meses
    const monthNames = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    
    // Nombres de días
    const dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    const dayNamesShort = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    
    // ==================== UTILIDADES ====================
    
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
        return window.reservas.filter(reserva => reserva.fecha === fecha);
    }
    
    /**
     * Obtiene el inicio de la semana (Lunes)
     */
    function getWeekStart(date) {
        const weekStart = new Date(date);
        const day = weekStart.getDay();
        const diff = weekStart.getDate() - day + (day === 0 ? -6 : 1);
        weekStart.setDate(diff);
        return weekStart;
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
    
    // ==================== CALENDARIO DESKTOP ====================
    
    /**
     * Renderiza el calendario desktop
     */
    function renderDesktopCalendar(date) {
        if (!calendarGrid || !currentMonthDisplay) return;
        
        // Actualizar el mes mostrado
        currentMonthDisplay.textContent = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
        
        // Obtener primer y último día del mes
        const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
        const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
        
        // Ajustar el día de la semana (Lunes = 0, Domingo = 6)
        let firstDayOfWeek = firstDay.getDay() - 1;
        if (firstDayOfWeek < 0) firstDayOfWeek = 6;
        
        // Limpiar el calendario
        calendarGrid.innerHTML = '';
        
        // Agregar días del mes anterior
        for (let i = 0; i < firstDayOfWeek; i++) {
            const prevMonthDay = new Date(date.getFullYear(), date.getMonth(), -firstDayOfWeek + i + 1);
            addDesktopDayToCalendar(prevMonthDay, false);
        }
        
        // Agregar días del mes actual
        for (let i = 1; i <= lastDay.getDate(); i++) {
            const currentMonthDay = new Date(date.getFullYear(), date.getMonth(), i);
            addDesktopDayToCalendar(currentMonthDay, true);
        }
        
        // Completar con días del mes siguiente
        const totalCellsSoFar = firstDayOfWeek + lastDay.getDate();
        const cellsNeeded = Math.ceil(totalCellsSoFar / 7) * 7;
        const nextMonthDays = cellsNeeded - totalCellsSoFar;
        
        for (let i = 1; i <= nextMonthDays; i++) {
            const nextMonthDay = new Date(date.getFullYear(), date.getMonth() + 1, i);
            addDesktopDayToCalendar(nextMonthDay, false);
        }
    }
    
    /**
     * Agrega una celda de día al calendario desktop
     */
    function addDesktopDayToCalendar(date, isCurrentMonth) {
        const formattedDate = formatDate(date);
        const dayReservas = getReservasByFecha(formattedDate);
        const todayClass = isToday(date);
        
        // Crear celda del día
        const dayCell = document.createElement('div');
        dayCell.className = `bg-white min-h-24 p-2 cursor-pointer hover:bg-gray-50 ${
            isCurrentMonth ? '' : 'text-gray-400'
        } ${todayClass ? 'bg-blue-50' : ''}`;
        
        // Crear contenido de la celda
        dayCell.innerHTML = `
            <div class="flex justify-between">
                <span class="text-sm font-medium ${
                    todayClass ? 'h-6 w-6 rounded-full bg-blue-600 text-white flex items-center justify-center' : ''
                }">
                    ${date.getDate()}
                </span>
                ${dayReservas.length > 0 ? `
                    <span class="text-xs font-medium bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full">
                        ${dayReservas.length}
                    </span>
                ` : ''}
            </div>
            <div class="mt-1 space-y-1" id="reservas-${formattedDate}"></div>
        `;
        
        // Hacer la celda clickable
        dayCell.addEventListener('click', function() {
            window.location.href = `/day?date=${formattedDate}`;
        });
        
        // Agregar la celda al grid
        calendarGrid.appendChild(dayCell);
        
        // Agregar las reservas a la celda
        const reservasContainer = dayCell.querySelector(`#reservas-${formattedDate}`);
        
        dayReservas.forEach(reserva => {
            const reservaItem = document.createElement('div');
            reservaItem.className = `text-xs p-1 rounded truncate cursor-pointer ${
                reserva.estado === 'confirmada' 
                    ? 'bg-green-100 text-green-800 border-l-2 border-green-500' 
                    : 'bg-amber-100 text-amber-800 border-l-2 border-amber-500'
            }`;
            reservaItem.textContent = `${reserva.hora.substring(0, 5)} - ${reserva.nombre}`;
            
            // Hacer que el item sea clickable y evitar propagación
            reservaItem.addEventListener('click', function(e) {
                e.stopPropagation();
                window.location.href = `/reserva-detail?id=${reserva.id}`;
            });
            
            reservasContainer.appendChild(reservaItem);
        });
    }
    
    // ==================== CALENDARIO MÓVIL ====================
    
    /**
     * Inicializa las vistas móviles
     */
    function initMobileViews() {
        updateMobileMonthStats();
        renderMobileMonthView();
        renderMobileWeekView();
        renderMobileDayView();
    }
    
    /**
     * Cambia entre vistas móviles
     */
    function switchMobileView(view) {
        currentMobileView = view;
        
        // Actualizar botones
        document.querySelectorAll('.mobile-view-btn').forEach(btn => btn.classList.remove('active'));
        const targetBtn = document.getElementById(`mobileView${view.charAt(0).toUpperCase() + view.slice(1)}`);
        if (targetBtn) targetBtn.classList.add('active');
        
        // Mostrar/ocultar vistas
        const monthView = document.getElementById('mobileMonthView');
        const weekView = document.getElementById('mobileWeekView');
        const dayView = document.getElementById('mobileDayView');
        
        if (monthView) monthView.style.display = view === 'month' ? 'block' : 'none';
        if (weekView) weekView.style.display = view === 'week' ? 'block' : 'none';
        if (dayView) dayView.style.display = view === 'day' ? 'block' : 'none';
        
        // Renderizar vista específica
        if (view === 'week') renderMobileWeekView();
        if (view === 'day') renderMobileDayView();
    }
    
    /**
     * Navega en las vistas móviles
     */
    function navigateMobile(direction, unit) {
        switch (unit) {
            case 'month':
                currentMobileDate.setMonth(currentMobileDate.getMonth() + direction);
                updateMobileMonthStats();
                renderMobileMonthView();
                break;
            case 'week':
                if (!currentWeekStart) {
                    currentWeekStart = getWeekStart(currentMobileDate);
                }
                currentWeekStart.setDate(currentWeekStart.getDate() + (direction * 7));
                renderMobileWeekView();
                break;
            case 'day':
                currentMobileDate.setDate(currentMobileDate.getDate() + direction);
                renderMobileDayView();
                break;
        }
    }
    
    /**
     * Actualiza las estadísticas del mes móvil
     */
    function updateMobileMonthStats() {
        const mobileMonthDisplay = document.getElementById('mobileMonthDisplay');
        const confirmadasCount = document.getElementById('mobileConfirmadasCount');
        const pendientesCount = document.getElementById('mobilePendientesCount');
        
        if (mobileMonthDisplay) {
            mobileMonthDisplay.textContent = 
                `${monthNames[currentMobileDate.getMonth()]} ${currentMobileDate.getFullYear()}`;
        }
        
        // Calcular estadísticas del mes
        const monthStart = new Date(currentMobileDate.getFullYear(), currentMobileDate.getMonth(), 1);
        const monthEnd = new Date(currentMobileDate.getFullYear(), currentMobileDate.getMonth() + 1, 0);
        
        let confirmadas = 0, pendientes = 0;
        
        window.reservas.forEach(reserva => {
            const fechaReserva = new Date(reserva.fecha);
            if (fechaReserva >= monthStart && fechaReserva <= monthEnd) {
                if (reserva.estado === 'confirmada') confirmadas++;
                if (reserva.estado === 'pendiente') pendientes++;
            }
        });
        
        if (confirmadasCount) confirmadasCount.textContent = confirmadas;
        if (pendientesCount) pendientesCount.textContent = pendientes;
    }
    
    /**
     * Renderiza la vista de mes móvil
     */
    function renderMobileMonthView() {
        const calendarBody = document.getElementById('mobileCalendarBody');
        if (!calendarBody) return;
        
        calendarBody.innerHTML = '';
        
        const firstDay = new Date(currentMobileDate.getFullYear(), currentMobileDate.getMonth(), 1);
        const lastDay = new Date(currentMobileDate.getFullYear(), currentMobileDate.getMonth() + 1, 0);
        
        let firstDayOfWeek = firstDay.getDay() - 1;
        if (firstDayOfWeek < 0) firstDayOfWeek = 6;
        
        // Días del mes anterior
        for (let i = 0; i < firstDayOfWeek; i++) {
            const prevMonthDay = new Date(currentMobileDate.getFullYear(), currentMobileDate.getMonth(), -firstDayOfWeek + i + 1);
            addMobileDayCell(calendarBody, prevMonthDay, false);
        }
        
        // Días del mes actual
        for (let i = 1; i <= lastDay.getDate(); i++) {
            const currentMonthDay = new Date(currentMobileDate.getFullYear(), currentMobileDate.getMonth(), i);
            addMobileDayCell(calendarBody, currentMonthDay, true);
        }
        
        // Completar semanas con días del mes siguiente
        const totalCells = firstDayOfWeek + lastDay.getDate();
        const cellsNeeded = Math.ceil(totalCells / 7) * 7;
        const nextMonthDays = cellsNeeded - totalCells;
        
        for (let i = 1; i <= nextMonthDays; i++) {
            const nextMonthDay = new Date(currentMobileDate.getFullYear(), currentMobileDate.getMonth() + 1, i);
            addMobileDayCell(calendarBody, nextMonthDay, false);
        }
    }
    
    /**
     * Agrega una celda de día al calendario móvil
     */
    function addMobileDayCell(container, date, isCurrentMonth) {
        const formattedDate = formatDate(date);
        const dayReservas = getReservasByFecha(formattedDate);
        const todayClass = isToday(date);
        
        const dayCell = document.createElement('div');
        dayCell.className = `mobile-day-cell ${!isCurrentMonth ? 'other-month' : ''} ${todayClass ? 'today' : ''}`;
        dayCell.onclick = () => {
            currentMobileDate = new Date(date);
            switchMobileView('day');
        };
        
        // Número del día
        const dayNumber = document.createElement('div');
        dayNumber.className = 'mobile-day-number';
        dayNumber.textContent = date.getDate();
        dayCell.appendChild(dayNumber);
        
        // Indicadores y badge de total
        if (dayReservas.length > 0) {
            // Badge con número total
            const totalBadge = document.createElement('div');
            totalBadge.className = 'mobile-total-badge';
            totalBadge.textContent = dayReservas.length;
            dayCell.appendChild(totalBadge);
            
            // Indicadores por tipo (máximo 3 puntos)
            const indicators = document.createElement('div');
            indicators.className = 'mobile-day-indicators';
            
            const confirmadas = dayReservas.filter(r => r.estado === 'confirmada').length;
            const pendientes = dayReservas.filter(r => r.estado === 'pendiente').length;
            
            // Mostrar hasta 3 indicadores
            const maxIndicators = 3;
            let indicatorCount = 0;
            
            for (let i = 0; i < Math.min(confirmadas, maxIndicators - indicatorCount); i++) {
                const indicator = document.createElement('div');
                indicator.className = 'mobile-indicator confirmada';
                indicators.appendChild(indicator);
                indicatorCount++;
            }
            
            for (let i = 0; i < Math.min(pendientes, maxIndicators - indicatorCount); i++) {
                const indicator = document.createElement('div');
                indicator.className = 'mobile-indicator pendiente';
                indicators.appendChild(indicator);
                indicatorCount++;
            }
            
            dayCell.appendChild(indicators);
        }
        
        container.appendChild(dayCell);
    }
    
    /**
     * Renderiza la vista de semana móvil
     */
    function renderMobileWeekView() {
        if (!currentWeekStart) {
            currentWeekStart = getWeekStart(currentMobileDate);
        }
        
        const weekDays = document.getElementById('mobileWeekDays');
        const weekTitle = document.getElementById('mobileWeekTitle');
        
        if (!weekDays) return;
        
        weekDays.innerHTML = '';
        
        const weekEnd = new Date(currentWeekStart);
        weekEnd.setDate(weekEnd.getDate() + 6);
        
        // Actualizar título de la semana
        if (weekTitle) {
            const formatOptions = { day: 'numeric', month: 'short' };
            const startStr = currentWeekStart.toLocaleDateString('es-ES', formatOptions);
            const endStr = weekEnd.toLocaleDateString('es-ES', formatOptions);
            weekTitle.textContent = `${startStr} - ${endStr}`;
        }
        
        const today = new Date();
        
        for (let i = 0; i < 7; i++) {
            const currentDay = new Date(currentWeekStart);
            currentDay.setDate(currentDay.getDate() + i);
            
            const formattedDate = formatDate(currentDay);
            const dayReservas = getReservasByFecha(formattedDate);
            const todayClass = isToday(currentDay);
            
            const dayElement = document.createElement('div');
            dayElement.className = `mobile-week-day ${todayClass ? 'today' : ''}`;
            dayElement.onclick = () => {
                currentMobileDate = new Date(currentDay);
                switchMobileView('day');
            };
            
            // Nombre del día
            const dayName = document.createElement('div');
            dayName.className = 'mobile-week-day-name';
            dayName.textContent = dayNamesShort[currentDay.getDay()];
            dayElement.appendChild(dayName);
            
            // Número del día
            const dayNumber = document.createElement('div');
            dayNumber.className = 'mobile-week-day-number';
            dayNumber.textContent = currentDay.getDate();
            dayElement.appendChild(dayNumber);
            
            // Estadísticas del día
            if (dayReservas.length > 0) {
                const stats = document.createElement('div');
                stats.className = 'mobile-week-stats';
                
                const confirmadas = dayReservas.filter(r => r.estado === 'confirmada').length;
                const pendientes = dayReservas.filter(r => r.estado === 'pendiente').length;
                
                if (confirmadas > 0) {
                    const confirmadasStat = document.createElement('div');
                    confirmadasStat.className = 'mobile-week-stat confirmada';
                    confirmadasStat.textContent = `${confirmadas}C`;
                    stats.appendChild(confirmadasStat);
                }
                
                if (pendientes > 0) {
                    const pendientesStat = document.createElement('div');
                    pendientesStat.className = 'mobile-week-stat pendiente';
                    pendientesStat.textContent = `${pendientes}P`;
                    stats.appendChild(pendientesStat);
                }
                
                dayElement.appendChild(stats);
            }
            
            weekDays.appendChild(dayElement);
        }
    }
    
    /**
     * Renderiza la vista de día móvil
     */
    function renderMobileDayView() {
        const formattedDate = formatDate(currentMobileDate);
        const dayReservas = getReservasByFecha(formattedDate);
        
        const dayDate = document.getElementById('mobileDayDate');
        const dayName = document.getElementById('mobileDayName');
        const reservationsList = document.getElementById('mobileReservationsList');
        
        // Actualizar header del día
        if (dayDate) dayDate.textContent = currentMobileDate.getDate();
        
        if (dayName) {
            dayName.textContent = 
                `${dayNames[currentMobileDate.getDay()]}, ${monthNames[currentMobileDate.getMonth()]} ${currentMobileDate.getFullYear()}`;
        }
        
        // Renderizar lista de reservas
        if (!reservationsList) return;
        
        reservationsList.innerHTML = '';
        
        if (dayReservas.length === 0) {
            const emptyState = document.createElement('div');
            emptyState.className = 'mobile-empty-state';
            emptyState.innerHTML = `
                <i class="ri-calendar-event-line mobile-empty-icon"></i>
                <h3 class="mobile-empty-title">Sin reservas</h3>
                <p class="mobile-empty-description">No hay reservas programadas para este día</p>
            `;
            reservationsList.appendChild(emptyState);
        } else {
            // Ordenar reservas por hora
            dayReservas.sort((a, b) => a.hora.localeCompare(b.hora));
            
            dayReservas.forEach(reserva => {
                const reservationItem = document.createElement('div');
                reservationItem.className = `mobile-reservation-item ${reserva.estado}`;
                reservationItem.onclick = () => {
                    window.location.href = `/reserva-detail?id=${reserva.id}`;
                };
                
                reservationItem.innerHTML = `
                    <div class="mobile-reservation-time">${reserva.hora.substring(0, 5)}</div>
                    <div class="mobile-reservation-client">
                        <i class="ri-user-line mr-1"></i>
                        ${reserva.nombre}
                        <br>
                        <i class="ri-phone-line mr-1"></i>
                        ${reserva.telefono}
                    </div>
                    <div class="mobile-reservation-status ${reserva.estado}">
                        <i class="ri-${reserva.estado === 'confirmada' ? 'check' : 'time'}-line"></i>
                        ${reserva.estado === 'confirmada' ? 'Confirmada' : 'Pendiente'}
                    </div>
                `;
                
                reservationsList.appendChild(reservationItem);
            });
        }
    }
    
    // ==================== INICIALIZACIÓN Y EVENT LISTENERS ====================
    
    /**
     * Inicializa el calendario desktop
     */
    function initDesktopCalendar() {
        if (!calendarGrid) return;
        
        // Event listeners para navegación
        if (prevMonthButton) {
            prevMonthButton.addEventListener('click', function() {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderDesktopCalendar(currentDate);
            });
        }
        
        if (nextMonthButton) {
            nextMonthButton.addEventListener('click', function() {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderDesktopCalendar(currentDate);
            });
        }
        
        // Renderizar calendario inicial
        renderDesktopCalendar(currentDate);
    }
    
    /**
     * Inicializa las vistas móviles y event listeners
     */
    function initMobileCalendar() {
        // Event listeners para cambio de vista
        const monthViewBtn = document.getElementById('mobileViewMonth');
        const weekViewBtn = document.getElementById('mobileViewWeek');
        const dayViewBtn = document.getElementById('mobileViewDay');
        
        if (monthViewBtn) monthViewBtn.addEventListener('click', () => switchMobileView('month'));
        if (weekViewBtn) weekViewBtn.addEventListener('click', () => switchMobileView('week'));
        if (dayViewBtn) dayViewBtn.addEventListener('click', () => switchMobileView('day'));
        
        // Event listeners para navegación móvil
        const mobilePrevMonth = document.getElementById('mobilePrevMonth');
        const mobileNextMonth = document.getElementById('mobileNextMonth');
        const mobilePrevWeek = document.getElementById('mobilePrevWeek');
        const mobileNextWeek = document.getElementById('mobileNextWeek');
        const mobilePrevDay = document.getElementById('mobilePrevDay');
        const mobileNextDay = document.getElementById('mobileNextDay');
        
        if (mobilePrevMonth) mobilePrevMonth.addEventListener('click', () => navigateMobile(-1, 'month'));
        if (mobileNextMonth) mobileNextMonth.addEventListener('click', () => navigateMobile(1, 'month'));
        if (mobilePrevWeek) mobilePrevWeek.addEventListener('click', () => navigateMobile(-1, 'week'));
        if (mobileNextWeek) mobileNextWeek.addEventListener('click', () => navigateMobile(1, 'week'));
        if (mobilePrevDay) mobilePrevDay.addEventListener('click', () => navigateMobile(-1, 'day'));
        if (mobileNextDay) mobileNextDay.addEventListener('click', () => navigateMobile(1, 'day'));
        
        // Inicializar vistas móviles
        initMobileViews();
    }
    
    // ==================== INICIALIZACIÓN PRINCIPAL ====================
    
    // Inicializar calendarios
    initDesktopCalendar();
    initMobileCalendar();
    
    console.log('Calendario inicializado con', window.reservas.length, 'reservas');
});