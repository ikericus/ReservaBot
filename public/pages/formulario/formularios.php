<?php
// pages/formularios.php

// Configurar la página actual
$pageTitle = 'ReservaBot - Enlaces de Reserva';
$currentPage = 'formularios';
$pageScript = 'formularios';

// Obtener usuario
$currentUser = getAuthenticatedUser();
$usuario_id = $currentUser['id'];

$formularioDomain = getContainer()->getFormularioDomain();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Procesar eliminación de enlace
    if(isset($_POST['eliminar_enlace'])) {
        $id = intval($_POST['id'] ?? 0);
        
        try {
            $eliminado = $formularioDomain->eliminarFormulario($id, $usuario_id);
            
            if ($eliminado) {
                setFlashSuccess('Enlace eliminado correctamente');
            } else {
                setFlashError('Formulario no encontrado o no tienes permisos');
            }
        } catch (Exception $e) {
            setFlashError('Error al eliminar el enlace');
            error_log("Error eliminando formulario: " . $e->getMessage());
        }
    }
    
    // Procesar creación de enlace
    if (isset($_POST['crear_enlace'])) {
        try {
            $formularioDomain->crearFormulario([
                'nombre' => trim($_POST['nombre'] ?? ''),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'activo' => true,
                'confirmacion_automatica' => isset($_POST['confirmacion_auto']),
            ], $usuario_id);
            
            setFlashSuccess('Enlace de reserva creado correctamente');
            
        } catch (InvalidArgumentException $e) {
            setFlashError('Error de validación: ' . $e->getMessage());
        } catch (Exception $e) {
            setFlashError('Error al crear el formulario');
            error_log("Error creando formulario: " . $e->getMessage());
        }
    }
    
    // Redirigir para evitar reenvío de formulario
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Obtener enlaces existentes y configuración del usuario
try {
    $formulariosEntities = $formularioDomain->obtenerFormulariosUsuario($usuario_id);
    $enlaces = array_map(fn($f) => $f->toArray(), $formulariosEntities);
    
    $configuracionDomain = getContainer()->getConfiguracionDomain();
    $configUsuario = $configuracionDomain->obtenerConfiguraciones($usuario_id);

} catch (Exception $e) {
    setFlashError('Error al obtener formularios: ' . $e->getMessage());
    $enlaces = [];
    $configUsuario = [];
}

// Incluir cabecera
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-link me-2"></i>Enlaces de Reserva
                    </h5>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearEnlace">
                        <i class="fas fa-plus me-1"></i>Crear Nuevo Enlace
                    </button>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($enlaces)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No tienes enlaces de reserva creados aún. Crea tu primer enlace para permitir que tus clientes hagan reservas.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Confirmación</th>
                                        <th>Enlace</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enlaces as $enlace): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($enlace['nombre']) ?></strong>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($enlace['descripcion'] ?? '-') ?>
                                            </td>
                                            <td>
                                                <?php if ($enlace['activo']): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($enlace['confirmacion_automatica']): ?>
                                                    <span class="badge bg-info">Automática</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Manual</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" 
                                                           class="form-control form-control-sm" 
                                                           value="<?= BASE_URL ?><?= htmlspecialchars($enlace['url_publica'] ?? "/pages/reservar?f={$enlace['slug']}") ?>" 
                                                           readonly 
                                                           id="url-<?= $enlace['id'] ?>">
                                                    <button class="btn btn-outline-secondary" 
                                                            type="button" 
                                                            onclick="copiarUrl(<?= $enlace['id'] ?>)">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                    <a href="<?= BASE_URL ?><?= htmlspecialchars($enlace['url_publica'] ?? "/pages/reservar?f={$enlace['slug']}") ?>" 
                                                       target="_blank" 
                                                       class="btn btn-outline-primary">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este enlace?');">
                                                    <input type="hidden" name="eliminar_enlace" value="1">
                                                    <input type="hidden" name="id" value="<?= $enlace['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Enlace -->
<div class="modal fade" id="modalCrearEnlace" tabindex="-1" aria-labelledby="modalCrearEnlaceLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearEnlaceLabel">Crear Nuevo Enlace de Reserva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="crear_enlace" value="1">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del enlace *</label>
                        <input type="text" 
                               class="form-control" 
                               id="nombre" 
                               name="nombre" 
                               required 
                               minlength="3" 
                               maxlength="100"
                               placeholder="Ej: Reservas web, Formulario principal, etc.">
                        <small class="text-muted">Nombre interno para identificar este enlace</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción (opcional)</label>
                        <textarea class="form-control" 
                                  id="descripcion" 
                                  name="descripcion" 
                                  rows="3"
                                  placeholder="Describe para qué usarás este enlace"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="confirmacion_auto" 
                                   name="confirmacion_auto">
                            <label class="form-check-label" for="confirmacion_auto">
                                Confirmación automática de reservas
                            </label>
                            <small class="d-block text-muted">Si está activado, las reservas se confirmarán automáticamente sin necesidad de revisión manual</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Los datos de tu empresa (nombre, logo, colores, contacto) se tomarán de la configuración de tu perfil.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Crear Enlace
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copiarUrl(id) {
    const input = document.getElementById(`url-${id}`);
    input.select();
    input.setSelectionRange(0, 99999); // Para móviles
    
    navigator.clipboard.writeText(input.value).then(() => {
        // Mostrar feedback
        const boton = event.target.closest('button');
        const iconoOriginal = boton.innerHTML;
        boton.innerHTML = '<i class="fas fa-check"></i>';
        boton.classList.add('btn-success');
        boton.classList.remove('btn-outline-secondary');
        
        setTimeout(() => {
            boton.innerHTML = iconoOriginal;
            boton.classList.remove('btn-success');
            boton.classList.add('btn-outline-secondary');
        }, 2000);
    }).catch(err => {
        console.error('Error al copiar:', err);
        alert('No se pudo copiar el enlace');
    });
}
</script>

<?php include 'includes/footer.php'; ?>