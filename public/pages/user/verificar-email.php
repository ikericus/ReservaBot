<?php
// pages/user/verificar-email.php

/**
 * Página de verificación de email
 */

$token = $_GET['token'] ?? '';
$success = false;

if (empty($token)) {
    $error = 'Token de verificación inválido';
} 
else {
    try {
        $usuarioDomain = getContainer()->getUsuarioDomain();
        
        // Verificar correo (marca como verificado y envía email de bienvenida)
        $usuarioDomain->verificarCorreo($token);
        
        $success = true;
        
    } catch (\DomainException $e) {
        error_log("Error en verificación de email: " . $e->getMessage());
    } catch (\Exception $e) {
        error_log('Error en verificación de email: ' . $e->getMessage());
    }
}

if ($success) {
    $_SESSION['login_message'] = '¡Tu correo ha sido verificado exitosamente!';
}
else {    
    $_SESSION['login_errors'] = 'Error al verificar tu correo. Por favor, inténtalo nuevamente.';
}
header('Location: /login');
exit;