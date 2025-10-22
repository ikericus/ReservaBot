<?php
// public/src/domain/admin/AdminDomain.php

namespace ReservaBot\Domain\Admin;

class AdminDomain {
    private IAdminRepository $adminRepository;
    
    public function __construct(IAdminRepository $adminRepository) {
        $this->adminRepository = $adminRepository;
    }
    
    // =============== ACTIVIDAD ===============
    
    /**
     * Obtiene último acceso de usuarios
     */
    public function obtenerUltimosAccesos(int $limit = 20): array {
        return $this->adminRepository->obtenerUltimosAccesos($limit);
    }
    
    /**
     * Obtiene logins por fecha
     */
    public function obtenerLoginsHoy(): int {
        return $this->adminRepository->contarLoginsHoy();
    }
    
    /**
     * Obtiene usuarios activos en última hora
     */
    public function obtenerUsuariosActivosUltimaHora(): int {
        return $this->adminRepository->contarUsuariosActivosUltimaHora();
    }
    
    /**
     * Obtiene estadísticas de recursos solicitados
     */
    public function obtenerEstadisticasRecursos(): array {
        return $this->adminRepository->obtenerEstadisticasRecursos();
    }
    
    /**
     * Obtiene errores recientes del sistema
     */
    public function obtenerErroresRecientes(int $limit = 10): array {
        return $this->adminRepository->obtenerErroresRecientes($limit);
    }
    
    // =============== USUARIOS ===============
    
    /**
     * Obtiene últimos usuarios registrados
     */
    public function obtenerUltimosUsuarios(int $limit = 10): array {
        return $this->adminRepository->obtenerUltimosUsuarios($limit);
    }
    
    /**
     * Cuenta total de usuarios
     */
    public function contarTotalUsuarios(): int {
        return $this->adminRepository->contarTotalUsuarios();
    }
    
    /**
     * Cuenta usuarios por plan
     */
    public function contarUsuariosPorPlan(): array {
        return $this->adminRepository->contarUsuariosPorPlan();
    }
    
    /**
     * Obtiene usuarios más activos
     */
    public function obtenerUsuariosMasActivos(int $limit = 10): array {
        return $this->adminRepository->obtenerUsuariosMasActivos($limit);
    }
    
    /**
     * Obtiene tasa de retención (usuarios con reservas en últimos 30 días)
     */
    public function obtenerTasaRetencion(): array {
        $total = $this->contarTotalUsuarios();
        $activos = $this->adminRepository->contarUsuariosActivosUltimos30Dias();
        
        return [
            'total' => $total,
            'activos_30_dias' => $activos,
            'tasa_retencion' => $total > 0 ? round(($activos / $total) * 100, 2) : 0
        ];
    }
    
    // =============== RESERVAS ===============
    
    /**
     * Obtiene últimas reservas
     */
    public function obtenerUltimasReservas(int $limit = 10): array {
        return $this->adminRepository->obtenerUltimasReservas($limit);
    }
    
    /**
     * Cuenta total de reservas
     */
    public function contarTotalReservas(): int {
        return $this->adminRepository->contarTotalReservas();
    }
    
    /**
     * Reservas creadas hoy
     */
    public function obtenerReservasHoy(): int {
        return $this->adminRepository->contarReservasHoy();
    }
    
    /**
     * Obtiene volumen de reservas por día (últimos 30 días)
     */
    public function obtenerVolumenReservasPor30Dias(): array {
        return $this->adminRepository->obtenerVolumenReservasPor30Dias();
    }
    
    /**
     * Obtiene volumen de reservas por hora (hoy)
     */
    public function obtenerVolumenReservasPorHoraHoy(): array {
        return $this->adminRepository->obtenerVolumenReservasPorHoraHoy();
    }
    
    /**
     * Obtiene estadísticas de reservas
     */
    public function obtenerEstadisticasReservas(): array {
        return [
            'total' => $this->contarTotalReservas(),
            'hoy' => $this->obtenerReservasHoy(),
            'semana' => $this->adminRepository->contarReservasSemana(),
            'mes' => $this->adminRepository->contarReservasMes(),
            'promedio_diario' => $this->calcularPromedioDiarioReservas(),
            'estado_distribucion' => $this->adminRepository->obtenerDistribucionEstadoReservas()
        ];
    }
    
