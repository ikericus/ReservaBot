<?php
// pages/errors/403.php
$errorCode = 403;
$errorTitle = 'Acceso denegado';
$errorMessage = 'No tienes permisos para acceder a este recurso. Si crees que esto es un error, contacta con el administrador.';
$errorIcon = 'ri-lock-line';
$errorLinks = [
    ['url' => '/', 'icon' => 'ri-home-line', 'text' => 'Inicio'],
    ['url' => '/login', 'icon' => 'ri-login-box-line', 'text' => 'Iniciar sesión']
];

include __DIR__ . '/error-layout.php';