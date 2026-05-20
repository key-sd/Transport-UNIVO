<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Obtener los datos enviados desde el frontend
$id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
$estado = isset($_POST['estado']) ? intval($_POST['estado']) : -1;

// validacion de datos validos
if ($id <= 0 || ($estado !== 0 && $estado !== 1)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos para procesar el cambio de estado.']);
    exit;
}

// Preparar la actualización en la tabla conductores
$stmt = $conn->prepare("UPDATE conductores SET estado = ? WHERE id = ?");
$stmt->bind_param('ii', $estado, $id);

if ($stmt->execute()) {
    $accion = ($estado === 1) ? 'activado' : 'desactivado';
    echo json_encode([
        'success' => true, 
        'message' => "El conductor ha sido {$accion} correctamente."
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'No se pudo actualizar el estado en la base de datos.'
    ]);
}

$stmt->close();
$conn->close();