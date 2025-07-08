<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/vehiculos.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$vehiculos = new Vehiculos();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agregar') {
    $placa = trim($_POST['placa']);
    $modelo = trim($_POST['modelo']);
    if ($placa === '' || $modelo === '') {
        echo json_encode(['success' => false, 'message' => 'Debes completar ambos campos: Placa y Modelo.']);
        exit;
    }
    $resultado = $vehiculos->agregarVehiculo($placa, $modelo);
    if ($resultado === 'Vehículo registrado exitosamente') {
        // Obtener el vehículo recién insertado
        $lista = $vehiculos->obtenerVehiculos();
        $vehiculo = $lista[0]; // El más reciente
        ob_start();
        ?>
        <tr>
            <td><?php echo htmlspecialchars($vehiculo['placa']); ?></td>
            <td><?php echo htmlspecialchars($vehiculo['modelo']); ?></td>
            <td><?php echo $vehiculo['ruta_id'] ? htmlspecialchars($vehiculo['nombre_ruta']) : '<span class="text-muted">Sin ruta asignada</span>'; ?></td>
            <td><?php echo date('d/m/Y', strtotime($vehiculo['fecha_registro'])); ?></td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#asignarRutaModal<?php echo $vehiculo['id']; ?>">
                        <i class="fas fa-route"></i> Asignar Ruta
                    </button>
                    <form method="POST" action="vehiculos.php" class="d-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este vehículo?');">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="id" value="<?php echo $vehiculo['id']; ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php
        $row_html = ob_get_clean();
        echo json_encode(['success' => true, 'message' => $resultado, 'row_html' => $row_html]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => $resultado]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición inválida']);
    exit;
} 