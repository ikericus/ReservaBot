<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Configurar la página actual
$pageTitle = 'ReservaBot - Enlaces de Reserva';
$currentPage = 'formularios';
$pageScript = 'formularios';

// Mensaje de estado
$mensaje = '';
$tipoMensaje = '';

// Procesar eliminación de enlace
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_enlace'])) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id > 0) {
        try {
            // Eliminar el formulario (simplificado - ya no hay tablas relacionadas complejas)
            getPDO()->beginTransaction();
            
            // Eliminar referencias en origen_reservas (opcional)
            $stmt = getPDO()->prepare("DELETE FROM origen_reservas WHERE formulario_id = ?");
            $stmt->execute([$id]);
            
            // Eliminar el formulario
            $stmt = getPDO()->prepare("DELETE FROM formularios_publicos WHERE id = ?");
            $stmt->execute([$id]);
            
            getPDO()->commit();
            
            $mensaje = 'Enlace eliminado correctamente';
            $tipoMensaje = 'success';
        } catch (Exception $e) {
            getPDO()->rollBack();
            $mensaje = 'Error al eliminar el enlace: ' . $e->getMessage();
            $tipoMensaje = 'error';
        }
    } else {
        $mensaje = 'ID de enlace no válido';
        $tipoMensaje = 'error';
    }
}

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
            $stmt = getPDO()->prepare("SELECT id FROM formularios_publicos WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug .= '-' . time();
            }
            
            // Insertar en base de datos (simplificado)
            $stmt = getPDO()->prepare("INSERT INTO formularios_publicos 
                (nombre, descripcion, slug, confirmacion_automatica, activo) 
                VALUES (?, ?, ?, ?, 1)");
            
            $stmt->execute([$nombre, $descripcion, $slug, $confirmacion_auto]);
            
            $mensaje = 'Enlace de reserva creado correctamente';
            $tipoMensaje = 'success';
        } catch (Exception $e) {
            error_log('Error al crear formulario: ' . $e->getMessage());
            $mensaje = 'Error al crear formulario: ' . $e->getMessage();
            $tipoMensaje = 'error';
        }
    } else {
        $mensaje = 'El nombre es obligatorio';
        $tipoMensaje = 'error';
    }
}

// Obtener usuario
$currentUser = getAuthenticatedUser();
$userId =  $currentUser['id'];

