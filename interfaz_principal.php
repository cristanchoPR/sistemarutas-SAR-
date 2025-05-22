<?php
include("proteger.php");
verificarSesion();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Interfaz Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="alert alert-success">
        <h3>Bienvenido al sistema</h3>
        <p>Has iniciado sesión correctamente.</p>
        <a href="logout.php" class="btn btn-danger">Cerrar sesión</a>
    </div>
</div>
</body>
</html>
