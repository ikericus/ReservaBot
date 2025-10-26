<?php
// src/domain/email/EmailTemplates.php

namespace ReservaBot\Domain\Email;

class EmailTemplates {
    private string $baseUrl;
    private string $appName;
    
    public function __construct() {
        $this->baseUrl = $_ENV['APP_URL'];
        $this->appName = $_ENV['APP_NAME'];
    }
    
    /**
     * Genera HTML base con header y footer
     */
    private function wrapHtml(string $titulo, string $contenido): string {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$titulo}</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8fafc;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: white;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='color: white; margin: 0; font-size: 28px;'>{$this->appName}</h1>
                </div>
                <div style='padding: 30px 20px;'>
                    {$contenido}
                </div>
                <div style='text-align: center; padding: 20px; color: #666; font-size: 12px; border-top: 1px solid #e5e7eb;'>
                    <p>&copy; " . date('Y') . " {$this->appName}. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Genera botón HTML
     */
    private function generarBoton(string $url, string $texto, string $color = '#4F46E5'): string {
        return "
        <div style='text-align: center; margin: 30px 0;'>
            <a href='{$url}' style='display: inline-block; background: {$color}; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
                {$texto}
            </a>
        </div>
        <p style='color: #9ca3af; font-size: 12px; text-align: center;'>
            Si tienes problemas con el botón, copia y pega este enlace:<br>
            <a href='{$url}' style='color: #4F46E5; word-break: break-all;'>{$url}</a>
        </p>";
    }
    
    /**
     * Template: Verificación de email
     */
    public function verificacionEmail(string $nombre, string $token): array {
        $url = $this->baseUrl . "/verificar-email?token=" . $token;
        
        $contenidoHtml = "
            <h2 style='color: #1f2937; margin-top: 0;'>¡Hola {$nombre}!</h2>
            <p>Gracias por registrarte en {$this->appName}.</p>
            <p>Para verificar tu cuenta, haz clic en el siguiente botón:</p>
            " . $this->generarBoton($url, 'Verificar mi cuenta', '#10b981') . "
            <p style='color: #6b7280; font-size: 14px;'>
                <strong>Importante:</strong> Este enlace expirará en 24 horas.
            </p>
            <p style='color: #6b7280; font-size: 14px;'>
                Si no creaste esta cuenta, puedes ignorar este mensaje.
            </p>";
        
        $contenidoTexto = "Hola {$nombre},\n\n";
        $contenidoTexto .= "Gracias por registrarte en {$this->appName}.\n\n";
        $contenidoTexto .= "Para verificar tu cuenta, visita este enlace:\n{$url}\n\n";
        $contenidoTexto .= "Este enlace expirará en 24 horas.\n\n";
        $contenidoTexto .= "Si no creaste esta cuenta, puedes ignorar este mensaje.\n\n";
        $contenidoTexto .= "Saludos,\nEquipo {$this->appName}";
        
        return [
            'asunto' => 'Verifica tu cuenta en ' . $this->appName,
            'cuerpo_texto' => $contenidoTexto,
            'cuerpo_html' => $this->wrapHtml('Verificar Email', $contenidoHtml)
        ];
    }
    
    /**
     * Template: Reset de contraseña
     */
    public function restablecimientoContrasena(string $nombre, string $token): array {
        $url = $this->baseUrl . "/reset-password?token=" . $token;
        
        $contenidoHtml = "
            <h2 style='color: #1f2937; margin-top: 0;'>¡Hola {$nombre}!</h2>
            <p>Recibimos una solicitud para restablecer tu contraseña.</p>
            <p>Para crear una nueva contraseña, haz clic en el siguiente botón:</p>
            " . $this->generarBoton($url, 'Restablecer contraseña', '#EF4444') . "
            <div style='background: #FEF3C7; padding: 15px; border-left: 4px solid #F59E0B; margin: 20px 0;'>
                <p style='margin: 0; color: #92400e;'>
                    <strong>⚠️ Importante:</strong> Este enlace expirará en 1 hora.
                </p>
            </div>
            <p style='color: #6b7280; font-size: 14px;'>
                Si no solicitaste este cambio, puedes ignorar este mensaje.
            </p>";
        
        $contenidoTexto = "Hola {$nombre},\n\n";
        $contenidoTexto .= "Recibimos una solicitud para restablecer tu contraseña.\n\n";
        $contenidoTexto .= "Para crear una nueva contraseña, visita:\n{$url}\n\n";
        $contenidoTexto .= "Este enlace expirará en 1 hora.\n\n";
        $contenidoTexto .= "Si no solicitaste este cambio, ignora este mensaje.\n\n";
        $contenidoTexto .= "Saludos,\nEquipo {$this->appName}";
        
        return [
            'asunto' => 'Restablece tu contraseña en ' . $this->appName,
            'cuerpo_texto' => $contenidoTexto,
            'cuerpo_html' => $this->wrapHtml('Restablecer Contraseña', $contenidoHtml)
        ];
    }
    
