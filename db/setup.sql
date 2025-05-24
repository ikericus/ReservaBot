-- =============================================
-- RESERVABOT - SETUP COMPLETO DE BASE DE DATOS
-- Versión: 4.0 - Esquema simplificado y optimizado
-- Fecha: Noviembre 2024
-- =============================================

-- =============================================
-- CONFIGURACIÓN INICIAL
-- =============================================

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS reservabot DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservabot;

-- Configurar variables de sesión para mejor rendimiento
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
SET SESSION foreign_key_checks = 0;
SET SESSION unique_checks = 0;

-- =============================================
-- LIMPIAR BASE DE DATOS (RESET COMPLETO)
-- =============================================

-- Eliminar todas las tablas si existen (orden importante para evitar errores de FK)
DROP TABLE IF EXISTS origen_reservas;
DROP TABLE IF EXISTS mensajes_whatsapp;
DROP TABLE IF EXISTS autorespuestas_whatsapp;
DROP TABLE IF EXISTS usuarios_whatsapp;
DROP TABLE IF EXISTS formularios_publicos;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS configuraciones;

-- Eliminar tablas del esquema anterior si existen
DROP TABLE IF EXISTS reservas_formulario;
DROP TABLE IF EXISTS formulario_preguntas;
DROP TABLE IF EXISTS chats_whatsapp;
DROP TABLE IF EXISTS sesiones_whatsapp;
DROP TABLE IF EXISTS reservas_whatsapp;
DROP TABLE IF EXISTS configuracion;
DROP TABLE IF EXISTS respuestas_automaticas;
DROP TABLE IF EXISTS backup_migration;

-- Eliminar vistas si existen
DROP VIEW IF EXISTS v_reservas_completas;
DROP VIEW IF EXISTS v_reservas_con_origen;
DROP VIEW IF EXISTS v_estadisticas_mensajes;

-- Eliminar procedimientos si existen
DROP PROCEDURE IF EXISTS LimpiarMensajesAntiguos;
DROP PROCEDURE IF EXISTS ObtenerEstadisticasDashboard;

-- =============================================
-- CREAR TABLAS PRINCIPALES
-- =============================================

-- Tabla de configuración del sistema
CREATE TABLE configuraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_clave (clave),
    INDEX idx_updated (updated_at)
) ENGINE=InnoDB COMMENT='Configuración global del sistema';

-- Tabla principal de reservas
CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(25) NOT NULL,
    whatsapp_id VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    mensaje TEXT,
    estado ENUM('pendiente', 'confirmada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    notas_internas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado),
    INDEX idx_fecha_hora (fecha, hora),
    INDEX idx_whatsapp (whatsapp_id),
    INDEX idx_telefono (telefono),
    INDEX idx_created (created_at)
) ENGINE=InnoDB COMMENT='Reservas principales del sistema';

-- =============================================
-- TABLAS DE FORMULARIOS PÚBLICOS
-- =============================================

-- Formularios públicos simplificados
CREATE TABLE formularios_publicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    slug VARCHAR(100) NOT NULL UNIQUE,
    confirmacion_automatica TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    mensaje_exito TEXT,
    mensaje_header TEXT,
    color_tema VARCHAR(7) DEFAULT '#3B82F6',
    mostrar_comentarios TINYINT(1) DEFAULT 1,
    mostrar_email TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_activo (activo),
    INDEX idx_created (created_at)
) ENGINE=InnoDB COMMENT='Formularios públicos para reservas online';

-- Tabla para rastrear origen de reservas
CREATE TABLE origen_reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    formulario_id INT NULL,
    origen ENUM('admin', 'formulario_publico', 'whatsapp', 'api') NOT NULL DEFAULT 'admin',
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer_url VARCHAR(500),
    utm_source VARCHAR(100),
    utm_campaign VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
    FOREIGN KEY (formulario_id) REFERENCES formularios_publicos(id) ON DELETE SET NULL,
    
    INDEX idx_reserva (reserva_id),
    INDEX idx_formulario (formulario_id),
    INDEX idx_origen (origen),
    INDEX idx_created (created_at)
) ENGINE=InnoDB COMMENT='Tracking del origen de las reservas';

-- =============================================
-- TABLAS DE WHATSAPP SIMPLIFICADAS
-- =============================================

-- Usuarios de WhatsApp
CREATE TABLE usuarios_whatsapp (
    id VARCHAR(50) PRIMARY KEY,
    nombre VARCHAR(150),
    telefono VARCHAR(25),
    avatar_url VARCHAR(500),
    is_business TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_telefono (telefono),
    INDEX idx_last_active (last_active)
) ENGINE=InnoDB COMMENT='Usuarios de WhatsApp registrados';

-- Mensajes de WhatsApp
CREATE TABLE mensajes_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id VARCHAR(100) NOT NULL,
    message_id VARCHAR(255) NOT NULL,
    from_me TINYINT(1) NOT NULL DEFAULT 0,
    body TEXT,
    type ENUM('text', 'image', 'audio', 'video', 'document', 'location', 'contact', 'sticker') DEFAULT 'text',
    media_url VARCHAR(500),
    is_auto_response TINYINT(1) DEFAULT 0,
    timestamp INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_message (message_id),
    INDEX idx_chat_id (chat_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_from_me (from_me),
    INDEX idx_auto_response (is_auto_response),
    INDEX idx_created (created_at)
) ENGINE=InnoDB COMMENT='Historial de mensajes de WhatsApp';