    /**
     * Calcula promedio de reservas por día (últimos 30 días)
     */
    private function calcularPromedioDiarioReservas(): float {
        $volumen = $this->obtenerVolumenReservasPor30Dias();
        $total = array_sum(array_column($volumen, 'cantidad'));
        return count($volumen) > 0 ? round($total / count($volumen), 2) : 0;
    }
    
    // =============== WHATSAPP ===============
    
    /**
     * Obtiene usuarios conectados a WhatsApp
     */
    public function obtenerUsuariosWhatsAppConectados(): int {
        return $this->adminRepository->contarUsuariosWhatsAppConectados();
    }
    
    /**
     * Obtiene usuarios registrados en WhatsApp (registrados alguna vez)
     */
    public function obtenerUsuariosWhatsAppRegistrados(): int {
        return $this->adminRepository->contarUsuariosWhatsAppRegistrados();
    }
    
    /**
     * Obtiene últimos usuarios conectados
     */
    public function obtenerUltimosUsuariosWhatsApp(int $limit = 10): array {
        return $this->adminRepository->obtenerUltimosUsuariosWhatsApp($limit);
    }
    
    /**
     * Obtiene estadísticas de mensajes
     */
    public function obtenerEstadisticasMensajes(): array {
        return [
            'total_enviados' => $this->adminRepository->contarMensajesEnviados(),
            'total_recibidos' => $this->adminRepository->contarMensajesRecibidos(),
            'hoy_enviados' => $this->adminRepository->contarMensajesEnviadosHoy(),
            'hoy_recibidos' => $this->adminRepository->contarMensajesRecibidosHoy(),
            'promedio_por_usuario' => $this->calcularPromedioMensajesPorUsuario()
        ];
    }
    
    /**
     * Obtiene volumen de mensajes por día (últimos 7 días)
     */
    public function obtenerVolumenMensajesPor7Dias(): array {
        return $this->adminRepository->obtenerVolumenMensajesPor7Dias();
    }
    
    /**
     * Obtiene número de teléfono más activo
     */
    public function obtenerNumerosMasActivos(int $limit = 5): array {
        return $this->adminRepository->obtenerNumerosMasActivos($limit);
    }
    
    /**
     * Calcula promedio de mensajes por usuario conectado
     */
    private function calcularPromedioMensajesPorUsuario(): float {
        $conectados = $this->obtenerUsuariosWhatsAppConectados();
        if ($conectados === 0) return 0;
        
        $total = $this->adminRepository->contarMensajesEnviados() + 
                 $this->adminRepository->contarMensajesRecibidos();
        
        return round($total / $conectados, 2);
    }
    
    /**
     * Obtiene salud del sistema WhatsApp
     */
    public function obtenerSaludWhatsApp(): array {
        $conectados = $this->obtenerUsuariosWhatsAppConectados();
        $registrados = $this->obtenerUsuariosWhatsAppRegistrados();
        
        return [
            'estado' => 'activo',
            'usuarios_conectados' => $conectados,
            'usuarios_registrados' => $registrados,
            'tasa_conexion' => $registrados > 0 ? round(($conectados / $registrados) * 100, 2) : 0,
            'mensajes_hoy' => $this->adminRepository->contarMensajesHoy(),
            'estado_servidor' => 'online' // Esto se verificaría con un healthcheck a Node.js
        ];
    }
    
    // =============== GENERAL ===============
    
    /**
     * Obtiene resumen general del sistema
     */
    public function obtenerResumenGeneral(): array {
        return [
            'usuarios_total' => $this->contarTotalUsuarios(),
            'usuarios_nuevos_hoy' => $this->adminRepository->contarNuevosUsuariosHoy(),
            'reservas_total' => $this->contarTotalReservas(),
            'reservas_hoy' => $this->obtenerReservasHoy(),
            'whatsapp_conectados' => $this->obtenerUsuariosWhatsAppConectados(),
            'mensajes_hoy' => $this->adminRepository->contarMensajesHoy(),
            'uptime' => 'N/A' // Se calcularía desde logs
        ];
    }
}
