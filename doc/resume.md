# ReservaBot - Estructura del Proyecto

## Arquitectura
DDD ligero y pragmático (Domain + Repository + Infrastructure). Sin capa de UseCases.

## Estructura de Carpetas
```
public/ (PROJECT_ROOT)
├── config/
│   ├── bootstrap.php      # Inicialización (PDO, auth, autoload, container)
│   ├── container.php      # DI Container (Singleton)
│   ├── database.php       # Config BD (lee .env)
│   ├── auth.php           # Sistema de autenticación
│   └── router.php         # Rutas y middleware
├── domain/
│   ├── reserva/         # ReservaDomain
│   ├── cliente/         # ClienteDomain  
│   ├── configuracion/   # ConfiguracionDomain
│   ├── disponibilidad/  # IDisponibilidadRepository
│   ├── whatsapp/        # WhatsAppDomain
│   ├── formulario/      # FormularioDomain
│   └── shared/
├── infrastructure/
│   ├── ReservaRepository.php, ClienteRepository.php
│   ├── ConfiguracionNegocioRepository.php, DisponibilidadRepository.php
│   ├── WhatsAppRepository.php, WhatsAppServerManager.php
│   ├── WhatsAppWebhookHandler.php
│   └── FormularioRepository.php
├── pages/                  # Páginas web
├── api/                    # Endpoints API
├── includes/
│   ├── functions.php       # Helpers globales + flash messages
│   ├── header.php          # Header con flash messages
│   ├── footer.php
│   └── sidebar.php
└── index.php               # Entry point (define PROJECT_ROOT)
```

## Convenciones
- **Carpetas**: minúsculas (`domain/formulario/`)
- **Archivos**: PascalCase (`Formulario.php`)
- **Métodos Repo**: `obtenerPor...` (no `findBy...`)
- **Métodos Domain con servidor externo**: prefijo verbal (`conectarConServidor()`, `enviarMensajePorServidor()`)

## Dominios del Sistema

### 1. ReservaDomain
Gestión de reservas y disponibilidad.
- `obtenerReservasPorFecha()`, `obtenerReservasPorRango()`
- `crearReserva()`, `confirmarReserva()`, `cancelarReserva()`
- `modificarReserva()`, `eliminarReserva()`
- `verificarDisponibilidad()`, `obtenerHorasDisponibles()`
- `obtenerHorasDisponiblesConCapacidad()` - Con info de capacidad múltiple
- `modificarReservaPublica()`, `cancelarReservaPublica()` - Con token de acceso

### 2. ClienteDomain
Estadísticas y listados de clientes.
- `obtenerDetalleCliente()`, `listarClientes()`
- `buscarPorTelefono()` - Autocompletado

### 3. ConfiguracionDomain
Configuración del negocio.
- `obtenerConfiguraciones()`, `actualizarConfiguracion()`
- `actualizarMultiples()`, `actualizarConfiguracionesValidadas()` - Con validaciones

### 4. DisponibilidadRepository
Horarios de apertura (sin domain, solo repo).
- `estaDisponible()`, `obtenerHorasDelDia()`, `obtenerIntervalo()`
- `obtenerHorarioDia()` - Devuelve activo + ventanas con capacidad

### 5. WhatsAppDomain
Gestión de WhatsApp y conversaciones.

**Métodos locales (BD):**
- `obtenerConfiguracion()`, `configurarMensajesAutomaticos()`
- `obtenerConversaciones()`, `registrarMensaje()`, `contarNoLeidas()`
- `marcarComoLeida()`, `actualizarActividad()`
- `obtenerEstadisticas()`, `puedeEnviarMensajes()`

**Métodos con servidor externo:**
- `conectarConServidor()` - Inicia conexión con servidor Node.js
- `desconectarDeServidor()` - Desconecta del servidor Node.js
- `obtenerEstadoServidor()` - Estado actual (con sync a BD local)
- `enviarMensajePorServidor()` - Envía mensaje vía servidor

**Infrastructure asociada:**
- `WhatsAppServerManager` - Comunicación HTTP con servidor Node.js (implements IWhatsAppServerManager)
- `WhatsAppWebhookHandler` - Procesa webhooks del servidor (QR, conexión, mensajes)

### 6. FormularioDomain
Gestión de formularios públicos de reservas.
- `crearFormulario()` - Slug único generado automáticamente
- `obtenerFormularioPorSlug()` - Acceso público por slug
- `obtenerFormulariosUsuario()` - Lista con/sin estadísticas
- `actualizarFormulario()`, `eliminarFormulario()`
- `activarFormulario()`, `desactivarFormulario()`

**Entity**: `Formulario` (personalización visual, info empresa, confirmación automática)
**Tabla**: `formularios_publicos`

## Uso en Páginas/APIs

### Páginas migradas a DDD ✅
- `pages/reservas.php` - ReservaDomain
- `pages/whatsapp.php` - WhatsAppDomain
- `pages/dia.php` - ReservaDomain
- `pages/semana.php` - ReservaDomain
- `pages/mes.php` - ReservaDomain
- `pages/formularios.php` - FormularioDomain

