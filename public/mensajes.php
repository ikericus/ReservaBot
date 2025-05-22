<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';
require_once 'includes/whatsapp-functions.php';

// Configurar la página actual
$currentPage = 'mensajes';
$pageTitle = 'ReservaBot - Historial de Mensajes';
$pageScript = 'mensajes';

// Parámetros de paginación y filtrado
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 50;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$chatId = isset($_GET['chat']) ? trim($_GET['chat']) : '';
$offset = ($page - 1) * $perPage;

// Consulta base para contar total de mensajes
$countQuery = 'SELECT COUNT(*) FROM mensajes_whatsapp mw
               JOIN chats_whatsapp cw ON mw.chat_id = cw.chat_id';

// Consulta base para obtener mensajes
$query = 'SELECT mw.*, cw.nombre as chat_nombre 
          FROM mensajes_whatsapp mw
          JOIN chats_whatsapp cw ON mw.chat_id = cw.chat_id';

// Condiciones de filtrado
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = '(mw.body LIKE ? OR cw.nombre LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($chatId)) {
    $where[] = 'mw.chat_id = ?';
    $params[] = $chatId;
}

// Aplicar condiciones si existen
if (!empty($where)) {
    $whereClause = ' WHERE ' . implode(' AND ', $where);
    $countQuery .= $whereClause;
    $query .= $whereClause;
}

// Ordenar y limitar resultados
$query .= ' ORDER BY mw.timestamp DESC LIMIT ?, ?';
$params[] = $offset;
$params[] = $perPage;

// Ejecutar consultas
try {
    // Contar total de mensajes filtrados
    $stmtCount = $pdo->prepare($countQuery);
    $stmtCount->execute(array_slice($params, 0, count($params) - 2)); // Sin límites
    $totalMensajes = $stmtCount->fetchColumn();
    
    // Obtener mensajes paginados
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $mensajes = $stmt->fetchAll();
    
    // Calcular total de páginas
    $totalPages = ceil($totalMensajes / $perPage);
    
    // Obtener chats disponibles para filtrado
    $stmtChats = $pdo->query('SELECT DISTINCT chat_id, nombre FROM chats_whatsapp ORDER BY nombre');
    $chats = $stmtChats->fetchAll();
} catch (\PDOException $e) {
    $mensajes = [];
    $totalMensajes = 0;
    $totalPages = 1;
    $chats = [];
}

// Incluir la cabecera
include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Historial de Mensajes</h1>
</div>

