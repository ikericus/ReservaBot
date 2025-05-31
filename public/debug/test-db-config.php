<?php

require_once '../includes/db-config.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM reservas WHERE estado = 'pendiente' ORDER BY fecha, hora");
    $stmt->execute();
    $reservasPendientes = $stmt->fetchAll();    
    echo('Obtenidas reservas: ' . count($reservasPendientes));
} catch (\PDOException $e) {
    error_log('Error obteniendo reservas: ' . $e->getMessage());
}