<?php
session_start();

// Zona horaria local — debe ir antes de cualquier uso de date()/time()
date_default_timezone_set('America/El_Salvador');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'conductor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit();
}

include("../includes/conexion.php");
header('Content-Type: application/json; charset=utf-8');

$estado        = $_POST['estado']        ?? '';
$capacidad     = $_POST['capacidad']     ?? '';
$id_asignacion = $_POST['id_asignacion'] ?? null;

$estados_validos  = ['en_sede', 'proximo_salir', 'en_camino', 'llegando'];
$capacidad_valida = ['disponible', 'medio_lleno', 'lleno'];

if (!in_array($estado, $estados_validos) || !in_array($capacidad, $capacidad_valida)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit();
}

$conductor_id = $_SESSION['usuario_id'];

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

// date() ya usa America/El_Salvador por el timezone seteado arriba
$hoy_dia_semana = [
    1 => 'Lunes',   2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
    5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
][date('N')];

$fecha = date('Y-m-d');

// ══════════════════════════════════════════════════════════════════════════════
// PASO 1: ¿hay un viaje activo hoy? (iniciado pero no completado)
// Si lo hay → el conductor lo está operando, permitir actualizar SIN validar tiempo.
// ══════════════════════════════════════════════════════════════════════════════
$viajeActivo = null;
if ($id_asignacion) {
    // PASO 1 con id_asignacion específica
    $stmt = $conn->prepare("
        SELECT
            ac.id       AS id_asignacion,
            ch.hora_salida,
            v.estado_recorrido
        FROM conductores c
        INNER JOIN asignaciones_conductor ac ON ac.id_conductor = c.id AND ac.activo = 1
        INNER JOIN cronograma_horarios ch    ON ch.id = ac.id_cronograma
                                            AND ch.dia_semana = ? AND ch.estado = 1
        INNER JOIN viajes v                  ON v.id_asignacion = ac.id AND v.fecha = ?
        WHERE c.id = ? AND ac.id = ?
          AND v.estado_recorrido NOT IN ('completado')
        LIMIT 1
    ");
    $stmt->bind_param('ssii', $hoy_dia_semana, $fecha, $cond_id, $id_asignacion);
    $stmt->execute();
    $viajeActivo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    // Fallback: PASO 1 original (el primer viaje activo del día)
    $stmt = $conn->prepare("
        SELECT
            ac.id       AS id_asignacion,
            ch.hora_salida,
            v.estado_recorrido
        FROM conductores c
        INNER JOIN asignaciones_conductor ac ON ac.id_conductor = c.id AND ac.activo = 1
        INNER JOIN cronograma_horarios ch    ON ch.id = ac.id_cronograma
                                            AND ch.dia_semana = ? AND ch.estado = 1
        INNER JOIN viajes v                  ON v.id_asignacion = ac.id AND v.fecha = ?
        WHERE c.id = ?
          AND v.estado_recorrido NOT IN ('completado')
        ORDER BY ch.hora_salida ASC
        LIMIT 1
    ");
    $stmt->bind_param('ssi', $hoy_dia_semana, $fecha, $cond_id);
    $stmt->execute();
    $viajeActivo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($viajeActivo) {
    $crono = $viajeActivo;

} else {
    // ══════════════════════════════════════════════════════════════════════════
    // PASO 2: sin viaje activo → buscar el viaje PENDIENTE.
    // ══════════════════════════════════════════════════════════════════════════
    $proximoPendiente = null;
    if ($id_asignacion) {
        // Buscar esta asignación pendiente específica
        $stmt = $conn->prepare("
            SELECT
                ac.id       AS id_asignacion,
                ch.hora_salida
            FROM conductores c
            INNER JOIN asignaciones_conductor ac ON ac.id_conductor = c.id AND ac.activo = 1
            INNER JOIN cronograma_horarios ch    ON ch.id = ac.id_cronograma
                                                AND ch.dia_semana = ? AND ch.estado = 1
            LEFT  JOIN viajes v                  ON v.id_asignacion = ac.id AND v.fecha = ?
            WHERE c.id = ? AND ac.id = ?
              AND v.id IS NULL
            LIMIT 1
        ");
        $stmt->bind_param('ssii', $hoy_dia_semana, $fecha, $cond_id, $id_asignacion);
        $stmt->execute();
        $proximoPendiente = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        // Fallback: PASO 2 original (el primer viaje pendiente del día)
        $stmt = $conn->prepare("
            SELECT
                ac.id       AS id_asignacion,
                ch.hora_salida
            FROM conductores c
            INNER JOIN asignaciones_conductor ac ON ac.id_conductor = c.id AND ac.activo = 1
            INNER JOIN cronograma_horarios ch    ON ch.id = ac.id_cronograma
                                                AND ch.dia_semana = ? AND ch.estado = 1
            LEFT  JOIN viajes v                  ON v.id_asignacion = ac.id AND v.fecha = ?
            WHERE c.id = ?
              AND v.id IS NULL
            ORDER BY ch.hora_salida ASC
            LIMIT 1
        ");
        $stmt->bind_param('ssi', $hoy_dia_semana, $fecha, $cond_id);
        $stmt->execute();
        $proximoPendiente = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$proximoPendiente) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes viajes pendientes para hoy.'
        ]);
        exit();
    }

    // ── Cálculo de minutos 100% en PHP con timezone correcto ─────────────────
    // time()      → timestamp actual en America/El_Salvador
    // strtotime() → timestamp de la hora programada en el mismo día/timezone
    $ahora_ts  = time();
    $salida_ts = strtotime($fecha . ' ' . $proximoPendiente['hora_salida']);
    $minutos   = (int) round(($salida_ts - $ahora_ts) / 60);

    // BLOQUEADO:  faltan MÁS de 20 minutos
    // PERMITIDO:  faltan 20 min o menos ($minutos <= 20)
    //             o la hora ya pasó ($minutos < 0) → viaje atrasado
    if ($minutos > 20) {
        if ($minutos >= 60) {
            $h = floor($minutos / 60);
            $m = $minutos % 60;
            $tiempo_texto = $m > 0 ? "{$h}h {$m}min" : "{$h}h";
        } else {
            $tiempo_texto = "{$minutos} minutos";
        }

        echo json_encode([
            'success' => false,
            'message' => 'No puedes editar el estado en este momento.'
        ]);
        exit();
    }

    // El primer estado al iniciar un viaje nuevo DEBE ser 'en_sede'
    if ($estado !== 'en_sede') {
        echo json_encode([
            'success' => false,
            'message' => 'Para iniciar el viaje selecciona primero el estado "En sede".'
        ]);
        exit();
    }

    $crono = $proximoPendiente;
}

// ══════════════════════════════════════════════════════════════════════════════
// PASO 3: guardar el estado en la tabla viajes
// ══════════════════════════════════════════════════════════════════════════════
$id_asignacion          = $crono['id_asignacion'];
$hora_salida_programada = $crono['hora_salida'];
$estado_unidad_db       = ($capacidad === 'disponible') ? 'vacio' : $capacidad;

$estado_final = ($estado === 'llegando') ? 'completado' : $estado;

$stmt = $conn->prepare("
    INSERT INTO viajes (id_asignacion, fecha, hora_salida_programada, estado_recorrido, estado_unidad)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        estado_recorrido = VALUES(estado_recorrido),
        estado_unidad    = VALUES(estado_unidad)
");
$stmt->bind_param('issss', $id_asignacion, $fecha, $hora_salida_programada, $estado_final, $estado_unidad_db);

if ($stmt->execute()) {
    $mensaje = ($estado_final === 'completado')
        ? '¡Viaje finalizado! El sistema buscará tu siguiente ruta.'
        : 'Estado actualizado correctamente.';
    echo json_encode(['success' => true, 'message' => $mensaje]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el estado.']);
}

$stmt->close();