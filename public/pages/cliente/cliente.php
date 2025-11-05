<?php
// pages/cliente.php

$currentPage = 'clientes';
$pageTitle = 'ReservaBot - Detalle de Cliente';
$pageScript = 'cliente';

// Obtener teléfono de la URL
$telefono = isset($_GET['telefono']) ? trim($_GET['telefono']) : '';

if (empty($telefono)) {
    header('Location: /clientes');
    exit;
}

// Obtener usuario actual
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

// Obtener datos del cliente usando domain
$cliente = null;
$reservas = [];
$mensajes = [];

try {
    $clienteDomain = getContainer()->getClienteDomain();
    $resultado = $clienteDomain->obtenerDetalleCliente($telefono, $userId);
    
    $cliente = $resultado['cliente']->toArray();
    $reservas = $resultado['reservas'];
    
} catch (\DomainException $e) {
    setFlashError($e->getMessage());
    header('Location: /clientes');
    exit;
} catch (\Exception $e) {
    error_log('Error al obtener detalle cliente: ' . $e->getMessage());
    setFlashError('Error al cargar datos del cliente');
    header('Location: /clientes');
    exit;
}

// Verificar estado de WhatsApp
$whatsappConnected = false;
try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    $config = $whatsappDomain->obtenerConfiguracion($userId);    
    $whatsappConnected = $config->estaConectado();
} catch (\Exception $e) {
    // WhatsApp no configurado
    error_log('No se pudo obtener configuración de WhatsApp: ' . $e->getMessage());
}

// Obtener mensajes de WhatsApp si existen
try {
    $mensajesEntities = $whatsappDomain->obtenerMensajesConversacion($userId, $telefono, 50);
    $mensajes = array_map(fn($r) => $r->toArray(), $mensajesEntities);
} catch (\Exception $e) {
    // Si no hay WhatsApp, continuar sin mensajes
    error_log('No se pudieron obtener mensajes de WhatsApp: ' . $e->getMessage());
}

// Definir variables para el componente de conversación
$clientPhone = $cliente['telefono'];
$clientName = $cliente['ultimo_nombre'];

include 'includes/header.php';
?>

