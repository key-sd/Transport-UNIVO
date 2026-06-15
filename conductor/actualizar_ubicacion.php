<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'conductor') {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit();
}

date_default_timezone_set('America/El_Salvador');
include("../includes/conexion.php");

header('Content-Type: application/json; charset=utf-8');

$accion = $_POST['accion'] ?? 'actualizar';
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

$conductor_id = $_SESSION['usuario_id'];

// Busca pot el id del conductor
$stmt = $conn->prepare("SELECT id FROM conductores WHERE usuario_id = ?");
$stmt->bind_param('i', $conductor_id);
$stmt->execute();
$conductor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conductor) {
    echo json_encode(['success' => false, 'message' => 'Conductor no encontrado.']);
    exit();
}

$cond_id = $conductor['id'];

if ($accion === 'desactivar') {
    $stmt = $conn->prepare("DELETE FROM ubicaciones WHERE id_conductor = ?");
    $stmt->bind_param('i', $cond_id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $ok]);
    exit();
}

if (!is_numeric($lat) || !is_numeric($lng)) {
    echo json_encode(['success' => false, 'message' => 'Coordenadas invalidas.']);
    exit();
}

$lat = (float) $lat;
$lng = (float) $lng;

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['success' => false, 'message' => 'Coordenadas fuera de rango.']);
    exit();
}

// Insertar nueva ubicación y guarda el historial (no se actualiza, se inserta un nuevo registro cada vez de momento)
$stmt = $conn->prepare("INSERT INTO ubicaciones (id_conductor, latitud, longitud) VALUES (?, ?, ?)");
$stmt->bind_param('idd', $cond_id, $lat, $lng);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
