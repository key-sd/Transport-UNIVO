<?php
/*accion = 'desactivar_bloque'     → cierra una asignación específica
accion = 'desactivar_conductor'  → cierra TODAS las asignaciones activas del conductor
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

/* DESACTIVAR UN BLOQUE */
if ($accion === 'desactivar_bloque') {
    $asig_id = intval($_POST['asig_id'] ?? 0);
    if ($asig_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de asignación inválido.']);
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE asignaciones_conductor SET activo = 0, fecha_fin = ? WHERE id = ? AND activo = 1"
    );
    $stmt->bind_param('si', $hoy, $asig_id);
    $stmt->execute();
    $afect = $stmt->affected_rows;
    $stmt->close();

    if ($afect === 0) {
        echo json_encode(['success' => false, 'message' => 'La asignación no existe o ya estaba inactiva.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Asignación desactivada correctamente.']);
    }
    exit;
}

/* DESACTIVAR CONDUCTOR (todos sus bloques activos) */
if ($accion === 'desactivar_conductor') {
    $conductor_id = intval($_POST['conductor_id'] ?? 0);
    if ($conductor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de conductor inválido.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            "UPDATE asignaciones_conductor SET activo = 0, fecha_fin = ? WHERE id_conductor = ? AND activo = 1"
        );
        $stmt->bind_param('si', $hoy, $conductor_id);
        $stmt->execute();
        $stmt->close();

        $stmt2 = $conn->prepare("UPDATE conductores SET estado = 0 WHERE id = ?");
        $stmt2->bind_param('i', $conductor_id);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Conductor desactivado y asignaciones cerradas.']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log('[desactivar_conductor] ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);