    /**
     * Template: Bienvenida
     */
    public function bienvenida(string $nombre): array {
        $url = $this->baseUrl . "/reservas";
        
        $contenidoHtml = "
            <h2 style='color: #1f2937; margin-top: 0;'>¡Hola {$nombre}!</h2>
            <p>¡Bienvenido a {$this->appName}! Estamos encantados de tenerte con nosotros.</p>
            <p>Tu cuenta ha sido verificada y ya puedes comenzar a usar todas nuestras funcionalidades.</p>
            <div style='background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #374151;'>¿Qué puedes hacer ahora?</h3>
                <ul style='color: #6b7280; padding-left: 20px;'>
                    <li>Gestionar reservas en tiempo real</li>
                    <li>Integrar con WhatsApp</li>
                    <li>Ver reportes y estadísticas</li>
                    <li>Configurar tu negocio</li>
                </ul>
            </div>
            " . $this->generarBoton($url, 'Ir a mi panel', '#4F46E5') . "
            <p style='color: #6b7280; font-size: 14px;'>
                Si tienes alguna pregunta, no dudes en contactarnos.
            </p>";
        
        $contenidoTexto = "¡Hola {$nombre}!\n\n";
        $contenidoTexto .= "¡Bienvenido a {$this->appName}! Estamos encantados de tenerte.\n\n";
        $contenidoTexto .= "Tu cuenta ha sido verificada y ya puedes empezar.\n\n";
        $contenidoTexto .= "Accede aquí: {$url}\n\n";
        $contenidoTexto .= "Saludos,\nEquipo {$this->appName}";
        
        return [
            'asunto' => '¡Bienvenido a ' . $this->appName . '!',
            'cuerpo_texto' => $contenidoTexto,
            'cuerpo_html' => $this->wrapHtml('Bienvenido', $contenidoHtml)
        ];
    }
    
    /**
     * Template: Confirmación de reserva
     */
    public function confirmacionReserva(array $reserva, string $gestionUrl): array {
        $fechaFormateada = date('d/m/Y', strtotime($reserva['fecha']));
        $horaFormateada = substr($reserva['hora'], 0, 5);
        
        $esConfirmada = $reserva['estado'] === 'confirmada';
        $estadoTexto = $esConfirmada ? 'confirmada' : 'pendiente de confirmación';
        $estadoIcon = $esConfirmada ? '✅' : '⏳';
        $estadoColor = $esConfirmada ? '#10b981' : '#f59e0b';
        
        $contenidoHtml = "
            <h2 style='color: #1f2937; margin-top: 0;'>Hola {$reserva['nombre']},</h2>
            <p>Tu reserva ha sido <strong style='color: {$estadoColor};'>{$estadoTexto}</strong>.</p>
            
            <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid {$estadoColor};'>
                <h3 style='margin-top: 0; color: #374151;'>📅 Detalles de tu cita:</h3>
                <table style='width: 100%;'>
                    <tr><td style='padding: 8px 0; color: #374151;'><strong>Fecha:</strong></td><td style='color: #6b7280;'>{$fechaFormateada}</td></tr>
                    <tr><td style='padding: 8px 0; color: #374151;'><strong>Hora:</strong></td><td style='color: #6b7280;'>{$horaFormateada}</td></tr>
                    <tr><td style='padding: 8px 0; color: #374151;'><strong>Teléfono:</strong></td><td style='color: #6b7280;'>{$reserva['telefono']}</td></tr>
                </table>
                " . (!empty($reserva['mensaje']) ? "<p style='margin-top: 15px; color: #374151;'><strong>Comentarios:</strong><br>{$reserva['mensaje']}</p>" : "") . "
            </div>
            
            " . $this->generarBoton($gestionUrl, 'Gestionar mi reserva', '#4F46E5') . "
            
            " . ($esConfirmada ? "
            <div style='background: #d1fae5; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981; margin: 20px 0;'>
                <p style='margin: 0; color: #065f46;'><strong>✅ Reserva confirmada</strong><br>¡Te esperamos!</p>
            </div>
            " : "
            <div style='background: #fef3c7; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b; margin: 20px 0;'>
                <p style='margin: 0; color: #92400e;'><strong>⏳ Pendiente</strong><br>Te contactaremos pronto.</p>
            </div>
            ") . "";
        
        $contenidoTexto = "Hola {$reserva['nombre']},\n\n";
        $contenidoTexto .= "Tu reserva ha sido {$estadoTexto}.\n\n";
        $contenidoTexto .= "Detalles:\n";
        $contenidoTexto .= "- Fecha: {$fechaFormateada}\n";
        $contenidoTexto .= "- Hora: {$horaFormateada}\n";
        $contenidoTexto .= "- Teléfono: {$reserva['telefono']}\n";
        if (!empty($reserva['mensaje'])) {
            $contenidoTexto .= "- Comentarios: {$reserva['mensaje']}\n";
        }
        $contenidoTexto .= "\nGestiona tu reserva aquí: {$gestionUrl}\n\n";
        $contenidoTexto .= "Saludos,\n{$reserva['formulario_nombre']}";
        
        return [
            'asunto' => "{$estadoIcon} Tu reserva en {$reserva['formulario_nombre']}",
            'cuerpo_texto' => $contenidoTexto,
            'cuerpo_html' => $this->wrapHtml('Confirmación de Reserva', $contenidoHtml)
        ];
    }

