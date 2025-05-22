<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';

// Configurar la página actual
$currentPage = 'formularios';
$pageTitle = 'ReservaBot - Enlaces de Reserva';

// Mensaje de estado
$mensaje = '';
$tipoMensaje = '';

// Procesar creación de enlace
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_enlace'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $confirmacion_auto = isset($_POST['confirmacion_auto']) ? 1 : 0;
    
    if (!empty($nombre)) {
        try {
            // Generar slug único
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $nombre));
            $slug = trim($slug, '-');
            $slug = $slug ?: 'reserva-' . time();
            
            // Verificar que el slug sea único
            $stmt = $pdo->prepare("SELECT id FROM formularios_publicos WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug .= '-' . time();
            }
            
            // Insertar en base de datos
            $stmt = $pdo->prepare("INSERT INTO formularios_publicos 
                (id_negocio, nombre, descripcion, slug, confirmacion_automatica, 
                 campos_activos, activo, created_at) 
                VALUES (1, ?, ?, ?, ?, ?, 1, ?)");
            
            $campos_activos = json_encode(['nombre', 'telefono', 'fecha', 'hora', 'comentarios']);
            $stmt->execute([$nombre, $descripcion, $slug, $confirmacion_auto, $campos_activos, time()]);
            
            $mensaje = 'Enlace de reserva creado correctamente';
            $tipoMensaje = 'success';
        } catch (Exception $e) {
            $mensaje = 'Error al crear el enlace: ' . $e->getMessage();
            $tipoMensaje = 'error';
        }
    } else {
        $mensaje = 'El nombre es obligatorio';
        $tipoMensaje = 'error';
    }
}

// Obtener enlaces existentes
try {
    $stmt = $pdo->query("SELECT * FROM formularios_publicos WHERE id_negocio = 1 ORDER BY created_at DESC");
    $enlaces = $stmt->fetchAll();
} catch (Exception $e) {
    $enlaces = [];
}

// Incluir cabecera
include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Enlaces de Reserva</h1>
</div>

<?php if (!empty($mensaje)): ?>
    <div class="mb-4 p-4 rounded-md <?php echo $tipoMensaje === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="<?php echo $tipoMensaje === 'success' ? 'ri-check-line text-green-400' : 'ri-error-warning-line text-red-400'; ?>"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm"><?php echo htmlspecialchars($mensaje); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Formulario para crear nuevo enlace -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4">Crear Nuevo Enlace de Reserva</h2>
    
    <form method="post" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre del enlace*
                </label>
                <input type="text" id="nombre" name="nombre" required
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="Ej: Reserva Consulta General">
                <p class="mt-1 text-xs text-gray-500">Este nombre aparecerá en el formulario público</p>
            </div>
            
            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">
                    Descripción (opcional)
                </label>
                <input type="text" id="descripcion" name="descripcion"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="Descripción interna del enlace">
            </div>
        </div>
        
        <div class="flex items-center">
            <input type="checkbox" id="confirmacion_auto" name="confirmacion_auto" 
                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <label for="confirmacion_auto" class="ml-2 block text-sm text-gray-700">
                Confirmación automática (las reservas se confirman automáticamente)
            </label>
        </div>
        
        <div class="flex justify-end">
            <button type="submit" name="crear_enlace" 
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="ri-add-line mr-2"></i>
                Crear Enlace
            </button>
        </div>
    </form>
</div>

<!-- Lista de enlaces existentes -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Enlaces Existentes</h2>
        
        <?php if (empty($enlaces)): ?>
            <div class="text-center py-8">
                <i class="ri-link text-gray-400 text-4xl"></i>
                <p class="mt-2 text-gray-500">Aún no has creado ningún enlace de reserva</p>
                <p class="text-sm text-gray-500">Crea tu primer enlace usando el formulario de arriba</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($enlaces as $enlace): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($enlace['nombre']); ?>
                                </h3>
                                <?php if (!empty($enlace['descripcion'])): ?>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <?php echo htmlspecialchars($enlace['descripcion']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="mt-2 flex items-center space-x-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $enlace['confirmacion_automatica'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $enlace['confirmacion_automatica'] ? 'Confirmación automática' : 'Confirmación manual'; ?>
                                    </span>
                                    
                                    <span class="text-xs text-gray-500">
                                        Creado: <?php echo date('d/m/Y', $enlace['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="ml-4 flex items-center space-x-2">
<?php 
                                // Generar URL completa
                                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                                $host = $_SERVER['HTTP_HOST'];
                                $path = dirname($_SERVER['REQUEST_URI']);
                                $baseUrl = $protocol . $host . $path . '/';
                                $enlaceCompleto = $baseUrl . 'reservar.php?f=' . $enlace['slug'];
                                ?>
                                
                                <a href="<?php echo $enlaceCompleto; ?>" 
                                   target="_blank"
                                   class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="ri-eye-line mr-1"></i>
                                    Ver
                                </a>
                                
                                <button class="btn-copiar inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                        data-clipboard-text="<?php echo $enlaceCompleto; ?>">
                                    <i class="ri-file-copy-line mr-1"></i>
                                    Copiar
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-3 p-3 bg-gray-50 rounded-md">
                            <div class="flex items-center">
                                <span class="text-xs font-medium text-gray-500 mr-2">Enlace:</span>
                                <input type="text" 
                                       value="<?php echo $enlaceCompleto; ?>" 
                                       readonly
                                       class="flex-1 text-xs bg-transparent border-0 text-gray-700 p-0 focus:ring-0">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Clipboard.js
    new ClipboardJS('.btn-copiar');
    
    // Mostrar feedback al copiar
    document.querySelectorAll('.btn-copiar').forEach(button => {
        button.addEventListener('click', function() {
            const original = this.innerHTML;
            this.innerHTML = '<i class="ri-check-line mr-1"></i>Copiado';
            
            setTimeout(() => {
                this.innerHTML = original;
            }, 2000);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>