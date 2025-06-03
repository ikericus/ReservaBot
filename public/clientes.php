<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';

// Configurar la página actual
$currentPage = 'clientes';
$pageTitle = 'ReservaBot - Clientes';
$pageScript = 'clientes';

// Parámetros de búsqueda y paginación
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Obtener usuario
$currentUser = getAuthenticatedUser();
$userId =  $currentUser['id'];

// Consulta para obtener clientes del negocio
$whereClause = 'WHERE usuario_id = ?';
$params = [$userId];

if (!empty($search)) {
    $whereClause = 'AND (r.nombre LIKE ? OR r.telefono LIKE ?)';
    array_push($params, "%$search%", "%$search%");
}

// Obtener clientes con estadísticas
try {
    // Contar total de clientes únicos
    $countQuery = "SELECT COUNT(DISTINCT r.telefono) as total 
                   FROM reservas r 
                   $whereClause";
    
    $stmt = getPDO()->prepare($countQuery);
    $stmt->execute($params);
    $totalClientes = $stmt->fetchColumn();
    
    // Obtener clientes paginados
    $query = "SELECT 
                r.telefono,
                r.nombre as ultimo_nombre,
                COUNT(r.id) as total_reservas,
                SUM(CASE WHEN r.estado = 'confirmada' THEN 1 ELSE 0 END) as reservas_confirmadas,
                SUM(CASE WHEN r.estado = 'pendiente' THEN 1 ELSE 0 END) as reservas_pendientes,
                MAX(r.fecha) as ultima_reserva,
                MIN(r.created_at) as primer_contacto,
                MAX(r.created_at) as ultimo_contacto
              FROM reservas r 
              $whereClause
              GROUP BY r.telefono 
              ORDER BY ultimo_contacto DESC 
              LIMIT ?, ?";
    
    $fullParams = array_merge($params, [$offset, $perPage]);
    $stmt = getPDO()->prepare($query);
    $stmt->execute($fullParams);
    $clientes = $stmt->fetchAll();
    
    $totalPages = ceil($totalClientes / $perPage);
    
} catch (\PDOException $e) {
    $clientes = [];
    $totalClientes = 0;
    $totalPages = 1;
    error_log('Error al obtener clientes: ' . $e->getMessage());
}

// Incluir la cabecera
include 'includes/header.php';
?>

<style>
/* Estilos específicos para móvil - Clientes */
@media (max-width: 768px) {
    .client-mobile-card {
        margin: 0.75rem 0;
        border-radius: 1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .client-mobile-card:hover {
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }
    
    .client-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .client-avatar {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.2rem;
        margin-right: 0.75rem;
        flex-shrink: 0;
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
    }
    
    .client-info {
        flex: 1;
        min-width: 0;
    }
    
    .client-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 0.25rem 0;
        line-height: 1.3;
    }
    
    .client-phone {
        font-size: 0.875rem;
        color: #6b7280;
        margin: 0;
        display: flex;
        align-items: center;
    }
    
    .client-phone i {
        margin-right: 0.375rem;
        color: #9ca3af;
    }
    
    .client-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin: 1rem 0;
    }
    
    .client-stat {
        background: rgba(102, 126, 234, 0.05);
        padding: 0.75rem;
        border-radius: 0.75rem;
        text-align: center;
        border: 1px solid rgba(102, 126, 234, 0.1);
    }
    
    .client-stat-number {
        font-size: 1.25rem;
        font-weight: 700;
        color: #667eea;
        margin: 0;
        line-height: 1;
    }
    
    .client-stat-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin: 0.25rem 0 0 0;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 500;
    }
    
    .client-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 1rem 0;
        padding-top: 1rem;
        border-top: 1px solid rgba(0, 0, 0, 0.06);
    }
    
    .client-meta-item {
        font-size: 0.75rem;
        color: #6b7280;
        display: flex;
        align-items: center;
        flex-direction: column;
        text-align: center;
    }
    
    .client-meta-item i {
        margin-bottom: 0.25rem;
        color: #9ca3af;
        font-size: 1rem;
    }
    
    .client-meta-value {
        font-weight: 500;
        color: #374151;
        margin-top: 0.125rem;
    }
    
    .client-action-btn {
        width: 100%;
        padding: 0.75rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        text-align: center;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        text-decoration: none;
        margin-top: 1rem;
    }
    
    .client-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }
    
    /* Estilos para las estadísticas superiores en móvil */
    .mobile-stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    
    .mobile-stat-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        padding: 1rem;
        border-radius: 1rem;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .mobile-stat-icon {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .mobile-stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
    }
    
    .mobile-stat-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin: 0.25rem 0 0 0;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 500;
    }
    
    /* Búsqueda móvil optimizada */
    .mobile-search {
        margin-bottom: 1.5rem;
    }
    
    .mobile-search-input {
        width: 100%;
        padding: 0.875rem 3rem 0.875rem 2.5rem;
        border-radius: 1rem;
        border: 2px solid #e5e7eb;
        font-size: 1rem;
        transition: all 0.2s ease;
        background: white;
    }
    
    .mobile-search-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .mobile-search-icon {
        position: absolute;
        left: 0.875rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 1.125rem;
    }
    
    .mobile-search-btn {
        position: absolute;
        right: 0.375rem;
        top: 50%;
        transform: translateY(-50%);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 0.625rem;
        padding: 0.5rem 0.875rem;
        font-size: 0.875rem;
    }
    
    /* Paginación móvil */
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
    
    .mobile-page-info {
        font-size: 0.875rem;
        color: #6b7280;
        text-align: center;
        margin: 1rem 0;
    }
    
    /* Animaciones */
    .fade-in-mobile {
        animation: fadeInMobile 0.4s ease-out;
    }
    
    @keyframes fadeInMobile {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
}

