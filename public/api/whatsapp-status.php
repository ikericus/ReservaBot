<?php
/**
 * API para verificar el estado de la conexión WhatsApp
 */

// Incluir configuración y funciones
require_once '../includes/db-config.php';
require_once '../includes/functions.php';
require_once '../includes/whatsapp-functions.php';

// Cabeceras para JSON
header('Content-Type: application/json');

// Verificar estado
$status = getWhatsAppStatus();

// Devolver resultado
echo json_encode($status);