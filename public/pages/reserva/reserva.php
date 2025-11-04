<?php
// pages/reserva/reserva.php

// Configurar la página actual
$currentPage = 'reserva';
$pageTitle = 'ReservaBot - Detalle de Reserva';
$pageScript = 'reserva';

// Obtener usuario autenticado
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

// Obtener ID de la reserva
$reservaId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($reservaId <= 0) {
    // Si no hay ID válido, redirigir al calendario
    header('Location: /mes');
    exit;
}

// Obtener la reserva usando la capa de dominio
$reserva = null;
try {
    $reservaDomain = getContainer()->getReservaDomain();
    $reservaEntity = $reservaDomain->obtenerReserva($reservaId, $userId);
    
    // Convertir entidad a array para la vista
    $reserva = $reservaEntity->toArray();
    
} catch (\DomainException $e) {
    // Reserva no encontrada o no pertenece al usuario
    error_log('Error obteniendo reserva: ' . $e->getMessage());
    setFlashError('Reserva no encontrada');
    header('Location: /mes');
    exit;
} catch (\Exception $e) {
    error_log('Error inesperado obteniendo reserva: ' . $e->getMessage());
    setFlashError('Error al obtener la reserva');
    header('Location: /mes');
    exit;
}

// Verificar estado de WhatsApp usando dominio
$whatsappConnected = false;
try {
    $whatsappDomain = getContainer()->getWhatsAppDomain();
    $whatsappConnected = $whatsappDomain->puedeEnviarMensajes($userId);
} catch (\Exception $e) {
    error_log('Error obteniendo estado de WhatsApp: ' . $e->getMessage());
    $whatsappConnected = false;
}

// Incluir la cabecera
include 'includes/header.php';
?>

<style>
/* Estilos específicos para la página de reserva */
.reservation-container {
    max-width: 800px;
    margin: 0 auto;
}

.back-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    color: #6b7280;
    text-decoration: none;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.back-button:hover {
    background: #f3f4f6;
    color: #374151;
    text-decoration: none;
}

.reservation-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.reservation-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
    color: white;
    text-align: center;
}

.reservation-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.reservation-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
}

.reservation-content {
    padding: 2rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.info-icon {
    flex-shrink: 0;
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.125rem;
}

.info-content h3 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 0.25rem 0;
}

.info-content p {
    font-size: 1rem;
    font-weight: 500;
    color: #1f2937;
    margin: 0;
}

.message-section {
    background: #f8fafc;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.message-section h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 0.75rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.message-content {
    color: #6b7280;
    font-size: 0.875rem;
    line-height: 1.5;
    font-style: italic;
}

.actions-section {
    border-top: 1px solid #e5e7eb;
    padding: 1.5rem 2rem;
    background: #fafafa;
}

.actions-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 1rem 0;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.action-btn:hover {
    text-decoration: none;
    transform: translateY(-1px);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-success:hover {
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    color: white;
}

.btn-whatsapp {
    background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(37, 211, 102, 0.3);
}

.btn-whatsapp:hover {
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
    color: white;
}

.btn-whatsapp:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
    opacity: 0.6;
}

.btn-danger {
    background: white;
    color: #dc2626;
    border: 1px solid #fecaca;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.1);
}

.btn-danger:hover {
    background: #fef2f2;
    color: #b91c1c;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
}

/* Modal de confirmación */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 50;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-overlay.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    width: 100%;
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.modal-header {
    padding: 1.5rem 1.5rem 1rem 1.5rem;
    text-align: center;
}

.modal-icon {
    width: 3rem;
    height: 3rem;
    background: #fef2f2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem auto;
    color: #dc2626;
    font-size: 1.5rem;
}

.modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

