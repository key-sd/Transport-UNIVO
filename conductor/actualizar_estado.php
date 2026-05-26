<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'conductor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit();
}

include("../includes/conexion.php");

header('Content-Type: application/json; charset=utf-8');

$estado    = $_POST['estado']    ?? '';
$capacidad = $_POST['capacidad'] ?? '';

$estados_validos    = ['en_sede', 'proximo_salir', 'en_camino', 'llegando'];
$capacidad_valida   = ['disponible', 'medio_lleno', 'lleno'];

if (!in_array($estado, $estados_validos) || !in_array($capacidad, $capacidad_valida)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit();
}

$conductor_id = $_SESSION['usuario_id'];

// Buscar el id del conductor
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

// Buscar la unidad asignada (por ahora toma la primera activa)
$stmt = $conn->prepare("SELECT id FROM unidades WHERE estado = 1 LIMIT 1");
$stmt->execute();
$unidad = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$unidad) {
    echo json_encode(['success' => false, 'message' => 'No hay unidades disponibles.']);
    exit();
}

$unidad_id = $unidad['id'];

// Insertar o actualizar el estado
$stmt = $conn->prepare("
    INSERT INTO estado_unidad (conductor_id, unidad_id, estado, capacidad)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE estado = VALUES(estado), capacidad = VALUES(capacidad), actualizado_en = CURRENT_TIMESTAMP
");
$stmt->bind_param('iiss', $cond_id, $unidad_id, $estado, $capacidad);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar.']);
}

$stmt->close();