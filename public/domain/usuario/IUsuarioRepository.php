<?php
// domain/usuario/IUsuarioRepository.php

namespace ReservaBot\Domain\Usuario;

interface IUsuarioRepository {
    /**
     * Busca usuario por email
     */
    public function obtenerPorEmail(string $email): ?Usuario;
    
    /**
     * Busca usuario por ID
     */
    public function obtenerPorId(int $id): ?Usuario;
    
    /**
     * Busca usuario por token de reset
     */
    public function obtenerPorTokenRestablecimiento(string $token): ?Usuario;

     /**
     * Busca usuario por token de verificación
     */
    public function obtenerPorVerificacionToken(string $token): ?Usuario;
    
    /**
     * Crea nuevo usuario
     */
    public function crear(string $nombre, string $email, string $telefono, string $passwordHash, string $plan = 'basico'): Usuario;
    
    /**
     * Actualiza datos del usuario
     */
    public function actualizar(int $id, array $datos): void;
    
    /**
     * Actualiza contraseña
     */
    public function actualizarPassword(int $id, string $passwordHash): void;
    
    /**
     * Establece token de reset
     */
    public function establecerTokenRestablecimiento(int $id, string $token, \DateTime $expiry): void;
    
    /**
     * Limpia token de reset
     */
    public function limpiarTokenRestablecimiento(int $id): void;
    
    /**
     * Establece token de verificación
     */
    public function establecerVerificacionToken(int $id, string $token): void;
    
    /**
     * Marca email como verificado
     */
    public function marcarEmailVerificado(int $id): void;

    /**
     * Verifica si email existe
     */
    public function emailExiste(string $email, ?int $excluirId = null): bool;

    /**
     * Verifica si el usuario es administrador
     */
    public function esAdmin(int $usuarioId): bool;
}