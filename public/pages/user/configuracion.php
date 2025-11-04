<?php
// pages/user/configuracion.php

$pageTitle = 'ReservaBot - Configuración';
$currentPage = 'configuracion';
$pageScript = 'configuracion';

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

// Información básica del negocio
$nombreNegocio = $configuraciones['empresa_nombre'] ?? '';
$imagenNegocio = $configuraciones['empresa_imagen'] ?? '';
$colorPrimario = $configuraciones['color_primario'] ?? '#667eea';
$colorSecundario = $configuraciones['color_secundario'] ?? '#764ba2';
$telefonoNegocio = $configuraciones['empresa_telefono'] ?? '';
$direccionNegocio = $configuraciones['empresa_direccion'] ?? '';
$webNegocio = $configuraciones['empresa_web'] ?? '';

// Configuración de reservas
$duracionReserva = $configuraciones['duracion_reserva'] ?? '30';
$intervaloReservas = $configuraciones['intervalo_reservas'] ?? '30';

// Tipos de día (JSON)
$tiposDiaJson = $configuraciones['tipos_dia'] ?? null;
if ($tiposDiaJson) {
    $tiposDia = json_decode($tiposDiaJson, true);
} else {
    // Valores por defecto
    $tiposDia = [
        'abierto' => [
            'nombre' => 'Abierto',
            'ventanas' => [['inicio' => '09:00', 'fin' => '18:00', 'capacidad' => 1]]
        ],
        'cerrado' => [
            'nombre' => 'Cerrado',
            'ventanas' => []
        ]
    ];
}

// Calendario anual (JSON) - día_mes_año => tipo_dia
$calendarioJson = $configuraciones['calendario'] ?? null;
$calendario = $calendarioJson ? json_decode($calendarioJson, true) : [];

// Mapeo día de semana => tipo_dia (para aplicar por defecto)
$mapeoSemanaJson = $configuraciones['mapeo_semana'] ?? null;
if ($mapeoSemanaJson) {
    $mapeoSemana = json_decode($mapeoSemanaJson, true);
} else {
    // Por defecto lun-vie => abierto, sab-dom => cerrado
    $mapeoSemana = [
        'lun' => 'abierto',
        'mar' => 'abierto',
        'mie' => 'abierto',
        'jue' => 'abierto',
        'vie' => 'abierto',
        'sab' => 'cerrado',
        'dom' => 'cerrado'
    ];
}

include 'includes/header.php';
?>

