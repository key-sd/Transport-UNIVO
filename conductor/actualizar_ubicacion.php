<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'conductor') {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit();
}

include("../includes/conexion.php");

header('Content-Type: application/json; charset=utf-8');

$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

if (!$lat || !$lng) {
    echo json_encode(['success' => false]);
    exit();
}

$conductor_id = $_SESSION['usuario_id'];

// Busca pot el id del conductor
$stmt = $conn->prepare("SELECT id FROM conductores WHERE usuario_id = ?");
$stmt->bind_param('i', $conductor_id);
$stmt->execute();
$conductor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conductor) {
    echo json_encode(['success' => false]);
    exit();
}

$cond_id = $conductor['id'];

// Insertar nueva ubicación y guarda el historial (no se actualiza, se inserta un nuevo registro cada vez de momento)
$stmt = $conn->prepare("INSERT INTO ubicaciones (id_conductor, latitud, longitud) VALUES (?, ?, ?)");
$stmt->bind_param('idd', $cond_id, $lat, $lng);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);