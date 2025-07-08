<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/vehiculos.php';
require_once 'config/rutas.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Variables para mensajes
$success = $success ?? '';
$error = $error ?? '';

// Procesar acciones de aprobación/rechazo/eliminación de líderes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    $database = new Database();
    $conn = $database->getConnection();

    if ($action === 'approve') {
        $query = "UPDATE usuarios SET aprobado = TRUE, estado = TRUE WHERE id = :id AND tipo_usuario = 'lider'";
    } elseif ($action === 'reject') {
        $query = "UPDATE usuarios SET estado = FALSE WHERE id = :id AND tipo_usuario = 'lider'";
    } elseif ($action === 'delete') {
        $query = "DELETE FROM usuarios WHERE id = :id AND tipo_usuario = 'lider'";
    }
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
}

// Procesar la edición en el backend
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar_lider' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $database = new Database();
    $conn = $database->getConnection();
    $query = "UPDATE usuarios SET nombre = :nombre, email = :email WHERE id = :id AND tipo_usuario = 'lider'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
}

// Obtener lista de líderes actualizada
$database = new Database();
$conn = $database->getConnection();
$query = "SELECT id, nombre, email, fecha_registro, aprobado, estado 
          FROM usuarios 
          WHERE tipo_usuario = 'lider' 
          ORDER BY fecha_registro DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$lideres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de vehículos
$vehiculos = new Vehiculos();
$lista_vehiculos = $vehiculos->obtenerVehiculos();

// Obtener lista de rutas
$rutas = new Rutas();
$lista_rutas = $rutas->obtenerRutas();

// Procesar acciones de rutas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'asignar_lider':
            $ruta_id = $_POST['ruta_id'];
            $lider_id = $_POST['lider_id'];
            // Validar en la base de datos
            $query = "SELECT id FROM usuarios WHERE id = :lider_id AND tipo_usuario = 'lider' AND aprobado = 1 AND estado = 1";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':lider_id', $lider_id);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                // Verificar que el líder no esté asignado a otra ruta
                $query2 = "SELECT id FROM rutas WHERE lider_id = :lider_id AND id != :ruta_id";
                $stmt2 = $conn->prepare($query2);
                $stmt2->bindParam(':lider_id', $lider_id);
                $stmt2->bindParam(':ruta_id', $ruta_id);
                $stmt2->execute();
                if ($stmt2->rowCount() == 0) {
                    $result = $rutas->asignarLider($ruta_id, $lider_id);
                    $success = 'Líder asignado correctamente.';
                } else {
                    $error = 'Este líder ya está asignado a otra ruta.';
                }
            } else {
                $error = 'Solo se pueden asignar líderes aprobados y activos.';
            }
            break;
        case 'eliminar_lider':
            $result = $rutas->asignarLider($_POST['ruta_id'], null);
            $success = 'Líder eliminado de la ruta correctamente.';
            break;
        case 'eliminar':
            $result = $rutas->eliminarRuta($_POST['id']);
            $success = 'Ruta eliminada correctamente.';
            break;
    }
}

// Restaurar la lógica original de sección activa
$seccion_activa = isset($_GET['seccion']) ? $_GET['seccion'] : 'lideres';

// En el modal para asignar/cambiar líder, solo mostrar líderes aprobados y no asignados a otra ruta
$lideresDisponibles = array_filter($lideres, function($l) use ($lista_rutas) {
    if (!$l['aprobado'] || !$l['estado']) return false;
    foreach ($lista_rutas as $r) {
        if ($r['lider_id'] == $l['id']) return false;
    }
    return true;
});

// Asociar vehículos por ruta_id
$vehiculos_por_ruta = [];
foreach ($lista_vehiculos as $vehiculo) {
    if ($vehiculo['ruta_id']) {
        $vehiculos_por_ruta[$vehiculo['ruta_id']][] = $vehiculo;
    }
}

