<?php
// pages/reserva/reservar.php

// Obtener el formulario por slug usando DDD
$slug = $_GET['f'] ?? '';
$formulario = null;
$formularioEntity = null;
$error = '';

if (empty($slug)) {
    $error = 'Enlace no válido';
} else {
    try {
        // Usar FormularioDomain para obtener el formulario
        $formularioDomain = getContainer()->getFormularioDomain();
        $formularioEntity = $formularioDomain->obtenerFormularioPorSlug($slug);
        
        if (!$formularioEntity) {
            $error = 'Formulario no encontrado o no disponible';
        } else {
            // Verificar que está activo
            if (!$formularioEntity->isActivo()) {
                $error = 'Este formulario no está disponible actualmente';
            } else {
                // Convertir a array para compatibilidad con el código existente
                $formulario = $formularioEntity->toArray();
            }
        }
    } catch (Exception $e) {
        error_log("Error al cargar formulario: " . $e->getMessage());
        $error = 'Error al cargar el formulario';
    }
}

$configuracionNegocio = [];
if ($formulario) {
    try {
        $configuracionDomain = getContainer()->getConfiguracionDomain();
        $todasConfig = $configuracionDomain->obtenerConfiguraciones($formulario['usuario_id']);
        
        $configuracionNegocio = [
            'nombre' => $todasConfig['empresa_nombre'] ?? ($formulario['empresa_nombre'] ?? $formulario['nombre']),
            'logo' => $todasConfig['empresa_imagen'] ?? ($formulario['empresa_logo'] ?? null),
            'telefono' => $todasConfig['empresa_telefono'] ?? ($formulario['telefono_contacto'] ?? null),
            'email' => $todasConfig['empresa_email'] ?? null,
            'direccion' => $todasConfig['empresa_direccion'] ?? ($formulario['direccion'] ?? null),
            'web' => $todasConfig['empresa_web'] ?? null,
            'color_primario' => $todasConfig['color_primario'] ?? ($formulario['color_primario'] ?? '#667eea'),
            'color_secundario' => $todasConfig['color_secundario'] ?? ($formulario['color_secundario'] ?? '#764ba2')
        ];
    } catch (Exception $e) {
        error_log("Error obteniendo configuración del negocio: " . $e->getMessage());
        // Usar valores del formulario como fallback
        $configuracionNegocio = [
            'nombre' => $formulario['empresa_nombre'] ?? $formulario['nombre'],
            'logo' => $formulario['empresa_logo'] ?? null,
            'telefono' => $formulario['telefono_contacto'] ?? null,
            'email' => null,
            'direccion' => $formulario['direccion'] ?? null,
            'web' => null,
            'color_primario' => $formulario['color_primario'] ?? '#667eea',
            'color_secundario' => $formulario['color_secundario'] ?? '#764ba2'
        ];
    }
}

// Obtener configuración de horarios usando ReservaDomain
$horarios = [];
$intervaloReservas = 30;
$modoAceptacion = 'manual';

