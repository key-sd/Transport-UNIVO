<?php
/*
 ┌──────────────────────────────────────────────────────────────┐
 │  conductores/crear_conductor.php                             │
 │  POST handler — inserta en `usuarios` y `conductores`        │
 │  Devuelve: JSON { success: bool, message: string }           │
 └──────────────────────────────────────────────────────────────┘
*/
require_once $_SERVER['DOCUMENT_ROOT'] . '/Transport-UNIVO/includes/sesion.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Transport-UNIVO/includes/conexion.php';  // → $conn (MySQLi)
solo_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

/* ════════════════════════════════════════════════
   1. RECOGER Y LIMPIAR ENTRADAS
════════════════════════════════════════════════ */
$nombre               = trim($_POST['nombre']               ?? '');
$apellido             = trim($_POST['apellido']             ?? '');
$telefono             = trim($_POST['telefono']             ?? '');
$codigo_universitario = trim($_POST['codigo_universitario'] ?? '');
$password             = $_POST['password']                  ?? '';
$confirmar_pwd        = $_POST['confirmar_password']        ?? '';

/* ════════════════════════════════════════════════
   2. VALIDACIONES EN SERVIDOR
════════════════════════════════════════════════ */
$errores = [];

if (empty($nombre))               $errores[] = 'El nombre es obligatorio.';
if (empty($apellido))             $errores[] = 'El apellido es obligatorio.';
if (empty($telefono))             $errores[] = 'El teléfono es obligatorio.';
if (empty($codigo_universitario)) $errores[] = 'El código universitario es obligatorio.';
if (strlen($password) < 8)        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
if ($password !== $confirmar_pwd) $errores[] = 'Las contraseñas no coinciden.';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

/* ════════════════════════════════════════════════
   3. VERIFICAR QUE EL CÓDIGO NO EXISTA YA
════════════════════════════════════════════════ */
$stmtDup = $conn->prepare(
    "SELECT id FROM usuarios WHERE codigo_universitario = ? LIMIT 1"
);
$stmtDup->bind_param('s', $codigo_universitario);
$stmtDup->execute();
$stmtDup->store_result();

if ($stmtDup->num_rows > 0) {
    $stmtDup->close();
    echo json_encode([
        'success' => false,
        'message' => "El código universitario '{$codigo_universitario}' ya está registrado."
    ]);
    exit;
}
$stmtDup->close();

/* ════════════════════════════════════════════════
   4. HASH DE CONTRASEÑA
   BCRYPT genera ~60 chars → varchar(255) en la BD.
   Si tu columna password_hash es varchar(20) debes
   ejecutar el ALTER TABLE del archivo LEEME.sql
════════════════════════════════════════════════ */
$password_hash = password_hash($password, PASSWORD_BCRYPT);

/* ════════════════════════════════════════════════
   5. OBTENER el rol_id de 'conductor'
   (ya insertado en el seed: id = 3)
   Lo consultamos en lugar de hardcodear el número
   por si cambia en otro entorno.
════════════════════════════════════════════════ */
$stmtRol = $conn->prepare("SELECT id FROM roles WHERE nombre = 'conductor' LIMIT 1");
$stmtRol->execute();
$stmtRol->bind_result($rol_id);
$stmtRol->fetch();
$stmtRol->close();

if (empty($rol_id)) {
    echo json_encode([
        'success' => false,
        'message' => "No se encontró el rol 'conductor' en la base de datos."
    ]);
    exit;
}

/* ════════════════════════════════════════════════
   6. TRANSACCIÓN — INSERT en ambas tablas
════════════════════════════════════════════════ */
$conn->begin_transaction();

try {

    /* ── INSERT usuarios ── */
    $sqlU = "INSERT INTO usuarios (codigo_universitario, password_hash, rol_id)
             VALUES (?, ?, ?)";
    $stmtU = $conn->prepare($sqlU);
    $stmtU->bind_param('ssi', $codigo_universitario, $password_hash, $rol_id);
    $stmtU->execute();
    $usuario_id = $conn->insert_id;   // FK para la siguiente inserción
    $stmtU->close();

    /* ── INSERT conductores ── */
    $sqlC = "INSERT INTO conductores (usuario_id, nombre, apellido, telefono)
             VALUES (?, ?, ?, ?)";
    $stmtC = $conn->prepare($sqlC);
    $stmtC->bind_param('isss', $usuario_id, $nombre, $apellido, $telefono);
    $stmtC->execute();
    $stmtC->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Conductor {$nombre} {$apellido} creado exitosamente."
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('[crear_conductor] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno al guardar. Intente de nuevo o contacte al administrador.'
    ]);
}