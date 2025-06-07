<?php
// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Configurar la página actual
$pageTitle = 'ReservaBot - Configuración';
$currentPage = 'configuracion';
$pageScript = 'configuracion';

// Obtener la configuración actual
try {
    $stmt = getPDO()->query('SELECT * FROM configuraciones');
    $configuraciones = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (\PDOException $e) {
    error_log('Error al obtener configuraciones: ' . $e->getMessage());
    $configuraciones = [];
}

// Establecer valores predeterminados si no existen
$modoAceptacion = $configuraciones['modo_aceptacion'] ?? 'manual';
$intervaloReservas = $configuraciones['intervalo_reservas'] ?? '30';

// Horarios con soporte para múltiples ventanas
$diasSemana = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];
$horarios = [];

foreach ($diasSemana as $dia) {
    $horarioConfig = $configuraciones["horario_{$dia}"] ?? 'true|[{"inicio":"09:00","fin":"18:00"}]';
    
    // Separar activo y ventanas
    $parts = explode('|', $horarioConfig, 2);
    $activo = $parts[0] === 'true';
    
    if ($activo && isset($parts[1])) {
        // Intentar decodificar como JSON (nuevo formato)
        $ventanas = json_decode($parts[1], true);
        
        // Si no es JSON válido, usar formato legacy
        if (!$ventanas) {
            // Formato legacy: "09:00|18:00"
            $tiempos = explode('|', $parts[1]);
            if (count($tiempos) >= 2) {
                $ventanas = [
                    ['inicio' => $tiempos[0], 'fin' => $tiempos[1]]
                ];
            } else {
                $ventanas = [['inicio' => '09:00', 'fin' => '18:00']];
            }
        }
    } else {
        $ventanas = [['inicio' => '09:00', 'fin' => '18:00']];
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

// Incluir la cabecera
include 'includes/header.php';
?>

<style>
/* Estilos responsivos para la página de configuración - Móvil */

/* Base responsive SOLO para el contenido de configuración */
main * {
    box-sizing: border-box;
}

/* Evitar que afecte al header */
main {
    max-width: 100vw;
    overflow-x: hidden;
}

/* Contenedor principal - SOLO main content */
@media (max-width: 768px) {
    main .max-w-4xl {
        max-width: 100%;
        margin: 0;
        padding: 0 1rem;
    }
    
    /* Título principal - SOLO en main */
    main .flex.justify-between.items-center.mb-6 {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding: 0 0.5rem;
    }
    
    main .flex.justify-between.items-center.mb-6 h1 {
        font-size: 1.5rem;
        line-height: 1.3;
        word-wrap: break-word;
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
    
    /* Tarjetas de configuración - SOLO en main */
    main .bg-white.rounded-lg.shadow-sm {
        border-radius: 1rem;
        padding: 1rem;
        margin-bottom: 1rem;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }
    
    /* Títulos de sección - SOLO en main */
    main .bg-white h2 {
        font-size: 1.125rem;
        line-height: 1.4;
        margin-bottom: 1rem;
        word-wrap: break-word;
    }
    
    main .bg-white h2 i {
        margin-right: 0.5rem;
        flex-shrink: 0;
    }
    
    /* Configuración de reservas - Modo de aceptación - SOLO en main */
    main .flex.justify-between.items-center {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        width: 100%;
    }
    
    main .flex.justify-between.items-center > div:first-child {
        width: 100%;
    }
    
    main .flex.justify-between.items-center > div:last-child {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    /* Toggle switch responsive */
    #toggleModo {
        flex-shrink: 0;
    }
    
    #modoLabel {
        font-size: 0.875rem;
        margin-right: 0.75rem;
    }
    
    /* Descripción del modo */
    #modoDescription {
        font-size: 0.875rem;
        line-height: 1.4;
        margin-top: 0.5rem;
        word-wrap: break-word;
    }
    
    /* Border inferior responsive */
    .border-b.border-gray-200.pb-6 {
        padding-bottom: 1rem;
        margin-bottom: 1rem;
    }
    
    /* Selector de intervalo - SOLO en main */
    main .space-y-4 > div {
        width: 100%;
        margin-bottom: 1rem;
    }
    
    main .space-y-4 label {
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
        display: block;
        word-wrap: break-word;
    }
    
    main .space-y-4 select {
        width: 100%;
        max-width: 100%;
        font-size: 0.875rem;
        padding: 0.75rem;
        border-radius: 0.5rem;
    }
    
    /* Horarios de atención - SOLO en main */
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
        padding: 0.5rem;
        background: rgba(59, 130, 246, 0.05);
        border-radius: 0.5rem;
        border: 1px solid rgba(59, 130, 246, 0.1);
    }
    
    /* Contenedor de días */
    .space-y-6 {
        gap: 1rem;
    }
    
    .space-y-6 > div {
        margin-bottom: 1rem;
    }
    
    /* Tarjetas de días individuales - SOLO en main */
    main .border.border-gray-200.rounded-lg.p-4 {
        border-radius: 0.75rem;
        padding: 1rem;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }
    
    /* Header de cada día - SOLO en main */
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
        padding: 0.5rem;
        background: rgba(59, 130, 246, 0.05);
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 0.5rem;
        font-size: 0.875rem;
    }
    
    /* Checkbox y label de días - SOLO en main */
    main .flex.items-center input[type="checkbox"] {
        margin-right: 0.75rem;
        flex-shrink: 0;
    }
    
    main .flex.items-center label {
        font-size: 1rem;
        font-weight: 500;
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
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
        padding: 1rem;
        border-radius: 0.75rem;
        width: 100%;
        max-width: 100%;
    }
    
    /* Grupo de inputs de hora */
    .ventana-horaria > div {
        width: 100%;
    }
    
    .ventana-horaria .flex.items-center.space-x-2 {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        gap: 0.5rem;
    }
    
    .ventana-horaria .flex.items-center.space-x-2 label {
        font-size: 0.875rem;
        margin-bottom: 0;
        flex-shrink: 0;
        min-width: 3.5rem;
    }
    
    .ventana-horaria .flex.items-center.space-x-2 input[type="time"] {
        flex: 1;
        min-width: 0;
        font-size: 0.875rem;
        padding: 0.5rem;
        border-radius: 0.5rem;
    }
    
    /* Badges y botones de acción en ventanas */
    .ventana-horaria .flex.items-center.space-x-2:last-child {
        justify-content: space-between;
        align-items: center;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .ventana-horaria .px-2.py-1 {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        white-space: nowrap;
    }
    
    .btn-remove-ventana {
        padding: 0.375rem;
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    /* Botón guardar */
    .pt-4.text-right {
        padding-top: 1rem;
        text-align: center;
        width: 100%;
    }
    
    .pt-4.text-right button {
        width: 100%;
        max-width: 100%;
        padding: 0.875rem 1rem;
        font-size: 1rem;
        border-radius: 0.75rem;
        justify-content: center;
    }
    
    /* Mensaje de éxito */
    #saveSuccessMessage {
        bottom: 1rem;
        right: 1rem;
        left: 1rem;
        max-width: calc(100vw - 2rem);
        font-size: 0.875rem;
    }
    
    /* Template y elementos ocultos */
    template {
        display: none;
    }
    
    /* Mejoras para inputs pequeños */
    input[type="time"] {
        min-height: 2.5rem;
    }
    
    input[type="checkbox"] {
        min-width: 1rem;
        min-height: 1rem;
    }
    
    select {
        min-height: 2.5rem;
    }
    
    /* Prevenir texto que se salga - SOLO en main */
    main * {
        word-wrap: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
    }
    
    /* Específico para pantallas muy pequeñas - SOLO main */
    @media (max-width: 380px) {
        main .max-w-4xl {
            padding: 0 0.75rem;
        }
        
        main .bg-white.rounded-lg.shadow-sm {
            padding: 0.75rem;
        }
        
        main .ventana-horaria {
            padding: 0.75rem;
        }
        
        main .ventana-horaria .flex.items-center.space-x-2 {
            flex-direction: column;
            align-items: stretch;
            gap: 0.5rem;
        }
        
        main .ventana-horaria .flex.items-center.space-x-2 label {
            min-width: auto;
            text-align: left;
        }
        
        main .border .flex.items-center.justify-between.mb-4 .btn-add-ventana {
            font-size: 0.8125rem;
            padding: 0.625rem;
        }
        
        main .flex.justify-between.items-center.mb-6 h1 {
            font-size: 1.375rem;
        }
    }
    
    /* Animaciones suaves */
    .ventana-horaria {
        transition: all 0.3s ease;
    }
    
    .btn-add-ventana {
        transition: all 0.2s ease;
    }
    
    .btn-add-ventana:hover {
        background: rgba(59, 130, 246, 0.1);
        transform: translateY(-1px);
    }
    
    /* Scroll suave para el formulario */
    #configForm {
        scroll-behavior: smooth;
    }
}

