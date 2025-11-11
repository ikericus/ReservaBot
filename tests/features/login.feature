# language: es
Característica: Sistema de login de ReservaBot
  Como usuario del sistema
  Quiero poder iniciar sesión
  Para acceder al panel de administración

  Escenario: Acceder a la página de login
    Dado estoy en la página "/login"
    Entonces el código de respuesta debe ser 200
    Y debería ver "Iniciar sesión"

  Escenario: Ver el formulario de login
    Dado estoy en la página "/login"
    Entonces debería ver un elemento con id "email"
    Y debería ver un elemento con id "password"

  Escenario: Intentar login sin credenciales
    Dado estoy en la página "/login"
    Cuando hago clic en el botón "Entrar"
    Entonces debería ver "email"
    Y debería ver "password"

  Escenario: Login con email inválido
    Dado estoy en la página "/login"
    Cuando completo el campo "email" con "correo-invalido"
    Y completo el campo "password" con "123456"
    Y hago clic en el botón "Entrar"
    Entonces debería ver "email"

  Escenario: Login con credenciales incorrectas
    Dado estoy en la página "/login"
    Cuando completo el campo "email" con "usuario@falso.com"
    Y completo el campo "password" con "contraseñaincorrecta"
    Y hago clic en el botón "Entrar"
    Entonces debería ver "credenciales"

  @wip
  Escenario: Login exitoso (requiere credenciales reales)
    Dado estoy en la página "/login"
    Cuando completo el campo "email" con "test@reservabot.es"
    Y completo el campo "password" con "password123"
    Y hago clic en el botón "Entrar"
    Entonces debería estar en la página "/admin"
    Y debería ver "Panel de Administración"