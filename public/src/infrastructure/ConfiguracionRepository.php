<?php
// public/src/infrastructure/ConfiguracionRepository.php

namespace ReservaBot\Infrastructure;

use ReservaBot\Domain\Configuracion\IConfiguracionRepository;
use DateTime;
use PDO;

class ConfiguracionRepository implements IConfiguracionRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function estaDisponible(DateTime $fecha, string $hora, int $usuarioId): bool {
        $horarioDia = $this->obtenerHorarioDiaInterno($fecha, $usuarioId);
        
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
        $clave = "horario_{$dia}";
        
        $stmt = $this->pdo->prepare(
            "SELECT valor FROM configuraciones WHERE clave = ? AND usuario_id = ?"
        );
        $stmt->execute([$clave, $usuarioId]);
        $valor = $stmt->fetchColumn();
        
        if (!$valor) {
            $esFinDeSemana = in_array($dia, ['sab', 'dom']);
            $valor = $esFinDeSemana 
                ? 'false|[]' 
                : 'true|[{"inicio":"09:00","fin":"18:00"}]';
        }
        
        return $this->parseHorarioConfig($valor);
    }
    
    public function obtenerInformacionNegocio(int $usuarioId): array {
        $sql = "SELECT clave, valor FROM configuraciones 
                WHERE usuario_id = ? 
                AND clave IN ('nombre_negocio', 'tipo_negocio', 'direccion', 'telefono', 'email')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        
        $info = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $info[$row['clave']] = $row['valor'];
        }
        
        return $info;
    }
    
    public function obtenerIntervalo(int $usuarioId): int {
        $stmt = $this->pdo->prepare(
            "SELECT valor FROM configuraciones WHERE clave = 'intervalo' AND usuario_id = ?"
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
                        ['inicio' => $tiempos[0], 'fin' => $tiempos[1]]
                    ];
                }
            }
        }
        
        if (empty($ventanas)) {
            $ventanas = [['inicio' => '09:00', 'fin' => '18:00']];
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