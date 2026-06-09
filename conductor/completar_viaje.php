<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'conductor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit();
}

date_default_timezone_set('America/El_Salvador');
include("../includes/conexion.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

$id_cronograma = intval($_POST['id_cronograma'] ?? 0);
if ($id_cronograma <= 0) {
    echo json_encode(['success' => false, 'message' => 'Cronograma inválido.']);
    exit();
}

$conductor_id = $_SESSION['usuario_id'];

// Buscar el id del conductor
$stmt = $conn->prepare("SELECT id FROM conductores WHERE usuario_id = ?");
$stmt->bind_param('i', $conductor_id);
$stmt->execute();
$conductor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conductor) {
    echo json_encode(['success' => false, 'message' => 'Conductor no encontrado.']);
    exit();
}
$cond_id = $conductor['id'];

// Obtener la asignación activa para este cronograma y conductor
$stmt = $conn->prepare("
    SELECT ac.id AS id_asignacion, ac.id_unidad, ch.hora_salida
    FROM asignaciones_conductor ac
    INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
    WHERE ac.id_cronograma = ? AND ac.id_conductor = ? AND ac.activo = 1
    LIMIT 1
");
$stmt->bind_param('ii', $id_cronograma, $cond_id);
$stmt->execute();
$asignacion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$asignacion) {
    echo json_encode(['success' => false, 'message' => 'No tienes una asignación activa para este viaje.']);
    exit();
}

$id_asignacion      = $asignacion['id_asignacion'];
$fecha              = date('Y-m-d');
$hora_salida_prog   = $asignacion['hora_salida'];

// Insertar o actualizar el viaje como completado
$stmt = $conn->prepare("
    INSERT INTO viajes (id_asignacion, fecha, hora_salida_programada, hora_salida_real, estado_recorrido, estado_unidad)
    VALUES (?, ?, ?, NOW(), 'completado', 'vacio')
    ON DUPLICATE KEY UPDATE
        estado_recorrido = 'completado',
        hora_salida_real = COALESCE(hora_salida_real, NOW())
");
$stmt->bind_param('iss', $id_asignacion, $fecha, $hora_salida_prog);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => '¡Viaje marcado como completado!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el viaje.']);
}
$stmt->close();
$conn->close();
