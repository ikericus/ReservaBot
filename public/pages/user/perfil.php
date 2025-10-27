<?php
// pages/user/perfil.php

$currentPage = 'perfil';
$pageTitle = 'ReservaBot - Mi Perfil';
$pageScript = 'perfil';

// Obtener datos del usuario
$usuario = getAuthenticatedUser();
$mensaje = '';
$tipoMensaje = '';

// Procesar actualización del perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    $errors = [];
    
    // ACTUALIZAR INFORMACIÓN PERSONAL
    if ($accion === 'actualizar_info') {
        $nombre = trim($_POST['nombre'] ?? '');
        $negocio = trim($_POST['negocio'] ?? '');
        
        // Validaciones básicas
        if (empty($nombre)) $errors[] = 'El nombre es obligatorio';
        if (empty($negocio)) $errors[] = 'El nombre del negocio es obligatorio';
        
        if (empty($errors)) {
            try {
                $stmt = getPDO()->prepare("UPDATE usuarios SET nombre = ?, negocio = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$nombre, $negocio, $usuario['id']]);
                
                // Actualizar la sesión
                $_SESSION['user_name'] = $nombre;
                $_SESSION['user_negocio'] = $negocio;
                
                $mensaje = 'Información actualizada correctamente';
                $tipoMensaje = 'success';
                
                // Actualizar datos para mostrar
                $usuario['name'] = $nombre;
                $usuario['negocio'] = $negocio;
            } catch (Exception $e) {
                error_log("Error actualizando información: " . $e->getMessage());
                $errors[] = 'Error interno del servidor';
            }
        }
    }
    
    // CAMBIAR CONTRASEÑA
    if ($accion === 'cambiar_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password)) {
            $errors[] = 'Debes proporcionar tu contraseña actual';
        }
        
        if (strlen($new_password) < 6) {
            $errors[] = 'La nueva contraseña debe tener al menos 6 caracteres';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'Las contraseñas nuevas no coinciden';
        }
        
        if (empty($errors)) {
            try {
                $stmt = getPDO()->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
                $stmt->execute([$usuario['id']]);
                $currentHash = $stmt->fetchColumn();
                
                if (!password_verify($current_password, $currentHash)) {
                    $errors[] = 'La contraseña actual es incorrecta';
                } else {
                    $passwordHash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = getPDO()->prepare("UPDATE usuarios SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$passwordHash, $usuario['id']]);
                    
                    $mensaje = 'Contraseña actualizada correctamente';
                    $tipoMensaje = 'success';
                }
            } catch (Exception $e) {
                error_log("Error cambiando contraseña: " . $e->getMessage());
                $errors[] = 'Error interno del servidor';
            }
        }
    }
    
    // CAMBIAR PLAN
    if ($accion === 'cambiar_plan') {
        $nuevoPlan = $_POST['plan'] ?? '';
        
        // Validar que el plan sea válido
        $planesValidos = ['gratis', 'estandar'];
        if (!in_array($nuevoPlan, $planesValidos)) {
            $errors[] = 'Plan no válido';
        }
        
        if (empty($errors)) {
            try {
                $stmt = getPDO()->prepare("UPDATE usuarios SET plan = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$nuevoPlan, $usuario['id']]);
                
                $mensaje = 'Plan actualizado correctamente';
                $tipoMensaje = 'success';
                
                // Actualizar datos para mostrar
                $usuario['plan'] = $nuevoPlan;
            } catch (Exception $e) {
                error_log("Error cambiando plan: " . $e->getMessage());
                $errors[] = 'Error interno del servidor';
            }
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

<div class="max-w-6xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Columna Principal (2/3) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Información Personal -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6 flex items-center">
                    <i class="ri-user-line mr-2 text-blue-600"></i>
                    Información Personal
                </h2>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="accion" value="actualizar_info">
                    
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
                    
                    <div>
                        <label for="email_display" class="block text-sm font-medium text-gray-700 mb-1">
                            Email
                        </label>
                        <input
                            type="email"
                            id="email_display"
                            disabled
                            class="block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm sm:text-sm cursor-not-allowed"
                            value="<?php echo htmlspecialchars($usuario['email']); ?>"
                        >
                        <p class="mt-1 text-xs text-gray-500">
                            <i class="ri-information-line"></i>
                            El email no se puede modificar
                        </p>
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
            
            <!-- Cambio de Contraseña -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6 flex items-center">
                    <i class="ri-lock-password-line mr-2 text-blue-600"></i>
                    Cambiar Contraseña
                </h2>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="accion" value="cambiar_password">
                    
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                            Contraseña actual
                        </label>
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            placeholder="Ingresa tu contraseña actual"
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
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="Mínimo 6 caracteres"
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
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="Repite la nueva contraseña"
                            >
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <i class="ri-lock-unlock-line mr-2"></i>
                            Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
        
        <!-- Columna Lateral (1/3) -->
        <div class="space-y-6">
            
            <!-- Información de la cuenta -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4 flex items-center">
                    <i class="ri-information-line mr-2 text-blue-600"></i>
                    Información de la Cuenta
                </h3>
                
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Plan actual</dt>
                        <dd class="text-sm text-gray-900 mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $usuario['plan'] === 'estandar' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo $usuario['plan'] === 'estandar' ? 'Profesional' : 'Básico'; ?>
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
                        <dt class="text-sm font-medium text-gray-500">Estado</dt>
                        <dd class="text-sm text-gray-900">
                            <span class="inline-flex items-center">
                                <span class="h-2 w-2 bg-green-400 rounded-full mr-2"></span>
                                Activo
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
            
            <!-- Gestión de Plan -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4 flex items-center">
                    <i class="ri-vip-crown-line mr-2 text-blue-600"></i>
                    Cambiar Plan
                </h3>
                
                <form method="POST">
                    <input type="hidden" name="accion" value="cambiar_plan">
                    
                    <div class="space-y-3">
                        <!-- Plan Básico -->
                        <label class="relative flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors <?php echo $usuario['plan'] === 'gratis' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>">
                            <input 
                                type="radio" 
                                name="plan" 
                                value="gratis" 
                                class="mt-1"
                                <?php echo $usuario['plan'] === 'gratis' ? 'checked' : ''; ?>
                            >
                            <div class="ml-3 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-900">Básico</span>
                                    <span class="text-lg font-bold text-gray-900">0€</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    Reservas por formulario web, calendario básico
                                </p>
                            </div>
                        </label>
                        
                        <!-- Plan Profesional -->
                        <label class="relative flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors <?php echo $usuario['plan'] === 'estandar' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>">
                            <input 
                                type="radio" 
                                name="plan" 
                                value="estandar" 
                                class="mt-1"
                                <?php echo $usuario['plan'] === 'estandar' ? 'checked' : ''; ?>
                            >
                            <div class="ml-3 flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center">
                                        <span class="text-sm font-semibold text-gray-900">Profesional</span>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Recomendado
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs line-through text-gray-400">9€</span>
                                        <span class="ml-1 text-lg font-bold text-green-600">GRATIS</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500">
                                    WhatsApp, agenda completa, recordatorios automáticos
                                </p>
                                <p class="text-xs text-red-600 font-medium mt-1">
                                    🎉 Gratis durante la beta
                                </p>
                            </div>
                        </label>
                        
                        <!-- Plan Premium (Deshabilitado) -->
                        <div class="relative flex items-start p-4 border-2 border-gray-200 rounded-lg opacity-60 cursor-not-allowed">
                            <input 
                                type="radio" 
                                name="plan" 
                                value="premium" 
                                disabled
                                class="mt-1"
                            >
                            <div class="ml-3 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-900">Automático</span>
                                    <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full font-semibold">
                                        Próximamente
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    IA automática, respuestas inteligentes, analytics avanzados
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <button
                        type="submit"
                        class="w-full mt-4 inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <i class="ri-refresh-line mr-2"></i>
                        Cambiar Plan
                    </button>
                </form>
                
                <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-xs text-blue-800 flex items-start">
                        <i class="ri-information-line mr-1 mt-0.5 flex-shrink-0"></i>
                        <span>Durante la fase beta, todos los planes están disponibles de forma gratuita.</span>
                    </p>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
// Validación de contraseñas en tiempo real
document.getElementById('confirm_password')?.addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Las contraseñas no coinciden');
        this.classList.add('border-red-300');
    } else {
        this.setCustomValidity('');
        this.classList.remove('border-red-300');
    }
});

document.getElementById('new_password')?.addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value) {
        confirmPassword.dispatchEvent(new Event('input'));
    }
});
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>