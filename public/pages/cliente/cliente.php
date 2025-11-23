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
                   class="inline-flex items-center justify-center px-3 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white text-sm font-medium rounded-lg hover:from-purple-700 hover:to-blue-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl w-full sm:w-auto">
                    <i class="ri-add-line mr-2"></i>
                    Nueva reserva para <?php echo htmlspecialchars($cliente['ultimo_nombre']); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Estadísticas del cliente -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4 mb-4 md:mb-6">
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
    </div>

    <!-- Encabezado de historial -->
    <div class="mb-4 md:mb-6">
        <h3 class="text-lg md:text-xl font-semibold text-gray-900 flex items-center">
            <i class="ri-calendar-line mr-2"></i>
            <span>Historial de Reservas</span>
            <span class="ml-2 text-sm font-normal text-gray-500">(<?php echo count($reservas); ?>)</span>
        </h3>
    </div>

    <!-- Contenido de reservas -->
    <div class="space-y-3 md:space-y-4">
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

</div>

<?php 
    // Incluir componente de conversación antes de los scripts
    include 'components/conversacion.php';
?>

<?php include 'includes/footer.php'; ?>