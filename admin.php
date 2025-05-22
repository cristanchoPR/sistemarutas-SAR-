<?php
include("conexion.php");
include("proteger.php");
verificarSesion();

if (!esAdministrador($conn)) {
    header("Location: aprendiz.php");
    exit();
}


if (isset($_GET['promocionar'])) {
    $usuario_id = intval($_GET['promocionar']);
    $conn->query("UPDATE usuarios SET rol = 'admin' WHERE id = $usuario_id");
    header("Location: admin.php");
    exit();
}


$aprendices = $conn->query("SELECT id, usuario FROM usuarios WHERE rol = 'aprendiz'");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <span class="navbar-brand">Panel del Administrador</span>
        <div class="d-flex">
            <a href="logout.php" class="btn btn-outline-light">Cerrar sesión</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h3 class="mb-4">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?></h3>

    <div class="row g-4">
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">🗺️ Gestionar Rutas</h5>
                    <a href="gestionar_rutas.php" class="btn btn-primary w-100">Ir a Rutas</a>
                </div>
            </div>
        </div>

        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">👥 Usuarios</h5>
                    <a href="usuarios.php" class="btn btn-primary w-100">Ver Usuarios</a>
                </div>
            </div>
        </div>

        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">⬆️ Promover Aprendices</h5>
                    <?php while ($row = $aprendices->fetch_assoc()): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?= htmlspecialchars($row['usuario']) ?></span>
                            <a href="?promocionar=<?= $row['id'] ?>" class="btn btn-sm btn-success">Hacer Admin</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
