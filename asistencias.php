<?php
session_start();
require_once 'config.php';
require 'vendor/autoload.php';
require_once 'config/whatsapp_sender.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Validar sesión de líder
if (!isset($_SESSION['user']) || $_SESSION['user']['tipo_usuario'] !== 'lider') {
    exit('No autorizado');
}

// Obtener ruta_id (puede venir por GET o POST)
$ruta_id = isset($_GET['ruta_id']) ? intval($_GET['ruta_id']) : (isset($_POST['ruta_id']) ? intval($_POST['ruta_id']) : 0);
if (!$ruta_id) exit('Ruta no especificada');

require_once 'config/rutas.php';
$rutas = new Rutas();
$ruta = $rutas->obtenerRuta($ruta_id);
$excel_file = $ruta['excel_file'] ?? null;
$excel_data = [];
$mensaje = '';
$exito = false;

if ($excel_file && file_exists("uploads/excel_rutas/$excel_file")) {
    $spreadsheet = IOFactory::load("uploads/excel_rutas/$excel_file");
    $sheet = $spreadsheet->getActiveSheet();
    $excel_data = $sheet->toArray();
}

function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Número del administrador (cambiar por el real, con código de país)
$telefonoAdmin = '3162758474'; // Ejemplo: 51987654321
$linkReporte = null;

