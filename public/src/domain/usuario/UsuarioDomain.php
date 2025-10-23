<?php
// src/domain/usuario/UsuarioDomain.php

namespace ReservaBot\Domain\Usuario;

use ReservaBot\Domain\Configuracion\ConfiguracionDomain;

class UsuarioDomain {
    private IUsuarioRepository $repository;
    private ConfiguracionDomain $configuracionDomain;
    
    public function __construct(
        IUsuarioRepository $repository,
        ConfiguracionDomain $configuracionDomain
    ) {
        $this->repository = $repository;
        $this->configuracionDomain = $configuracionDomain;
    }
    
    /**
     * Autentica usuario con email y password
     */
    public function autenticar(string $email, string $password): ?Usuario {
        $email = trim(strtolower($email));
        
        logMessage("UsuarioDomain.php: Autenticando usuario con email: " . $email);

        $usuario = $this->repository->obtenerPorEmail($email);
        
        if (!$usuario) {
            logMessage("UsuarioDomain.php: Usuario no encontrado con email: " . $email);
            throw new \DomainException('Usuario no encontrado');
        }
        
        if (!$usuario->isActivo()) {
            logMessage("UsuarioDomain.php: Intento de login de usuario inactivo: " . $usuario->getEmail());
            throw new \DomainException('Usuario inactivo');
        }
        
        if (!$usuario->verificarPassword($password)) {
            logMessage("UsuarioDomain.php: Fallo de autenticación para el usuario: " . $usuario->getEmail());
            throw new \DomainException('Contraseña incorrecta');
        }
        logMessage("UsuarioDomain.php: Autenticación exitosa para el usuario: " . $usuario->getEmail());
        return $usuario;
    }
    
    /**
     * Registra nuevo usuario
     */
    public function registrar(
        string $nombre,
        string $email,
        string $telefono,
        string $negocio,
        string $password,
        string $plan = 'gratis'
    ): Usuario {
        // Validaciones
        $nombre = trim($nombre);
        $email = trim(strtolower($email));
        $telefono = trim($telefono);
        $negocio = trim($negocio);
        
        if (empty($nombre)) {
            throw new \InvalidArgumentException('El nombre es obligatorio');
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email inválido');
        }
        
        if (empty($telefono)) {
            throw new \InvalidArgumentException('El teléfono es obligatorio');
        }
        
        if (empty($negocio)) {
            throw new \InvalidArgumentException('El nombre del negocio es obligatorio');
        }
        
        if (strlen($password) < 6) {
            throw new \InvalidArgumentException('La contraseña debe tener al menos 6 caracteres');
        }
        
        // Verificar email único
        if ($this->repository->emailExiste($email)) {
            throw new \DomainException('Ya existe una cuenta con este email');
        }
        
        // Crear usuario
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $usuario = $this->repository->crear(
            $nombre,
            $email,
            $telefono,
            $negocio,
            $passwordHash,
            $plan
        );
        
        // Crear configuraciones iniciales
        $this->crearConfiguracionesIniciales($usuario->getId(), $negocio, $telefono);
        
        return $usuario;
    }
    
    /**
     * Actualiza perfil de usuario
     */
    public function actualizarPerfil(
        int $id,
        string $nombre,
        string $email,
        string $telefono,
        string $negocio
    ): void {
        $nombre = trim($nombre);
        $email = trim(strtolower($email));
        $telefono = trim($telefono);
        $negocio = trim($negocio);
        
        if (empty($nombre) || empty($email) || empty($telefono) || empty($negocio)) {
            throw new \InvalidArgumentException('Todos los campos son obligatorios');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email inválido');
        }
        
        // Verificar email único (excluyendo el usuario actual)
        if ($this->repository->emailExiste($email, $id)) {
            throw new \DomainException('Este email ya está siendo usado por otro usuario');
        }
        
        $this->repository->actualizar($id, [
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'negocio' => $negocio
        ]);
    }
    
    /**
     * Cambia contraseña del usuario
     */
    public function cambiarPassword(
        int $id,
        string $passwordActual,
        string $passwordNueva
    ): void {
        if (strlen($passwordNueva) < 6) {
            throw new \InvalidArgumentException('La nueva contraseña debe tener al menos 6 caracteres');
        }
        
        $usuario = $this->repository->obtenerPorId($id);
        if (!$usuario) {
            throw new \DomainException('Usuario no encontrado');
        }
        
        if (!$usuario->verificarPassword($passwordActual)) {
            throw new \DomainException('La contraseña actual es incorrecta');
        }
        
        $passwordHash = password_hash($passwordNueva, PASSWORD_DEFAULT);
        $this->repository->actualizarPassword($id, $passwordHash);
    }
    
    /**
     * Solicita reset de contraseña
     */
    public function solicitarResetPassword(string $email): ?Usuario {
        $email = trim(strtolower($email));
        
        $usuario = $this->repository->obtenerPorEmail($email);
        
        if (!$usuario || !$usuario->isActivo()) {
            return null; // Por seguridad, no revelar si existe
        }
        
        $token = bin2hex(random_bytes(32));
        $expiry = new \DateTime('+1 hour');
        
        $this->repository->establecerResetToken($usuario->getId(), $token, $expiry);
        
        // Devolver usuario actualizado
        return $this->repository->obtenerPorId($usuario->getId());
    }
    
    /**
     * Verifica token de reset
     */
    public function verificarResetToken(string $token): ?Usuario {
        $usuario = $this->repository->obtenerPorResetToken($token);
        
        if (!$usuario) {
            return null;
        }
        
        if (!$usuario->tokenResetValido()) {
            return null;
        }
        
        return $usuario;
    }
    
    /**
     * Resetea contraseña con token
     */
    public function resetearPassword(string $token, string $passwordNueva): void {
        if (strlen($passwordNueva) < 6) {
            throw new \InvalidArgumentException('La contraseña debe tener al menos 6 caracteres');
        }
        
        $usuario = $this->verificarResetToken($token);
        
        if (!$usuario) {
            throw new \DomainException('Token inválido o expirado');
        }
        
        $passwordHash = password_hash($passwordNueva, PASSWORD_DEFAULT);
        $this->repository->actualizarPassword($usuario->getId(), $passwordHash);
        $this->repository->limpiarResetToken($usuario->getId());
    }
    
    /**
     * Obtiene usuario por ID
     */
    public function obtenerPorId(int $id): ?Usuario {
        return $this->repository->obtenerPorId($id);
    }
    
    /**
     * Crea configuraciones iniciales para un nuevo usuario
     */
    private function crearConfiguracionesIniciales(int $usuarioId, string $negocio, string $telefono): void {
        $configuracionesIniciales = [
            'app_name' => $negocio,
            'empresa_nombre' => $negocio,
            'empresa_telefono' => $telefono,
            'modo_aceptacion' => 'manual',
            'intervalo_reservas' => '30',
            'horario_lun' => 'true|[{"inicio":"09:00","fin":"18:00"}]',
            'horario_mar' => 'true|[{"inicio":"09:00","fin":"18:00"}]',
            'horario_mie' => 'true|[{"inicio":"09:00","fin":"18:00"}]',
            'horario_jue' => 'true|[{"inicio":"09:00","fin":"18:00"}]',
            'horario_vie' => 'true|[{"inicio":"09:00","fin":"18:00"}]',
            'horario_sab' => 'true|[{"inicio":"10:00","fin":"14:00"}]',
            'horario_dom' => 'false|[]'
        ];
        
        $this->configuracionDomain->actualizarMultiples($configuracionesIniciales, $usuarioId);
    }
}