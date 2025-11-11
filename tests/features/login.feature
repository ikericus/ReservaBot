# language: es
Característica: Sistema de login de ReservaBot
  Como usuario del sistema
  Quiero poder iniciar sesión
  Para acceder al panel de administración

  Escenario: Acceder a la página de login
    Dado estoy en la página "/login"
    Entonces el código de respuesta debe ser 200
    Y debería ver "Inicia sesión"

  Escenario: Ver el formulario de login completo
    Dado estoy en la página "/login"
    Entonces debería ver un elemento con id "email"
    Y debería ver un elemento con id "password"
    Y debería ver un elemento con id "loginForm"
    Y debería ver "Email"
    Y debería ver "Contraseña"
    Y debería ver "Recordar sesión"
    Y debería ver "¿Olvidaste tu contraseña?"

  Escenario: Ver el botón de iniciar sesión
    Dado estoy en la página "/login"
    Entonces debería ver "Iniciar Sesión"
    Y debería ver "Regístrate"

  Escenario: Intentar login sin completar campos
    Dado estoy en la página "/login"
    Cuando hago clic en el botón "Iniciar Sesión"
    Entonces debería ver "email"

  Escenario: Login con email en formato incorrecto
    Dado estoy en la página "/login"
    Cuando completo el campo "email" con "correo-sin-arroba"
    Y completo el campo "password" con "123456"
    Y hago clic en el botón "Iniciar Sesión"
    Entonces debería ver "email"

  Escenario: Login con credenciales incorrectas
    Dado estoy en la página "/login"
    Cuando completo el campo "email" con "usuario@falso.com"
    Y completo el campo "password" con "contraseñaincorrecta123"
    Y hago clic en el botón "Iniciar Sesión"
    Entonces la URL debería contener "login"

  Escenario: Verificar enlace de recuperar contraseña
    Dado estoy en la página "/login"
    Cuando hago clic en "¿Olvidaste tu contraseña?"
    Entonces debería estar en la página "/password-reset"

  Escenario: Verificar enlace de registro
    Dado estoy en la página "/login"
    Cuando hago clic en "Regístrate aquí"
    Entonces debería estar en la página "/signup"

  @wip
  Escenario: Login exitoso con credenciales válidas
    Dado estoy en la página "/login"
    Cuando completo el campo "email" con "test@reservabot.es"
    Y completo el campo "password" con "password123"
    Y hago clic en el botón "Iniciar Sesión"
    Entonces debería estar en la página "/admin"
    Y debería ver "Panel"