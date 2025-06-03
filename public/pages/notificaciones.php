<?php
// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Configurar la página actual
$currentPage = 'notificaciones';
$pageTitle = 'ReservaBot - Notificaciones';
$pageScript = 'notificaciones';

// Parámetros de filtrado y paginación
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 25;
$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : '';
$offset = ($page - 1) * $perPage;

// Obtener usuario actual
$usuario = getAuthenticatedUser();
$userId = $usuario['id'];

// Construir consulta base
$whereClause = "WHERE usuario_id = ?";
$params = [$userId];

// Aplicar filtros
if (!empty($tipo)) {
    $whereClause .= " AND tipo = ?";
    $params[] = $tipo;
}

if (!empty($fecha)) {
    $whereClause .= " AND DATE(created_at) = ?";
    $params[] = $fecha;
}

try {
    // Contar total de notificaciones
    $countQuery = "SELECT COUNT(*) FROM notificaciones $whereClause";
    $stmt = getPDO()->prepare($countQuery);
    $stmt->execute($params);
    $totalNotificaciones = $stmt->fetchColumn();
    
    // Obtener notificaciones paginadas
    $query = "SELECT * FROM notificaciones $whereClause ORDER BY created_at DESC LIMIT ?, ?";
    $fullParams = array_merge($params, [$offset, $perPage]);
    $stmt = getPDO()->prepare($query);
    $stmt->execute($fullParams);
    $notificaciones = $stmt->fetchAll();
    
    // Calcular total de páginas
    $totalPages = ceil($totalNotificaciones / $perPage);
    
    // Marcar notificaciones como leídas al cargar la página
    $updateStmt = getPDO()->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ? AND leida = 0");
    $updateStmt->execute([$userId]);
    
    // Obtener tipos disponibles para el filtro
    $tiposStmt = getPDO()->prepare("SELECT DISTINCT tipo FROM notificaciones WHERE usuario_id = ? ORDER BY tipo");
    $tiposStmt->execute([$userId]);
    $tiposDisponibles = $tiposStmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (\PDOException $e) {
    error_log('Error al obtener notificaciones: ' . $e->getMessage());
    $notificaciones = [];
    $totalNotificaciones = 0;
    $totalPages = 1;
    $tiposDisponibles = [];
}

// Función para formatear el mensaje según el tipo
function formatearMensajeNotificacion($notificacion) {
    $data = json_decode($notificacion['data'], true) ?: [];
    
    switch ($notificacion['tipo']) {
        case 'nueva_reserva':
            return "Nueva reserva de <strong>{$data['nombre']}</strong> para el {$data['fecha']} a las {$data['hora']}";
        case 'reserva_confirmada':
            return "Reserva confirmada para <strong>{$data['nombre']}</strong> el {$data['fecha']} a las {$data['hora']}";
        case 'reserva_cancelada':
            return "Reserva cancelada de <strong>{$data['nombre']}</strong> del {$data['fecha']} a las {$data['hora']}";
        case 'mensaje_whatsapp':
            return "Nuevo mensaje de WhatsApp de <strong>{$data['nombre']}</strong>: " . substr($data['mensaje'], 0, 50) . '...';
        case 'sistema':
            return $notificacion['mensaje'];
        default:
            return $notificacion['mensaje'];
    }
}

// Función para obtener el icono según el tipo
function getIconoNotificacion($tipo) {
    switch ($tipo) {
        case 'nueva_reserva':
            return 'ri-calendar-check-line text-blue-600';
        case 'reserva_confirmada':
            return 'ri-check-double-line text-green-600';
        case 'reserva_cancelada':
            return 'ri-close-circle-line text-red-600';
        case 'mensaje_whatsapp':
            return 'ri-whatsapp-line text-green-600';
        case 'sistema':
            return 'ri-settings-line text-gray-600';
        default:
            return 'ri-notification-line text-blue-600';
    }
}

// Incluir la cabecera
include 'includes/header.php';
?>

<style>
/* Estilos específicos para móvil - Notificaciones */
@media (max-width: 768px) {
    .notification-mobile-card {
        margin: 0.75rem 0;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        background: white;
        border-left: 4px solid;
        position: relative;
    }
    
    .notification-mobile-card.nueva_reserva {
        border-left-color: #3b82f6;
    }
    
    .notification-mobile-card.reserva_confirmada {
        border-left-color: #10b981;
    }
    
    .notification-mobile-card.reserva_cancelada {
        border-left-color: #ef4444;
    }
    
    .notification-mobile-card.mensaje_whatsapp {
        border-left-color: #25d366;
    }
    
    .notification-mobile-card.sistema {
        border-left-color: #6b7280;
    }
    
    .notification-mobile-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }
    
    .notification-card-header {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    .notification-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .notification-icon.nueva_reserva {
        background: rgba(59, 130, 246, 0.1);
    }
    
    .notification-icon.reserva_confirmada {
        background: rgba(16, 185, 129, 0.1);
    }
    
    .notification-icon.reserva_cancelada {
        background: rgba(239, 68, 68, 0.1);
    }
    
    .notification-icon.mensaje_whatsapp {
        background: rgba(37, 211, 102, 0.1);
    }
    
    .notification-icon.sistema {
        background: rgba(107, 114, 128, 0.1);
    }
    
    .notification-content {
        flex: 1;
        min-width: 0;
    }
    
    .notification-message {
        font-size: 0.875rem;
        color: #374151;
        line-height: 1.4;
        margin-bottom: 0.5rem;
    }
    
    .notification-time {
        font-size: 0.75rem;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .notification-badge {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        width: 0.5rem;
        height: 0.5rem;
        background: #ef4444;
        border-radius: 50%;
    }
    
    .notification-filters-mobile {
        background: white;
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    .filter-mobile-input {
        width: 100%;
        padding: 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        font-size: 0.875rem;
        margin-bottom: 0.75rem;
    }
    
    .filter-mobile-button {
        width: 100%;
        padding: 0.75rem;
        border-radius: 0.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        font-weight: 500;
        font-size: 0.875rem;
    }
    
    .mobile-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }
    
    .mobile-page-btn {
        min-width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s ease;
        border: 1px solid #e5e7eb;
        background: white;
        color: #6b7280;
        text-decoration: none;
    }
    
    .mobile-page-btn:hover {
        background: #f3f4f6;
        color: #374151;
        text-decoration: none;
    }
    
    .mobile-page-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: #667eea;
    }
}

@media (min-width: 769px) {
    .desktop-view {
        display: block;
    }
    
    .mobile-view {
        display: none;
    }
}

@media (max-width: 768px) {
    .desktop-view {
        display: none;
    }
    
    .mobile-view {
        display: block;
    }
}
</style>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Notificaciones</h1>
    <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-500">Total: <?php echo number_format($totalNotificaciones); ?></span>
        <?php if ($totalNotificaciones > 0): ?>
        <button id="marcarTodasLeidasBtn" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
            Marcar todas como leídas
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros - Vista Desktop -->
<div class="desktop-view bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4">
        <div class="flex-grow min-w-[200px]">
            <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de notificación</label>
            <select
                id="tipo"
                name="tipo"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            >
                <option value="">Todos los tipos</option>
                <?php foreach ($tiposDisponibles as $tipoDisponible): ?>
                    <option value="<?php echo htmlspecialchars($tipoDisponible); ?>" <?php echo $tipo === $tipoDisponible ? 'selected' : ''; ?>>
                        <?php echo ucfirst(str_replace('_', ' ', $tipoDisponible)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="w-48">
            <label for="fecha" class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
            <input
                type="date"
                id="fecha"
                name="fecha"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                value="<?php echo htmlspecialchars($fecha); ?>"
            >
        </div>
        
        <div class="flex items-end">
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <i class="ri-filter-line mr-2"></i>
                Filtrar
            </button>
            
            <?php if (!empty($tipo) || !empty($fecha)): ?>
                
                    href="/notificaciones"
                    class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <i class="ri-refresh-line mr-2"></i>
                    Limpiar
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Filtros - Vista Mobile -->
<div class="mobile-view notification-filters-mobile">
    <form method="GET">
        <select name="tipo" class="filter-mobile-input">
            <option value="">Todos los tipos</option>
            <?php foreach ($tiposDisponibles as $tipoDisponible): ?>
                <option value="<?php echo htmlspecialchars($tipoDisponible); ?>" <?php echo $tipo === $tipoDisponible ? 'selected' : ''; ?>>
                    <?php echo ucfirst(str_replace('_', ' ', $tipoDisponible)); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <input
            type="date"
            name="fecha"
            class="filter-mobile-input"
            value="<?php echo htmlspecialchars($fecha); ?>"
            placeholder="Filtrar por fecha"
        >
        
        <button type="submit" class="filter-mobile-button">
            <i class="ri-filter-line mr-2"></i>
            Filtrar Notificaciones
        </button>
        
        <?php if (!empty($tipo) || !empty($fecha)): ?>
            <a href="/notificaciones" class="block text-center text-blue-600 text-sm mt-2">
                Limpiar filtros
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Lista de notificaciones -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <?php if (empty($notificaciones)): ?>
        <div class="p-6 text-center">
            <i class="ri-notification-off-line text-gray-400 text-4xl"></i>
            <p class="mt-2 text-gray-500">No hay notificaciones</p>
            <?php if (!empty($tipo) || !empty($fecha)): ?>
                <p class="mt-1 text-sm text-gray-500">Prueba a cambiar los filtros</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        
        <!-- Vista Desktop -->
        <div class="desktop-view">
            <div class="divide-y divide-gray-200">
                <?php foreach ($notificaciones as $notificacion): ?>
                    <div class="p-4 hover:bg-gray-50 transition-colors <?php echo $notificacion['leida'] ? '' : 'bg-blue-50'; ?>">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo str_replace('text-', 'bg-', str_replace('-600', '-100', getIconoNotificacion($notificacion['tipo']))); ?>">
                                    <i class="<?php echo getIconoNotificacion($notificacion['tipo']); ?> text-lg"></i>
                                </div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm text-gray-900">
                                        <?php echo formatearMensajeNotificacion($notificacion); ?>
                                    </p>
                                    <?php if (!$notificacion['leida']): ?>
                                        <span class="flex-shrink-0 w-2 h-2 bg-blue-600 rounded-full"></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-1 flex items-center space-x-2 text-xs text-gray-500">
                                    <i class="ri-time-line"></i>
                                    <span><?php echo date('d/m/Y H:i', strtotime($notificacion['created_at'])); ?></span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        <?php echo ucfirst(str_replace('_', ' ', $notificacion['tipo'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Vista Mobile -->
        <div class="mobile-view p-4">
            <div class="space-y-3">
                <?php foreach ($notificaciones as $notificacion): ?>
                    <div class="notification-mobile-card <?php echo $notificacion['tipo']; ?> p-4 <?php echo !$notificacion['leida'] ? 'notification-badge' : ''; ?>">
                        <?php if (!$notificacion['leida']): ?>
                            <div class="notification-badge"></div>
                        <?php endif; ?>
                        
                        <div class="notification-card-header">
                            <div class="notification-icon <?php echo $notificacion['tipo']; ?>">
                                <i class="<?php echo getIconoNotificacion($notificacion['tipo']); ?> text-lg"></i>
                            </div>
                            
                            <div class="notification-content">
                                <div class="notification-message">
                                    <?php echo formatearMensajeNotificacion($notificacion); ?>
                                </div>
                                
                                <div class="notification-time">
                                    <i class="ri-time-line"></i>
                                    <span><?php echo date('d/m/Y H:i', strtotime($notificacion['created_at'])); ?></span>
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 ml-2">
                                        <?php echo ucfirst(str_replace('_', ' ', $notificacion['tipo'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
            <!-- Paginación Desktop -->
            <div class="desktop-view bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Mostrando <span class="font-medium"><?php echo $offset + 1; ?></span> a 
                            <span class="font-medium"><?php echo min($offset + $perPage, $totalNotificaciones); ?></span> de 
                            <span class="font-medium"><?php echo $totalNotificaciones; ?></span> notificaciones
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="/notificaciones?page=<?php echo $page - 1; ?>&tipo=<?php echo urlencode($tipo); ?>&fecha=<?php echo urlencode($fecha); ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="ri-arrow-left-s-line"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <?php if ($i == $page): ?>
                                    <span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">
                                        <?php echo $i; ?>
                                    </span>
                                <?php else: ?>
                                    <a href="/notificaciones?page=<?php echo $i; ?>&tipo=<?php echo urlencode($tipo); ?>&fecha=<?php echo urlencode($fecha); ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="/notificaciones?page=<?php echo $page + 1; ?>&tipo=<?php echo urlencode($tipo); ?>&fecha=<?php echo urlencode($fecha); ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="ri-arrow-right-s-line"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Paginación Mobile -->
            <div class="mobile-view p-4">
                <div class="mobile-pagination">
                    <?php if ($page > 1): ?>
                        <a href="/notificaciones?page=<?php echo $page - 1; ?>&tipo=<?php echo urlencode($tipo); ?>&fecha=<?php echo urlencode($fecha); ?>" 
                           class="mobile-page-btn">
                            <i class="ri-arrow-left-s-line"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 1);
                    $endPage = min($totalPages, $page + 1);
                    
                    if ($startPage > 1) {
                        echo '<a href="/notificaciones?page=1&tipo=' . urlencode($tipo) . '&fecha=' . urlencode($fecha) . '" class="mobile-page-btn">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="mobile-page-btn">...</span>';
                        }
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        if ($i == $page) {
                            echo '<span class="mobile-page-btn active">' . $i . '</span>';
                        } else {
                            echo '<a href="/notificaciones?page=' . $i . '&tipo=' . urlencode($tipo) . '&fecha=' . urlencode($fecha) . '" class="mobile-page-btn">' . $i . '</a>';
                        }
                    }
                    
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="mobile-page-btn">...</span>';
                        }
                        echo '<a href="/notificaciones?page=' . $totalPages . '&tipo=' . urlencode($tipo) . '&fecha=' . urlencode($fecha) . '" class="mobile-page-btn">' . $totalPages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="/notificaciones?page=<?php echo $page + 1; ?>&tipo=<?php echo urlencode($tipo); ?>&fecha=<?php echo urlencode($fecha); ?>" 
                           class="mobile-page-btn">
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botón para marcar todas como leídas
    const marcarTodasBtn = document.getElementById('marcarTodasLeidasBtn');
    if (marcarTodasBtn) {
        marcarTodasBtn.addEventListener('click', function() {
            if (confirm('¿Marcar todas las notificaciones como leídas?')) {
                // Implementar AJAX para marcar como leídas
                fetch('/api/marcar-notificaciones-leidas', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        });
    }
});
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>