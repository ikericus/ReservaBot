# ReservaBot - Estructura del Proyecto

## Arquitectura
DDD ligero y pragmático (Domain + Repository). Sin capa de UseCases para evitar pass-through innecesario.

## Estructura de Carpetas
```
public_html/ (PROJECT_ROOT)
├── config/
│   ├── bootstrap.php      # Inicialización (PDO, auth, autoload, container)
│   ├── container.php      # DI Container (Singleton)
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
│   │   ├── cliente/
│   │   │   ├── Cliente.php              # Entidad (datos agregados)
│   │   │   ├── ClienteDomain.php        # Lógica negocio
│   │   │   └── IClienteRepository.php   # Interfaz
│   │   ├── configuracion/
│   │   │   ├── ConfiguracionNegocio.php        # Entidad
│   │   │   ├── ConfiguracionDomain.php         # Lógica negocio
│   │   │   └── IConfiguracionNegocioRepository.php  # Interfaz
│   │   ├── disponibilidad/
│   │   │   └── IDisponibilidadRepository.php   # Interfaz (horarios)
│   │   └── shared/
│   │       └── Telefono.php             # Value Object
│   └── infrastructure/   # Implementaciones (minúsculas)
│       ├── ReservaRepository.php        # Implementación PDO
│       ├── ClienteRepository.php        # Implementación PDO
│       ├── ConfiguracionNegocioRepository.php  # Implementación PDO
│       └── DisponibilidadRepository.php        # Implementación PDO
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

## Dominios del Sistema

### 1. ReservaDomain
**Responsabilidad**: Gestión de reservas y disponibilidad de horarios.

**Métodos principales**:
- `obtenerTodasReservasUsuario()` - Todas las reservas
- `obtenerReservasPendientes()` - Solo pendientes
- `obtenerReservasConfirmadas()` - Solo confirmadas
- `obtenerReservasPorFecha()` - Reservas de un día
- `obtenerReservasPorRango()` - Reservas entre fechas
- `crearReserva()` - Nueva reserva con validación
- `confirmarReserva()` - Cambiar estado a confirmada
- `cancelarReserva()` - Cambiar estado a cancelada
- `modificarReserva()` - Editar fecha/hora
- `eliminarReserva()` - Eliminar reserva
- `verificarDisponibilidad()` - Valida horario + negocio
- `obtenerHorasDisponibles()` - Horas libres del día

### 2. ClienteDomain
**Responsabilidad**: Estadísticas y listados de clientes (agregación de reservas).

**Métodos principales**:
- `obtenerDetalleCliente()` - Estadísticas + reservas de un cliente
- `listarClientes()` - Lista paginada con búsqueda

**Entity Cliente**: Datos agregados (total reservas, confirmadas, pendientes, fechas).

### 3. ConfiguracionDomain
**Responsabilidad**: Configuración general del negocio (nombre, datos contacto, etc).

**Métodos principales**:
- `obtenerConfiguraciones()` - Todas las configs
- `actualizarConfiguracion()` - Una config
- `actualizarMultiples()` - Varias configs

**Tabla**: `configuraciones_usuario` (por usuario).

### 4. DisponibilidadRepository
**Responsabilidad**: Horarios de apertura y disponibilidad (sin domain, solo repo).

**Métodos principales**:
- `estaDisponible()` - Verifica si hora está en horario
- `obtenerHorasDelDia()` - Todas las horas configuradas
- `obtenerHorarioDia()` - Config de un día específico

**Tabla**: `configuraciones_usuario` (horarios: `horario_lun`, `horario_mar`, etc).

## Uso en Páginas/APIs (DDD)

### Ejemplo completo:
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

## Tablas Principales

### reservas
Reservas del sistema.
- Campos: id, usuario_id, nombre, telefono, whatsapp_id, fecha, hora, mensaje, estado, notas_internas, created_at, updated_at

### configuraciones_usuario
Configuraciones por usuario (horarios + config negocio).
- Campos: id, usuario_id, clave, valor, created_at, updated_at
- Ejemplos claves: `horario_lun`, `nombre_negocio`, `intervalo`

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
### Próximas a migrar:
- `pages/calendario.php` - Vista calendario → ReservaDomain
- `pages/dia.php` - Vista día → ReservaDomain
- `pages/day.php` - Vista día (legacy) → ReservaDomain
- `pages/cliente-detail.php` - Detalle cliente → ClienteDomain
- `pages/clientes.php` - Lista clientes → ClienteDomain
- `pages/configuracion.php` - Configuración → ConfiguracionDomain

### Otras pendientes:
- `pages/semana.php` - Calendario semana
- `pages/mes.php` - Calendario mes
- `pages/whatsapp.php` - WhatsApp
- `pages/conversaciones.php` - Conversaciones WhatsApp

### APIs pendientes:
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
4. bootstrap.php → carga auth.php, functions.php, autoload, Container
5. Página/API → usa getContainer()->getReservaDomain()
```

## Pendientes
- Migrar páginas y APIs legacy a DDD
- Tests unitarios para Domain
- WhatsAppDomain cuando sea necesario

## Estilo de Respuesta
Código directo. Sin explicaciones largas previas. Escueto.