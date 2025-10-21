<?php
// tests/SQLitePDOWrapper.php

/**
 * PDO Wrapper que convierte queries MySQL a SQLite automáticamente
 */
class SQLitePDOWrapper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Convierte queries de MySQL a sintaxis SQLite
     */
    private function convertQuery($query) {
        // TIME_FORMAT -> SUBSTR
        $query = preg_replace(
            "/TIME_FORMAT\s*\(\s*([^,]+)\s*,\s*['\"]%H:%i['\"]\s*\)/i",
            "SUBSTR($1, 1, 5)",
            $query
        );
        
        // DATE_FORMAT con %Y-%m-%d -> simplemente usa la columna (ya está en ese formato)
        $query = preg_replace(
            "/DATE_FORMAT\s*\(\s*([^,]+)\s*,\s*['\"]%Y-%m-%d['\"]\s*\)/i",
            "$1",
            $query
        );
        
        // DATE_FORMAT con %d/%m/%Y -> usar strftime
        $query = preg_replace(
            "/DATE_FORMAT\s*\(\s*([^,]+)\s*,\s*['\"]%d\/%m\/%Y['\"]\s*\)/i",
            "STRFTIME('%d/%m/%Y', $1)",
            $query
        );
        
        return $query;
    }
    
    /**
     * Prepara y ejecuta una query convertida
     */
    public function prepare($query) {
        $convertedQuery = $this->convertQuery($query);
        return new SQLitePDOStatementWrapper(
            $this->pdo->prepare($convertedQuery),
            $convertedQuery
        );
    }
    
    /**
     * Ejecuta una query convertida directamente
     */
    public function exec($query) {
        $convertedQuery = $this->convertQuery($query);
        return $this->pdo->exec($convertedQuery);
    }
    
    /**
     * Ejecuta una query convertida y retorna resultados
     */
    public function query($query, $mode = null) {
        $convertedQuery = $this->convertQuery($query);
        if ($mode !== null) {
            return $this->pdo->query($convertedQuery, $mode);
        }
        return $this->pdo->query($convertedQuery);
    }
    
    /**
     * Obtiene el último ID insertado
     */
    public function lastInsertId($name = null) {
        return $this->pdo->lastInsertId($name);
    }
    
    /**
     * Delega otros métodos al PDO real
     */
    public function __call($name, $arguments) {
        return call_user_func_array([$this->pdo, $name], $arguments);
    }
    
    /**
     * Delega propiedades al PDO real
     */
    public function __get($name) {
        return $this->pdo->$name;
    }
    
    public function __set($name, $value) {
        $this->pdo->$name = $value;
    }
}

/**
 * Wrapper para PDOStatement
 */
class SQLitePDOStatementWrapper {
    private $stmt;
    private $query;
    
    public function __construct($stmt, $query = '') {
        $this->stmt = $stmt;
        $this->query = $query;
    }
    
    public function execute($params = null) {
        return $this->stmt->execute($params);
    }
    
    public function fetch($mode = null) {
        if ($mode !== null) {
            return $this->stmt->fetch($mode);
        }
        return $this->stmt->fetch();
    }
    
    public function fetchAll($mode = null) {
        if ($mode !== null) {
            return $this->stmt->fetchAll($mode);
        }
        return $this->stmt->fetchAll();
    }
    
    public function fetchColumn($colno = 0) {
        return $this->stmt->fetchColumn($colno);
    }
    
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    public function __call($name, $arguments) {
        return call_user_func_array([$this->stmt, $name], $arguments);
    }
}
