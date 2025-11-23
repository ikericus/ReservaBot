/**
 * JavaScript completo para la página de formularios
 * Maneja CRUD de formularios, QR, clipboard y todas las funcionalidades
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Formularios.js cargado');
    
    // Verificar librerías
    checkLibraries();
    
    // Inicializar todas las funcionalidades
    initFormCRUD();
    initClipboard();
    initQRFunctionality();
    initDeleteFunctionality();
    initFormCollapse();
    initModalEditar();
});

/**
 * ============================================
 * CRUD DE FORMULARIOS (APIs)
 * ============================================
 */

function initFormCRUD() {
    // === CREAR FORMULARIO ===
    const formCrearDesktop = document.querySelector('#formContainerDesktop form');
    const formCrearMobile = document.querySelector('#formContainerMobile form');
    
    [formCrearDesktop, formCrearMobile].forEach(form => {
        if (form) {
            form.addEventListener('submit', handleFormCreate);
        }
    });
    
    // === EDITAR FORMULARIO ===
    const formEditar = document.getElementById('formEditar');
    if (formEditar) {
        formEditar.addEventListener('submit', handleFormEdit);
    }
    
    // === TOGGLE ESTADO ===
    document.querySelectorAll('.btn-toggle-estado').forEach(btn => {
        btn.addEventListener('click', handleFormToggle);
    });
}

function handleFormCreate(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        nombre: formData.get('nombre'),
        descripcion: formData.get('mensaje_bienvenida'),
        confirmacion_automatica: formData.get('confirmacion_auto') === 'on'
    };
    
    const btn = e.target.querySelector('button[type="submit"]');
    setButtonLoading(btn, 'Creando...');
    
    fetch('api/formulario-crear', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(error.message || 'Error al crear el enlace', 'error');
        resetButton(btn);
    });
}

function handleFormEdit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        id: formData.get('id'),
        nombre: formData.get('nombre'),
        descripcion: formData.get('mensaje_bienvenida'),
        confirmacion_automatica: formData.get('confirmacion_auto') === 'on'
    };
    
    const btn = e.target.querySelector('button[type="submit"]');
    setButtonLoading(btn, 'Guardando...');
    
    fetch('api/formulario-actualizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(error.message || 'Error al actualizar el enlace', 'error');
        resetButton(btn);
    });
}

function handleFormToggle(e) {
    const btn = e.currentTarget;
    const id = btn.dataset.id;
    const accion = btn.dataset.accion;
    const nombre = btn.dataset.nombre;
    
    const mensaje = accion === 'desactivar' 
        ? `¿Desactivar el enlace "${nombre}"? Los clientes no podrán hacer nuevas reservas.`
        : `¿Activar el enlace "${nombre}"?`;
    
    if (!confirm(mensaje)) return;
    
    btn.disabled = true;
    const iconElement = btn.querySelector('i');
    const originalIcon = iconElement.className;
    iconElement.className = 'ri-loader-4-line animate-spin mr-1.5';
    
    fetch('api/formulario-actualizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, accion })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(error.message || 'Error al cambiar el estado', 'error');
        btn.disabled = false;
        iconElement.className = originalIcon;
    });
}

/**
 * ============================================
 * MODAL DE EDICIÓN
 * ============================================
 */

function initModalEditar() {
    const editarModal = document.getElementById('editarModal');
    const btnsCancelarEditar = document.getElementById('cancelarEditar');
    const btsEditar = document.querySelectorAll('.btn-editar');
    
    btsEditar.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('idEnlaceEditar').value = this.dataset.id;
            document.getElementById('nombreEditar').value = this.dataset.nombre;
            document.getElementById('descripcionEditar').value = this.dataset.descripcion;
            document.getElementById('confirmacionAutoEditar').checked = this.dataset.confirmacion === '1';
            editarModal.classList.remove('hidden');
        });
    });
    
    if (btnsCancelarEditar) {
        btnsCancelarEditar.addEventListener('click', () => {
            editarModal.classList.add('hidden');
        });
    }
}

/**
 * ============================================
 * ELIMINACIÓN DE FORMULARIOS
 * ============================================
 */

function initDeleteFunctionality() {
    const btsEliminar = document.querySelectorAll('.btn-eliminar');
    const eliminarModal = document.getElementById('eliminarModal');
    const btnCancelarEliminar = document.getElementById('cancelarEliminar');
    let idFormularioEliminar = null;
    
    btsEliminar.forEach(btn => {
        btn.addEventListener('click', function() {
            idFormularioEliminar = this.dataset.id;
            document.getElementById('nombreEnlaceEliminar').textContent = this.dataset.nombre;
            eliminarModal.classList.remove('hidden');
        });
    });
    
    if (btnCancelarEliminar) {
        btnCancelarEliminar.addEventListener('click', () => {
            eliminarModal.classList.add('hidden');
        });
    }
    
    const formEliminar = document.getElementById('formEliminar');
    if (formEliminar) {
        formEliminar.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!idFormularioEliminar) return;
            
            const btn = this.querySelector('button[type="submit"]');
            setButtonLoading(btn, 'Eliminando...');
            
            fetch('api/formulario-eliminar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: idFormularioEliminar })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification(result.message, 'success');
                    eliminarModal.classList.add('hidden');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error(result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification(error.message || 'Error al eliminar el enlace', 'error');
                resetButton(btn);
            });
        });
    }
}

