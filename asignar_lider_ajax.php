<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/rutas.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo_usuario'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$rutas = new Rutas();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'asignar_lider') {
    $ruta_id = $_POST['ruta_id'];
    $lider_id = $_POST['lider_id'];
    $result = $rutas->asignarLider($ruta_id, $lider_id);
    if ($result['success']) {
        $nuevaRuta = $rutas->obtenerRuta($ruta_id);
        echo json_encode([
            'success' => true,
            'ruta_id' => $ruta_id,
            'lider_nombre' => $nuevaRuta['lider_nombre'] ?? '',
            'message' => $result['message']
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición inválida']);
    exit;
} 