-- Respuestas automáticas de WhatsApp
CREATE TABLE autorespuestas_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    keyword VARCHAR(255) NOT NULL,
    response TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_regex TINYINT(1) DEFAULT 0,
    match_type ENUM('contains', 'exact', 'starts_with', 'ends_with', 'regex') DEFAULT 'contains',
    priority INT DEFAULT 0,
    uso_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active),
    INDEX idx_keyword (keyword),
    INDEX idx_priority (priority),
    INDEX idx_updated (updated_at)
) ENGINE=InnoDB COMMENT='Respuestas automáticas para WhatsApp';

-- =============================================
-- INSERTAR CONFIGURACIÓN INICIAL
-- =============================================

INSERT INTO configuraciones (clave, valor, descripcion) VALUES
-- Configuración básica del sistema
('app_name', 'ReservaBot', 'Nombre de la aplicación'),
('app_version', '4.0', 'Versión de la aplicación'),
('timezone', 'Europe/Madrid', 'Zona horaria del sistema'),
('date_format', 'd/m/Y', 'Formato de fecha por defecto'),
('time_format', 'H:i', 'Formato de hora por defecto'),

-- Configuración de reservas
('modo_aceptacion', 'manual', 'Modo de aceptación: manual o automatico'),
('intervalo_reservas', '30', 'Intervalo entre reservas en minutos'),
('dias_anticipacion_max', '30', 'Días máximos de anticipación para reservas'),
('dias_anticipacion_min', '0', 'Días mínimos de anticipación para reservas'),
('max_reservas_por_dia', '20', 'Máximo número de reservas por día'),
('permitir_reservas_fines_semana', 'true', 'Permitir reservas en fines de semana'),

-- Horarios de atención (formato: activo|hora_inicio|hora_fin)
('horario_lun', 'true|09:00|18:00', 'Horario de lunes'),
('horario_mar', 'true|09:00|18:00', 'Horario de martes'),
('horario_mie', 'true|09:00|18:00', 'Horario de miércoles'),
('horario_jue', 'true|09:00|18:00', 'Horario de jueves'),
('horario_vie', 'true|09:00|18:00', 'Horario de viernes'),
('horario_sab', 'true|10:00|14:00', 'Horario de sábado'),
('horario_dom', 'false|00:00|00:00', 'Horario de domingo'),

-- Configuración de notificaciones por email
('email_enabled', 'false', 'Activar notificaciones por email'),
('email_smtp_host', '', 'Servidor SMTP para emails'),
('email_smtp_port', '587', 'Puerto SMTP'),
('email_smtp_user', '', 'Usuario SMTP'),
('email_smtp_pass', '', 'Contraseña SMTP'),
('email_from_address', '', 'Email remitente'),
('email_from_name', 'ReservaBot', 'Nombre remitente'),

-- Configuración de WhatsApp
('whatsapp_enabled', 'true', 'Activar integración con WhatsApp'),
('whatsapp_server_url', 'http://localhost:3000', 'URL del servidor de WhatsApp'),
('whatsapp_api_key', '', 'Clave API del servidor WhatsApp'),
('whatsapp_status', 'disconnected', 'Estado actual de WhatsApp'),
('whatsapp_last_activity', '', 'Última actividad de WhatsApp'),
('whatsapp_webhook_url', '', 'URL del webhook para WhatsApp'),

-- Configuración de notificaciones de WhatsApp
('whatsapp_notify_nueva_reserva', 'true', 'Notificar nueva reserva por WhatsApp'),
('whatsapp_notify_confirmacion', 'true', 'Notificar confirmación por WhatsApp'),
('whatsapp_notify_recordatorio', 'true', 'Enviar recordatorios por WhatsApp'),
('whatsapp_notify_cancelacion', 'true', 'Notificar cancelaciones por WhatsApp'),
('whatsapp_tiempo_recordatorio', '24', 'Horas de antelación para recordatorios'),

-- Mensajes automáticos básicos
('mensaje_bienvenida', '¡Hola! 👋 Soy el asistente virtual de ReservaBot.\n\n¿En qué puedo ayudarte hoy?\n\n• Escribe "reserva" para hacer una cita\n• Escribe "horarios" para ver nuestros horarios\n• Escribe "info" para más información', 'Mensaje de bienvenida automático'),

('mensaje_confirmacion', '✅ *¡Reserva Confirmada!*\n\nTu cita para el *{fecha}* a las *{hora}* ha sido confirmada.\n\n📍 Te esperamos puntualmente.\n📞 Si necesitas cambios, contáctanos con antelación.\n\n¡Gracias por elegirnos!', 'Mensaje de confirmación de reserva'),

