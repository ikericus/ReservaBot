# ReservaBot - Estructura del Proyecto

## Arquitectura
DDD ligero y pragmático (Domain + Repository). Sin capa de UseCases para evitar pass-through innecesario.

## Estructura de Carpetas
```
public_html/ (PROJECT_ROOT)
├── config/
│   ├── bootstrap.php      # Inicialización (PDO, auth, autoload, container)
│   ├── database.php       # Config BD (lee .env)
│   ├── auth.php          # Sistema de autenticación
│   └── router.php        # Rutas y middleware
├── src/
│   ├── domain/           # Lógica de negocio (minúsculas)
│   │   ├── reserva/
│   │   │   ├── Reserva.php              # Entidad
│   │   │   ├── ReservaDomain.php        # Lógica negocio
│   │   │   ├── IReservaRepository.php   # Interfaz
│   │   │   └── EstadoReserva.php        # Enum
│   │   ├── configuracion/
│   │   │   └── IConfiguracionRepository.php
│   │   └── shared/
│   │       └── Telefono.php             # Value Object
│   └── infrastructure/   # Implementaciones (minúsculas)
│       ├── Container.php                # DI Container (Singleton)
│       ├── ReservaRepository.php        # Implementación PDO
│       └── ConfiguracionRepository.php
├── pages/               # Páginas web
├── api/                 # Endpoints API
├── includes/
│   ├── functions.php    # Helpers globales + flash messages
│   ├── header.php       # Header con flash messages
│   ├── footer.php
│   └── sidebar.php
└── index.php           # Entry point (define PROJECT_ROOT)

/.env                   # Credenciales (NO en Git, fuera de public_html)
```

## Constantes
```php
PROJECT_ROOT  // = /public_html (definida en index.php)
```

## Convenciones

### Rutas
- **Carpetas**: minúsculas (`src/domain/reserva/`)
- **Archivos**: PascalCase (`Reserva.php`, `Container.php`)
- **Siempre usar**: `PROJECT_ROOT . '/ruta'`

### Nomenclatura
- **Domain**: `ReservaDomain` (no Service)
- **Infrastructure**: `ReservaRepository` (no PDOReservaRepository)
- **Métodos Repo**: `obtenerPor...` (no `findBy...`)

### Autoload
```php
ReservaBot\Domain\Reserva\Reserva 
→ src/domain/reserva/Reserva.php
```
Carpetas en minúsculas, archivos case-sensitive.

## Uso en Páginas/APIs (DDD)

### Páginas migradas a DDD
```php
// pages/reservas.php

// Bootstrap ya cargado por router
$currentUser = getAuthenticatedUser();
$userId = $currentUser['id'];

try {
    $reservaDomain = getContainer()->getReservaDomain();
    $reservasPendientes = $reservaDomain->obtenerReservasPendientes($userId);
    
    // Convertir objetos a arrays para la vista
    $reservasPendientes = array_map(fn($r) => $r->toArray(), $reservasPendientes);
} catch (Exception $e) {
    setFlashError('Error: ' . $e->getMessage());
    $reservasPendientes = [];
}
```

### Páginas legacy (pendientes de migrar)
```php
// ❌ Código antiguo que hay que eliminar:
session_start();
require_once '../includes/db-config.php';
require_once '../includes/auth.php';

// Consultas SQL directas
$stmt = $pdo->prepare("SELECT * FROM ...");

// ✅ Debe migrar a:
// - Eliminar requires (bootstrap ya cargado)
// - Usar Domain/Repository
// - Usar flash messages en vez de error_log
```

## Router
- Middleware `['auth']` valida automáticamente
- Bootstrap se carga antes de ejecutar cualquier ruta
- No hace falta `session_start()` ni `require_once` en páginas
- Usuario disponible via `getAuthenticatedUser()`

## Base de Datos
- Credenciales en `.env` (fuera de public_html)
- PDO disponible via `getPDO()`
- Container inyecta PDO a repositorios

## Flash Messages
```php
// Establecer mensajes
setFlashError('Error al guardar');
setFlashSuccess('Reserva confirmada');
setFlashInfo('Recuerda configurar tu horario');

// Se muestran automáticamente en header.php
// Los mensajes se limpian después de mostrarse
```

## Helpers Globales
```php
// BD y Container
getPDO()                    // Conexión BD
getContainer()              // DI Container
hasContainer()              // Bool container disponible

// Auth
isAuthenticated()           // Bool auth
getAuthenticatedUser()      // Array con datos usuario
getCurrentUserId()          // ID usuario autenticado

// Flash Messages
setFlashError($msg)         // Mensaje de error
setFlashSuccess($msg)       // Mensaje de éxito
setFlashInfo($msg)          // Mensaje informativo
getFlashMessages()          // Obtener y limpiar mensajes
```

## Páginas Migradas a DDD ✅
- `pages/reservas.php` - Lista de reservas

## Páginas Legacy (Pendientes) ⚠️
Estas páginas tienen código antiguo y deben migrarse:

### Autenticación
- `pages/login.php` - ✅ Parcialmente (eliminar requires legacy)
- `api/login-handler.php` - ✅ Parcialmente (eliminar requires legacy)

### Páginas principales
- `pages/dia.php` - Calendario día
- `pages/semana.php` - Calendario semana
- `pages/mes.php` - Calendario mes
- `pages/clientes.php` - Lista clientes
- `pages/configuracion.php` - Configuración
- `pages/whatsapp.php` - WhatsApp

### APIs
- `api/crear-reserva.php`
- `api/actualizar-reserva.php`
- `api/eliminar-reserva.php`
- `api/horas-disponibles.php`

**Patrón de migración:**
1. Eliminar `session_start()`, `require_once db-config`, `require_once auth`
2. Reemplazar consultas SQL directas por Domain/Repository
3. Usar `setFlashError()` en vez de `error_log()`
4. Convertir objetos Domain a arrays con `->toArray()` para vistas

## Flujo de Petición
```
1. .htaccess → index.php
2. index.php → define PROJECT_ROOT, carga router.php
3. router.php → carga bootstrap.php, ejecuta middleware ['auth']
4. bootstrap.php → carga auth.php, functions.php, autoload, container
5. Página/API → usa getContainer()->getReservaDomain()
```

## Pendientes
- Migrar páginas y APIs legacy a DDD
- Dominio de Cliente cuando sea necesario
- Dominio de Configuración cuando sea necesario
- Tests unitarios para Domain

## Estilo de Respuesta
Código directo. Sin explicaciones largas previas. Escueto.