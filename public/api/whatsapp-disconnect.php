<?php
/**
 * API para desconectar WhatsApp
 */

// Incluir configuración y funciones
require_once '../db-config.php';
require_once '../functions.php';
require_once '../whatsapp-functions.php';

// Cabeceras para JSON
header('Content-Type: application/json');

// Desconectar
$result = disconnectWhatsApp();

// Devolver resultado
echo json_encode($result);