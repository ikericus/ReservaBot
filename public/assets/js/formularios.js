/**
 * JavaScript completo para la página de formularios
 * Maneja la generación de códigos QR, clipboard, eliminación y todas las funcionalidades
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Formularios.js cargado');
    
    // Verificar que las librerías estén disponibles
    checkLibraries();
    
    // Inicializar todas las funcionalidades
    initClipboard();
    initQRFunctionality();
    initDeleteFunctionality();
    initShareFunctionality();
    initFormValidation();
    initMobileEnhancements();
    
    // Sincronizar color pickers
    document.getElementById('color_primario').addEventListener('input', function() {
        document.getElementById('color_primario_text').value = this.value;
    });

    document.getElementById('color_secundario').addEventListener('input', function() {
        document.getElementById('color_secundario_text').value = this.value;
    });
});

/**
 * Verificar y cargar librerías necesarias
 */
function checkLibraries() {
    console.log('ClipboardJS:', typeof ClipboardJS !== 'undefined' ? 'CARGADO' : 'NO CARGADO');
    console.log('QRCode:', typeof QRCode !== 'undefined' ? 'CARGADO' : 'NO CARGADO');
    
    // Si QRCode no está disponible, intentar cargar desde CDN alternativo
    if (typeof QRCode === 'undefined') {
        console.log('Intentando cargar QRCode desde CDN alternativo...');
        loadAlternativeQRLibrary();
    }
}

/**
 * Cargar librería QR alternativa si la principal falla
 */
function loadAlternativeQRLibrary() {
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
    script.onload = function() {
        console.log('QRious cargado como alternativa');
        // Crear adapter para QRious que imite la API de QRCode.js
        window.QRCode = {
            toCanvas: function(canvas, text, options, callback) {
                try {
                    const qr = new QRious({
                        element: canvas,
                        value: text,
                        size: options.width || 256,
                        foreground: options.color?.dark || '#000',
                        background: options.color?.light || '#fff',
                        level: 'M'
                    });
                    callback(null);
                } catch (error) {
                    callback(error);
                }
            }
        };
    };
    script.onerror = function() {
        console.log('Error cargando QRCode alternativo');
    };
    document.head.appendChild(script);
}

/**
 * Inicializar funcionalidad de portapapeles
 */
function initClipboard() {
    // Verificar si ClipboardJS está disponible
    if (typeof ClipboardJS !== 'undefined') {
        // ClipboardJS para botones de escritorio
        new ClipboardJS('.btn-copiar');
        
        // Feedback para botones de escritorio
        document.querySelectorAll('.btn-copiar').forEach(button => {
            button.addEventListener('click', function() {
                const original = this.innerHTML;
                this.innerHTML = '<i class="ri-check-line mr-1"></i>Copiado';
                
                setTimeout(() => {
                    this.innerHTML = original;
                }, 2000);
            });
        });

        // ClipboardJS específico para móvil
        const clipboardMobile = new ClipboardJS('.btn-copiar-mobile');
        
        clipboardMobile.on('success', function(e) {
            const button = e.trigger;
            const original = button.innerHTML;
            
            // Feedback visual mejorado para móvil
            button.classList.add('copy-feedback');
            button.innerHTML = '<i class="ri-check-line"></i>¡Copiado!';
            
            // Vibración si está disponible
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
            
            setTimeout(() => {
                button.classList.remove('copy-feedback');
                button.innerHTML = original;
            }, 2000);
            
            e.clearSelection();
        });
        
        clipboardMobile.on('error', function(e) {
            console.error('Error copiando:', e);
            showNotification('Error al copiar el enlace', 'error');
        });
    } else {
        // Fallback para navegadores que no soportan ClipboardJS
        document.querySelectorAll('.btn-copiar, .btn-copiar-mobile').forEach(button => {
            button.addEventListener('click', function() {
                const url = this.dataset.clipboardText;
                fallbackCopyToClipboard(url, this);
            });
        });
    }
}

/**
 * Fallback para copiar al portapapeles sin ClipboardJS
 */
function fallbackCopyToClipboard(text, button = null) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            if (button) {
                const original = button.innerHTML;
                button.innerHTML = '<i class="ri-check-line"></i>¡Copiado!';
                
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }
                
                setTimeout(() => {
                    button.innerHTML = original;
                }, 2000);
            }
            showNotification('Enlace copiado al portapapeles', 'success');
        }).catch(() => {
            showNotification('Error al copiar el enlace', 'error');
        });
    } else {
        // Fallback usando textarea
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            if (button) {
                const original = button.innerHTML;
                button.innerHTML = '<i class="ri-check-line"></i>¡Copiado!';
                
                setTimeout(() => {
                    button.innerHTML = original;
                }, 2000);
            }
            showNotification('Enlace copiado al portapapeles', 'success');
        } catch (err) {
            showNotification('Error al copiar el enlace', 'error');
        } finally {
            document.body.removeChild(textArea);
        }
    }
}

