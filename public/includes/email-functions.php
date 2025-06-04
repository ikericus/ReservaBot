<?php
/**
 * Funciones para envío de emails
 * Agregar a: public/includes/email-functions.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Instalar PHPMailer con: composer require phpmailer/phpmailer

require_once dirname(__DIR__) . '/includes/db-config.php';

/**
 * Configuración de email desde base de datos
 */
function getEmailConfig() {
    
    try {
        $stmt = getPDO()->prepare("
            SELECT clave, valor FROM configuraciones 
            WHERE clave IN ('email_smtp_host', 'email_smtp_port', 'email_smtp_user', 'email_smtp_pass', 'email_from_address', 'email_from_name')
        ");
        $stmt->execute();
        $config = [];
        
        while ($row = $stmt->fetch()) {
            $config[$row['clave']] = $row['valor'];
        }
        
        return $config;
    } catch (Exception $e) {
        error_log("Error obteniendo configuración de email: " . $e->getMessage());
        return [];
    }
}

/**
 * Enviar email de restablecimiento de contraseña
 */
function sendPasswordResetEmail($email, $resetToken) {
    $config = getEmailConfig();
    
    if (empty($config['email_smtp_host']) || empty($config['email_smtp_user'])) {
        error_log("Configuración de email incompleta");
        return false;
    }
    
    try {
        $mail = new PHPMailer(true);
        
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = $config['email_smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['email_smtp_user'];
        $mail->Password = $config['email_smtp_pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['email_smtp_port'] ?? 587;
        $mail->CharSet = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom($config['email_from_address'], $config['email_from_name'] ?? 'ReservaBot');
        $mail->addAddress($email);
        
        // Contenido del email
        $resetUrl = "https://{$_SERVER['HTTP_HOST']}/password-reset.php?token={$resetToken}";
        
        $mail->isHTML(true);
        $mail->Subject = 'Restablecer contraseña - ReservaBot';
        $mail->Body = getPasswordResetEmailTemplate($resetUrl, $email);
        $mail->AltBody = "Para restablecer tu contraseña, visita: {$resetUrl}";
        
        $mail->send();
        error_log("Email de restablecimiento enviado a: {$email}");
        return true;
        
    } catch (Exception $e) {
        error_log("Error enviando email de restablecimiento: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Enviar email de verificación
 */
function sendVerificationEmail($email, $verificationToken) {
    $config = getEmailConfig();
    
    if (empty($config['email_smtp_host']) || empty($config['email_smtp_user'])) {
        error_log("Configuración de email incompleta");
        return false;
    }
    
    try {
        $mail = new PHPMailer(true);
        
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = $config['email_smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['email_smtp_user'];
        $mail->Password = $config['email_smtp_pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['email_smtp_port'] ?? 587;
        $mail->CharSet = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom($config['email_from_address'], $config['email_from_name'] ?? 'ReservaBot');
        $mail->addAddress($email);
        
        // Contenido del email
        $verifyUrl = "https://{$_SERVER['HTTP_HOST']}/verify-email.php?token={$verificationToken}";
        
        $mail->isHTML(true);
        $mail->Subject = 'Verificar email - ReservaBot';
        $mail->Body = getVerificationEmailTemplate($verifyUrl, $email);
        $mail->AltBody = "Para verificar tu email, visita: {$verifyUrl}";
        
        $mail->send();
        error_log("Email de verificación enviado a: {$email}");
        return true;
        
    } catch (Exception $e) {
        error_log("Error enviando email de verificación: {$mail->ErrorInfo}");
        return false;
    }
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
 * Configurar email desde panel de administración
 */
function updateEmailConfig($config) {
    
    try {
        $stmt = getPDO()->prepare("
            INSERT INTO configuraciones (clave, valor, updated_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()
        ");
        
        foreach ($config as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error actualizando configuración de email: " . $e->getMessage());
        return false;
    }
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
    
    // Contenido HTML del email
    $htmlContent = generarHTMLEmail($reserva, $gestionUrl);
    
    // Headers del email
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ReservaBot <noreply@reservabot.com>',
        'Reply-To: info@reservabot.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Enviar email (puedes cambiar por otra librería de email)
    $enviado = mail(
        $reserva['email'],
        $asunto,
        $htmlContent,
        implode("\r\n", $headers)
    );
    
    // Log del resultado
    if ($enviado) {
        error_log("Email de confirmación enviado para reserva ID: " . $reserva['id']);
    } else {
        error_log("Error enviando email de confirmación para reserva ID: " . $reserva['id']);
    }
    
    return $enviado;
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