/**
 * ============================================
 * COLLAPSE DE FORMULARIOS
 * ============================================
 */

function initFormCollapse() {
    // Desktop
    const toggleBtnDesktop = document.getElementById('toggleFormDesktop');
    const formContainerDesktop = document.getElementById('formContainerDesktop');
    
    if (toggleBtnDesktop && formContainerDesktop) {
        toggleBtnDesktop.addEventListener('click', () => {
            formContainerDesktop.classList.toggle('collapsed');
            formContainerDesktop.classList.toggle('expanded');
            toggleBtnDesktop.classList.toggle('active');
        });
    }
    
    // Mobile
    const toggleBtnMobile = document.getElementById('toggleFormMobile');
    const formContainerMobile = document.getElementById('formContainerMobile');
    
    if (toggleBtnMobile && formContainerMobile) {
        toggleBtnMobile.addEventListener('click', () => {
            formContainerMobile.classList.toggle('collapsed');
            formContainerMobile.classList.toggle('expanded');
            toggleBtnMobile.classList.toggle('active');
        });
    }
}

/**
 * ============================================
 * UTILIDADES DE BOTONES
 * ============================================
 */

function setButtonLoading(btn, text) {
    if (!btn) return;
    btn.dataset.originalHtml = btn.innerHTML;
    btn.innerHTML = `<i class="ri-loader-4-line animate-spin mr-2"></i>${text}`;
    btn.disabled = true;
}

function resetButton(btn) {
    if (!btn) return;
    btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
    btn.disabled = false;
}

/**
 * ============================================
 * CLIPBOARD
 * ============================================
 */

function initClipboard() {
    if (typeof ClipboardJS !== 'undefined') {
        new ClipboardJS('.btn-copiar, .btn-copiar-mobile');
        
        document.querySelectorAll('.btn-copiar, .btn-copiar-mobile').forEach(button => {
            button.addEventListener('click', function() {
                const original = this.innerHTML;
                this.innerHTML = '<i class="ri-check-line mr-1"></i>Copiado';
                if (navigator.vibrate) navigator.vibrate(50);
                setTimeout(() => { this.innerHTML = original; }, 2000);
            });
        });
    } else {
        document.querySelectorAll('.btn-copiar, .btn-copiar-mobile').forEach(button => {
            button.addEventListener('click', function() {
                fallbackCopyToClipboard(this.dataset.clipboardText, this);
            });
        });
    }
}

function fallbackCopyToClipboard(text, button = null) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            if (button) {
                const original = button.innerHTML;
                button.innerHTML = '<i class="ri-check-line"></i>Copiado';
                if (navigator.vibrate) navigator.vibrate(50);
                setTimeout(() => { button.innerHTML = original; }, 2000);
            }
            showNotification('Enlace copiado al portapapeles', 'success');
        }).catch(() => {
            showNotification('Error al copiar el enlace', 'error');
        });
    } else {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            if (button) {
                const original = button.innerHTML;
                button.innerHTML = '<i class="ri-check-line"></i>Copiado';
                setTimeout(() => { button.innerHTML = original; }, 2000);
            }
            showNotification('Enlace copiado', 'success');
        } catch (err) {
            showNotification('Error al copiar', 'error');
        } finally {
            document.body.removeChild(textArea);
        }
    }
}

/**
 * ============================================
 * CÓDIGOS QR
 * ============================================
 */

function initQRFunctionality() {
    document.querySelectorAll('.btn-qr').forEach(button => {
        button.addEventListener('click', function() {
            showQRModal(this.dataset.url, this.dataset.nombre);
        });
    });
    
    const closeQrBtn = document.getElementById('closeQrModal');
    if (closeQrBtn) {
        closeQrBtn.addEventListener('click', () => {
            document.getElementById('qrModal').classList.add('hidden');
        });
    }
}

function showQRModal(url, nombre) {
    const modal = document.getElementById('qrModal');
    const title = document.getElementById('qrModalTitle');
    const container = document.getElementById('qrCodeContainer');
    
    if (!modal || !container) return;
    
    title.textContent = `Código QR - ${nombre}`;
    container.innerHTML = '<div class="text-center p-4"><i class="ri-loader-line animate-spin text-2xl"></i><br>Generando...</div>';
    modal.classList.remove('hidden');
    
    setTimeout(() => generateQRCode(container, url, nombre), 200);
}