('mensaje_pendiente', '⏳ *Solicitud Recibida*\n\nHemos recibido tu solicitud de reserva para el *{fecha}* a las *{hora}*.\n\n📋 Estamos revisando la disponibilidad y te confirmaremos pronto.\n\n⏰ Tiempo estimado de respuesta: 2 horas.\n\n¡Gracias por tu paciencia!', 'Mensaje para reservas pendientes'),

-- Mensajes personalizados de WhatsApp
('whatsapp_mensaje_nueva_reserva', '📅 *Nueva Reserva Realizada*\n\nHas solicitado una reserva para:\n• Fecha: *{fecha}*\n• Hora: *{hora}*\n• Nombre: *{nombre}*\n\n⏳ Te confirmaremos la disponibilidad pronto.\n\n¡Gracias!', 'Mensaje automático al crear reserva'),

('whatsapp_mensaje_confirmacion', '✅ *¡Reserva Confirmada!*\n\nTu cita ha sido confirmada:\n• Fecha: *{fecha}*\n• Hora: *{hora}*\n\n📍 Te esperamos puntualmente.\n📱 Guarda este mensaje como comprobante.\n\n¡Nos vemos pronto!', 'Mensaje de confirmación por WhatsApp'),

('whatsapp_mensaje_recordatorio', '⏰ *Recordatorio de Cita*\n\nTe recordamos que tienes una cita:\n• Mañana *{fecha}*\n• A las *{hora}*\n\n📍 No olvides asistir puntualmente.\n📞 Si hay algún problema, contáctanos.\n\n¡Te esperamos!', 'Mensaje de recordatorio'),

('whatsapp_mensaje_cancelacion', '❌ *Reserva Cancelada*\n\nTu reserva para el *{fecha}* a las *{hora}* ha sido cancelada.\n\n📅 Puedes hacer una nueva reserva cuando gustes.\n📞 Si tienes dudas, no dudes en contactarnos.\n\n¡Gracias por entender!', 'Mensaje de cancelación'),

-- Configuración de la interfaz
('theme_color', '#3B82F6', 'Color principal del tema'),
('logo_url', '', 'URL del logo de la empresa'),
('empresa_nombre', 'Mi Empresa', 'Nombre de la empresa'),
('empresa_direccion', '', 'Dirección de la empresa'),
('empresa_telefono', '', 'Teléfono de la empresa'),
('empresa_email', '', 'Email de la empresa'),
('empresa_web', '', 'Sitio web de la empresa'),

-- Configuración de seguridad
('session_timeout', '1440', 'Tiempo de expiración de sesión en minutos'),
('max_login_attempts', '5', 'Máximo intentos de login'),
('enable_2fa', 'false', 'Activar autenticación de dos factores'),
('backup_enabled', 'true', 'Activar respaldos automáticos'),
('backup_frequency', 'daily', 'Frecuencia de respaldos: daily, weekly, monthly');

-- =============================================
-- INSERTAR DATOS DE EJEMPLO
-- =============================================

-- Formularios públicos de ejemplo
INSERT INTO formularios_publicos (nombre, descripcion, slug, confirmacion_automatica, activo, mensaje_exito, mensaje_header, mostrar_email) VALUES
('Reserva General', 'Formulario principal para solicitar citas y consultas generales', 'reserva-general', 0, 1, 
'¡Gracias por tu solicitud! Hemos recibido tu información y te contactaremos pronto para confirmar tu reserva.', 
'Completa el siguiente formulario para solicitar una cita. Nos pondremos en contacto contigo para confirmar la disponibilidad.', 1),

('Reserva Rápida', 'Formulario con confirmación automática para clientes habituales', 'reserva-rapida', 1, 1, 
'¡Tu reserva ha sido confirmada automáticamente! Te esperamos el {fecha} a las {hora}.', 
'Reserva rápida con confirmación inmediata.', 0),

('Consulta Urgente', 'Formulario especial para consultas urgentes con prioridad', 'consulta-urgente', 0, 1, 
'Tu solicitud urgente ha sido recibida. Te contactaremos en las próximas 2 horas.', 
'Formulario para consultas urgentes. Respuesta garantizada en menos de 2 horas.', 1);

-- Reservas de ejemplo con fechas futuras
INSERT INTO reservas (nombre, telefono, whatsapp_id, email, fecha, hora, mensaje, estado) VALUES
('María García López', '+34 612 345 678', '34612345678', 'maria.garcia@email.com', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'Primera consulta. Necesito información sobre los servicios disponibles.', 'pendiente'),

