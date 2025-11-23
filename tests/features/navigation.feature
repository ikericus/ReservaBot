# language: es
# tests/features/navigation.feature
Característica: Sistema de navegación de ReservaBot
  Como usuario autenticado del sistema
  Quiero poder navegar por todas las secciones
  Para acceder a las diferentes funcionalidades

  Antecedentes:
    Dado estoy autenticado como "demo@dev.reservabot.es"

  # ==========================================
  # NAVEGACIÓN PRINCIPAL - DESKTOP
  # ==========================================

  Escenario: Acceder a la página de Reservas desde el menú desktop
    Dado estoy en la página "/dia"
    Cuando hago clic en "Reservas"
    Entonces debería estar en la página "/reservas"
    Y el código de respuesta debe ser 200

  Escenario: Acceder a la página de Calendario desde el menú desktop
    Dado estoy en la página "/"
    Cuando hago clic en "Calendario"
    Entonces la URL debería contener "/dia"
    Y el código de respuesta debe ser 200

  Escenario: Acceder a la página de Clientes desde el menú desktop
    Dado estoy en la página "/"
    Cuando hago clic en "Clientes"
    Entonces debería estar en la página "/clientes"
    Y el código de respuesta debe ser 200

  Escenario: Acceder a la página de Formularios desde el menú desktop
    Dado estoy en la página "/"
    Cuando hago clic en "Formularios"
    Entonces debería estar en la página "/formularios"
    Y el código de respuesta debe ser 200

  Escenario: Acceder a la página de WhatsApp desde el menú desktop
    Dado estoy en la página "/"
    Cuando hago clic en "WhatsApp"
    Entonces debería estar en la página "/whatsapp"
    Y el código de respuesta debe ser 200

  Escenario: Acceder a la página de Configuración desde el menú desktop
    Dado estoy en la página "/"
    Cuando hago clic en "Configuración"
    Entonces debería estar en la página "/configuracion"
    Y el código de respuesta debe ser 200

  # ==========================================
  # NAVEGACIÓN DIRECTA POR URL
  # ==========================================

  Escenario: Acceso directo a Reservas
    Dado estoy en la página "/"
    Entonces el código de respuesta debe ser 200
    Y debería ver "Solicitudes Pendientes"

  Escenario: Acceso directo a Calendario
    Dado estoy en la página "/dia"
    Entonces el código de respuesta debe ser 200
    Y debería ver "Calendario"

  Escenario: Acceso directo a Clientes
    Dado estoy en la página "/clientes"
    Entonces el código de respuesta debe ser 200
    Y debería ver "Clientes"

  Escenario: Acceso directo a Formularios
    Dado estoy en la página "/formularios"
    Entonces el código de respuesta debe ser 200

  Escenario: Acceso directo a WhatsApp
    Dado estoy en la página "/whatsapp"
    Entonces el código de respuesta debe ser 200
    Y debería ver "WhatsApp"

  Escenario: Acceso directo a Configuración
    Dado estoy en la página "/configuracion"
    Entonces el código de respuesta debe ser 200
    Y debería ver "Configuración"

  Escenario: Acceso directo a Nueva Reserva
    Dado estoy en la página "/reserva-form"
    Entonces el código de respuesta debe ser 200
    Y debería ver "Nueva Reserva"

  Escenario: Acceso directo a Perfil
    Dado estoy en la página "/perfil"
    Entonces el código de respuesta debe ser 200
    Y debería ver "Mi perfil"

  # ==========================================
  # BREADCRUMBS - NAVEGACIÓN CONTEXTUAL
  # ==========================================

  Escenario: Verificar breadcrumb en página de Reservas
    Dado estoy en la página "/"
    Entonces debería ver "Reservas"

  Escenario: Verificar breadcrumb en página de Calendario
    Dado estoy en la página "/dia"
    Entonces debería ver "Calendario"

  Escenario: Verificar breadcrumb en página de Clientes
    Dado estoy en la página "/clientes"
    Entonces debería ver "Clientes"

  Escenario: Verificar breadcrumb en página de Configuración
    Dado estoy en la página "/configuracion"
    Entonces debería ver "Configuración"

  # ==========================================
  # BOTÓN NUEVA RESERVA
  # ==========================================

  Escenario: Botón Nueva Reserva visible en desktop
    Dado estoy en la página "/"
    Entonces debería ver "Nueva Reserva"

  Escenario: Botón Nueva Reserva funciona desde cualquier página
    Dado estoy en la página "/clientes"
    Cuando hago clic en "Nueva Reserva"
    Entonces debería estar en la página "/reserva-form"

  # ==========================================
  # MENÚ DE USUARIO - DROPDOWN
  # ==========================================

  Escenario: Acceder a Mi Perfil desde menú de usuario
    Dado estoy en la página "/"
    Cuando hago clic en "Mi Perfil"
    Entonces debería estar en la página "/perfil"

  # ==========================================
  # PROTECCIÓN DE RUTAS SIN AUTENTICACIÓN
  # ==========================================

  Escenario: Usuario no autenticado es redirigido desde Reservas
    Dado no estoy autenticado
    Cuando estoy en la página "/"
    Entonces debería estar en la página "/landing"

  Escenario: Usuario no autenticado es redirigido desde Calendario
    Dado no estoy autenticado
    Cuando estoy en la página "/dia"
    Entonces debería estar en la página "/login"

  Escenario: Usuario no autenticado es redirigido desde Clientes
    Dado no estoy autenticado
    Cuando estoy en la página "/clientes"
    Entonces debería estar en la página "/login"

  Escenario: Usuario no autenticado es redirigido desde Configuración
    Dado no estoy autenticado
    Cuando estoy en la página "/configuracion"
    Entonces debería estar en la página "/login"

  Escenario: Usuario no autenticado es redirigido desde Nueva Reserva
    Dado no estoy autenticado
    Cuando estoy en la página "/reserva-form"
    Entonces debería estar en la página "/login"

  # ==========================================
  # ELEMENTOS VISUALES DE NAVEGACIÓN
  # ==========================================

  Escenario: Verificar íconos en el sidebar
    Dado estoy en la página "/"
    Entonces debería ver un elemento con clase "ri-home-line"
    Y debería ver un elemento con clase "ri-calendar-line"
    Y debería ver un elemento con clase "ri-user-line"
    Y debería ver un elemento con clase "ri-whatsapp-line"
    Y debería ver un elemento con clase "ri-settings-line"

  Escenario: Verificar que la página actual está marcada como activa
    Dado estoy en la página "/"
    Entonces debería ver un elemento con clase "active"

  Escenario: Verificar estadísticas en el sidebar
    Dado estoy en la página "/"
    Entonces debería ver "Hoy"
    Y debería ver "Resumen"

  Escenario: Verificar información del usuario en sidebar
    Dado estoy en la página "/"
    Entonces debería ver un elemento con clase "user-avatar"

  # ==========================================
  # FUNCIONALIDAD DE LOGOUT
  # ==========================================

  Escenario: Cerrar sesión desde el menú
    Dado estoy en la página "/"
    Cuando hago clic en "Cerrar Sesión"
    Entonces debería estar en la página "/login"

  # ==========================================
  # NAVEGACIÓN HACIA ATRÁS
  # ==========================================

  Escenario: Volver desde formulario de reserva a calendario
    Dado estoy en la página "/reserva-form?fecha=2025-12-25"
    Entonces debería ver "Cancelar"

  Escenario: Verificar logo de ReservaBot es visible
    Dado estoy en la página "/"
    Entonces debería ver "ReservaBot"

  Escenario: Verificar indicador de plan en sidebar
    Dado estoy en la página "/"
    Entonces debería ver "Plan"