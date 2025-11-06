<?php
// domain/email/EmailTemplates.php

namespace ReservaBot\Domain\Email;

use ReservaBot\Domain\Configuracion\IConfiguracionNegocioRepository;

class EmailTemplates {
    private string $baseUrl;
    private string $appName;
    private ?IConfiguracionNegocioRepository $configuracionRepository;
    
    public function __construct(?IConfiguracionNegocioRepository $configuracionRepository = null) {
        $this->baseUrl = $_ENV['APP_URL'];
        $this->appName = $_ENV['APP_NAME'];
        $this->configuracionRepository = $configuracionRepository;
    }
    
    /**
     * Genera HTML base con header institucional (para emails de la aplicación)
     */
    private function wrapHtmlInstitucional(string $titulo, string $contenido): string {
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
     * Genera HTML base con header de negocio (personalizado por usuario/empresa)
     */
    private function wrapHtmlNegocio(string $titulo, string $contenido, int $usuarioId): string {
        $nombreNegocio = $this->appName;
        $colorPrimario = '#667eea';
        $colorSecundario = '#764ba2';
        $logoBase64 = null;
        
        if ($this->configuracionRepository) {
            try {
                $nombreNegocio = $this->configuracionRepository->obtenerValor('empresa_nombre', $usuarioId) 
                    ?? $this->appName;
                $colorPrimario = $this->configuracionRepository->obtenerValor('color_primario', $usuarioId) 
                    ?? '#667eea';
                $colorSecundario = $this->configuracionRepository->obtenerValor('color_secundario', $usuarioId) 
                    ?? '#764ba2';
                $logoBase64 = $this->configuracionRepository->obtenerValor('empresa_imagen', $usuarioId);
            } catch (\Exception $e) {
                error_log("Error obteniendo configuración: " . $e->getMessage());
            }
        }
        
        // Header con logo o texto
        $headerContent = $logoBase64 
            ? "<img src='{$logoBase64}' alt='{$nombreNegocio}' style='max-height: 64px; max-width: 200px;'>"
            : "<h1 style='color: white; margin: 0; font-size: 28px;'>{$nombreNegocio}</h1>";
        
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
                <div style='background: linear-gradient(135deg, {$colorPrimario} 0%, {$colorSecundario} 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                    {$headerContent}
                </div>
                <div style='padding: 30px 20px;'>
                    {$contenido}
                </div>
                <div style='text-align: center; padding: 20px; color: #666; font-size: 12px; border-top: 1px solid #e5e7eb;'>
                    <p>&copy; " . date('Y') . " {$nombreNegocio}. Todos los derechos reservados.</p>
                    <p style='margin: 5px 0 0 0; font-size: 11px; color: #999;'>Powered by {$this->appName}</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Genera botón HTML con color personalizable
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
     * Template: Verificación de email (INSTITUCIONAL)
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
            'cuerpo_html' => $this->wrapHtmlInstitucional('Verificar Email', $contenidoHtml)
        ];
    }
    
    
    /**
     * Template: Reset de contraseña (INSTITUCIONAL)
     */
    public function restablecimientoContrasena(string $nombre, string $token): array {
        $url = $this->baseUrl . "/password-reset?token=" . $token;
        
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
            'cuerpo_html' => $this->wrapHtmlInstitucional('Restablecer Contraseña', $contenidoHtml)
        ];
    }
    
    /**
     * Template: Bienvenida (INSTITUCIONAL)
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
            'cuerpo_html' => $this->wrapHtmlInstitucional('Bienvenido', $contenidoHtml)
        ];
    }
    
    /**
     * Template: Confirmación de reserva (NEGOCIO - Personalizado)
     */
    public function confirmacionReserva(array $reserva, ?string $gestionUrl = null): array {
        $fechaFormateada = date('d/m/Y', strtotime($reserva['fecha']));
        $horaFormateada = substr($reserva['hora'], 0, 5);
        $usuarioId = $reserva['usuario_id'];
        
        // Obtener nombre del negocio y color primario desde configuraciones
        $nombreNegocio = $this->appName;
        $colorPrimario = '#4F46E5';
        
        if ($this->configuracionRepository) {
            try {
                $nombreNegocio = $this->configuracionRepository->obtenerValor('empresa_nombre', $usuarioId) 
                    ?? $this->appName;
                $colorPrimario = $this->configuracionRepository->obtenerValor('color_primario', $usuarioId) 
                    ?? '#4F46E5';
            } catch (\Exception $e) {
                error_log("Error obteniendo configuración para confirmación de reserva: " . $e->getMessage());
            }
        }
        
        $esConfirmada = $reserva['estado'] === 'confirmada';
        $esCancelada = $reserva['estado'] === 'cancelada';
        $esRechazada = $reserva['estado'] === 'rechazada';
        
        // Determinar estado y colores
        if ($esConfirmada) {
            $estadoTexto = 'confirmada';
            $estadoIcon = '✅';
            $estadoColor = '#10b981';
        } elseif ($esCancelada) {
            $estadoTexto = 'cancelada';
            $estadoIcon = '❌';
            $estadoColor = '#6b7280';
        } elseif ($esRechazada) {
            $estadoTexto = 'rechazada';
            $estadoIcon = '❌';
            $estadoColor = '#ef4444';
        } else {
            $estadoTexto = 'pendiente de confirmación';
            $estadoIcon = '⏳';
            $estadoColor = '#f59e0b';
        }
        
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
            </div>";
        
        // Agregar botón de gestión solo si hay URL y no está cancelada/rechazada
        if ($gestionUrl && !$esCancelada && !$esRechazada) {
            $contenidoHtml .= $this->generarBoton($gestionUrl, 'Gestionar mi reserva', $colorPrimario);
        }
        
        // Banner de estado
        if ($esConfirmada) {
            $contenidoHtml .= "
            <div style='background: #d1fae5; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981; margin: 20px 0;'>
                <p style='margin: 0; color: #065f46;'><strong>✅ Reserva confirmada</strong><br>¡Te esperamos!</p>
            </div>";
        } elseif ($esCancelada) {
            $contenidoHtml .= "
            <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; border-left: 4px solid #6b7280; margin: 20px 0;'>
                <p style='margin: 0; color: #374151;'><strong>❌ Reserva cancelada</strong><br>Tu reserva ha sido cancelada.</p>
            </div>";
        } elseif ($esRechazada) {
            $contenidoHtml .= "
            <div style='background: #fee2e2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin: 20px 0;'>
                <p style='margin: 0; color: #991b1b;'><strong>❌ Reserva rechazada</strong><br>Lo sentimos, no pudimos confirmar tu reserva.</p>
            </div>";
        } else {
            $contenidoHtml .= "
            <div style='background: #fef3c7; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b; margin: 20px 0;'>
                <p style='margin: 0; color: #92400e;'><strong>⏳ Pendiente</strong><br>Te contactaremos pronto.</p>
            </div>";
        }
        
        $contenidoTexto = "Hola {$reserva['nombre']},\n\n";
        $contenidoTexto .= "Tu reserva ha sido {$estadoTexto}.\n\n";
        $contenidoTexto .= "Detalles:\n";
        $contenidoTexto .= "- Fecha: {$fechaFormateada}\n";
        $contenidoTexto .= "- Hora: {$horaFormateada}\n";
        $contenidoTexto .= "- Teléfono: {$reserva['telefono']}\n";
        if (!empty($reserva['mensaje'])) {
            $contenidoTexto .= "- Comentarios: {$reserva['mensaje']}\n";
        }
        if ($gestionUrl && !$esCancelada && !$esRechazada) {
            $contenidoTexto .= "\nGestiona tu reserva aquí: {$gestionUrl}\n";
        }
        $contenidoTexto .= "\nSaludos,\n{$nombreNegocio}";
        
        return [
            'asunto' => "{$estadoIcon} Tu reserva en {$nombreNegocio}",
            'cuerpo_texto' => $contenidoTexto,
            'cuerpo_html' => $this->wrapHtmlNegocio('Confirmación de Reserva', $contenidoHtml, $usuarioId)
        ];
    }

    /**
     * Template: Contacto web (INSTITUCIONAL)
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
            'cuerpo_html' => $this->wrapHtmlInstitucional('Nuevo Contacto', $contenidoHtml)
        ];
    }

    /**
     * Template: Alerta servidor caído (INSTITUCIONAL)
     */
    public function alertaServidorCaido(): array {
        $asunto = "Alerta: Servidor WhatsApp no saludable en {$this->appName}";

        $estado = 'desconocido';
        $timestamp = date('Y-m-d H:i:s');

        $contenidoHtml = "
            <h2 style='color: #b91c1c; margin-top: 0;'>⚠️ Servidor no saludable</h2>
            <p>Se ha detectado un problema con el servicio de WhatsApp en <strong>{$this->appName}</strong>.</p>
            <table style='border-collapse: collapse; width: 100%; margin-top: 10px;'>
                <tr><td style='padding: 6px; border: 1px solid #ddd;'>Estado</td><td style='padding: 6px; border: 1px solid #ddd;'>{$estado}</td></tr>
                <tr><td style='padding: 6px; border: 1px solid #ddd;'>Último reporte</td><td style='padding: 6px; border: 1px solid #ddd;'>{$timestamp}</td></tr>
            </table>
            <p style='margin-top: 16px;'>Por favor, revisa el estado del servidor lo antes posible.</p>
            " . $this->generarBoton($this->baseUrl . '/admin/whatsapp', 'Ver estado del servidor', '#ef4444') . "
            <p style='color: #6b7280; font-size: 14px;'>
                Este mensaje fue generado automáticamente por el monitor de {$this->appName}.
            </p>";

        $contenidoTexto  = "⚠️ Servidor no saludable en {$this->appName}\n\n";
        $contenidoTexto .= "Estado: {$estado}\n";
        $contenidoTexto .= "Último reporte: {$timestamp}\n\n";
        $contenidoTexto .= "Por favor, revisa el estado del servidor lo antes posible:\n";
        $contenidoTexto .= "{$this->baseUrl}/admin/whatsapp\n\n";
        $contenidoTexto .= "Mensaje automático del monitor de {$this->appName}.\n";

        return [
            'asunto' => $asunto,
            'cuerpo_texto' => $contenidoTexto,
            'cuerpo_html' => $this->wrapHtmlInstitucional('Servidor no saludable', $contenidoHtml)
        ];
    }

    /**
     * Template: Email de prueba de configuración (NEGOCIO - Personalizado)
     * 
     * Genera un email de ejemplo para que el usuario visualice cómo se verán
     * los emails con su configuración de colores, logo y datos del negocio
     */
    public function emailPruebaConfiguracion(string $nombreUsuario, int $usuarioId): array {
        // Obtener configuración del negocio
        $nombreNegocio = $this->appName;
        $colorPrimario = '#4F46E5';
        
        if ($this->configuracionRepository) {
            try {
                $nombreNegocio = $this->configuracionRepository->obtenerValor('empresa_nombre', $usuarioId) 
                    ?? $this->appName;
                $colorPrimario = $this->configuracionRepository->obtenerValor('color_primario', $usuarioId) 
                    ?? '#4F46E5';
            } catch (\Exception $e) {
                error_log("Error obteniendo configuración para email de prueba: " . $e->getMessage());
            }
        }
        
        // Datos ficticios de una reserva de ejemplo
        $fechaEjemplo = date('d/m/Y', strtotime('+7 days'));
        $horaEjemplo = '10:00';
        
        // Nota de prueba destacada
        $notaPrueba = "
            <div style='background: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; margin-bottom: 20px; border-radius: 8px;'>
                <p style='margin: 0; color: #1e40af; font-size: 14px;'>
                    <strong>📧 Email de Prueba</strong><br>
                    Este es un email de ejemplo para que visualices cómo se mostrarán los colores, logo y datos de tu negocio en los emails enviados a tus clientes. 
                    Los datos de la reserva son ficticios.
                </p>
            </div>";
        
        // Contenido del email usando el mismo formato que confirmacionReserva
        $contenidoHtml = $notaPrueba . "
            <h2 style='color: #1f2937; margin-top: 0;'>Hola {$nombreUsuario},</h2>
            <p>Tu reserva ha sido <strong style='color: #10b981;'>confirmada</strong>.</p>
            
            <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981;'>
                <h3 style='margin-top: 0; color: #374151;'>📅 Detalles de tu cita:</h3>
                <table style='width: 100%;'>
                    <tr><td style='padding: 8px 0; color: #374151;'><strong>Fecha:</strong></td><td style='color: #6b7280;'>{$fechaEjemplo}</td></tr>
                    <tr><td style='padding: 8px 0; color: #374151;'><strong>Hora:</strong></td><td style='color: #6b7280;'>{$horaEjemplo}</td></tr>
                    <tr><td style='padding: 8px 0; color: #374151;'><strong>Teléfono:</strong></td><td style='color: #6b7280;'>+34 600 000 000</td></tr>
                </table>
                <p style='margin-top: 15px; color: #374151;'><strong>Comentarios:</strong><br>Este es un mensaje de ejemplo para visualizar cómo se verán los emails enviados a tus clientes.</p>
            </div>";
        
        // Botón de gestión
        $gestionUrl = $this->baseUrl . '/reserva/ejemplo';
        $contenidoHtml .= $this->generarBoton($gestionUrl, 'Gestionar mi reserva', $colorPrimario);
        
        // Banner de estado confirmado
        $contenidoHtml .= "
            <div style='background: #d1fae5; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981; margin: 20px 0;'>
                <p style='margin: 0; color: #065f46;'><strong>✅ Reserva confirmada</strong><br>¡Te esperamos!</p>
            </div>";
        
        // Versión texto plano
        $contenidoTexto = "=== EMAIL DE PRUEBA ===\n\n";
        $contenidoTexto .= "Este es un email de ejemplo para visualizar cómo se mostrarán los emails a tus clientes.\n";
        $contenidoTexto .= "Los datos de la reserva son ficticios.\n\n";
        $contenidoTexto .= "======================\n\n";
        $contenidoTexto .= "Hola {$nombreUsuario},\n\n";
        $contenidoTexto .= "Tu reserva ha sido confirmada.\n\n";
        $contenidoTexto .= "Detalles:\n";
        $contenidoTexto .= "- Fecha: {$fechaEjemplo}\n";
        $contenidoTexto .= "- Hora: {$horaEjemplo}\n";
        $contenidoTexto .= "- Teléfono: +34 600 000 000\n";
        $contenidoTexto .= "- Comentarios: Este es un mensaje de ejemplo para visualizar cómo se verán los emails enviados a tus clientes.\n\n";
        $contenidoTexto .= "Gestiona tu reserva aquí: {$gestionUrl}\n\n";
        $contenidoTexto .= "Saludos,\n{$nombreNegocio}";
        
        return [
            'asunto' => "🎨 [PRUEBA] ✅ Tu reserva en {$nombreNegocio}",
            'cuerpo_texto' => $contenidoTexto,
            'cuerpo_html' => $this->wrapHtmlNegocio('Email de Prueba', $contenidoHtml, $usuarioId)
        ];
    }

}