('Carlos López Martín', '+34 623 456 789', '34623456789', 'carlos.lopez@email.com', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:30:00', 'Seguimiento de consulta anterior. Tengo algunas dudas específicas.', 'pendiente'),

('Ana Martínez Ruiz', '+34 634 567 890', '34634567890', 'ana.martinez@email.com', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '12:15:00', 'Consulta sobre servicios para empresa. Somos un equipo de 15 personas.', 'confirmada'),

('Javier Ruiz Sánchez', '+34 645 678 901', '34645678901', 'javier.ruiz@email.com', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '11:30:00', 'Segunda visita. Todo perfecto en la anterior cita.', 'confirmada'),

('Laura Sánchez Torres', '+34 656 789 012', '34656789012', 'laura.sanchez@email.com', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '17:00:00', 'Seguimiento trimestral. Muy satisfecha con el servicio.', 'confirmada'),

('Pedro Fernández Gil', '+34 667 890 123', '34667890123', NULL, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '09:30:00', 'Primera consulta. Vengo recomendado por María García.', 'pendiente'),

('Isabel Moreno Vega', '+34 678 901 234', '34678901234', 'isabel.moreno@email.com', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '15:45:00', 'Revisión anual. Tengo toda la documentación preparada.', 'confirmada'),

('Roberto Silva Castro', '+34 689 012 345', '34689012345', NULL, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '10:15:00', 'Consulta urgente sobre un tema muy específico.', 'pendiente'),

('Carmen Jiménez Ruiz', '+34 690 123 456', '34690123456', 'carmen.jimenez@email.com', DATE_ADD(CURDATE(), INTERVAL 8 DAY), '14:30:00', 'Consulta familiar. Necesitamos asesoramiento completo.', 'pendiente'),

('Miguel Ángel Torres', '+34 701 234 567', '34701234567', NULL, DATE_ADD(CURDATE(), INTERVAL 10 DAY), '16:00:00', 'Primera vez que vengo. Muy interesado en los servicios.', 'pendiente');

-- Registrar origen de algunas reservas de ejemplo
INSERT INTO origen_reservas (reserva_id, formulario_id, origen, ip_address) VALUES
(1, 1, 'formulario_publico', '192.168.1.100'),
(2, 1, 'formulario_publico', '192.168.1.101'),
(3, 2, 'formulario_publico', '192.168.1.102'),
(6, 3, 'formulario_publico', '192.168.1.103'),
(9, 1, 'formulario_publico', '192.168.1.104');

-- Usuarios de WhatsApp de ejemplo
INSERT INTO usuarios_whatsapp (id, nombre, telefono) VALUES
('default', 'Administrador', '+34600000000'),
('34612345678@c.us', 'María García López', '+34612345678'),
('34623456789@c.us', 'Carlos López Martín', '+34623456789'),
('34634567890@c.us', 'Ana Martínez Ruiz', '+34634567890'),
('34645678901@c.us', 'Javier Ruiz Sánchez', '+34645678901'),
('34656789012@c.us', 'Laura Sánchez Torres', '+34656789012');

-- Mensajes de WhatsApp de ejemplo
INSERT INTO mensajes_whatsapp (chat_id, message_id, from_me, body, type, is_auto_response, timestamp) VALUES
('34612345678@c.us', 'msg_001_' || UNIX_TIMESTAMP(), 0, 'Hola, quisiera hacer una reserva para mañana por la mañana', 'text', 0, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 2 HOUR))),
('34612345678@c.us', 'msg_002_' || UNIX_TIMESTAMP(), 1, '¡Hola María! 👋 Por supuesto, te ayudo con tu reserva. ¿Qué horario te viene mejor por la mañana?', 'text', 1, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 2 HOUR)) + 300),
('34623456789@c.us', 'msg_003_' || UNIX_TIMESTAMP(), 0, 'Buenos días, necesito cambiar mi cita de mañana', 'text', 0, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 HOUR))),
('34623456789@c.us', 'msg_004_' || UNIX_TIMESTAMP(), 1, 'Buenos días Carlos. Te ayudo con el cambio de cita. ¿Cuál es tu nueva disponibilidad?', 'text', 0, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 HOUR)) + 180),
('34634567890@c.us', 'msg_005_' || UNIX_TIMESTAMP(), 0, '¿Están abiertos hoy?', 'text', 0, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 MINUTE))),
('34634567890@c.us', 'msg_006_' || UNIX_TIMESTAMP(), 1, 'Sí Ana, estamos abiertos de 9:00 a 18:00. ¿En qué puedo ayudarte?', 'text', 1, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 MINUTE)) + 120);

-- Respuestas automáticas de WhatsApp de ejemplo
INSERT INTO autorespuestas_whatsapp (nombre, keyword, response, is_active, match_type, priority) VALUES
('Saludo General', 'hola', '¡Hola! 👋 Bienvenido a ReservaBot.\n\n¿En qué puedo ayudarte hoy?\n\n• Escribe "reserva" para hacer una cita\n• Escribe "horarios" para ver nuestros horarios\n• Escribe "info" para más información', 1, 'contains', 10),

('Información de Horarios', 'horarios', '🕐 *Nuestros horarios de atención:*\n\n📅 **Lunes a Viernes:** 9:00 - 18:00\n📅 **Sábado:** 10:00 - 14:00\n📅 **Domingo:** Cerrado\n\n¿Te gustaría hacer una reserva? Escribe "reserva" 📝', 1, 'contains', 8),

('Proceso de Reserva', 'reserva', '📅 *Para hacer una reserva necesito:*\n\n• Tu nombre completo\n• Fecha preferida\n• Hora preferida\n• Motivo de la consulta (opcional)\n\nPor favor compárteme esta información y te ayudo a confirmar tu cita. 😊', 1, 'contains', 9),

