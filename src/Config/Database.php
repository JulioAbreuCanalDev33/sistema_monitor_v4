<?php

namespace App\Config;
Use PDOException;
Use PDO;


class Database {
    private $dbFile = __DIR__ . '/sistema.db'; 
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            // Conectar ao arquivo SQLite (se não existir, será criado)
            $this->conn = new PDO("sqlite:" . $this->dbFile);
            
            // Ativar erros como exceções
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Usar UTF-8
            $this->conn->exec("PRAGMA encoding = 'UTF-8';");
        } catch(PDOException $exception) {
            echo "Erro de conexão: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

?>

