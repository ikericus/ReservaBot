-- =============================================
-- RESERVABOT - SETUP COMPLETO DE BASE DE DATOS
-- Versión: 4.1 - Con Sistema de Autenticación Multi-Tenancy
-- Fecha: Diciembre 2024
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
DROP TABLE IF EXISTS sesiones_usuario;
DROP TABLE IF EXISTS configuraciones_usuario;
DROP TABLE IF EXISTS origen_reservas;
DROP TABLE IF EXISTS mensajes_whatsapp;
DROP TABLE IF EXISTS autorespuestas_whatsapp;
DROP TABLE IF EXISTS usuarios_whatsapp;
DROP TABLE IF EXISTS formularios_publicos;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS configuraciones;
DROP TABLE IF EXISTS usuarios;

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
DROP VIEW IF EXISTS v_estadisticas_formularios;

-- Eliminar procedimientos si existen
DROP PROCEDURE IF EXISTS LimpiarMensajesAntiguos;
DROP PROCEDURE IF EXISTS ObtenerEstadisticasDashboard;
DROP PROCEDURE IF EXISTS ObtenerEstadisticasDashboardUsuario;
DROP PROCEDURE IF EXISTS LimpiarDatosAntiguos;
DROP PROCEDURE IF EXISTS LimpiarDatosAntiguosUsuario;
DROP PROCEDURE IF EXISTS ObtenerHorasDisponibles;
DROP PROCEDURE IF EXISTS ObtenerHorasDisponiblesUsuario;
DROP PROCEDURE IF EXISTS GenerarReporteReservas;

-- =============================================
-- CREAR TABLAS DE AUTENTICACIÓN (NUEVAS)
-- =============================================

-- Tabla de usuarios del sistema
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    telefono VARCHAR(25),
    negocio VARCHAR(255),
    password_hash VARCHAR(255) NOT NULL,
    plan ENUM('gratis', 'estandar', 'premium') DEFAULT 'gratis',
    api_key VARCHAR(64) UNIQUE,
    activo TINYINT(1) DEFAULT 1,
    intentos_login INT DEFAULT 0,
    ultimo_intento_login TIMESTAMP NULL,
    email_verificado TINYINT(1) DEFAULT 0,
    verificacion_token VARCHAR(64),
    reset_token VARCHAR(64),
    reset_token_expiry TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_activity TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_api_key (api_key),
    INDEX idx_activo (activo),
    INDEX idx_plan (plan),
    INDEX idx_last_activity (last_activity),
    INDEX idx_verificacion_token (verificacion_token),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB COMMENT='Usuarios del sistema ReservaBot';

-- Tabla de configuraciones específicas por usuario
CREATE TABLE configuraciones_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    clave VARCHAR(100) NOT NULL,
    valor TEXT NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_config (usuario_id, clave),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    INDEX idx_usuario_clave (usuario_id, clave),
    INDEX idx_updated (updated_at)
) ENGINE=InnoDB COMMENT='Configuraciones específicas por usuario';

-- Tabla de sesiones para gestión avanzada
CREATE TABLE sesiones_usuario (
    id VARCHAR(128) PRIMARY KEY,
    usuario_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB COMMENT='Gestión de sesiones de usuario';

-- =============================================
-- CREAR TABLAS PRINCIPALES (ACTUALIZADAS)
-- =============================================

-- Tabla de configuración global del sistema
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

-- Tabla principal de reservas con soporte multi-tenancy
CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
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
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado),
    INDEX idx_fecha_hora (fecha, hora),
    INDEX idx_whatsapp (whatsapp_id),
    INDEX idx_telefono (telefono),
    INDEX idx_created (created_at),
    INDEX idx_reservas_usuario_fecha (usuario_id, fecha),
    INDEX idx_reservas_usuario_estado (usuario_id, estado)
) ENGINE=InnoDB COMMENT='Reservas principales del sistema';

-- =============================================
-- TABLAS DE FORMULARIOS PÚBLICOS (ACTUALIZADAS)
-- =============================================

-- Formularios públicos con soporte multi-tenancy
CREATE TABLE formularios_publicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    slug VARCHAR(100) NOT NULL,
    confirmacion_automatica TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    mensaje_exito TEXT,
    mensaje_header TEXT,
    color_tema VARCHAR(7) DEFAULT '#3B82F6',
    mostrar_comentarios TINYINT(1) DEFAULT 1,
    mostrar_email TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_slug (usuario_id, slug),
    
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_slug (slug),
    INDEX idx_activo (activo),
    INDEX idx_created (created_at),
    INDEX idx_formularios_usuario_activo (usuario_id, activo)
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
-- TABLAS DE WHATSAPP (ACTUALIZADAS)
-- =============================================

-- Usuarios de WhatsApp con soporte multi-tenancy
CREATE TABLE usuarios_whatsapp (
    id VARCHAR(50) NOT NULL,
    usuario_id INT NOT NULL,
    nombre VARCHAR(150),
    telefono VARCHAR(25),
    avatar_url VARCHAR(500),
    is_business TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id, usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_telefono (telefono),
    INDEX idx_last_active (last_active)
) ENGINE=InnoDB COMMENT='Usuarios de WhatsApp registrados';

-- Mensajes de WhatsApp con soporte multi-tenancy
CREATE TABLE mensajes_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    chat_id VARCHAR(100) NOT NULL,
    message_id VARCHAR(255) NOT NULL,
    from_me TINYINT(1) NOT NULL DEFAULT 0,
    body TEXT,
    type ENUM('text', 'image', 'audio', 'video', 'document', 'location', 'contact', 'sticker') DEFAULT 'text',
    media_url VARCHAR(500),
    is_auto_response TINYINT(1) DEFAULT 0,
    timestamp INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_message (usuario_id, message_id),
    
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_chat_id (chat_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_from_me (from_me),
    INDEX idx_auto_response (is_auto_response),
    INDEX idx_created (created_at),
    INDEX idx_mensajes_usuario (usuario_id)
) ENGINE=InnoDB COMMENT='Historial de mensajes de WhatsApp';

