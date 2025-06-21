<?php
// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Configurar la página actual
$currentPage = 'calendario';
$pageTitle = 'ReservaBot - Calendario';
$pageScript = 'calendario'; // Esto carga calendario.js

// Obtener usuario
$currentUser = getAuthenticatedUser();

// Obtener las reservas del usuario autenticado
$userId =  $currentUser['id'];

// Obtener reservas para el mes actual
try {
    $stmt = getPDO()->prepare("SELECT * FROM reservas WHERE usuario_id = ?");
    $stmt->execute([$userId]);
    $todasReservas = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log('Error al obtener reservas: ' . $e->getMessage());
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
        cursor: pointer;
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
        cursor: pointer;
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
        background: #f8fafc;
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
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
        cursor: pointer;
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
        margin-bottom: 1rem;
    }

    .mobile-day-nav-btn {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        background: rgba(102, 126, 234, 0.2);
        color: #667eea;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        transition: all 0.2s ease;
        cursor: pointer;
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
    
    #calendarGrid .cursor-pointer {
        position: relative;
        overflow: visible;
    }
    
    #calendarGrid .cursor-pointer:hover {
        z-index: 10;
        transform: translateY(-1px);
    }
    
    /* Mejorar la transición del día completo */
    .mobile-day-cell,
    #calendarGrid > div {
        transition: all 0.2s ease;
    }
    
    #calendarGrid > div:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

<!-- <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Calendario de Reservas</h1>
</div> -->

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
        
        <!-- <div class="mobile-month-stats">
            <div class="mobile-stat-item">
                <div class="mobile-stat-number" id="mobileConfirmadasCount">0</div>
                <div class="mobile-stat-label">Confirmadas</div>
            </div>
            <div class="mobile-stat-item">
                <div class="mobile-stat-number" id="mobilePendientesCount">0</div>
                <div class="mobile-stat-label">Pendientes</div>
            </div>
        </div> -->
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
                </button>
                <div>
                    <h3 id="mobileDayDate" class="mobile-day-date">25</h3>
                    <p id="mobileDayName" class="mobile-day-name">Domingo, Mayo 2025</p>
                </div>
                <button id="mobileNextDay" class="mobile-day-nav-btn">
                    <i class="ri-arrow-right-s-line"></i>
                </button>
            </div>
        </div>
        
        <div id="mobileReservationsList" class="mobile-reservations-list">
            <!-- Las reservas del día se mostrarán aquí -->
        </div>
    </div>
</div>

<!-- Pasar datos al JavaScript -->
<script>
    // Convertir los datos PHP a JSON para que JavaScript pueda usarlos
    window.reservas = <?php echo json_encode($todasReservas); ?>;
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>