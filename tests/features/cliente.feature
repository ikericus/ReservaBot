# language: es
# tests/features/cliente.feature
Característica: Detalle de cliente
  Como usuario autenticado del sistema
  Quiero ver el detalle de un cliente específico
  Para consultar su historial y gestionar sus reservas

  Antecedentes:
    Dado estoy autenticado como "demo@dev.reservabot.es"

  # ==========================================
  # ACCESO Y PROTECCIÓN
  # ==========================================

  Escenario: Acceder a detalle de cliente con teléfono válido
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces el código de respuesta debe ser 200

  Escenario: Redirigir a lista si no hay teléfono
    Dado estoy en la página "/cliente"
    Entonces debería estar en la página "/clientes"

  Escenario: Usuario no autenticado no puede ver detalle
    Dado no estoy autenticado
    Cuando estoy en la página "/cliente?telefono=611105549"
    Entonces debería estar en la página "/login"

  # ==========================================
  # INFORMACIÓN DEL CLIENTE
  # ==========================================

  Escenario: Ver información básica del cliente
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver un elemento con clase "ri-user-line"

  Escenario: Ver fecha de primer contacto
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver "Cliente desde"

  # ==========================================
  # ESTADÍSTICAS DEL CLIENTE
  # ==========================================

  Escenario: Ver estadísticas de reservas
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver "Total Reservas"
    Y debería ver "Confirmadas"
    Y debería ver "Pendientes"

  Escenario: Verificar iconos en estadísticas
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver un elemento con clase "ri-calendar-line"
    Y debería ver un elemento con clase "ri-check-line"
    Y debería ver un elemento con clase "ri-time-line"

  # ==========================================
  # ACCIONES DISPONIBLES
  # ==========================================

  Escenario: Ver botón de WhatsApp
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver "Chat WhatsApp"

  Escenario: Ver botón Nueva Reserva
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver "Nueva Reserva"

  Escenario: Botón Nueva Reserva prellenado con datos del cliente
    Dado estoy en la página "/cliente?telefono=611105549"
    Cuando hago clic en "Nueva Reserva"
    Entonces la URL debería contener "/reserva-form?telefono="
    Y la URL debería contener "&nombre="

  # ==========================================
  # HISTORIAL DE RESERVAS
  # ==========================================

  Escenario: Ver título de historial de reservas
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver "Historial de Reservas"

  Escenario: Ver contador de reservas en historial
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver un elemento con clase "ri-calendar-line"

  Escenario: Mensaje cuando no hay reservas
    Dado estoy en la página "/cliente?telefono=999999999"
    Entonces debería ver "No hay reservas para este cliente"

  # ==========================================
  # DETALLES DE RESERVAS EN HISTORIAL
  # ==========================================

  Escenario: Ver estados de reservas en historial
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces el código de respuesta debe ser 200

  Escenario: Ver fecha y hora en reservas
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver un elemento con clase "ri-calendar-line"

  Escenario: Ver mensajes en reservas si existen
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver un elemento con clase "ri-message-2-line"

  # ==========================================
  # ACCIONES EN RESERVAS DEL HISTORIAL
  # ==========================================

  Escenario: Ver botón de ver detalle de reserva
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver un elemento con clase "ri-eye-line"

  Escenario: Ver botón de editar reserva
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver un elemento con clase "ri-edit-line"

  Escenario: Acceder a detalle de reserva desde historial
    Dado estoy en la página "/cliente?telefono=611105549"
    Cuando hago clic en un elemento con clase "ri-eye-line"
    Entonces la URL debería contener "/reserva?id="

  Escenario: Acceder a editar reserva desde historial
    Dado estoy en la página "/cliente?telefono=611105549"
    Cuando hago clic en un elemento con clase "ri-edit-line"
    Entonces la URL debería contener "/reserva-form?id="

  # ==========================================
  # ELEMENTOS VISUALES Y BADGES
  # ==========================================

  Escenario: Ver badges de estado en reservas
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces el código de respuesta debe ser 200

  Escenario: Verificar colores por estado de reserva
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces el código de respuesta debe ser 200

  # ==========================================
  # RESPONSIVE - MOBILE
  # ==========================================

  Escenario: Vista mobile carga correctamente
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces el código de respuesta debe ser 200
    Y debería ver un elemento con clase "container-max-width"

  # ==========================================
  # INTEGRACIÓN CON WHATSAPP
  # ==========================================

  Escenario: WhatsApp deshabilitado cuando no está conectado
    Dado estoy en la página "/cliente?telefono=611105549"
    Entonces debería ver "Chat WhatsApp"

  # ==========================================
  # NAVEGACIÓN
  # ==========================================

  Escenario: Volver a lista de clientes
    Dado estoy en la página "/cliente?telefono=611105549"
    Cuando hago clic en "Clientes"
    Entonces debería estar en la página "/clientes" 