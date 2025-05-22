<?php
/**
 * API para iniciar la conexión de WhatsApp
 */

// Incluir configuración y funciones
require_once '../db-config.php';
require_once '../functions.php';
require_once '../whatsapp-functions.php';

// Cabeceras para JSON
header('Content-Type: application/json');

// Iniciar conexión
$result = connectWhatsApp();

// Devolver resultado
echo json_encode($result);