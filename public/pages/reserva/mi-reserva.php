<?php
// pages/reserva/mi-reserva.php

// Página para que los clientes gestionen sus reservas mediante token

// Configurar idioma español para fechas
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'spanish');

$token = $_GET['token'] ?? '';
$reserva = null;
$configuracionNegocio = [];
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
        
        // Obtener configuración completa del negocio usando ConfiguracionDomain
        try {
            $configuracionDomain = getContainer()->getConfiguracionDomain();
            
            // Obtener datos del formulario si existe (para fallback de colores)
            $formularioData = null;
            if ($reserva['formulario_id']) {
                try {
                    $formularioDomain = getContainer()->getFormularioDomain();
                    $formularioEntity = $formularioDomain->obtenerFormularioPorId(
                        $reserva['formulario_id'], 
                        $reserva['usuario_id']
                    );
                    
                    if ($formularioEntity) {
                        $formularioData = $formularioEntity->toArray();
                    }
                } catch (\Exception $e) {
                    error_log('Error obteniendo formulario público: ' . $e->getMessage());
                }
            }
            
            // ✅ Obtener configuración del negocio desde ConfiguracionDomain
            $configuracionNegocio = $configuracionDomain->obtenerConfiguracionNegocioPublica(
                $reserva['usuario_id'], 
                $formularioData
            );
            
        } catch (\Exception $e) {
            error_log('Error obteniendo configuración del negocio: ' . $e->getMessage());
            
            // Fallback a valores por defecto
            $configuracionNegocio = [
                'nombre' => $formularioData['empresa_nombre'] ?? $formularioData['nombre'] ?? 'ReservaBot',
                'logo' => $formularioData['empresa_logo'] ?? null,
                'telefono' => $formularioData['telefono_contacto'] ?? null,
                'email' => $formularioData['email_contacto'] ?? null,
                'direccion' => $formularioData['direccion'] ?? null,
                'web' => null,
                'color_primario' => $formularioData['color_primario'] ?? '#667eea',
                'color_secundario' => $formularioData['color_secundario'] ?? '#764ba2'
            ];
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
    <title>Mi Reserva - <?php echo htmlspecialchars($configuracionNegocio['nombre']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        
        /* === ANIMACIONES SUAVES === */
        .fade-in {
            animation: fadeIn 0.4s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* === CALENDARIO Y HORARIOS === */
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
        
        /* === RESPONSIVO === */
        @media (max-width: 640px) {
            .truncate {
                max-width: 200px;
            }
        }
        
        /* === ACCESIBILIDAD === */
        @media (prefers-reduced-motion: reduce) {
            .fade-in,
            .btn-primary:hover {
                animation: none;
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
        
        <!-- Header con diseño consistente -->
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
                            <p class="text-xs text-white/80 hidden sm:block">Mi Reserva</p>
                        </div>
                    </div>
                    
                    <!-- Información de contacto DESKTOP (lado derecho) -->
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
                    
                    <!-- MÓVIL: Teléfono + botón info -->
                    <div class="sm:hidden flex items-center space-x-2">
                        <?php if (!empty($configuracionNegocio['telefono'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($configuracionNegocio['telefono']); ?>" 
                            class="flex items-center space-x-1.5 bg-white/10 rounded-full px-2.5 py-1.5 hover:bg-white/20 transition-colors">
                                <i class="ri-phone-line text-sm"></i>
                                <span class="font-medium text-xs"><?php echo htmlspecialchars($configuracionNegocio['telefono']); ?></span>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($configuracionNegocio['direccion']) || !empty($configuracionNegocio['email'])): ?>
                            <button type="button" onclick="toggleMobileInfo()" class="bg-white/10 rounded-full p-2 hover:bg-white/20 transition-colors">
                                <i class="ri-information-line text-sm"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Información móvil desplegable -->
                <div id="mobileInfo" class="hidden sm:hidden mt-3 pt-3 border-t border-white/20">
                    <div class="space-y-2 text-sm">
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
                                class="hover:text-white">
                                    <?php echo htmlspecialchars($configuracionNegocio['email']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resto del contenido (sin cambios en estructura) -->

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

// Función para toggle del menú móvil
function toggleMobileInfo() {
    const mobileInfo = document.getElementById('mobileInfo');
    mobileInfo.classList.toggle('hidden');
}

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
    
    // Manejar envío del formulario de modificación
    if (modificarForm) {
        modificarForm.addEventListener('submit', function(e) {
            if (!selectedDate || !selectedTime) {
                e.preventDefault();
                alert('Por favor selecciona fecha y hora');
                return false;
            }
            
            // Mostrar loading state
            const originalText = btnModificar.innerHTML;
            btnModificar.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Modificando...';
            btnModificar.disabled = true;
            
            return true;
        });
    }
});

// Funciones para modal de cancelación
function confirmarCancelacion() {
    document.getElementById('modalCancelacion').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modalCancelacion').classList.add('hidden');
}

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modalCancelacion');
        if (modal && !modal.classList.contains('hidden')) {
            cerrarModal();
        }
    }
});

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(e) {
    const modal = document.getElementById('modalCancelacion');
    if (modal && !modal.classList.contains('hidden')) {
        if (e.target === modal) {
            cerrarModal();
        }
    }
});
</script>

</body>
</html>