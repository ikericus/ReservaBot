-- =============================================
-- RESERVABOT - CONFIGURACIÓN COMPLETA DE BASE DE DATOS
-- Versión: 2.0 - Corregida y actualizada
-- =============================================

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS reservabot DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservabot;

-- Eliminar tablas existentes para empezar desde cero (comentar si no se desea)
-- DROP TABLE IF EXISTS reservas_formulario;
-- DROP TABLE IF EXISTS formulario_preguntas;
-- DROP TABLE IF EXISTS formularios_publicos;
-- DROP TABLE IF EXISTS reservas_whatsapp;
-- DROP TABLE IF EXISTS mensajes_whatsapp;
-- DROP TABLE IF EXISTS chats_whatsapp;
-- DROP TABLE IF EXISTS sesiones_whatsapp;
-- DROP TABLE IF EXISTS usuarios_whatsapp;
-- DROP TABLE IF EXISTS autorespuestas_whatsapp;
-- DROP TABLE IF EXISTS respuestas_automaticas;
-- DROP TABLE IF EXISTS reservas;
-- DROP TABLE IF EXISTS configuraciones;
-- DROP TABLE IF EXISTS configuracion;

-- =============================================
-- TABLAS PRINCIPALES
-- =============================================

-- Tabla de reservas principal
CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    whatsapp_id VARCHAR(100) NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    mensaje TEXT,
    estado ENUM('pendiente', 'confirmada') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado),
    INDEX idx_whatsapp (whatsapp_id)
) ENGINE=InnoDB;

-- Tabla de configuración (corregida - con 's')
CREATE TABLE IF NOT EXISTS configuraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clave (clave)
) ENGINE=InnoDB;

-- =============================================
-- TABLAS DE WHATSAPP
-- =============================================

-- Tabla de usuarios (para gestionar conexiones de WhatsApp)
CREATE TABLE IF NOT EXISTS usuarios_whatsapp (
    id VARCHAR(50) PRIMARY KEY,
    nombre VARCHAR(100),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de sesiones de WhatsApp
CREATE TABLE IF NOT EXISTS sesiones_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL, -- 'initializing', 'authenticated', 'ready', 'disconnected'
    last_status_change TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    disconnect_reason TEXT,
    FOREIGN KEY (user_id) REFERENCES usuarios_whatsapp(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de chats de WhatsApp
CREATE TABLE IF NOT EXISTS chats_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL DEFAULT 'default',
    chat_id VARCHAR(100) NOT NULL, -- Formato WhatsApp: '1234567890@c.us'
    nombre VARCHAR(100),
    last_message TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chat (user_id, chat_id),
    INDEX idx_chat_id (chat_id)
) ENGINE=InnoDB;

-- Tabla de mensajes de WhatsApp
CREATE TABLE IF NOT EXISTS mensajes_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL DEFAULT 'default',
    chat_id VARCHAR(100) NOT NULL,
    message_id VARCHAR(255) NOT NULL, -- ID serializado de WhatsApp
    body TEXT,
    direction ENUM('sent', 'received') NOT NULL,
    is_auto_response BOOLEAN DEFAULT FALSE,
    timestamp INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_message (user_id, message_id),
    INDEX idx_chat_messages (chat_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_direction (direction)
) ENGINE=InnoDB;

-- Tabla de respuestas automáticas para WhatsApp (corregida)
CREATE TABLE IF NOT EXISTS autorespuestas_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL DEFAULT 'default',
    keyword VARCHAR(255) NOT NULL,
    response TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_regex BOOLEAN DEFAULT FALSE,
    created_at INT NOT NULL,
    updated_at INT NULL,
    INDEX idx_active (is_active),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Tabla de relación entre reservas y mensajes de WhatsApp
CREATE TABLE IF NOT EXISTS reservas_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    chat_id VARCHAR(100) NOT NULL,
    estado_notificacion ENUM('pendiente', 'enviada', 'confirmada', 'cancelada') DEFAULT 'pendiente',
    fecha_notificacion TIMESTAMP NULL,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
    INDEX idx_reserva (reserva_id),
    INDEX idx_chat (chat_id),
    INDEX idx_estado (estado_notificacion)
) ENGINE=InnoDB;

-- =============================================
-- TABLAS DE FORMULARIOS PÚBLICOS
-- =============================================

