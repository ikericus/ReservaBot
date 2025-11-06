<?php
// domain/usuario/UsuarioDomain.php

namespace ReservaBot\Domain\Usuario;

use ReservaBot\Domain\Configuracion\ConfiguracionDomain;
use ReservaBot\Domain\Email\IEmailRepository;
use ReservaBot\Domain\Email\EmailTemplates;

class UsuarioDomain {
    private IUsuarioRepository $repository;
    private ConfiguracionDomain $configuracionDomain;
    private IEmailRepository $emailRepository;
    private EmailTemplates $emailTemplates;
    
    public function __construct(
        IUsuarioRepository $repository,
        ConfiguracionDomain $configuracionDomain,
        IEmailRepository $emailRepository
    ) {
        $this->repository = $repository;
        $this->configuracionDomain = $configuracionDomain;
        $this->emailRepository = $emailRepository;
        $this->emailTemplates = new EmailTemplates();
    }
    
    /**
     * Autentica usuario con email y password
     */
    public function autenticar(string $email, string $password): ?Usuario {
        $email = trim(strtolower($email));
        
        $usuario = $this->repository->obtenerPorEmail($email);
        
        if (!$usuario) {
            throw new \DomainException('Usuario no encontrado');
        }
        
        if (!$usuario->isActivo()) {
            throw new \DomainException('Usuario inactivo');
        }
        
        if (!$usuario->verificarPassword($password)) {
            throw new \DomainException('Contraseña incorrecta');
        }
        
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
        string $plan = 'basico'
    ): Usuario {
        // Validaciones
        $nombre = trim($nombre);
        $telefono = !empty($telefono) ? trim($telefono) : '';
        $negocio = trim($negocio);
        
        if (empty($nombre)) {
            throw new \InvalidArgumentException('El nombre es obligatorio');
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email inválido');
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
        
        // Crear usuario (sin campo negocio)
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $usuario = $this->repository->crear(
            $nombre,
            $email,
            $telefono,
            $passwordHash,
            $plan
        );
        
        // Crear configuraciones iniciales (incluyendo empresa_nombre)
        $this->crearConfiguracionesIniciales($usuario->getId(), $negocio, $telefono);
        
        return $usuario;
    }
    
    /**
     * Actualiza perfil de usuario
     * Nota: El nombre del negocio se actualiza en ConfiguracionDomain, no aquí
     */
    public function actualizarPerfil(
        int $id,
        string $nombre,
        string $email,
        string $telefono
    ): void {
        $nombre = trim($nombre);
        $email = trim(strtolower($email));
        $telefono = trim($telefono);
        
        if (empty($nombre) || empty($email)) {
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
            'telefono' => $telefono
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
     * Solicita restablecimiento de contraseña (envía email con token)
     */
    public function solicitarRestablecimientoContrasena(string $email): bool {
        $email = trim(strtolower($email));
        
        $usuario = $this->repository->obtenerPorEmail($email);
        
        // Por seguridad, no revelamos si el usuario existe
        if (!$usuario || !$usuario->isActivo()) {
            // Retornamos true para no revelar si el email existe
            return true;
        }
        
        $token = bin2hex(random_bytes(32));
        $expiry = new \DateTime('+1 hour');
        
        $this->repository->establecerTokenRestablecimiento($usuario->getId(), $token, $expiry);
        
        // Generar email
        $emailData = $this->emailTemplates->restablecimientoContrasena($usuario->getNombre(), $token);
        
        // Enviar
        return $this->emailRepository->enviar(
            $usuario->getEmail(),
            $emailData['asunto'],
            $emailData['cuerpo_texto'],
            $emailData['cuerpo_html']
        );
    }
    
    /**
     * Valida token de restablecimiento y retorna datos del usuario
     */
    public function validarTokenRestablecimiento(string $token): ?array {
        $usuario = $this->repository->obtenerPorTokenRestablecimiento($token);
        
        if (!$usuario) {
            return null;
        }
        
        if (!$usuario->tokenRestablecimientoValido()) {
            return null;
        }
        
        return [
            'id' => $usuario->getId(),
            'nombre' => $usuario->getNombre(),
            'email' => $usuario->getEmail()
        ];
    }
    
    /**
     * Restablece contraseña con token
     */
    public function restablecerContrasena(string $token, string $passwordNueva): void {
        if (strlen($passwordNueva) < 6) {
            throw new \InvalidArgumentException('La contraseña debe tener al menos 6 caracteres');
        }
        
        $datosUsuario = $this->validarTokenRestablecimiento($token);
        
        if (!$datosUsuario) {
            throw new \DomainException('Token inválido o expirado');
        }
        
        $passwordHash = password_hash($passwordNueva, PASSWORD_DEFAULT);
        $this->repository->actualizarPassword($datosUsuario['id'], $passwordHash);
        $this->repository->limpiarTokenRestablecimiento($datosUsuario['id']);
    }
    
    // ========================================================================
    // MÉTODOS DE GESTIÓN DE USUARIOS
    // ========================================================================
    
    /**
     * Obtiene usuario por ID
     */
    public function obtenerPorId(int $id): ?Usuario {
        return $this->repository->obtenerPorId($id);
    }
    
    /**
     * Verifica si un usuario tiene permisos de administrador
     */
    public function esAdministrador(int $usuarioId): bool {
        return $this->repository->esAdmin($usuarioId);
    }

    // ========================================================================
    // VERIFICACIÓN DE EMAIL
    // ========================================================================

    /**
     * Inicia proceso de verificación de correo
     */
    public function iniciarVerificacionCorreo(int $usuarioId): bool {
        $usuario = $this->repository->obtenerPorId($usuarioId);
        
        if (!$usuario) {
            throw new \DomainException('Usuario no encontrado');
        }
        
        // Generar token
        $token = bin2hex(random_bytes(32));
                
        // Guardar en BD
        $this->repository->establecerVerificacionToken($usuarioId, $token);
        
        // Generar contenido del email
        $email = $this->emailTemplates->verificacionEmail($usuario->getNombre(), $token);
        
        // Enviar usando repositorio genérico
        return $this->emailRepository->enviar(
            $usuario->getEmail(),
            $email['asunto'],
            $email['cuerpo_texto'],
            $email['cuerpo_html']
        );
    }

    /**
     * Verifica el token y envía bienvenida
     */
    public function verificarCorreo(string $token): bool {
        $usuario = $this->repository->obtenerPorVerificacionToken($token);
        
        if (!$usuario) {
            throw new \DomainException('Token inválido');
        }
        
        if (!$usuario->tokenVerificacionValido()) {
            throw new \DomainException('Token expirado');
        }
        
        // Marcar como verificado
        $this->repository->marcarEmailVerificado($usuario->getId());
        
        // Generar email de bienvenida
        $email = $this->emailTemplates->bienvenida($usuario->getNombre());
        
        // Enviar
        $this->emailRepository->enviar(
            $usuario->getEmail(),
            $email['asunto'],
            $email['cuerpo_texto'],
            $email['cuerpo_html']
        );
        
        return true;
    }

    /**
     * Actualiza el plan de un usuario
     */
    public function actualizarPlan(int $id, string $plan): void {
        // Validar que el plan sea válido
        $planesValidos = ['basico', 'profesional', 'avanzado'];
        if (!in_array($plan, $planesValidos)) {
            throw new \InvalidArgumentException('Plan no válido');
        }
        
        // Verificar que el usuario existe
        $usuario = $this->repository->obtenerPorId($id);
        if (!$usuario) {
            throw new \DomainException('Usuario no encontrado');
        }
        
        // Actualizar el plan
        $this->repository->actualizar($id, [
            'plan' => $plan
        ]);
    }

    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================

    /**
     * Crea configuraciones iniciales para un nuevo usuario
     */
    private function crearConfiguracionesIniciales(int $usuarioId, string $negocio, string $telefono): void {
        $configuracionesIniciales = [
            'app_name' => $negocio,
            'empresa_nombre' => $negocio,
            'empresa_telefono' => !empty($telefono) ? $telefono : '',
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