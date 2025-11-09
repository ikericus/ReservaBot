<?php
// pages/dia.php

// Configurar la página actual
$currentPage = 'calendario';
$pageTitle = 'ReservaBot - Vista día';
$pageScript = 'dia'; // Esto carga dia.js

// Obtener usuario
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

// Obtener fecha del parámetro o usar fecha actual
$fecha = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validar formato de fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

// Convertir a objeto DateTime para manipular
$fechaObj = new DateTime($fecha);

// Calcular fechas anterior y siguiente
$fechaAnterior = clone $fechaObj;
$fechaAnterior->modify('-1 day');

$fechaSiguiente = clone $fechaObj;
$fechaSiguiente->modify('+1 day');

// Obtener reservas para la fecha específica
try {
    $reservaDomain = getContainer()->getReservaDomain();
    $reservasEntities = $reservaDomain->obtenerReservasPorFecha($fechaObj, $userId);

    // Filtramos solo las pendientes o confirmadas
    $reservasFiltradas = array_filter($reservasEntities, function($r) {
        return $r->estaPendiente() || $r->estaConfirmada();
    });

    $reservas = array_map(fn($r) => $r->toArray(), $reservasFiltradas);
} catch (Exception $e) {
    setFlashError('Error al obtener reservas: ' . $e->getMessage());
    $reservas = [];
}

// Formatear datos para JavaScript
$reservasData = json_encode($reservas);
$fechaData = json_encode([
    'fecha' => $fecha,
    'fechaAnterior' => $fechaAnterior->format('Y-m-d'),
    'fechaSiguiente' => $fechaSiguiente->format('Y-m-d')
]);

// Incluir la cabecera
include 'includes/header.php';
?>

<style>
/* Estilos para la vista de día mejorada */
.day-view-container {
    max-width: 100%;
    margin: 0 auto;
}

/* Header con navegación de vistas con degradado morado - igual que mes.php */
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

/* Contenedor principal del día */
.day-container {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

/* Header del día con navegación - MISMA ALTURA que mes.php */
.day-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem;
    color: white;
    min-height: 72px; /* Misma altura que el header de mes.php */
    display: flex;
    align-items: center;
}

.day-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.day-nav-btn {
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

.day-nav-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}

.day-title-container {
    text-align: center;
}

/* NUEVA FECHA EN UNA SOLA LÍNEA */
.day-date {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    margin: 0;
}

/* Contenedor de reservas - NUEVO TIMELINE */
.reservations-container {
    padding: 0;
    height: 600px; /* Altura fija */
    overflow: hidden;
}

.reservations-list {
    padding: 1rem;
    height: 100%;
    overflow-y: auto;
    background: #fafafa;
}

/* TIMELINE DE HORAS */
.timeline-container {
    position: relative;
    padding-left: 80px; /* Espacio para las horas */
}

.timeline-line {
    position: absolute;
    left: 70px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #e5e7eb 0%, #d1d5db 50%, #e5e7eb 100%);
}

.timeline-hour {
    position: relative;
    min-height: 60px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: flex-start;
}

.timeline-hour:last-child {
    border-bottom: none;
}

.hour-label {
    position: absolute;
    left: -70px;
    top: 10px;
    width: 60px;
    text-align: right;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    background: #fafafa;
    padding-right: 10px;
}

.hour-content {
    flex: 1;
    padding: 10px 0 10px 20px;
    min-height: 60px;
    position: relative;
}

.hour-dot {
    position: absolute;
    left: -6px;
    top: 15px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #d1d5db;
    border: 2px solid #fafafa;
}

.hour-content.has-reservation .hour-dot {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
}

/* RESERVAS EN EL TIMELINE */
.timeline-reservation {
    background: white;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    border-left: 4px solid;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
    cursor: pointer;
}

.timeline-reservation:last-child {
    margin-bottom: 0;
}

.timeline-reservation.confirmada {
    border-left-color: #10b981;
    background: linear-gradient(to right, rgba(16, 185, 129, 0.05) 0%, white 20%);
}

.timeline-reservation.pendiente {
    border-left-color: #f59e0b;
    background: linear-gradient(to right, rgba(245, 158, 11, 0.05) 0%, white 20%);
}

.timeline-reservation:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.reservation-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.reservation-time {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
}

.reservation-status {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
    margin-left: auto;
}

