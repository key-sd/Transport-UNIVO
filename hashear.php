<?php
/*
hashear.php
   Actualiza las contraseñas en texto plano de los usuarios    
   seed a hashes bcrypt correctos.                             
*/
require_once 'includes/conexion.php';

// usuarios seed con sus contraseñas originales en texto plano
$usuarios = [
    ['codigo' => 'u2026001', 'pass' => 'adminpass'],
    ['codigo' => 'u2026002', 'pass' => 'pasajeropass'],
    ['codigo' => 'u2026003', 'pass' => 'conductorpass'],
];

foreach ($usuarios as $u) {
    $hash = password_hash($u['pass'], PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE usuarios SET password_hash = ? WHERE codigo_universitario = ?");
    $stmt->bind_param('ss', $hash, $u['codigo']);

    if ($stmt->execute()) {
        echo "✅ {$u['codigo']} actualizado correctamente.<br>";
    } else {
        echo "❌ Error al actualizar {$u['codigo']}: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

echo "<br><strong>⚠ Elimina este archivo del servidor ahora.</strong>";