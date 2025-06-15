<?php
// API para buscar clientes por teléfono
header('Content-Type: application/json');

// Incluir configuración y funciones
require_once dirname(__DIR__) . '/includes/db-config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$user = getAuthenticatedUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Obtener los datos enviados
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verificar que los datos se decodificaron correctamente
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit;
}

// Verificar que el teléfono esté presente
if (!isset($data['telefono']) || empty(trim($data['telefono']))) {
    echo json_encode(['success' => false, 'message' => 'Teléfono requerido']);
    exit;
}

$telefonoBusqueda = trim($data['telefono']);

// Validar longitud mínima
if (strlen($telefonoBusqueda) < 3) {
    echo json_encode(['success' => false, 'message' => 'Teléfono demasiado corto']);
    exit;
}

try {
    $pdo = getPDO();
    
    // Obtener el ID del usuario actual
    $usuarioId = getCurrentUserId();
    
    // Función para normalizar teléfonos para comparación
    function normalizePhoneForSearch($phone) {
        // Remover todos los caracteres que no sean números
        $normalized = preg_replace('/[^\d]/', '', $phone);
        
        // Si empieza con 34 y tiene 11 dígitos, remover el 34
        if (substr($normalized, 0, 2) === '34' && strlen($normalized) === 11) {
            $normalized = substr($normalized, 2);
        }
        
        return $normalized;
    }
    
    // Normalizar el teléfono de búsqueda
    $telefonoNormalizado = normalizePhoneForSearch($telefonoBusqueda);
    
    // Buscar clientes que coincidan con el teléfono
    // Buscamos tanto en telefono como en whatsapp_id, y normalizamos para comparar
    $sql = "
        SELECT DISTINCT
            nombre,
            telefono,
            whatsapp_id,
            COUNT(r.id) as total_reservas,
            MAX(r.fecha) as last_reserva_fecha,
            MAX(r.created_at) as last_created
        FROM reservas r
        WHERE r.usuario_id = ?
        AND (
            REPLACE(REPLACE(REPLACE(REPLACE(r.telefono, '+', ''), ' ', ''), '-', ''), '(', '') LIKE ?
            OR REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(r.whatsapp_id, ''), '+', ''), ' ', ''), '-', ''), '(', '') LIKE ?
            OR REPLACE(REPLACE(REPLACE(REPLACE(r.telefono, '+34', ''), ' ', ''), '-', ''), '(', '') LIKE ?
            OR REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(r.whatsapp_id, ''), '+34', ''), ' ', ''), '-', ''), '(', '') LIKE ?
        )
        GROUP BY nombre, telefono, whatsapp_id
        ORDER BY last_created DESC, total_reservas DESC
        LIMIT 10
    ";
    
    // Preparar patrones de búsqueda
    $patron = '%' . $telefonoNormalizado . '%';
    $patronOriginal = '%' . $telefonoBusqueda . '%';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $usuarioId,
        $patron,           // telefono normalizado
        $patron,           // whatsapp_id normalizado  
        $patron,           // telefono sin +34
        $patron            // whatsapp_id sin +34
    ]);
    
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar resultados
    $clientes = [];
    $telefonosVistos = [];
    
    foreach ($resultados as $resultado) {
        // Evitar duplicados basados en teléfono normalizado
        $telefonoKey = normalizePhoneForSearch($resultado['telefono']);
        
        if (in_array($telefonoKey, $telefonosVistos)) {
            continue;
        }
        
        $telefonosVistos[] = $telefonoKey;
        
        // Formatear fecha de última reserva
        $lastReserva = null;
        if ($resultado['last_reserva_fecha']) {
            $fecha = DateTime::createFromFormat('Y-m-d', $resultado['last_reserva_fecha']);
            if ($fecha) {
                $lastReserva = $fecha->format('d/m/Y');
            }
        }
        
        $clientes[] = [
            'nombre' => $resultado['nombre'],
            'telefono' => $resultado['telefono'],
            'whatsapp_id' => $resultado['whatsapp_id'],
            'total_reservas' => intval($resultado['total_reservas']),
            'last_reserva' => $lastReserva
        ];
    }
    
    // Si no se encontraron resultados, buscar también por nombre
    if (empty($clientes) && strlen($telefonoBusqueda) >= 3) {
        $sqlNombre = "
            SELECT DISTINCT
                nombre,
                telefono,
                whatsapp_id,
                COUNT(r.id) as total_reservas,
                MAX(r.fecha) as last_reserva_fecha,
                MAX(r.created_at) as last_created
            FROM reservas r
            WHERE r.usuario_id = ?
            AND LOWER(r.nombre) LIKE LOWER(?)
            GROUP BY nombre, telefono, whatsapp_id
            ORDER BY last_created DESC, total_reservas DESC
            LIMIT 5
        ";
        
        $stmt = $pdo->prepare($sqlNombre);
        $stmt->execute([$usuarioId, '%' . $telefonoBusqueda . '%']);
        
        $resultadosNombre = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($resultadosNombre as $resultado) {
            $lastReserva = null;
            if ($resultado['last_reserva_fecha']) {
                $fecha = DateTime::createFromFormat('Y-m-d', $resultado['last_reserva_fecha']);
                if ($fecha) {
                    $lastReserva = $fecha->format('d/m/Y');
                }
            }
            
            $clientes[] = [
                'nombre' => $resultado['nombre'],
                'telefono' => $resultado['telefono'],
                'whatsapp_id' => $resultado['whatsapp_id'],
                'total_reservas' => intval($resultado['total_reservas']),
                'last_reserva' => $lastReserva
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'clientes' => $clientes,
        'total' => count($clientes),
        'busqueda' => $telefonoBusqueda
    ]);
    
} catch (PDOException $e) {
    error_log('Error en búsqueda de clientes: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error en la base de datos'
    ]);
} catch (Exception $e) {
    error_log('Error general en búsqueda de clientes: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>