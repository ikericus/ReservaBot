<?php
// infrastructure/FormularioRepository.php

namespace ReservaBot\Infrastructure;

use PDO;
use PDOException;
use ReservaBot\Domain\Formulario\Formulario;
use ReservaBot\Domain\Formulario\IFormularioRepository;

/**
 * Implementación PDO del repositorio de Formularios
 */
class FormularioRepository implements IFormularioRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritDoc}
     */
    public function guardar(Formulario $formulario): Formulario
    {
        $sql = "
            INSERT INTO formularios_publicos (
                usuario_id, nombre, slug, activo, confirmacion_automatica,
                empresa_nombre, empresa_logo, direccion, telefono_contacto, email_contacto,
                color_primario, color_secundario, mensaje_bienvenida,
                created_at, updated_at
            ) VALUES (
                :usuario_id, :nombre, :slug, :activo, :confirmacion_automatica,
                :empresa_nombre, :empresa_logo, :direccion, :telefono_contacto, :email_contacto,
                :color_primario, :color_secundario, :mensaje_bienvenida,
                :created_at, :updated_at
            )
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $formulario->getUsuarioId(),
                ':nombre' => $formulario->getNombre(),
                ':slug' => $formulario->getSlug(),
                ':activo' => $formulario->isActivo() ? 1 : 0,
                ':confirmacion_automatica' => $formulario->isConfirmacionAutomatica() ? 1 : 0,
                ':empresa_nombre' => $formulario->getEmpresaNombre(),
                ':empresa_logo' => $formulario->getEmpresaLogo(),
                ':direccion' => $formulario->getDireccion(),
                ':telefono_contacto' => $formulario->getTelefonoContacto(),
                ':email_contacto' => $formulario->getEmailContacto(),
                ':color_primario' => $formulario->getColorPrimario(),
                ':color_secundario' => $formulario->getColorSecundario(),
                ':mensaje_bienvenida' => $formulario->getMensajeBienvenida(),
                ':created_at' => $formulario->getCreatedAt()->format('Y-m-d H:i:s'),
                ':updated_at' => $formulario->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]);

            $id = (int) $this->pdo->lastInsertId();

            // Reconstruir con el ID asignado
            $row = $formulario->toArray();
            $row['id'] = $id;

            return Formulario::fromDatabase($row);

        } catch (PDOException $e) {
            error_log("Error al guardar formulario: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function actualizar(Formulario $formulario): void
    {
        $sql = "
            UPDATE formularios_publicos 
            SET 
                nombre = :nombre,
                activo = :activo,
                confirmacion_automatica = :confirmacion_automatica,
                empresa_nombre = :empresa_nombre,
                empresa_logo = :empresa_logo,
                direccion = :direccion,
                telefono_contacto = :telefono_contacto,
                email_contacto = :email_contacto,
                color_primario = :color_primario,
                color_secundario = :color_secundario,
                mensaje_bienvenida = :mensaje_bienvenida,
                updated_at = :updated_at
            WHERE id = :id AND usuario_id = :usuario_id
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $formulario->getId(),
                ':usuario_id' => $formulario->getUsuarioId(),
                ':nombre' => $formulario->getNombre(),
                ':activo' => $formulario->isActivo() ? 1 : 0,
                ':confirmacion_automatica' => $formulario->isConfirmacionAutomatica() ? 1 : 0,
                ':empresa_nombre' => $formulario->getEmpresaNombre(),
                ':empresa_logo' => $formulario->getEmpresaLogo(),
                ':direccion' => $formulario->getDireccion(),
                ':telefono_contacto' => $formulario->getTelefonoContacto(),
                ':email_contacto' => $formulario->getEmailContacto(),
                ':color_primario' => $formulario->getColorPrimario(),
                ':color_secundario' => $formulario->getColorSecundario(),
                ':mensaje_bienvenida' => $formulario->getMensajeBienvenida(),
                ':updated_at' => $formulario->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar formulario: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function obtenerPorSlug(string $slug): ?Formulario
    {
        $sql = "SELECT * FROM formularios_publicos WHERE slug = :slug AND activo = 1 LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? Formulario::fromDatabase($row) : null;

        } catch (PDOException $e) {
            error_log("Error al obtener formulario por slug: " . $e->getMessage());
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function obtenerPorId(int $id, int $usuarioId): ?Formulario
    {
        $sql = "SELECT * FROM formularios_publicos WHERE id = :id AND usuario_id = :usuario_id LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':usuario_id' => $usuarioId
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? Formulario::fromDatabase($row) : null;

        } catch (PDOException $e) {
            error_log("Error al obtener formulario por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function obtenerPorUsuario(int $usuarioId): array
    {
        $sql = "
            SELECT * FROM formularios_publicos 
            WHERE usuario_id = :usuario_id 
            ORDER BY created_at DESC
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':usuario_id' => $usuarioId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formularios = [];
            foreach ($rows as $row) {
                $formularios[] = Formulario::fromDatabase($row);
            }

            return $formularios;

        } catch (PDOException $e) {
            error_log("Error al obtener formularios por usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function eliminar(int $id, int $usuarioId): bool
    {
        try {
            // Iniciar transacción
            $this->pdo->beginTransaction();

            // Eliminar referencias en reservas_origen (si existen)
            $sqlOrigen = "DELETE FROM reservas_origen WHERE formulario_id = :formulario_id";
            $stmtOrigen = $this->pdo->prepare($sqlOrigen);
            $stmtOrigen->execute([':formulario_id' => $id]);

            // Eliminar el formulario
            $sql = "DELETE FROM formularios_publicos WHERE id = :id AND usuario_id = :usuario_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':usuario_id' => $usuarioId
            ]);

            $deleted = $stmt->rowCount() > 0;

            // Confirmar transacción
            $this->pdo->commit();

            return $deleted;

        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error al eliminar formulario: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function existeSlug(string $slug): bool
    {
        $sql = "SELECT COUNT(*) FROM formularios_publicos WHERE slug = :slug";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            return $stmt->fetchColumn() > 0;

        } catch (PDOException $e) {
            error_log("Error al verificar existencia de slug: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function obtenerEstadisticas(int $formularioId): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total_reservas,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as reservas_pendientes,
                SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as reservas_confirmadas,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as reservas_canceladas,
                MAX(created_at) as ultima_reserva
            FROM reservas
            WHERE formulario_id = :formulario_id
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':formulario_id' => $formularioId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_reservas' => (int) ($row['total_reservas'] ?? 0),
                'reservas_pendientes' => (int) ($row['reservas_pendientes'] ?? 0),
                'reservas_confirmadas' => (int) ($row['reservas_confirmadas'] ?? 0),
                'reservas_canceladas' => (int) ($row['reservas_canceladas'] ?? 0),
                'ultima_reserva' => $row['ultima_reserva'],
            ];

        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas de formulario: " . $e->getMessage());
            return [
                'total_reservas' => 0,
                'reservas_pendientes' => 0,
                'reservas_confirmadas' => 0,
                'reservas_canceladas' => 0,
                'ultima_reserva' => null,
            ];
        }
    }
}