/**
 * Inicializar funcionalidad de códigos QR
 */
function initQRFunctionality() {
    // Manejar botones de QR
    document.querySelectorAll('.btn-qr').forEach(button => {
        button.addEventListener('click', function() {
            const url = this.dataset.url;
            const nombre = this.dataset.nombre;
            showQRModal(url, nombre);
        });
    });
    
    // Cerrar modal de QR
    const closeQrBtn = document.getElementById('closeQrModal');
    if (closeQrBtn) {
        closeQrBtn.addEventListener('click', () => {
            document.getElementById('qrModal').classList.add('hidden');
        });
    }
    
    // Cerrar modal al hacer clic fuera
    const qrModal = document.getElementById('qrModal');
    if (qrModal) {
        qrModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    }
}

/**
 * Mostrar modal de código QR - VERSIÓN CORREGIDA
 */
function showQRModal(url, nombre) {
    const modal = document.getElementById('qrModal');
    const title = document.getElementById('qrModalTitle');
    const container = document.getElementById('qrCodeContainer');
    
    if (!modal || !container) {
        showNotification('Error al abrir el modal de QR', 'error');
        return;
    }
    
    title.textContent = `Código QR - ${nombre}`;
    container.innerHTML = '<div class="text-center p-4"><i class="ri-loader-line animate-spin text-2xl"></i><br>Generando código QR...</div>';
    
    // Mostrar modal
    modal.classList.remove('hidden');
    
    // Pequeño delay para asegurar que el modal se muestre antes de generar el QR
    setTimeout(() => {
        generateQRCode(container, url, nombre);
    }, 200);
}

/**
 * Generar código QR - función principal
 */
function generateQRCode(container, url, nombre) {
    // Verificar si QRCode está disponible
    if (typeof QRCode !== 'undefined') {
        console.log('Generando QR con QRCode.js para:', url);
        
        // Limpiar container antes de generar nuevo QR
        container.innerHTML = '';
        
        // Crear elemento canvas
        const canvas = document.createElement('canvas');
        container.appendChild(canvas);
        
        // Generar código QR
        QRCode.toCanvas(canvas, url, {
            width: 256,
            height: 256,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#FFFFFF'
            },
            errorCorrectionLevel: 'M'
        }, function (error) {
            if (error) {
                console.error('Error generando QR con QRCode.js:', error);
                // Fallback a Google API
                generateQRWithGoogleAPI(container, url, nombre);
            } else {
                console.log('QR generado exitosamente con QRCode.js');
                setupQRButtons(container, url, nombre);
            }
        });
    } else {
        console.log('QRCode.js no disponible, usando Google Charts API');
        // Fallback: usar API de Google Charts
        generateQRWithGoogleAPI(container, url, nombre);
    }
}

/**
 * Generar QR usando Google Charts API (fallback) - MEJORADO
 */
function generateQRWithGoogleAPI(container, url, nombre) {
    try {
        const qrSize = 256;
        const encodedUrl = encodeURIComponent(url);
        const qrApiUrl = `https://chart.googleapis.com/chart?chs=${qrSize}x${qrSize}&cht=qr&chl=${encodedUrl}&choe=UTF-8`;
        
        console.log('Generando QR con Google API:', qrApiUrl);
        
        const img = document.createElement('img');
        img.src = qrApiUrl;
        img.alt = `Código QR para ${nombre}`;
        img.className = 'mx-auto border border-gray-200 rounded';
        img.style.width = qrSize + 'px';
        img.style.height = qrSize + 'px';
        img.crossOrigin = 'anonymous'; // Para permitir descargas
        
        img.onload = function() {
            console.log('QR de Google API cargado exitosamente');
            container.innerHTML = '';
            container.appendChild(img);
            setupQRButtons(container, url, nombre);
        };
        
        img.onerror = function() {
            console.error('Error cargando QR de Google API');
            container.innerHTML = `
                <div class="text-red-500 p-4 text-center">
                    <i class="ri-error-warning-line text-2xl"></i><br>
                    Error al generar código QR<br>
                    <small class="text-xs">Verifica tu conexión a internet</small><br>
                    <button onclick="ReservaBot.showQRModal('${url}', '${nombre}')" class="mt-2 px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600">
                        Reintentar
                    </button>
                </div>
            `;
        };
        
        // Timeout de 10 segundos
        setTimeout(() => {
            if (!img.complete) {
                img.src = '';
                container.innerHTML = `
                    <div class="text-red-500 p-4 text-center">
                        <i class="ri-time-line text-2xl"></i><br>
                        Tiempo de espera agotado<br>
                        <button onclick="ReservaBot.showQRModal('${url}', '${nombre}')" class="mt-2 px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600">
                            Reintentar
                        </button>
                    </div>
                `;
            }
        }, 10000);
        
    } catch (error) {
        console.error('Error en generateQRWithGoogleAPI:', error);
        container.innerHTML = '<div class="text-red-500 p-4 text-center"><i class="ri-error-warning-line text-2xl"></i><br>Error inesperado</div>';
    }
}

