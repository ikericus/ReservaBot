<?php
/**
 * API para gestionar respuestas automáticas de WhatsApp
 */

// Incluir configuración y funciones
require_once '../db-config.php';
require_once '../functions.php';
require_once '../whatsapp-functions.php';

// Cabeceras para JSON
header('Content-Type: application/json');

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener respuesta por ID
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM autorespuestas_whatsapp WHERE id = ?");
        $stmt->execute([$id]);
        $response = $stmt->fetch();
        
        if (!$response) {
            echo json_encode(['success' => false, 'message' => 'Respuesta no encontrada']);
            exit;
        }
        
        echo json_encode(['success' => true, 'response' => $response]);
    } catch (\PDOException $e) {
        error_log('Error al obtener respuesta automática: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al obtener los datos']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear, actualizar o eliminar respuesta
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            handleCreateResponse();
            break;
            
        case 'update':
            handleUpdateResponse();
            break;
            
        case 'delete':
            handleDeleteResponse();
            break;
            
        case 'update_status':
            handleUpdateStatus();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

/**
 * Crea una nueva respuesta automática
 */
function handleCreateResponse() {
    global $pdo;
    
    try {
        // Validar datos
        $keyword = trim($_POST['keyword'] ?? '');
        $response = trim($_POST['response'] ?? '');
        $isActive = isset($_POST['isActive']) ? 1 : 0;
        $isRegex = isset($_POST['isRegex']) ? 1 : 0;
        
        if (empty($keyword) || empty($response)) {
            echo json_encode(['success' => false, 'message' => 'La palabra clave y la respuesta son obligatorias']);
            return;
        }
        
        // Validar que la expresión regular sea válida si es el caso
        if ($isRegex && @preg_match('/' . $keyword . '/', 'test') === false) {
            echo json_encode(['success' => false, 'message' => 'La expresión regular no es válida']);
            return;
        }
        
        // Insertar en la base de datos
        $stmt = $pdo->prepare("INSERT INTO autorespuestas_whatsapp (keyword, response, is_active, is_regex, created_at) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$keyword, $response, $isActive, $isRegex, time()]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Respuesta automática creada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear la respuesta automática']);
        }
    } catch (\PDOException $e) {
        error_log('Error al crear respuesta automática: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al guardar los datos']);
    }
}

/**
 * Actualiza una respuesta automática existente
 */
function handleUpdateResponse() {
    global $pdo;
    
    try {
        // Validar datos
        $id = intval($_POST['id'] ?? 0);
        $keyword = trim($_POST['keyword'] ?? '');
        $response = trim($_POST['response'] ?? '');
        $isActive = isset($_POST['isActive']) ? 1 : 0;
        $isRegex = isset($_POST['isRegex']) ? 1 : 0;
        
        if ($id <= 0 || empty($keyword) || empty($response)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos']);
            return;
        }
        
        // Validar que la expresión regular sea válida si es el caso
        if ($isRegex && @preg_match('/' . $keyword . '/', 'test') === false) {
            echo json_encode(['success' => false, 'message' => 'La expresión regular no es válida']);
            return;
        }
        
        // Actualizar en la base de datos
        $stmt = $pdo->prepare("UPDATE autorespuestas_whatsapp SET keyword = ?, response = ?, is_active = ?, is_regex = ?, updated_at = ? WHERE id = ?");
        $result = $stmt->execute([$keyword, $response, $isActive, $isRegex, time(), $id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Respuesta automática actualizada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la respuesta automática']);
        }
    } catch (\PDOException $e) {
        error_log('Error al actualizar respuesta automática: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al actualizar los datos']);
    }
}

/**
 * Elimina una respuesta automática
 */
function handleDeleteResponse() {
    global $pdo;
    
    try {
        // Validar ID
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID no válido']);
            return;
        }
        
        // Eliminar de la base de datos
        $stmt = $pdo->prepare("DELETE FROM autorespuestas_whatsapp WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Respuesta automática eliminada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la respuesta automática']);
        }
    } catch (\PDOException $e) {
        error_log('Error al eliminar respuesta automática: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al eliminar los datos']);
    }
}

/**
 * Actualiza el estado de activación de una respuesta
 */
function handleUpdateStatus() {
    global $pdo;
    
    try {
        // Validar datos
        $id = intval($_POST['id'] ?? 0);
        $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID no válido']);
            return;
        }
        
        // Actualizar en la base de datos
        $stmt = $pdo->prepare("UPDATE autorespuestas_whatsapp SET is_active = ?, updated_at = ? WHERE id = ?");
        $result = $stmt->execute([$isActive, time(), $id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
        }
    } catch (\PDOException $e) {
        error_log('Error al actualizar estado de respuesta: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
    }
}