// Obtener enlaces existentes
try {
    $stmt = getPDO()->query("SELECT * FROM formularios_publicos WHERE usuario_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $enlaces = $stmt->fetchAll();
} catch (Exception $e) {
    $enlaces = [];
}

// Incluir cabecera
include 'includes/header.php';
?>

<style>
/* Estilos específicos para móvil - Formularios */
@media (max-width: 768px) {
    .form-mobile-card {
        margin: 0.75rem 0;
        border-radius: 1rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: hidden;
        position: relative;
    }
    
    .form-mobile-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        transform: translateY(-3px);
    }
    
    .form-card-header {
        padding: 1rem 1rem 0.5rem 1rem;
        position: relative;
    }
    
    .form-card-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 1rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .form-card-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 0.25rem 0;
        line-height: 1.3;
    }
    
    .form-card-description {
        font-size: 0.875rem;
        color: #6b7280;
        margin: 0 0 0.75rem 0;
        line-height: 1.4;
    }
    
    .form-card-badges {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }
    
    .form-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .form-badge-auto {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .form-badge-manual {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }
    
    .form-badge-date {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
        border: 1px solid rgba(102, 126, 234, 0.2);
    }
    
    .form-card-url {
        background: rgba(102, 126, 234, 0.05);
        padding: 0.75rem;
        border-radius: 0.75rem;
        margin: 1rem;
        border: 1px solid rgba(102, 126, 234, 0.1);
    }
    
    .form-card-url-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 500;
    }
    
    .form-card-url-text {
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        font-size: 0.75rem;
        color: #374151;
        word-break: break-all;
        line-height: 1.4;
    }
    
    .form-card-actions {
        padding: 0 1rem 1rem 1rem;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .form-action-btn {
        flex: 1;
        min-width: calc(50% - 0.25rem);
        padding: 0.625rem 0.75rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        text-align: center;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }
    
    .form-action-btn:focus {
        outline: none;
        ring: 2px solid rgba(102, 126, 234, 0.5);
    }
    
    .form-btn-view {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .form-btn-view:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: white;
        text-decoration: none;
        transform: translateY(-1px);
    }
    
    .form-btn-copy {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }
    
    .form-btn-copy:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: white;
        transform: translateY(-1px);
    }
    
    .form-btn-qr {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
    }
    
    .form-btn-qr:hover {
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        color: white;
        transform: translateY(-1px);
    }
    
    .form-btn-delete {
        background: white;
        color: #dc2626;
        border: 1px solid rgba(220, 38, 38, 0.2);
    }
    
    .form-btn-delete:hover {
        background: rgba(220, 38, 38, 0.05);
        color: #b91c1c;
        transform: translateY(-1px);
    }
    
    /* Formulario de creación mobile */
    .mobile-create-form {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .mobile-form-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .mobile-form-title i {
        color: #667eea;
        font-size: 1.5rem;
    }
    
    .mobile-form-input {
        width: 100%;
        padding: 0.875rem;
        border-radius: 0.75rem;
        border: 2px solid #e5e7eb;
        font-size: 1rem;
        transition: all 0.2s ease;
        background: white;
        margin-bottom: 1rem;
    }
    
    .mobile-form-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .mobile-form-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .mobile-form-checkbox {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding: 0.75rem;
        background: rgba(102, 126, 234, 0.05);
        border-radius: 0.75rem;
        border: 1px solid rgba(102, 126, 234, 0.1);
    }
    
    .mobile-form-checkbox input {
        width: 1.25rem;
        height: 1.25rem;
        accent-color: #667eea;
    }
    
    .mobile-form-checkbox label {
        font-size: 0.875rem;
        color: #374151;
        margin: 0;
    }
    
    .mobile-submit-btn {
        width: 100%;
        padding: 1rem;
        border-radius: 0.75rem;
        font-size: 1rem;
        font-weight: 600;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .mobile-submit-btn:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    /* Estados de mensajes */
    .mobile-message {
        padding: 1rem;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }
    
    .mobile-message-success {
        background: rgba(16, 185, 129, 0.1);
        color: #065f46;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .mobile-message-error {
        background: rgba(220, 38, 38, 0.1);
        color: #991b1b;
        border: 1px solid rgba(220, 38, 38, 0.2);
    }
    
    /* Lista vacía */
    .mobile-empty-state {
        text-align: center;
        padding: 2rem 1rem;
        color: #6b7280;
    }
    
    .mobile-empty-icon {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }
    
    .mobile-empty-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }
    
    .mobile-empty-description {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 1.5rem;
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
    
    .copy-feedback {
        animation: copyFeedback 2s ease-in-out;
    }
    
    @keyframes copyFeedback {
        0%, 100% { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        50% { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
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
    <h1 class="text-2xl font-bold text-gray-900">Enlaces de Reserva</h1>
</div>

<?php if (!empty($mensaje)): ?>
    <!-- Mensaje Desktop -->
    <div class="desktop-view mb-4 p-4 rounded-md <?php echo $tipoMensaje === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="<?php echo $tipoMensaje === 'success' ? 'ri-check-line text-green-400' : 'ri-error-warning-line text-red-400'; ?>"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm"><?php echo htmlspecialchars($mensaje); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Mensaje Mobile -->
    <div class="mobile-view mobile-message <?php echo $tipoMensaje === 'success' ? 'mobile-message-success' : 'mobile-message-error'; ?>">
        <i class="<?php echo $tipoMensaje === 'success' ? 'ri-check-line' : 'ri-error-warning-line'; ?>"></i>
        <span><?php echo htmlspecialchars($mensaje); ?></span>
    </div>
<?php endif; ?>

<!-- Formulario para crear nuevo enlace - Vista Desktop -->
<div class="desktop-view bg-white rounded-lg shadow-sm p-6 mb-6">
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

<!-- Formulario para crear nuevo enlace - Vista Mobile -->
<div class="mobile-view mobile-create-form">
    <h2 class="mobile-form-title">
        <i class="ri-add-circle-line"></i>
        Crear Nuevo Enlace
    </h2>
    
    <form method="post">
        <label class="mobile-form-label">Nombre del enlace*</label>
        <input type="text" name="nombre" required
               class="mobile-form-input"
               placeholder="Ej: Reserva Consulta General">
        
        <label class="mobile-form-label">Descripción (opcional)</label>
        <input type="text" name="descripcion"
               class="mobile-form-input"
               placeholder="Descripción interna del enlace">
        
        <div class="mobile-form-checkbox">
            <input type="checkbox" id="confirmacion_auto_mobile" name="confirmacion_auto">
            <label for="confirmacion_auto_mobile">
                Confirmación automática de reservas
            </label>
        </div>
        
        <button type="submit" name="crear_enlace" class="mobile-submit-btn">
            <i class="ri-add-line"></i>
            Crear Enlace de Reserva
        </button>
    </form>
</div>

<!-- Lista de enlaces existentes -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="desktop-view p-6">
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
                                        Creado: <?php echo date('d/m/Y', strtotime($enlace['created_at'])); ?>
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
                                
                                <button class="btn-qr inline-flex items-center px-3 py-1 border border-blue-300 shadow-sm text-xs font-medium rounded text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                        data-url="<?php echo $enlaceCompleto; ?>"
                                        data-nombre="<?php echo htmlspecialchars($enlace['nombre']); ?>">
                                    <i class="ri-qr-code-line mr-1"></i>
                                    QR
                                </button>
                                
                                <button class="btn-eliminar inline-flex items-center px-3 py-1 border border-red-300 shadow-sm text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                        data-id="<?php echo $enlace['id']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($enlace['nombre']); ?>">
                                    <i class="ri-delete-bin-line mr-1"></i>
                                    Eliminar
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
    
    <!-- Vista Mobile -->
    <div class="mobile-view">
        <?php if (empty($enlaces)): ?>
            <div class="mobile-empty-state">
                <i class="ri-link mobile-empty-icon"></i>
                <h3 class="mobile-empty-title">No hay enlaces creados</h3>
                <p class="mobile-empty-description">Crea tu primer enlace de reserva usando el formulario de arriba</p>
            </div>
        <?php else: ?>
            <div class="p-4">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Enlaces Existentes (<?php echo count($enlaces); ?>)</h2>
                
                <div class="space-y-4">
                    <?php foreach ($enlaces as $enlace): ?>
                        <div class="form-mobile-card fade-in-mobile">
                            <div class="form-card-header">
                                <div class="form-card-icon">
                                    <i class="ri-link"></i>
                                </div>
                                <h3 class="form-card-title"><?php echo htmlspecialchars($enlace['nombre']); ?></h3>
                                <?php if (!empty($enlace['descripcion'])): ?>
                                    <p class="form-card-description"><?php echo htmlspecialchars($enlace['descripcion']); ?></p>
                                <?php endif; ?>
                                
                                <div class="form-card-badges">
                                    <span class="form-badge <?php echo $enlace['confirmacion_automatica'] ? 'form-badge-auto' : 'form-badge-manual'; ?>">
                                        <i class="<?php echo $enlace['confirmacion_automatica'] ? 'ri-check-line' : 'ri-time-line'; ?>"></i>
                                        <?php echo $enlace['confirmacion_automatica'] ? 'Automática' : 'Manual'; ?>
                                    </span>
                                    <span class="form-badge form-badge-date">
                                        <i class="ri-calendar-line"></i>
                                        <?php echo date('d/m/Y', strtotime($enlace['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php 
                            // Generar URL completa
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                            $host = $_SERVER['HTTP_HOST'];
                            $path = dirname($_SERVER['REQUEST_URI']);
                            $baseUrl = $protocol . $host . $path . '/';
                            $enlaceCompleto = $baseUrl . 'reservar.php?f=' . $enlace['slug'];
                            ?>
                            
                            <div class="form-card-url">
                                <div class="form-card-url-label">Enlace del formulario:</div>
                                <div class="form-card-url-text"><?php echo $enlaceCompleto; ?></div>
                            </div>
                            
                            <div class="form-card-actions">
                                <a href="<?php echo $enlaceCompleto; ?>" 
                                   target="_blank"
                                   class="form-action-btn form-btn-view">
                                    <i class="ri-eye-line"></i>
                                    Ver
                                </a>
                                
                                <button class="form-action-btn form-btn-copy btn-copiar-mobile"
                                        data-clipboard-text="<?php echo $enlaceCompleto; ?>"
                                        data-enlace-id="<?php echo $enlace['id']; ?>">
                                    <i class="ri-file-copy-line"></i>
                                    Copiar
                                </button>
                                
                                <button class="form-action-btn form-btn-qr btn-qr"
                                        data-url="<?php echo $enlaceCompleto; ?>"
                                        data-nombre="<?php echo htmlspecialchars($enlace['nombre']); ?>">
                                    <i class="ri-qr-code-line"></i>
                                    QR
                                </button>
                                
                                <button class="form-action-btn form-btn-delete btn-eliminar"
                                        data-id="<?php echo $enlace['id']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($enlace['nombre']); ?>">
                                    <i class="ri-delete-bin-line"></i>
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para mostrar código QR -->
<div id="qrModal" class="fixed inset-0 z-10 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="qrModalTitle">
                        Código QR para compartir
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Comparte este código QR en redes sociales o imprímelo para que tus clientes puedan acceder fácilmente al formulario de reserva.
                    </p>
                </div>
                
                <div class="text-center">
                    <div id="qrCodeContainer" class="inline-block p-4 bg-white border border-gray-300 rounded-lg">
                        <!-- El código QR se generará aquí -->
                    </div>
                    
                    <div class="mt-4 space-y-2">
                        <button id="downloadQrBtn" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="ri-download-line mr-2"></i>
                            Descargar QR
                        </button>
                        
                        <button id="shareQrBtn" class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="ri-share-line mr-2"></i>
                            Compartir
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="closeQrModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmar eliminación -->
<div id="eliminarModal" class="fixed inset-0 z-10 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="ri-delete-bin-line text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Eliminar Enlace de Reserva
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                ¿Estás seguro de que deseas eliminar el enlace "<span id="nombreEnlaceEliminar" class="font-medium"></span>"?
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                Esta acción eliminará también todas las reservas realizadas a través de este enlace y no se puede deshacer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form method="post" id="formEliminar" class="inline">
                    <input type="hidden" name="id" id="idEnlaceEliminar">
                    <button type="submit" name="eliminar_enlace" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Eliminar
                    </button>
                </form>
                <button type="button" id="cancelarEliminar" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/lib/browser.min.js"></script>
<script src="assets/js/formularios.js"></script>

<script>
// JavaScript específico para móvil
document.addEventListener('DOMContentLoaded', function() {
    console.log('Formularios responsive loaded');
    
    // Funcionalidad de copiado específica para móvil
    if (typeof ClipboardJS !== 'undefined') {
        // ClipboardJS para desktop
        new ClipboardJS('.btn-copiar');
        
        // Feedback para desktop
        document.querySelectorAll('.btn-copiar').forEach(button => {
            button.addEventListener('click', function() {
                const original = this.innerHTML;
                this.innerHTML = '<i class="ri-check-line mr-1"></i>Copiado';
                
                setTimeout(() => {
                    this.innerHTML = original;
                }, 2000);
            });
        });
        
        // ClipboardJS específico para móvil
        const clipboardMobile = new ClipboardJS('.btn-copiar-mobile');
        
        clipboardMobile.on('success', function(e) {
            const button = e.trigger;
            const original = button.innerHTML;
            
            // Feedback visual mejorado para móvil
            button.classList.add('copy-feedback');
            button.innerHTML = '<i class="ri-check-line"></i>¡Copiado!';
            
            // Vibración si está disponible
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
            
            setTimeout(() => {
                button.classList.remove('copy-feedback');
                button.innerHTML = original;
            }, 2000);
            
            e.clearSelection();
        });
        
        clipboardMobile.on('error', function(e) {
            console.error('Error copiando:', e);
            showMobileNotification('Error al copiar el enlace', 'error');
        });
    } else {
        // Fallback para móvil sin ClipboardJS
        document.querySelectorAll('.btn-copiar-mobile').forEach(button => {
            button.addEventListener('click', function() {
                const url = this.dataset.clipboardText;
                fallbackCopyToClipboard(url, this);
            });
        });
    }
    
    // Funcionalidad mejorada de QR para móvil
    document.querySelectorAll('.btn-qr').forEach(button => {
        button.addEventListener('click', function() {
            const url = this.dataset.url;
            const nombre = this.dataset.nombre;
            showQRModal(url, nombre);
        });
    });
    
    // Funcionalidad de eliminación mejorada para móvil
    document.querySelectorAll('.btn-eliminar').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;
            showDeleteModal(id, nombre);
        });
    });
    
    // Cerrar modales al tocar fuera (mejorado para móvil)
    document.addEventListener('click', function(e) {
        const qrModal = document.getElementById('qrModal');
        const deleteModal = document.getElementById('eliminarModal');
        
        if (e.target === qrModal) {
            qrModal.classList.add('hidden');
        }
        
        if (e.target === deleteModal) {
            deleteModal.classList.add('hidden');
        }
    });
    
    // Botones de cerrar modales
    const closeQrBtn = document.getElementById('closeQrModal');
    const cancelDeleteBtn = document.getElementById('cancelarEliminar');
    
    if (closeQrBtn) {
        closeQrBtn.addEventListener('click', () => {
            document.getElementById('qrModal').classList.add('hidden');
        });
    }
    
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => {
            document.getElementById('eliminarModal').classList.add('hidden');
        });
    }
    
    // Validación mejorada del formulario para móvil
    const createForms = document.querySelectorAll('form[method="post"]');
    createForms.forEach(form => {
        if (!form.querySelector('input[name="eliminar_enlace"]')) {
            form.addEventListener('submit', function(e) {
                const nombreInput = form.querySelector('input[name="nombre"]');
                const nombre = nombreInput ? nombreInput.value.trim() : '';
                
                if (nombre.length < 3) {
                    e.preventDefault();
                    showMobileNotification('El nombre debe tener al menos 3 caracteres', 'error');
                    if (nombreInput) nombreInput.focus();
                    return false;
                }
                
                if (nombre.length > 100) {
                    e.preventDefault();
                    showMobileNotification('El nombre no puede tener más de 100 caracteres', 'error');
                    if (nombreInput) nombreInput.focus();
                    return false;
                }
                
                // Loading state para el botón
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="ri-loader-line animate-spin"></i>Creando...';
                    submitBtn.disabled = true;
                    
                    // Restaurar después de 5 segundos si no se envía
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 5000);
                }
            });
        }
    });
});

