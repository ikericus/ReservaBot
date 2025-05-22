-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS reservabot DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservabot;

-- Crear tabla de reservas
CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    mensaje TEXT,
    estado ENUM('pendiente', 'confirmada') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Crear tabla de configuración
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar configuración inicial
-- Insertar configuración inicial (continuación)
INSERT INTO configuracion (clave, valor) VALUES
('modo_aceptacion', 'manual'),
('mensaje_bienvenida', '¡Hola! Soy el asistente virtual de [Nombre del Negocio]. ¿En qué puedo ayudarte hoy?'),
('mensaje_confirmacion', 'Tu reserva para el día {fecha} a las {hora} ha sido confirmada. ¡Te esperamos!'),
('mensaje_pendiente', 'Hemos recibido tu solicitud para el día {fecha} a las {hora}. Te confirmaremos pronto.'),
('horario_lun', 'true|09:00|18:00'),
('horario_mar', 'true|09:00|18:00'),
('horario_mie', 'true|09:00|18:00'),
('horario_jue', 'true|09:00|18:00'),
('horario_vie', 'true|09:00|18:00'),
('horario_sab', 'true|10:00|14:00'),
('horario_dom', 'false|00:00|00:00'),
('intervalo_reservas', '30');

-- Insertar algunos datos de ejemplo
INSERT INTO reservas (nombre, telefono, fecha, hora, mensaje, estado) VALUES
('María García', '+34 612 345 678', '2025-05-07', '10:00:00', 'Quisiera una cita para consulta general.', 'pendiente'),
('Carlos López', '+34 623 456 789', '2025-05-07', '16:30:00', 'Necesito revisar mi expediente.', 'pendiente'),
('Ana Martínez', '+34 634 567 890', '2025-05-08', '12:15:00', 'Consulta rápida sobre servicios.', 'pendiente'),
('Javier Ruiz', '+34 645 678 901', '2025-05-06', '11:30:00', 'Cita confirmada por WhatsApp.', 'confirmada'),
('Laura Sánchez', '+34 656 789 012', '2025-05-06', '17:00:00', 'Segunda visita para seguimiento.', 'confirmada');


-- NUEVA CONFIGURACIÓN PARA EL SISTEMA DE WHATSAPP
-- Las siguientes tablas se añaden para la integración con WhatsApp

-- Tabla de usuarios (para gestionar conexiones de WhatsApp)
CREATE TABLE IF NOT EXISTS usuarios_whatsapp (
    id VARCHAR(50) PRIMARY KEY,
    nombre VARCHAR(100),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de sesiones de WhatsApp
CREATE TABLE IF NOT EXISTS sesiones_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL, -- 'initializing', 'authenticated', 'ready', 'disconnected'
    last_status_change TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    disconnect_reason TEXT,
    FOREIGN KEY (user_id) REFERENCES usuarios_whatsapp(id) ON DELETE CASCADE
);

-- Tabla de chats de WhatsApp
CREATE TABLE IF NOT EXISTS chats_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    chat_id VARCHAR(100) NOT NULL, -- Formato WhatsApp: '1234567890@c.us'
    nombre VARCHAR(100),
    last_message TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chat (user_id, chat_id),
    FOREIGN KEY (user_id) REFERENCES usuarios_whatsapp(id) ON DELETE CASCADE
);

-- Tabla de mensajes de WhatsApp
CREATE TABLE IF NOT EXISTS mensajes_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    chat_id VARCHAR(100) NOT NULL,
    message_id VARCHAR(255) NOT NULL, -- ID serializado de WhatsApp
    body TEXT,
    direction ENUM('sent', 'received') NOT NULL,
    is_auto_response BOOLEAN DEFAULT FALSE,
    timestamp INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_message (user_id, message_id),
    FOREIGN KEY (user_id) REFERENCES usuarios_whatsapp(id) ON DELETE CASCADE
);

-- Tabla de respuestas automáticas para WhatsApp
CREATE TABLE IF NOT EXISTS respuestas_automaticas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    trigger_text VARCHAR(255) NOT NULL,
    response_text TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios_whatsapp(id) ON DELETE CASCADE
);

-- Tabla de relación entre reservas y mensajes de WhatsApp
CREATE TABLE IF NOT EXISTS reservas_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    chat_id VARCHAR(100) NOT NULL,
    estado_notificacion ENUM('pendiente', 'enviada', 'confirmada', 'cancelada') DEFAULT 'pendiente',
    fecha_notificacion TIMESTAMP NULL,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE
);

-- Añadir campo para el cliente de WhatsApp a la tabla de reservas
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS whatsapp_id VARCHAR(100) NULL AFTER telefono;

-- Actualizar configuración para incluir nuevos parámetros de WhatsApp
INSERT INTO configuracion (clave, valor) VALUES
('whatsapp_enabled', 'true'),
('whatsapp_notify_nueva_reserva', 'true'),
('whatsapp_notify_confirmacion', 'true'),
('whatsapp_notify_recordatorio', 'true'),
('whatsapp_tiempo_recordatorio', '24'), -- Horas antes de la cita
('whatsapp_mensaje_nueva_reserva', 'Has realizado una nueva reserva para el {fecha} a las {hora}. Te confirmaremos pronto.'),
('whatsapp_mensaje_confirmacion', 'Tu reserva para el {fecha} a las {hora} ha sido confirmada. ¡Te esperamos!'),
('whatsapp_mensaje_recordatorio', 'Recordatorio: Tienes una cita mañana {fecha} a las {hora}. ¡Te esperamos!'),
('whatsapp_mensaje_cancelacion', 'Tu reserva para el {fecha} a las {hora} ha sido cancelada.');

-- Crear índices para mejorar el rendimiento
CREATE INDEX idx_mensajes_user_chat ON mensajes_whatsapp (user_id, chat_id);
CREATE INDEX idx_mensajes_timestamp ON mensajes_whatsapp (timestamp);
CREATE INDEX idx_respuestas_automaticas_user ON respuestas_automaticas (user_id, is_active);
CREATE INDEX idx_reservas_whatsapp_chat ON reservas_whatsapp (chat_id);
CREATE INDEX idx_reservas_whatsapp_estado ON reservas_whatsapp (estado_notificacion);

-- Estructura del generador de formularios
-- Tabla para formularios públicos
CREATE TABLE formularios_publicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_negocio INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    slug VARCHAR(100) UNIQUE,
    confirmacion_automatica TINYINT(1) DEFAULT 0,
    campos_activos TEXT NOT NULL,
    mensaje_confirmacion TEXT,
    mensaje_header TEXT,
    activo TINYINT(1) DEFAULT 1,
    created_at INT NOT NULL,
    updated_at INT
);

-- Tabla para preguntas personalizadas opcionales
CREATE TABLE formulario_preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_formulario INT NOT NULL,
    pregunta VARCHAR(255) NOT NULL,
    tipo ENUM('texto', 'numero', 'lista', 'checkbox') NOT NULL,
    opciones TEXT,
    requerido TINYINT(1) DEFAULT 0,
    orden INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_formulario) REFERENCES formularios_publicos(id) ON DELETE CASCADE
);

-- Tabla para reservas creadas a través de formularios
CREATE TABLE reservas_formulario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT NOT NULL,
    id_formulario INT NOT NULL,
    respuestas TEXT,
    origin_url VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at INT NOT NULL,
    FOREIGN KEY (id_reserva) REFERENCES reservas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_formulario) REFERENCES formularios_publicos(id) ON DELETE CASCADE
);