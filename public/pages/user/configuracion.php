<?php
// pages/configuracion.php

$pageTitle = 'ReservaBot - Configuración';
$currentPage = 'configuracion';
$pageScript = 'configuracion';

?>
<style>
/* Estilos responsivos para configuración - Mobile First */
* {
    box-sizing: border-box;
}

main {
    max-width: 100vw;
    overflow-x: hidden;
}

/* Estilos para el campo de capacidad */
.capacidad-badge {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.capacidad-input {
    text-align: center;
    font-weight: 600;
}

.capacidad-help {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

@media (max-width: 768px) {
    /* Contenedor principal móvil */
    main .max-w-4xl {
        max-width: 100%;
        margin: 0;
        padding: 0 1rem;
    }
    
    /* Formulario principal */
    #configForm {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }
    
    #configForm .space-y-8 > * {
        margin-bottom: 1.5rem;
    }
    
    /* Tarjetas de configuración mejoradas */
    main .bg-white.rounded-lg.shadow-sm {
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1rem;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    }
    
    /* Títulos de sección */
    main .bg-white h2 {
        font-size: 1.125rem;
        line-height: 1.4;
        margin-bottom: 1rem;
        word-wrap: break-word;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    main .bg-white h2 i {
        flex-shrink: 0;
        font-size: 1.25rem;
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
    
    /* Selector de intervalo */
    main .space-y-4 > div {
        width: 100%;
        margin-bottom: 1rem;
    }
    
    main .space-y-4 select {
        width: 100%;
        max-width: 100%;
        font-size: 0.875rem;
        padding: 0.875rem;
        border-radius: 0.75rem;
        border: 2px solid #e5e7eb;
        transition: all 0.2s ease;
        background: white;
    }
    
    main .space-y-4 select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    main .space-y-4 .text-sm.text-gray-500 {
        font-size: 0.8125rem;
        margin-top: 0.25rem;
        color: #6b7280;
    }
    
    /* Horarios de atención */
    main .flex.items-center.justify-between.mb-4 {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    
    main .flex.items-center.justify-between.mb-4 h2 {
        width: 100%;
        margin-bottom: 0;
    }
    
    main .flex.items-center.justify-between.mb-4 .text-sm {
        width: 100%;
        font-size: 0.8125rem;
        color: #6b7280;
        padding: 0.75rem;
        background: rgba(59, 130, 246, 0.05);
        border-radius: 0.75rem;
        border: 1px solid rgba(59, 130, 246, 0.1);
        line-height: 1.4;
    }
    
    /* Contenedor de días */
    .space-y-6 {
        gap: 1rem;
    }
    
    .space-y-6 > div {
        margin-bottom: 1rem;
    }
    
    /* Tarjetas de días individuales */
    main .border.border-gray-200.rounded-lg.p-4 {
        border-radius: 0.75rem;
        padding: 1rem;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    
    /* Header de cada día */
    main .border .flex.items-center.justify-between.mb-4 {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    
    main .border .flex.items-center.justify-between.mb-4 > div:first-child {
        width: 100%;
    }
    
    main .border .flex.items-center.justify-between.mb-4 .btn-add-ventana {
        width: 100%;
        justify-content: center;
        padding: 0.75rem;
        background: rgba(59, 130, 246, 0.05);
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 0.75rem;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    main .border .flex.items-center.justify-between.mb-4 .btn-add-ventana:hover {
        background: rgba(59, 130, 246, 0.1);
        transform: translateY(-1px);
    }
    
    /* Checkbox y label de días */
    main .flex.items-center input[type="checkbox"] {
        margin-right: 0.75rem;
        flex-shrink: 0;
        width: 1.125rem;
        height: 1.125rem;
        accent-color: #667eea;
    }
    
    main .flex.items-center label {
        font-size: 1rem;
        font-weight: 600;
        word-wrap: break-word;
    }
    
    /* Ventanas horarias */
    .ventanas-horarias {
        width: 100%;
        max-width: 100%;
    }
    
    .ventanas-horarias .space-y-3 > * {
        margin-bottom: 0.75rem;
    }
    
    /* Ventana horaria individual */
    .ventana-horaria {
        padding: 1rem;
        border-radius: 0.75rem;
        width: 100%;
        max-width: 100%;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
    }
    
    /* Grid de inputs de hora - stack en móvil */
    .ventana-horaria > div {
        width: 100%;
    }
    
    .ventana-horaria .grid {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .ventana-horaria .grid .flex {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        gap: 0.5rem;
    }
    
    .ventana-horaria .grid .flex label {
        font-size: 0.875rem;
        margin-bottom: 0;
        flex-shrink: 0;
        min-width: 4rem;
        font-weight: 500;
    }
    
    .ventana-horaria .grid .flex input {
        flex: 1;
        min-width: 0;
        font-size: 0.875rem;
        padding: 0.625rem;
        border-radius: 0.5rem;
        border: 2px solid #e5e7eb;
        transition: all 0.2s ease;
    }
    
    .ventana-horaria .grid .flex input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    /* Capacidad input específico */
    .capacidad-input {
        max-width: 100px;
        text-align: center;
    }
    
    /* Ayuda de capacidad */
    .capacidad-help {
        text-align: center;
        padding: 0.5rem;
        background: rgba(59, 130, 246, 0.03);
        border-radius: 0.5rem;
        margin: 0.5rem 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
    }
    
    /* Badges y botones de acción en ventanas */
    .ventana-horaria .flex.items-center.justify-between:last-child {
        justify-content: space-between;
        align-items: center;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .ventana-horaria .px-2.py-1 {
        font-size: 0.75rem;
        padding: 0.375rem 0.625rem;
        border-radius: 9999px;
        white-space: nowrap;
    }
    
    .btn-remove-ventana {
        padding: 0.5rem;
        border-radius: 50%;
        flex-shrink: 0;
        width: 2rem;
        height: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .btn-remove-ventana:hover {
        transform: scale(1.1);
    }
    
    /* Botón guardar mejorado */
    .pt-4.text-right {
        padding-top: 1rem;
        text-align: center;
        width: 100%;
    }
    
    .pt-4.text-right button {
        width: 100%;
        max-width: 100%;
        padding: 1rem;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 0.75rem;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: all 0.2s ease;
    }
    
    .pt-4.text-right button:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    /* Mensaje de éxito */
    #saveSuccessMessage {
        bottom: 1rem;
        right: 1rem;
        left: 1rem;
        max-width: calc(100vw - 2rem);
        font-size: 0.875rem;
        padding: 1rem;
        border-radius: 0.75rem;
    }
    
    /* Template y elementos ocultos */
    template {
        display: none;
    }
    
    /* Mejoras para inputs pequeños */
    input[type="time"], 
    input[type="number"] {
        min-height: 2.5rem;
    }
    
    input[type="checkbox"] {
        min-width: 1.125rem;
        min-height: 1.125rem;
    }
    
    select {
        min-height: 2.75rem;
    }
    
    /* Prevenir texto que se salga */
    main *,
    main p,
    main span,
    main div {
        word-wrap: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
        max-width: 100%;
    }
    
    /* Específico para pantallas muy pequeñas */
    @media (max-width: 380px) {
        main .max-w-4xl {
            padding: 0 0.75rem;
        }
        
        main .bg-white.rounded-lg.shadow-sm {
            padding: 1rem;
        }
        
        main .ventana-horaria {
            padding: 0.875rem;
        }
        
        main .ventana-horaria .grid .flex {
            flex-direction: column;
            align-items: stretch;
            gap: 0.5rem;
        }
        
        main .ventana-horaria .grid .flex label {
            min-width: auto;
            text-align: left;
        }
        
        main .border .flex.items-center.justify-between.mb-4 .btn-add-ventana {
            font-size: 0.8125rem;
            padding: 0.625rem;
        }
        
        main .capacidad-input {
            max-width: 100%;
        }
    }
    
    /* Animaciones suaves */
    .ventana-horaria {
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .btn-add-ventana {
        transition: all 0.2s ease;
    }
    
    /* Scroll suave para el formulario */
    #configForm {
        scroll-behavior: smooth;
    }
    
    /* Mejora visual de campos activos */
    .ventana-horaria:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
}

/* Animación de fade in para tarjetas */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

main .bg-white.rounded-lg.shadow-sm {
    animation: fadeIn 0.3s ease-out;
}
</style>
<?php

// Obtener usuario
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

try {
    $configuracionDomain = getContainer()->getConfiguracionDomain();
    
    // Obtener todas las configuraciones
    $configuraciones = $configuracionDomain->obtenerConfiguraciones($userId);
    
} catch (Exception $e) {
    setFlashError('Error al cargar configuración: ' . $e->getMessage());
    $configuraciones = [];
}

// Establecer valores predeterminados si no existen
$intervaloReservas = $configuraciones['intervalo_reservas'] ?? '30';

// Horarios con soporte para múltiples ventanas y capacidad
$diasSemana = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];
$horarios = [];

foreach ($diasSemana as $dia) {
    $horarioConfig = $configuraciones["horario_{$dia}"] ?? 'true|[{"inicio":"09:00","fin":"18:00","capacidad":1}]';
    
    // Separar activo y ventanas
    $parts = explode('|', $horarioConfig, 2);
    $activo = $parts[0] === 'true';
    
    if ($activo && isset($parts[1])) {
        // Intentar decodificar como JSON (nuevo formato)
        $ventanas = json_decode($parts[1], true);
        
        // Si no es JSON válido, usar formato legacy y añadir capacidad por defecto
        if (!$ventanas) {
            // Formato legacy: "09:00|18:00"
            $tiempos = explode('|', $parts[1]);
            if (count($tiempos) >= 2) {
                $ventanas = [
                    ['inicio' => $tiempos[0], 'fin' => $tiempos[1], 'capacidad' => 1]
                ];
            } else {
                $ventanas = [['inicio' => '09:00', 'fin' => '18:00', 'capacidad' => 1]];
            }
        } else {
            // Asegurar que todas las ventanas tienen capacidad
            foreach ($ventanas as &$ventana) {
                if (!isset($ventana['capacidad'])) {
                    $ventana['capacidad'] = 1;
                }
            }
        }
    } else {
        $ventanas = [['inicio' => '09:00', 'fin' => '18:00', 'capacidad' => 1]];
    }
    
    $horarios[$dia] = [
        'activo' => $activo,
        'ventanas' => $ventanas
    ];
}

// Nombres completos de los días
$nombresDias = [
    'lun' => 'Lunes',
    'mar' => 'Martes',
    'mie' => 'Miércoles',
    'jue' => 'Jueves',
    'vie' => 'Viernes',
    'sab' => 'Sábado',
    'dom' => 'Domingo'
];

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <form id="configForm" class="space-y-8">

        <!-- Configuración de reservas -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                <i class="ri-calendar-check-line mr-2 text-blue-600"></i>
                Configuración de reservas
            </h2>
            
            <!-- Solo configuración de intervalo -->
            <div class="space-y-4">
                <div>
                    <label for="intervaloReservas" class="block text-sm font-medium text-gray-700 mb-1">
                        Intervalo entre horarios disponibles (minutos)
                    </label>
                    <select
                        id="intervaloReservas"
                        name="intervalo_reservas"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    >
                        <option value="15" <?php echo $intervaloReservas == 15 ? 'selected' : ''; ?>>15 minutos</option>
                        <option value="30" <?php echo $intervaloReservas == 30 ? 'selected' : ''; ?>>30 minutos</option>
                        <option value="45" <?php echo $intervaloReservas == 45 ? 'selected' : ''; ?>>45 minutos</option>
                        <option value="60" <?php echo $intervaloReservas == 60 ? 'selected' : ''; ?>>1 hora</option>
                        <option value="90" <?php echo $intervaloReservas == 90 ? 'selected' : ''; ?>>1 hora y 30 minutos</option>
                        <option value="120" <?php echo $intervaloReservas == 120 ? 'selected' : ''; ?>>2 horas</option>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Define cada cuánto tiempo se pueden hacer reservas</p>
                </div>
            </div>
        </div>
        
        <!-- Horario de atención con múltiples ventanas y capacidad -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium text-gray-900 flex items-center">
                    <i class="ri-time-line mr-2 text-blue-600"></i>
                    Horario de atención y capacidad
                </h2>
                <div class="text-sm text-gray-500">
                    <i class="ri-information-line mr-1"></i>
                    Define horarios y cuántas reservas simultáneas puedes atender
                </div>
            </div>
            
            <div class="space-y-6">
                <?php foreach ($diasSemana as $dia): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <input 
                                    class="toggle-day h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3" 
                                    type="checkbox" 
                                    id="horario_<?php echo $dia; ?>_activo"
                                    name="horario_<?php echo $dia; ?>_activo" 
                                    data-dia="<?php echo $dia; ?>"
                                    <?php echo $horarios[$dia]['activo'] ? 'checked' : ''; ?>
                                >
                                <label for="horario_<?php echo $dia; ?>_activo" class="text-base font-medium text-gray-900">
                                    <?php echo $nombresDias[$dia]; ?>
                                </label>
                            </div>
                            
                            <button 
                                type="button" 
                                class="btn-add-ventana text-sm text-blue-600 hover:text-blue-800 flex items-center"
                                data-dia="<?php echo $dia; ?>"
                                style="<?php echo !$horarios[$dia]['activo'] ? 'display: none;' : ''; ?>"
                            >
                                <i class="ri-add-line mr-1"></i>
                                Añadir ventana horaria
                            </button>
                        </div>
                        
                        <div class="ventanas-horarias space-y-3" id="ventanas_<?php echo $dia; ?>" 
                             style="<?php echo !$horarios[$dia]['activo'] ? 'display: none;' : ''; ?>">
                            
                            <?php foreach ($horarios[$dia]['ventanas'] as $index => $ventana): ?>
                                <div class="ventana-horaria p-3 <?php echo $index === 0 ? 'bg-gray-50' : 'bg-blue-50 border border-blue-200'; ?> rounded-lg">
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-3">
                                        <div class="flex items-center space-x-2">
                                            <label class="text-sm font-medium text-gray-700">Desde:</label>
                                            <input
                                                type="time"
                                                name="horario_<?php echo $dia; ?>_inicio_<?php echo $index + 1; ?>"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                value="<?php echo htmlspecialchars($ventana['inicio']); ?>"
                                                <?php echo !$horarios[$dia]['activo'] ? 'disabled' : ''; ?>
                                            >
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
                                            <label class="text-sm font-medium text-gray-700">Hasta:</label>
                                            <input
                                                type="time"
                                                name="horario_<?php echo $dia; ?>_fin_<?php echo $index + 1; ?>"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                value="<?php echo htmlspecialchars($ventana['fin']); ?>"
                                                <?php echo !$horarios[$dia]['activo'] ? 'disabled' : ''; ?>
                                            >
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
                                            <label class="text-sm font-medium text-gray-700">Capacidad:</label>
                                            <input
                                                type="number"
                                                name="horario_<?php echo $dia; ?>_capacidad_<?php echo $index + 1; ?>"
                                                class="capacidad-input block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                value="<?php echo htmlspecialchars($ventana['capacidad'] ?? 1); ?>"
                                                min="1"
                                                max="50"
                                                <?php echo !$horarios[$dia]['activo'] ? 'disabled' : ''; ?>
                                            >
                                        </div>
                                    </div>
                                    
                                    <div class="capacidad-help text-center">
                                        <i class="ri-information-line mr-1"></i>
                                        Número máximo de reservas simultáneas en este horario
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-1 text-xs font-medium <?php echo $index === 0 ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?> rounded-full">
                                                <?php echo $index === 0 ? 'Principal' : 'Adicional'; ?>
                                            </span>
                                            
                                            <span class="capacidad-badge px-2 py-1 text-xs rounded-full">
                                                🏪 <?php echo htmlspecialchars($ventana['capacidad'] ?? 1); ?> reservas máx.
                                            </span>
                                        </div>
                                        
                                        <?php if ($index > 0): ?>
                                            <button 
                                                type="button" 
                                                class="btn-remove-ventana text-red-600 hover:text-red-800 p-1 rounded-full hover:bg-red-50"
                                                title="Eliminar ventana"
                                            >
                                                <i class="ri-close-line text-sm"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
                
        <!-- Botón guardar -->
        <div class="pt-4 text-right">
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <i class="ri-save-line mr-2"></i>
                Guardar configuración
            </button>
        </div>
    </form>
</div>

<!-- Template para nuevas ventanas horarias -->
<template id="ventana-horaria-template">
    <div class="ventana-horaria p-3 bg-blue-50 rounded-lg border border-blue-200">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-3">
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Desde:</label>
                <input
                    type="time"
                    name=""
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                    value="14:00"
                >
            </div>
            
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Hasta:</label>
                <input
                    type="time"
                    name=""
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                    value="18:00"
                >
            </div>
            
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Capacidad:</label>
                <input
                    type="number"
                    name=""
                    class="capacidad-input block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                    value="1"
                    min="1"
                    max="50"
                >
            </div>
        </div>
        
        <div class="capacidad-help text-center">
            <i class="ri-information-line mr-1"></i>
            Número máximo de reservas simultáneas en este horario
        </div>
        
        <div class="flex items-center justify-between mt-3">
            <div class="flex items-center space-x-2">
                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    Adicional
                </span>
                <span class="capacidad-badge px-2 py-1 text-xs rounded-full">
                    🏪 1 reservas máx.
                </span>
            </div>
            
            <button 
                type="button" 
                class="btn-remove-ventana text-red-600 hover:text-red-800 p-1 rounded-full hover:bg-red-50"
                title="Eliminar ventana"
            >
                <i class="ri-close-line text-sm"></i>
            </button>
        </div>
    </div>
</template>

<div id="saveSuccessMessage" class="fixed bottom-4 right-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md shadow-lg hidden">
    <div class="flex items-center">
        <i class="ri-check-line mr-2 text-green-500"></i>
        <span>Configuración guardada correctamente</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Contadores para nombrar los inputs únicamente
    const ventanaCounters = {};
    
    // Inicializar contadores basados en ventanas existentes
    document.querySelectorAll('.toggle-day').forEach(checkbox => {
        const dia = checkbox.dataset.dia;
        const ventanasContainer = document.getElementById(`ventanas_${dia}`);
        const ventanasExistentes = ventanasContainer.querySelectorAll('.ventana-horaria').length;
        ventanaCounters[dia] = ventanasExistentes;
    });
    
    // Event listeners para toggle de días
    document.querySelectorAll('.toggle-day').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const dia = this.dataset.dia;
            const ventanasContainer = document.getElementById(`ventanas_${dia}`);
            const addButton = document.querySelector(`[data-dia="${dia}"]`);
            
            if (this.checked) {
                ventanasContainer.style.display = 'block';
                if (addButton) addButton.style.display = 'flex';
                
                // Habilitar inputs
                ventanasContainer.querySelectorAll('input').forEach(input => {
                    input.disabled = false;
                });
            } else {
                ventanasContainer.style.display = 'none';
                if (addButton) addButton.style.display = 'none';
                
                // Deshabilitar inputs
                ventanasContainer.querySelectorAll('input').forEach(input => {
                    input.disabled = true;
                });
            }
        });
    });
    
    // Event listeners para añadir ventanas
    document.querySelectorAll('.btn-add-ventana').forEach(button => {
        button.addEventListener('click', function() {
            const dia = this.dataset.dia;
            addVentanaHoraria(dia);
        });
    });
    
    // Event listeners para eliminar ventanas existentes
    document.querySelectorAll('.btn-remove-ventana').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.ventana-horaria').remove();
        });
    });
    
    // Event listeners para actualizar el badge de capacidad
    document.querySelectorAll('input[name*="_capacidad_"]').forEach(input => {
        input.addEventListener('input', function() {
            updateCapacidadBadge(this);
        });
    });
    
    // Función para actualizar el badge de capacidad
    function updateCapacidadBadge(input) {
        const ventana = input.closest('.ventana-horaria');
        const badge = ventana.querySelector('.capacidad-badge');
        const valor = parseInt(input.value) || 1;
        
        if (badge) {
            badge.textContent = `🏪 ${valor} reservas máx.`;
        }
    }
    
    // Función para añadir nueva ventana horaria
    function addVentanaHoraria(dia) {
        const template = document.getElementById('ventana-horaria-template');
        const ventanasContainer = document.getElementById(`ventanas_${dia}`);
        const clone = template.content.cloneNode(true);
        
        // Incrementar contador
        ventanaCounters[dia]++;
        const ventanaNum = ventanaCounters[dia];
        
        // Configurar nombres de los inputs
        const inputs = clone.querySelectorAll('input');
        inputs[0].name = `horario_${dia}_inicio_${ventanaNum}`;    // time inicio
        inputs[1].name = `horario_${dia}_fin_${ventanaNum}`;       // time fin
        inputs[2].name = `horario_${dia}_capacidad_${ventanaNum}`; // number capacidad
        
        // Event listener para eliminar ventana
        const removeBtn = clone.querySelector('.btn-remove-ventana');
        removeBtn.addEventListener('click', function() {
            this.closest('.ventana-horaria').remove();
        });
        
        // Event listener para actualizar badge de capacidad
        const capacidadInput = inputs[2];
        capacidadInput.addEventListener('input', function() {
            updateCapacidadBadge(this);
        });
        
        // Añadir al contenedor
        ventanasContainer.appendChild(clone);
        
        // Animación suave
        const newVentana = ventanasContainer.lastElementChild;
        newVentana.style.opacity = '0';
        newVentana.style.transform = 'translateY(-10px)';
        
        setTimeout(() => {
            newVentana.style.transition = 'all 0.3s ease';
            newVentana.style.opacity = '1';
            newVentana.style.transform = 'translateY(0)';
        }, 10);
    }
    
    // Envío del formulario
    const configForm = document.getElementById('configForm');
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const data = {};
            
            // Recopilar intervalo de reservas
            const intervaloReservas = document.getElementById('intervaloReservas');
            if (intervaloReservas) data['intervalo_reservas'] = intervaloReservas.value;
            
            // Recopilar horarios con múltiples ventanas y capacidad
            const diasSemana = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];
            
            diasSemana.forEach(dia => {
                const activoCheckbox = document.querySelector(`[name="horario_${dia}_activo"]`);
                const activo = activoCheckbox ? activoCheckbox.checked : false;
                
                if (activo) {
                    // Recopilar todas las ventanas horarias para este día
                    const ventanas = [];
                    const ventanasContainer = document.getElementById(`ventanas_${dia}`);
                    const ventanasElements = ventanasContainer.querySelectorAll('.ventana-horaria');
                    
                    ventanasElements.forEach((ventana, index) => {
                        const inicioInput = ventana.querySelector(`input[name*="_inicio_"]`);
                        const finInput = ventana.querySelector(`input[name*="_fin_"]`);
                        const capacidadInput = ventana.querySelector(`input[name*="_capacidad_"]`);
                        
                        if (inicioInput && finInput && capacidadInput && 
                            inicioInput.value && finInput.value) {
                            ventanas.push({
                                inicio: inicioInput.value,
                                fin: finInput.value,
                                capacidad: parseInt(capacidadInput.value) || 1
                            });
                        }
                    });
                    
                    // Guardar como JSON las múltiples ventanas con capacidad
                    data[`horario_${dia}`] = `true|${JSON.stringify(ventanas)}`;
                } else {
                    data[`horario_${dia}`] = 'false|[]';
                }
            });
            
            // Enviar la solicitud
            fetch('api/actualizar-configuracion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Mostrar mensaje de éxito
                    const saveSuccessMessage = document.getElementById('saveSuccessMessage');
                    if (saveSuccessMessage) {
                        saveSuccessMessage.classList.remove('hidden');
                        
                        setTimeout(() => {
                            saveSuccessMessage.classList.add('hidden');
                        }, 3000);
                    }
                } else {
                    alert('Error al guardar la configuración: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>