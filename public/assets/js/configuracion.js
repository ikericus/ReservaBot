// assets/js/configuracion.js

document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================================================
    // TABS NAVIGATION
    // ========================================================================
    
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tab = button.dataset.tab;
            
            // Remove active from all
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active to clicked
            button.classList.add('active');
            document.querySelector(`[data-tab-content="${tab}"]`).classList.add('active');
        });
    });
    
    // ========================================================================
    // COLOR PICKERS
    // ========================================================================
    
    function setupColorPicker(colorId, previewId, textId) {
        const colorInput = document.getElementById(colorId);
        const preview = document.getElementById(previewId);
        const textInput = document.getElementById(textId);
        
        if (!colorInput || !preview || !textInput) return;
        
        // Sync color picker with text input
        colorInput.addEventListener('input', (e) => {
            const color = e.target.value;
            preview.style.backgroundColor = color;
            textInput.value = color;
            updateGradientPreview();
        });
        
        // Sync text input with color picker
        textInput.addEventListener('input', (e) => {
            const color = e.target.value;
            if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                colorInput.value = color;
                preview.style.backgroundColor = color;
                updateGradientPreview();
            }
        });
    }
    
    setupColorPicker('color_primario', 'previewPrimario', 'color_primario_text');
    setupColorPicker('color_secundario', 'previewSecundario', 'color_secundario_text');
    
    function updateGradientPreview() {
        const primario = document.getElementById('color_primario').value;
        const secundario = document.getElementById('color_secundario').value;
        const gradientPreview = document.getElementById('gradientPreview');
        
        if (gradientPreview) {
            gradientPreview.style.background = `linear-gradient(135deg, ${primario} 0%, ${secundario} 100%)`;
        }
    }
    
    // ========================================================================
    // IMAGE UPLOAD
    // ========================================================================
    
    const imagenInput = document.getElementById('imagen_negocio');
    const imagePreview = document.getElementById('imagePreview');
    const imagenUrlInput = document.getElementById('imagen_negocio_url');
    
    if (imagenInput && imagePreview) {
        imagenInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    imagePreview.innerHTML = `<img src="${event.target.result}" alt="Logo">`;
                    imagePreview.classList.remove('empty');
                    // Aquí podrías implementar upload a servidor
                    imagenUrlInput.value = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // ========================================================================
    // TIPOS DE DÍA
    // ========================================================================
    
    let tipoCounter = 0;
    
    // Botón añadir nuevo tipo de día
    const btnAddTipoDia = document.getElementById('btnAddTipoDia');
    if (btnAddTipoDia) {
        btnAddTipoDia.addEventListener('click', () => {
            addTipoDia();
        });
    }
    
    function addTipoDia() {
        const template = document.getElementById('tipoDiaTemplate');
        const container = document.getElementById('tiposDiaContainer');
        const clone = template.content.cloneNode(true);
        
        // Asignar ID único
        tipoCounter++;
        const tipoId = 'tipo_' + Date.now();
        const card = clone.querySelector('.tipo-dia-card');
        card.dataset.tipoId = tipoId;
        
        // Setup event listeners
        setupTipoDiaCard(clone);
        
        container.appendChild(clone);
    }
    
    function setupTipoDiaCard(element) {
        // Botón eliminar tipo
        const btnDeleteTipo = element.querySelector('.btn-delete-tipo');
        if (btnDeleteTipo) {
            btnDeleteTipo.addEventListener('click', function() {
                if (confirm('¿Eliminar este tipo de horario?')) {
                    this.closest('.tipo-dia-card').remove();
                }
            });
        }
        
        // Botón añadir ventana
        const btnAddVentana = element.querySelector('.btn-add-ventana');
        if (btnAddVentana) {
            btnAddVentana.addEventListener('click', function() {
                addVentanaHoraria(this.closest('.tipo-dia-card'));
            });
        }
        
        // Botones eliminar ventanas existentes
        element.querySelectorAll('.btn-delete-ventana').forEach(btn => {
            btn.addEventListener('click', function() {
                deleteVentanaHoraria(this);
            });
        });
    }
    
    function addVentanaHoraria(card) {
        const template = document.getElementById('ventanaHorariaTemplate');
        const container = card.querySelector('.ventanas-container');
        const clone = template.content.cloneNode(true);
        
        // Remover mensaje de "sin horarios"
        const emptyMessage = container.querySelector('p.italic');
        if (emptyMessage) {
            emptyMessage.remove();
        }
        
        // Setup delete button
        const btnDelete = clone.querySelector('.btn-delete-ventana');
        btnDelete.addEventListener('click', function() {
            deleteVentanaHoraria(this);
        });
        
        container.appendChild(clone);
    }
    
    function deleteVentanaHoraria(btn) {
        const ventana = btn.closest('.ventana-horaria');
        const container = ventana.closest('.ventanas-container');
        
        ventana.remove();
        
        // Si no quedan ventanas, mostrar mensaje
        if (container.querySelectorAll('.ventana-horaria').length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500 italic">Sin horarios definidos (día cerrado)</p>';
        }
    }
    
    // Setup inicial de cards existentes
    document.querySelectorAll('.tipo-dia-card').forEach(card => {
        setupTipoDiaCard(card);
    });
    
    // ========================================================================
    // CARGAR FESTIVOS
    // ========================================================================
    
    const btnCargarFestivos = document.getElementById('btnCargarFestivos');
    if (btnCargarFestivos) {
        btnCargarFestivos.addEventListener('click', async () => {
            if (!confirm('¿Cargar festivos nacionales de España? Esto marcará los días como "Cerrado"')) {
                return;
            }
            
            try {
                const year = new Date().getFullYear();
                const festivos = getFestivosEspana(year);
                
                alert(`Se cargarán ${festivos.length} festivos nacionales.\\n\\nNota: Aún no está implementado el calendario interactivo.`);
                
                // TODO: Implementar cuando tengamos el calendario interactivo
                
            } catch (error) {
                alert('Error al cargar festivos: ' + error.message);
            }
        });
    }
    
    function getFestivosEspana(year) {
        // Festivos nacionales fijos
        const festivos = [
            { fecha: `${year}-01-01`, nombre: 'Año Nuevo' },
            { fecha: `${year}-01-06`, nombre: 'Reyes Magos' },
            { fecha: `${year}-05-01`, nombre: 'Día del Trabajo' },
            { fecha: `${year}-08-15`, nombre: 'Asunción de la Virgen' },
            { fecha: `${year}-10-12`, nombre: 'Fiesta Nacional de España' },
            { fecha: `${year}-11-01`, nombre: 'Todos los Santos' },
            { fecha: `${year}-12-06`, nombre: 'Día de la Constitución' },
            { fecha: `${year}-12-08`, nombre: 'Inmaculada Concepción' },
            { fecha: `${year}-12-25`, nombre: 'Navidad' }
        ];
        
        // Calcular festivos móviles (Semana Santa)
        const easter = calculateEaster(year);
        const juevesSanto = new Date(easter);
        juevesSanto.setDate(juevesSanto.getDate() - 3);
        const viernesSanto = new Date(easter);
        viernesSanto.setDate(viernesSanto.getDate() - 2);
        
        festivos.push({
            fecha: formatDate(juevesSanto),
            nombre: 'Jueves Santo'
        });
        festivos.push({
            fecha: formatDate(viernesSanto),
            nombre: 'Viernes Santo'
        });
        
        return festivos;
    }
    
    function calculateEaster(year) {
        // Algoritmo de Meeus/Jones/Butcher
        const a = year % 19;
        const b = Math.floor(year / 100);
        const c = year % 100;
        const d = Math.floor(b / 4);
        const e = b % 4;
        const f = Math.floor((b + 8) / 25);
        const g = Math.floor((b - f + 1) / 3);
        const h = (19 * a + b - d - g + 15) % 30;
        const i = Math.floor(c / 4);
        const k = c % 4;
        const l = (32 + 2 * e + 2 * i - h - k) % 7;
        const m = Math.floor((a + 11 * h + 22 * l) / 451);
        const month = Math.floor((h + l - 7 * m + 114) / 31);
        const day = ((h + l - 7 * m + 114) % 31) + 1;
        
        return new Date(year, month - 1, day);
    }
    
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // ========================================================================
    // FORM SUBMISSION
    // ========================================================================
    
    const configForm = document.getElementById('configForm');
    if (configForm) {
        configForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const data = {};
            
            // TAB 1: Información básica
            data['empresa_nombre'] = document.getElementById('nombre_negocio')?.value || '';
            data['empresa_imagen'] = document.getElementById('imagen_negocio_url')?.value || '';
            data['color_primario'] = document.getElementById('color_primario')?.value || '#667eea';
            data['color_secundario'] = document.getElementById('color_secundario')?.value || '#764ba2';
            data['empresa_telefono'] = document.getElementById('telefono_negocio')?.value || '';
            data['empresa_direccion'] = document.getElementById('direccion_negocio')?.value || '';
            data['empresa_web'] = document.getElementById('web_negocio')?.value || '';
            
            // TAB 2: Reservas
            data['duracion_reserva'] = document.getElementById('duracion_reserva')?.value || '30';
            data['intervalo_reservas'] = document.getElementById('intervalo_reservas')?.value || '30';
            
            // TAB 3: Tipos de día
            const tiposDia = {};
            document.querySelectorAll('.tipo-dia-card').forEach(card => {
                const tipoId = card.dataset.tipoId;
                const nombre = card.querySelector('.tipo-dia-nombre').value;
                const ventanas = [];
                
                card.querySelectorAll('.ventana-horaria').forEach(ventana => {
                    const inicio = ventana.querySelector('.ventana-inicio').value;
                    const fin = ventana.querySelector('.ventana-fin').value;
                    const capacidad = parseInt(ventana.querySelector('.ventana-capacidad').value) || 1;
                    
                    if (inicio && fin) {
                        ventanas.push({ inicio, fin, capacidad });
                    }
                });
                
                tiposDia[tipoId] = {
                    nombre: nombre,
                    ventanas: ventanas
                };
            });
            
            data['tipos_dia'] = JSON.stringify(tiposDia);
            
            // TAB 4: Mapeo semana
            const mapeoSemana = {};
            ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'].forEach(dia => {
                const select = document.querySelector(`[name="mapeo_semana_${dia}"]`);
                if (select) {
                    mapeoSemana[dia] = select.value;
                }
            });
            data['mapeo_semana'] = JSON.stringify(mapeoSemana);
            
            // TODO: Calendario (cuando se implemente)
            // data['calendario'] = JSON.stringify({});
            
            try {
                const response = await fetch('/api/actualizar-configuracion', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessMessage();
                } else {
                    alert('Error al guardar: ' + (result.message || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al guardar la configuración');
            }
        });
    }
    
    function showSuccessMessage() {
        const message = document.getElementById('saveSuccessMessage');
        if (message) {
            message.classList.remove('hidden');
            setTimeout(() => {
                message.classList.add('hidden');
            }, 3000);
        }
    }
});