/**
 * Configurar botones del modal de QR
 */
function setupQRButtons(container, url, nombre) {
    const downloadBtn = document.getElementById('downloadQrBtn');
    const shareBtn = document.getElementById('shareQrBtn');
    
    if (downloadBtn) {
        downloadBtn.onclick = function() {
            downloadQRCode(container, nombre);
        };
        downloadBtn.style.display = 'inline-flex';
    }
    
    if (shareBtn) {
        shareBtn.onclick = function() {
            shareQRCode(container, url, nombre);
        };
        shareBtn.style.display = 'inline-flex';
    }
}

/**
 * Descargar código QR - MEJORADO
 */
function downloadQRCode(container, nombre) {
    const canvas = container.querySelector('canvas');
    const img = container.querySelector('img');
    
    try {
        if (canvas) {
            // Si es un canvas, convertir a imagen y descargar
            const link = document.createElement('a');
            link.download = `qr-${sanitizeFilename(nombre)}.png`;
            link.href = canvas.toDataURL('image/png');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showNotification('Código QR descargado', 'success');
        } else if (img && img.complete) {
            // Si es una imagen de Google API, convertir a canvas primero
            convertImageToCanvasAndDownload(img, nombre);
        } else {
            showNotification('Error: Código QR no disponible para descarga', 'error');
        }
    } catch (error) {
        console.error('Error descargando QR:', error);
        showNotification('Error al descargar el código QR', 'error');
    }
}

/**
 * Convertir imagen a canvas y descargar
 */
function convertImageToCanvasAndDownload(img, nombre) {
    try {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = img.naturalWidth || img.width;
        canvas.height = img.naturalHeight || img.height;
        
        ctx.drawImage(img, 0, 0);
        
        const link = document.createElement('a');
        link.download = `qr-${sanitizeFilename(nombre)}.png`;
        link.href = canvas.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showNotification('Código QR descargado', 'success');
    } catch (error) {
        console.error('Error convirtiendo imagen a canvas:', error);
        // Fallback: intentar descarga directa
        downloadImageFromUrl(img.src, `qr-${sanitizeFilename(nombre)}.png`);
    }
}

/**
 * Descargar imagen desde URL - MEJORADO
 */
function downloadImageFromUrl(url, filename) {
    try {
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showNotification('Código QR descargado', 'success');
    } catch (error) {
        console.error('Error descargando imagen:', error);
        showNotification('Error al descargar el código QR', 'error');
    }
}

/**
 * Compartir código QR
 */
function shareQRCode(container, url, nombre) {
    if (navigator.share) {
        const canvas = container.querySelector('canvas');
        
        if (canvas) {
            canvas.toBlob(blob => {
                const file = new File([blob], `qr-${sanitizeFilename(nombre)}.png`, { type: 'image/png' });
                navigator.share({
                    title: `Reserva - ${nombre}`,
                    text: `Reserva tu cita en ${nombre}`,
                    url: url,
                    files: [file]
                }).catch(error => {
                    console.log('Error sharing:', error);
                    fallbackShare(url);
                });
            });
        } else {
            navigator.share({
                title: `Reserva - ${nombre}`,
                text: `Reserva tu cita en ${nombre}`,
                url: url
            }).catch(error => {
                console.log('Error sharing:', error);
                fallbackShare(url);
            });
        }
    } else {
        fallbackShare(url);
    }
}

/**
 * Fallback para compartir (copiar al portapapeles)
 */
function fallbackShare(url) {
    fallbackCopyToClipboard(url);
    showNotification('Enlace copiado al portapapeles para compartir', 'success');
}

/**
 * Limpiar nombre de archivo
 */
