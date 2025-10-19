<?php
// pages/mes.php

// Configurar la página actual
$currentPage = 'calendario';
$pageTitle = 'ReservaBot - Vista Mes';
$pageScript = 'mes'; // Esto carga mes.js

// Obtener usuario
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

// Obtener fecha del parámetro o usar fecha actual
$fecha = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validar formato de fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

// Convertir a objeto DateTime
$fechaObj = new DateTime($fecha);

// Obtener primer y último día del mes
$primerDiaMes = new DateTime($fechaObj->format('Y-m-01'));
$ultimoDiaMes = new DateTime($fechaObj->format('Y-m-t'));

// Calcular meses anterior y siguiente
$mesAnterior = clone $primerDiaMes;
$mesAnterior->modify('-1 month');

$mesSiguiente = clone $primerDiaMes;
$mesSiguiente->modify('+1 month');

// Obtener reservas para todo el mes
try {
    $reservaDomain = getContainer()->getReservaDomain();
    $reservasEntities = $reservaDomain->obtenerReservasPorRango(
        $primerDiaMes, 
        $ultimoDiaMes, 
        $userId
    );
    $reservas = array_map(fn($r) => $r->toArray(), $reservasEntities);
} catch (Exception $e) {
    setFlashError('Error al obtener reservas: ' . $e->getMessage());
    $reservas = [];
}

// Agrupar reservas por fecha
$reservasPorFecha = [];
foreach ($reservas as $reserva) {
    $reservasPorFecha[$reserva['fecha']][] = $reserva;
}

// Formatear datos para JavaScript
$reservasData = json_encode($reservasPorFecha);
$mesData = json_encode([
    'año' => (int)$fechaObj->format('Y'),
    'mes' => (int)$fechaObj->format('n') - 1, // JavaScript usa 0-11 para meses
    'mesAnterior' => $mesAnterior->format('Y-m-d'),
    'mesSiguiente' => $mesSiguiente->format('Y-m-d')
]);

// Incluir la cabecera
include 'includes/header.php';
?>

<style>
/* Estilos para la vista de mes mejorada */
.month-view-container {
    max-width: 100%;
    margin: 0 auto;
}

/* Header con navegación de vistas con degradado morado */
.view-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.view-links {
    display: flex;
    gap: 0.5rem;
    background: white;
    border-radius: 0.75rem;
    padding: 0.25rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.view-link {
    padding: 0.75rem 1.25rem;
    background: transparent;
    border: none;
    border-radius: 0.5rem;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    cursor: pointer;
    white-space: nowrap;
}

.view-link:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #374151;
    text-decoration: none;
}

.view-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.view-link i {
    font-size: 1rem;
}

/* Calendario principal - estilo desktop */
.calendar-container {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.calendar-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem;
    color: white;
}

.calendar-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.calendar-nav-btn {
    padding: 0.5rem;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 1.25rem;
    transition: all 0.2s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    text-decoration: none;
}

.calendar-nav-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}

.calendar-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    margin: 0;
}

/* Grid del calendario - igual que calendario.php */
.calendar-grid-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #e5e7eb;
    margin-bottom: 1px;
}

.calendar-day-header {
    background: #f8fafc;
    padding: 0.75rem 0.5rem;
    text-align: center;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.calendar-grid-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #e5e7eb;
}

.calendar-day-cell {
    background: white;
    min-height: 120px;
    padding: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: visible;
}

.calendar-day-cell:hover {
    background: #f8fafc;
    transform: translateY(-1px);
    z-index: 10;
}

.calendar-day-cell.other-month {
    background: #f9fafb;
    color: #d1d5db;
}

