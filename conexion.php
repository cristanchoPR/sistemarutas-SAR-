<?php
$host = 'localhost';
$db   = 'sistema_ruta';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
echo password_hash("aprendiz", PASSWORD_DEFAULT);

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>
