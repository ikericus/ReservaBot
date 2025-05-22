document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const calendarGrid = document.getElementById('calendarGrid');
    const currentMonthDisplay = document.getElementById('currentMonthDisplay');
    const prevMonthButton = document.getElementById('prevMonth');
    const nextMonthButton = document.getElementById('nextMonth');
    
    // Fecha actual (simulamos que estamos en Mayo 2025 para los ejemplos)
    let currentDate = new Date(2025, 4, 1);
    
    // Actualizar con la fecha actual real
    if (!window.location.href.includes('demo')) {
        currentDate = new Date();
    }
    
    // Nombres de los meses
    const monthNames = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    
    // Función para formatear la fecha como YYYY-MM-DD
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // Función para obtener reservas por fecha
    function getReservasByFecha(fecha) {
        return reservas.filter(reserva => reserva.fecha === fecha);
    }
    
    // Función para renderizar el calendario
    function renderCalendar(date) {
        // Actualizar el mes mostrado
        currentMonthDisplay.textContent = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
        
        // Obtener el primer día del mes
        const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
        
        // Obtener el último día del mes
        const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
        
        // Ajustar el día de la semana (0 = Domingo, 1 = Lunes, ..., 6 = Sábado)
        // Convertimos a formato donde Lunes es 0, Domingo es 6
        let firstDayOfWeek = firstDay.getDay() - 1;
        if (firstDayOfWeek < 0) firstDayOfWeek = 6; // Si es domingo (0), convertirlo a 6
        
        // Limpiar el calendario
        calendarGrid.innerHTML = '';
        
        // Agregar días del mes anterior
        for (let i = 0; i < firstDayOfWeek; i++) {
            const prevMonthDay = new Date(date.getFullYear(), date.getMonth(), -firstDayOfWeek + i + 1);
            addDayToCalendar(prevMonthDay, false);
        }
        
        // Agregar días del mes actual
        for (let i = 1; i <= lastDay.getDate(); i++) {
            const currentMonthDay = new Date(date.getFullYear(), date.getMonth(), i);
            addDayToCalendar(currentMonthDay, true);
        }
        
        // Calcular cuántos días del siguiente mes necesitamos
        const totalCellsSoFar = firstDayOfWeek + lastDay.getDate();
        const cellsNeeded = Math.ceil(totalCellsSoFar / 7) * 7;
        const nextMonthDays = cellsNeeded - totalCellsSoFar;
        
        // Agregar días del mes siguiente
        for (let i = 1; i <= nextMonthDays; i++) {
            const nextMonthDay = new Date(date.getFullYear(), date.getMonth() + 1, i);
            addDayToCalendar(nextMonthDay, false);
        }
    }
    
    // Función para agregar un día al calendario
    function addDayToCalendar(date, isCurrentMonth) {
        const formattedDate = formatDate(date);
        const dayReservas = getReservasByFecha(formattedDate);
        
        // Verificar si es hoy
        const today = new Date();
        const isToday = date.getDate() === today.getDate() && 
                         date.getMonth() === today.getMonth() && 
                         date.getFullYear() === today.getFullYear();
        
        // Crear celda del día
        const dayCell = document.createElement('div');
        dayCell.className = `bg-white min-h-24 p-2 ${isCurrentMonth ? '' : 'text-gray-400'} ${isToday ? 'bg-blue-50' : ''}`;
        
        // Crear contenido de la celda
        dayCell.innerHTML = `
            <div class="flex justify-between">
                <span class="text-sm font-medium ${isToday ? 'h-6 w-6 rounded-full bg-blue-600 text-white flex items-center justify-center' : ''}">
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
            window.location.href = `day.php?date=${formattedDate}`;
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
            
            // Hacer que el item sea clickable y evitar que el click se propague al día
            reservaItem.addEventListener('click', function(e) {
                e.stopPropagation();
                window.location.href = `reserva-detail.php?id=${reserva.id}`;
            });
            
            reservasContainer.appendChild(reservaItem);
        });
    }
    
    // Navegar al mes anterior
    prevMonthButton.addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
    });
    
    // Navegar al mes siguiente
    nextMonthButton.addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
    });
    
    // Renderizar el calendario inicialmente
    renderCalendar(currentDate);
});