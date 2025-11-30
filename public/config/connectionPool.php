<?php
// config/connectionPool.php

namespace ReservaBot\Config;

use PDO;
use PDOException;

class ConnectionPool {
    private ?PDO $pdo = null;
    private array $config;
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function getConnection(): PDO {
        if ($this->pdo === null || !$this->isAlive()) {
            $this->connect();
        }
        return $this->pdo;
    }
    
    private function isAlive(): bool {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function connect(): void {
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']};charset={$this->config['charset']}";
        $this->pdo = new PDO(
            $dsn, 
            $this->config['username'], 
            $this->config['password'], 
            $this->config['options']
        );
    }
    
    // Métodos proxy para usar como PDO normal
    public function prepare(string $query, array $options = []): \PDOStatement {
        return $this->getConnection()->prepare($query, $options);
    }
    
    public function query(string $query, ?int $fetchMode = null): \PDOStatement|false {
        return $this->getConnection()->query($query, $fetchMode);
    }
    
    public function lastInsertId(): int {
        $sql = "SELECT LAST_INSERT_ID() as id";
        $stmt = $this->prepare($sql); 
        $stmt->execute();
        $id = (int) $stmt->fetchColumn();
        
        if ($id === 0) {
            throw new \RuntimeException('No se pudo obtener el último ID insertado');
        }
        
        return $id; 
    }
    
    public function beginTransaction(): bool {
        return $this->getConnection()->beginTransaction();
    }
    
    public function commit(): bool {
        return $this->getConnection()->commit();
    }
    
    public function rollBack(): bool {
        return $this->getConnection()->rollBack();
    }
    
    public function inTransaction(): bool {
        return $this->getConnection()->inTransaction();
    }
}