function sanitizeFilename(filename) {
    return filename.toLowerCase()
        .replace(/[^a-z0-9\s]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

/**
 * Inicializar funcionalidad de eliminación
 */
function initDeleteFunctionality() {
    // Manejar botones de eliminar
    document.querySelectorAll('.btn-eliminar').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;
            showDeleteModal(id, nombre);
        });
    });
    
    // Cerrar modal de eliminar
    const cancelBtn = document.getElementById('cancelarEliminar');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            document.getElementById('eliminarModal').classList.add('hidden');
        });
    }
    
    // Cerrar modal al hacer clic fuera
    const deleteModal = document.getElementById('eliminarModal');
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    }
}

/**
 * Mostrar modal de eliminación
 */
function showDeleteModal(id, nombre) {
    const modal = document.getElementById('eliminarModal');
    const nameSpan = document.getElementById('nombreEnlaceEliminar');
    const idInput = document.getElementById('idEnlaceEliminar');
    
    if (modal && nameSpan && idInput) {
        nameSpan.textContent = nombre;
        idInput.value = id;
        modal.classList.remove('hidden');
    }
}

/**
 * Inicializar funcionalidad de compartir social
 */
function initShareFunctionality() {
    // Placeholder para botones de redes sociales específicas
    const enlaces = document.querySelectorAll('[data-url]');
    
    enlaces.forEach(enlaceContainer => {
        const url = enlaceContainer.dataset.url;
        const nombre = enlaceContainer.dataset.nombre;
        
        if (url && nombre) {
            // Se puede expandir para añadir botones de redes sociales específicas
        }
    });
}

/**
 * Inicializar validación de formularios
 */
function initFormValidation() {
    const createForms = document.querySelectorAll('form[method="post"]');
    createForms.forEach(form => {
        if (!form.querySelector('input[name="eliminar_enlace"]')) {
            form.addEventListener('submit', function(e) {
                const nombreInput = form.querySelector('input[name="nombre"]');
                const nombre = nombreInput ? nombreInput.value.trim() : '';
                
                if (nombre.length < 3) {
                    e.preventDefault();
                    showNotification('El nombre debe tener al menos 3 caracteres', 'error');
                    if (nombreInput) nombreInput.focus();
                    return false;
                }
                
                if (nombre.length > 100) {
                    e.preventDefault();
                    showNotification('El nombre no puede tener más de 100 caracteres', 'error');
                    if (nombreInput) nombreInput.focus();
                    return false;
                }
                
                // Loading state para el botón
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="ri-loader-line animate-spin"></i>Creando...';
                    submitBtn.disabled = true;
                    
                    // Restaurar después de 5 segundos si no se envía
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 5000);
                }
            });
        }
    });
}

/**
 * Inicializar mejoras específicas para móvil
 */
function initMobileEnhancements() {
    // Detectar si es dispositivo móvil
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // Mejorar experiencia táctil
        document.querySelectorAll('.form-action-btn').forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            btn.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
        
        // Optimizar modales para móvil
        const modals = document.querySelectorAll('.fixed.inset-0');
        modals.forEach(modal => {
            modal.addEventListener('touchmove', function(e) {
                // Prevenir scroll del body cuando el modal está abierto
                if (!modal.classList.contains('hidden')) {
                    e.preventDefault();
                }
            }, { passive: false });
        });
    }
}

/**
 * Mostrar notificación - MEJORADA
 */
