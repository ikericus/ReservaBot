<?php
// Página para que los clientes gestionen sus reservas mediante token
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$token = $_GET['token'] ?? '';
$reserva = null;
$error = '';
$mensaje = '';
$tipoMensaje = '';

// Validar token
if (empty($token)) {
    $error = 'Token no válido';
} else {
    try {
        $stmt = getPDO()->prepare("
            SELECT r.*, fp.nombre as formulario_nombre, fp.empresa_nombre, fp.empresa_logo, 
                   fp.color_primario, fp.color_secundario, fp.direccion, fp.telefono_contacto,
                   fp.email_contacto, fp.mensaje_bienvenida
            FROM reservas r
            LEFT JOIN formularios_publicos fp ON r.formulario_id = fp.id
            WHERE r.access_token = ? 
            AND r.token_expires > NOW() 
            AND r.estado != 'cancelada'
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $reserva = $stmt->fetch();
        
        if (!$reserva) {
            $error = 'Enlace no válido o expirado';
        }
    } catch (Exception $e) {
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

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reserva) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'cancelar' && $puedeModificar) {
        try {
            $stmt = getPDO()->prepare("UPDATE reservas SET estado = 'cancelada' WHERE id = ?");
            $result = $stmt->execute([$reserva['id']]);
            
            if ($result) {
                $mensaje = 'Tu reserva ha sido cancelada correctamente';
                $tipoMensaje = 'success';
                $reserva['estado'] = 'cancelada';
                $puedeModificar = false;
            } else {
                $mensaje = 'Error al cancelar la reserva';
                $tipoMensaje = 'error';
            }
        } catch (Exception $e) {
            error_log('Error cancelando reserva: ' . $e->getMessage());
            $mensaje = 'Error al cancelar la reserva';
            $tipoMensaje = 'error';
        }
    }
}

// Obtener horarios disponibles para modificación
$horariosDisponibles = [];
if ($reserva && $puedeModificar) {
    try {
        // Obtener configuración de horarios del usuario
        $stmt = getPDO()->prepare("SELECT clave, valor FROM configuraciones WHERE usuario_id = ? AND (clave LIKE 'horario_%' OR clave = 'intervalo_reservas')");
        $stmt->execute([$reserva['usuario_id']]);
        $configuraciones = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $intervalo = intval($configuraciones['intervalo_reservas'] ?? 30);
        
        // Generar próximos 7 días disponibles
        for ($i = 0; $i < 7; $i++) {
            $fecha = date('Y-m-d', strtotime("+$i days"));
            $diaSemana = date('w', strtotime($fecha));
            $diasMap = [1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 5 => 'vie', 6 => 'sab', 0 => 'dom'];
            $diaConfig = $diasMap[$diaSemana];
            
            $horarioConfig = $configuraciones["horario_{$diaConfig}"] ?? 'false|[]';
            $parts = explode('|', $horarioConfig, 2);
            $activo = $parts[0] === 'true';
            
            if ($activo && isset($parts[1])) {
                // Obtener horas ocupadas para esta fecha
                $stmt = getPDO()->prepare("
                    SELECT TIME_FORMAT(hora, '%H:%i') as hora_ocupada 
                    FROM reservas 
                    WHERE usuario_id = ? AND fecha = ? AND estado IN ('pendiente', 'confirmada') AND id != ?
                ");
                $stmt->execute([$reserva['usuario_id'], $fecha, $reserva['id']]);
                $horasOcupadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Generar horas disponibles (simplificado)
                $ventanas = json_decode($parts[1], true) ?: [['inicio' => '09:00', 'fin' => '18:00']];
                $horas = [];
                
                foreach ($ventanas as $ventana) {
                    $inicio = strtotime($ventana['inicio']);
                    $fin = strtotime($ventana['fin']);
                    
                    for ($h = $inicio; $h < $fin; $h += ($intervalo * 60)) {
                        $hora = date('H:i', $h);
                        if (!in_array($hora, $horasOcupadas)) {
                            $horas[] = $hora;
                        }
                    }
                }
                
                if (!empty($horas)) {
                    $horariosDisponibles[$fecha] = [
                        'fecha_formateada' => date('d/m/Y', strtotime($fecha)),
                        'dia_semana' => ucfirst($diaConfig),
                        'horas' => $horas
                    ];
                }
            }
        }
    } catch (Exception $e) {
        error_log('Error obteniendo horarios disponibles: ' . $e->getMessage());
    }
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
            <div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
                <div class="text-center text-white fade-in">
                    <!-- Logo y nombre de empresa -->
                    <div class="mb-6">
                        <?php if (!empty($reserva['empresa_logo'])): ?>
                            <div class="flex justify-center mb-4">
                                <img src="<?php echo htmlspecialchars($reserva['empresa_logo']); ?>" 
                                     alt="<?php echo htmlspecialchars($reserva['empresa_nombre'] ?? $reserva['formulario_nombre']); ?>"
                                     class="h-16 w-auto object-contain bg-white bg-opacity-20 rounded-lg p-2">
                            </div>
                        <?php endif; ?>
                        
                        <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="ri-calendar-check-line text-3xl"></i>
                        </div>
                        
                        <h1 class="text-3xl font-bold sm:text-4xl">Mi Reserva</h1>
                        <p class="mt-2 text-lg text-blue-100">
                            <?php echo htmlspecialchars($reserva['empresa_nombre'] ?? $reserva['formulario_nombre'] ?? 'Gestión de Reserva'); ?>
                        </p>
                        
                        <!-- Información de contacto -->
                        <?php if (!empty($reserva['direccion']) || !empty($reserva['telefono_contacto'])): ?>
                            <div class="flex flex-wrap justify-center items-center gap-4 mt-4 text-sm text-white text-opacity-80">
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
                                    <?php echo date('l, d \d\e F \d\e Y', strtotime($reserva['fecha'])); ?>
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
// Configuración de horarios disponibles
const horariosDisponibles = <?php echo json_encode($horariosDisponibles); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const nuevaFechaSelect = document.getElementById('nuevaFecha');
    const nuevaHoraSelect = document.getElementById('nuevaHora');
    const modificarForm = document.getElementById('modificarForm');

    // Manejar cambio de fecha
    if (nuevaFechaSelect) {
        nuevaFechaSelect.addEventListener('change', function() {
            const fecha = this.value;
            nuevaHoraSelect.innerHTML = '<option value="">Selecciona una hora</option>';
            nuevaHoraSelect.disabled = !fecha;

            if (fecha && horariosDisponibles[fecha]) {
                const horas = horariosDisponibles[fecha].horas;
                horas.forEach(hora => {
                    const option = document.createElement('option');
                    option.value = hora;
                    option.textContent = hora;
                    nuevaHoraSelect.appendChild(option);
                });
                nuevaHoraSelect.disabled = false;
            }
        });
    }

    // Manejar envío del formulario de modificación
    if (modificarForm) {
        modificarForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const btnModificar = document.getElementById('btnModificar');
            
            // Validaciones
            if (!formData.get('nueva_fecha') || !formData.get('nueva_hora')) {
                alert('Por favor selecciona fecha y hora');
                return;
            }
            
            // Loading state
            const originalText = btnModificar.innerHTML;
            btnModificar.innerHTML = '<i class="ri-loader-line animate-spin mr-2"></i>Modificando...';
            btnModificar.disabled = true;
            
            // Enviar petición
            fetch('api/actualizar-reserva-publica', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Reserva modificada exitosamente');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo modificar la reserva'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al modificar la reserva');
            })
            .finally(() => {
                btnModificar.innerHTML = originalText;
                btnModificar.disabled = false;
            });
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