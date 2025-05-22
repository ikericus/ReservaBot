document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const reservaForm = document.getElementById('reservaForm');
    
    // Event listener para el envío del formulario
    reservaForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Crear FormData
        const formData = new FormData(reservaForm);
        
        // Convertir FormData a objeto
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        // Enviar la solicitud
        fetch(reservaForm.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirigir según el modo
                if (data.id) {
                    window.location.href = `reserva-detail.php?id=${data.id}`;
                } else {
                    window.location.href = `day.php?date=${formData.get('fecha')}`;
                }
            } else {
                alert('Error al guardar la reserva: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        });
    });
});