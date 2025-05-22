<?php
// Incluir configuración y funciones
require_once 'includes/db-config.php';
require_once 'includes/functions.php';
require_once 'includes/whatsapp-functions.php';

// Configurar la página actual
$currentPage = 'autorespuestas';
$pageTitle = 'ReservaBot - Respuestas Automáticas';
$pageScript = 'autorespuestas';

// Obtener todas las respuestas automáticas
try {
    $stmt = $pdo->query('SELECT * FROM respuestas_automaticas ORDER BY created_at DESC');
    $respuestas = $stmt->fetchAll();
} catch (\PDOException $e) {
    $respuestas = [];
}

// Incluir la cabecera
include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Respuestas Automáticas</h1>
    
    <button id="nuevaRespuestaBtn" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <i class="ri-add-line mr-2"></i>
        Nueva Respuesta
    </button>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="flex items-start mb-4">
        <div class="flex-shrink-0 mt-1">
            <i class="ri-information-line text-blue-500"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-gray-900">Sobre las respuestas automáticas</h3>
            <p class="text-sm text-gray-500 mt-1">
                Configure mensajes que se enviarán automáticamente cuando un cliente envíe un mensaje que contenga determinadas palabras clave.
                <br>
                Las respuestas automáticas pueden incluir variables como {nombre}, {fecha} y {hora}.
            </p>
        </div>
    </div>
</div>

<!-- Lista de respuestas automáticas -->
<div class="space-y-4 mb-6" id="respuestasList">
    <?php if (empty($respuestas)): ?>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <i class="ri-chat-off-line text-gray-400 text-4xl"></i>
            <p class="mt-2 text-gray-500">No hay respuestas automáticas configuradas</p>
            <button id="emptyAddBtn" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="ri-add-line mr-2"></i>
                Crear primera respuesta
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($respuestas as $respuesta): ?>
            <div class="bg-white rounded-lg shadow-sm border-l-4 <?php echo $respuesta['is_active'] ? 'border-green-500' : 'border-gray-300'; ?>" data-id="<?php echo $respuesta['id']; ?>">
                <div class="p-4">
                    <div class="flex justify-between">
                        <div>
                            <div class="flex items-center">
                                <h3 class="text-base font-medium text-gray-900">
                                    <span class="font-mono bg-gray-100 px-2 py-0.5 rounded text-sm"><?php echo htmlspecialchars($respuesta['trigger_text']); ?></span>
                                </h3>
                                <?php if (!$respuesta['is_active']): ?>
                                    <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Desactivada
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-2 text-sm text-gray-600">
                                <?php echo nl2br(htmlspecialchars($respuesta['response_text'])); ?>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-2">
                            <button class="inline-flex items-center p-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 btn-editar" data-id="<?php echo $respuesta['id']; ?>">
                                <i class="ri-edit-line"></i>
                            </button>
                            
                            <button class="inline-flex items-center p-2 border <?php echo $respuesta['is_active'] ? 'border-amber-300 text-amber-700' : 'border-green-300 text-green-700'; ?> rounded-md text-sm bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 btn-toggle" data-id="<?php echo $respuesta['id']; ?>" data-active="<?php echo $respuesta['is_active'] ? '1' : '0'; ?>">
                                <i class="<?php echo $respuesta['is_active'] ? 'ri-pause-line' : 'ri-play-line'; ?>"></i>
                            </button>
                            
                            <button class="inline-flex items-center p-2 border border-red-300 rounded-md text-sm text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 btn-eliminar" data-id="<?php echo $respuesta['id']; ?>">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal para crear/editar respuesta automática -->
<div id="respuestaModal" class="fixed inset-0 z-10 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">
                        Nueva Respuesta Automática
                    </h3>
                </div>
                
                <form id="respuestaForm">
                    <input type="hidden" id="respuestaId" value="">
                    
                    <div class="space-y-4">
                        <div>
                            <label for="triggerText" class="block text-sm font-medium text-gray-700 mb-1">
                                Palabra o frase clave
                            </label>
                            <input
                                type="text"
                                id="triggerText"
                                name="trigger_text"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="Ejemplo: reserva, horario, ubicación"
                            >
                            <p class="mt-1 text-xs text-gray-500">
                                Se activará cuando el mensaje contenga esta palabra o frase
                            </p>
                        </div>
                        
                        <div>
                            <label for="responseText" class="block text-sm font-medium text-gray-700 mb-1">
                                Respuesta automática
                            </label>
                            <textarea
                                id="responseText"
                                name="response_text"
                                required
                                rows="4"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="Mensaje que se enviará automáticamente"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                Variables disponibles: {nombre}, {fecha}, {hora}
                            </p>
                        </div>
                        
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                id="isActive"
                                name="is_active"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                checked
                            >
                            <label for="isActive" class="ml-2 block text-sm text-gray-700">
                                Activar esta respuesta automática
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="saveRespuestaBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Guardar
                </button>
                <button type="button" id="cancelModalBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmar eliminación -->
<div id="deleteConfirmModal" class="fixed inset-0 z-10 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="ri-delete-bin-line text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Eliminar Respuesta Automática
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                ¿Estás seguro de que deseas eliminar esta respuesta automática? Esta acción no se puede deshacer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="confirmDeleteBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Eliminar
                </button>
                <button type="button" id="cancelDeleteBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
                </div>
        </div>
    </div>
</div>

<!-- Mensajes de estado -->
<div id="successMessage" class="fixed bottom-4 right-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md shadow-lg hidden">
    <div class="flex items-center">
        <i class="ri-check-line mr-2 text-green-500"></i>
        <span id="successText">Operación completada con éxito</span>
    </div>
</div>

<div id="errorMessage" class="fixed bottom-4 right-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md shadow-lg hidden">
    <div class="flex items-center">
        <i class="ri-error-warning-line mr-2 text-red-500"></i>
        <span id="errorText">Error al realizar la operación</span>
    </div>
</div>

<?php 
// Incluir el pie de página
include 'includes/footer.php'; 
?>