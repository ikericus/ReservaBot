<?php
// api/crear-reserva-publica.php

// Cabeceras para JSON
header('Content-Type: application/json');

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener los datos enviados
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verificar que los datos se decodificaron correctamente
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit;
}

// Verificar que los datos requeridos estén presentes
$required = ['nombre', 'telefono', 'email', 'fecha', 'hora', 'usuario_id'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Campo requerido faltante: $field"]);
        exit;
    }
}

// Validaciones básicas de formato
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email no válido']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['fecha'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
    exit;
}

if (!preg_match('/^\d{2}:\d{2}$/', $data['hora'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato de hora inválido']);
    exit;
}

if ($data['fecha'] < date('Y-m-d')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La fecha no puede ser anterior a hoy']);
    exit;
}

// Limpiar y preparar datos
$nombre = trim($data['nombre']);
$telefono = trim($data['telefono']);
$email = trim($data['email']);
$fechaStr = $data['fecha'];
$hora = $data['hora']; // HH:MM
$mensaje = trim($data['mensaje'] ?? '');
$usuarioId = intval($data['usuario_id']);
$formularioId = isset($data['formulario_id']) ? intval($data['formulario_id']) : null;

try {
    // Obtener container con dependencias
    $container = getContainer();
    $reservaDomain = $container->getReservaDomain();
    $formularioDomain = $container->getFormularioDomain();
    
    // Convertir fecha string a DateTime
    $fecha = new DateTime($fechaStr);
    
    // Validar formulario si se proporcionó y determinar confirmación automática
    $confirmacionAutomatica = false;
    
    if ($formularioId) {
        try {
            $formulario = $formularioDomain->obtenerFormularioPorId($formularioId, $usuarioId);
            
            if (!$formulario) {
                error_log("Formulario no encontrado: ID $formularioId para usuario $usuarioId");
            } else if (!$formulario->isActivo()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El formulario no está activo']);
                exit;
            }
            
            // Determinar confirmación automática del formulario
            $confirmacionAutomatica = $formulario->isConfirmacionAutomatica();
            
        } catch (Exception $e) {
            error_log("Error al validar formulario: " . $e->getMessage());
            // Continuar sin formulario si hay error
        }
    }
    
    // Crear la reserva usando el dominio
    // El dominio se encarga de:
    // - Validar disponibilidad
    // - Crear el token de acceso
    // - Guardar todo en la BD en una sola operación
    // - Registrar el origen
    // - Enviar el email de confirmación
    $reserva = $reservaDomain->crearReservaPublica(
        $nombre,
        $telefono,
        $email,
        $fecha,
        $hora,
        $usuarioId,
        $mensaje,
        $formularioId,
        $confirmacionAutomatica
    );
    
    // Obtener información de capacidad para la hora seleccionada
    try {
        $horasDisponiblesInfo = $reservaDomain->obtenerHorasDisponiblesConCapacidad($fecha, $usuarioId);
        $capacidadInfo = $horasDisponiblesInfo['capacidad_info'][$hora] ?? [
            'total' => 1,
            'ocupadas' => 1,
            'libres' => 0
        ];
    } catch (Exception $e) {
        error_log("Error al obtener información de capacidad: " . $e->getMessage());
        $capacidadInfo = [
            'total' => 1,
            'ocupadas' => 1,
            'libres' => 0
        ];
    }
    
    // Preparar respuesta exitosa
    $reservaArray = $reserva->toArray();
    
    http_response_code(201); // Created
    echo json_encode([
        'success' => true,
        'id' => $reserva->getId(),
        'message' => $confirmacionAutomatica 
            ? 'Reserva confirmada automáticamente' 
            : 'Reserva creada. Te contactaremos pronto para confirmarla',
        'confirmacion_automatica' => $confirmacionAutomatica,
        'token' => $reserva->getAccessToken(),
        'email_enviado' => true, // El dominio ya envió el email
        'capacidad_info' => $capacidadInfo,
        'datos' => [
            'nombre' => $reservaArray['nombre'],
            'email' => $email,
            'telefono' => $reservaArray['telefono'],
            'fecha' => $reservaArray['fecha'],
            'hora' => substr($reservaArray['hora'], 0, 5),
            'mensaje' => $reservaArray['mensaje'],
            'estado' => $reservaArray['estado']
        ]
    ]);
    
} catch (\DomainException $e) {
    // Errores de lógica de negocio (validaciones del dominio)
    error_log('Error de dominio al crear reserva pública: ' . $e->getMessage());
    http_response_code(409); // Conflict
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
    
} catch (\InvalidArgumentException $e) {
    // Errores de validación de entidad
    error_log('Error de validación al crear reserva pública: ' . $e->getMessage());
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
    
} catch (\PDOException $e) {
    // Errores de base de datos
    error_log('Error PDO al crear reserva pública: ' . $e->getMessage());
    http_response_code(500);
    
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Ya existe una reserva con esos datos']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
    }
    
} catch (\Exception $e) {
    // Errores generales
    error_log('Error general al crear reserva pública: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}