-- Respuestas automáticas de WhatsApp con soporte multi-tenancy
CREATE TABLE autorespuestas_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
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
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_active (is_active),
    INDEX idx_keyword (keyword),
    INDEX idx_priority (priority),
    INDEX idx_updated (updated_at),
    INDEX idx_autorespuestas_usuario_activo (usuario_id, is_active)
) ENGINE=InnoDB COMMENT='Respuestas automáticas para WhatsApp';

-- =============================================
-- CREAR USUARIO ADMINISTRADOR POR DEFECTO
-- =============================================

-- Insertar usuario administrador
INSERT INTO usuarios (
    nombre, 
    email, 
    telefono,
    negocio,
    password_hash, 
    plan, 
    activo, 
    email_verificado, 
    api_key,
    created_at
) VALUES (
    'Administrador',
    'admin@reservabot.com',
    '+34900000000',
    'ReservaBot Admin',
    '$2y$12$LQv3c1yqBWVHxkjrjQG.ROinVIc8/6XJPb8T.Zj8s8qBsHwqQf8.W', -- demo123
    'premium',
    1,
    1,
    SHA2(CONCAT('admin@reservabot.com', NOW(), RAND()), 256),
    NOW()
);

-- Obtener ID del admin
SET @admin_id = LAST_INSERT_ID();

-- =============================================
-- INSERTAR CONFIGURACIÓN INICIAL
-- =============================================

-- Configuraciones globales del sistema
INSERT INTO configuraciones (clave, valor, descripcion) VALUES
-- Configuración básica del sistema
('app_name', 'ReservaBot', 'Nombre de la aplicación'),
('app_version', '4.1', 'Versión de la aplicación'),
('timezone', 'Europe/Madrid', 'Zona horaria del sistema'),
('date_format', 'd/m/Y', 'Formato de fecha por defecto'),
('time_format', 'H:i', 'Formato de hora por defecto'),

-- Configuración por defecto de reservas
('modo_aceptacion_default', 'manual', 'Modo de aceptación por defecto para nuevos usuarios'),
('intervalo_reservas_default', '30', 'Intervalo por defecto entre reservas en minutos'),
('dias_anticipacion_max_default', '30', 'Días máximos de anticipación por defecto'),
('dias_anticipacion_min_default', '0', 'Días mínimos de anticipación por defecto'),
('max_reservas_por_dia_default', '20', 'Máximo número de reservas por día por defecto'),