/* Estilos para desktop - mantener diseño original */
@media (min-width: 769px) {
    .desktop-view {
        display: block;
    }
    
    .mobile-view {
        display: none;
    }
}

/* Estilos para móvil - usar diseño de tarjetas */
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
    <h1 class="text-2xl font-bold text-gray-900">Clientes</h1>
</div>

<!-- Estadísticas generales - Vista Desktop -->
<div class="desktop-view grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="ri-user-line text-2xl text-blue-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Clientes</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo number_format($totalClientes); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Obtener estadísticas adicionales
    try {
        $stmt = getPDO()->query("SELECT 
            COUNT(*) as total_reservas,
            COUNT(DISTINCT telefono) as clientes_activos,
            SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas
            FROM reservas 
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stats = $stmt->fetch();
    } catch (\PDOException $e) {
        $stats = ['total_reservas' => 0, 'clientes_activos' => 0, 'confirmadas' => 0];
    }
    ?>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="ri-calendar-check-line text-2xl text-green-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Reservas (30 días)</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo number_format($stats['total_reservas']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="ri-user-star-line text-2xl text-purple-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Clientes Activos</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo number_format($stats['clientes_activos']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="ri-check-double-line text-2xl text-emerald-600"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Confirmadas</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo number_format($stats['confirmadas']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas generales - Vista Mobile -->
<div class="mobile-view mobile-stats-grid">
    <div class="mobile-stat-card">
        <i class="ri-user-line mobile-stat-icon text-blue-600"></i>
        <div class="mobile-stat-number text-blue-900"><?php echo number_format($totalClientes); ?></div>
        <div class="mobile-stat-label">Total Clientes</div>
    </div>
</div>

<!-- Búsqueda - Vista Desktop -->
<div class="desktop-view bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4">
        <div class="flex-grow min-w-[200px]">
            <div class="relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="ri-search-line text-gray-400"></i>
                </div>
                <input
                    type="text"
                    name="search"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Buscar por nombre o teléfono..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </div>
        </div>
        
        <div class="flex items-end">
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <i class="ri-search-line mr-2"></i>
                Buscar
            </button>
            
            <?php if (!empty($search)): ?>
                <a
                    href="/clientes"
                    class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <i class="ri-refresh-line mr-2"></i>
                    Limpiar
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Búsqueda - Vista Mobile -->
<div class="mobile-view mobile-search">
    <form method="GET" class="relative">
        <i class="ri-search-line mobile-search-icon"></i>
        <input
            type="text"
            name="search"
            class="mobile-search-input"
            placeholder="Buscar clientes..."
            value="<?php echo htmlspecialchars($search); ?>"
        >
        <button type="submit" class="mobile-search-btn">
            Buscar
        </button>
    </form>
    
    <?php if (!empty($search)): ?>
        <div class="mt-2 text-center">
            <a href="/clientes" class="text-blue-600 text-sm">
                <i class="ri-refresh-line mr-1"></i>
                Limpiar búsqueda
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Lista de clientes -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <?php if (empty($clientes)): ?>
        <div class="p-6 text-center">
            <i class="ri-user-line text-gray-400 text-4xl"></i>
            <p class="mt-2 text-gray-500">No se encontraron clientes</p>
            <?php if (!empty($search)): ?>
                <p class="mt-1 text-sm text-gray-500">Prueba a cambiar los términos de búsqueda</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        
        <!-- Vista Desktop - Tabla -->
        <div class="desktop-view overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Reservas
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Última Reserva
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Primer Contacto
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($clientes as $cliente): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="ri-user-line text-blue-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($cliente['ultimo_nombre']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($cliente['telefono']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <span class="font-medium"><?php echo $cliente['total_reservas']; ?></span> total
                                </div>
                                <div class="text-sm text-gray-500">
                                    <span class="text-green-600"><?php echo $cliente['reservas_confirmadas']; ?> confirmadas</span>
                                    <?php if ($cliente['reservas_pendientes'] > 0): ?>
                                        <span class="text-amber-600"><?php echo $cliente['reservas_pendientes']; ?> pendientes</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $cliente['ultima_reserva'] ? date('d/m/Y', strtotime($cliente['ultima_reserva'])) : 'N/A'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d/m/Y', strtotime($cliente['primer_contacto'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="/cliente-detail?telefono=<?php echo urlencode($cliente['telefono']); ?>" 
                                   class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="ri-eye-line mr-1"></i>
                                    Ver Detalle
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Vista Mobile - Tarjetas -->
        <div class="mobile-view p-4">
            <div class="space-y-4">
                <?php foreach ($clientes as $cliente): ?>
                    <div class="client-mobile-card p-4 fade-in-mobile">
                        <div class="client-card-header">
                            <div class="client-avatar">
                                <?php echo strtoupper(substr($cliente['ultimo_nombre'], 0, 1)); ?>
                            </div>
                            <div class="client-info">
                                <h3 class="client-name"><?php echo htmlspecialchars($cliente['ultimo_nombre']); ?></h3>
                                <p class="client-phone">
                                    <i class="ri-phone-line"></i>
                                    <?php echo htmlspecialchars($cliente['telefono']); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="client-stats">
                            <div class="client-stat">
                                <div class="client-stat-number"><?php echo $cliente['total_reservas']; ?></div>
                                <div class="client-stat-label">Total</div>
                            </div>
                            <div class="client-stat">
                                <div class="client-stat-number text-green-600"><?php echo $cliente['reservas_confirmadas']; ?></div>
                                <div class="client-stat-label">Confirmadas</div>
                            </div>
                        </div>
                        
                        <div class="client-meta">
                            <div class="client-meta-item">
                                <i class="ri-calendar-line"></i>
                                <span>Última reserva</span>
                                <div class="client-meta-value">
                                    <?php echo $cliente['ultima_reserva'] ? date('d/m/Y', strtotime($cliente['ultima_reserva'])) : 'N/A'; ?>
                                </div>
                            </div>
                            <div class="client-meta-item">
                                <i class="ri-user-add-line"></i>
                                <span>Cliente desde</span>
                                <div class="client-meta-value">
                                    <?php echo date('d/m/Y', strtotime($cliente['primer_contacto'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <a href="/cliente-detail?telefono=<?php echo urlencode($cliente['telefono']); ?>" 
                           class="client-action-btn">
                            <i class="ri-eye-line"></i>
                            Ver Detalle Completo
                        </a>
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
                            <span class="font-medium"><?php echo min($offset + $perPage, $totalClientes); ?></span> de 
                            <span class="font-medium"><?php echo $totalClientes; ?></span> clientes
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="/clientes?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
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
                                    <a href="/clientes?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="/clientes?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="ri-arrow-right-s-line"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Paginación Mobile -->
            <div class="mobile-view p-4 border-t border-gray-200">
                <div class="mobile-page-info">
                    Mostrando <?php echo $offset + 1; ?> - <?php echo min($offset + $perPage, $totalClientes); ?> de <?php echo $totalClientes; ?> clientes
                </div>
                
                <div class="mobile-pagination">
                    <?php if ($page > 1): ?>
                        <a href="/clientes?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                           class="mobile-page-btn">
                            <i class="ri-arrow-left-s-line"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Mostrar páginas simplificado para móvil
                    $startPage = max(1, $page - 1);
                    $endPage = min($totalPages, $page + 1);
                    
                    if ($startPage > 1) {
                        echo '<a href="/clientes?page=1&search=' . urlencode($search) . '" class="mobile-page-btn">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="mobile-page-btn">...</span>';
                        }
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        if ($i == $page) {
                            echo '<span class="mobile-page-btn active">' . $i . '</span>';
                        } else {
                            echo '<a href="/clientes?page=' . $i . '&search=' . urlencode($search) . '" class="mobile-page-btn">' . $i . '</a>';
                        }
                    }
                    
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="mobile-page-btn">...</span>';
                        }
                        echo '<a href="/clientes?page=' . $totalPages . '&search=' . urlencode($search) . '" class="mobile-page-btn">' . $totalPages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="/clientes?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                           class="mobile-page-btn">
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>