if ($formulario) {
    try {
        $reservaDomain = getContainer()->getReservaDomain();
        $usuarioId = $formulario['usuario_id'];
        
        // Obtener configuración de horarios a través del dominio
        $diasSemana = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];
        
        // Crear una fecha de ejemplo para cada día de la semana
        $fechaBase = new DateTime();
        $diaActual = (int)$fechaBase->format('w'); // 0 (domingo) a 6 (sábado)
        
        foreach ($diasSemana as $index => $dia) {
            // Mapear índice a día de semana: lun=1, mar=2, ..., dom=0
            $diaNumerico = $index === 6 ? 0 : $index + 1;
            
            // Calcular días de diferencia desde hoy
            $diferenciaDias = ($diaNumerico - $diaActual + 7) % 7;
            if ($diferenciaDias === 0) {
                $diferenciaDias = 0; // Hoy
            }
            
            $fechaPrueba = clone $fechaBase;
            $fechaPrueba->modify("+{$diferenciaDias} days");
            
            try {
                // Obtener horas del día a través del dominio
                $horasDelDia = $reservaDomain->obtenerHorasDelDia($fechaPrueba, $usuarioId);
                
                $horarios[$dia] = [
                    'activo' => !empty($horasDelDia),
                    'inicio' => !empty($horasDelDia) ? $horasDelDia[0] : '09:00',
                    'fin' => !empty($horasDelDia) ? end($horasDelDia) : '18:00'
                ];
            } catch (Exception $e) {
                // Si falla, marcar como inactivo
                $horarios[$dia] = [
                    'activo' => false,
                    'inicio' => '09:00',
                    'fin' => '18:00'
                ];
            }
        }
        
        // El intervalo se puede inferir o obtener de configuración
        // Por ahora usamos un valor por defecto
        $intervaloReservas = 30;
        
        // Obtener modo de aceptación desde configuraciones
        $stmt = getPDO()->prepare("SELECT valor FROM configuraciones WHERE clave = 'modo_aceptacion' AND usuario_id = ?");
        $stmt->execute([$usuarioId]);
        $modoAceptacion = $stmt->fetchColumn() ?: 'manual';
        
    } catch (Exception $e) {
        error_log("Error obteniendo configuración de horarios: " . $e->getMessage());
        
        // Si hay error, usar horarios por defecto
        foreach (['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'] as $dia) {
            $horarios[$dia] = [
                'activo' => in_array($dia, ['lun', 'mar', 'mie', 'jue', 'vie']),
                'inicio' => '09:00',
                'fin' => '18:00'
            ];
        }
        $intervaloReservas = 30;
        $modoAceptacion = 'manual';
    }
}

// Verificar si es una respuesta exitosa
$reservaExitosa = false;
$datosReserva = null;

