SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Base de datos: `reservabot`
--
CREATE DATABASE IF NOT EXISTS `reservabot` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `reservabot`;

DELIMITER $$

CREATE PROCEDURE GenerateDemoData (IN fecha_actual DATE)
BEGIN
    DECLARE demo_user_id INT;
    DECLARE i INT DEFAULT 0;
    DECLARE j INT DEFAULT 0;
    DECLARE fecha_reserva DATE;
    DECLARE hora_reserva TIME;
    DECLARE nombre_cliente VARCHAR(150);
    DECLARE telefono_cliente VARCHAR(25);
    DECLARE email_cliente VARCHAR(255);
    DECLARE servicio_solicitado TEXT;
    DECLARE estado_reserva ENUM('pendiente','confirmada','cancelada');
    DECLARE notas_demo TEXT;
    DECLARE created_timestamp TIMESTAMP;
    DECLARE num_reservas_dia INT;

    DECLARE nombres_count INT DEFAULT 20;
    DECLARE servicios_count INT DEFAULT 16;
    DECLARE comentarios_count INT DEFAULT 8;

    -- Obtener ID del usuario demo
    SELECT id INTO demo_user_id 
    FROM usuarios 
    WHERE email = 'demo@reservabot.es' 
    LIMIT 1;

    IF demo_user_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario demo no encontrado';
    END IF;

    -- Borrar reservas anteriores de demo
    DELETE FROM reservas 
    WHERE usuario_id = demo_user_id 
      AND (notas_internas LIKE '%[DEMO]%' OR notas_internas IS NULL);

    -- Generar reservas PASADAS (últimos 30 días)
    SET i = 30;
    WHILE i >= 1 DO
        SET fecha_reserva = DATE_SUB(fecha_actual, INTERVAL i DAY);
        SET num_reservas_dia = FLOOR(2 + (RAND() * 5)); 
        
        SET j = 0;
        WHILE j < num_reservas_dia DO

            SET hora_reserva = ADDTIME('09:00:00', SEC_TO_TIME((FLOOR(RAND() * 21) * 1800))); 

            SET nombre_cliente = CASE FLOOR(RAND() * nombres_count)
                WHEN 0 THEN 'María García'
                WHEN 1 THEN 'Ana López'
                WHEN 2 THEN 'Carmen Rodríguez'
                WHEN 3 THEN 'Isabel Martín'
                WHEN 4 THEN 'Pilar Sánchez'
                WHEN 5 THEN 'Teresa Fernández'
                WHEN 6 THEN 'Rosa Díaz'
                WHEN 7 THEN 'Elena Ruiz'
                WHEN 8 THEN 'Cristina Moreno'
                WHEN 9 THEN 'Patricia Jiménez'
                WHEN 10 THEN 'Laura González'
                WHEN 11 THEN 'Beatriz Álvarez'
                WHEN 12 THEN 'Mónica Romero'
                WHEN 13 THEN 'Silvia Navarro'
                WHEN 14 THEN 'Raquel Torres'
                WHEN 15 THEN 'Nuria Ramos'
                WHEN 16 THEN 'Carlos Mendoza'
                WHEN 17 THEN 'Alberto Silva'
                WHEN 18 THEN 'Diego Herrera'
                ELSE 'Manuel Castro'
            END;

            SET telefono_cliente = CONCAT('6', LPAD(FLOOR(RAND() * 100000000), 8, '0'));
            SET email_cliente = CONCAT(LOWER(REPLACE(nombre_cliente, ' ', '.')), '@email.com');

            SET servicio_solicitado = CASE FLOOR(RAND() * servicios_count)
                WHEN 0 THEN 'Corte de pelo'
                WHEN 1 THEN 'Tinte y mechas'
                WHEN 2 THEN 'Peinado para evento'
                WHEN 3 THEN 'Tratamiento capilar'
                WHEN 4 THEN 'Manicura y pedicura'
                WHEN 5 THEN 'Tratamiento facial'
                WHEN 6 THEN 'Depilación'
                WHEN 7 THEN 'Masaje relajante'
                WHEN 8 THEN 'Limpieza facial'
                WHEN 9 THEN 'Extensiones'
                WHEN 10 THEN 'Alisado brasileño'
                WHEN 11 THEN 'Color y corte'
                WHEN 12 THEN 'Recogido de novia'
                WHEN 13 THEN 'Tratamiento anti-edad'
                WHEN 14 THEN 'Microblading'
                ELSE 'Pestañas'
            END;

            SET notas_demo = CONCAT('[DEMO] ', 
                CASE FLOOR(RAND() * comentarios_count)
                    WHEN 0 THEN 'Cliente habitual, muy puntual'
                    WHEN 1 THEN 'Primera vez, viene recomendada'
                    WHEN 2 THEN 'Recordar que es alérgica a ciertos productos'
                    WHEN 3 THEN 'Cliente VIP, trato preferencial'
                    WHEN 4 THEN 'Quiere cambio de look radical'
                    WHEN 5 THEN 'Evento especial, necesita asesoramiento'
                    WHEN 6 THEN 'Cliente fiel desde hace años'
                    ELSE 'Viene con prisa, optimizar tiempo'
                END
            );

            SET created_timestamp = TIMESTAMP(
                DATE_SUB(
                    TIMESTAMP(fecha_reserva, hora_reserva), 
                    INTERVAL (3600 + FLOOR(RAND() * 82800)) SECOND
                )
            );

            INSERT INTO reservas (
                usuario_id, nombre, telefono, email, fecha, hora,
                mensaje, estado, notas_internas, created_at, updated_at
            ) VALUES (
                demo_user_id, nombre_cliente, telefono_cliente, email_cliente, 
                fecha_reserva, hora_reserva, CONCAT('Solicita: ', servicio_solicitado),
                'confirmada', notas_demo, created_timestamp, created_timestamp
            );
            
            SET j = j + 1;
        END WHILE;
        
        SET i = i - 1;
    END WHILE;

    -- Generar reservas FUTURAS (próximos 20 días)
    SET i = 1;
    WHILE i <= 20 DO
        SET fecha_reserva = DATE_ADD(fecha_actual, INTERVAL i DAY);
        SET num_reservas_dia = FLOOR(1 + (RAND() * 4)); 
        
        SET j = 0;
        WHILE j < num_reservas_dia DO
            SET hora_reserva = ADDTIME('09:00:00', SEC_TO_TIME((FLOOR(RAND() * 21) * 1800)));

            SET nombre_cliente = CASE FLOOR(RAND() * nombres_count)
                WHEN 0 THEN 'María García'
                WHEN 1 THEN 'Ana López'
                WHEN 2 THEN 'Carmen Rodríguez'
                WHEN 3 THEN 'Isabel Martín'
                WHEN 4 THEN 'Pilar Sánchez'
                WHEN 5 THEN 'Teresa Fernández'
                WHEN 6 THEN 'Rosa Díaz'
                WHEN 7 THEN 'Elena Ruiz'
                WHEN 8 THEN 'Cristina Moreno'
                WHEN 9 THEN 'Patricia Jiménez'
                WHEN 10 THEN 'Laura González'
                WHEN 11 THEN 'Beatriz Álvarez'
                WHEN 12 THEN 'Mónica Romero'
                WHEN 13 THEN 'Silvia Navarro'
                WHEN 14 THEN 'Raquel Torres'
                WHEN 15 THEN 'Nuria Ramos'
                WHEN 16 THEN 'Carlos Mendoza'
                WHEN 17 THEN 'Alberto Silva'
                WHEN 18 THEN 'Diego Herrera'
                ELSE 'Manuel Castro'
            END;
            
            SET telefono_cliente = CONCAT('6', LPAD(FLOOR(RAND() * 100000000), 8, '0'));
            SET email_cliente = CONCAT(LOWER(REPLACE(nombre_cliente, ' ', '.')), '@email.com');

            SET servicio_solicitado = CASE FLOOR(RAND() * servicios_count)
                WHEN 0 THEN 'Corte de pelo'
                WHEN 1 THEN 'Tinte y mechas'
                WHEN 2 THEN 'Peinado para evento'
                WHEN 3 THEN 'Tratamiento capilar'
                WHEN 4 THEN 'Manicura y pedicura'
                WHEN 5 THEN 'Tratamiento facial'
                WHEN 6 THEN 'Depilación'
                WHEN 7 THEN 'Masaje relajante'
                WHEN 8 THEN 'Limpieza facial'
                WHEN 9 THEN 'Extensiones'
                WHEN 10 THEN 'Alisado brasileño'
                WHEN 11 THEN 'Color y corte'
                WHEN 12 THEN 'Recogido de novia'
                WHEN 13 THEN 'Tratamiento anti-edad'
                WHEN 14 THEN 'Microblading'
                ELSE 'Pestañas'
            END;

            SET estado_reserva = IF(RAND() < 0.7, 'confirmada', 'pendiente');

            SET notas_demo = CONCAT('[DEMO] ', 
                CASE FLOOR(RAND() * comentarios_count)
                    WHEN 0 THEN 'Cliente habitual, muy puntual'
                    WHEN 1 THEN 'Primera vez, viene recomendada'
                    WHEN 2 THEN 'Recordar que es alérgica a ciertos productos'
                    WHEN 3 THEN 'Cliente VIP, trato preferencial'
                    WHEN 4 THEN 'Quiere cambio de look radical'
                    WHEN 5 THEN 'Evento especial, necesita asesoramiento'
                    WHEN 6 THEN 'Cliente fiel desde hace años'
                    ELSE 'Viene con prisa, optimizar tiempo'
                END
            );

            SET created_timestamp = TIMESTAMP(
                DATE_SUB(fecha_actual, INTERVAL FLOOR(RAND() * 48) HOUR)
            );

            INSERT INTO reservas (
                usuario_id, nombre, telefono, email, fecha, hora,
                mensaje, estado, notas_internas, created_at, updated_at
            ) VALUES (
                demo_user_id, nombre_cliente, telefono_cliente, email_cliente,
                fecha_reserva, hora_reserva, CONCAT('Solicita: ', servicio_solicitado),
                estado_reserva, notas_demo, created_timestamp, created_timestamp
            );
            
            SET j = j + 1;
        END WHILE;
        
        SET i = i + 1;
    END WHILE;

    -- Generar cancelaciones aleatorias
    SET i = 0;
    WHILE i < 5 DO
        SET fecha_reserva = DATE_SUB(fecha_actual, INTERVAL FLOOR(1 + RAND() * 30) DAY);
        SET hora_reserva = ADDTIME('09:00:00', SEC_TO_TIME((FLOOR(RAND() * 21) * 1800)));
        
        SET nombre_cliente = CASE FLOOR(RAND() * nombres_count)
            WHEN 0 THEN 'María García'
            WHEN 1 THEN 'Ana López'
            WHEN 2 THEN 'Carmen Rodríguez'
            WHEN 3 THEN 'Isabel Martín'
            WHEN 4 THEN 'Pilar Sánchez'
            ELSE 'Teresa Fernández'
        END;
        
        SET telefono_cliente = CONCAT('6', LPAD(FLOOR(RAND() * 100000000), 8, '0'));
        SET email_cliente = CONCAT(LOWER(REPLACE(nombre_cliente, ' ', '.')), '@email.com');
        
        SET servicio_solicitado = CASE FLOOR(RAND() * 6)
            WHEN 0 THEN 'Corte de pelo'
            WHEN 1 THEN 'Tinte y mechas'
            WHEN 2 THEN 'Tratamiento facial'
            WHEN 3 THEN 'Manicura'
            WHEN 4 THEN 'Depilación'
            ELSE 'Masaje'
        END;
        
        SET notas_demo = CONCAT('[DEMO] Cancelación: ', 
            CASE FLOOR(RAND() * 3)
                WHEN 0 THEN 'Cliente canceló'
                WHEN 1 THEN 'No pudo venir'
                ELSE 'Cambio de planes'
            END
        );
        
        SET created_timestamp = TIMESTAMP(
            DATE_SUB(
                TIMESTAMP(fecha_reserva, hora_reserva), 
                INTERVAL (3600 + FLOOR(RAND() * 82800)) SECOND
            )
        );
        
        INSERT INTO reservas (
            usuario_id, nombre, telefono, email, fecha, hora,
            mensaje, estado, notas_internas, created_at, updated_at
        ) VALUES (
            demo_user_id, nombre_cliente, telefono_cliente, email_cliente,
            fecha_reserva, hora_reserva, CONCAT('Solicita: ', servicio_solicitado),
            'cancelada', notas_demo, created_timestamp, created_timestamp
        );
        
        SET i = i + 1;
    END WHILE;
END $$

DELIMITER ;