function showNotification(message, type = 'info') {
    // Eliminar notificaciones existentes
    const existingNotifications = document.querySelectorAll('.notification-toast');
    existingNotifications.forEach(notification => notification.remove());
    
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification-toast fixed z-50 transition-all duration-300 transform opacity-0 max-w-sm`;
    
    // Posición responsiva
    if (window.innerWidth <= 768) {
        notification.className += ' top-4 left-4 right-4 translate-y-full';
    } else {
        notification.className += ' bottom-4 right-4 translate-y-full';
    }
    
    notification.className += ' px-4 py-3 rounded-md shadow-lg';
    
    // Aplicar estilos según el tipo
    switch (type) {
        case 'success':
            notification.classList.add('bg-green-50', 'border', 'border-green-200', 'text-green-800');
            notification.innerHTML = `<div class="flex items-center"><i class="ri-check-line mr-2 text-green-500"></i><span>${message}</span></div>`;
            break;
        case 'error':
            notification.classList.add('bg-red-50', 'border', 'border-red-200', 'text-red-800');
            notification.innerHTML = `<div class="flex items-center"><i class="ri-error-warning-line mr-2 text-red-500"></i><span>${message}</span></div>`;
            break;
        case 'warning':
            notification.classList.add('bg-yellow-50', 'border', 'border-yellow-200', 'text-yellow-800');
            notification.innerHTML = `<div class="flex items-center"><i class="ri-alert-line mr-2 text-yellow-500"></i><span>${message}</span></div>`;
            break;
        default:
            notification.classList.add('bg-blue-50', 'border', 'border-blue-200', 'text-blue-800');
            notification.innerHTML = `<div class="flex items-center"><i class="ri-information-line mr-2 text-blue-500"></i><span>${message}</span></div>`;
    }
    
    // Añadir al DOM
    document.body.appendChild(notification);
    
    // Mostrar notificación
    setTimeout(() => {
        notification.classList.remove('translate-y-full', 'opacity-0');
    }, 100);
    
    // Ocultar notificación después de 4 segundos
    setTimeout(() => {
        notification.classList.add('translate-y-full', 'opacity-0');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

/**
 * Funciones de utilidad para validación
 */
function validateFormData(formData) {
    const errors = [];
    
    if (!formData.nombre || formData.nombre.trim().length < 2) {
        errors.push('El nombre debe tener al menos 2 caracteres');
    }
    
    if (!formData.telefono || !/^[+]?[\d\s\-\(\)]{9,}$/.test(formData.telefono)) {
        errors.push('El teléfono no tiene un formato válido');
    }
    
    return errors;
}

/**
 * Formatear número de teléfono
 */
function formatPhoneNumber(phone) {
    // Eliminar todos los caracteres que no sean dígitos o el signo +
    let cleaned = phone.replace(/[^\d+]/g, '');
    
    // Si no empieza con +, agregar +34 para España
    if (!cleaned.startsWith('+')) {
        if (cleaned.startsWith('34')) {
            cleaned = '+' + cleaned;
        } else {
            cleaned = '+34' + cleaned;
        }
    }
    
    return cleaned;
}

/**
 * Generar URL corta (opcional, para futuras implementaciones)
 */
function generateShortUrl(longUrl) {
    // Placeholder para futura implementación de URLs cortas
    return longUrl;
}

/**
 * Funciones de debugging y testing
 */
function debugQR() {
    console.log('QRCode disponible:', typeof QRCode !== 'undefined');
    console.log('ClipboardJS disponible:', typeof ClipboardJS !== 'undefined');
    
    if (typeof QRCode !== 'undefined') {
        console.log('QRCode object:', QRCode);
    }
}

function testQR() {
    const testUrl = 'https://example.com/test';
    console.log('Probando generación QR para:', testUrl);
    
    if (typeof QRCode !== 'undefined') {
        const canvas = document.createElement('canvas');
        document.body.appendChild(canvas);
        canvas.style.position = 'fixed';
        canvas.style.top = '10px';
        canvas.style.left = '10px';
        canvas.style.zIndex = '9999';
        canvas.style.border = '2px solid red';
        
        QRCode.toCanvas(canvas, testUrl, {
            width: 128,
            margin: 2
        }, function(error) {
            if (error) {
                console.error('Error en test QR:', error);
            } else {
                console.log('Test QR exitoso');
                setTimeout(() => {
                    if (document.body.contains(canvas)) {
                        document.body.removeChild(canvas);
                    }
                }, 3000);
            }
        });
    } else {
        console.log('QRCode no disponible para test');
    }
}

function debugFormularios() {
    console.log('=== DEBUG FORMULARIOS ===');
    console.log('QRCode disponible:', typeof QRCode !== 'undefined');
    console.log('ClipboardJS disponible:', typeof ClipboardJS !== 'undefined');
    console.log('Botones QR encontrados:', document.querySelectorAll('.btn-qr').length);
    console.log('Modal QR:', document.getElementById('qrModal') ? 'ENCONTRADO' : 'NO ENCONTRADO');
    console.log('Container QR:', document.getElementById('qrCodeContainer') ? 'ENCONTRADO' : 'NO ENCONTRADO');
    
    // Probar QR si el usuario confirma
    if (confirm('¿Probar generación de QR?')) {
        testQR();
    }
}


// Exponer funciones globalmente para uso en otros scripts y debugging
window.ReservaBot = {
    showNotification,
    validateFormData,
    formatPhoneNumber,
    generateShortUrl,
    showQRModal,
    showDeleteModal,
    debugQR,
    testQR,
    debugFormularios
};

// Exponer funciones de debugging globalmente para consola
window.debugFormularios = debugFormularios;
window.testQR = testQR;