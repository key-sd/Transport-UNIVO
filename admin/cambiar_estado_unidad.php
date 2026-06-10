<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

date_default_timezone_set('America/El_Salvador');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// recoger los datos enviados por el frontend
$id     = (int) ($_POST['id']     ?? 0);
$estado = (int) ($_POST['estado'] ?? -1);

// validaciones básicas
if ($id <= 0 || !in_array($estado, [0, 1])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

try {
    $stmt = $conexion->prepare("UPDATE unidades SET estado = ? WHERE id = ?");
    $stmt->bind_param('ii', $estado, $id);
    $stmt->execute();
    $stmt->close();

    $mensaje = $estado === 1 ? 'Unidad activada exitosamente.' : 'Unidad desactivada exitosamente.';
    echo json_encode(['success' => true, 'message' => $mensaje]);

} catch (Exception $e) {
    error_log('[cambiar_estado_unidad] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al actualizar el estado.']);
}