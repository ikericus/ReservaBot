<?php
// pages/errors/500.php
$errorCode = 500;
$errorTitle = 'Error del servidor';
$errorMessage = 'Algo salió mal en nuestros servidores. Nuestro equipo ya ha sido notificado y está trabajando en solucionar el problema.';
$errorIcon = 'ri-server-line';
$errorLinks = [
    ['url' => '/', 'icon' => 'ri-home-line', 'text' => 'Inicio'],
    ['url' => '/estado', 'icon' => 'ri-pulse-line', 'text' => 'Estado del sistema']
];

include __DIR__ . '/error-layout.php';