-- Tabla para formularios públicos
CREATE TABLE IF NOT EXISTS formularios_publicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_negocio INT NOT NULL DEFAULT 1,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    slug VARCHAR(100) UNIQUE,
    confirmacion_automatica TINYINT(1) DEFAULT 0,
    campos_activos TEXT NOT NULL,
    mensaje_confirmacion TEXT,
    mensaje_header TEXT,
    activo TINYINT(1) DEFAULT 1,
    created_at INT NOT NULL,
    updated_at INT,
    INDEX idx_slug (slug),
    INDEX idx_activo (activo),
    INDEX idx_negocio (id_negocio)
) ENGINE=InnoDB;

-- Tabla para preguntas personalizadas opcionales
CREATE TABLE IF NOT EXISTS formulario_preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_formulario INT NOT NULL,
    pregunta VARCHAR(255) NOT NULL,
    tipo ENUM('texto', 'numero', 'lista', 'checkbox') NOT NULL,
    opciones TEXT,
    requerido TINYINT(1) DEFAULT 0,
    orden INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_formulario) REFERENCES formularios_publicos(id) ON DELETE CASCADE,
    INDEX idx_formulario (id_formulario),
    INDEX idx_orden (orden)
) ENGINE=InnoDB;

-- Tabla para reservas creadas a través de formularios
CREATE TABLE IF NOT EXISTS reservas_formulario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT NOT NULL,
    id_formulario INT NOT NULL,
    respuestas TEXT,
    origin_url VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at INT NOT NULL,
    FOREIGN KEY (id_reserva) REFERENCES reservas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_formulario) REFERENCES formularios_publicos(id) ON DELETE CASCADE,
    INDEX idx_reserva_form (id_reserva),
    INDEX idx_formulario_form (id_formulario)
) ENGINE=InnoDB;

-- =============================================
-- INSERTAR CONFIGURACIÓN INICIAL
-- =============================================

-- Configuración básica del sistema
INSERT INTO configuraciones (clave, valor) VALUES
-- Modo de funcionamiento
('modo_aceptacion', 'manual'),
('intervalo_reservas', '30'),

-- Mensajes automáticos básicos
('mensaje_bienvenida', '¡Hola! Soy el asistente virtual de ReservaBot Demo. ¿En qué puedo ayudarte hoy?'),
('mensaje_confirmacion', 'Tu reserva para el día {fecha} a las {hora} ha sido confirmada. ¡Te esperamos!'),
('mensaje_pendiente', 'Hemos recibido tu solicitud para el día {fecha} a las {hora}. Te confirmaremos pronto.'),

-- Horarios de atención (Lunes a Viernes 9:00-18:00, Sábado 10:00-14:00, Domingo cerrado)
('horario_lun', 'true|09:00|18:00'),
('horario_mar', 'true|09:00|18:00'),
('horario_mie', 'true|09:00|18:00'),
('horario_jue', 'true|09:00|18:00'),
('horario_vie', 'true|09:00|18:00'),
('horario_sab', 'true|10:00|14:00'),
('horario_dom', 'false|00:00|00:00'),

-- Configuración de WhatsApp
('whatsapp_enabled', 'true'),
('whatsapp_server_url', 'http://localhost:3000'),
('whatsapp_api_key', ''),
('whatsapp_status', 'disconnected'),
('whatsapp_last_activity', ''),

-- Notificaciones de WhatsApp
('whatsapp_notify_nueva_reserva', 'true'),
('whatsapp_notify_confirmacion', 'true'),
('whatsapp_notify_recordatorio', 'true'),
('whatsapp_notify_cancelacion', 'true'),
('whatsapp_tiempo_recordatorio', '24'),

-- Mensajes personalizados de WhatsApp
('whatsapp_mensaje_nueva_reserva', 'Has realizado una nueva reserva para el {fecha} a las {hora}. Te confirmaremos pronto.'),
('whatsapp_mensaje_confirmacion', 'Tu reserva para el {fecha} a las {hora} ha sido confirmada. ¡Te esperamos!'),
('whatsapp_mensaje_recordatorio', 'Recordatorio: Tienes una cita mañana {fecha} a las {hora}. ¡Te esperamos!'),
('whatsapp_mensaje_cancelacion', 'Tu reserva para el {fecha} a las {hora} ha sido cancelada.');

