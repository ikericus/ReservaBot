<?php
// pages/clientes.php

$currentPage = 'clientes';
$pageTitle = 'ReservaBot - Clientes';
$pageScript = 'clientes';

// Parámetros de búsqueda y paginación
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;

// Obtener usuario
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

try {
    $clienteDomain = getContainer()->getClienteDomain();
    
    // Obtener clientes con paginación y búsqueda
    $resultado = $clienteDomain->listarClientes($userId, $search, $page, $perPage);
    
    $clientes = $resultado['clientes'];
    $totalClientes = $resultado['total'];
    $totalPages = $resultado['total_paginas'];
    $offset = ($page - 1) * $perPage;
    
} catch (Exception $e) {
    setFlashError('Error al cargar clientes: ' . $e->getMessage());
    $clientes = [];
    $totalClientes = 0;
    $totalPages = 1;
    $offset = 0;
}

include 'includes/header.php';
?>

<style>
/* Estilos específicos para móvil - Clientes */
@media (max-width: 768px) {
    .client-mobile-card {
        margin: 0 0 0.75rem 0;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
        background: white;
        border: 1px solid #e5e7eb;
        text-decoration: none;
        display: block;
    }
    
    .client-mobile-card:active {
        background: #f9fafb;
        transform: scale(0.98);
    }
    
    .client-card-content {
        display: flex;
        align-items: center;
        padding: 1rem;
        gap: 0.75rem;
    }
    
    .client-avatar {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    
    .client-info {
        flex: 1;
        min-width: 0;
    }
    
    .client-name {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 0.25rem 0;
        line-height: 1.3;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .client-phone {
        font-size: 0.875rem;
        color: #6b7280;
        margin: 0 0 0.375rem 0;
        display: flex;
        align-items: center;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .client-phone i {
        margin-right: 0.375rem;
        color: #9ca3af;
        flex-shrink: 0;
    }
    
    .client-stats-inline {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.8125rem;
    }
    
    .client-stat-inline {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        color: #6b7280;
    }
    
    .client-stat-inline i {
        font-size: 0.875rem;
        color: #9ca3af;
    }
    
    .client-stat-number {
        font-weight: 600;
        color: #374151;
    }
    
    .client-arrow {
        color: #9ca3af;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .mobile-search {
        margin-bottom: 1rem;
    }
    
    .mobile-search-input {
        width: 100%;
        padding: 0.75rem 2.5rem 0.75rem 2.5rem;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
        font-size: 0.9375rem;
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
        font-size: 1rem;
    }
    
    .mobile-search-btn {
        position: absolute;
        right: 0.375rem;
        top: 50%;
        transform: translateY(-50%);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 0.5rem;
        padding: 0.4rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 500;
    }
    
    .mobile-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
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
        font-size: 0.8125rem;
        color: #6b7280;
        text-align: center;
        margin: 0.75rem 0;
    }
    
    .fade-in-mobile {
        animation: fadeInMobile 0.3s ease-out;
    }
    
    @keyframes fadeInMobile {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
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
<div class="mobile-view mobile-search px-4">
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
                                        · <span class="text-amber-600"><?php echo $cliente['reservas_pendientes']; ?> pendientes</span>
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
                                <a href="/cliente?telefono=<?php echo urlencode($cliente['telefono']); ?>" 
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
        
        <!-- Vista Mobile - Tarjetas Simplificadas -->
        <div class="mobile-view p-4">
            <div>
                <?php foreach ($clientes as $cliente): ?>
                    <a href="/cliente?telefono=<?php echo urlencode($cliente['telefono']); ?>" 
                       class="client-mobile-card fade-in-mobile">
                        <div class="client-card-content">
                            <div class="client-avatar">
                                <?php echo strtoupper(substr($cliente['ultimo_nombre'], 0, 1)); ?>
                            </div>
                            <div class="client-info">
                                <h3 class="client-name"><?php echo htmlspecialchars($cliente['ultimo_nombre']); ?></h3>
                                <p class="client-phone">
                                    <i class="ri-phone-line"></i>
                                    <?php echo htmlspecialchars($cliente['telefono']); ?>
                                </p>
                                <div class="client-stats-inline">
                                    <div class="client-stat-inline">
                                        <i class="ri-calendar-line"></i>
                                        <span class="client-stat-number"><?php echo $cliente['total_reservas']; ?></span>
                                        <span>reservas</span>
                                    </div>
                                    <?php if ($cliente['reservas_pendientes'] > 0): ?>
                                        <div class="client-stat-inline">
                                            <i class="ri-time-line"></i>
                                            <span class="client-stat-number text-amber-600"><?php echo $cliente['reservas_pendientes']; ?></span>
                                            <span>pendientes</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <i class="ri-arrow-right-s-line client-arrow"></i>
                        </div>
                    </a>
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

<?php include 'includes/footer.php'; ?>