<?php
// Página pública para reservas de clientes
require_once 'includes/db-config.php';
require_once 'includes/functions.php';

// Obtener el formulario por slug
$slug = $_GET['f'] ?? '';
$formulario = null;
$error = '';

if (empty($slug)) {
    $error = 'Enlace no válido';
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM formularios_publicos WHERE slug = ? AND activo = 1");
        $stmt->execute([$slug]);
        $formulario = $stmt->fetch();
        
        if (!$formulario) {
            $error = 'Formulario no encontrado o no disponible';
        }
    } catch (Exception $e) {
        $error = 'Error al cargar el formulario';
    }
}

// Obtener configuración de horarios
$horarios = [];
$diasSemana = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];

if ($formulario) {
    try {
        $stmt = $pdo->prepare("SELECT clave, valor FROM configuraciones WHERE clave LIKE 'horario_%'");
        $stmt->execute();
        $configHorarios = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        foreach ($diasSemana as $dia) {
            $horarioConfig = $configHorarios["horario_{$dia}"] ?? 'true|09:00|18:00';
            list($activo, $horaInicio, $horaFin) = explode('|', $horarioConfig);
            
            $horarios[$dia] = [
                'activo' => $activo === 'true',
                'inicio' => $horaInicio,
                'fin' => $horaFin
            ];
        }
        
        // Obtener intervalo de reservas
        $stmt = $pdo->prepare("SELECT valor FROM configuraciones WHERE clave = 'intervalo_reservas'");
        $stmt->execute();
        $intervaloReservas = intval($stmt->fetchColumn() ?: 30);
    } catch (Exception $e) {
        // Si hay error, usar horarios por defecto
        foreach ($diasSemana as $dia) {
            $horarios[$dia] = [
                'activo' => in_array($dia, ['lun', 'mar', 'mie', 'jue', 'vie']),
                'inicio' => '09:00',
                'fin' => '18:00'
            ];
        }
        $intervaloReservas = 30;
    }
}

// Función para obtener el día de la semana en formato español
function getDiaSemana($fecha) {
    $diasMap = [
        1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 
        5 => 'vie', 6 => 'sab', 0 => 'dom'
    ];
    $diaSemana = date('w', strtotime($fecha));
    return $diasMap[$diaSemana];
}

