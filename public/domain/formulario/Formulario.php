<?php

namespace ReservaBot\Domain\Formulario;

use DateTime;
use InvalidArgumentException;

/**
 * Entidad Formulario
 * 
 * Representa un formulario público de reservas con su configuración.
 * La personalización visual (colores, logo, nombre empresa) se obtiene
 * de configuraciones_usuario del propietario.
 */
class Formulario
{
    private int $id;
    private int $usuarioId;
    private string $nombre;
    private ?string $descripcion;
    private string $slug;
    private bool $activo;
    private bool $confirmacionAutomatica;
    
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
        $formulario->descripcion = !empty($datos['descripcion']) 
            ? trim($datos['descripcion']) 
            : null;
        $formulario->slug = self::generarSlug();
        $formulario->activo = $datos['activo'] ?? true;
        $formulario->confirmacionAutomatica = $datos['confirmacion_automatica'] ?? false;
        
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
        $formulario->descripcion = $row['descripcion'];
        $formulario->slug = $row['slug'];
        $formulario->activo = (bool) $row['activo'];
        $formulario->confirmacionAutomatica = (bool) $row['confirmacion_automatica'];
        
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
        
        if (isset($datos['descripcion'])) {
            $this->descripcion = !empty($datos['descripcion']) 
                ? trim($datos['descripcion']) 
                : null;
        }
        
        if (isset($datos['confirmacion_automatica'])) {
            $this->confirmacionAutomatica = (bool) $datos['confirmacion_automatica'];
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

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
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
        $array = [
            'usuario_id' => $this->usuarioId,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'slug' => $this->slug,
            'activo' => $this->activo,
            'confirmacion_automatica' => $this->confirmacionAutomatica,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
        
        // Solo incluir id si está inicializado (después de guardar en BD)
        if (isset($this->id)) {
            $array['id'] = $this->id;
        }
        
        return $array;
    }

    /**
     * Obtener URL pública del formulario
     */
    public function getUrlPublica(): string
    {
        return "/pages/reservar?f={$this->slug}";
    }
}