<!-- Filtros y búsqueda -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form id="filterForm" class="flex flex-wrap gap-4">
        <div class="flex-grow min-w-[200px]">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar mensaje</label>
            <div class="relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="ri-search-line text-gray-400"></i>
                </div>
                <input
                    type="text"
                    id="search"
                    name="search"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Buscar en mensajes..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </div>
        </div>
        
        <div class="w-48">
            <label for="chat" class="block text-sm font-medium text-gray-700 mb-1">Filtrar por chat</label>
            <select
                id="chat"
                name="chat"
                class="block w-full pl-3 pr-10 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
                <option value="">Todos los chats</option>
                <?php foreach ($chats as $chat): ?>
                    <option value="<?php echo htmlspecialchars($chat['chat_id']); ?>" <?php echo $chatId === $chat['chat_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($chat['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex items-end">
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <i class="ri-filter-line mr-2"></i>
                Filtrar
            </button>
            
            <?php if (!empty($search) || !empty($chatId)): ?>
                
                    href="mensajes.php"
                    class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <i class="ri-refresh-line mr-2"></i>
                    Limpiar
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Estadísticas -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-4 bg-blue-50 rounded-lg">
            <div class="text-sm text-blue-600 font-medium">Total de mensajes</div>
            <div class="text-2xl font-bold text-blue-900"><?php echo number_format($totalMensajes); ?></div>
        </div>
        
        <div id="statsRecibidos" class="p-4 bg-green-50 rounded-lg">
            <div class="text-sm text-green-600 font-medium">Mensajes recibidos</div>
            <div class="text-2xl font-bold text-green-900">Cargando...</div>
        </div>
        
        <div id="statsEnviados" class="p-4 bg-purple-50 rounded-lg">
            <div class="text-sm text-purple-600 font-medium">Mensajes enviados</div>
            <div class="text-2xl font-bold text-purple-900">Cargando...</div>
        </div>
    </div>
</div>

<!-- Lista de mensajes -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <?php if (empty($mensajes)): ?>
        <div class="p-6 text-center">
            <i class="ri-chat-off-line text-gray-400 text-4xl"></i>
            <p class="mt-2 text-gray-500">No se encontraron mensajes</p>
            <?php if (!empty($search) || !empty($chatId)): ?>
                <p class="mt-1 text-sm text-gray-500">Prueba a cambiar los filtros de búsqueda</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fecha y hora
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Chat
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dirección
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mensaje
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Auto
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($mensajes as $mensaje): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d/m/Y H:i:s', $mensaje['timestamp']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($mensaje['chat_nombre']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo formatWhatsappId($mensaje['chat_id']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $mensaje['direction'] === 'sent' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo $mensaje['direction'] === 'sent' ? 'Enviado' : 'Recibido'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-md truncate">
                                    <?php echo htmlspecialchars($mensaje['body']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if ($mensaje['is_auto_response']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Auto
                                    </span>
                                <?php endif; ?>
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
                            Mostrando <span class="font-medium"><?php echo $offset + 1; ?></span> a <span class="font-medium"><?php echo min($offset + $perPage, $totalMensajes); ?></span> de <span class="font-medium"><?php echo $totalMensajes; ?></span> mensajes
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&chat=<?php echo urlencode($chatId); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Anterior</span>
                                    <i class="ri-arrow-left-s-line"></i>
                                </a>
                            <?php else: ?>
                                <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    <span class="sr-only">Anterior</span>
                                    <i class="ri-arrow-left-s-line"></i>
                                </span>
                            <?php endif; ?>
                            
                            <?php
                            // Mostrar números de página
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            // Asegurar que siempre mostramos 5 páginas si hay suficientes
                            if ($endPage - $startPage < 4 && $totalPages > 5) {
                                if ($startPage == 1) {
                                    $endPage = min($startPage + 4, $totalPages);
                                } elseif ($endPage == $totalPages) {
                                    $startPage = max($endPage - 4, 1);
                                }
                            }
                            
                            // Primera página
                            if ($startPage > 1) {
                                echo '<a href="?page=1&search=' . urlencode($search) . '&chat=' . urlencode($chatId) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                if ($startPage > 2) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                            }
                            
                            // Páginas numeradas
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                if ($i == $page) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">' . $i . '</span>';
                                } else {
                                    echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '&chat=' . urlencode($chatId) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $i . '</a>';
                                }
                            }
                            
                            // Última página
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                                echo '<a href="?page=' . $totalPages . '&search=' . urlencode($search) . '&chat=' . urlencode($chatId) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $totalPages . '</a>';
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&chat=<?php echo urlencode($chatId); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Siguiente</span>
                                    <i class="ri-arrow-right-s-line"></i>
                                </a>
                            <?php else: ?>
                                <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    <span class="sr-only">Siguiente</span>
                                    <i class="ri-arrow-right-s-line"></i>
                                </span>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal para ver mensaje completo -->
<div id="messageDetailModal" class="fixed inset-0 z-10 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="messageDetailTitle">
                        Detalle del mensaje
                    </h3>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Chat</div>
                        <div id="messageDetailChat" class="mt-1 text-sm text-gray-900"></div>
                    </div>
                    
                    <div>
                        <div class="text-sm font-medium text-gray-500">Fecha y hora</div>
                        <div id="messageDetailTime" class="mt-1 text-sm text-gray-900"></div>
                    </div>
                    
                    <div>
                        <div class="text-sm font-medium text-gray-500">Dirección</div>
                        <div id="messageDetailDirection" class="mt-1"></div>
                    </div>
                    
                    <div>
                        <div class="text-sm font-medium text-gray-500">Mensaje</div>
                        <div id="messageDetailBody" class="mt-1 text-sm text-gray-900 whitespace-pre-wrap p-3 bg-gray-50 rounded-md"></div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="closeDetailBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Datos para el JavaScript
    const searchParams = <?php echo json_encode([
        'search' => $search,
        'chat' => $chatId,
        'page' => $page
    ]); ?>;
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>