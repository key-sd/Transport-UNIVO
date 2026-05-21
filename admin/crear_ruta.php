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
// los campos reciben texto pero la tabla espera IDs enteros
$origen  = (int) ($_POST['origen']  ?? 0);
$destino = (int) ($_POST['destino'] ?? 0);

// validaciones básicas
$errores = [];
if ($origen  === 0) $errores[] = 'El origen es obligatorio.';
if ($destino === 0) $errores[] = 'El destino es obligatorio.';
if ($origen === $destino) $errores[] = 'El origen y destino no pueden ser iguales.';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

// verificar que no exista ya esa combinación origen-destino
$stmtDup = $conn->prepare("SELECT id FROM rutas WHERE id_sede_origen = ? AND id_sede_destino = ? LIMIT 1");
$stmtDup->bind_param('ii', $origen, $destino);
$stmtDup->execute();
$stmtDup->store_result();

if ($stmtDup->num_rows > 0) {
    $stmtDup->close();
    echo json_encode(['success' => false, 'message' => 'Ya existe una ruta con ese origen y destino.']);
    exit;
}
$stmtDup->close();

try {
    $stmtU = $conn->prepare("INSERT INTO rutas (id_sede_origen, id_sede_destino) VALUES (?, ?)");
    $stmtU->bind_param('ii', $origen, $destino); 
    $stmtU->execute();
    $stmtU->close();

    echo json_encode(['success' => true, 'message' => 'Ruta creada exitosamente.']);

} catch (Exception $e) {
    $conn->rollback();
    error_log('[crear_ruta] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al guardar. Intenta de nuevo.']);
}