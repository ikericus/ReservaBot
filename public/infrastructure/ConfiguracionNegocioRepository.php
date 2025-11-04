<?php
// infrastructure/ConfiguracionNegocioRepository.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\Configuracion\IConfiguracionNegocioRepository;
use DateTime;
use PDO;

class ConfiguracionNegocioRepository implements IConfiguracionNegocioRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function obtenerTodas(int $usuarioId): array {
        // Usa tabla configuraciones_usuario
        $sql = "SELECT clave, valor FROM configuraciones_usuario WHERE usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['clave']] = $row['valor'];
        }
        
        return $config;
    }
    
    public function obtener(string $clave, int $usuarioId): ?string {
        $sql = "SELECT valor FROM configuraciones_usuario 
                WHERE clave = ? AND usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$clave, $usuarioId]);
        
        $valor = $stmt->fetchColumn();
        return $valor !== false ? $valor : null;
    }
    
    /**
     * Alias de obtener() para mayor claridad en EmailTemplates
     */
    public function obtenerValor(string $clave, int $usuarioId): ?string {
        return $this->obtener($clave, $usuarioId);
    }
    
    public function actualizar(string $clave, string $valor, int $usuarioId): void {
        $sql = "INSERT INTO configuraciones_usuario (usuario_id, clave, valor) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE valor = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId, $clave, $valor, $valor]);
    }
    
    public function actualizarVarias(array $configuraciones, int $usuarioId): void {
        $this->pdo->beginTransaction();
        
        try {
            foreach ($configuraciones as $clave => $valor) {
                $this->actualizar($clave, $valor, $usuarioId);
            }
            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
     public function estaDisponible(DateTime $fecha, string $hora, int $usuarioId): bool {
        $horarioDia = $this->obtenerHorarioDiaInterno($fecha, $usuarioId);
        debug_log("Verificando disponibilidad para {$fecha->format('Y-m-d')} a las {$hora} - Horario del día: " . json_encode($horarioDia));
        if (!$horarioDia['activo']) {
            return false;
        }
        
        return $this->horaEnVentanas($hora, $horarioDia['ventanas']);
    }
    
    public function obtenerHorasDelDia(DateTime $fecha, int $usuarioId): array {
        $horarioDia = $this->obtenerHorarioDiaInterno($fecha, $usuarioId);
        
        if (!$horarioDia['activo']) {
            return [];
        }
        
        $intervalo = $this->obtenerIntervalo($usuarioId);
        
        return $this->generarHorasDisponibles($horarioDia['ventanas'], $intervalo);
    }
    
    public function obtenerHorarioDia(string $dia, int $usuarioId): array {
        // Primero intentar obtener el mapeo de semana y tipos de día
        $mapeoSemanaJson = $this->obtener('mapeo_semana', $usuarioId);
        $tiposDiaJson = $this->obtener('tipos_dia', $usuarioId);
        
        if ($mapeoSemanaJson && $tiposDiaJson) {
            $mapeoSemana = json_decode($mapeoSemanaJson, true);
            $tiposDia = json_decode($tiposDiaJson, true);
            
            if (isset($mapeoSemana[$dia]) && isset($tiposDia[$mapeoSemana[$dia]])) {
                $tipoDia = $tiposDia[$mapeoSemana[$dia]];
                $ventanas = $tipoDia['ventanas'] ?? [];
                
                // Asegurar que todas las ventanas tengan capacidad
                foreach ($ventanas as &$ventana) {
                    if (!isset($ventana['capacidad'])) {
                        $ventana['capacidad'] = 1;
                    }
                }
                
                return [
                    'activo' => !empty($ventanas),
                    'ventanas' => $ventanas
                ];
            }
        }
        
        // Fallback al sistema antiguo
        $clave = "horario_{$dia}";
        $valor = $this->obtener($clave, $usuarioId);
        
        if (!$valor) {
            $esFinDeSemana = in_array($dia, ['sab', 'dom']);
            $valor = $esFinDeSemana 
                ? 'false|[]' 
                : 'true|[{"inicio":"09:00","fin":"18:00","capacidad":1}]';
        }
        
        return $this->parseHorarioConfig($valor);
    }
    
    public function obtenerIntervalo(int $usuarioId): int {
        $stmt = $this->pdo->prepare(
            "SELECT valor FROM configuraciones_usuario WHERE clave = 'intervalo_reservas' AND usuario_id = ?"
        );
        $stmt->execute([$usuarioId]);
        $valor = $stmt->fetchColumn();
        
        return $valor ? (int)$valor : 30;
    }
    
    private function obtenerHorarioDiaInterno(DateTime $fecha, int $usuarioId): array {
        $diasMap = [
            1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue',
            5 => 'vie', 6 => 'sab', 0 => 'dom'
        ];
        
        $diaSemana = (int)$fecha->format('w');
        $dia = $diasMap[$diaSemana];
        
        return $this->obtenerHorarioDia($dia, $usuarioId);
    }
    
    private function parseHorarioConfig(string $config): array {
        $parts = explode('|', $config, 2);
        $activo = $parts[0] === 'true';
        $ventanas = [];
        
        if ($activo && isset($parts[1])) {
            $ventanasJson = json_decode($parts[1], true);
            
            if ($ventanasJson && is_array($ventanasJson)) {
                $ventanas = $ventanasJson;
            } else {
                $tiempos = explode('|', $parts[1]);
                if (count($tiempos) >= 2) {
                    $ventanas = [
                        ['inicio' => $tiempos[0], 'fin' => $tiempos[1], 'capacidad' => 1]
                    ];
                }
            }
        }
        
        if (empty($ventanas)) {
            $ventanas = [['inicio' => '09:00', 'fin' => '18:00', 'capacidad' => 1]];
        }
        
        // Asegurar que todas las ventanas tengan capacidad
        foreach ($ventanas as &$ventana) {
            if (!isset($ventana['capacidad'])) {
                $ventana['capacidad'] = 1;
            }
        }
        
        return [
            'activo' => $activo,
            'ventanas' => $ventanas
        ];
    }
    
    private function horaEnVentanas(string $hora, array $ventanas): bool {
        foreach ($ventanas as $ventana) {
            if ($hora >= $ventana['inicio'] && $hora < $ventana['fin']) {
                return true;
            }
        }
        return false;
    }
    
    private function generarHorasDisponibles(array $ventanas, int $intervalo): array {
        $horas = [];
        
        foreach ($ventanas as $ventana) {
            $current = strtotime($ventana['inicio']);
            $end = strtotime($ventana['fin']);
            
            while ($current < $end) {
                $hora = date('H:i', $current);
                if (!in_array($hora, $horas)) {
                    $horas[] = $hora;
                }
                $current += $intervalo * 60;
            }
        }
        
        sort($horas);
        return $horas;
    }
}