function generateQRCode(container, url, nombre) {
    if (typeof QRCode !== 'undefined') {
        container.innerHTML = '';
        const canvas = document.createElement('canvas');
        container.appendChild(canvas);
        
        QRCode.toCanvas(canvas, url, {
            width: 256,
            margin: 2,
            errorCorrectionLevel: 'H', // ✅ Cambiado a H para permitir logo
            color: {
                dark: '#667eea',  // ✅ Color base del gradiente
                light: '#ffffff'
            }
        }, function(error) {
            if (error) {
                showNotification('Error al generar código QR', 'error');
            } else {
                console.log('QR generado exitosamente');
                
                // ✅ Aplicar gradiente y añadir logo
                applyGradientToQR(canvas);
                addLogoToQR(canvas);
                
                setupQRButtons(container, url, nombre);
            }
        });
    } else {
        console.warn('Libreria QRCode no disponible');
        showNotification('Error al generar código QR', 'error');
    }
}

// Aplicar gradiente al QR
function applyGradientToQR(canvas) {
    const ctx = canvas.getContext('2d');
    
    // Guardar el canvas original
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = canvas.width;
    tempCanvas.height = canvas.height;
    const tempCtx = tempCanvas.getContext('2d');
    tempCtx.drawImage(canvas, 0, 0);
    
    // Crear gradiente ReservaBot
    const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
    gradient.addColorStop(0, '#667eea');
    gradient.addColorStop(1, '#764ba2');
    
    // Aplicar gradiente
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Usar el QR original como máscara
    ctx.globalCompositeOperation = 'destination-in';
    ctx.drawImage(tempCanvas, 0, 0);
    ctx.globalCompositeOperation = 'source-over';
}

// Añadir logo sin recuadro
function addLogoToQR(canvas) {
    const ctx = canvas.getContext('2d');
    const logo = new Image();
    
    logo.onload = function() {
        const logoSize = canvas.width * 0.2;
        const logoX = (canvas.width - logoSize) / 2;
        const logoY = (canvas.height - logoSize) / 2;
        
        // Fondo blanco circular (sin borde)
        ctx.fillStyle = 'white';
        ctx.beginPath();
        ctx.arc(logoX + logoSize/2, logoY + logoSize/2, logoSize/2 + 6, 0, Math.PI * 2);
        ctx.fill();
        
        // Dibujar logo circular
        ctx.save();
        ctx.beginPath();
        ctx.arc(logoX + logoSize/2, logoY + logoSize/2, logoSize/2, 0, Math.PI * 2);
        ctx.clip();
        ctx.drawImage(logo, logoX, logoY, logoSize, logoSize);
        ctx.restore();
    };
    
    logo.src = '/icons/icon-192.png';
}

function setupQRButtons(container, url, nombre) {
    const downloadBtn = document.getElementById('downloadQrBtn');
    const shareBtn = document.getElementById('shareQrBtn');
    
    if (downloadBtn) {
        downloadBtn.onclick = () => downloadQRCode(container, nombre);
    }
    
    if (shareBtn) {
        shareBtn.onclick = () => shareQRCode(container, url, nombre);
    }
}

function downloadQRCode(container, nombre) {
    const canvas = container.querySelector('canvas');
    const img = container.querySelector('img');
    
    if (canvas) {
        const link = document.createElement('a');
        link.download = `qr-${sanitizeFilename(nombre)}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        showNotification('QR descargado', 'success');
    } else if (img) {
        const link = document.createElement('a');
        link.download = `qr-${sanitizeFilename(nombre)}.png`;
        link.href = img.src;
        link.click();
        showNotification('QR descargado', 'success');
    }
}

function shareQRCode(container, url, nombre) {
    if (navigator.share) {
        navigator.share({
            title: `Reserva - ${nombre}`,
            text: `Reserva tu cita en ${nombre}`,
            url: url
        }).catch(() => fallbackCopyToClipboard(url));
    } else {
        fallbackCopyToClipboard(url);
        showNotification('Enlace copiado para compartir', 'success');
    }
}

function sanitizeFilename(filename) {
    return filename.toLowerCase()
        .replace(/[^a-z0-9\s]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

/**
 * ============================================
 * NOTIFICACIONES
 * ============================================
 */

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transition-all duration-300 transform opacity-0 translate-y-4';
    
    const colors = {
        success: 'bg-green-50 border border-green-200 text-green-800',
        error: 'bg-red-50 border border-red-200 text-red-800',
        warning: 'bg-yellow-50 border border-yellow-200 text-yellow-800',
        info: 'bg-blue-50 border border-blue-200 text-blue-800'
    };
    
    const icons = {
        success: 'ri-check-line',
        error: 'ri-error-warning-line',
        warning: 'ri-alert-line',
        info: 'ri-information-line'
    };
    
    notification.className += ' ' + colors[type];
    notification.innerHTML = `<div class="flex items-center"><i class="${icons[type]} mr-2"></i><span class="text-sm font-medium">${message}</span></div>`;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.remove('opacity-0', 'translate-y-4');
    }, 10);
    
    setTimeout(() => {
        notification.classList.add('opacity-0', 'translate-y-4');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * ============================================
 * VERIFICAR LIBRERÍAS
 * ============================================
 */

function checkLibraries() {
    console.log('ClipboardJS:', typeof ClipboardJS !== 'undefined' ? '✓' : '✗');
    console.log('QRCode:', typeof QRCode !== 'undefined' ? '✓' : '✗');
}

// Exponer funciones globalmente
window.ReservaBot = {
    showNotification,
    showQRModal
};