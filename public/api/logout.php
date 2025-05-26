<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';

// Cerrar sesión
logout();

echo json_encode([
    'success' => true,
    'message' => 'Sesión cerrada correctamente',
    'redirect' => '/login.php'
]);
