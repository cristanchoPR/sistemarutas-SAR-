<?php
require_once 'database.php';

class Rutas {
    private $db;
    private $table = 'rutas';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function agregarRuta($nombre, $inicio_ruta, $fin_ruta) {
        try {
            // Validar que los campos no estén vacíos
            if (empty($nombre) || empty($inicio_ruta) || empty($fin_ruta)) {
                return ['success' => false, 'message' => 'El nombre, inicio y fin de ruta son requeridos'];
            }

            // Verificar si ya existe una ruta con el mismo nombre
            $query = "SELECT id FROM " . $this->table . " WHERE nombre = :nombre";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Ya existe una ruta con ese nombre'];
            }

            // Insertar la nueva ruta
            $query = "INSERT INTO " . $this->table . " (nombre, inicio_ruta, fin_ruta, fecha_registro) VALUES (:nombre, :inicio_ruta, :fin_ruta, NOW())";
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":inicio_ruta", $inicio_ruta);
            $stmt->bindParam(":fin_ruta", $fin_ruta);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Ruta agregada exitosamente'];
            }
            
            return ['success' => false, 'message' => 'Error al agregar la ruta'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()];
        }
    }

    public function obtenerRutas() {
        try {
            $query = "SELECT r.*, u.nombre as lider_nombre 
                     FROM " . $this->table . " r 
                     LEFT JOIN usuarios u ON r.lider_id = u.id 
                     ORDER BY r.fecha_registro DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function obtenerRutasPorLider($lider_id) {
        try {
            $query = "SELECT r.*, u.nombre as lider_nombre 
                     FROM " . $this->table . " r 
                     LEFT JOIN usuarios u ON r.lider_id = u.id 
                     WHERE r.lider_id = :lider_id 
                     ORDER BY r.fecha_registro DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":lider_id", $lider_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function obtenerRuta($id) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return null;
        }
    }

    public function asignarLider($ruta_id, $lider_id) {
        try {
            // Verificar que el líder existe y está aprobado
            $query = "SELECT id FROM usuarios WHERE id = :lider_id AND tipo_usuario = 'lider' AND estado = TRUE";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":lider_id", $lider_id);
            $stmt->execute();
            
            if($stmt->rowCount() == 0) {
                return ['success' => false, 'message' => 'El líder seleccionado no existe o no está aprobado'];
            }

            // Asignar el líder a la ruta
            $query = "UPDATE " . $this->table . " SET lider_id = :lider_id WHERE id = :ruta_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":lider_id", $lider_id);
            $stmt->bindParam(":ruta_id", $ruta_id);
            
            if($stmt->execute()) {
                return ['success' => true, 'message' => 'Líder asignado exitosamente'];
            }
            return ['success' => false, 'message' => 'Error al asignar el líder'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function obtenerLideresDisponibles() {
        try {
            $query = "SELECT u.id, u.nombre, u.email 
                     FROM usuarios u
                     WHERE u.tipo_usuario = 'lider' 
                     AND u.estado = TRUE 
                     AND u.aprobado = TRUE
                     AND u.id NOT IN (SELECT lider_id FROM rutas WHERE lider_id IS NOT NULL)
                     ORDER BY u.nombre";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function eliminarRuta($id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            
            if($stmt->execute()) {
                return ['success' => true, 'message' => 'Ruta eliminada exitosamente'];
            }
            return ['success' => false, 'message' => 'Error al eliminar la ruta'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function obtenerLideres() {
        try {
            $query = "SELECT * FROM usuarios WHERE tipo_usuario = 'lider'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function getLastInsertId() {
        return $this->db->lastInsertId();
    }

    public function actualizarExcel($ruta_id, $filename) {
        try {
            $query = "UPDATE " . $this->table . " SET excel_file = :filename WHERE id = :ruta_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":filename", $filename);
            $stmt->bindParam(":ruta_id", $ruta_id);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
} 