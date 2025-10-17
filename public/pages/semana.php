<?php
// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Configurar la página actual
$currentPage = 'calendario';
$pageTitle = 'ReservaBot - Vista Semana';
$pageScript = 'semana'; // Esto carga semana.js

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

// Obtener el inicio de la semana (Lunes)
$inicioSemana = clone $fechaObj;
$diaSemana = $inicioSemana->format('w'); // 0 = Domingo, 1 = Lunes, etc.
$diasHastaLunes = ($diaSemana == 0) ? 6 : $diaSemana - 1; // Calcular días hasta el lunes
$inicioSemana->modify("-$diasHastaLunes days");

// Calcular fin de semana (Domingo)
$finSemana = clone $inicioSemana;
$finSemana->modify('+6 days');

// Calcular semanas anterior y siguiente
$semanaAnterior = clone $inicioSemana;
$semanaAnterior->modify('-7 days');

$semanaSiguiente = clone $inicioSemana;
$semanaSiguiente->modify('+7 days');

// Obtener reservas para toda la semana
try {
    $stmt = getPDO()->prepare("
        SELECT * FROM reservas 
        WHERE usuario_id = ? 
        AND fecha BETWEEN ? AND ? 
        ORDER BY fecha ASC, hora ASC
    ");
    $stmt->execute([
        $userId, 
        $inicioSemana->format('Y-m-d'), 
        $finSemana->format('Y-m-d')
    ]);
    $reservas = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log('Error al obtener reservas: ' . $e->getMessage());
    $reservas = [];
}

// Agrupar reservas por fecha
$reservasPorFecha = [];
foreach ($reservas as $reserva) {
    $reservasPorFecha[$reserva['fecha']][] = $reserva;
}

// Formatear datos para JavaScript
$reservasData = json_encode($reservasPorFecha);
$semanaData = json_encode([
    'inicioSemana' => $inicioSemana->format('Y-m-d'),
    'finSemana' => $finSemana->format('Y-m-d'),
    'semanaAnterior' => $semanaAnterior->format('Y-m-d'),
    'semanaSiguiente' => $semanaSiguiente->format('Y-m-d')
]);

// Incluir la cabecera
include 'includes/header.php';
?>

<style>
/* Estilos para la vista de semana mejorada */
.week-view-container {
    max-width: 100%;
    margin: 0 auto;
}

/* Header con navegación de vistas con degradado morado - igual que dia.php y mes.php */
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

/* Contenedor principal de la semana */
.week-container {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

/* Header de la semana con navegación - MISMA ALTURA que mes.php y dia.php */
.week-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem;
    color: white;
    min-height: 72px; /* Misma altura que el header de mes.php y dia.php */
    display: flex;
    align-items: center;
}

.week-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.week-nav-btn {
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

.week-nav-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}

.week-title-container {
    text-align: center;
}

.week-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    margin: 0;
}

/* Grid de la semana */
.week-grid {
    padding: 0;
}

.week-days-header {
    background: #f8fafc;
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    border-bottom: 1px solid #e5e7eb;
}

.week-day-header {
    padding: 0.75rem 0.5rem;
    text-align: center;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.week-days-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    height: 500px; /* Altura fija para que funcione el scroll */
}

.week-day-cell {
    border-right: 1px solid #f3f4f6;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    overflow: hidden; /* Importante para el scroll interno */
}

.week-day-cell:last-child {
    border-right: none;
}

.week-day-cell:hover {
    background: rgba(102, 126, 234, 0.05);
}

