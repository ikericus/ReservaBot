<?php
// pages/formularios.php

// Configurar la página actual
$pageTitle = 'ReservaBot - Enlaces de Reserva';
$currentPage = 'formularios';
$pageScript = 'formularios';

// Obtener usuario
$currentUser = getAuthenticatedUser();
$usuario_id = $currentUser['id'];

$formularioDomain = getContainer()->getFormularioDomain();

// Obtener enlaces existentes
try {
    $formulariosEntities = $formularioDomain->obtenerFormulariosUsuario($usuario_id);
    $enlaces = array_map(fn($f) => $f->toArray(), $formulariosEntities);
} catch (Exception $e) {
    setFlashError('Error al obtener formularios: ' . $e->getMessage());
    $enlaces = [];
}

// Incluir cabecera
include 'includes/header.php';
?>

<style>
    
/* Animación de spinner */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Estilos específicos para móvil - Formularios */
/* Correcciones de responsividad para móvil - Reemplazar en @media (max-width: 768px) */

/* Base responsive */
* {
    box-sizing: border-box;
}

.mobile-view {
    width: 100%;
    max-width: 100vw;
    overflow-x: hidden;
}

/* Formulario colapsable */
.collapsible-form {
    transition: all 0.3s ease;
}

.collapsible-form.collapsed {
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    margin-bottom: 0;
    padding-top: 0;
    padding-bottom: 0;
}

.collapsible-form.expanded {
    max-height: 5000px;
    opacity: 1;
}

.toggle-form-btn {
    transition: all 0.2s ease;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.toggle-form-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.toggle-form-btn i.ri-arrow-down-s-line {
    transition: transform 0.3s ease;
    font-size: 1.25rem;
}

.toggle-form-btn.active i.ri-arrow-down-s-line {
    transform: rotate(180deg);
}

/* Tarjetas de formularios móviles */
.form-mobile-card {
    margin: 0.75rem 0;
    border-radius: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid rgba(0, 0, 0, 0.05);
    overflow: hidden;
    position: relative;
    /* Garantizar que no se salga del contenedor */
    width: 100%;
    max-width: 100%;
}

.form-mobile-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    transform: translateY(-3px);
}

.form-card-header {
    padding: 1rem;
    position: relative;
    width: 100%;
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
    flex-shrink: 0;
}

.form-card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.25rem 0;
    line-height: 1.3;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.form-card-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0 0 0.75rem 0;
    line-height: 1.4;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.form-card-badges {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    width: 100%;
}

.form-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
    flex-shrink: 0;
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

/* URL del formulario */
.form-card-url {
    background: rgba(102, 126, 234, 0.05);
    padding: 0.75rem;
    border-radius: 0.75rem;
    margin: 1rem;
    border: 1px solid rgba(102, 126, 234, 0.1);
    /* Prevenir overflow horizontal */
    width: calc(100% - 2rem);
    max-width: calc(100% - 2rem);
    overflow: hidden;
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
    /* Prevenir overflow */
    overflow-wrap: break-word;
    word-wrap: break-word;
    hyphens: auto;
}

/* Información detallada móvil - corregir ancho */
.form-mobile-card .px-4 {
    padding-left: 1rem;
    padding-right: 1rem;
    width: 100%;
    max-width: 100%;
}

/* Botones de acción */
.form-card-actions {
    padding: 0 1rem 1rem 1rem;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    width: 100%;
}

.form-action-btn {
    flex: 1;
    min-width: calc(50% - 0.25rem);
    max-width: calc(50% - 0.25rem);
    padding: 0.625rem 0.5rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    text-decoration: none;
    border: 1px solid;
    cursor: pointer;
    background: white;
    /* Prevenir que se salgan */
    box-sizing: border-box;
    white-space: nowrap;
    overflow: hidden;
}

/* En pantallas muy pequeñas, hacer botones de ancho completo */
@media (max-width: 380px) {
    .form-action-btn {
        flex: 1 1 100%;
        min-width: 100%;
        max-width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .form-action-btn:last-child {
        margin-bottom: 0;
    }
}

.form-action-btn:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.5);
}

.form-btn-view {
    border-color: #10b981;
    color: #047857;
    background: white;
}

.form-btn-view:hover {
    background: #ecfdf5;
    color: #065f46;
    text-decoration: none;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
}

