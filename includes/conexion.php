<?php
$server = "localhost";
$user = "root";
$pass = "";
$db = "db_transport_univo";

date_default_timezone_set('America/El_Salvador');
$conexion = new mysqli($server, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conn = $conexion; 
?>