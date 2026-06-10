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

$conductor_id    = intval($_POST['id_conductor']  ?? 0);
$unidad_id       = intval($_POST['id_unidad']     ?? 0);
$fecha_inicio    = trim($_POST['fecha_inicio']    ?? '');
$fecha_fin       = trim($_POST['fecha_fin']       ?? '') ?: null;
$cronogramas_raw = json_decode($_POST['cronogramas'] ?? '[]', true);

$errores = [];
if ($conductor_id <= 0)      $errores[] = 'Debes seleccionar un conductor.';
if ($unidad_id <= 0)         $errores[] = 'Debes seleccionar una unidad.';
if (empty($fecha_inicio))    $errores[] = 'La fecha de inicio es obligatoria.';
if (empty($cronogramas_raw)) $errores[] = 'Debes seleccionar al menos un horario.';
if ($fecha_fin && $fecha_fin < $fecha_inicio)
    $errores[] = 'La fecha fin no puede ser anterior a la fecha de inicio.';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

$cronograma_ids = array_filter(array_map('intval', $cronogramas_raw), fn($v) => $v > 0);
if (empty($cronograma_ids)) {
    echo json_encode(['success' => false, 'message' => 'IDs de cronograma inválidos.']);
    exit;
}

// Verificar conductor activo
$chk = $conn->prepare("
    SELECT c.id FROM conductores c
    INNER JOIN usuarios u ON u.id = c.usuario_id
    WHERE c.id = ? AND u.estado = 1
");
$chk->bind_param('i', $conductor_id);
$chk->execute(); $chk->store_result();
if ($chk->num_rows === 0) {
    $chk->close();
    echo json_encode(['success' => false, 'message' => 'El conductor no existe o está inactivo.']);
    exit;
}
$chk->close();

// Verificar unidad activa
$chk = $conn->prepare("SELECT id FROM unidades WHERE id = ? AND estado = 1");
$chk->bind_param('i', $unidad_id);
$chk->execute(); $chk->store_result();
if ($chk->num_rows === 0) {
    $chk->close();
    echo json_encode(['success' => false, 'message' => 'La unidad no existe o está inactiva.']);
    exit;
}
$chk->close();

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO asignaciones_conductor
         (id_cronograma, id_conductor, id_unidad, fecha_inicio, fecha_fin)
         VALUES (?, ?, ?, ?, ?)"
    );

    $insertados = 0;
    $omitidos   = [];

    foreach ($cronograma_ids as $id_crono) {

        // Verificar que el cronograma exista y esté activo
        $chk2 = $conn->prepare("SELECT dia_semana, hora_salida FROM cronograma_horarios WHERE id = ? AND estado = 1");
        $chk2->bind_param('i', $id_crono);
        $chk2->execute();
        $chk2->bind_result($dia, $hora);
        $existe = $chk2->fetch();
        $chk2->close();
        if (!$existe) continue;

        // Verificar que el cronograma no tenga ya una asignación activa
        $chkCrono = $conn->prepare(
            "SELECT id FROM asignaciones_conductor WHERE id_cronograma = ? AND activo = 1"
        );
        $chkCrono->bind_param('i', $id_crono);
        $chkCrono->execute(); $chkCrono->store_result();
        if ($chkCrono->num_rows > 0) {
            $chkCrono->close();
            $omitidos[] = "El horario del {$dia} a las {$hora} ya tiene un conductor asignado.";
            continue;
        }
        $chkCrono->close();

        // Verificar que el conductor no esté en otro cronograma activo mismo día+hora
        $chkC = $conn->prepare("
            SELECT ac.id FROM asignaciones_conductor ac
            INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
            WHERE ac.id_conductor = ? AND ac.activo = 1
              AND ch.dia_semana = ? AND ch.hora_salida = ?
        ");
        $chkC->bind_param('iss', $conductor_id, $dia, $hora);
        $chkC->execute(); $chkC->store_result();
        if ($chkC->num_rows > 0) {
            $chkC->close();
            $omitidos[] = "El conductor ya tiene otro horario asignado el {$dia} a las {$hora}.";
            continue;
        }
        $chkC->close();

        // Verificar que la unidad no esté en otro cronograma activo mismo día+hora
        $chkU = $conn->prepare("
            SELECT ac.id FROM asignaciones_conductor ac
            INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
            WHERE ac.id_unidad = ? AND ac.activo = 1
              AND ch.dia_semana = ? AND ch.hora_salida = ?
        ");
        $chkU->bind_param('iss', $unidad_id, $dia, $hora);
        $chkU->execute(); $chkU->store_result();
        if ($chkU->num_rows > 0) {
            $chkU->close();
            $omitidos[] = "La unidad ya está asignada en otro horario el {$dia} a las {$hora}.";
            continue;
        }
        $chkU->close();

        $stmt->bind_param('iiiss', $id_crono, $conductor_id, $unidad_id, $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $insertados++;
    }

    $stmt->close();
    $conn->commit();

    if ($insertados === 0) {
        echo json_encode(['success' => false, 'message' => implode(' ', $omitidos) ?: 'No se pudo crear ninguna asignación.']);
        exit;
    }

    $msg = "Se crearon {$insertados} asignación(es) correctamente.";
    if (!empty($omitidos)) $msg .= ' Omitidos: ' . implode(' ', $omitidos);

    echo json_encode(['success' => true, 'message' => $msg, 'insertados' => $insertados]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('[crear_asignacion] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}