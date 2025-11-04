<?php
// pages/reserva/mi-reserva.php

// Página para que los clientes gestionen sus reservas mediante token

// Configurar idioma español para fechas
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'spanish');

$token = $_GET['token'] ?? '';
$reserva = null;
$formularioData = null;
$error = '';
$mensaje = '';
$tipoMensaje = '';

// Validar token y obtener reserva usando dominio
if (empty($token)) {
    $error = 'Token no válido';
} else {
    try {
        $reservaDomain = getContainer()->getReservaDomain();
        
        // Obtener reserva por token
        $reservaEntity = $reservaDomain->obtenerReservaPorToken($token);
        $reserva = $reservaEntity->toArray();
        
        // Obtener datos del formulario si existe (para branding) usando dominio
        if ($reserva['formulario_id']) {
            try {
                $formularioDomain = getContainer()->getFormularioDomain();
                $formularioEntity = $formularioDomain->obtenerFormularioPorId(
                    $reserva['formulario_id'], 
                    $reserva['usuario_id']
                );
                
                if ($formularioEntity) {
                    $formularioData = $formularioEntity->toArray();
                    
                    // Mapear campos para compatibilidad con template
                    $reserva['formulario_nombre'] = $formularioData['nombre'];
                    $reserva['empresa_nombre'] = $formularioData['empresa_nombre'];
                    $reserva['empresa_logo'] = $formularioData['empresa_logo'];
                    $reserva['color_primario'] = $formularioData['color_primario'];
                    $reserva['color_secundario'] = $formularioData['color_secundario'];
                    $reserva['direccion'] = $formularioData['direccion'];
                    $reserva['telefono_contacto'] = $formularioData['telefono_contacto'];
                    $reserva['email_contacto'] = $formularioData['email_contacto'];
                    $reserva['mensaje_bienvenida'] = $formularioData['mensaje_bienvenida'] ?? null;
                }
            } catch (\Exception $e) {
                error_log('Error obteniendo formulario público: ' . $e->getMessage());
            }
        }
        
    } catch (\DomainException $e) {
        $error = $e->getMessage();
    } catch (\Exception $e) {
        error_log('Error cargando reserva por token: ' . $e->getMessage());
        $error = 'Error al cargar la reserva';
    }
}

// Verificar si puede modificar (24h antes)
$puedeModificar = false;
$tiempoRestante = '';

if ($reserva) {
    $fechaHoraReserva = new DateTime($reserva['fecha'] . ' ' . $reserva['hora']);
    $fechaLimite = clone $fechaHoraReserva;
    $fechaLimite->sub(new DateInterval('PT24H'));
    $ahora = new DateTime();
    
    $puedeModificar = $ahora < $fechaLimite && in_array($reserva['estado'], ['pendiente', 'confirmada']);
    
    if ($puedeModificar) {
        $diferencia = $fechaLimite->diff($ahora);
        if ($diferencia->days > 0) {
            $tiempoRestante = $diferencia->days . ' días';
        } elseif ($diferencia->h > 0) {
            $tiempoRestante = $diferencia->h . ' horas';
        } else {
            $tiempoRestante = 'menos de 1 hora';
        }
    }
}

