<?php
require_once 'database.php';

class Vehiculos {
    private $conn;
    private $table_name = "vehiculos";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function agregarVehiculo($placa, $modelo) {
        try {
            // Verificar si la placa ya existe
            $query = "SELECT id FROM " . $this->table_name . " WHERE placa = :placa";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":placa", $placa);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                return "La placa ya está registrada";
            }

            $query = "INSERT INTO " . $this->table_name . " (placa, modelo) VALUES (:placa, :modelo)";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":placa", $placa);
            $stmt->bindParam(":modelo", $modelo);
            
            if($stmt->execute()) {
                return "Vehículo registrado exitosamente";
            }
            return "Error al registrar el vehículo";
        } catch(PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function obtenerVehiculos() {
        try {
            $query = "SELECT v.*, r.nombre as nombre_ruta 
                     FROM " . $this->table_name . " v 
                     LEFT JOIN rutas r ON v.ruta_id = r.id 
                     ORDER BY v.fecha_registro DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function asignarRuta($vehiculo_id, $ruta_id) {
        try {
            // Verificar si la ruta ya está asignada a otro vehículo
            $query = "SELECT id FROM " . $this->table_name . " WHERE ruta_id = :ruta_id AND id != :vehiculo_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":ruta_id", $ruta_id);
            $stmt->bindParam(":vehiculo_id", $vehiculo_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return "Esta ruta ya está asignada a otro vehículo.";
            }

            $query = "UPDATE " . $this->table_name . " SET ruta_id = :ruta_id WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":ruta_id", $ruta_id);
            $stmt->bindParam(":id", $vehiculo_id);

            if($stmt->execute()) {
                return "Ruta asignada exitosamente";
            }
            return "Error al asignar la ruta";
        } catch(PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function eliminarVehiculo($id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function obtenerVehiculoPorRuta($ruta_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE ruta_id = :ruta_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":ruta_id", $ruta_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return null;
        }
    }
}
?> 