<?php
include("conexion.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"];
    $clave = password_hash($_POST["clave"], PASSWORD_DEFAULT);
    $rol = "aprendiz";

    $sql = $conn->prepare("INSERT INTO usuarios (usuario, clave, rol) VALUES (?, ?, ?)");
    $sql->bind_param("sss", $usuario, $clave, $rol);

    if ($sql->execute()) {
        $_SESSION["usuario"] = $usuario;
        $_SESSION["usuario_id"] = $sql->insert_id;
        header("Location: aprendiz.php");
        exit();
    } else {
        $error = "Error al registrar el usuario. ¿Ya existe?";
    }

    $sql->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-6 offset-md-3">
        <div class="card shadow">
            <div class="card-body">
                <h4 class="card-title mb-4 text-center">Registro de Usuario</h4>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="usuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="clave" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Registrarse</button>
                    <a href="login.php" class="btn btn-link w-100">Ya tengo cuenta</a>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>


