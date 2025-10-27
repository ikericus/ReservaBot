<?php

// Configurar la página actual
$currentPage = 'reserva-form';
$pageTitle = 'ReservaBot - Formulario de Reserva';
$pageScript = 'reserva-form';

// Obtener mensajes de error y datos del formulario si existen
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
$formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];

// Limpiar los mensajes de la sesión después de obtenerlos
if (isset($_SESSION['error'])) unset($_SESSION['error']);
if (isset($_SESSION['form_data'])) unset($_SESSION['form_data']);

// Comprobar si es modo edición o creación
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$isEditMode = $id > 0;

// Para nuevas reservas, usar siempre la fecha de hoy por defecto
if (!$isEditMode) {
    $fecha = isset($formData['fecha']) ? $formData['fecha'] : date('Y-m-d');
} else {
    // En modo edición, se establecerá cuando obtengamos la reserva
    $fecha = null;
}

// Obtener la reserva si estamos en modo edición
$reserva = null;
if ($isEditMode) {
    try {
        $reservaUseCases = getContainer()->getReservaUseCases();
        $reservaObj = $reservaUseCases->obtenerReserva($id, $usuarioId);
        
        // Convertir a array para compatibilidad con el resto del código
        $reserva = [
            'id' => $reservaObj->getId(),
            'nombre' => $reservaObj->getNombre(),
            'telefono' => $reservaObj->getTelefono()->getValue(),
            'fecha' => $reservaObj->getFecha()->format('Y-m-d'),
            'hora' => $reservaObj->getHora(),
            'mensaje' => $reservaObj->getMensaje(),
            'estado' => $reservaObj->getEstado()->value
        ];
        
        if (!$reserva) {
            // Si la reserva no existe, redirigir al calendario
            header('Location: /dia');
            exit;
        }
        
        // Usar la fecha de la reserva en modo edición
        $fecha = $reserva['fecha'];
    } catch (\PDOException $e) {
        // Si hay un error, redirigir al calendario
        header('Location: /dia');
        exit;
    }
}

// Incluir la cabecera
include 'includes/header.php';
?>

<div class="flex items-center mb-6">
    <a href="<?php echo $isEditMode ? "/reserva?id={$id}" : "/day?date={$fecha}"; ?>" class="mr-4 p-2 rounded-full hover:bg-gray-100">
        <i class="ri-arrow-left-line text-gray-600 text-xl"></i>
    </a>
    <h1 class="text-2xl font-bold text-gray-900">
        <?php echo $isEditMode ? 'Editar Reserva' : 'Nueva Reserva'; ?>
    </h1>
</div>

<!-- Mostrar mensaje de error si existe -->
<?php if ($error): ?>
<div class="mb-6 bg-red-50 border border-red-300 rounded-lg p-4">
    <div class="flex items-center">
        <i class="ri-error-warning-line text-red-500 mr-2"></i>
        <span class="text-red-700"><?php echo htmlspecialchars($error); ?></span>
    </div>
</div>
<?php endif; ?>

