<?php
/*Es para crear una asignación de conductor y unidad a uno o más cronogramas existentes.*/
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$conductor_id   = intval($_POST['id_conductor']  ?? 0);
$unidad_id      = intval($_POST['id_unidad']     ?? 0);
$fecha_inicio   = trim($_POST['fecha_inicio']    ?? '');
$fecha_fin      = trim($_POST['fecha_fin']       ?? '') ?: null;
$cronogramas_raw = json_decode($_POST['cronogramas'] ?? '[]', true);

/*Validaciones básicas */
$errores = [];
if ($conductor_id <= 0)     $errores[] = 'Debes seleccionar un conductor.';
if ($unidad_id <= 0)        $errores[] = 'Debes seleccionar una unidad.';
if (empty($fecha_inicio))   $errores[] = 'La fecha de inicio es obligatoria.';
if (empty($cronogramas_raw))$errores[] = 'Debes seleccionar al menos un horario.';
if ($fecha_fin && $fecha_fin < $fecha_inicio)
    $errores[] = 'La fecha fin no puede ser anterior a la fecha de inicio.';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

$cronograma_ids = array_map('intval', $cronogramas_raw);
$cronograma_ids = array_filter($cronograma_ids, fn($v) => $v > 0);

if (empty($cronograma_ids)) {
    echo json_encode(['success' => false, 'message' => 'IDs de cronograma inválidos.']);
    exit;
}

/*Verificar que conductor y unidad existan y estén activos */
$chk = $conn->prepare("SELECT id FROM conductores WHERE id = ? AND estado = 1");
$chk->bind_param('i', $conductor_id);
$chk->execute(); $chk->store_result();
if ($chk->num_rows === 0) {
    $chk->close();
    echo json_encode(['success' => false, 'message' => 'El conductor no existe o está inactivo.']);
    exit;
}
$chk->close();

$chk = $conn->prepare("SELECT id FROM unidades WHERE id = ? AND estado = 1");
$chk->bind_param('i', $unidad_id);
$chk->execute(); $chk->store_result();
if ($chk->num_rows === 0) {
    $chk->close();
    echo json_encode(['success' => false, 'message' => 'La unidad no existe o está inactiva.']);
    exit;
}
$chk->close();

/*Transacción */
$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO asignaciones_conductor
         (id_cronograma, id_conductor, id_unidad, fecha_inicio, fecha_fin)
         VALUES (?, ?, ?, ?, ?)"
    );

    $insertados  = 0;
    $duplicados  = 0;

    foreach ($cronograma_ids as $id_crono) {
        /* verificar que el cronograma exista */
        $chk2 = $conn->prepare("SELECT id FROM cronograma_horarios WHERE id = ? AND estado = 1");
        $chk2->bind_param('i', $id_crono);
        $chk2->execute(); $chk2->store_result();
        $existe = $chk2->num_rows > 0;
        $chk2->close();
        if (!$existe) continue;

        /* verificar duplicado (mismo cronograma + conductor + fecha_inicio) */
        $chkDup = $conn->prepare(
            "SELECT id FROM asignaciones_conductor
             WHERE id_cronograma = ? AND id_conductor = ? AND fecha_inicio = ? AND activo = 1"
        );
        $chkDup->bind_param('iis', $id_crono, $conductor_id, $fecha_inicio);
        $chkDup->execute(); $chkDup->store_result();
        $esDup = $chkDup->num_rows > 0;
        $chkDup->close();

        if ($esDup) { $duplicados++; continue; }

        $stmt->bind_param('iiiss', $id_crono, $conductor_id, $unidad_id, $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $insertados++;
    }

    $stmt->close();
    $conn->commit();

    if ($insertados === 0 && $duplicados > 0) {
        echo json_encode(['success' => false, 'message' => 'Todos los horarios seleccionados ya tienen este conductor asignado.']);
        exit;
    }

    $msg = "Se crearon {$insertados} asignación(es) correctamente.";
    if ($duplicados > 0) $msg .= " ({$duplicados} omitidas por duplicado)";

    echo json_encode(['success' => true, 'message' => $msg, 'insertados' => $insertados]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('[crear_asignacion] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno. Intenta de nuevo.']);
}
