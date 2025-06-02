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
    
    // Preparar la consulta de inserción (incluir whatsapp_id si está presente)
    $campos = ['nombre', 'telefono', 'fecha', 'hora', 'mensaje', 'estado', 'usuario_id'];
    $valores = ['?', '?', '?', '?', '?', '?', '?'];
    
    if (isset($data['whatsapp_id']) && !empty(trim($data['whatsapp_id']))) {
        $campos[] = 'whatsapp_id';
        $valores[] = '?';
    }
    
    $sql = 'INSERT INTO reservas (' . implode(', ', $campos) . ') VALUES (' . implode(', ', $valores) . ')';
    $stmt = getPDO()->prepare($sql);
    
    // Establecer el estado según el rol del usuario
    $estado = isAdmin() ? 'confirmada' : 'pendiente';
    
    // Convertir la hora al formato de MySQL (HH:MM:SS)
    $hora = $data['hora'] . ':00';
    
    // Limpiar y preparar datos
    $nombre = trim($data['nombre']);
    $telefono = trim($data['telefono']);
    $mensaje = isset($data['mensaje']) ? trim($data['mensaje']) : '';
    
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
        $usuarioId
    ];
    
    // Agregar whatsapp_id si está presente
    if (isset($data['whatsapp_id']) && !empty(trim($data['whatsapp_id']))) {
        $params[] = trim($data['whatsapp_id']);
    }
    
    // Ejecutar la consulta
    $result = $stmt->execute($params);
    
    if ($result) {
        $id = getPDO()->lastInsertId();
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
    redirectWithError('Error en la base de datos: ' . $e->getMessage(), $data);
} catch (\Exception $e) {
    redirectWithError('Error interno del servidor: ' . $e->getMessage(), $data);
}
?>