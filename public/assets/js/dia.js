document.addEventListener('DOMContentLoaded', function() {
    // Verificar que tengamos los datos
    if (typeof window.reservasData === 'undefined' || typeof window.fechaData === 'undefined') {
        console.error('No se encontraron datos necesarios');
        return;
    }
    
    // Variables globales
    const reservas = window.reservasData;
    const fechaInfo = window.fechaData;
    const fechaObj = new Date(fechaInfo.fecha + 'T00:00:00');
    
    // Nombres de días y meses
    const dayNames = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    const monthNames = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
    ];
    
    // Slots fijos de 1 hora (de 8:00 a 22:00)
    const horasDelDia = [
        '08:00', '09:00', '10:00', '11:00', '12:00', '13:00',
        '14:00', '15:00', '16:00', '17:00', '18:00', '19:00',
        '20:00', '21:00', '22:00'
    ];
    
    // Referencias a elementos del DOM
    const dayDateElement = document.getElementById('dayDate');
    const mobileDayDateElement = document.getElementById('mobileDayDate');
    const reservationsList = document.getElementById('reservationsList');
    const mobileReservationsList = document.getElementById('mobileReservationsList');
    
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
     * Formatea la fecha para mostrar
     */
    function formatDateDisplay() {
        const dayName = dayNames[fechaObj.getDay()];
        const day = fechaObj.getDate();
        const monthName = monthNames[fechaObj.getMonth()];
        const year = fechaObj.getFullYear();
        
        let displayText = `${capitalize(dayName)}, ${day} de ${monthName} de ${year}`;
        
        // Agregar indicador si es hoy
        if (isToday(fechaObj)) {
            displayText += ' (Hoy)';
        }
        
        return displayText;
    }
    
    /**
     * Agrupa las reservas por franja horaria (por hora, no por minuto exacto)
     * Ejemplo: 14:10, 14:30, 14:55 → todas van al slot "14:00"
     */
    function groupReservationsByHourSlot() {
        const reservasPorSlot = {};
        
        // Inicializar todos los slots vacíos
        horasDelDia.forEach(slot => {
            reservasPorSlot[slot] = [];
        });
        
        // Agrupar reservas por slot horario
        reservas.forEach(reserva => {
            const horaReserva = reserva.hora.substring(0, 5); // HH:MM
            const [hora, minuto] = horaReserva.split(':');
            const horaNum = parseInt(hora);
            
            // Determinar a qué slot pertenece esta reserva
            const slotCorrespondiente = `${hora.padStart(2, '0')}:00`;
            
            // Solo agregar si el slot existe en nuestro rango (8:00-22:00)
            if (reservasPorSlot.hasOwnProperty(slotCorrespondiente)) {
                reservasPorSlot[slotCorrespondiente].push(reserva);
            }
        });
        
        // Ordenar las reservas dentro de cada slot cronológicamente
        Object.keys(reservasPorSlot).forEach(slot => {
            reservasPorSlot[slot].sort((a, b) => {
                return a.hora.localeCompare(b.hora);
            });
        });
        
        console.log('Reservas agrupadas por slots:', reservasPorSlot);
        return reservasPorSlot;
    }
    
    /**
     * Inicializa la vista del día
     */
    function initDayView() {
        // Actualizar la fecha en el header
        updateDateDisplay();
        
        // Renderizar el timeline de reservas
        renderTimelineReservations();
        
        // Renderizar vista móvil
        renderMobileTimelineReservations();
        
        console.log('Vista de día inicializada para:', fechaInfo.fecha);
    }
    
    /**
     * Actualiza la fecha en el header
     */
    function updateDateDisplay() {
        const dateText = formatDateDisplay();
        
        if (dayDateElement) {
            dayDateElement.textContent = dateText;
        }
        
        if (mobileDayDateElement) {
            mobileDayDateElement.textContent = dateText;
        }
    }
        
    /**
     * Renderiza el timeline de reservas para desktop
     */
    function renderTimelineReservations() {
        if (!reservationsList) return;
        
        const timelineContainer = reservationsList.querySelector('.timeline-container');
        if (!timelineContainer) return;
        
        // Limpiar contenido anterior (mantener la línea del timeline)
        const timelineLine = timelineContainer.querySelector('.timeline-line');
        timelineContainer.innerHTML = '';
        if (timelineLine) {
            timelineContainer.appendChild(timelineLine);
        }
        
        const reservasPorSlot = groupReservationsByHourSlot();
        
        // Renderizar cada slot de hora
        horasDelDia.forEach(slot => {
            const timelineHour = document.createElement('div');
            timelineHour.className = 'timeline-hour';
            
            const hourLabel = document.createElement('div');
            hourLabel.className = 'hour-label';
            hourLabel.textContent = formatHourDisplay(slot);
            
            const hourContent = document.createElement('div');
            hourContent.className = 'hour-content';
            
            // Obtener reservas de este slot
            const reservasEnSlot = reservasPorSlot[slot] || [];
            
            if (reservasEnSlot.length > 0) {
                hourContent.classList.add('has-reservation');
                
                // Renderizar cada reserva de este slot, ya ordenadas cronológicamente
                reservasEnSlot.forEach(reserva => {
                    const reservationElement = createReservationElement(reserva);
                    hourContent.appendChild(reservationElement);
                });
            } else {
                // Agregar botón de crear reserva para horas vacías
                const addBtn = document.createElement('a');
                addBtn.className = 'hour-add-btn';
                addBtn.href = `/reserva-form?fecha=${fechaInfo.fecha}&hora=${slot}`;
                addBtn.innerHTML = '<i class="ri-add-line"></i>Nueva reserva';
                hourContent.appendChild(addBtn);
            }
            
            timelineHour.appendChild(hourLabel);
            timelineHour.appendChild(hourContent);
            timelineContainer.appendChild(timelineHour);
        });
    }

    /**
     * Renderiza el timeline de reservas para móvil
     */
    function renderMobileTimelineReservations() {
        if (!mobileReservationsList) return;
        
        const mobileTimelineContainer = mobileReservationsList.querySelector('.mobile-timeline-container');
        if (!mobileTimelineContainer) return;
        
        // Limpiar contenido anterior (mantener la línea del timeline)
        const timelineLine = mobileTimelineContainer.querySelector('.mobile-timeline-line');
        mobileTimelineContainer.innerHTML = '';
        if (timelineLine) {
            mobileTimelineContainer.appendChild(timelineLine);
        }
        
        const reservasPorSlot = groupReservationsByHourSlot();
        
        // Renderizar cada slot de hora para móvil
        horasDelDia.forEach(slot => {
            const mobileTimelineHour = document.createElement('div');
            mobileTimelineHour.className = 'mobile-timeline-hour';
            
            const mobileHourLabel = document.createElement('div');
            mobileHourLabel.className = 'mobile-hour-label';
            mobileHourLabel.textContent = formatHourDisplay(slot);
            
            const mobileHourContent = document.createElement('div');
            mobileHourContent.className = 'mobile-hour-content';
            
            // Obtener reservas de este slot
            const reservasEnSlot = reservasPorSlot[slot] || [];
            
            if (reservasEnSlot.length > 0) {
                mobileHourContent.classList.add('has-reservation');
                
                // Renderizar cada reserva de este slot, ya ordenadas cronológicamente
                reservasEnSlot.forEach(reserva => {
                    const mobileReservationElement = createMobileReservationElement(reserva);
                    mobileHourContent.appendChild(mobileReservationElement);
                });
            } else {
                // Agregar botón de crear reserva para horas vacías
                const addBtn = document.createElement('a');
                addBtn.className = 'mobile-hour-add-btn';
                addBtn.href = `/reserva-form?fecha=${fechaInfo.fecha}&hora=${slot}`;
                addBtn.innerHTML = '<i class="ri-add-line"></i>Nueva reserva';
                mobileHourContent.appendChild(addBtn);
            }
            
            mobileTimelineHour.appendChild(mobileHourLabel);
            mobileTimelineHour.appendChild(mobileHourContent);
            mobileTimelineContainer.appendChild(mobileTimelineHour);
        });
    }
    
    /**
     * Formatea la hora para mostrar en formato 24h (ej: 08:00 -> 08:00)
     */
    function formatHourDisplay(hora) {
        return hora; // Mantener formato 24h
    }
    
    /**
     * Crea un elemento de reserva para desktop
     */
    function createReservationElement(reserva) {
        const reservationElement = document.createElement('div');
        reservationElement.className = `timeline-reservation ${reserva.estado}`;
        
        // Hacer el elemento clickeable
        reservationElement.addEventListener('click', function() {
            window.location.href = `/reserva?id=${reserva.id}`;
        });
        
        const estadoInfo = getEstadoInfo(reserva.estado);
        const horaFormateada = reserva.hora.substring(0, 5); // Mostrar hora exacta (14:30, 14:55, etc.)
        
        reservationElement.innerHTML = `
            <div class="reservation-header">
                <div class="reservation-time">${horaFormateada}</div>
                <div class="reservation-status ${reserva.estado}">
                    <i class="ri-${estadoInfo.icon}"></i>
                    ${estadoInfo.text}
                </div>
            </div>
            <div class="reservation-client">
                <i class="ri-user-line"></i>
                ${reserva.nombre}
            </div>
            <div class="reservation-phone">
                <i class="ri-phone-line"></i>
                ${reserva.telefono}
            </div>
        `;
        
        return reservationElement;
    }
    
    /**
     * Crea un elemento de reserva para móvil
     */
    function createMobileReservationElement(reserva) {
        const mobileReservationElement = document.createElement('div');
        mobileReservationElement.className = `mobile-timeline-reservation ${reserva.estado}`;
        
        // Hacer el elemento clickeable
        mobileReservationElement.addEventListener('click', function() {
            window.location.href = `/reserva?id=${reserva.id}`;
        });
        
        const horaFormateada = reserva.hora.substring(0, 5); // Mostrar hora exacta
        
        mobileReservationElement.innerHTML = `
            <div class="mobile-reservation-time">${horaFormateada}</div>
            <div class="mobile-reservation-client">
                <i class="ri-user-line"></i>
                ${reserva.nombre}
            </div>
            <div class="mobile-reservation-phone">
                <i class="ri-phone-line"></i>
                ${reserva.telefono}
            </div>
        `;
        
        return mobileReservationElement;
    }
    
    /**
     * Obtiene la información de visualización para un estado
     */
    function getEstadoInfo(estado) {
        switch (estado) {
            case 'confirmada':
                return { icon: 'check-line', text: 'Confirmada' };
            case 'pendiente':
                return { icon: 'time-line', text: 'Pendiente' };
            case 'cancelada':
                return { icon: 'close-line', text: 'Cancelada' };
            default:
                return { icon: 'question-line', text: 'Desconocido' };
        }
    }
    
    /**
     * Agregar funcionalidad para navegación con teclado
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
                    window.location.href = `/dia?date=${fechaInfo.fechaAnterior}`;
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    window.location.href = `/dia?date=${fechaInfo.fechaSiguiente}`;
                    break;
                case 'w':
                case 'W':
                    e.preventDefault();
                    window.location.href = `/semana?date=${fechaInfo.fecha}`;
                    break;
                case 'm':
                case 'M':
                    e.preventDefault();
                    window.location.href = `/mes?date=${fechaInfo.fecha}`;
                    break;
                case 'n':
                case 'N':
                    e.preventDefault();
                    window.location.href = `/nueva-reserva?date=${fechaInfo.fecha}`;
                    break;
            }
        });
    }
    
    /**
     * Configurar tooltips para mejor UX
     */
    function setupTooltips() {
        // Agregar tooltips a los botones de navegación
        const prevBtns = document.querySelectorAll(`a[href*="${fechaInfo.fechaAnterior}"]`);
        const nextBtns = document.querySelectorAll(`a[href*="${fechaInfo.fechaSiguiente}"]`);
        
        prevBtns.forEach(btn => {
            btn.title = 'Día anterior (←)';
        });
        
        nextBtns.forEach(btn => {
            btn.title = 'Día siguiente (→)';
        });
        
        // Agregar tooltips a los enlaces de vista
        const weekLinks = document.querySelectorAll('a[href*="/semana"]');
        const monthLinks = document.querySelectorAll('a[href*="/mes"]');
        
        weekLinks.forEach(link => {
            link.title = 'Vista semana (W)';
        });
        
        monthLinks.forEach(link => {
            link.title = 'Vista mes (M)';
        });
    }
    
    /**
     * Configura efectos visuales adicionales
     */
    function setupVisualEffects() {
        // Agregar animación suave al timeline cuando se carga
        const timelineContainers = document.querySelectorAll('.timeline-container, .mobile-timeline-container');
        
        timelineContainers.forEach(container => {
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            // Animar entrada
            setTimeout(() => {
                container.style.transition = 'all 0.5s ease-out';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
        
        // Hacer scroll a la primera reserva del día
        scrollToFirstReservation();
    }
    
    /**
     * Hace scroll a la primera reserva del día
     */
    function scrollToFirstReservation() {
        if (reservas.length === 0) return;
        
        // Encontrar la primera reserva (ya ordenadas por hora)
        const primeraReserva = reservas[0];
        const horaReserva = primeraReserva.hora.substring(0, 2); // Solo la hora
        const slotCorrespondiente = `${horaReserva}:00`;
        
        // Buscar el elemento de ese slot en desktop
        const hourLabels = document.querySelectorAll('.hour-label');
        hourLabels.forEach(label => {
            if (label.textContent === slotCorrespondiente) {
                const hourContainer = label.parentElement;
                setTimeout(() => {
                    hourContainer.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }, 600);
            }
        });
        
        // Buscar el elemento de ese slot en móvil
        const mobileHourLabels = document.querySelectorAll('.mobile-hour-label');
        mobileHourLabels.forEach(label => {
            if (label.textContent === slotCorrespondiente) {
                const hourContainer = label.parentElement;
                setTimeout(() => {
                    hourContainer.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }, 600);
            }
        });
    }
    
    // Inicializar la vista
    initDayView();
    setupKeyboardNavigation();
    setupTooltips();
    setupVisualEffects();
    
    console.log('Vista de día cargada con', reservas.length, 'reservas');
    console.log('Slots fijos de 1 hora:', horasDelDia.length);
});