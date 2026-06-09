<?php
/*
 ┌──────────────────────────────────────────────────────────────┐
 │  includes/sesion.php                                         │
 │  Incluir al inicio de cualquier página protegida.            │
 │  Verifica que haya sesión activa y que el rol sea el         │
 │  esperado. Si no, redirige al login.                         │
 └──────────────────────────────────────────────────────────────┘
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/El_Salvador');
/**
 * Llama esta función en páginas que solo puede ver el admin.
 * Ejemplo de uso al inicio del archivo:
 *   require_once '../includes/sesion.php';
 *   solo_admin();
 */
function solo_admin(): void {
    if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        header('Location: ../login.php');
        exit;
    }
}

/**
 * Para páginas del conductor.
 */
function solo_conductor(): void {
    if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'conductor') {
        header('Location: ../login.php');
        exit;
    }
}

/**
 * Cualquier usuario autenticado.
 */
function autenticado(): void {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ../login.php');
        exit;
    }
}