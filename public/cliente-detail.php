<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';

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

// Obtener información del cliente
try {
    // Datos generales del cliente
    $stmt = $pdo->prepare("SELECT 
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
    $stmt = $pdo->prepare("SELECT * FROM reservas WHERE telefono = ? ORDER BY fecha DESC, hora DESC");
    $stmt->execute([$telefono]);
    $reservas = $stmt->fetchAll();
    
    // Obtener mensajes de WhatsApp si existen
    $mensajes = [];
    try {
        // Buscar mensajes por teléfono (pueden estar en diferentes formatos)
        $telefonoFormats = [
            $telefono,
            str_replace(['+', ' ', '-', '(', ')'], '', $telefono),
            '+34' . str_replace(['+', ' ', '-', '(', ')'], '', $telefono),
            str_replace(['+', ' ', '-', '(', ')'], '', $telefono) . '@c.us'
        ];
        
        $placeholders = str_repeat('?,', count($telefonoFormats) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT mw.*, cw.nombre as chat_nombre 
                               FROM mensajes_whatsapp mw
                               LEFT JOIN chats_whatsapp cw ON mw.chat_id = cw.chat_id
                               WHERE mw.chat_id IN ($placeholders)
                               OR cw.telefono IN ($placeholders)
                               ORDER BY mw.timestamp DESC 
                               LIMIT 50");
        
        $allParams = array_merge($telefonoFormats, $telefonoFormats);
        $stmt->execute($allParams);
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

// Incluir la cabecera
include 'includes/header.php';
?>

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
            <button class="inline-flex items-center px-3 py-2 border border-green-300 shadow-sm text-sm font-medium rounded-md text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <i class="ri-whatsapp-line mr-2"></i>
                Enviar WhatsApp
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
        
        <?php if (!empty($mensajes)): ?>
        <button 
            id="mensajesTab" 
            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
        >
            <i class="ri-message-line mr-2"></i>
            Mensajes WhatsApp (<?php echo count($mensajes); ?>)
        </button>
        <?php endif; ?>
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

<!-- Contenido de mensajes WhatsApp -->
<?php if (!empty($mensajes)): ?>
<div id="mensajesContent" class="hidden">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Historial de Conversación WhatsApp</h3>
            <p class="text-sm text-gray-500">Últimos <?php echo count($mensajes); ?> mensajes</p>
        </div>
        
        <div class="p-4 max-h-96 overflow-y-auto">
            <div class="space-y-4">
                <?php foreach ($mensajes as $mensaje): ?>
                    <div class="flex <?php echo $mensaje['direction'] === 'sent' ? 'justify-end' : 'justify-start'; ?>">
                        <div class="max-w-xs lg:max-w-md">
                            <div class="<?php echo $mensaje['direction'] === 'sent' 
                                ? 'bg-blue-500 text-white rounded-br-none' 
                                : 'bg-gray-200 text-gray-800 rounded-bl-none'; ?> rounded-lg px-4 py-2">
                                
                                <p class="text-sm"><?php echo nl2br(htmlspecialchars($mensaje['body'])); ?></p>
                                
                                <?php if ($mensaje['is_auto_response']): ?>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs bg-yellow-200 text-yellow-800 rounded">
                                        Respuesta automática
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-xs text-gray-500 mt-1 <?php echo $mensaje['direction'] === 'sent' ? 'text-right' : 'text-left'; ?>">
                                <?php echo date('d/m/Y H:i', $mensaje['timestamp']); ?>
                                <?php if ($mensaje['direction'] === 'sent'): ?>
                                    <i class="ri-check-line ml-1"></i>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Panel para enviar nuevo mensaje -->
        <div class="border-t border-gray-200 p-4">
            <form id="enviarMensajeForm" class="flex space-x-3">
                <div class="flex-1">
                    <textarea
                        id="nuevoMensaje"
                        rows="2"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        placeholder="Escribe un mensaje..."
                    ></textarea>
                </div>
                <div class="flex flex-col justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        <i class="ri-send-plane-line mr-1"></i>
                        Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- JavaScript para los tabs -->
<script>
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Manejar envío de mensajes
    const enviarMensajeForm = document.getElementById('enviarMensajeForm');
    if (enviarMensajeForm) {
        enviarMensajeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const mensaje = document.getElementById('nuevoMensaje').value.trim();
            if (!mensaje) {
                alert('Por favor escribe un mensaje');
                return;
            }
            
            // Aquí puedes implementar el envío del mensaje vía WhatsApp
            // Por ahora solo mostramos una confirmación
            if (confirm('¿Enviar mensaje por WhatsApp a <?php echo htmlspecialchars($cliente['telefono']); ?>?')) {
                alert('Funcionalidad de envío de WhatsApp pendiente de implementar');
                document.getElementById('nuevoMensaje').value = '';
            }
        });
    }
});
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>