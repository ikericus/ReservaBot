<?php

namespace ReservaBot\Domain\Formulario;

use InvalidArgumentException;
use RuntimeException;

/**
 * Capa de Dominio de Formularios
 * 
 * Orquesta la lógica de negocio relacionada con la gestión
 * de formularios públicos de reservas.
 */
class FormularioDomain
{
    private IFormularioRepository $repository;

    public function __construct(IFormularioRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Crear un nuevo formulario
     * 
     * Valida los datos, genera slug único y persiste el formulario
     * 
     * @param array $datos Datos del formulario
     * @param int $usuarioId ID del usuario propietario
     * @return Formulario El formulario creado con ID asignado
     * @throws InvalidArgumentException Si los datos son inválidos
     * @throws RuntimeException Si hay error al guardar
     */
    public function crearFormulario(array $datos, int $usuarioId): Formulario
    {
        // Crear entidad con validaciones
        $formulario = Formulario::crear($datos, $usuarioId);
        
        // Verificar que el slug sea único (muy improbable colisión con 8 chars hex)
        $intentos = 0;
        $maxIntentos = 5;
        
        while ($this->repository->existeSlug($formulario->getSlug()) && $intentos < $maxIntentos) {
            // Regenerar formulario con nuevo slug
            $formulario = Formulario::crear($datos, $usuarioId);
            $intentos++;
        }
        
        if ($intentos >= $maxIntentos) {
            throw new RuntimeException('No se pudo generar un slug único después de varios intentos');
        }
        
        // Guardar en base de datos
        return $this->repository->guardar($formulario);
    }

    /**
     * Obtener un formulario por su slug (para acceso público)
     * 
     * @param string $slug
     * @return Formulario|null
     */
    public function obtenerFormularioPorSlug(string $slug): ?Formulario
    {
        if (empty($slug)) {
            return null;
        }
        
        return $this->repository->obtenerPorSlug($slug);
    }

    /**
     * Obtener un formulario por ID (solo si pertenece al usuario)
     * 
     * @param int $id
     * @param int $usuarioId
     * @return Formulario|null
     */
    public function obtenerFormularioPorId(int $id, int $usuarioId): ?Formulario
    {
        if ($id <= 0 || $usuarioId <= 0) {
            return null;
        }
        
        return $this->repository->obtenerPorId($id, $usuarioId);
    }

    /**
     * Obtener todos los formularios de un usuario
     * 
     * @param int $usuarioId
     * @return Formulario[]
     */
    public function obtenerFormulariosUsuario(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }
        
        return $this->repository->obtenerPorUsuario($usuarioId);
    }

    /**
     * Actualizar un formulario existente
     * 
     * @param int $id
     * @param array $datos
     * @param int $usuarioId
     * @return Formulario El formulario actualizado
     * @throws InvalidArgumentException Si el formulario no existe o no pertenece al usuario
     * @throws RuntimeException Si hay error al actualizar
     */
    public function actualizarFormulario(int $id, array $datos, int $usuarioId): Formulario
    {
        // Obtener formulario existente
        $formulario = $this->repository->obtenerPorId($id, $usuarioId);
        
        if (!$formulario) {
            throw new InvalidArgumentException('Formulario no encontrado o no tienes permiso para modificarlo');
        }
        
        // Aplicar cambios
        $formulario->actualizarConfiguracion($datos);
        
        // Persistir
        $this->repository->actualizar($formulario);
        
        return $formulario;
    }

    /**
     * Activar un formulario
     * 
     * @param int $id
     * @param int $usuarioId
     * @return Formulario
     * @throws InvalidArgumentException Si el formulario no existe
     */
    public function activarFormulario(int $id, int $usuarioId): Formulario
    {
        $formulario = $this->repository->obtenerPorId($id, $usuarioId);
        
        if (!$formulario) {
            throw new InvalidArgumentException('Formulario no encontrado');
        }
        
        $formulario->activar();
        $this->repository->actualizar($formulario);
        
        return $formulario;
    }

    /**
     * Desactivar un formulario
     * 
     * @param int $id
     * @param int $usuarioId
     * @return Formulario
     * @throws InvalidArgumentException Si el formulario no existe
     */
    public function desactivarFormulario(int $id, int $usuarioId): Formulario
    {
        $formulario = $this->repository->obtenerPorId($id, $usuarioId);
        
        if (!$formulario) {
            throw new InvalidArgumentException('Formulario no encontrado');
        }
        
        $formulario->desactivar();
        $this->repository->actualizar($formulario);
        
        return $formulario;
    }

    /**
     * Eliminar un formulario
     * 
     * Elimina el formulario y sus referencias en reservas_origen.
     * Las reservas asociadas permanecen pero pierden la referencia al formulario.
     * 
     * @param int $id
     * @param int $usuarioId
     * @return bool True si se eliminó, false si no existía
     * @throws RuntimeException Si hay error al eliminar
     */
    public function eliminarFormulario(int $id, int $usuarioId): bool
    {
        // Verificar que existe y pertenece al usuario
        $formulario = $this->repository->obtenerPorId($id, $usuarioId);
        
        if (!$formulario) {
            return false;
        }
        
        // Eliminar (el cascade se maneja en el repository)
        return $this->repository->eliminar($id, $usuarioId);
    }

    /**
     * Obtener estadísticas de un formulario
     * 
     * @param int $formularioId
     * @param int $usuarioId Para verificar permisos
     * @return array Estadísticas del formulario
     * @throws InvalidArgumentException Si el formulario no existe o no pertenece al usuario
     */
    public function obtenerEstadisticas(int $formularioId, int $usuarioId): array
    {
        // Verificar permisos
        $formulario = $this->repository->obtenerPorId($formularioId, $usuarioId);
        
        if (!$formulario) {
            throw new InvalidArgumentException('Formulario no encontrado o no tienes permiso para acceder');
        }
        
        return $this->repository->obtenerEstadisticas($formularioId);
    }

    /**
     * Obtener formularios con sus estadísticas
     * 
     * Útil para el panel de administración
     * 
     * @param int $usuarioId
     * @return array Array de formularios con estadísticas incluidas
     */
    public function obtenerFormulariosConEstadisticas(int $usuarioId): array
    {
        $formularios = $this->repository->obtenerPorUsuario($usuarioId);
        
        $resultado = [];
        foreach ($formularios as $formulario) {
            $datos = $formulario->toArray();
            $datos['estadisticas'] = $this->repository->obtenerEstadisticas($formulario->getId());
            $datos['url_publica'] = $formulario->getUrlPublica();
            $resultado[] = $datos;
        }
        
        return $resultado;
    }

    /**
     * Verificar si un usuario tiene formularios activos
     * 
     * @param int $usuarioId
     * @return bool
     */
    public function tieneFormulariosActivos(int $usuarioId): bool
    {
        $formularios = $this->repository->obtenerPorUsuario($usuarioId);
        
        foreach ($formularios as $formulario) {
            if ($formulario->isActivo()) {
                return true;
            }
        }
        
        return false;
    }
}