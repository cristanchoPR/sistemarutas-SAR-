<?php
function verificarSesion() {
    session_start();
    if (!isset($_SESSION['usuario'])) {
        header("Location: login.php");
        exit();
    }
}

function esAdministrador($conn) {
    if (!isset($_SESSION["usuario_id"])) return false;

    $id = $_SESSION["usuario_id"];
    $sql = $conn->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $sql->bind_param("i", $id);
    $sql->execute();
    $sql->bind_result($rol);
    $sql->fetch();
    $sql->close();

    return $rol === "admin";
}

