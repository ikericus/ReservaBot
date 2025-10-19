<?php

namespace ReservaBot\Domain\Formulario;

use DateTime;
use InvalidArgumentException;

/**
 * Entidad Formulario
 * 
 * Representa un formulario público de reservas con su configuración,
 * personalización visual y datos de contacto de la empresa.
 */
class Formulario
{
    private int $id;
    private int $usuarioId;
    private string $nombre;
    private string $slug;
    private bool $activo;
    private bool $confirmacionAutomatica;
    
    // Información de la empresa
    private ?string $empresaNombre;
    private ?string $empresaLogo;
    private ?string $direccion;
    private ?string $telefonoContacto;
    private ?string $emailContacto;
    
    // Personalización visual
    private string $colorPrimario;
    private string $colorSecundario;
    private ?string $mensajeBienvenida;
    
    // Timestamps
    private DateTime $createdAt;
    private DateTime $updatedAt;

    private function __construct() {}

    /**
     * Factory method para crear un nuevo formulario
     */
    public static function crear(array $datos, int $usuarioId): self
    {
        $formulario = new self();
        
        $formulario->usuarioId = $usuarioId;
        $formulario->nombre = self::validarNombre($datos['nombre'] ?? '');
        $formulario->slug = self::generarSlug();
        $formulario->activo = $datos['activo'] ?? true;
        $formulario->confirmacionAutomatica = $datos['confirmacion_automatica'] ?? false;
        
        // Información de empresa
        $formulario->empresaNombre = !empty($datos['empresa_nombre']) 
            ? trim($datos['empresa_nombre']) 
            : null;
        $formulario->empresaLogo = !empty($datos['empresa_logo']) 
            ? self::validarUrl($datos['empresa_logo']) 
            : null;
        $formulario->direccion = !empty($datos['direccion']) 
            ? trim($datos['direccion']) 
            : null;
        $formulario->telefonoContacto = !empty($datos['telefono_contacto']) 
            ? self::validarTelefono($datos['telefono_contacto']) 
            : null;
        $formulario->emailContacto = !empty($datos['email_contacto']) 
            ? self::validarEmail($datos['email_contacto']) 
            : null;
        
        // Personalización visual
        $formulario->colorPrimario = self::validarColor($datos['color_primario'] ?? '#667eea');
        $formulario->colorSecundario = self::validarColor($datos['color_secundario'] ?? '#764ba2');
        $formulario->mensajeBienvenida = !empty($datos['mensaje_bienvenida']) 
            ? trim($datos['mensaje_bienvenida']) 
            : null;
        
        // Timestamps
        $ahora = new DateTime();
        $formulario->createdAt = $ahora;
        $formulario->updatedAt = $ahora;
        
        return $formulario;
    }

    /**
     * Factory method para reconstruir desde base de datos
     */
    public static function fromDatabase(array $row): self
    {
        $formulario = new self();
        
        $formulario->id = (int) $row['id'];
        $formulario->usuarioId = (int) $row['usuario_id'];
        $formulario->nombre = $row['nombre'];
        $formulario->slug = $row['slug'];
        $formulario->activo = (bool) $row['activo'];
        $formulario->confirmacionAutomatica = (bool) $row['confirmacion_automatica'];
        
        // Información de empresa
        $formulario->empresaNombre = $row['empresa_nombre'];
        $formulario->empresaLogo = $row['empresa_logo'];
        $formulario->direccion = $row['direccion'];
        $formulario->telefonoContacto = $row['telefono_contacto'];
        $formulario->emailContacto = $row['email_contacto'];
        
        // Personalización visual
        $formulario->colorPrimario = $row['color_primario'] ?? '#667eea';
        $formulario->colorSecundario = $row['color_secundario'] ?? '#764ba2';
        $formulario->mensajeBienvenida = $row['mensaje_bienvenida'];
        
        // Timestamps
        $formulario->createdAt = new DateTime($row['created_at']);
        $formulario->updatedAt = new DateTime($row['updated_at']);
        
        return $formulario;
    }

    // ==================== COMPORTAMIENTOS ====================

    /**
     * Activar el formulario para que acepte reservas
     */
    public function activar(): void
    {
        $this->activo = true;
        $this->updatedAt = new DateTime();
    }

    /**
     * Desactivar el formulario (no aceptará nuevas reservas)
     */
    public function desactivar(): void
    {
        $this->activo = false;
        $this->updatedAt = new DateTime();
    }

