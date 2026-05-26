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
$ruta  = (int) ($_POST['ruta']  ?? 0);
$horario = (int) ($_POST['horario'] ?? 0);
$dia = (int) ($_POST['dia'] ?? 0);

// validaciones básicas
$errores = [];
if ($ruta  === 0) $errores[] = 'La ruta es obligatoria.';
if ($horario === 0) $errores[] = 'El horario es obligatorio.';
if ($dia === 0) $errores[] = 'El día es obligatorio.';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

// verificar que no exista ya esa combinación origen-destino
$stmtDup = $conn->prepare("SELECT id FROM cronogramas WHERE id_ruta = ? AND id_horario = ? AND dia = ? LIMIT 1");
$stmtDup->bind_param('ii', $ruta, $horario, $dia);
$stmtDup->execute();
$stmtDup->store_result();

if ($stmtDup->num_rows > 0) {
    $stmtDup->close();
    echo json_encode(['success' => false, 'message' => 'Ya existe un cronograma con esa ruta, horario y día.']);
    exit;
}
$stmtDup->close();

try {
    $stmtU = $conn->prepare("INSERT INTO cronogramas (id_ruta, id_horario, dia) VALUES (?, ?, ?)");
    $stmtU->bind_param('ii', $ruta, $horario, $dia);
    $stmtU->execute();
    $stmtU->close();

    echo json_encode(['success' => true, 'message' => 'Cronograma creado exitosamente.']);

} catch (Exception $e) {
    $conn->rollback();
    error_log('[crear_cronograma] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al guardar. Intenta de nuevo.']);
}