('Información General', 'info', 'ℹ️ *Información de contacto:*\n\n📧 Email: info@empresa.com\n📞 Teléfono: +34 900 000 000\n🌐 Web: www.empresa.com\n📍 Dirección: Calle Principal, 123\n\n¿Necesitas algo más específico?', 1, 'contains', 7),

('Precios y Tarifas', 'precio', '💰 *Información sobre precios:*\n\nPara información detallada sobre nuestras tarifas, por favor:\n\n📞 Llámanos durante horario de oficina\n📧 Envíanos un email\n📅 Programa una cita gratuita de información\n\n¿Te gustaría programar una cita?', 1, 'contains', 6),

('Cancelaciones', 'cancelar', '❌ *Para cancelar tu reserva:*\n\nPor favor proporciona:\n• Tu nombre completo\n• Fecha y hora de tu cita\n\nTe ayudaré con la cancelación de inmediato.\n\n⚠️ Te recomendamos cancelar con al menos 24h de antelación.', 1, 'contains', 5),

('Ubicación', 'ubicacion', '📍 *Nuestra ubicación:*\n\nCalle Principal, 123\nCiudad, CP 12345\n\n🚗 Parking disponible\n🚌 Transporte público: Líneas 1, 5, 7\n🚇 Metro: Estación Central (5 min andando)\n\n¿Necesitas indicaciones específicas?', 1, 'starts_with', 4),

('Despedida', 'gracias', '😊 *¡De nada!*\n\nHa sido un placer ayudarte.\n\n📞 Si necesitas algo más, no dudes en escribirme.\n🕐 Estoy disponible durante nuestro horario de atención.\n\n¡Que tengas un excelente día! ✨', 1, 'contains', 3);

-- =============================================
-- CREAR ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =============================================

-- Índices compuestos para consultas comunes
CREATE INDEX idx_reservas_fecha_estado ON reservas (fecha, estado);
CREATE INDEX idx_reservas_estado_fecha ON reservas (estado, fecha);
CREATE INDEX idx_mensajes_chat_timestamp ON mensajes_whatsapp (chat_id, timestamp DESC);
CREATE INDEX idx_autorespuestas_active_priority ON autorespuestas_whatsapp (is_active, priority DESC);
CREATE INDEX idx_origen_formulario_fecha ON origen_reservas (formulario_id, created_at);

-- =============================================
-- CREAR VISTAS ÚTILES
-- =============================================

-- Vista completa de reservas con origen
CREATE VIEW v_reservas_completas AS
SELECT 
    r.*,
    COALESCE(o.origen, 'admin') as origen,
    o.formulario_id,
    f.nombre as formulario_nombre,
    f.slug as formulario_slug,
    o.ip_address,
    o.utm_source,
    o.utm_campaign
FROM reservas r
LEFT JOIN origen_reservas o ON r.id = o.reserva_id
LEFT JOIN formularios_publicos f ON o.formulario_id = f.id;

-- Vista de estadísticas de mensajes
CREATE VIEW v_estadisticas_mensajes AS
SELECT 
    DATE(FROM_UNIXTIME(timestamp)) as fecha,
    CASE WHEN from_me = 1 THEN 'enviado' ELSE 'recibido' END as direccion,
    COUNT(*) as total_mensajes,
    COUNT(DISTINCT chat_id) as chats_unicos,
    SUM(CASE WHEN is_auto_response = 1 THEN 1 ELSE 0 END) as respuestas_automaticas,
    SUM(CASE WHEN type != 'text' THEN 1 ELSE 0 END) as mensajes_multimedia
FROM mensajes_whatsapp
WHERE timestamp > 0
GROUP BY DATE(FROM_UNIXTIME(timestamp)), from_me
ORDER BY fecha DESC, direccion;