-- Horarios por defecto para nuevos usuarios
('horario_lun_default', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario por defecto de lunes'),
('horario_mar_default', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario por defecto de martes'),
('horario_mie_default', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario por defecto de miércoles'),
('horario_jue_default', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario por defecto de jueves'),
('horario_vie_default', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario por defecto de viernes'),
('horario_sab_default', 'true|[{"inicio":"10:00","fin":"14:00"}]', 'Horario por defecto de sábado'),
('horario_dom_default', 'false|[]', 'Horario por defecto de domingo'),

-- Configuración de notificaciones por email
('email_enabled', 'false', 'Activar notificaciones por email'),
('email_smtp_host', '', 'Servidor SMTP para emails'),
('email_smtp_port', '587', 'Puerto SMTP'),
('email_smtp_user', '', 'Usuario SMTP'),
('email_smtp_pass', '', 'Contraseña SMTP'),
('email_from_address', '', 'Email remitente'),
('email_from_name', 'ReservaBot', 'Nombre remitente'),

-- Configuración de WhatsApp global
('whatsapp_enabled', 'true', 'Activar integración con WhatsApp'),
('whatsapp_server_url', 'http://localhost:3000', 'URL del servidor de WhatsApp'),

-- Mensajes por defecto para nuevos usuarios
('mensaje_bienvenida_default', '¡Hola! 👋 Soy el asistente virtual.\n\n¿En qué puedo ayudarte hoy?\n\n• Escribe "reserva" para hacer una cita\n• Escribe "horarios" para ver nuestros horarios\n• Escribe "info" para más información', 'Mensaje de bienvenida por defecto'),

('mensaje_confirmacion_default', '✅ *¡Reserva Confirmada!*\n\nTu cita para el *{fecha}* a las *{hora}* ha sido confirmada.\n\n📍 Te esperamos puntualmente.\n📞 Si necesitas cambios, contáctanos con antelación.\n\n¡Gracias por elegirnos!', 'Mensaje de confirmación por defecto'),

-- Configuración de la interfaz por defecto
('theme_color_default', '#3B82F6', 'Color principal del tema por defecto'),

-- Configuración de seguridad
('session_timeout', '1440', 'Tiempo de expiración de sesión en minutos'),
('max_login_attempts', '5', 'Máximo intentos de login'),
('enable_2fa', 'false', 'Activar autenticación de dos factores'),
('backup_enabled', 'true', 'Activar respaldos automáticos'),
('backup_frequency', 'daily', 'Frecuencia de respaldos: daily, weekly, monthly');

-- =============================================
-- CONFIGURACIONES INICIALES PARA ADMIN
-- =============================================

-- Insertar configuraciones iniciales para el administrador
INSERT INTO configuraciones_usuario (usuario_id, clave, valor, descripcion) VALUES
-- Configuración básica del usuario admin
(@admin_id, 'app_name', 'ReservaBot Admin', 'Nombre de la aplicación'),
(@admin_id, 'timezone', 'Europe/Madrid', 'Zona horaria del sistema'),
(@admin_id, 'date_format', 'd/m/Y', 'Formato de fecha por defecto'),
(@admin_id, 'time_format', 'H:i', 'Formato de hora por defecto'),

-- Configuración de reservas
(@admin_id, 'modo_aceptacion', 'manual', 'Modo de aceptación: manual o automatico'),
(@admin_id, 'intervalo_reservas', '30', 'Intervalo entre reservas en minutos'),
(@admin_id, 'dias_anticipacion_max', '30', 'Días máximos de anticipación para reservas'),
(@admin_id, 'dias_anticipacion_min', '0', 'Días mínimos de anticipación para reservas'),
(@admin_id, 'max_reservas_por_dia', '20', 'Máximo número de reservas por día'),
(@admin_id, 'permitir_reservas_fines_semana', 'true', 'Permitir reservas en fines de semana'),

-- Horarios de atención con nuevo formato JSON
(@admin_id, 'horario_lun', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario de lunes'),
(@admin_id, 'horario_mar', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario de martes'),
(@admin_id, 'horario_mie', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario de miércoles'),
(@admin_id, 'horario_jue', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario de jueves'),
(@admin_id, 'horario_vie', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario de viernes'),
(@admin_id, 'horario_sab', 'true|[{"inicio":"10:00","fin":"14:00"}]', 'Horario de sábado'),
(@admin_id, 'horario_dom', 'false|[]', 'Horario de domingo'),

-- Configuración de WhatsApp
(@admin_id, 'whatsapp_enabled', 'true', 'Activar integración con WhatsApp'),
(@admin_id, 'whatsapp_server_url', 'http://localhost:3000', 'URL del servidor de WhatsApp'),
(@admin_id, 'whatsapp_api_key', '', 'Clave API del servidor WhatsApp'),
(@admin_id, 'whatsapp_status', 'disconnected', 'Estado actual de WhatsApp'),
(@admin_id, 'whatsapp_last_activity', '', 'Última actividad de WhatsApp'),
(@admin_id, 'whatsapp_webhook_url', '', 'URL del webhook para WhatsApp'),

-- Configuración de notificaciones de WhatsApp
(@admin_id, 'whatsapp_notify_nueva_reserva', 'true', 'Notificar nueva reserva por WhatsApp'),
(@admin_id, 'whatsapp_notify_confirmacion', 'true', 'Notificar confirmación por WhatsApp'),
(@admin_id, 'whatsapp_notify_recordatorio', 'true', 'Enviar recordatorios por WhatsApp'),
(@admin_id, 'whatsapp_notify_cancelacion', 'true', 'Notificar cancelaciones por WhatsApp'),
(@admin_id, 'whatsapp_tiempo_recordatorio', '24', 'Horas de antelación para recordatorios'),

-- Mensajes automáticos básicos
(@admin_id, 'mensaje_bienvenida', '¡Hola! 👋 Soy el asistente virtual de ReservaBot.\n\n¿En qué puedo ayudarte hoy?\n\n• Escribe "reserva" para hacer una cita\n• Escribe "horarios" para ver nuestros horarios\n• Escribe "info" para más información', 'Mensaje de bienvenida automático'),

(@admin_id, 'mensaje_confirmacion', '✅ *¡Reserva Confirmada!*\n\nTu cita para el *{fecha}* a las *{hora}* ha sido confirmada.\n\n📍 Te esperamos puntualmente.\n📞 Si necesitas cambios, contáctanos con antelación.\n\n¡Gracias por elegirnos!', 'Mensaje de confirmación de reserva'),

(@admin_id, 'mensaje_pendiente', '⏳ *Solicitud Recibida*\n\nHemos recibido tu solicitud de reserva para el *{fecha}* a las *{hora}*.\n\n📋 Estamos revisando la disponibilidad y te confirmaremos pronto.\n\n⏰ Tiempo estimado de respuesta: 2 horas.\n\n¡Gracias por tu paciencia!', 'Mensaje para reservas pendientes'),

-- Mensajes personalizados de WhatsApp
(@admin_id, 'whatsapp_mensaje_nueva_reserva', '📅 *Nueva Reserva Realizada*\n\nHas solicitado una reserva para:\n• Fecha: *{fecha}*\n• Hora: *{hora}*\n• Nombre: *{nombre}*\n\n⏳ Te confirmaremos la disponibilidad pronto.\n\n¡Gracias!', 'Mensaje automático al crear reserva'),

(@admin_id, 'whatsapp_mensaje_confirmacion', '✅ *¡Reserva Confirmada!*\n\nTu cita ha sido confirmada:\n• Fecha: *{fecha}*\n• Hora: *{hora}*\n\n📍 Te esperamos puntualmente.\n📱 Guarda este mensaje como comprobante.\n\n¡Nos vemos pronto!', 'Mensaje de confirmación por WhatsApp'),

(@admin_id, 'whatsapp_mensaje_recordatorio', '⏰ *Recordatorio de Cita*\n\nTe recordamos que tienes una cita:\n• Mañana *{fecha}*\n• A las *{hora}*\n\n📍 No olvides asistir puntualmente.\n📞 Si hay algún problema, contáctanos.\n\n¡Te esperamos!', 'Mensaje de recordatorio'),

(@admin_id, 'whatsapp_mensaje_cancelacion', '❌ *Reserva Cancelada*\n\nTu reserva para el *{fecha}* a las *{hora}* ha sido cancelada.\n\n📅 Puedes hacer una nueva reserva cuando gustes.\n📞 Si tienes dudas, no dudes en contactarnos.\n\n¡Gracias por entender!', 'Mensaje de cancelación'),

-- Configuración de la interfaz
(@admin_id, 'theme_color', '#3B82F6', 'Color principal del tema'),
(@admin_id, 'logo_url', '', 'URL del logo de la empresa'),
(@admin_id, 'empresa_nombre', 'ReservaBot Admin', 'Nombre de la empresa'),
(@admin_id, 'empresa_direccion', '', 'Dirección de la empresa'),
(@admin_id, 'empresa_telefono', '+34900000000', 'Teléfono de la empresa'),
(@admin_id, 'empresa_email', 'admin@reservabot.com', 'Email de la empresa'),
(@admin_id, 'empresa_web', '', 'Sitio web de la empresa');

-- =============================================
-- INSERTAR DATOS DE EJEMPLO
-- =============================================

-- Formularios públicos de ejemplo para el admin
INSERT INTO formularios_publicos (usuario_id, nombre, descripcion, slug, confirmacion_automatica, activo, mensaje_exito, mensaje_header, mostrar_email) VALUES
(@admin_id, 'Reserva General', 'Formulario principal para solicitar citas y consultas generales', 'reserva-general', 0, 1, 
'¡Gracias por tu solicitud! Hemos recibido tu información y te contactaremos pronto para confirmar tu reserva.', 
'Completa el siguiente formulario para solicitar una cita. Nos pondremos en contacto contigo para confirmar la disponibilidad.', 1),

(@admin_id, 'Reserva Rápida', 'Formulario con confirmación automática para clientes habituales', 'reserva-rapida', 1, 1, 
'¡Tu reserva ha sido confirmada automáticamente! Te esperamos el {fecha} a las {hora}.', 
'Reserva rápida con confirmación inmediata.', 0),

(@admin_id, 'Consulta Urgente', 'Formulario especial para consultas urgentes con prioridad', 'consulta-urgente', 0, 1, 
'Tu solicitud urgente ha sido recibida. Te contactaremos en las próximas 2 horas.', 
'Formulario para consultas urgentes. Respuesta garantizada en menos de 2 horas.', 1);

-- Reservas de ejemplo con fechas futuras para el admin
INSERT INTO reservas (usuario_id, nombre, telefono, whatsapp_id, email, fecha, hora, mensaje, estado) VALUES
(@admin_id, 'María García López', '+34 612 345 678', '34612345678', 'maria.garcia@email.com', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'Primera consulta. Necesito información sobre los servicios disponibles.', 'pendiente'),

(@admin_id, 'Carlos López Martín', '+34 623 456 789', '34623456789', 'carlos.lopez@email.com', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:30:00', 'Seguimiento de consulta anterior. Tengo algunas dudas específicas.', 'pendiente'),

(@admin_id, 'Ana Martínez Ruiz', '+34 634 567 890', '34634567890', 'ana.martinez@email.com', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '12:15:00', 'Consulta sobre servicios para empresa. Somos un equipo de 15 personas.', 'confirmada'),

(@admin_id, 'Javier Ruiz Sánchez', '+34 645 678 901', '34645678901', 'javier.ruiz@email.com', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '11:30:00', 'Segunda visita. Todo perfecto en la anterior cita.', 'confirmada'),

(@admin_id, 'Laura Sánchez Torres', '+34 656 789 012', '34656789012', 'laura.sanchez@email.com', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '17:00:00', 'Seguimiento trimestral. Muy satisfecha con el servicio.', 'confirmada'),

(@admin_id, 'Pedro Fernández Gil', '+34 667 890 123', '34667890123', NULL, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '09:30:00', 'Primera consulta. Vengo recomendado por María García.', 'pendiente'),

(@admin_id, 'Isabel Moreno Vega', '+34 678 901 234', '34678901234', 'isabel.moreno@email.com', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '15:45:00', 'Revisión anual. Tengo toda la documentación preparada.', 'confirmada'),

(@admin_id, 'Roberto Silva Castro', '+34 689 012 345', '34689012345', NULL, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '10:15:00', 'Consulta urgente sobre un tema muy específico.', 'pendiente'),

(@admin_id, 'Carmen Jiménez Ruiz', '+34 690 123 456', '34690123456', 'carmen.jimenez@email.com', DATE_ADD(CURDATE(), INTERVAL 8 DAY), '14:30:00', 'Consulta familiar. Necesitamos asesoramiento completo.', 'pendiente'),

(@admin_id, 'Miguel Ángel Torres', '+34 701 234 567', '34701234567', NULL, DATE_ADD(CURDATE(), INTERVAL 10 DAY), '16:00:00', 'Primera vez que vengo. Muy interesado en los servicios.', 'pendiente');

-- Registrar origen de algunas reservas de ejemplo
INSERT INTO origen_reservas (reserva_id, formulario_id, origen, ip_address) VALUES
(1, 1, 'formulario_publico', '192.168.1.100'),
(2, 1, 'formulario_publico', '192.168.1.101'),
(3, 2, 'formulario_publico', '192.168.1.102'),
(6, 3, 'formulario_publico', '192.168.1.103'),
(9, 1, 'formulario_publico', '192.168.1.104');

-- Usuarios de WhatsApp de ejemplo para el admin
INSERT INTO usuarios_whatsapp (id, usuario_id, nombre, telefono) VALUES
('default', @admin_id, 'Administrador', '+34900000000'),
('34612345678@c.us', @admin_id, 'María García López', '+34612345678'),
('34623456789@c.us', @admin_id, 'Carlos López Martín', '+34623456789'),
('34634567890@c.us', @admin_id, 'Ana Martínez Ruiz', '+34634567890'),
('34645678901@c.us', @admin_id, 'Javier Ruiz Sánchez', '+34645678901'),
('34656789012@c.us', @admin_id, 'Laura Sánchez Torres', '+34656789012');

-- Mensajes de WhatsApp de ejemplo para el admin
INSERT INTO mensajes_whatsapp (usuario_id, chat_id, message_id, from_me, body, type, is_auto_response, timestamp) VALUES
(@admin_id, '34612345678@c.us', CONCAT('msg_001_', UNIX_TIMESTAMP()), 0, 'Hola, quisiera hacer una reserva para mañana por la mañana', 'text', 0, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 2 HOUR))),
(@admin_id, '34612345678@c.us', CONCAT('msg_002_', UNIX_TIMESTAMP()), 1, '¡Hola María! 👋 Por supuesto, te ayudo con tu reserva. ¿Qué horario te viene mejor por la mañana?', 'text', 1, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 2 HOUR)) + 300),
(@admin_id, '34623456789@c.us', CONCAT('msg_003_', UNIX_TIMESTAMP()), 0, 'Buenos días, necesito cambiar mi cita de mañana', 'text', 0, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 HOUR))),
(@admin_id, '34623456789@c.us', CONCAT('msg_004_', UNIX_TIMESTAMP()), 1, 'Buenos días Carlos. Te ayudo con el cambio de cita. ¿Cuál es tu nueva disponibilidad?', 'text', 0, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 HOUR)) + 180),
(@admin_id, '34634567890@c.us', CONCAT('msg_005_', UNIX_TIMESTAMP()), 0, '¿Están abiertos hoy?', 'text', 0, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 MINUTE))),
(@admin_id, '34634567890@c.us', CONCAT('msg_006_', UNIX_TIMESTAMP()), 1, 'Sí Ana, estamos abiertos de 9:00 a 18:00. ¿En qué puedo ayudarte?', 'text', 1, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 MINUTE)) + 120);

