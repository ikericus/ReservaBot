<?php
/**
 * Administración de formularios públicos
 */

 // Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';
require_once 'includes/formbuilder-functions.php';

// Verificar autenticación
verificarSesion();

// Obtener el ID del negocio del usuario actual
$idNegocio = obtenerNegocioUsuario();

if (!$idNegocio) {
    // Redirigir si no tiene negocio asociado
    header('Location: dashboard.php?error=no_negocio');
    exit;
}

// Acción a realizar
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$idFormulario = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Comprobar que el formulario pertenece al negocio
if ($idFormulario > 0) {
    $formulario = getFormularioById($idFormulario);
    if (!$formulario || $formulario['id_negocio'] != $idNegocio) {
        header('Location: forms.php?error=acceso_denegado');
        exit;
    }
}

// Procesar acciones
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_formulario']) || isset($_POST['actualizar_formulario'])) {
        // Recoger datos del formulario
        $datosFormulario = [
            'nombre' => $_POST['nombre'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'confirmacion_automatica' => isset($_POST['confirmacion_automatica']),
            'campos_activos' => $_POST['campos_activos'] ?? [],
            'mensaje_confirmacion' => $_POST['mensaje_confirmacion'] ?? '',
            'mensaje_header' => $_POST['mensaje_header'] ?? '',
            'id_negocio' => $idNegocio,
            'activo' => isset($_POST['activo']),
        ];
        
        // Procesar preguntas personalizadas
        $preguntas = [];
        
        if (isset($_POST['preguntas']) && is_array($_POST['preguntas'])) {
            foreach ($_POST['preguntas'] as $index => $datosPregunta) {
                if (!empty($datosPregunta['pregunta'])) {
                    $opciones = [];
                    
                    // Procesar opciones para tipos lista y checkbox
                    if (in_array($datosPregunta['tipo'], ['lista', 'checkbox']) && 
                        isset($datosPregunta['opciones']) && 
                        !empty($datosPregunta['opciones'])) {
                        
                        $lineasOpciones = explode("\n", $datosPregunta['opciones']);
                        foreach ($lineasOpciones as $linea) {
                            $opcion = trim($linea);
                            if (!empty($opcion)) {
                                $opciones[] = $opcion;
                            }
                        }
                    }
                    
                    $preguntas[] = [
                        'pregunta' => $datosPregunta['pregunta'],
                        'tipo' => $datosPregunta['tipo'],
                        'requerido' => isset($datosPregunta['requerido']),
                        'opciones' => $opciones
                    ];
                }
            }
        }
        
        $datosFormulario['preguntas'] = $preguntas;
        
        // Crear o actualizar formulario
        if (isset($_POST['crear_formulario'])) {
            $resultado = createFormularioPublico($datosFormulario);
        } else {
            $resultado = updateFormularioPublico($idFormulario, $datosFormulario);
        }
        
        if ($resultado['success']) {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'success';
            
            // Redirigir a la lista después de crear
            if (isset($_POST['crear_formulario'])) {
                header('Location: forms.php?mensaje=' . urlencode($mensaje) . '&tipo=' . $tipoMensaje);
                exit;
            }
        } else {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'danger';
        }
    } elseif (isset($_POST['eliminar_formulario'])) {
        // Eliminación lógica (desactivar)
        $stmt = $pdo->prepare("UPDATE formularios_publicos SET activo = 0 WHERE id = ? AND id_negocio = ?");
        $resultado = $stmt->execute([$idFormulario, $idNegocio]);
        
        if ($resultado) {
            $mensaje = 'Formulario eliminado correctamente';
            $tipoMensaje = 'success';
            header('Location: forms.php?mensaje=' . urlencode($mensaje) . '&tipo=' . $tipoMensaje);
            exit;
        } else {
            $mensaje = 'Error al eliminar el formulario';
            $tipoMensaje = 'danger';
        }
    }
}

// Obtener parámetros GET para mensajes
if (isset($_GET['mensaje']) && isset($_GET['tipo'])) {
    $mensaje = $_GET['mensaje'];
    $tipoMensaje = $_GET['tipo'];
}

// Título de la página según la acción
$tituloPagina = 'Formularios de Reserva';
if ($accion === 'crear') {
    $tituloPagina = 'Crear Nuevo Formulario';
} elseif ($accion === 'editar' && $idFormulario > 0) {
    $tituloPagina = 'Editar Formulario';
}

