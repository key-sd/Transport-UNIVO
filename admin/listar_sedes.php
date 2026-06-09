<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

date_default_timezone_set('America/El_Salvador');
header('Content-Type: application/json; charset=utf-8');

$resultado = $conn->query("SELECT id, nombre FROM sedes WHERE estado = 1 ORDER BY nombre ASC");
$sedes = [];
while ($fila = $resultado->fetch_assoc()) $sedes[] = $fila;

echo json_encode($sedes);