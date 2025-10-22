<?php
// public/src/domain/admin/IAdminRepository.php

namespace ReservaBot\Domain\Admin;

interface IAdminRepository {
    // =============== ACTIVIDAD ===============
    
    public function obtenerUltimosAccesos(int $limit): array;
    public function contarLoginsHoy(): int;
    public function contarUsuariosActivosUltimaHora(): int;
    public function obtenerEstadisticasRecursos(): array;
    public function obtenerErroresRecientes(int $limit): array;
    
    // =============== USUARIOS ===============
    
    public function obtenerUltimosUsuarios(int $limit): array;
    public function contarTotalUsuarios(): int;
    public function contarUsuariosPorPlan(): array;
    public function obtenerUsuariosMasActivos(int $limit): array;
    public function contarUsuariosActivosUltimos30Dias(): int;
    public function contarNuevosUsuariosHoy(): int;
    
    // =============== RESERVAS ===============
    
    public function obtenerUltimasReservas(int $limit): array;
    public function contarTotalReservas(): int;
    public function contarReservasHoy(): int;
    public function contarReservasSemana(): int;
    public function contarReservasMes(): int;
    public function obtenerVolumenReservasPor30Dias(): array;
    public function obtenerVolumenReservasPorHoraHoy(): array;
    public function obtenerDistribucionEstadoReservas(): array;
    
    // =============== WHATSAPP ===============
    
    public function contarUsuariosWhatsAppConectados(): int;
    public function contarUsuariosWhatsAppRegistrados(): int;
    public function obtenerUltimosUsuariosWhatsApp(int $limit): array;
    public function contarMensajesEnviados(): int;
    public function contarMensajesRecibidos(): int;
    public function contarMensajesEnviadosHoy(): int;
    public function contarMensajesRecibidosHoy(): int;
    public function contarMensajesHoy(): int;
    public function obtenerVolumenMensajesPor7Dias(): array;
    public function obtenerNumerosMasActivos(int $limit): array;
}
