<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Recoger y limpiar los datos del formulario mandados por FormData
// Los selects envían el ID numérico de cada registro
$ruta    = (int) ($_POST['ruta']    ?? 0);
$horario = (int) ($_POST['horario'] ?? 0);
$dia     = (int) ($_POST['dia']     ?? 0);

// Validaciones básicas
$errores = [];
if ($ruta    === 0) $errores[] = 'La ruta es obligatoria.';
if ($horario === 0) $errores[] = 'El horario es obligatorio.';
if ($dia     === 0) $errores[] = 'El día es obligatorio.';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

// Verificar que no exista ya esa combinación exacta en la tabla cronogramas
$stmtDup = $conn->prepare("SELECT id FROM cronogramas WHERE id_ruta = ? AND id_horario = ? AND id_dia = ? LIMIT 1");
$stmtDup->bind_param('iii', $ruta, $horario, $dia);
$stmtDup->execute();
$stmtDup->store_result();

if ($stmtDup->num_rows > 0) {
    $stmtDup->close();
    echo json_encode(['success' => false, 'message' => 'Ya existe un cronograma con esa ruta, horario y día asignados.']);
    exit;
}
$stmtDup->close();

try {
    // Inserción directa de los identificadores relacionales
    $stmtU = $conn->prepare("INSERT INTO cronogramas (id_ruta, id_horario, id_dia) VALUES (?, ?, ?)");
    $stmtU->bind_param('iii', $ruta, $horario, $dia);
    $stmtU->execute();
    $stmtU->close();

    echo json_encode(['success' => true, 'message' => 'Cronograma creado exitosamente.']);

} catch (Exception $e) {
    error_log('[crear_cronograma] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al guardar el cronograma. Intenta de nuevo.']);
}