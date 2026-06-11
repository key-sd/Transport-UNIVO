<?php
$server = "sql5.freesqldatabase.com";
$user = "sql5830068";
$pass = "4IKjt9vqYt";
$db = "sql5830068";
$port = 3306;

date_default_timezone_set('America/El_Salvador');

$conexion = new mysqli($server, $user, $pass, $db, $port);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conn = $conexion;
?>