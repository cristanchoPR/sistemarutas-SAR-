<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/vehiculos.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$vehiculos = new Vehiculos();
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'agregar':
                if (isset($_POST['placa']) && isset($_POST['modelo'])) {
                    $resultado = $vehiculos->agregarVehiculo($_POST['placa'], $_POST['modelo']);
                    $mensaje = $resultado;
                }
                break;
                
            case 'eliminar':
                if (isset($_POST['id'])) {
                    if ($vehiculos->eliminarVehiculo($_POST['id'])) {
                        $mensaje = "Vehículo eliminado exitosamente";
                    } else {
                        $mensaje = "Error al eliminar el vehículo";
                    }
                }
                break;

            case 'asignar_ruta':
                if (isset($_POST['vehiculo_id']) && isset($_POST['ruta_id'])) {
                    $resultado = $vehiculos->asignarRuta($_POST['vehiculo_id'], $_POST['ruta_id']);
                    $mensaje = $resultado;
                }
                break;
        }
    }
}

// Redirigir de vuelta al panel de administración
header('Location: admin.php?seccion=vehiculos&mensaje=' . urlencode($mensaje));
exit();

// Obtener lista de vehículos
$lista_vehiculos = $vehiculos->obtenerVehiculos();

// Obtener rutas y líderes para mostrar en el panel
require_once 'config/rutas.php';
$rutasObj = new Rutas();
$lista_rutas = $rutasObj->obtenerRutas();
$rutas_por_id = [];
foreach ($lista_rutas as $ruta) {
    $rutas_por_id[$ruta['id']] = $ruta;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Vehículos - SAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            color: white;
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
        }
        .nav-link:hover {
            color: white;
        }
        .nav-link.active {
            background: #34495e;
            color: white;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-group-sm .btn {
            margin-right: 5px;
        }
        .btn-group-sm .btn:last-child {
            margin-right: 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4 class="text-center mb-4">SAR</h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">
                                <i class="fas fa-home me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">
                                <i class="fas fa-users me-2"></i> Gestión de Líderes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="vehiculos.php">
                                <i class="fas fa-car me-2"></i> Vehículos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-cog me-2"></i> Configuración
                            </a>
                        </li>
                        <li class="nav-item mt-5">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Gestión de Vehículos</h2>
                    <div class="user-info">
                        <span class="me-2">Bienvenido, <?php echo htmlspecialchars($_SESSION['user']['nombre']); ?></span>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user']['nombre']); ?>" 
                             class="rounded-circle" width="40" height="40">
                    </div>
                </div>

                <?php if($mensaje): ?>
                    <div class="alert alert-<?php echo strpos($mensaje, 'exitosamente') !== false ? 'success' : 'danger'; ?>" role="alert">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <!-- Formulario de Registro de Vehículo -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Registrar Nuevo Vehículo</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="agregar">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="placa" class="form-label">Placa</label>
                                    <input type="text" class="form-control" id="placa" name="placa" required 
                                           placeholder="Ingrese la placa del vehículo">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="modelo" class="form-label">Modelo</label>
                                    <input type="text" class="form-control" id="modelo" name="modelo" required 
                                           placeholder="Ingrese el modelo del vehículo">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Registrar Vehículo
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Lista de Vehículos -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Vehículos Registrados</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Placa</th>
                                        <th>Modelo</th>
                                        <th>Fecha de Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lista_vehiculos as $vehiculo): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($vehiculo['placa']); ?></td>
                                        <td><?php echo htmlspecialchars($vehiculo['modelo']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($vehiculo['fecha_registro'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirm('¿Estás seguro de que deseas eliminar este vehículo?');">
                                                    <input type="hidden" name="action" value="eliminar">
                                                    <input type="hidden" name="id" value="<?php echo $vehiculo['id']; ?>">
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 