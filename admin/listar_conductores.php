<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

// trae todos los conductores con su código asignado, para mostrarlos en la tabla del admin
$sql = "SELECT c.id, c.nombre, c.apellido, c.telefono, u.codigo_universitario
        FROM conductores c
        INNER JOIN usuarios u ON u.id = c.usuario_id
        ORDER BY c.nombre ASC, c.apellido ASC";

$resultado = $conn->query($sql);

// si hay un error en la consulta, respondemos con un error 500 y un array vacío
if (!$resultado) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}
// convertimos el resultado a un array de conductores con su código universitario para enviarlo al frontend
$conductores = [];
while ($fila = $resultado->fetch_assoc()) {
    $conductores[] = $fila;
}

echo json_encode($conductores);