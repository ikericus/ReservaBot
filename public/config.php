<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';

// Configurar la página actual
$currentPage = 'config';
$pageTitle = 'ReservaBot - Configuración';
$pageScript = 'dashboard'; // Cambiado de 'config' a 'dashboard' para usar el archivo corregido

// Obtener la configuración actual - USAR TABLA CORRECTA
try {
    $stmt = $pdo->query('SELECT * FROM configuraciones');
    $configuraciones = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (\PDOException $e) {
    error_log('Error al obtener configuraciones: ' . $e->getMessage());
    $configuraciones = [];
}

// Establecer valores predeterminados si no existen
$modoAceptacion = $configuraciones['modo_aceptacion'] ?? 'manual';
$mensajeBienvenida = $configuraciones['mensaje_bienvenida'] ?? '¡Hola! Soy el asistente virtual de [Nombre del Negocio]. ¿En qué puedo ayudarte hoy?';
$mensajeConfirmacion = $configuraciones['mensaje_confirmacion'] ?? 'Tu reserva para el día {fecha} a las {hora} ha sido confirmada. ¡Te esperamos!';
$mensajePendiente = $configuraciones['mensaje_pendiente'] ?? 'Hemos recibido tu solicitud para el día {fecha} a las {hora}. Te confirmaremos pronto.';
$intervaloReservas = $configuraciones['intervalo_reservas'] ?? '30';

// Horarios
$diasSemana = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];
$horarios = [];

foreach ($diasSemana as $dia) {
    $horarioConfig = $configuraciones["horario_{$dia}"] ?? 'true|09:00|18:00';
    list($activo, $horaInicio, $horaFin) = explode('|', $horarioConfig);
    
    $horarios[$dia] = [
        'activo' => $activo === 'true',
        'inicio' => $horaInicio,
        'fin' => $horaFin
    ];
}

// Nombres completos de los días
$nombresDias = [
    'lun' => 'Lunes',
    'mar' => 'Martes',
    'mie' => 'Miércoles',
    'jue' => 'Jueves',
    'vie' => 'Viernes',
    'sab' => 'Sábado',
    'dom' => 'Domingo'
];

// Incluir la cabecera
include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Configuración</h1>
</div>