if (isset($_GET['success']) && $_GET['success'] == '1' && $formulario) {
    $reservaExitosa = true;
    $datosReserva = [
        'nombre' => $_GET['nombre'] ?? '',
        'email' => $_GET['email'] ?? '',
        'telefono' => $_GET['telefono'] ?? '',
        'fecha' => $_GET['fecha'] ?? '',
        'hora' => $_GET['hora'] ?? '',
        'mensaje' => $_GET['mensaje'] ?? '',
        'confirmacion_automatica' => isset($_GET['auto']) && $_GET['auto'] == '1',
        'token' => $_GET['token'] ?? ''
    ];
    
    $mensaje = $datosReserva['confirmacion_automatica']
        ? 'Tu reserva ha sido confirmada automáticamente. ¡Te esperamos!' 
        : 'Tu solicitud de reserva ha sido recibida. Te contactaremos pronto para confirmarla.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $formulario ? htmlspecialchars($formulario['nombre']) : 'Reservar Cita'; ?> - ReservaBot</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Iconos -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
        
<style>
    :root {
        --primary-color: <?php echo htmlspecialchars($configuracionNegocio['color_primario'] ?? '#667eea'); ?>;
        --secondary-color: <?php echo htmlspecialchars($configuracionNegocio['color_secundario'] ?? '#764ba2'); ?>;
    }
    
    /* === GRADIENTE COMPACTO === */
    .gradient-bg {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    }
    
    /* === BOTONES === */
    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        transition: all 0.2s ease;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, 
            color-mix(in srgb, var(--primary-color) 90%, black) 0%, 
            color-mix(in srgb, var(--secondary-color) 90%, black) 100%);
        transform: translateY(-1px);
    }
    
    /* === UTILIDADES === */
    .text-primary { color: var(--primary-color); }
    .border-primary { border-color: var(--primary-color); }
    .focus\:ring-primary:focus { --tw-ring-color: var(--primary-color); }
    .focus\:border-primary:focus { border-color: var(--primary-color); }
    
    /* === ANIMACIONES SUAVES === */
    .fade-in {
        animation: fadeIn 0.4s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .success-animation {
        animation: successPulse 1s ease-in-out;
    }
    
    @keyframes successPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }
    
    /* === LOADING === */
    .btn-loading {
        position: relative;
        color: transparent;
    }
    
    .btn-loading::after {
        content: "";
        position: absolute;
        width: 14px;
        height: 14px;
        top: 50%;
        left: 50%;
        margin-left: -7px;
        margin-top: -7px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    /* === RESPONSIVO === */
    @media (max-width: 640px) {
        .truncate {
            max-width: 200px;
        }
    }
    
    /* === ACCESIBILIDAD === */
    @media (prefers-reduced-motion: reduce) {
        .fade-in,
        .btn-loading::after {
            animation: none;
        }
        
        .btn-primary:hover {
            transform: none;
        }
    }
</style>

</head>
<body class="bg-gray-50">
    
    <?php if ($error): ?>
        <!-- Página de error -->
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-8 text-center">
                <div class="fade-in">
                    <i class="ri-error-warning-line text-6xl text-red-500 mb-4"></i>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Error</h1>
                    <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($error); ?></p>
                    <a href="/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="ri-home-line mr-2"></i>
                        Volver al inicio
                    </a>
                </div>
            </div>
        </div>
        
    <?php elseif ($reservaExitosa): ?>
        <!-- Página de éxito -->
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-8">
                <div class="fade-in success-animation bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-check-line text-2xl text-green-600"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        <?php echo $datosReserva['confirmacion_automatica'] ? '¡Reserva Confirmada!' : '¡Solicitud Recibida!'; ?>
                    </h1>
                    <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($mensaje); ?></p>
                    
                    <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                        <h3 class="font-medium text-gray-900 mb-2">Detalles de tu reserva:</h3>
                        <div class="space-y-1 text-sm text-gray-600">
                            <p><span class="font-medium">Nombre:</span> <?php echo htmlspecialchars($datosReserva['nombre']); ?></p>
                            <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($datosReserva['email']); ?></p>
                            <p><span class="font-medium">Teléfono:</span> <?php echo htmlspecialchars($datosReserva['telefono']); ?></p>
                            <p><span class="font-medium">Fecha:</span> <?php echo $datosReserva['fecha'] ? date('d/m/Y', strtotime($datosReserva['fecha'])) : ''; ?></p>
                            <p><span class="font-medium">Hora:</span> <?php echo htmlspecialchars($datosReserva['hora']); ?></p>
                            <?php if (!empty($datosReserva['mensaje'])): ?>
                                <p><span class="font-medium">Comentarios:</span> <?php echo htmlspecialchars($datosReserva['mensaje']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Enlace de gestión de reserva -->
                    <?php if (!empty($datosReserva['token'])): ?>
                        <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-lg p-4 mb-6">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="ri-link text-purple-600 text-lg"></i>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h4 class="text-sm font-medium text-purple-900 mb-2">📧 Email de confirmación enviado</h4>
                                    <p class="text-sm text-purple-800 mb-3">
                                        Hemos enviado un email a <strong><?php echo htmlspecialchars($datosReserva['email']); ?></strong> 
                                        con todos los detalles y un enlace para gestionar tu reserva.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Estado de la reserva -->
                    <?php if (!$datosReserva['confirmacion_automatica']): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-6">
                            <p class="text-sm text-blue-800">
                                <i class="ri-information-line mr-1"></i>
                                Tu solicitud está pendiente. Te contactaremos pronto para confirmarla.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-6">
                            <p class="text-sm text-green-800">
                                <i class="ri-check-line mr-1"></i>
                                ¡Tu reserva está confirmada! No necesitas hacer nada más.
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Botones de acción -->
                    <div class="space-y-3">
                        <?php if (!empty($datosReserva['token'])): ?>
                            <a href="mi-reserva?token=<?php echo htmlspecialchars($datosReserva['token']); ?>" 
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all">
                                <i class="ri-settings-line mr-2"></i>
                                Ver y gestionar mi reserva
                            </a>
                        <?php endif; ?>
                        
                        <button onclick="resetForm()" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <i class="ri-add-line mr-2"></i>
                            Hacer otra reserva
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Formulario de reserva -->
        <div class="min-h-screen bg-gray-50">

            <!-- Header -->
            <div class="gradient-bg relative overflow-hidden">
                <!-- Efecto sutil de fondo -->
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white rounded-full -mr-20 -mt-20"></div>
                    <div class="absolute bottom-0 left-0 w-32 h-32 bg-white rounded-full -ml-16 -mb-16"></div>
                </div>
                
                <!-- Contenido principal -->
                <div class="relative z-10 max-w-4xl mx-auto px-4 py-3 sm:px-6">
                    <div class="flex items-center justify-between text-white">
                        
                        <!-- Logo y nombre (lado izquierdo) -->
                        <div class="flex items-center space-x-3">
                            <?php if (!empty($configuracionNegocio['logo'])): ?>
                                <div class="flex-shrink-0">
                                    <img src="<?php echo htmlspecialchars($configuracionNegocio['logo']); ?>" 
                                        alt="<?php echo htmlspecialchars($configuracionNegocio['nombre']); ?>"
                                        class="h-10 w-auto object-contain bg-white/15 rounded-lg p-1.5 shadow-md">
                                </div>
                            <?php endif; ?>
                            
                            <div class="min-w-0">
                                <h1 class="text-lg font-bold sm:text-xl truncate">
                                    <?php echo htmlspecialchars($configuracionNegocio['nombre']); ?>
                                </h1>
                                
                                <?php if (!empty($formulario['nombre']) && $formulario['nombre'] !== $configuracionNegocio['nombre']): ?>
                                    <p class="text-sm text-white/90 truncate">
                                        <?php echo htmlspecialchars($formulario['nombre']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Información de contacto (lado derecho) -->
                        <div class="hidden sm:flex items-center space-x-4 text-sm">
                            <?php if (!empty($configuracionNegocio['telefono'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($configuracionNegocio['telefono']); ?>" 
                                class="flex items-center space-x-2 bg-white/10 rounded-full px-3 py-1.5 hover:bg-white/20 transition-colors">
                                    <i class="ri-phone-line text-xs"></i>
                                    <span class="font-medium"><?php echo htmlspecialchars($configuracionNegocio['telefono']); ?></span>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($configuracionNegocio['direccion'])): ?>
                                <div class="flex items-center space-x-2 text-white/80 max-w-xs">
                                    <i class="ri-map-pin-line text-xs flex-shrink-0"></i>
                                    <span class="truncate text-xs"><?php echo htmlspecialchars($configuracionNegocio['direccion']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Menú móvil para información -->
                        <div class="sm:hidden">
                            <button type="button" onclick="toggleMobileInfo()" class="bg-white/10 rounded-full p-2 hover:bg-white/20 transition-colors">
                                <i class="ri-information-line text-sm"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Mensaje de bienvenida compacto (opcional) -->
                    <?php if (!empty($formulario['mensaje_bienvenida'])): ?>
                        <div class="mt-2 text-center">
                            <p class="text-sm text-white/90 max-w-2xl mx-auto leading-relaxed">
                                <?php echo htmlspecialchars($formulario['mensaje_bienvenida']); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Información móvil desplegable -->
                    <div id="mobileInfo" class="hidden sm:hidden mt-3 pt-3 border-t border-white/20">
                        <div class="space-y-2 text-sm">
                            <?php if (!empty($configuracionNegocio['telefono'])): ?>
                                <div class="flex items-center justify-center space-x-2">
                                    <i class="ri-phone-line"></i>
                                    <a href="tel:<?php echo htmlspecialchars($configuracionNegocio['telefono']); ?>" 
                                    class="font-medium hover:text-white/80">
                                        <?php echo htmlspecialchars($configuracionNegocio['telefono']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($configuracionNegocio['direccion'])): ?>
                                <div class="flex items-center justify-center space-x-2 text-white/80">
                                    <i class="ri-map-pin-line"></i>
                                    <span class="text-center"><?php echo htmlspecialchars($configuracionNegocio['direccion']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($configuracionNegocio['email'])): ?>
                                <div class="flex items-center justify-center space-x-2 text-white/80">
                                    <i class="ri-mail-line"></i>
                                    <a href="mailto:<?php echo htmlspecialchars($configuracionNegocio['email']); ?>" 
                                    class="hover:text-white/60">
                                        <?php echo htmlspecialchars($configuracionNegocio['email']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulario -->
            <div class="max-w-2xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow-lg fade-in">
                    <div class="px-6 py-8">
                        <!-- Contenedor para mensajes de error -->
                        <div id="errorContainer" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4 hidden">
                            <div class="flex">
                                <i class="ri-error-warning-line text-red-400 mt-0.5 mr-3"></i>
                                <p class="text-sm text-red-800" id="errorMessage"></p>
                            </div>
                        </div>
                        
                        <form id="reservaForm" class="space-y-6">
                            <!-- Información personal -->
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                                        Nombre completo *
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="ri-user-line text-gray-400"></i>
                                        </div>
                                        <input
                                            type="text"
                                            name="nombre"
                                            id="nombre"
                                            required
                                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            placeholder="Tu nombre completo"
                                        >
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                     <div>
                                        <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">
                                            Teléfono *
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ri-phone-line text-gray-400"></i>
                                            </div>
                                            <input
                                                type="tel"
                                                name="telefono"
                                                id="telefono"
                                                required
                                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                placeholder="+34 600 123 456"
                                            >
                                        </div>
                                    </div>

                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                            Email *
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="ri-mail-line text-gray-400"></i>
                                            </div>
                                            <input
                                                type="email"
                                                name="email"
                                                id="email"
                                                required
                                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                placeholder="tu@email.com"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Fecha y hora -->
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="fecha" class="block text-sm font-medium text-gray-700 mb-1">
                                        Fecha *
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="ri-calendar-line text-gray-400"></i>
                                        </div>
                                        <input
                                            type="date"
                                            name="fecha"
                                            id="fecha"
                                            required
                                            min="<?php echo date('Y-m-d'); ?>"
                                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="hora" class="block text-sm font-medium text-gray-700 mb-1">
                                        Hora *
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="ri-time-line text-gray-400"></i>
                                        </div>
                                        <select
                                            name="hora"
                                            id="hora"
                                            required
                                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                            <option value="">Selecciona una fecha primero</option>
                                        </select>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500" id="horarioInfo"></p>
                                </div>
                            </div>
                            
                            <!-- Comentarios -->
                            <div>
                                <label for="comentarios" class="block text-sm font-medium text-gray-700 mb-1">
                                    Comentarios adicionales
                                </label>
                                <div class="relative">
                                    <div class="absolute top-3 left-3 flex items-start pointer-events-none">
                                        <i class="ri-message-2-line text-gray-400"></i>
                                    </div>
                                    <textarea
                                        name="mensaje"
                                        id="mensaje"
                                        rows="3"
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        placeholder="Información adicional que consideres importante..."
                                    ></textarea>
                                </div>
                            </div>
                            
                            <!-- Botón de envío -->
                            <div class="pt-4">
                                <button
                                    type="submit"
                                    id="submitBtn"
                                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white btn-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <i class="ri-calendar-check-line mr-2" id="submitIcon"></i>
                                    <span id="submitText">Reservar Cita</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Información adicional -->
                <div class="mt-8 bg-white rounded-lg shadow-sm p-6 fade-in">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información importante</h3>
                    <div class="space-y-3 text-sm text-gray-600">
                        <div class="flex items-start">
                            <?php if ($formulario['confirmacion_automatica']): ?>
                                <i class="ri-check-line text-green-500 mt-0.5 mr-3 flex-shrink-0"></i>
                                <p>Tu reserva será confirmada automáticamente.</p>
                            <?php else: ?>
                                <i class="ri-time-line text-orange-500 mt-0.5 mr-3 flex-shrink-0"></i>
                                <p>Tu reserva será confirmada en las próximas horas.</p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-start">
                            <i class="ri-mail-line text-blue-500 mt-0.5 mr-3 flex-shrink-0"></i>
                            <p>Recibirás un email con los detalles y un enlace para gestionar tu reserva.</p>
                        </div>
                        <div class="flex items-start">
                            <i class="ri-phone-line text-green-500 mt-0.5 mr-3 flex-shrink-0"></i>
                            <p>Te contactaremos al número proporcionado si es necesario.</p>
                        </div>
                    </div>
                </div>
            </div>
        
        
        
        </div>
    <?php endif; ?>
    
    <script>
        // Datos de configuración para JavaScript
        const config = {
            horarios: <?php echo json_encode($horarios ?? []); ?>,
            intervalo: <?php echo json_encode($intervaloReservas ?? 30); ?>,
            slug: <?php echo json_encode($slug); ?>,
            confirmacionAutomatica: <?php echo json_encode($formulario['confirmacion_automatica'] ?? 0); ?>,
            usuarioId: <?php echo json_encode($formulario['usuario_id'] ?? null); ?>,
            formularioId: <?php echo json_encode($formulario['id'] ?? null); ?>
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            const fechaInput = document.getElementById('fecha');
            const horaSelect = document.getElementById('hora');
            const horarioInfo = document.getElementById('horarioInfo');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitIcon = document.getElementById('submitIcon');
            const errorContainer = document.getElementById('errorContainer');
            const errorMessage = document.getElementById('errorMessage');
            
            if (fechaInput && horaSelect) {
                fechaInput.addEventListener('change', cargarHorasDisponibles);
            }

            function showError(message, type = 'error') {
                if (type === 'success') {
                    // Cambiar el estilo del contenedor para éxito
                    errorContainer.className = 'mb-6 bg-green-50 border border-green-200 rounded-md p-4';
                    errorContainer.innerHTML = `
                        <div class="flex">
                            <i class="ri-check-line text-green-400 mt-0.5 mr-3"></i>
                            <p class="text-sm text-green-800">${message}</p>
                        </div>
                    `;
                } else {
                    // Estilo normal de error
                    errorContainer.className = 'mb-6 bg-red-50 border border-red-200 rounded-md p-4';
                    errorContainer.innerHTML = `
                        <div class="flex">
                            <i class="ri-error-warning-line text-red-400 mt-0.5 mr-3"></i>
                            <p class="text-sm text-red-800">${message}</p>
                        </div>
                    `;
                }
                
                errorContainer.classList.remove('hidden');
                // Scroll al mensaje
                errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            function hideError() {
                errorContainer.classList.add('hidden');
            }
            
            function cargarHorasDisponibles() {
                const fecha = fechaInput.value;
                if (!fecha) return;
                
                hideError(); // Ocultar errores previos
                
                // Mostrar loading
                horaSelect.innerHTML = '<option value="">Cargando horas...</option>';
                horaSelect.disabled = true;
                
                // Obtener día de la semana
                const diaSemana = getDiaSemana(fecha);
                const horarioDia = config.horarios[diaSemana];
                
                if (!horarioDia || !horarioDia.activo) {
                    horaSelect.innerHTML = '<option value="">Día no disponible</option>';
                    horarioInfo.textContent = 'Este día no está disponible para reservas.';
                    return;
                }
                
                // Hacer petición para obtener horas disponibles (usando la nueva API con DDD)
                fetch('api/horas-disponibles', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        fecha: fecha,
                        usuario_id: config.usuarioId 
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        horaSelect.innerHTML = '<option value="">Selecciona una hora</option>';
                        
                        if (data.horas.length === 0) {
                            horaSelect.innerHTML += '<option value="">No hay horas disponibles</option>';
                            horarioInfo.innerHTML = '<span class="text-red-600">No hay horarios disponibles para esta fecha.</span>';
                        } else {
                            // Agrupar horas por ventanas si hay múltiples
                            if (data.total_ventanas && data.total_ventanas > 1) {
                                // Mostrar información de múltiples ventanas
                                let horarioTexto = `<div class="space-y-1">
                                    <div class="text-sm font-medium text-gray-700">Horarios disponibles:</div>`;
                                
                                data.ventanas.forEach((ventana, index) => {
                                    horarioTexto += `<div class="text-xs text-gray-600">• ${ventana}</div>`;
                                });
                                
                                horarioTexto += `</div>`;
                                horarioInfo.innerHTML = horarioTexto;
                                
                                // Agregar horas con separadores visuales si es necesario
                                let currentVentana = '';
                                data.horas.forEach(hora => {
                                    // Determinar a qué ventana pertenece esta hora
                                    let ventanaCorrespondiente = '';
                                    data.ventanas.forEach(ventana => {
                                        const [inicio, fin] = ventana.split(' - ');
                                        if (hora >= inicio && hora < fin) {
                                            ventanaCorrespondiente = ventana;
                                        }
                                    });
                                    
                                    // Agregar separador si es una nueva ventana
                                    if (ventanaCorrespondiente !== currentVentana && data.total_ventanas > 1) {
                                        if (currentVentana !== '') {
                                            horaSelect.innerHTML += '<option disabled>────────────</option>';
                                        }
                                        horaSelect.innerHTML += `<option disabled style="font-weight: bold; color: #4F46E5;">📅 ${ventanaCorrespondiente}</option>`;
                                        currentVentana = ventanaCorrespondiente;
                                    }
                                    
                                    horaSelect.innerHTML += `<option value="${hora}">${hora}</option>`;
                                });
                            } else {
                                // Una sola ventana, mostrar como antes
                                data.horas.forEach(hora => {
                                    horaSelect.innerHTML += `<option value="${hora}">${hora}</option>`;
                                });
                                
                                horarioInfo.innerHTML = `<span class="text-green-600">Horario: ${data.horario_inicio} - ${data.horario_fin}</span>`;
                            }
                        }
                    } else {
                        horaSelect.innerHTML = '<option value="">Error al cargar horas</option>';
                        horarioInfo.innerHTML = `<span class="text-red-600">${data.message || 'Error al cargar las horas disponibles.'}</span>`;
                    }
                    horaSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    horaSelect.innerHTML = '<option value="">Error al cargar horas</option>';
                    horarioInfo.innerHTML = '<span class="text-red-600">Error de conexión. Por favor, intenta nuevamente.</span>';
                    horaSelect.disabled = false;
                });
            }
            
            function getDiaSemana(fecha) {
                const diasMap = {
                    1: 'lun', 2: 'mar', 3: 'mie', 4: 'jue', 
                    5: 'vie', 6: 'sab', 0: 'dom'
                };
                const diaSemana = new Date(fecha + 'T00:00:00').getDay();
                return diasMap[diaSemana];
            }
            
            function setLoadingState(loading) {
                submitBtn.disabled = loading;
                if (loading) {
                    submitBtn.classList.add('btn-loading');
                    submitText.textContent = 'Procesando reserva...';
                    submitIcon.style.display = 'none';
                } else {
                    submitBtn.classList.remove('btn-loading');
                    submitText.textContent = 'Reservar Cita';
                    submitIcon.style.display = 'inline';
                }
            }
            
            // Manejar envío del formulario
            const form = document.getElementById('reservaForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Evitar envío tradicional
                    
                    hideError(); // Ocultar errores previos
                    setLoadingState(true); // Mostrar estado de carga
                    
                    // Recopilar datos del formulario
                    const formData = new FormData(form);
                    const data = {};
                    formData.forEach((value, key) => {
                        data[key] = value.trim();
                    });

                    // Añadir el usuario_id del negocio y el ID del formulario
                    data.usuario_id = config.usuarioId;
                    data.formulario_id = config.formularioId;
                    
                    // Validaciones del lado cliente
                    if (!data.nombre || !data.email || !data.telefono || !data.fecha || !data.hora) {
                        showError('Por favor completa todos los campos obligatorios');
                        setLoadingState(false);
                        return;
                    }

                    // Validar formato de email
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(data.email)) {
                        showError('Por favor introduce un email válido');
                        setLoadingState(false);
                        return;
                    }
                    
                    if (data.fecha < new Date().toISOString().split('T')[0]) {
                        showError('La fecha no puede ser anterior a hoy');
                        setLoadingState(false);
                        return;
                    }
                    
                    // Enviar vía AJAX a la API (que ahora usa ReservaDomain)
                    fetch('api/reserva-publica-crear', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => {
                        // Verificar si la respuesta es válida
                        if (!response.ok) {
                            if (response.status === 404) {
                                throw new Error('API no encontrada. Verifica que el archivo api/reserva-publica-crear existe.');
                            } else if (response.status === 500) {
                                throw new Error('Error interno del servidor. Revisa los logs de PHP.');
                            } else {
                                throw new Error(`Error del servidor: ${response.status} ${response.statusText}`);
                            }
                        }
                        
                        // Verificar que la respuesta es JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            throw new Error('La respuesta del servidor no es JSON válido. Verifica la configuración de la API.');
                        }
                        
                        return response.json();
                    })
                    .then(result => {
                        console.log('Respuesta de la API:', result); // Debug
                        
                        if (result.success) {
                            // Verificar que tenemos los datos necesarios
                            if (!result.datos || !result.datos.nombre) {
                                console.warn('Respuesta exitosa pero faltan datos:', result);
                                throw new Error('Respuesta incompleta del servidor');
                            }
                            
                            // Redirigir a la misma página con parámetros de éxito
                            const urlParams = new URLSearchParams(window.location.search);
                            const baseUrl = window.location.pathname + '?f=' + urlParams.get('f');
                            
                            const successParams = new URLSearchParams({
                                success: '1',
                                nombre: result.datos.nombre,
                                email: result.datos.email,
                                telefono: result.datos.telefono,
                                fecha: result.datos.fecha,
                                hora: result.datos.hora,
                                mensaje: result.datos.mensaje || '',
                                auto: result.confirmacion_automatica ? '1' : '0',
                                token: result.token || ''
                            });
                            
                            const successUrl = baseUrl + '&' + successParams.toString();
                            
                            // Mostrar mensaje de éxito antes de redirigir
                            showError('¡Reserva creada con éxito! Redirigiendo...', 'success');
                            
                            // Redirigir después de un breve delay
                            setTimeout(() => {
                                window.location.href = successUrl;
                            }, 1000);
                            
                        } else {
                            // Mostrar error del servidor
                            const errorMsg = result.message || 'Error desconocido al procesar la reserva';
                            showError(errorMsg);
                            setLoadingState(false);
                        }
                    })
                    .catch(error => {
                        console.error('Error completo:', error);
                        
                        // Mostrar error más específico
                        let errorMessage = 'Error de conexión. ';
                        
                        if (error.message.includes('API no encontrada')) {
                            errorMessage = 'Error del sistema: API no encontrada. Contacta al administrador.';
                        } else if (error.message.includes('JSON')) {
                            errorMessage = 'Error del servidor: respuesta inválida. Contacta al administrador.';
                        } else if (error.message.includes('NetworkError') || error.message.includes('fetch')) {
                            errorMessage = 'Error de conexión a internet. Verifica tu conexión e intenta nuevamente.';
                        } else {
                            errorMessage += error.message;
                        }
                        
                        showError(errorMessage);
                        setLoadingState(false);
                    });
                });
            }
            
            // Validación específica para email
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    const email = this.value.trim();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    
                    if (email && !emailRegex.test(email)) {
                        this.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                        this.classList.remove('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
                    } else if (email) {
                        this.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                        this.classList.add('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
                    }
                });
            }
        });
        
        // Función para resetear el formulario (llamada desde el botón de "Hacer otra reserva")
        function resetForm() {
            const urlParams = new URLSearchParams(window.location.search);
            const baseUrl = window.location.pathname + '?f=' + urlParams.get('f');
            window.location.href = baseUrl;
        }
        
        // JavaScript para el menú móvil
        function toggleMobileInfo() {
            const mobileInfo = document.getElementById('mobileInfo');
            mobileInfo.classList.toggle('hidden');
        }
    </script>
</body>
</html>