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

include 'includes/header.php';
?>

<style>
.container-max-width {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}
.whatsapp-button {
    background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
    box-shadow: 0 2px 8px rgba(37, 211, 102, 0.3);
    transition: all 0.3s ease;
}

.whatsapp-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
}

.whatsapp-button:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
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
</style>

<div class="container-max-width">

    <!-- Botón de volver -->
    <!-- <div class="flex items-center mb-6">
        <a href="javascript:history.back()" class="mr-4 p-2 rounded-full hover:bg-gray-100">
            <i class="ri-arrow-left-line text-gray-600 text-xl"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Detalle de Reserva</h1>
    </div> -->

    <!-- Información de la reserva -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-start justify-between mb-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-16 w-16">
                    <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="ri-calendar-check-line text-blue-600 text-2xl"></i>
                    </div>
                </div>
                <div class="ml-6">
                    <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($reserva['nombre']); ?></h2>
                    <p class="text-gray-600 flex items-center mt-1">
                        <i class="ri-phone-line mr-1"></i>
                        <?php echo htmlspecialchars($reserva['telefono']); ?>
                    </p>
                    <?php if (!empty($reserva['email'])): ?>
                    <p class="text-gray-600 flex items-center mt-1">
                        <i class="ri-mail-line mr-1"></i>
                        <?php echo htmlspecialchars($reserva['email']); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                <?php 
                echo match($reserva['estado']) {
                    'confirmada' => 'bg-green-100 text-green-800',
                    'pendiente' => 'bg-amber-100 text-amber-800',
                    'rechazada' => 'bg-red-100 text-red-800',
                    'cancelada' => 'bg-gray-100 text-gray-800',
                    default => 'bg-gray-100 text-gray-800'
                };
                ?>">
                <i class="ri-<?php 
                    echo match($reserva['estado']) {
                        'confirmada' => 'check-line',
                        'pendiente' => 'time-line',
                        'rechazada' => 'close-line',
                        'cancelada' => 'close-circle-line',
                        default => 'question-line'
                    };
                ?> mr-1"></i>
                <?php 
                    echo match($reserva['estado']) {
                        'confirmada' => 'Confirmada',
                        'pendiente' => 'Pendiente',
                        'rechazada' => 'Rechazada',
                        'cancelada' => 'Cancelada',
                        default => ucfirst($reserva['estado'])
                    };
                ?>
            </span>
        </div>
        
        <!-- Información de fecha y hora -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200">
            <div>
                <div class="flex items-center text-sm text-gray-500 mb-1">
                    <i class="ri-calendar-line mr-2"></i>
                    <span class="font-medium">Fecha de la reserva</span>
                </div>
                <div class="text-lg font-semibold text-gray-900 ml-6">
                    <?php echo formatearFecha($reserva['fecha']); ?>
                </div>
            </div>
            
            <div>
                <div class="flex items-center text-sm text-gray-500 mb-1">
                    <i class="ri-time-line mr-2"></i>
                    <span class="font-medium">Hora de la reserva</span>
                </div>
                <div class="text-lg font-semibold text-gray-900 ml-6">
                    <?php echo substr($reserva['hora'], 0, 5); ?>
                </div>
            </div>
            
            <?php if (isset($reserva['created_at']) && $reserva['created_at']): ?>
            <div>
                <div class="flex items-center text-sm text-gray-500 mb-1">
                    <i class="ri-history-line mr-2"></i>
                    <span class="font-medium">Solicitud creada</span>
                </div>
                <div class="text-sm text-gray-700 ml-6">
                    <?php echo date('d/m/Y H:i', strtotime($reserva['created_at'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mensaje de la reserva -->
    <?php if (!empty($reserva['mensaje'])): ?>
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
            <i class="ri-message-2-line mr-2 text-blue-600"></i>
            Mensaje
        </h3>
        <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-blue-400">
            <p class="text-gray-700 italic"><?php echo nl2br(htmlspecialchars($reserva['mensaje'])); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Notas internas -->
    <?php if (!empty($reserva['notas_internas'])): ?>
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
            <i class="ri-sticky-note-line mr-2 text-amber-600"></i>
            Notas Internas
        </h3>
        <div class="bg-amber-50 rounded-lg p-4 border-l-4 border-amber-400">
            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($reserva['notas_internas'])); ?></p>
        </div>
    </div>
    <?php endif; ?>


    <!-- Acciones -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Acciones</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <?php if ($reserva['estado'] === 'pendiente'): ?>
                <!-- Aceptar -->
                <button id="confirmarBtn" 
                        class="inline-flex items-center justify-center px-4 py-3 border border-green-300 shadow-sm text-sm font-medium rounded-md text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                    <i class="ri-check-line mr-2"></i>
                    Aceptar
                </button>
                
                <!-- Rechazar -->
                <button id="rechazarBtn" 
                        class="inline-flex items-center justify-center px-4 py-3 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                    <i class="ri-close-line mr-2"></i>
                    Rechazar
                </button>
                
                <!-- Editar -->
                <a href="/reserva-form?id=<?php echo $reserva['id']; ?>" 
                class="inline-flex items-center justify-center px-4 py-3 border border-blue-300 shadow-sm text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <i class="ri-edit-line mr-2"></i>
                    Editar
                </a>
                
            <?php elseif ($reserva['estado'] === 'confirmada'): ?>
                <!-- Cancelar -->
                <button id="cancelarBtn" 
                        class="inline-flex items-center justify-center px-4 py-3 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                    <i class="ri-close-circle-line mr-2"></i>
                    Cancelar
                </button>
                
                <!-- Editar -->
                <a href="/reserva-form?id=<?php echo $reserva['id']; ?>" 
                class="inline-flex items-center justify-center px-4 py-3 border border-blue-300 shadow-sm text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <i class="ri-edit-line mr-2"></i>
                    Editar
                </a>
                
            <?php endif; ?>
            
            <!-- WhatsApp (siempre disponible) -->
            <button 
                onclick="openWhatsAppChat('<?php echo addslashes($reserva['telefono']); ?>', '<?php echo addslashes($reserva['nombre']); ?>')"
                class="whatsapp-button inline-flex items-center justify-center px-4 py-3 border border-green-300 shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 <?php echo !$whatsappConnected ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                <?php echo !$whatsappConnected ? 'disabled title="WhatsApp no está conectado"' : ''; ?>
            >
                <i class="ri-whatsapp-line mr-2"></i>
                WhatsApp
            </button>
            
            <!-- Ver Cliente -->
            <a href="/cliente?telefono=<?php echo urlencode($reserva['telefono']); ?>" 
            class="inline-flex items-center justify-center px-4 py-3 border border-purple-300 shadow-sm text-sm font-medium rounded-md text-purple-700 bg-white hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                <i class="ri-user-line mr-2"></i>
                Ver Cliente
            </a>
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
include 'includes/footer.php'; 
?>