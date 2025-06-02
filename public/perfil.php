<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Proteger la página
requireAuth();

// Configurar la página actual
$currentPage = 'perfil';
$pageTitle = 'ReservaBot - Mi Perfil';
$pageScript = 'perfil';

// Obtener datos del usuario
$usuario = getAuthenticatedUser();
$mensaje = '';
$tipoMensaje = '';

// Procesar actualización del perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $negocio = trim($_POST['negocio'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validaciones básicas
    if (empty($nombre)) $errors[] = 'El nombre es obligatorio';
    if (empty($email)) $errors[] = 'El email es obligatorio';
    if (empty($telefono)) $errors[] = 'El teléfono es obligatorio';
    if (empty($negocio)) $errors[] = 'El nombre del negocio es obligatorio';
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del email no es válido';
    }
    
    // Validar cambio de contraseña si se proporciona
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = 'Debes proporcionar tu contraseña actual para cambiarla';
        }
        
        if (strlen($new_password) < 6) {
            $errors[] = 'La nueva contraseña debe tener al menos 6 caracteres';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'Las contraseñas nuevas no coinciden';
        }
    }
    
    if (empty($errors)) {
        try {            
            // Si hay cambio de contraseña, verificar la actual
            if (!empty($new_password)) {
                $stmt = getPDO()->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
                $stmt->execute([$usuario['id']]);
                $currentHash = $stmt->fetchColumn();
                
                if (!password_verify($current_password, $currentHash)) {
                    $errors[] = 'La contraseña actual es incorrecta';
                }
            }
            
            if (empty($errors)) {
                // Verificar si el email ya existe (para otro usuario)
                $stmt = getPDO()->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                $stmt->execute([$email, $usuario['id']]);
                if ($stmt->fetch()) {
                    $errors[] = 'Este email ya está siendo usado por otro usuario';
                } else {
                    // Actualizar datos del usuario
                    if (!empty($new_password)) {
                        $passwordHash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = getPDO()->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, negocio = ?, password_hash = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$nombre, $email, $telefono, $negocio, $passwordHash, $usuario['id']]);
                    } else {
                        $stmt = getPDO()->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, negocio = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$nombre, $email, $telefono, $negocio, $usuario['id']]);
                    }
                    
                    // Actualizar la sesión
                    $_SESSION['user_name'] = $nombre;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_negocio'] = $negocio;
                    
                    $mensaje = 'Perfil actualizado correctamente';
                    $tipoMensaje = 'success';
                    
                    // Actualizar datos para mostrar
                    $usuario['name'] = $nombre;
                    $usuario['email'] = $email;
                    $usuario['negocio'] = $negocio;
                }
            }
        } catch (Exception $e) {
            error_log("Error actualizando perfil: " . $e->getMessage());
            $errors[] = 'Error interno del servidor';
        }
    }
    
    if (!empty($errors)) {
        $mensaje = implode('<br>', $errors);
        $tipoMensaje = 'error';
    }
}

// Incluir la cabecera
include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Mi Perfil</h1>
</div>

<!-- Mensaje de estado -->
<?php if (!empty($mensaje)): ?>
    <div class="mb-6 p-4 rounded-md <?php echo $tipoMensaje === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="<?php echo $tipoMensaje === 'success' ? 'ri-check-line text-green-400' : 'ri-error-warning-line text-red-400'; ?>"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm <?php echo $tipoMensaje === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                    <?php echo $mensaje; ?>
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="max-w-4xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Información del perfil -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Información Personal</h2>
                
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre completo
                            </label>
                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                value="<?php echo htmlspecialchars($usuario['name']); ?>"
                            >
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                value="<?php echo htmlspecialchars($usuario['email']); ?>"
                            >
                        </div>
                        
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">
                                Teléfono
                            </label>
                            <input
                                type="tel"
                                id="telefono"
                                name="telefono"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>"
                            >
                        </div>
                        
                        <div>
                            <label for="negocio" class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre del negocio
                            </label>
                            <input
                                type="text"
                                id="negocio"
                                name="negocio"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                value="<?php echo htmlspecialchars($usuario['negocio']); ?>"
                            >
                        </div>
                    </div>
                    
                    <!-- Cambio de contraseña -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-base font-medium text-gray-900 mb-4">Cambiar Contraseña</h3>
                        <p class="text-sm text-gray-500 mb-4">Deja estos campos vacíos si no quieres cambiar tu contraseña</p>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Contraseña actual
                                </label>
                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                >
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                                        Nueva contraseña
                                    </label>
                                    <input
                                        type="password"
                                        id="new_password"
                                        name="new_password"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    >
                                </div>
                                
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                                        Confirmar nueva contraseña
                                    </label>
                                    <input
                                        type="password"
                                        id="confirm_password"
                                        name="confirm_password"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <i class="ri-save-line mr-2"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Panel lateral -->
        <div class="space-y-6">
            <!-- Información de la cuenta -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Información de la Cuenta</h3>
                
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Plan actual</dt>
                        <dd class="text-sm text-gray-900">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <?php echo ucfirst($usuario['plan']); ?>
                            </span>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Miembro desde</dt>
                        <dd class="text-sm text-gray-900">
                            <?php 
                            if (isset($usuario['created_at'])) {
                                echo date('d/m/Y', strtotime($usuario['created_at']));
                            } else {
                                echo 'Información no disponible';
                            }
                            ?>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Último acceso</dt>
                        <dd class="text-sm text-gray-900">
                            <?php 
                            if (isset($usuario['last_activity'])) {
                                echo date('d/m/Y H:i', $usuario['last_activity']);
                            } else {
                                echo 'Ahora';
                            }
                            ?>
                        </dd>
                    </div>
                </dl>
            </div>
            
            <!-- Acciones de la cuenta -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Acciones de la Cuenta</h3>
                
                <div class="space-y-3">
                    <a href="/configuracion" class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                        <i class="ri-settings-line mr-2"></i>
                        Configuración General
                    </a>
                    
                    <a href="/logout" class="block w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md">
                        <i class="ri-logout-box-line mr-2"></i>
                        Cerrar Sesión
                    </a>
                </div>
            </div>
            
            <!-- Soporte -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">¿Necesitas ayuda?</h3>
                
                <div class="space-y-3">
                    <p class="text-sm text-gray-600">
                        Si tienes alguna pregunta o necesitas soporte, no dudes en contactarnos.
                    </p>
                    
                    <a href="mailto:soporte@reservabot.es" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="ri-mail-line mr-2"></i>
                        Contactar Soporte
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>