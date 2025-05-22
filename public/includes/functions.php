<?php

/**
 * Obtiene la URL base del sitio web
 *
 * @return string URL base
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
    
    // Asegurarse de que la ruta llega a la raíz pública
    $publicPath = '/';
    if (strpos($path, '/admin/') !== false) {
        $publicPath = str_replace('/admin/', '/', $path);
    }
    
    return $protocol . $host . $publicPath;
}


// Función para obtener todas las reservas
function getReservas() {
    global $pdo;
    $stmt = $pdo->query('SELECT * FROM reservas ORDER BY fecha, hora');
    return $stmt->fetchAll();
}

// Función para obtener reservas por fecha
function getReservasByFecha($fecha) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM reservas WHERE fecha = ? ORDER BY hora');
    $stmt->execute([$fecha]);
    return $stmt->fetchAll();
}

// Función para obtener una reserva por ID
function getReservaById($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM reservas WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Función para crear una nueva reserva
function createReserva($data) {
    global $pdo;
    $sql = 'INSERT INTO reservas (nombre, telefono, fecha, hora, mensaje, estado) 
            VALUES (?, ?, ?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['nombre'],
        $data['telefono'],
        $data['fecha'],
        $data['hora'],
        $data['mensaje'],
        $data['estado'] ?? 'pendiente'
    ]);
    return $pdo->lastInsertId();
}

// Función para actualizar una reserva
function updateReserva($id, $data) {
    global $pdo;
    $sql = 'UPDATE reservas SET 
            nombre = ?, 
            telefono = ?, 
            fecha = ?, 
            hora = ?, 
            mensaje = ?, 
            estado = ? 
            WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $data['nombre'],
        $data['telefono'],
        $data['fecha'],
        $data['hora'],
        $data['mensaje'],
        $data['estado'],
        $id
    ]);
    return $result;
}

// Función para eliminar una reserva
function deleteReserva($id) {
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM reservas WHERE id = ?');
    return $stmt->execute([$id]);
}

// Función para formatear la fecha en formato legible
function formatearFecha($fecha) {
    $timestamp = strtotime($fecha);
    $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $dia = date('j', $timestamp);
    $mes = $meses[date('n', $timestamp) - 1];
    $anio = date('Y', $timestamp);
    
    return "$dia de $mes de $anio";
}
?>