<style>
/* Tabs Styling */
.tabs {
    display: flex;
    gap: 0.5rem;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 2rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.tab-button {
    padding: 0.75rem 1.5rem;
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

/* Color Picker Custom */
.color-picker-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.color-preview {
    width: 60px;
    height: 60px;
    border-radius: 0.5rem;
    border: 2px solid #e5e7eb;
    cursor: pointer;
    transition: transform 0.2s;
}

.color-preview:hover {
    transform: scale(1.05);
}

.gradient-preview {
    width: 100%;
    height: 80px;
    border-radius: 0.75rem;
    border: 2px solid #e5e7eb;
    margin-top: 1rem;
}

/* Image Upload */
.image-upload-wrapper {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.image-preview {
    width: 200px;
    height: 200px;
    border: 2px dashed #e5e7eb;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #f9fafb;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-preview.empty {
    flex-direction: column;
    color: #9ca3af;
}

/* Tipo de día cards */
.tipo-dia-card {
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    background: white;
    transition: all 0.2s;
}

.tipo-dia-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}

.tipo-dia-card.editing {
    border-color: #667eea;
    background: linear-gradient(to bottom, rgba(102, 126, 234, 0.02), white);
}

/* Calendar Grid */
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
}

.calendar-day {
    aspect-ratio: 1;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.5rem;
    font-size: 0.875rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.calendar-day:hover {
    border-color: #667eea;
    background: #f3f4f6;
}

.calendar-day.selected {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.calendar-day.festivo {
    background: #fef3c7;
    border-color: #fbbf24;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .tabs {
        gap: 0.25rem;
    }
    
    .tab-button {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .calendar-grid {
        grid-template-columns: repeat(7, 1fr);
        gap: 0.25rem;
    }
    
    .calendar-day {
        font-size: 0.75rem;
        padding: 0.25rem;
    }
    
    .color-picker-wrapper {
        flex-direction: column;
        align-items: stretch;
    }
    
    .image-preview {
        width: 100%;
        height: 200px;
    }
}
</style>

<div class="max-w-6xl mx-auto px-4">
    <!-- Tabs Navigation -->
    <div class="tabs">
        <button class="tab-button active" data-tab="basica">
            <i class="ri-building-line"></i>
            <span>Información Básica</span>
        </button>
        <button class="tab-button" data-tab="reservas">
            <i class="ri-calendar-check-line"></i>
            <span>Reservas</span>
        </button>
        <button class="tab-button" data-tab="horarios">
            <i class="ri-time-line"></i>
            <span>Tipos de Horario</span>
        </button>
        <button class="tab-button" data-tab="calendario">
            <i class="ri-calendar-2-line"></i>
            <span>Calendario</span>
        </button>
    </div>

    <form id="configForm" class="space-y-6">
        
        <!-- TAB 1: Información Básica -->
        <div class="tab-content active" data-tab-content="basica">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                    <i class="ri-building-line mr-2 text-blue-600"></i>
                    Información del Negocio
                </h2>

                <div class="space-y-6">
                    <!-- Nombre del negocio -->
                    <div>
                        <label for="nombre_negocio" class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre del negocio *
                        </label>
                        <input
                            type="text"
                            id="nombre_negocio"
                            name="empresa_nombre"
                            value="<?php echo htmlspecialchars($nombreNegocio); ?>"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            required
                        >
                        <p class="text-sm text-gray-500 mt-1">Este nombre aparecerá en los emails a tus clientes</p>
                    </div>

                    <!-- Logo/Imagen -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Logo del negocio (opcional)
                        </label>
                        <div class="image-upload-wrapper">
                            <div class="image-preview <?php echo empty($imagenNegocio) ? 'empty' : ''; ?>" id="imagePreview">
                                <?php if (!empty($imagenNegocio)): ?>
                                    <img src="<?php echo htmlspecialchars($imagenNegocio); ?>" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <?php else: ?>
                                    <i class="ri-image-line text-4xl text-gray-400"></i>
                                    <span class="text-sm text-gray-500">Sin imagen</span>
                                <?php endif; ?>
                            </div>
                            <input
                                type="file"
                                id="imagen_negocio"
                                accept="image/*"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            >
                            <input type="hidden" id="imagen_negocio_url" name="empresa_imagen" value="<?php echo htmlspecialchars($imagenNegocio); ?>">
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="ri-information-line"></i>
                                Se redimensionará automáticamente a 128x128px. Máximo 60KB.
                            </p>
                        </div>
                    </div>

                    <!-- Colores -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="color_primario" class="block text-sm font-medium text-gray-700 mb-2">
                                Color Primario *
                            </label>
                            <div class="color-picker-wrapper">
                                <div class="color-preview" id="previewPrimario" style="background-color: <?php echo htmlspecialchars($colorPrimario); ?>;" onclick="document.getElementById('color_primario').click()"></div>
                                <input
                                    type="color"
                                    id="color_primario"
                                    name="color_primario"
                                    value="<?php echo htmlspecialchars($colorPrimario); ?>"
                                    class="hidden"
                                >
                                <input
                                    type="text"
                                    id="color_primario_text"
                                    value="<?php echo htmlspecialchars($colorPrimario); ?>"
                                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                                    pattern="^#[0-9A-Fa-f]{6}$"
                                >
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Color principal de tu marca</p>
                        </div>

                        <div>
                            <label for="color_secundario" class="block text-sm font-medium text-gray-700 mb-2">
                                Color Secundario *
                            </label>
                            <div class="color-picker-wrapper">
                                <div class="color-preview" id="previewSecundario" style="background-color: <?php echo htmlspecialchars($colorSecundario); ?>;" onclick="document.getElementById('color_secundario').click()"></div>
                                <input
                                    type="color"
                                    id="color_secundario"
                                    name="color_secundario"
                                    value="<?php echo htmlspecialchars($colorSecundario); ?>"
                                    class="hidden"
                                >
                                <input
                                    type="text"
                                    id="color_secundario_text"
                                    value="<?php echo htmlspecialchars($colorSecundario); ?>"
                                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                                    pattern="^#[0-9A-Fa-f]{6}$"
                                >
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Color complementario</p>
                        </div>
                    </div>

                    <!-- Preview del gradiente -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Vista previa del gradiente
                        </label>
                        <div class="gradient-preview" id="gradientPreview" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($colorPrimario); ?> 0%, <?php echo htmlspecialchars($colorSecundario); ?> 100%);"></div>
                    </div>

                    <!-- Información de contacto -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="telefono_negocio" class="block text-sm font-medium text-gray-700 mb-2">
                                Teléfono
                            </label>
                            <input
                                type="tel"
                                id="telefono_negocio"
                                name="empresa_telefono"
                                value="<?php echo htmlspecialchars($telefonoNegocio); ?>"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label for="web_negocio" class="block text-sm font-medium text-gray-700 mb-2">
                                Sitio web
                            </label>
                            <input
                                type="url"
                                id="web_negocio"
                                name="empresa_web"
                                value="<?php echo htmlspecialchars($webNegocio); ?>"
                                placeholder="https://www.ejemplo.com"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="direccion_negocio" class="block text-sm font-medium text-gray-700 mb-2">
                            Dirección
                        </label>
                        <input
                            type="text"
                            id="direccion_negocio"
                            name="empresa_direccion"
                            value="<?php echo htmlspecialchars($direccionNegocio); ?>"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Configuración de Reservas -->
        <div class="tab-content" data-tab-content="reservas">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                    <i class="ri-calendar-check-line mr-2 text-blue-600"></i>
                    Configuración de Reservas
                </h2>

                <div class="space-y-6">
                    <div>
                        <label for="duracion_reserva" class="block text-sm font-medium text-gray-700 mb-2">
                            Duración predeterminada de cada reserva (minutos) *
                        </label>
                        <select
                            id="duracion_reserva"
                            name="duracion_reserva"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="15" <?php echo $duracionReserva == 15 ? 'selected' : ''; ?>>15 minutos</option>
                            <option value="30" <?php echo $duracionReserva == 30 ? 'selected' : ''; ?>>30 minutos</option>
                            <option value="45" <?php echo $duracionReserva == 45 ? 'selected' : ''; ?>>45 minutos</option>
                            <option value="60" <?php echo $duracionReserva == 60 ? 'selected' : ''; ?>>1 hora</option>
                            <option value="90" <?php echo $duracionReserva == 90 ? 'selected' : ''; ?>>1 hora y 30 minutos</option>
                            <option value="120" <?php echo $duracionReserva == 120 ? 'selected' : ''; ?>>2 horas</option>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">
                            Tiempo que se asigna a cada reserva por defecto
                        </p>
                    </div>

                    <div>
                        <label for="intervalo_reservas" class="block text-sm font-medium text-gray-700 mb-2">
                            Intervalo entre horarios disponibles (minutos) *
                        </label>
                        <select
                            id="intervalo_reservas"
                            name="intervalo_reservas"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="15" <?php echo $intervaloReservas == 15 ? 'selected' : ''; ?>>15 minutos</option>
                            <option value="30" <?php echo $intervaloReservas == 30 ? 'selected' : ''; ?>>30 minutos</option>
                            <option value="45" <?php echo $intervaloReservas == 45 ? 'selected' : ''; ?>>45 minutos</option>
                            <option value="60" <?php echo $intervaloReservas == 60 ? 'selected' : ''; ?>>1 hora</option>
                            <option value="90" <?php echo $intervaloReservas == 90 ? 'selected' : ''; ?>>1 hora y 30 minutos</option>
                            <option value="120" <?php echo $intervaloReservas == 120 ? 'selected' : ''; ?>>2 horas</option>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">
                            Define cada cuánto tiempo se pueden hacer reservas
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: Tipos de Horario -->
        <div class="tab-content" data-tab-content="horarios">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="ri-time-line mr-2 text-blue-600"></i>
                        Tipos de Horario
                    </h2>
                    <button
                        type="button"
                        id="btnAddTipoDia"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                    >
                        <i class="ri-add-line mr-2"></i>
                        Nuevo Tipo
                    </button>
                </div>

                <p class="text-sm text-gray-600 mb-6">
                    Define diferentes tipos de horarios (ej: "Laboral", "Sábados", "Solo mañanas", etc.) y luego asígnalos en el calendario
                </p>

                <div id="tiposDiaContainer" class="space-y-4">
                    <?php foreach ($tiposDia as $id => $tipo): ?>
                        <div class="tipo-dia-card" data-tipo-id="<?php echo htmlspecialchars($id); ?>">
                            <div class="flex items-center justify-between mb-4">
                                <input
                                    type="text"
                                    value="<?php echo htmlspecialchars($tipo['nombre']); ?>"
                                    class="tipo-dia-nombre text-lg font-semibold border-0 border-b-2 border-transparent focus:border-blue-500 focus:ring-0 px-0"
                                    placeholder="Nombre del tipo de horario"
                                >
                                <div class="flex gap-2">
                                    <?php if (!in_array($id, ['abierto', 'cerrado'])): ?>
                                        <button
                                            type="button"
                                            class="btn-delete-tipo text-red-600 hover:text-red-800 p-2 rounded-full hover:bg-red-50"
                                            title="Eliminar tipo"
                                        >
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="ventanas-container space-y-3">
                                <?php if (empty($tipo['ventanas'])): ?>
                                    <p class="text-sm text-gray-500 italic">Sin horarios definidos (día cerrado)</p>
                                <?php else: ?>
                                    <?php foreach ($tipo['ventanas'] as $index => $ventana): ?>
                                        <div class="ventana-horaria flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                                            <div class="flex-1 grid grid-cols-3 gap-4">
                                                <div>
                                                    <label class="text-xs text-gray-600">Desde</label>
                                                    <input
                                                        type="time"
                                                        value="<?php echo htmlspecialchars($ventana['inicio']); ?>"
                                                        class="ventana-inicio block w-full rounded-md border-gray-300 text-sm"
                                                    >
                                                </div>
                                                <div>
                                                    <label class="text-xs text-gray-600">Hasta</label>
                                                    <input
                                                        type="time"
                                                        value="<?php echo htmlspecialchars($ventana['fin']); ?>"
                                                        class="ventana-fin block w-full rounded-md border-gray-300 text-sm"
                                                    >
                                                </div>
                                                <div>
                                                    <label class="text-xs text-gray-600">Capacidad</label>
                                                    <input
                                                        type="number"
                                                        value="<?php echo htmlspecialchars($ventana['capacidad'] ?? 1); ?>"
                                                        min="1"
                                                        max="50"
                                                        class="ventana-capacidad block w-full rounded-md border-gray-300 text-sm text-center"
                                                    >
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                class="btn-delete-ventana text-red-600 hover:text-red-800 p-2 rounded-full hover:bg-red-50"
                                            >
                                                <i class="ri-close-line"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <button
                                type="button"
                                class="btn-add-ventana mt-4 text-sm text-blue-600 hover:text-blue-800 flex items-center"
                            >
                                <i class="ri-add-line mr-1"></i>
                                Añadir ventana horaria
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- TAB 4: Calendario -->
        <div class="tab-content" data-tab-content="calendario">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                    <i class="ri-calendar-2-line mr-2 text-blue-600"></i>
                    Calendario Anual
                </h2>

                <div class="mb-6">
                    <h3 class="font-medium text-gray-900 mb-3">Asignación por defecto (Días de la semana)</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Selecciona qué tipo de horario se aplicará por defecto a cada día de la semana
                    </p>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                        <?php 
                        $diasSemana = ['lun' => 'Lunes', 'mar' => 'Martes', 'mie' => 'Miércoles', 'jue' => 'Jueves', 'vie' => 'Viernes', 'sab' => 'Sábado', 'dom' => 'Domingo'];
                        foreach ($diasSemana as $diaKey => $diaNombre): 
                        ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo $diaNombre; ?></label>
                                <select
                                    name="mapeo_semana_<?php echo $diaKey; ?>"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                >
                                    <?php foreach ($tiposDia as $id => $tipo): ?>
                                        <option value="<?php echo htmlspecialchars($id); ?>" <?php echo ($mapeoSemana[$diaKey] ?? 'abierto') == $id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="border-t pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-medium text-gray-900">Excepciones y días especiales</h3>
                        <button
                            type="button"
                            id="btnCargarFestivos"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <i class="ri-calendar-event-line mr-2"></i>
                            Cargar festivos de España <?php echo date('Y'); ?>
                        </button>
                    </div>

                    <p class="text-sm text-gray-600 mb-4">
                        Próximamente: Calendario interactivo para asignar tipos de horario a días específicos
                    </p>
                </div>
            </div>
        </div>

        <!-- Botón guardar -->
        <div class="flex justify-end gap-4 pt-4">
            <button
                type="submit"
                class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <i class="ri-save-line mr-2"></i>
                Guardar Configuración
            </button>
        </div>
    </form>
</div>

<!-- Templates -->
<template id="ventanaHorariaTemplate">
    <div class="ventana-horaria flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
        <div class="flex-1 grid grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gray-600">Desde</label>
                <input
                    type="time"
                    value="09:00"
                    class="ventana-inicio block w-full rounded-md border-gray-300 text-sm"
                >
            </div>
            <div>
                <label class="text-xs text-gray-600">Hasta</label>
                <input
                    type="time"
                    value="18:00"
                    class="ventana-fin block w-full rounded-md border-gray-300 text-sm"
                >
            </div>
            <div>
                <label class="text-xs text-gray-600">Capacidad</label>
                <input
                    type="number"
                    value="1"
                    min="1"
                    max="50"
                    class="ventana-capacidad block w-full rounded-md border-gray-300 text-sm text-center"
                >
            </div>
        </div>
        <button
            type="button"
            class="btn-delete-ventana text-red-600 hover:text-red-800 p-2 rounded-full hover:bg-red-50"
        >
            <i class="ri-close-line"></i>
        </button>
    </div>
</template>

<template id="tipoDiaTemplate">
    <div class="tipo-dia-card">
        <div class="flex items-center justify-between mb-4">
            <input
                type="text"
                value="Nuevo Tipo"
                class="tipo-dia-nombre text-lg font-semibold border-0 border-b-2 border-transparent focus:border-blue-500 focus:ring-0 px-0"
                placeholder="Nombre del tipo de horario"
            >
            <button
                type="button"
                class="btn-delete-tipo text-red-600 hover:text-red-800 p-2 rounded-full hover:bg-red-50"
                title="Eliminar tipo"
            >
                <i class="ri-delete-bin-line"></i>
            </button>
        </div>

        <div class="ventanas-container space-y-3">
            <p class="text-sm text-gray-500 italic">Sin horarios definidos</p>
        </div>

        <button
            type="button"
            class="btn-add-ventana mt-4 text-sm text-blue-600 hover:text-blue-800 flex items-center"
        >
            <i class="ri-add-line mr-1"></i>
            Añadir ventana horaria
        </button>
    </div>
</template>

<!-- Success Message -->
<div id="saveSuccessMessage" class="fixed bottom-4 right-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md shadow-lg hidden z-50">
    <div class="flex items-center">
        <i class="ri-check-line mr-2 text-green-500"></i>
        <span>Configuración guardada correctamente</span>
    </div>
</div>

<script src="/assets/js/configuracion.js"></script>

<?php include 'includes/footer.php'; ?>