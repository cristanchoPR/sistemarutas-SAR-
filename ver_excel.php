<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea líder
requireLider();

// Obtener el ID del líder actual
$lider_id = $_SESSION['user_id'];

// Función para obtener los datos del Excel asignado al líder
function obtenerDatosExcel($lider_id) {
    global $conn;
    
    $sql = "SELECT * FROM datos_excel_asignado WHERE lider_id = ? ORDER BY nombre_empleado, apellido_empleado";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$lider_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener los datos
$datos_excel = obtenerDatosExcel($lider_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos del Excel Asignado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .excel-table {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        .excel-header {
            background: #0d6efd;
            color: #fff;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            font-weight: 500;
        }
        .excel-body {
            padding: 20px;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .table td {
            vertical-align: middle;
        }
        .estado-activo {
            color: #198754;
            font-weight: 500;
        }
        .estado-inactivo {
            color: #dc3545;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="excel-table">
            <div class="excel-header">
                <i class="fas fa-file-excel"></i> Datos del Excel Asignado
            </div>
            <div class="excel-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Documento</th>
                                <th>Cargo</th>
                                <th>Fecha Ingreso</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($datos_excel as $empleado): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($empleado['nombre_empleado']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['apellido_empleado']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['documento']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['cargo']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($empleado['fecha_ingreso'])); ?></td>
                                <td>
                                    <span class="estado-<?php echo $empleado['estado']; ?>">
                                        <?php echo ucfirst($empleado['estado']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 