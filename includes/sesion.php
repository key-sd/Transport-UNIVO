<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/El_Salvador');

function solo_admin(): void {
    if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        header("Location: /login.php");
        exit;
    }
}

function solo_conductor(): void {
    if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'conductor') {
        header("Location: /login.php");;
        exit;
    }
}

function autenticado(): void {
    if (empty($_SESSION['usuario_id'])) {
        header("Location: /login.php");
        exit;
    }
}