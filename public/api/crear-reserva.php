<?php

// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Establecer el estado según el rol del usuario
$estado = isAdmin() ? 'confirmada' : 'pendiente';

// Iniciar sesión para manejar mensajes de error
session_start();

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método no permitido';
    header('Location: /nueva-reserva');
    exit;
}

// Obtener los datos del formulario
$data = $_POST;

// Función para redirigir con error
function redirectWithError($message, $data = []) {
    $_SESSION['error'] = $message;
    $_SESSION['form_data'] = $data;
    $fecha = isset($data['fecha']) ? $data['fecha'] : date('Y-m-d');
    header("Location: /nueva-reserva?date={$fecha}");
    exit;
}

// Función para normalizar teléfono al formato WhatsApp
function normalizePhoneForWhatsApp($phone) {
    if (!$phone) return '';
    
    // Remover todos los caracteres que no sean números o el signo +
    $normalized = preg_replace('/[^\d+]/', '', $phone);
    
    // Si empieza con +, mantenerlo
    if (strpos($normalized, '+') === 0) {
        return $normalized;
    }
    
    // Si empieza con 34, agregar +
    if (strpos($normalized, '34') === 0 && strlen($normalized) >= 11) {
        return '+' . $normalized;
    }
    
    // Si empieza con 6, 7, 8 o 9 (números españoles), agregar +34
    if (preg_match('/^[6789]/', $normalized) && strlen($normalized) === 9) {
        return '+34' . $normalized;
    }
    
    // Si no tiene prefijo y tiene más de 9 dígitos, asumir que tiene código de país
    if (strlen($normalized) > 9) {
        return '+' . $normalized;
    }
    
    // Por defecto, asumir España si es un número de 9 dígitos
    if (strlen($normalized) === 9) {
        return '+34' . $normalized;
    }
    
    return $normalized;
}

// Verificar que los datos requeridos estén presentes
if (!isset($data['nombre']) || !isset($data['telefono']) || !isset($data['fecha']) || !isset($data['hora'])) {
    redirectWithError('Faltan datos requeridos', $data);
}

// Validar los datos
if (empty(trim($data['nombre'])) || empty(trim($data['telefono'])) || empty($data['fecha']) || empty($data['hora'])) {
    redirectWithError('Todos los campos obligatorios deben estar completos', $data);
}

// Validar el formato de la fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['fecha'])) {
    redirectWithError('Formato de fecha inválido', $data);
}

// Validar el formato de la hora
if (!preg_match('/^\d{2}:\d{2}$/', $data['hora'])) {
    redirectWithError('Formato de hora inválido', $data);
}

// Validar que la fecha no sea anterior a hoy
if ($data['fecha'] < date('Y-m-d')) {
    redirectWithError('La fecha no puede ser anterior a hoy', $data);
}

try {
    // Verificar reservas existentes usando CAST para evitar problemas de collation
    $stmt = getPDO()->prepare('
        SELECT COUNT(*) FROM reservas 
        WHERE CAST(fecha AS CHAR) = CAST(? AS CHAR) 
        AND TIME_FORMAT(hora, "%H:%i") = CAST(? AS CHAR) 
        AND estado IN ("pendiente", "confirmada")
    ');
    $stmt->execute([$data['fecha'], $data['hora']]);
    $existeReserva = $stmt->fetchColumn();
    
    if ($existeReserva > 0) {
        redirectWithError('Ya existe una reserva para esa fecha y hora', $data);
    }
    
    // Limpiar y preparar datos
    $nombre = trim($data['nombre']);
    $telefono = trim($data['telefono']);
    $mensaje = isset($data['mensaje']) ? trim($data['mensaje']) : '';
    $estado = trim($data['estado']);
    
    // Normalizar teléfono para WhatsApp
    $whatsappId = '';
    if (isset($data['whatsapp_id']) && !empty(trim($data['whatsapp_id']))) {
        $whatsappId = normalizePhoneForWhatsApp(trim($data['whatsapp_id']));
    } else {
        // Si no se proporciona whatsapp_id explícito, usar el teléfono normalizado
        $whatsappId = normalizePhoneForWhatsApp($telefono);
    }
    
    // Preparar la consulta de inserción
    $campos = ['nombre', 'telefono', 'fecha', 'hora', 'mensaje', 'estado', 'usuario_id'];
    $valores = ['?', '?', '?', '?', '?', '?', '?'];
    
    // Siempre incluir whatsapp_id ahora
    $campos[] = 'whatsapp_id';
    $valores[] = '?';
    
    $sql = 'INSERT INTO reservas (' . implode(', ', $campos) . ') VALUES (' . implode(', ', $valores) . ')';
    $stmt = getPDO()->prepare($sql);
        
    // Convertir la hora al formato de MySQL (HH:MM:SS)
    $hora = $data['hora'] . ':00';
    
    // Obtener el ID del usuario actual (NULL si no está autenticado)
    $usuarioId = getCurrentUserId();

    // Preparar parámetros para la ejecución
    $params = [
        $nombre,
        $telefono,
        $data['fecha'],
        $hora,
        $mensaje,
        $estado,
        $usuarioId,
        $whatsappId  // WhatsApp ID normalizado
    ];
    
    // Ejecutar la consulta
    $result = $stmt->execute($params);
    
    if ($result) {
        $id = getPDO()->lastInsertId();
        
        // Log de la creación con información de normalización
        error_log("Reserva creada - ID: {$id}, Teléfono: {$telefono}, WhatsApp normalizado: {$whatsappId}");
        
        // Limpiar datos del formulario y mensaje de error
        unset($_SESSION['form_data']);
        unset($_SESSION['error']);
        
        // Redirigir al detalle de la reserva
        header("Location: /reserva-detail?id={$id}");
        exit;
    } else {
        redirectWithError('Error al crear la reserva en la base de datos', $data);
    }
    
} catch (\PDOException $e) {
    error_log('Error PDO al crear reserva: ' . $e->getMessage());
    redirectWithError('Error en la base de datos: ' . $e->getMessage(), $data);
} catch (\Exception $e) {
    error_log('Error general al crear reserva: ' . $e->getMessage());
    redirectWithError('Error interno del servidor: ' . $e->getMessage(), $data);
}
?>