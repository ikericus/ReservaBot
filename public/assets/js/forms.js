document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Clipboard.js para botones de copiar
    if (typeof ClipboardJS !== 'undefined') {
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
    }
    
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
    let preguntaCounter = document.querySelectorAll('.pregunta-item').length;
    
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