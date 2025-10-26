<?php
/**
 * Funciones para envío de emails
 * Archivo: public/includes/email-functions.php
 * Refactorizado con método central enviarEmail()
 */

// No necesitamos db-config.php ya que no usamos configuraciones de BD

/**
 * Método central para envío de emails
 * 
 * @param string $destinatario Email del destinatario
 * @param string $asunto Asunto del email
 * @param string $contenidoHTML Contenido HTML del email
 * @param string $fromEmail Email del remitente
 * @param string $fromName Nombre del remitente
 * @param array $opciones Opciones adicionales (reply_to, etc.)
 * @param string $tipoEmail Tipo de email para logs
 * @return bool True si se envió correctamente
 */
function enviarEmail($destinatario, $asunto, $contenidoHTML, $fromEmail, $fromName, $opciones = [], $tipoEmail = 'genérico') {
    // Headers base
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        "From: {$fromName} <{$fromEmail}>",
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Headers adicionales según opciones
    if (isset($opciones['reply_to'])) {
        if (isset($opciones['reply_to_name'])) {
            $headers[] = "Reply-To: {$opciones['reply_to_name']} <{$opciones['reply_to']}>";
        } else {
            $headers[] = "Reply-To: {$opciones['reply_to']}";
        }
    }
    
    // Enviar email
    $enviado = mail(
        $destinatario,
        $asunto,
        $contenidoHTML,
        implode("\r\n", $headers)
    );
    
    // Log del resultado
    if ($enviado) {
        error_log("Email {$tipoEmail} enviado a: {$destinatario}");
    } else {
        error_log("Error enviando email {$tipoEmail} a: {$destinatario}");
    }
    
    return $enviado;
}

/**
 * Enviar email de restablecimiento de contraseña
 */
function sendPasswordResetEmail($email, $resetToken) {
    // Generar URL de restablecimiento
    $resetUrl = "https://{$_SERVER['HTTP_HOST']}/password-reset.php?token={$resetToken}";
    
    // Asunto y contenido
    $asunto = 'Restablecer contraseña - ReservaBot';
    $contenido = getPasswordResetEmailTemplate($resetUrl, $email);
    
    // Enviar usando método central
    return enviarEmail(
        $email, 
        $asunto, 
        $contenido, 
        'noreply@reservabot.es', 
        'ReservaBot',
        [], 
        'restablecimiento de contraseña'
    );
}

/**
 * Enviar email de verificación
 */
function sendVerificationEmail($email, $verificationToken) {
    // Generar URL de verificación
    $verifyUrl = "https://{$_SERVER['HTTP_HOST']}/verify-email.php?token={$verificationToken}";
    
    // Asunto y contenido
    $asunto = 'Verificar email - ReservaBot';
    $contenido = getVerificationEmailTemplate($verifyUrl, $email);
    
    // Enviar usando método central
    return enviarEmail(
        $email, 
        $asunto, 
        $contenido, 
        'noreply@reservabot.es', 
        'ReservaBot',
        [], 
        'verificación de email'
    );
}


/**
 * Template para email de restablecimiento
 */
function getPasswordResetEmailTemplate($resetUrl, $email) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Restablecer Contraseña</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
            <h1 style='color: white; margin: 0; font-size: 28px;'>ReservaBot</h1>
            <p style='color: #e0e7ff; margin: 10px 0 0 0;'>Restablecer Contraseña</p>
        </div>
        
        <div style='background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e2e8f0;'>
            <h2 style='color: #1f2937; margin-top: 0;'>¡Hola!</h2>
            
            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta de ReservaBot asociada con <strong>{$email}</strong>.</p>
            
            <p>Si solicitaste este cambio, haz clic en el botón de abajo para crear una nueva contraseña:</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$resetUrl}' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                    Restablecer Contraseña
                </a>
            </div>
            
            <p style='color: #6b7280; font-size: 14px;'>
                <strong>Importante:</strong> Este enlace expirará en 1 hora por seguridad.
            </p>
            
            <p style='color: #6b7280; font-size: 14px;'>
                Si no solicitaste este cambio, puedes ignorar este email. Tu contraseña no será modificada.
            </p>
            
            <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;'>
            
            <p style='color: #9ca3af; font-size: 12px; text-align: center;'>
                Este email fue enviado desde ReservaBot<br>
                Si tienes problemas con el botón, copia y pega este enlace en tu navegador:<br>
                <a href='{$resetUrl}' style='color: #667eea; word-break: break-all;'>{$resetUrl}</a>
            </p>
        </div>
    </body>
    </html>
    ";
}

/**
 * Template para email de verificación
 */
function getVerificationEmailTemplate($verifyUrl, $email) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Verificar Email</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
            <h1 style='color: white; margin: 0; font-size: 28px;'>¡Bienvenido a ReservaBot!</h1>
            <p style='color: #e0e7ff; margin: 10px 0 0 0;'>Verificar Email</p>
        </div>
        
        <div style='background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e2e8f0;'>
            <h2 style='color: #1f2937; margin-top: 0;'>¡Gracias por registrarte!</h2>
            
            <p>Tu cuenta ha sido creada exitosamente. Para completar el proceso y activar todas las funciones, necesitas verificar tu dirección de email <strong>{$email}</strong>.</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$verifyUrl}' style='background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                    Verificar Email
                </a>
            </div>
            
            <p style='color: #6b7280; font-size: 14px;'>
                Una vez verificado, podrás acceder a todas las funciones de ReservaBot para automatizar las reservas de tu negocio.
            </p>
            
            <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;'>
            
            <p style='color: #9ca3af; font-size: 12px; text-align: center;'>
                Si tienes problemas con el botón, copia y pega este enlace en tu navegador:<br>
                <a href='{$verifyUrl}' style='color: #667eea; word-break: break-all;'>{$verifyUrl}</a>
            </p>
        </div>
    </body>
    </html>
    ";
}


?>