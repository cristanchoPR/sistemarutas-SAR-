<?php
require_once 'database.php';

class Auth {
    private $conn;
    private $table_name = "usuarios";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($email, $password) {
        try {
            $query = "SELECT id, nombre, email, password, tipo_usuario, aprobado, estado 
                     FROM " . $this->table_name . " 
                     WHERE email = :email";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar si el usuario está activo
                if(!$row['estado']) {
                    return ['success' => false, 'message' => 'Tu cuenta ha sido desactivada'];
                }
                
                // Verificar si es líder y no está aprobado
                if($row['tipo_usuario'] === 'lider' && !$row['aprobado']) {
                    return ['success' => false, 'message' => 'Tu cuenta está pendiente de aprobación'];
                }
                
                // Verificar la contraseña
                if(password_verify($password, $row['password'])) {
                    // Iniciar sesión
                    session_start();
                    $_SESSION['user'] = [
                        'id' => $row['id'],
                        'nombre' => $row['nombre'],
                        'email' => $row['email'],
                        'tipo_usuario' => $row['tipo_usuario']
                    ];
                    
                    return ['success' => true, 'message' => 'Inicio de sesión exitoso'];
                }
            }
            
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Error en el inicio de sesión'];
        }
    }

    public function register($nombre, $email, $password, $tipo_usuario) {
        try {
            // Verificar si el email ya existe
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'El email ya está registrado'];
            }
            
            // Hash de la contraseña
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar nuevo usuario con estado TRUE y aprobado FALSE
            $query = "INSERT INTO " . $this->table_name . " 
                     (nombre, email, password, tipo_usuario, estado, aprobado) 
                     VALUES (:nombre, :email, :password, :tipo_usuario, TRUE, FALSE)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":tipo_usuario", $tipo_usuario);
            
            if($stmt->execute()) {
                return ['success' => true, 'message' => 'Registro exitoso'];
            }
            
            return ['success' => false, 'message' => 'Error en el registro'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Error en el registro'];
        }
    }

    public function logout() {
        session_start();
        session_destroy();
        return true;
    }

    public function getLideresPendientes() {
        try {
            $query = "SELECT id, nombre, email, fecha_registro, aprobado, estado 
                     FROM " . $this->table_name . " 
                     WHERE tipo_usuario = 'lider' 
                     ORDER BY fecha_registro DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function aprobarLider($id) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET aprobado = TRUE 
                     WHERE id = :id AND tipo_usuario = 'lider'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function rechazarLider($id) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET estado = FALSE 
                     WHERE id = :id AND tipo_usuario = 'lider'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}
?> 