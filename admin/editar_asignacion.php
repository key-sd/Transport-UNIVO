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

$asig_id      = intval($_POST['asig_id']      ?? 0);
$id_conductor = intval($_POST['id_conductor'] ?? 0);
$id_unidad    = intval($_POST['id_unidad']    ?? 0);
$fecha_inicio = trim($_POST['fecha_inicio']   ?? date('Y-m-d'));
$fecha_fin    = trim($_POST['fecha_fin']      ?? '') ?: null;
$hoy          = date('Y-m-d');

if ($asig_id <= 0)       { echo json_encode(['success'=>false,'message'=>'ID de asignación inválido.']); exit; }
if ($id_conductor <= 0)  { echo json_encode(['success'=>false,'message'=>'Debes seleccionar un conductor.']); exit; }
if ($id_unidad <= 0)     { echo json_encode(['success'=>false,'message'=>'Debes seleccionar una unidad.']); exit; }
if (empty($fecha_inicio)){ echo json_encode(['success'=>false,'message'=>'La fecha de inicio es obligatoria.']); exit; }
if ($fecha_fin && $fecha_fin < $fecha_inicio) {
    echo json_encode(['success'=>false,'message'=>'La fecha fin no puede ser anterior a la fecha de inicio.']);
    exit;
}

// Obtener id_cronograma + día + hora de la asignación actual
$q = $conn->prepare("
    SELECT ac.id_cronograma, ch.dia_semana, ch.hora_salida
    FROM asignaciones_conductor ac
    INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
    WHERE ac.id = ? AND ac.activo = 1
");
$q->bind_param('i', $asig_id);
$q->execute();
$q->bind_result($id_cronograma, $dia, $hora);
$fetched = $q->fetch();
$q->close();

if (!$fetched || !$id_cronograma) {
    echo json_encode(['success'=>false,'message'=>'La asignación no existe o ya estaba inactiva.']);
    exit;
}

// Verificar conductor activo
$chkC = $conn->prepare("
    SELECT c.id FROM conductores c
    INNER JOIN usuarios u ON u.id = c.usuario_id
    WHERE c.id = ? AND u.estado = 1
");
$chkC->bind_param('i', $id_conductor);
$chkC->execute(); $chkC->store_result();
if ($chkC->num_rows === 0) {
    $chkC->close();
    echo json_encode(['success'=>false,'message'=>'El conductor no existe o está inactivo.']);
    exit;
}
$chkC->close();

// Verificar unidad activa
$chkU = $conn->prepare("SELECT id FROM unidades WHERE id = ? AND estado = 1");
$chkU->bind_param('i', $id_unidad);
$chkU->execute(); $chkU->store_result();
if ($chkU->num_rows === 0) {
    $chkU->close();
    echo json_encode(['success'=>false,'message'=>'La unidad no existe o está inactiva.']);
    exit;
}
$chkU->close();

// Verificar que el conductor no esté activo en otro cronograma del mismo día+hora
$chkConductorHorario = $conn->prepare("
    SELECT ac.id FROM asignaciones_conductor ac
    INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
    WHERE ac.id_conductor = ?
      AND ac.activo = 1
      AND ch.dia_semana = ?
      AND ch.hora_salida = ?
      AND ac.id != ?
");
$chkConductorHorario->bind_param('issi', $id_conductor, $dia, $hora, $asig_id);
$chkConductorHorario->execute(); $chkConductorHorario->store_result();
if ($chkConductorHorario->num_rows > 0) {
    $chkConductorHorario->close();
    echo json_encode(['success'=>false,'message'=>"Este conductor ya está asignado en otro horario el {$dia} a las {$hora}."]);
    exit;
}
$chkConductorHorario->close();

// Verificar que la unidad no esté activa en otro cronograma del mismo día+hora
$chkUnidadHorario = $conn->prepare("
    SELECT ac.id FROM asignaciones_conductor ac
    INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
    WHERE ac.id_unidad = ?
      AND ac.activo = 1
      AND ch.dia_semana = ?
      AND ch.hora_salida = ?
      AND ac.id != ?
");
$chkUnidadHorario->bind_param('issi', $id_unidad, $dia, $hora, $asig_id);
$chkUnidadHorario->execute(); $chkUnidadHorario->store_result();
if ($chkUnidadHorario->num_rows > 0) {
    $chkUnidadHorario->close();
    echo json_encode(['success'=>false,'message'=>"Esta unidad ya está asignada en otro horario el {$dia} a las {$hora}."]);
    exit;
}
$chkUnidadHorario->close();

// Transacción: cerrar actual + crear nueva
$conn->begin_transaction();
try {
    $stmtCerrar = $conn->prepare(
        "UPDATE asignaciones_conductor SET activo = 0, fecha_fin = ? WHERE id = ? AND activo = 1"
    );
    $stmtCerrar->bind_param('si', $hoy, $asig_id);
    $stmtCerrar->execute();
    $stmtCerrar->close();

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
    echo json_encode(['success'=>true,'message'=>'Asignación actualizada correctamente.','nueva_id'=>$nueva_id]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('[editar_asignacion] ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Error interno: ' . $e->getMessage()]);
}