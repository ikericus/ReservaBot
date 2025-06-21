-- Procedimiento almacenado para generar datos de demo
-- Recibe la fecha actual como parámetro
-- No genera reservas en fines de semana (sábado y domingo)

DELIMITER //

DROP PROCEDURE IF EXISTS GenerateDemoData//

CREATE PROCEDURE GenerateDemoData(IN fecha_actual DATE)
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
    DECLARE dia_semana INT;
    
    -- Arrays simulados con CASE para nombres
    DECLARE nombres_count INT DEFAULT 20;
    DECLARE servicios_count INT DEFAULT 16;
    DECLARE comentarios_count INT DEFAULT 8;
    
    -- Obtener ID del usuario demo
    SELECT id INTO demo_user_id 
    FROM usuarios 
    WHERE email = 'demo@reservabot.es' 
    LIMIT 1;
    
    -- Si no existe el usuario demo, salir
    IF demo_user_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario demo no encontrado';
    END IF;
    
    -- Limpiar datos de demo existentes
    DELETE FROM reservas 
    WHERE usuario_id = demo_user_id 
    AND (notas_internas LIKE '%[DEMO]%' OR notas_internas IS NULL);
    
    -- Generar reservas para los últimos 30 días (confirmadas)
    SET i = 30;
    WHILE i >= 1 DO
        SET fecha_reserva = DATE_SUB(fecha_actual, INTERVAL i DAY);
        
        -- Verificar que no sea fin de semana (1=Domingo, 7=Sábado en DAYOFWEEK)
        SET dia_semana = DAYOFWEEK(fecha_reserva);
        
        -- Solo generar reservas si es día laborable (Lunes=2 a Viernes=6)
        IF dia_semana >= 2 AND dia_semana <= 6 THEN
            SET num_reservas_dia = FLOOR(2 + (RAND() * 5)); -- Entre 2 y 6 reservas
            
            SET j = 0;
            WHILE j < num_reservas_dia DO
                -- Generar hora aleatoria (9:00-19:30, cada 30 min)
                SET hora_reserva = ADDTIME('09:00:00', SEC_TO_TIME((FLOOR(RAND() * 21) * 1800))); -- 21 slots de 30min
                
                -- Seleccionar nombre aleatorio
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
                
                -- Generar teléfono
                SET telefono_cliente = CONCAT('6', LPAD(FLOOR(RAND() * 100000000), 8, '0'));
                
                -- Generar email
                SET email_cliente = CONCAT(
                    LOWER(REPLACE(nombre_cliente, ' ', '.')), 
                    '@email.com'
                );
                
                -- Seleccionar servicio aleatorio
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
                
                -- Generar notas internas
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
                
                -- Timestamp de creación (entre 1h y 1 día antes)
                SET created_timestamp = TIMESTAMP(
                    DATE_SUB(
                        TIMESTAMP(fecha_reserva, hora_reserva), 
                        INTERVAL (3600 + FLOOR(RAND() * 82800)) SECOND
                    )
                );
                
                -- Insertar reserva confirmada
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
        END IF;
        
        SET i = i - 1;
    END WHILE;
    
    -- Generar reservas para los próximos 20 días (confirmadas y pendientes)
    SET i = 1;
    WHILE i <= 20 DO
        SET fecha_reserva = DATE_ADD(fecha_actual, INTERVAL i DAY);
        
        -- Verificar que no sea fin de semana
        SET dia_semana = DAYOFWEEK(fecha_reserva);
        
        -- Solo generar reservas si es día laborable
        IF dia_semana >= 2 AND dia_semana <= 6 THEN
            SET num_reservas_dia = FLOOR(1 + (RAND() * 4)); -- Entre 1 y 4 reservas
            
            SET j = 0;
            WHILE j < num_reservas_dia DO
                -- Generar hora aleatoria
                SET hora_reserva = ADDTIME('09:00:00', SEC_TO_TIME((FLOOR(RAND() * 21) * 1800)));
                
                -- Seleccionar nombre aleatorio
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
                
                -- 70% confirmadas, 30% pendientes para futuro
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
                
                -- Creada en las últimas 48h
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
        END IF;
        
        SET i = i + 1;
    END WHILE;
    
    -- Generar algunas reservas canceladas en el pasado (solo días laborables)
    SET i = 0;
    WHILE i < 5 DO
        -- Generar fecha aleatoria en el pasado
        SET fecha_reserva = DATE_SUB(fecha_actual, INTERVAL FLOOR(1 + RAND() * 30) DAY);
        SET dia_semana = DAYOFWEEK(fecha_reserva);
        
        -- Solo crear cancelación si era día laborable
        IF dia_semana >= 2 AND dia_semana <= 6 THEN
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
        END IF;
        
        -- Prevenir bucle infinito si hay muchos fines de semana consecutivos
        IF i >= 10 THEN
            LEAVE;
        END IF;
    END WHILE;
    
END//

DELIMITER ; TIMESTAMP(
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
    
END//

DELIMITER ;