// Procesar acciones usando dominio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reserva) {
    $action = $_POST['action'] ?? '';
    
    try {
        $reservaDomain = getContainer()->getReservaDomain();
        
        if ($action === 'cancelar' && $puedeModificar) {
            // Cancelar usando dominio
            $reservaDomain->cancelarReservaPublica($reserva['id'], $token);
            
            $mensaje = 'Tu reserva ha sido cancelada correctamente';
            $tipoMensaje = 'success';
            $reserva['estado'] = 'cancelada';
            $puedeModificar = false;
            
        } elseif ($action === 'modificar' && $puedeModificar) {
            $nuevaFecha = $_POST['nueva_fecha'] ?? '';
            $nuevaHora = $_POST['nueva_hora'] ?? '';
            
            if (!empty($nuevaFecha) && !empty($nuevaHora)) {
                // Modificar usando dominio
                $fechaObj = new DateTime($nuevaFecha);
                $reservaDomain->modificarReservaPublica(
                    $reserva['id'], 
                    $token, 
                    $fechaObj, 
                    $nuevaHora
                );
                
                $mensaje = 'Tu reserva ha sido modificada correctamente';
                $tipoMensaje = 'success';
                $reserva['fecha'] = $nuevaFecha;
                $reserva['hora'] = $nuevaHora;
                
                // Recalcular si puede modificar con la nueva fecha
                $fechaHoraReserva = new DateTime($reserva['fecha'] . ' ' . $reserva['hora']);
                $fechaLimite = clone $fechaHoraReserva;
                $fechaLimite->sub(new DateInterval('PT24H'));
                $ahora = new DateTime();
                $puedeModificar = $ahora < $fechaLimite && in_array($reserva['estado'], ['pendiente', 'confirmada']);
            } else {
                $mensaje = 'Debes seleccionar fecha y hora';
                $tipoMensaje = 'error';
            }
        }
        
    } catch (\DomainException $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'error';
    } catch (\Exception $e) {
        error_log('Error procesando acción: ' . $e->getMessage());
        $mensaje = 'Error al procesar la solicitud';
        $tipoMensaje = 'error';
    }
}

// Obtener horarios disponibles para modificación usando dominio
$horariosDisponibles = [];
if ($reserva && $puedeModificar) {
    try {
        $reservaDomain = getContainer()->getReservaDomain();
        
        // Generar próximos 14 días disponibles
        for ($i = 0; $i < 14; $i++) {
            $fechaStr = date('Y-m-d', strtotime("+$i days"));
            $fechaObj = new DateTime($fechaStr);
            
            try {
                // Usar método del dominio que ya implementa toda la lógica
                $disponibilidad = $reservaDomain->obtenerHorasDisponiblesConCapacidad(
                    $fechaObj, 
                    $reserva['usuario_id']
                );
                
                // Si hay horas disponibles, agregar al array
                if (!empty($disponibilidad['horas'])) {
                    $horariosDisponibles[$fechaStr] = [
                        'fecha_formateada' => date('d/m/Y', strtotime($fechaStr)),
                        'dia_semana' => ucfirst($disponibilidad['dia_semana']),
                        'dia_completo' => formatearDiaCompleto($fechaStr),
                        'horas' => $disponibilidad['horas']
                    ];
                }
                
            } catch (\DomainException $e) {
                // Día no disponible, continuar con el siguiente
                continue;
            }
        }
        
    } catch (\Exception $e) {
        error_log('Error obteniendo horarios disponibles: ' . $e->getMessage());
    }
}

// Función para formatear fecha en español
function formatearFechaEspanol($fecha) {
    $dias = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes', 
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];
    
    $meses = [
        'January' => 'Enero',
        'February' => 'Febrero',
        'March' => 'Marzo',
        'April' => 'Abril',
        'May' => 'Mayo',
        'June' => 'Junio',
        'July' => 'Julio',
        'August' => 'Agosto',
        'September' => 'Septiembre',
        'October' => 'Octubre',
        'November' => 'Noviembre',
        'December' => 'Diciembre'
    ];
    
    $fechaFormateada = date('l, j \d\e F \d\e Y', strtotime($fecha));
    
    foreach ($dias as $ingles => $espanol) {
        $fechaFormateada = str_replace($ingles, $espanol, $fechaFormateada);
    }
    
    foreach ($meses as $ingles => $espanol) {
        $fechaFormateada = str_replace($ingles, $espanol, $fechaFormateada);
    }
    
    return $fechaFormateada;
}

