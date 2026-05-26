<?php 
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT c.id, r.id_ruta AS Ruta, h.id_horario AS Horario, c.dia AS Día, c.estado,
        FROM cronogramas c
        INNER JOIN rutas r ON r.id = c.id_ruta
        INNER JOIN horarios h ON h.id = c.id_horario
        ORDER BY c.id ASC";
$resultado = $conn->query($sql);

// si hay un error en la consulta, respondemos con un error 500 y un array vacío
if (!$resultado) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}

$cronogramas = [];
while ($fila = $resultado->fetch_assoc()) {
    $cronogramas[] = $fila;
}

echo json_encode($cronogramas);