<!-- Formulario -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <form id="reservaForm" class="space-y-6" method="post" action="api/<?php echo $isEditMode ? 'actualizar-reserva' : 'crear-reserva'; ?>">
        <?php if ($isEditMode): ?>
            <input type="hidden" name="id" value="<?php echo $reserva['id']; ?>">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Teléfono con búsqueda de clientes -->
            <div class="relative">
                <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">
                    Teléfono
                </label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-phone-line text-gray-400"></i>
                    </div>
                    <input
                        type="tel"
                        name="telefono"
                        id="telefono"
                        required
                        autocomplete="off"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="+34 600 123 456"
                        value="<?php echo $isEditMode ? htmlspecialchars($reserva['telefono']) : (isset($formData['telefono']) ? htmlspecialchars($formData['telefono']) : ''); ?>"
                    >
                    <!-- Indicador de búsqueda -->
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none" id="searchIndicator" style="display: none;">
                        <div class="animate-spin h-4 w-4 border-2 border-blue-500 border-t-transparent rounded-full"></div>
                    </div>
                </div>
                
                <!-- Dropdown de resultados de búsqueda -->
                <div id="clientSearchResults" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto">
                    <!-- Los resultados se cargarán aquí dinámicamente -->
                </div>
                
                <p class="mt-1 text-xs text-gray-500">
                    Ingrese el teléfono para buscar clientes existentes o crear uno nuevo
                </p>
            </div>
            
            <!-- Nombre del cliente -->
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre completo
                </label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-user-line text-gray-400"></i>
                    </div>
                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        required
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Nombre del cliente"
                        value="<?php echo $isEditMode ? htmlspecialchars($reserva['nombre']) : (isset($formData['nombre']) ? htmlspecialchars($formData['nombre']) : ''); ?>"
                    >
                </div>
                <div id="clientInfoBadge" class="mt-1 hidden">
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800">
                        <i class="ri-user-check-line mr-1"></i>
                        Cliente existente
                    </span>
                </div>
            </div>

            <!-- Fecha -->
            <div>
                <label for="fecha" class="block text-sm font-medium text-gray-700 mb-1">
                    Fecha
                </label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-calendar-line text-gray-400"></i>
                    </div>
                    <input
                        type="date"
                        name="fecha"
                        id="fecha"
                        required
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        value="<?php echo $isEditMode ? $reserva['fecha'] : (isset($formData['fecha']) ? $formData['fecha'] : $fecha); ?>"
                    >
                </div>
            </div>
            
            <!-- Hora -->
            <div>
                <label for="hora" class="block text-sm font-medium text-gray-700 mb-1">
                    Hora
                </label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-time-line text-gray-400"></i>
                    </div>
                    <select
                        name="hora"
                        id="hora"
                        required
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">Seleccione una hora</option>
                        <?php
                        // Horarios disponibles
                        $horarios = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30'];
                        
                        foreach ($horarios as $horario):
                            $selected = '';
                            if ($isEditMode && substr($reserva['hora'], 0, 5) === $horario) {
                                $selected = 'selected';
                            } elseif (!$isEditMode && isset($formData['hora']) && $formData['hora'] === $horario) {
                                $selected = 'selected';
                            }
                        ?>
                            <option value="<?php echo $horario; ?>" <?php echo $selected; ?>><?php echo $horario; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Estado -->
            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                    Estado
                </label>
                <select
                    name="estado"
                    id="estado"
                    class="block w-full pl-3 pr-10 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                >
                    <option value="pendiente" <?php echo ($isEditMode && $reserva['estado'] === 'pendiente') || !$isEditMode ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="confirmada" <?php echo $isEditMode && $reserva['estado'] === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                </select>
            </div>
        </div>
        
        <!-- Campo oculto para whatsapp_id normalizado -->
        <input type="hidden" name="whatsapp_id" id="whatsapp_id_hidden" value="">
        
        <!-- Mensaje -->
        <div>
            <label for="mensaje" class="block text-sm font-medium text-gray-700 mb-1">
                Mensaje o notas
            </label>
            <div class="relative rounded-md shadow-sm">
                <div class="absolute top-3 left-3 flex items-start pointer-events-none">
                    <i class="ri-message-2-line text-gray-400"></i>
                </div>
                <textarea
                    name="mensaje"
                    id="mensaje"
                    rows="4"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Notas adicionales para la reserva"
                ><?php echo $isEditMode ? htmlspecialchars($reserva['mensaje']) : (isset($formData['mensaje']) ? htmlspecialchars($formData['mensaje']) : ''); ?></textarea>
            </div>
        </div>
        
        <!-- Botones de acción -->
        <div class="flex justify-end space-x-3">            
            <a href="<?php echo $isEditMode ? "/reserva?id={$id}" : "/day?date={$fecha}"; ?>"
               class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Cancelar
            </a>
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <i class="ri-save-line mr-2"></i>
                <?php echo $isEditMode ? 'Guardar Cambios' : 'Crear Reserva'; ?>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const telefonoInput = document.getElementById('telefono');
    const nombreInput = document.getElementById('nombre');
    const searchResults = document.getElementById('clientSearchResults');
    const searchIndicator = document.getElementById('searchIndicator');
    const clientInfoBadge = document.getElementById('clientInfoBadge');
    const whatsappHidden = document.getElementById('whatsapp_id_hidden');
    
    let searchTimeout;
    let selectedClientData = null;

    // Función para normalizar teléfono al formato WhatsApp
    function normalizePhoneForWhatsApp(phone) {
        if (!phone) return '';
        
        // Remover todos los caracteres que no sean números o el signo +
        let normalized = phone.replace(/[^\d+]/g, '');
        
        // Si empieza con +, mantenerlo
        if (normalized.startsWith('+')) {
            return normalized;
        }
        
        // Si empieza con 34, agregar +
        if (normalized.startsWith('34') && normalized.length >= 11) {
            return '+' + normalized;
        }
        
        // Si empieza con 6, 7, 8 o 9 (números españoles), agregar +34
        if (/^[6789]/.test(normalized) && normalized.length === 9) {
            return '+34' + normalized;
        }
        
        // Si no tiene prefijo y tiene más de 9 dígitos, asumir que tiene código de país
        if (normalized.length > 9) {
            return '+' + normalized;
        }
        
        // Por defecto, asumir España si es un número de 9 dígitos
        if (normalized.length === 9) {
            return '+34' + normalized;
        }
        
        return normalized;
    }

    // Función para buscar clientes
    function searchClients(phoneNumber) {
        if (!phoneNumber || phoneNumber.length < 3) {
            hideSearchResults();
            return;
        }

        showSearchIndicator();

        fetch('api/buscar-clientes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ telefono: phoneNumber })
        })
        .then(response => response.json())
        .then(data => {
            hideSearchIndicator();
            
            if (data.success && data.clientes && data.clientes.length > 0) {
                showSearchResults(data.clientes);
            } else {
                hideSearchResults();
                // Limpiar datos de cliente existente si no se encuentra
                clearSelectedClient();
            }
        })
        .catch(error => {
            console.error('Error buscando clientes:', error);
            hideSearchIndicator();
            hideSearchResults();
        });
    }

    // Mostrar resultados de búsqueda
    function showSearchResults(clientes) {
        let html = '';
        
        clientes.forEach(cliente => {
            html += `
                <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 client-result" 
                     data-client='${JSON.stringify(cliente)}'>
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900">${cliente.nombre}</div>
                            <div class="text-xs text-gray-500">${cliente.telefono}</div>
                            ${cliente.last_reserva ? `<div class="text-xs text-blue-600">Última reserva: ${cliente.last_reserva}</div>` : ''}
                        </div>
                        <div class="flex items-center text-xs text-gray-400">
                            <i class="ri-user-line mr-1"></i>
                            ${cliente.total_reservas || 0} reserva${(cliente.total_reservas || 0) !== 1 ? 's' : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        searchResults.innerHTML = html;
        searchResults.classList.remove('hidden');

        // Agregar event listeners a los resultados
        document.querySelectorAll('.client-result').forEach(result => {
            result.addEventListener('click', function() {
                const clientData = JSON.parse(this.dataset.client);
                selectClient(clientData);
            });
        });
    }

    // Seleccionar cliente
    function selectClient(clientData) {
        selectedClientData = clientData;
        
        // Llenar los campos
        telefonoInput.value = clientData.telefono;
        nombreInput.value = clientData.nombre;
        
        // Normalizar teléfono para WhatsApp
        const normalizedPhone = normalizePhoneForWhatsApp(clientData.telefono);
        whatsappHidden.value = normalizedPhone;
        
        // Mostrar badge de cliente existente
        clientInfoBadge.classList.remove('hidden');
        
        // Ocultar resultados
        hideSearchResults();
    }

    // Limpiar cliente seleccionado
    function clearSelectedClient() {
        selectedClientData = null;
        clientInfoBadge.classList.add('hidden');
    }

    // Ocultar resultados de búsqueda
    function hideSearchResults() {
        searchResults.classList.add('hidden');
        searchResults.innerHTML = '';
    }

    // Mostrar indicador de búsqueda
    function showSearchIndicator() {
        searchIndicator.style.display = 'flex';
    }

    // Ocultar indicador de búsqueda
    function hideSearchIndicator() {
        searchIndicator.style.display = 'none';
    }

    // Event listener para el input de teléfono
    telefonoInput.addEventListener('input', function() {
        const phoneNumber = this.value.trim();
        
        // Normalizar teléfono para WhatsApp en tiempo real
        const normalizedPhone = normalizePhoneForWhatsApp(phoneNumber);
        whatsappHidden.value = normalizedPhone;
        
        // Limpiar timeout anterior
        clearTimeout(searchTimeout);
        
        // Si el campo está vacío, limpiar todo
        if (!phoneNumber) {
            hideSearchResults();
            hideSearchIndicator();
            clearSelectedClient();
            return;
        }
        
        // Búsqueda con debounce de 300ms
        searchTimeout = setTimeout(() => {
            searchClients(phoneNumber);
        }, 300);
    });

    // Event listener para cambios manuales en el nombre
    nombreInput.addEventListener('input', function() {
        // Si el usuario cambia el nombre manualmente, limpiar el cliente seleccionado
        if (selectedClientData && this.value !== selectedClientData.nombre) {
            clearSelectedClient();
        }
    });

    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!telefonoInput.contains(e.target) && !searchResults.contains(e.target)) {
            hideSearchResults();
        }
    });

    // Prevenir que el formulario se cierre los resultados
    searchResults.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Manejar navegación con teclado en los resultados
    telefonoInput.addEventListener('keydown', function(e) {
        const results = document.querySelectorAll('.client-result');
        
        if (results.length === 0) return;
        
        let currentIndex = -1;
        results.forEach((result, index) => {
            if (result.classList.contains('bg-blue-50')) {
                currentIndex = index;
            }
        });

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (currentIndex < results.length - 1) {
                if (currentIndex >= 0) {
                    results[currentIndex].classList.remove('bg-blue-50');
                }
                results[currentIndex + 1].classList.add('bg-blue-50');
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (currentIndex > 0) {
                results[currentIndex].classList.remove('bg-blue-50');
                results[currentIndex - 1].classList.add('bg-blue-50');
            }
        } else if (e.key === 'Enter' && currentIndex >= 0) {
            e.preventDefault();
            const clientData = JSON.parse(results[currentIndex].dataset.client);
            selectClient(clientData);
        } else if (e.key === 'Escape') {
            hideSearchResults();
        }
    });

    // Si estamos en modo edición, normalizar el teléfono existente
    if (telefonoInput.value) {
        const normalizedPhone = normalizePhoneForWhatsApp(telefonoInput.value);
        whatsappHidden.value = normalizedPhone;
    }
});
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>