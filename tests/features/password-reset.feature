# language: es
Característica: Sistema de restablecimiento de contraseña
  Como usuario del sistema
  Quiero poder restablecer mi contraseña
  Para recuperar el acceso a mi cuenta

  Escenario: Acceder a la página de solicitud de restablecimiento
    Dado estoy en la página "/password-reset"
    Entonces el código de respuesta debe ser 200
    Y debería ver "Restablecer Contraseña"
    Y debería ver "Te ayudamos a recuperar tu cuenta"

  Escenario: Ver el formulario de solicitud de restablecimiento
    Dado estoy en la página "/password-reset"
    Entonces debería ver un elemento con id "email"
    Y debería ver un elemento con id "requestForm"
    Y debería ver "Email de tu cuenta"
    Y debería ver "Enviar Enlace de Restablecimiento"
    Y debería ver "Ingresa tu email y te enviaremos un enlace para restablecer tu contraseña"

  Escenario: Ver enlaces de navegación en página de solicitud
    Dado estoy en la página "/password-reset"
    Entonces debería ver "Volver al login"
    Y debería ver "¿No tienes cuenta?"
    Y debería ver "Regístrate aquí"

  Escenario: Solicitar restablecimiento sin completar email
    Dado estoy en la página "/password-reset"
    Cuando hago clic en el botón "Enviar Enlace de Restablecimiento"
    Entonces debería ver "email"

  Escenario: Solicitar restablecimiento con email en formato incorrecto
    Dado estoy en la página "/password-reset"
    Cuando completo el campo "email" con "correo-invalido"
    Y hago clic en el botón "Enviar Enlace de Restablecimiento"
    Entonces debería ver "El formato del email no es válido"

  Escenario: Solicitar restablecimiento con email válido
    Dado estoy en la página "/password-reset"
    Cuando completo el campo "email" con "usuario@ejemplo.com"
    Y hago clic en el botón "Enviar Enlace de Restablecimiento"
    Entonces debería ver "Si existe una cuenta con este email, recibirás un enlace de restablecimiento en breve"
    Y debería ver "Revisa tu bandeja de entrada y la carpeta de spam"

  Escenario: Acceder con token inválido
    Dado estoy en la página "/password-reset?token=token_invalido_123"
    Entonces debería ver "Token de restablecimiento inválido o expirado"

  Escenario: Ver formulario de nueva contraseña con token válido
    Dado estoy en la página "/password-reset?token=token_valido_test"
    Entonces debería ver "Nueva Contraseña"
    Y debería ver "Ingresa tu nueva contraseña segura"
    Y debería ver un elemento con id "password"
    Y debería ver un elemento con id "confirm_password"
    Y debería ver un elemento con id "resetForm"
    Y debería ver "Nueva contraseña"
    Y debería ver "Confirmar contraseña"
    Y debería ver "Guardar Nueva Contraseña"

  Escenario: Información de usuario visible con token válido
    Dado estoy en la página "/password-reset?token=token_valido_test"
    Entonces debería ver un elemento con clase "bg-blue-50"

  Escenario: Intentar restablecer sin completar campos
    Dado estoy en la página "/password-reset?token=token_valido_test"
    Cuando hago clic en el botón "Guardar Nueva Contraseña"
    Entonces debería ver "password"

  Escenario: Restablecer con contraseña muy corta
    Dado estoy en la página "/password-reset?token=token_valido_test"
    Cuando completo el campo "password" con "123"
    Y completo el campo "confirm_password" con "123"
    Y hago clic en el botón "Guardar Nueva Contraseña"
    Entonces debería ver "La contraseña debe tener al menos 6 caracteres"

  Escenario: Restablecer con contraseñas que no coinciden
    Dado estoy en la página "/password-reset?token=token_valido_test"
    Cuando completo el campo "password" con "contraseña123"
    Y completo el campo "confirm_password" con "contraseña456"
    Y hago clic en el botón "Guardar Nueva Contraseña"
    Entonces debería ver "Las contraseñas no coinciden"

  Escenario: Restablecer solo contraseña sin confirmación
    Dado estoy en la página "/password-reset?token=token_valido_test"
    Cuando completo el campo "password" con "nuevacontraseña123"
    Y hago clic en el botón "Guardar Nueva Contraseña"
    Entonces debería ver "La confirmación de contraseña es obligatoria"

  Escenario: Restablecer solo confirmación sin contraseña
    Dado estoy en la página "/password-reset?token=token_valido_test"
    Cuando completo el campo "confirm_password" con "nuevacontraseña123"
    Y hago clic en el botón "Guardar Nueva Contraseña"
    Entonces debería ver "La contraseña es obligatoria"

  Escenario: Restablecer contraseña exitosamente
    Dado estoy en la página "/password-reset?token=token_valido_test"
    Cuando completo el campo "password" con "nuevacontraseña123"
    Y completo el campo "confirm_password" con "nuevacontraseña123"
    Y hago clic en el botón "Guardar Nueva Contraseña"
    Entonces debería estar en la página "/login"
    Y debería ver "Contraseña restablecida exitosamente"

  Escenario: Navegar al login desde solicitud de restablecimiento
    Dado estoy en la página "/password-reset"
    Cuando hago clic en "Volver al login"
    Entonces debería estar en la página "/login"

  Escenario: Navegar al registro desde solicitud de restablecimiento
    Dado estoy en la página "/password-reset"
    Cuando hago clic en "Regístrate aquí"
    Entonces debería estar en la página "/signup"

  Escenario: No mostrar enlace de registro después de mensaje de éxito
    Dado estoy en la página "/password-reset"
    Cuando completo el campo "email" con "usuario@ejemplo.com"
    Y hago clic en el botón "Enviar Enlace de Restablecimiento"
    Entonces debería ver "Si existe una cuenta con este email"
    Y no debería ver "¿No tienes cuenta?"

  Escenario: Verificar indicador de fortaleza de contraseña
    Dado estoy en la página "/password-reset?token=token_valido_test"
    Entonces debería ver un elemento con id "strengthMeter"
    Y debería ver un elemento con id "strengthText"

  Escenario: Verificar botón de mostrar/ocultar contraseña
    Dado estoy en la página "/password-reset?token=token_valido_test"
    Entonces debería ver un elemento con id "togglePassword"
    Y debería ver un elemento con id "eyeIcon"

  Escenario: Verificar indicador de coincidencia de contraseñas
    Dado estoy en la página "/password-reset?token=token_valido_test"
    Entonces debería ver un elemento con id "passwordMatch"
    Y debería ver un elemento con id "matchIcon"