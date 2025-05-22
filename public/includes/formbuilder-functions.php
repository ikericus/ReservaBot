<?php
/**
 * Funciones para el generador de formularios - VERSIÓN COMPLETA CORREGIDA
 */

/**
 * Verifica si el usuario está autenticado (simplificado para MVP)
 */
function verificarSesion() {
    // Para MVP, asumimos que el usuario está siempre autenticado
    // En producción aquí iría la lógica de autenticación real
    return true;
}

/**
 * Obtiene el ID del negocio del usuario actual (simplificado para MVP)
 */
function obtenerNegocioUsuario() {
    // Para MVP, retornamos ID fijo
    // En producción aquí se obtendría del usuario autenticado
    return 1;
}

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
        $activo = isset($data['activo']) ? 1 : 0;
        
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
             campos_activos, mensaje_confirmacion, mensaje_header, activo, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $timestamp = time();
        $stmt->execute([
            $id_negocio, $nombre, $descripcion, $slug, $confirmacionAutomatica,
            $camposActivosJson, $mensajeConfirmacion, $mensajeHeader, $activo, $timestamp
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
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $nombre));
    $slug = preg_replace('/\s+/', '-', $slug);
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
        $camposRequeridos = ['nombre', 'fecha', 'hora'];
        foreach ($camposRequeridos as $campo) {
            if (empty($data[$campo]) && in_array($campo, $formulario['campos_activos'])) {
                return ['success' => false, 'message' => 'Faltan campos obligatorios'];
            }
        }
        
        // Preparar datos para la reserva
        $nombre = trim($data['nombre'] ?? '');
        $telefono = trim($data['telefono'] ?? '');
        $fecha = $data['fecha'] ?? '';
        $hora = $data['hora'] ?? '';
        $comentarios = trim($data['comentarios'] ?? '');
        
        // Determinar estado de la reserva
        $estado = $formulario['confirmacion_automatica'] ? 'confirmada' : 'pendiente';
        
        // Crear la reserva
        $stmtReserva = $pdo->prepare("INSERT INTO reservas 
            (nombre, telefono, fecha, hora, mensaje, estado) 
            VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmtReserva->execute([
            $nombre, $telefono, $fecha, $hora . ':00', $comentarios, $estado
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
            time()
        ]);
        
        return [
            'success' => true, 
            'message' => 'Reserva creada correctamente',
            'id' => $reservaId,
            'confirmada' => $formulario['confirmacion_automatica']
        ];
    } catch (\PDOException $e) {
        error_log('Error al procesar reserva desde formulario: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error al procesar la reserva'];
    }
}