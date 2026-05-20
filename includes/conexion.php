<?php
$server = "localhost";
$user = "root";
$pass = "";
$db = "transport_univo";

$conexion = new mysqli($server, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conn = $conexion; // ← agrega esta línea
?>