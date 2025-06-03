<?php
/**
 * Página de logout
 */

require_once dirname(__DIR__) . '/includes/auth.php';

// Cerrar sesión
logout();

// Redirigir al login con mensaje
session_start();
$_SESSION['login_message'] = 'Has cerrado sesión correctamente';
header('Location: /login');
exit;
?>