-- =============================================
-- DATOS DE PRUEBA
-- =============================================

-- Reservas de ejemplo (fechas futuras para testing)
INSERT INTO reservas (nombre, telefono, whatsapp_id, fecha, hora, mensaje, estado) VALUES
('María García López', '+34 612 345 678', '34612345678', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'Quisiera una cita para consulta general. Es la primera vez que vengo.', 'pendiente'),
('Carlos López Martín', '+34 623 456 789', '34623456789', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:30:00', 'Necesito revisar mi expediente y hacer algunas consultas.', 'pendiente'),
('Ana Martínez Ruiz', '+34 634 567 890', '34634567890', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '12:15:00', 'Consulta rápida sobre servicios disponibles.', 'pendiente'),
('Javier Ruiz Sánchez', '+34 645 678 901', '34645678901', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '11:30:00', 'Cita confirmada por WhatsApp. Segunda visita.', 'confirmada'),
('Laura Sánchez Torres', '+34 656 789 012', '34656789012', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '17:00:00', 'Seguimiento de consulta anterior. Muy importante.', 'confirmada'),
('Pedro Fernández Gil', '+34 667 890 123', '34667890123', DATE_ADD(CURDATE(), INTERVAL 4 DAY), '09:30:00', 'Primera consulta. Vengo recomendado por María García.', 'pendiente'),
('Isabel Moreno Vega', '+34 678 901 234', '34678901234', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '15:45:00', 'Revisión anual. Tengo toda la documentación preparada.', 'confirmada'),
('Roberto Silva Castro', '+34 689 012 345', '34689012345', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '10:15:00', 'Consulta urgente sobre un tema específico.', 'pendiente');

-- Usuario por defecto para WhatsApp
INSERT INTO usuarios_whatsapp (id, nombre, email) VALUES
('default', 'Usuario Demo', 'demo@reservabot.com');

-- Chats de ejemplo (basados en las reservas)
INSERT INTO chats_whatsapp (user_id, chat_id, nombre, last_message, created_at) VALUES
('default', '34612345678@c.us', 'María García López', NOW(), NOW()),
('default', '34623456789@c.us', 'Carlos López Martín', NOW(), NOW()),
('default', '34634567890@c.us', 'Ana Martínez Ruiz', NOW(), NOW()),
('default', '34645678901@c.us', 'Javier Ruiz Sánchez', NOW(), NOW()),
('default', '34656789012@c.us', 'Laura Sánchez Torres', NOW(), NOW());

-- Mensajes de ejemplo
INSERT INTO mensajes_whatsapp (user_id, chat_id, message_id, body, direction, is_auto_response, timestamp) VALUES
('default', '34612345678@c.us', 'msg_001', 'Hola, quisiera hacer una reserva para mañana', 'received', FALSE, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 2 HOUR))),
('default', '34612345678@c.us', 'msg_002', '¡Hola! Por supuesto, te ayudo con tu reserva. ¿Qué horario te viene mejor?', 'sent', TRUE, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 2 HOUR)) + 300),
('default', '34623456789@c.us', 'msg_003', 'Buenos días, necesito cambiar mi cita', 'received', FALSE, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 HOUR))),
('default', '34623456789@c.us', 'msg_004', 'Buenos días Carlos. Te ayudo con el cambio de cita. ¿Cuál es tu nueva disponibilidad?', 'sent', FALSE, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 HOUR)) + 180),
('default', '34634567890@c.us', 'msg_005', 'Hola, ¿están abiertos hoy?', 'received', FALSE, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 MINUTE))),
('default', '34634567890@c.us', 'msg_006', 'Sí, estamos abiertos de 9:00 a 18:00. ¿En qué puedo ayudarte?', 'sent', TRUE, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 MINUTE)) + 120);

