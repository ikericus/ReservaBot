<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';

// Configurar la página actual
$currentPage = 'calendar';
$pageTitle = 'ReservaBot - Formulario de Reserva';
$pageScript = 'reserva-form';

// Comprobar si es modo edición o creación
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$fecha = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$isEditMode = $id > 0;

// Obtener la reserva si estamos en modo edición
$reserva = null;
if ($isEditMode) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM reservas WHERE id = ?');
        $stmt->execute([$id]);
        $reserva = $stmt->fetch();
        
        if (!$reserva) {
            // Si la reserva no existe, redirigir al calendario
            header('Location: /calendar');
            exit;
        }
        
        // Usar la fecha de la reserva
        $fecha = $reserva['fecha'];
    } catch (\PDOException $e) {
        // Si hay un error, redirigir al calendario
        header('Location: /calendar');
        exit;
    }
}

// Incluir la cabecera
include 'includes/header.php';
?>

<div class="flex items-center mb-6">
    <a href="<?php echo $isEditMode ? "/reserva-detail?id={$id}" : "/day?date={$fecha}"; ?>" class="mr-4 p-2 rounded-full hover:bg-gray-100">
        <i class="ri-arrow-left-line text-gray-600 text-xl"></i>
    </a>
    <h1 class="text-2xl font-bold text-gray-900">
        <?php echo $isEditMode ? 'Editar Reserva' : 'Nueva Reserva'; ?>
    </h1>
</div>

<!-- Formulario -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <form id="reservaForm" class="space-y-6" method="post" action="api/<?php echo $isEditMode ? 'actualizar-reserva-completa.php' : 'crear-reserva.php'; ?>">
        <?php if ($isEditMode): ?>
            <input type="hidden" name="id" value="<?php echo $reserva['id']; ?>">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Nombre del cliente -->
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre completo
                </label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-user-line text-gray-400"></i>
                    </div>
                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        required
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Nombre del cliente"
                        value="<?php echo $isEditMode ? htmlspecialchars($reserva['nombre']) : ''; ?>"
                    >
                </div>
            </div>
            
            <!-- Teléfono -->
            <div>
                <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">
                    Teléfono
                </label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-phone-line text-gray-400"></i>
                    </div>
                    <input
                        type="tel"
                        name="telefono"
                        id="telefono"
                        required
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="+34 600 123 456"
                        value="<?php echo $isEditMode ? htmlspecialchars($reserva['telefono']) : ''; ?>"
                    >
                </div>
            </div>
            
            <!-- WhatsApp -->
            <div>
                <label for="whatsapp_id" class="flex items-center text-sm font-medium text-gray-700 mb-1">
                    <i class="ri-whatsapp-line mr-1 text-green-600"></i>
                    WhatsApp
                    <span class="ml-1 text-xs text-gray-500">(opcional)</span>
                </label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-whatsapp-line text-green-500"></i>
                    </div>
                    <input
                        type="tel"
                        name="whatsapp_id"
                        id="whatsapp_id"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="+34600123456"
                        value="<?php echo $isEditMode && isset($reserva['whatsapp_id']) ? htmlspecialchars($reserva['whatsapp_id']) : ''; ?>"
                    >
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Ingrese el número de WhatsApp para enviar notificaciones automáticas
                </p>
            </div>

            <!-- Fecha -->
            <div>
                <label for="fecha" class="block text-sm font-medium text-gray-700 mb-1">
                    Fecha
                </label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-calendar-line text-gray-400"></i>
                    </div>
                    <input
                        type="date"
                        name="fecha"
                        id="fecha"
                        required
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        value="<?php echo $isEditMode ? $reserva['fecha'] : $fecha; ?>"
                    >
                </div>
            </div>
            
            <!-- Hora -->
            <div>
                <label for="hora" class="block text-sm font-medium text-gray-700 mb-1">
                    Hora
                </label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-time-line text-gray-400"></i>
                    </div>
                    <select
                        name="hora"
                        id="hora"
                        required
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">Seleccione una hora</option>
                        <?php
                        // Horarios disponibles
                        $horarios = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30'];
                        
                        foreach ($horarios as $horario):
                            $selected = $isEditMode && substr($reserva['hora'], 0, 5) === $horario ? 'selected' : '';
                        ?>
                            <option value="<?php echo $horario; ?>" <?php echo $selected; ?>><?php echo $horario; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <?php if ($isEditMode): ?>
            <!-- Estado (solo en modo edición) -->
            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                    Estado
                </label>
                <select
                    name="estado"
                    id="estado"
                    class="block w-full pl-3 pr-10 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                >
                    <option value="pendiente" <?php echo $reserva['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="confirmada" <?php echo $reserva['estado'] === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Mensaje -->
        <div>
            <label for="mensaje" class="block text-sm font-medium text-gray-700 mb-1">
                Mensaje o notas
            </label>
            <div class="relative rounded-md shadow-sm">
                <div class="absolute top-3 left-3 flex items-start pointer-events-none">
                    <i class="ri-message-2-line text-gray-400"></i>
                </div>
                <textarea
                    name="mensaje"
                    id="mensaje"
                    rows="4"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Notas adicionales para la reserva"
                ><?php echo $isEditMode ? htmlspecialchars($reserva['mensaje']) : ''; ?></textarea>
            </div>
        </div>
        
        <!-- Botones de acción -->
        <div class="flex justify-end space-x-3">            
            <a href="<?php echo $isEditMode ? "/reserva-detail?id={$id}" : "/day?date={$fecha}"; ?>"
               class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Cancelar
            </a>
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <i class="ri-save-line mr-2"></i>
                <?php echo $isEditMode ? 'Guardar Cambios' : 'Crear Reserva'; ?>
            </button>
        </div>
    </form>
</div>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>