// Incluir cabecera
include '../includes/admin-header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?php echo $tituloPagina; ?></h1>
        
        <?php if ($accion === ''): ?>
            <a href="forms.php?accion=crear" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Nuevo Formulario
            </a>
        <?php else: ?>
            <a href="forms.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver a la lista
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($accion === ''): ?>
        <!-- Lista de formularios -->
        <div class="card">
            <div class="card-body">
                <?php
                $formularios = getFormulariosByNegocio($idNegocio);
                
                if (empty($formularios)):
                ?>
                    <div class="alert alert-info">
                        <p class="mb-0">Aún no has creado ningún formulario de reserva. Los formularios te permiten integrar un sistema de reservas en tu sitio web o redes sociales.</p>
                    </div>
                    <div class="text-center">
                        <a href="forms.php?accion=crear" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i>Crear mi primer formulario
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Confirmación</th>
                                    <th>Enlace</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($formularios as $form): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($form['nombre']); ?></td>
                                        <td>
                                            <?php if ($form['confirmacion_automatica']): ?>
                                                <span class="badge bg-success">Automática</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Manual</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control form-control-sm" 
                                                       value="<?php echo htmlspecialchars(getBaseUrl() . 'formulario.php?slug=' . $form['slug']); ?>" 
                                                       readonly>
                                                <button class="btn btn-outline-secondary btn-copiar" 
                                                        type="button" 
                                                        data-clipboard-text="<?php echo htmlspecialchars(getBaseUrl() . 'formulario.php?slug=' . $form['slug']); ?>">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($form['activo']): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', $form['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="forms.php?accion=editar&id=<?php echo $form['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo getBaseUrl() . 'formulario.php?slug=' . $form['slug']; ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger btn-eliminar"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#eliminarModal" 
                                                        data-id="<?php echo $form['id']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($form['nombre']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Modal de confirmación para eliminar -->
        <div class="modal fade" id="eliminarModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar el formulario <strong id="nombreFormulario"></strong>?</p>
                        <p class="text-danger">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <form method="post">
                            <input type="hidden" name="id_formulario" id="idFormularioEliminar">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" name="eliminar_formulario" class="btn btn-danger">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($accion === 'crear' || $accion === 'editar'): ?>
        <!-- Formulario para crear/editar -->
        <div class="card">
            <div class="card-body">
                <form method="post" id="formBuilder">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del formulario*</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo $accion === 'editar' ? htmlspecialchars($formulario['nombre']) : ''; ?>" 
                                       required>
                                <div class="form-text">Este nombre será visible para los clientes.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php 
                                    echo $accion === 'editar' ? htmlspecialchars($formulario['descripcion']) : ''; 
                                ?></textarea>
                                <div class="form-text">Descripción interna del propósito de este formulario (no visible para los clientes).</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Configuración</h5>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="confirmacion_automatica" name="confirmacion_automatica"
                                               <?php echo ($accion === 'editar' && $formulario['confirmacion_automatica']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="confirmacion_automatica">Confirmación automática</label>
                                        <div class="form-text">Las reservas se confirmarán automáticamente sin intervención manual.</div>
                                    </div>
                                    
                                    <?php if ($accion === 'editar'): ?>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="activo" name="activo"
                                                   <?php echo $formulario['activo'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="activo">Formulario activo</label>
                                            <div class="form-text">Desactiva para pausar temporalmente las reservas.</div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Enlace público:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" 
                                                       value="<?php echo htmlspecialchars(getBaseUrl() . 'formulario.php?slug=' . $formulario['slug']); ?>" 
                                                       readonly>
                                                <button class="btn btn-outline-secondary btn-copiar" 
                                                        type="button" 
                                                        data-clipboard-text="<?php echo htmlspecialchars(getBaseUrl() . 'formulario.php?slug=' . $formulario['slug']); ?>">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Comparte este enlace en tus redes sociales.</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mt-4 mb-3">Campos del formulario</h4>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="card-text">Selecciona los campos que aparecerán en el formulario público:</p>
                            
                            <div class="row">
                                <?php
                                $camposPosibles = [
                                    'nombre' => 'Nombre completo',
                                    'email' => 'Email',
                                    'telefono' => 'Teléfono',
                                    'fecha' => 'Fecha',
                                    'hora' => 'Hora',
                                    'personas' => 'Número de personas',
                                    'comentarios' => 'Comentarios'
                                ];
                                
                                $camposActivos = $accion === 'editar' ? $formulario['campos_activos'] : ['nombre', 'email', 'telefono', 'fecha', 'hora', 'personas'];
                                
                                foreach ($camposPosibles as $campo => $etiqueta):
                                ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="campo_<?php echo $campo; ?>" 
                                                   name="campos_activos[]" 
                                                   value="<?php echo $campo; ?>"
                                                   <?php echo in_array($campo, $camposActivos) ? 'checked' : ''; ?>
                                                   <?php echo in_array($campo, ['nombre', 'fecha', 'hora']) ? 'disabled checked' : ''; ?>>
                                            <label class="form-check-label" for="campo_<?php echo $campo; ?>">
                                                <?php echo $etiqueta; ?> 
                                                <?php if (in_array($campo, ['nombre', 'fecha', 'hora'])): ?>
                                                    <span class="text-muted">(Obligatorio)</span>
                                                    <input type="hidden" name="campos_activos[]" value="<?php echo $campo; ?>">
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mt-4 mb-3">Preguntas personalizadas</h4>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="card-text">Añade preguntas específicas para tu negocio:</p>
                            
                            <div id="preguntas-container">
                                <?php 
                                if ($accion === 'editar' && !empty($formulario['preguntas'])):
                                    foreach ($formulario['preguntas'] as $index => $pregunta):
                                ?>
                                    <div class="pregunta-item card mb-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Pregunta</label>
                                                    <input type="text" class="form-control" 
                                                           name="preguntas[<?php echo $index; ?>][pregunta]" 
                                                           value="<?php echo htmlspecialchars($pregunta['pregunta']); ?>" required>
                                                </div>
                                                
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">Tipo</label>
                                                    <select class="form-select pregunta-tipo" 
                                                            name="preguntas[<?php echo $index; ?>][tipo]" 
                                                            data-index="<?php echo $index; ?>">
                                                        <option value="texto" <?php echo $pregunta['tipo'] === 'texto' ? 'selected' : ''; ?>>
                                                            Texto (respuesta corta)
                                                        </option>
                                                        <option value="numero" <?php echo $pregunta['tipo'] === 'numero' ? 'selected' : ''; ?>>
                                                            Número
                                                        </option>
                                                        <option value="lista" <?php echo $pregunta['tipo'] === 'lista' ? 'selected' : ''; ?>>
                                                            Lista desplegable
                                                        </option>
                                                        <option value="checkbox" <?php echo $pregunta['tipo'] === 'checkbox' ? 'selected' : ''; ?>>
                                                            Casillas de verificación
                                                        </option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">Obligatorio</label>
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="preguntas[<?php echo $index; ?>][requerido]" 
                                                               <?php echo $pregunta['requerido'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label">Requerido</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-1 mb-3 text-end">
                                                    <label class="form-label d-block">&nbsp;</label>
                                                    <button type="button" class="btn btn-danger btn-eliminar-pregunta">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="opciones-container" 
                                                 style="<?php echo in_array($pregunta['tipo'], ['lista', 'checkbox']) ? '' : 'display: none;'; ?>">
                                                <label class="form-label">Opciones</label>
                                                <textarea class="form-control" 
                                                          name="preguntas[<?php echo $index; ?>][opciones]" 
                                                          rows="3" 
                                                          placeholder="Escribe cada opción en una línea nueva"><?php 
                                                    if (!empty($pregunta['opciones'])) {
                                                        echo htmlspecialchars(implode("\n", $pregunta['opciones']));
                                                    }
                                                ?></textarea>
                                                <div class="form-text">Escribe cada opción en una línea nueva.</div>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    <div class="alert alert-info">
                                        <p>Aún no has añadido preguntas personalizadas. Puedes hacerlo usando el botón de abajo.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button type="button" id="btn-add-pregunta" class="btn btn-outline-primary mt-2">
                                <i class="fas fa-plus-circle me-2"></i>Añadir pregunta
                            </button>
                        </div>
                    </div>
                    
                    <h4 class="mt-4 mb-3">Mensajes del formulario</h4>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mensaje_header" class="form-label">Mensaje de cabecera</label>
                            <textarea class="form-control" id="mensaje_header" name="mensaje_header" rows="3"><?php 
                                echo $accion === 'editar' ? htmlspecialchars($formulario['mensaje_header']) : 'Completa el siguiente formulario para realizar tu reserva.'; 
                            ?></textarea>
                            <div class="form-text">Este mensaje aparecerá en la parte superior del formulario.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="mensaje_confirmacion" class="form-label">Mensaje de confirmación</label>
                            <textarea class="form-control" id="mensaje
                            <label for="mensaje_confirmacion" class="form-label">Mensaje de confirmación</label>
                            <textarea class="form-control" id="mensaje_confirmacion" name="mensaje_confirmacion" rows="3"><?php 
                                echo $accion === 'editar' ? htmlspecialchars($formulario['mensaje_confirmacion']) : 'Gracias por tu reserva. Te notificaremos cuando sea confirmada.'; 
                            ?></textarea>
                            <div class="form-text">Este mensaje se mostrará después de enviar el formulario.</div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="forms.php" class="btn btn-secondary me-md-2">Cancelar</a>
                        <?php if ($accion === 'crear'): ?>
                            <button type="submit" name="crear_formulario" class="btn btn-primary">Crear formulario</button>
                        <?php else: ?>
                            <button type="submit" name="actualizar_formulario" class="btn btn-primary">Guardar cambios</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Template para nuevas preguntas -->
        <template id="pregunta-template">
            <div class="pregunta-item card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pregunta</label>
                            <input type="text" class="form-control" name="preguntas[INDEX][pregunta]" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select pregunta-tipo" name="preguntas[INDEX][tipo]" data-index="INDEX">
                                <option value="texto">Texto (respuesta corta)</option>
                                <option value="numero">Número</option>
                                <option value="lista">Lista desplegable</option>
                                <option value="checkbox">Casillas de verificación</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Obligatorio</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="preguntas[INDEX][requerido]">
                                <label class="form-check-label">Requerido</label>
                            </div>
                        </div>
                        
                        <div class="col-md-1 mb-3 text-end">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-eliminar-pregunta">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="opciones-container" style="display: none;">
                        <label class="form-label">Opciones</label>
                        <textarea class="form-control" name="preguntas[INDEX][opciones]" rows="3" 
                                  placeholder="Escribe cada opción en una línea nueva"></textarea>
                        <div class="form-text">Escribe cada opción en una línea nueva.</div>
                    </div>
                </div>
            </div>
        </template>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Clipboard.js para botones de copiar
    new ClipboardJS('.btn-copiar');
    
    // Mostrar mensaje al copiar
    document.querySelectorAll('.btn-copiar').forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i>';
            
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    });
    
    // Modal de eliminación
    const eliminarModal = document.getElementById('eliminarModal');
    if (eliminarModal) {
        eliminarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            
            document.getElementById('idFormularioEliminar').value = id;
            document.getElementById('nombreFormulario').textContent = nombre;
        });
    }
    
    // Contador para nuevas preguntas
    let preguntaCounter = <?php echo $accion === 'editar' && !empty($formulario['preguntas']) ? count($formulario['preguntas']) : 0; ?>;
    
    // Añadir nueva pregunta
    const btnAddPregunta = document.getElementById('btn-add-pregunta');
    if (btnAddPregunta) {
        btnAddPregunta.addEventListener('click', function() {
            const template = document.getElementById('pregunta-template');
            const contenedor = document.getElementById('preguntas-container');
            
            // Limpiar mensaje de "no hay preguntas"
            const mensajeInfo = contenedor.querySelector('.alert-info');
            if (mensajeInfo) {
                mensajeInfo.remove();
            }
            
            // Clonar template
            const preguntaHTML = template.content.cloneNode(true);
            
            // Reemplazar INDEX con contador
            preguntaHTML.querySelectorAll('[name*="[INDEX]"]').forEach(input => {
                input.name = input.name.replace('INDEX', preguntaCounter);
            });
            
            preguntaHTML.querySelector('.pregunta-tipo').dataset.index = preguntaCounter;
            
            // Añadir al contenedor
            contenedor.appendChild(preguntaHTML);
            
            // Incrementar contador
            preguntaCounter++;
            
            // Inicializar eventos para la nueva pregunta
            initPreguntaEvents();
        });
    }
    
    // Inicializar eventos para preguntas existentes
    initPreguntaEvents();
    
    function initPreguntaEvents() {
        // Eliminar pregunta
        document.querySelectorAll('.btn-eliminar-pregunta').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.pregunta-item').remove();
                
                // Mostrar mensaje si no hay preguntas
                const contenedor = document.getElementById('preguntas-container');
                if (contenedor.children.length === 0) {
                    contenedor.innerHTML = `
                        <div class="alert alert-info">
                            <p>Aún no has añadido preguntas personalizadas. Puedes hacerlo usando el botón de abajo.</p>
                        </div>
                    `;
                }
            });
        });
        
        // Cambiar tipo de pregunta
        document.querySelectorAll('.pregunta-tipo').forEach(select => {
            select.addEventListener('change', function() {
                const preguntaItem = this.closest('.pregunta-item');
                const opcionesContainer = preguntaItem.querySelector('.opciones-container');
                
                if (this.value === 'lista' || this.value === 'checkbox') {
                    opcionesContainer.style.display = 'block';
                } else {
                    opcionesContainer.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php include '../includes/admin-footer.php'; ?>