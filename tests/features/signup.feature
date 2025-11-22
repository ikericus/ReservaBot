# language: es
Característica: Sistema de registro de ReservaBot
  Como visitante del sistema
  Quiero poder registrarme
  Para crear una cuenta y usar ReservaBot

  Escenario: Acceder a la página de registro
    Dado estoy en la página "/signup"
    Entonces el código de respuesta debe ser 200
    Y debería ver "Únete a ReservaBot"
    Y debería ver "Crea tu cuenta y automatiza las reservas de tu negocio"

  Escenario: Ver el formulario de registro completo
    Dado estoy en la página "/signup"
    Entonces debería ver un elemento con id "nombre"
    Y debería ver un elemento con id "negocio"
    Y debería ver un elemento con id "email"
    Y debería ver un elemento con id "password"
    Y debería ver un elemento con id "confirm_password"
    Y debería ver un elemento con id "terminos"

  Escenario: Ver los campos obligatorios del formulario
    Dado estoy en la página "/signup"
    Entonces debería ver "Nombre *"
    Y debería ver "Nombre del negocio *"
    Y debería ver "Email *"
    Y debería ver "Contraseña *"
    Y debería ver "Confirmar contraseña *"

  Escenario: Ver las opciones de planes disponibles
    Dado estoy en la página "/signup"
    Entonces debería ver "Elige tu plan inicial"
    Y debería ver "Básico"
    Y debería ver "Profesional"
    Y debería ver "Avanzado"
    Y debería ver "GRATIS EN BETA"
    Y debería ver "Próximamente"

  Escenario: Ver información de términos y condiciones
    Dado estoy en la página "/signup"
    Entonces debería ver "Acepto los"
    Y debería ver "términos y condiciones"
    Y debería ver "política de privacidad"

  Escenario: Intentar registro sin completar campos
    Dado estoy en la página "/signup"
    Cuando hago clic en el botón "Crear mi cuenta"
    Entonces debería ver "nombre"

  Escenario: Registro con email en formato incorrecto
    Dado estoy en la página "/signup"
    Cuando completo el formulario con:
      | nombre   | Usuario Test       |
      | negocio  | Mi Negocio Test    |
      | email    | correo-invalido    |
      | password | password123        |
    Y hago clic en el botón "Crear mi cuenta"
    Entonces debería ver "El formato del email no es válido"

  Escenario: Registro con contraseñas que no coinciden
    Dado estoy en la página "/signup"
    Cuando completo el formulario con:
      | nombre           | Usuario Test       |
      | negocio          | Mi Negocio Test    |
      | email            | test@example.com   |
      | password         | password123        |
      | confirm_password | password456        |
    Entonces debería ver un elemento con id "passwordMatchText"

  Escenario: Registro con contraseña débil
    Dado estoy en la página "/signup"
    Cuando completo el campo "password" con "123"
    Entonces debería ver un elemento con id "strengthMeter"

  Escenario: Verificar validación de fortaleza de contraseña
    Dado estoy en la página "/signup"
    Cuando completo el campo "password" con "abc123"
    Entonces debería ver "Contraseña"
    Y debería ver un elemento con id "strengthText"

  Escenario: Intentar registro sin aceptar términos
    Dado estoy en la página "/signup"
    Cuando completo el formulario con:
      | nombre           | Usuario Test       |
      | negocio          | Mi Negocio Test    |
      | email            | test@example.com   |
      | password         | Password123!       |
      | confirm_password | Password123!       |
    Y hago clic en el botón "Crear mi cuenta"
    Entonces debería ver "terminos"

  Escenario: Seleccionar plan Básico
    Dado estoy en la página "/signup"
    Cuando marco el radio button "plan" con valor "basico"
    Entonces el radio button "plan" con valor "basico" debe estar seleccionado

  Escenario: Registro exitoso con plan Básico
    Dado estoy en la página "/signup"
    Cuando completo el formulario con:
      | nombre           | Usuario Test       |
      | negocio          | Mi Negocio Test    |
      | email            | test_{timestamp}@test.com |
      | password         | Password123!       |
      | confirm_password | Password123!       |
    Y marco la casilla "terminos"
    Y hago clic en el botón "Crear mi cuenta"
    Entonces debería estar en la página "/login"
    Y debería ver un mensaje de éxito

  Escenario: Registro exitoso con plan Profesional
    Dado estoy en la página "/signup"
    Cuando completo el formulario con:
      | nombre           | Usuario Pro        |
      | negocio          | Negocio Pro Test   |
      | email            | pro_{timestamp}@test.com |
      | password         | Password123!       |
      | confirm_password | Password123!       |
    Y marco el radio button "plan" con valor "profesional"
    Y marco la casilla "terminos"
    Y hago clic en el botón "Crear mi cuenta"
    Entonces debería estar en la página "/login"
    Y debería ver un mensaje de éxito

  Escenario: Verificar enlace de inicio de sesión
    Dado estoy en la página "/signup"
    Cuando hago clic en "Inicia sesión aquí"
    Entonces debería estar en la página "/login"

  Escenario: Ver mensaje de fase beta
    Dado estoy en la página "/signup"
    Entonces debería ver "Durante la fase beta, todos los planes están disponibles gratis"

  Escenario: Verificar que plan Avanzado no está disponible
    Dado estoy en la página "/signup"
    Entonces debería ver "Próximamente"
    Y debería ver un elemento con clase "opacity-75"

  Escenario: Registro con email ya existente
    Dado estoy en la página "/signup"
    Cuando completo el formulario con:
      | nombre           | Usuario Test       |
      | negocio          | Mi Negocio         |
      | email            | demo@dev.reservabot.es |
      | password         | Password123!       |
      | confirm_password | Password123!       |
    Y marco la casilla "terminos"
    Y hago clic en el botón "Crear mi cuenta"
    Entonces debería ver "error"

  Escenario: Verificar toggle de visibilidad de contraseña
    Dado estoy en la página "/signup"
    Entonces debería ver un elemento con id "togglePassword"
    Y debería ver un elemento con id "eyeIcon"

  Escenario: Verificar indicador de coincidencia de contraseñas
    Dado estoy en la página "/signup"
    Cuando completo el campo "password" con "Password123!"
    Y completo el campo "confirm_password" con "Password123!"
    Entonces debería ver un elemento con id "matchIcon"

  Escenario: Usuario autenticado no puede acceder a registro
    Dado estoy autenticado como "demo@dev.reservabot.es"
    Cuando estoy en la página "/signup"
    Entonces debería estar en la página "/reservas"
