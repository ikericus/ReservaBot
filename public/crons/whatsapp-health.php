<?php
// public/crons/whatsapp-health.php
echo "Iniciando cron de verificación de salud de WhatsApp...\n";
try
{
    // Definir raíz del proyecto (public/)
    define('PROJECT_ROOT', dirname(__DIR__));

    require_once PROJECT_ROOT . '/config/bootstrap.php';

    $serverManager = getContainer()->getWhatsAppServerManager();
    $healthData = $serverManager->verificarSalud();
    
    if(isset($healthData) && $healthData['status'] == 'healthy') {
        echo "Estado de salud del servidor WhatsApp: " . $healthData['status'] . ". Cron finalizado\n";
        exit(0);
    }     
    
    // Si el estado no es "healthy", enviamos un correo de alerta    
    echo "Enviando correo de alerta...\n";
    $emailRepository = getContainer()->getEmailRepository();
    
    // Email de destino
    $emailAdmin = $_ENV['ADMIN_EMAIL'] . ', ikerzuazu@gmail.com';
        
    $emailTemplates = new \ReservaBot\Domain\Email\EmailTemplates();    
    // Generar contenido del email
    $emailData = $emailTemplates->alertaServidorCaido($healthData);

    // Opciones: responder al email del cliente
    $opciones = [
        'reply_to' => $emailAdmin,
        'reply_to_name' => 'Administrador ReservaBot'
    ];        

    // Enviar 
    $enviado = $emailRepository->enviar(
        $emailAdmin,
        $emailData['asunto'],
        $emailData['cuerpo_texto'],
        $emailData['cuerpo_html'],
        $opciones
    );

    if (!$enviado) {
        error_log('Error: No se pudo enviar el correo de alerta usando EmailRepository.');
    }
}
catch (\Exception $e)
{
    error_log("Error en el cron: " . $e->getMessage());
    exit(1);
}

?>