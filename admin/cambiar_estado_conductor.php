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

$id     = isset($_POST['id'])     ? intval($_POST['id'])     : 0;
$estado = isset($_POST['estado']) ? intval($_POST['estado']) : -1;

if ($id <= 0 || ($estado !== 0 && $estado !== 1)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos para procesar el cambio de estado.']);
    exit;
}

// ── Si se intenta DESACTIVAR, verificar que no tenga asignaciones activas ──
if ($estado === 0) {
    $chk = $conexion->prepare(
        "SELECT COUNT(*) AS total FROM asignaciones_conductor WHERE id_conductor = ? AND activo = 1"
    );
    $chk->bind_param('i', $id);
    $chk->execute();
    $chk->bind_result($total);
    $chk->fetch();
    $chk->close();
    $soloValidar = isset($_POST['solo_validar']);
    
    if ($total > 0) {
        echo json_encode([
            'success'            => false,
            'tiene_asignaciones' => true,
            'total'              => $total,
            'message'            => "Este conductor tiene {$total} asignación(es) activa(s). Debes liberar o reasignar sus horarios antes de desactivarlo.",
        ]);
    exit;
}

if ($soloValidar) {
    echo json_encode([
        'success' => true,
        'message' => 'Validación correcta.'
    ]);
    exit;
}
}

// ── Actualizar estado ──
$stmt = $conexion->prepare("
    UPDATE usuarios u
    INNER JOIN conductores c ON c.usuario_id = u.id
    SET u.estado = ?
    WHERE c.id = ?
");
$stmt->bind_param('ii', $estado, $id);

if ($stmt->execute()) {
    $accion = ($estado === 1) ? 'activado' : 'desactivado';
    echo json_encode(['success' => true, 'message' => "El conductor ha sido {$accion} correctamente."]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado en la base de datos.']);
}

$stmt->close();
$conexion->close();