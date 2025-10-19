# ReservaBot - Estructura del Proyecto

## Arquitectura
DDD ligero y pragmático (Domain + Repository). Sin capa de UseCases.

## Estructura de Carpetas
```
public_html/ (PROJECT_ROOT)
├── config/
│   ├── bootstrap.php      # Inicialización (PDO, auth, autoload, container)
│   ├── container.php      # DI Container (Singleton)
│   ├── database.php       # Config BD (lee .env)
│   ├── auth.php           # Sistema de autenticación
│   └── router.php         # Rutas y middleware
├── src/
│   ├── domain/
│   │   ├── reserva/         # ReservaDomain
│   │   ├── cliente/         # ClienteDomain  
│   │   ├── configuracion/   # ConfiguracionDomain
│   │   ├── disponibilidad/  # IDisponibilidadRepository
│   │   ├── whatsapp/        # WhatsAppDomain
│   │   ├── formulario/      # FormularioDomain (NUEVO)
│   │   └── shared/
│   └── infrastructure/
│       ├── ReservaRepository.php, ClienteRepository.php
│       ├── ConfiguracionNegocioRepository.php, DisponibilidadRepository.php
│       ├── WhatsAppRepository.php
│       └── FormularioRepository.php (NUEVO)
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
- **Carpetas**: minúsculas (`src/domain/formulario/`)
- **Archivos**: PascalCase (`Formulario.php`)
- **Métodos Repo**: `obtenerPor...` (no `findBy...`)

## Dominios del Sistema

### 1. ReservaDomain
Gestión de reservas y disponibilidad.
- `obtenerReservasPorFecha()`, `obtenerReservasPorRango()`
- `crearReserva()`, `confirmarReserva()`, `cancelarReserva()`
- `verificarDisponibilidad()`, `obtenerHorasDisponibles()`

### 2. ClienteDomain
Estadísticas y listados de clientes.
- `obtenerDetalleCliente()`, `listarClientes()`

### 3. ConfiguracionDomain
Configuración del negocio.
- `obtenerConfiguraciones()`, `actualizarConfiguracion()`

### 4. DisponibilidadRepository
Horarios de apertura (sin domain, solo repo).
- `estaDisponible()`, `obtenerHorasDelDia()`, `obtenerIntervalo()`

### 5. WhatsAppDomain
Gestión de WhatsApp y conversaciones.
- `obtenerConfiguracion()`, `iniciarConexion()`, `desconectar()`
- `obtenerConversaciones()`, `registrarMensaje()`, `contarNoLeidas()`

### 6. FormularioDomain ✅ NUEVO
Gestión de formularios públicos de reservas.
- `crearFormulario()` - Slug único generado automáticamente
- `obtenerFormularioPorSlug()` - Acceso público por slug
- `obtenerFormulariosUsuario()` - Lista con/sin estadísticas
- `actualizarFormulario()`, `eliminarFormulario()`
- `activarFormulario()`, `desactivarFormulario()`

**Entity**: `Formulario` (personalización visual, info empresa, confirmación automática)
**Tabla**: `formularios_publicos`

## Uso en Páginas/APIs

### Páginas legacy (pendientes de migrar)
```php
// ❌ Código antiguo que hay que eliminar:
session_start();
require_once '../includes/db-config.php';
require_once '../includes/auth.php';
// Consultas SQL directas
$stmt = $pdo->prepare("SELECT * FROM ...");

```php
// Obtener dominio desde container
$formularioDomain = getContainer()->getFormularioDomain();
$reservaDomain = getContainer()->getReservaDomain();

// Usar métodos del dominio
$formularios = $formularioDomain->obtenerFormulariosUsuario($userId);
$reservas = $reservaDomain->obtenerReservasPorFecha($fechaObj, $userId);

// Convertir a array para vistas
$data = array_map(fn($f) => $f->toArray(), $formularios);
```


## Helpers Globales
```php
getPDO()                    // Conexión BD
getContainer()              // DI Container
getAuthenticatedUser()      // Array con datos usuario
setFlashError($msg)         // Mensaje de error
setFlashSuccess($msg)       // Mensaje de éxito
```

## Páginas Migradas a DDD ✅
- `pages/reservas.php` - ReservaDomain
- `pages/whatsapp.php` - WhatsAppDomain
- `pages/dia.php` - ReservaDomain
- `pages/semana.php` - ReservaDomain
- `pages/mes.php` - ReservaDomain
- `pages/formularios.php` - FormularioDomain

## Páginas Pendientes ⚠️
- `pages/calendario.php` - Vista calendario → ReservaDomain
- `pages/cliente-detail.php` - ClienteDomain
- `pages/clientes.php` - ClienteDomain  
- `pages/configuracion.php` - ConfiguracionDomain
- `pages/reservar.php` - FormularioDomain (actualizar consulta slug)

## APIs Pendientes
- `api/crear-reserva.php`, `api/actualizar-reserva.php`, `api/eliminar-reserva.php`
- `api/horas-disponibles.php`
- `api/whatsapp-*.php` - Usar WhatsAppDomain
- `api/crear-reserva-publica.php` - Usar FormularioDomain

## Patrón de Migración
1. Eliminar `require_once` de db-config, functions, auth
2. Eliminar consultas SQL directas. Usar `getContainer()->getDomain()`
3. Convertir entities a arrays: `array_map(fn($e) => $e->toArray(), $entities)`
4. Usar `setFlashError()` / `setFlashSuccess()`

## Tablas Principales
- `reservas` - Reservas del sistema
- `configuraciones_usuario` - Config por usuario + horarios
- `whatsapp_config`, `whatsapp_conversaciones` - WhatsApp
- `formularios_publicos` - Formularios públicos (NUEVO)

## Flujo de Petición
```
1. .htaccess → index.php
2. index.php → router.php
3. router.php → bootstrap.php → middleware ['auth']
4. bootstrap.php → auth.php, functions.php, autoload, Container
5. Página → getContainer()->getDomain()
```

## Estilo de Respuesta
Proponer dirección de los cambios antes de crear código. Código directo. Sin explicaciones largas previas. Escueto.