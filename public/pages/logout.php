<?php
/**
 * Página de logout
 */

logout();   // Cerrar sesión

// Redirigir al login con mensaje
$_SESSION['login_message'] = 'Has cerrado sesión correctamente';

header('Location: /login');
exit;
?>