-- Respuestas automáticas de WhatsApp de ejemplo para el admin
INSERT INTO autorespuestas_whatsapp (usuario_id, nombre, keyword, response, is_active, match_type, priority) VALUES
(@admin_id, 'Saludo General', 'hola', '¡Hola! 👋 Bienvenido a ReservaBot.\n\n¿En qué puedo ayudarte hoy?\n\n• Escribe "reserva" para hacer una cita\n• Escribe "horarios" para ver nuestros horarios\n• Escribe "info" para más información', 1, 'contains', 10),

(@admin_id, 'Información de Horarios', 'horarios', '🕐 *Nuestros horarios de atención:*\n\n📅 **Lunes a Viernes:** 9:00 - 18:00\n📅 **Sábado:** 10:00 - 14:00\n📅 **Domingo:** Cerrado\n\n¿Te gustaría hacer una reserva? Escribe "reserva" 📝', 1, 'contains', 8),

(@admin_id, 'Proceso de Reserva', 'reserva', '📅 *Para hacer una reserva necesito:*\n\n• Tu nombre completo\n• Fecha preferida\n• Hora preferida\n• Motivo de la consulta (opcional)\n\nPor favor compárteme esta información y te ayudo a confirmar tu cita. 😊', 1, 'contains', 9),

(@admin_id, 'Información General', 'info', 'ℹ️ *Información de contacto:*\n\n📧 Email: admin@reservabot.com\n📞 Teléfono: +34 900 000 000\n🌐 Web: www.reservabot.com\n📍 Dirección: Calle Principal, 123\n\n¿Necesitas algo más específico?', 1, 'contains', 7),