    /**
     * Actualizar configuración del formulario
     */
    public function actualizarConfiguracion(array $datos): void
    {
        if (isset($datos['nombre'])) {
            $this->nombre = self::validarNombre($datos['nombre']);
        }
        
        if (isset($datos['confirmacion_automatica'])) {
            $this->confirmacionAutomatica = (bool) $datos['confirmacion_automatica'];
        }
        
        // Información de empresa
        if (isset($datos['empresa_nombre'])) {
            $this->empresaNombre = !empty($datos['empresa_nombre']) 
                ? trim($datos['empresa_nombre']) 
                : null;
        }
        
        if (isset($datos['empresa_logo'])) {
            $this->empresaLogo = !empty($datos['empresa_logo']) 
                ? self::validarUrl($datos['empresa_logo']) 
                : null;
        }
        
        if (isset($datos['direccion'])) {
            $this->direccion = !empty($datos['direccion']) 
                ? trim($datos['direccion']) 
                : null;
        }
        
        if (isset($datos['telefono_contacto'])) {
            $this->telefonoContacto = !empty($datos['telefono_contacto']) 
                ? self::validarTelefono($datos['telefono_contacto']) 
                : null;
        }
        
        if (isset($datos['email_contacto'])) {
            $this->emailContacto = !empty($datos['email_contacto']) 
                ? self::validarEmail($datos['email_contacto']) 
                : null;
        }
        
        // Personalización visual
        if (isset($datos['color_primario'])) {
            $this->colorPrimario = self::validarColor($datos['color_primario']);
        }
        
        if (isset($datos['color_secundario'])) {
            $this->colorSecundario = self::validarColor($datos['color_secundario']);
        }
        
        if (isset($datos['mensaje_bienvenida'])) {
            $this->mensajeBienvenida = !empty($datos['mensaje_bienvenida']) 
                ? trim($datos['mensaje_bienvenida']) 
                : null;
        }
        
        $this->updatedAt = new DateTime();
    }

    // ==================== VALIDACIONES ====================

    private static function validarNombre(string $nombre): string
    {
        $nombre = trim($nombre);
        
        if (empty($nombre)) {
            throw new InvalidArgumentException('El nombre del formulario no puede estar vacío');
        }
        
        if (strlen($nombre) < 3) {
            throw new InvalidArgumentException('El nombre del formulario debe tener al menos 3 caracteres');
        }
        
        if (strlen($nombre) > 100) {
            throw new InvalidArgumentException('El nombre del formulario no puede exceder 100 caracteres');
        }
        
        return $nombre;
    }

    private static function generarSlug(): string
    {
        // Generar slug único de 8 caracteres hexadecimales
        return bin2hex(random_bytes(4));
    }

    private static function validarColor(string $color): string
    {
        $color = trim($color);
        
        // Validar formato hexadecimal (#RRGGBB o #RGB)
        if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            throw new InvalidArgumentException("Color inválido: {$color}. Debe ser formato hexadecimal (#RRGGBB)");
        }
        
        return $color;
    }

    private static function validarUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        
        $url = trim($url);
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("URL inválida: {$url}");
        }
        
        return $url;
    }

    private static function validarTelefono(?string $telefono): ?string
    {
        if (empty($telefono)) {
            return null;
        }
        
        $telefono = trim($telefono);
        
        // Validación básica: mínimo 7 caracteres (puede incluir +, espacios, guiones)
        if (strlen($telefono) < 7) {
            throw new InvalidArgumentException('El teléfono debe tener al menos 7 caracteres');
        }
        
        return $telefono;
    }

    private static function validarEmail(?string $email): ?string
    {
        if (empty($email)) {
            return null;
        }
        
        $email = trim($email);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email inválido: {$email}");
        }
        
        return $email;
    }

    // ==================== GETTERS ====================

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsuarioId(): int
    {
        return $this->usuarioId;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function isConfirmacionAutomatica(): bool
    {
        return $this->confirmacionAutomatica;
    }

    public function getEmpresaNombre(): ?string
    {
        return $this->empresaNombre;
    }

    public function getEmpresaLogo(): ?string
    {
        return $this->empresaLogo;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function getTelefonoContacto(): ?string
    {
        return $this->telefonoContacto;
    }

    public function getEmailContacto(): ?string
    {
        return $this->emailContacto;
    }

    public function getColorPrimario(): string
    {
        return $this->colorPrimario;
    }

    public function getColorSecundario(): string
    {
        return $this->colorSecundario;
    }

    public function getMensajeBienvenida(): ?string
    {
        return $this->mensajeBienvenida;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    // ==================== UTILIDADES ====================

    /**
     * Convertir a array para uso en vistas/APIs
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuarioId,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'activo' => $this->activo,
            'confirmacion_automatica' => $this->confirmacionAutomatica,
            'empresa_nombre' => $this->empresaNombre,
            'empresa_logo' => $this->empresaLogo,
            'direccion' => $this->direccion,
            'telefono_contacto' => $this->telefonoContacto,
            'email_contacto' => $this->emailContacto,
            'color_primario' => $this->colorPrimario,
            'color_secundario' => $this->colorSecundario,
            'mensaje_bienvenida' => $this->mensajeBienvenida,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Obtener URL pública del formulario
     */
    public function getUrlPublica(): string
    {
        // TODO: Obtener domain desde configuración
        return "/pages/reservar?f={$this->slug}";
    }
}