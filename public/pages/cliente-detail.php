<?php

// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Configurar la página actual
$currentPage = 'clientes';
$pageTitle = 'ReservaBot - Detalle de Cliente';
$pageScript = 'cliente-detail';

// Obtener teléfono de la URL
$telefono = isset($_GET['telefono']) ? trim($_GET['telefono']) : '';

if (empty($telefono)) {
    header('Location: /clientes');
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

// Obtener información del cliente
try {
    // Datos generales del cliente
    $stmt = getPDO()->prepare("SELECT 
        telefono,
        nombre as ultimo_nombre,
        COUNT(id) as total_reservas,
        SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as reservas_confirmadas,
        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as reservas_pendientes,
        SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as reservas_canceladas,
        MAX(fecha) as ultima_reserva,
        MIN(created_at) as primer_contacto,
        MAX(created_at) as ultimo_contacto
        FROM reservas 
        WHERE telefono = ?
        GROUP BY telefono");
    
    $stmt->execute([$telefono]);
    $cliente = $stmt->fetch();
    
    if (!$cliente) {
        header('Location: /clientes');
        exit;
    }
    
    // Obtener historial de reservas
    $stmt = getPDO()->prepare("SELECT * FROM reservas WHERE telefono = ? ORDER BY fecha DESC, hora DESC");
    $stmt->execute([$telefono]);
    $reservas = $stmt->fetchAll();
    
    // Obtener mensajes de WhatsApp si existen
    $mensajes = [];
    try {
        // Formatear número para búsqueda
        $telefonoFormateado = preg_replace('/[^\d]/', '', $telefono);
        if (strlen($telefonoFormateado) === 9 && (substr($telefonoFormateado, 0, 1) === '6' || substr($telefonoFormateado, 0, 1) === '7' || substr($telefonoFormateado, 0, 1) === '9')) {
            $telefonoFormateado = '34' . $telefonoFormateado;
        }
        
        $stmt = getPDO()->prepare("SELECT * FROM whatsapp_messages 
                               WHERE usuario_id = ? AND phone_number = ?
                               ORDER BY timestamp_sent DESC 
                               LIMIT 50");
        $stmt->execute([$userId, $telefonoFormateado]);
        $mensajes = $stmt->fetchAll();
        
    } catch (\PDOException $e) {
        // Si las tablas de WhatsApp no existen, continuar sin mensajes
        $mensajes = [];
    }
    
} catch (\PDOException $e) {
    error_log('Error al obtener datos del cliente: ' . $e->getMessage());
    header('Location: /clientes');
    exit;
}

// Definir variables para el componente de conversación
$clientPhone = $cliente['telefono'];
$clientName = $cliente['ultimo_nombre'];

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
    <a href="/clientes" class="mr-4 p-2 rounded-full hover:bg-gray-100">
        <i class="ri-arrow-left-line text-gray-600 text-xl"></i>
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Detalle de Cliente</h1>
</div>

<!-- Información del cliente -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="flex items-start justify-between">
        <div class="flex items-center">
            <div class="flex-shrink-0 h-16 w-16">
                <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="ri-user-line text-blue-600 text-2xl"></i>
                </div>
            </div>
            <div class="ml-6">
                <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($cliente['ultimo_nombre']); ?></h2>
                <p class="text-gray-600"><?php echo htmlspecialchars($cliente['telefono']); ?></p>
                <p class="text-sm text-gray-500 mt-1">
                    Cliente desde <?php echo date('d/m/Y', strtotime($cliente['primer_contacto'])); ?>
                </p>
            </div>
        </div>
        
        <div class="flex space-x-2">
            <button 
                onclick="openWhatsAppChat('<?php echo addslashes($cliente['telefono']); ?>', '<?php echo addslashes($cliente['ultimo_nombre']); ?>')"
                class="whatsapp-button inline-flex items-center px-3 py-2 border border-green-300 shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 <?php echo !$whatsappConnected ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                <?php echo !$whatsappConnected ? 'disabled title="WhatsApp no está conectado"' : ''; ?>
            >
                <i class="ri-whatsapp-line mr-2"></i>
                Abrir Chat WhatsApp
            </button>
            <a href="/reserva-form?telefono=<?php echo urlencode($cliente['telefono']); ?>&nombre=<?php echo urlencode($cliente['ultimo_nombre']); ?>" 
               class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="ri-add-line mr-2"></i>
                Nueva Reserva
            </a>
        </div>
    </div>
</div>

<!-- Estadísticas del cliente -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="ri-calendar-line text-2xl text-blue-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Reservas</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $cliente['total_reservas']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="ri-check-line text-2xl text-green-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Confirmadas</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $cliente['reservas_confirmadas']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="ri-time-line text-2xl text-amber-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Pendientes</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $cliente['reservas_pendientes']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="ri-message-line text-2xl text-purple-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Mensajes</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo count($mensajes); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs para historial -->
<div class="border-b border-gray-200 mb-6">
    <nav class="-mb-px flex space-x-8">
        <button 
            id="reservasTab" 
            class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
        >
            <i class="ri-calendar-line mr-2"></i>
            Historial de Reservas (<?php echo count($reservas); ?>)
        </button>
        
        <button 
            id="mensajesTab" 
            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
        >
            <i class="ri-message-line mr-2"></i>
            Mensajes WhatsApp (<?php echo count($mensajes); ?>)
        </button>
    </nav>
</div>

<!-- Contenido de reservas -->
<div id="reservasContent" class="space-y-4">
    <?php if (empty($reservas)): ?>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <i class="ri-calendar-line text-gray-400 text-4xl"></i>
            <p class="mt-2 text-gray-500">No hay reservas para este cliente</p>
        </div>
    <?php else: ?>
        <?php foreach ($reservas as $reserva): ?>
            <div class="bg-white rounded-lg shadow-sm border-l-4 <?php echo $reserva['estado'] === 'confirmada' ? 'border-green-500' : ($reserva['estado'] === 'pendiente' ? 'border-amber-500' : 'border-red-500'); ?>">
                <div class="p-4">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($reserva['fecha'])); ?> - <?php echo substr($reserva['hora'], 0, 5); ?>
                                </h3>
                                <span class="ml-3 px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php echo $reserva['estado'] === 'confirmada' ? 'bg-green-100 text-green-800' : 
                                              ($reserva['estado'] === 'pendiente' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucfirst($reserva['estado']); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($reserva['mensaje'])): ?>
                                <p class="text-sm text-gray-600 mb-2">
                                    <i class="ri-message-2-line mr-1"></i>
                                    <?php echo htmlspecialchars($reserva['mensaje']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <p class="text-xs text-gray-500">
                                Creada el <?php echo date('d/m/Y H:i', strtotime($reserva['created_at'])); ?>
                            </p>
                        </div>
                        
                        <div class="flex space-x-2">
                            <a href="/reserva-detail?id=<?php echo $reserva['id']; ?>" 
                               class="inline-flex items-center p-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="ri-eye-line"></i>
                            </a>
                            <a href="/reserva-form?id=<?php echo $reserva['id']; ?>" 
                               class="inline-flex items-center p-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="ri-edit-line"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Contenido de mensajes WhatsApp (versión simplificada para el tab) -->
<div id="mensajesContent" class="hidden">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <?php if (empty($mensajes)): ?>
            <div class="text-center py-8">
                <i class="ri-chat-3-line text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-700 mb-2">No hay mensajes</h3>
                <p class="text-gray-500 mb-4">Aún no hay conversación con este cliente por WhatsApp</p>
                <?php if ($whatsappConnected): ?>
                    <button 
                        onclick="openWhatsAppChat('<?php echo addslashes($cliente['telefono']); ?>', '<?php echo addslashes($cliente['ultimo_nombre']); ?>')"
                        class="whatsapp-button text-white px-4 py-2 rounded-lg font-medium inline-flex items-center"
                    >
                        <i class="ri-whatsapp-line mr-2"></i>
                        Iniciar Conversación
                    </button>
                <?php else: ?>
                    <a href="/whatsapp" class="text-blue-600 hover:text-blue-700 font-medium">
                        Conectar WhatsApp primero →
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="mb-4 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Conversación WhatsApp</h3>
                <p class="text-sm text-gray-600">
                    <?php echo count($mensajes); ?> mensajes intercambiados
                </p>
                <button 
                    onclick="openWhatsAppChat('<?php echo addslashes($cliente['telefono']); ?>', '<?php echo addslashes($cliente['ultimo_nombre']); ?>')"
                    class="mt-3 whatsapp-button text-white px-4 py-2 rounded-lg font-medium inline-flex items-center"
                >
                    <i class="ri-whatsapp-line mr-2"></i>
                    Abrir Chat Completo
                </button>
            </div>
            
            <!-- Vista previa de mensajes recientes -->
            <div class="space-y-3 max-h-96 overflow-y-auto">
                <?php foreach (array_slice($mensajes, 0, 10) as $mensaje): ?>
                    <div class="flex <?php echo $mensaje['direction'] === 'outgoing' ? 'justify-end' : 'justify-start'; ?>">
                        <div class="max-w-xs lg:max-w-md">
                            <div class="<?php echo $mensaje['direction'] === 'outgoing' 
                                ? 'bg-green-100 text-green-800 rounded-br-none' 
                                : 'bg-gray-100 text-gray-800 rounded-bl-none'; ?> rounded-lg px-4 py-2">
                                
                                <p class="text-sm"><?php echo nl2br(htmlspecialchars($mensaje['message_text'])); ?></p>
                            </div>
                            
                            <p class="text-xs text-gray-500 mt-1 <?php echo $mensaje['direction'] === 'outgoing' ? 'text-right' : 'text-left'; ?>">
                                <?php echo date('d/m/Y H:i', strtotime($mensaje['timestamp_sent'])); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($mensajes) > 10): ?>
                    <div class="text-center py-3 border-t">
                        <p class="text-sm text-gray-500">
                            +<?php echo count($mensajes) - 10; ?> mensajes más
                        </p>
                        <button 
                            onclick="openWhatsAppChat('<?php echo addslashes($cliente['telefono']); ?>', '<?php echo addslashes($cliente['ultimo_nombre']); ?>')"
                            class="text-green-600 hover:text-green-700 text-sm font-medium"
                        >
                            Ver conversación completa →
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// Incluir componente de conversación antes de los scripts
include 'components/conversacion.php';
?>

<script>
// JavaScript para los tabs
document.addEventListener('DOMContentLoaded', function() {
    // Gestión de tabs
    const reservasTab = document.getElementById('reservasTab');
    const mensajesTab = document.getElementById('mensajesTab');
    const reservasContent = document.getElementById('reservasContent');
    const mensajesContent = document.getElementById('mensajesContent');
    
    if (reservasTab && mensajesTab) {
        reservasTab.addEventListener('click', function() {
            // Activar tab de reservas
            reservasTab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            reservasTab.classList.add('border-blue-500', 'text-blue-600');
            
            // Desactivar tab de mensajes
            mensajesTab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            mensajesTab.classList.remove('border-blue-500', 'text-blue-600');
            
            // Mostrar contenido de reservas
            reservasContent.classList.remove('hidden');
            if (mensajesContent) mensajesContent.classList.add('hidden');
        });
        
        mensajesTab.addEventListener('click', function() {
            // Activar tab de mensajes
            mensajesTab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            mensajesTab.classList.add('border-blue-500', 'text-blue-600');
            
            // Desactivar tab de reservas
            reservasTab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            reservasTab.classList.remove('border-blue-500', 'text-blue-600');
            
            // Mostrar contenido de mensajes
            if (mensajesContent) mensajesContent.classList.remove('hidden');
            reservasContent.classList.add('hidden');
        });
    }
});
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>