.modal-message {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

.modal-actions {
    padding: 1rem 1.5rem 1.5rem 1.5rem;
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
}

.modal-btn {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.modal-btn-cancel {
    background: #f3f4f6;
    color: #374151;
}

.modal-btn-cancel:hover {
    background: #e5e7eb;
}

.modal-btn-confirm {
    background: #dc2626;
    color: white;
}

.modal-btn-confirm:hover {
    background: #b91c1c;
}

/* Responsive */
@media (max-width: 768px) {
    .reservation-container {
        margin: 0;
        padding: 0 1rem;
    }
    
    .reservation-header {
        padding: 1.5rem;
    }
    
    .reservation-title {
        font-size: 1.25rem;
    }
    
    .reservation-content {
        padding: 1.5rem;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-section {
        padding: 1.5rem;
    }
}
</style>

<div class="reservation-container">
    <!-- Botón de retroceso -->
    <a href="javascript:history.back()" class="back-button">
        <i class="ri-arrow-left-line"></i>
        Volver
    </a>
    
    <!-- Tarjeta principal de la reserva -->
    <div class="reservation-card">
        <!-- Header con información principal -->
        <div class="reservation-header">
            <h1 class="reservation-title"><?php echo htmlspecialchars($reserva['nombre']); ?></h1>
            <div class="reservation-status-badge">
                <i class="ri-<?php echo $reserva['estado'] === 'confirmada' ? 'check-line' : 'time-line'; ?>"></i>
                <?php echo $reserva['estado'] === 'confirmada' ? 'Confirmada' : 'Pendiente'; ?>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div class="reservation-content">
            <!-- Grid de información -->
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="ri-calendar-line"></i>
                    </div>
                    <div class="info-content">
                        <h3>Fecha</h3>
                        <p><?php echo formatearFecha($reserva['fecha']); ?></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="ri-time-line"></i>
                    </div>
                    <div class="info-content">
                        <h3>Hora</h3>
                        <p><?php echo substr($reserva['hora'], 0, 5); ?></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="ri-phone-line"></i>
                    </div>
                    <div class="info-content">
                        <h3>Teléfono</h3>
                        <p><?php echo htmlspecialchars($reserva['telefono']); ?></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="ri-user-line"></i>
                    </div>
                    <div class="info-content">
                        <h3>Cliente</h3>
                        <p><?php echo htmlspecialchars($reserva['nombre']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Sección de mensaje -->
            <?php if (!empty($reserva['mensaje'])): ?>
            <div class="message-section">
                <h3>
                    <i class="ri-message-2-line"></i>
                    Mensaje o notas
                </h3>
                <div class="message-content">
                    <?php echo nl2br(htmlspecialchars($reserva['mensaje'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sección de acciones -->
        <div class="actions-section">
            <h3 class="actions-title">Acciones</h3>
            <div class="actions-grid">
                <!-- Editar reserva -->
                <a href="/reserva-form?id=<?php echo $reserva['id']; ?>" class="action-btn btn-primary">
                    <i class="ri-edit-line"></i>
                    Editar
                </a>
                
                <!-- Confirmar reserva (solo si está pendiente) -->
                <?php if ($reserva['estado'] === 'pendiente'): ?>
                <button id="confirmarBtn" class="action-btn btn-success">
                    <i class="ri-check-line"></i>
                    Confirmar
                </button>
                <?php endif; ?>
                
                <!-- WhatsApp -->
                <button 
                    onclick="openWhatsAppChat('<?php echo addslashes($reserva['telefono']); ?>', '<?php echo addslashes($reserva['nombre']); ?>')" 
                    class="action-btn btn-whatsapp"
                    <?php echo !$whatsappConnected ? 'disabled title="WhatsApp no está conectado"' : ''; ?>
                >
                    <i class="ri-whatsapp-line"></i>
                    WhatsApp
                </button>
                
                <!-- Eliminar reserva -->
                <button id="eliminarBtn" class="action-btn btn-danger">
                    <i class="ri-delete-bin-line"></i>
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon">
                <i class="ri-delete-bin-line"></i>
            </div>
            <h3 class="modal-title">Eliminar Reserva</h3>
            <p class="modal-message">
                ¿Estás seguro de que deseas eliminar esta reserva? Esta acción no se puede deshacer.
            </p>
        </div>
        <div class="modal-actions">
            <button type="button" id="cancelDeleteBtn" class="modal-btn modal-btn-cancel">
                Cancelar
            </button>
            <button type="button" id="confirmDeleteBtn" class="modal-btn modal-btn-confirm">
                Eliminar
            </button>
        </div>
    </div>
</div>

<?php 
    // Incluir componente de conversación antes de los scripts
    include 'components/conversacion.php';
?>

<!-- JavaScript para manejar las acciones -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmarBtn = document.getElementById('confirmarBtn');
    const eliminarBtn = document.getElementById('eliminarBtn');
    const deleteModal = document.getElementById('deleteModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    // Confirmar reserva
    if (confirmarBtn) {
        confirmarBtn.addEventListener('click', function() {
            if (confirm('¿Confirmar esta reserva?')) {
                // Crear formulario para enviar la confirmación
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/api/confirmar-reserva';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = '<?php echo $reserva['id']; ?>';
                
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    
    // Mostrar modal de eliminación
    if (eliminarBtn) {
        eliminarBtn.addEventListener('click', function() {
            deleteModal.classList.add('show');
        });
    }
    
    // Cancelar eliminación
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            deleteModal.classList.remove('show');
        });
    }
    
    // Confirmar eliminación
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            // Crear formulario para enviar la eliminación
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/api/cancelar-reserva';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = '<?php echo $reserva['id']; ?>';
            
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        });
    }
    
    // Cerrar modal al hacer clic fuera
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.classList.remove('show');
        }
    });
    
    // Cerrar modal con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && deleteModal.classList.contains('show')) {
            deleteModal.classList.remove('show');
        }
    });
});

</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>