-- Respuestas automáticas de ejemplo
INSERT INTO autorespuestas_whatsapp (user_id, keyword, response, is_active, is_regex, created_at) VALUES
('default', 'hola', '¡Hola! Bienvenido a ReservaBot. ¿En qué puedo ayudarte hoy? Puedes escribir "reserva" para hacer una cita o "horarios" para ver nuestros horarios.', TRUE, FALSE, UNIX_TIMESTAMP()),
('default', 'horarios', 'Nuestros horarios de atención son:\n📅 Lunes a Viernes: 9:00 - 18:00\n📅 Sábado: 10:00 - 14:00\n📅 Domingo: Cerrado\n\n¿Te gustaría hacer una reserva?', TRUE, FALSE, UNIX_TIMESTAMP()),
('default', 'reserva', 'Para hacer una reserva necesito algunos datos:\n• Tu nombre completo\n• Fecha preferida\n• Hora preferida\n\nPor favor compárteme esta información y te ayudo a confirmar tu cita.', TRUE, FALSE, UNIX_TIMESTAMP()),
('default', 'precio', 'Para información sobre precios y tarifas, por favor contacta directamente durante horario de oficina. ¿Te gustaría que programe una cita para hablar con un asesor?', TRUE, FALSE, UNIX_TIMESTAMP()),
('default', 'ubicación', 'Nuestra oficina está ubicada en el centro de la ciudad. Te enviaré la dirección exacta una vez confirmes tu reserva. ¿Te gustaría programar una cita?', TRUE, FALSE, UNIX_TIMESTAMP()),
('default', 'cancelar', 'Entiendo que necesitas cancelar. Por favor proporciona tu nombre completo y la fecha de tu cita para ayudarte con la cancelación.', TRUE, FALSE, UNIX_TIMESTAMP());

-- Formulario público de ejemplo
INSERT INTO formularios_publicos (id_negocio, nombre, descripcion, slug, confirmacion_automatica, campos_activos, mensaje_confirmacion, mensaje_header, activo, created_at) VALUES
(1, 'Reserva Online - Consulta General', 'Formulario principal para reservas de consultas generales', 'reserva-consulta', 0, '["nombre","email","telefono","fecha","hora","comentarios"]', 'Gracias por tu reserva. Hemos recibido tu solicitud y te contactaremos pronto para confirmarla.', 'Completa el siguiente formulario para solicitar una cita. Nos pondremos en contacto contigo para confirmar la disponibilidad.', 1, UNIX_TIMESTAMP()),
(1, 'Reserva Rápida - Seguimiento', 'Formulario simplificado para clientes que ya han estado antes', 'seguimiento', 1, '["nombre","telefono","fecha","hora"]', 'Tu cita de seguimiento ha sido confirmada automáticamente. Te esperamos el {fecha} a las {hora}.', 'Reserva rápida para consultas de seguimiento.', 1, UNIX_TIMESTAMP());

-- Preguntas personalizadas para el primer formulario
INSERT INTO formulario_preguntas (id_formulario, pregunta, tipo, opciones, requerido, orden, activo) VALUES
(1, '¿Es tu primera consulta con nosotros?', 'lista', '["Sí, es mi primera vez","No, ya he estado antes"]', 1, 1, 1),
(1, '¿Cómo nos conociste?', 'lista', '["Recomendación de un amigo","Búsqueda en Google","Redes sociales","Publicidad","Otro"]', 0, 2, 1),
(1, '¿Tienes alguna condición especial que debamos conocer?', 'texto', NULL, 0, 3, 1),
(1, 'Servicios de interés (puedes seleccionar varios)', 'checkbox', '["Consulta general","Asesoramiento","Revisión de documentos","Seguimiento","Información sobre servicios"]', 0, 4, 1);

-- =============================================
-- CREAR ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =============================================

-- Índices para mejorar el rendimiento
CREATE INDEX IF NOT EXISTS idx_mensajes_user_chat ON mensajes_whatsapp (user_id, chat_id);
CREATE INDEX IF NOT EXISTS idx_mensajes_timestamp ON mensajes_whatsapp (timestamp);
CREATE INDEX IF NOT EXISTS idx_respuestas_automaticas_user ON autorespuestas_whatsapp (user_id, is_active);
CREATE INDEX IF NOT EXISTS idx_reservas_whatsapp_chat ON reservas_whatsapp (chat_id);
CREATE INDEX IF NOT EXISTS idx_reservas_whatsapp_estado ON reservas_whatsapp (estado_notificacion);
CREATE INDEX IF NOT EXISTS idx_reservas_fecha_hora ON reservas (fecha, hora);
CREATE INDEX IF NOT EXISTS idx_configuraciones_clave ON configuraciones (clave);

-- =============================================
-- VISTAS ÚTILES PARA REPORTES (OPCIONAL)
-- =============================================

