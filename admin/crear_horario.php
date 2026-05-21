<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$hora_salida = trim($_POST['hora_salida'] ?? '');

if (empty($hora_salida)) {
    echo json_encode(['success' => false, 'message' => 'La hora de salida es obligatoria.']);
    exit;
}

// validaar el rango de hora de 5am a 6pm
$hora = $_POST['hora_salida'];
$t    = strtotime($hora);
$min  = strtotime('05:00');
$max  = strtotime('18:00');

if ($t < $min || $t > $max) {
    echo json_encode(['success' => false, 'message' => 'La hora debe estar entre las 5:00 AM y las 6:00 PM.']);
    exit();
}

$stmtDup = $conn->prepare("SELECT id FROM horas_salida WHERE hora = ? LIMIT 1");
$stmtDup->bind_param('s', $hora_salida);
$stmtDup->execute();
$stmtDup->store_result();

if ($stmtDup->num_rows > 0) {
    $stmtDup->close();
    echo json_encode(['success' => false, 'message' => "La hora seleccionada ya se encuentra registrada."]);
    exit;
}
$stmtDup->close();

try {
    $stmt = $conn->prepare("INSERT INTO horas_salida (hora) VALUES (?)");
    $stmt->bind_param('s', $hora_salida);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Horario registrado exitosamente.']);
} catch (Exception $e) {
    error_log('[crear_horario] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno en el servidor al intentar guardar.']);
}