CREATE TABLE configuraciones (
  id int(11) NOT NULL,
  clave varchar(100) NOT NULL,
  valor text NOT NULL,
  descripcion text DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración global del sistema';

INSERT INTO configuraciones (id, clave, valor, descripcion, created_at, updated_at) VALUES
(1, 'app_name', 'ReservaBot', 'Nombre de la aplicación', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(2, 'app_version', '4.0', 'Versión de la aplicación', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(3, 'timezone', 'Europe/Madrid', 'Zona horaria del sistema', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(4, 'date_format', 'd/m/Y', 'Formato de fecha por defecto', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(5, 'time_format', 'H:i', 'Formato de hora por defecto', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(24, 'email_from_address', 'auto@reservabot.es', 'Email remitente', '2025-05-22 20:36:19', '2025-05-26 20:50:49'),
(25, 'email_from_name', 'ReservaBot', 'Nombre remitente', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(26, 'whatsapp_enabled', 'true', 'Activar integración con WhatsApp', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(27, 'whatsapp_server_url', 'http://localhost:3000', 'URL del servidor de WhatsApp', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(28, 'whatsapp_api_key', '', 'Clave API del servidor WhatsApp', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(29, 'whatsapp_status', 'disconnected', 'Estado actual de WhatsApp', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(30, 'whatsapp_last_activity', '', 'Última actividad de WhatsApp', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(31, 'whatsapp_webhook_url', '', 'URL del webhook para WhatsApp', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(51, 'session_timeout', '1440', 'Tiempo de expiración de sesión en minutos', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(52, 'max_login_attempts', '5', 'Máximo intentos de login', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(53, 'enable_2fa', 'false', 'Activar autenticación de dos factores', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(54, 'backup_enabled', 'true', 'Activar respaldos automáticos', '2025-05-22 20:36:19', '2025-05-22 20:36:19'),
(55, 'backup_frequency', 'daily', 'Frecuencia de respaldos: daily, weekly, monthly', '2025-05-22 20:36:19', '2025-05-22 20:36:19');

CREATE TABLE configuraciones_usuario (
  id int(11) NOT NULL,
  usuario_id int(11) NOT NULL,
  clave varchar(100) NOT NULL,
  valor text NOT NULL,
  descripcion text DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuraciones específicas por usuario';

INSERT INTO configuraciones_usuario (id, usuario_id, clave, valor, descripcion, created_at, updated_at) VALUES
(1, 1, 'app_name', 'ReservaBot Admin', 'Nombre de la aplicación', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(2, 1, 'timezone', 'Europe/Madrid', 'Zona horaria del sistema', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(3, 1, 'date_format', 'd/m/Y', 'Formato de fecha por defecto', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(4, 1, 'time_format', 'H:i', 'Formato de hora por defecto', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(5, 1, 'modo_aceptacion', 'manual', 'Modo de aceptación: manual o automatico', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(6, 1, 'intervalo_reservas', '30', 'Intervalo entre reservas en minutos', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(7, 1, 'dias_anticipacion_max', '30', 'Días máximos de anticipación para reservas', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(8, 1, 'dias_anticipacion_min', '0', 'Días mínimos de anticipación para reservas', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(9, 1, 'max_reservas_por_dia', '20', 'Máximo número de reservas por día', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(10, 1, 'permitir_reservas_fines_semana', 'true', 'Permitir reservas en fines de semana', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(11, 1, 'horario_lun', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', 'Horario de lunes', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(12, 1, 'horario_mar', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', 'Horario de martes', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(13, 1, 'horario_mie', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', 'Horario de miércoles', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(14, 1, 'horario_jue', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', 'Horario de jueves', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(15, 1, 'horario_vie', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', 'Horario de viernes', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(16, 1, 'horario_sab', 'true|[{\"inicio\":\"10:00\",\"fin\":\"14:00\"}]', 'Horario de sábado', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(17, 1, 'horario_dom', 'false|[]', 'Horario de domingo', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(18, 1, 'whatsapp_enabled', 'true', 'Activar integración con WhatsApp', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(19, 1, 'whatsapp_server_url', 'http://localhost:3000', 'URL del servidor de WhatsApp', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(20, 1, 'whatsapp_api_key', '', 'Clave API del servidor WhatsApp', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(21, 1, 'whatsapp_status', 'disconnected', 'Estado actual de WhatsApp', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(22, 1, 'mensaje_bienvenida', '¡Hola! 👋 Soy el asistente virtual de ReservaBot.\n\n¿En qué puedo ayudarte hoy?\n\n• Escribe \"reserva\" para hacer una cita\n• Escribe \"horarios\" para ver nuestros horarios\n• Escribe \"info\" para más información', 'Mensaje de bienvenida automático', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(23, 1, 'mensaje_confirmacion', '✅ *¡Reserva Confirmada!*\n\nTu cita para el *{fecha}* a las *{hora}* ha sido confirmada.\n\n📍 Te esperamos puntualmente.\n📞 Si necesitas cambios, contáctanos con antelación.\n\n¡Gracias por elegirnos!', 'Mensaje de confirmación de reserva', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(24, 1, 'theme_color', '#3B82F6', 'Color principal del tema', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(25, 1, 'empresa_nombre', 'ReservaBot Admin', 'Nombre de la empresa', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(26, 1, 'empresa_telefono', '+34900000000', 'Teléfono de la empresa', '2025-05-26 20:19:05', '2025-05-26 20:19:05'),
(121, 19, 'empresa_nombre', 'Estética Belleza', NULL, '2025-10-27 21:07:04', '2025-11-07 08:50:12'),
(122, 19, 'empresa_telefono', '666666666', NULL, '2025-10-27 21:07:04', '2025-11-07 12:55:47'),
(123, 19, 'modo_aceptacion', 'manual', NULL, '2025-10-27 21:07:04', '2025-10-27 21:07:04'),
(124, 19, 'intervalo_reservas', '30', NULL, '2025-10-27 21:07:04', '2025-11-06 13:58:52'),
(125, 19, 'horario_lun', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', NULL, '2025-10-27 21:07:04', '2025-10-27 21:07:04'),
(126, 19, 'horario_mar', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', NULL, '2025-10-27 21:07:04', '2025-10-27 21:07:04'),
(127, 19, 'horario_mie', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', NULL, '2025-10-27 21:07:04', '2025-10-27 21:07:04'),
(128, 19, 'horario_jue', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', NULL, '2025-10-27 21:07:04', '2025-10-27 21:07:04'),
(129, 19, 'horario_vie', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', NULL, '2025-10-27 21:07:04', '2025-10-27 21:07:04'),
(130, 19, 'horario_sab', 'true|[{\"inicio\":\"10:00\",\"fin\":\"14:00\"}]', NULL, '2025-10-27 21:07:04', '2025-10-27 21:07:04'),
(131, 19, 'horario_dom', 'false|[]', NULL, '2025-10-27 21:07:04', '2025-10-27 21:07:04'),
(143, 19, 'empresa_imagen', 'data:image/png;base64,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', NULL, '2025-11-04 17:11:11', '2025-11-07 08:59:50'),
(144, 19, 'color_primario', '#060d6a', NULL, '2025-11-04 17:11:11', '2025-11-07 09:00:41'),
(145, 19, 'color_secundario', '#7a0070', NULL, '2025-11-04 17:11:11', '2025-11-07 08:49:39'),
(147, 19, 'empresa_direccion', 'Calle Fuente de la Teja 41', NULL, '2025-11-04 17:11:11', '2025-11-07 12:31:20'),
(148, 19, 'empresa_web', 'https://www.estetica-belleza.es', NULL, '2025-11-04 17:11:11', '2025-11-07 08:50:12'),
(149, 19, 'duracion_reserva', '60', NULL, '2025-11-04 17:11:11', '2025-11-06 13:58:52'),
(151, 19, 'tipos_dia', '{\"abierto\":{\"nombre\":\"Abierto\",\"ventanas\":[{\"inicio\":\"09:00\",\"fin\":\"18:00\",\"capacidad\":1}]},\"cerrado\":{\"nombre\":\"Cerrado\",\"ventanas\":[]},\"tipo_1762437390887\":{\"nombre\":\"Sólo mañana\",\"ventanas\":[{\"inicio\":\"09:00\",\"fin\":\"14:00\",\"capacidad\":1}]}}', NULL, '2025-11-04 17:11:11', '2025-11-06 13:57:01'),
(152, 19, 'mapeo_semana', '{\"lun\":\"abierto\",\"mar\":\"abierto\",\"mie\":\"abierto\",\"jue\":\"abierto\",\"vie\":\"abierto\",\"sab\":\"cerrado\",\"dom\":\"cerrado\"}', NULL, '2025-11-04 17:11:11', '2025-11-04 17:11:11'),
(277, 20, 'app_name', 'Activa Soluciones', NULL, '2025-11-06 10:08:39', '2025-11-06 10:08:39'),
(278, 20, 'empresa_nombre', 'Activa Soluciones', NULL, '2025-11-06 10:08:39', '2025-11-06 10:08:39'),
(279, 20, 'empresa_telefono', '', NULL, '2025-11-06 10:08:39', '2025-11-06 10:08:39'),
(280, 20, 'modo_aceptacion', 'manual', NULL, '2025-11-06 10:08:39', '2025-11-06 10:08:39'),
(281, 20, 'intervalo_reservas', '30', NULL, '2025-11-06 10:08:39', '2025-11-06 10:08:39'),
(282, 20, 'horario_lun', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', NULL, '2025-11-06 10:08:39', '2025-11-06 10:08:39'),
(283, 20, 'horario_mar', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', NULL, '2025-11-06 10:08:39', '2025-11-06 10:08:39'),
(284, 20, 'horario_mie', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', NULL, '2025-11-06 10:08:39', '2025-11-06 10:08:39'),
(285, 20, 'horario_jue', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', NULL, '2025-11-06 10:08:39', '2025-11-06 10:08:39'),
(286, 20, 'horario_vie', 'true|[{\"inicio\":\"09:00\",\"fin\":\"18:00\"}]', NULL, '2025-11-06 10:08:39', '2025-11-06 10:08:39'),
(287, 20, 'horario_sab', 'true|[{\"inicio\":\"10:00\",\"fin\":\"14:00\"}]', NULL, '2025-11-06 10:08:39', '2025-11-06 10:08:39'),
(288, 20, 'horario_dom', 'false|[]', NULL, '2025-11-06 10:08:39', '2025-11-06 10:08:39');

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

INSERT INTO conversaciones (id, usuario_id, cliente_phone, cliente_nombre, ultimo_mensaje, no_leidos, created_at, updated_at) VALUES
(1, 1, '34665871857', '34665871857', 'jajaja', 0, '2025-06-11 13:58:55', '2025-06-11 21:09:49');

CREATE TABLE formularios_publicos (
  id int(11) NOT NULL,
  usuario_id int(11) DEFAULT NULL,
  nombre varchar(150) NOT NULL,
  descripcion text DEFAULT NULL,
  slug varchar(100) NOT NULL,
  confirmacion_automatica tinyint(1) NOT NULL DEFAULT 0,
  activo tinyint(1) NOT NULL DEFAULT 1,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Formularios públicos para reservas online';

INSERT INTO formularios_publicos (id, usuario_id, nombre, descripcion, slug, confirmacion_automatica, activo, created_at, updated_at) VALUES
(9, 6, 'Fisioterapia Julian', '', '2279', 1, 1, '2025-06-04 14:25:28', '2025-06-04 14:25:28'),
(10, 6, 'Fisioterapia Julian', '', 'f676', 0, 1, '2025-06-04 14:27:33', '2025-06-04 14:27:33'),
(12, 1, 'Reserva general consulta', '', 'b067', 0, 1, '2025-06-05 19:50:52', '2025-06-05 19:50:52'),
(13, 1, 'Reserva tu cita', 'Cita para consulta2', '7b26', 1, 1, '2025-06-06 09:05:57', '2025-06-06 09:27:30'),
(14, 18, 'Cita para presupuesto', NULL, 'a039138b', 0, 1, '2025-10-26 16:09:23', '2025-10-26 16:09:23'),
(15, 19, 'Formulario ejemplo', NULL, '6e31a6ec', 0, 1, '2025-10-31 14:03:46', '2025-10-31 14:03:46'),
(17, 19, 'Esto no se tiene que ver', 'Esto si se tiene que ver', '15689fd5', 0, 1, '2025-11-07 12:18:34', '2025-11-07 12:18:34');

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

INSERT INTO reservas (id, usuario_id, nombre, telefono, whatsapp_id, email, fecha, hora, mensaje, estado, notas_internas, created_at, updated_at, access_token, token_expires, formulario_id) VALUES
(26, 6, 'Andrea Ucar', '665871857', '665871857', NULL, '2025-06-04', '18:00:00', '', 'confirmada', NULL, '2025-06-04 08:14:07', '2025-06-04 08:14:07', NULL, NULL, NULL),
(27, 6, 'Andrea Ucar', '222222', '11111', NULL, '2025-06-04', '10:00:00', '', 'confirmada', NULL, '2025-06-04 13:16:34', '2025-06-04 14:11:43', NULL, NULL, NULL),
(30, 6, 'Iker Zuazu', '98748579', NULL, NULL, '2025-06-05', '14:00:00', 'asdasd', 'confirmada', NULL, '2025-06-04 14:11:14', '2025-06-04 14:11:14', NULL, NULL, NULL),
(31, 6, 'Mikel', '665871857', NULL, NULL, '2025-06-05', '14:30:00', '', 'confirmada', NULL, '2025-06-04 14:26:42', '2025-06-04 14:26:42', NULL, NULL, NULL),
(32, 6, 'Aimar', '665871887', NULL, NULL, '2025-06-06', '12:00:00', '', 'confirmada', NULL, '2025-06-04 14:28:44', '2025-06-04 14:28:44', NULL, NULL, NULL),
(36, 6, 'Mikel', '666112268', NULL, 'ikerzuazu@gmail.com', '2025-06-07', '12:00:00', '', 'confirmada', NULL, '2025-06-04 20:16:15', '2025-06-04 20:16:15', '6d4387ea744e40ae32f870b06298d2920e343ce07ffcecb9d19b0fc3b70cd6ee', '2025-07-04 20:16:15', NULL),
(52, 0, 'Iker', '665871857', '+34665871857', NULL, '2025-06-16', '12:00:00', '', 'pendiente', NULL, '2025-06-16 09:03:39', '2025-06-16 09:03:39', NULL, NULL, NULL),
(3389, 18, 'Jose Luis', '111111', '111111', '11111@yo.com', '2025-10-27', '10:00:00', '', 'pendiente', NULL, '2025-10-26 21:39:59', '2025-10-26 21:39:59', '958b49e3c228e1cf72f8667c059cf562d8bb90eeae5da65bc5d28fc913ae9646', '2025-11-25 21:39:59', 14),
(3390, 18, 'proton', '665871857', '+34665871857', 'iker.zuazu@proton.me', '2025-10-28', '11:00:00', '', 'confirmada', NULL, '2025-10-26 21:41:09', '2025-10-26 21:41:55', 'b93f1e095b52a75f0cf52f98b813ea2d5b5fbedef47de489a760bc68432aa3aa', '2025-11-25 21:41:09', 14),
(3931, 19, 'Iker Zuazu', '34665871857', '+34665871857', NULL, '2025-11-05', '11:30:00', '', 'confirmada', NULL, '2025-11-04 13:24:44', '2025-11-04 13:25:11', NULL, NULL, NULL),
(3932, 19, 'Andrea Ucar', '34669110135', '+34669110135', NULL, '2025-11-07', '09:00:00', 'Presupuesto de casa', 'confirmada', NULL, '2025-11-04 13:26:46', '2025-11-04 13:27:47', NULL, NULL, NULL),
(3933, 19, 'Aimar Ucar', '34666112233', '+34666112233', 'ikericus@hotmail.com', '2025-11-07', '10:00:00', 'lololo', 'confirmada', NULL, '2025-11-04 13:33:05', '2025-11-04 13:35:38', '2327420c4cb6435635edaaac5b4a7d15417d87ce632c6a7f110d4ad02805a67a', '2025-12-04 13:33:05', 15),
(3934, 19, 'Paco Salas', '34666000000', '+34666000000', 'ikerzuazu@gmail.com', '2025-11-10', '10:00:00', 'a ver', 'cancelada', NULL, '2025-11-04 13:55:32', '2025-11-04 14:05:42', '8e4763f4250a2092636cc0cd530fdeb157a874fc37cbdd6a11241871699bafd9', '2025-12-04 13:55:32', 15),
(3935, 19, 'Gustavo', '675843746', '+34675843746', 'ikerzuazu@gmail.com', '2025-11-05', '13:00:00', 'Quiero cita', 'confirmada', NULL, '2025-11-04 17:33:48', '2025-11-05 08:19:59', 'c464a781780ce64325f91537c40d3b86803e011dccbdc599ca2a017d45dc2351', '2025-12-04 17:33:48', 15),
(3936, 19, 'Aimar Ucar', '34666112233', '+34666112233', NULL, '2025-11-06', '11:00:00', '', 'cancelada', NULL, '2025-11-05 20:29:30', '2025-11-05 20:41:36', NULL, NULL, NULL),
(4972, 19, 'Iker Zuazu', '34665871857', '+34665871857', 'iker.zuazu@proton.me', '2025-11-07', '15:00:00', 'Yeahh', 'pendiente', NULL, '2025-11-07 10:34:52', '2025-11-07 10:34:52', '8ff22533ff521810364b6a3633a85e6cdc97be9193e20ec72f19cd03eb6b14c3', '2025-12-07 10:34:52', 15),
(4973, 19, 'Iker Zuazu', '34665871857', '+34665871857', 'iker.zuazu@proton.me', '2025-11-07', '17:00:00', 'aaaa', 'pendiente', NULL, '2025-11-07 10:49:16', '2025-11-07 10:49:16', 'ac6d9e6edb2d090b5aade444e299b6bb71b52e52d11bd58bf1837fb2d0e7042d', '2025-12-07 10:49:16', 15),
(4974, 19, 'Iker Zuazu', '34665871857', '+34665871857', 'iker.zuazu@proton.me', '2025-11-07', '17:30:00', ':)', 'pendiente', NULL, '2025-11-07 10:57:45', '2025-11-07 10:57:45', 'dc5e9e612a8d0e8992be13e36075114f3b19f098d34112467e89f1a6dee3be74', '2025-12-07 10:57:45', 15),
(4975, 19, 'Iker Zuazu', '34665871857', '+34665871857', 'iker.zuazu@proton.me', '2025-11-07', '16:30:00', '', 'pendiente', NULL, '2025-11-07 11:08:09', '2025-11-07 11:08:09', 'fc7bca11660d4cb15db82f0aeb57a3577059b6023f57dd3cf551afc9c457b911', '2025-12-07 11:08:09', 15),
(5151, 1, 'Teresa Fernández', '662228617', NULL, 'teresa.fernández@email.com', '2025-10-08', '14:30:00', 'Solicita: Tratamiento facial', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-07 23:33:36', '2025-10-07 23:33:36', NULL, NULL, NULL),
(5152, 1, 'Silvia Navarro', '668542814', NULL, 'silvia.navarro@email.com', '2025-10-08', '18:00:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-08 06:11:38', '2025-10-08 06:11:38', NULL, NULL, NULL),
(5153, 1, 'Ana López', '636219621', NULL, 'ana.lópez@email.com', '2025-10-09', '12:30:00', 'Solicita: Extensiones', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-10-09 09:24:14', '2025-10-09 09:24:14', NULL, NULL, NULL),
(5154, 1, 'Mónica Romero', '651238822', NULL, 'mónica.romero@email.com', '2025-10-09', '10:30:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-10-08 21:18:27', '2025-10-08 21:18:27', NULL, NULL, NULL),
(5155, 1, 'Cristina Moreno', '657482609', NULL, 'cristina.moreno@email.com', '2025-10-09', '11:00:00', 'Solicita: Limpieza facial', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-08 18:31:20', '2025-10-08 18:31:20', NULL, NULL, NULL),
(5156, 1, 'Nuria Ramos', '631557009', NULL, 'nuria.ramos@email.com', '2025-10-09', '10:30:00', 'Solicita: Manicura y pedicura', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-09 07:45:08', '2025-10-09 07:45:08', NULL, NULL, NULL),
(5157, 1, 'Beatriz Álvarez', '682388701', NULL, 'beatriz.álvarez@email.com', '2025-10-10', '15:30:00', 'Solicita: Masaje relajante', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-10 07:01:43', '2025-10-10 07:01:43', NULL, NULL, NULL),
(5158, 1, 'Pilar Sánchez', '674331387', NULL, 'pilar.sánchez@email.com', '2025-10-10', '13:30:00', 'Solicita: Tinte y mechas', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-10-09 20:32:11', '2025-10-09 20:32:11', NULL, NULL, NULL),
(5159, 1, 'Cristina Moreno', '645642077', NULL, 'cristina.moreno@email.com', '2025-10-10', '18:00:00', 'Solicita: Pestañas', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-09 23:12:20', '2025-10-09 23:12:20', NULL, NULL, NULL),
(5160, 1, 'Alberto Silva', '657056895', NULL, 'alberto.silva@email.com', '2025-10-10', '11:00:00', 'Solicita: Manicura y pedicura', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-09 16:36:17', '2025-10-09 16:36:17', NULL, NULL, NULL),
(5161, 1, 'Carmen Rodríguez', '614010911', NULL, 'carmen.rodríguez@email.com', '2025-10-11', '17:00:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-10 23:42:33', '2025-10-10 23:42:33', NULL, NULL, NULL),
(5162, 1, 'Alberto Silva', '688140718', NULL, 'alberto.silva@email.com', '2025-10-11', '17:00:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-11 07:04:05', '2025-10-11 07:04:05', NULL, NULL, NULL),
(5163, 1, 'Nuria Ramos', '627060135', NULL, 'nuria.ramos@email.com', '2025-10-11', '10:30:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-11 05:45:49', '2025-10-11 05:45:49', NULL, NULL, NULL),
(5164, 1, 'Beatriz Álvarez', '669056425', NULL, 'beatriz.álvarez@email.com', '2025-10-11', '13:00:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-10 16:14:58', '2025-10-10 16:14:58', NULL, NULL, NULL),
(5165, 1, 'Ana López', '605254342', NULL, 'ana.lópez@email.com', '2025-10-11', '10:00:00', 'Solicita: Pestañas', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-10-11 08:35:12', '2025-10-11 08:35:12', NULL, NULL, NULL),
(5166, 1, 'María García', '683443963', NULL, 'maría.garcía@email.com', '2025-10-12', '13:00:00', 'Solicita: Tinte y mechas', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-11 13:11:57', '2025-10-11 13:11:57', NULL, NULL, NULL),
(5167, 1, 'Carlos Mendoza', '677833523', NULL, 'carlos.mendoza@email.com', '2025-10-12', '16:30:00', 'Solicita: Masaje relajante', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-12 03:59:36', '2025-10-12 03:59:36', NULL, NULL, NULL),
(5168, 1, 'Rosa Díaz', '600905615', NULL, 'rosa.díaz@email.com', '2025-10-12', '14:30:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-12 08:31:26', '2025-10-12 08:31:26', NULL, NULL, NULL),
(5169, 1, 'Manuel Castro', '606412875', NULL, 'manuel.castro@email.com', '2025-10-12', '18:30:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-12 10:51:42', '2025-10-12 10:51:42', NULL, NULL, NULL),
(5170, 1, 'Silvia Navarro', '635929171', NULL, 'silvia.navarro@email.com', '2025-10-12', '16:00:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-11 17:08:44', '2025-10-11 17:08:44', NULL, NULL, NULL),
(5171, 1, 'Rosa Díaz', '624178559', NULL, 'rosa.díaz@email.com', '2025-10-13', '17:00:00', 'Solicita: Manicura y pedicura', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-13 12:39:53', '2025-10-13 12:39:53', NULL, NULL, NULL),
(5172, 1, 'Elena Ruiz', '607362001', NULL, 'elena.ruiz@email.com', '2025-10-13', '19:00:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-12 19:35:55', '2025-10-12 19:35:55', NULL, NULL, NULL),
(5173, 1, 'Carlos Mendoza', '693107894', NULL, 'carlos.mendoza@email.com', '2025-10-13', '16:30:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-13 11:09:35', '2025-10-13 11:09:35', NULL, NULL, NULL),
(5174, 1, 'Teresa Fernández', '695815766', NULL, 'teresa.fernández@email.com', '2025-10-13', '10:30:00', 'Solicita: Microblading', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-12 20:40:58', '2025-10-12 20:40:58', NULL, NULL, NULL),
(5175, 1, 'Raquel Torres', '625488471', NULL, 'raquel.torres@email.com', '2025-10-14', '13:30:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-14 07:37:02', '2025-10-14 07:37:02', NULL, NULL, NULL),
(5176, 1, 'Nuria Ramos', '610261995', NULL, 'nuria.ramos@email.com', '2025-10-14', '12:00:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-14 00:01:18', '2025-10-14 00:01:18', NULL, NULL, NULL),
(5177, 1, 'Carlos Mendoza', '641542254', NULL, 'carlos.mendoza@email.com', '2025-10-14', '11:30:00', 'Solicita: Extensiones', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-14 02:32:34', '2025-10-14 02:32:34', NULL, NULL, NULL),
(5178, 1, 'Cristina Moreno', '646169443', NULL, 'cristina.moreno@email.com', '2025-10-14', '18:00:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-14 03:20:01', '2025-10-14 03:20:01', NULL, NULL, NULL),
(5179, 1, 'Ana López', '607004417', NULL, 'ana.lópez@email.com', '2025-10-14', '17:00:00', 'Solicita: Tinte y mechas', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-14 03:06:56', '2025-10-14 03:06:56', NULL, NULL, NULL),
(5180, 1, 'Beatriz Álvarez', '692523105', NULL, 'beatriz.álvarez@email.com', '2025-10-14', '15:30:00', 'Solicita: Microblading', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-13 17:57:45', '2025-10-13 17:57:45', NULL, NULL, NULL),
(5181, 1, 'Patricia Jiménez', '663850058', NULL, 'patricia.jiménez@email.com', '2025-10-15', '18:00:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-10-15 01:28:18', '2025-10-15 01:28:18', NULL, NULL, NULL),
(5182, 1, 'Elena Ruiz', '677225222', NULL, 'elena.ruiz@email.com', '2025-10-15', '16:00:00', 'Solicita: Recogido de novia', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-15 02:19:20', '2025-10-15 02:19:20', NULL, NULL, NULL),
(5183, 1, 'Patricia Jiménez', '624584445', NULL, 'patricia.jiménez@email.com', '2025-10-15', '09:00:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-14 13:56:48', '2025-10-14 13:56:48', NULL, NULL, NULL),
(5184, 1, 'Isabel Martín', '671549075', NULL, 'isabel.martín@email.com', '2025-10-16', '12:30:00', 'Solicita: Tinte y mechas', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-15 21:47:03', '2025-10-15 21:47:03', NULL, NULL, NULL),
(5185, 1, 'Elena Ruiz', '638957915', NULL, 'elena.ruiz@email.com', '2025-10-16', '17:30:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-16 15:00:23', '2025-10-16 15:00:23', NULL, NULL, NULL),
(5186, 1, 'Diego Herrera', '667675266', NULL, 'diego.herrera@email.com', '2025-10-16', '15:30:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-10-16 05:36:16', '2025-10-16 05:36:16', NULL, NULL, NULL),
(5187, 1, 'Cristina Moreno', '666938846', NULL, 'cristina.moreno@email.com', '2025-10-16', '10:00:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-16 06:30:37', '2025-10-16 06:30:37', NULL, NULL, NULL),
(5188, 1, 'Beatriz Álvarez', '662767851', NULL, 'beatriz.álvarez@email.com', '2025-10-17', '17:00:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-16 19:03:25', '2025-10-16 19:03:25', NULL, NULL, NULL),
(5189, 1, 'Alberto Silva', '689902103', NULL, 'alberto.silva@email.com', '2025-10-17', '14:30:00', 'Solicita: Recogido de novia', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-10-17 05:46:16', '2025-10-17 05:46:16', NULL, NULL, NULL),
(5190, 1, 'Ana López', '660999347', NULL, 'ana.lópez@email.com', '2025-10-17', '15:30:00', 'Solicita: Recogido de novia', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-16 21:47:45', '2025-10-16 21:47:45', NULL, NULL, NULL),
(5191, 1, 'Alberto Silva', '663245829', NULL, 'alberto.silva@email.com', '2025-10-17', '15:00:00', 'Solicita: Masaje relajante', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-16 15:17:45', '2025-10-16 15:17:45', NULL, NULL, NULL),
(5192, 1, 'Alberto Silva', '606352717', NULL, 'alberto.silva@email.com', '2025-10-18', '13:30:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-17 14:42:40', '2025-10-17 14:42:40', NULL, NULL, NULL),
(5193, 1, 'Rosa Díaz', '607268767', NULL, 'rosa.díaz@email.com', '2025-10-18', '17:30:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-18 11:03:31', '2025-10-18 11:03:31', NULL, NULL, NULL),
(5194, 1, 'Silvia Navarro', '696482706', NULL, 'silvia.navarro@email.com', '2025-10-18', '14:00:00', 'Solicita: Recogido de novia', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-17 18:38:32', '2025-10-17 18:38:32', NULL, NULL, NULL),
(5195, 1, 'Carmen Rodríguez', '680344610', NULL, 'carmen.rodríguez@email.com', '2025-10-18', '18:00:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-10-18 00:19:27', '2025-10-18 00:19:27', NULL, NULL, NULL),
(5196, 1, 'Rosa Díaz', '658614481', NULL, 'rosa.díaz@email.com', '2025-10-19', '12:00:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-10-19 06:11:38', '2025-10-19 06:11:38', NULL, NULL, NULL),
(5197, 1, 'Manuel Castro', '689165456', NULL, 'manuel.castro@email.com', '2025-10-19', '12:00:00', 'Solicita: Limpieza facial', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-18 19:07:33', '2025-10-18 19:07:33', NULL, NULL, NULL),
(5198, 1, 'Carmen Rodríguez', '616850831', NULL, 'carmen.rodríguez@email.com', '2025-10-20', '10:30:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-19 16:30:18', '2025-10-19 16:30:18', NULL, NULL, NULL),
(5199, 1, 'Pilar Sánchez', '656347202', NULL, 'pilar.sánchez@email.com', '2025-10-20', '18:00:00', 'Solicita: Tinte y mechas', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-20 14:04:40', '2025-10-20 14:04:40', NULL, NULL, NULL),
(5200, 1, 'Nuria Ramos', '695107987', NULL, 'nuria.ramos@email.com', '2025-10-20', '15:30:00', 'Solicita: Masaje relajante', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-19 23:25:03', '2025-10-19 23:25:03', NULL, NULL, NULL),
(5201, 1, 'Nuria Ramos', '670823526', NULL, 'nuria.ramos@email.com', '2025-10-21', '13:00:00', 'Solicita: Tratamiento capilar', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-10-20 20:31:52', '2025-10-20 20:31:52', NULL, NULL, NULL),
(5202, 1, 'María García', '668344419', NULL, 'maría.garcía@email.com', '2025-10-21', '17:00:00', 'Solicita: Tratamiento facial', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-21 04:07:49', '2025-10-21 04:07:49', NULL, NULL, NULL),
(5203, 1, 'Carlos Mendoza', '628416989', NULL, 'carlos.mendoza@email.com', '2025-10-22', '15:30:00', 'Solicita: Microblading', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-21 15:53:49', '2025-10-21 15:53:49', NULL, NULL, NULL),
(5204, 1, 'Elena Ruiz', '694752361', NULL, 'elena.ruiz@email.com', '2025-10-22', '16:00:00', 'Solicita: Extensiones', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-22 08:50:43', '2025-10-22 08:50:43', NULL, NULL, NULL),
(5205, 1, 'Manuel Castro', '682521129', NULL, 'manuel.castro@email.com', '2025-10-22', '12:30:00', 'Solicita: Tratamiento capilar', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-21 17:17:37', '2025-10-21 17:17:37', NULL, NULL, NULL),
(5206, 1, 'Rosa Díaz', '604631842', NULL, 'rosa.díaz@email.com', '2025-10-22', '14:30:00', 'Solicita: Tratamiento capilar', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-22 05:51:01', '2025-10-22 05:51:01', NULL, NULL, NULL),
(5207, 1, 'Elena Ruiz', '600068634', NULL, 'elena.ruiz@email.com', '2025-10-23', '11:30:00', 'Solicita: Microblading', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-23 00:12:43', '2025-10-23 00:12:43', NULL, NULL, NULL),
(5208, 1, 'Carmen Rodríguez', '676654488', NULL, 'carmen.rodríguez@email.com', '2025-10-23', '12:00:00', 'Solicita: Masaje relajante', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-23 05:20:21', '2025-10-23 05:20:21', NULL, NULL, NULL),
(5209, 1, 'Silvia Navarro', '699365705', NULL, 'silvia.navarro@email.com', '2025-10-23', '14:00:00', 'Solicita: Microblading', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-22 17:31:03', '2025-10-22 17:31:03', NULL, NULL, NULL),
(5210, 1, 'María García', '693664955', NULL, 'maría.garcía@email.com', '2025-10-23', '16:00:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-23 13:35:37', '2025-10-23 13:35:37', NULL, NULL, NULL),
(5211, 1, 'Elena Ruiz', '660226029', NULL, 'elena.ruiz@email.com', '2025-10-23', '10:00:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-22 20:48:49', '2025-10-22 20:48:49', NULL, NULL, NULL),
(5212, 1, 'Carmen Rodríguez', '621402211', NULL, 'carmen.rodríguez@email.com', '2025-10-24', '13:30:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-10-23 13:50:14', '2025-10-23 13:50:14', NULL, NULL, NULL),
(5213, 1, 'Laura González', '604942105', NULL, 'laura.gonzález@email.com', '2025-10-24', '14:00:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-24 04:58:04', '2025-10-24 04:58:04', NULL, NULL, NULL),
(5214, 1, 'Ana López', '660409053', NULL, 'ana.lópez@email.com', '2025-10-24', '11:00:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-23 13:20:45', '2025-10-23 13:20:45', NULL, NULL, NULL),
(5215, 1, 'Rosa Díaz', '653075386', NULL, 'rosa.díaz@email.com', '2025-10-24', '09:00:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-24 03:04:49', '2025-10-24 03:04:49', NULL, NULL, NULL),
(5216, 1, 'Nuria Ramos', '656981825', NULL, 'nuria.ramos@email.com', '2025-10-25', '13:30:00', 'Solicita: Limpieza facial', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-10-25 00:52:00', '2025-10-25 00:52:00', NULL, NULL, NULL),
(5217, 1, 'Ana López', '607900432', NULL, 'ana.lópez@email.com', '2025-10-25', '10:00:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-24 17:44:05', '2025-10-24 17:44:05', NULL, NULL, NULL),
(5218, 1, 'Cristina Moreno', '696408842', NULL, 'cristina.moreno@email.com', '2025-10-25', '09:30:00', 'Solicita: Masaje relajante', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-25 03:44:40', '2025-10-25 03:44:40', NULL, NULL, NULL),
(5219, 1, 'Beatriz Álvarez', '651113789', NULL, 'beatriz.álvarez@email.com', '2025-10-25', '13:30:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-10-25 05:24:36', '2025-10-25 05:24:36', NULL, NULL, NULL),
(5220, 1, 'Ana López', '686082431', NULL, 'ana.lópez@email.com', '2025-10-25', '11:00:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-24 21:31:07', '2025-10-24 21:31:07', NULL, NULL, NULL),
(5221, 1, 'Carmen Rodríguez', '617172753', NULL, 'carmen.rodríguez@email.com', '2025-10-25', '10:30:00', 'Solicita: Masaje relajante', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-25 07:31:21', '2025-10-25 07:31:21', NULL, NULL, NULL),
(5222, 1, 'Pilar Sánchez', '639475917', NULL, 'pilar.sánchez@email.com', '2025-10-26', '11:30:00', 'Solicita: Tratamiento capilar', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-25 22:34:04', '2025-10-25 22:34:04', NULL, NULL, NULL),
(5223, 1, 'Mónica Romero', '684536762', NULL, 'mónica.romero@email.com', '2025-10-26', '13:30:00', 'Solicita: Tratamiento facial', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-25 22:11:51', '2025-10-25 22:11:51', NULL, NULL, NULL),
(5224, 1, 'Carlos Mendoza', '692367785', NULL, 'carlos.mendoza@email.com', '2025-10-26', '16:30:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-26 13:06:23', '2025-10-26 13:06:23', NULL, NULL, NULL),
(5225, 1, 'Nuria Ramos', '638681932', NULL, 'nuria.ramos@email.com', '2025-10-26', '17:30:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-26 13:23:03', '2025-10-26 13:23:03', NULL, NULL, NULL),
(5226, 1, 'Alberto Silva', '639445423', NULL, 'alberto.silva@email.com', '2025-10-26', '15:30:00', 'Solicita: Tratamiento facial', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-25 17:50:19', '2025-10-25 17:50:19', NULL, NULL, NULL),
(5227, 1, 'Pilar Sánchez', '646421365', NULL, 'pilar.sánchez@email.com', '2025-10-27', '18:00:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-26 20:44:25', '2025-10-26 20:44:25', NULL, NULL, NULL),
(5228, 1, 'Silvia Navarro', '693485242', NULL, 'silvia.navarro@email.com', '2025-10-27', '13:30:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-27 12:23:03', '2025-10-27 12:23:03', NULL, NULL, NULL),
(5229, 1, 'Diego Herrera', '616191625', NULL, 'diego.herrera@email.com', '2025-10-27', '11:00:00', 'Solicita: Pestañas', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-10-26 15:12:07', '2025-10-26 15:12:07', NULL, NULL, NULL),
(5230, 1, 'Carlos Mendoza', '601330944', NULL, 'carlos.mendoza@email.com', '2025-10-27', '09:30:00', 'Solicita: Limpieza facial', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-26 09:33:44', '2025-10-26 09:33:44', NULL, NULL, NULL),
(5231, 1, 'Manuel Castro', '642496210', NULL, 'manuel.castro@email.com', '2025-10-27', '17:00:00', 'Solicita: Tratamiento capilar', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-27 14:23:44', '2025-10-27 14:23:44', NULL, NULL, NULL),
(5232, 1, 'Carmen Rodríguez', '624045872', NULL, 'carmen.rodríguez@email.com', '2025-10-28', '10:00:00', 'Solicita: Microblading', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-27 15:09:46', '2025-10-27 15:09:46', NULL, NULL, NULL),
(5233, 1, 'Carlos Mendoza', '655041022', NULL, 'carlos.mendoza@email.com', '2025-10-28', '17:30:00', 'Solicita: Tratamiento facial', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-28 14:21:09', '2025-10-28 14:21:09', NULL, NULL, NULL),
(5234, 1, 'Nuria Ramos', '660907312', NULL, 'nuria.ramos@email.com', '2025-10-28', '13:00:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-28 07:17:33', '2025-10-28 07:17:33', NULL, NULL, NULL),
(5235, 1, 'Pilar Sánchez', '692915804', NULL, 'pilar.sánchez@email.com', '2025-10-28', '12:30:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-27 16:57:13', '2025-10-27 16:57:13', NULL, NULL, NULL),
(5236, 1, 'Cristina Moreno', '606353861', NULL, 'cristina.moreno@email.com', '2025-10-29', '09:00:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-10-29 05:02:34', '2025-10-29 05:02:34', NULL, NULL, NULL),
(5237, 1, 'María García', '657113634', NULL, 'maría.garcía@email.com', '2025-10-29', '14:00:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-29 07:57:34', '2025-10-29 07:57:34', NULL, NULL, NULL),
(5238, 1, 'Patricia Jiménez', '611175059', NULL, 'patricia.jiménez@email.com', '2025-10-29', '13:00:00', 'Solicita: Tinte y mechas', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-10-29 11:28:28', '2025-10-29 11:28:28', NULL, NULL, NULL),
(5239, 1, 'Rosa Díaz', '681107529', NULL, 'rosa.díaz@email.com', '2025-10-29', '11:30:00', 'Solicita: Tinte y mechas', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-10-29 04:18:51', '2025-10-29 04:18:51', NULL, NULL, NULL),
(5240, 1, 'Laura González', '661780361', NULL, 'laura.gonzález@email.com', '2025-10-29', '15:30:00', 'Solicita: Limpieza facial', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-29 10:45:57', '2025-10-29 10:45:57', NULL, NULL, NULL),
(5241, 1, 'Cristina Moreno', '630804857', NULL, 'cristina.moreno@email.com', '2025-10-29', '15:00:00', 'Solicita: Tratamiento facial', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-10-29 08:00:57', '2025-10-29 08:00:57', NULL, NULL, NULL),
(5242, 1, 'Patricia Jiménez', '610213620', NULL, 'patricia.jiménez@email.com', '2025-10-30', '10:00:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-10-29 13:11:23', '2025-10-29 13:11:23', NULL, NULL, NULL),
(5243, 1, 'Pilar Sánchez', '614803944', NULL, 'pilar.sánchez@email.com', '2025-10-30', '19:00:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-10-30 04:29:17', '2025-10-30 04:29:17', NULL, NULL, NULL),
(5244, 1, 'María García', '600121089', NULL, 'maría.garcía@email.com', '2025-10-30', '12:30:00', 'Solicita: Pestañas', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-10-30 06:05:46', '2025-10-30 06:05:46', NULL, NULL, NULL),
(5245, 1, 'Nuria Ramos', '650958762', NULL, 'nuria.ramos@email.com', '2025-10-31', '17:00:00', 'Solicita: Manicura y pedicura', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-10-31 15:53:57', '2025-10-31 15:53:57', NULL, NULL, NULL),
(5246, 1, 'Nuria Ramos', '659217453', NULL, 'nuria.ramos@email.com', '2025-10-31', '16:30:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-10-31 09:37:26', '2025-10-31 09:37:26', NULL, NULL, NULL),
(5247, 1, 'Ana López', '645038307', NULL, 'ana.lópez@email.com', '2025-10-31', '19:00:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-10-31 14:57:13', '2025-10-31 14:57:13', NULL, NULL, NULL),
(5248, 1, 'Isabel Martín', '643592581', NULL, 'isabel.martín@email.com', '2025-10-31', '10:00:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-10-31 02:09:00', '2025-10-31 02:09:00', NULL, NULL, NULL),
(5249, 1, 'Isabel Martín', '605614369', NULL, 'isabel.martín@email.com', '2025-10-31', '15:00:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-10-31 03:38:46', '2025-10-31 03:38:46', NULL, NULL, NULL),
(5250, 1, 'Patricia Jiménez', '682583677', NULL, 'patricia.jiménez@email.com', '2025-11-01', '18:00:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-11-01 03:43:15', '2025-11-01 03:43:15', NULL, NULL, NULL),
(5251, 1, 'Teresa Fernández', '689176499', NULL, 'teresa.fernández@email.com', '2025-11-01', '17:00:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-10-31 22:41:51', '2025-10-31 22:41:51', NULL, NULL, NULL),
(5252, 1, 'Carmen Rodríguez', '610582484', NULL, 'carmen.rodríguez@email.com', '2025-11-01', '13:30:00', 'Solicita: Tratamiento capilar', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-11-01 08:57:24', '2025-11-01 08:57:24', NULL, NULL, NULL),
(5253, 1, 'Manuel Castro', '646796910', NULL, 'manuel.castro@email.com', '2025-11-01', '14:00:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-11-01 06:36:42', '2025-11-01 06:36:42', NULL, NULL, NULL),
(5254, 1, 'Alberto Silva', '658580599', NULL, 'alberto.silva@email.com', '2025-11-01', '18:30:00', 'Solicita: Manicura y pedicura', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-11-01 09:06:26', '2025-11-01 09:06:26', NULL, NULL, NULL),
(5255, 1, 'Elena Ruiz', '662848026', NULL, 'elena.ruiz@email.com', '2025-11-02', '13:00:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-11-01 14:53:00', '2025-11-01 14:53:00', NULL, NULL, NULL),
(5256, 1, 'Rosa Díaz', '648125358', NULL, 'rosa.díaz@email.com', '2025-11-02', '09:00:00', 'Solicita: Masaje relajante', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-11-01 15:39:03', '2025-11-01 15:39:03', NULL, NULL, NULL),
(5257, 1, 'María García', '687328497', NULL, 'maría.garcía@email.com', '2025-11-02', '13:30:00', 'Solicita: Manicura y pedicura', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-11-01 21:05:13', '2025-11-01 21:05:13', NULL, NULL, NULL),
(5258, 1, 'Elena Ruiz', '603276266', NULL, 'elena.ruiz@email.com', '2025-11-02', '11:30:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-11-01 22:22:33', '2025-11-01 22:22:33', NULL, NULL, NULL),
(5259, 1, 'Beatriz Álvarez', '628727050', NULL, 'beatriz.álvarez@email.com', '2025-11-02', '11:00:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-11-01 16:46:53', '2025-11-01 16:46:53', NULL, NULL, NULL),
(5260, 1, 'Patricia Jiménez', '613484291', NULL, 'patricia.jiménez@email.com', '2025-11-02', '16:30:00', 'Solicita: Manicura y pedicura', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-11-02 02:17:39', '2025-11-02 02:17:39', NULL, NULL, NULL),
(5261, 1, 'Teresa Fernández', '660655710', NULL, 'teresa.fernández@email.com', '2025-11-03', '15:30:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-11-03 08:08:16', '2025-11-03 08:08:16', NULL, NULL, NULL),
(5262, 1, 'Alberto Silva', '675337804', NULL, 'alberto.silva@email.com', '2025-11-03', '14:30:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-11-02 19:04:19', '2025-11-02 19:04:19', NULL, NULL, NULL),
(5263, 1, 'Manuel Castro', '675713353', NULL, 'manuel.castro@email.com', '2025-11-03', '16:00:00', 'Solicita: Microblading', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-11-02 17:26:16', '2025-11-02 17:26:16', NULL, NULL, NULL),
(5264, 1, 'Beatriz Álvarez', '625459002', NULL, 'beatriz.álvarez@email.com', '2025-11-04', '14:00:00', 'Solicita: Extensiones', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-11-04 03:13:13', '2025-11-04 03:13:13', NULL, NULL, NULL),
(5265, 1, 'Silvia Navarro', '614259611', NULL, 'silvia.navarro@email.com', '2025-11-04', '13:00:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-11-03 14:40:35', '2025-11-03 14:40:35', NULL, NULL, NULL),
(5266, 1, 'María García', '631901933', NULL, 'maría.garcía@email.com', '2025-11-04', '19:00:00', 'Solicita: Masaje relajante', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-11-04 06:44:32', '2025-11-04 06:44:32', NULL, NULL, NULL),
(5267, 1, 'Ana López', '654432917', NULL, 'ana.lópez@email.com', '2025-11-04', '12:00:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-11-04 03:23:50', '2025-11-04 03:23:50', NULL, NULL, NULL),
(5268, 1, 'Elena Ruiz', '674799703', NULL, 'elena.ruiz@email.com', '2025-11-04', '09:30:00', 'Solicita: Limpieza facial', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-11-03 11:27:50', '2025-11-03 11:27:50', NULL, NULL, NULL),
(5269, 1, 'Manuel Castro', '662635136', NULL, 'manuel.castro@email.com', '2025-11-05', '13:00:00', 'Solicita: Tratamiento capilar', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-11-05 06:03:56', '2025-11-05 06:03:56', NULL, NULL, NULL),
(5270, 1, 'Manuel Castro', '673036920', NULL, 'manuel.castro@email.com', '2025-11-05', '16:30:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-11-05 04:51:11', '2025-11-05 04:51:11', NULL, NULL, NULL),
(5271, 1, 'Rosa Díaz', '687433634', NULL, 'rosa.díaz@email.com', '2025-11-06', '18:00:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-11-06 07:41:37', '2025-11-06 07:41:37', NULL, NULL, NULL),
(5272, 1, 'Nuria Ramos', '676360379', NULL, 'nuria.ramos@email.com', '2025-11-06', '13:00:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-11-06 08:14:48', '2025-11-06 08:14:48', NULL, NULL, NULL),
(5273, 1, 'Cristina Moreno', '669380842', NULL, 'cristina.moreno@email.com', '2025-11-08', '10:30:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-11-05 12:00:00', '2025-11-05 12:00:00', NULL, NULL, NULL),
(5274, 1, 'Cristina Moreno', '650875549', NULL, 'cristina.moreno@email.com', '2025-11-09', '11:00:00', 'Solicita: Tratamiento capilar', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-11-06 16:00:00', '2025-11-06 16:00:00', NULL, NULL, NULL),
(5275, 1, 'Raquel Torres', '664740080', NULL, 'raquel.torres@email.com', '2025-11-09', '15:30:00', 'Solicita: Tinte y mechas', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-11-06 15:00:00', '2025-11-06 15:00:00', NULL, NULL, NULL),
(5276, 1, 'Patricia Jiménez', '663151644', NULL, 'patricia.jiménez@email.com', '2025-11-09', '15:00:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-11-06 00:00:00', '2025-11-06 00:00:00', NULL, NULL, NULL),
(5277, 1, 'Nuria Ramos', '669161439', NULL, 'nuria.ramos@email.com', '2025-11-09', '09:30:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-11-06 13:00:00', '2025-11-06 13:00:00', NULL, NULL, NULL),
(5278, 1, 'Cristina Moreno', '673010709', NULL, 'cristina.moreno@email.com', '2025-11-10', '10:00:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-11-06 19:00:00', '2025-11-07 20:25:27', NULL, NULL, NULL),
(5279, 1, 'Cristina Moreno', '695114786', NULL, 'cristina.moreno@email.com', '2025-11-10', '17:00:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-11-06 02:00:00', '2025-11-06 02:00:00', NULL, NULL, NULL),
(5280, 1, 'Rosa Díaz', '674971130', NULL, 'rosa.díaz@email.com', '2025-11-11', '19:00:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-11-06 16:00:00', '2025-11-06 16:00:00', NULL, NULL, NULL),
(5281, 1, 'Ana López', '641739461', NULL, 'ana.lópez@email.com', '2025-11-11', '12:00:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-11-05 17:00:00', '2025-11-05 17:00:00', NULL, NULL, NULL),
(5282, 1, 'Teresa Fernández', '634198046', NULL, 'teresa.fernández@email.com', '2025-11-11', '15:30:00', 'Solicita: Pestañas', 'pendiente', '[DEMO] Evento especial, necesita asesoramiento', '2025-11-06 13:00:00', '2025-11-06 13:00:00', NULL, NULL, NULL),
(5283, 1, 'Ana López', '693543391', NULL, 'ana.lópez@email.com', '2025-11-11', '10:30:00', 'Solicita: Masaje relajante', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-11-05 07:00:00', '2025-11-05 07:00:00', NULL, NULL, NULL),
(5284, 1, 'Carmen Rodríguez', '609449473', NULL, 'carmen.rodríguez@email.com', '2025-11-12', '10:30:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-11-05 20:00:00', '2025-11-05 20:00:00', NULL, NULL, NULL),
(5285, 1, 'Carlos Mendoza', '620717395', NULL, 'carlos.mendoza@email.com', '2025-11-12', '12:00:00', 'Solicita: Limpieza facial', 'pendiente', '[DEMO] Cliente VIP, trato preferencial', '2025-11-06 19:00:00', '2025-11-06 19:00:00', NULL, NULL, NULL),
(5286, 1, 'Pilar Sánchez', '622550372', NULL, 'pilar.sánchez@email.com', '2025-11-12', '12:00:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-11-06 20:00:00', '2025-11-06 20:00:00', NULL, NULL, NULL),
(5287, 1, 'Teresa Fernández', '654670481', NULL, 'teresa.fernández@email.com', '2025-11-13', '19:00:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-11-05 11:00:00', '2025-11-05 11:00:00', NULL, NULL, NULL),
(5288, 1, 'Mónica Romero', '641912126', NULL, 'mónica.romero@email.com', '2025-11-13', '15:00:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-11-05 19:00:00', '2025-11-05 19:00:00', NULL, NULL, NULL),
(5289, 1, 'Mónica Romero', '646335717', NULL, 'mónica.romero@email.com', '2025-11-13', '18:00:00', 'Solicita: Masaje relajante', 'pendiente', '[DEMO] Evento especial, necesita asesoramiento', '2025-11-06 11:00:00', '2025-11-06 11:00:00', NULL, NULL, NULL),
(5290, 1, 'Manuel Castro', '635011755', NULL, 'manuel.castro@email.com', '2025-11-13', '10:30:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-11-05 07:00:00', '2025-11-05 07:00:00', NULL, NULL, NULL),
(5291, 1, 'Beatriz Álvarez', '610701757', NULL, 'beatriz.álvarez@email.com', '2025-11-14', '15:30:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-11-06 14:00:00', '2025-11-06 14:00:00', NULL, NULL, NULL),
(5292, 1, 'Carlos Mendoza', '691864156', NULL, 'carlos.mendoza@email.com', '2025-11-14', '10:00:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-11-05 16:00:00', '2025-11-05 16:00:00', NULL, NULL, NULL),
(5293, 1, 'Rosa Díaz', '646127585', NULL, 'rosa.díaz@email.com', '2025-11-14', '09:30:00', 'Solicita: Manicura y pedicura', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-11-05 19:00:00', '2025-11-05 19:00:00', NULL, NULL, NULL),
(5294, 1, 'Patricia Jiménez', '606887788', NULL, 'patricia.jiménez@email.com', '2025-11-14', '09:30:00', 'Solicita: Pestañas', 'pendiente', '[DEMO] Evento especial, necesita asesoramiento', '2025-11-06 07:00:00', '2025-11-06 07:00:00', NULL, NULL, NULL),
(5295, 1, 'Diego Herrera', '630079901', NULL, 'diego.herrera@email.com', '2025-11-15', '13:00:00', 'Solicita: Recogido de novia', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-11-06 14:00:00', '2025-11-06 14:00:00', NULL, NULL, NULL),
(5296, 1, 'Carmen Rodríguez', '627619391', NULL, 'carmen.rodríguez@email.com', '2025-11-15', '16:30:00', 'Solicita: Tinte y mechas', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-11-06 05:00:00', '2025-11-06 05:00:00', NULL, NULL, NULL),
(5297, 1, 'María García', '662827549', NULL, 'maría.garcía@email.com', '2025-11-15', '17:30:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-11-05 19:00:00', '2025-11-05 19:00:00', NULL, NULL, NULL),
(5298, 1, 'María García', '677626309', NULL, 'maría.garcía@email.com', '2025-11-16', '10:00:00', 'Solicita: Recogido de novia', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-11-05 13:00:00', '2025-11-05 13:00:00', NULL, NULL, NULL),
(5299, 1, 'María García', '671023672', NULL, 'maría.garcía@email.com', '2025-11-17', '10:00:00', 'Solicita: Masaje relajante', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-11-06 12:00:00', '2025-11-06 12:00:00', NULL, NULL, NULL),
(5300, 1, 'Silvia Navarro', '690909515', NULL, 'silvia.navarro@email.com', '2025-11-17', '14:00:00', 'Solicita: Limpieza facial', 'pendiente', '[DEMO] Viene con prisa, optimizar tiempo', '2025-11-05 14:00:00', '2025-11-05 14:00:00', NULL, NULL, NULL),
(5301, 1, 'Patricia Jiménez', '655636485', NULL, 'patricia.jiménez@email.com', '2025-11-17', '18:30:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-11-05 03:00:00', '2025-11-05 03:00:00', NULL, NULL, NULL),
(5302, 1, 'Pilar Sánchez', '635203422', NULL, 'pilar.sánchez@email.com', '2025-11-17', '15:00:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-11-06 19:00:00', '2025-11-06 19:00:00', NULL, NULL, NULL),
(5303, 1, 'Laura González', '682040664', NULL, 'laura.gonzález@email.com', '2025-11-18', '18:30:00', 'Solicita: Limpieza facial', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-11-05 09:00:00', '2025-11-05 09:00:00', NULL, NULL, NULL),
(5304, 1, 'Rosa Díaz', '642218156', NULL, 'rosa.díaz@email.com', '2025-11-19', '09:00:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-11-06 01:00:00', '2025-11-06 01:00:00', NULL, NULL, NULL),
(5305, 1, 'Teresa Fernández', '640490499', NULL, 'teresa.fernández@email.com', '2025-11-19', '12:30:00', 'Solicita: Peinado para evento', 'confirmada', '[DEMO] Cliente VIP, trato preferencial', '2025-11-06 00:00:00', '2025-11-06 00:00:00', NULL, NULL, NULL),
(5306, 1, 'Mónica Romero', '677903244', NULL, 'mónica.romero@email.com', '2025-11-20', '13:30:00', 'Solicita: Corte de pelo', 'pendiente', '[DEMO] Cliente habitual, muy puntual', '2025-11-05 13:00:00', '2025-11-05 13:00:00', NULL, NULL, NULL),
(5307, 1, 'Raquel Torres', '660641054', NULL, 'raquel.torres@email.com', '2025-11-21', '16:00:00', 'Solicita: Recogido de novia', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-11-05 19:00:00', '2025-11-05 19:00:00', NULL, NULL, NULL),
(5308, 1, 'Raquel Torres', '625623204', NULL, 'raquel.torres@email.com', '2025-11-21', '10:00:00', 'Solicita: Peinado para evento', 'pendiente', '[DEMO] Viene con prisa, optimizar tiempo', '2025-11-06 18:00:00', '2025-11-06 18:00:00', NULL, NULL, NULL),
(5309, 1, 'Manuel Castro', '619697851', NULL, 'manuel.castro@email.com', '2025-11-21', '18:00:00', 'Solicita: Tinte y mechas', 'pendiente', '[DEMO] Cliente fiel desde hace años', '2025-11-05 14:00:00', '2025-11-05 14:00:00', NULL, NULL, NULL),
(5310, 1, 'Carmen Rodríguez', '607698778', NULL, 'carmen.rodríguez@email.com', '2025-11-22', '14:00:00', 'Solicita: Corte de pelo', 'pendiente', '[DEMO] Cliente VIP, trato preferencial', '2025-11-06 07:00:00', '2025-11-06 07:00:00', NULL, NULL, NULL),
(5311, 1, 'Pilar Sánchez', '669139435', NULL, 'pilar.sánchez@email.com', '2025-11-23', '17:00:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-11-05 06:00:00', '2025-11-05 06:00:00', NULL, NULL, NULL),
(5312, 1, 'Pilar Sánchez', '629482108', NULL, 'pilar.sánchez@email.com', '2025-11-23', '19:00:00', 'Solicita: Color y corte', 'pendiente', '[DEMO] Cliente fiel desde hace años', '2025-11-06 05:00:00', '2025-11-06 05:00:00', NULL, NULL, NULL),
(5313, 1, 'Cristina Moreno', '601116007', NULL, 'cristina.moreno@email.com', '2025-11-23', '16:30:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Primera vez, viene recomendada', '2025-11-05 16:00:00', '2025-11-05 16:00:00', NULL, NULL, NULL),
(5314, 1, 'Manuel Castro', '646859825', NULL, 'manuel.castro@email.com', '2025-11-24', '17:00:00', 'Solicita: Masaje relajante', 'confirmada', '[DEMO] Evento especial, necesita asesoramiento', '2025-11-06 20:00:00', '2025-11-06 20:00:00', NULL, NULL, NULL),
(5315, 1, 'Beatriz Álvarez', '613809685', NULL, 'beatriz.álvarez@email.com', '2025-11-24', '14:30:00', 'Solicita: Pestañas', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-11-06 00:00:00', '2025-11-06 00:00:00', NULL, NULL, NULL),
(5316, 1, 'Pilar Sánchez', '691111492', NULL, 'pilar.sánchez@email.com', '2025-11-24', '16:30:00', 'Solicita: Tratamiento anti-edad', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-11-05 04:00:00', '2025-11-05 04:00:00', NULL, NULL, NULL),
(5317, 1, 'Cristina Moreno', '697600603', NULL, 'cristina.moreno@email.com', '2025-11-24', '09:00:00', 'Solicita: Extensiones', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-11-05 07:00:00', '2025-11-05 07:00:00', NULL, NULL, NULL),
(5318, 1, 'Pilar Sánchez', '622574088', NULL, 'pilar.sánchez@email.com', '2025-11-25', '12:00:00', 'Solicita: Depilación', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-11-05 12:00:00', '2025-11-05 12:00:00', NULL, NULL, NULL),
(5319, 1, 'Mónica Romero', '698297076', NULL, 'mónica.romero@email.com', '2025-11-25', '12:30:00', 'Solicita: Corte de pelo', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-11-05 18:00:00', '2025-11-05 18:00:00', NULL, NULL, NULL),
(5320, 1, 'Silvia Navarro', '632208144', NULL, 'silvia.navarro@email.com', '2025-11-25', '12:00:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Recordar que es alérgica a ciertos productos', '2025-11-05 02:00:00', '2025-11-05 02:00:00', NULL, NULL, NULL),
(5321, 1, 'Pilar Sánchez', '667754998', NULL, 'pilar.sánchez@email.com', '2025-11-25', '17:30:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Cliente fiel desde hace años', '2025-11-06 19:00:00', '2025-11-06 19:00:00', NULL, NULL, NULL),
(5322, 1, 'Carmen Rodríguez', '601466443', NULL, 'carmen.rodríguez@email.com', '2025-11-26', '17:30:00', 'Solicita: Color y corte', 'confirmada', '[DEMO] Cliente habitual, muy puntual', '2025-11-06 07:00:00', '2025-11-06 07:00:00', NULL, NULL, NULL),
(5323, 1, 'Nuria Ramos', '647787320', NULL, 'nuria.ramos@email.com', '2025-11-27', '10:30:00', 'Solicita: Corte de pelo', 'pendiente', '[DEMO] Quiere cambio de look radical', '2025-11-05 12:00:00', '2025-11-05 12:00:00', NULL, NULL, NULL),
(5324, 1, 'Carlos Mendoza', '602644938', NULL, 'carlos.mendoza@email.com', '2025-11-27', '09:00:00', 'Solicita: Alisado brasileño', 'confirmada', '[DEMO] Quiere cambio de look radical', '2025-11-05 12:00:00', '2025-11-05 12:00:00', NULL, NULL, NULL),
(5325, 1, 'Laura González', '609241297', NULL, 'laura.gonzález@email.com', '2025-11-27', '10:30:00', 'Solicita: Microblading', 'confirmada', '[DEMO] Viene con prisa, optimizar tiempo', '2025-11-05 04:00:00', '2025-11-05 04:00:00', NULL, NULL, NULL),
(5326, 1, 'Teresa Fernández', '698289226', NULL, 'teresa.fernández@email.com', '2025-10-18', '14:00:00', 'Solicita: Tratamiento facial', 'cancelada', '[DEMO] Cancelación: Cliente canceló', '2025-10-17 16:44:11', '2025-10-17 16:44:11', NULL, NULL, NULL),
(5327, 1, 'Teresa Fernández', '631582476', NULL, 'teresa.fernández@email.com', '2025-10-17', '17:00:00', 'Solicita: Depilación', 'cancelada', '[DEMO] Cancelación: Cambio de planes', '2025-10-17 00:48:38', '2025-10-17 00:48:38', NULL, NULL, NULL),
(5328, 1, 'Teresa Fernández', '601057420', NULL, 'teresa.fernández@email.com', '2025-10-09', '17:30:00', 'Solicita: Corte de pelo', 'cancelada', '[DEMO] Cancelación: No pudo venir', '2025-10-08 21:48:18', '2025-10-08 21:48:18', NULL, NULL, NULL),
(5329, 1, 'Teresa Fernández', '602065094', NULL, 'teresa.fernández@email.com', '2025-11-02', '11:00:00', 'Solicita: Masaje', 'cancelada', '[DEMO] Cancelación: No pudo venir', '2025-11-02 08:48:30', '2025-11-02 08:48:30', NULL, NULL, NULL),
(5330, 1, 'Teresa Fernández', '674910904', NULL, 'teresa.fernández@email.com', '2025-10-30', '09:30:00', 'Solicita: Masaje', 'cancelada', '[DEMO] Cancelación: No pudo venir', '2025-10-30 00:35:38', '2025-10-30 00:35:38', NULL, NULL, NULL);

CREATE TABLE reservas_auditoria (
  id int(11) NOT NULL,
  reserva_id int(11) NOT NULL,
  usuario_id int(11) NOT NULL,
  accion varchar(50) NOT NULL,
  campo_modificado varchar(100) DEFAULT NULL,
  valor_anterior text DEFAULT NULL,
  valor_nuevo text DEFAULT NULL,
  ip_address varchar(45) DEFAULT NULL,
  user_agent text DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO reservas_auditoria (id, reserva_id, usuario_id, accion, campo_modificado, valor_anterior, valor_nuevo, ip_address, user_agent, created_at) VALUES
(6, 3931, 19, 'creada', NULL, NULL, NULL, '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 13:24:44'),
(7, 3931, 19, 'modificada', 'hora', '11:00', '11:30', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 13:24:58'),
(8, 3931, 19, 'confirmada', 'estado', 'pendiente', 'confirmada', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 13:25:11'),
(9, 3932, 19, 'creada', NULL, NULL, NULL, '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 13:26:46'),
(10, 3932, 19, 'modificada', 'fecha', '06/11/2025', '07/11/2025', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 13:27:42'),
(11, 3932, 19, 'confirmada', 'estado', 'pendiente', 'confirmada', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 13:27:47'),
(12, 3933, 19, 'creada', NULL, NULL, NULL, '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 13:33:05'),
(13, 3933, 19, 'confirmada', 'estado', 'pendiente', 'confirmada', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 13:35:38'),
(14, 3934, 19, 'creada', NULL, NULL, NULL, '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 13:55:32'),
(15, 3934, 19, 'confirmada', 'estado', 'pendiente', 'confirmada', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 14:05:18'),
(16, 3934, 19, 'cancelada', 'estado', 'confirmada', 'cancelada', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 14:05:42'),
(17, 3935, 19, 'creada', NULL, NULL, NULL, '31.4.128.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 17:33:48'),
(18, 3935, 19, 'modificada', 'hora', '12:00', '13:00', '31.4.135.203', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-05 08:19:51'),
(19, 3935, 19, 'confirmada', 'estado', 'pendiente', 'confirmada', '31.4.135.203', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-05 08:19:59'),
(20, 3936, 19, 'creada', NULL, NULL, NULL, '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-05 20:29:30'),
(21, 3936, 19, 'confirmada', 'estado', 'pendiente', 'confirmada', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-05 20:30:29'),
(22, 3936, 19, 'cancelada', 'estado', 'confirmada', 'cancelada', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-05 20:41:36'),
(27, 4972, 19, 'creada', NULL, NULL, NULL, '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-07 10:34:52'),
(28, 4973, 19, 'creada', NULL, NULL, NULL, '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-07 10:49:16'),
(29, 4974, 19, 'creada', NULL, NULL, NULL, '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-07 10:57:45'),
(30, 4975, 19, 'creada', NULL, NULL, NULL, '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-07 11:08:09'),
(31, 5278, 1, 'confirmada', 'estado', 'pendiente', 'confirmada', '2a0c:5a83:ff01:f00:d157:7351:5bfb:2bec', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-07 20:25:27');

CREATE TABLE reservas_origen (
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

INSERT INTO reservas_origen (id, reserva_id, formulario_id, origen, ip_address, user_agent, referrer_url, utm_source, utm_campaign, created_at) VALUES
(8, 3389, 14, 'formulario_publico', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', NULL, NULL, NULL, '2025-10-26 21:39:59'),
(9, 3390, 14, 'formulario_publico', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', NULL, NULL, NULL, '2025-10-26 21:41:09'),
(11, 3933, 15, 'formulario_publico', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, NULL, NULL, '2025-11-04 13:33:05'),
(12, 3934, 15, 'formulario_publico', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, NULL, NULL, '2025-11-04 13:55:32'),
(13, 3935, 15, 'formulario_publico', '31.4.128.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, NULL, NULL, '2025-11-04 17:33:48'),
(14, 4972, 15, 'formulario_publico', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, NULL, NULL, '2025-11-07 10:34:52'),
(15, 4973, 15, 'formulario_publico', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, NULL, NULL, '2025-11-07 10:49:16'),
(16, 4974, 15, 'formulario_publico', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, NULL, NULL, '2025-11-07 10:57:45'),
(17, 4975, 15, 'formulario_publico', '212.81.182.41', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, NULL, NULL, '2025-11-07 11:08:09');

CREATE TABLE usuarios (
  id int(11) NOT NULL,
  nombre varchar(255) NOT NULL,
  email varchar(255) NOT NULL,
  telefono varchar(25) DEFAULT NULL,
  password_hash varchar(255) NOT NULL,
  plan enum('basico','profesional','avanzado') DEFAULT 'basico',
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
  last_activity timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios del sistema ReservaBot';

INSERT INTO usuarios (id, nombre, email, telefono, password_hash, plan, api_key, activo, intentos_login, ultimo_intento_login, email_verificado, verificacion_token, reset_token, reset_token_expiry, created_at, updated_at, last_activity) VALUES
(1, 'Sonia Demo', 'demo@reservabot.es', '+34900000000', '$2y$10$bQzXd40PqZ9muELf1gB3teuFTwsyS3IWCYLYNVW7c6Cg3thyl1B1a', 'profesional', '5ac4d9114131880b8b7764431545787bf0b448f7985a80726b7dec21c06755e5', 1, 1, '2025-05-27 13:33:42', 1, NULL, NULL, NULL, '2025-05-26 20:19:05', '2025-11-06 14:26:43', NULL),
(9, 'Andrea Ucar', 'ardeaucar@hotmail.com', '669110135', '$2y$10$aB0R5zBjiJBltlUDcfYiy.mIpk86FMubnjCBztUdHCj7LnvuJWvSm', 'basico', 'c08e0a0224d4d3322569c9a12cd328f4e378e1571f28a7c9df14dc98e790c39d', 1, 0, NULL, 0, '3a67b95aeae5592ef02e785f8fdea0103ee6fd751eb024da20b44a89f47bc74e', NULL, NULL, '2025-06-07 09:57:10', '2025-11-06 14:26:47', NULL),
(11, 'Admin', 'admin@reservabot.es', '+34900000000', '$2y$10$eVr3cBh3clCKmW7HSPiOmOnn0b.t7yhGkDgcNhAmJhryDod57HWbG', 'profesional', '5ac4d9114131880b8b7764431545787bf0b448f7985a80726b7dec21c06755e4', 1, 1, '2025-05-27 13:33:42', 1, NULL, NULL, NULL, '2025-05-26 20:19:05', '2025-11-06 14:26:51', NULL),
(19, 'Celia Blanco', 'ikerzuazu@gmail.com', '', '$2y$10$.12vBJBdgQff4uxjqEpt6uugSvVBijbjJRa2Gy3z.Ij8forhMbbL2', 'profesional', '93e84f09507f6d106b31a25dbd5d80f85a1356ba23acf2e9b437fe2d507adf64', 1, 0, NULL, 1, NULL, NULL, NULL, '2025-10-27 21:07:04', '2025-11-07 09:13:13', NULL),
(20, 'Vanesa Blanco', 'vanessa_blanco@hotmail.es', '', '$2y$10$H4rHJT5aPOhsyChY/0rIle1I/8UaDzHMZDJ4xiNinboueBAfh.y.K', 'profesional', 'ddcac9e8a770d5e837bd7d4f175cfab354b1a4440d1eab21839e703770c1c6ae', 1, 0, NULL, 1, NULL, NULL, NULL, '2025-11-06 10:08:39', '2025-11-06 14:27:09', NULL);

CREATE TABLE usuarios_sesiones (
  id varchar(128) NOT NULL,
  usuario_id int(11) NOT NULL,
  ip_address varchar(45) DEFAULT NULL,
  user_agent text DEFAULT NULL,
  payload text DEFAULT NULL,
  last_activity timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  created_at timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gestión de sesiones de usuario';

CREATE TABLE whatsapp_automessage_templates (
  id int(11) NOT NULL,
  usuario_id int(11) NOT NULL,
  tipo_mensaje enum('confirmacion','recordatorio','bienvenida') NOT NULL,
  mensaje text NOT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO whatsapp_automessage_templates (id, usuario_id, tipo_mensaje, mensaje, created_at, updated_at) VALUES
(1, 19, 'confirmacion', '¡Hola {nombre_cliente}! ✅\n\nTu reserva ha sido confirmada:\n📅 Fecha: {fecha}\n⏰ Hora: {hora}\n\n¡Te esperamos en {negocio}!', '2025-11-06 13:02:25', '2025-11-06 13:28:48'),
(2, 19, 'recordatorio', '¡Hola {nombre_cliente}! 👋\n\nTe recordamos tu cita de mañana:\n📅 Fecha: {fecha}\n⏰ Hora: {hora}\n\n¡Nos vemos en {negocio}!', '2025-11-06 13:02:25', '2025-11-06 13:25:41'),
(3, 19, 'bienvenida', '¡Hola! 👋 Bienvenido/a a {negocio}.\n\nEnseguida leeré tu mensaje.', '2025-11-06 13:02:25', '2025-11-06 13:28:56'),
(16, 1, 'confirmacion', '¡Hola {nombre_cliente}! ✅\n\nTu reserva ha sido confirmada:\n📅 Fecha: {fecha}\n⏰ Hora: {hora}\n\n¡Te esperamos en {negocio}!', '2025-11-07 09:32:19', '2025-11-07 09:32:19'),
(17, 1, 'recordatorio', '¡Hola {nombre_cliente}! 👋\n\nTe recordamos tu cita de mañana:\n📅 Fecha: {fecha}\n⏰ Hora: {hora}\n\n¡Nos vemos en {negocio}!', '2025-11-07 09:32:19', '2025-11-07 09:32:19'),
(18, 1, 'bienvenida', '¡Hola! 👋 Bienvenido/a a {negocio}.\n\nEnseguida leeré tu mensaje.', '2025-11-07 09:32:19', '2025-11-07 09:32:19');

CREATE TABLE whatsapp_autorespuestas (
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

INSERT INTO whatsapp_autorespuestas (id, usuario_id, nombre, keyword, response, is_active, is_regex, match_type, priority, uso_count, created_at, updated_at) VALUES
(1, 1, 'Saludo General', 'hola', '¡Hola! 👋 Bienvenido a ReservaBot.\n\n¿En qué puedo ayudarte hoy?\n\n• Escribe \"reserva\" para hacer una cita\n• Escribe \"horarios\" para ver nuestros horarios\n• Escribe \"info\" para más información', 1, 0, 'contains', 10, 0, '2025-05-22 20:36:19', '2025-05-26 20:19:05'),
(2, 1, 'Información de Horarios', 'horarios', '🕐 *Nuestros horarios de atención:*\n\n📅 **Lunes a Viernes:** 9:00 - 18:00\n📅 **Sábado:** 10:00 - 14:00\n📅 **Domingo:** Cerrado\n\n¿Te gustaría hacer una reserva? Escribe \"reserva\" 📝', 1, 0, 'contains', 8, 0, '2025-05-22 20:36:19', '2025-05-26 20:19:05'),
(3, 1, 'Proceso de Reserva', 'reserva', '📅 *Para hacer una reserva necesito:*\n\n• Tu nombre completo\n• Fecha preferida\n• Hora preferida\n• Motivo de la consulta (opcional)\n\nPor favor compárteme esta información y te ayudo a confirmar tu cita. 😊', 1, 0, 'contains', 9, 0, '2025-05-22 20:36:19', '2025-05-26 20:19:05'),
(4, 1, 'Información General', 'info', 'ℹ️ *Información de contacto:*\n\n📧 Email: info@empresa.com\n📞 Teléfono: +34 900 000 000\n🌐 Web: www.empresa.com\n📍 Dirección: Calle Principal, 123\n\n¿Necesitas algo más específico?', 1, 0, 'contains', 7, 0, '2025-05-22 20:36:19', '2025-05-26 20:19:05'),
(5, 1, 'Precios y Tarifas', 'precio', '💰 *Información sobre precios:*\n\nPara información detallada sobre nuestras tarifas, por favor:\n\n📞 Llámanos durante horario de oficina\n📧 Envíanos un email\n📅 Programa una cita gratuita de información\n\n¿Te gustaría programar una cita?', 1, 0, 'contains', 6, 0, '2025-05-22 20:36:19', '2025-05-26 20:19:05'),
(6, 1, 'Cancelaciones', 'cancelar', '❌ *Para cancelar tu reserva:*\n\nPor favor proporciona:\n• Tu nombre completo\n• Fecha y hora de tu cita\n\nTe ayudaré con la cancelación de inmediato.\n\n⚠️ Te recomendamos cancelar con al menos 24h de antelación.', 1, 0, 'contains', 5, 0, '2025-05-22 20:36:19', '2025-05-26 20:19:05'),
(7, 1, 'Ubicación', 'ubicacion', '📍 *Nuestra ubicación:*\n\nCalle Principal, 123\nCiudad, CP 12345\n\n🚗 Parking disponible\n🚌 Transporte público: Líneas 1, 5, 7\n🚇 Metro: Estación Central (5 min andando)\n\n¿Necesitas indicaciones específicas?', 1, 0, 'starts_with', 4, 0, '2025-05-22 20:36:19', '2025-05-26 20:19:05'),
(8, 1, 'Despedida', 'gracias', '😊 *¡De nada!*\n\nHa sido un placer ayudarte.\n\n📞 Si necesitas algo más, no dudes en escribirme.\n🕐 Estoy disponible durante nuestro horario de atención.\n\n¡Que tengas un excelente día! ✨', 1, 0, 'contains', 3, 0, '2025-05-22 20:36:19', '2025-05-26 20:19:05');

CREATE TABLE whatsapp_config (
  id int(11) NOT NULL,
  usuario_id int(11) NOT NULL,
  phone_number varchar(20) DEFAULT NULL,
  qr_code text DEFAULT NULL,
  status enum('disconnected','connecting','connected','waiting_qr','error') DEFAULT 'disconnected',
  last_activity timestamp NULL DEFAULT current_timestamp(),
  token varchar(255) DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  auto_confirmacion tinyint(1) DEFAULT 0 COMMENT 'Enviar confirmación automática de reservas',
  auto_recordatorio tinyint(1) DEFAULT 0 COMMENT 'Enviar recordatorios automáticos 24h antes',
  auto_bienvenida tinyint(1) DEFAULT 0 COMMENT 'Enviar mensaje de bienvenida a nuevos clientes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO whatsapp_config (id, usuario_id, phone_number, qr_code, status, last_activity, token, created_at, updated_at, auto_confirmacion, auto_recordatorio, auto_bienvenida) VALUES
(1060, 1, '34644198930', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAARQAAAEUCAYAAADqcMl5AAAAAklEQVR4AewaftIAABJNSURBVO3BQY7gRpIAQXei/v9l3z7GKQGCWS2NNszsD9Za64KHtda65GGttS55WGutSx7WWuuSh7XWuuRhrbUueVhrrUse1lrrkoe11rrkYa21LnlYa61LHtZa65KHtda65GGttS55WGutS374SOVvqnhD5aTiROWkYlL5ouINlaniDZWpYlI5qXhD5Y2KN1TeqPhCZao4UTmpmFT+poovHtZa65KHtda65GGttS754bKKm1TeUDmpmFRuqphU3lB5o+INlTcqvlCZKt5QeaNiUjlROamYVKaKv6niJpWbHtZa65KHtda65GGttS754ZepvFHxhspUMam8UfGGylRxUnGiclLxhspUMalMKm9UTCpTxYnKVHFScaJyUjGpvFExqUwVk8pvUnmj4jc9rLXWJQ9rrXXJw1prXfLDf4zKVDGpTBVfVJxUnKjcpDJVvFHxRcWJyonKVPFFxaQyVUwqJyonKicVk8p/ycNaa13ysNZalzystdYlP/zHVLyhclLxhsobFScqJyo3qdxUMVWcqNykcqIyVZxUvKEyqUwV/yUPa611ycNaa13ysNZal/zwyyr+JpWp4m+qmFS+qJhUTiomlTcq3lCZKiaVqeKkYlKZKiaVqWJSmSomlROVqWJSmSpOKm6q+Dd5WGutSx7WWuuSh7XWuuSHy1T+SRWTylQxqUwVk8pUMalMFScVk8pUMalMFZPKGxWTyonKVHGTylTx/4nKVHGi8m/2sNZalzystdYlD2utdYn9wf8wlZsqJpWTihOVLyomlaniDZWTijdU3qiYVKaKL1RuqphUTiomlaniv+xhrbUueVhrrUse1lrrEvuDD1SmiknlpoovVE4q3lA5qThROak4UTmpOFH5TRWTyhsVb6hMFW+o3FRxojJVTCo3Vfymh7XWuuRhrbUueVhrrUt+uEzlpGJSmSreUPmiYlKZKiaVk4oTlaliUplUpoqbKt5QOal4o2JSeUPlRGWqOKk4UZkqJpUTlanii4pJ5URlqrjpYa21LnlYa61LHtZa65IfPqo4UZlU3lCZKqaKSWWqOFH5omJSOak4qThRmSomlROVqWJSeaNiUpkqTlSmihOVqeILlTcqJpWp4qRiUjmp+KLiRGWq+OJhrbUueVhrrUse1lrrkh8uU5kqTlQmlaliUpkqpopJZap4Q+WLiknljYqpYlKZKiaVqeKkYlKZKm6qOFGZKiaVLyomlaliUjlROak4qXhDZao4UZkqbnpYa61LHtZa65KHtda65IePVKaKNyomlUnlRGWq+KLiRGVSmSpOKr5QmSomlaliUpkqJpWp4kRlqphUpopJZao4UZkqTlT+pooTlS9UpopJZaqYKiaVqeKLh7XWuuRhrbUueVhrrUt++KjipooTlaniDZWp4kTlDZWp4guVqWJSeaPipGJS+aJiUpkqJpWpYlI5UfmbKt6oOFG5SeWk4qaHtda65GGttS55WGutS+wPPlCZKiaVmyr+SSpvVLyhclPFpDJVvKEyVUwqU8WJyknFGyonFW+onFRMKicVb6hMFZPKVDGpTBWTylTxxcNaa13ysNZalzystdYl9gcfqEwVX6hMFZPKVDGpTBVfqLxRMan8pooTlaniROWkYlKZKk5UTiomld9U8YbKScWkclLxhspJxYnKScUXD2utdcnDWmtd8rDWWpf8cJnKVPFGxaRyojJVTCpTxaQyVZxUvFFxojJVTConKicVJyonFZPKVHGi8kXFFypvqEwVX1RMKjdVnKj8TQ9rrXXJw1prXfKw1lqX2B98oDJV/JNUpopJ5YuKN1SmiknljYo3VE4qTlSmit+kMlVMKjdV3KQyVbyh8kbFpDJVTCpTxU0Pa611ycNaa13ysNZal9gffKByUjGpTBWTyhcVk8pUMalMFZPKScWJyknFpDJVTCpTxU0qU8WkMlXcpHJSMam8UXGiclJxonJScaIyVZyovFExqUwVXzystdYlD2utdcnDWmtd8sM/TGWqeENlUnmj4qTiRGWqmCr+JpWp4o2KSeVE5aTiJpWTijdUpoovKt5QmSpOVKaKN1Smipse1lrrkoe11rrkYa21Lvnho4pJ5aRiUplUTiqmihOVSWWqmFS+UJkqJpWp4kTljYoTlaliUpkq3lCZVN6omFSmii9UpooTlTcqJpWp4g2VN1Smir/pYa21LnlYa61LHtZa65IfPlI5UTmpmFSmiknlpGKqmFTeqDhReaPiN6lMFVPFFyonFV+oTBUnKlPFpDJVTCpTxVRxojKpTBUnFV+ovFExqUwVXzystdYlD2utdcnDWmtd8sNlFZPKicpUMamcVJyoTBVvqEwVU8UbKlPFGxWTylTxRcUbFW+ofKFyojJVTCpTxRsqJxWTylRxojJVnKicqPxND2utdcnDWmtd8rDWWpf88FHFScWkMlVMKlPFpHKicqIyVZxUnKhMFW+o3KTyRsWkMlVMFW+onFR8UXGiMlWcqPyTKiaVqWKqeEPlNz2stdYlD2utdcnDWmtdYn/wgcpUcaJyUjGpTBWTylTxhcpUMalMFZPKScWJylQxqZxU3KQyVXyh8kbFTSpTxaTyRcUbKl9UTConFZPKVPHFw1prXfKw1lqXPKy11iX2Bx+o/KaKE5WTiknlpOJE5Y2KE5U3KiaVLypOVKaKE5Wp4kTlpGJS+aJiUpkq3lA5qXhD5aTiDZWTipse1lrrkoe11rrkYa21LrE/+EBlqnhDZaqYVL6omFR+U8WkclIxqXxRMalMFZPKv0nFpHJSMal8UfFvpnJScaJyUvHFw1prXfKw1lqXPKy11iU//GUqb1RMKlPFpDKpvFExqUwVk8qkMlWcqEwVk8pUMal8UXGiMlVMKicVk8pUMamcVEwqb1ScqEwVk8pNFTepTBVTxW96WGutSx7WWuuSh7XWusT+4CKVqWJS+U0Vk8pU8YbKVDGpfFFxovJGxRsqf1PFpPJGxRcqU8WJyhsVk8q/WcVND2utdcnDWmtd8rDWWpfYH1yk8kXFGypTxaTyRsWJyknFicpUMan8kyreUDmpmFSmikllqphUpopJ5YuKE5U3Kk5Upoo3VE4q/qaHtda65GGttS55WGutS374SOWNiknlRGWqOFF5o+KNikllUnlD5Y2KSWWqmFS+UJkqTiomlTcqJpUTlaliUjmpmFROKk5UTlTeUJkqTiomlTcqvnhYa61LHtZa65KHtda65IfLKiaVLyreqJhUpopJ5Y2KqWJSmSq+UHlDZao4UTmpeEPlpGJSmSqmijdUTireqHij4qaKLypOVG56WGutSx7WWuuSh7XWuuSHy1ROVE5UbqqYVE4qJpVJZap4Q2WqmFSmijcqvlD5omJSmVTeUJkq3qiYVKaKqWJSmSomlZtUvlD5Jz2stdYlD2utdcnDWmtdYn/wgcpUcaIyVUwqU8WkMlVMKicVJypTxaTyRsWJyr9ZxaRyUvGGyknFGyo3VbyhMlV8oXJS8YXKVPHFw1prXfKw1lqXPKy11iU/fFQxqUwVU8WkMlVMKlPFFypTxYnKScWkMql8UXGiclPFGxWTyknFGypTxUnFicpJxaQyVUwqb6h8UTGpTBUnKr/pYa21LnlYa61LHtZa65IffpnKVDFVnFRMKlPFScWk8kXFTRUnKr+pYlK5qWJSOamYVCaVqWJSuanipGJSuanipOLf5GGttS55WGutSx7WWuuSHy6ruEllqphU3qiYVE4qvqg4UXmjYlI5qZhUvqg4qZhU3lCZKt6omFSmihOVqWJS+SepnFScVPymh7XWuuRhrbUueVhrrUt+uEzli4qpYlI5qfiiYlL5QmWqOKk4UTmpmFSmikllqnhDZaqYKt5QeUPlpoqTiknli4oTlaniRGWq+Jse1lrrkoe11rrkYa21LvnhI5WTiknlROWNihOVk4qTiknlpGJS+U0Vk8qJylTxhsoXKlPFVPFFxaRyonJTxaTyhspvUpkqbnpYa61LHtZa65KHtda6xP7gIpWTii9Upoq/SWWqmFTeqPhCZaqYVKaK36RyUjGpnFRMKicVk8pUcaIyVUwqU8WJylQxqbxRcZPKVPHFw1prXfKw1lqXPKy11iU/fKQyVUwqJypTxaRyk8pUcaLym1SmikllqjhReUNlqphUblK5qeKkYlKZKt6omFSmihOV/7KHtda65GGttS55WGutS+wPLlJ5o+ILlTcqJpWTihOVqWJSOak4UTmpuEllqrhJZaqYVKaKN1S+qDhRmSomlaniRGWqmFROKk5U3qj44mGttS55WGutSx7WWuuSHz5SmSomlaniROWNihOVk4pJZVKZKk5UvlA5qThRmSomlaniDZWTikllqphUpopJZao4qXhD5URlqphUpopJZao4UTmpmFROKiaV3/Sw1lqXPKy11iUPa611if3BX6QyVfybqHxRMamcVLyhMlV8ofJGxRsqJxUnKlPFpDJV/CaVLyreUJkqvlCZKm56WGutSx7WWuuSh7XWuuSHj1ROKr5QOan4QmWquKliUplUpopJ5SaVqeJEZVJ5o2JSmVSmihOVqeJE5aTiROWNihOVqeImlZOKSWWq+OJhrbUueVhrrUse1lrrEvuDD1SmikllqphUTiomlZOKSWWqmFROKiaVqeJE5aRiUjmpmFRuqjhRmSomlaliUjmpeEPlpooTlTcqJpWTihOVqeJEZaqYVKaKLx7WWuuSh7XWuuRhrbUu+eGXVXyh8obKb6qYVN6omFROKiaVk4qbVN6omFSmikllUjmpOKk4UZkq3qiYVH6TylQxqZxUTCq/6WGttS55WGutSx7WWuuSH36ZylQxVUwqU8WJylQxqUwqJxUnKm9UvFExqUwVk8qkMlWcqNyk8kbFpHKi8kXFFypTxaTyhcpU8UbFP+lhrbUueVhrrUse1lrrkh8uU/miYlL5ouKmiknlROWkYlL5ouKLikllqjipOFE5qZhUpooTlTcqTlROVG6q+EJlqpgqftPDWmtd8rDWWpc8rLXWJT/8sopJ5aRiqjhR+ULlpooTlS9UTlROKqaKSeWfpDJVTCp/U8WkMlVMKicVb6icVJyonFTc9LDWWpc8rLXWJQ9rrXXJDx9VnKi8oXJSMVWcqEwVJxWTylRxojJVTBVfVEwqb6hMFb9J5Y2KSeWkYlL5TRWTylRxovJFxRcVk8pU8cXDWmtd8rDWWpc8rLXWJfYHH6i8UTGpTBVvqEwVk8pJxaQyVZyonFRMKlPFicpJxaQyVUwqb1TcpDJVnKhMFZPKVPGGyhsVk8pU8YXKVDGpnFScqEwVNz2stdYlD2utdcnDWmtdYn/wgcoXFScqU8WJylTxhspU8YXKVDGpnFScqEwVb6jcVPGGylQxqUwVJyonFV+onFRMKv8mFb/pYa21LnlYa61LHtZa6xL7g/9hKv8mFScqv6liUpkqJpWp4g2VqeILlaliUrmp4g2VNyomlZOKN1ROKv6mh7XWuuRhrbUueVhrrUt++Ejlb6qYKiaVLyomlaliUjlRmSomlTcqJpVJZaqYVN5QmSpOVN6omCreqDhRmSpOVN6oOFGZKiaVE5Wp4qTiROWk4ouHtda65GGttS55WGutS364rOImlROVqeJEZaqYVKaKN1SmiknlpopJ5aRiUjmpeKPib1KZKk5U/pdU/KaKmx7WWuuSh7XWuuRhrbUu+eGXqbxR8ZsqJpWp4o2KE5U3Kk5UTipOVE5UblJ5o2JSmSpOVKaKSWWqmFSmikllUpkqpoo3VL5QeUNlqvjiYa21LnlYa61LHtZa65If/p9RmSq+UPmi4iaVNyomlTcqTiomlROVqeKNiknlRGWq+EJlqripYlKZKt5QuelhrbUueVhrrUse1lrrkh/WkcpUMVVMKlPFpDKpTBUnFX9TxRsqU8UXKicVJxU3VbyhclIxqUwVk8pU8W/ysNZalzystdYlD2utdckPv6ziN1VMKlPFicpJxRsVJxVfqLxRMalMFVPFb6o4UZkqTlSmii9U/iaVE5U3VKaKv+lhrbUueVhrrUse1lrrkh8uU/mbVKaKSWWqOFF5Q+WNihOVqeKNipOKE5WpYlKZKqaKE5WTikllqpgqJpWpYlKZKk4qJpU3Kv6miknlb3pYa61LHtZa65KHtda6xP5grbUueFhrrUse1lrrkoe11rrkYa21LnlYa61LHtZa65KHtda65GGttS55WGutSx7WWuuSh7XWuuRhrbUueVhrrUse1lrrkoe11rrk/wD2c6CAWCdXhgAAAABJRU5ErkJggg==', 'connecting', '2025-10-29 21:02:07', NULL, '2025-06-12 20:11:39', '2025-10-29 21:02:07', 0, 0, 0),
(298327, 19, '34644198930', NULL, 'connected', '2025-11-07 10:22:02', NULL, '2025-10-28 14:58:18', '2025-11-07 10:22:02', 1, 1, 1),
(298816, 11, NULL, NULL, 'disconnected', NULL, NULL, '2025-10-31 12:58:45', '2025-10-31 12:58:45', 0, 0, 0);

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

INSERT INTO whatsapp_messages (id, usuario_id, message_id, phone_number, message_text, direction, is_group, has_media, status, timestamp_received, timestamp_sent, created_at, updated_at) VALUES
(6, 1, 'true_34665871857@c.us_3EB01E9830AED05B797358', '34665871857', 'hola hola', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 11:31:14', '2025-06-12 11:31:14', '2025-06-12 11:31:14'),
(7, 1, 'false_34665871857@c.us_FB70B5BFD1B499DC2F08DF0D67C2D761', '34665871857', 'Como estás?', 'incoming', 0, 0, 'pending', '2025-06-12 11:33:21', NULL, '2025-06-12 11:33:22', '2025-06-12 11:33:22'),
(8, 1, 'true_34665871857@c.us_3EB007F2A7193042D007B6', '34665871857', 'genial !', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 12:29:44', '2025-06-12 12:29:44', '2025-06-12 12:29:44'),
(9, 1, 'false_34665871857@c.us_EE6E750365E993372731DEF07F88E586', '34665871857', 'Lo flipo', 'incoming', 0, 0, 'pending', '2025-06-12 12:30:15', NULL, '2025-06-12 12:30:15', '2025-06-12 12:30:15'),
(10, 1, 'true_34665871857@c.us_3EB033E472EF258730F61E', '34665871857', 'que opinas?', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 12:41:49', '2025-06-12 12:41:49', '2025-06-12 12:41:49'),
(11, 1, 'false_34665871857@c.us_2F97EF24E2195B42019D6856A63ABF64', '34665871857', 'Me parece bien', 'incoming', 0, 0, 'pending', '2025-06-12 12:42:14', NULL, '2025-06-12 12:42:15', '2025-06-12 12:42:15'),
(12, 1, 'false_34665871857@c.us_54A3724CCB584863D61513CE449E1E26', '34665871857', 'Si', 'incoming', 0, 0, 'pending', '2025-06-12 12:42:52', NULL, '2025-06-12 12:42:52', '2025-06-12 12:42:52'),
(13, 1, 'true_34665871857@c.us_3EB0767A6EDCE271DA43F1', '34665871857', 'lll', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 12:43:49', '2025-06-12 12:43:49', '2025-06-12 12:43:49'),
(14, 1, 'true_34665871857@c.us_3EB01E604AF4EE12C42206', '34665871857', 'Uenas', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 15:01:31', '2025-06-12 15:01:31', '2025-06-12 15:01:31'),
(15, 1, 'true_34665871857@c.us_3EB04EDB01A54EA61B36B0', '34665871857', 'Hola', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 15:03:09', '2025-06-12 15:03:09', '2025-06-12 15:03:09'),
(16, 1, 'msg_684b34486b57d_71f74f27', '34665871857', 'hola', 'outgoing', 0, 0, 'failed', NULL, '2025-06-12 20:10:48', '2025-06-12 20:10:48', '2025-06-12 20:10:48'),
(17, 1, 'true_34665871857@c.us_3EB033DA0C45C8438694D9', '34665871857', 'adios', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 20:11:53', '2025-06-12 20:11:53', '2025-06-12 20:11:53'),
(18, 1, 'true_34665871857@c.us_3EB042DC0107299DBF694C', '34665871857', 'lala', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 20:12:03', '2025-06-12 20:12:03', '2025-06-12 20:12:03'),
(19, 1, 'true_34665871857@c.us_3EB0001E8022AA5BCAD481', '34665871857', 'alala', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 20:12:04', '2025-06-12 20:12:04', '2025-06-12 20:12:04'),
(20, 1, 'true_34665871857@c.us_3EB0A1B735823089578BB0', '34665871857', 'laa', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 20:12:05', '2025-06-12 20:12:05', '2025-06-12 20:12:05'),
(21, 1, 'true_34665871857@c.us_3EB00A078FC7CDD1AA38B9', '34665871857', 'ala', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 20:12:05', '2025-06-12 20:12:05', '2025-06-12 20:12:05'),
(22, 1, 'true_34665871857@c.us_3EB077541EC62D8993978B', '34665871857', 'alalal', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 20:12:06', '2025-06-12 20:12:06', '2025-06-12 20:12:06'),
(23, 1, 'true_34665871857@c.us_3EB0D73C2061A715D739CE', '34665871857', 'al', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 20:12:06', '2025-06-12 20:12:06', '2025-06-12 20:12:06'),
(24, 1, 'true_34665871857@c.us_3EB09129BDD8A16E067303', '34665871857', 'al', 'outgoing', 0, 0, 'sent', NULL, '2025-06-12 20:12:07', '2025-06-12 20:12:07', '2025-06-12 20:12:07'),
(25, 1, 'true_34665871857@c.us_3EB09B38D83C7FA02B7C64', '34665871857', 'Lololo', 'outgoing', 0, 0, 'sent', NULL, '2025-06-13 08:18:18', '2025-06-13 08:18:18', '2025-06-13 08:18:18'),
(26, 1, 'true_34665871857@c.us_3EB0A5CA75D75A3555B6B5', '34665871857', 'Hola', 'outgoing', 0, 0, 'sent', NULL, '2025-06-13 14:30:46', '2025-06-13 14:30:46', '2025-06-13 14:30:46'),
(27, 1, 'true_34665871857@c.us_3EB0E1BDA8B626D0E9C962', '34665871857', 'hola?', 'outgoing', 0, 0, 'sent', NULL, '2025-06-14 14:15:14', '2025-06-14 14:15:14', '2025-06-14 14:15:14'),
(28, 1, 'true_34665871857@c.us_3EB039273F72E9C93AA772', '34665871857', 'hola2?', 'outgoing', 0, 0, 'sent', NULL, '2025-06-14 14:16:53', '2025-06-14 14:16:53', '2025-06-14 14:16:53'),
(29, 1, 'true_34665871857@c.us_3EB031CF1680F4162D3963', '34665871857', 'que te cuentas?', 'outgoing', 0, 0, 'sent', NULL, '2025-06-14 14:17:08', '2025-06-14 14:17:08', '2025-06-14 14:17:08'),
(30, 1, 'true_34665871857@c.us_3EB008FBB27545B9475906', '34665871857', 'ya te he apuntado', 'outgoing', 0, 0, 'sent', NULL, '2025-06-14 14:18:54', '2025-06-14 14:18:54', '2025-06-14 14:18:54'),
(31, 1, 'true_34665871857@c.us_3EB020BBB2AAA4D983F928', '34665871857', 'Hola', 'outgoing', 0, 0, 'sent', NULL, '2025-06-16 09:08:15', '2025-06-16 09:08:15', '2025-06-16 09:08:15'),
(32, 1, 'msg_68593678aaf2a_ece90728', '34665871857', 'Hola', 'outgoing', 0, 0, 'failed', NULL, '2025-06-23 11:11:52', '2025-06-23 11:11:52', '2025-06-23 11:11:52'),
(33, 1, 'msg_687634530e64e_40713a50', '34665871857', 'bien?', 'outgoing', 0, 0, 'failed', NULL, '2025-07-15 10:58:27', '2025-07-15 10:58:27', '2025-07-15 10:58:27'),
(34, 1, 'msg_68763512b17ee_ab016a55', '34665871857', 'no', 'outgoing', 0, 0, 'failed', NULL, '2025-07-15 11:01:38', '2025-07-15 11:01:38', '2025-07-15 11:01:38'),
(35, 19, 'false_34665871857@c.us_AC623B4EB90BD2D58FEA7B15BA3B484D', '34665871857', 'Me lees?', 'incoming', 0, 0, 'read', '2025-10-30 17:25:01', NULL, '2025-10-30 17:25:01', '2025-10-30 17:25:01'),
(36, 19, 'true_34665871857@c.us_3EB0B26F71232A9FBA3D84', '34665871857', 'si', 'outgoing', 0, 0, 'pending', '2025-10-30 20:35:01', NULL, '2025-10-30 20:35:01', '2025-10-30 20:35:01'),
(37, 19, 'true_34665871857@c.us_3EB0D84AA687AD70930E62', '34665871857', 'hola', 'outgoing', 0, 0, 'sent', '2025-10-30 21:04:05', NULL, '2025-10-30 21:04:05', '2025-10-30 21:04:06'),
(38, 19, 'true_34665871857@c.us_3EB0E1F81D359D83E2C8C4', '34665871857', 'que quieres?', 'outgoing', 0, 0, 'sent', '2025-10-30 21:04:27', NULL, '2025-10-30 21:04:27', '2025-10-30 21:04:27'),
(39, 19, 'false_34665871857@c.us_ACE6FCF71E550950ABBDB3F7D388D902', '34665871857', 'Nada', 'incoming', 0, 0, 'read', '2025-10-30 21:04:48', NULL, '2025-10-30 21:04:48', '2025-10-30 21:04:48'),
(40, 19, 'true_34669110135@c.us_3EB04E4BAEDE1C1B0640FD', '34669110135', 'Hola', 'outgoing', 0, 0, 'sent', '2025-10-30 21:07:53', NULL, '2025-10-30 21:07:53', '2025-10-30 21:07:55'),
(41, 19, 'false_34669110135@c.us_3EB0216F981F2C53BC9D', '34669110135', '', 'incoming', 0, 0, 'read', '2025-10-30 21:07:53', NULL, '2025-10-30 21:07:53', '2025-10-30 21:07:53'),
(42, 19, 'false_34665871857@c.us_AC8B097320D3AFD19CC37273D29646CD', '34665871857', 'Hola', 'incoming', 0, 0, 'read', '2025-11-01 14:55:46', NULL, '2025-11-01 14:55:46', '2025-11-01 14:55:46'),
(43, 19, 'true_34669110135@c.us_3EB0456D7F819CF8B85396', '34669110135', 'no me haces casoo', 'outgoing', 0, 0, 'sent', '2025-11-02 12:32:39', NULL, '2025-11-02 12:32:39', '2025-11-02 12:32:41'),
(44, 19, 'false_34669110135@c.us_3AD713DC7F5A14A7EC3C', '34669110135', 'Cosi', 'incoming', 0, 0, 'read', '2025-11-02 12:33:39', NULL, '2025-11-02 12:33:39', '2025-11-02 12:33:39'),
(45, 19, 'true_34665871857@c.us_3EB06BB585B12662B883B4', '34665871857', 'hola', 'outgoing', 0, 0, 'sent', '2025-11-02 13:49:54', NULL, '2025-11-02 13:49:54', '2025-11-02 13:51:47'),
(46, 19, 'true_34665871857@c.us_3EB0EDC42E13C117EE6785', '34665871857', 'Kaixo', 'outgoing', 0, 0, 'sent', '2025-11-02 14:50:16', NULL, '2025-11-02 14:50:16', '2025-11-02 14:50:17'),
(47, 19, 'true_34665871857@c.us_3EB060D4E359AF03476D33', '34665871857', 'Te viene bien quedar después', 'outgoing', 0, 0, 'sent', '2025-11-03 08:01:58', NULL, '2025-11-03 08:01:58', '2025-11-03 08:01:59'),
(48, 19, 'true_34669110135@c.us_3EB01EC4E4E171604725F8', '34669110135', 'hola', 'outgoing', 0, 0, 'sent', '2025-11-05 21:07:00', NULL, '2025-11-05 21:07:00', '2025-11-05 21:07:01'),
(49, 19, 'false_34669110135@c.us_3A7566CA24DC3E539AFB', '34669110135', '😴😴', 'incoming', 0, 0, 'read', '2025-11-05 21:12:24', NULL, '2025-11-05 21:12:24', '2025-11-05 21:12:24'),
(50, 19, 'true_34665871857@c.us_3EB00290CB48C5A46AC7DE', '34665871857', 'me puedes decir algo', 'outgoing', 0, 0, 'sent', '2025-11-06 08:05:58', NULL, '2025-11-06 08:05:58', '2025-11-06 08:05:59'),
(51, 19, 'false_0@c.us_3EB0D56D2F58215A59C2', '0', '', 'incoming', 0, 0, 'read', '2025-11-06 17:58:22', NULL, '2025-11-06 17:58:22', '2025-11-06 17:58:22'),
(52, 19, 'false_0@c.us_3EB00AF724482BD123BB', '0', '', 'incoming', 0, 0, 'read', '2025-11-06 17:58:22', NULL, '2025-11-06 17:58:22', '2025-11-06 17:58:22'),
(53, 19, 'true_34669110135@c.us_3EB03D17F268B3BA68C4CC', '34669110135', 'hola', 'outgoing', 0, 0, 'sent', '2025-11-06 18:27:04', NULL, '2025-11-06 18:27:04', '2025-11-06 18:27:07'),
(54, 19, 'false_34669110135@c.us_3A202FE5D94964AA6110', '34669110135', 'Holaa', 'incoming', 0, 0, 'read', '2025-11-06 18:27:24', NULL, '2025-11-06 18:27:24', '2025-11-06 18:27:24'),
(55, 19, 'true_34669110135@c.us_3EB08F69F382EF5ED9A234', '34669110135', 'prueba ahora', 'outgoing', 0, 0, 'sent', '2025-11-06 18:28:05', NULL, '2025-11-06 18:28:05', '2025-11-06 18:28:09'),
(56, 19, 'false_34669110135@c.us_3ABF459A58016FD78903', '34669110135', 'Hebd', 'incoming', 0, 0, 'read', '2025-11-06 18:28:09', NULL, '2025-11-06 18:28:09', '2025-11-06 18:28:09'),
(57, 19, 'false_34669110135@c.us_3ACDC197685FAA084604', '34669110135', 'Hh', 'incoming', 0, 0, 'read', '2025-11-06 18:28:15', NULL, '2025-11-06 18:28:15', '2025-11-06 18:28:15'),
(58, 19, 'false_34669110135@c.us_3AADB55101E18889BBCB', '34669110135', 'Hrje', 'incoming', 0, 0, 'read', '2025-11-06 18:28:16', NULL, '2025-11-06 18:28:16', '2025-11-06 18:28:16'),
(59, 19, 'false_34669110135@c.us_3A9A9F6B3A5A1CF2AF52', '34669110135', 'Ee', 'incoming', 0, 0, 'read', '2025-11-06 18:28:16', NULL, '2025-11-06 18:28:16', '2025-11-06 18:28:16'),
(60, 19, 'false_34669110135@c.us_3A0D4FE684642F3C1F05', '34669110135', 'E', 'incoming', 0, 0, 'read', '2025-11-06 18:28:17', NULL, '2025-11-06 18:28:17', '2025-11-06 18:28:17'),
(61, 19, 'true_34669110135@c.us_3EB0BEA2DE003A97FB4EAA', '34669110135', 'Hola', 'outgoing', 0, 0, 'sent', '2025-11-07 10:22:02', NULL, '2025-11-07 10:22:02', '2025-11-07 10:22:04');


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

ALTER TABLE reservas_auditoria
  ADD PRIMARY KEY (id),
  ADD KEY usuario_id (usuario_id),
  ADD KEY idx_reserva_usuario (reserva_id,usuario_id),
  ADD KEY idx_created_at (created_at);

ALTER TABLE reservas_origen
  ADD PRIMARY KEY (id),
  ADD KEY idx_reserva (reserva_id),
  ADD KEY idx_formulario (formulario_id),
  ADD KEY idx_origen (origen),
  ADD KEY idx_created (created_at),
  ADD KEY idx_origen_formulario_fecha (formulario_id,created_at);

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

ALTER TABLE usuarios_sesiones
  ADD PRIMARY KEY (id),
  ADD KEY idx_usuario_id (usuario_id),
  ADD KEY idx_last_activity (last_activity);

ALTER TABLE whatsapp_automessage_templates
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY unique_user_template (usuario_id,tipo_mensaje),
  ADD KEY idx_usuario_tipo (usuario_id,tipo_mensaje);

ALTER TABLE whatsapp_autorespuestas
  ADD PRIMARY KEY (id),
  ADD KEY idx_active (is_active),
  ADD KEY idx_keyword (keyword),
  ADD KEY idx_priority (priority),
  ADD KEY idx_updated (updated_at),
  ADD KEY idx_autorespuestas_active_priority (is_active,priority DESC),
  ADD KEY idx_usuario_id (usuario_id),
  ADD KEY idx_autorespuestas_usuario_activo (usuario_id,is_active);

ALTER TABLE whatsapp_config
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY unique_user (usuario_id);

ALTER TABLE whatsapp_messages
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY message_id (message_id),
  ADD KEY idx_usuario_phone (usuario_id,phone_number),
  ADD KEY idx_message_id (message_id),
  ADD KEY idx_timestamp (timestamp_received);


ALTER TABLE configuraciones
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

ALTER TABLE configuraciones_usuario
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=641;

ALTER TABLE conversaciones
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE formularios_publicos
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

ALTER TABLE reservas
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5331;

ALTER TABLE reservas_auditoria
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

ALTER TABLE reservas_origen
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

ALTER TABLE usuarios
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

ALTER TABLE whatsapp_automessage_templates
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

ALTER TABLE whatsapp_autorespuestas
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE whatsapp_config
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=298962;

ALTER TABLE whatsapp_messages
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;


ALTER TABLE configuraciones_usuario
  ADD CONSTRAINT configuraciones_usuario_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;

ALTER TABLE conversaciones
  ADD CONSTRAINT conversaciones_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;

ALTER TABLE reservas
  ADD CONSTRAINT reservas_ibfk_1 FOREIGN KEY (formulario_id) REFERENCES formularios_publicos (id) ON DELETE SET NULL;

ALTER TABLE reservas_auditoria
  ADD CONSTRAINT reservas_auditoria_ibfk_1 FOREIGN KEY (reserva_id) REFERENCES reservas (id) ON DELETE CASCADE,
  ADD CONSTRAINT reservas_auditoria_ibfk_2 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;

ALTER TABLE reservas_origen
  ADD CONSTRAINT reservas_origen_ibfk_1 FOREIGN KEY (reserva_id) REFERENCES reservas (id) ON DELETE CASCADE,
  ADD CONSTRAINT reservas_origen_ibfk_2 FOREIGN KEY (formulario_id) REFERENCES formularios_publicos (id) ON DELETE SET NULL;

ALTER TABLE usuarios_sesiones
  ADD CONSTRAINT usuarios_sesiones_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;

ALTER TABLE whatsapp_automessage_templates
  ADD CONSTRAINT whatsapp_automessage_templates_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;

ALTER TABLE whatsapp_config
  ADD CONSTRAINT whatsapp_config_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;

ALTER TABLE whatsapp_messages
  ADD CONSTRAINT whatsapp_messages_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;