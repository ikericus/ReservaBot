<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';

// Configurar la página actual
$currentPage = 'dashboard';
$pageTitle = 'ReservaBot - Reservas';
$pageScript = 'dashboard';

// Obtener las reservas
$reservasPendientes = [];
$reservasConfirmadas = [];

try {
    $stmt = $pdo->query("SELECT * FROM reservas WHERE estado = 'pendiente' ORDER BY fecha, hora");
    $reservasPendientes = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT * FROM reservas WHERE estado = 'confirmada' ORDER BY fecha, hora");
    $reservasConfirmadas = $stmt->fetchAll();
} catch (\PDOException $e) {
    // Manejar el error (para un MVP podemos simplemente mostrar reservas vacías)
    $reservasPendientes = [];
    $reservasConfirmadas = [];
}

// Incluir la cabecera
include 'includes/header.php';
?>

<style>
/* Estilos específicos para móvil */
@media (max-width: 768px) {
    .mobile-card {
        margin: 0.5rem 0;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }
    
    .mobile-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transform: translateY(-1px);
    }
    
    .mobile-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }
    
    .mobile-card-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }
    
    .mobile-card-status {
        flex-shrink: 0;
        margin-left: 0.5rem;
    }
    
    .mobile-card-content {
        margin-bottom: 1rem;
    }
    
    .mobile-card-info {
        display: flex;
        align-items: center;
        margin-bottom: 0.375rem;
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .mobile-card-info i {
        width: 1rem;
        margin-right: 0.5rem;
        flex-shrink: 0;
    }
    
    .mobile-card-message {
        background-color: #f9fafb;
        padding: 0.75rem;
        border-radius: 0.5rem;
        margin: 0.75rem 0;
        font-style: italic;
        font-size: 0.875rem;
        color: #4b5563;
        border-left: 3px solid #e5e7eb;
    }
    
    .mobile-card-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .mobile-btn {
        flex: 1;
        min-width: calc(50% - 0.25rem);
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        text-align: center;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
    }
    
    .mobile-btn-primary {
        background-color: #059669;
        color: white;
        border: none;
    }
    
    .mobile-btn-primary:hover {
        background-color: #047857;
    }
    
    .mobile-btn-secondary {
        background-color: white;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }
    
    .mobile-btn-secondary:hover {
        background-color: #f9fafb;
        color: #4b5563;
    }
    
    .mobile-btn-danger {
        background-color: white;
        color: #dc2626;
        border: 1px solid #fecaca;
    }
    
    .mobile-btn-danger:hover {
        background-color: #fef2f2;
    }
    
    .mobile-btn-blue {
        background-color: white;
        color: #2563eb;
        border: 1px solid #dbeafe;
    }
    
    .mobile-btn-blue:hover {
        background-color: #eff6ff;
    }
    
    /* Contador en tabs */
    .mobile-tab-counter {
        font-size: 0.75rem;
        padding: 0.125rem 0.375rem;
        border-radius: 9999px;
        font-weight: 500;
        margin-left: 0.5rem;
    }
    
    /* Animaciones suaves */
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

/* Estilos para móvil - usar diseño de tarjetas */
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
    <h1 class="text-2xl font-bold text-gray-900">Reservas</h1>
</div>

<!-- Tabs de navegación -->
<div class="border-b border-gray-200">
    <nav class="-mb-px flex space-x-8">
        <button 
            id="pendientesTab" 
            class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center"
        >
            <i class="ri-time-line mr-2"></i>
            <span class="hidden sm:inline">Solicitudes Pendientes</span>
            <span class="sm:hidden">Pendientes</span>
            <span id="pendientesCount" class="bg-red-100 text-red-800 text-xs font-medium ml-2 px-2.5 py-0.5 rounded-full mobile-tab-counter">
                <?php echo count($reservasPendientes); ?>
            </span>
        </button>
        <button 
            id="confirmadasTab" 
            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center"
        >
            <i class="ri-check-line mr-2"></i>
            <span class="hidden sm:inline">Reservas Confirmadas</span>
            <span class="sm:hidden">Confirmadas</span>
            <span id="confirmadasCount" class="bg-green-100 text-green-800 text-xs font-medium ml-2 px-2.5 py-0.5 rounded-full mobile-tab-counter">
                <?php echo count($reservasConfirmadas); ?>
            </span>
        </button>
    </nav>
</div>

<!-- Contenido de los tabs -->
<div class="mt-6">
    <!-- Solicitudes Pendientes -->
    <div id="pendientesContent" class="block">
        <h2 class="text-lg font-medium text-gray-900 mb-4 hidden sm:block">Solicitudes de Reserva Pendientes</h2>
        
        <!-- Vista Desktop -->
        <div class="desktop-view" id="pendientesList">
            <?php if (empty($reservasPendientes)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="ri-calendar-line text-4xl text-gray-400 mb-2"></i>
                    <p>No hay solicitudes pendientes</p>
                </div>
            <?php else: ?>
                <?php foreach ($reservasPendientes as $reserva): ?>
                    <div class="bg-white p-4 rounded-lg shadow-sm mb-4 border-l-4 border-amber-500" data-id="<?php echo $reserva['id']; ?>">
                        <div class="flex justify-between">
                            <div>
                                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($reserva['nombre']); ?></h3>
                                <div class="mt-1 flex items-center text-sm text-gray-500">
                                    <i class="ri-calendar-line mr-1"></i>
                                    <?php echo htmlspecialchars($reserva['fecha']); ?> - <?php echo htmlspecialchars($reserva['hora']); ?>
                                </div>
                                <div class="mt-1 flex items-center text-sm text-gray-500">
                                    <i class="ri-phone-line mr-1"></i>
                                    <?php echo htmlspecialchars($reserva['telefono']); ?>
                                </div>
                                <?php if (!empty($reserva['mensaje'])): ?>
                                    <p class="mt-2 text-sm text-gray-600 italic">"<?php echo htmlspecialchars($reserva['mensaje']); ?>"</p>
                                <?php endif; ?>
                            </div>
                            <div class="flex space-x-2">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 btn-aceptar" data-id="<?php echo $reserva['id']; ?>">
                                    <i class="ri-check-line mr-1"></i>
                                    Aceptar
                                </button>
                                <button class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 btn-rechazar" data-id="<?php echo $reserva['id']; ?>">
                                    <i class="ri-close-line mr-1"></i>
                                    Rechazar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Vista Mobile -->
        <div class="mobile-view">
            <?php if (empty($reservasPendientes)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="ri-calendar-line text-4xl text-gray-400 mb-2"></i>
                    <p class="text-sm">No hay solicitudes pendientes</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($reservasPendientes as $reserva): ?>
                        <div class="bg-white p-4 mobile-card border-l-4 border-amber-500 fade-in-mobile" data-id="<?php echo $reserva['id']; ?>">
                            <div class="mobile-card-header">
                                <h3 class="mobile-card-title"><?php echo htmlspecialchars($reserva['nombre']); ?></h3>
                                <span class="mobile-card-status inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                    Pendiente
                                </span>
                            </div>
                            
                            <div class="mobile-card-content">
                                <div class="mobile-card-info">
                                    <i class="ri-calendar-line"></i>
                                    <span><?php echo date('d/m/Y', strtotime($reserva['fecha'])); ?></span>
                                </div>
                                <div class="mobile-card-info">
                                    <i class="ri-time-line"></i>
                                    <span><?php echo substr($reserva['hora'], 0, 5); ?></span>
                                </div>
                                <div class="mobile-card-info">
                                    <i class="ri-phone-line"></i>
                                    <span><?php echo htmlspecialchars($reserva['telefono']); ?></span>
                                </div>
                                
                                <?php if (!empty($reserva['mensaje'])): ?>
                                    <div class="mobile-card-message">
                                        <i class="ri-chat-1-line mr-1"></i>
                                        <?php echo htmlspecialchars($reserva['mensaje']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mobile-card-actions">
                                <button class="mobile-btn mobile-btn-primary btn-aceptar" data-id="<?php echo $reserva['id']; ?>">
                                    <i class="ri-check-line"></i>
                                    Aceptar
                                </button>
                                <button class="mobile-btn mobile-btn-danger btn-rechazar" data-id="<?php echo $reserva['id']; ?>">
                                    <i class="ri-close-line"></i>
                                    Rechazar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Reservas Confirmadas -->
    <div id="confirmadasContent" class="hidden">
        <h2 class="text-lg font-medium text-gray-900 mb-4 hidden sm:block">Reservas Confirmadas</h2>
        
        <!-- Vista Desktop -->
        <div class="desktop-view" id="confirmadasList">
            <?php if (empty($reservasConfirmadas)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="ri-calendar-check-line text-4xl text-gray-400 mb-2"></i>
                    <p>No hay reservas confirmadas</p>
                </div>
            <?php else: ?>
                <?php foreach ($reservasConfirmadas as $reserva): ?>
                    <div class="bg-white p-4 rounded-lg shadow-sm mb-4 border-l-4 border-green-500" data-id="<?php echo $reserva['id']; ?>">
                        <div class="flex justify-between">
                            <div>
                                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($reserva['nombre']); ?></h3>
                                <div class="mt-1 flex items-center text-sm text-gray-500">
                                    <i class="ri-calendar-line mr-1"></i>
                                    <?php echo htmlspecialchars($reserva['fecha']); ?> - <?php echo htmlspecialchars($reserva['hora']); ?>
                                </div>
                                <div class="mt-1 flex items-center text-sm text-gray-500">
                                    <i class="ri-phone-line mr-1"></i>
                                    <?php echo htmlspecialchars($reserva['telefono']); ?>
                                </div>
                                <?php if (!empty($reserva['mensaje'])): ?>
                                    <p class="mt-2 text-sm text-gray-600 italic">"<?php echo htmlspecialchars($reserva['mensaje']); ?>"</p>
                                <?php endif; ?>
                            </div>
                            <div class="flex space-x-2">
                                <button class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 btn-mensaje" data-id="<?php echo $reserva['id']; ?>">
                                    <i class="ri-message-2-line mr-1"></i>
                                    Enviar mensaje
                                </button>
                                <button class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 btn-cancelar" data-id="<?php echo $reserva['id']; ?>">
                                    <i class="ri-close-line mr-1"></i>
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Vista Mobile -->
        <div class="mobile-view">
            <?php if (empty($reservasConfirmadas)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="ri-calendar-check-line text-4xl text-gray-400 mb-2"></i>
                    <p class="text-sm">No hay reservas confirmadas</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($reservasConfirmadas as $reserva): ?>
                        <div class="bg-white p-4 mobile-card border-l-4 border-green-500 fade-in-mobile" data-id="<?php echo $reserva['id']; ?>">
                            <div class="mobile-card-header">
                                <h3 class="mobile-card-title"><?php echo htmlspecialchars($reserva['nombre']); ?></h3>
                                <span class="mobile-card-status inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Confirmada
                                </span>
                            </div>
                            
                            <div class="mobile-card-content">
                                <div class="mobile-card-info">
                                    <i class="ri-calendar-line"></i>
                                    <span><?php echo date('d/m/Y', strtotime($reserva['fecha'])); ?></span>
                                </div>
                                <div class="mobile-card-info">
                                    <i class="ri-time-line"></i>
                                    <span><?php echo substr($reserva['hora'], 0, 5); ?></span>
                                </div>
                                <div class="mobile-card-info">
                                    <i class="ri-phone-line"></i>
                                    <span><?php echo htmlspecialchars($reserva['telefono']); ?></span>
                                </div>
                                
                                <?php if (!empty($reserva['mensaje'])): ?>
                                    <div class="mobile-card-message">
                                        <i class="ri-chat-1-line mr-1"></i>
                                        <?php echo htmlspecialchars($reserva['mensaje']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mobile-card-actions">
                                <button class="mobile-btn mobile-btn-blue btn-mensaje" data-id="<?php echo $reserva['id']; ?>">
                                    <i class="ri-message-2-line"></i>
                                    Mensaje
                                </button>
                                <button class="mobile-btn mobile-btn-danger btn-cancelar" data-id="<?php echo $reserva['id']; ?>">
                                    <i class="ri-close-line"></i>
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>