$mensaje_ruta = '';
$exito_ruta = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agregar') {
    $nombre = trim($_POST['nombre'] ?? '');
    $inicio_ruta = trim($_POST['inicio_ruta'] ?? '');
    $fin_ruta = trim($_POST['fin_ruta'] ?? '');
    if ($nombre === '' || $inicio_ruta === '' || $fin_ruta === '') {
        $mensaje_ruta = 'Todos los campos son obligatorios.';
    } else {
        require_once 'config/rutas.php';
        $rutas = new Rutas();
        $result = $rutas->agregarRuta($nombre, $inicio_ruta, $fin_ruta);
        $mensaje_ruta = $result['message'];
        $exito_ruta = $result['success'];
        if ($exito_ruta) {
            $lastId = $rutas->getLastInsertId();
            $lider_id = isset($_POST['lider_id']) && $_POST['lider_id'] !== '' ? $_POST['lider_id'] : null;
            if ($lider_id) {
                $rutas->asignarLider($lastId, $lider_id);
            }
            header("Location: admin.php?seccion=rutas&nueva_ruta_id=$lastId");
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'asignar_lider') {
    $ruta_id = $_POST['ruta_id'] ?? null;
    $lider_id = $_POST['lider_id'] ?? null;
    if ($ruta_id && $lider_id) {
        $rutas = new Rutas();
        $result = $rutas->asignarLider($ruta_id, $lider_id);
        $mensaje_ruta = $result['message'];
        $exito_ruta = $result['success'];
    } else {
        $mensaje_ruta = 'Debes seleccionar un líder.';
        $exito_ruta = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'subir_excel') {
    $ruta_id = $_POST['ruta_id'];
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
        $allowed = ['xls', 'xlsx'];
        if (in_array(strtolower($ext), $allowed)) {
            $dest = 'uploads/excel_rutas/';
            if (!is_dir($dest)) mkdir($dest, 0777, true);
            $filename = 'ruta_' . $ruta_id . '_' . time() . '.' . $ext;
            $filepath = $dest . $filename;
            if (move_uploaded_file($_FILES['excel_file']['tmp_name'], $filepath)) {
                $rutas = new Rutas();
                $rutas->actualizarExcel($ruta_id, $filename);
                $mensaje_ruta = 'Archivo Excel subido correctamente.';
                $exito_ruta = true;
            } else {
                $mensaje_ruta = 'Error al mover el archivo.';
                $exito_ruta = false;
            }
        } else {
            $mensaje_ruta = 'Solo se permiten archivos .xls y .xlsx';
            $exito_ruta = false;
        }
    } else {
        $mensaje_ruta = 'No se seleccionó ningún archivo o hubo un error en la subida.';
        $exito_ruta = false;
    }
}

$nueva_ruta_id = isset($_GET['nueva_ruta_id']) ? $_GET['nueva_ruta_id'] : null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - SAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/Imagen1.png">
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
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border: none;
        }
        .card-header {
            border-radius: 12px 12px 0 0;
            font-weight: 600;
            font-size: 1.1rem;
            background: linear-gradient(90deg, #0d6efd 60%, #0dcaf0 100%);
            color: #fff;
            letter-spacing: 1px;
        }
        .status-badge {
            font-size: 0.8em;
            padding: 5px 10px;
        }
        .btn-group-sm .btn {
            margin-right: 5px;
        }
        .btn-group-sm .btn:last-child {
            margin-right: 0;
        }
        .table-success {
            background-color: #d4edda !important;
            font-weight: bold;
        }
        .table-hover tbody tr:hover {
            background-color: #f1f7ff;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .badge.bg-info, .badge.bg-secondary {
            font-size: 0.95em;
            padding: 0.5em 0.8em;
            border-radius: 8px;
        }
        .btn-action {
            border-radius: 8px;
        }
        #whatsapp-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
            background: #25d366;
            color: #fff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: background 0.2s;
            text-decoration: none;
        }
        #whatsapp-float:hover {
            background: #128c7e;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-map me-2"></i> SAR
                    </h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $seccion_activa === 'lideres' ? 'active' : ''; ?>" 
                               href="?seccion=lideres">
                                <i class="fas fa-users me-2"></i> Gestión de Líderes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $seccion_activa === 'vehiculos' ? 'active' : ''; ?>" 
                               href="?seccion=vehiculos">
                                <i class="fas fa-car me-2"></i> Vehículos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $seccion_activa === 'rutas' ? 'active' : ''; ?>" 
                               href="?seccion=rutas">
                                <i class="fas fa-route me-2"></i> Rutas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $seccion_activa === 'rutas_registradas' ? 'active' : ''; ?>" 
                               href="?seccion=rutas_registradas">
                                <i class="fas fa-list me-2"></i> Rutas Registradas
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
                    <h2>
                        <?php 
                        if ($seccion_activa === 'lideres') {
                            echo 'Gestión de Líderes';
                        } else if ($seccion_activa === 'vehiculos') {
                            echo 'Gestión de Vehículos';
                        } else if ($seccion_activa === 'rutas') {
                            echo 'Gestión de Rutas';
                        } else if ($seccion_activa === 'rutas_registradas') {
                            echo 'Rutas Registradas';
                        }
                        ?>
                    </h2>
                    <div class="user-info">
                        <span class="me-2">Bienvenido, <?php echo htmlspecialchars($_SESSION['user']['nombre']); ?></span>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user']['nombre']); ?>" 
                             class="rounded-circle" width="40" height="40">
                    </div>
                </div>

                <?php if ($seccion_activa === 'lideres'): ?>
                <!-- Gestión de Líderes -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Gestión de Líderes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Fecha de Registro</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lideres as $lider): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($lider['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($lider['email']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($lider['fecha_registro'])); ?></td>
                                        <td>
                                            <?php if ($lider['estado']): ?>
                                                <?php if ($lider['aprobado']): ?>
                                                    <span class="badge bg-success status-badge">Aprobado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning status-badge">Pendiente</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-danger status-badge">Rechazado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($lider['estado'] && !$lider['aprobado']): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $lider['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-check"></i> Aprobar
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $lider['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn btn-warning">
                                                            <i class="fas fa-times"></i> Rechazar
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este líder?');">
                                                    <input type="hidden" name="user_id" value="<?php echo $lider['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
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

                <?php elseif ($seccion_activa === 'vehiculos'): ?>
                <!-- Gestión de Vehículos -->
                <!-- Formulario de Registro de Vehículo -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Registrar Nuevo Vehículo</h5>
                    </div>
                    <div class="card-body">
                        <form id="vehiculo-form" method="POST" action="vehiculos.php">
                            <input type="hidden" name="action" value="agregar">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="placa" class="form-label">Placa</label>
                                    <input type="text" class="form-control" id="placa" name="placa" required placeholder="Ingrese la placa del vehículo">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="modelo" class="form-label">Modelo</label>
                                    <input type="text" class="form-control" id="modelo" name="modelo" required placeholder="Ingrese el modelo del vehículo">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Registrar Vehículo
                            </button>
                        </form>
                        <div id="vehiculo-alert" style="display:none;" class="alert mt-3"></div>
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
                                        <th>Ruta Asignada</th>
                                        <th>Fecha de Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lista_vehiculos as $vehiculo): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($vehiculo['placa']); ?></td>
                                        <td><?php echo htmlspecialchars($vehiculo['modelo']); ?></td>
                                        <td>
                                            <?php if ($vehiculo['ruta_id']): ?>
                                                <?php echo htmlspecialchars($vehiculo['nombre_ruta']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Sin ruta asignada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($vehiculo['fecha_registro'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#asignarRutaModal<?php echo $vehiculo['id']; ?>">
                                                    <i class="fas fa-route"></i> Asignar Ruta
                                                </button>
                                                <form method="POST" action="vehiculos.php" class="d-inline" 
                                                      onsubmit="return confirm('¿Estás seguro de que deseas eliminar este vehículo?');">
                                                    <input type="hidden" name="action" value="eliminar">
                                                    <input type="hidden" name="id" value="<?php echo $vehiculo['id']; ?>">
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            </div>

                                            <!-- Modal para Asignar Ruta -->
                                            <div class="modal fade" id="asignarRutaModal<?php echo $vehiculo['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Asignar Ruta a Vehículo</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="vehiculos.php">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="asignar_ruta">
                                                                <input type="hidden" name="vehiculo_id" value="<?php echo $vehiculo['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="ruta_id" class="form-label">Seleccionar Ruta</label>
                                                                    <select class="form-select" name="ruta_id" required>
                                                                        <option value="">Seleccione una ruta</option>
                                                                        <?php foreach ($lista_rutas as $ruta): ?>
                                                                            <?php
                                                                            $ruta_asignada = false;
                                                                            foreach ($lista_vehiculos as $v) {
                                                                                if ($v['ruta_id'] == $ruta['id'] && $v['id'] != $vehiculo['id']) {
                                                                                    $ruta_asignada = true;
                                                                                    break;
                                                                                }
                                                                            }
                                                                            ?>
                                                                            <option value="<?php echo $ruta['id']; ?>" <?php echo $ruta_asignada ? 'disabled' : ''; ?>
                                                                                <?php echo ($vehiculo['ruta_id'] == $ruta['id']) ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($ruta['nombre']); ?><?php if ($ruta_asignada) echo ' (Ya asignada)'; ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-primary">Asignar Ruta</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php elseif ($seccion_activa === 'rutas'): ?>
                <!-- Gestión de Rutas -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Registrar Nueva Ruta</h5>
                    </div>
                    <div class="card-body">
                        <form id="ruta-form" method="POST" action="admin.php?seccion=rutas">
                            <input type="hidden" name="action" value="agregar">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="nombre" class="form-label">Nombre de la Ruta</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Nombre de la ruta">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="inicio_ruta" class="form-label">Inicio de Ruta</label>
                                    <input type="text" class="form-control" id="inicio_ruta" name="inicio_ruta" required placeholder="Inicio de la ruta">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="fin_ruta" class="form-label">Fin de Ruta</label>
                                    <input type="text" class="form-control" id="fin_ruta" name="fin_ruta" required placeholder="Fin de la ruta">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="lider_id" class="form-label">Líder (opcional)</label>
                                    <select class="form-select" id="lider_id" name="lider_id">
                                        <option value="">Sin asignar</option>
                                        <?php foreach($lideresDisponibles as $lider): ?>
                                            <option value="<?php echo $lider['id']; ?>">
                                                <?php echo htmlspecialchars($lider['nombre'] . ' (' . $lider['email'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Registrar Ruta</button>
                        </form>
                        <?php if ($mensaje_ruta): ?>
                            <div class="alert alert-<?php echo $exito_ruta ? 'success' : 'danger'; ?> mt-3">
                                <?php echo $mensaje_ruta; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Tabla de rutas -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Rutas Registradas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Inicio de Ruta</th>
                                        <th>Fin de Ruta</th>
                                        <th>Modelo Vehículo</th>
                                        <th>Líder Asignado</th>
                                        <th>Fecha de Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="rutas-tbody">
                                    <?php foreach($lista_rutas as $ruta): ?>
                                    <tr id="ruta-row-<?php echo $ruta['id']; ?>"<?php if ($nueva_ruta_id == $ruta['id']) echo ' class="table-success"'; ?>>
                                        <td><?php echo htmlspecialchars($ruta['id']); ?></td>
                                        <td><?php echo htmlspecialchars($ruta['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($ruta['inicio_ruta']); ?></td>
                                        <td><?php echo htmlspecialchars($ruta['fin_ruta']); ?></td>
                                        <td>
                                            <?php if (!empty($vehiculos_por_ruta[$ruta['id']])): ?>
                                                <?php foreach($vehiculos_por_ruta[$ruta['id']] as $vehiculo): ?>
                                                    <span class="badge bg-info"> <?php echo htmlspecialchars($vehiculo['modelo']); ?> </span><br>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin vehículo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="td-lider">
                                            <?php if (!empty($ruta['lider_nombre'])): ?>
                                                <?php echo htmlspecialchars($ruta['lider_nombre']); ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin asignar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo isset($ruta['fecha_registro']) ? date('d/m/Y H:i', strtotime($ruta['fecha_registro'])) : ''; ?></td>
                                        <td>
                                            <?php if (empty($ruta['lider_nombre'])): ?>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#asignarLiderModal<?php echo $ruta['id']; ?>">
                                                    <i class="fas fa-user-plus"></i> Asignar Líder
                                                </button>
                                            <?php endif; ?>

                                            <form method="POST" class="d-inline delete-route-form">
                                                <input type="hidden" name="action" value="eliminar">
                                                <input type="hidden" name="id" value="<?php echo $ruta['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger btn-action delete-route-btn" data-id="<?php echo $ruta['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php elseif ($seccion_activa === 'rutas_registradas'): ?>
                <!-- Panel de Rutas Registradas -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">Rutas Registradas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Inicio de Ruta</th>
                                        <th>Fin de Ruta</th>
                                        <th>Modelo Vehículo</th>
                                        <th>Líder Asignado</th>
                                        <th>Fecha de Registro</th>
                                        <th>Excel</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lista_rutas as $ruta): ?>
                                    <tr id="ruta-row-<?php echo $ruta['id']; ?>">
                                        <td><?php echo htmlspecialchars($ruta['id']); ?></td>
                                        <td><?php echo htmlspecialchars($ruta['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($ruta['inicio_ruta']); ?></td>
                                        <td><?php echo htmlspecialchars($ruta['fin_ruta']); ?></td>
                                        <td>
                                            <?php if (!empty($vehiculos_por_ruta[$ruta['id']])): ?>
                                                <?php foreach($vehiculos_por_ruta[$ruta['id']] as $vehiculo): ?>
                                                    <span class="badge bg-info"> <?php echo htmlspecialchars($vehiculo['modelo']); ?> </span><br>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin vehículo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($ruta['lider_nombre'])): ?>
                                                <?php echo htmlspecialchars($ruta['lider_nombre']); ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin asignar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo isset($ruta['fecha_registro']) ? date('d/m/Y H:i', strtotime($ruta['fecha_registro'])) : ''; ?></td>
                                        <td class="td-excel">
                                            <?php if (isset($_SESSION['user']) && $_SESSION['user']['tipo_usuario'] === 'admin'): ?>
                                                <?php if (empty($ruta['excel_file'])): ?>
                                                    <form method="POST" enctype="multipart/form-data" class="d-inline excel-upload-form" style="display:inline-block;vertical-align:middle;">
                                                        <input type="hidden" name="ruta_id" value="<?php echo $ruta['id']; ?>">
                                                        <input type="file" name="excel_file" accept=".xls,.xlsx" style="display:none;" required onchange="subirExcel(this)">
                                                        <button type="button" class="btn btn-success" title="Subir Excel" onclick="this.parentNode.querySelector('input[type=file]').click();">
                                                            <i class="fas fa-file-excel fa-2x"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <a href="uploads/excel_rutas/<?php echo $ruta['excel_file']; ?>" class="btn btn-sm btn-info" target="_blank">
                                                        <i class="fas fa-file-excel"></i> Ver Excel
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="eliminarExcel(this, <?php echo $ruta['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php foreach ($lideres as $lider): ?>
    <div class="modal fade" id="editarLiderModal<?php echo $lider['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Líder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="editar_lider">
                        <input type="hidden" name="user_id" value="<?php echo $lider['id']; ?>">
                        <div class="mb-3">
                            <label for="nombre_edit<?php echo $lider['id']; ?>" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre_edit<?php echo $lider['id']; ?>" name="nombre" value="<?php echo htmlspecialchars($lider['nombre']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email_edit<?php echo $lider['id']; ?>" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email_edit<?php echo $lider['id']; ?>" name="email" value="<?php echo htmlspecialchars($lider['email']); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Modal para asignar líder a cada ruta -->
    <?php foreach($lista_rutas as $ruta):
      if (empty($ruta['lider_nombre'])): ?>
        <div class="modal fade" id="asignarLiderModal<?php echo $ruta['id']; ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="POST" class="asignar-lider-form" data-ruta-id="<?php echo $ruta['id']; ?>" action="#">
                <div class="modal-header">
                  <h5 class="modal-title">Asignar Líder a la Ruta: <?php echo htmlspecialchars($ruta['nombre']); ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="action" value="asignar_lider">
                  <input type="hidden" name="ruta_id" value="<?php echo $ruta['id']; ?>">
                  <div class="mb-3">
                    <label for="lider_id_<?php echo $ruta['id']; ?>" class="form-label">Seleccionar Líder</label>
                    <select class="form-select" id="lider_id_<?php echo $ruta['id']; ?>" name="lider_id" required>
                      <option value="">Seleccione un líder</option>
                      <?php foreach($lideresDisponibles as $lider): ?>
                        <option value="<?php echo $lider['id']; ?>">
                          <?php echo htmlspecialchars($lider['nombre'] . ' (' . $lider['email'] . ')'); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="submit" class="btn btn-primary">Asignar Líder</button>
                </div>
              </form>
            </div>
          </div>
        </div>
    <?php endif; endforeach; ?>

    <!-- Mostrar mensajes en la interfaz -->
    <?php if($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Botón flotante de WhatsApp -->
    <a href="https://wa.me/573162758474" target="_blank" title="Chat WhatsApp con líderes" id="whatsapp-float">
        <i class="fab fa-whatsapp"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelectorAll('.delete-route-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (confirm('¿Está seguro de eliminar esta ruta?')) {
                var btn = form.querySelector('.delete-route-btn');
                var routeId = btn.getAttribute('data-id');
                var formData = new FormData(form);
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // Eliminar la fila de la tabla
                    var row = btn.closest('tr');
                    if (row) row.remove();
                    // Opcional: mostrar mensaje de éxito
                    // Puedes agregar aquí un alert o actualizar un div de mensajes
                });
            }
        });
    });

    const vehiculoForm = document.getElementById('vehiculo-form');
    const vehiculoAlert = document.getElementById('vehiculo-alert');
    vehiculoForm.addEventListener('submit', function(e) {
        e.preventDefault();
        let placa = vehiculoForm.placa.value.trim();
        let modelo = vehiculoForm.modelo.value.trim();
        if (!placa || !modelo) {
            vehiculoAlert.style.display = 'block';
            vehiculoAlert.className = 'alert alert-danger mt-3';
            vehiculoAlert.innerText = 'Debes completar ambos campos: Placa y Modelo.';
            return;
        }
        const formData = new FormData(vehiculoForm);
        fetch('vehiculos_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                vehiculoForm.reset();
                vehiculoAlert.style.display = 'block';
                vehiculoAlert.className = 'alert alert-success mt-3';
                vehiculoAlert.innerText = data.message;
                document.querySelector('.table tbody').insertAdjacentHTML('afterbegin', data.row_html);
            } else {
                vehiculoAlert.style.display = 'block';
                vehiculoAlert.className = 'alert alert-danger mt-3';
                vehiculoAlert.innerText = data.message;
            }
        });
    });

    const rutaForm = document.getElementById('ruta-form');
    const rutaAlert = document.getElementById('ruta-alert');
    rutaForm.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Formulario enviado');
        
        const formData = new FormData(this);
        console.log('Datos del formulario:', Object.fromEntries(formData));
        
        fetch('rutas_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Respuesta recibida:', response);
            return response.json();
        })
        .then(data => {
            console.log('Datos procesados:', data);
            
            if (data.success) {
                rutaAlert.style.display = 'block';
                rutaAlert.className = 'alert alert-success mt-3';
                rutaAlert.innerText = data.message;
                rutaForm.reset();
                
                // Agregar la nueva fila a la tabla
                const tbody = document.getElementById('rutas-tbody');
                if (tbody && data.row_html) {
                    tbody.insertAdjacentHTML('afterbegin', data.row_html);
                    console.log('Nueva fila agregada a la tabla');
                } else {
                    console.error('No se pudo agregar la fila: tbody o row_html no encontrados');
                }
            } else {
                rutaAlert.style.display = 'block';
                rutaAlert.className = 'alert alert-danger mt-3';
                rutaAlert.innerText = data.message;
                console.error('Error:', data.message);
            }
        })
        .catch(error => {
            console.error('Error en la petición:', error);
            rutaAlert.style.display = 'block';
            rutaAlert.className = 'alert alert-danger mt-3';
            rutaAlert.innerText = 'Error al procesar la solicitud: ' + error.message;
        });
    });

    document.querySelectorAll('.asignar-lider-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            formData.append('action', 'asignar_lider');
            const rutaId = form.getAttribute('data-ruta-id');
            fetch('rutas_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Cambia el contenido de la celda líder en la tabla (quita el botón)
                    const celda = document.querySelector('#ruta-row-' + rutaId + ' .td-lider');
                    if (celda) {
                        celda.innerHTML = `<span>${data.lider_nombre}</span>`;
                    }
                    // Quitar el botón de asignar líder de la columna de acciones
                    const fila = document.getElementById('ruta-row-' + rutaId);
                    if (fila) {
                        const acciones = fila.querySelectorAll('td');
                        if (acciones.length > 0) {
                            // Busca el botón por clase y lo elimina
                            const btnAsignar = fila.querySelector('button[data-bs-target="#asignarLiderModal' + rutaId + '"]');
                            if (btnAsignar) btnAsignar.remove();
                        }
                    }
                    // Cierra el modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('asignarLiderModal' + rutaId));
                    if (modal) modal.hide();
                    // Mostrar mensaje de éxito
                    let alertDiv = document.getElementById('asignar-lider-alert');
                    if (!alertDiv) {
                        alertDiv = document.createElement('div');
                        alertDiv.id = 'asignar-lider-alert';
                        alertDiv.className = 'alert alert-success mt-3';
                        alertDiv.innerText = 'Líder asignado correctamente.';
                        document.body.prepend(alertDiv);
                        setTimeout(() => alertDiv.remove(), 3000);
                    }
                } else {
                    alert(data.message);
                }
            });
        });
    });

    // Subir Excel por AJAX
    if (document.querySelectorAll('.excel-upload-form').length) {
        document.querySelectorAll('.excel-upload-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(form);
                formData.append('action', 'subir_excel');
                const rutaId = form.querySelector('[name="ruta_id"]').value;
                fetch('rutas_ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const celda = document.querySelector('#ruta-row-' + rutaId + ' .td-excel');
                        if (celda) {
                            celda.innerHTML = data.excel_html;
                        }
                    } else {
                        alert(data.message);
                    }
                });
            });
        });
    }

    // Eliminar Excel por AJAX
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-eliminar-excel')) {
            const btn = e.target.closest('.btn-eliminar-excel');
            const rutaId = btn.getAttribute('data-ruta-id');
            if (confirm('¿Seguro que deseas eliminar el archivo Excel de esta ruta?')) {
                const formData = new FormData();
                formData.append('action', 'eliminar_excel');
                formData.append('ruta_id', rutaId);
                fetch('rutas_ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const celda = document.querySelector('#ruta-row-' + rutaId + ' .td-excel');
                        if (celda) {
                            celda.innerHTML = data.excel_html;
                        }
                    } else {
                        alert(data.message);
                    }
                });
            }
        }
    });

    // Activar input file al hacer click en el botón verde de Excel
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-upload-excel')) {
            const btn = e.target.closest('.btn-upload-excel');
            const form = btn.closest('form');
            const fileInput = form.querySelector('input[type="file"]');
            fileInput.click();
        }
    });

    // Subir automáticamente el archivo al seleccionarlo
    document.addEventListener('change', function(e) {
        if (e.target.matches('.excel-upload-form input[type="file"]')) {
            const form = e.target.closest('form');
            const formData = new FormData(form);
            formData.append('action', 'subir_excel');
            const rutaId = form.querySelector('[name="ruta_id"]').value;
            fetch('rutas_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const celda = document.querySelector('#ruta-row-' + rutaId + ' .td-excel');
                    if (celda) {
                        celda.innerHTML = data.excel_html;
                    }
                } else {
                    alert(data.message);
                }
            });
        }
    });

    function subirExcel(input) {
        var form = input.closest('form');
        var formData = new FormData(form);
        formData.append('action', 'subir_excel');
        var rutaId = form.querySelector('[name="ruta_id"]').value;
        fetch('rutas_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                var celda = document.querySelector('#ruta-row-' + rutaId + ' .td-excel');
                if (celda) {
                    celda.innerHTML = data.excel_html;
                }
            } else {
                alert(data.message);
            }
        });
    }

    function eliminarExcel(btn, rutaId) {
        if (!confirm('¿Seguro que deseas eliminar el archivo Excel de esta ruta?')) return;
        var formData = new FormData();
        formData.append('action', 'eliminar_excel');
        formData.append('ruta_id', rutaId);
        fetch('rutas_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                var celda = btn.closest('.td-excel');
                if (celda) {
                    celda.innerHTML = data.excel_html;
                }
            } else {
                alert(data.message);
            }
        });
    }
    </script>
</body>
</html> 