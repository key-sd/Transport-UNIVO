<?php
session_start();

// protección de ruta por rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /Transport-UNIVO/login.php");
    exit();
}

include("../includes/conexion.php");

// total de conductores — tabla usuarios y roles ya existen
$q = mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id WHERE r.nombre = 'conductor'");
$total_conductores = mysqli_fetch_assoc($q)['total'];

$q = mysqli_query($conexion, "SELECT COUNT(*) as total FROM sedes");
$total_sedes = mysqli_fetch_assoc($q)['total'];

$q = mysqli_query($conexion, "SELECT COUNT(*) as total FROM unidades");
$total_unidades = mysqli_fetch_assoc($q)['total'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Inicio</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Flowbite -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>

    <link rel="stylesheet" href="../css/admin.css">
    <link rel="icon" href="../img/logo-app.png">
</head>
<body>

<div class="admin-wrapper">

    <?php include("../components/sidebar_admin.php"); ?>

    <div class="admin-contenido">

        <?php include("../components/navbar_admin.php"); ?>

        <main class="admin-main">

            <!-- banner -->
            <div class="banner-bienvenida animate__animated animate__fadeInDown">
                <div class="banner-texto">
                    <p class="banner-sub">Bienvenido de vuelta</p>
                    <h2 class="banner-titulo">¡Hola, <?php echo ucfirst($_SESSION['usuario']); ?>!</h2>
                    <p class="banner-desc">Aquí tienes un resumen de lo que está pasando hoy.</p>
                </div>
                <div class="banner-icono">
                    <i class="ri-shield-user-line"></i>
                </div>
                <div class="banner-circulo banner-circulo-1"></div>
                <div class="banner-circulo banner-circulo-2"></div>
            </div>

            <!-- estadísticas -->
            <div class="stats-grid animate__animated animate__fadeInUp">

                <div class="stat-card">
                    <div class="stat-card-top">
                        <p class="stat-label">Conductores</p>
                        <div class="stat-icono" style="background:#0d234615;">
                            <i class="ri-steering-2-line" style="color:#0d2346;"></i>
                        </div>
                    </div>
                    <p class="stat-numero"><?php echo $total_conductores; ?></p>
                    <p class="stat-nota">registrados en el sistema</p>
                </div>

                <div class="stat-card">
                    <div class="stat-card-top">
                        <p class="stat-label">Rutas Disponibles</p>
                        <div class="stat-icono" style="background:#1862a815;">
                            <i class="ri-map-2-line" style="color:#1862a8;"></i>
                        </div>
                    </div>
                    <p class="stat-numero"><?php echo $total_sedes; ?></p>
                    <p class="stat-nota">en operación</p>
                </div>

                <div class="stat-card">
                    <div class="stat-card-top">
                        <p class="stat-label">Total de Unidades</p>
                        <div class="stat-icono" style="background:#22c55e15;">
                            <i class="ri-bus-line" style="color:#16a34a;"></i>
                        </div>
                    </div>
                    <p class="stat-numero"><?php echo $total_unidades; ?></p>
                    <p class="stat-nota">en el sistema</p>
                </div>

            </div>
        </main>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>