.week-day-cell.today {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

/* Header del día (número) - FIJO */
.week-day-header-content {
    padding: 1rem 0.75rem 0.5rem;
    flex-shrink: 0; /* No se encoge */
}

.week-day-number {
    font-size: 1.125rem;
    font-weight: 600;
    color: #374151;
    text-align: center;
    margin-bottom: 0.5rem;
}

.week-day-cell.today .week-day-number {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem auto;
    font-size: 1rem;
}

/* Contenedor de reservas - CON SCROLL */
.week-day-reservations {
    flex: 1; /* Ocupa todo el espacio disponible */
    overflow-y: auto; /* Scroll vertical */
    padding: 0 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

/* Personalizar scrollbar */
.week-day-reservations::-webkit-scrollbar {
    width: 4px;
}

.week-day-reservations::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.week-day-reservations::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.week-day-reservations::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.week-reservation-item {
    background: #f8fafc;
    border-radius: 0.375rem;
    padding: 0.5rem;
    border-left: 3px solid;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    overflow: hidden;
    flex-shrink: 0; /* No se encoge */
}

.week-reservation-item.confirmada {
    border-left-color: #10b981;
    background: rgba(16, 185, 129, 0.05);
}

.week-reservation-item.pendiente {
    border-left-color: #f59e0b;
    background: rgba(245, 158, 11, 0.05);
}

.week-reservation-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.week-reservation-time {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.25rem;
}

.week-reservation-client {
    color: #6b7280;
    font-size: 0.7rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Stats al final - FIJAS */
.week-day-stats {
    padding: 0.5rem 0.75rem;
    display: flex;
    gap: 0.25rem;
    justify-content: center;
    flex-wrap: wrap;
    flex-shrink: 0; /* No se encoge */
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
}

.week-stat-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.625rem;
    font-weight: 500;
}

.week-stat-badge.confirmada {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.week-stat-badge.pendiente {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

/* ===== ESTILOS MÓVIL ===== */
@media (max-width: 768px) {
    /* NUEVO: Evitar scroll en el body/main en móvil */
    body {
        overflow: hidden;
    }
    
    .main-content {
        overflow: hidden;
        height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    .desktop-view {
        display: none;
    }
    
    .mobile-view {
        display: flex;
        flex-direction: column;
        height: 100vh;
        overflow: hidden;
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
        flex-shrink: 0; /* NUEVO: No se encoge */
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
    
    .mobile-week-title {
        text-align: center;
    }
    
    .mobile-week-title-text {
        font-size: 1.125rem;
        font-weight: 600;
        margin: 0;
        color: white;
    }
    
    /* Vista de semana móvil */
    .mobile-week-view {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        flex: 1; /* NUEVO: Ocupa el espacio restante */
        display: flex;
        flex-direction: column;
    }
    
    /* Header de días (L M X J V S D) */
    .mobile-week-days-header {
        background: #f8fafc;
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        border-bottom: 1px solid #e5e7eb;
        flex-shrink: 0;
    }
    
    .mobile-week-day-header {
        padding: 0.5rem 0.25rem;
        text-align: center;
        font-size: 0.7rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .mobile-week-days {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        flex: 1; /* NUEVO: Ocupa todo el espacio disponible */
        overflow: hidden;
    }
    
    .mobile-week-day {
        border-right: 1px solid #f3f4f6;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.5rem 0.25rem;
        overflow: hidden;
    }
    
    .mobile-week-day:last-child {
        border-right: none;
    }
    
    .mobile-week-day:hover {
        background: rgba(102, 126, 234, 0.05);
    }
    
    .mobile-week-day.today {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    }
    
    .mobile-week-day-number {
        font-size: 1rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.25rem;
        flex-shrink: 0;
    }
    
    .mobile-week-day.today .mobile-week-day-number {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
    }
    
    .mobile-week-reservations {
        flex: 1;
        overflow-y: auto;
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
    }
    
    .mobile-week-reservation {
        background: #f8fafc;
        border-radius: 0.25rem;
        padding: 0.25rem;
        border-left: 2px solid;
        cursor: pointer;
        font-size: 0.6rem;
        text-align: center;
        flex-shrink: 0;
    }
    
    .mobile-week-reservation.confirmada {
        border-left-color: #10b981;
        background: rgba(16, 185, 129, 0.05);
    }
    
    .mobile-week-reservation.pendiente {
        border-left-color: #f59e0b;
        background: rgba(245, 158, 11, 0.05);
    }
    
    .mobile-week-reservation-time {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.125rem;
        font-size: 0.5rem;
    }
    
    .mobile-week-reservation-client {
        color: #6b7280;
        font-size: 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .mobile-week-stats {
        display: flex;
        gap: 0.125rem;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 0.25rem;
        flex-shrink: 0;
    }
    
    .mobile-week-stat {
        background: #f3f4f6;
        padding: 0.125rem 0.25rem;
        border-radius: 0.25rem;
        font-size: 0.5rem;
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
    
    /* Navegación de vistas móvil */
    .view-navigation {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
        margin-bottom: 1rem;
        flex-shrink: 0; /* NUEVO: No se encoge */
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
    <div class="week-view-container">
        
        <!-- Navegación de vistas -->
        <div class="view-navigation">
            <div class="view-links">
                <a href="/dia?date=<?php echo $fecha; ?>" class="view-link">
                    <i class="ri-calendar-event-line"></i>
                    Día
                </a>
                <a href="/semana?date=<?php echo $fecha; ?>" class="view-link active">
                    <i class="ri-calendar-line"></i>
                    Semana
                </a>
                <a href="/mes?date=<?php echo $fecha; ?>" class="view-link">
                    <i class="ri-calendar-2-line"></i>
                    Mes
                </a>
            </div>
        </div>
        
        <!-- Contenedor de la semana -->
        <div class="week-container">
            
            <!-- Header de la semana con navegación -->
            <div class="week-header">
                <div class="week-nav">
                    <a href="/semana?date=<?php echo $semanaAnterior->format('Y-m-d'); ?>" class="week-nav-btn">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                    <div class="week-title-container">
                        <h2 id="weekTitle" class="week-title"></h2>
                    </div>
                    <a href="/semana?date=<?php echo $semanaSiguiente->format('Y-m-d'); ?>" class="week-nav-btn">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                </div>
            </div>
            
            <!-- Cabecera de días de la semana -->
            <div class="week-days-header">
                <div class="week-day-header">Lun</div>
                <div class="week-day-header">Mar</div>
                <div class="week-day-header">Mié</div>
                <div class="week-day-header">Jue</div>
                <div class="week-day-header">Vie</div>
                <div class="week-day-header">Sáb</div>
                <div class="week-day-header">Dom</div>
            </div>
            
            <!-- Grid de días de la semana -->
            <div id="weekDaysGrid" class="week-days-grid">
                <!-- Los días se cargarán con JavaScript -->
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
            <a href="/semana?date=<?php echo $fecha; ?>" class="view-link active">
                <i class="ri-calendar-line"></i>
                Semana
            </a>
            <a href="/mes?date=<?php echo $fecha; ?>" class="view-link">
                <i class="ri-calendar-2-line"></i>
                Mes
            </a>
        </div>
    </div>
    
    <!-- Vista de Semana Móvil -->
    <div id="mobileWeekView" class="fade-in-mobile">
        <!-- Navegación de la semana -->
        <div class="mobile-nav-header">
            <a href="/semana?date=<?php echo $semanaAnterior->format('Y-m-d'); ?>" class="mobile-nav-btn">
                <i class="ri-arrow-left-s-line"></i>
            </a>
            <div class="mobile-week-title">
                <h3 id="mobileWeekTitle" class="mobile-week-title-text"></h3>
            </div>
            <a href="/semana?date=<?php echo $semanaSiguiente->format('Y-m-d'); ?>" class="mobile-nav-btn">
                <i class="ri-arrow-right-s-line"></i>
            </a>
        </div>
        
        <!-- Grid de la semana móvil -->
        <div class="mobile-week-view">
            <!-- Header de días móvil -->
            <div class="mobile-week-days-header">
                <div class="mobile-week-day-header">L</div>
                <div class="mobile-week-day-header">M</div>
                <div class="mobile-week-day-header">X</div>
                <div class="mobile-week-day-header">J</div>
                <div class="mobile-week-day-header">V</div>
                <div class="mobile-week-day-header">S</div>
                <div class="mobile-week-day-header">D</div>
            </div>
            
            <div id="mobileWeekDays" class="mobile-week-days">
                <!-- Los días de la semana se generarán con JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Pasar datos al JavaScript -->
<script>
    window.reservasData = <?php echo $reservasData; ?>;
    window.semanaData = <?php echo $semanaData; ?>;
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>