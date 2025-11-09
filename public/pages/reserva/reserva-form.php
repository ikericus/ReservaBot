<?php
// pages/reserva/reserva-form.php

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

// Obtener usuario autenticado
$currentUser = getAuthenticatedUser();
$usuarioId = $currentUser['id'];

// Obtener configuración de reservas desde ConfiguracionDomain
$intervaloReservas = 30; // Default
$duracionReservas = 60; // Default

$configuracionDomain = getContainer()->getConfiguracionDomain();
$reservaDomain = getContainer()->getReservaDomain();

try {
    $intervaloReservas = $configuracionDomain->obtenerIntervaloReservas($usuarioId);
    $duracionReservas = $configuracionDomain->obtenerDuracionReserva($usuarioId);
} catch (Exception $e) {
    error_log("Error obteniendo configuración de reservas: " . $e->getMessage());
}

// Variables iniciales
$fecha = null;
$telefonoUrl = '';
$nombreUrl = '';
$horasOcupadas = [];

// Obtener la reserva si estamos en modo edición
$reserva = null;
if ($isEditMode) {
    try {
        $reservaObj = $reservaDomain->obtenerReserva($id, $usuarioId);
        
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
            header('Location: /dia');
            exit;
        }
        
        // Usar la fecha de la reserva en modo edición
        $fecha = $reserva['fecha'];
        
        // Obtener horas ocupadas del día (excluyendo esta reserva y considerando duración)
        try {
            $fechaObj = new DateTime($reserva['fecha']);
            $reservasDelDia = $reservaDomain->obtenerReservasPorFecha($fechaObj, $usuarioId);
            
            // Calcular todas las franjas bloqueadas por cada reserva (considerando duración)
            foreach ($reservasDelDia as $r) {
                // Excluir la reserva actual
                if ($r->getId() !== $reserva['id'] && $r->getEstado()->esActiva()) {
                    // Calcular TODAS las franjas bloqueadas por esta reserva
                    $franjasBloqueadas = $configuracionDomain->calcularFranjasBloqueadas(
                        $r->getHora(),
                        $usuarioId
                    );
                    
                    // Agregar todas las franjas al array
                    foreach ($franjasBloqueadas as $franja) {
                        if (!in_array($franja, $horasOcupadas)) {
                            $horasOcupadas[] = $franja;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error obteniendo reservas del día en edición: " . $e->getMessage());
        }
        
    } catch (\Exception $e) {
        error_log("Error obteniendo reserva: " . $e->getMessage());
        header('Location: /dia');
        exit;
    }
} else {
    // Modo creación
    $fecha = isset($formData['fecha']) ? $formData['fecha'] : date('Y-m-d');
    
    // Obtener parámetros de la URL para prellenar el formulario
    $telefonoUrl = isset($_GET['telefono']) ? trim($_GET['telefono']) : '';
    $nombreUrl = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';
    
    // Obtener horas ocupadas del día actual (considerando duración de reservas)
    try {
        $fechaObj = new DateTime($fecha);
        $reservasDelDia = $reservaDomain->obtenerReservasPorFecha($fechaObj, $usuarioId);
        
        // Calcular todas las franjas bloqueadas por cada reserva
        foreach ($reservasDelDia as $reserva) {
            if ($reserva->getEstado()->esActiva()) {
                // Calcular todas las franjas bloqueadas
                $franjasBloqueadas = $configuracionDomain->calcularFranjasBloqueadas(
                    $reserva->getHora(),
                    $usuarioId
                );
                
                foreach ($franjasBloqueadas as $franja) {
                    if (!in_array($franja, $horasOcupadas)) {
                        $horasOcupadas[] = $franja;
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error obteniendo reservas del día: " . $e->getMessage());
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

<!-- Contenedor de errores dinámicos -->
<div id="dynamicErrors" class="mb-6 bg-red-50 border border-red-300 rounded-lg p-4 hidden">
    <div class="flex items-start">
        <i class="ri-error-warning-line text-red-500 mr-2 mt-0.5"></i>
        <div class="flex-1">
            <ul id="errorList" class="text-red-700 text-sm space-y-1 list-disc list-inside">
            </ul>
        </div>
    </div>
</div>

<!-- Formulario -->
<div class="bg-white rounded-lg shadow-sm p-6">
    <form id="reservaForm" class="space-y-6">
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
                        value="<?php echo $isEditMode ? htmlspecialchars($reserva['telefono']) : (isset($formData['telefono']) ? htmlspecialchars($formData['telefono']) : htmlspecialchars($telefonoUrl)); ?>"
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
                        value="<?php echo $isEditMode ? htmlspecialchars($reserva['nombre']) : (isset($formData['nombre']) ? htmlspecialchars($formData['nombre']) : htmlspecialchars($nombreUrl)); ?>"
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
                            // Generar todas las horas del día según el intervalo configurado
                            $minutosIntervalo = $intervaloReservas;
                            
                            for ($hora = 0; $hora < 24; $hora++) {
                                for ($minuto = 0; $minuto < 60; $minuto += $minutosIntervalo) {
                                    $horaFormateada = sprintf('%02d:%02d', $hora, $minuto);
                                    
                                    // Verificar si está seleccionada
                                    $selected = '';
                                    if ($isEditMode && isset($reserva['hora']) && substr($reserva['hora'], 0, 5) === $horaFormateada) {
                                        $selected = 'selected';
                                    } elseif (!$isEditMode && isset($formData['hora']) && $formData['hora'] === $horaFormateada) {
                                        $selected = 'selected';
                                    }
                                    
                                    // Verificar si está ocupada
                                    $ocupada = in_array($horaFormateada, $horasOcupadas);
                                    $disabled = $ocupada ? 'disabled' : '';
                                    $label = $ocupada ? "{$horaFormateada} (Ocupada)" : $horaFormateada;
                                    $style = $ocupada ? 'style="color: #9ca3af; background-color: #f3f4f6;"' : '';
                                    
                                    echo "<option value=\"{$horaFormateada}\" {$selected} {$disabled} {$style}>{$label}</option>";
                                }
                            }
                            ?>
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
    const form = document.getElementById('reservaForm');
    const telefonoInput = document.getElementById('telefono');
    const nombreInput = document.getElementById('nombre');
    const searchResults = document.getElementById('clientSearchResults');
    const searchIndicator = document.getElementById('searchIndicator');
    const clientInfoBadge = document.getElementById('clientInfoBadge');
    const whatsappHidden = document.getElementById('whatsapp_id_hidden');
    const dynamicErrors = document.getElementById('dynamicErrors');
    const errorList = document.getElementById('errorList');
    const submitButton = form.querySelector('button[type="submit"]');
    
    let searchTimeout;
    let selectedClientData = null;
    const isEditMode = <?php echo $isEditMode ? 'true' : 'false'; ?>;

    const fechaInput = document.getElementById('fecha');
    const horaSelect = document.getElementById('hora');
    const intervaloReservas = <?php echo $intervaloReservas; ?>;
    const duracionReservas = <?php echo $duracionReservas; ?>; 

    // Actualizar horas disponibles cuando cambia la fecha
    if (fechaInput) {
        fechaInput.addEventListener('change', function() {
            const fecha = this.value;
            if (!fecha) return;
            
            // Mostrar loading
            const horaActual = horaSelect.value;
            horaSelect.disabled = true;
            horaSelect.innerHTML = '<option value="">Cargando horas...</option>';
            
            // Obtener horas ocupadas para la nueva fecha
            fetch('/api/horas-disponibles', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    fecha: fecha,
                    usuario_id: <?php echo $usuarioId; ?>,
                    admin_mode: true,
                    excluir_id: <?php echo $isEditMode && isset($reserva['id']) ? $reserva['id'] : 'null'; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.admin_mode) {
                    const horasOcupadas = data.horas_ocupadas || [];
                    
                    // Regenerar opciones usando el intervalo configurado
                    horaSelect.innerHTML = '<option value="">Seleccione una hora</option>';
                    
                    for (let h = 0; h < 24; h++) {
                        for (let m = 0; m < 60; m += intervaloReservas) {
                            const horaFormateada = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                            const ocupada = horasOcupadas.includes(horaFormateada);
                            
                            const option = document.createElement('option');
                            option.value = horaFormateada;
                            option.textContent = ocupada ? `${horaFormateada} (Ocupada)` : horaFormateada;
                            option.disabled = ocupada;
                            
                            if (ocupada) {
                                option.style.color = '#9ca3af';
                                option.style.backgroundColor = '#f3f4f6';
                            }
                            
                            if (horaFormateada === horaActual) {
                                option.selected = true;
                            }
                            
                            horaSelect.appendChild(option);
                        }
                    }
                }
                horaSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                horaSelect.innerHTML = '<option value="">Error al cargar horas</option>';
                horaSelect.disabled = false;
            });
        });
    }

    // Función para mostrar errores
    function showErrors(errors) {
        errorList.innerHTML = '';
        
        if (Array.isArray(errors)) {
            errors.forEach(error => {
                const li = document.createElement('li');
                li.textContent = error;
                errorList.appendChild(li);
            });
        } else if (typeof errors === 'string') {
            const li = document.createElement('li');
            li.textContent = errors;
            errorList.appendChild(li);
        }
        
        dynamicErrors.classList.remove('hidden');
        
        // Scroll al contenedor de errores
        dynamicErrors.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Función para ocultar errores
    function hideErrors() {
        dynamicErrors.classList.add('hidden');
        errorList.innerHTML = '';
    }

    // Función para deshabilitar el botón de submit
    function disableSubmitButton() {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="ri-loader-4-line mr-2 animate-spin"></i>Guardando...';
        submitButton.classList.add('opacity-75', 'cursor-not-allowed');
    }

    // Función para habilitar el botón de submit
    function enableSubmitButton() {
        submitButton.disabled = false;
        submitButton.innerHTML = `<i class="ri-save-line mr-2"></i>${isEditMode ? 'Guardar Cambios' : 'Crear Reserva'}`;
        submitButton.classList.remove('opacity-75', 'cursor-not-allowed');
    }

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

        fetch('/api/clientes-buscar', {
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
                            <div class="text-sm font-medium text-gray-900">${cliente.ultimo_nombre}</div>
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
        
        telefonoInput.value = clientData.telefono;
        nombreInput.value = clientData.ultimo_nombre;
        
        const normalizedPhone = normalizePhoneForWhatsApp(clientData.telefono);
        whatsappHidden.value = normalizedPhone;
        
        clientInfoBadge.classList.remove('hidden');
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
        
        const normalizedPhone = normalizePhoneForWhatsApp(phoneNumber);
        whatsappHidden.value = normalizedPhone;
        
        clearTimeout(searchTimeout);
        
        if (!phoneNumber) {
            hideSearchResults();
            hideSearchIndicator();
            clearSelectedClient();
            return;
        }
        
        searchTimeout = setTimeout(() => {
            searchClients(phoneNumber);
        }, 300);
    });

    // Event listener para cambios manuales en el nombre
    nombreInput.addEventListener('input', function() {
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

    // Prevenir que el formulario cierre los resultados
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

    // Si venimos de la URL con parámetros, normalizar el teléfono y disparar búsqueda
    if (!isEditMode && telefonoInput.value) {
        const normalizedPhone = normalizePhoneForWhatsApp(telefonoInput.value);
        whatsappHidden.value = normalizedPhone;
        
        // Disparar búsqueda automática para verificar si el cliente existe
        searchClients(telefonoInput.value);
    }

    // Manejar el envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Ocultar errores previos
        hideErrors();
        
        // Deshabilitar botón
        disableSubmitButton();
        
        // Obtener datos del formulario
        const formData = new FormData(form);
        
        // Convertir FormData a objeto
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        // Determinar la URL según el modo
        const url = isEditMode ? '/api/reserva-actualizar' : '/api/reserva-crear';
        
        // Realizar la petición
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirigir a la página de detalle de la reserva
                window.location.href = `/reserva?id=${data.id}`;
            } else {
                // Mostrar errores
                enableSubmitButton();
                
                if (data.errors) {
                    showErrors(data.errors);
                } else if (data.message) {
                    showErrors([data.message]);
                } else {
                    showErrors(['Ha ocurrido un error al procesar la reserva']);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            enableSubmitButton();
            showErrors(['Error de conexión. Por favor, inténtalo de nuevo.']);
        });
    });
});
</script>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>