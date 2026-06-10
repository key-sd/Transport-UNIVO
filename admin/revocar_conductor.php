<?php
/*
    accion = 'revocar_bloque'    → cierra TODAS las asignaciones activas del conductor
    accion = 'revocar_una'       → cierra UNA asignación específica
*/
require_once '../includes/sesion.php';
require_once '../includes/conexion.php';
solo_admin();

date_default_timezone_set('America/El_Salvador');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$accion = trim($_POST['accion'] ?? '');
$hoy    = date('Y-m-d');

/* ── REVOCAR TODAS LAS ASIGNACIONES DEL CONDUCTOR ── */
if ($accion === 'revocar_bloque') {
    $conductor_id = intval($_POST['conductor_id'] ?? 0);
    if ($conductor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de conductor inválido.']);
        exit;
    }

    $stmt = $conexion->prepare(
        "UPDATE asignaciones_conductor SET activo = 0, fecha_fin = ? WHERE id_conductor = ? AND activo = 1"
    );
    $stmt->bind_param('si', $hoy, $conductor_id);
    $stmt->execute();
    $afect = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        'success'   => true,
        'liberadas' => $afect,
        'message'   => "Se liberaron {$afect} asignación(es). Ahora puedes desactivar al conductor.",
    ]);
    exit;
}

/* ── REVOCAR UNA ASIGNACIÓN ESPECÍFICA ── */
if ($accion === 'revocar_una') {
    $asig_id = intval($_POST['asig_id'] ?? 0);
    if ($asig_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de asignación inválido.']);
        exit;
    }

    $stmt = $conexion->prepare(
        "UPDATE asignaciones_conductor SET activo = 0, fecha_fin = ? WHERE id = ? AND activo = 1"
    );
    $stmt->bind_param('si', $hoy, $asig_id);
    $stmt->execute();
    $afect = $stmt->affected_rows;
    $stmt->close();

    if ($afect === 0) {
        echo json_encode(['success' => false, 'message' => 'La asignación no existe o ya estaba inactiva.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Asignación liberada correctamente.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);