// Función para generar horarios disponibles para una fecha
function getHorariosDisponibles($fecha, $horarios, $intervalo, $pdo) {
    $diaSemana = getDiaSemana($fecha);
    
    // Verificar si el día está activo
    if (!$horarios[$diaSemana]['activo']) {
        return [];
    }
    
    $horaInicio = $horarios[$diaSemana]['inicio'];
    $horaFin = $horarios[$diaSemana]['fin'];
    
    // Generar todas las horas posibles en intervalos configurados
    $horas = [];
    $current = strtotime($horaInicio);
    $end = strtotime($horaFin);
    
    while ($current < $end) {
        $horas[] = date('H:i', $current);
        $current += $intervalo * 60; // Convertir minutos a segundos
    }
    
    // Obtener horas ya reservadas para esta fecha
    try {
        $stmt = $pdo->prepare("SELECT TIME_FORMAT(hora, '%H:%i') as hora_reservada 
                               FROM reservas 
                               WHERE fecha = ? AND estado IN ('pendiente', 'confirmada')");
        $stmt->execute([$fecha]);
        $horasReservadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Filtrar horas ya reservadas
        $horas = array_diff($horas, $horasReservadas);
        
        // Si es hoy, filtrar horas que ya pasaron
        if ($fecha === date('Y-m-d')) {
            $horaActual = date('H:i');
            $horas = array_filter($horas, function($hora) use ($horaActual) {
                return $hora > $horaActual;
            });
        }
    } catch (Exception $e) {
        // Si hay error, devolver todas las horas
    }
    
    return array_values($horas);
}

// Verificar si es una respuesta exitosa vía GET
$reservaExitosaGet = isset($_GET['success']) && $_GET['success'] == '1';
if ($reservaExitosaGet && $formulario) {
    $reservaExitosa = true;
    $nombre = $_GET['nombre'] ?? '';
    $telefono = $_GET['telefono'] ?? '';
    $fecha = $_GET['fecha'] ?? '';
    $hora = $_GET['hora'] ?? '';
    $comentarios = $_GET['mensaje'] ?? '';
    $confirmacionAuto = isset($_GET['auto']) && $_GET['auto'] == '1';
    
    // Usar la configuración real del formulario
    $mensaje = $confirmacionAuto
        ? 'Tu reserva ha sido confirmada automáticamente. ¡Te esperamos!' 
        : 'Tu solicitud de reserva ha sido recibida. Te contactaremos pronto para confirmarla.';
}

// Procesar envío de reserva vía POST (mantener como fallback)
$mensaje = '';
$reservaExitosa = false;
$reservaId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $formulario) {
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $comentarios = trim($_POST['mensaje'] ?? '');
    
    // Validaciones básicas
    if (empty($nombre) || empty($telefono) || empty($fecha) || empty($hora)) {
        $mensaje = 'Por favor completa todos los campos obligatorios';
    } elseif ($fecha < date('Y-m-d')) {
        $mensaje = 'La fecha no puede ser anterior a hoy';
    } else {
        // Validar que el día esté activo
        $diaSemana = getDiaSemana($fecha);
        if (!$horarios[$diaSemana]['activo']) {
            $mensaje = 'El día seleccionado no está disponible para reservas';
        } else {
            // Validar que la hora esté dentro del horario de atención
            $horaInicio = $horarios[$diaSemana]['inicio'];
            $horaFin = $horarios[$diaSemana]['fin'];
            
            if ($hora < $horaInicio || $hora >= $horaFin) {
                $mensaje = 'La hora seleccionada está fuera del horario de atención';
            } else {
                // Verificar que la hora no esté ya reservada
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas 
                                           WHERE fecha = ? AND TIME_FORMAT(hora, '%H:%i') = ? 
                                           AND estado IN ('pendiente', 'confirmada')");
                    $stmt->execute([$fecha, $hora]);
                    $yaReservada = $stmt->fetchColumn();
                    
                    if ($yaReservada > 0) {
                        $mensaje = 'La hora seleccionada ya no está disponible. Por favor elige otra hora.';
                    } else {
                        // Crear la reserva
                        $estado = $formulario['confirmacion_automatica'] ? 'confirmada' : 'pendiente';
                        
                        $stmt = $pdo->prepare("INSERT INTO reservas (nombre, telefono, fecha, hora, mensaje, estado, created_at) 
                                               VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        
                        $stmt->execute([$nombre, $telefono, $fecha, $hora . ':00', $comentarios, $estado]);
                        $reservaId = $pdo->lastInsertId();
                        
                        $reservaExitosa = true;
                        $mensaje = $formulario['confirmacion_automatica'] 
                            ? 'Tu reserva ha sido confirmada. ¡Te esperamos!' 
                            : 'Tu solicitud de reserva ha sido recibida. Te contactaremos pronto para confirmarla.';
                    }
                } catch (Exception $e) {
                    $mensaje = 'Error al procesar la reserva. Por favor intenta nuevamente.';
                }
            }
        }
    }
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
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .success-animation {
            animation: successPulse 1.5s ease-in-out;
        }
        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
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
                        <?php echo $formulario['confirmacion_automatica'] ? '¡Reserva Confirmada!' : '¡Solicitud Recibida!'; ?>
                    </h1>
                    <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($mensaje); ?></p>
                    
                    <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                        <h3 class="font-medium text-gray-900 mb-2">Detalles de tu reserva:</h3>
                        <div class="space-y-1 text-sm text-gray-600">
                            <p><span class="font-medium">Nombre:</span> <?php echo htmlspecialchars($nombre); ?></p>
                            <p><span class="font-medium">Teléfono:</span> <?php echo htmlspecialchars($telefono); ?></p>
                            <p><span class="font-medium">Fecha:</span> <?php echo $fecha ? date('d/m/Y', strtotime($fecha)) : ''; ?></p>
                            <p><span class="font-medium">Hora:</span> <?php echo htmlspecialchars($hora); ?></p>
                            <?php if (!empty($comentarios)): ?>
                                <p><span class="font-medium">Comentarios:</span> <?php echo htmlspecialchars($comentarios); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!$confirmacionAuto): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-6">
                            <p class="text-sm text-blue-800">
                                <i class="ri-information-line mr-1"></i>
                                Te contactaremos pronto para confirmar tu reserva.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-6">
                            <p class="text-sm text-green-800">
                                <i class="ri-check-line mr-1"></i>
                                Tu reserva está confirmada. No necesitas hacer nada más.
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <button onclick="window.location.reload()" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="ri-add-line mr-2"></i>
                        Hacer otra reserva
                    </button>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Formulario de reserva -->
        <div class="min-h-screen bg-gray-50">
            <!-- Header -->
            <div class="gradient-bg">
                <div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
                    <div class="text-center text-white fade-in">
                        <h1 class="text-3xl font-bold sm:text-4xl"><?php echo htmlspecialchars($formulario['nombre']); ?></h1>
                        <?php if (!empty($formulario['descripcion'])): ?>
                            <p class="mt-2 text-lg text-blue-100"><?php echo htmlspecialchars($formulario['descripcion']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Formulario -->
            <div class="max-w-2xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow-lg fade-in">
                    <div class="px-6 py-8">
                        <?php if (!empty($mensaje) && !$reservaExitosa): ?>
                            <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                                <div class="flex">
                                    <i class="ri-error-warning-line text-red-400 mt-0.5 mr-3"></i>
                                    <p class="text-sm text-red-800"><?php echo htmlspecialchars($mensaje); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="reservaForm" class="space-y-6">
                            <!-- Información personal -->
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
                                            value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>
                                
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
                                            value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                                        >
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
                                            value="<?php echo htmlspecialchars($_POST['fecha'] ?? ''); ?>"
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
                                    ><?php echo htmlspecialchars($_POST['mensaje'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Botón de envío -->
                            <div class="pt-4">
                                <button
                                    type="submit"
                                    id="submitBtn"
                                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <i class="ri-calendar-check-line mr-2"></i>
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
                            <i class="ri-information-line text-blue-500 mt-0.5 mr-3 flex-shrink-0"></i>
                            <p>
                                <?php echo $formulario['confirmacion_automatica'] 
                                    ? 'Tu reserva será confirmada automáticamente.' 
                                    : 'Recibirás una confirmación por teléfono en las próximas horas.'; ?>
                            </p>
                        </div>
                        <div class="flex items-start">
                            <i class="ri-phone-line text-green-500 mt-0.5 mr-3 flex-shrink-0"></i>
                            <p>Te contactaremos al número proporcionado para cualquier comunicación necesaria.</p>
                        </div>
                        <div class="flex items-start">
                            <i class="ri-time-line text-orange-500 mt-0.5 mr-3 flex-shrink-0"></i>
                            <p>Por favor, llega con 5 minutos de antelación a tu cita.</p>
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
            slug: <?php echo json_encode($slug); ?>
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            const fechaInput = document.getElementById('fecha');
            const horaSelect = document.getElementById('hora');
            const horarioInfo = document.getElementById('horarioInfo');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            
            if (fechaInput && horaSelect) {
                fechaInput.addEventListener('change', cargarHorasDisponibles);
                
                // Si hay una fecha preseleccionada, cargar las horas
                if (fechaInput.value) {
                    cargarHorasDisponibles();
                }
            }
            
            function cargarHorasDisponibles() {
                const fecha = fechaInput.value;
                if (!fecha) return;
                
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
                
                // Hacer petición para obtener horas disponibles
                fetch('api/horas-disponibles.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ fecha: fecha })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        horaSelect.innerHTML = '<option value="">Selecciona una hora</option>';
                        
                        if (data.horas.length === 0) {
                            horaSelect.innerHTML += '<option value="">No hay horas disponibles</option>';
                            horarioInfo.textContent = 'No hay horarios disponibles para esta fecha.';
                        } else {
                            data.horas.forEach(hora => {
                                horaSelect.innerHTML += `<option value="${hora}">${hora}</option>`;
                            });
                            horarioInfo.textContent = `Horario de atención: ${data.horario_inicio} - ${data.horario_fin}`;
                        }
                    } else {
                        horaSelect.innerHTML = '<option value="">Error al cargar horas</option>';
                        horarioInfo.textContent = data.message || 'Error al cargar las horas disponibles.';
                    }
                    horaSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    horaSelect.innerHTML = '<option value="">Error al cargar horas</option>';
                    horarioInfo.textContent = 'Error de conexión. Por favor, intenta nuevamente.';
                    horaSelect.disabled = false;
                });
            }
            
            function getDiaSemana(fecha) {
                const diasMap = {
                    1: 'lun', 2: 'mar', 3: 'mie', 4: 'jue', 
                    5: 'vie', 6: 'sab', 0: 'dom'
                };
                const diaSemana = new Date(fecha).getDay();
                return diasMap[diaSemana];
            }
            
            // Manejar envío del formulario
            const form = document.getElementById('reservaForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Evitar envío tradicional
                    
                    // Cambiar el texto del botón para mostrar que se está procesando
                    submitBtn.disabled = true;
                    submitText.textContent = 'Procesando reserva...';
                    
                    // Recopilar datos del formulario
                    const formData = new FormData(form);
                    const data = {};
                    formData.forEach((value, key) => {
                        data[key] = value;
                    });
                    
                    // Enviar vía AJAX
                    fetch('api/crear-reserva.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            // Redirigir a la misma página con parámetros de éxito
                            const urlParams = new URLSearchParams(window.location.search);
                            const baseUrl = window.location.pathname + '?f=' + urlParams.get('f');
                            
                            const successUrl = baseUrl + 
                                              '&success=1' +
                                              '&nombre=' + encodeURIComponent(data.nombre) +
                                              '&telefono=' + encodeURIComponent(data.telefono) +
                                              '&fecha=' + encodeURIComponent(data.fecha) +
                                              '&hora=' + encodeURIComponent(data.hora) +
                                              '&mensaje=' + encodeURIComponent(data.mensaje || '') +
                                              '&auto=' + (<?php echo json_encode($formulario['confirmacion_automatica'] ?? 0); ?> ? '1' : '0');
                            
                            window.location.href = successUrl;
                        } else {
                            // Mostrar error
                            alert('Error: ' + (result.message || 'Error desconocido'));
                            submitBtn.disabled = false;
                            submitText.textContent = 'Reservar Cita';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error de conexión. Por favor, intenta nuevamente.');
                        submitBtn.disabled = false;
                        submitText.textContent = 'Reservar Cita';
                    });
                });
            }
            
            // Validación en tiempo real
            const inputs = form.querySelectorAll('input[required], select[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.classList.add('border-red-300');
                        this.classList.remove('border-gray-300');
                    } else {
                        this.classList.remove('border-red-300');
                        this.classList.add('border-gray-300');
                    }
                });
            });
        });
    </script>
</body>
</html>