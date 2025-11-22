# language: es
Característica: Sistema de creación de reservas
  Como usuario autenticado del sistema
  Quiero poder crear nuevas reservas
  Para gestionar las citas de mis clientes

  Antecedentes:
    Dado estoy autenticado como "demo@dev.reservabot.es"

  Escenario: Acceder a la página de nueva reserva
    Dado estoy en la página "/reserva-form"
    Entonces el código de respuesta debe ser 200
    Y debería ver "Nueva Reserva"

  Escenario: Ver el formulario completo de nueva reserva
    Dado estoy en la página "/reserva-form"
    Entonces debería ver un elemento con id "telefono"
    Y debería ver un elemento con id "nombre"
    Y debería ver un elemento con id "fecha"
    Y debería ver un elemento con id "hora"
    Y debería ver un elemento con id "estado"
    Y debería ver un elemento con id "mensaje"

  Escenario: Ver los campos obligatorios
    Dado estoy en la página "/reserva-form"
    Entonces debería ver "Teléfono"
    Y debería ver "Nombre completo"
    Y debería ver "Fecha"
    Y debería ver "Hora"
    Y debería ver "Estado"
    Y debería ver "Mensaje o notas"

  Escenario: Ver opciones de estado
    Dado estoy en la página "/reserva-form"
    Entonces debería ver "Pendiente"
    Y debería ver "Confirmada"

  Escenario: Ver botones de acción
    Dado estoy en la página "/reserva-form"
    Entonces debería ver "Cancelar"
    Y debería ver "Crear Reserva"

  Escenario: Intentar crear reserva sin completar campos obligatorios
    Dado estoy en la página "/reserva-form"
    Cuando hago clic en el botón "Crear Reserva"
    Entonces debería ver "telefono"

  Escenario: Crear reserva con datos mínimos
    Dado estoy en la página "/reserva-form"
    Cuando completo el formulario con:
      | telefono | +34600{timestamp}         |
      | nombre   | Cliente Test              |
      | fecha    | 2025-12-31                |
    Y selecciono "10:00" del campo "hora"
    Y hago clic en el botón "Crear Reserva"
    Entonces la URL debería contener "/reserva"

  Escenario: Crear reserva con todos los datos
    Dado estoy en la página "/reserva-form"
    Cuando completo el formulario con:
      | telefono | +34600{timestamp}         |
      | nombre   | Cliente Completo Test     |
      | fecha    | 2025-12-31                |
      | mensaje  | Reserva de prueba completa|
    Y selecciono "11:00" del campo "hora"
    Y selecciono "confirmada" del campo "estado"
    Y hago clic en el botón "Crear Reserva"
    Entonces la URL debería contener "/reserva"

  Escenario: Ver información sobre búsqueda de clientes
    Dado estoy en la página "/reserva-form"
    Entonces debería ver "Ingrese el teléfono para buscar clientes existentes o crear uno nuevo"

  Escenario: Acceder con parámetros de URL - teléfono y nombre
    Dado estoy en la página "/reserva-form?telefono=600111222&nombre=Cliente%20URL"
    Entonces el campo "telefono" debe tener el valor "600111222"
    Y el campo "nombre" debe tener el valor "Cliente URL"

  Escenario: Acceder con parámetro de fecha en URL
    Dado estoy en la página "/reserva-form?fecha=2025-12-25"
    Entonces el campo "fecha" debe tener el valor "2025-12-25"

  Escenario: Ver el selector de horas con intervalos
    Dado estoy en la página "/reserva-form"
    Entonces debería ver "Seleccione una hora"
    Y debería ver un elemento con id "hora"

  Escenario: Verificar botón de cancelar
    Dado estoy en la página "/reserva-form"
    Cuando hago clic en "Cancelar"
    Entonces la URL debería contener "/day"

  Escenario: Crear reserva con teléfono en formato español sin prefijo
    Dado estoy en la página "/reserva-form"
    Cuando completo el formulario con:
      | telefono | 600{timestamp}            |
      | nombre   | Cliente España Test       |
      | fecha    | 2025-12-31                |
    Y selecciono "12:00" del campo "hora"
    Y hago clic en el botón "Crear Reserva"
    Entonces la URL debería contener "/reserva"

  Escenario: Verificar campo oculto de whatsapp_id
    Dado estoy en la página "/reserva-form"
    Entonces debería ver un elemento con id "whatsapp_id_hidden"

  Escenario: Verificar contenedor de errores dinámicos
    Dado estoy en la página "/reserva-form"
    Entonces debería ver un elemento con id "dynamicErrors"
    Y debería ver un elemento con id "errorList"

  Escenario: Usuario no autenticado no puede acceder
    Dado no estoy autenticado
    Cuando estoy en la página "/reserva-form"
    Entonces debería estar en la página "/login"

  Escenario: Verificar iconos en los campos
    Dado estoy en la página "/reserva-form"
    Entonces debería ver un elemento con clase "ri-phone-line"
    Y debería ver un elemento con clase "ri-user-line"
    Y debería ver un elemento con clase "ri-calendar-line"
    Y debería ver un elemento con clase "ri-time-line"
    Y debería ver un elemento con clase "ri-message-2-line"