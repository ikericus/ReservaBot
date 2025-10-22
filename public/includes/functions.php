<?php

/**
 * Sistema de mensajes flash
 */

function setFlashError(string $mensaje): void {
    $_SESSION['flash_error'] = $mensaje;
}

function setFlashSuccess(string $mensaje): void {
    $_SESSION['flash_success'] = $mensaje;
}

function setFlashInfo(string $mensaje): void {
    $_SESSION['flash_info'] = $mensaje;
}

function getFlashMessages(): array {
    $messages = [
        'error' => $_SESSION['flash_error'] ?? null,
        'success' => $_SESSION['flash_success'] ?? null,
        'info' => $_SESSION['flash_info'] ?? null,
    ];
    
    // Limpiar después de leer
    unset($_SESSION['flash_error'], $_SESSION['flash_success'], $_SESSION['flash_info']);
    
    return array_filter($messages);
}


function logMessage($msg) {
    $path = PROJECT_ROOT . '/../../debug.log';
    error_log(date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, 3, $path);
}

// Función mejorada para hacer requests HTTP con debug detallado
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    // error_log("=== INICIO REQUEST ===");
    // error_log("URL: " . $url);
    // error_log("Método: " . $method);
    // error_log("Headers: " . json_encode($headers));
    // error_log("Data: " . ($data ? json_encode($data) : 'null'));
    
    $ch = curl_init();
    
    // Configuración básica de cURL
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'ReservaBot-WhatsApp/1.0',
        CURLOPT_VERBOSE => true
    ]);
    
    // Headers
    $curlHeaders = ['Content-Type: application/json'];
    if (!empty($headers)) {
        $curlHeaders = array_merge($curlHeaders, $headers);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
    
    // Método y datos
    switch (strtoupper($method)) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
    }
    
    // Ejecutar request
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    // Información de la respuesta
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    
    curl_close($ch);
    
    // error_log("=== RESPUESTA ===");
    // error_log("HTTP Code: " . $httpCode);
    // error_log("Duración: " . $duration . "ms");
    // error_log("Error cURL: " . ($error ?: 'ninguno'));
    // error_log("Info cURL: " . json_encode([
    //     'url' => $info['url'],
    //     'content_type' => $info['content_type'],
    //     'total_time' => $info['total_time'],
    //     'namelookup_time' => $info['namelookup_time'],
    //     'connect_time' => $info['connect_time']
    // ]));
    // error_log("Response body: " . substr($response, 0, 1000) . (strlen($response) > 1000 ? '...[truncated]' : ''));
    // error_log("=== FIN REQUEST ===");
    
    // Verificar errores
    if ($error) {
        throw new Exception("Error cURL: " . $error);
    }
    
    if ($response === false) {
        throw new Exception("cURL devolvió false - no se pudo obtener respuesta del servidor");
    }
    
    if ($httpCode >= 400) {
        throw new Exception("HTTP Error {$httpCode}: " . substr($response, 0, 200));
    }
    
    // Decodificar JSON
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error decodificando JSON: " . json_last_error_msg() . ". Response: " . substr($response, 0, 200));
    }
    
    return $decoded;
}


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

/**
 * Parsea la configuración de horarios con soporte para múltiples ventanas
 *
 * @param string $horarioConfig Configuración en formato "true|[{...}]" o legacy "true|09:00|18:00"
 * @return array Array con 'activo' y 'ventanas'
 */
function parseHorarioConfig($horarioConfig) {
    $parts = explode('|', $horarioConfig, 2);
    $activo = $parts[0] === 'true';
    $ventanas = [];
    
    if ($activo && isset($parts[1])) {
        // Intentar decodificar como JSON (nuevo formato)
        $ventanasJson = json_decode($parts[1], true);
        
        if ($ventanasJson && is_array($ventanasJson)) {
            // Formato nuevo: JSON con múltiples ventanas
            $ventanas = $ventanasJson;
        } else {
            // Formato legacy: "09:00|18:00"
            $tiempos = explode('|', $parts[1]);
            if (count($tiempos) >= 2) {
                $ventanas = [
                    ['inicio' => $tiempos[0], 'fin' => $tiempos[1]]
                ];
            }
        }
    }
    
    // Si no hay ventanas válidas, usar valores por defecto
    if (empty($ventanas)) {
        $ventanas = [['inicio' => '09:00', 'fin' => '18:00']];
    }
    
    return [
        'activo' => $activo,
        'ventanas' => $ventanas
    ];
}

/**
 * Verifica si una hora específica está dentro de alguna ventana horaria
 *
 * @param string $hora Hora en formato HH:MM
 * @param array $ventanas Array de ventanas con 'inicio' y 'fin'
 * @return bool True si la hora está dentro de alguna ventana
 */
function horaEnVentanas($hora, $ventanas) {
    foreach ($ventanas as $ventana) {
        if ($hora >= $ventana['inicio'] && $hora < $ventana['fin']) {
            return true;
        }
    }
    return false;
}

/**
 * Genera todas las horas disponibles para un conjunto de ventanas horarias
 *
 * @param array $ventanas Array de ventanas con 'inicio' y 'fin'
 * @param int $intervalo Intervalo en minutos entre horas
 * @return array Array de horas en formato HH:MM
 */
function generarHorasDisponibles($ventanas, $intervalo = 30) {
    $horas = [];
    
    foreach ($ventanas as $ventana) {
        $current = strtotime($ventana['inicio']);
        $end = strtotime($ventana['fin']);
        
        while ($current < $end) {
            $hora = date('H:i', $current);
            if (!in_array($hora, $horas)) {
                $horas[] = $hora;
            }
            $current += $intervalo * 60; // Convertir minutos a segundos
        }
    }
    
    sort($horas);
    return $horas;
}

