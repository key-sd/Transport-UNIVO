<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

date_default_timezone_set('America/El_Salvador');
header('Content-Type: application/json; charset=utf-8');

$id     = (int) ($_POST['id']     ?? 0);
$estado = (int) ($_POST['estado'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit;
}

$stmt = $conexion->prepare("UPDATE cronogramas SET estado = ? WHERE id = ?");
$stmt->bind_param('ii', $estado, $id);
$stmt->execute();
$stmt->close();

$msg = $estado === 1 ? 'Cronograma activado correctamente.' : 'Cronograma desactivado correctamente.';
echo json_encode(['success' => true, 'message' => $msg]);