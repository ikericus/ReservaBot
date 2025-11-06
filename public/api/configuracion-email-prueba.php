<?php
// api/configuracion-email-prueba.php

header('Content-Type: application/json');

// Verificar autenticación
$currentUser = getAuthenticatedUser();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

try {
    $usuarioId = $currentUser['id'];
    $emailDestino = $currentUser['email'];
    $nombreUsuario = $currentUser['nombre'];

    // Enviar email de prueba usando el método del dominio
    $resultado = getContainer()->getConfiguracionDomain()->enviarEmailPrueba($emailDestino, $usuarioId, $nombreUsuario);
    
    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Email de prueba enviado correctamente a ' . $emailDestino
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo enviar el email de prueba'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en enviar-email-prueba: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al enviar email de prueba: ' . $e->getMessage()
    ]);
}