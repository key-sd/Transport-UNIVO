<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// recoger y limpiar los datos del form
$nombre           = trim($_POST['nombre']           ?? '');
$placa            = strtoupper(trim($_POST['placa'] ?? ''));
$capacidad_maxima = (int) ($_POST['capacidad_maxima'] ?? 0);

// validaciones básicas
$errores = [];
if (empty($nombre))          $errores[] = 'El nombre es obligatorio.';
if (empty($placa))           $errores[] = 'La placa es obligatoria.';
if ($capacidad_maxima <= 0)  $errores[] = 'La capacidad debe ser mayor a 0.';
if ($capacidad_maxima > 100) $errores[] = 'La capacidad no puede superar 100 pasajeros.';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

// verificar que la placa no esté duplicada
$stmtDup = $conn->prepare("SELECT id FROM unidades WHERE placa = ? LIMIT 1");
$stmtDup->bind_param('s', $placa);
$stmtDup->execute();
$stmtDup->store_result();

if ($stmtDup->num_rows > 0) {
    $stmtDup->close();
    echo json_encode(['success' => false, 'message' => "La placa '{$placa}' ya está registrada en el sistema."]);
    exit;
}
$stmtDup->close();

try {
    $stmt = $conn->prepare("INSERT INTO unidades (nombre, placa, capacidad_maxima) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $nombre, $placa, $capacidad_maxima);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => "Unidad '{$nombre}' registrada exitosamente."]);

} catch (Exception $e) {
    error_log('[crear_unidad] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al guardar. Intenta de nuevo.']);
}