-- Vista de estadísticas de reservas por formulario
CREATE VIEW v_estadisticas_formularios AS
SELECT 
    f.id,
    f.nombre,
    f.slug,
    f.activo,
    COUNT(o.id) as total_reservas,
    COUNT(CASE WHEN r.estado = 'confirmada' THEN 1 END) as reservas_confirmadas,
    COUNT(CASE WHEN r.estado = 'pendiente' THEN 1 END) as reservas_pendientes,
    COUNT(CASE WHEN o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as reservas_ultimo_mes,
    COUNT(CASE WHEN o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as reservas_ultima_semana
FROM formularios_publicos f
LEFT JOIN origen_reservas o ON f.id = o.formulario_id
LEFT JOIN reservas r ON o.reserva_id = r.id
GROUP BY f.id, f.nombre, f.slug, f.activo;

-- =============================================
-- CREAR PROCEDIMIENTOS ALMACENADOS ÚTILES
-- =============================================

DELIMITER //

-- Procedimiento para obtener estadísticas del dashboard
CREATE PROCEDURE ObtenerEstadisticasDashboard()
BEGIN
    SELECT 
        -- Reservas
        (SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente') as reservas_pendientes,
        (SELECT COUNT(*) FROM reservas WHERE estado = 'confirmada') as reservas_confirmadas,
        (SELECT COUNT(*) FROM reservas WHERE estado = 'cancelada') as reservas_canceladas,
        (SELECT COUNT(*) FROM reservas WHERE fecha = CURDATE()) as reservas_hoy,
        (SELECT COUNT(*) FROM reservas WHERE fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) as reservas_esta_semana,
        (SELECT COUNT(*) FROM reservas WHERE fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()) as reservas_ultimo_mes,
        
        -- WhatsApp
        (SELECT COUNT(*) FROM mensajes_whatsapp WHERE timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))) as mensajes_24h,
        (SELECT COUNT(*) FROM mensajes_whatsapp WHERE from_me = 0 AND timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))) as mensajes_recibidos_24h,
        (SELECT COUNT(*) FROM mensajes_whatsapp WHERE from_me = 1 AND timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))) as mensajes_enviados_24h,
        (SELECT COUNT(*) FROM autorespuestas_whatsapp WHERE is_active = 1) as respuestas_automaticas_activas,
        
        -- Formularios
        (SELECT COUNT(*) FROM formularios_publicos WHERE activo = 1) as formularios_activos,
        (SELECT COUNT(*) FROM origen_reservas WHERE origen = 'formulario_publico' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as reservas_formularios_semana,
        
        -- Sistema
        (SELECT valor FROM configuraciones WHERE clave = 'whatsapp_status') as whatsapp_status,
        (SELECT COUNT(DISTINCT chat_id) FROM mensajes_whatsapp WHERE timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))) as chats_activos_semana;
END //

-- Procedimiento para limpiar datos antiguos
CREATE PROCEDURE LimpiarDatosAntiguos()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Limpiar mensajes de WhatsApp mayores a 6 meses
    DELETE FROM mensajes_whatsapp 
    WHERE timestamp < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 6 MONTH));
    
    -- Limpiar reservas canceladas mayores a 1 año
    DELETE FROM reservas 
    WHERE estado = 'cancelada' 
    AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
    
    -- Limpiar registros de origen huérfanos
    DELETE FROM origen_reservas 
    WHERE reserva_id NOT IN (SELECT id FROM reservas);
    
    COMMIT;
    
    SELECT 
        'Limpieza completada' as resultado,
        NOW() as fecha_limpieza;
END //

-- Procedimiento para obtener horas disponibles de una fecha
CREATE PROCEDURE ObtenerHorasDisponibles(IN fecha_consulta DATE)
BEGIN
    DECLARE dia_semana VARCHAR(3);
    DECLARE horario_config VARCHAR(50);
    DECLARE dia_activo BOOLEAN DEFAULT FALSE;
    DECLARE hora_inicio TIME;
    DECLARE hora_fin TIME;
    DECLARE intervalo_minutos INT DEFAULT 30;
    
    -- Obtener día de la semana
    SET dia_semana = CASE DAYOFWEEK(fecha_consulta)
        WHEN 1 THEN 'dom'
        WHEN 2 THEN 'lun'
        WHEN 3 THEN 'mar'
        WHEN 4 THEN 'mie'
        WHEN 5 THEN 'jue'
        WHEN 6 THEN 'vie'
        WHEN 7 THEN 'sab'
    END;
    
    -- Obtener configuración del día
    SELECT valor INTO horario_config
    FROM configuraciones 
    WHERE clave = CONCAT('horario_', dia_semana);
    
    -- Obtener intervalo de reservas
    SELECT CAST(valor AS UNSIGNED) INTO intervalo_minutos
    FROM configuraciones 
    WHERE clave = 'intervalo_reservas';
    
    -- Parsear configuración de horario
    IF horario_config IS NOT NULL THEN
        SET dia_activo = SUBSTRING_INDEX(horario_config, '|', 1) = 'true';
        SET hora_inicio = STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(horario_config, '|', 2), '|', -1), '%H:%i');
        SET hora_fin = STR_TO_DATE(SUBSTRING_INDEX(horario_config, '|', -1), '%H:%i');
    END IF;
    
    -- Si el día no está activo, devolver vacío
    IF NOT dia_activo THEN
        SELECT 'Día no disponible' as mensaje, dia_semana as dia;
    ELSE
        -- Crear tabla temporal con todas las horas posibles
        DROP TEMPORARY TABLE IF EXISTS temp_horas;
        CREATE TEMPORARY TABLE temp_horas (
            hora TIME,
            disponible BOOLEAN DEFAULT TRUE
        );
        
        -- Generar horas disponibles
        SET @hora_actual = hora_inicio;
        WHILE @hora_actual < hora_fin DO
            INSERT INTO temp_horas (hora) VALUES (@hora_actual);
            SET @hora_actual = ADDTIME(@hora_actual, SEC_TO_TIME(intervalo_minutos * 60));
        END WHILE;
        
        -- Marcar horas ocupadas
        UPDATE temp_horas t
        SET disponible = FALSE
        WHERE EXISTS (
            SELECT 1 FROM reservas r
            WHERE r.fecha = fecha_consulta 
            AND r.hora = t.hora
            AND r.estado IN ('pendiente', 'confirmada')
        );
        
        -- Si es hoy, marcar horas pasadas como no disponibles
        IF fecha_consulta = CURDATE() THEN
            UPDATE temp_horas
            SET disponible = FALSE
            WHERE hora <= CURTIME();
        END IF;
        
        -- Devolver resultados
        SELECT 
            hora,
            disponible,
            CASE WHEN disponible THEN 'Disponible' ELSE 'Ocupada' END as estado
        FROM temp_horas
        ORDER BY hora;
        
        DROP TEMPORARY TABLE temp_horas;
    END IF;
