# language: es
# tests/features/landing.feature
Característica: Página de aterrizaje de ReservaBot
  Como visitante del sitio web
  Quiero conocer las funcionalidades de ReservaBot
  Para decidir si quiero registrarme

  # ==========================================
  # ACCESO Y ESTRUCTURA BÁSICA
  # ==========================================

  Escenario: Acceder a la página de inicio
    Dado estoy en la página "/landing"
    Entonces el código de respuesta debe ser 200
    Y debería ver "ReservaBot"

  Escenario: Usuario autenticado accede a landing
    Dado estoy autenticado como "demo@dev.reservabot.es"
    Cuando estoy en la página "/landing"
    Entonces el código de respuesta debe ser 200

  # ==========================================
  # NAVEGACIÓN PRINCIPAL
  # ==========================================

  Escenario: Ver menú de navegación
    Dado estoy en la página "/landing"
    Entonces debería ver "Inicio"
    Y debería ver "Funcionalidades"
    Y debería ver "Planes"
    Y debería ver "Contacto"

  Escenario: Ver botones de acción en navegación
    Dado estoy en la página "/landing"
    Entonces debería ver "Iniciar Sesión"
    Y debería ver "Iniciar Demo"

  Escenario: Acceder a login desde landing
    Dado estoy en la página "/landing"
    Cuando hago clic en "Iniciar Sesión"
    Entonces debería estar en la página "/login"

  # ==========================================
  # SECCIÓN HERO
  # ==========================================

  Escenario: Ver título principal
    Dado estoy en la página "/landing"
    Entonces debería ver "Gestión Completa de"
    Y debería ver "Citas"

  Escenario: Ver descripción del servicio
    Dado estoy en la página "/landing"
    Entonces debería ver "Plataforma integral con WhatsApp, calendario, agenda de clientes"

  Escenario: Ver tipos de negocio objetivo
    Dado estoy en la página "/landing"
    Entonces debería ver "Ideal para:"
    Y debería ver "Peluquerías"
    Y debería ver "Estética"
    Y debería ver "Fisioterapia"
    Y debería ver "Consultoría"

  Escenario: Ver beneficios principales
    Dado estoy en la página "/landing"
    Entonces debería ver "Sin instalación"
    Y debería ver "Gratis en Beta"
    Y debería ver "Soporte incluido"

  Escenario: Ver botones CTA en hero
    Dado estoy en la página "/landing"
    Entonces debería ver "Iniciar prueba"
    Y debería ver "Contactar"

  # ==========================================
  # SECCIÓN FUNCIONALIDADES
  # ==========================================

  Escenario: Ver título de funcionalidades
    Dado estoy en la página "/landing"
    Entonces debería ver "Más que un"
    Y debería ver "chatbot"

  Escenario: Ver beneficios principales
    Dado estoy en la página "/landing"
    Entonces debería ver "Reduce las llamadas constantes"
    Y debería ver "Disponible 24/7 para tus clientes"
    Y debería ver "Evita dobles reservas"
    Y debería ver "Mejora la experiencia del cliente"

  Escenario: Ver iconos de funcionalidades
    Dado estoy en la página "/landing"
    Entonces debería ver un elemento con clase "ri-phone-line"
    Y debería ver un elemento con clase "ri-time-line"
    Y debería ver un elemento con clase "ri-calendar-check-line"
    Y debería ver un elemento con clase "ri-user-heart-line"

  Escenario: Ver estadísticas destacadas
    Dado estoy en la página "/landing"
    Entonces debería ver "67%"
    Y debería ver "2 horas"
    Y debería ver "95%"

  # ==========================================
  # SECCIÓN PLANES
  # ==========================================

  Escenario: Ver título de planes
    Dado estoy en la página "/landing"
    Entonces debería ver "Planes adaptados a tu"
    Y debería ver "negocio"

  Escenario: Ver plan Básico
    Dado estoy en la página "/landing"
    Entonces debería ver "Básico"
    Y debería ver "0€"
    Y debería ver "Empezar Gratis"

  Escenario: Ver plan Profesional
    Dado estoy en la página "/landing"
    Entonces debería ver "Profesional"
    Y debería ver "Más Popular"
    Y debería ver "Gratis en Beta"
    Y debería ver "Empezar Beta Gratis"

  Escenario: Ver plan Avanzado
    Dado estoy en la página "/landing"
    Entonces debería ver "Avanzado"
    Y debería ver "Próximamente"
    Y debería ver "No Disponible"

  Escenario: Ver características del plan Básico
    Dado estoy en la página "/landing"
    Entonces debería ver "Reservas por formulario web"
    Y debería ver "Calendario de reservas"
    Y debería ver "Agenda básica de clientes"

  Escenario: Ver características del plan Profesional
    Dado estoy en la página "/landing"
    Entonces debería ver "Integración WhatsApp completa"
    Y debería ver "Comunicación directa con clientes"
    Y debería ver "Recordatorios automáticos"

  Escenario: Ver características del plan Avanzado
    Dado estoy en la página "/landing"
    Entonces debería ver "IA para reservas automáticas"
    Y debería ver "Respuestas automáticas inteligentes"
    Y debería ver "Analytics avanzados"

  Escenario: Registrarse desde plan Básico
    Dado estoy en la página "/landing"
    Cuando hago clic en "Empezar Gratis"
    Entonces la URL debería contener "/signup"

  Escenario: Registrarse desde plan Profesional
    Dado estoy en la página "/landing"
    Cuando hago clic en "Empezar Beta Gratis"
    Entonces la URL debería contener "/signup?plan=profesional"

  # ==========================================
  # SECCIÓN FAQ
  # ==========================================

  Escenario: Ver título de preguntas frecuentes
    Dado estoy en la página "/landing"
    Entonces debería ver "Preguntas"
    Y debería ver "frecuentes"

  Escenario: Ver preguntas disponibles
    Dado estoy en la página "/landing"
    Entonces debería ver "¿ReservaBot es solo un chatbot de WhatsApp?"
    Y debería ver "¿Necesito instalar algo en mi teléfono?"
    Y debería ver "¿Funciona para cualquier tipo de negocio?"
    Y debería ver "¿Puedo personalizar los mensajes automáticos?"
    Y debería ver "¿Qué pasa cuando termine la fase beta?"

  # ==========================================
  # SECCIÓN CTA FINAL
  # ==========================================

  Escenario: Ver llamada a la acción final
    Dado estoy en la página "/landing"
    Entonces debería ver "¿Listo para automatizar tu agenda?"
    Y debería ver "Únete a la beta gratuita"

  Escenario: Ver botón de demo en CTA
    Dado estoy en la página "/landing"
    Entonces debería ver "Probar Beta Gratis"

  Escenario: Ver beneficios en CTA final
    Dado estoy en la página "/landing"
    Entonces debería ver "Setup en 5 minutos"
    Y debería ver "Sin tarjeta de crédito"

  # ==========================================
  # FOOTER
  # ==========================================

  Escenario: Ver información en footer
    Dado estoy en la página "/landing"
    Entonces debería ver "© 2025 ReservaBot"

  Escenario: Ver secciones del footer
    Dado estoy en la página "/landing"
    Entonces debería ver "Producto"
    Y debería ver "Soporte"

  Escenario: Ver enlaces del footer
    Dado estoy en la página "/landing"
    Entonces debería ver "Centro de Ayuda"
    Y debería ver "Tutoriales"
    Y debería ver "Estado del Sistema"

  Escenario: Ver enlaces legales en footer
    Dado estoy en la página "/landing"
    Entonces debería ver "Privacidad"
    Y debería ver "Términos"
    Y debería ver "Cookies"

  Escenario: Ver redes sociales en footer
    Dado estoy en la página "/landing"
    Entonces debería ver un elemento con clase "ri-twitter-line"
    Y debería ver un elemento con clase "ri-facebook-line"
    Y debería ver un elemento con clase "ri-linkedin-line"
    Y debería ver un elemento con clase "ri-instagram-line"

  # ==========================================
  # ICONOS Y ELEMENTOS VISUALES
  # ==========================================

  Escenario: Ver logo de ReservaBot
    Dado estoy en la página "/landing"
    Entonces debería ver un elemento con clase "ri-calendar-line"

  Escenario: Ver iconos de check en beneficios
    Dado estoy en la página "/landing"
    Entonces debería ver un elemento con clase "ri-check-line"

  Escenario: Ver iconos de WhatsApp
    Dado estoy en la página "/landing"
    Entonces debería ver un elemento con clase "ri-whatsapp-line"

  # ==========================================
  # RESPONSIVE ELEMENTS
  # ==========================================

  Escenario: Ver menú mobile presente
    Dado estoy en la página "/landing"
    Entonces debería ver un elemento con id "mobileMenuBtn"

  # ==========================================
  # ENLACES IMPORTANTES
  # ==========================================

  Escenario: Verificar que hay múltiples CTAs al registro
    Dado estoy en la página "/landing"
    Entonces debería ver "Empezar"

  Escenario: Acceso rápido a demo desde múltiples lugares
    Dado estoy en la página "/landing"
    Entonces debería ver "Demo"