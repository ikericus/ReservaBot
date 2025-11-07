<?php
// api/formulario-actualizar.php

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

// Verificar autenticación
$currentUser = getAuthenticatedUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autenticado'
    ]);
    exit;
}

try {
    // Obtener datos del request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new InvalidArgumentException('Datos inválidos');
    }
    
    $id = intval($data['id'] ?? 0);
    $usuario_id = $currentUser['id'];
    
    if ($id <= 0) {
        throw new InvalidArgumentException('ID de formulario inválido');
    }
    
    $formularioDomain = getContainer()->getFormularioDomain();
    
    // Si viene 'accion' es un toggle de estado
    if (isset($data['accion'])) {
        $accion = $data['accion'];
        
        if ($accion === 'activar') {
            $formulario = $formularioDomain->activarFormulario($id, $usuario_id);
            $mensaje = 'Enlace activado correctamente';
        } elseif ($accion === 'desactivar') {
            $formulario = $formularioDomain->desactivarFormulario($id, $usuario_id);
            $mensaje = 'Enlace desactivado correctamente';
        } else {
            throw new InvalidArgumentException('Acción inválida');
        }
    } else {
        // Es una actualización normal de campos
        $datosActualizacion = [];
        
        if (isset($data['nombre'])) {
            $datosActualizacion['nombre'] = trim($data['nombre']);
        }
        
        if (array_key_exists('descripcion', $data)) {
            $datosActualizacion['descripcion'] = !empty($data['descripcion']) ? trim($data['descripcion']) : null;
        }
        
        if (isset($data['confirmacion_automatica'])) {
            $datosActualizacion['confirmacion_automatica'] = $data['confirmacion_automatica'];
        }
        
        $formulario = $formularioDomain->actualizarFormulario($id, $datosActualizacion, $usuario_id);
        $mensaje = 'Enlace actualizado correctamente';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'data' => $formulario->toArray()
    ]);
    
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en actualizar formulario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar el formulario'
    ]);
}