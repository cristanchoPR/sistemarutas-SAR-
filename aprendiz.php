<?php
include("conexion.php");
include("proteger.php");
verificarSesion();


if (esAdministrador($conn)) {
    header("Location: admin.php");
    exit();
}


$usuario = isset($_SESSION["usuario"]) ? $_SESSION["usuario"] : "Aprendiz";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Aprendiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container-fluid">
        <span class="navbar-brand">Panel del Aprendiz</span>
        <div class="d-flex">
            <a href="logout.php" class="btn btn-outline-light">Cerrar sesión</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h3 class="mb-4">Bienvenido, <?php echo htmlspecialchars($usuario); ?></h3>

    <div class="row g-4">
        
        <div class="col-md-6">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <h5 class="card-title">📋 Registro de Asistencia</h5>
                    <p class="card-text">Marca tu hora de entrada y salida.</p>
                    <a href="registro_asistencia.php" class="btn btn-success w-100">Registrar Asistencia</a>
                </div>
            </div>
        </div>

        
        <div class="col-md-6">
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <h5 class="card-title">🗺️ Mis Rutas</h5>
                    <p class="card-text">Consulta tus rutas asignadas.</p>
                    <a href="mis_rutas.php" class="btn btn-primary w-100">Ver Rutas</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

