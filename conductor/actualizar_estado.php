<?php
session_start();
date_default_timezone_set('America/El_Salvador');

include("../includes/conexion.php");
header('Content-Type: application/json; charset=utf-8');

$usuario_sesion_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_sesion_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit();
}

$estado        = $_POST['estado']        ?? '';
$capacidad     = $_POST['capacidad']     ?? '';
$id_asignacion = $_POST['id_asignacion'] ?? null;

$estados_validos  = ['en_sede', 'proximo_salir', 'en_camino', 'llegando', 'cancelado'];
$capacidad_valida = ['disponible', 'medio_lleno', 'lleno'];

if (!in_array($estado, $estados_validos, true) || !in_array($capacidad, $capacidad_valida, true)) {
    echo json_encode(['success' => false, 'message' => 'Datos invalidos.']);
    exit();
}

$stmt = $conn->prepare("SELECT id FROM conductores WHERE usuario_id = ?");
$stmt->bind_param('i', $usuario_sesion_id);
$stmt->execute();
$conductor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conductor) {
    http_response_code(403);
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

$viajeActivo = null;

if ($id_asignacion) {
    $stmt = $conn->prepare("
        SELECT ac.id AS id_asignacion, ch.hora_salida, v.estado_recorrido
        FROM conductores c
        INNER JOIN asignaciones_conductor ac ON ac.id_conductor = c.id AND ac.activo = 1
        INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
            AND ch.dia_semana = ? AND ch.estado = 1
        INNER JOIN viajes v ON v.id_asignacion = ac.id AND v.fecha = ?
        WHERE c.id = ? AND ac.id = ?
          AND v.estado_recorrido NOT IN ('completado', 'cancelado')
        LIMIT 1
    ");
    $stmt->bind_param('ssii', $hoy_dia_semana, $fecha, $cond_id, $id_asignacion);
} else {
    $stmt = $conn->prepare("
        SELECT ac.id AS id_asignacion, ch.hora_salida, v.estado_recorrido
        FROM conductores c
        INNER JOIN asignaciones_conductor ac ON ac.id_conductor = c.id AND ac.activo = 1
        INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
            AND ch.dia_semana = ? AND ch.estado = 1
        INNER JOIN viajes v ON v.id_asignacion = ac.id AND v.fecha = ?
        WHERE c.id = ?
          AND v.estado_recorrido NOT IN ('completado', 'cancelado')
        ORDER BY ch.hora_salida ASC
        LIMIT 1
    ");
    $stmt->bind_param('ssi', $hoy_dia_semana, $fecha, $cond_id);
}

$stmt->execute();
$viajeActivo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($viajeActivo) {
    $crono = $viajeActivo;
} else {
    $proximoPendiente = null;

    if ($id_asignacion) {
        $stmt = $conn->prepare("
            SELECT ac.id AS id_asignacion, ch.hora_salida
            FROM conductores c
            INNER JOIN asignaciones_conductor ac ON ac.id_conductor = c.id AND ac.activo = 1
            INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
                AND ch.dia_semana = ? AND ch.estado = 1
            LEFT JOIN viajes v ON v.id_asignacion = ac.id AND v.fecha = ?
            WHERE c.id = ? AND ac.id = ?
              AND v.id IS NULL
            LIMIT 1
        ");
        $stmt->bind_param('ssii', $hoy_dia_semana, $fecha, $cond_id, $id_asignacion);
    } else {
        $stmt = $conn->prepare("
            SELECT ac.id AS id_asignacion, ch.hora_salida
            FROM conductores c
            INNER JOIN asignaciones_conductor ac ON ac.id_conductor = c.id AND ac.activo = 1
            INNER JOIN cronograma_horarios ch ON ch.id = ac.id_cronograma
                AND ch.dia_semana = ? AND ch.estado = 1
            LEFT JOIN viajes v ON v.id_asignacion = ac.id AND v.fecha = ?
            WHERE c.id = ?
              AND v.id IS NULL
            ORDER BY ch.hora_salida ASC
            LIMIT 1
        ");
        $stmt->bind_param('ssi', $hoy_dia_semana, $fecha, $cond_id);
    }

    $stmt->execute();
    $proximoPendiente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$proximoPendiente) {
        echo json_encode(['success' => false, 'message' => 'No tienes viajes pendientes para hoy.']);
        exit();
    }

    $ahora_ts  = time();
    $salida_ts = strtotime($fecha . ' ' . $proximoPendiente['hora_salida']);
    $minutos   = (int) round(($salida_ts - $ahora_ts) / 60);

    if ($minutos > 20) {
        echo json_encode(['success' => false, 'message' => 'No puedes editar el estado en este momento.']);
        exit();
    }

    if ($estado !== 'en_sede' && $estado !== 'cancelado') {
        echo json_encode(['success' => false, 'message' => 'Para iniciar el viaje selecciona primero el estado "En sede".']);
        exit();
    }

    $crono = $proximoPendiente;
}

$id_asignacion          = $crono['id_asignacion'];
$hora_salida_programada = $crono['hora_salida'];
$estado_unidad_db       = ($capacidad === 'disponible') ? 'vacio' : $capacidad;
$estado_final           = ($estado === 'llegando') ? 'completado' : $estado;

$stmt = $conn->prepare("
    INSERT INTO viajes (id_asignacion, fecha, hora_salida_programada, estado_recorrido, estado_unidad)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        estado_recorrido = VALUES(estado_recorrido),
        estado_unidad = VALUES(estado_unidad)
");
$stmt->bind_param('issss', $id_asignacion, $fecha, $hora_salida_programada, $estado_final, $estado_unidad_db);

if ($stmt->execute()) {
    if ($estado_final === 'completado') {
        $mensaje = 'Viaje finalizado, el sistema buscará tu siguiente ruta.';
    } elseif ($estado_final === 'cancelado') {
        $mensaje = 'Viaje cancelado exitosamente, los pasajeros verán el aviso.';
    } else {
        $mensaje = 'Estado actualizado correctamente.';
    }
    echo json_encode(['success' => true, 'message' => $mensaje]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el estado.']);
}

$stmt->close();