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
 * Envía email de contacto desde el formulario web
 */
function enviarEmailContactoWeb($nombre, $emailCliente, $asunto, $mensaje) {
    // Destinatario y asunto
    $destinatario = 'contacto@reservabot.es';
    $subject = 'Contacto Web - ' . ucfirst($asunto);
    
    // Contenido HTML
    $contenido = generarEmailContactoWebHTML($nombre, $emailCliente, $asunto, $mensaje);
    
    // Opciones específicas para contacto
    $opciones = [
        'reply_to' => $emailCliente,
        'reply_to_name' => $nombre
    ];
    
    // Enviar usando método central (el email viene del cliente)
    return enviarEmail(
        $destinatario, 
        $subject, 
        $contenido, 
        $emailCliente, 
        $nombre,
        $opciones, 
        'contacto web'
    );
}

/**
 * Envía email de confirmación al cliente
 */
function enviarEmailConfirmacion($reserva) {
    // Generar URL de gestión
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $gestionUrl = $protocol . $host . '/mi-reserva?token=' . $reserva['access_token'];
    
    // Asunto del email
    $asunto = $reserva['estado'] === 'confirmada' 
        ? '✅ Tu reserva está confirmada - ' . $reserva['formulario_nombre']
        : '⏳ Tu solicitud de reserva - ' . $reserva['formulario_nombre'];
    
    // Contenido HTML
    $contenido = generarHTMLEmail($reserva, $gestionUrl);
    
    // Enviar usando método central
    return enviarEmail(
        $reserva['email'], 
        $asunto, 
        $contenido, 
        'noreply@reservabot.es', 
        'ReservaBot',
        [], 
        "confirmación de reserva ID: {$reserva['id']}"
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

/**
 * Genera el HTML del email de contacto web
 */
function generarEmailContactoWebHTML($nombre, $emailCliente, $asunto, $mensaje) {
    $fechaActual = date('d/m/Y H:i:s');
    $asuntoFormateado = ucfirst($asunto);
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Nuevo Contacto Web</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8fafc;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: white;'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center;'>
                <h1 style='margin: 0; font-size: 24px;'>📧 Nuevo Contacto Web</h1>
                <p style='margin: 10px 0 0 0; opacity: 0.9;'>ReservaBot</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 30px 20px;'>
                <h2 style='color: #374151; margin-top: 0;'>Nuevo mensaje de contacto</h2>
                <p style='color: #6b7280; line-height: 1.6;'>
                    Se ha recibido un nuevo mensaje desde el formulario de contacto del sitio web.
                </p>
                
                <!-- Detalles del contacto -->
                <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;'>
                    <h3 style='margin-top: 0; color: #374151;'>👤 Información del contacto:</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr><td style='padding: 8px 0; color: #374151; width: 120px;'><strong>Nombre:</strong></td><td style='padding: 8px 0; color: #6b7280;'>{$nombre}</td></tr>
                        <tr><td style='padding: 8px 0; color: #374151;'><strong>Email:</strong></td><td style='padding: 8px 0; color: #6b7280;'><a href='mailto:{$emailCliente}' style='color: #667eea;'>{$emailCliente}</a></td></tr>
                        <tr><td style='padding: 8px 0; color: #374151;'><strong>Asunto:</strong></td><td style='padding: 8px 0; color: #6b7280;'>{$asuntoFormateado}</td></tr>
                        <tr><td style='padding: 8px 0; color: #374151;'><strong>Fecha:</strong></td><td style='padding: 8px 0; color: #6b7280;'>{$fechaActual}</td></tr>
                    </table>
                </div>
                
                <!-- Mensaje -->
                <div style='background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #bfdbfe;'>
                    <h3 style='margin-top: 0; color: #374151;'>💬 Mensaje:</h3>
                    <div style='color: #1e40af; line-height: 1.6; white-space: pre-wrap;'>{$mensaje}</div>
                </div>
                
                <!-- Botón de respuesta -->
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='mailto:{$emailCliente}?subject=Re: {$asuntoFormateado}' style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
                        📧 Responder
                    </a>
                </div>
                
                <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;'>
                <p style='font-size: 12px; color: #9ca3af; text-align: center; margin: 0;'>
                    Este email fue generado automáticamente desde el formulario de contacto de ReservaBot<br>
                    <strong>No responder a este email</strong> - Usar el botón de respuesta de arriba
                </p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Genera el HTML del email de confirmación
 */
function generarHTMLEmail($reserva, $gestionUrl) {
    $fechaFormateada = date('d/m/Y', strtotime($reserva['fecha']));
    $horaFormateada = substr($reserva['hora'], 0, 5);
    
    $estadoTexto = $reserva['estado'] === 'confirmada' 
        ? 'confirmada automáticamente' 
        : 'recibida y pendiente de confirmación';
    
    $estadoColor = $reserva['estado'] === 'confirmada' ? '#10b981' : '#f59e0b';
    $estadoIcon = $reserva['estado'] === 'confirmada' ? '✅' : '⏳';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Confirmación de Reserva</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8fafc;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: white;'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center;'>
                <h1 style='margin: 0; font-size: 24px;'>{$estadoIcon} Reserva {$reserva['estado']}</h1>
                <p style='margin: 10px 0 0 0; opacity: 0.9;'>{$reserva['formulario_nombre']}</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 30px 20px;'>
                <h2 style='color: #374151; margin-top: 0;'>Hola {$reserva['nombre']},</h2>
                <p style='color: #6b7280; line-height: 1.6;'>
                    Tu reserva ha sido <strong style='color: {$estadoColor};'>{$estadoTexto}</strong>.
                </p>
                
                <!-- Detalles de la reserva -->
                <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid {$estadoColor};'>
                    <h3 style='margin-top: 0; color: #374151;'>📅 Detalles de tu cita:</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr><td style='padding: 8px 0; color: #374151;'><strong>📅 Fecha:</strong></td><td style='padding: 8px 0; color: #6b7280;'>{$fechaFormateada}</td></tr>
                        <tr><td style='padding: 8px 0; color: #374151;'><strong>🕐 Hora:</strong></td><td style='padding: 8px 0; color: #6b7280;'>{$horaFormateada}</td></tr>
                        <tr><td style='padding: 8px 0; color: #374151;'><strong>👤 Nombre:</strong></td><td style='padding: 8px 0; color: #6b7280;'>{$reserva['nombre']}</td></tr>
                        <tr><td style='padding: 8px 0; color: #374151;'><strong>📞 Teléfono:</strong></td><td style='padding: 8px 0; color: #6b7280;'>{$reserva['telefono']}</td></tr>
                    </table>
                    " . (!empty($reserva['mensaje']) ? "<p style='margin-top: 15px; color: #374151;'><strong>💬 Comentarios:</strong><br>{$reserva['mensaje']}</p>" : "") . "
                </div>
                
                <!-- Botón de gestión -->
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$gestionUrl}' style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
                        🔗 Gestionar mi Reserva
                    </a>
                </div>
                
                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; border: 1px solid #bfdbfe; margin: 20px 0;'>
                    <p style='margin: 0; font-size: 14px; color: #1e40af;'>
                        <strong>💡 ¿Qué puedes hacer?</strong><br>
                        Desde el enlace de arriba podrás consultar, modificar o cancelar tu reserva hasta 24h antes de la cita.
                    </p>
                </div>
                
                " . ($reserva['estado'] === 'pendiente' ? "
                <div style='background: #fef3c7; padding: 15px; border-radius: 8px; border: 1px solid #fbbf24; margin: 20px 0;'>
                    <p style='margin: 0; font-size: 14px; color: #92400e;'>
                        <strong>⏳ Confirmación pendiente</strong><br>
                        Te contactaremos pronto para confirmar tu reserva.
                    </p>
                </div>
                " : "
                <div style='background: #d1fae5; padding: 15px; border-radius: 8px; border: 1px solid #10b981; margin: 20px 0;'>
                    <p style='margin: 0; font-size: 14px; color: #065f46;'>
                        <strong>✅ Reserva confirmada</strong><br>
                        Tu reserva está confirmada. ¡Te esperamos!
                    </p>
                </div>
                ") . "
                
                <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;'>
                <p style='font-size: 12px; color: #9ca3af; text-align: center; margin: 0;'>
                    Este email fue enviado automáticamente. Si tienes dudas, contacta con nosotros.<br>
                    <strong>ReservaBot</strong> - Sistema de gestión de reservas
                </p>
            </div>
        </div>
    </body>
    </html>";
}

?>