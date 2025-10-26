<?php
// pages/user/verificar-email.php

/**
 * Página de verificación de email
 */

require_once __DIR__ . '/includes/bootstrap.php';

$token = $_GET['token'] ?? '';
$error = null;
$success = false;

if (empty($token)) {
    $error = 'Token de verificación inválido';
} else {
    try {
        $usuarioDomain = getContainer()->getUsuarioDomain();
        
        // Verificar correo (marca como verificado y envía email de bienvenida)
        $usuarioDomain->verificarCorreo($token);
        
        $success = true;
        
    } catch (\DomainException $e) {
        $error = $e->getMessage();
    } catch (\Exception $e) {
        error_log('Error en verificación de email: ' . $e->getMessage());
        $error = 'Error al verificar tu correo. Por favor, inténtalo nuevamente.';
    }
}

if ($success) {
    $_SESSION['login_message'] = '¡Tu correo ha sido verificado exitosamente!';
}
else {    
    $_SESSION['login_message'] = $error;
}
header('Location: /login');
exit;