// Procesar el formulario clásico
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asistencia'])) {
    $asistencias = $_POST['asistencia'];
    $header = array_map(function($v) { return $v !== null ? strtolower($v) : ''; }, $excel_data[0]);
    $colAsistio = array_search('asistio', $header);
    $colFallo = array_search('fallo', $header);
    $colTelefono = array_search('telefono', $header);
    
    // Limpiar columnas antes de marcar
    for ($i = 2; $i <= $sheet->getHighestRow(); $i++) {
        if ($colAsistio !== false) {
            $colLet = Coordinate::stringFromColumnIndex($colAsistio + 1);
            $sheet->setCellValue($colLet . $i, '');
        }
        if ($colFallo !== false) {
            $colLet = Coordinate::stringFromColumnIndex($colFallo + 1);
            $sheet->setCellValue($colLet . $i, '');
        }
    }

    $whatsappSender = new WhatsAppSender('');
    $mensajesPendientes = [];

    foreach ($asistencias as $idx => $valor) {
        $rowNum = $idx + 2;
        $colLetNombre = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2);
        if ($valor === 'asistio' && $colAsistio !== false) {
            $colLet = Coordinate::stringFromColumnIndex($colAsistio + 1);
            $sheet->setCellValue($colLet . $rowNum, 'X');
            // Mensaje individual
            if ($colTelefono !== false) {
                $colLetTel = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colTelefono + 1);
                $telefono = $sheet->getCell($colLetTel . $rowNum)->getValue();
                $nombre = $sheet->getCell($colLetNombre . $rowNum)->getValue();
                if ($telefono) {
                    $mensaje = "✅ Asistencia confirmada: $nombre ha sido marcado como presente en la ruta de hoy.";
                    $mensajesPendientes[] = [
                        'telefono' => $telefono,
                        'mensaje' => $mensaje,
                        'link' => $whatsappSender->enviarMensaje($telefono, $mensaje)
                    ];
                }
            }
        } else if ($valor === 'fallo' && $colFallo !== false) {
            $colLet = Coordinate::stringFromColumnIndex($colFallo + 1);
            $sheet->setCellValue($colLet . $rowNum, 'X');
            // Mensaje individual
            if ($colTelefono !== false) {
                $colLetTel = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colTelefono + 1);
                $telefono = $sheet->getCell($colLetTel . $rowNum)->getValue();
                $nombre = $sheet->getCell($colLetNombre . $rowNum)->getValue();
                if ($telefono) {
                    $mensaje = "❌ Inasistencia registrada: $nombre ha sido marcado como ausente en la ruta de hoy.";
                    $mensajesPendientes[] = [
                        'telefono' => $telefono,
                        'mensaje' => $mensaje,
                        'link' => $whatsappSender->enviarMensaje($telefono, $mensaje)
                    ];
                }
            }
        }
    }

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save("uploads/excel_rutas/$excel_file");
    
    $novedades = isset($_POST['novedades']) ? trim($_POST['novedades']) : '';
    $reporte = "Reporte de asistencia de la ruta: " . ($ruta['nombre'] ?? '') . "\n";
    foreach ($asistencias as $idx => $valor) {
        $rowNum = $idx + 2;
        $colLetNombre = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2);
        $nombre = $sheet->getCell($colLetNombre . $rowNum)->getValue();
        $reporte .= "- $nombre: " . ($valor === 'asistio' ? 'Asistió' : 'Faltó') . "\n";
    }
    if ($novedades) {
        $reporte .= "\nNovedades: \n$novedades";
    }
    $linkReporte = $whatsappSender->enviarMensaje($telefonoAdmin, $reporte);

    $mensaje = '¡Asistencia guardada y Excel actualizado correctamente!';
    $exito = true;
    // Recargar datos para mostrar la tabla actualizada
    $excel_data = $sheet->toArray();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tomar Asistencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/Imagen1.png">
    <style>
    .card-asistencia {
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border: none;
        background: #fff;
        padding: 32px 24px;
        margin-top: 32px;
    }
    .table-asistencia th {
        background: #003366 !important;
        color: #fff !important;
        font-weight: bold;
        border: none;
    }
    .table-asistencia td {
        background: #f8fafd !important;
        color: #222 !important;
        border: none;
    }
    .table-asistencia {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e0e6ed;
    }
    .btn {
        border-radius: 8px;
        font-weight: 500;
        padding: 8px 20px;
    }
    .btn-info {
        background: #0dcaf0;
        color: #fff;
        border: none;
    }
    .btn-primary {
        background: #0d6efd;
        color: #fff;
        border: none;
    }
    .btn-info:hover, .btn-primary:hover {
        opacity: 0.9;
    }
    input[type="radio"] {
        width: 20px;
        height: 20px;
        accent-color: #0d6efd;
    }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="lider.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
        <h2 class="mb-0 flex-grow-1 text-center">
            <i class="fas fa-route me-2"></i> SAR
        </h2>
        <div style="width: 160px;"></div> <!-- Espacio para alinear el título -->
    </div>
    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $exito ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>
    <?php if ($excel_data):
        $header = array_map(function($v) { return $v !== null ? strtolower($v) : ''; }, $excel_data[0]);
        $colAsistio = array_search('asistio', $header);
        $colFallo = array_search('fallo', $header);
    ?>
    <form method="POST" action="asistencias.php">
        <input type="hidden" name="ruta_id" value="<?= $ruta_id ?>">
        <div class="card-asistencia">
            <div class="mb-3 text-end">
                <button type="button" class="btn btn-info me-2" id="btnNovedades"><i class="fas fa-lightbulb"></i> Novedades</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Asistencia</button>
            </div>
            <div id="novedadesBox" class="mb-3" style="display:none;">
                <label for="novedades" class="form-label">Escribe aquí las novedades:</label>
                <textarea name="novedades" id="novedades" class="form-control" rows="3"></textarea>
            </div>
            <div class="table-responsive">
                <table class="table table-asistencia align-middle">
                    <thead>
                        <tr>
                            <?php foreach ($excel_data[0] as $col): ?>
                                <th><?= safe($col ?? '') ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($excel_data, 1) as $idx => $row): ?>
                            <tr>
                                <?php foreach ($row as $colIdx => $cell): ?>
                                    <?php if ($colIdx === $colAsistio || $colIdx === $colFallo): ?>
                                        <td class="text-center">
                                            <input type="radio" name="asistencia[<?= $idx ?>]" value="<?= $colIdx === $colAsistio ? 'asistio' : 'fallo' ?>" required <?= ($cell === 'X') ? 'checked' : '' ?>>
                                            <?php if ($cell === 'X'): ?><span class="fw-bold text-success">X</span><?php endif; ?>
                                        </td>
                                    <?php else: ?>
                                        <td><?= safe($cell ?? '') ?></td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
    <?php endif; ?>
    <?php if (isset($linkReporte)): ?>
        <div class="mt-4 text-center">
            <a href="<?= $linkReporte ?>" target="_blank" class="btn btn-success btn-lg">
                <i class="fab fa-whatsapp"></i> Enviar reporte al administrador
            </a>
        </div>
    <?php endif; ?>
    <script>
    document.getElementById('btnNovedades').onclick = function() {
        var box = document.getElementById('novedadesBox');
        box.style.display = (box.style.display === 'none') ? 'block' : 'none';
    };
    </script>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 