// Función auxiliar para copiar sin ClipboardJS
function fallbackCopyToClipboard(text, button) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            const original = button.innerHTML;
            button.innerHTML = '<i class="ri-check-line"></i>¡Copiado!';
            
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
            
            setTimeout(() => {
                button.innerHTML = original;
            }, 2000);
        }).catch(() => {
            showMobileNotification('Error al copiar el enlace', 'error');
        });
    } else {
        // Fallback para navegadores muy antiguos
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            const original = button.innerHTML;
            button.innerHTML = '<i class="ri-check-line"></i>¡Copiado!';
            
            setTimeout(() => {
                button.innerHTML = original;
            }, 2000);
        } catch (err) {
            showMobileNotification('Error al copiar el enlace', 'error');
        } finally {
            document.body.removeChild(textArea);
        }
    }
}

// Función para mostrar notificaciones móviles
function showMobileNotification(message, type = 'info') {
    // Crear elemento de notificación optimizado para móvil
    const notification = document.createElement('div');
    notification.className = `fixed top-4 left-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform -translate-y-full opacity-0`;
    
    // Aplicar estilos según el tipo
    switch (type) {
        case 'success':
            notification.className += ' bg-green-500 text-white';
            notification.innerHTML = `<div class="flex items-center"><i class="ri-check-line mr-2"></i><span>${message}</span></div>`;
            break;
        case 'error':
            notification.className += ' bg-red-500 text-white';
            notification.innerHTML = `<div class="flex items-center"><i class="ri-error-warning-line mr-2"></i><span>${message}</span></div>`;
            break;
        default:
            notification.className += ' bg-blue-500 text-white';
            notification.innerHTML = `<div class="flex items-center"><i class="ri-information-line mr-2"></i><span>${message}</span></div>`;
    }
    
    // Añadir al DOM
    document.body.appendChild(notification);
    
    // Mostrar notificación
    setTimeout(() => {
        notification.classList.remove('-translate-y-full', 'opacity-0');
    }, 100);
    
    // Ocultar notificación después de 3 segundos
    setTimeout(() => {
        notification.classList.add('-translate-y-full', 'opacity-0');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Funciones para modales (reutilizadas del script original)
function showQRModal(url, nombre) {
    const modal = document.getElementById('qrModal');
    const title = document.getElementById('qrModalTitle');
    const container = document.getElementById('qrCodeContainer');
    
    if (!modal || !container) {
        showMobileNotification('Error al abrir el modal de QR', 'error');
        return;
    }
    
    title.textContent = `Código QR - ${nombre}`;
    container.innerHTML = '<div class="text-center p-4"><i class="ri-loader-line animate-spin text-2xl"></i><br>Generando código QR...</div>';
    
    // Mostrar modal
    modal.classList.remove('hidden');
    
    // Generar QR usando el script original
    if (typeof QRCode !== 'undefined') {
        container.innerHTML = '';
        QRCode.toCanvas(container, url, {
            width: 256,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#FFFFFF'
            }
        }, function (error) {
            if (error) {
                container.innerHTML = '<div class="text-red-500 p-4"><i class="ri-error-warning-line"></i><br>Error al generar código QR</div>';
                console.error('Error generando QR:', error);
            }
        });
    }
}

function showDeleteModal(id, nombre) {
    const modal = document.getElementById('eliminarModal');
    const nameSpan = document.getElementById('nombreEnlaceEliminar');
    const idInput = document.getElementById('idEnlaceEliminar');
    
    if (modal && nameSpan && idInput) {
        nameSpan.textContent = nombre;
        idInput.value = id;
        modal.classList.remove('hidden');
    }
}
</script>

<?php include 'includes/footer.php'; ?>