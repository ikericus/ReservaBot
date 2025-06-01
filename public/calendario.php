<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';

// Configurar la página actual
$currentPage = 'calendario';
$pageTitle = 'ReservaBot - Calendario';
$pageScript = 'calendario';

// Obtener reservas para el mes actual (el mes se filtrará en JS)
try {
    $stmt = getPDO()->prepare('SELECT * FROM reservas ORDER BY fecha, hora');
    $todasReservas = $stmt->fetchAll();
} catch (\PDOException $e) {
    $todasReservas = [];
}

// Incluir la cabecera
include 'includes/header.php';
?>

<style>
/* Estilos específicos para móvil - Calendario */
@media (max-width: 768px) {
    .mobile-calendar-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 1rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .mobile-month-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .mobile-nav-btn {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        transition: all 0.2s ease;
    }
    
    .mobile-nav-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.05);
    }
    
    .mobile-month-title {
        font-size: 1.25rem;
        font-weight: 600;
        text-align: center;
    }
    
    .mobile-month-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    
    .mobile-stat-item {
        text-align: center;
        background: rgba(255, 255, 255, 0.15);
        padding: 0.75rem;
        border-radius: 0.75rem;
        backdrop-filter: blur(10px);
    }
    
    .mobile-stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
    }
    
    .mobile-stat-label {
        font-size: 0.75rem;
        opacity: 0.9;
        margin: 0.25rem 0 0 0;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    /* Selector de vista */
    .mobile-view-selector {
        background: white;
        border-radius: 1rem;
        padding: 0.25rem;
        margin-bottom: 1rem;
        display: flex;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .mobile-view-btn {
        flex: 1;
        padding: 0.75rem;
        border-radius: 0.75rem;
        border: none;
        background: transparent;
        color: #6b7280;
        font-weight: 500;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
    }
    
    .mobile-view-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }
    
    /* Vista de mes - Grid simplificado */
    .mobile-calendar-grid {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .mobile-calendar-header-row {
        background: #f8fafc;
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        border-bottom: 1px solid #e5e7eb;
    }
    
    .mobile-day-header {
        padding: 0.75rem 0.25rem;
        text-align: center;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .mobile-calendar-body {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
    }
    
    .mobile-day-cell {
        aspect-ratio: 1;
        border-right: 1px solid #f3f4f6;
        border-bottom: 1px solid #f3f4f6;
        padding: 0.5rem 0.25rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        background: white;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .mobile-day-cell:hover {
        background: rgba(102, 126, 234, 0.05);
    }
    
    .mobile-day-cell.other-month {
        color: #d1d5db;
        background: #fafafa;
    }
    
    .mobile-day-cell.today {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    }
    
    .mobile-day-number {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.25rem;
    }
    
    .mobile-day-cell.today .mobile-day-number {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
    }
    
    .mobile-day-indicators {
        display: flex;
        gap: 0.125rem;
        align-items: center;
    }
    
    .mobile-indicator {
        width: 0.375rem;
        height: 0.375rem;
        border-radius: 50%;
        font-size: 0;
    }
    
    .mobile-indicator.confirmada {
        background: #10b981;
    }
    
    .mobile-indicator.pendiente {
        background: #f59e0b;
    }
    
    .mobile-total-badge {
        position: absolute;
        top: 0.25rem;
        right: 0.25rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-size: 0.625rem;
        font-weight: 600;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Vista de semana */
    .mobile-week-view {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .mobile-week-header {
        background: #f8fafc;
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
        text-align: center;
    }
    
    .mobile-week-title {
        font-size: 1rem;
        font-weight: 600;
        color: #374151;
        margin: 0;
    }
    
    .mobile-week-days {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
    }
    
    .mobile-week-day {
        padding: 1rem 0.5rem;
        border-right: 1px solid #f3f4f6;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .mobile-week-day:hover {
        background: rgba(102, 126, 234, 0.05);
    }
    
    .mobile-week-day.today {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    }
    
    .mobile-week-day-number {
        font-size: 1.125rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }
    
    .mobile-week-day.today .mobile-week-day-number {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem auto;
    }
    
    .mobile-week-day-name {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    
    .mobile-week-stats {
        display: flex;
        gap: 0.25rem;
        justify-content: center;
        align-items: center;
    }
    
    .mobile-week-stat {
        background: #f3f4f6;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.625rem;
        font-weight: 500;
    }
    
    .mobile-week-stat.confirmada {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }
    
    .mobile-week-stat.pendiente {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }
    
    /* Vista de lista diaria */
    .mobile-day-view {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .mobile-day-header-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.25rem;
        text-align: center;
    }
    
    .mobile-day-date {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 0.25rem 0;
    }
    
    .mobile-day-name {
        font-size: 0.875rem;
        opacity: 0.9;
        margin: 0;
    }
    
    .mobile-reservations-list {
        padding: 1rem;
    }
    
    .mobile-reservation-item {
        background: #f8fafc;
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 0.75rem;
        border-left: 4px solid;
        transition: all 0.2s ease;
    }
    
    .mobile-reservation-item.confirmada {
        border-left-color: #10b981;
    }
    
    .mobile-reservation-item.pendiente {
        border-left-color: #f59e0b;
    }
    
    .mobile-reservation-item:hover {
        background: #f1f5f9;
        transform: translateX(2px);
    }
    
    .mobile-reservation-time {
        font-size: 1.125rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.25rem;
    }
    
    .mobile-reservation-client {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 0.5rem;
    }
    
    .mobile-reservation-status {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .mobile-reservation-status.confirmada {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }
    
    .mobile-reservation-status.pendiente {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }
    
    /* Navegación de día */
    .mobile-day-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 1.25rem 1.25rem 1.25rem;
    }
    
    .mobile-day-nav-btn {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: none;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        transition: all 0.2s ease;
    }
    
    .mobile-day-nav-btn:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    /* Estados vacíos */
    .mobile-empty-state {
        text-align: center;
        padding: 2rem 1rem;
        color: #6b7280;
    }
    
    .mobile-empty-icon {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }
    
    .mobile-empty-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }
    
    .mobile-empty-description {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    /* Animaciones */
    .fade-in-mobile {
        animation: fadeInMobile 0.3s ease-out;
    }
    
    @keyframes fadeInMobile {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
}

/* Estilos para desktop - mantener diseño original */
@media (min-width: 769px) {
    .desktop-view {
        display: block;
    }
    
    .mobile-view {
        display: none;
    }
}

/* Estilos para móvil - usar diseño optimizado */
@media (max-width: 768px) {
    .desktop-view {
        display: none;
    }
    
    .mobile-view {
        display: block;
    }
}
</style>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Calendario de Reservas</h1>
</div>

<!-- Vista Desktop - Calendario Original -->
<div class="desktop-view bg-white rounded-lg shadow-sm p-6">
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

<!-- Vista Mobile - Calendario Optimizado -->
<div class="mobile-view">
    <!-- Header con estadísticas del mes -->
    <div class="mobile-calendar-header">
        <div class="mobile-month-nav">
            <button id="mobilePrevMonth" class="mobile-nav-btn">
                <i class="ri-arrow-left-s-line"></i>
            </button>
            <h2 id="mobileMonthDisplay" class="mobile-month-title">Mayo 2025</h2>
            <button id="mobileNextMonth" class="mobile-nav-btn">
                <i class="ri-arrow-right-s-line"></i>
            </button>
        </div>
        
        <div class="mobile-month-stats">
            <div class="mobile-stat-item">
                <div class="mobile-stat-number" id="mobileConfirmadasCount">0</div>
                <div class="mobile-stat-label">Confirmadas</div>
            </div>
            <div class="mobile-stat-item">
                <div class="mobile-stat-number" id="mobilePendientesCount">0</div>
                <div class="mobile-stat-label">Pendientes</div>
            </div>
        </div>
    </div>
    
    <!-- Selector de vista -->
    <div class="mobile-view-selector">
        <button id="mobileViewMonth" class="mobile-view-btn active">
            <i class="ri-calendar-2-line"></i>
            Mes
        </button>
        <button id="mobileViewWeek" class="mobile-view-btn">
            <i class="ri-calendar-line"></i>
            Semana
        </button>
        <button id="mobileViewDay" class="mobile-view-btn">
            <i class="ri-calendar-event-line"></i>
            Día
        </button>
    </div>
    
    <!-- Vista de Mes -->
    <div id="mobileMonthView" class="mobile-calendar-grid fade-in-mobile">
        <div class="mobile-calendar-header-row">
            <div class="mobile-day-header">L</div>
            <div class="mobile-day-header">M</div>
            <div class="mobile-day-header">X</div>
            <div class="mobile-day-header">J</div>
            <div class="mobile-day-header">V</div>
            <div class="mobile-day-header">S</div>
            <div class="mobile-day-header">D</div>
        </div>
        <div id="mobileCalendarBody" class="mobile-calendar-body">
            <!-- Las celdas se generarán con JavaScript -->
        </div>
    </div>
    
    <!-- Vista de Semana -->
    <div id="mobileWeekView" class="mobile-week-view fade-in-mobile" style="display: none;">
        <div class="mobile-week-header">
            <div class="mobile-month-nav">
                <button id="mobilePrevWeek" class="mobile-nav-btn" style="background: rgba(102, 126, 234, 0.2); color: #667eea;">
                    <i class="ri-arrow-left-s-line"></i>
                </button>
                <h3 id="mobileWeekTitle" class="mobile-week-title">Semana del 1 - 7 Mayo</h3>
                <button id="mobileNextWeek" class="mobile-nav-btn" style="background: rgba(102, 126, 234, 0.2); color: #667eea;">
                    <i class="ri-arrow-right-s-line"></i>
                </button>
            </div>
        </div>
        <div id="mobileWeekDays" class="mobile-week-days">
            <!-- Los días de la semana se generarán con JavaScript -->
        </div>
    </div>
    
    <!-- Vista de Día -->
    <div id="mobileDayView" class="mobile-day-view fade-in-mobile" style="display: none;">
        <div class="mobile-day-header-section">
            <div class="mobile-day-nav">
                <button id="mobilePrevDay" class="mobile-day-nav-btn">
                    <i class="ri-arrow-left-s-line"></i>
                    Anterior
                </button>
                <button id="mobileNextDay" class="mobile-day-nav-btn">
                    Siguiente
                    <i class="ri-arrow-right-s-line"></i>
                </button>
            </div>
            <h3 id="mobileDayDate" class="mobile-day-date">25</h3>
            <p id="mobileDayName" class="mobile-day-name">Domingo, Mayo 2025</p>
        </div>
        
        <div id="mobileReservationsList" class="mobile-reservations-list">
            <!-- Las reservas del día se mostrarán aquí -->
        </div>
    </div>
</div>

<!-- Pasar datos al JavaScript -->
<script>
    // Convertir los datos PHP a JSON para que JavaScript pueda usarlos
    const reservas = <?php echo json_encode($todasReservas); ?>;
    
    // Variables globales para las vistas móviles
    let currentMobileDate = new Date();
    let currentMobileView = 'month';
    let currentWeekStart = null;
    
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar vistas móviles
        initMobileViews();
        
        // Event listeners para cambio de vista
        document.getElementById('mobileViewMonth').addEventListener('click', () => switchMobileView('month'));
        document.getElementById('mobileViewWeek').addEventListener('click', () => switchMobileView('week'));
        document.getElementById('mobileViewDay').addEventListener('click', () => switchMobileView('day'));
        
        // Event listeners para navegación móvil
        document.getElementById('mobilePrevMonth').addEventListener('click', () => navigateMobile(-1, 'month'));
        document.getElementById('mobileNextMonth').addEventListener('click', () => navigateMobile(1, 'month'));
        document.getElementById('mobilePrevWeek').addEventListener('click', () => navigateMobile(-1, 'week'));
        document.getElementById('mobileNextWeek').addEventListener('click', () => navigateMobile(1, 'week'));
        document.getElementById('mobilePrevDay').addEventListener('click', () => navigateMobile(-1, 'day'));
        document.getElementById('mobileNextDay').addEventListener('click', () => navigateMobile(1, 'day'));
        
        // Desktop calendar (código original)
        initDesktopCalendar();
    });
    
    function initMobileViews() {
        updateMobileMonthStats();
        renderMobileMonthView();
        renderMobileWeekView();
        renderMobileDayView();
    }
    
    function switchMobileView(view) {
        currentMobileView = view;
        
        // Actualizar botones
        document.querySelectorAll('.mobile-view-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`mobileView${view.charAt(0).toUpperCase() + view.slice(1)}`).classList.add('active');
        
        // Mostrar/ocultar vistas
        document.getElementById('mobileMonthView').style.display = view === 'month' ? 'block' : 'none';
        document.getElementById('mobileWeekView').style.display = view === 'week' ? 'block' : 'none';
        document.getElementById('mobileDayView').style.display = view === 'day' ? 'block' : 'none';
        
        // Renderizar vista específica
        if (view === 'week') renderMobileWeekView();
        if (view === 'day') renderMobileDayView();
    }
    
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
    
    function updateMobileMonthStats() {
        const monthNames = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        
        document.getElementById('mobileMonthDisplay').textContent = 
            `${monthNames[currentMobileDate.getMonth()]} ${currentMobileDate.getFullYear()}`;
        
        // Calcular estadísticas del mes
        const monthStart = new Date(currentMobileDate.getFullYear(), currentMobileDate.getMonth(), 1);
        const monthEnd = new Date(currentMobileDate.getFullYear(), currentMobileDate.getMonth() + 1, 0);
        
        let confirmadas = 0, pendientes = 0;
        
        reservas.forEach(reserva => {
            const fechaReserva = new Date(reserva.fecha);
            if (fechaReserva >= monthStart && fechaReserva <= monthEnd) {
                if (reserva.estado === 'confirmada') confirmadas++;
                if (reserva.estado === 'pendiente') pendientes++;
            }
        });
        
        document.getElementById('mobileConfirmadasCount').textContent = confirmadas;
        document.getElementById('mobilePendientesCount').textContent = pendientes;
    }
    
    function renderMobileMonthView() {
        const calendarBody = document.getElementById('mobileCalendarBody');
        calendarBody.innerHTML = '';
        
        const firstDay = new Date(currentMobileDate.getFullYear(), currentMobileDate.getMonth(), 1);
        const lastDay = new Date(currentMobileDate.getFullYear(), currentMobileDate.getMonth() + 1, 0);
        
        let firstDayOfWeek = firstDay.getDay() - 1;
        if (firstDayOfWeek < 0) firstDayOfWeek = 6;
        
        const today = new Date();
        
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
    
    function addMobileDayCell(container, date, isCurrentMonth) {
        const formattedDate = formatDate(date);
        const dayReservas = getReservasByFecha(formattedDate);
        
        const today = new Date();
        const isToday = date.getDate() === today.getDate() && 
                         date.getMonth() === today.getMonth() && 
                         date.getFullYear() === today.getFullYear();
        
        const dayCell = document.createElement('div');
        dayCell.className = `mobile-day-cell ${!isCurrentMonth ? 'other-month' : ''} ${isToday ? 'today' : ''}`;
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
    
    function renderMobileWeekView() {
        if (!currentWeekStart) {
            currentWeekStart = getWeekStart(currentMobileDate);
        }
        
        const weekDays = document.getElementById('mobileWeekDays');
        weekDays.innerHTML = '';
        
        const weekEnd = new Date(currentWeekStart);
        weekEnd.setDate(weekEnd.getDate() + 6);
        
        // Actualizar título de la semana
        const formatOptions = { day: 'numeric', month: 'short' };
        const startStr = currentWeekStart.toLocaleDateString('es-ES', formatOptions);
        const endStr = weekEnd.toLocaleDateString('es-ES', formatOptions);
        document.getElementById('mobileWeekTitle').textContent = `${startStr} - ${endStr}`;
        
        const today = new Date();
        const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        
        for (let i = 0; i < 7; i++) {
            const currentDay = new Date(currentWeekStart);
            currentDay.setDate(currentDay.getDate() + i);
            
            const formattedDate = formatDate(currentDay);
            const dayReservas = getReservasByFecha(formattedDate);
            
            const isToday = currentDay.getDate() === today.getDate() && 
                           currentDay.getMonth() === today.getMonth() && 
                           currentDay.getFullYear() === today.getFullYear();
            
            const dayElement = document.createElement('div');
            dayElement.className = `mobile-week-day ${isToday ? 'today' : ''}`;
            dayElement.onclick = () => {
                currentMobileDate = new Date(currentDay);
                switchMobileView('day');
            };
            
            // Nombre del día
            const dayName = document.createElement('div');
            dayName.className = 'mobile-week-day-name';
            dayName.textContent = dayNames[currentDay.getDay()];
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
    
    function renderMobileDayView() {
        const formattedDate = formatDate(currentMobileDate);
        const dayReservas = getReservasByFecha(formattedDate);
        
        // Actualizar header del día
        document.getElementById('mobileDayDate').textContent = currentMobileDate.getDate();
        
        const dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        
        document.getElementById('mobileDayName').textContent = 
            `${dayNames[currentMobileDate.getDay()]}, ${monthNames[currentMobileDate.getMonth()]} ${currentMobileDate.getFullYear()}`;
        
        // Renderizar lista de reservas
        const reservationsList = document.getElementById('mobileReservationsList');
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
    
    function getWeekStart(date) {
        const weekStart = new Date(date);
        const day = weekStart.getDay();
        const diff = weekStart.getDate() - day + (day === 0 ? -6 : 1); // Lunes como primer día
        weekStart.setDate(diff);
        return weekStart;
    }
    
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    function getReservasByFecha(fecha) {
        return reservas.filter(reserva => reserva.fecha === fecha);
    }
    
    // Desktop Calendar Functions (código original adaptado)
    function initDesktopCalendar() {
        const calendarGrid = document.getElementById('calendarGrid');
        const currentMonthDisplay = document.getElementById('currentMonthDisplay');
        const prevMonthButton = document.getElementById('prevMonth');
        const nextMonthButton = document.getElementById('nextMonth');
        
        if (!calendarGrid) return; // Solo ejecutar en desktop
        
        let currentDate = new Date(2025, 4, 1);
        
        if (!window.location.href.includes('demo')) {
            currentDate = new Date();
        }
        
        const monthNames = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        
        function renderDesktopCalendar(date) {
            currentMonthDisplay.textContent = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
            
            const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
            const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
            
            let firstDayOfWeek = firstDay.getDay() - 1;
            if (firstDayOfWeek < 0) firstDayOfWeek = 6;
            
            calendarGrid.innerHTML = '';
            
            for (let i = 0; i < firstDayOfWeek; i++) {
                const prevMonthDay = new Date(date.getFullYear(), date.getMonth(), -firstDayOfWeek + i + 1);
                addDesktopDayToCalendar(prevMonthDay, false);
            }
            
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const currentMonthDay = new Date(date.getFullYear(), date.getMonth(), i);
                addDesktopDayToCalendar(currentMonthDay, true);
            }
            
            const totalCellsSoFar = firstDayOfWeek + lastDay.getDate();
            const cellsNeeded = Math.ceil(totalCellsSoFar / 7) * 7;
            const nextMonthDays = cellsNeeded - totalCellsSoFar;
            
            for (let i = 1; i <= nextMonthDays; i++) {
                const nextMonthDay = new Date(date.getFullYear(), date.getMonth() + 1, i);
                addDesktopDayToCalendar(nextMonthDay, false);
            }
        }
        
        function addDesktopDayToCalendar(date, isCurrentMonth) {
            const formattedDate = formatDate(date);
            const dayReservas = getReservasByFecha(formattedDate);
            
            const today = new Date();
            const isToday = date.getDate() === today.getDate() && 
                             date.getMonth() === today.getMonth() && 
                             date.getFullYear() === today.getFullYear();
            
            const dayCell = document.createElement('div');
            dayCell.className = `bg-white min-h-24 p-2 ${isCurrentMonth ? '' : 'text-gray-400'} ${isToday ? 'bg-blue-50' : ''}`;
            
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
            
            dayCell.addEventListener('click', function() {
                window.location.href = `/day?date=${formattedDate}`;
            });
            
            calendarGrid.appendChild(dayCell);
            
            const reservasContainer = dayCell.querySelector(`#reservas-${formattedDate}`);
            
            dayReservas.forEach(reserva => {
                const reservaItem = document.createElement('div');
                reservaItem.className = `text-xs p-1 rounded truncate cursor-pointer ${
                    reserva.estado === 'confirmada' 
                        ? 'bg-green-100 text-green-800 border-l-2 border-green-500' 
                        : 'bg-amber-100 text-amber-800 border-l-2 border-amber-500'
                }`;
                reservaItem.textContent = `${reserva.hora.substring(0, 5)} - ${reserva.nombre}`;
                
                reservaItem.addEventListener('click', function(e) {
                    e.stopPropagation();
                    window.location.href = `/reserva-detail?id=${reserva.id}`;
                });
                
                reservasContainer.appendChild(reservaItem);
            });
        }
        
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
        
        renderDesktopCalendar(currentDate);
    }
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>