(@admin_id, 'Precios y Tarifas', 'precio', '💰 *Información sobre precios:*\n\nPara información detallada sobre nuestras tarifas, por favor:\n\n📞 Llámanos durante horario de oficina\n📧 Envíanos un email\n📅 Programa una cita gratuita de información\n\n¿Te gustaría programar una cita?', 1, 'contains', 6),

(@admin_id, 'Cancelaciones', 'cancelar', '❌ *Para cancelar tu reserva:*\n\nPor favor proporciona:\n• Tu nombre completo\n• Fecha y hora de tu cita\n\nTe ayudaré con la cancelación de inmediato.\n\n⚠️ Te recomendamos cancelar con al menos 24h de antelación.', 1, 'contains', 5),

(@admin_id, 'Ubicación', 'ubicacion', '📍 *Nuestra ubicación:*\n\nCalle Principal, 123\nCiudad, CP 12345\n\n🚗 Parking disponible\n🚌 Transporte público: Líneas 1, 5, 7\n🚇 Metro: Estación Central (5 min andando)\n\n¿Necesitas indicaciones específicas?', 1, 'starts_with', 4),

(@admin_id, 'Despedida', 'gracias', '😊 *¡De nada!*\n\nHa sido un placer ayudarte.\n\n📞 Si necesitas algo más, no dudes en escribirme.\n🕐 Estoy disponible durante nuestro horario de atención.\n\n¡Que tengas un excelente día! ✨', 1, 'contains', 3);

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

-- Vista completa de reservas con origen y usuario
CREATE VIEW v_reservas_completas AS
SELECT 
    r.*,
    u.nombre as usuario_nombre,
    u.negocio as usuario_negocio,
    u.plan as usuario_plan,
    COALESCE(o.origen, 'admin') as origen,
    o.formulario_id,
    f.nombre as formulario_nombre,
    f.slug as formulario_slug,
    o.ip_address,
    o.utm_source,
    o.utm_campaign
FROM reservas r
LEFT JOIN usuarios u ON r.usuario_id = u.id
LEFT JOIN origen_reservas o ON r.id = o.reserva_id
LEFT JOIN formularios_publicos f ON o.formulario_id = f.id;

