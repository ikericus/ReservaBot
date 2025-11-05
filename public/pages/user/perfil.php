<?php
// pages/user/perfil.php

$currentPage = 'perfil';
$pageTitle = 'ReservaBot - Mi Perfil';
$pageScript = 'perfil';

// Obtener el tab activo desde la URL
$tabActivo = isset($_GET['tab']) ? $_GET['tab'] : 'info';
$tabsValidos = ['info', 'password', 'cuenta', 'plan'];
if (!in_array($tabActivo, $tabsValidos)) {
    $tabActivo = 'info';
}

?>
<style>
/* Estilos responsivos para perfil - Mobile First */
* {
    box-sizing: border-box;
}

main {
    max-width: 100vw;
    overflow-x: hidden;
}

/* Tabs Styling */
.tabs {
    display: flex;
    gap: 0.25rem;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 1.5rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.tabs::-webkit-scrollbar {
    display: none;
}

.tab-button {
    padding: 0.75rem 1rem;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #6b7280;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
    text-decoration: none;
}

.tab-button:hover {
    color: #374151;
    background: #f9fafb;
}

.tab-button.active {
    color: #667eea;
    border-bottom-color: #667eea;
    background: linear-gradient(to bottom, rgba(102, 126, 234, 0.05), transparent);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    /* Contenedor principal móvil */
    main .max-w-4xl {
        max-width: 100%;
        margin: 0;
        padding: 0 0.75rem;
    }
    
    .tabs {
        margin-left: -0.75rem;
        margin-right: -0.75rem;
        padding: 0 0.75rem;
        gap: 0.25rem;
    }
    
    .tab-button {
        padding: 0.625rem 0.75rem;
        font-size: 0.875rem;
        gap: 0.375rem;
    }
    
    /* Tarjetas de sección mejoradas */
    main .bg-white.rounded-lg.shadow-sm {
        border-radius: 0.75rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }
    
    /* Títulos de sección */
    main .bg-white h2,
    main .bg-white h3 {
        font-size: 1.125rem;
        line-height: 1.4;
        margin-bottom: 1rem;
        word-wrap: break-word;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    main .bg-white h2 i,
    main .bg-white h3 i {
        flex-shrink: 0;
        font-size: 1.25rem;
    }
    
    /* Formularios - estilo mejorado */
    main form {
        width: 100%;
        max-width: 100%;
    }
    
    /* Labels mejorados */
    main label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
        display: block;
        word-wrap: break-word;
    }
    
    /* Inputs mejorados */
    main input[type="text"],
    main input[type="email"],
    main input[type="password"] {
        width: 100%;
        max-width: 100%;
        padding: 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        font-size: 1rem;
        transition: all 0.2s ease;
        background: white;
        margin-bottom: 0.5rem;
        box-sizing: border-box;
    }
    
    main input[type="text"]:focus,
    main input[type="email"]:focus,
    main input[type="password"]:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    main input:disabled {
        background: #f9fafb;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    /* Grid de inputs - stack en móvil */
    main .grid.grid-cols-2 {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    main .grid.grid-cols-2 > div {
        width: 100%;
    }
    
    /* Textos de ayuda */
    main .text-xs.text-gray-500 {
        font-size: 0.8125rem;
        margin-top: 0.25rem;
        color: #6b7280;
        word-wrap: break-word;
    }
    
    /* Botones mejorados */
    main button[type="submit"] {
        width: 100%;
        max-width: 100%;
        padding: 0.875rem;
        border-radius: 0.5rem;
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
        box-sizing: border-box;
        margin-top: 1rem;
    }
    
    main button[type="submit"]:active {
        transform: scale(0.98);
    }
    
    /* Sección de información de cuenta */
    main dl.space-y-3 > div {
        padding: 0.75rem;
        background: #f9fafb;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        border: 1px solid #e5e7eb;
    }
    
    main dl dt {
        font-size: 0.8125rem;
        font-weight: 500;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    
    main dl dd {
        font-size: 0.9375rem;
        color: #1f2937;
        font-weight: 500;
    }
    
    /* Badges */
    main .inline-flex.items-center.px-2\\.5 {
        font-size: 0.75rem;
        padding: 0.375rem 0.75rem;
        border-radius: 9999px;
        font-weight: 500;
    }
    
    /* Radio buttons de planes mejorados */
    main label.relative.flex {
        flex-direction: row;
        padding: 1rem;
        border-radius: 0.75rem;
        margin-bottom: 0.75rem;
        transition: all 0.2s ease;
        overflow: hidden;
    }
    
    main label.relative.flex input[type="radio"] {
        margin-top: 0.25rem;
        flex-shrink: 0;
        width: 1.125rem;
        height: 1.125rem;
    }
    
    main label.relative.flex .ml-3 {
        margin-left: 0.75rem;
        flex: 1;
        min-width: 0;
    }
    
    main label.relative.flex .text-sm {
        font-size: 0.875rem;
    }
    
    main label.relative.flex .text-xs {
        font-size: 0.8125rem;
        line-height: 1.4;
        word-wrap: break-word;
    }
    
    main label.relative.flex .text-lg {
        font-size: 1.125rem;
    }
    
    /* Nota informativa mejorada */
    main .p-3.bg-blue-50 {
        padding: 0.875rem;
        border-radius: 0.5rem;
        margin-top: 1rem;
    }
    
    main .p-3.bg-blue-50 .text-xs {
        font-size: 0.8125rem;
        line-height: 1.4;
    }
    
    /* Mensaje de alerta/éxito mejorado */
    main .mb-6.p-4.rounded-md {
        margin-bottom: 1rem;
        padding: 1rem;
        border-radius: 0.5rem;
        word-wrap: break-word;
    }
    
    /* Prevenir overflow */
    main *,
    main p,
    main span,
    main div {
        word-wrap: break-word;
        overflow-wrap: break-word;
        max-width: 100%;
    }
}

@media (max-width: 640px) {
    .tab-button span {
        display: none;
    }
    
    .tab-button {
        justify-content: center;
        min-width: 3rem;
    }
}
</style>
<?php

$usuarioDomain = getContainer()->getUsuarioDomain();

// Obtener datos del usuario autenticado
$usuarioAuth = getAuthenticatedUser();
$usuarioEntity = $usuarioDomain->obtenerPorId($usuarioAuth['id']);

if (!$usuarioEntity) {
    header('Location: /logout');
    exit;
}

$mensaje = '';
$tipoMensaje = '';

// Procesar actualización del perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        // ACTUALIZAR INFORMACIÓN PERSONAL
        if ($accion === 'actualizar_info') {
            $nombre = trim($_POST['nombre'] ?? '');
            
            // Usar el método del dominio (manteniendo email, teléfono y negocio actuales)
            $usuarioDomain->actualizarPerfil(
                $usuarioEntity->getId(),
                $nombre,
                $usuarioEntity->getEmail(),
                $usuarioEntity->getTelefono(),
                $usuarioEntity->getNegocio()
            );
            
            // Actualizar la sesión
            $_SESSION['user_name'] = $nombre;
            
            $mensaje = 'Información actualizada correctamente';
            $tipoMensaje = 'success';
            
            // Recargar datos
            $usuarioEntity = $usuarioDomain->obtenerPorId($usuarioAuth['id']);
        }
        
        // CAMBIAR CONTRASEÑA
        if ($accion === 'cambiar_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if ($new_password !== $confirm_password) {
                throw new \InvalidArgumentException('Las contraseñas nuevas no coinciden');
            }
            
            // Usar el método del dominio
            $usuarioDomain->cambiarPassword(
                $usuarioEntity->getId(),
                $current_password,
                $new_password
            );
            
            $mensaje = 'Contraseña actualizada correctamente';
            $tipoMensaje = 'success';
        }
        
        // CAMBIAR PLAN
        if ($accion === 'cambiar_plan') {
            $nuevoPlan = $_POST['plan'] ?? '';
            
            // Usar el método del dominio
            $usuarioDomain->actualizarPlan(
                $usuarioEntity->getId(),
                $nuevoPlan
            );
            
            $mensaje = 'Plan actualizado correctamente';
            $tipoMensaje = 'success';
            
            // Recargar datos
            $usuarioEntity = $usuarioDomain->obtenerPorId($usuarioAuth['id']);
        }
        
    } catch (\InvalidArgumentException $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'error';
    } catch (\DomainException $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'error';
    } catch (\Exception $e) {
        error_log("Error en perfil: " . $e->getMessage());
        $mensaje = 'Error interno del servidor';
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
                    <?php echo htmlspecialchars($mensaje); ?>
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="max-w-4xl mx-auto px-4">
    
    <!-- Tabs Navigation -->
    <div class="tabs">
        <a href="/perfil?tab=info" class="tab-button <?php echo $tabActivo === 'info' ? 'active' : ''; ?>">
            <i class="ri-user-line"></i>
            <span>Información Personal</span>
        </a>
        <a href="/perfil?tab=password" class="tab-button <?php echo $tabActivo === 'password' ? 'active' : ''; ?>">
            <i class="ri-lock-password-line"></i>
            <span>Cambiar Contraseña</span>
        </a>
        <a href="/perfil?tab=cuenta" class="tab-button <?php echo $tabActivo === 'cuenta' ? 'active' : ''; ?>">
            <i class="ri-information-line"></i>
            <span>Información de Cuenta</span>
        </a>
        <a href="/perfil?tab=plan" class="tab-button <?php echo $tabActivo === 'plan' ? 'active' : ''; ?>">
            <i class="ri-vip-crown-line"></i>
            <span>Cambiar Plan</span>
        </a>
    </div>

    <!-- TAB 1: Información Personal -->
    <div class="tab-content <?php echo $tabActivo === 'info' ? 'active' : ''; ?>">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6 flex items-center">
                <i class="ri-user-line mr-2 text-blue-600"></i>
                Información Personal
            </h2>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="accion" value="actualizar_info">
                
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre completo *
                    </label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        value="<?php echo htmlspecialchars($usuarioEntity->getNombre()); ?>"
                    >
                </div>
                
                <div>
                    <label for="email_display" class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input
                        type="email"
                        id="email_display"
                        disabled
                        class="block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm cursor-not-allowed"
                        value="<?php echo htmlspecialchars($usuarioEntity->getEmail()); ?>"
                    >
                    <p class="mt-1 text-xs text-gray-500">
                        <i class="ri-information-line"></i>
                        El email no se puede modificar
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre del negocio
                    </label>
                    <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-md">
                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($usuarioEntity->getNegocio()); ?></span>
                        <a href="/configuracion" class="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center">
                            <i class="ri-settings-3-line mr-1"></i>
                            Modificar en Configuración
                        </a>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        <i class="ri-information-line"></i>
                        El nombre del negocio se configura desde la página de Configuración
                    </p>
                </div>
                
                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center px-6 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <i class="ri-save-line mr-2"></i>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 2: Cambiar Contraseña -->
    <div class="tab-content <?php echo $tabActivo === 'password' ? 'active' : ''; ?>">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6 flex items-center">
                <i class="ri-lock-password-line mr-2 text-blue-600"></i>
                Cambiar Contraseña
            </h2>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="accion" value="cambiar_password">
                
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Contraseña actual *
                    </label>
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Ingresa tu contraseña actual"
                    >
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Nueva contraseña *
                        </label>
                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            required
                            minlength="6"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Mínimo 6 caracteres"
                        >
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirmar nueva contraseña *
                        </label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            required
                            minlength="6"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Repite la nueva contraseña"
                        >
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center px-6 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <i class="ri-lock-unlock-line mr-2"></i>
                        Cambiar Contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 3: Información de Cuenta -->
    <div class="tab-content <?php echo $tabActivo === 'cuenta' ? 'active' : ''; ?>">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6 flex items-center">
                <i class="ri-information-line mr-2 text-blue-600"></i>
                Información de la Cuenta
            </h2>
            
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Plan actual</dt>
                    <dd class="text-sm text-gray-900 mt-1 flex items-center justify-between">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $usuarioEntity->getPlan() === 'estandar' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $usuarioEntity->getPlan() === 'estandar' ? 'Profesional' : 'Básico'; ?>
                        </span>
                        <a href="/perfil?tab=plan" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                            Cambiar plan →
                        </a>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Negocio</dt>
                    <dd class="text-sm text-gray-900 mt-1">
                        <?php echo htmlspecialchars($usuarioEntity->getNegocio()); ?>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Miembro desde</dt>
                    <dd class="text-sm text-gray-900 mt-1">
                        <?php echo $usuarioEntity->getCreatedAt()->format('d/m/Y'); ?>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Estado</dt>
                    <dd class="text-sm text-gray-900 mt-1">
                        <span class="inline-flex items-center">
                            <span class="h-2 w-2 bg-green-400 rounded-full mr-2"></span>
                            Activo
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- TAB 4: Cambiar Plan -->
    <div class="tab-content <?php echo $tabActivo === 'plan' ? 'active' : ''; ?>">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-6 flex items-center">
                <i class="ri-vip-crown-line mr-2 text-blue-600"></i>
                Cambiar Plan
            </h2>
            
            <form method="POST">
                <input type="hidden" name="accion" value="cambiar_plan">
                
                <div class="space-y-3">
                    <!-- Plan Básico -->
                    <label class="relative flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors <?php echo $usuarioEntity->getPlan() === 'gratis' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>">
                        <input 
                            type="radio" 
                            name="plan" 
                            value="gratis" 
                            class="mt-1"
                            <?php echo $usuarioEntity->getPlan() === 'gratis' ? 'checked' : ''; ?>
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
                    <label class="relative flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors <?php echo $usuarioEntity->getPlan() === 'estandar' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>">
                        <input 
                            type="radio" 
                            name="plan" 
                            value="estandar" 
                            class="mt-1"
                            <?php echo $usuarioEntity->getPlan() === 'estandar' ? 'checked' : ''; ?>
                        >
                        <div class="ml-3 flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center flex-wrap gap-2">
                                    <span class="text-sm font-semibold text-gray-900">Profesional</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
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
                    class="w-full mt-4 inline-flex justify-center items-center px-6 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
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