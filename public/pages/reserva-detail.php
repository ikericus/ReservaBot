<?php
// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Configurar la página actual
$currentPage = 'calendar';
$pageTitle = 'ReservaBot - Detalle de Reserva';
$pageScript = 'reserva-detail';

// Obtener ID de la URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Obtener la reserva
try {
    $stmt = getPDO()->prepare('SELECT * FROM reservas WHERE id = ?');
    $stmt->execute([$id]);
    $reserva = $stmt->fetch();
    
    if (!$reserva) {
        // Si la reserva no existe, redirigir al calendario
        header('Location: /dia');
        exit;
    }
} catch (\PDOException $e) {
    // Si hay un error, redirigir al calendario
    header('Location: /dia');
    exit;
}

// Obtener usuario actual para verificar conexión WhatsApp
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

// Verificar estado de WhatsApp
$whatsappConfig = null;
try {
    $stmt = getPDO()->prepare('SELECT status, phone_number FROM whatsapp_config WHERE usuario_id = ?');
    $stmt->execute([$userId]);
    $whatsappConfig = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Error obteniendo configuración WhatsApp: ' . $e->getMessage());
}

$whatsappConnected = $whatsappConfig && in_array($whatsappConfig['status'], ['connected', 'ready']);

// Incluir la cabecera
include 'includes/header.php';
?>

<style>
/* Estilos para el botón de WhatsApp */
.whatsapp-button {
    background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
    box-shadow: 0 2px 8px rgba(37, 211, 102, 0.3);
    transition: all 0.3s ease;
}

.whatsapp-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
}

.whatsapp-button:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}
</style>

<div class="flex items-center mb-6">
    <a href="/day?date=<?php echo $reserva['fecha']; ?>" class="mr-4 p-2 rounded-full hover:bg-gray-100">
        <i class="ri-arrow-left-line text-gray-600 text-xl"></i>
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Detalle de Reserva</h1>
</div>

<!-- Tarjeta de información -->
<div class="bg-white rounded-lg shadow-sm border-l-4 <?php echo $reserva['estado'] === 'confirmada' ? 'border-green-500' : 'border-amber-500'; ?> mb-6">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-medium text-gray-900"><?php echo htmlspecialchars($reserva['nombre']); ?></h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $reserva['estado'] === 'confirmada' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'; ?>">
                <?php echo $reserva['estado'] === 'confirmada' ? 'Confirmada' : 'Pendiente'; ?>
            </span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="text-sm font-medium text-gray-500">FECHA Y HORA</h3>
                <p class="mt-1 text-base text-gray-900 flex items-center">
                    <i class="ri-calendar-line mr-2 text-gray-500"></i>
                    <?php echo formatearFecha($reserva['fecha']); ?>
                </p>
                <p class="mt-1 text-base text-gray-900 flex items-center">
                    <i class="ri-time-line mr-2 text-gray-500"></i>
                    <?php echo substr($reserva['hora'], 0, 5); ?>
                </p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500">CONTACTO</h3>
                <p class="mt-1 text-base text-gray-900 flex items-center">
                    <i class="ri-phone-line mr-2 text-gray-500"></i>
                    <?php echo htmlspecialchars($reserva['telefono']); ?>
                </p>
            </div>
        </div>
        
        <div class="mt-4">
            <h3 class="text-sm font-medium text-gray-500">MENSAJE</h3>
            <p class="mt-1 text-base text-gray-900">
                <?php echo !empty($reserva['mensaje']) ? htmlspecialchars($reserva['mensaje']) : 'Sin mensaje'; ?>
            </p>
        </div>
    </div>
</div>

<!-- Acciones -->
<div class="border-t border-gray-200 pt-4">
    <h3 class="text-lg font-medium text-gray-900 mb-3">Acciones</h3>
    <div class="flex flex-wrap gap-2">
        <a href="/reserva-form?id=<?php echo $reserva['id']; ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="ri-edit-line mr-2"></i>
            Editar Reserva
        </a>
        
        <?php if ($reserva['estado'] === 'pendiente'): ?>
            <button id="confirmarBtn" class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <i class="ri-check-line mr-2"></i>
                Confirmar Reserva
            </button>
        <?php endif; ?>
        
        <button 
            onclick="openWhatsAppChat('<?php echo addslashes($reserva['telefono']); ?>', '<?php echo addslashes($reserva['nombre']); ?>')" 
            class="whatsapp-button inline-flex items-center px-3 py-2 border border-green-300 shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 <?php echo !$whatsappConnected ? 'opacity-50 cursor-not-allowed' : ''; ?>"
            <?php echo !$whatsappConnected ? 'disabled title="WhatsApp no está conectado"' : ''; ?>
        >
            <i class="ri-whatsapp-line mr-2"></i>
            Enviar Mensaje WhatsApp
        </button>
        
        <button id="eliminarBtn" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
            <i class="ri-delete-bin-line mr-2"></i>
            Eliminar Reserva
        </button>
    </div>
</div>

<!-- Panel de mensajes (inicialmente oculto) -->
<div id="mensajesPanel" class="mt-6 border-t border-gray-200 pt-4 <?php echo $action === 'message' ? '' : 'hidden'; ?>">
    <h3 class="text-lg font-medium text-gray-900 mb-3">Enviar mensaje a través de WhatsApp</h3>
    <div class="space-y-3">
        <textarea
            id="mensajeTexto"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            rows="4"
            placeholder="Escribe tu mensaje aquí..."
        ></textarea>
        <div class="flex justify-end space-x-3">
            <button
                type="button"
                id="cancelarMensajeBtn"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                Cancelar
            </button>
          <button
                type="button"
                id="enviarMensajeBtn"
                class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <i class="ri-send-plane-line mr-2"></i>
                Enviar Mensaje
            </button>
        </div>
    </div>
</div>

<!-- Diálogo de confirmación para eliminar (inicialmente oculto) -->
<div id="deleteConfirmDialog" class="fixed inset-0 z-10 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="ri-delete-bin-line text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Eliminar Reserva
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                ¿Estás seguro de que deseas eliminar esta reserva? Esta acción no se puede deshacer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="confirmDeleteBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Eliminar
                </button>
                <button type="button" id="cancelDeleteBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Definir variables para el componente de conversación
$clientPhone = $reserva['telefono'];
$clientName = $reserva['nombre'];

// Incluir componente de conversación
include 'components/conversacion.php';

// Incluir el pie de página
include 'includes/footer.php'; 
?>