    /**
     * Template: Contacto web
     */
    public function contactoWeb(string $nombre, string $emailCliente, string $asunto, string $mensaje): array {
        $fechaActual = date('d/m/Y H:i:s');
        $asuntoFormateado = !empty($asunto) ? ucfirst($asunto) : 'Consulta general';
        
        $contenidoHtml = "
            <h2 style='color: #1f2937; margin-top: 0;'>📧 Nuevo Contacto Web</h2>
            <p style='color: #6b7280;'>Se ha recibido un nuevo mensaje desde el formulario de contacto.</p>
            
            <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;'>
                <h3 style='margin-top: 0; color: #374151;'>👤 Información del contacto</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; color: #374151; width: 120px;'><strong>Nombre:</strong></td>
                        <td style='padding: 8px 0; color: #6b7280;'>{$nombre}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #374151;'><strong>Email:</strong></td>
                        <td style='padding: 8px 0; color: #6b7280;'>
                            <a href='mailto:{$emailCliente}' style='color: #667eea;'>{$emailCliente}</a>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #374151;'><strong>Asunto:</strong></td>
                        <td style='padding: 8px 0; color: #6b7280;'>{$asuntoFormateado}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #374151;'><strong>Fecha:</strong></td>
                        <td style='padding: 8px 0; color: #6b7280;'>{$fechaActual}</td>
                    </tr>
                </table>
            </div>
            
            <div style='background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #bfdbfe;'>
                <h3 style='margin-top: 0; color: #374151;'>💬 Mensaje</h3>
                <div style='color: #1e40af; line-height: 1.6; white-space: pre-wrap;'>{$mensaje}</div>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='mailto:{$emailCliente}?subject=Re: {$asuntoFormateado}' style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
                    📧 Responder
                </a>
            </div>
            
            <p style='font-size: 12px; color: #9ca3af; text-align: center; margin: 20px 0 0 0; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                Este email fue generado automáticamente desde el formulario de contacto.<br>
                <strong>No responder a este email</strong> - Usar el botón de respuesta.
            </p>";
        
        $contenidoTexto = "NUEVO CONTACTO WEB\n\n";
        $contenidoTexto .= "Información del contacto:\n";
        $contenidoTexto .= "- Nombre: {$nombre}\n";
        $contenidoTexto .= "- Email: {$emailCliente}\n";
        $contenidoTexto .= "- Asunto: {$asuntoFormateado}\n";
        $contenidoTexto .= "- Fecha: {$fechaActual}\n\n";
        $contenidoTexto .= "Mensaje:\n";
        $contenidoTexto .= "---\n{$mensaje}\n---\n\n";
        $contenidoTexto .= "Para responder, envía un email a: {$emailCliente}\n";
        
        return [
            'asunto' => 'Contacto Web - ' . $asuntoFormateado,
            'cuerpo_texto' => $contenidoTexto,
            'cuerpo_html' => $this->wrapHtml('Nuevo Contacto', $contenidoHtml)
        ];
    }
}