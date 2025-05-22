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

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>