<?php
// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Configurar la página actual
$currentPage = 'calendar';
$pageTitle = 'ReservaBot - Detalle del Día';
$pageScript = 'day';

// Obtener fecha de la URL
$fecha = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validar formato de fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    // Si el formato no es válido, usar la fecha actual
    $fecha = date('Y-m-d');
}

// Obtener usuario
$currentUser = getAuthenticatedUser();
$userId =  $currentUser['id'];

// Obtener reservas del día
try {
    $stmt = getPDO()->prepare('SELECT * FROM reservas WHERE fecha = ? AND usuario_id = ? ORDER BY hora');
    $stmt->execute([$fecha, $userId]);
    $reservasDelDia = $stmt->fetchAll();
} catch (\PDOException $e) {
    $reservasDelDia = [];
}

// Incluir la cabecera
include 'includes/header.php';
?>

<div class="flex items-center mb-6">
    <a href="/calendario" class="mr-4 p-2 rounded-full hover:bg-gray-100">
        <i class="ri-arrow-left-line text-gray-600 text-xl"></i>
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Reservas del <?php echo formatearFecha($fecha); ?></h1>
</div>

<div class="flex justify-end mb-6">
    <a href="/reserva-form?date=<?php echo $fecha; ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <i class="ri-add-line mr-2"></i>
        Nueva Reserva
    </a>
</div>

<!-- Lista de reservas del día -->
<?php if (empty($reservasDelDia)): ?>
    <div class="text-center py-12 bg-white rounded-lg shadow-sm">
        <i class="ri-calendar-event-line text-gray-400 text-6xl"></i>
        <h3 class="mt-2 text-lg font-medium text-gray-900">No hay reservas para este día</h3>
        <p class="mt-1 text-gray-500">
            ¿Quieres crear una nueva reserva?
        </p>
        <div class="mt-6">
            <a href="/reserva-form?date=<?php echo $fecha; ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="ri-add-line mr-2"></i>
                Nueva Reserva
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($reservasDelDia as $reserva): ?>
            <div class="bg-white rounded-lg shadow-sm border-l-4 <?php echo $reserva['estado'] === 'confirmada' ? 'border-green-500' : 'border-amber-500'; ?>">
                <div class="p-4">
                    <div class="flex justify-between">
                        <div>
                            <div class="flex items-center">
                                <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($reserva['nombre']); ?></h3>
                                <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $reserva['estado'] === 'confirmada' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'; ?>">
                                    <?php echo $reserva['estado'] === 'confirmada' ? 'Confirmada' : 'Pendiente'; ?>
                                </span>
                            </div>
                            
                            <div class="mt-2 text-sm text-gray-600 space-y-1">
                                <div class="flex items-center">
                                    <i class="ri-time-line mr-1"></i>
                                    <?php echo substr($reserva['hora'], 0, 5); ?>
                                </div>
                                <div class="flex items-center">
                                    <i class="ri-phone-line mr-1"></i>
                                    <?php echo htmlspecialchars($reserva['telefono']); ?>
                                </div>
                                <?php if (!empty($reserva['mensaje'])): ?>
                                <div class="flex items-start mt-1">
                                    <i class="ri-chat-1-line mr-1 mt-1"></i>
                                    <p class="text-gray-700"><?php echo htmlspecialchars($reserva['mensaje']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-2">
                            <a href="/reserva-detail?id=<?php echo $reserva['id']; ?>" class="inline-flex items-center p-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="ri-eye-line"></i>
                            </a>
                            <a href="/reserva-form?id=<?php echo $reserva['id']; ?>" class="inline-flex items-center p-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="ri-edit-line"></i>
                            </a>
                            <?php if ($reserva['estado'] === 'pendiente'): ?>
                                <button class="inline-flex items-center p-2 border border-green-300 rounded-md text-sm text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 btn-confirmar" data-id="<?php echo $reserva['id']; ?>">
                                    <i class="ri-check-line"></i>
                                </button>
                            <?php else: ?>
                                <button class="inline-flex items-center p-2 border border-blue-300 rounded-md text-sm text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 btn-mensaje" data-id="<?php echo $reserva['id']; ?>">
                                    <i class="ri-message-line"></i>
                                </button>
                            <?php endif; ?>
                            <button class="inline-flex items-center p-2 border border-red-300 rounded-md text-sm text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 btn-eliminar" data-id="<?php echo $reserva['id']; ?>">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>