.calendar-day-cell.today {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

.calendar-day-number {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.calendar-day-cell.today .calendar-day-number {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
}

.calendar-day-cell.other-month .calendar-day-number {
    color: #d1d5db;
}

/* Reservas en las celdas */
.calendar-day-reservations {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.calendar-reservation-item {
    background: #f3f4f6;
    border-radius: 0.25rem;
    padding: 0.25rem 0.375rem;
    border-left: 2px solid;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.2;
}

.calendar-reservation-item.confirmada {
    border-left-color: #10b981;
    background: #ecfdf5;
    color: #065f46;
}

.calendar-reservation-item.pendiente {
    border-left-color: #f59e0b;
    background: #fffbeb;
    color: #92400e;
}

.calendar-reservation-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    z-index: 20;
    position: relative;
}

.calendar-reservation-time {
    font-weight: 600;
}

.calendar-reservation-client {
    margin-left: 0.25rem;
    opacity: 0.8;
}

.calendar-more-reservations {
    background: #e5e7eb;
    color: #6b7280;
    text-align: center;
    padding: 0.125rem;
    border-radius: 0.25rem;
    font-size: 0.625rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.calendar-more-reservations:hover {
    background: #d1d5db;
}

/* Leyenda */
.calendar-legend {
    padding: 1rem;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 1.5rem;
    justify-content: center;
    align-items: center;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-color {
    width: 0.75rem;
    height: 0.5rem;
    border-radius: 0.125rem;
}

.legend-color.confirmada {
    background: #10b981;
}

.legend-color.pendiente {
    background: #f59e0b;
}

.legend-text {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
}

/* ===== ESTILOS MÓVIL - COPIADOS DE CALENDARIO.PHP ===== */
@media (max-width: 768px) {
    .desktop-view {
        display: none;
    }
    
    .mobile-view {
        display: block;
    }
    
    /* Navegación común para todas las vistas */
    .mobile-nav-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 1rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        display: flex;
        justify-content: space-between;
        align-items: center;
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
        text-decoration: none;
    }
    
    .mobile-nav-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.05);
    }
    
    .mobile-nav-title {
        font-size: 1.25rem;
        font-weight: 600;
        text-align: center;
        margin: 0;
        color: white;
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
    
    /* Navegación de vistas móvil */
    .view-navigation {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
        margin-bottom: 1rem;
    }
    
    .view-links {
        justify-content: center;
        background: white;
        border-radius: 1rem;
        padding: 0.25rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .view-link {
        flex: 1;
        padding: 0.75rem;
        border-radius: 0.75rem;
        text-align: center;
        justify-content: center;
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

/* Estilos para desktop */
@media (min-width: 769px) {
    .desktop-view {
        display: block;
    }
    
    .mobile-view {
        display: none;
    }
    
    .view-navigation {
        flex-direction: row;
        gap: 1rem;
        align-items: center;
    }
    
    .view-links {
        flex-direction: row;
    }
}
</style>

<!-- Vista Desktop -->
<div class="desktop-view">
    <div class="month-view-container">
        
        <!-- Navegación de vistas -->
        <div class="view-navigation">
            <div class="view-links">
                <a href="/dia?date=<?php echo $fecha; ?>" class="view-link">
                    <i class="ri-calendar-event-line"></i>
                    Día
                </a>
                <a href="/semana?date=<?php echo $fecha; ?>" class="view-link">
                    <i class="ri-calendar-line"></i>
                    Semana
                </a>
                <a href="/mes?date=<?php echo $fecha; ?>" class="view-link active">
                    <i class="ri-calendar-2-line"></i>
                    Mes
                </a>
            </div>
        </div>
        
        <!-- Contenedor del calendario -->
        <div class="calendar-container">
            
            <!-- Header del calendario con navegación -->
            <div class="calendar-header">
                <div class="calendar-nav">
                    <a href="/mes?date=<?php echo $mesAnterior->format('Y-m-d'); ?>" class="calendar-nav-btn">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                    <h2 id="monthTitle" class="calendar-title"></h2>
                    <a href="/mes?date=<?php echo $mesSiguiente->format('Y-m-d'); ?>" class="calendar-nav-btn">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                </div>
            </div>
            
            <!-- Cabecera de días de la semana -->
            <div class="calendar-grid-header">
                <div class="calendar-day-header">Lun</div>
                <div class="calendar-day-header">Mar</div>
                <div class="calendar-day-header">Mié</div>
                <div class="calendar-day-header">Jue</div>
                <div class="calendar-day-header">Vie</div>
                <div class="calendar-day-header">Sáb</div>
                <div class="calendar-day-header">Dom</div>
            </div>
            
            <!-- Grid del calendario -->
            <div id="calendarGrid" class="calendar-grid-body">
                <!-- Las celdas se cargarán con JavaScript -->
            </div>
            
            <!-- Leyenda -->
            <div class="calendar-legend">
                <div class="legend-item">
                    <div class="legend-color pendiente"></div>
                    <span class="legend-text">Reservas Pendientes</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color confirmada"></div>
                    <span class="legend-text">Reservas Confirmadas</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vista Mobile -->
<div class="mobile-view">
    <!-- Navegación de vistas -->
    <div class="view-navigation">
        <div class="view-links">
            <a href="/dia?date=<?php echo $fecha; ?>" class="view-link">
                <i class="ri-calendar-event-line"></i>
                Día
            </a>
            <a href="/semana?date=<?php echo $fecha; ?>" class="view-link">
                <i class="ri-calendar-line"></i>
                Semana
            </a>
            <a href="/mes?date=<?php echo $fecha; ?>" class="view-link active">
                <i class="ri-calendar-2-line"></i>
                Mes
            </a>
        </div>
    </div>
    
    <!-- Vista de Mes Móvil -->
    <div id="mobileMonthView" class="fade-in-mobile">
        <!-- Navegación del mes -->
        <div class="mobile-nav-header">
            <a href="/mes?date=<?php echo $mesAnterior->format('Y-m-d'); ?>" class="mobile-nav-btn">
                <i class="ri-arrow-left-s-line"></i>
            </a>
            <h2 id="mobileMonthDisplay" class="mobile-nav-title"></h2>
            <a href="/mes?date=<?php echo $mesSiguiente->format('Y-m-d'); ?>" class="mobile-nav-btn">
                <i class="ri-arrow-right-s-line"></i>
            </a>
        </div>
        
        <!-- Grid del calendario móvil -->
        <div class="mobile-calendar-grid">
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
    </div>
</div>

<!-- Pasar datos al JavaScript -->
<script>
    window.reservasData = <?php echo $reservasData; ?>;
    window.mesData = <?php echo $mesData; ?>;
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>