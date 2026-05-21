<?php 
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT r.id, r.id_sede_origen, r.id_sede_destino, r.estado,
               o.nombre AS origen, d.nombre AS destino
        FROM rutas r
        INNER JOIN sedes o ON o.id = r.id_sede_origen
        INNER JOIN sedes d ON d.id = r.id_sede_destino
        ORDER BY r.id ASC";
$resultado = $conn->query($sql);

// si hay un error en la consulta, respondemos con un error 500 y un array vacío
if (!$resultado) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}

$rutas = [];
while ($fila = $resultado->fetch_assoc()) {
    $rutas[] = $fila;
}

echo json_encode($rutas);