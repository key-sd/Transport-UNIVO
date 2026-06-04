<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'conductor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit();
}

include("../includes/conexion.php");

header('Content-Type: application/json; charset=utf-8');

$estado    = $_POST['estado']    ?? '';
$capacidad = $_POST['capacidad'] ?? '';

$estados_validos    = ['en_sede', 'proximo_salir', 'en_camino', 'llegando'];
$capacidad_valida   = ['disponible', 'medio_lleno', 'lleno'];

if (!in_array($estado, $estados_validos) || !in_array($capacidad, $capacidad_valida)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
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

$hoy_dia_semana = [
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo'
][date('N')];

$stmt = $conn->prepare("
    SELECT ac.id AS id_asignacion, ch.hora_salida
    FROM conductores c
    INNER JOIN asignaciones_conductor ac ON ac.id_conductor = c.id
    INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
    WHERE c.id = ? AND ac.activo = 1 AND ch.dia_semana = ? AND ch.estado = 1
    ORDER BY ABS(TIME_TO_SEC(ch.hora_salida) - TIME_TO_SEC(CURRENT_TIME())) ASC
    LIMIT 1
");
$stmt->bind_param('is', $cond_id, $hoy_dia_semana);
$stmt->execute();
$crono = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$crono) {
    echo json_encode(['success' => false, 'message' => 'No tienes viajes programados para hoy en este momento.']);
    exit();
}

$id_asignacion = $crono['id_asignacion'];
$fecha = date('Y-m-d');
$hora_salida_programada = $crono['hora_salida'];
$estado_unidad_db = ($capacidad === 'disponible') ? 'vacio' : $capacidad;

// Insertar o actualizar el estado del viaje
$stmt = $conn->prepare("
    INSERT INTO viajes (id_asignacion, fecha, hora_salida_programada, estado_recorrido, estado_unidad)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        estado_recorrido = VALUES(estado_recorrido), 
        estado_unidad = VALUES(estado_unidad)
");
$stmt->bind_param('issss', $id_asignacion, $fecha, $hora_salida_programada, $estado, $estado_unidad_db);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el estado.']);
}

$stmt->close();