<div class="max-w-4xl mx-auto">
    <form id="configForm" class="space-y-8">

    <!-- Configuración de WhatsApp -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
            <i class="ri-whatsapp-line mr-2 text-green-600"></i>
            Configuración de WhatsApp
        </h2>
        
        <div class="space-y-4">
            <div>
                <label for="whatsappMensajeNuevaReserva" class="block text-sm font-medium text-gray-700 mb-1">
                    Mensaje de nueva reserva
                </label>
                <textarea
                    id="whatsappMensajeNuevaReserva"
                    name="whatsapp_mensaje_nueva_reserva"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                    rows="2"
                ><?php echo htmlspecialchars($configuraciones['whatsapp_mensaje_nueva_reserva'] ?? 'Has realizado una nueva reserva para el {fecha} a las {hora}. Te confirmaremos pronto.'); ?></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Variables disponibles: {nombre}, {fecha}, {hora}
                </p>
            </div>
            
            <div>
                <label for="whatsappMensajeConfirmacion" class="block text-sm font-medium text-gray-700 mb-1">
                    Mensaje de confirmación
                </label>
                <textarea
                    id="whatsappMensajeConfirmacion"
                    name="whatsapp_mensaje_confirmacion"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                    rows="2"
                ><?php echo htmlspecialchars($configuraciones['whatsapp_mensaje_confirmacion'] ?? 'Tu reserva para el {fecha} a las {hora} ha sido confirmada. ¡Te esperamos!'); ?></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Variables disponibles: {nombre}, {fecha}, {hora}
                </p>
            </div>
            
            <div>
                <label for="whatsappMensajeRecordatorio" class="block text-sm font-medium text-gray-700 mb-1">
                    Mensaje de recordatorio
                </label>
                <textarea
                    id="whatsappMensajeRecordatorio"
                    name="whatsapp_mensaje_recordatorio"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                    rows="2"
                ><?php echo htmlspecialchars($configuraciones['whatsapp_mensaje_recordatorio'] ?? 'Recordatorio: Tienes una cita mañana {fecha} a las {hora}. ¡Te esperamos!'); ?></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Variables disponibles: {nombre}, {fecha}, {hora}
                </p>
            </div>
            
            <div>
                <label for="whatsappMensajeCancelacion" class="block text-sm font-medium text-gray-700 mb-1">
                    Mensaje de cancelación
                </label>
                <textarea
                    id="whatsappMensajeCancelacion"
                    name="whatsapp_mensaje_cancelacion"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                    rows="2"
                ><?php echo htmlspecialchars($configuraciones['whatsapp_mensaje_cancelacion'] ?? 'Tu reserva para el {fecha} a las {hora} ha sido cancelada.'); ?></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Variables disponibles: {nombre}, {fecha}, {hora}
                </p>
            </div>
            
            <div class="mt-2">
                <p class="text-sm text-gray-500">
                    Para configuración avanzada de WhatsApp, vaya a la sección <a href="/whatsapp" class="text-blue-600 hover:text-blue-800">Conexión WhatsApp</a>.
                </p>
            </div>
        </div>
    </div>

        <!-- Configuración de reservas -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                <i class="ri-calendar-check-line mr-2 text-blue-600"></i>
                Configuración de reservas
            </h2>
            
            <!-- Destacar el modo de aceptación -->
            <div class="mb-6 border-b border-gray-200 pb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-medium text-gray-900">Modo de aceptación de reservas</h3>
                        <p class="text-sm text-gray-500" id="modoDescription">
                            <?php echo $modoAceptacion === 'automatico' 
                                ? 'Las reservas se aceptan automáticamente en horarios disponibles' 
                                : 'Las reservas requieren aprobación manual'; ?>
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="mr-3 text-sm font-medium text-gray-700" id="modoLabel">
                            <?php echo $modoAceptacion === 'automatico' ? 'Automático' : 'Manual'; ?>
                        </span>
                        <button 
                            id="toggleModo" 
                            type="button"
                            class="relative inline-flex h-6 w-11 items-center rounded-full <?php echo $modoAceptacion === 'automatico' ? 'bg-blue-600' : 'bg-gray-200'; ?> focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            <span 
                                class="inline-block h-4 w-4 transform rounded-full bg-white <?php echo $modoAceptacion === 'automatico' ? 'translate-x-6' : 'translate-x-1'; ?> transition-transform"
                            ></span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Otras configuraciones de reservas -->
            <div class="space-y-4">
                <div>
                    <label for="intervaloReservas" class="block text-sm font-medium text-gray-700 mb-1">
                        Intervalo entre reservas (minutos)
                    </label>
                    <select
                        id="intervaloReservas"
                        name="intervalo_reservas"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    >
                        <option value="15" <?php echo $intervaloReservas == 15 ? 'selected' : ''; ?>>15 minutos</option>
                        <option value="30" <?php echo $intervaloReservas == 30 ? 'selected' : ''; ?>>30 minutos</option>
                        <option value="45" <?php echo $intervaloReservas == 45 ? 'selected' : ''; ?>>45 minutos</option>
                        <option value="60" <?php echo $intervaloReservas == 60 ? 'selected' : ''; ?>>1 hora</option>
                        <option value="90" <?php echo $intervaloReservas == 90 ? 'selected' : ''; ?>>1 hora y 30 minutos</option>
                        <option value="120" <?php echo $intervaloReservas == 120 ? 'selected' : ''; ?>>2 horas</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Mensajes automáticos -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                <i class="ri-message-3-line mr-2 text-blue-600"></i>
                Mensajes automáticos
            </h2>
            
            <div class="space-y-4">
                <div>
                    <label for="mensajeBienvenida" class="block text-sm font-medium text-gray-700 mb-1">
                        Mensaje de bienvenida
                    </label>
                    <textarea
                        id="mensajeBienvenida"
                        name="mensaje_bienvenida"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                        rows="2"
                    ><?php echo htmlspecialchars($mensajeBienvenida); ?></textarea>
                </div>
                
                <div>
                    <label for="mensajeConfirmacion" class="block text-sm font-medium text-gray-700 mb-1">
                        Mensaje de confirmación
                    </label>
                    <textarea
                        id="mensajeConfirmacion"
                        name="mensaje_confirmacion"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                        rows="2"
                    ><?php echo htmlspecialchars($mensajeConfirmacion); ?></textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        Variables disponibles: {fecha}, {hora}, {nombre}
                    </p>
                </div>
                
                <div>
                    <label for="mensajePendiente" class="block text-sm font-medium text-gray-700 mb-1">
                        Mensaje de reserva pendiente
                    </label>
                    <textarea
                        id="mensajePendiente"
                        name="mensaje_pendiente"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                        rows="2"
                    ><?php echo htmlspecialchars($mensajePendiente); ?></textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        Variables disponibles: {fecha}, {hora}, {nombre}
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Horario de atención -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                <i class="ri-time-line mr-2 text-blue-600"></i>
                Horario de atención
            </h2>
            
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg mb-4">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Día</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Hora Inicio</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Hora Fin</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Activo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach ($diasSemana as $dia): ?>
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                    <?php echo $nombresDias[$dia]; ?>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <input
                                        type="time"
                                        name="horario_<?php echo $dia; ?>_inicio"
                                        class="block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        value="<?php echo $horarios[$dia]['inicio']; ?>"
                                        <?php echo !$horarios[$dia]['activo'] ? 'disabled' : ''; ?>
                                    >
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <input
                                        type="time"
                                        name="horario_<?php echo $dia; ?>_fin"
                                        class="block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        value="<?php echo $horarios[$dia]['fin']; ?>"
                                        <?php echo !$horarios[$dia]['activo'] ? 'disabled' : ''; ?>
                                    >
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <div class="form-check form-switch">
                                        <input 
                                            class="toggle-day" 
                                            type="checkbox" 
                                            name="horario_<?php echo $dia; ?>_activo" 
                                            data-dia="<?php echo $dia; ?>"
                                            <?php echo $horarios[$dia]['activo'] ? 'checked' : ''; ?>
                                        >
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
                
        <!-- Botón guardar -->
        <div class="pt-4 text-right">
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <i class="ri-save-line mr-2"></i>
                Guardar configuración
            </button>
        </div>
    </form>
</div>

<div id="saveSuccessMessage" class="fixed bottom-4 right-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md shadow-lg hidden">
    <div class="flex items-center">
        <i class="ri-check-line mr-2 text-green-500"></i>
        <span>Configuración guardada correctamente</span>
    </div>
</div>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>