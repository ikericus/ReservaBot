<?php
// pages/errors/404.php
$errorCode = 404;
$errorTitle = 'Página no encontrada';
$errorMessage = 'Lo sentimos, la página que buscas no existe o ha sido movida. Puede que el enlace esté roto o que la URL sea incorrecta.';
$errorIcon = 'ri-calendar-close-line';
$errorLinks = [
    ['url' => '/dia', 'icon' => 'ri-calendar-line', 'text' => 'Calendario'],
    ['url' => '/configuracion', 'icon' => 'ri-settings-3-line', 'text' => 'Configuración'],
    ['url' => '/reservas', 'icon' => 'ri-file-list-3-line', 'text' => 'Mis Reservas']
];

include __DIR__ . '/error-layout.php';