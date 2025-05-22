<?php
/**
 * Funciones para el generador de formularios
 */

/**
 * Crea un nuevo formulario público
 *
 * @param array $data Datos del formulario
 * @return array Resultado de la operación
 */
function createFormularioPublico($data) {
    global $pdo;
    
    try {
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $confirmacionAutomatica = isset($data['confirmacion_automatica']) ? 1 : 0;
        $camposActivos = $data['campos_activos'] ?? [];
        $mensajeConfirmacion = trim($data['mensaje_confirmacion'] ?? '');
        $mensajeHeader = trim($data['mensaje_header'] ?? '');
        $id_negocio = intval($data['id_negocio'] ?? 0);
        
        // Validaciones
        if (empty($nombre) || empty($camposActivos) || $id_negocio <= 0) {
            return ['success' => false, 'message' => 'Faltan datos requeridos'];
        }
        
        // Convertir campos activos a JSON
        $camposActivosJson = json_encode($camposActivos);
        
        // Generar slug único basado en el nombre
        $slug = generarSlugUnico($nombre);
        
        // Insertar formulario
        $stmt = $pdo->prepare("INSERT INTO formularios_publicos 
            (id_negocio, nombre, descripcion, slug, confirmacion_automatica, 
             campos_activos, mensaje_confirmacion, mensaje_header, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $timestamp = time();
        $stmt->execute([
            $id_negocio, $nombre, $descripcion, $slug, $confirmacionAutomatica,
            $camposActivosJson, $mensajeConfirmacion, $mensajeHeader, $timestamp
        ]);
        
        $formularioId = $pdo->lastInsertId();
        
        // Si hay preguntas personalizadas, guardarlas
        if (!empty($data['preguntas'])) {
            guardarPreguntasFormulario($formularioId, $data['preguntas']);
        }
        
        return [
            'success' => true, 
            'message' => 'Formulario creado correctamente',
            'id' => $formularioId,
            'slug' => $slug
        ];
    } catch (\PDOException $e) {
        error_log('Error al crear formulario: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error al guardar el formulario'];
    }
}

/**
 * Actualiza un formulario existente
 *
 * @param int $id ID del formulario
 * @param array $data Datos actualizados
 * @return array Resultado de la operación
 */
function updateFormularioPublico($id, $data) {
    global $pdo;
    
    try {
        $id = intval($id);
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $confirmacionAutomatica = isset($data['confirmacion_automatica']) ? 1 : 0;
        $camposActivos = $data['campos_activos'] ?? [];
        $mensajeConfirmacion = trim($data['mensaje_confirmacion'] ?? '');
        $mensajeHeader = trim($data['mensaje_header'] ?? '');
        $activo = isset($data['activo']) ? 1 : 0;
        
        // Validaciones
        if ($id <= 0 || empty($nombre) || empty($camposActivos)) {
            return ['success' => false, 'message' => 'Datos inválidos o incompletos'];
        }
        
        // Convertir campos activos a JSON
        $camposActivosJson = json_encode($camposActivos);
        
        // Actualizar formulario
        $stmt = $pdo->prepare("UPDATE formularios_publicos 
            SET nombre = ?, descripcion = ?, confirmacion_automatica = ?, 
                campos_activos = ?, mensaje_confirmacion = ?, mensaje_header = ?,
                activo = ?, updated_at = ? 
            WHERE id = ?");
        
        $timestamp = time();
        $stmt->execute([
            $nombre, $descripcion, $confirmacionAutomatica,
            $camposActivosJson, $mensajeConfirmacion, $mensajeHeader,
            $activo, $timestamp, $id
        ]);
        
        // Actualizar preguntas si se han enviado
        if (isset($data['preguntas'])) {
            // Eliminar preguntas existentes
            $pdo->prepare("DELETE FROM formulario_preguntas WHERE id_formulario = ?")->execute([$id]);
            
            // Guardar nuevas preguntas
            if (!empty($data['preguntas'])) {
                guardarPreguntasFormulario($id, $data['preguntas']);
            }
        }
        
        return ['success' => true, 'message' => 'Formulario actualizado correctamente'];
    } catch (\PDOException $e) {
        error_log('Error al actualizar formulario: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error al actualizar el formulario'];
    }
}

/**
 * Guarda las preguntas personalizadas de un formulario
 *
 * @param int $formularioId ID del formulario
 * @param array $preguntas Lista de preguntas
 */
function guardarPreguntasFormulario($formularioId, $preguntas) {
    global $pdo;
    
    $orden = 1;
    foreach ($preguntas as $pregunta) {
        $texto = trim($pregunta['pregunta'] ?? '');
        $tipo = $pregunta['tipo'] ?? 'texto';
        $requerido = isset($pregunta['requerido']) ? 1 : 0;
        $opciones = isset($pregunta['opciones']) ? json_encode($pregunta['opciones']) : null;
        
        if (!empty($texto)) {
            $stmt = $pdo->prepare("INSERT INTO formulario_preguntas 
                (id_formulario, pregunta, tipo, opciones, requerido, orden, activo) 
                VALUES (?, ?, ?, ?, ?, ?, 1)");
            
            $stmt->execute([$formularioId, $texto, $tipo, $opciones, $requerido, $orden]);
            $orden++;
        }
    }
}

/**
 * Obtiene un formulario por su ID
 *
 * @param int $id ID del formulario
 * @return array|false Datos del formulario o false si no existe
 */
function getFormularioById($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM formularios_publicos WHERE id = ?");
        $stmt->execute([intval($id)]);
        $formulario = $stmt->fetch();
        
        if (!$formulario) {
            return false;
        }
        
        // Decodificar campos activos
        $formulario['campos_activos'] = json_decode($formulario['campos_activos'], true);
        
        // Obtener preguntas
        $formulario['preguntas'] = getFormularioPreguntas($id);
        
        return $formulario;
    } catch (\PDOException $e) {
        error_log('Error al obtener formulario: ' . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene un formulario por su slug
 *
 * @param string $slug Slug del formulario
 * @return array|false Datos del formulario o false si no existe
 */
function getFormularioBySlug($slug) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM formularios_publicos WHERE slug = ? AND activo = 1");
        $stmt->execute([trim($slug)]);
        $formulario = $stmt->fetch();
        
        if (!$formulario) {
            return false;
        }
        
        // Decodificar campos activos
        $formulario['campos_activos'] = json_decode($formulario['campos_activos'], true);
        
        // Obtener preguntas
        $formulario['preguntas'] = getFormularioPreguntas($formulario['id']);
        
        return $formulario;
    } catch (\PDOException $e) {
        error_log('Error al obtener formulario por slug: ' . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene todos los formularios de un negocio
 *
 * @param int $idNegocio ID del negocio
 * @return array Lista de formularios
 */
function getFormulariosByNegocio($idNegocio) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM formularios_publicos WHERE id_negocio = ? ORDER BY created_at DESC");
        $stmt->execute([intval($idNegocio)]);
        
        $formularios = [];
        while ($row = $stmt->fetch()) {
            $row['campos_activos'] = json_decode($row['campos_activos'], true);
            $formularios[] = $row;
        }
        
        return $formularios;
    } catch (\PDOException $e) {
        error_log('Error al obtener formularios por negocio: ' . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene las preguntas de un formulario
 *
 * @param int $formularioId ID del formulario
 * @return array Lista de preguntas
 */
function getFormularioPreguntas($formularioId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM formulario_preguntas 
                               WHERE id_formulario = ? AND activo = 1 
                               ORDER BY orden");
        $stmt->execute([intval($formularioId)]);
        
        $preguntas = [];
        while ($row = $stmt->fetch()) {
            if (!empty($row['opciones'])) {
                $row['opciones'] = json_decode($row['opciones'], true);
            }
            $preguntas[] = $row;
        }
        
        return $preguntas;
    } catch (\PDOException $e) {
        error_log('Error al obtener preguntas del formulario: ' . $e->getMessage());
        return [];
    }
}

/**
 * Genera un slug único para el formulario
 *
 * @param string $nombre Nombre del formulario
 * @return string Slug generado
 */
function generarSlugUnico($nombre) {
    global $pdo;
    
    // Generar slug base
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $nombre));
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Si está vacío, usar un valor predeterminado
    if (empty($slug)) {
        $slug = 'reserva';
    }
    
    // Verificar si ya existe
    $baseSlug = $slug;
    $contador = 1;
    
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM formularios_publicos WHERE slug = ?");
        $stmt->execute([$slug]);
        
        if (!$stmt->fetch()) {
            break; // El slug es único
        }
        
        // Añadir contador
        $slug = $baseSlug . '-' . $contador;
        $contador++;
    }
    
    return $slug;
}

/**
 * Procesa una reserva desde un formulario público
 *
 * @param array $data Datos del formulario
 * @return array Resultado del procesamiento
 */
function procesarReservaFormulario($data) {
    global $pdo;
    
    try {
        // Obtener el formulario
        $idFormulario = intval($data['id_formulario'] ?? 0);
        
        if ($idFormulario <= 0) {
            return ['success' => false, 'message' => 'Formulario no válido'];
        }
        
        $formulario = getFormularioById($idFormulario);
        
        if (!$formulario || !$formulario['activo']) {
            return ['success' => false, 'message' => 'El formulario no existe o no está activo'];
        }
        
        // Validar datos requeridos
        $camposRequeridos = ['nombre', 'email', 'fecha', 'hora'];
        foreach ($camposRequeridos as $campo) {
            if (empty($data[$campo]) && in_array($campo, $formulario['campos_activos'])) {
                return ['success' => false, 'message' => 'Faltan campos obligatorios'];
            }
        }
        
        // Preparar datos para la reserva
        $nombre = trim($data['nombre'] ?? '');
        $email = trim($data['email'] ?? '');
        $telefono = trim($data['telefono'] ?? '');
        $fecha = $data['fecha'] ?? '';
        $hora = $data['hora'] ?? '';
        $personas = intval($data['personas'] ?? 1);
        $comentarios = trim($data['comentarios'] ?? '');
        
        // Buscar o crear cliente
        $clienteId = 0;
        
        if (!empty($email)) {
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
            $stmt->execute([$email]);
            $cliente = $stmt->fetch();
            
            if ($cliente) {
                $clienteId = $cliente['id'];
                
                // Actualizar información si es necesario
                if (!empty($nombre) || !empty($telefono)) {
                    $stmtUpdate = $pdo->prepare("UPDATE clientes SET nombre = ?, telefono = ? WHERE id = ?");
                    $stmtUpdate->execute([$nombre, $telefono, $clienteId]);
                }
            } else {
                // Crear nuevo cliente
                $stmtInsert = $pdo->prepare("INSERT INTO clientes (nombre, email, telefono, created_at) VALUES (?, ?, ?, ?)");
                $stmtInsert->execute([$nombre, $email, $telefono, time()]);
                $clienteId = $pdo->lastInsertId();
            }
        }
        
        // Crear la reserva
        $confirmada = $formulario['confirmacion_automatica'] ? 1 : 0;
        
        $stmtReserva = $pdo->prepare("INSERT INTO reservas 
            (id_negocio, cliente_id, fecha, hora, personas, comentarios, confirmada, origen, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'formulario', ?)");
        
        $timestamp = time();
        $stmtReserva->execute([
            $formulario['id_negocio'], $clienteId, $fecha, $hora, 
            $personas, $comentarios, $confirmada, $timestamp
        ]);
        
        $reservaId = $pdo->lastInsertId();
        
        // Registrar respuestas personalizadas si existen
        $respuestas = [];
        
        if (!empty($formulario['preguntas'])) {
            foreach ($formulario['preguntas'] as $pregunta) {
                $idPregunta = $pregunta['id'];
                $respuestaKey = 'pregunta_' . $idPregunta;
                
                if (isset($data[$respuestaKey])) {
                    $respuestas[$idPregunta] = $data[$respuestaKey];
                }
            }
        }
        
        // Guardar metadatos de la reserva por formulario
        $stmtMeta = $pdo->prepare("INSERT INTO reservas_formulario 
            (id_reserva, id_formulario, respuestas, origin_url, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmtMeta->execute([
            $reservaId, 
            $idFormulario, 
            !empty($respuestas) ? json_encode($respuestas) : null,
            $_SERVER['HTTP_REFERER'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $timestamp
        ]);
        
        // Enviar notificación por email si está configurado
        if (!empty($email)) {
            enviarEmailConfirmacionReserva($reservaId);
        }
        
        // Si está habilitado, enviar notificación por WhatsApp
        if (!empty($telefono) && function_exists('sendWhatsAppNotification')) {
            sendWhatsAppNotification($reservaId, 'nueva_reserva');
        }
        
        return [
            'success' => true, 
            'message' => 'Reserva creada correctamente',
            'id' => $reservaId,
            'confirmada' => $confirmada
        ];
    } catch (\PDOException $e) {
        error_log('Error al procesar reserva desde formulario: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error al procesar la reserva'];
    }
}

/**
 * Envía un email de confirmación de reserva
 *
 * @param int $reservaId ID de la reserva
 * @return bool Resultado del envío
 */
function enviarEmailConfirmacionReserva($reservaId) {
    global $pdo;
    
    try {
        // Obtener datos de la reserva
        $stmt = $pdo->prepare("
            SELECT r.*, c.nombre, c.email, n.nombre AS nombre_negocio
            FROM reservas r
            LEFT JOIN clientes c ON r.cliente_id = c.id
            LEFT JOIN negocios n ON r.id_negocio = n.id
            WHERE r.id = ?
        ");
        $stmt->execute([intval($reservaId)]);
        $reserva = $stmt->fetch();
        
        if (!$reserva || empty($reserva['email'])) {
            return false;
        }
        
        // Formatear fecha y hora
        $fecha = date('d/m/Y', strtotime($reserva['fecha']));
        $hora = date('H:i', strtotime($reserva['hora']));
        
        // Obtener configuración de email
        $configStmt = $pdo->query("SELECT clave, valor FROM configuraciones WHERE clave LIKE 'email_%'");
        $config = [];
        
        while ($row = $configStmt->fetch()) {
            $config[$row['clave']] = $row['valor'];
        }
        
        // Verificar configuración mínima
        if (empty($config['email_host']) || empty($config['email_from'])) {
            return false;
        }
        
        // Preparar plantilla de email
        $asunto = 'Confirmación de reserva - ' . $reserva['nombre_negocio'];
        
        $mensaje = "<html><body>";
        $mensaje .= "<h2>Confirmación de Reserva</h2>";
        $mensaje .= "<p>Hola " . htmlspecialchars($reserva['nombre']) . ",</p>";
        
        if ($reserva['confirmada']) {
            $mensaje .= "<p>Tu reserva ha sido <strong>confirmada</strong>.</p>";
        } else {
            $mensaje .= "<p>Hemos recibido tu solicitud de reserva. Te notificaremos cuando sea confirmada.</p>";
        }
        
        $mensaje .= "<h3>Detalles de la reserva:</h3>";
        $mensaje .= "<ul>";
        $mensaje .= "<li><strong>Fecha:</strong> " . $fecha . "</li>";
        $mensaje .= "<li><strong>Hora:</strong> " . $hora . "</li>";
        $mensaje .= "<li><strong>Personas:</strong> " . $reserva['personas'] . "</li>";
        
        if (!empty($reserva['comentarios'])) {
            $mensaje .= "<li><strong>Comentarios:</strong> " . htmlspecialchars($reserva['comentarios']) . "</li>";
        }
        
        $mensaje .= "</ul>";
        
        // Obtener respuestas personalizadas si existen
        $stmtMeta = $pdo->prepare("SELECT * FROM reservas_formulario WHERE id_reserva = ?");
        $stmtMeta->execute([$reservaId]);
        $metadatos = $stmtMeta->fetch();
        
        if ($metadatos && !empty($metadatos['respuestas'])) {
            $respuestas = json_decode($metadatos['respuestas'], true);
            
            if (!empty($respuestas)) {
                $mensaje .= "<h3>Información adicional:</h3>";
                $mensaje .= "<ul>";
                
                // Obtener detalles de las preguntas
                $stmtPreguntas = $pdo->prepare("SELECT * FROM formulario_preguntas WHERE id_formulario = ?");
                $stmtPreguntas->execute([$metadatos['id_formulario']]);
                $preguntas = [];
                
                while ($pregunta = $stmtPreguntas->fetch()) {
                    $preguntas[$pregunta['id']] = $pregunta;
                }
                
                // Mostrar respuestas con sus preguntas
                foreach ($respuestas as $idPregunta => $respuesta) {
                    if (isset($preguntas[$idPregunta])) {
                        $mensaje .= "<li><strong>" . htmlspecialchars($preguntas[$idPregunta]['pregunta']) . ":</strong> " . htmlspecialchars($respuesta) . "</li>";
                    }
                }
                
                $mensaje .= "</ul>";
            }
        }
        
        $mensaje .= "<p>Gracias por tu reserva.</p>";
        $mensaje .= "<p><em>Este es un mensaje automático, por favor no respondas a este email.</em></p>";
        $mensaje .= "</body></html>";
        
        // Configurar cabeceras
        $cabeceras  = "MIME-Version: 1.0\r\n";
        $cabeceras .= "Content-type: text/html; charset=UTF-8\r\n";
        $cabeceras .= "From: " . $config['email_from'] . "\r\n";
        
        // Enviar email
        return mail($reserva['email'], $asunto, $mensaje, $cabeceras);
    } catch (\PDOException $e) {
        error_log('Error al enviar email de confirmación: ' . $e->getMessage());
        return false;
    }
}