.reservation-status.confirmada {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.reservation-status.pendiente {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.reservation-status i {
    font-size: 0.75rem;
}

.reservation-client {
    font-size: 0.875rem;
    color: #374151;
    font-weight: 500;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.reservation-phone {
    font-size: 0.75rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.empty-hour {
    color: #9ca3af;
    font-style: italic;
    font-size: 0.875rem;
    margin-top: 5px;
}

/* Estado vacío general */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1.5rem;
}

.empty-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.empty-description {
    font-size: 1rem;
    color: #6b7280;
    margin-bottom: 2rem;
}

.add-reservation-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.add-reservation-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    color: white;
}

/* ===== ESTILOS MÓVIL ===== */
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
    
    .mobile-day-title {
        text-align: center;
    }
    
    .mobile-day-date {
        font-size: 1.125rem;
        font-weight: 600;
        margin: 0;
        color: white;
    }
    
    /* Vista de día móvil */
    .mobile-day-view {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .mobile-reservations-list {
        padding: 0.5rem;
        /* max-height: calc(100vh - 220px);
        overflow-y: auto; */
    }
    
    .mobile-timeline-container {
        position: relative;
        padding-left: 60px;
    }
    
    .mobile-timeline-line {
        position: absolute;
        left: 50px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, #e5e7eb 0%, #d1d5db 50%, #e5e7eb 100%);
    }
    
    .mobile-timeline-hour {
        position: relative;
        min-height: 50px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: flex-start;
    }
    
    .mobile-hour-label {
        position: absolute;
        left: -55px;
        top: 8px;
        width: 45px;
        text-align: right;
        font-size: 0.75rem;
        font-weight: 500;
        color: #6b7280;
        background: white;
        padding-right: 8px;
    }
    
    .mobile-hour-content {
        flex: 1;
        padding: 8px 0 8px 15px;
        min-height: 50px;
        position: relative;
    }
    
    .mobile-hour-dot {
        position: absolute;
        left: -6px;
        top: 12px;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #d1d5db;
        border: 2px solid white;
    }
    
    .mobile-hour-content.has-reservation .mobile-hour-dot {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.3);
    }
    
    .mobile-timeline-reservation {
        background: white;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin-bottom: 0.375rem;
        border-left: 3px solid;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .mobile-timeline-reservation.confirmada {
        border-left-color: #10b981;
        background: linear-gradient(to right, rgba(16, 185, 129, 0.05) 0%, white 20%);
    }
    
    .mobile-timeline-reservation.pendiente {
        border-left-color: #f59e0b;
        background: linear-gradient(to right, rgba(245, 158, 11, 0.05) 0%, white 20%);
    }
    
    .mobile-reservation-time {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.25rem;
    }
    
    .mobile-reservation-client {
        font-size: 0.75rem;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin-bottom: 0.125rem;
    }
    
    .mobile-reservation-phone {
        font-size: 0.7rem;
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .mobile-empty-hour {
        color: #9ca3af;
        font-style: italic;
        font-size: 0.75rem;
        margin-top: 3px;
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
    <div class="day-view-container">
        
        <!-- Navegación de vistas -->
        <div class="view-navigation">
            <div class="view-links">
                <a href="/dia?date=<?php echo $fecha; ?>" class="view-link active">
                    <i class="ri-calendar-event-line"></i>
                    Día
                </a>
                <a href="/semana?date=<?php echo $fecha; ?>" class="view-link">
                    <i class="ri-calendar-line"></i>
                    Semana
                </a>
                <a href="/mes?date=<?php echo $fecha; ?>" class="view-link">
                    <i class="ri-calendar-2-line"></i>
                    Mes
                </a>
            </div>
        </div>
        
        <!-- Contenedor del día -->
        <div class="day-container">
            
            <!-- Header del día con navegación -->
            <div class="day-header">
                <div class="day-nav">
                    <a href="/dia?date=<?php echo $fechaAnterior->format('Y-m-d'); ?>" class="day-nav-btn">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                    <div class="day-title-container">
                        <h2 id="dayDate" class="day-date"></h2>
                    </div>
                    <a href="/dia?date=<?php echo $fechaSiguiente->format('Y-m-d'); ?>" class="day-nav-btn">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                </div>
            </div>
            
            <!-- Timeline de reservas -->
            <div class="reservations-container">
                <div id="reservationsList" class="reservations-list">
                    <div class="timeline-container">
                        <div class="timeline-line"></div>
                        <!-- El timeline se cargará con JavaScript -->
                    </div>
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
            <a href="/dia?date=<?php echo $fecha; ?>" class="view-link active">
                <i class="ri-calendar-event-line"></i>
                Día
            </a>
            <a href="/semana?date=<?php echo $fecha; ?>" class="view-link">
                <i class="ri-calendar-line"></i>
                Semana
            </a>
            <a href="/mes?date=<?php echo $fecha; ?>" class="view-link">
                <i class="ri-calendar-2-line"></i>
                Mes
            </a>
        </div>
    </div>
    
    <!-- Vista de Día Móvil -->
    <div id="mobileDayView" class="fade-in-mobile">
        <!-- Navegación del día -->
        <div class="mobile-nav-header">
            <a href="/dia?date=<?php echo $fechaAnterior->format('Y-m-d'); ?>" class="mobile-nav-btn">
                <i class="ri-arrow-left-s-line"></i>
            </a>
            <div class="mobile-day-title">
                <h3 id="mobileDayDate" class="mobile-day-date"></h3>
            </div>
            <a href="/dia?date=<?php echo $fechaSiguiente->format('Y-m-d'); ?>" class="mobile-nav-btn">
                <i class="ri-arrow-right-s-line"></i>
            </a>
        </div>
        
        <!-- Timeline móvil -->
        <div class="mobile-day-view">
            <div id="mobileReservationsList" class="mobile-reservations-list">
                <div class="mobile-timeline-container">
                    <div class="mobile-timeline-line"></div>
                    <!-- El timeline móvil se cargará con JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pasar datos al JavaScript -->
<script>
    window.reservasData = <?php echo $reservasData; ?>;
    window.fechaData = <?php echo $fechaData; ?>;
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>