<?php
/* Reasigna conductor y/o unidad en un horario existente (patrón historial):
      1. Cierra la asignación actual → activo=0, fecha_fin=hoy
      2. Crea una nueva fila sobre el mismo id_cronograma */
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$asig_id      = intval($_POST['asig_id']      ?? 0);
$id_conductor = intval($_POST['id_conductor'] ?? 0);
$id_unidad    = intval($_POST['id_unidad']    ?? 0);
$fecha_inicio = trim($_POST['fecha_inicio']   ?? date('Y-m-d'));
$fecha_fin    = trim($_POST['fecha_fin']      ?? '') ?: null;
$hoy          = date('Y-m-d');

/* Validaciones */
if ($asig_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de asignación inválido.']);
    exit;
}
if ($id_conductor <= 0) {
    echo json_encode(['success' => false, 'message' => 'Debes seleccionar un conductor.']);
    exit;
}
if ($id_unidad <= 0) {
    echo json_encode(['success' => false, 'message' => 'Debes seleccionar una unidad.']);
    exit;
}
if (empty($fecha_inicio)) {
    echo json_encode(['success' => false, 'message' => 'La fecha de inicio es obligatoria.']);
    exit;
}
if ($fecha_fin && $fecha_fin < $fecha_inicio) {
    echo json_encode(['success' => false, 'message' => 'La fecha fin no puede ser anterior a la fecha de inicio.']);
    exit;
}

/*  Obtener id_cronograma de la asignación actual y verificar que esté activa */
$q = $conn->prepare(
    "SELECT id_cronograma FROM asignaciones_conductor WHERE id = ? AND activo = 1"
);
$q->bind_param('i', $asig_id);
$q->execute();
$q->bind_result($id_cronograma);
$fetched = $q->fetch();
$q->close();

if (!$fetched || !$id_cronograma) {
    echo json_encode(['success' => false, 'message' => 'La asignación no existe o ya estaba inactiva.']);
    exit;
}

/* Verificar que conductor y unidad existan y estén activos */
$chkC = $conn->prepare("SELECT id FROM conductores WHERE id = ? AND estado = 1");
$chkC->bind_param('i', $id_conductor);
$chkC->execute();
$chkC->store_result();
if ($chkC->num_rows === 0) {
    $chkC->close();
    echo json_encode(['success' => false, 'message' => 'El conductor seleccionado no existe o está inactivo.']);
    exit;
}
$chkC->close();

$chkU = $conn->prepare("SELECT id FROM unidades WHERE id = ? AND estado = 1");
$chkU->bind_param('i', $id_unidad);
$chkU->execute();
$chkU->store_result();
if ($chkU->num_rows === 0) {
    $chkU->close();
    echo json_encode(['success' => false, 'message' => 'La unidad seleccionada no existe o está inactiva.']);
    exit;
}
$chkU->close();

/* Transacción: cerrar actual + abrir nueva  */
$conn->begin_transaction();
try {
    /* cerrar la asignación actual */
    $stmtCerrar = $conn->prepare(
        "UPDATE asignaciones_conductor
         SET activo = 0, fecha_fin = ?
         WHERE id = ? AND activo = 1"
    );
    $stmtCerrar->bind_param('si', $hoy, $asig_id);
    $stmtCerrar->execute();
    $stmtCerrar->close();

    /* crear la nueva asignación sobre el mismo cronograma */
    $stmtNueva = $conn->prepare(
        "INSERT INTO asignaciones_conductor
         (id_cronograma, id_conductor, id_unidad, fecha_inicio, fecha_fin)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmtNueva->bind_param('iiiss', $id_cronograma, $id_conductor, $id_unidad, $fecha_inicio, $fecha_fin);
    $stmtNueva->execute();
    $nueva_id = $conn->insert_id;
    $stmtNueva->close();

    $conn->commit();

    echo json_encode([
        'success'  => true,
        'message'  => 'Asignación actualizada correctamente.',
        'nueva_id' => $nueva_id,
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('[editar_asignacion] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno. Intenta de nuevo.']);
}