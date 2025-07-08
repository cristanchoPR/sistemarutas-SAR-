<?php
session_start();
require_once 'config.php';
require_once 'config/rutas.php';
require 'vendor/autoload.php'; // composer require phpoffice/phpspreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo_usuario'] !== 'lider') {
    header('Location: login.php');
    exit;
}
$lider_id = $_SESSION['user']['id'];
$rutas = new Rutas();
$rutas_lider = $rutas->obtenerRutasPorLider($lider_id);

// Obtener el primer vehículo asignado a la(s) ruta(s) del líder
require_once 'config/vehiculos.php';
$vehiculos = new Vehiculos();
$vehiculo_asignado = null;
$ruta_asignada = null;
if (!empty($rutas_lider)) {
    $ruta_asignada = $rutas_lider[0];
    $vehiculo_asignado = $vehiculos->obtenerVehiculoPorRuta($ruta_asignada['id']);
}

// Obtener datos del líder, incluyendo el archivo Excel asignado
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id=?");
$stmt->execute([$lider_id]);
$lider = $stmt->fetch(PDO::FETCH_ASSOC);

$excel_file = $lider['excel_file'] ?? null;
$excel_data = [];

if ($excel_file && file_exists("uploads/excel_lideres/$excel_file")) {
    $spreadsheet = IOFactory::load("uploads/excel_lideres/$excel_file");
    $sheet = $spreadsheet->getActiveSheet();
    $excel_data = $sheet->toArray();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Líder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/Imagen1.png">
    <style>
        body { background: linear-gradient(120deg, #f4f6f9 60%, #e9f0fb 100%); min-height: 100vh; }
        .topbar {
            width: 100%; height: 60px; background: #003366; color: #fff;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 30px; position: fixed; top: 0; left: 0; z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .sidebar {
            position: fixed; top: 60px; left: 0; height: calc(100vh - 60px); width: 70px;
            background: #23272b; color: #fff; transition: width 0.2s;
            overflow-x: hidden; z-index: 999;
        }
        .sidebar.expanded { width: 220px; }
        .sidebar .nav-link {
            color: #fff; margin: 10px 0; border-radius: 5px;
            transition: background 0.2s, color 0.2s;
            display: flex; align-items: center; gap: 12px; padding: 10px 18px;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #003366; color: #fff !important;
        }
        .sidebar .nav-link span { display: none; }
        .sidebar.expanded .nav-link span { display: inline; }
        .sidebar-toggler {
            background: none; border: none; color: #fff; font-size: 1.5rem;
            margin-right: 10px; cursor: pointer;
        }
        .main-content {
            margin-top: 70px; margin-left: 70px; padding: 30px; transition: margin-left 0.2s;
        }
        .sidebar.expanded ~ .main-content { margin-left: 220px; }
        .card {
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 24px;
            background: #fff;
        }
        .card-header {
            background: #003366 !important;
            color: #fff !important;
            border-radius: 14px 14px 0 0;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 1px;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-header.excel-header {
            background: linear-gradient(90deg, #ffc107 60%, #ffe066 100%) !important;
            color: #212529 !important;
        }
        .table-bordered {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e0e6ed;
        }
        .table th, .table td {
            vertical-align: middle;
            font-size: 1.05em;
            border-top: 1px solid #e0e6ed !important;
        }
        .tabla-ruta th {
            width: 40%;
            background: #f8f9fa;
        }
        .tabla-ruta td {
            background: #fff;
        }
        .tabla-lider th { background: #0d6efd; color: #fff; font-size: 1.05rem; }
        .tabla-excel th { background: #ffc107; color: #212529; font-size: 1.05rem; }
        .tabla-vehiculo th { background: #dc3545; color: #fff; font-size: 1.05rem; }
        .tabla-lider td { color: #222 !important; }
        .tabla-ruta td, .tabla-excel td, .tabla-vehiculo td { background: #f8fafd; font-size: 1.01rem; color: #222; }
        .tabla-lider { border: 2px solid #0d6efd; }
        .tabla-excel { border: 2px solid #ffc107; }
        .tabla-vehiculo { border: 2px solid #dc3545; }
        .icono-panel {
            font-size: 2.1rem;
            opacity: 0.85;
            margin-right: 8px;
        }
        .card-body {
            padding: 20px 24px;
        }
        .excel-btn {
            font-size: 1.1rem;
            padding: 8px 18px;
            border-radius: 8px;
        }
        .tabla-excel, .tabla-excel th, .tabla-excel td {
            background: #fffbe6 !important;
        }
        .tabla-vehiculo, .tabla-vehiculo th, .tabla-vehiculo td {
            background: #fff5f5 !important;
        }
        .tabla-ruta, .tabla-ruta th, .tabla-ruta td {
            background: #f3fcf6 !important;
        }
        .tabla-lider, .tabla-lider th, .tabla-lider td {
            background: #f0f6ff !important;
        }
        .tabla-lider th, .tabla-ruta th, .tabla-vehiculo th {
            background: #003366 !important;
            color: #fff !important;
            font-weight: bold;
            border: none;
        }
        .tabla-lider td, .tabla-ruta td, .tabla-vehiculo td {
            background: #fff !important;
            color: #222 !important;
            border: none;
        }
        .icono-panel {
            font-size: 2.1rem;
            opacity: 0.85;
            margin-right: 8px;
        }
        .card-body {
            padding: 20px 24px;
        }
        .excel-btn {
            font-size: 1.1rem;
            padding: 8px 18px;
            border-radius: 8px;
        }
        .tabla-excel, .tabla-excel th, .tabla-excel td {
            background: #fffbe6 !important;
        }
        .tabla-vehiculo, .tabla-vehiculo th, .tabla-vehiculo td {
            background: #fff5f5 !important;
        }
        .tabla-ruta, .tabla-ruta th, .tabla-ruta td {
            background: #f3fcf6 !important;
        }
        .tabla-lider, .tabla-lider th, .tabla-lider td {
            background: #f0f6ff !important;
        }
        .tabla-lider th, .tabla-ruta th, .tabla-vehiculo th {
            font-weight: bold;
        }
        .tabla-excel th, .tabla-excel td {
            /* Mantener el color actual del Excel */
        }
        @media (max-width: 768px) {
            .sidebar, .sidebar.expanded { width: 100vw; height: auto; position: static; }
            .main-content, .sidebar.expanded ~ .main-content { margin-left: 0; }
            .card-body { padding: 14px 8px; }
        }
        /* Forzar color negro en la tabla de datos del líder */
        .card .tabla-lider td, .card .tabla-lider th {
            color: #222 !important;
            background: #f8fafd;
        }
        /* Forzar color negro en la tabla de ruta asignada */
        .card .tabla-ruta td, .card .tabla-ruta th {
            color: #222 !important;
            background: #f3fcf6;
        }
        /* Forzar color negro en la tabla de vehículo asignado */
        .card .tabla-vehiculo td, .card .tabla-vehiculo th {
            color: #222 !important;
            background: #fff5f5;
        }
        /* Forzar color azul en la cabecera de la tarjeta de datos del líder */
        .card-header.tabla-lider-header {
            background: #003366 !important;
            color: #fff !important;
        }
        /* Estilos para la sección de asistencias */
        .asistencia-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .asistencia-header {
            background: #0d6efd;
            color: #fff;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            font-weight: 500;
        }
        .asistencia-body {
            padding: 20px;
        }
        .btn-asistio {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 5px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-falto {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 5px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-asistio:hover { background: #218838; }
        .btn-falto:hover { background: #c82333; }
        .observacion-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="topbar d-flex align-items-center">
        <button class="sidebar-toggler" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <div>
            <i class="fas fa-user-circle me-2"></i>
            <span><?php echo htmlspecialchars($_SESSION['user']['nombre'] ?? 'Líder'); ?></span>
        </div>
        <a href="logout.php" class="text-white text-decoration-none"><i class="fas fa-sign-out-alt"></i> Salir</a>
    </div>
    <div class="sidebar" id="sidebar">
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a class="nav-link active" href="#" data-section="panel">
                    <i class="fas fa-tachometer-alt"></i> <span>Panel</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="main-content" id="mainContent">
        <!-- Panel principal -->
        <div id="panel" class="section active">
            <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> Información Asignada al lider</h2>
            <div class="mb-4">
                <div class="card">
                    <div class="card-header tabla-lider-header"><i class="fas fa-user icono-panel text-primary"></i> Datos del Líder</div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0 tabla-lider">
                            <tr>
                                <th>Nombre</th>
                                <td><?php echo htmlspecialchars($_SESSION['user']['nombre']); ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?php echo htmlspecialchars($_SESSION['user']['email']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <?php if (!empty($rutas_lider)): ?>
                <?php foreach ($rutas_lider as $ruta): ?>
                    <?php $vehiculo = $vehiculos->obtenerVehiculoPorRuta($ruta['id']); ?>
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header"><i class="fas fa-route icono-panel text-success"></i> Ruta Asignada</div>
                                <div class="card-body p-0">
                                    <table class="table table-bordered mb-0 tabla-ruta">
                                        <tr>
                                            <th>Nombre</th>
                                            <td><?php echo htmlspecialchars($ruta['nombre']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Descripción</th>
                                            <td>
                                                <?php echo 'Inicio: <b>' . htmlspecialchars($ruta['inicio_ruta']) . '</b><br>Fin: <b>' . htmlspecialchars($ruta['fin_ruta']) . '</b>'; ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header"><i class="fas fa-file-excel icono-panel text-warning"></i> Archivo Excel Asignado</div>
                                <div class="card-body d-flex flex-column align-items-center justify-content-center" style="background: #fffbe6; min-height: 110px;">
                                    <?php if (!empty($ruta['excel_file'])): ?>
                                        <a href="uploads/excel_rutas/<?php echo htmlspecialchars($ruta['excel_file']); ?>" target="_blank" class="btn btn-success btn-sm excel-btn mb-2"><i class="fas fa-file-excel"></i> Ver Excel</a>
                                    <?php else: ?>
                                        <span class="text-muted">No hay archivo Excel asignado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header"><i class="fas fa-car icono-panel text-danger"></i> Vehículo Asignado</div>
                                <div class="card-body p-0">
                                    <table class="table table-bordered mb-0 tabla-vehiculo">
                                        <tr>
                                            <th>Modelo</th>
                                            <td><?php echo $vehiculo ? htmlspecialchars($vehiculo['modelo']) : '<span class=\'text-muted\'>No asignado</span>'; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Placa</th>
                                            <td><?php echo $vehiculo ? htmlspecialchars($vehiculo['placa']) : '<span class=\'text-muted\'>No asignado</span>'; ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($ruta['excel_file'])): ?>
                        <div class="text-center mb-4">
                            <a href="asistencias.php?ruta_id=<?= $ruta['id'] ?>" class="btn btn-primary"><i class="fas fa-user-check"></i> Tomar Asistencia</a>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">No tienes rutas asignadas.</div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Modal para mostrar el Excel de la ruta -->
    <div class="modal fade" id="modalExcelRuta" tabindex="-1" aria-labelledby="modalExcelRutaLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalExcelRutaLabel"><i class="fas fa-file-excel"></i> Listado de Asistencia</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body" id="contenidoExcelRuta">
            <div class="text-center text-muted">Cargando...</div>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Sidebar toggle
    document.getElementById('sidebarToggle').onclick = function() {
        var sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('expanded');
        document.getElementById('mainContent').classList.toggle('expanded');
    };
    // Navegación entre secciones
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.section').forEach(section => section.style.display = 'none');
            var target = document.getElementById(this.dataset.section);
            if (target) target.style.display = 'block';
        });
    });
    document.querySelectorAll('.btnTomarAsistencia').forEach(btn => {
        btn.addEventListener('click', function() {
            const rutaId = this.getAttribute('data-ruta-id');
            const modal = new bootstrap.Modal(document.getElementById('modalExcelRuta'));
            document.getElementById('contenidoExcelRuta').innerHTML = '<div class="text-center text-muted">Cargando...</div>';
            fetch('ver_excel_ruta_ajax.php?ruta_id=' + rutaId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('contenidoExcelRuta').innerHTML = html;
                });
            modal.show();
        });
    });
    </script>
</body>
</html>