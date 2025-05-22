<?php
/**
 * API para gestionar configuración de WhatsApp
 */

// Incluir configuración y funciones
require_once '../db-config.php';
require_once '../functions.php';
require_once '../whatsapp-functions.php';

// Cabeceras para JSON
header('Content-Type: application/json');

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener parámetros
$setting = $_POST['setting'] ?? '';
$enabled = isset($_POST['enabled']) ? (int)$_POST['enabled'] : 0;

// Validar parámetros
if (empty($setting)) {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
    exit;
}

// Actualizar configuración
$result = updateWhatsAppNotificationSetting($setting, $enabled);

// Devolver resultado
echo json_encode($result);