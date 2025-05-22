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
            <i class="ri-message-2-line mr-2"></i>
            Solicitudes Pendientes
            <span id="pendientesCount" class="bg-red-100 text-red-800 text-xs font-medium ml-2 px-2.5 py-0.5 rounded-full">
                <?php echo count($reservasPendientes); ?>
            </span>
        </button>
        <button 
            id="confirmadasTab" 
            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center"
        >
            <i class="ri-check-line mr-2"></i>
            Reservas Confirmadas
            <span id="confirmadasCount" class="bg-green-100 text-green-800 text-xs font-medium ml-2 px-2.5 py-0.5 rounded-full">
                <?php echo count($reservasConfirmadas); ?>
            </span>
        </button>
    </nav>
</div>

<!-- Contenido de los tabs -->
<div class="mt-6">
    <!-- Solicitudes Pendientes -->
    <div id="pendientesContent" class="block">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Solicitudes de Reserva Pendientes</h2>
        <div id="pendientesList">
            <?php if (empty($reservasPendientes)): ?>
                <div class="text-center py-8 text-gray-500">
                    No hay solicitudes pendientes
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
                                <p class="mt-2 text-sm text-gray-600 italic">"<?php echo htmlspecialchars($reserva['mensaje']); ?>"</p>
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
    </div>
    
    <!-- Reservas Confirmadas -->
    <div id="confirmadasContent" class="hidden">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Reservas Confirmadas</h2>
        <div id="confirmadasList">
            <?php if (empty($reservasConfirmadas)): ?>
                <div class="text-center py-8 text-gray-500">
                    No hay reservas confirmadas
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
                                <p class="mt-2 text-sm text-gray-600 italic">"<?php echo htmlspecialchars($reserva['mensaje']); ?>"</p>
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
    </div>
</div>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>