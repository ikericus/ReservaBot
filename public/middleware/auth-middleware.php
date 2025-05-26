
<?php
/**
 * Middleware de autenticación para incluir en todas las páginas protegidas
 */

require_once __DIR__ . '/../includes/db-config.php';
require_once __DIR__ . '/../includes/auth.php';

// Aplicar middleware de autenticación
requireAuth();

// Actualizar última actividad
updateLastActivity();

// Verificar CSRF en peticiones POST (opcional, para mayor seguridad)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        // Por ahora solo log, en producción podrías ser más estricto
        error_log('CSRF token inválido desde IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
}

// Hacer disponible la información del usuario para las páginas
$currentUser = getAuthenticatedUser();
$csrfToken = generateCSRFToken();