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
                <?php
                $iconoEstado = match($reserva['estado']) {
                    'confirmada' => 'check-line',
                    'pendiente' => 'time-line',
                    'rechazada' => 'close-line',
                    'cancelada' => 'close-circle-line',
                    default => 'question-line'
                };
                
                $labelEstado = match($reserva['estado']) {
                    'confirmada' => 'Confirmada',
                    'pendiente' => 'Pendiente',
                    'rechazada' => 'Rechazada',
                    'cancelada' => 'Cancelada',
                    default => ucfirst($reserva['estado'])
                };
                ?>
                <i class="ri-<?php echo $iconoEstado; ?>"></i>
                <?php echo $labelEstado; ?>
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
                <?php if ($reserva['estado'] === 'pendiente'): ?>
                    <!-- Acciones para reservas PENDIENTES -->
                    
                    <!-- Confirmar/Aceptar reserva -->
                    <button id="confirmarBtn" class="action-btn btn-success">
                        <i class="ri-check-line"></i>
                        Aceptar
                    </button>
                    
                    <!-- Rechazar reserva -->
                    <button id="rechazarBtn" class="action-btn btn-danger">
                        <i class="ri-close-line"></i>
                        Rechazar
                    </button>
                    
                    <!-- Editar reserva -->
                    <a href="/reserva-form?id=<?php echo $reserva['id']; ?>" class="action-btn btn-primary">
                        <i class="ri-edit-line"></i>
                        Editar
                    </a>
                    
                <?php elseif ($reserva['estado'] === 'confirmada'): ?>
                    <!-- Acciones para reservas CONFIRMADAS -->
                    
                    <!-- Cancelar reserva -->
                    <button id="cancelarBtn" class="action-btn btn-danger">
                        <i class="ri-close-circle-line"></i>
                        Cancelar
                    </button>
                    
                    <!-- Editar reserva -->
                    <a href="/reserva-form?id=<?php echo $reserva['id']; ?>" class="action-btn btn-primary">
                        <i class="ri-edit-line"></i>
                        Editar
                    </a>
                    
                <?php else: ?>
                    <!-- Acciones para reservas RECHAZADAS o CANCELADAS -->
                    <p class="text-gray-500 text-sm">Esta reserva no permite acciones adicionales.</p>
                <?php endif; ?>
                
                <!-- WhatsApp (disponible para todos los estados) -->
                <button 
                    onclick="openWhatsAppChat('<?php echo addslashes($reserva['telefono']); ?>', '<?php echo addslashes($reserva['nombre']); ?>')" 
                    class="action-btn btn-whatsapp"
                    <?php echo !$whatsappConnected ? 'disabled title="WhatsApp no está conectado"' : ''; ?>
                >
                    <i class="ri-whatsapp-line"></i>
                    WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para rechazar -->
<div id="rechazarModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon">
                <i class="ri-close-line"></i>
            </div>
            <h3 class="modal-title">Rechazar Reserva</h3>
            <p class="modal-message">
                ¿Estás seguro de que deseas rechazar esta solicitud de reserva?
            </p>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('rechazarModal')">
                Cancelar
            </button>
            <button type="button" id="confirmRechazarBtn" class="modal-btn modal-btn-confirm">
                Rechazar
            </button>
        </div>
    </div>
</div>

<!-- Modal de confirmación para cancelar -->
<div id="cancelarModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon">
                <i class="ri-close-circle-line"></i>
            </div>
            <h3 class="modal-title">Cancelar Reserva</h3>
            <p class="modal-message">
                ¿Estás seguro de que deseas cancelar esta reserva confirmada?
            </p>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('cancelarModal')">
                No, mantener
            </button>
            <button type="button" id="confirmCancelarBtn" class="modal-btn modal-btn-confirm">
                Sí, cancelar
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
    const rechazarBtn = document.getElementById('rechazarBtn');
    const cancelarBtn = document.getElementById('cancelarBtn');
    
    // Confirmar/Aceptar reserva
    if (confirmarBtn) {
        confirmarBtn.addEventListener('click', function() {
            if (confirm('¿Aceptar esta solicitud de reserva?')) {
                fetch('/api/actualizar-reserva', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        id: <?php echo $reserva['id']; ?>, 
                        estado: 'confirmada' 
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error al confirmar la reserva: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud');
                });
            }
        });
    }
    
    // Rechazar reserva
    if (rechazarBtn) {
        rechazarBtn.addEventListener('click', function() {
            openModal('rechazarModal');
        });
        
        document.getElementById('confirmRechazarBtn').addEventListener('click', function() {
            fetch('/api/rechazar-reserva', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    id: <?php echo $reserva['id']; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/reservas';
                } else {
                    alert('Error al rechazar la reserva: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        });
    }
    
    // Cancelar reserva
    if (cancelarBtn) {
        cancelarBtn.addEventListener('click', function() {
            openModal('cancelarModal');
        });
        
        document.getElementById('confirmCancelarBtn').addEventListener('click', function() {
            fetch('/api/cancelar-reserva', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    id: <?php echo $reserva['id']; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error al cancelar la reserva: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        });
    }
    
    // Cerrar modal con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('rechazarModal');
            closeModal('cancelarModal');
        }
    });
});

// Funciones de modal
function openModal(modalId) {
    document.getElementById(modalId).classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('show');
    }
});
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>