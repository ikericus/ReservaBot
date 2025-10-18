# ReservaBot - Estructura del Proyecto

## Arquitectura
DDD ligero y pragmático. Migración progresiva desde código legacy a arquitectura limpia.

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
│   ├── infrastructure/   # Implementaciones (minúsculas)
│   │   ├── Container.php                # DI Container (Singleton)
│   │   ├── ReservaRepository.php        # Implementación PDO
│   │   └── ConfiguracionRepository.php
│   └── application/      # Casos de uso (minúsculas)
│       └── reserva/
│           └── ReservaUseCases.php      # Casos agrupados
├── pages/               # Páginas web
├── api/                 # Endpoints API
├── includes/
│   └── functions.php    # Legacy helpers
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
- **Application**: Casos de uso agrupados en un archivo

### Autoload
```php
ReservaBot\Domain\Reserva\Reserva 
→ src/domain/reserva/Reserva.php
```
Carpetas en minúsculas, archivos case-sensitive.

## Uso
```php
// En cualquier página/API
require_once PROJECT_ROOT . '/config/bootstrap.php';

$usuarioId = getCurrentUserId(); // Router ya validó auth
$reservaUseCases = getContainer()->getReservaUseCases();
$reserva = $reservaUseCases->crearReserva(...);
```

## Router
- Middleware `['auth']` valida automáticamente
- No hace falta `requireAuth()` en páginas protegidas
- Usuario disponible en `$GLOBALS['currentUser']`

## Base de Datos
- Credenciales en `.env` (fuera de public_html)
- PDO disponible via `getPDO()`
- Container inyecta PDO a repositorios

## Helpers Globales
```php
getPDO()              // Conexión BD
getContainer()        // DI Container
getCurrentUserId()    // ID usuario autenticado
isAuthenticated()     // Bool auth
hasContainer()        // Bool container disponible
```

## Pendientes
- Migrar resto de APIs y páginas
- Refactorizar `functions.php` a Domain/Infrastructure
- Dominio de Cliente/Usuario cuando sea necesario

## Estilo de Respuesta
Código directo. Sin explicaciones largas previas. Escueto.