/* Mantener estilos desktop intactos */
@media (min-width: 769px) {
    /* Estilos desktop originales se mantienen */
}
</style>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Configuración General</h1>
</div>

<div class="max-w-4xl mx-auto">
    <form id="configForm" class="space-y-8">

        <!-- Configuración de reservas -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                <i class="ri-calendar-check-line mr-2 text-blue-600"></i>
                Configuración de reservas
            </h2>
            
            <!-- Destacar el modo de aceptación -->
            <div class="mb-6 border-b border-gray-200 pb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-medium text-gray-900">Modo de aceptación de reservas</h3>
                        <p class="text-sm text-gray-500" id="modoDescription">
                            <?php echo $modoAceptacion === 'automatico' 
                                ? 'Las reservas se aceptan automáticamente en horarios disponibles' 
                                : 'Las reservas requieren aprobación manual'; ?>
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="mr-3 text-sm font-medium text-gray-700" id="modoLabel">
                            <?php echo $modoAceptacion === 'automatico' ? 'Automático' : 'Manual'; ?>
                        </span>
                        <button 
                            id="toggleModo" 
                            type="button"
                            class="relative inline-flex h-6 w-11 items-center rounded-full <?php echo $modoAceptacion === 'automatico' ? 'bg-blue-600' : 'bg-gray-200'; ?> focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            <span 
                                class="inline-block h-4 w-4 transform rounded-full bg-white <?php echo $modoAceptacion === 'automatico' ? 'translate-x-6' : 'translate-x-1'; ?> transition-transform"
                            ></span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Otras configuraciones de reservas -->
            <div class="space-y-4">
                <div>
                    <label for="intervaloReservas" class="block text-sm font-medium text-gray-700 mb-1">
                        Intervalo entre reservas (minutos)
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
                </div>
            </div>
        </div>
        
        <!-- Horario de atención con múltiples ventanas -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium text-gray-900 flex items-center">
                    <i class="ri-time-line mr-2 text-blue-600"></i>
                    Horario de atención
                </h2>
                <div class="text-sm text-gray-500">
                    <i class="ri-information-line mr-1"></i>
                    Puedes definir múltiples ventanas horarias por día
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
                                <div class="ventana-horaria flex items-center space-x-3 p-3 <?php echo $index === 0 ? 'bg-gray-50' : 'bg-blue-50 border border-blue-200'; ?> rounded-lg">
                                    <div class="flex items-center space-x-2">
                                        <label class="text-sm font-medium text-gray-700">Desde:</label>
                                        <input
                                            type="time"
                                            name="horario_<?php echo $dia; ?>_inicio_<?php echo $index + 1; ?>"
                                            class="block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                            value="<?php echo htmlspecialchars($ventana['inicio']); ?>"
                                            <?php echo !$horarios[$dia]['activo'] ? 'disabled' : ''; ?>
                                        >
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <label class="text-sm font-medium text-gray-700">Hasta:</label>
                                        <input
                                            type="time"
                                            name="horario_<?php echo $dia; ?>_fin_<?php echo $index + 1; ?>"
                                            class="block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                            value="<?php echo htmlspecialchars($ventana['fin']); ?>"
                                            <?php echo !$horarios[$dia]['activo'] ? 'disabled' : ''; ?>
                                        >
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-1 text-xs font-medium <?php echo $index === 0 ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?> rounded-full">
                                            <?php echo $index === 0 ? 'Principal' : 'Adicional'; ?>
                                        </span>
                                        
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
    <div class="ventana-horaria flex items-center space-x-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
        <div class="flex items-center space-x-2">
            <label class="text-sm font-medium text-gray-700">Desde:</label>
            <input
                type="time"
                name=""
                class="block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                value="14:00"
            >
        </div>
        
        <div class="flex items-center space-x-2">
            <label class="text-sm font-medium text-gray-700">Hasta:</label>
            <input
                type="time"
                name=""
                class="block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                value="18:00"
            >
        </div>
        
        <div class="flex items-center space-x-2">
            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                Adicional
            </span>
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
    
    // Función para añadir nueva ventana horaria
    function addVentanaHoraria(dia) {
        const template = document.getElementById('ventana-horaria-template');
        const ventanasContainer = document.getElementById(`ventanas_${dia}`);
        const clone = template.content.cloneNode(true);
        
        // Incrementar contador
        ventanaCounters[dia]++;
        const ventanaNum = ventanaCounters[dia];
        
        // Configurar nombres de los inputs
        const inputs = clone.querySelectorAll('input[type="time"]');
        inputs[0].name = `horario_${dia}_inicio_${ventanaNum}`;
        inputs[1].name = `horario_${dia}_fin_${ventanaNum}`;
        
        // Event listener para eliminar ventana
        const removeBtn = clone.querySelector('.btn-remove-ventana');
        removeBtn.addEventListener('click', function() {
            this.closest('.ventana-horaria').remove();
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
    
    // Toggle modo aceptación
    const toggleModo = document.getElementById('toggleModo');
    const modoLabel = document.getElementById('modoLabel');
    const modoDescription = document.getElementById('modoDescription');
    let modoAceptacion = modoLabel.textContent.trim() === 'Automático' ? 'automatico' : 'manual';
    
    if (toggleModo) {
        toggleModo.addEventListener('click', function() {
            const toggleButton = toggleModo.querySelector('span');
            
            if (toggleButton.classList.contains('translate-x-1')) {
                // Cambiar a modo automático
                toggleButton.classList.remove('translate-x-1');
                toggleButton.classList.add('translate-x-6');
                toggleModo.classList.remove('bg-gray-200');
                toggleModo.classList.add('bg-blue-600');
                modoLabel.textContent = 'Automático';
                modoDescription.textContent = 'Las reservas se aceptan automáticamente en horarios disponibles';
                modoAceptacion = 'automatico';
            } else {
                // Cambiar a modo manual
                toggleButton.classList.remove('translate-x-6');
                toggleButton.classList.add('translate-x-1');
                toggleModo.classList.remove('bg-blue-600');
                toggleModo.classList.add('bg-gray-200');
                modoLabel.textContent = 'Manual';
                modoDescription.textContent = 'Las reservas requieren aprobación manual';
                modoAceptacion = 'manual';
            }
        });
    }
    
    // Envío del formulario
    const configForm = document.getElementById('configForm');
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const data = {};
            
            // Recopilar modo de aceptación
            data['modo_aceptacion'] = modoAceptacion;
            
            // Recopilar intervalo de reservas
            const intervaloReservas = document.getElementById('intervaloReservas');
            if (intervaloReservas) data['intervalo_reservas'] = intervaloReservas.value;
            
            // Recopilar horarios con múltiples ventanas
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
                        
                        if (inicioInput && finInput && inicioInput.value && finInput.value) {
                            ventanas.push({
                                inicio: inicioInput.value,
                                fin: finInput.value
                            });
                        }
                    });
                    
                    // Guardar como JSON las múltiples ventanas
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

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>