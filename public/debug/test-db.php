<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=u329673490_reservabot;charset=utf8mb4', 'u329673490_reservabot', 'QFk[aas3f@');
    echo "Conexión exitosa";
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
