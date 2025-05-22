document.addEventListener('DOMContentLoaded', function() {
    // Elementos UI
    const autorespuestasTable = document.getElementById('autorespuestasTable');
    const autorespuestasForm = document.getElementById('autorespuestasForm');
    const addResponseBtn = document.getElementById('addResponseBtn');
    const modalTitle = document.getElementById('modalTitle');
    const responseForm = document.getElementById('responseForm');
    const responseModal = document.getElementById('responseModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    
    // Variables globales
    let currentEditId = null;
    
    // Event listeners
    if (addResponseBtn) {
        addResponseBtn.addEventListener('click', () => showModal());
    }
    
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', hideModal);
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', hideModal);
    }
    
    if (responseForm) {
        responseForm.addEventListener('submit', handleFormSubmit);
    }
    
    if (autorespuestasTable) {
        autorespuestasTable.addEventListener('click', handleTableClick);
    }
    
    // Funciones
    function showModal(id = null, data = null) {
        modalTitle.textContent = id ? 'Editar respuesta automática' : 'Añadir respuesta automática';
        currentEditId = id;
        
        if (data) {
            document.getElementById('keyword').value = data.keyword || '';
            document.getElementById('response').value = data.response || '';
            document.getElementById('isActive').checked = data.is_active === '1';
            document.getElementById('isRegex').checked = data.is_regex === '1';
        } else {
            responseForm.reset();
            document.getElementById('isActive').checked = true;
            document.getElementById('isRegex').checked = false;
        }
        
        responseModal.classList.remove('hidden');
    }
    
    function hideModal() {
        responseModal.classList.add('hidden');
        responseForm.reset();
        currentEditId = null;
    }
    
    function handleFormSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(responseForm);
        
        if (currentEditId) {
            formData.append('id', currentEditId);
            formData.append('action', 'update');
        } else {
            formData.append('action', 'create');
        }
        
        fetch('api/autorespuestas.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    hideModal();
                    showSuccessMessage(data.message || 'Respuesta guardada correctamente');
                    
                    // Recargar la tabla
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    showErrorMessage(data.message || 'Error al guardar la respuesta');
                }
            })
            .catch(error => {
                console.error('Error al enviar formulario:', error);
                showErrorMessage('Error al guardar la respuesta');
            });
    }
    
    function handleTableClick(e) {
        const target = e.target;
        
        // Editar respuesta
        if (target.classList.contains('edit-btn') || 
            target.closest('.edit-btn')) {
            const btn = target.classList.contains('edit-btn') ? target : target.closest('.edit-btn');
            const id = btn.dataset.id;
            getResponseData(id);
        }
        
        // Eliminar respuesta
        if (target.classList.contains('delete-btn') || 
            target.closest('.delete-btn')) {
            const btn = target.classList.contains('delete-btn') ? target : target.closest('.delete-btn');
            const id = btn.dataset.id;
            confirmDelete(id);
        }
        
        // Toggle estado activo
        if (target.classList.contains('toggle-active')) {
            const checkbox = target;
            const id = checkbox.dataset.id;
            const isActive = checkbox.checked;
            
            updateResponseStatus(id, isActive);
        }
    }
    
    function getResponseData(id) {
        fetch(`api/autorespuestas.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.response) {
                    showModal(id, data.response);
                } else {
                    showErrorMessage(data.message || 'Error al obtener datos de la respuesta');
                }
            })
            .catch(error => {
                console.error('Error al obtener datos:', error);
                showErrorMessage('Error al obtener datos de la respuesta');
            });
    }
    
    function confirmDelete(id) {
        if (confirm('¿Está seguro de que desea eliminar esta respuesta automática?')) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', 'delete');
            
            fetch('api/autorespuestas.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessMessage(data.message || 'Respuesta eliminada correctamente');
                        
                        // Eliminar la fila de la tabla
                        const row = document.querySelector(`tr[data-id="${id}"]`);
                        if (row) {
                            row.remove();
                        } else {
                            // Si no se encuentra la fila, recargar la página
                            setTimeout(() => {
                                window.location.reload();
                            }, 500);
                        }
                    } else {
                        showErrorMessage(data.message || 'Error al eliminar la respuesta');
                    }
                })
                .catch(error => {
                    console.error('Error al eliminar:', error);
                    showErrorMessage('Error al eliminar la respuesta');
                });
        }
    }
    
    function updateResponseStatus(id, isActive) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('action', 'update_status');
        formData.append('is_active', isActive ? 1 : 0);
        
        fetch('api/autorespuestas.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(data.message || 'Estado actualizado correctamente');
                } else {
                    showErrorMessage(data.message || 'Error al actualizar el estado');
                    // Revertir el cambio del checkbox
                    const checkbox = document.querySelector(`.toggle-active[data-id="${id}"]`);
                    if (checkbox) {
                        checkbox.checked = !isActive;
                    }
                }
            })
            .catch(error => {
                console.error('Error al actualizar estado:', error);
                showErrorMessage('Error al actualizar el estado');
                // Revertir el cambio del checkbox
                const checkbox = document.querySelector(`.toggle-active[data-id="${id}"]`);
                if (checkbox) {
                    checkbox.checked = !isActive;
                }
            });
    }
    
    function showSuccessMessage(message) {
        const successMessage = document.getElementById('successMessage');
        const successText = document.getElementById('successText');
        
        if (successMessage && successText) {
            successText.textContent = message;
            successMessage.classList.remove('hidden');
            
            setTimeout(() => {
                successMessage.classList.add('hidden');
            }, 3000);
        }
    }
    
    function showErrorMessage(message) {
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        
        if (errorMessage && errorText) {
            errorText.textContent = message;
            errorMessage.classList.remove('hidden');
            
            setTimeout(() => {
                errorMessage.classList.add('hidden');
            }, 3000);
        }
    }
});