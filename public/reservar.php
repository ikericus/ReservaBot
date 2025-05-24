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
        $stmt = $pdo->prepare("SELECT clave, valor FROM configuraciones WHERE clave LIKE 'horario_%' OR clave = 'intervalo_reservas' OR clave = 'modo_aceptacion'");
        $stmt->execute();
        $configuraciones = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        foreach ($diasSemana as $dia) {
            $horarioConfig = $configuraciones["horario_{$dia}"] ?? 'true|09:00|18:00';
            list($activo, $horaInicio, $horaFin) = explode('|', $horarioConfig);
            
            $horarios[$dia] = [
                'activo' => $activo === 'true',
                'inicio' => $horaInicio,
                'fin' => $horaFin
            ];
        }
        
        // Obtener intervalo de reservas y modo de aceptación
        $intervaloReservas = intval($configuraciones['intervalo_reservas'] ?? 30);
        $modoAceptacion = $configuraciones['modo_aceptacion'] ?? 'manual';
        
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
        'telefono' => $_GET['telefono'] ?? '',
        'fecha' => $_GET['fecha'] ?? '',
        'hora' => $_GET['hora'] ?? '',
        'mensaje' => $_GET['mensaje'] ?? '',
        'confirmacion_automatica' => isset($_GET['auto']) && $_GET['auto'] == '1'
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
        .btn-loading {
            position: relative;
            color: transparent;
        }
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
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
                            <p><span class="font-medium">Teléfono:</span> <?php echo htmlspecialchars($datosReserva['telefono']); ?></p>
                            <p><span class="font-medium">Fecha:</span> <?php echo $datosReserva['fecha'] ? date('d/m/Y', strtotime($datosReserva['fecha'])) : ''; ?></p>
                            <p><span class="font-medium">Hora:</span> <?php echo htmlspecialchars($datosReserva['hora']); ?></p>
                            <?php if (!empty($datosReserva['mensaje'])): ?>
                                <p><span class="font-medium">Comentarios:</span> <?php echo htmlspecialchars($datosReserva['mensaje']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!$datosReserva['confirmacion_automatica']): ?>
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
                    
                    <button onclick="resetForm()" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
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
                        <!-- Contenedor para mensajes de error -->
                        <div id="errorContainer" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4 hidden">
                            <div class="flex">
                                <i class="ri-error-warning-line text-red-400 mt-0.5 mr-3"></i>
                                <p class="text-sm text-red-800" id="errorMessage"></p>
                            </div>
                        </div>
                        
                        <form id="reservaForm" class="space-y-6">
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
                                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
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
            slug: <?php echo json_encode($slug); ?>,
            confirmacionAutomatica: <?php echo json_encode($formulario['confirmacion_automatica'] ?? 0); ?>
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
                    
                    // Validaciones del lado cliente
                    if (!data.nombre || !data.telefono || !data.fecha || !data.hora) {
                        showError('Por favor completa todos los campos obligatorios');
                        setLoadingState(false);
                        return;
                    }
                    
                    if (data.fecha < new Date().toISOString().split('T')[0]) {
                        showError('La fecha no puede ser anterior a hoy');
                        setLoadingState(false);
                        return;
                    }
                    
                    // Enviar vía AJAX a la nueva API
                    fetch('api/crear-reserva-publica.php', {
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
                                throw new Error('API no encontrada. Verifica que el archivo api/crear-reserva-publica.php existe.');
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
                                telefono: result.datos.telefono,
                                fecha: result.datos.fecha,
                                hora: result.datos.hora,
                                mensaje: result.datos.mensaje || '',
                                auto: result.confirmacion_automatica ? '1' : '0'
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
            
            // Validación en tiempo real
            const inputs = form.querySelectorAll('input[required], select[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                        this.classList.remove('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
                    } else {
                        this.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                        this.classList.add('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
                    }
                });
                
                input.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        this.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                        this.classList.add('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
                    }
                });
            });
        });
        
        // Función para resetear el formulario (llamada desde el botón de "Hacer otra reserva")
        function resetForm() {
            const urlParams = new URLSearchParams(window.location.search);
            const baseUrl = window.location.pathname + '?f=' + urlParams.get('f');
            window.location.href = baseUrl;
        }
    </script>
</body>
</html>