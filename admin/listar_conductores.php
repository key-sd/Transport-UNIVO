<?php
/*
 ┌──────────────────────────────────────────────────────────────┐
 │  conductores/listar_conductores.php                          │
 │  Devuelve JSON con todos los conductores para la tabla AJAX  │
 └──────────────────────────────────────────────────────────────┘
*/
require_once $_SERVER['DOCUMENT_ROOT'] . '/Transport-UNIVO/includes/sesion.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Transport-UNIVO/includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

/*
  JOIN entre conductores y usuarios para traer también
  el código universitario (que funciona como "username").
*/
$sql = "
    SELECT
        c.id,
        c.nombre,
        c.apellido,
        c.telefono,
        u.codigo_universitario
    FROM conductores c
    INNER JOIN usuarios u ON u.id = c.usuario_id
    ORDER BY c.nombre ASC, c.apellido ASC
";

$resultado = $conn->query($sql);

if (!$resultado) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}

$conductores = [];
while ($fila = $resultado->fetch_assoc()) {
    $conductores[] = $fila;
}

echo json_encode($conductores);