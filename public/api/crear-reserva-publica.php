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

// Validaciones de formato
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
    
    // Validar formulario si se proporcionó
    $formulario = null;
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
    
    // Crear la reserva usando el método específico para reservas públicas
    // Este método incluye todas las validaciones necesarias
    $reserva = $reservaDomain->crearReservaPublica(
        $nombre,
        $telefono,
        $email,
        $fecha,
        $hora,
        $usuarioId,
        $mensaje,
        $formularioId
    );
    
    // Si es confirmación automática, confirmar la reserva
    if ($confirmacionAutomatica) {
        $reserva = $reservaDomain->confirmarReserva($reserva->getId(), $usuarioId);
    }
    
    // Generar token de acceso para gestión pública
    $accessToken = bin2hex(random_bytes(32));
    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Actualizar reserva con token y email
    // TODO: Mover esto al dominio creando un método actualizarDatosPublicos()
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        UPDATE reservas 
        SET email = ?, access_token = ?, token_expires = ?, formulario_id = ?
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([
        $email, 
        $accessToken, 
        $tokenExpiry,
        $formularioId,
        $reserva->getId(), 
        $usuarioId
    ]);
    
    // Registrar origen de la reserva
    if ($formularioId) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO origen_reservas (
                    reserva_id, formulario_id, origen, ip_address, user_agent, created_at
                ) VALUES (?, ?, 'formulario_publico', ?, ?, NOW())
            ");
            $stmt->execute([
                $reserva->getId(), 
                $formularioId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar origen de reserva: " . $e->getMessage());
            // No es crítico, continuar
        }
    }
    
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
    
    // TODO: Enviar email de confirmación
    // $emailEnviado = $reservaDomain->enviarConfirmacion($reserva->getId());
    $emailEnviado = true; // Simulado por ahora
    
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
        'token' => $accessToken,
        'email_enviado' => $emailEnviado,
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