<?php
session_start();
require_once 'config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Validar sesión de líder
if (!isset($_SESSION['user']) || $_SESSION['user']['tipo_usuario'] !== 'lider') {
    exit('No autorizado');
}

// Obtener ruta_id por GET
$ruta_id = isset($_GET['ruta_id']) ? intval($_GET['ruta_id']) : 0;
if (!$ruta_id) exit('Ruta no especificada');

require_once 'config/rutas.php';
$rutas = new Rutas();
$ruta = $rutas->obtenerRuta($ruta_id);
$excel_file = $ruta['excel_file'] ?? null;
$excel_data = [];

if ($excel_file && file_exists("uploads/excel_rutas/$excel_file")) {
    $spreadsheet = IOFactory::load("uploads/excel_rutas/$excel_file");
    $sheet = $spreadsheet->getActiveSheet();
    $excel_data = $sheet->toArray();
}

function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

if ($excel_data):
    // Buscar índices de columnas 'asistio' y 'fallo' de forma segura
    $header = array_map(function($v) { return $v !== null ? strtolower($v) : ''; }, $excel_data[0]);
    $colAsistio = array_search('asistio', $header);
    $colFallo = array_search('fallo', $header);
?>
    <div id="alertaAsistencia" class="alert alert-success d-none" role="alert">
        ¡Asistencia guardada correctamente!
    </div>
    <form id="formAsistenciaExcel">
    <div class="table-responsive">
        <table class="table table-bordered" id="tablaAsistenciaExcel">
            <thead>
                <tr>
                    <?php foreach ($excel_data[0] as $col): ?>
                        <th><?= safe($col) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($excel_data, 1) as $idx => $row): ?>
                    <tr>
                        <?php foreach ($row as $colIdx => $cell): ?>
                            <?php if ($colIdx === $colAsistio || $colIdx === $colFallo): ?>
                                <td class="text-center celda-x" data-idx="<?= $idx ?>" data-col="<?= $colIdx ?>">
                                    <input type="radio" name="asistencia[<?= $idx ?>]" value="<?= $colIdx === $colAsistio ? 'asistio' : 'fallo' ?>" style="display:inline-block;vertical-align:middle;" required>
                                </td>
                            <?php else: ?>
                                <td><?= safe($cell) ?></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <input type="hidden" name="empleado[<?= $idx ?>]" value="<?= safe($row[0]) ?>">
                        <input type="hidden" name="documento[<?= $idx ?>]" value="<?= safe($row[2]) ?>">
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="text-end">
        <button type="submit" class="btn btn-primary">Guardar Asistencia</button>
    </div>
    <input type="hidden" name="ruta_id" value="<?= $ruta_id ?>">
    </form>
    <script>
    // Mostrar la X en la celda seleccionada
    document.querySelectorAll('#tablaAsistenciaExcel input[type=radio]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var idx = this.closest('td').getAttribute('data-idx');
            var col = this.closest('td').getAttribute('data-col');
            // Limpiar X en ambas celdas de la fila
            document.querySelectorAll('td[data-idx="' + idx + '"]').forEach(function(td) {
                td.childNodes.forEach(function(node) {
                    if (node.nodeType === 3) node.textContent = '';
                });
                td.querySelectorAll('.x-marcada').forEach(function(x) { x.remove(); });
            });
            // Poner la X en la celda seleccionada
            var x = document.createElement('span');
            x.textContent = 'X';
            x.className = 'x-marcada';
            this.closest('td').appendChild(x);
        });
    });
    document.getElementById('formAsistenciaExcel').onsubmit = function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        formData.append('action', 'guardar_excel');
        fetch('asistencias.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('alertaAsistencia').classList.remove('d-none');
                setTimeout(function() {
                    document.getElementById('alertaAsistencia').classList.add('d-none');
                }, 2500);
            } else {
                alert(data.message || 'Error al guardar la asistencia');
            }
        })
        .catch(() => alert('Error al guardar la asistencia'));
    };
    </script>
<?php else: ?>
    <div class="alert alert-warning">No tienes un Excel asignado o el archivo no existe.</div>
<?php endif; ?> 