-- Vista de estadísticas de mensajes por usuario
CREATE VIEW v_estadisticas_mensajes AS
SELECT 
    m.usuario_id,
    u.nombre as usuario_nombre,
    u.negocio as usuario_negocio,
    DATE(FROM_UNIXTIME(m.timestamp)) as fecha,
    CASE WHEN m.from_me = 1 THEN 'enviado' ELSE 'recibido' END as direccion,
    COUNT(*) as total_mensajes,
    COUNT(DISTINCT m.chat_id) as chats_unicos,
    SUM(CASE WHEN m.is_auto_response = 1 THEN 1 ELSE 0 END) as respuestas_automaticas,
    SUM(CASE WHEN m.type != 'text' THEN 1 ELSE 0 END) as mensajes_multimedia
FROM mensajes_whatsapp m
LEFT JOIN usuarios u ON m.usuario_id = u.id
WHERE m.timestamp > 0
GROUP BY m.usuario_id, DATE(FROM_UNIXTIME(m.timestamp)), m.from_me
ORDER BY m.usuario_id, fecha DESC, direccion;

-- Vista de estadísticas de reservas por formulario y usuario
CREATE VIEW v_estadisticas_formularios AS
SELECT 
    f.usuario_id,
    u.nombre as usuario_nombre,
    u.negocio as usuario_negocio,
    f.id as formulario_id,
    f.nombre as formulario_nombre,
    f.slug,
    f.activo,
    COUNT(o.id) as total_reservas,
    COUNT(CASE WHEN r.estado = 'confirmada' THEN 1 END) as reservas_confirmadas,
    COUNT(CASE WHEN r.estado = 'pendiente' THEN 1 END) as reservas_pendientes,
    COUNT(CASE WHEN o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as reservas_ultimo_mes,
    COUNT(CASE WHEN o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as reservas_ultima_semana
FROM formularios_publicos f
LEFT JOIN usuarios u ON f.usuario_id = u.id
LEFT JOIN origen_reservas o ON f.id = o.formulario_id
LEFT JOIN reservas r ON o.reserva_id = r.id
GROUP BY f.usuario_id, f.id, f.nombre, f.slug, f.activo, u.nombre, u.negocio;

-- =============================================
-- CREAR PROCEDIMIENTOS ALMACENADOS ÚTILES
-- =============================================

DELIMITER //

-- Procedimiento para obtener estadísticas del dashboard por usuario
CREATE PROCEDURE ObtenerEstadisticasDashboardUsuario(IN p_usuario_id INT)
BEGIN
    SELECT 
        -- Reservas del usuario
        (SELECT COUNT(*) FROM reservas WHERE usuario_id = p_usuario_id AND estado = 'pendiente') as reservas_pendientes,
        (SELECT COUNT(*) FROM reservas WHERE usuario_id = p_usuario_id AND estado = 'confirmada') as reservas_confirmadas,
        (SELECT COUNT(*) FROM reservas WHERE usuario_id = p_usuario_id AND estado = 'cancelada') as reservas_canceladas,
        (SELECT COUNT(*) FROM reservas WHERE usuario_id = p_usuario_id AND fecha = CURDATE()) as reservas_hoy,
        (SELECT COUNT(*) FROM reservas WHERE usuario_id = p_usuario_id AND fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) as reservas_esta_semana,
        (SELECT COUNT(*) FROM reservas WHERE usuario_id = p_usuario_id AND fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()) as reservas_ultimo_mes,
        
        -- WhatsApp del usuario
        (SELECT COUNT(*) FROM mensajes_whatsapp WHERE usuario_id = p_usuario_id AND timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))) as mensajes_24h,
        (SELECT COUNT(*) FROM mensajes_whatsapp WHERE usuario_id = p_usuario_id AND from_me = 0 AND timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))) as mensajes_recibidos_24h,
        (SELECT COUNT(*) FROM mensajes_whatsapp WHERE usuario_id = p_usuario_id AND from_me = 1 AND timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))) as mensajes_enviados_24h,
        (SELECT COUNT(*) FROM autorespuestas_whatsapp WHERE usuario_id = p_usuario_id AND is_active = 1) as respuestas_automaticas_activas,
        
        -- Formularios del usuario
        (SELECT COUNT(*) FROM formularios_publicos WHERE usuario_id = p_usuario_id AND activo = 1) as formularios_activos,
        (SELECT COUNT(*) FROM origen_reservas o JOIN reservas r ON o.reserva_id = r.id WHERE r.usuario_id = p_usuario_id AND o.origen = 'formulario_publico' AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as reservas_formularios_semana,
        
        -- Usuario info
        (SELECT nombre FROM usuarios WHERE id = p_usuario_id) as usuario_nombre,
        (SELECT negocio FROM usuarios WHERE id = p_usuario_id) as usuario_negocio,
        (SELECT plan FROM usuarios WHERE id = p_usuario_id) as usuario_plan;
END //

-- Procedimiento global de estadísticas (para admin)
CREATE PROCEDURE ObtenerEstadisticasDashboard()
BEGIN
    SELECT 
        -- Reservas globales
        (SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente') as reservas_pendientes,
        (SELECT COUNT(*) FROM reservas WHERE estado = 'confirmada') as reservas_confirmadas,
        (SELECT COUNT(*) FROM reservas WHERE estado = 'cancelada') as reservas_canceladas,
        (SELECT COUNT(*) FROM reservas WHERE fecha = CURDATE()) as reservas_hoy,
        (SELECT COUNT(*) FROM reservas WHERE fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) as reservas_esta_semana,
        (SELECT COUNT(*) FROM reservas WHERE fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()) as reservas_ultimo_mes,
        
        -- WhatsApp global
        (SELECT COUNT(*) FROM mensajes_whatsapp WHERE timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))) as mensajes_24h,
        (SELECT COUNT(*) FROM mensajes_whatsapp WHERE from_me = 0 AND timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))) as mensajes_recibidos_24h,
        (SELECT COUNT(*) FROM mensajes_whatsapp WHERE from_me = 1 AND timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))) as mensajes_enviados_24h,
        (SELECT COUNT(*) FROM autorespuestas_whatsapp WHERE is_active = 1) as respuestas_automaticas_activas,
        
        -- Formularios global
        (SELECT COUNT(*) FROM formularios_publicos WHERE activo = 1) as formularios_activos,
        (SELECT COUNT(*) FROM origen_reservas WHERE origen = 'formulario_publico' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as reservas_formularios_semana,
        
        -- Usuarios del sistema
        (SELECT COUNT(*) FROM usuarios WHERE activo = 1) as usuarios_activos,
        (SELECT COUNT(*) FROM usuarios WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as usuarios_activos_semana;
END //

-- Procedimiento para limpiar datos antiguos por usuario
CREATE PROCEDURE LimpiarDatosAntiguosUsuario(IN p_usuario_id INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Limpiar mensajes de WhatsApp mayores a 6 meses del usuario
    DELETE FROM mensajes_whatsapp 
    WHERE usuario_id = p_usuario_id 
    AND timestamp < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 6 MONTH));
    
    -- Limpiar reservas canceladas mayores a 1 año del usuario
    DELETE FROM reservas 
    WHERE usuario_id = p_usuario_id 
    AND estado = 'cancelada' 
    AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
    
    -- Limpiar registros de origen huérfanos
    DELETE o FROM origen_reservas o
    LEFT JOIN reservas r ON o.reserva_id = r.id
    WHERE r.id IS NULL;
    
    -- Limpiar sesiones inactivas del usuario
    DELETE FROM sesiones_usuario 
    WHERE usuario_id = p_usuario_id 
    AND last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    COMMIT;
    
    SELECT 
        'Limpieza completada para usuario' as resultado,
        p_usuario_id as usuario_id,
        NOW() as fecha_limpieza;
END //

-- Procedimiento global de limpieza (para admin)
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
    DELETE o FROM origen_reservas o
    LEFT JOIN reservas r ON o.reserva_id = r.id
    WHERE r.id IS NULL;
    
    -- Limpiar sesiones inactivas
    DELETE FROM sesiones_usuario 
    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Limpiar tokens de restablecimiento expirados
    UPDATE usuarios 
    SET reset_token = NULL, reset_token_expiry = NULL 
    WHERE reset_token_expiry < NOW();
    
    COMMIT;
    
    SELECT 
        'Limpieza global completada' as resultado,
        NOW() as fecha_limpieza;
END //

-- Procedimiento para obtener horas disponibles por usuario
CREATE PROCEDURE ObtenerHorasDisponiblesUsuario(
    IN p_usuario_id INT,
    IN fecha_consulta DATE
)
BEGIN
    DECLARE dia_semana VARCHAR(3);
    DECLARE horario_config VARCHAR(500);
    DECLARE dia_activo BOOLEAN DEFAULT FALSE;
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
    
    -- Obtener configuración del día para el usuario
    SELECT valor INTO horario_config
    FROM configuraciones_usuario 
    WHERE usuario_id = p_usuario_id AND clave = CONCAT('horario_', dia_semana);
    
    -- Si no tiene configuración específica, usar la global
    IF horario_config IS NULL THEN
        SELECT valor INTO horario_config
        FROM configuraciones 
        WHERE clave = CONCAT('horario_', dia_semana, '_default');
    END IF;
    
    -- Obtener intervalo de reservas del usuario
    SELECT CAST(valor AS UNSIGNED) INTO intervalo_minutos
    FROM configuraciones_usuario 
    WHERE usuario_id = p_usuario_id AND clave = 'intervalo_reservas';
    
    -- Si no tiene configuración específica, usar la global
    IF intervalo_minutos IS NULL THEN
        SELECT CAST(valor AS UNSIGNED) INTO intervalo_minutos
        FROM configuraciones 
        WHERE clave = 'intervalo_reservas_default';
        
        IF intervalo_minutos IS NULL THEN
            SET intervalo_minutos = 30;
        END IF;
    END IF;
    
    -- Parsear configuración de horario
    IF horario_config IS NOT NULL THEN
        SET dia_activo = SUBSTRING_INDEX(horario_config, '|', 1) = 'true';
    END IF;
    
    -- Si el día no está activo, devolver vacío
    IF NOT dia_activo THEN
        SELECT 'Día no disponible' as mensaje, dia_semana as dia, p_usuario_id as usuario_id;
    ELSE
        -- Crear tabla temporal con horas posibles
        DROP TEMPORARY TABLE IF EXISTS temp_horas_usuario;
        CREATE TEMPORARY TABLE temp_horas_usuario (
            hora TIME,
            disponible BOOLEAN DEFAULT TRUE
        );
        
        -- Generar horas básicas (simplificado - en producción parsearías el JSON)
        INSERT INTO temp_horas_usuario (hora) VALUES 
        ('09:00'), ('09:30'), ('10:00'), ('10:30'), ('11:00'), ('11:30'),
        ('12:00'), ('12:30'), ('13:00'), ('13:30'), ('14:00'), ('14:30'),
        ('15:00'), ('15:30'), ('16:00'), ('16:30'), ('17:00'), ('17:30');
        
        -- Marcar horas ocupadas para este usuario
        UPDATE temp_horas_usuario t
        SET disponible = FALSE
        WHERE EXISTS (
            SELECT 1 FROM reservas r
            WHERE r.usuario_id = p_usuario_id
            AND r.fecha = fecha_consulta 
            AND TIME_FORMAT(r.hora, '%H:%i') = TIME_FORMAT(t.hora, '%H:%i')
            AND r.estado IN ('pendiente', 'confirmada')
        );
        
        -- Si es hoy, marcar horas pasadas como no disponibles
        IF fecha_consulta = CURDATE() THEN
            UPDATE temp_horas_usuario
            SET disponible = FALSE
            WHERE hora <= CURTIME();
        END IF;
        
        -- Devolver resultados
        SELECT 
            hora,
            disponible,
            CASE WHEN disponible THEN 'Disponible' ELSE 'Ocupada' END as estado,
            p_usuario_id as usuario_id
        FROM temp_horas_usuario
        ORDER BY hora;
        
        DROP TEMPORARY TABLE temp_horas_usuario;
    END IF;
END //

-- Procedimiento para generar reporte de reservas por usuario
CREATE PROCEDURE GenerarReporteReservasUsuario(
    IN p_usuario_id INT,
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
        END as tiene_whatsapp,
        u.nombre as usuario_nombre,
        u.negocio as usuario_negocio
    FROM reservas r
    LEFT JOIN usuarios u ON r.usuario_id = u.id
    LEFT JOIN origen_reservas o ON r.id = o.reserva_id
    LEFT JOIN formularios_publicos f ON o.formulario_id = f.id
    WHERE r.usuario_id = p_usuario_id
    AND r.fecha BETWEEN fecha_inicio AND fecha_fin
    AND (estado_filtro IS NULL OR r.estado = estado_filtro)
    ORDER BY r.fecha, r.hora;
END //

-- Procedimiento para crear configuraciones iniciales para nuevo usuario
CREATE PROCEDURE CrearConfiguracionesIniciales(IN p_usuario_id INT, IN p_negocio VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Insertar configuraciones básicas
    INSERT IGNORE INTO configuraciones_usuario (usuario_id, clave, valor, descripcion) VALUES
    (p_usuario_id, 'app_name', p_negocio, 'Nombre de la aplicación'),
    (p_usuario_id, 'empresa_nombre', p_negocio, 'Nombre de la empresa'),
    (p_usuario_id, 'modo_aceptacion', 'manual', 'Modo de aceptación: manual o automatico'),
    (p_usuario_id, 'intervalo_reservas', '30', 'Intervalo entre reservas en minutos'),
    (p_usuario_id, 'horario_lun', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario de lunes'),
    (p_usuario_id, 'horario_mar', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario de martes'),
    (p_usuario_id, 'horario_mie', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario de miércoles'),
    (p_usuario_id, 'horario_jue', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario de jueves'),
    (p_usuario_id, 'horario_vie', 'true|[{"inicio":"09:00","fin":"18:00"}]', 'Horario de viernes'),
    (p_usuario_id, 'horario_sab', 'true|[{"inicio":"10:00","fin":"14:00"}]', 'Horario de sábado'),
    (p_usuario_id, 'horario_dom', 'false|[]', 'Horario de domingo'),
    (p_usuario_id, 'theme_color', '#3B82F6', 'Color principal del tema'),
    (p_usuario_id, 'whatsapp_enabled', 'true', 'Activar integración con WhatsApp');
    
    -- Crear formulario inicial
    INSERT INTO formularios_publicos (usuario_id, nombre, descripcion, slug, confirmacion_automatica, activo, mensaje_exito, mensaje_header, mostrar_email) 
    VALUES (p_usuario_id, 'Reserva General', 'Formulario principal para solicitar citas', CONCAT('reserva-', p_usuario_id), 0, 1, 
    '¡Gracias por tu solicitud! Te contactaremos pronto para confirmar tu reserva.', 
    'Completa el siguiente formulario para solicitar una cita.', 1);
    
    COMMIT;
    
    SELECT 'Configuraciones iniciales creadas' as resultado, p_usuario_id as usuario_id;
END //

DELIMITER ;

-- =============================================
-- CREAR TRIGGERS
-- =============================================

DELIMITER //

-- Trigger para auditoría de cambios en reservas por usuario
CREATE TRIGGER tr_reserva_usuario_estado_changed
AFTER UPDATE ON reservas
FOR EACH ROW
BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO configuraciones_usuario (usuario_id, clave, valor)
        VALUES (NEW.usuario_id, CONCAT('reserva_', NEW.id, '_estado_changed'), CONCAT(OLD.estado, ' -> ', NEW.estado, ' at ', NOW()))
        ON DUPLICATE KEY UPDATE valor = CONCAT(OLD.estado, ' -> ', NEW.estado, ' at ', NOW());
    END IF;
END //

-- Trigger para crear configuraciones automáticas al crear usuario
CREATE TRIGGER tr_usuario_created_config
AFTER INSERT ON usuarios
FOR EACH ROW
BEGIN
    -- Llamar al procedimiento para crear configuraciones iniciales
    CALL CrearConfiguracionesIniciales(NEW.id, NEW.negocio);
END //

DELIMITER ;

-- =============================================
-- CONFIGURACIÓN DE SEGURIDAD Y RENDIMIENTO
-- =============================================

-- Restablecer configuración de sesión
SET SESSION foreign_key_checks = 1;
SET SESSION unique_checks = 1;

-- Optimizar tablas después de la inserción de datos
OPTIMIZE TABLE usuarios;
OPTIMIZE TABLE configuraciones;
OPTIMIZE TABLE reservas;
OPTIMIZE TABLE formularios_publicos;
OPTIMIZE TABLE origen_reservas;
OPTIMIZE TABLE usuarios_whatsapp;
OPTIMIZE TABLE mensajes_whatsapp;
OPTIMIZE TABLE autorespuestas_whatsapp;