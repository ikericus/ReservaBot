<?php
// pages/dia.php

// Configurar la página actual
$pageTitle = 'ReservaBot - Día';
$currentPage = 'calendario';
$pageScript = 'dia'; // Esto carga dia.js
$pageStyle = 'dia'; // Esto carga dia.css

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