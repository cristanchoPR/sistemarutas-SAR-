<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/rutas.php';
require_once 'config/vehiculos.php';

header('Content-Type: application/json');

// Log para debug
$log_file = 'debug_rutas.log';
file_put_contents($log_file, "\n--- NUEVA PETICIÓN ---\n" . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents($log_file, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo_usuario'] !== 'admin') {
    file_put_contents($log_file, "Error: No autorizado\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$rutas = new Rutas();
$vehiculosObj = new Vehiculos();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agregar') {
    file_put_contents($log_file, "Procesando acción: agregar\n", FILE_APPEND);
    
    $nombre = trim($_POST['nombre'] ?? '');
    $inicio_ruta = trim($_POST['inicio_ruta'] ?? '');
    $fin_ruta = trim($_POST['fin_ruta'] ?? '');
    
    file_put_contents($log_file, "Datos recibidos - Nombre: $nombre, Inicio: $inicio_ruta, Fin: $fin_ruta\n", FILE_APPEND);
    
    if ($nombre === '' || $inicio_ruta === '' || $fin_ruta === '') {
        file_put_contents($log_file, "Error: Todos los campos son obligatorios.\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    $lider_id = isset($_POST['lider_id']) && $_POST['lider_id'] !== '' ? $_POST['lider_id'] : null;
    
    try {
        file_put_contents($log_file, "Intentando agregar ruta...\n", FILE_APPEND);
        $result = $rutas->agregarRuta($nombre, $inicio_ruta, $fin_ruta);
        file_put_contents($log_file, "Resultado agregarRuta: " . print_r($result, true) . "\n", FILE_APPEND);
        
        if ($result['success']) {
            $lastId = $rutas->db->lastInsertId();
            file_put_contents($log_file, "ID de la nueva ruta: $lastId\n", FILE_APPEND);
            
            if ($lider_id) {
                $rutas->asignarLider($lastId, $lider_id);
            }
            
            $nuevaRuta = $rutas->obtenerRuta($lastId);
            file_put_contents($log_file, "Nueva ruta obtenida: " . print_r($nuevaRuta, true) . "\n", FILE_APPEND);
            
            $vehiculos_por_ruta = [];
            $lista_vehiculos = $vehiculosObj->obtenerVehiculos();
            
            foreach ($lista_vehiculos as $vehiculo) {
                if ($vehiculo['ruta_id'] == $lastId) {
                    $vehiculos_por_ruta[$lastId][] = $vehiculo;
                }
            }
            
            ob_start();
            ?>
            <tr>
                <td><?php echo htmlspecialchars($nuevaRuta['id']); ?></td>
                <td><?php echo htmlspecialchars($nuevaRuta['nombre']); ?></td>
                <td><?php echo htmlspecialchars($nuevaRuta['inicio_ruta']); ?></td>
                <td><?php echo htmlspecialchars($nuevaRuta['fin_ruta']); ?></td>
                <td>
                    <?php if (!empty($vehiculos_por_ruta[$lastId])): ?>
                        <?php foreach($vehiculos_por_ruta[$lastId] as $vehiculo): ?>
                            <span class="badge bg-info"><?php echo htmlspecialchars($vehiculo['modelo']); ?></span><br>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="badge bg-secondary">Sin vehículo</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($nuevaRuta['lider_nombre'])): ?>
                        <?php echo htmlspecialchars($nuevaRuta['lider_nombre']); ?>
                    <?php else: ?>
                        <span class="badge bg-secondary">Sin asignar</span>
                    <?php endif; ?>
                </td>
                <td><?php echo isset($nuevaRuta['fecha_registro']) ? date('d/m/Y H:i', strtotime($nuevaRuta['fecha_registro'])) : ''; ?></td>
                <td>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#excelModal<?php echo $nuevaRuta['id']; ?>">
                        <i class="fas fa-list"></i>
                    </button>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#asignarLiderModal<?php echo $nuevaRuta['id']; ?>">
                        <i class="fas fa-user-cog"></i>
                    </button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar esta ruta?');">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="id" value="<?php echo $nuevaRuta['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger btn-action">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php
            $row_html = ob_get_clean();
            
            file_put_contents($log_file, "Respuesta exitosa enviada\n", FILE_APPEND);
            echo json_encode([
                'success' => true,
                'message' => 'Ruta registrada exitosamente',
                'row_html' => $row_html
            ]);
        } else {
            file_put_contents($log_file, "Error en agregarRuta: " . $result['message'] . "\n", FILE_APPEND);
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
        }
    } catch (Exception $e) {
        file_put_contents($log_file, "Excepción: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode([
            'success' => false,
            'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
        ]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'asignar_lider') {
    $ruta_id = $_POST['ruta_id'] ?? null;
    $lider_id = $_POST['lider_id'] ?? null;
    $rutas = new Rutas();
    $result = $rutas->asignarLider($ruta_id, $lider_id);
    if ($result['success']) {
        $nuevaRuta = $rutas->obtenerRuta($ruta_id);
        echo json_encode([
            'success' => true,
            'lider_nombre' => $nuevaRuta['lider_nombre'] ?? '',
            'message' => $result['message']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Acción no reconocida'];

    switch ($action) {
        case 'eliminar_excel':
            if (isset($_POST['ruta_id'])) {
                $ruta_id = $_POST['ruta_id'];
                $rutas = new Rutas();
                $ruta = $rutas->obtenerRuta($ruta_id);
                if (!empty($ruta['excel_file'])) {
                    $file = 'uploads/excel_rutas/' . $ruta['excel_file'];
                    if (file_exists($file)) {
                        unlink($file);
                    }
                    $rutas->actualizarExcel($ruta_id, null);
                    $excel_html = '';
                    if (isset($_SESSION['user']) && $_SESSION['user']['tipo_usuario'] === 'admin') {
                        $excel_html = '<form method="POST" enctype="multipart/form-data" class="d-inline excel-upload-form" style="display:inline-block;vertical-align:middle;">'
                            . '<input type="hidden" name="ruta_id" value="' . $ruta_id . '">' 
                            . '<input type="file" name="excel_file" accept=".xls,.xlsx" style="display:none;" required>'
                            . '<button type="button" class="btn btn-success btn-upload-excel" title="Subir Excel">'
                            . '<i class="fas fa-file-excel fa-2x"></i></button></form>';
                    }
                    $response = ['success' => true, 'ruta_id' => $ruta_id, 'excel_html' => $excel_html];
                } else {
                    $response = ['success' => false, 'message' => 'No se encontró el archivo Excel asociado a esta ruta.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'ID de ruta no proporcionado.'];
            }
            break;

        case 'subir_excel':
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
                        $excel_html = '<a href="uploads/excel_rutas/' . $filename . '" class="btn btn-sm btn-info" target="_blank"><i class="fas fa-file-excel"></i> Ver Excel</a>';
                        $excel_html .= ' <button type="button" class="btn btn-sm btn-danger btn-eliminar-excel" data-ruta-id="' . $ruta_id . '"><i class="fas fa-trash"></i></button>';
                        $response = ['success' => true, 'ruta_id' => $ruta_id, 'excel_html' => $excel_html];
                    } else {
                        $response = ['success' => false, 'message' => 'Error al mover el archivo.'];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Solo se permiten archivos .xls y .xlsx'];
                }
            } else {
                $response = ['success' => false, 'message' => 'No se seleccionó ningún archivo o hubo un error en la subida.'];
            }
            break;

        default:
            $response = ['success' => false, 'message' => 'Acción no reconocida'];
    }
    echo json_encode($response);
    exit;
} else {
    file_put_contents($log_file, "Petición inválida\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Petición inválida']);
} 