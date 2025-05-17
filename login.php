<?php
session_start();
require 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];
    

    
    $query = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo AND  activo = 1");
    $query->execute(['correo' => $correo]);
    $usuario = $query->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($contrasena,$usuario['password'])){  
        
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['nombre'] = $usuario['nombre'];

        if ($usuario['rol'] === 'Administrador') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: aprendiz_dashboard.php");
        }
        exit;
    } else {
        $error = "Correo o contraseña incorrectos.";
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Login - Sistema de Rutas</title>
</head>
<body>
    <h2>Iniciar Sesión</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post" action="login.php">
        <label>Correo:</label>
        <input type="text" name="correo" required><br><br>
        <label>Contraseña:</label>
        <input type="password" name="contrasena" required><br><br>
        <input type="submit" value="Iniciar Sesión">
    </form>
</body>
</html>
