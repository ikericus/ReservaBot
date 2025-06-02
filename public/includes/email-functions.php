<?php
/**
 * Funciones para envío de emails
 * Agregar a: public/includes/email-functions.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Instalar PHPMailer con: composer require phpmailer/phpmailer

require_once '../includes/db-config.php';

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
?>