<?php
require_once 'config.php';
require 'vendor/autoload.php'; // Necesitarás instalar PhpSpreadsheet: composer require phpoffice/phpspreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

// Verificar que el usuario esté logueado y sea líder
requireLider();

$lider_id = $_SESSION['user_id'];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    try {
        $inputFileName = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = IOFactory::load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Comenzar transacción
        $conn->beginTransaction();

        // Limpiar datos anteriores del líder
        $sql = "DELETE FROM datos_excel_asignado WHERE lider_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$lider_id]);

        // Preparar la inserción
        $sql = "INSERT INTO datos_excel_asignado (lider_id, nombre_empleado, apellido_empleado, documento, cargo, fecha_ingreso) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        // Saltar la primera fila (encabezados)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Asegurarse de que la fila tenga los datos necesarios
            if (count($row) >= 6) {
                $stmt->execute([
                    $lider_id,
                    $row[0], // nombre
                    $row[1], // apellido
                    $row[2], // documento
                    $row[3], // cargo
                    $row[4]  // fecha ingreso
                ]);
            }
        }

        // Confirmar transacción
        $conn->commit();
        $mensaje = 'Excel cargado correctamente';
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollBack();
        $mensaje = 'Error al cargar el Excel: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar Excel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .upload-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        .upload-header {
            background: #0d6efd;
            color: #fff;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            font-weight: 500;
        }
        .upload-body {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="upload-card">
            <div class="upload-header">
                <i class="fas fa-file-upload"></i> Cargar Excel
            </div>
            <div class="upload-body">
                <?php if ($mensaje): ?>
                    <div class="alert alert-info"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Seleccionar archivo Excel</label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Cargar Excel
                    </button>
                </form>

                <div class="mt-4">
                    <h5>Formato esperado del Excel:</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Documento</th>
                                <th>Cargo</th>
                                <th>Fecha Ingreso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Juan</td>
                                <td>Pérez</td>
                                <td>12345678</td>
                                <td>Operador</td>
                                <td>2024-01-01</td>
                            </tr>
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