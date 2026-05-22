<?php
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Recoge y limpia las entradas
$horario_id  = intval(trim($_POST['horario_id']  ?? 0));
$hora_salida = trim($_POST['hora_salida']         ?? '');

// validaciones
if ($horario_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de horario inválido.']);
    exit;
}

if (empty($hora_salida)) {
    echo json_encode(['success' => false, 'message' => 'La hora de salida es obligatoria.']);
    exit;
}

// validar que la hora esté entre las 5:00 AM y las 6:00 PM
$t   = strtotime($hora_salida);
$min = strtotime('05:00');
$max = strtotime('18:00');

if ($t === false || $t < $min || $t > $max) {
    echo json_encode(['success' => false, 'message' => 'La hora debe estar entre las 5:00 AM y las 6:00 PM.']);
    exit;
}

// recalcular el turno según el rango, igual que en crear_horario.php
$turno = ($t >= strtotime('05:00') && $t < strtotime('12:00')) ? 'Matutino' : 'Vespertino';

// verifica si la hora nueva ya ha sido asignada a otra ruta
$stmtDup = $conn->prepare(
    "SELECT id FROM horas_salida WHERE hora = ? AND id != ? LIMIT 1"
);
$stmtDup->bind_param('si', $hora_salida, $horario_id);
$stmtDup->execute();
$stmtDup->store_result();

if ($stmtDup->num_rows > 0) {
    $stmtDup->close();
    echo json_encode(['success' => false, 'message' => 'La hora seleccionada ya está registrada en otro horario.']);
    exit;
}
$stmtDup->close();

// consulta en la tabla de horarios para actualizar el horario
try {
    $stmt = $conn->prepare(
        "UPDATE horas_salida SET hora = ?, turno = ? WHERE id = ?"
    );
    $stmt->bind_param('ssi', $hora_salida, $turno, $horario_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => "Horario actualizado a las " . date("g:i A", strtotime($hora_salida)) . " ({$turno}) exitosamente."
    ]);

} catch (Exception $e) {
    error_log('[editar_horario] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al actualizar. Intenta de nuevo.']);
}