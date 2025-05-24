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

// Consulta para obtener clientes únicos con estadísticas
$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = 'WHERE (r.nombre LIKE ? OR r.telefono LIKE ?)';
    $params = ["%$search%", "%$search%"];
}

// Obtener clientes con estadísticas
try {
    // Contar total de clientes únicos
    $countQuery = "SELECT COUNT(DISTINCT r.telefono) as total 
                   FROM reservas r 
                   $whereClause";
    
    $stmt = $pdo->prepare($countQuery);
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
    $stmt = $pdo->prepare($query);
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

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Clientes</h1>
</div>

<!-- Estadísticas generales -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
        $stmt = $pdo->query("SELECT 
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

<!-- Búsqueda -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
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
        <div class="overflow-x-auto">
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
        
        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
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
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>