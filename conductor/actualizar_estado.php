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

$estados_validos  = ['en_sede', 'proximo_salir', 'en_camino', 'llegando'];
$capacidad_valida = ['disponible', 'medio_lleno', 'lleno'];

if (!in_array($estado, $estados_validos) || !in_array($capacidad, $capacidad_valida)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit();
}

$conductor_id = $_SESSION['usuario_id'];

// Buscar el id del conductor en la tabla conductores
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

$fecha = date('Y-m-d');

// ── Buscar la asignación activa más cercana del día que aún no esté completada ──
// Se considera "próxima" solo si faltan 20 minutos o menos, O si ya inició
// (hora_salida <= ahora pero el viaje del día no está marcado como completado)
$stmt = $conn->prepare("
    SELECT
        ac.id            AS id_asignacion,
        ch.hora_salida,
        TIMESTAMPDIFF(MINUTE, CURRENT_TIME(), ch.hora_salida) AS minutos_para_salir,
        v.id             AS viaje_id,
        v.estado_recorrido
    FROM conductores c
    INNER JOIN asignaciones_conductor ac ON ac.id_conductor = c.id AND ac.activo = 1
    INNER JOIN cronograma_horarios ch    ON ch.id = ac.id_cronograma AND ch.dia_semana = ? AND ch.estado = 1
    LEFT  JOIN viajes v                  ON v.id_asignacion = ac.id AND v.fecha = ?
    WHERE c.id = ?
      AND (
            -- Viaje aún no existe en la tabla viajes (nunca se guardó estado)
            v.id IS NULL
            OR
            -- Viaje existe pero no está completado
            v.estado_recorrido != 'completado'
      )
    ORDER BY ch.hora_salida ASC
    LIMIT 1
");
$stmt->bind_param('ssi', $hoy_dia_semana, $fecha, $cond_id);
$stmt->execute();
$crono = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── Validación: debe existir una ruta y faltar 20 minutos o menos para salir ──
// También se permite si el viaje ya inició (minutos negativos = ya pasó la hora)
if (!$crono) {
    echo json_encode([
        'success' => false,
        'message' => 'No tienes viajes programados para hoy.'
    ]);
    exit();
}

$minutos = (int) $crono['minutos_para_salir'];

// Si faltan más de 20 minutos, no se permite actualizar el estado
if ($minutos > 20) {
    $tiempo_texto = $minutos >= 60
        ? floor($minutos / 60) . 'h ' . ($minutos % 60) . 'min'
        : $minutos . ' minutos';

    echo json_encode([
        'success' => false,
        'message' => "Tu próxima salida es en {$tiempo_texto}. Solo puedes actualizar el estado 20 minutos antes de salir."
    ]);
    exit();
}

$id_asignacion          = $crono['id_asignacion'];
$hora_salida_programada = $crono['hora_salida'];
$estado_unidad_db       = ($capacidad === 'disponible') ? 'vacio' : $capacidad;

// ── Determinar si el viaje debe marcarse como completado ──
// El conductor llegó al destino al seleccionar "en sede destino" (llegando)
$estado_final = ($estado === 'llegando') ? 'completado' : $estado;

// ── Insertar o actualizar el estado del viaje ──
$stmt = $conn->prepare("
    INSERT INTO viajes (id_asignacion, fecha, hora_salida_programada, estado_recorrido, estado_unidad)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        estado_recorrido = VALUES(estado_recorrido),
        estado_unidad    = VALUES(estado_unidad)
");
$stmt->bind_param('issss', $id_asignacion, $fecha, $hora_salida_programada, $estado_final, $estado_unidad_db);

if ($stmt->execute()) {
    // Mensaje personalizado según si el viaje quedó completado o no
    $mensaje = ($estado_final === 'completado')
        ? 'Viaje finalizado. El sistema buscará tu siguiente ruta.'
        : 'Estado actualizado correctamente.';

    echo json_encode(['success' => true, 'message' => $mensaje]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el estado.']);
}

$stmt->close();