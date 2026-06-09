<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

date_default_timezone_set('America/El_Salvador');
header('Content-Type: application/json; charset=utf-8');

// trae todas las unidades ordenadas: activas primero, luego inactivas, ambas por nombre
$sql = "SELECT id, nombre, placa, capacidad_maxima, estado
        FROM unidades
        ORDER BY estado DESC, nombre ASC";

$resultado = $conn->query($sql);

// si hay un error en la consulta, respondemos con un error 500 y un array vacío
if (!$resultado) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}

// convertimos el resultado a un array para enviarlo al frontend
$unidades = [];
while ($fila = $resultado->fetch_assoc()) {
    $unidades[] = $fila;
}

echo json_encode($unidades);