END //

-- Procedimiento para generar reporte de reservas
CREATE PROCEDURE GenerarReporteReservas(
    IN fecha_inicio DATE,
    IN fecha_fin DATE,
    IN estado_filtro VARCHAR(20)
)
BEGIN
    SELECT 
        r.id,
        r.nombre,
        r.telefono,
        r.email,
        r.fecha,
        r.hora,
        r.estado,
        r.mensaje,
        r.created_at,
        COALESCE(o.origen, 'admin') as origen,
        f.nombre as formulario_nombre,
        CASE 
            WHEN r.whatsapp_id IS NOT NULL THEN 'Sí'
            ELSE 'No'
        END as tiene_whatsapp
    FROM reservas r
    LEFT JOIN origen_reservas o ON r.id = o.reserva_id
    LEFT JOIN formularios_publicos f ON o.formulario_id = f.id
    WHERE r.fecha BETWEEN fecha_inicio AND fecha_fin
    AND (estado_filtro IS NULL OR r.estado = estado_filtro)
    ORDER BY r.fecha, r.hora;
END //

DELIMITER ;

-- =============================================
-- CREAR TRIGGERS PARA AUDITORÍA
-- =============================================

DELIMITER //

-- Trigger para actualizar contador de uso en autorespuestas
CREATE TRIGGER tr_autorespuesta_usado
AFTER INSERT ON mensajes_whatsapp
FOR EACH ROW
BEGIN
    IF NEW.is_auto_response = 1 THEN
        -- Aquí podrías implementar lógica para rastrear qué autorespuesta se usó
        -- Por simplicidad, no implementamos esto en el MVP
        INSERT INTO configuraciones (clave, valor) 
        VALUES ('last_autoresponse_used', UNIX_TIMESTAMP())
        ON DUPLICATE KEY UPDATE valor = UNIX_TIMESTAMP();
    END IF;
END //

-- Trigger para logging de cambios en reservas importantes
CREATE TRIGGER tr_reserva_estado_changed
AFTER UPDATE ON reservas
FOR EACH ROW
BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO configuraciones (clave, valor)
        VALUES (CONCAT('reserva_', NEW.id, '_estado_changed'), CONCAT(OLD.estado, ' -> ', NEW.estado, ' at ', NOW()))
        ON DUPLICATE KEY UPDATE valor = CONCAT(OLD.estado, ' -> ', NEW.estado, ' at ', NOW());
    END IF;
END //

DELIMITER ;

-- =============================================
-- CONFIGURACIÓN DE SEGURIDAD Y RENDIMIENTO
-- =============================================

-- Restablecer configuración de sesión
SET SESSION foreign_key_checks = 1;
SET SESSION unique_checks = 1;

-- Optimizar tablas después de la inserción de datos
OPTIMIZE TABLE configuraciones;
OPTIMIZE TABLE reservas;
OPTIMIZE TABLE formularios_publicos;
OPTIMIZE TABLE origen_reservas;
OPTIMIZE TABLE usuarios_whatsapp;
OPTIMIZE TABLE mensajes_whatsapp;
OPTIMIZE TABLE autorespuestas_whatsapp;

-- =============================================
-- VERIFICACIÓN FINAL E INFORMACIÓN
-- =============================================

-- Mostrar resumen de la instalación
SELECT 'INSTALACIÓN COMPLETADA EXITOSAMENTE' as resultado;

SELECT 'RESUMEN DE DATOS INSTALADOS' as seccion;
SELECT 
    'configuraciones' as tabla, 
    COUNT(*) as registros,
    'Configuración del sistema' as descripcion
FROM configuraciones
UNION ALL
SELECT 
    'reservas' as tabla, 
    COUNT(*) as registros,
    'Reservas de ejemplo con fechas futuras' as descripcion
FROM reservas
UNION ALL
SELECT 
    'formularios_publicos' as tabla, 
    COUNT(*) as registros,
    'Formularios públicos de ejemplo' as descripcion
FROM formularios_publicos
UNION ALL
SELECT 
    'autorespuestas_whatsapp' as tabla, 
    COUNT(*) as registros,
    'Respuestas automáticas configuradas' as descripcion
FROM autorespuestas_whatsapp
UNION ALL
SELECT 
    'mensajes_whatsapp' as tabla, 
    COUNT(*) as registros,
    'Mensajes de ejemplo' as descripcion
