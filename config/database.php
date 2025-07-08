<?php
class Database {
    private $host = "localhost";
    private $db_name = "sar_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
            
            // Verificar la estructura de la tabla rutas
            $query = "SHOW CREATE TABLE rutas";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Log de la estructura
            file_put_contents('debug_db.log', "\n--- ESTRUCTURA TABLA RUTAS ---\n" . print_r($result, true) . "\n", FILE_APPEND);
            
        } catch(PDOException $e) {
            file_put_contents('debug_db.log', "\n--- ERROR CONEXIÓN DB ---\n" . $e->getMessage() . "\n", FILE_APPEND);
            throw new Exception("Error de conexión: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?> 