<style>
.container-max-width {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

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

/* Responsive improvements */
@media (max-width: 768px) {
    .container-max-width {
        padding: 0 0.75rem;
    }
    
    /* Eliminar scroll horizontal */
    body {
        overflow-x: hidden;
    }
    
    /* Ajustar tabs para mobile */
    .tabs-mobile {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE/Edge */
    }
    
    .tabs-mobile::-webkit-scrollbar {
        display: none; /* Chrome/Safari */
    }
    
    .tabs-mobile nav {
        min-width: min-content;
    }
    
    .tabs-mobile button {
        white-space: nowrap;
        font-size: 0.875rem;
        padding: 0.75rem 0.5rem;
    }
}

@media (max-width: 640px) {
    /* Hacer el texto más pequeño en pantallas muy pequeñas */
    .text-responsive {
        font-size: 0.875rem;
    }
    
    /* Iconos más pequeños */
    .icon-responsive {
        font-size: 1.25rem !important;
    }
}
</style>

<div class="container-max-width">

    <!-- Información del cliente -->
    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-4 md:mb-6">
        <div class="flex flex-col md:flex-row items-start md:items-start justify-between gap-4">
            <div class="flex items-center w-full md:w-auto">
                <div class="flex-shrink-0 h-12 w-12 md:h-16 md:w-16">
                    <div class="h-12 w-12 md:h-16 md:w-16 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="ri-user-line text-blue-600 text-xl md:text-2xl"></i>
                    </div>
                </div>
                <div class="ml-4 md:ml-6 flex-1 min-w-0">
                    <h2 class="text-lg md:text-xl font-bold text-gray-900 truncate"><?php echo htmlspecialchars($cliente['ultimo_nombre']); ?></h2>
                    <p class="text-sm md:text-base text-gray-600 truncate"><?php echo htmlspecialchars($cliente['telefono']); ?></p>
                    <p class="text-xs md:text-sm text-gray-500 mt-1">
                        Cliente desde <?php echo date('d/m/Y', strtotime($cliente['primer_contacto'])); ?>
                    </p>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                <button 
                    onclick="openWhatsAppChat('<?php echo addslashes($cliente['telefono']); ?>', '<?php echo addslashes($cliente['ultimo_nombre']); ?>')"
                    class="whatsapp-button inline-flex items-center justify-center px-3 py-2 border border-green-300 shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 w-full sm:w-auto <?php echo !$whatsappConnected ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                    <?php echo !$whatsappConnected ? 'disabled title="WhatsApp no está conectado"' : ''; ?>
                >
                    <i class="ri-whatsapp-line mr-2"></i>
                    <span class="hidden sm:inline">Abrir Chat WhatsApp</span>
                    <span class="sm:hidden">Chat WhatsApp</span>
                </button>
                <a href="/reserva-form?telefono=<?php echo urlencode($cliente['telefono']); ?>&nombre=<?php echo urlencode($cliente['ultimo_nombre']); ?>" 
                   class="inline-flex items-center justify-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full sm:w-auto">
                    <i class="ri-add-line mr-2"></i>
                    Nueva Reserva
                </a>
            </div>
        </div>
    </div>

    <!-- Estadísticas del cliente -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-4 md:p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="ri-calendar-line text-xl md:text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-3 md:ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-xs md:text-sm font-medium text-gray-500 truncate">Total Reservas</dt>
                            <dd class="text-base md:text-lg font-medium text-gray-900"><?php echo $cliente['total_reservas']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-4 md:p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="ri-check-line text-xl md:text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-3 md:ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-xs md:text-sm font-medium text-gray-500 truncate">Confirmadas</dt>
                            <dd class="text-base md:text-lg font-medium text-gray-900"><?php echo $cliente['reservas_confirmadas']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-4 md:p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="ri-time-line text-xl md:text-2xl text-amber-600"></i>
                    </div>
                    <div class="ml-3 md:ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-xs md:text-sm font-medium text-gray-500 truncate">Pendientes</dt>
                            <dd class="text-base md:text-lg font-medium text-gray-900"><?php echo $cliente['reservas_pendientes']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-4 md:p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="ri-whatsapp-line text-xl md:text-2xl nav-icon h-5 w-5 text-green-500"></i>
                    </div>
                    <div class="ml-3 md:ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-xs md:text-sm font-medium text-gray-500 truncate">Mensajes</dt>
                            <dd class="text-base md:text-lg font-medium text-gray-900"><?php echo count($mensajes); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs para historial -->
    <div class="border-b border-gray-200 mb-4 md:mb-6 tabs-mobile">
        <nav class="-mb-px flex space-x-4 md:space-x-8">
            <button 
                id="reservasTab" 
                class="border-blue-500 text-blue-600 whitespace-nowrap py-3 md:py-4 px-1 border-b-2 font-medium text-xs md:text-sm"
            >
                <i class="ri-calendar-line mr-1 md:mr-2"></i>
                <span class="hidden sm:inline">Historial de Reservas</span>
                <span class="sm:hidden">Reservas</span>
                (<?php echo count($reservas); ?>)
            </button>
            
            <button 
                id="mensajesTab" 
                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 md:py-4 px-1 border-b-2 font-medium text-xs md:text-sm"
            >
                <i class="ri-message-line mr-1 md:mr-2"></i>
                <span class="hidden sm:inline">Mensajes WhatsApp</span>
                <span class="sm:hidden">Mensajes</span>
                (<?php echo count($mensajes); ?>)
            </button>
        </nav>
    </div>

    <!-- Contenido de reservas -->
    <div id="reservasContent" class="space-y-3 md:space-y-4">
        <?php if (empty($reservas)): ?>
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 text-center">
                <i class="ri-calendar-line text-gray-400 text-3xl md:text-4xl"></i>
                <p class="mt-2 text-sm md:text-base text-gray-500">No hay reservas para este cliente</p>
            </div>
        <?php else: ?>
            <?php foreach ($reservas as $reserva): ?>
                <div class="bg-white rounded-lg shadow-sm border-l-4 <?php echo $reserva['estado'] === 'confirmada' ? 'border-green-500' : ($reserva['estado'] === 'pendiente' ? 'border-amber-500' : 'border-red-500'); ?>">
                    <div class="p-3 md:p-4">
                        <div class="flex flex-col md:flex-row justify-between md:items-start gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <h3 class="text-base md:text-lg font-medium text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($reserva['fecha'])); ?> - <?php echo substr($reserva['hora'], 0, 5); ?>
                                    </h3>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php echo $reserva['estado'] === 'confirmada' ? 'bg-green-100 text-green-800' : 
                                                ($reserva['estado'] === 'pendiente' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'); ?>">
                                        <?php echo ucfirst($reserva['estado']); ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($reserva['mensaje'])): ?>
                                    <p class="text-xs md:text-sm text-gray-600 mb-2 break-words">
                                        <i class="ri-message-2-line mr-1"></i>
                                        <?php echo htmlspecialchars($reserva['mensaje']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <p class="text-xs text-gray-500">
                                    Creada el <?php echo date('d/m/Y H:i', strtotime($reserva['fecha'])); ?>
                                </p>
                            </div>
                            
                            <div class="flex gap-2 justify-end md:justify-start">
                                <a href="/reserva?id=<?php echo $reserva['id']; ?>" 
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
    <div id="mensajesContent" class="hidden">
        <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
            <?php if (empty($mensajes)): ?>
                <div class="text-center py-6 md:py-8">
                    <i class="ri-chat-3-line text-gray-400 text-3xl md:text-4xl mb-3 md:mb-4"></i>
                    <h3 class="text-base md:text-lg font-medium text-gray-700 mb-2">No hay mensajes</h3>
                    <p class="text-sm md:text-base text-gray-500 mb-3 md:mb-4">Aún no hay conversación con este cliente por WhatsApp</p>
                    <?php if ($whatsappConnected): ?>
                        <button 
                            onclick="openWhatsAppChat('<?php echo addslashes($cliente['telefono']); ?>', '<?php echo addslashes($cliente['ultimo_nombre']); ?>')"
                            class="whatsapp-button text-white px-4 py-2 rounded-lg font-medium inline-flex items-center text-sm md:text-base"
                        >
                            <i class="ri-whatsapp-line mr-2"></i>
                            Iniciar Conversación
                        </button>
                    <?php else: ?>
                        <a href="/whatsapp" class="text-blue-600 hover:text-blue-700 font-medium text-sm md:text-base">
                            Conectar WhatsApp primero →
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="mb-4 text-center">
                    <h3 class="text-base md:text-lg font-medium text-gray-900 mb-2">Conversación WhatsApp</h3>
                    <p class="text-xs md:text-sm text-gray-600">
                        <?php echo count($mensajes); ?> mensajes intercambiados
                    </p>
                    <button 
                        onclick="openWhatsAppChat('<?php echo addslashes($cliente['telefono']); ?>', '<?php echo addslashes($cliente['ultimo_nombre']); ?>')"
                        class="mt-3 whatsapp-button text-white px-3 md:px-4 py-2 rounded-lg font-medium inline-flex items-center text-sm md:text-base"
                    >
                        <i class="ri-whatsapp-line mr-2"></i>
                        Abrir Chat Completo
                    </button>
                </div>
                
                <!-- Vista previa de mensajes recientes -->
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php foreach (array_slice($mensajes, 0, 10) as $mensaje): ?>
                        <div class="flex <?php echo $mensaje['direction'] === 'outgoing' ? 'justify-end' : 'justify-start'; ?>">
                            <div class="max-w-[85%] sm:max-w-xs lg:max-w-md">
                                <div class="<?php echo $mensaje['direction'] === 'outgoing' 
                                    ? 'bg-green-100 text-green-800 rounded-br-none' 
                                    : 'bg-gray-100 text-gray-800 rounded-bl-none'; ?> rounded-lg px-3 md:px-4 py-2">
                                    
                                    <p class="text-xs md:text-sm break-words"><?php echo nl2br(htmlspecialchars($mensaje['message_text'])); ?></p>
                                </div>
                                
                                <p class="text-xs text-gray-500 mt-1 <?php echo $mensaje['direction'] === 'outgoing' ? 'text-right' : 'text-left'; ?>">
                                    <?php echo date('d/m/Y H:i', strtotime($mensaje['timestamp_sent'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($mensajes) > 10): ?>
                        <div class="text-center py-3 border-t">
                            <p class="text-xs md:text-sm text-gray-500">
                                +<?php echo count($mensajes) - 10; ?> mensajes más
                            </p>
                            <button 
                                onclick="openWhatsAppChat('<?php echo addslashes($cliente['telefono']); ?>', '<?php echo addslashes($cliente['ultimo_nombre']); ?>')"
                                class="text-green-600 hover:text-green-700 text-xs md:text-sm font-medium"
                            >
                                Ver conversación completa →
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php 
    // Incluir componente de conversación antes de los scripts
    include 'components/conversacion.php';
?>

<script>
// JavaScript para los tabs
document.addEventListener('DOMContentLoaded', function() {
    const reservasTab = document.getElementById('reservasTab');
    const mensajesTab = document.getElementById('mensajesTab');
    const reservasContent = document.getElementById('reservasContent');
    const mensajesContent = document.getElementById('mensajesContent');
    
    if (reservasTab && mensajesTab) {
        reservasTab.addEventListener('click', function() {
            reservasTab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            reservasTab.classList.add('border-blue-500', 'text-blue-600');
            
            mensajesTab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            mensajesTab.classList.remove('border-blue-500', 'text-blue-600');
            
            reservasContent.classList.remove('hidden');
            if (mensajesContent) mensajesContent.classList.add('hidden');
        });
        
        mensajesTab.addEventListener('click', function() {
            mensajesTab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            mensajesTab.classList.add('border-blue-500', 'text-blue-600');
            
            reservasTab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            reservasTab.classList.remove('border-blue-500', 'text-blue-600');
            
            if (mensajesContent) mensajesContent.classList.remove('hidden');
            reservasContent.classList.add('hidden');
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>