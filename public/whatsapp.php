<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';
require_once 'includes/whatsapp-functions.php';

// Configurar la página actual
$currentPage = 'whatsapp';
$pageTitle = 'ReservaBot - WhatsApp';
$pageScript = 'whatsapp';

// Obtener el estado actual de la conexión de WhatsApp
$whatsappStatus = getWhatsAppConnectionStatus();
$isConnected = $whatsappStatus['status'] === 'connected';

// Obtener configuraciones de mensajes de WhatsApp
try {
    $stmt = getPDO()->query("SELECT * FROM configuraciones WHERE clave LIKE 'whatsapp_%'");
    $whatsappConfig = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (\PDOException $e) {
    $whatsappConfig = [];
}

// Configuraciones de mensajes WhatsApp
$whatsappMensajeNuevaReserva = $whatsappConfig['whatsapp_mensaje_nueva_reserva'] ?? 'Has realizado una nueva reserva para el {fecha} a las {hora}. Te confirmaremos pronto.';
$whatsappMensajeConfirmacion = $whatsappConfig['whatsapp_mensaje_confirmacion'] ?? 'Tu reserva para el {fecha} a las {hora} ha sido confirmada. ¡Te esperamos!';
$whatsappMensajeRecordatorio = $whatsappConfig['whatsapp_mensaje_recordatorio'] ?? 'Recordatorio: Tienes una cita mañana {fecha} a las {hora}. ¡Te esperamos!';
$whatsappMensajeCancelacion = $whatsappConfig['whatsapp_mensaje_cancelacion'] ?? 'Tu reserva para el {fecha} a las {hora} ha sido cancelada.';

// Configuraciones de notificaciones
$notifyNuevaReserva = $whatsappConfig['whatsapp_notify_nueva_reserva'] ?? '1';
$notifyConfirmacion = $whatsappConfig['whatsapp_notify_confirmacion'] ?? '1';
$notifyRecordatorio = $whatsappConfig['whatsapp_notify_recordatorio'] ?? '1';
$notifyCancelacion = $whatsappConfig['whatsapp_notify_cancelacion'] ?? '1';

// Incluir la cabecera
include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">WhatsApp</h1>
</div>

<!-- Estado de la conexión -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-medium text-gray-900 flex items-center">
                <i class="ri-whatsapp-line mr-2 text-green-600"></i>
                Estado de la conexión
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                <?php if ($isConnected): ?>
                    WhatsApp está conectado y funcionando correctamente
                <?php else: ?>
                    Conecta WhatsApp para enviar notificaciones automáticas a tus clientes
                <?php endif; ?>
            </p>
        </div>
        
        <div class="flex items-center space-x-3">
            <?php if ($isConnected): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    <span class="w-2 h-2 mr-2 bg-green-500 rounded-full"></span>
                    Conectado
                </span>
            <?php else: ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                    <span class="w-2 h-2 mr-2 bg-red-500 rounded-full"></span>
                    Desconectado
                </span>
            <?php endif; ?>
            
            <button id="refreshBtn" class="text-blue-600 hover:text-blue-800 p-1 rounded-full hover:bg-blue-50">
                <i class="ri-refresh-line text-lg"></i>
            </button>
        </div>
    </div>
    
    <?php if ($isConnected): ?>
        <!-- Información de conexión activa -->
        <div class="bg-green-50 rounded-lg p-4 mb-4">
            <div class="flex items-center mb-2">
                <i class="ri-check-circle-line text-green-600 mr-2"></i>
                <span class="font-medium text-green-800">WhatsApp conectado correctamente</span>
            </div>
            <?php if (isset($whatsappStatus['lastActivity'])): ?>
                <p class="text-sm text-green-700">
                    Última actividad: <?php echo $whatsappStatus['lastActivity']; ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="flex space-x-3">
            <button id="disconnectBtn" class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <i class="ri-logout-box-line mr-2"></i>
                Desconectar WhatsApp
            </button>
        </div>
    <?php else: ?>
        <!-- Proceso de conexión -->
        <div class="space-y-4">
            <?php if ($whatsappStatus['status'] === 'qr_ready' && isset($whatsappStatus['qrCode'])): ?>
                <!-- Mostrar código QR -->
                <div id="qrContainer" class="text-center">
                    <div class="inline-block p-4 bg-white border border-gray-300 rounded-lg shadow-sm">
                        <img id="qrCode" src="<?php echo $whatsappStatus['qrCode']; ?>" alt="Código QR de WhatsApp" class="w-64 h-64">
                    </div>
                    <p class="mt-3 text-sm text-gray-600">
                        <strong>Instrucciones:</strong>
                    </p>
                    <ol class="mt-2 text-sm text-gray-600 text-left max-w-md mx-auto space-y-1">
                        <li>1. Abre WhatsApp en tu teléfono</li>
                        <li>2. Ve a <strong>Configuración > Dispositivos vinculados</strong></li>
                        <li>3. Toca <strong>Vincular un dispositivo</strong></li>
                        <li>4. Escanea este código QR</li>
                    </ol>
                </div>
            <?php elseif ($whatsappStatus['status'] === 'connecting'): ?>
                <!-- Estado de conexión -->
                <div class="text-center">
                    <div class="inline-flex items-center px-4 py-2 bg-yellow-50 text-yellow-800 rounded-lg">
                        <i class="ri-loader-line animate-spin mr-2"></i>
                        Conectando WhatsApp...
                    </div>
                    <p class="mt-2 text-sm text-gray-600">
                        Iniciando el proceso de conexión, espera un momento
                    </p>
                </div>
            <?php else: ?>
                <!-- Botón para iniciar conexión -->
                <div class="text-center">
                    <button id="connectBtn" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-sm">
                        <i class="ri-whatsapp-line mr-2 text-xl"></i>
                        Conectar WhatsApp
                    </button>
                    <p class="mt-3 text-sm text-gray-600">
                        Conecta tu cuenta de WhatsApp para enviar notificaciones automáticas
                    </p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($isConnected): ?>
<!-- Configuración de mensajes de WhatsApp -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
        <i class="ri-message-3-line mr-2 text-blue-600"></i>
        Mensajes de WhatsApp
    </h2>
    
    <form id="whatsappMessagesForm" class="space-y-6">
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
                ><?php echo htmlspecialchars($whatsappMensajeNuevaReserva); ?></textarea>
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
                ><?php echo htmlspecialchars($whatsappMensajeConfirmacion); ?></textarea>
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
                ><?php echo htmlspecialchars($whatsappMensajeRecordatorio); ?></textarea>
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
                ><?php echo htmlspecialchars($whatsappMensajeCancelacion); ?></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Variables disponibles: {nombre}, {fecha}, {hora}
                </p>
            </div>
        </div>
        
        <!-- Configuración de notificaciones automáticas -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-base font-medium text-gray-900 mb-4">Notificaciones automáticas</h3>
            
            <div class="space-y-3">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="notifyNuevaReserva"
                        name="whatsapp_notify_nueva_reserva"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        <?php echo $notifyNuevaReserva === '1' ? 'checked' : ''; ?>
                    >
                    <label for="notifyNuevaReserva" class="ml-2 block text-sm text-gray-700">
                        Enviar mensaje cuando se cree una nueva reserva
                    </label>
                </div>
                
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="notifyConfirmacion"
                        name="whatsapp_notify_confirmacion"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        <?php echo $notifyConfirmacion === '1' ? 'checked' : ''; ?>
                    >
                    <label for="notifyConfirmacion" class="ml-2 block text-sm text-gray-700">
                        Enviar mensaje cuando se confirme una reserva
                    </label>
                </div>
                
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="notifyRecordatorio"
                        name="whatsapp_notify_recordatorio"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        <?php echo $notifyRecordatorio === '1' ? 'checked' : ''; ?>
                    >
                    <label for="notifyRecordatorio" class="ml-2 block text-sm text-gray-700">
                        Enviar recordatorio antes de la cita
                    </label>
                </div>
                
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="notifyCancelacion"
                        name="whatsapp_notify_cancelacion"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        <?php echo $notifyCancelacion === '1' ? 'checked' : ''; ?>
                    >
                    <label for="notifyCancelacion" class="ml-2 block text-sm text-gray-700">
                        Enviar mensaje cuando se cancele una reserva
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Botón guardar -->
        <div class="pt-4 text-right">
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <i class="ri-save-line mr-2"></i>
                Guardar configuración de mensajes
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Información adicional sobre WhatsApp -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
        <i class="ri-information-line mr-2 text-blue-600"></i>
        Información sobre WhatsApp
    </h2>
    
    <div class="space-y-4 text-sm text-gray-600">
        <div class="flex items-start">
            <i class="ri-check-line text-green-500 mt-0.5 mr-3 flex-shrink-0"></i>
            <div>
                <p class="font-medium text-gray-900">Notificaciones automáticas</p>
                <p>Envía mensajes automáticamente cuando se crean, confirman o cancelan reservas.</p>
            </div>
        </div>
        
        <div class="flex items-start">
            <i class="ri-robot-line text-blue-500 mt-0.5 mr-3 flex-shrink-0"></i>
            <div>
                <p class="font-medium text-gray-900">Respuestas automáticas</p>
                <p>Configura respuestas automáticas en la sección <a href="/autorespuestas" class="text-blue-600 hover:text-blue-800 underline">Respuestas Automáticas</a>.</p>
            </div>
        </div>
        
        <div class="flex items-start">
            <i class="ri-history-line text-purple-500 mt-0.5 mr-3 flex-shrink-0"></i>
            <div>
                <p class="font-medium text-gray-900">Historial de mensajes</p>
                <p>Revisa todos los mensajes enviados y recibidos en la sección <a href="/mensajes" class="text-blue-600 hover:text-blue-800 underline">Historial de Mensajes</a>.</p>
            </div>
        </div>
        
        <div class="flex items-start">
            <i class="ri-shield-check-line text-green-500 mt-0.5 mr-3 flex-shrink-0"></i>
            <div>
                <p class="font-medium text-gray-900">Seguridad y privacidad</p>
                <p>Tu conexión de WhatsApp es segura y privada. Solo tú tienes acceso a los mensajes de tu cuenta.</p>
            </div>
        </div>
    </div>
</div>

<!-- Mensajes de estado -->
<div id="successMessage" class="fixed bottom-4 right-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md shadow-lg hidden">
    <div class="flex items-center">
        <i class="ri-check-line mr-2 text-green-500"></i>
        <span id="successText">Operación completada correctamente</span>
    </div>
</div>

<div id="errorMessage" class="fixed bottom-4 right-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md shadow-lg hidden">
    <div class="flex items-center">
        <i class="ri-error-warning-line mr-2 text-red-500"></i>
        <span id="errorText">Error al realizar la operación</span>
    </div>
</div>

<script>
    // Datos para el JavaScript
    const whatsappStatus = <?php echo json_encode($whatsappStatus); ?>;
    const isConnected = <?php echo json_encode($isConnected); ?>;
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>