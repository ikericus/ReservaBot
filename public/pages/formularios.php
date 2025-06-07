<?php
// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Configurar la página actual
$pageTitle = 'ReservaBot - Enlaces de Reserva';
$currentPage = 'formularios';
$pageScript = 'formularios';

// Mensaje de estado
$mensaje = '';
$tipoMensaje = '';

// Obtener usuario
$currentUser = getAuthenticatedUser();
$usuario_id =  $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Procesar eliminación de enlace
    if(isset($_POST['eliminar_enlace'])) {

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
    if (isset($_POST['crear_enlace'])) {

        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = ''; // Ya no usamos descripción
        $confirmacionAutomatica = isset($_POST['confirmacion_auto']) ? 1 : 0;
        
        if (!empty($nombre)) {
            try {
                // Generar slug único
                $slug = bin2hex(random_bytes(2));
                
                // Verificar que el slug sea único
                $stmt = getPDO()->prepare("SELECT id FROM formularios_publicos WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetch()) {
                    $slug .= '-' . time();
                }
                
                // Insertar en base de datos 
                $sql = 'INSERT INTO formularios_publicos (usuario_id, nombre, descripcion, empresa_nombre, empresa_logo, color_primario, color_secundario, mensaje_bienvenida, direccion, telefono_contacto, email_contacto, slug, activo, confirmacion_automatica, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())';

                $stmt = getPDO()->prepare($sql);
                $result = $stmt->execute([
                    $usuario_id,
                    $nombre,
                    '', // descripción vacía
                    $_POST['empresa_nombre'] ?? $currentUser['negocio'] ?? '',
                    $_POST['empresa_logo'] ?? '',
                    $_POST['color_primario'] ?? '#667eea',
                    $_POST['color_secundario'] ?? '#764ba2',
                    $_POST['mensaje_bienvenida'] ?? '',
                    $_POST['direccion'] ?? '',
                    $_POST['telefono_contacto'] ?? '',
                    $_POST['email_contacto'] ?? '',
                    $slug,
                    $confirmacionAutomatica
                ]);
                
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
}

// Obtener enlaces existentes
try {
    $stmt = getPDO()->prepare("SELECT * FROM formularios_publicos WHERE usuario_id = ? ORDER BY created_at DESC");
    $stmt->execute([$usuario_id]);
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
        <div>
            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                Nombre del enlace (solo para identificarlo)*
            </label>
            <input type="text" id="nombre" name="nombre" required
                class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Ej: Formulario Consulta General, Reservas Urgentes...">
            <p class="mt-1 text-xs text-gray-500">Solo para identificar este enlace en tu panel de control</p>
        </div>

        <!-- Buscar el formulario existente y añadir estos campos después del campo "descripcion" -->

        <!-- Información de la empresa -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mt-6 pt-6 border-t border-gray-200">
            <!-- <div class="sm:col-span-2">
                <h3 class="text-lg font-medium text-gray-900">🏢 Información de la empresa</h3>
            </div> -->
                        
            <div>
                <label for="empresa_nombre" class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre de la empresa *
                </label>
                <input
                    type="text"
                    name="empresa_nombre"
                    id="empresa_nombre"
                    required
                    class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Ej: Clínica Dental López"
                    value="<?php echo htmlspecialchars($currentUser['negocio'] ?? ''); ?>"
                >
            </div>
            
            <div>
                <label for="empresa_logo" class="block text-sm font-medium text-gray-700 mb-1">
                    URL del logo (opcional)
                </label>
                <input
                    type="url"
                    name="empresa_logo"
                    id="empresa_logo"
                    class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="https://ejemplo.com/logo.png"
                >
            </div>
            
            <div>
                <label for="direccion" class="block text-sm font-medium text-gray-700 mb-1">
                    Dirección
                </label>
                <input
                    type="text"
                    name="direccion"
                    id="direccion"
                    class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Calle Principal 123, Madrid"
                >
            </div>
            
            <div>
                <label for="telefono_contacto" class="block text-sm font-medium text-gray-700 mb-1">
                    Teléfono de contacto
                </label>
                <input
                    type="tel"
                    name="telefono_contacto"
                    id="telefono_contacto"
                    class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="+34 900 123 456"
                >
            </div>
        </div>

        <!-- Personalización visual -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mt-6 pt-6 border-t border-gray-200">
            <!-- <div class="sm:col-span-2">
                <h3 class="text-lg font-medium text-gray-900 ">🎨 Personalización visual</h3>
            </div> -->
            
            <div>
                <label for="color_primario" class="block text-sm font-medium text-gray-700 mb-1">
                    Color primario
                </label>
                <div class="flex items-center space-x-3">
                    <input
                        type="color"
                        name="color_primario"
                        id="color_primario"
                        value="#667eea"
                        class="h-10 w-16 border border-gray-300 rounded cursor-pointer"
                    >
                    <input
                        type="text"
                        id="color_primario_text"
                        value="#667eea"
                        class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm"
                        readonly
                    >
                </div>
            </div>
            
            <div>
                <label for="color_secundario" class="block text-sm font-medium text-gray-700 mb-1">
                    Color secundario
                </label>
                <div class="flex items-center space-x-3">
                    <input
                        type="color"
                        name="color_secundario"
                        id="color_secundario"
                        value="#764ba2"
                        class="h-10 w-16 border border-gray-300 rounded cursor-pointer"
                    >
                    <input
                        type="text"
                        id="color_secundario_text"
                        value="#764ba2"
                        class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm"
                        readonly
                    >
                </div>
            </div>
            
            <div class="sm:col-span-2">
                <label for="mensaje_bienvenida" class="block text-sm font-medium text-gray-700 mb-1">
                    Mensaje de bienvenida personalizado
                </label>
                <textarea
                    name="mensaje_bienvenida"
                    id="mensaje_bienvenida"
                    rows="3"
                    class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Ej: Bienvenido a nuestra clínica. Reserva tu cita de forma rápida y sencilla."
                ></textarea>
            </div>
        </div>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center">
                <input type="checkbox" id="confirmacion_auto" name="confirmacion_auto" 
                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="confirmacion_auto" class="ml-2 block text-sm text-gray-700">
                    Confirmación automática (las reservas se confirman automáticamente)
                </label>
            </div>
        </div>

        <div class="flex justify-end">
            <input type="text" name="crear_enlace" value="Crear Enlace" class="hidden">
            <button type="submit"
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
    
    <form method="post" class="w-full">
        <!-- Información básica -->
        <label class="mobile-form-label">Nombre del enlace (solo para identificarlo)*</label>
        <input type="text" name="nombre" required
            class="mobile-form-input"
            placeholder="Ej: Formulario Consulta General">
        <p class="text-xs text-gray-500 mb-4 -mt-3">Solo para identificar este enlace en tu panel</p>
        
        <!-- Información de la empresa -->
        <div class="mt-6 pt-4 border-t border-gray-200">
            <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                🏢 Información de la empresa
            </h3>
            
            <label class="mobile-form-label">Nombre de la empresa*</label>
            <input type="text" name="empresa_nombre" required
                class="mobile-form-input"
                placeholder="Ej: Clínica Dental López"
                value="<?php echo htmlspecialchars($currentUser['negocio'] ?? ''); ?>">
            
            <label class="mobile-form-label">URL del logo (opcional)</label>
            <input type="url" name="empresa_logo"
                   class="mobile-form-input"
                   placeholder="https://ejemplo.com/logo.png">
            
            <label class="mobile-form-label">Dirección</label>
            <input type="text" name="direccion"
                   class="mobile-form-input"
                   placeholder="Calle Principal 123, Madrid">
            
            <label class="mobile-form-label">Teléfono de contacto</label>
            <input type="tel" name="telefono_contacto"
                   class="mobile-form-input"
                   placeholder="+34 900 123 456">
            
            <label class="mobile-form-label">Email de contacto</label>
            <input type="email" name="email_contacto"
                   class="mobile-form-input"
                   placeholder="info@empresa.com">
        </div>
        
        <!-- Personalización visual -->
        <div class="mt-6 pt-4 border-t border-gray-200">
            <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                🎨 Personalización visual
            </h3>
            
            <label class="mobile-form-label">Color primario</label>
            <div class="flex gap-3 mb-4 w-full">
                <input type="color" name="color_primario" value="#667eea" id="color_primario_mobile"
                       class="w-12 h-10 border border-gray-300 rounded-lg cursor-pointer flex-shrink-0">
                <input type="text" id="color_primario_text_mobile" value="#667eea" readonly
                       class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm">
            </div>
            
            <label class="mobile-form-label">Color secundario</label>
            <div class="flex gap-3 mb-4 w-full">
                <input type="color" name="color_secundario" value="#764ba2" id="color_secundario_mobile"
                       class="w-12 h-10 border border-gray-300 rounded-lg cursor-pointer flex-shrink-0">
                <input type="text" id="color_secundario_text_mobile" value="#764ba2" readonly
                       class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm">
            </div>
            
            <label class="mobile-form-label">Mensaje de bienvenida personalizado</label>
            <textarea name="mensaje_bienvenida" rows="3"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm resize-vertical min-h-16"
                     placeholder="Ej: Bienvenido a nuestra clínica. Reserva tu cita de forma rápida y sencilla."></textarea>
        </div>
        
        <!-- Confirmación automática -->
        <div class="mobile-form-checkbox mt-4">
            <input type="checkbox" id="confirmacion_auto_mobile" name="confirmacion_auto">
            <label for="confirmacion_auto_mobile">
                Confirmación automática de reservas
            </label>
        </div>
        
        <input type="hidden" name="crear_enlace" value="1">
        <button type="submit" class="mobile-submit-btn mt-4">
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
            <div class="space-y-6">
                <?php foreach ($enlaces as $enlace): ?>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <!-- Cabecera del enlace -->
                        <div class="bg-gradient-to-r from-gray-50 to-blue-50 px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                            <i class="ri-link text-white text-sm"></i>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($enlace['nombre']); ?>
                                        </h3>
                                    </div>
                                    
                                    <div class="flex items-center space-x-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $enlace['confirmacion_automatica'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <i class="<?php echo $enlace['confirmacion_automatica'] ? 'ri-check-line' : 'ri-time-line'; ?> mr-1"></i>
                                            <?php echo $enlace['confirmacion_automatica'] ? 'Confirmación automática' : 'Confirmación manual'; ?>
                                        </span>
                                        
                                        <span class="text-xs text-gray-500">
                                            <i class="ri-calendar-line mr-1"></i>
                                            Creado: <?php echo date('d/m/Y H:i', strtotime($enlace['created_at'])); ?>
                                        </span>
                                        
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo $enlace['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <span class="w-1.5 h-1.5 <?php echo $enlace['activo'] ? 'bg-green-400' : 'bg-red-400'; ?> rounded-full mr-1"></span>
                                            <?php echo $enlace['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Botones de acción principales -->
                                <div class="flex items-center space-x-2">
                                    <?php 
                                    // Generar URL completa
                                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                                    $host = $_SERVER['HTTP_HOST'];
                                    $path = dirname($_SERVER['REQUEST_URI']);
                                    $baseUrl = $protocol . $host . $path;
                                    $enlaceCompleto = $baseUrl . 'reservar?f=' . $enlace['slug'];
                                    ?>
                                    
                                    <a href="<?php echo $enlaceCompleto; ?>" 
                                       target="_blank"
                                       class="inline-flex items-center px-3 py-2 border border-green-300 shadow-sm text-sm font-medium rounded-md text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                        <i class="ri-external-link-line mr-2"></i>
                                        Ver formulario
                                    </a>
                                    
                                    <button class="btn-copiar inline-flex items-center px-3 py-2 border border-blue-300 shadow-sm text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                            data-clipboard-text="<?php echo $enlaceCompleto; ?>">
                                        <i class="ri-file-copy-line mr-2"></i>
                                        Copiar enlace
                                    </button>
                                    
                                    <button class="btn-qr inline-flex items-center px-3 py-2 border border-purple-300 shadow-sm text-sm font-medium rounded-md text-purple-700 bg-white hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors"
                                            data-url="<?php echo $enlaceCompleto; ?>"
                                            data-nombre="<?php echo htmlspecialchars($enlace['nombre']); ?>">
                                        <i class="ri-qr-code-line mr-2"></i>
                                        Generar QR
                                    </button>
                                    
                                    <button class="btn-eliminar inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                                            data-id="<?php echo $enlace['id']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($enlace['nombre']); ?>">
                                        <i class="ri-delete-bin-line mr-2"></i>
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contenido del enlace -->
                        <div class="p-6">
                           
                            <!-- Información de la empresa -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                                <div class="space-y-4">
                                    <h4 class="text-sm font-semibold text-gray-900 flex items-center">
                                        <i class="ri-building-line mr-2 text-blue-600"></i>
                                        Información de la empresa
                                    </h4>
                                    
                                    <div class="space-y-3 pl-6">
                                        <div class="flex items-start">
                                            <span class="text-sm font-medium text-gray-700 w-20 flex-shrink-0">Nombre:</span>
                                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($enlace['empresa_nombre'] ?: 'No especificado'); ?></span>
                                        </div>
                                        
                                        <?php if (!empty($enlace['direccion'])): ?>
                                        <div class="flex items-start">
                                            <span class="text-sm font-medium text-gray-700 w-20 flex-shrink-0">Dirección:</span>
                                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($enlace['direccion']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($enlace['telefono_contacto'])): ?>
                                        <div class="flex items-start">
                                            <span class="text-sm font-medium text-gray-700 w-20 flex-shrink-0">Teléfono:</span>
                                            <span class="text-sm text-gray-900">
                                                <a href="tel:<?php echo htmlspecialchars($enlace['telefono_contacto']); ?>" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <?php echo htmlspecialchars($enlace['telefono_contacto']); ?>
                                                </a>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($enlace['email_contacto'])): ?>
                                        <div class="flex items-start">
                                            <span class="text-sm font-medium text-gray-700 w-20 flex-shrink-0">Email:</span>
                                            <span class="text-sm text-gray-900">
                                                <a href="mailto:<?php echo htmlspecialchars($enlace['email_contacto']); ?>" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <?php echo htmlspecialchars($enlace['email_contacto']); ?>
                                                </a>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($enlace['empresa_logo'])): ?>
                                        <div class="flex items-start">
                                            <span class="text-sm font-medium text-gray-700 w-20 flex-shrink-0">Logo:</span>
                                            <div class="flex items-center space-x-2">
                                                <img src="<?php echo htmlspecialchars($enlace['empresa_logo']); ?>" 
                                                     alt="Logo" 
                                                     class="h-8 w-auto object-contain border rounded">
                                                <a href="<?php echo htmlspecialchars($enlace['empresa_logo']); ?>" 
                                                   target="_blank" 
                                                   class="text-xs text-blue-600 hover:text-blue-800">
                                                    Ver imagen
                                                </a>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Personalización visual -->
                                <div class="space-y-4">
                                    <h4 class="text-sm font-semibold text-gray-900 flex items-center">
                                        <i class="ri-palette-line mr-2 text-purple-600"></i>
                                        Personalización visual
                                    </h4>
                                    
                                    <div class="space-y-3 pl-6">
                                        <div class="flex items-center">
                                            <span class="text-sm font-medium text-gray-700 w-24 flex-shrink-0">Color primario:</span>
                                            <div class="flex items-center space-x-2">
                                                <div class="w-6 h-6 rounded border border-gray-300" 
                                                     style="background-color: <?php echo htmlspecialchars($enlace['color_primario'] ?: '#667eea'); ?>"></div>
                                                <span class="text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($enlace['color_primario'] ?: '#667eea'); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center">
                                            <span class="text-sm font-medium text-gray-700 w-24 flex-shrink-0">Color secundario:</span>
                                            <div class="flex items-center space-x-2">
                                                <div class="w-6 h-6 rounded border border-gray-300" 
                                                     style="background-color: <?php echo htmlspecialchars($enlace['color_secundario'] ?: '#764ba2'); ?>"></div>
                                                <span class="text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($enlace['color_secundario'] ?: '#764ba2'); ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Vista previa del gradiente -->
                                        <div class="flex items-center">
                                            <span class="text-sm font-medium text-gray-700 w-24 flex-shrink-0">Vista previa:</span>
                                            <div class="w-32 h-8 rounded border border-gray-300" 
                                                 style="background: linear-gradient(135deg, <?php echo htmlspecialchars($enlace['color_primario'] ?: '#667eea'); ?> 0%, <?php echo htmlspecialchars($enlace['color_secundario'] ?: '#764ba2'); ?> 100%);">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mensaje de bienvenida -->
                            <?php if (!empty($enlace['mensaje_bienvenida'])): ?>
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold text-gray-900 flex items-center mb-3">
                                    <i class="ri-message-2-line mr-2 text-green-600"></i>
                                    Mensaje de bienvenida
                                </h4>
                                <div class="pl-6 p-3 bg-gray-50 rounded-md border-l-4 border-green-400">
                                    <p class="text-sm text-gray-700 italic">"<?php echo htmlspecialchars($enlace['mensaje_bienvenida']); ?>"</p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Estadísticas de uso (si las tienes) -->
                            <?php
                            // Opcional: obtener estadísticas de uso del formulario
                            try {
                                $stmt = getPDO()->prepare("SELECT COUNT(*) as total_reservas FROM reservas WHERE formulario_id = ?");
                                $stmt->execute([$enlace['id']]);
                                $stats = $stmt->fetch();
                                $totalReservas = $stats['total_reservas'] ?? 0;
                            } catch (Exception $e) {
                                $totalReservas = 0;
                            }
                            ?>
                            
                            <div class="pt-4 border-t border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-6">
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="ri-calendar-check-line mr-2 text-blue-600"></i>
                                            <span class="font-medium"><?php echo $totalReservas; ?></span>
                                            <span class="ml-1">reservas recibidas</span>
                                        </div>
                                        
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="ri-link mr-2 text-purple-600"></i>
                                            <span class="ml-1 font-mono"><?php echo $enlaceCompleto; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="text-xs text-gray-500">
                                        ID: <?php echo $enlace['id']; ?>
                                    </div>
                                </div>
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
                                <p class="form-card-description">Empresa: <?php echo htmlspecialchars($enlace['empresa_nombre'] ?: 'No especificado'); ?></p>
                                
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
                            
                            <!-- Información detallada móvil -->
                            <div class="px-4 pb-2">
                                <!-- Colores -->
                                <div class="mb-3">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Colores:</span>
                                        <div class="flex items-center space-x-2">
                                            <div class="w-4 h-4 rounded border" 
                                                 style="background-color: <?php echo htmlspecialchars($enlace['color_primario'] ?: '#667eea'); ?>"></div>
                                            <div class="w-4 h-4 rounded border" 
                                                 style="background-color: <?php echo htmlspecialchars($enlace['color_secundario'] ?: '#764ba2'); ?>"></div>
                                            <div class="w-12 h-4 rounded border" 
                                                 style="background: linear-gradient(135deg, <?php echo htmlspecialchars($enlace['color_primario'] ?: '#667eea'); ?> 0%, <?php echo htmlspecialchars($enlace['color_secundario'] ?: '#764ba2'); ?> 100%);">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Contacto -->
                                <?php if (!empty($enlace['telefono_contacto']) || !empty($enlace['email_contacto'])): ?>
                                <div class="mb-3 text-sm">
                                    <div class="text-gray-600 mb-1">Contacto:</div>
                                    <?php if (!empty($enlace['telefono_contacto'])): ?>
                                        <div class="flex items-center text-gray-700 mb-1">
                                            <i class="ri-phone-line mr-2 text-green-600"></i>
                                            <a href="tel:<?php echo htmlspecialchars($enlace['telefono_contacto']); ?>" 
                                               class="text-blue-600"><?php echo htmlspecialchars($enlace['telefono_contacto']); ?></a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($enlace['email_contacto'])): ?>
                                        <div class="flex items-center text-gray-700">
                                            <i class="ri-mail-line mr-2 text-blue-600"></i>
                                            <a href="mailto:<?php echo htmlspecialchars($enlace['email_contacto']); ?>" 
                                               class="text-blue-600 truncate"><?php echo htmlspecialchars($enlace['email_contacto']); ?></a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Estadísticas -->
                                <?php
                                try {
                                    $stmt = getPDO()->prepare("SELECT COUNT(*) as total_reservas FROM reservas WHERE formulario_id = ?");
                                    $stmt->execute([$enlace['id']]);
                                    $stats = $stmt->fetch();
                                    $totalReservas = $stats['total_reservas'] ?? 0;
                                } catch (Exception $e) {
                                    $totalReservas = 0;
                                }
                                ?>
                                <div class="mb-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">Reservas recibidas:</span>
                                        <span class="font-semibold text-blue-600"><?php echo $totalReservas; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php 
                            // Generar URL completa
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                            $host = $_SERVER['HTTP_HOST'];
                            $path = dirname($_SERVER['REQUEST_URI']);
                            $baseUrl = $protocol . $host . $path;
                            $enlaceCompleto = $baseUrl . 'reservar?f=' . $enlace['slug'];
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
                                    Copiar enlace
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
                    <input type="hidden" name="eliminar_enlace" value="Eliminar Enlace">
                    <button type="submit" name="eliminar_enlace" value="1" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
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

<!-- ClipboardJS -->
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>
<!-- QRCode.js -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>

<?php include 'includes/footer.php'; ?>