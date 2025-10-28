SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Base de datos: `reservabot`
--
CREATE DATABASE IF NOT EXISTS `reservabot` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `reservabot`;

CREATE TABLE autorespuestas_whatsapp (
  id int(11) NOT NULL,
  usuario_id int(11) DEFAULT NULL,
  nombre varchar(100) NOT NULL,
  keyword varchar(255) NOT NULL,
  response text NOT NULL,
  is_active tinyint(1) DEFAULT 1,
  is_regex tinyint(1) DEFAULT 0,
  match_type enum('contains','exact','starts_with','ends_with','regex') DEFAULT 'contains',
  priority int(11) DEFAULT 0,
  uso_count int(11) DEFAULT 0,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Respuestas automáticas para WhatsApp';

CREATE TABLE configuraciones (
  id int(11) NOT NULL,
  clave varchar(100) NOT NULL,
  valor text NOT NULL,
  descripcion text DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración global del sistema';

CREATE TABLE configuraciones_usuario (
  id int(11) NOT NULL,
  usuario_id int(11) NOT NULL,
  clave varchar(100) NOT NULL,
  valor text NOT NULL,
  descripcion text DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuraciones específicas por usuario';

CREATE TABLE conversaciones (
  id int(11) NOT NULL,
  usuario_id int(11) NOT NULL,
  cliente_phone varchar(20) NOT NULL,
  cliente_nombre varchar(100) DEFAULT NULL,
  ultimo_mensaje text DEFAULT NULL,
  no_leidos int(11) DEFAULT 0,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE formularios_publicos (
  id int(11) NOT NULL,
  usuario_id int(11) DEFAULT NULL,
  nombre varchar(150) NOT NULL,
  empresa_nombre varchar(255) DEFAULT NULL,
  descripcion text DEFAULT NULL,
  slug varchar(100) NOT NULL,
  confirmacion_automatica tinyint(1) NOT NULL DEFAULT 0,
  activo tinyint(1) NOT NULL DEFAULT 1,
  mensaje_exito text DEFAULT NULL,
  mensaje_header text DEFAULT NULL,
  color_tema varchar(7) DEFAULT '#3B82F6',
  mostrar_comentarios tinyint(1) DEFAULT 1,
  mostrar_email tinyint(1) DEFAULT 0,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  empresa_logo varchar(500) DEFAULT NULL COMMENT 'URL del logo',
  color_primario varchar(7) DEFAULT '#667eea' COMMENT 'Color hex principal',
  color_secundario varchar(7) DEFAULT '#764ba2' COMMENT 'Color hex secundario',
  mensaje_bienvenida text DEFAULT NULL COMMENT 'Mensaje personalizado de bienvenida',
  direccion varchar(500) DEFAULT NULL COMMENT 'Dirección del negocio',
  telefono_contacto varchar(20) DEFAULT NULL COMMENT 'Teléfono de contacto del negocio',
  email_contacto varchar(255) DEFAULT NULL COMMENT 'Email de contacto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Formularios públicos para reservas online';

CREATE TABLE mensajes (
  id int(11) NOT NULL,
  conversacion_id int(11) NOT NULL,
  tipo enum('entrante','saliente') NOT NULL,
  contenido text NOT NULL,
  enviado_por enum('cliente','usuario','sistema') NOT NULL,
  mensaje_id varchar(100) DEFAULT NULL,
  timestamp timestamp NULL DEFAULT current_timestamp(),
  leido tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE origen_reservas (
  id int(11) NOT NULL,
  reserva_id int(11) NOT NULL,
  formulario_id int(11) DEFAULT NULL,
  origen enum('admin','formulario_publico','whatsapp','api') NOT NULL DEFAULT 'admin',
  ip_address varchar(45) DEFAULT NULL,
  user_agent text DEFAULT NULL,
  referrer_url varchar(500) DEFAULT NULL,
  utm_source varchar(100) DEFAULT NULL,
  utm_campaign varchar(100) DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracking del origen de las reservas';

CREATE TABLE reservas (
  id int(11) NOT NULL,
  usuario_id int(11) DEFAULT NULL,
  nombre varchar(150) NOT NULL,
  telefono varchar(25) NOT NULL,
  whatsapp_id varchar(50) DEFAULT NULL,
  email varchar(255) DEFAULT NULL,
  fecha date NOT NULL,
  hora time NOT NULL,
  mensaje text DEFAULT NULL,
  estado enum('pendiente','confirmada','cancelada') NOT NULL DEFAULT 'pendiente',
  notas_internas text DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  access_token varchar(64) DEFAULT NULL,
  token_expires datetime DEFAULT NULL,
  formulario_id int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Reservas principales del sistema';

CREATE TABLE sesiones_usuario (
  id varchar(128) NOT NULL,
  usuario_id int(11) NOT NULL,
  ip_address varchar(45) DEFAULT NULL,
  user_agent text DEFAULT NULL,
  payload text DEFAULT NULL,
  last_activity timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  created_at timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gestión de sesiones de usuario';

CREATE TABLE usuarios (
  id int(11) NOT NULL,
  nombre varchar(255) NOT NULL,
  email varchar(255) NOT NULL,
  telefono varchar(25) DEFAULT NULL,
  negocio varchar(255) DEFAULT NULL,
  password_hash varchar(255) NOT NULL,
  plan enum('gratis','estandar','premium') DEFAULT 'gratis',
  api_key varchar(64) DEFAULT NULL,
  activo tinyint(1) DEFAULT 1,
  intentos_login int(11) DEFAULT 0,
  ultimo_intento_login timestamp NULL DEFAULT NULL,
  email_verificado tinyint(1) DEFAULT 0,
  verificacion_token varchar(64) DEFAULT NULL,
  reset_token varchar(64) DEFAULT NULL,
  reset_token_expiry timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  last_activity timestamp NULL DEFAULT NULL,
  whatsapp_status enum('disconnected','connecting','waiting_qr','connected','auth_failed') DEFAULT 'disconnected',
  whatsapp_phone varchar(20) DEFAULT NULL,
  whatsapp_name varchar(100) DEFAULT NULL,
  whatsapp_qr text DEFAULT NULL,
  whatsapp_connected_at datetime DEFAULT NULL,
  whatsapp_updated_at datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios del sistema ReservaBot';

CREATE TABLE whatsapp_config (
  id int(11) NOT NULL,
  usuario_id int(11) NOT NULL,
  phone_number varchar(20) DEFAULT NULL,
  qr_code text DEFAULT NULL,
  status enum('disconnected','connecting','connected','waiting_qr','error') DEFAULT 'disconnected',
  last_activity timestamp NULL DEFAULT current_timestamp(),
  token varchar(255) DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE whatsapp_messages (
  id int(11) NOT NULL,
  usuario_id int(11) NOT NULL,
  message_id varchar(255) DEFAULT NULL,
  phone_number varchar(20) NOT NULL,
  message_text text DEFAULT NULL,
  direction enum('incoming','outgoing') NOT NULL,
  is_group tinyint(1) DEFAULT 0,
  has_media tinyint(1) DEFAULT 0,
  status enum('pending','sent','delivered','read','failed') DEFAULT 'pending',
  timestamp_received datetime DEFAULT NULL,
  timestamp_sent datetime DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE autorespuestas_whatsapp
  ADD PRIMARY KEY (id),
  ADD KEY idx_active (is_active),
  ADD KEY idx_keyword (keyword),
  ADD KEY idx_priority (priority),
  ADD KEY idx_updated (updated_at),
  ADD KEY idx_autorespuestas_active_priority (is_active,priority DESC),
  ADD KEY idx_usuario_id (usuario_id),
  ADD KEY idx_autorespuestas_usuario_activo (usuario_id,is_active);

ALTER TABLE configuraciones
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY clave (clave),
  ADD KEY idx_clave (clave),
  ADD KEY idx_updated (updated_at);

ALTER TABLE configuraciones_usuario
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY unique_user_config (usuario_id,clave),
  ADD KEY idx_usuario_clave (usuario_id,clave),
  ADD KEY idx_updated (updated_at);

ALTER TABLE conversaciones
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY unique_conversation (usuario_id,cliente_phone),
  ADD KEY idx_usuario_updated (usuario_id,updated_at);

ALTER TABLE formularios_publicos
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY slug (slug),
  ADD KEY idx_slug (slug),
  ADD KEY idx_activo (activo),
  ADD KEY idx_created (created_at),
  ADD KEY idx_usuario_id (usuario_id),
  ADD KEY idx_formularios_usuario_activo (usuario_id,activo);

ALTER TABLE mensajes
  ADD PRIMARY KEY (id),
  ADD KEY idx_conversacion_timestamp (conversacion_id,timestamp),
  ADD KEY idx_mensaje_id (mensaje_id);

ALTER TABLE origen_reservas
  ADD PRIMARY KEY (id),
  ADD KEY idx_reserva (reserva_id),
  ADD KEY idx_formulario (formulario_id),
  ADD KEY idx_origen (origen),
  ADD KEY idx_created (created_at),
  ADD KEY idx_origen_formulario_fecha (formulario_id,created_at);

ALTER TABLE reservas
  ADD PRIMARY KEY (id),
  ADD KEY idx_fecha (fecha),
  ADD KEY idx_estado (estado),
  ADD KEY idx_fecha_hora (fecha,hora),
  ADD KEY idx_whatsapp (whatsapp_id),
  ADD KEY idx_telefono (telefono),
  ADD KEY idx_created (created_at),
  ADD KEY idx_reservas_fecha_estado (fecha,estado),
  ADD KEY idx_reservas_estado_fecha (estado,fecha),
  ADD KEY idx_usuario_id (usuario_id),
  ADD KEY idx_reservas_usuario_fecha (usuario_id,fecha),
  ADD KEY idx_reservas_usuario_estado (usuario_id,estado),
  ADD KEY idx_access_token (access_token),
  ADD KEY formulario_id (formulario_id);

ALTER TABLE sesiones_usuario
  ADD PRIMARY KEY (id),
  ADD KEY idx_usuario_id (usuario_id),
  ADD KEY idx_last_activity (last_activity);

ALTER TABLE usuarios
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY email (email),
  ADD UNIQUE KEY api_key (api_key),
  ADD KEY idx_email (email),
  ADD KEY idx_api_key (api_key),
  ADD KEY idx_activo (activo),
  ADD KEY idx_plan (plan),
  ADD KEY idx_last_activity (last_activity),
  ADD KEY idx_verificacion_token (verificacion_token),
  ADD KEY idx_reset_token (reset_token);

ALTER TABLE whatsapp_config
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY unique_user (usuario_id);

ALTER TABLE whatsapp_messages
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY message_id (message_id),
  ADD KEY idx_usuario_phone (usuario_id,phone_number),
  ADD KEY idx_message_id (message_id),
  ADD KEY idx_timestamp (timestamp_received);


ALTER TABLE autorespuestas_whatsapp
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE configuraciones
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE configuraciones_usuario
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE conversaciones
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE formularios_publicos
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE mensajes
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE origen_reservas
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE reservas
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE usuarios
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE whatsapp_config
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE whatsapp_messages
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE configuraciones_usuario
  ADD CONSTRAINT configuraciones_usuario_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;

ALTER TABLE conversaciones
  ADD CONSTRAINT conversaciones_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;

ALTER TABLE mensajes
  ADD CONSTRAINT mensajes_ibfk_1 FOREIGN KEY (conversacion_id) REFERENCES conversaciones (id) ON DELETE CASCADE;

ALTER TABLE origen_reservas
  ADD CONSTRAINT origen_reservas_ibfk_1 FOREIGN KEY (reserva_id) REFERENCES reservas (id) ON DELETE CASCADE,
  ADD CONSTRAINT origen_reservas_ibfk_2 FOREIGN KEY (formulario_id) REFERENCES formularios_publicos (id) ON DELETE SET NULL;

ALTER TABLE reservas
  ADD CONSTRAINT reservas_ibfk_1 FOREIGN KEY (formulario_id) REFERENCES formularios_publicos (id) ON DELETE SET NULL;

ALTER TABLE sesiones_usuario
  ADD CONSTRAINT sesiones_usuario_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;

ALTER TABLE whatsapp_config
  ADD CONSTRAINT whatsapp_config_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;

ALTER TABLE whatsapp_messages
  ADD CONSTRAINT whatsapp_messages_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;