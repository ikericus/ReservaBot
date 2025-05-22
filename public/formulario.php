<?php
/**
 * Página de formulario público de reservas
 */

// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';
require_once 'includes/formbuilder-functions.php';

// Incluir cabecera y pie
include 'includes/header.php';
include 'includes/footer.php';

// Obtener slug desde la URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    // Redirigir a página de error o inicio
    header('Location: index.php');
    exit;
}

// Obtener formulario por slug
$formulario = getFormularioBySlug($slug);

if (!$formulario) {
    // Formulario no encontrado o no activo
    include 'header.php';
    echo '<div class="container mt-5">
            <div class="alert alert-danger">
                <h4>Formulario no encontrado</h4>
                <p>El formulario que estás buscando no existe o no está disponible.</p>
            </div>
          </div>';
    include 'footer.php';
    exit;
}

// Comprobar si se ha enviado el formulario
$mensaje = '';
$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar el formulario
    $resultado = procesarReservaFormulario($_POST);
    
    if ($resultado['success']) {
        $mensaje = !empty($formulario['mensaje_confirmacion']) 
            ? $formulario['mensaje_confirmacion'] 
            : 'Gracias por tu reserva. ' . ($resultado['confirmada'] 
                ? 'Ha sido confirmada.' 
                : 'Te notificaremos cuando sea confirmada.');
    } else {
        $mensaje = $resultado['message'];
    }
}

// Incluir cabecera
$pageTitle = $formulario['nombre'];
include 'header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0"><?php echo htmlspecialchars($formulario['nombre']); ?></h3>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($formulario['mensaje_header'])): ?>
                        <div class="alert alert-info">
                            <?php echo nl2br(htmlspecialchars($formulario['mensaje_header'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($mensaje)): ?>
                        <div class="alert <?php echo $resultado && $resultado['success'] ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo $mensaje; ?>
                        </div>
                        
                        <?php if ($resultado && $resultado['success']): ?>
                            <div class="text-center my-4">
                                <a href="<?php echo $_SERVER['REQUEST_URI']; ?>" class="btn btn-primary">Realizar otra reserva</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (empty($mensaje) || !($resultado && $resultado['success'])): ?>
                        <form method="post" id="reserva-form">
                            <input type="hidden" name="id_formulario" value="<?php echo $formulario['id']; ?>">
                            
                            <!-- Campos base según configuración -->
                            <div class="row">
                                <?php if (in_array('nombre', $formulario['campos_activos'])): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre" class="form-label">Nombre completo*</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (in_array('email', $formulario['campos_activos'])): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email*</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (in_array('telefono', $formulario['campos_activos'])): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono">
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (in_array('fecha', $formulario['campos_activos'])): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha" class="form-label">Fecha*</label>
                                        <input type="date" class="form-control" id="fecha" name="fecha" required 
                                               min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (in_array('hora', $formulario['campos_activos'])): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="hora" class="form-label">Hora*</label>
                                        <input type="time" class="form-control" id="hora" name="hora" required>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (in_array('personas', $formulario['campos_activos'])): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="personas" class="form-label">Número de personas</label>
                                        <input type="number" class="form-control" id="personas" name="personas" 
                                               min="1" value="1">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Preguntas personalizadas -->
                             <!-- Preguntas personalizadas -->
                            <?php if (!empty($formulario['preguntas'])): ?>
                                <hr>
                                <h5 class="mb-3">Información adicional</h5>
                                
                                <?php foreach ($formulario['preguntas'] as $pregunta): ?>
                                    <div class="mb-3">
                                        <label for="pregunta_<?php echo $pregunta['id']; ?>" class="form-label">
                                            <?php echo htmlspecialchars($pregunta['pregunta']); ?>
                                            <?php if ($pregunta['requerido']): ?>*<?php endif; ?>
                                        </label>
                                        
                                        <?php if ($pregunta['tipo'] === 'texto'): ?>
                                            <input type="text" class="form-control" 
                                                  id="pregunta_<?php echo $pregunta['id']; ?>" 
                                                  name="pregunta_<?php echo $pregunta['id']; ?>"
                                                  <?php if ($pregunta['requerido']): ?>required<?php endif; ?>>
                                                  
                                        <?php elseif ($pregunta['tipo'] === 'numero'): ?>
                                            <input type="number" class="form-control" 
                                                  id="pregunta_<?php echo $pregunta['id']; ?>" 
                                                  name="pregunta_<?php echo $pregunta['id']; ?>"
                                                  <?php if ($pregunta['requerido']): ?>required<?php endif; ?>>
                                                  
                                        <?php elseif ($pregunta['tipo'] === 'lista'): ?>
                                            <select class="form-select" 
                                                   id="pregunta_<?php echo $pregunta['id']; ?>" 
                                                   name="pregunta_<?php echo $pregunta['id']; ?>"
                                                   <?php if ($pregunta['requerido']): ?>required<?php endif; ?>>
                                                <option value="">Seleccionar...</option>
                                                <?php if (!empty($pregunta['opciones'])): ?>
                                                    <?php foreach ($pregunta['opciones'] as $opcion): ?>
                                                        <option value="<?php echo htmlspecialchars($opcion); ?>">
                                                            <?php echo htmlspecialchars($opcion); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                            
                                        <?php elseif ($pregunta['tipo'] === 'checkbox'): ?>
                                            <?php if (!empty($pregunta['opciones'])): ?>
                                                <?php foreach ($pregunta['opciones'] as $index => $opcion): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                              id="pregunta_<?php echo $pregunta['id']; ?>_<?php echo $index; ?>" 
                                                              name="pregunta_<?php echo $pregunta['id']; ?>[]"
                                                              value="<?php echo htmlspecialchars($opcion); ?>">
                                                        <label class="form-check-label" for="pregunta_<?php echo $pregunta['id']; ?>_<?php echo $index; ?>">
                                                            <?php echo htmlspecialchars($opcion); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <!-- Comentarios -->
                            <?php if (in_array('comentarios', $formulario['campos_activos'])): ?>
                                <div class="mb-3">
                                    <label for="comentarios" class="form-label">Comentarios o solicitudes especiales</label>
                                    <textarea class="form-control" id="comentarios" name="comentarios" rows="3"></textarea>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">Confirmar reserva</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer text-muted">
                    <small>
                        <?php if ($formulario['confirmacion_automatica']): ?>
                            Las reservas se confirman automáticamente.
                        <?php else: ?>
                            Las reservas requieren confirmación manual por parte del negocio.
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validar formulario antes de enviar
    const form = document.getElementById('reserva-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validar fecha (que no sea pasada)
            const fechaInput = document.getElementById('fecha');
            if (fechaInput) {
                const fechaSeleccionada = new Date(fechaInput.value);
                const hoy = new Date();
                hoy.setHours(0, 0, 0, 0);
                
                if (fechaSeleccionada < hoy) {
                    alert('Por favor, selecciona una fecha futura.');
                    e.preventDefault();
                    isValid = false;
                }
            }
            
            // Más validaciones si son necesarias...
            
            return isValid;
        });
    }
});
</script>

<?php include 'footer.php'; ?>