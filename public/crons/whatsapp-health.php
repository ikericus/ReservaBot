<?php
// public/crons/whatsapp-health.php
echo "Iniciando cron de verificación de salud de WhatsApp...\n";
try
{
    // Definir raíz del proyecto (public/)
    define('PROJECT_ROOT', dirname(__DIR__));

    require_once PROJECT_ROOT . '/config/bootstrap.php';

    $serverManager = getContainer()->getWhatsAppServerManager();
    $health = $serverManager->verificarSalud();
    
    echo "Estado: " . $health['status'] . "\n";

    // Si el estado no es "healthy", enviamos un correo de alerta
    if ($health['status'] !== 'healthy') {
        echo "Enviando correo de alerta...\n";
        $emailRepository = getContainer()->getEmailRepository();
        
        // Email de destino
        $emailAdmin = $_ENV['ADMIN_EMAIL'];
            
        // Enviar 
        $enviado = $emailRepository->enviar(
            $emailAdmin,
            '⚠️ Alerta: WhatsApp Server está caído',
            "Se detectó un problema en el servidor WhatsApp:\n\n" . json_encode($healthData, JSON_PRETTY_PRINT) . "\n\nHora del cron: " . date('Y-m-d H:i:s'));

        if (!$enviado) {
            error_log('Error: No se pudo enviar el correo de alerta usando EmailRepository.');
        }
    }
    echo "Cron finalizado\n";
}
catch (\Exception $e)
{
    error_log("Error en el cron: " . $e->getMessage());
    exit(1);
}

?>