/**
 * Obtiene la información de horarios para un día específico
 *
 * @param string $dia Día de la semana (lun, mar, mie, etc.)
 * @param PDO getPDO() Conexión a la base de datos
 * @return array Array con información del horario del día
 */
// function getHorarioDia($dia) {
//     try {
//         $stmt = getPDO()->prepare("SELECT valor FROM configuraciones WHERE clave = ?");
//         $stmt->execute(["horario_{$dia}"]);
//         $horarioConfig = $stmt->fetchColumn();
        
//         if (!$horarioConfig) {
//             // Valores por defecto
//             $horarioConfig = in_array($dia, ['lun', 'mar', 'mie', 'jue', 'vie']) 
//                 ? 'true|[{"inicio":"09:00","fin":"18:00"}]' 
//                 : 'false|[]';
//         }
        
//         return parseHorarioConfig($horarioConfig);
//     } catch (\PDOException $e) {
//         error_log('Error al obtener horario del día: ' . $e->getMessage());
//         return [
//             'activo' => false,
//             'ventanas' => []
//         ];
//     }
// }

/**
 * Verifica si un día y hora específicos están disponibles para reservas
 *
 * @param string $fecha Fecha en formato Y-m-d
 * @param string $hora Hora en formato H:i
 * @param PDO getPDO() Conexión a la base de datos
 * @return bool True si está disponible
 */
// function horaDisponible($fecha, $hora) {
//     // Obtener día de la semana
//     $diasMap = [
//         1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 
//         5 => 'vie', 6 => 'sab', 0 => 'dom'
//     ];
//     $diaSemana = date('w', strtotime($fecha));
//     $dia = $diasMap[$diaSemana];
    
//     // Obtener configuración del día
//     $horarioDia = getHorarioDia($dia);
    
//     // Verificar si el día está activo
//     if (!$horarioDia['activo']) {
//         return false;
//     }
    
//     // Verificar si la hora está en alguna ventana
//     if (!horaEnVentanas($hora, $horarioDia['ventanas'])) {
//         return false;
//     }
    
//     // Verificar si ya hay una reserva para esa fecha y hora
//     try {
//         $stmt = getPDO()->prepare('SELECT COUNT(*) FROM reservas WHERE fecha = ? AND TIME_FORMAT(hora, "%H:%i") = ? AND estado IN ("pendiente", "confirmada")');
//         $stmt->execute([$fecha, $hora]);
//         $existeReserva = $stmt->fetchColumn();
        
//         return $existeReserva == 0;
//     } catch (\PDOException $e) {
//         error_log('Error al verificar disponibilidad: ' . $e->getMessage());
//         return false;
//     }
// }

/**
 * Obtiene un resumen legible de las ventanas horarias
 *
 * @param array $ventanas Array de ventanas con 'inicio' y 'fin'
 * @return string Resumen legible de las ventanas
 */
function getResumenVentanas($ventanas) {
    if (empty($ventanas)) {
        return 'Sin horarios configurados';
    }
    
    if (count($ventanas) == 1) {
        return $ventanas[0]['inicio'] . ' - ' . $ventanas[0]['fin'];
    }
    
    $resumen = [];
    foreach ($ventanas as $ventana) {
        $resumen[] = $ventana['inicio'] . ' - ' . $ventana['fin'];
    }
    
    return implode(', ', $resumen);
}

// Función para obtener todas las reservas
// function getReservas() {
//     $stmt = getPDO()->query('SELECT * FROM reservas ORDER BY fecha, hora');
//     return $stmt->fetchAll();
// }

// Función para obtener reservas por fecha
// function getReservasByFecha($fecha) {
//     $stmt = getPDO()->prepare('SELECT * FROM reservas WHERE fecha = ? ORDER BY hora');
//     $stmt->execute([$fecha]);
//     return $stmt->fetchAll();
// }

// Función para obtener una reserva por ID
// function getReservaById($id) {
//     $stmt = getPDO()->prepare('SELECT * FROM reservas WHERE id = ?');
//     $stmt->execute([$id]);
//     return $stmt->fetch();
// }

// Función para crear una nueva reserva
// function createReserva($data) {
//     $sql = 'INSERT INTO reservas (nombre, telefono, fecha, hora, mensaje, estado) 
//             VALUES (?, ?, ?, ?, ?, ?)';
//     $stmt = getPDO()->prepare($sql);
//     $stmt->execute([
//         $data['nombre'],
//         $data['telefono'],
//         $data['fecha'],
//         $data['hora'],
//         $data['mensaje'],
//         $data['estado'] ?? 'pendiente'
//     ]);
//     return getPDO()->lastInsertId();
// }

// Función para actualizar una reserva
// function updateReserva($id, $data) {
//     $sql = 'UPDATE reservas SET 
//             nombre = ?, 
//             telefono = ?, 
//             fecha = ?, 
//             hora = ?, 
//             mensaje = ?, 
//             estado = ? 
//             WHERE id = ?';
//     $stmt = getPDO()->prepare($sql);
//     $result = $stmt->execute([
//         $data['nombre'],
//         $data['telefono'],
//         $data['fecha'],
//         $data['hora'],
//         $data['mensaje'],
//         $data['estado'],
//         $id
//     ]);
//     return $result;
// }

// Función para eliminar una reserva
// function deleteReserva($id) {
//     $stmt = getPDO()->prepare('DELETE FROM reservas WHERE id = ?');
//     return $stmt->execute([$id]);
// }

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