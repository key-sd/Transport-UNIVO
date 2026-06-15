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

$asig_ids_raw = json_decode($_POST['asig_ids'] ?? '[]', true);
$id_conductor = intval($_POST['id_conductor'] ?? 0);
$id_unidad    = intval($_POST['id_unidad']    ?? 0);
$fecha_inicio = trim($_POST['fecha_inicio']   ?? date('Y-m-d'));
$fecha_fin    = trim($_POST['fecha_fin']      ?? '') ?: null;
$hoy          = date('Y-m-d');

// ── Validaciones básicas ──
if (empty($asig_ids_raw))   { echo json_encode(['success'=>false,'message'=>'Selecciona al menos una asignación.']); exit; }
if ($id_conductor <= 0)     { echo json_encode(['success'=>false,'message'=>'Debes seleccionar un conductor.']); exit; }
if ($id_unidad <= 0)        { echo json_encode(['success'=>false,'message'=>'Debes seleccionar una unidad.']); exit; }
if (empty($fecha_inicio))   { echo json_encode(['success'=>false,'message'=>'La fecha de inicio es obligatoria.']); exit; }
if ($fecha_fin && $fecha_fin < $fecha_inicio) {
    echo json_encode(['success'=>false,'message'=>'La fecha fin no puede ser anterior a la fecha de inicio.']);
    exit;
}

if ($fecha_inicio < $hoy) {
    echo json_encode(['success' => false, 'message' => 'La fecha de inicio no puede ser anterior a hoy.']);
    exit;
}

$asig_ids = array_filter(array_map('intval', $asig_ids_raw), fn($v) => $v > 0);
if (empty($asig_ids)) {
    echo json_encode(['success'=>false,'message'=>'IDs de asignación inválidos.']);
    exit;
}

// ── Verificar conductor activo ──
$chkC = $conexion->prepare("
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

// ── Verificar unidad activa ──
$chkU = $conexion->prepare("SELECT id FROM unidades WHERE id = ? AND estado = 1");
$chkU->bind_param('i', $id_unidad);
$chkU->execute(); $chkU->store_result();
if ($chkU->num_rows === 0) {
    $chkU->close();
    echo json_encode(['success'=>false,'message'=>'La unidad no existe o está inactiva.']);
    exit;
}
$chkU->close();

$conexion->begin_transaction();
try {
    $procesados = 0;
    $omitidos   = [];

    foreach ($asig_ids as $asig_id) {
        // Obtener datos de la asignación actual
        $q = $conexion->prepare("
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
            $omitidos[] = "Asignación #{$asig_id} no encontrada o ya inactiva.";
            continue;
        }

        // Verificar conflicto conductor en mismo día+hora (excluyendo la asig actual)
        $chkConf = $conexion->prepare("
            SELECT ac.id FROM asignaciones_conductor ac
            INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
            WHERE ac.id_conductor = ? AND ac.activo = 1
              AND ch.dia_semana = ? AND ch.hora_salida = ?
              AND ac.id != ?
        ");
        $chkConf->bind_param('issi', $id_conductor, $dia, $hora, $asig_id);
        $chkConf->execute(); $chkConf->store_result();
        if ($chkConf->num_rows > 0) {
            $chkConf->close();
            $omitidos[] = "El conductor ya tiene otro horario el {$dia} a las {$hora}.";
            continue;
        }
        $chkConf->close();

        // Verificar conflicto unidad en mismo día+hora (excluyendo la asig actual)
        $chkUConf = $conexion->prepare("
            SELECT ac.id FROM asignaciones_conductor ac
            INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
            WHERE ac.id_unidad = ? AND ac.activo = 1
              AND ch.dia_semana = ? AND ch.hora_salida = ?
              AND ac.id != ?
        ");
        $chkUConf->bind_param('issi', $id_unidad, $dia, $hora, $asig_id);
        $chkUConf->execute(); $chkUConf->store_result();
        if ($chkUConf->num_rows > 0) {
            $chkUConf->close();
            $omitidos[] = "La unidad ya está asignada el {$dia} a las {$hora}.";
            continue;
        }
        $chkUConf->close();

        // Cerrar asignación actual
        $stmtCerrar = $conexion->prepare(
            "UPDATE asignaciones_conductor SET activo = 0, fecha_fin = ? WHERE id = ? AND activo = 1"
        );
        $stmtCerrar->bind_param('si', $hoy, $asig_id);
        $stmtCerrar->execute();
        $stmtCerrar->close();

        // Crear nueva asignación
        $stmtNueva = $conexion->prepare(
            "INSERT INTO asignaciones_conductor
             (id_cronograma, id_conductor, id_unidad, fecha_inicio, fecha_fin)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmtNueva->bind_param('iiiss', $id_cronograma, $id_conductor, $id_unidad, $fecha_inicio, $fecha_fin);
        $stmtNueva->execute();
        $stmtNueva->close();

        $procesados++;
    }

    $conexion->commit();

    if ($procesados === 0) {
        echo json_encode(['success' => false, 'message' => implode(' ', $omitidos) ?: 'No se pudo reasignar ninguna asignación.']);
        exit;
    }

    $msg = "Se reasignaron {$procesados} horario(s) correctamente.";
    if (!empty($omitidos)) $msg .= ' Omitidos: ' . implode(' ', $omitidos);

    echo json_encode(['success' => true, 'message' => $msg, 'procesados' => $procesados]);

} catch (Exception $e) {
    $conexion->rollback();
    error_log('[reasignar_bloque] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}