-- Vista para reservas con información de WhatsApp
CREATE OR REPLACE VIEW v_reservas_completas AS
SELECT 
    r.id,
    r.nombre,
    r.telefono,
    r.whatsapp_id,
    r.fecha,
    r.hora,
    r.mensaje,
    r.estado,
    r.created_at,
    r.updated_at,
    c.nombre as chat_nombre,
    rw.estado_notificacion,
    rw.fecha_notificacion
FROM reservas r
LEFT JOIN chats_whatsapp c ON r.whatsapp_id = REPLACE(c.chat_id, '@c.us', '')
LEFT JOIN reservas_whatsapp rw ON r.id = rw.reserva_id;

-- Vista para estadísticas de mensajes
CREATE OR REPLACE VIEW v_estadisticas_mensajes AS
SELECT 
    DATE(FROM_UNIXTIME(timestamp)) as fecha,
    direction,
    COUNT(*) as total_mensajes,
    COUNT(DISTINCT chat_id) as chats_unicos,
    SUM(CASE WHEN is_auto_response = 1 THEN 1 ELSE 0 END) as respuestas_automaticas
FROM mensajes_whatsapp
GROUP BY DATE(FROM_UNIXTIME(timestamp)), direction
ORDER BY fecha DESC, direction;

-- =============================================
-- PROCEDIMIENTOS ALMACENADOS ÚTILES (OPCIONAL)
-- =============================================

DELIMITER //

-- Procedimiento para limpiar mensajes antiguos (más de 6 meses)
CREATE PROCEDURE IF NOT EXISTS LimpiarMensajesAntiguos()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    DELETE FROM mensajes_whatsapp 
    WHERE timestamp < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 6 MONTH));
    
    COMMIT;
END //

-- Procedimiento para obtener estadísticas del dashboard
CREATE PROCEDURE IF NOT EXISTS ObtenerEstadisticasDashboard()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente') as pendientes,
        (SELECT COUNT(*) FROM reservas WHERE estado = 'confirmada') as confirmadas,
        (SELECT COUNT(*) FROM reservas WHERE fecha = CURDATE()) as hoy,
        (SELECT COUNT(*) FROM reservas WHERE fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) as esta_semana,
        (SELECT COUNT(*) FROM mensajes_whatsapp WHERE timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))) as mensajes_24h,
        (SELECT COUNT(*) FROM autorespuestas_whatsapp WHERE is_active = 1) as respuestas_activas;
END //

DELIMITER ;

-- =============================================
-- VERIFICACIÓN FINAL
-- =============================================

-- Mostrar resumen de datos insertados
SELECT 'RESUMEN DE LA INSTALACIÓN' as info;
SELECT COUNT(*) as total_reservas FROM reservas;
SELECT COUNT(*) as total_configuraciones FROM configuraciones;
SELECT COUNT(*) as total_mensajes FROM mensajes_whatsapp;
SELECT COUNT(*) as total_autorespuestas FROM autorespuestas_whatsapp;
SELECT COUNT(*) as total_formularios FROM formularios_publicos;

-- Verificar que las fechas de las reservas son futuras
SELECT 
    'Reservas programadas' as tipo,
    COUNT(*) as cantidad,
    MIN(fecha) as fecha_mas_proxima,
    MAX(fecha) as fecha_mas_lejana
FROM reservas 
WHERE fecha >= CURDATE();

COMMIT;

-- =============================================
-- NOTAS IMPORTANTES
-- =============================================
/*
INSTRUCCIONES POST-INSTALACIÓN:

1. Verificar que todas las tablas se crearon correctamente
2. Ajustar las fechas de las reservas de prueba si es necesario
3. Configurar la URL del servidor WhatsApp en la configuración
4. Revisar y personalizar los mensajes automáticos
5. Crear usuarios adicionales de WhatsApp si es necesario

DATOS DE PRUEBA INCLUIDOS:
- 8 reservas de ejemplo con fechas futuras
- 6 respuestas automáticas básicas
- 6 mensajes de conversación de ejemplo
- 2 formularios públicos funcionales
- Configuración completa del sistema

SEGURIDAD:
- Cambiar contraseñas por defecto en producción
- Configurar backup automático de la base de datos
- Revisar permisos de usuario de base de datos
*/