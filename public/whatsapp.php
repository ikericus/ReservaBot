<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';
require_once 'includes/whatsapp-functions.php';

// Configurar la página actual
$currentPage = 'whatsapp';
$pageTitle = 'ReservaBot - WhatsApp';
$pageScript = 'whatsapp';

// Obtener la configuración de WhatsApp
try {
    $stmt = $pdo->query("SELECT * FROM configuracion WHERE clave LIKE 'whatsapp_%'");
    $whatsappConfig = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (\PDOException $e) {
    $whatsappConfig = [];
}

// Establecer valores predeterminados si no existen
$whatsappEnabled = $whatsappConfig['whatsapp_enabled'] ?? 'false';
$whatsappServerUrl = $whatsappConfig['whatsapp_server_url'] ?? 'http://localhost:3000';
$whatsappApiKey = $whatsappConfig['whatsapp_api_key'] ?? '';
$notifyNuevaReserva = $whatsappConfig['whatsapp_notify_nueva_reserva'] ?? 'true';
$notifyConfirmacion = $whatsappConfig['whatsapp_notify_confirmacion'] ?? 'true';
$notifyRecordatorio = $whatsappConfig['whatsapp_notify_recordatorio'] ?? 'true';
$tiempoRecordatorio = $whatsappConfig['whatsapp_tiempo_recordatorio'] ?? '24';

// Obtener estado actual de la conexión de WhatsApp
$whatsappStatus = checkWhatsAppStatus($whatsappServerUrl, $whatsappApiKey);

// Incluir la cabecera
include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">WhatsApp</h1>
</div>

<!-- Estado de la conexión -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
        <i class="ri-whatsapp-line mr-2 text-green-600"></i>
        Estado de la conexión
    </h2>
    
    <div class="flex items-center mb-4">
        <div class="mr-3">
            <?php if ($whatsappStatus['connected']): ?>
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
        </div>
        
        <button id="refreshStatus" class="text-blue-600 hover:text-blue-800">
            <i class="ri-refresh-line"></i> Actualizar
        </button>
    </div>
    
    <?php if ($whatsappStatus['connected']): ?>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex flex-col md:flex-row md:justify-between">
                <div class="mb-3 md:mb-0">
                    <div class="text-sm text-gray-500">Número registrado</div>
                    <div class="font-medium"><?php echo htmlspecialchars($whatsappStatus['phone'] ?? 'No disponible'); ?></div>
                </div>
                <div class="mb-3 md:mb-0">
                    <div class="text-sm text-gray-500">Nombre del usuario</div>
                    <div class="font-medium"><?php echo htmlspecialchars($whatsappStatus['name'] ?? 'No disponible'); ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Última actualización</div>
                    <div class="font-medium"><?php echo date('d/m/Y H:i:s'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 text-sm text-gray-500">
            Para cerrar sesión y conectar otro número, haga clic en el botón de abajo.
        </div>
        
        <div class="mt-2">
            <button id="logoutBtn" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <i class="ri-logout-box-line mr-2"></i>
                Cerrar sesión en WhatsApp
            </button>
        </div>
    <?php else: ?>
        <div class="mt-4 text-sm text-gray-500">
            Para conectarse a WhatsApp, haga clic en el botón de abajo y escanee el código QR con su teléfono.
        </div>
        
        <div class="mt-4">
            <button id="connectBtn" class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <i class="ri-qr-code-line mr-2"></i>
                Conectar WhatsApp
            </button>
        </div>
        
        <!-- Contenedor para el código QR (inicialmente oculto) -->
        <div id="qrContainer" class="mt-6 hidden">
            <div class="bg-white p-4 rounded-lg border border-gray-300 inline-block">
                <div id="qrCode" class="w-64 h-64 flex items-center justify-center text-gray-500">
                    Cargando código QR...
                </div>
            </div>
            <p class="mt-2 text-sm text-gray-500">
                Escanee este código con la aplicación de WhatsApp en su teléfono para conectarse.
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- Configuración de WhatsApp -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
        <i class="ri-settings-line mr-2 text-blue-600"></i>
        Configuración de WhatsApp
    </h2>
    
    <form id="whatsappConfigForm" class="space-y-6">
        <!-- Activar/Desactivar integración -->
        <div class="border-b border-gray-200 pb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-base font-medium text-gray-900">Integración con WhatsApp</h3>
                    <p class="text-sm text-gray-500" id="whatsappEnabledDescription">
                        <?php echo $whatsappEnabled === 'true' 
                            ? 'La integración con WhatsApp está activada' 
                            : 'La integración con WhatsApp está desactivada'; ?>
                    </p>
                </div>
                <div class="flex items-center">
                    <span class="mr-3 text-sm font-medium text-gray-700" id="whatsappEnabledLabel">
                        <?php echo $whatsappEnabled === 'true' ? 'Activado' : 'Desactivado'; ?>
                    </span>
                    <button 
                        id="toggleWhatsapp" 
                        type="button"
                        class="relative inline-flex h-6 w-11 items-center rounded-full 
                        <?php echo $whatsappEnabled === 'true' ? 'bg-blue-600' : 'bg-gray-200'; ?> 
                        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <span 
                            class="inline-block h-4 w-4 transform rounded-full bg-white 
                            <?php echo $whatsappEnabled === 'true' ? 'translate-x-6' : 'translate-x-1'; ?> 
                            transition-transform"
                        ></span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Configuración del servidor -->
        <div class="space-y-4">
            <div>
                <label for="whatsappServerUrl" class="block text-sm font-medium text-gray-700 mb-1">
                    URL del servidor WhatsApp
                </label>
                <input
                    type="url"
                    id="whatsappServerUrl"
                    name="whatsapp_server_url"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    value="<?php echo htmlspecialchars($whatsappServerUrl); ?>"
                    placeholder="http://localhost:3000"
                >
                <p class="mt-1 text-xs text-gray-500">
                    Dirección del servidor Node.js que maneja la conexión con WhatsApp
                </p>
            </div>
            
            <div>
                <label for="whatsappApiKey" class="block text-sm font-medium text-gray-700 mb-1">
                    Clave API del servidor (opcional)
                </label>
                <input
                    type="password"
                    id="whatsappApiKey"
                    name="whatsapp_api_key"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    value="<?php echo htmlspecialchars($whatsappApiKey); ?>"
                    placeholder="Clave secreta del servidor"
                >
            </div>
        </div>
        
        <!-- Configuración de notificaciones -->
        <div class="space-y-4 border-t border-gray-200 pt-6">
            <h3 class="text-base font-medium text-gray-900">Notificaciones automáticas</h3>
            
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="whatsappNotifyNuevaReserva"
                    name="whatsapp_notify_nueva_reserva"
                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    <?php echo $notifyNuevaReserva === 'true' ? 'checked' : ''; ?>
                >
                <label for="whatsappNotifyNuevaReserva" class="ml-2 block text-sm text-gray-700">
                    Enviar notificación al crear una nueva reserva
                </label>
            </div>
            
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="whatsappNotifyConfirmacion"
                    name="whatsapp_notify_confirmacion"
                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    <?php echo $notifyConfirmacion === 'true' ? 'checked' : ''; ?>
                >
                <label for="whatsappNotifyConfirmacion" class="ml-2 block text-sm text-gray-700">
                    Enviar notificación al confirmar una reserva
                </label>
            </div>
            
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="whatsappNotifyRecordatorio"
                    name="whatsapp_notify_recordatorio"
                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    <?php echo $notifyRecordatorio === 'true' ? 'checked' : ''; ?>
                >
                <label for="whatsappNotifyRecordatorio" class="ml-2 block text-sm text-gray-700">
                    Enviar recordatorio antes de la cita
                </label>
            </div>
            
            <div class="pl-6">
                <label for="whatsappTiempoRecordatorio" class="block text-sm font-medium text-gray-700 mb-1">
                    Horas de antelación para el recordatorio
                </label>
                <select
                    id="whatsappTiempoRecordatorio"
                    name="whatsapp_tiempo_recordatorio"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    <?php echo $notifyRecordatorio === 'true' ? '' : 'disabled'; ?>
                >
                    <option value="1" <?php echo $tiempoRecordatorio == 1 ? 'selected' : ''; ?>>1 hora antes</option>
                    <option value="2" <?php echo $tiempoRecordatorio == 2 ? 'selected' : ''; ?>>2 horas antes</option>
                    <option value="3" <?php echo $tiempoRecordatorio == 3 ? 'selected' : ''; ?>>3 horas antes</option>
                    <option value="6" <?php echo $tiempoRecordatorio == 6 ? 'selected' : ''; ?>>6 horas antes</option>
                    <option value="12" <?php echo $tiempoRecordatorio == 12 ? 'selected' : ''; ?>>12 horas antes</option>
                    <option value="24" <?php echo $tiempoRecordatorio == 24 ? 'selected' : ''; ?>>24 horas antes</option>
                    <option value="48" <?php echo $tiempoRecordatorio == 48 ? 'selected' : ''; ?>>2 días antes</option>
                </select>
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

<!-- Mensajes de estado -->
<div id="saveSuccessMessage" class="fixed bottom-4 right-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md shadow-lg hidden">
    <div class="flex items-center">
        <i class="ri-check-line mr-2 text-green-500"></i>
        <span>Configuración guardada correctamente</span>
    </div>
</div>

<div id="errorMessage" class="fixed bottom-4 right-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md shadow-lg hidden">
    <div class="flex items-center">
        <i class="ri-error-warning-line mr-2 text-red-500"></i>
        <span id="errorText">Error al guardar la configuración</span>
    </div>
</div>

<script>
    // Datos para el JavaScript
    const initialWhatsappStatus = <?php echo json_encode($whatsappStatus); ?>;
    const whatsappServerUrl = <?php echo json_encode($whatsappServerUrl); ?>;
    const whatsappApiKey = <?php echo json_encode($whatsappApiKey); ?>;
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>