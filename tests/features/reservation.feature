# language: es
Característica: Sistema de reservas de ReservaBot
  Como usuario del sistema
  Quiero poder gestionar reservas
  Para organizar las citas de mis clientes

  Escenario: Acceder a la página de inicio
    Dado estoy en la página de inicio
    Entonces el código de respuesta debe ser 200
    Y debería ver "ReservaBot"

  Escenario: Ver el panel de administración
    Dado estoy en la página "/admin"
    Entonces debería ver "Panel de Administración"

  Escenario: Crear una nueva reserva
    Dado estoy en la página "/reservas/nueva"
    Cuando completo el formulario con:
      | nombre   | Juan Pérez       |
      | email    | juan@example.com |
      | telefono | 666123456        |
    Y hago clic en el botón "Guardar"
    Entonces debería ver "Reserva creada correctamente"

  @api
  Escenario: Verificar endpoint de API de configuración
    Cuando estoy en la página "/api/configuracion"
    Entonces el código de respuesta debe ser 200
    Y la respuesta debe ser JSON válido
    Y el JSON debe contener la clave "success"

  @api
  Escenario: Listar reservas mediante API
    Cuando estoy en la página "/api/reservas"
    Entonces el código de respuesta debe ser 200
    Y la respuesta debe ser JSON válido

  Escenario: Buscar cliente existente
    Dado estoy en la página "/clientes"
    Cuando completo el campo "buscar" con "Juan"
    Y hago clic en el botón "Buscar"
    Entonces debería ver "Juan Pérez"

  Escenario: Validar formulario vacío
    Dado estoy en la página "/reservas/nueva"
    Cuando hago clic en el botón "Guardar"
    Entonces debería ver "Por favor complete los campos obligatorios"

  @wip
  Escenario: Integración con WhatsApp (en desarrollo)
    Dado estoy en la página "/configuracion/whatsapp"
    Cuando completo el campo "numero" con "666123456"
    Y hago clic en el botón "Conectar"
    Entonces debería ver "WhatsApp conectado correctamente"