.form-btn-copy {
    border-color: #3b82f6;
    color: #1d4ed8;
    background: white;
}

.form-btn-copy:hover {
    background: #eff6ff;
    color: #1e40af;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.form-btn-qr {
    border-color: #8b5cf6;
    color: #7c3aed;
    background: white;
}

.form-btn-qr:hover {
    background: #f3e8ff;
    color: #6d28d9;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(139, 92, 246, 0.2);
}

.form-btn-delete {
    border-color: #ef4444;
    color: #dc2626;
    background: white;
}

.form-btn-delete:hover {
    background: #fef2f2;
    color: #b91c1c;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
}

/* Formulario de creación mobile */
.mobile-create-form {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

.mobile-form-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    word-wrap: break-word;
}

.mobile-form-title i {
    color: #667eea;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.mobile-form-input {
    width: 100%;
    max-width: 100%;
    padding: 0.875rem;
    border-radius: 0.75rem;
    border: 2px solid #e5e7eb;
    font-size: 1rem;
    transition: all 0.2s ease;
    background: white;
    margin-bottom: 1rem;
    box-sizing: border-box;
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
    word-wrap: break-word;
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
    width: 100%;
    max-width: 100%;
}

.mobile-form-checkbox input {
    width: 1.25rem;
    height: 1.25rem;
    accent-color: #667eea;
    flex-shrink: 0;
}

.mobile-form-checkbox label {
    font-size: 0.875rem;
    color: #374151;
    margin: 0;
    flex: 1;
    line-height: 1.4;
}

.mobile-submit-btn {
    width: 100%;
    max-width: 100%;
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
    box-sizing: border-box;
}

.mobile-submit-btn:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Textarea responsive */
.mobile-create-form textarea {
    width: 100%;
    max-width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    resize: vertical;
    min-height: 4rem;
    box-sizing: border-box;
}

.mobile-create-form textarea:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 1px #667eea;
    outline: none;
}

/* Estados de mensajes */
.mobile-message {
    padding: 1rem;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.875rem;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    word-wrap: break-word;
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
    width: 100%;
    max-width: 100%;
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
    word-wrap: break-word;
}

.mobile-empty-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 1.5rem;
    word-wrap: break-word;
}

