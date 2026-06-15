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

// recoger y limpiar los datos del form
$nombre               = trim($_POST['nombre']               ?? '');
$apellido             = trim($_POST['apellido']             ?? '');
$telefono             = trim($_POST['telefono']             ?? '');
$codigo = trim($_POST['codigo'] ?? '');
$password             = $_POST['password']                  ?? '';
$confirmar_pwd        = $_POST['confirmar_password']        ?? '';

// validaciones básicas
$errores = [];
if (empty($nombre))               $errores[] = 'El nombre es obligatorio.';
if (empty($apellido))             $errores[] = 'El apellido es obligatorio.';
if (empty($telefono))             $errores[] = 'El teléfono es obligatorio.';
if (empty($codigo)) $errores[] = 'El código es obligatorio.';
if (strlen($password) < 8)        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
if ($password !== $confirmar_pwd) $errores[] = 'Las contraseñas no coinciden.';

// solo permite letras, espacios y tildes en nombre y apellidos
if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/', $nombre))
    $errores[] = 'El nombre solo puede contener letras.';

if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/', $apellido))
    $errores[] = 'El apellido solo puede contener letras.';

// Código formato c0000
if (!preg_match('/^c\d{4}$/', $codigo))
    $errores[] = 'El código debe tener el formato c0000 (letra c seguida de 4 dígitos).';

// numero de telefono salvadoreño
if (!preg_match('/^[267]\d{3}-?\d{4}$/', $telefono))
    $errores[] = 'El teléfono debe ser un número salvadoreño válido (ej. 7000-1234).';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}
// verificar que el código universitario no esté en uso
$stmtDup = $conn->prepare("SELECT id FROM usuarios WHERE codigo = ? LIMIT 1");
$stmtDup->bind_param('s', $codigo);
$stmtDup->execute();
$stmtDup->store_result();


if ($stmtDup->num_rows > 0) {
    $stmtDup->close();
    echo json_encode(['success' => false, 'message' => "El código '{$codigo}' ya está registrado."]);
    exit;
}
$stmtDup->close();

// cifrar la contraseña
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// obtener el id del rol conductor desde la BD
$stmtRol = $conn->prepare("SELECT id FROM roles WHERE nombre = 'conductor' LIMIT 1");
$stmtRol->execute();
$stmtRol->bind_result($rol_id);
$stmtRol->fetch();
$stmtRol->close();

if (empty($rol_id)) {
    echo json_encode(['success' => false, 'message' => "No se encontró el rol 'conductor' en la base de datos."]);
    exit;
}

// insertar en usuarios y conductores dentro de una transacción
$conn->begin_transaction();

try {

    $stmtU = $conn->prepare("INSERT INTO usuarios (codigo, password_hash, rol_id) VALUES (?, ?, ?)");
    $stmtU->bind_param('ssi', $codigo, $password_hash, $rol_id);
    $stmtU->execute();
    $usuario_id = $conn->insert_id;
    $stmtU->close();

    $stmtC = $conn->prepare("INSERT INTO conductores (usuario_id, nombre, apellido, telefono) VALUES (?, ?, ?, ?)");
    $stmtC->bind_param('isss', $usuario_id, $nombre, $apellido, $telefono);
    $stmtC->execute();
    $stmtC->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Conductor {$nombre} {$apellido} creado exitosamente."]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('[crear_conductor] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al guardar. Intenta de nuevo.']);
}