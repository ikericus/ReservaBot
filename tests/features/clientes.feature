# language: es
# tests/features/clientes.feature
Característica: Sistema de gestión de clientes
  Como usuario autenticado del sistema
  Quiero poder ver y buscar mis clientes
  Para gestionar su información y reservas

  Antecedentes:
    Dado estoy autenticado como "demo@dev.reservabot.es"

  # ==========================================
  # ACCESO Y VISUALIZACIÓN BÁSICA
  # ==========================================

  Escenario: Acceder a la página de clientes
    Dado estoy en la página "/clientes"
    Entonces el código de respuesta debe ser 200
    Y debería ver "Clientes"

  Escenario: Ver lista de clientes
    Dado estoy en la página "/clientes"
    Entonces debería ver un elemento con clase "ri-user-line"

  Escenario: Usuario no autenticado no puede acceder a clientes
    Dado no estoy autenticado
    Cuando estoy en la página "/clientes"
    Entonces debería estar en la página "/login"

  # ==========================================
  # BÚSQUEDA DE CLIENTES
  # ==========================================

  Escenario: Ver formulario de búsqueda
    Dado estoy en la página "/clientes"
    Entonces debería ver un elemento con id "search"
    Y debería ver "Buscar"

  Escenario: Buscar cliente por nombre
    Dado estoy en la página "/clientes"
    Cuando completo el campo "search" con "Test"
    Y hago clic en el botón "Buscar"
    Entonces la URL debería contener "search=Test"

  Escenario: Limpiar búsqueda
    Dado estoy en la página "/clientes?search=Test"
    Cuando hago clic en "Limpiar"
    Entonces debería estar en la página "/clientes"

  Escenario: Mensaje cuando no hay resultados
    Dado estoy en la página "/clientes?search=ClienteQueNoExiste123456"
    Entonces debería ver "No se encontraron clientes"

  # ==========================================
  # VISTA DESKTOP - TABLA
  # ==========================================

  Escenario: Ver columnas de la tabla en desktop
    Dado estoy en la página "/clientes"
    Entonces debería ver "Cliente"
    Y debería ver "Reservas"
    Y debería ver "Última Reserva"
    Y debería ver "Primer Contacto"
    Y debería ver "Acciones"

  Escenario: Ver botón de ver detalle
    Dado estoy en la página "/clientes"
    Entonces debería ver "Ver Detalle"

  # ==========================================
  # PAGINACIÓN
  # ==========================================

  Escenario: Ver información de paginación
    Dado estoy en la página "/clientes"
    Entonces debería ver "Mostrando"

  Escenario: Navegar a siguiente página si hay más de 20 clientes
    Dado estoy en la página "/clientes?page=1"
    Entonces el código de respuesta debe ser 200

  # ==========================================
  # ICONOS Y ELEMENTOS VISUALES
  # ==========================================

  Escenario: Verificar iconos en la interfaz
    Dado estoy en la página "/clientes"
    Entonces debería ver un elemento con clase "ri-search-line"
    Y debería ver un elemento con clase "ri-user-line"

  # ==========================================
  # NAVEGACIÓN A DETALLE
  # ==========================================

  Escenario: Acceder a detalle de cliente desde la lista
    Dado estoy en la página "/clientes"
    Cuando hago clic en "Ver Detalle"
    Entonces la URL debería contener "/cliente?telefono="