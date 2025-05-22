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

// Procesar envío de reserva
$mensaje = '';
$reservaExitosa = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $formulario) {
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $comentarios = trim($_POST['comentarios'] ?? '');
    
    // Validaciones básicas
    if (empty($nombre) || empty($telefono) || empty($fecha) || empty($hora)) {
        $mensaje = 'Por favor completa todos los campos obligatorios';
    } elseif ($fecha < date('Y-m-d')) {
        $mensaje = 'La fecha no puede ser anterior a hoy';
    } else {
        try {
            // Crear la reserva
            $estado = $formulario['confirmacion_automatica'] ? 'confirmada' : 'pendiente';
            
            $stmt = $pdo->prepare("INSERT INTO reservas (nombre, telefono, fecha, hora, mensaje, estado, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([$nombre, $telefono, $fecha, $hora . ':00', $comentarios, $estado]);
            
            $reservaExitosa = true;
            $mensaje = $formulario['confirmacion_automatica'] 
                ? 'Tu reserva ha sido confirmada. ¡Te esperamos!' 
                : 'Tu solicitud de reserva ha sido recibida. Te contactaremos pronto para confirmarla.';
        } catch (Exception $e) {
            $mensaje = 'Error al procesar la reserva. Por favor intenta nuevamente.';
        }
    }
}

// Obtener horarios disponibles (simplificado)
$horasDisponibles = [];
if ($formulario) {
    // Por ahora, horarios fijos - en el futuro se pueden obtener de configuración
    $horasDisponibles = [
        '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
        '12:00', '12:30', '16:00', '16:30', '17:00', '17:30', '18:00'
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $formulario ? htmlspecialchars($formulario['nombre']) : 'Error'; ?> - Reserva</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8">
        <div class="max-w-md mx-auto px-4">
            <?php if ($error): ?>
                <!-- Error -->
                <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="ri-error-warning-line text-red-600 text-2xl"></i>
                    </div>
                    <h1 class="text-xl font-semibold text-gray-900 mb-2">Error</h1>
                    <p class="text-gray-600"><?php echo htmlspecialchars($error); ?></p>
                </div>
                
            <?php elseif ($reservaExitosa): ?>
                <!-- Éxito -->
                <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="ri-check-line text-green-600 text-2xl"></i>
                    </div>
                    <h1 class="text-xl font-semibold text-gray-900 mb-2">¡Reserva Realizada!</h1>
                    <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($mensaje); ?></p>
                    
                    <div class="bg-gray-50 rounded-lg p-4 text-left">
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Detalles de tu reserva:</h3>
                        <div class="space-y-1 text-sm text-gray-600">
                            <div><strong>Nombre:</strong> <?php echo htmlspecialchars($_POST['nombre']); ?></div>
                            <div><strong>Teléfono:</strong> <?php echo htmlspecialchars($_POST['telefono']); ?></div>
                            <div><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($_POST['fecha'])); ?></div>
                            <div><strong>Hora:</strong> <?php echo htmlspecialchars($_POST['hora']); ?></div>
                            <?php if (!empty($_POST['comentarios'])): ?>
                                <div><strong>Comentarios:</strong> <?php echo htmlspecialchars($_POST['comentarios']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <button onclick="location.reload()" 
                            class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200">
                        Hacer otra reserva
                    </button>
                </div>
                
            <?php else: ?>
                <!-- Formulario de reserva -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <!-- Header -->
                    <div class="bg-blue-600 px-6 py-4">
                        <h1 class="text-xl font-semibold text-white">
                            <?php echo htmlspecialchars($formulario['nombre']); ?>
                        </h1>
                        <p class="text-blue-100 text-sm mt-1">
                            Completa los datos para solicitar tu reserva
                        </p>
                    </div>
                    
                    <!-- Contenido -->
                    <div class="p-6">
                        <?php if (!empty($mensaje)): ?>
                            <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-200">
                                <p class="text-sm text-red-800"><?php echo htmlspecialchars($mensaje); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" class="space-y-4">
                            <!-- Nombre -->
                            <div>
                                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nombre completo *
                                </label>
                                <input type="text" id="nombre" name="nombre" required
                                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="Tu nombre completo">
                            </div>
                            
                            <!-- Teléfono -->
                            <div>
                                <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">
                                    Teléfono *
                                </label>
                                <input type="tel" id="telefono" name="telefono" required
                                       value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="+34 600 123 456">
                            </div>
                            
                            <!-- Fecha -->
                            <div>
                                <label for="fecha" class="block text-sm font-medium text-gray-700 mb-1">
                                    Fecha *
                                </label>
                                <input type="date" id="fecha" name="fecha" required
                                       value="<?php echo htmlspecialchars($_POST['fecha'] ?? ''); ?>"
                                       min="<?php echo date('Y-m-d'); ?>"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                            
                            <!-- Hora -->
                            <div>
                                <label for="hora" class="block text-sm font-medium text-gray-700 mb-1">
                                    Hora *
                                </label>
                                <select id="hora" name="hora" required
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">Selecciona una hora</option>
                                    <?php foreach ($horasDisponibles as $hora): ?>
                                        <option value="<?php echo $hora; ?>" 
                                                <?php echo ($_POST['hora'] ?? '') === $hora ? 'selected' : ''; ?>>
                                            <?php echo $hora; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Comentarios -->
                            <div>
                                <label for="comentarios" class="block text-sm font-medium text-gray-700 mb-1">
                                    Comentarios (opcional)
                                </label>
                                <textarea id="comentarios" name="comentarios" rows="3"
                                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                          placeholder="¿Algo que debamos saber sobre tu reserva?"><?php echo htmlspecialchars($_POST['comentarios'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Información adicional -->
                            <div class="bg-blue-50 rounded-md p-3">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="ri-information-line text-blue-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-800">
                                            <?php if ($formulario['confirmacion_automatica']): ?>
                                                Tu reserva será confirmada automáticamente.
                                            <?php else: ?>
                                                Recibirás una confirmación en las próximas horas.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botón de envío -->
                            <button type="submit" 
                                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 font-medium">
                                <i class="ri-calendar-check-line mr-2"></i>
                                Solicitar Reserva
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500">
                        Powered by ReservaBot
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>