function formatearDiaCompleto($fecha) {
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    
    $timestamp = strtotime($fecha);
    $dia_semana = $dias[date('w', $timestamp)];
    $dia = date('j', $timestamp);
    $mes = $meses[date('n', $timestamp)];
    
    return $dia_semana . ' ' . $dia . ' ' . $mes;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Reserva - <?php echo htmlspecialchars($reserva['empresa_nombre'] ?? $reserva['formulario_nombre'] ?? 'ReservaBot'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <?php if ($reserva): ?>
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($reserva['color_primario'] ?? '#667eea'); ?>;
            --secondary-color: <?php echo htmlspecialchars($reserva['color_secundario'] ?? '#764ba2'); ?>;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, 
                color-mix(in srgb, var(--primary-color) 90%, black) 0%, 
                color-mix(in srgb, var(--secondary-color) 90%, black) 100%);
        }
        
        .text-primary {
            color: var(--primary-color);
        }
        
        .border-primary {
            border-color: var(--primary-color);
        }
        
        .focus\:ring-primary:focus {
            --tw-ring-color: var(--primary-color);
        }
        
        .focus\:border-primary:focus {
            border-color: var(--primary-color);
        }
        
        .bg-primary-50 {
            background-color: color-mix(in srgb, var(--primary-color) 10%, white);
        }
        
        .border-primary-200 {
            border-color: color-mix(in srgb, var(--primary-color) 30%, white);
        }
        
        .text-primary-800 {
            color: color-mix(in srgb, var(--primary-color) 80%, black);
        }
        
        .text-primary-900 {
            color: color-mix(in srgb, var(--primary-color) 90%, black);
        }
    </style>
    <?php else: ?>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        }
    </style>
    <?php endif; ?>
    
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Calendario de fechas */
        .date-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 8px;
        }
        
        .date-option {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }
        
        .date-option:hover {
            border-color: var(--primary-color);
            background-color: color-mix(in srgb, var(--primary-color) 5%, white);
        }
        
        .date-option.selected {
            border-color: var(--primary-color);
            background-color: var(--primary-color);
            color: white;
        }
        
        .date-option .day {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-bottom: 2px;
        }
        
        .date-option .date {
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        /* Horarios */
        .time-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px;
        }
        
        .time-option {
            padding: 8px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
            font-size: 0.875rem;
        }
        
        .time-option:hover {
            border-color: var(--primary-color);
            background-color: color-mix(in srgb, var(--primary-color) 5%, white);
        }
        
        .time-option.selected {
            border-color: var(--primary-color);
            background-color: var(--primary-color);
            color: white;
        }
        
        .hidden-section {
            display: none;
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
                <p class="text-sm text-gray-500 mb-6">El enlace puede haber expirado o no ser válido.</p>
                <a href="/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="ri-home-line mr-2"></i>
                    Volver al inicio
                </a>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Página principal -->
    <div class="min-h-screen bg-gray-50">
        
        <!-- Header -->
        <div class="gradient-bg">
            <div class="max-w-4xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
                <div class="text-center text-white fade-in">
                    <!-- Logo y nombre de empresa -->
                    <div class="mb-3">
                        <?php if (!empty($reserva['empresa_logo'])): ?>
                            <div class="flex justify-center mb-2">
                                <img src="<?php echo htmlspecialchars($reserva['empresa_logo']); ?>" 
                                    alt="<?php echo htmlspecialchars($reserva['empresa_nombre'] ?? $reserva['formulario_nombre']); ?>"
                                    class="h-12 w-auto object-contain bg-white bg-opacity-20 rounded-lg p-2">
                            </div>
                        <?php endif; ?>
                        
                        <h1 class="text-2xl font-bold sm:text-3xl"><?php echo htmlspecialchars($reserva['empresa_nombre'] ?? $reserva['formulario_nombre'] ?? 'Gestión de Reserva'); ?></h1>

                        <!-- Información de contacto -->
                        <?php if (!empty($reserva['direccion']) || !empty($reserva['telefono_contacto'])): ?>
                            <div class="flex flex-wrap justify-center items-center gap-4 mt-2 text-sm text-white text-opacity-80">
                                <?php if (!empty($reserva['direccion'])): ?>
                                    <div class="flex items-center">
                                        <i class="ri-map-pin-line mr-2"></i>
                                        <?php echo htmlspecialchars($reserva['direccion']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($reserva['telefono_contacto'])): ?>
                                    <div class="flex items-center">
                                        <i class="ri-phone-line mr-2"></i>
                                        <a href="tel:<?php echo htmlspecialchars($reserva['telefono_contacto']); ?>" 
                                        class="hover:text-white transition-colors">
                                            <?php echo htmlspecialchars($reserva['telefono_contacto']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido -->
        <div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
            
            <?php if (!empty($mensaje)): ?>
                <div class="mb-6 p-4 rounded-md <?php echo $tipoMensaje === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                    <div class="flex">
                        <i class="<?php echo $tipoMensaje === 'success' ? 'ri-check-line text-green-400' : 'ri-error-warning-line text-red-400'; ?> mt-0.5 mr-3"></i>
                        <p class="text-sm <?php echo $tipoMensaje === 'success' ? 'text-green-800' : 'text-red-800'; ?>"><?php echo htmlspecialchars($mensaje); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Información de la reserva -->
                <div class="bg-white rounded-lg shadow-sm fade-in">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Detalles de tu reserva</h3>
                    </div>
                    <div class="px-6 py-5">
                        
                        <!-- Estado -->
                        <div class="mb-6">
                            <div class="flex items-center">
                                <?php
                                $estadoConfig = [
                                    'confirmada' => ['color' => 'green', 'icon' => 'ri-check-line', 'text' => 'Confirmada'],
                                    'pendiente' => ['color' => 'yellow', 'icon' => 'ri-time-line', 'text' => 'Pendiente'],
                                    'cancelada' => ['color' => 'red', 'icon' => 'ri-close-line', 'text' => 'Cancelada']
                                ];
                                $config = $estadoConfig[$reserva['estado']] ?? $estadoConfig['pendiente'];
                                ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-<?php echo $config['color']; ?>-100 text-<?php echo $config['color']; ?>-800">
                                    <i class="<?php echo $config['icon']; ?> mr-1"></i>
                                    <?php echo $config['text']; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Información -->
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Nombre</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($reserva['nombre']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Teléfono</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($reserva['telefono']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($reserva['email']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Fecha</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <i class="ri-calendar-line mr-2 text-primary"></i>
                                    <?php echo formatearFechaEspanol($reserva['fecha']); ?>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Hora</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <i class="ri-time-line mr-2 text-primary"></i>
                                    <?php echo substr($reserva['hora'], 0, 5); ?>
                                </dd>
                            </div>
                            <?php if (!empty($reserva['mensaje'])): ?>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Comentarios</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($reserva['mensaje'])); ?></dd>
                            </div>
                            <?php endif; ?>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Fecha de creación</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo date('d/m/Y \a \l\a\s H:i', strtotime($reserva['created_at'])); ?></dd>
                            </div>
                        </dl>

                        <!-- Información de modificación -->
                        <?php if ($puedeModificar): ?>
                            <div class="mt-6 p-4 bg-primary-50 rounded-lg border border-primary-200">
                                <h4 class="text-sm font-medium text-primary-900 mb-2">📝 Modificación disponible</h4>
                                <p class="text-sm text-primary-800">
                                    Puedes modificar o cancelar tu reserva hasta 24 horas antes de la cita.
                                    <br><strong>Tiempo restante:</strong> <?php echo $tiempoRestante; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">🔒 Modificación no disponible</h4>
                                <p class="text-sm text-gray-600">
                                    <?php if ($reserva['estado'] === 'cancelada'): ?>
                                        Esta reserva ha sido cancelada.
                                    <?php else: ?>
                                        El plazo para modificar esta reserva ha expirado (24h antes de la cita).
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Panel de acciones -->
                <div class="space-y-6">
                    
                    <?php if ($puedeModificar): ?>
                        <!-- Modificar fecha/hora -->
                        <div class="bg-white rounded-lg shadow-sm fade-in">
                            <div class="px-6 py-5 border-b border-gray-200">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Cambiar fecha y hora</h3>
                            </div>
                            <div class="px-6 py-5">
                                <form method="POST" id="modificarForm" class="space-y-6">
                                    <input type="hidden" name="action" value="modificar">
                                    <input type="hidden" name="nueva_fecha" id="selectedDate">
                                    <input type="hidden" name="nueva_hora" id="selectedTime">
                                    
                                    <!-- Selección de fecha -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-3">Nueva fecha</label>
                                        <div class="date-grid" id="dateGrid">
                                            <?php foreach ($horariosDisponibles as $fecha => $info): ?>
                                                <div class="date-option" data-fecha="<?php echo $fecha; ?>" data-horas="<?php echo htmlspecialchars(json_encode($info['horas'])); ?>">
                                                    <div class="day"><?php echo $info['dia_semana']; ?></div>
                                                    <div class="date"><?php echo date('j M', strtotime($fecha)); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Selección de hora -->
                                    <div id="timeSection" class="hidden-section">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">Nueva hora</label>
                                        <div class="time-grid" id="timeGrid">
                                            <!-- Las horas se cargarán dinámicamente -->
                                        </div>
                                    </div>
                                    
                                    <!-- Resumen de selección -->
                                    <div id="summarySection" class="hidden-section">
                                        <div class="bg-gray-50 rounded-lg p-4 border">
                                            <h4 class="text-sm font-medium text-gray-900 mb-2">Resumen del cambio:</h4>
                                            <div class="flex items-center justify-between text-sm">
                                                <div>
                                                    <span class="text-gray-600">Fecha actual:</span>
                                                    <span class="ml-2 font-medium"><?php echo formatearFechaEspanol($reserva['fecha']); ?> a las <?php echo substr($reserva['hora'], 0, 5); ?></span>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between text-sm mt-2">
                                                <div>
                                                    <span class="text-gray-600">Nueva fecha:</span>
                                                    <span class="ml-2 font-medium text-primary" id="newDateSummary">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white btn-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all disabled:opacity-50 disabled:cursor-not-allowed" id="btnModificar" disabled>
                                        <i class="ri-calendar-check-line mr-2"></i>
                                        Confirmar cambio
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Cancelar reserva -->
                        <div class="bg-white rounded-lg shadow-sm fade-in">
                            <div class="px-6 py-5 border-b border-gray-200">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Cancelar reserva</h3>
                            </div>
                            <div class="px-6 py-5">
                                <p class="text-sm text-gray-600 mb-4">
                                    Si necesitas cancelar tu reserva, puedes hacerlo desde aquí. Esta acción no se puede deshacer.
                                </p>
                                <button type="button" onclick="confirmarCancelacion()" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all">
                                    <i class="ri-close-line mr-2"></i>
                                    Cancelar mi reserva
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Información de contacto -->
                    <div class="bg-white rounded-lg shadow-sm fade-in">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">¿Necesitas ayuda?</h3>
                        </div>
                        <div class="px-6 py-5">
                            <div class="space-y-3 text-sm">
                                <?php if (!empty($reserva['telefono_contacto'])): ?>
                                <div class="flex items-center">
                                    <i class="ri-phone-line text-green-500 mr-3"></i>
                                    <div>
                                        <span class="text-gray-700">Para cambios urgentes: </span>
                                        <a href="tel:<?php echo htmlspecialchars($reserva['telefono_contacto']); ?>" 
                                           class="text-primary font-medium hover:underline">
                                            <?php echo htmlspecialchars($reserva['telefono_contacto']); ?>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($reserva['email_contacto'])): ?>
                                <div class="flex items-center">
                                    <i class="ri-mail-line text-blue-500 mr-3"></i>
                                    <div>
                                        <span class="text-gray-700">Email: </span>
                                        <a href="mailto:<?php echo htmlspecialchars($reserva['email_contacto']); ?>" 
                                           class="text-primary font-medium hover:underline">
                                            <?php echo htmlspecialchars($reserva['email_contacto']); ?>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center">
                                    <i class="ri-time-line text-orange-500 mr-3"></i>
                                    <span class="text-gray-700">Recuerda llegar 5 minutos antes</span>
                                </div>
                                
                                <?php if (!empty($reserva['direccion'])): ?>
                                <div class="flex items-start">
                                    <i class="ri-map-pin-line text-red-500 mr-3 mt-0.5"></i>
                                    <div>
                                        <span class="text-gray-700">Dirección: </span>
                                        <span class="text-gray-900"><?php echo htmlspecialchars($reserva['direccion']); ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de cancelación -->
    <div id="modalCancelacion" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="ri-error-warning-line text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Cancelar reserva</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    ¿Estás seguro de que deseas cancelar tu reserva? Esta acción no se puede deshacer.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form method="post" class="inline">
                        <input type="hidden" name="action" value="cancelar">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Sí, cancelar
                        </button>
                    </form>
                    <button type="button" onclick="cerrarModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        No, mantener reserva
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<script>
// Datos de horarios disponibles
const horariosDisponibles = <?php echo json_encode($horariosDisponibles); ?>;

document.addEventListener('DOMContentLoaded', function() {
    let selectedDate = null;
    let selectedTime = null;
    
    const dateOptions = document.querySelectorAll('.date-option');
    const timeSection = document.getElementById('timeSection');
    const timeGrid = document.getElementById('timeGrid');
    const summarySection = document.getElementById('summarySection');
    const btnModificar = document.getElementById('btnModificar');
    const selectedDateInput = document.getElementById('selectedDate');
    const selectedTimeInput = document.getElementById('selectedTime');
    const newDateSummary = document.getElementById('newDateSummary');
    const modificarForm = document.getElementById('modificarForm');

    // Manejar selección de fecha
    dateOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remover selección anterior
            document.querySelectorAll('.date-option.selected').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Seleccionar nueva fecha
            this.classList.add('selected');
            selectedDate = this.dataset.fecha;
            selectedDateInput.value = selectedDate;
            
            // Mostrar horarios disponibles
            const horas = JSON.parse(this.dataset.horas);
            timeGrid.innerHTML = '';
            
            horas.forEach(hora => {
                const timeOption = document.createElement('div');
                timeOption.className = 'time-option';
                timeOption.dataset.hora = hora;
                timeOption.textContent = hora;
                timeOption.addEventListener('click', selectTime);
                timeGrid.appendChild(timeOption);
            });
            
            // Mostrar sección de horarios
            timeSection.classList.remove('hidden-section');
            
            // Resetear selección de hora
            selectedTime = null;
            selectedTimeInput.value = '';
            summarySection.classList.add('hidden-section');
            btnModificar.disabled = true;
        });
    });
    
    // Función para seleccionar hora
    function selectTime() {
        // Remover selección anterior
        document.querySelectorAll('.time-option.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Seleccionar nueva hora
        this.classList.add('selected');
        selectedTime = this.dataset.hora;
        selectedTimeInput.value = selectedTime;
        
        // Actualizar resumen
        if (selectedDate && selectedTime) {
            const fechaInfo = horariosDisponibles[selectedDate];
            newDateSummary.textContent = `${fechaInfo.dia_completo} a las ${selectedTime}`;
            summarySection.classList.remove('hidden-section');
            btnModificar.disabled = false;
        }
    }
    
    // Manejar envío del formulario
    if (modificarForm) {
        modificarForm.addEventListener('submit', function(e) {
            if (!selectedDate || !selectedTime) {
                e.preventDefault();
                alert('Por favor selecciona fecha y hora');
                return false;
            }
            
            // Mostrar loading state
            const originalText = btnModificar.innerHTML;
            btnModificar.innerHTML = '<i class="ri-loader-line animate-spin mr-2"></i>Modificando...';
            btnModificar.disabled = true;
            
            return true;
        });
    }
});

function confirmarCancelacion() {
    document.getElementById('modalCancelacion').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modalCancelacion').classList.add('hidden');
}
</script>

</body>
</html>