### APIs migradas a DDD ✅
- `api/crear-reserva.php` - ReservaDomain
- `api/actualizar-reserva.php` - ReservaDomain
- `api/eliminar-reserva.php` - ReservaDomain
- `api/actualizar-reserva-publica.php` - ReservaDomain (con token)
- `api/horas-disponibles.php` - ReservaDomain + DisponibilidadRepository
- `api/whatsapp-connect.php` - WhatsAppDomain
- `api/whatsapp-disconnect.php` - WhatsAppDomain
- `api/whatsapp-status.php` - WhatsAppDomain
- `api/whatsapp-send.php` - WhatsAppDomain
- `api/whatsapp-conversations.php` - WhatsAppDomain
- `api/whatsapp-stats.php` - WhatsAppDomain
- `api/whatsapp-webhook.php` - WhatsAppWebhookHandler

### APIs pendientes de migrar ⚠️
- `api/buscar-clientes.php` - ClienteDomain
- `api/actualizar-configuracion.php` - ConfiguracionDomain
- `api/crear-reserva-publica.php` - FormularioDomain + ReservaDomain

### Páginas Pendientes ⚠️
- `pages/calendario.php` - Vista calendario → ReservaDomain
- `pages/cliente-detail.php` - ClienteDomain
- `pages/clientes.php` - ClienteDomain  
- `pages/configuracion.php` - ConfiguracionDomain
- `pages/reservar.php` - FormularioDomain (actualizar consulta slug)

## Patrón de Migración para APIs
```php
<?php
// api/ejemplo.php

header('Content-Type: application/json');

// 1. Verificar autenticación (si aplica)
$user = getAuthenticatedUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// 2. Validar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// 3. Obtener y validar datos
$data = json_decode(file_get_contents('php://input'), true);
// ... validaciones

try {
    // 4. Obtener dominio desde container
    $domain = getContainer()->getDomain();
    
    // 5. Ejecutar lógica de negocio
    $resultado = $domain->metodo($param1, $param2);
    
    // 6. Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $resultado
    ]);
    
} catch (\DomainException $e) {
    // Errores de negocio
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\InvalidArgumentException $e) {
    // Errores de validación
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\Exception $e) {
    // Errores inesperados
    error_log('Error en API: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
```

## Patrón de Migración para Páginas
```php
<?php
// pages/ejemplo.php

// 1. Obtener dominio desde container
$domain = getContainer()->getDomain();
$userId = getAuthenticatedUser()['id'];

// 2. Obtener datos del dominio
try {
    $datos = $domain->obtenerDatos($userId);
    
    // 3. Convertir entities a arrays para vistas
    $datosArray = array_map(fn($e) => $e->toArray(), $datos);
    
} catch (\Exception $e) {
    setFlashError('Error: ' . $e->getMessage());
    $datosArray = [];
}

// 4. Incluir header y renderizar
include '../includes/header.php';
?>
<!-- HTML de la página usando $datosArray -->
<?php include '../includes/footer.php'; ?>
```

## Helpers Globales
```php
getPDO()                    // Conexión BD
getContainer()              // DI Container
getAuthenticatedUser()      // Array con datos usuario
setFlashError($msg)         // Mensaje de error
setFlashSuccess($msg)       // Mensaje de éxito
```

## Separación Domain vs Infrastructure

### Domain Layer (reglas de negocio)
- Entities (Reserva, Cliente, WhatsAppConfig, Conversacion)
- Value Objects (Telefono, Email)
- Domain Services (ReservaDomain, WhatsAppDomain)
- Interfaces de repositorios (IReservaRepository, IWhatsAppRepository)
- Interfaces de servicios externos (IWhatsAppServerManager)

### Infrastructure Layer (detalles técnicos)
- Implementaciones de repositorios (ReservaRepository, WhatsAppRepository)
- Managers de servicios externos (WhatsAppServerManager)
- Handlers de eventos (WhatsAppWebhookHandler)
- Acceso a BD con PDO
- Llamadas HTTP a servicios externos

## Tablas Principales
- `reservas` - Reservas del sistema (con access_token para modificación pública)
- `configuraciones_usuario` - Config por usuario + horarios con capacidad
- `whatsapp_config` - Configuración WhatsApp por usuario
- `whatsapp_conversaciones` - Conversaciones WhatsApp
- `whatsapp_messages` - Mensajes WhatsApp (entrantes/salientes)
- `formularios_publicos` - Formularios públicos

## Flujo de Petición
```
1. .htaccess → index.php
2. index.php → router.php
3. router.php → bootstrap.php
4. bootstrap.php → middleware ['auth']
5. bootstrap.php → auth.php, functions.php, autoload, Container
6. Página/API → getContainer()->getDomain()
7. Domain → Repository → BD
8. Domain → ServerManager → Servidor externo (si aplica)
```

## Tests
Tests de integración con SQLite en memoria. Sin mocks complejos.
- `tests/bootstrap_test.php` - Setup mínimo (20 líneas)
- `tests/tests/ReservaRepositoryTest.php` - 3 tests básicos
- Ejecutar: `phpunit`

## Estilo de Respuesta
Proponer dirección antes de crear código. Solicitar ficheros si es necesario. Respuestas escuetas.