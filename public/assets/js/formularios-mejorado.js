/**
 * JavaScript mejorado para la página de formularios
 * Maneja la generación de códigos QR y funcionalidades adicionales
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todas las funcionalidades
    initClipboard();
    initQRFunctionality();
    initDeleteFunctionality();
    initShareFunctionality();
});

/**
 * Inicializar funcionalidad de portapapeles
 */
function initClipboard() {
    // Verificar si ClipboardJS está disponible
    if (typeof ClipboardJS !== 'undefined') {
        new ClipboardJS('.btn-copiar');
        
        // Mostrar feedback al copiar
        document.querySelectorAll('.btn-copiar').forEach(button => {
            button.addEventListener('click', function() {
                const original = this.innerHTML;
                this.innerHTML = '<i class="ri-check-line mr-1"></i>Copiado';
                
                setTimeout(() => {
                    this.innerHTML = original;
                }, 2000);
            });
        });
    } else {
        // Fallback para navegadores que no soportan Clipboard API
        document.querySelectorAll('.btn-copiar').forEach(button => {
            button.addEventListener('click', function() {
                const url = this.dataset.clipboardText;
                fallbackCopyToClipboard(url);
            });
        });
    }
}

/**
 * Fallback para copiar al portapapeles sin ClipboardJS
 */
function fallbackCopyToClipboard(text) {
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
        showNotification('Enlace copiado al portapapeles', 'success');
    } catch (err) {
        showNotification('Error al copiar el enlace', 'error');
    } finally {
        document.body.removeChild(textArea);
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
 * Mostrar modal de código QR
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
    
    // Verificar si QRCode está disponible
    if (typeof QRCode !== 'undefined') {
        // Generar código QR con QRCode.js
        container.innerHTML = '';
        QRCode.toCanvas(container, url, {
            width: 256,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#FFFFFF'
            }
        }, function (error) {
            if (error) {
                container.innerHTML = '<div class="text-red-500 p-4"><i class="ri-error-warning-line"></i><br>Error al generar código QR</div>';
                console.error('Error generando QR:', error);
            } else {
                setupQRButtons(container, url, nombre);
            }
        });
    } else {
        // Fallback: usar API de Google Charts (requiere conexión a internet)
        generateQRWithGoogleAPI(container, url, nombre);
    }
}

/**
 * Generar QR usando Google Charts API (fallback)
 */
function generateQRWithGoogleAPI(container, url, nombre) {
    const qrSize = 256;
    const encodedUrl = encodeURIComponent(url);
    const qrApiUrl = `https://chart.googleapis.com/chart?chs=${qrSize}x${qrSize}&cht=qr&chl=${encodedUrl}`;
    
    const img = document.createElement('img');
    img.src = qrApiUrl;
    img.alt = `Código QR para ${nombre}`;
    img.className = 'mx-auto';
    img.style.width = qrSize + 'px';
    img.style.height = qrSize + 'px';
    
    img.onload = function() {
        container.innerHTML = '';
        container.appendChild(img);
        setupQRButtons(container, url, nombre);
    };
    
    img.onerror = function() {
        container.innerHTML = '<div class="text-red-500 p-4"><i class="ri-error-warning-line"></i><br>Error al generar código QR<br><small>Verifica tu conexión a internet</small></div>';
    };
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
    }
    
    if (shareBtn) {
        shareBtn.onclick = function() {
            shareQRCode(container, url, nombre);
        };
    }
}

/**
 * Descargar código QR
 */
function downloadQRCode(container, nombre) {
    const canvas = container.querySelector('canvas');
    const img = container.querySelector('img');
    
    if (canvas) {
        // Si es un canvas, convertir a imagen y descargar
        const link = document.createElement('a');
        link.download = `qr-${sanitizeFilename(nombre)}.png`;
        link.href = canvas.toDataURL();
        link.click();
        showNotification('Código QR descargado', 'success');
    } else if (img) {
        // Si es una imagen, descargar directamente
        downloadImageFromUrl(img.src, `qr-${sanitizeFilename(nombre)}.png`);
    } else {
        showNotification('No se puede descargar el código QR', 'error');
    }
}

/**
 * Descargar imagen desde URL
 */
function downloadImageFromUrl(url, filename) {
    fetch(url)
        .then(response => response.blob())
        .then(blob => {
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            URL.revokeObjectURL(link.href);
            showNotification('Código QR descargado', 'success');
        })
        .catch(error => {
            console.error('Error descargando imagen:', error);
            showNotification('Error al descargar el código QR', 'error');
        });
}

/**
 * Compartir código QR
 */
function shareQRCode(container, url, nombre) {
    if (navigator.share) {
        // Usar Web Share API si está disponible
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
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Enlace copiado al portapapeles para compartir', 'success');
        }).catch(() => {
            fallbackCopyToClipboard(url);
        });
    } else {
        fallbackCopyToClipboard(url);
    }
}

/**
 * Limpiar nombre de archivo
 */
function sanitizeFilename(filename) {
    return filename.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-').trim('-');
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
    // Agregar botones de compartir en redes sociales (opcional)
    addSocialShareButtons();
}

/**
 * Agregar botones de compartir en redes sociales
 */
function addSocialShareButtons() {
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
 * Mostrar notificación
 */
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-4 py-3 rounded-md shadow-lg z-50 transition-all duration-300 transform translate-y-full opacity-0`;
    
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
    
    // Ocultar notificación después de 3 segundos
    setTimeout(() => {
        notification.classList.add('translate-y-full', 'opacity-0');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
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

// Exponer funciones globalmente para uso en otros scripts
window.ReservaBot = {
    showNotification,
    validateFormData,
    formatPhoneNumber,
    generateShortUrl,
    showQRModal,
    showDeleteModal
};