/* Padding del contenedor principal móvil */
.mobile-view .p-4 {
    padding: 1rem;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

/* Prevenir overflow en todas las secciones */
.mobile-view h2,
.mobile-view h3,
.mobile-view p,
.mobile-view span,
.mobile-view div {
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
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

/* Botones de toggle estado en móvil */
.form-btn-toggle-on {
    border-color: #10b981;
    color: #047857;
    background: white;
}

.form-btn-toggle-on:hover {
    background: #ecfdf5;
    color: #065f46;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
}

.form-btn-toggle-off {
    border-color: #f59e0b;
    color: #d97706;
    background: white;
}

.form-btn-toggle-off:hover {
    background: #fef3c7;
    color: #b45309;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
}

.form-btn-edit {
    border-color: #6366f1;
    color: #4f46e5;
    background: white;
}

.form-btn-edit:hover {
    background: #eef2ff;
    color: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
}
</style>

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

<!-- Botón para expandir/colapsar formulario - Vista Desktop -->
<div class="desktop-view mb-4">
    <button type="button" id="toggleFormDesktop" class="toggle-form-btn">
        <i class="ri-add-circle-line"></i>
        <span>Crear Nuevo Enlace de Reserva</span>
        <i class="ri-arrow-down-s-line"></i>
    </button>
</div>

<!-- Formulario para crear nuevo enlace - Vista Desktop -->
<div class="desktop-view bg-white rounded-lg shadow-sm p-6 mb-6 collapsible-form collapsed" id="formContainerDesktop">
    <h2 class="text-lg font-medium text-gray-900 mb-4">Crear Nuevo Enlace de Reserva</h2>
    
    <form method="post" class="space-y-4">
        <div>
            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                Nombre del enlace *
            </label>
            <input type="text" id="nombre" name="nombre" required
                class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Ej: Formulario Consulta General, Reservas Urgentes...">
            <p class="mt-1 text-xs text-gray-500">Solo para identificar este enlace en tu panel de control</p>
        </div>

        <!-- Mensaje de bienvenida -->
        <div>
            <label for="mensaje_bienvenida" class="block text-sm font-medium text-gray-700 mb-1">
                Mensaje de bienvenida personalizado (opcional)
            </label>
            <textarea
                name="mensaje_bienvenida"
                id="mensaje_bienvenida"
                rows="3"
                class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Ej: Bienvenido a nuestra clínica. Reserva tu cita de forma rápida y sencilla."
            ></textarea>
        </div>
        
        <div class="flex items-center">
            <input type="checkbox" id="confirmacion_auto" name="confirmacion_auto" 
                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <label for="confirmacion_auto" class="ml-2 block text-sm text-gray-700">
                Confirmación automática (las reservas se confirman automáticamente)
            </label>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="ri-add-line mr-2"></i>
                Crear Enlace
            </button>            
        </div>
    </form>
</div>

<!-- Botón para expandir/colapsar formulario - Vista Mobile -->
<div class="mobile-view p-4">
    <button type="button" id="toggleFormMobile" class="toggle-form-btn">
        <i class="ri-add-circle-line"></i>
        <span>Crear Nuevo Enlace</span>
        <i class="ri-arrow-down-s-line"></i>
    </button>
</div>

<!-- Formulario para crear nuevo enlace - Vista Mobile -->
<div class="mobile-view mobile-create-form collapsible-form collapsed" id="formContainerMobile">
    <h2 class="mobile-form-title">
        <i class="ri-add-circle-line"></i>
        Crear Nuevo Enlace
    </h2>
    
    <form method="post" class="w-full">
        <!-- Información básica -->
        <label class="mobile-form-label">Nombre del enlace *</label>
        <input type="text" name="nombre" required
            class="mobile-form-input"
            placeholder="Ej: Formulario Consulta General">
        <p class="text-xs text-gray-500 mb-4 -mt-3">Solo para identificar este enlace en tu panel</p>
        
        <!-- Mensaje de bienvenida -->
        <label class="mobile-form-label">Mensaje de bienvenida personalizado (opcional)</label>
        <textarea name="mensaje_bienvenida" rows="3"
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm resize-vertical min-h-16 mb-4"
                 placeholder="Ej: Bienvenido a nuestra clínica. Reserva tu cita de forma rápida y sencilla."></textarea>
        
        <!-- Confirmación automática -->
        <div class="mobile-form-checkbox mt-4">
            <input type="checkbox" id="confirmacion_auto_mobile" name="confirmacion_auto">
            <label for="confirmacion_auto_mobile">
                Confirmación automática de reservas
            </label>
        </div>
        
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
                            <!-- Mensaje de bienvenida/descripción -->
                            <?php if (!empty($enlace['descripcion'])): ?>
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold text-gray-900 flex items-center mb-3">
                                    <i class="ri-message-2-line mr-2 text-green-600"></i>
                                    Mensaje de bienvenida
                                </h4>
                                <div class="pl-6 p-3 bg-gray-50 rounded-md border-l-4 border-green-400">
                                    <p class="text-sm text-gray-700 italic">"<?php echo nl2br(htmlspecialchars($enlace['descripcion'])); ?>"</p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Estadísticas de uso -->
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
                            
                            <div class="pt-4 border-t border-gray-200">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-6">
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="ri-calendar-check-line mr-2 text-blue-600"></i>
                                            <span class="font-medium"><?php echo $totalReservas; ?></span>
                                            <span class="ml-1">reservas recibidas</span>
                                        </div>
                                        
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="ri-link mr-2 text-purple-600"></i>
                                            <span class="ml-1 font-mono text-xs"><?php echo $enlaceCompleto; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="text-xs text-gray-500">
                                        ID: <?php echo $enlace['id']; ?>
                                    </div>
                                </div>
                                
                                <!-- Botones de acción secundarios -->
                                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-gray-100">
                                    <!-- Botón de activar/desactivar -->
                                    <button type="button"
                                            class="btn-toggle-estado inline-flex items-center px-3 py-1.5 border <?php echo $enlace['activo'] ? 'border-orange-300 text-orange-700 hover:bg-orange-50' : 'border-green-300 text-green-700 hover:bg-green-50'; ?> shadow-sm text-xs font-medium rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 <?php echo $enlace['activo'] ? 'focus:ring-orange-500' : 'focus:ring-green-500'; ?> transition-colors"
                                            data-id="<?php echo $enlace['id']; ?>"
                                            data-accion="<?php echo $enlace['activo'] ? 'desactivar' : 'activar'; ?>"
                                            data-nombre="<?php echo htmlspecialchars($enlace['nombre']); ?>">
                                        <i class="<?php echo $enlace['activo'] ? 'ri-pause-circle-line' : 'ri-play-circle-line'; ?> mr-1.5"></i>
                                        <?php echo $enlace['activo'] ? 'Desactivar' : 'Activar'; ?>
                                    </button>
                                    
                                    <!-- Botón de editar -->
                                    <button class="btn-editar inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                            data-id="<?php echo $enlace['id']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($enlace['nombre']); ?>"
                                            data-descripcion="<?php echo htmlspecialchars($enlace['descripcion'] ?? ''); ?>"
                                            data-confirmacion="<?php echo $enlace['confirmacion_automatica'] ? '1' : '0'; ?>">
                                        <i class="ri-edit-line mr-1.5"></i>
                                        Editar
                                    </button>
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
                <p class="mobile-empty-description">Crea tu primer enlace de reserva usando el botón de arriba</p>
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
                                <!-- Mensaje de bienvenida -->
                                <?php if (!empty($enlace['mensaje_bienvenida'])): ?>
                                <div class="mb-3 p-3 bg-green-50 rounded-lg border border-green-200">
                                    <div class="text-xs font-semibold text-green-700 mb-1 flex items-center">
                                        <i class="ri-message-2-line mr-1"></i>
                                        Mensaje de bienvenida:
                                    </div>
                                    <p class="text-sm text-gray-700 italic">"<?php echo htmlspecialchars($enlace['mensaje_bienvenida']); ?>"</p>
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
                                
                                <!-- Botón de activar/desactivar -->
                                <button type="button"
                                        class="form-action-btn btn-toggle-estado <?php echo $enlace['activo'] ? 'form-btn-toggle-off' : 'form-btn-toggle-on'; ?>"
                                        data-id="<?php echo $enlace['id']; ?>"
                                        data-accion="<?php echo $enlace['activo'] ? 'desactivar' : 'activar'; ?>"
                                        data-nombre="<?php echo htmlspecialchars($enlace['nombre']); ?>">
                                    <i class="<?php echo $enlace['activo'] ? 'ri-pause-circle-line' : 'ri-play-circle-line'; ?>"></i>
                                    <?php echo $enlace['activo'] ? 'Pausar' : 'Activar'; ?>
                                </button>
                                
                                <button class="form-action-btn form-btn-edit btn-editar"
                                        data-id="<?php echo $enlace['id']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($enlace['nombre']); ?>"
                                        data-descripcion="<?php echo htmlspecialchars($enlace['descripcion'] ?? ''); ?>"
                                        data-confirmacion="<?php echo $enlace['confirmacion_automatica'] ? '1' : '0'; ?>">
                                    <i class="ri-edit-line"></i>
                                    Editar
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

<!-- Modal para editar enlace -->
<div id="editarModal" class="fixed inset-0 z-10 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="post" id="formEditar">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Editar Enlace de Reserva
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Modifica los detalles de tu enlace de reserva
                        </p>
                    </div>
                    
                    <div class="space-y-4">                        
                        <div>
                            <label for="nombreEditar" class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre del enlace *
                            </label>
                            <input type="text" id="nombreEditar" name="nombre" required
                                class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Ej: Formulario Consulta General">
                        </div>
                        
                        <div>
                            <label for="descripcionEditar" class="block text-sm font-medium text-gray-700 mb-1">
                                Mensaje de bienvenida (opcional)
                            </label>
                            <textarea
                                name="mensaje_bienvenida"
                                id="descripcionEditar"
                                rows="3"
                                class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Ej: Bienvenido a nuestra clínica. Reserva tu cita de forma rápida y sencilla."
                            ></textarea>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="confirmacionAutoEditar" name="confirmacion_auto" 
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="confirmacionAutoEditar" class="ml-2 block text-sm text-gray-700">
                                Confirmación automática
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="ri-save-line mr-2"></i>
                        Guardar Cambios
                    </button>
                    <button type="button" id="cancelarEditar"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </form>
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
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
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