FROM mensajes_whatsapp
UNION ALL
SELECT 
    'usuarios_whatsapp' as tabla, 
    COUNT(*) as registros,
    'Usuarios de WhatsApp' as descripcion
FROM usuarios_whatsapp;

-- Mostrar formularios creados
SELECT 'FORMULARIOS PÚBLICOS CREADOS' as seccion;
SELECT 
    id,
    nombre,
    slug,
    CONCAT('http://tu-dominio.com/reservar.php?f=', slug) as url_publica,
    CASE WHEN confirmacion_automatica = 1 THEN 'Automática' ELSE 'Manual' END as confirmacion
FROM formularios_publicos
WHERE activo = 1;

-- Mostrar próximas reservas
SELECT 'PRÓXIMAS RESERVAS DE EJEMPLO' as seccion;
SELECT 
    nombre,
    fecha,
    TIME_FORMAT(hora, '%H:%i') as hora,
    estado,
    CASE WHEN fecha = CURDATE() THEN 'HOY'
         WHEN fecha = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'MAÑANA'
         ELSE CONCAT('En ', DATEDIFF(fecha, CURDATE()), ' días')
    END as cuando
FROM reservas
WHERE fecha >= CURDATE()
ORDER BY fecha, hora
LIMIT 5;

-- Información importante
SELECT 'INFORMACIÓN IMPORTANTE' as seccion;
SELECT 
    'CREDENCIALES' as tipo,
    'Usuario admin por defecto no configurado - Configurar en includes/auth.php' as detalle
UNION ALL
SELECT 
    'WHATSAPP',
    'Servidor Node.js configurado en http://localhost:3000' as detalle
UNION ALL
SELECT 
    'FORMULARIOS',
    'URLs públicas generadas automáticamente' as detalle
UNION ALL
SELECT 
    'HORARIOS',
    'Lun-Vie: 9:00-18:00, Sáb: 10:00-14:00, Dom: Cerrado' as detalle
UNION ALL
SELECT 
    'BACKUP',
    'Configurar respaldos automáticos en producción' as detalle;

-- Verificación de integridad
SELECT 'VERIFICACIÓN DE INTEGRIDAD' as seccion;
SELECT 
    'Reservas futuras' as check_type,
    COUNT(*) as cantidad,
    'OK' as estado
FROM reservas 
WHERE fecha >= CURDATE()
UNION ALL
SELECT 
    'Configuraciones cargadas',
    COUNT(*) as cantidad,
    CASE WHEN COUNT(*) > 50 THEN 'OK' ELSE 'REVISAR' END as estado
FROM configuraciones
UNION ALL
SELECT 
    'Autorespuestas activas',
    COUNT(*) as cantidad,
    CASE WHEN COUNT(*) > 0 THEN 'OK' ELSE 'REVISAR' END as estado
FROM autorespuestas_whatsapp
WHERE is_active = 1;

COMMIT;

-- =============================================
-- INSTRUCCIONES POST-INSTALACIÓN
-- =============================================

/*
🎉 ¡INSTALACIÓN COMPLETADA!

📋 PRÓXIMOS PASOS:

1. CONFIGURAR ACCESO:
   - Configurar autenticación en includes/auth.php
   - Cambiar credenciales por defecto
   - Configurar SSL en producción

2. PERSONALIZAR EMPRESA:
   - Actualizar datos en tabla 'configuraciones'
   - Subir logo de la empresa
   - Configurar colores del tema

3. CONFIGURAR WHATSAPP:
   - Instalar servidor Node.js de WhatsApp
   - Configurar webhook en api/whatsapp-webhook.php
   - Probar respuestas automáticas

4. CONFIGURAR EMAIL (OPCIONAL):
   - Configurar SMTP en tabla 'configuraciones'
   - Probar envío de notificaciones

5. PERSONALIZAR FORMULARIOS:
   - Editar formularios en /formularios.php
   - Personalizar mensajes de confirmación
   - Generar códigos QR para compartir

6. CONFIGURAR RESPALDOS:
   - Configurar backups automáticos
   - Programar procedimiento LimpiarDatosAntiguos()

7. TESTING:
   - Probar reservas desde admin
   - Probar formularios públicos
   - Verificar notificaciones WhatsApp
   - Probar respuestas automáticas

📂 ARCHIVOS IMPORTANTES:
- /includes/db-config.php (configuración BD)
- /includes/auth.php (autenticación)
- /api/ (endpoints API)
- /reservar.php (formularios públicos)

🔗 URLs DE FORMULARIOS PÚBLICOS:
- General: /reservar.php?f=reserva-general
- Rápida: /reservar.php?f=reserva-rapida  
- Urgente: /reservar.php?f=consulta-urgente

🛡️ SEGURIDAD:
- Cambiar todas las contraseñas por defecto
- Configurar HTTPS en producción
- Revisar permisos de archivos
- Configurar firewall de BD

📈 MONITOREO:
- Usar procedimiento ObtenerEstadisticasDashboard()
- Revisar logs de PHP regularmente
- Monitorear uso de WhatsApp API

¡ReservaBot está listo para usar! 🚀
*/