<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

// Consulta simple para extraer los horarios ordenados cronológicamente
$sql = "SELECT id, hora, estado FROM horas_salida ORDER BY hora ASC";
$resultado = $conn->query($sql);

if (!$resultado) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}

$horas_salida = [];
while ($fila = $resultado->fetch_assoc()) {
    // formatea la hora para que no se vea "13:00:00" sino "01:00 PM":
    $fila['hora_formateada'] = date("g:i A", strtotime($fila['hora']));
    $horas_salida[] = $fila;
}

echo json_encode($horas_salida);