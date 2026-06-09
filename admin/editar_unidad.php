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

// recoger y limpiar los datos del form
$unidad_id        = (int) ($_POST['unidad_id']       ?? 0);
$nombre           = trim($_POST['nombre']             ?? '');
$placa            = strtoupper(trim($_POST['placa']   ?? ''));
$capacidad_maxima = (int) ($_POST['capacidad_maxima'] ?? 0);

// validaciones básicas
$errores = [];
if ($unidad_id <= 0)         $errores[] = 'ID de unidad inválido.';
if (empty($nombre))          $errores[] = 'El nombre es obligatorio.';
if (empty($placa))           $errores[] = 'La placa es obligatoria.';
if ($capacidad_maxima <= 0)  $errores[] = 'La capacidad debe ser mayor a 0.';
if ($capacidad_maxima > 100) $errores[] = 'La capacidad no puede superar 100 pasajeros.';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

// verificar que la placa no esté en uso por otra unidad distinta
$stmtDup = $conn->prepare("SELECT id FROM unidades WHERE placa = ? AND id != ? LIMIT 1");
$stmtDup->bind_param('si', $placa, $unidad_id);
$stmtDup->execute();
$stmtDup->store_result();

if ($stmtDup->num_rows > 0) {
    $stmtDup->close();
    echo json_encode(['success' => false, 'message' => "La placa '{$placa}' ya está registrada en otra unidad."]);
    exit;
}
$stmtDup->close();

try {
    $stmt = $conn->prepare("UPDATE unidades SET nombre = ?, placa = ?, capacidad_maxima = ? WHERE id = ?");
    $stmt->bind_param('ssii', $nombre, $placa, $capacidad_maxima, $unidad_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => "Unidad '{$nombre}' actualizada exitosamente."]);

} catch (Exception $e) {
    error_log('[editar_unidad] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al actualizar. Intenta de nuevo.']);
}