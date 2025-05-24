<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';

// Configurar la página actual
$currentPage = 'calendar';
$pageTitle = 'ReservaBot - Calendario';
$pageScript = 'calendar';

// Obtener reservas para el mes actual (el mes se filtrará en JS)
try {
    $stmt = $pdo->query('SELECT * FROM reservas ORDER BY fecha, hora');
    $todasReservas = $stmt->fetchAll();
} catch (\PDOException $e) {
    $todasReservas = [];
}

// Incluir la cabecera
include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Calendario de Reservas</h1>
</div>

<!-- Calendario -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <button id="prevMonth" class="p-2 rounded-full hover:bg-gray-100">
            <i class="ri-arrow-left-s-line text-gray-600 text-xl"></i>
        </button>
        <h2 id="currentMonthDisplay" class="text-lg font-medium text-gray-900">Mayo 2025</h2>
        <button id="nextMonth" class="p-2 rounded-full hover:bg-gray-100">
            <i class="ri-arrow-right-s-line text-gray-600 text-xl"></i>
        </button>
    </div>
    
    <!-- Cabecera del calendario -->
    <div class="grid grid-cols-7 gap-px bg-gray-200 mb-px">
        <div class="bg-gray-100 py-2 text-center text-sm font-medium text-gray-700">Lun</div>
        <div class="bg-gray-100 py-2 text-center text-sm font-medium text-gray-700">Mar</div>
        <div class="bg-gray-100 py-2 text-center text-sm font-medium text-gray-700">Mié</div>
        <div class="bg-gray-100 py-2 text-center text-sm font-medium text-gray-700">Jue</div>
        <div class="bg-gray-100 py-2 text-center text-sm font-medium text-gray-700">Vie</div>
        <div class="bg-gray-100 py-2 text-center text-sm font-medium text-gray-700">Sáb</div>
        <div class="bg-gray-100 py-2 text-center text-sm font-medium text-gray-700">Dom</div>
    </div>
    
    <!-- Cuerpo del calendario -->
    <div id="calendarGrid" class="grid grid-cols-7 gap-px bg-gray-200">
        <!-- Las celdas del calendario se generarán con JavaScript -->
    </div>
    
    <!-- Leyenda -->
    <div class="mt-4 flex space-x-4">
        <div class="flex items-center">
            <span class="w-3 h-3 bg-amber-100 border-l-2 border-amber-500 rounded mr-1"></span>
            <span class="text-xs text-gray-600">Pendiente</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-green-100 border-l-2 border-green-500 rounded mr-1"></span>
            <span class="text-xs text-gray-600">Confirmada</span>
        </div>
    </div>
</div>

<!-- Pasar datos al JavaScript -->
<script>
    // Convertir los datos PHP a JSON para que JavaScript pueda usarlos
    const reservas = <?php echo json_encode($todasReservas); ?>;
</script>

<!-- Modificar el JavaScript del calendario para usar URLs limpias -->
<script>
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
        
        // Hacer la celda clickable - USAR URL LIMPIA
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
            
            // Hacer que el item sea clickable y evitar que el click se propague al día - USAR URL LIMPIA
            reservaItem.addEventListener('click', function(e) {
                e.stopPropagation();
                window.location.href = `/reserva-detail?id=${reserva.id}`;
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
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>